<?php

declare(strict_types=1);

namespace app\common\service;

use app\common\model\auth\Admin;
use app\common\model\crm\SalesAdvisor;
use app\common\model\notification\Notification;

/** 工作人员业务通知使用已验证关联的平台账号。 */
class InternalNotificationService
{
    public static function userIds(array $adminIds): array
    {
        $adminIds = array_values(array_filter(array_map('intval', $adminIds)));
        if (!$adminIds) {
            return [];
        }
        return array_values(array_unique(array_filter(array_map('intval',
            Admin::whereIn('id', $adminIds)->where('disable', 0)->column('user_id')
        ))));
    }

    public static function advisor(int $advisorId, string $title, string $content, string $businessType, int $businessId, array $options = []): bool
    {
        $advisor = SalesAdvisor::find($advisorId);
        return $advisor && (int)$advisor->status !== SalesAdvisor::STATUS_LEAVE
            ? self::send([(int) $advisor->admin_id], $title, $content, $businessType, $businessId, $options) : false;
    }

    public static function send(array $adminIds, string $title, string $content, string $businessType, int $businessId, array $options = []): bool
    {
        $ids = self::userIds($adminIds);
        foreach ($ids ?: [0] as $userId) {
            BusinessNotificationService::record([
                'event' => $options['event'] ?? $businessType, 'instance' => $options['instance'] ?? (string)$businessId,
                'user_id' => $userId, 'audience' => str_starts_with($businessType, 'crm_') ? 'advisor' : 'admin',
                'title' => $title, 'content' => $content, 'notify_type' => Notification::TYPE_ORDER,
                'target_type' => 'admin_business', 'target_id' => $businessId,
                'business_type' => $businessType, 'business_id' => $businessId, 'scene' => 'staff_internal',
                'page' => 'packages/pages/notification/index', 'options' => $options + ['admin_ids' => $adminIds],
            ]);
        }
        return !empty($ids);
    }
}
