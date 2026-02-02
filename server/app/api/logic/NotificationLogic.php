<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 小程序端消息通知逻辑层
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\api\logic;

use app\common\logic\BaseLogic;
use app\common\model\notification\Notification;

/**
 * 小程序端消息通知逻辑层
 * Class NotificationLogic
 * @package app\api\logic
 */
class NotificationLogic extends BaseLogic
{
    /**
     * @notes 消息列表
     * @param array $params
     * @return array
     */
    public static function lists(array $params): array
    {
        $page = (int)($params['page'] ?? 1);
        $limit = (int)($params['limit'] ?? 10);
        $notifyType = (int)($params['notify_type'] ?? 0);

        $where = [
            ['user_id', '=', $params['user_id']],
        ];

        if ($notifyType > 0) {
            $where[] = ['notify_type', '=', $notifyType];
        }

        $total = Notification::where($where)->count();

        $lists = Notification::where($where)
            ->order('create_time desc')
            ->page($page, $limit)
            ->select()
            ->toArray();

        foreach ($lists as &$item) {
            $item['notify_type_text'] = self::getTypeText($item['notify_type']);
            $item['is_read_text'] = $item['is_read'] ? '已读' : '未读';
            $item['create_time_text'] = self::formatTime($item['create_time']);
        }

        return [
            'lists' => $lists,
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
            'has_more' => $total > $page * $limit,
        ];
    }

    /**
     * @notes 消息详情
     * @param int $id
     * @param int $userId
     * @return array|false
     */
    public static function detail(int $id, int $userId)
    {
        $notification = Notification::where('id', $id)
            ->where('user_id', $userId)
            ->find();

        if (!$notification) {
            self::setError('消息不存在');
            return false;
        }

        // 标记已读
        if (!$notification->is_read) {
            $notification->save([
                'is_read' => 1,
                'read_time' => time(),
            ]);
        }

        $data = $notification->toArray();
        $data['notify_type_text'] = self::getTypeText($data['notify_type']);
        $data['is_read_text'] = '已读';
        $data['create_time_text'] = date('Y-m-d H:i:s', $data['create_time']);

        return $data;
    }

    /**
     * @notes 未读数量
     * @param int $userId
     * @return array
     */
    public static function unreadCount(int $userId): array
    {
        return Notification::getUnreadCountByType($userId);
    }

    /**
     * @notes 标记已读
     * @param int $id
     * @param int $userId
     * @return bool
     */
    public static function markRead(int $id, int $userId): bool
    {
        $result = Notification::markRead($id, $userId);
        if (!$result) {
            self::setError('消息不存在或已读');
            return false;
        }
        return true;
    }

    /**
     * @notes 全部标记已读
     * @param int $userId
     * @param int $notifyType
     * @return int
     */
    public static function markAllRead(int $userId, int $notifyType = 0): int
    {
        return Notification::markAllRead($userId, $notifyType);
    }

    /**
     * @notes 删除消息
     * @param int $id
     * @param int $userId
     * @return bool
     */
    public static function delete(int $id, int $userId): bool
    {
        $result = Notification::deleteNotification($id, $userId);
        if (!$result) {
            self::setError('消息不存在');
            return false;
        }
        return true;
    }

    /**
     * @notes 清空消息
     * @param int $userId
     * @param int $notifyType
     * @return int
     */
    public static function clear(int $userId, int $notifyType = 0): int
    {
        return Notification::clearNotifications($userId, $notifyType);
    }

    /**
     * @notes 获取类型文本
     * @param int $type
     * @return string
     */
    private static function getTypeText(int $type): string
    {
        $map = [
            Notification::TYPE_SYSTEM => '系统通知',
            Notification::TYPE_ORDER => '订单通知',
            Notification::TYPE_INTERACT => '互动通知',
            Notification::TYPE_ACTIVITY => '活动通知',
        ];
        return $map[$type] ?? '未知';
    }

    /**
     * @notes 格式化时间
     * @param int $timestamp
     * @return string
     */
    private static function formatTime(int $timestamp): string
    {
        $diff = time() - $timestamp;

        if ($diff < 60) {
            return '刚刚';
        } elseif ($diff < 3600) {
            return floor($diff / 60) . '分钟前';
        } elseif ($diff < 86400) {
            return floor($diff / 3600) . '小时前';
        } elseif ($diff < 604800) {
            return floor($diff / 86400) . '天前';
        } else {
            return date('Y-m-d', $timestamp);
        }
    }
}
