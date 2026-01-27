<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 消息通知管理逻辑层
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\adminapi\logic\notification;

use app\common\logic\BaseLogic;
use app\common\model\notification\Notification;
use app\common\model\user\User;
use think\facade\Db;

/**
 * 消息通知管理逻辑层
 * Class NotificationLogic
 * @package app\adminapi\logic\notification
 */
class NotificationLogic extends BaseLogic
{
    /**
     * @notes 通知详情
     * @param int $id
     * @return array
     */
    public static function detail(int $id): array
    {
        $notification = Notification::find($id);
        if (!$notification) {
            return [];
        }

        $data = $notification->toArray();
        $data['notify_type_text'] = self::getTypeText($data['notify_type']);
        $data['is_read_text'] = $data['is_read'] ? '已读' : '未读';
        $data['create_time_text'] = date('Y-m-d H:i:s', $data['create_time']);
        $data['read_time_text'] = $data['read_time'] ? date('Y-m-d H:i:s', $data['read_time']) : '-';

        // 获取接收者信息
        $user = User::field('id,nickname,avatar,mobile')->find($data['user_id']);
        $data['user'] = $user ? $user->toArray() : null;

        return $data;
    }

    /**
     * @notes 发送通知（单个用户）
     * @param array $params
     * @return bool
     */
    public static function send(array $params): bool
    {
        try {
            $userId = (int)$params['user_id'];
            $user = User::find($userId);
            if (!$user) {
                self::setError('用户不存在');
                return false;
            }

            // 类型转换 - PHP 8.0+ 严格类型检查
            $notifyType = (int)$params['notify_type'];
            $targetId = (int)($params['target_id'] ?? 0);

            Notification::send(
                $userId,
                $notifyType,
                $params['title'],
                $params['content'],
                $params['target_type'] ?? '',
                $targetId
            );

            return true;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * @notes 批量发送通知
     * @param array $params
     * @return bool
     */
    public static function batchSend(array $params): bool
    {
        try {
            $userIds = $params['user_ids'] ?? [];
            if (empty($userIds)) {
                self::setError('请选择用户');
                return false;
            }

            // 确保用户ID都是整数
            $userIds = array_map('intval', $userIds);

            // 验证用户存在
            $existUserIds = User::whereIn('id', $userIds)->column('id');
            if (empty($existUserIds)) {
                self::setError('未找到有效用户');
                return false;
            }

            // 类型转换 - PHP 8.0+ 严格类型检查
            $notifyType = (int)$params['notify_type'];
            $targetId = (int)($params['target_id'] ?? 0);

            Notification::batchSend(
                $existUserIds,
                $notifyType,
                $params['title'],
                $params['content'],
                $params['target_type'] ?? '',
                $targetId
            );

            return true;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * @notes 全员通知
     * @param array $params
     * @return bool
     */
    public static function sendToAll(array $params): bool
    {
        try {
            // 获取所有用户ID（软删除会自动过滤已删除的用户）
            $userIds = User::column('id');
            if (empty($userIds)) {
                self::setError('暂无用户');
                return false;
            }

            // 类型转换 - PHP 8.0+ 严格类型检查
            $notifyType = (int)$params['notify_type'];
            $targetId = (int)($params['target_id'] ?? 0);

            // 分批发送，每批1000条
            $chunks = array_chunk($userIds, 1000);
            $totalSent = 0;
            foreach ($chunks as $chunk) {
                $sent = Notification::batchSend(
                    $chunk,
                    $notifyType,
                    $params['title'],
                    $params['content'],
                    $params['target_type'] ?? '',
                    $targetId
                );
                $totalSent += $sent;
            }

            if ($totalSent === 0) {
                self::setError('发送失败，未能成功发送任何通知');
                return false;
            }

            return true;
        } catch (\Exception $e) {
            self::setError('发送失败：' . $e->getMessage());
            return false;
        }
    }

    /**
     * @notes 删除通知
     * @param int $id
     * @return bool
     */
    public static function delete(int $id): bool
    {
        try {
            Notification::where('id', $id)->delete();
            return true;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * @notes 批量删除通知
     * @param array $ids
     * @return bool
     */
    public static function batchDelete(array $ids): bool
    {
        try {
            Notification::whereIn('id', $ids)->delete();
            return true;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * @notes 通知统计
     * @param array $params
     * @return array
     */
    public static function statistics(array $params): array
    {
        $where = [];
        if (!empty($params['start_date']) && !empty($params['end_date'])) {
            $where[] = ['create_time', '>=', strtotime($params['start_date'])];
            $where[] = ['create_time', '<=', strtotime($params['end_date'] . ' 23:59:59')];
        }

        // 总通知数
        $totalCount = Notification::where($where)->count();

        // 已读数
        $readCount = Notification::where($where)->where('is_read', 1)->count();

        // 未读数
        $unreadCount = Notification::where($where)->where('is_read', 0)->count();

        // 阅读率
        $readRate = $totalCount > 0 ? round($readCount / $totalCount * 100, 2) : 0;

        // 按类型统计
        $typeStats = [];
        foreach (self::getTypeOptions() as $type => $name) {
            $count = Notification::where($where)->where('notify_type', $type)->count();
            $typeStats[] = [
                'type' => $type,
                'name' => $name,
                'count' => $count,
            ];
        }

        // 今日发送
        $todayStart = strtotime('today');
        $todayCount = Notification::where('create_time', '>=', $todayStart)->count();

        // 本周发送
        $weekStart = strtotime('monday this week');
        $weekCount = Notification::where('create_time', '>=', $weekStart)->count();

        return [
            'total_count' => $totalCount,
            'read_count' => $readCount,
            'unread_count' => $unreadCount,
            'read_rate' => $readRate . '%',
            'today_count' => $todayCount,
            'week_count' => $weekCount,
            'type_stats' => $typeStats,
        ];
    }

    /**
     * @notes 获取通知类型选项
     * @return array
     */
    public static function typeOptions(): array
    {
        $options = [];
        foreach (self::getTypeOptions() as $value => $label) {
            $options[] = [
                'value' => $value,
                'label' => $label,
            ];
        }
        return $options;
    }

    /**
     * @notes 通知模板列表
     * @return array
     */
    public static function templates(): array
    {
        return [
            [
                'id' => 1,
                'name' => '订单确认通知',
                'type' => Notification::TYPE_ORDER,
                'title' => '您的订单已确认',
                'content' => '尊敬的客户，您的订单{order_sn}已确认，服务日期为{service_date}，请做好准备。',
                'variables' => ['order_sn', 'service_date'],
            ],
            [
                'id' => 2,
                'name' => '服务提醒通知',
                'type' => Notification::TYPE_ORDER,
                'title' => '服务即将开始',
                'content' => '您预约的{service_name}服务将于{service_date}开始，服务人员{staff_name}将为您服务。',
                'variables' => ['service_name', 'service_date', 'staff_name'],
            ],
            [
                'id' => 3,
                'name' => '评价邀请通知',
                'type' => Notification::TYPE_SYSTEM,
                'title' => '邀请您评价服务',
                'content' => '您的订单{order_sn}已完成，请对本次服务进行评价，您的反馈对我们很重要！',
                'variables' => ['order_sn'],
            ],
            [
                'id' => 4,
                'name' => '优惠券到账通知',
                'type' => Notification::TYPE_ACTIVITY,
                'title' => '优惠券到账通知',
                'content' => '恭喜您获得{coupon_name}优惠券，面额{amount}元，有效期至{expire_date}，快去使用吧！',
                'variables' => ['coupon_name', 'amount', 'expire_date'],
            ],
            [
                'id' => 5,
                'name' => '系统公告通知',
                'type' => Notification::TYPE_SYSTEM,
                'title' => '系统公告',
                'content' => '{content}',
                'variables' => ['content'],
            ],
        ];
    }

    /**
     * @notes 发送趋势统计
     * @param array $params
     * @return array
     */
    public static function sendTrend(array $params): array
    {
        $days = $params['days'] ?? 30;
        $startTime = strtotime("-{$days} days");

        $result = [];
        for ($i = $days - 1; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime("-{$i} days"));
            $dayStart = strtotime($date);
            $dayEnd = strtotime($date . ' 23:59:59');

            $sendCount = Notification::where('create_time', '>=', $dayStart)
                ->where('create_time', '<=', $dayEnd)
                ->count();

            $readCount = Notification::where('create_time', '>=', $dayStart)
                ->where('create_time', '<=', $dayEnd)
                ->where('is_read', 1)
                ->count();

            $result[] = [
                'date' => $date,
                'send_count' => $sendCount,
                'read_count' => $readCount,
            ];
        }

        return $result;
    }

    /**
     * @notes 获取类型选项
     * @return array
     */
    private static function getTypeOptions(): array
    {
        return [
            Notification::TYPE_SYSTEM => '系统通知',
            Notification::TYPE_ORDER => '订单通知',
            Notification::TYPE_INTERACT => '互动通知',
            Notification::TYPE_ACTIVITY => '活动通知',
        ];
    }

    /**
     * @notes 获取类型文本
     * @param int $type
     * @return string
     */
    private static function getTypeText(int $type): string
    {
        $options = self::getTypeOptions();
        return $options[$type] ?? '未知';
    }
}
