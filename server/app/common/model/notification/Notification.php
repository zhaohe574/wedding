<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 消息通知模型
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\common\model\notification;

use app\common\model\BaseModel;

/**
 * 消息通知模型
 * Class Notification
 * @package app\common\model\notification
 */
class Notification extends BaseModel
{
    protected $name = 'notification';

    // 通知类型
    const TYPE_SYSTEM = 1;      // 系统通知
    const TYPE_ORDER = 2;       // 订单通知
    const TYPE_INTERACT = 3;    // 互动通知
    const TYPE_ACTIVITY = 4;    // 活动通知

    /**
     * @notes 通知类型描述获取器
     * @param $value
     * @param $data
     * @return string
     */
    public function getNotifyTypeDescAttr($value, $data): string
    {
        $map = [
            self::TYPE_SYSTEM => '系统通知',
            self::TYPE_ORDER => '订单通知',
            self::TYPE_INTERACT => '互动通知',
            self::TYPE_ACTIVITY => '活动通知',
        ];
        return $map[$data['notify_type']] ?? '未知';
    }

    /**
     * @notes 发送通知
     * @param int $userId
     * @param int $notifyType
     * @param string $title
     * @param string $content
     * @param string $targetType
     * @param int $targetId
     * @param int $senderId
     * @return Notification
     */
    public static function send(int $userId, int $notifyType, string $title, string $content, string $targetType = '', int $targetId = 0, int $senderId = 0): Notification
    {
        return self::create([
            'user_id' => $userId,
            'sender_id' => $senderId,
            'notify_type' => $notifyType,
            'title' => $title,
            'content' => $content,
            'target_type' => $targetType,
            'target_id' => $targetId,
            'is_read' => 0,
            'create_time' => time(),
        ]);
    }

    /**
     * @notes 批量发送通知
     * @param array $userIds
     * @param int $notifyType
     * @param string $title
     * @param string $content
     * @param string $targetType
     * @param int $targetId
     * @return int 发送数量
     */
    public static function batchSend(array $userIds, int $notifyType, string $title, string $content, string $targetType = '', int $targetId = 0): int
    {
        $data = [];
        $time = time();
        foreach ($userIds as $userId) {
            $data[] = [
                'user_id' => $userId,
                'sender_id' => 0,
                'notify_type' => $notifyType,
                'title' => $title,
                'content' => $content,
                'target_type' => $targetType,
                'target_id' => $targetId,
                'is_read' => 0,
                'create_time' => $time,
            ];
        }
        
        return (new self())->saveAll($data) ? count($data) : 0;
    }

    /**
     * @notes 标记已读
     * @param int $notificationId
     * @param int $userId
     * @return bool
     */
    public static function markRead(int $notificationId, int $userId): bool
    {
        return self::where('id', $notificationId)
            ->where('user_id', $userId)
            ->update([
                'is_read' => 1,
                'read_time' => time(),
            ]) > 0;
    }

    /**
     * @notes 全部标记已读
     * @param int $userId
     * @param int $notifyType
     * @return int
     */
    public static function markAllRead(int $userId, int $notifyType = 0): int
    {
        $query = self::where('user_id', $userId)
            ->where('is_read', 0);
        
        if ($notifyType > 0) {
            $query->where('notify_type', $notifyType);
        }

        return $query->update([
            'is_read' => 1,
            'read_time' => time(),
        ]);
    }

    /**
     * @notes 获取用户通知列表
     * @param int $userId
     * @param int $notifyType
     * @param array $params
     * @return array
     */
    public static function getUserNotifications(int $userId, int $notifyType = 0, array $params = []): array
    {
        $query = self::where('user_id', $userId);
        
        if ($notifyType > 0) {
            $query->where('notify_type', $notifyType);
        }

        return $query->order('create_time', 'desc')
            ->paginate($params['page_size'] ?? 20)
            ->toArray();
    }

    /**
     * @notes 获取未读数量
     * @param int $userId
     * @param int $notifyType
     * @return int
     */
    public static function getUnreadCount(int $userId, int $notifyType = 0): int
    {
        $query = self::where('user_id', $userId)
            ->where('is_read', 0);
        
        if ($notifyType > 0) {
            $query->where('notify_type', $notifyType);
        }

        return $query->count();
    }

    /**
     * @notes 获取各类型未读数量
     * @param int $userId
     * @return array
     */
    public static function getUnreadCountByType(int $userId): array
    {
        $result = self::where('user_id', $userId)
            ->where('is_read', 0)
            ->field('notify_type, count(*) as count')
            ->group('notify_type')
            ->select()
            ->toArray();

        $counts = [
            'total' => 0,
            'system' => 0,
            'order' => 0,
            'interact' => 0,
            'activity' => 0,
        ];

        foreach ($result as $item) {
            $counts['total'] += $item['count'];
            switch ($item['notify_type']) {
                case self::TYPE_SYSTEM:
                    $counts['system'] = $item['count'];
                    break;
                case self::TYPE_ORDER:
                    $counts['order'] = $item['count'];
                    break;
                case self::TYPE_INTERACT:
                    $counts['interact'] = $item['count'];
                    break;
                case self::TYPE_ACTIVITY:
                    $counts['activity'] = $item['count'];
                    break;
            }
        }

        return $counts;
    }

    /**
     * @notes 删除通知
     * @param int $notificationId
     * @param int $userId
     * @return bool
     */
    public static function deleteNotification(int $notificationId, int $userId): bool
    {
        return self::where('id', $notificationId)
            ->where('user_id', $userId)
            ->delete() > 0;
    }

    /**
     * @notes 清空通知
     * @param int $userId
     * @param int $notifyType
     * @return int
     */
    public static function clearNotifications(int $userId, int $notifyType = 0): int
    {
        $query = self::where('user_id', $userId);
        
        if ($notifyType > 0) {
            $query->where('notify_type', $notifyType);
        }

        return $query->delete();
    }
}
