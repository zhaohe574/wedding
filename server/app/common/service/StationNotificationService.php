<?php
declare(strict_types=1);

namespace app\common\service;

use app\common\model\notification\Notification;

class StationNotificationService
{
    public const TARGET_ORDER_DETAIL = 'order_detail';
    public const TARGET_STAFF_ORDER = 'staff_order';
    public const TARGET_WAITLIST = 'waitlist';
    public const TARGET_CHANGE = 'change';
    public const TARGET_PAUSE = 'pause';
    public const TARGET_TICKET_DETAIL = 'ticket_detail';
    public const TARGET_REVIEW_LIST = 'review_list';
    public const TARGET_REVIEW_DETAIL = 'review_detail';
    public const TARGET_DYNAMIC_DETAIL = 'dynamic_detail';
    public const TARGET_STAFF_DETAIL = 'staff_detail';
    public const TARGET_COUPLE_QUESTIONNAIRE = 'couple_questionnaire';
    public const TARGET_ACTIVITY_REGISTRATION = 'activity_registration';
    public const TARGET_STAFF_SETTLEMENT = 'staff_settlement';


    /** 兼容业务调用入口；事件身份由调用方明确提供。 */
    public static function send(int $userId, int $notifyType, string $title, string $content,
        string $targetType = '', int $targetId = 0, int $senderId = 0, array $options = []): bool
    {
        $target = self::target($targetType);
        $event = $options['event'] ?? 'station_message';
        $instance = $options['instance'] ?? bin2hex(random_bytes(16));
        return BusinessNotificationService::record([
            'event' => $event, 'instance' => (string)$instance, 'user_id' => $userId,
            'audience' => $options['audience'] ?? $target['audience'],
            'notify_type' => $notifyType, 'title' => $title, 'content' => $content,
            'target_type' => $targetType, 'target_id' => $targetId, 'sender_id' => $senderId,
            'scene' => $options['scene'] ?? $target['scene'],
            'business_type' => $options['business_type'] ?? $target['business_type'],
            'business_id' => $options['business_id'] ?? $targetId,
            'page' => $target['page'] ? $target['page'] . '?id=' . $targetId : '',
            'data' => $options['data'] ?? [],
            'options' => $options,
        ]);
    }

    public static function batchSend(array $userIds, int $notifyType, string $title, string $content,
        string $targetType = '', int $targetId = 0, int $excludeUserId = 0, array $options = []): int
    {
        $count = 0;
        foreach (self::normalizeUserIds($userIds, $excludeUserId) as $id) {
            $count += (int)self::send($id, $notifyType, $title, $content, $targetType, $targetId, 0, $options);
        }
        return $count;
    }

    public static function sendUnique(int $userId, int $notifyType, string $title, string $content,
        string $targetType = '', int $targetId = 0, int $senderId = 0, array $options = []): bool
    {
        if (empty($options['event']) || !isset($options['instance'])) {
            throw new \InvalidArgumentException('一次性提醒必须提供业务事件和实例编号');
        }
        return self::send($userId, $notifyType, $title, $content, $targetType, $targetId, $senderId, $options);
    }

    public static function target(string $type): array
    {
        $targets = [
            self::TARGET_ORDER_DETAIL => ['order', 'order_update', 'order_detail/order_detail', 'user'],
            self::TARGET_STAFF_ORDER => ['order', '', 'staff_order_detail/staff_order_detail', 'staff'],
            self::TARGET_CHANGE => ['change', 'change_result', 'order_change/change_detail', 'user'],
            self::TARGET_PAUSE => ['pause', 'order_update', 'order_change/pause_detail', 'user'],
            self::TARGET_TICKET_DETAIL => ['ticket', 'ticket_update', 'aftersale/ticket_detail', 'user'],
            self::TARGET_COUPLE_QUESTIONNAIRE => ['questionnaire', 'questionnaire_update', 'couple_questionnaire/detail', 'user'],
            self::TARGET_ACTIVITY_REGISTRATION => ['activity', 'activity_update', 'activity_registration/detail', 'user'],
            self::TARGET_STAFF_SETTLEMENT => ['settlement', 'settlement_update', 'staff_settlement/staff_settlement', 'staff'],
            self::TARGET_WAITLIST => ['waitlist', '', 'waitlist/list', 'user'],
        ];
        [$business, $scene, $page, $audience] = $targets[$type] ?? ['', '', '', 'user'];
        return ['business_type' => $business, 'scene' => $scene, 'page' => $page ? 'packages/pages/' . $page : '', 'audience' => $audience];
    }

    public static function normalizeUserIds(array $userIds, int $excludeUserId = 0): array
    {
        return array_values(array_unique(array_filter(array_map('intval', $userIds),
            static fn(int $id) => $id > 0 && $id !== $excludeUserId)));
    }
}
