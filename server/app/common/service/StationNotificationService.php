<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 站内消息通用服务
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\common\service;

use app\common\model\notification\Notification;
use think\facade\Log;

/**
 * 统一处理站内消息发送、去重和跳转目标常量。
 */
class StationNotificationService
{
    public const TARGET_ORDER_DETAIL = 'order_detail';
    public const TARGET_CONFIRM_LETTER_ORDER = 'confirm_letter_order';
    public const TARGET_STAFF_ORDER = 'staff_order';
    public const TARGET_WAITLIST = 'waitlist';
    public const TARGET_CHANGE = 'change';
    public const TARGET_PAUSE = 'pause';
    public const TARGET_TICKET_DETAIL = 'ticket_detail';
    public const TARGET_REVIEW_LIST = 'review_list';
    public const TARGET_REVIEW_DETAIL = 'review_detail';
    public const TARGET_DYNAMIC_DETAIL = 'dynamic_detail';
    public const TARGET_STAFF_DETAIL = 'staff_detail';

    /**
     * 安全发送单条站内消息。
     */
    public static function send(
        int $userId,
        int $notifyType,
        string $title,
        string $content,
        string $targetType = '',
        int $targetId = 0,
        int $senderId = 0
    ): void {
        if ($userId <= 0) {
            return;
        }

        try {
            Notification::send($userId, $notifyType, $title, $content, $targetType, $targetId, $senderId);
        } catch (\Throwable $e) {
            Log::error('发送站内消息失败：' . $e->getMessage());
        }
    }

    /**
     * 安全批量发送站内消息。
     */
    public static function batchSend(
        array $userIds,
        int $notifyType,
        string $title,
        string $content,
        string $targetType = '',
        int $targetId = 0,
        int $excludeUserId = 0
    ): void {
        $userIds = self::normalizeUserIds($userIds, $excludeUserId);
        if (empty($userIds)) {
            return;
        }

        try {
            Notification::batchSend($userIds, $notifyType, $title, $content, $targetType, $targetId);
        } catch (\Throwable $e) {
            Log::error('批量发送站内消息失败：' . $e->getMessage());
        }
    }

    /**
     * 按完整文案做一次性提醒去重。
     */
    public static function sendUnique(
        int $userId,
        int $notifyType,
        string $title,
        string $content,
        string $targetType = '',
        int $targetId = 0,
        int $senderId = 0
    ): bool {
        if ($userId <= 0) {
            return false;
        }

        $exists = Notification::where('user_id', $userId)
            ->where('notify_type', $notifyType)
            ->where('target_type', $targetType)
            ->where('target_id', $targetId)
            ->where('title', $title)
            ->where('content', $content)
            ->find();

        if ($exists) {
            return false;
        }

        self::send($userId, $notifyType, $title, $content, $targetType, $targetId, $senderId);
        return true;
    }

    /**
     * 归一化接收用户列表。
     */
    public static function normalizeUserIds(array $userIds, int $excludeUserId = 0): array
    {
        $normalized = array_values(array_unique(array_filter(array_map('intval', $userIds), static function (int $userId) use ($excludeUserId) {
            return $userId > 0 && $userId !== $excludeUserId;
        })));

        return $normalized;
    }
}
