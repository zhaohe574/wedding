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

    /**
     * @notes 顾问通知（支持返回真实投递明细及即时派发）
     */
    public static function advisorDispatch(
        int $advisorId,
        string $title,
        string $content,
        string $businessType,
        int $businessId,
        array $options = [],
        bool $immediate = false
    ): array {
        $advisor = SalesAdvisor::find($advisorId);
        if (!$advisor || (int)$advisor->status === SalesAdvisor::STATUS_LEAVE) {
            return ['ok' => false, 'duplicate' => false, 'station_sent' => false, 'oa_sent' => false, 'oa_msg' => '顾问不存在或已离职', 'error' => '顾问不存在或已离职'];
        }

        $adminIds = [(int)$advisor->admin_id];
        $ids = self::userIds($adminIds);
        if (empty($ids)) {
            return [
                'ok' => false,
                'duplicate' => false,
                'station_sent' => false,
                'oa_sent' => false,
                'oa_msg' => '顾问关联后台账号未绑定前台微信账号，请前往账号绑定配置',
                'error' => '顾问关联后台账号未绑定前台微信账号，请前往账号绑定配置',
            ];
        }

        $userId = (int)$ids[0];
        $res = BusinessNotificationService::recordWithStatus([
            'event' => $options['event'] ?? $businessType,
            'instance' => $options['instance'] ?? (string)$businessId,
            'user_id' => $userId,
            'audience' => str_starts_with($businessType, 'crm_') ? 'advisor' : 'admin',
            'title' => $title,
            'content' => $content,
            'notify_type' => Notification::TYPE_ORDER,
            'target_type' => 'admin_business',
            'target_id' => $businessId,
            'business_type' => $businessType,
            'business_id' => $businessId,
            'scene' => 'staff_internal',
            'page' => 'pages/message/message',
            'options' => $options + ['admin_ids' => $adminIds],
        ]);

        if (!empty($res['duplicate'])) {
            return [
                'ok' => true,
                'duplicate' => true,
                'station_sent' => false,
                'oa_sent' => false,
                'oa_status' => 'duplicate',
                'oa_msg' => '短时间内已推送，请勿重复发送',
                'error' => '',
            ];
        }

        if ($immediate) {
            $dispatched = BusinessNotificationService::dispatchByEventKey((string)$res['event_key'], true);
            return $dispatched + ['duplicate' => false];
        }

        return [
            'ok' => !empty($res['recorded']),
            'duplicate' => false,
            'station_sent' => false,
            'oa_sent' => false,
            'oa_queued' => true,
            'oa_status' => 'queued',
            'oa_msg' => '已加入发送队列',
            'error' => '',
        ];
    }

    public static function send(array $adminIds, string $title, string $content, string $businessType, int $businessId, array $options = []): bool
    {
        $ids = self::userIds($adminIds);
        if (empty($ids)) {
            return false;
        }
        foreach ($ids as $userId) {
            BusinessNotificationService::record([
                'event' => $options['event'] ?? $businessType, 'instance' => $options['instance'] ?? (string)$businessId,
                'user_id' => $userId, 'audience' => str_starts_with($businessType, 'crm_') ? 'advisor' : 'admin',
                'title' => $title, 'content' => $content, 'notify_type' => Notification::TYPE_ORDER,
                'target_type' => 'admin_business', 'target_id' => $businessId,
                'business_type' => $businessType, 'business_id' => $businessId, 'scene' => 'staff_internal',
                'page' => 'pages/message/message', 'options' => $options + ['admin_ids' => $adminIds],
            ]);
        }
        return true;
    }
}
