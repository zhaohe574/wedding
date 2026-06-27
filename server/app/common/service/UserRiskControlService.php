<?php

declare(strict_types=1);

namespace app\common\service;

use app\common\model\user\User;

/**
 * 用户风险等级管控服务。
 */
class UserRiskControlService
{
    public const MANUAL_FOLLOW_WECHAT = -1;
    public const RANK_NORMAL = 0;
    public const RANK_LOW = 1;
    public const RANK_MEDIUM = 2;
    public const RANK_HIGH = 3;
    public const RANK_CRITICAL = 4;

    private const ABILITY_OPTIONS = [
        'profile' => '资料',
        'content_publish' => '内容发布',
        'comment' => '发表评论',
        'interaction' => '互动',
        'activity' => '活动报名',
        'schedule' => '档期',
        'order' => '订单',
        'payment' => '支付',
        'recharge' => '充值',
        'after_sale' => '售后投诉',
        'customer_service' => '发起咨询',
        'upload' => '上传',
        'notification' => '通知',
        'staff_center' => '服务人员中心',
    ];

    private const ACTION_ABILITY_MAP = [
        'user/setinfo' => 'profile',
        'user/setprofile' => 'profile',
        'user/bindmobile' => 'profile',
        'user/getmobilebymnp' => 'profile',
        'user/changepassword' => 'profile',
        'login/updateuser' => 'profile',
        'login/mnpauthbind' => 'profile',
        'login/oaauthbind' => 'profile',

        'dynamic/publish' => 'content_publish',
        'dynamic/delete' => 'content_publish',
        'review/publish' => 'content_publish',
        'review/append' => 'content_publish',
        'review/applysharereward' => 'content_publish',

        'dynamic/addcomment' => 'comment',
        'dynamic/deletecomment' => 'comment',

        'article/collect' => 'interaction',
        'article/addcollect' => 'interaction',
        'article/cancelcollect' => 'interaction',
        'dynamic/like' => 'interaction',
        'dynamic/collect' => 'interaction',
        'dynamic/likecomment' => 'interaction',
        'review/togglelike' => 'interaction',
        'staff/togglefavorite' => 'interaction',

        'dynamic/activityregister' => 'activity',
        'dynamic/activityprepay' => 'activity',
        'dynamic/activitycancelapply' => 'activity',

        'schedule/joinwaitlist' => 'schedule',
        'schedule/cancelwaitlist' => 'schedule',
        'schedule/lockschedule' => 'schedule',
        'schedule/batchlockschedule' => 'schedule',
        'schedule/releaselock' => 'schedule',

        'order/create' => 'order',
        'order/preview' => 'order',
        'order/cancel' => 'order',
        'order/confirm' => 'order',
        'order/delete' => 'order',
        'order_change/applydatechange' => 'order',
        'orderchange/applydatechange' => 'order',
        'order_change/applystaffchange' => 'order',
        'orderchange/applystaffchange' => 'order',
        'order_change/applyadditem' => 'order',
        'orderchange/applyadditem' => 'order',
        'order_change/cancel' => 'order',
        'orderchange/cancel' => 'order',
        'order_change/applypause' => 'order',
        'orderchange/applypause' => 'order',
        'order_change/cancelpause' => 'order',
        'orderchange/cancelpause' => 'order',

        'order/pay' => 'payment',
        'order/uploadvoucher' => 'payment',
        'order/paybalance' => 'payment',
        'order/applyrefund' => 'payment',
        'pay/prepay' => 'payment',

        'recharge/recharge' => 'recharge',

        'after_sale/createticket' => 'after_sale',
        'aftersale/createticket' => 'after_sale',
        'after_sale/cancelticket' => 'after_sale',
        'aftersale/cancelticket' => 'after_sale',
        'after_sale/confirmcomplete' => 'after_sale',
        'aftersale/confirmcomplete' => 'after_sale',
        'after_sale/rejectcomplete' => 'after_sale',
        'aftersale/rejectcomplete' => 'after_sale',
        'after_sale/submitcomplaint' => 'after_sale',
        'aftersale/submitcomplaint' => 'after_sale',
        'after_sale/ratecomplaint' => 'after_sale',
        'aftersale/ratecomplaint' => 'after_sale',
        'after_sale/submitquestionnaire' => 'after_sale',
        'aftersale/submitquestionnaire' => 'after_sale',
        'couple_questionnaire/submit' => 'after_sale',
        'couplequestionnaire/submit' => 'after_sale',

        'customer_service/startconsult' => 'customer_service',
        'customerservice/startconsult' => 'customer_service',

        'upload/image' => 'upload',
        'upload/video' => 'upload',

        'dynamic/markread' => 'notification',
        'notification/markread' => 'notification',
        'notification/markallread' => 'notification',
        'notification/delete' => 'notification',
        'notification/clear' => 'notification',
        'subscribe/recordsubscribe' => 'notification',
        'subscribe/batchrecordsubscribe' => 'notification',

        'staff_center/updateprofile' => 'staff_center',
        'staffcenter/updateprofile' => 'staff_center',
        'staff_center/certificateadd' => 'staff_center',
        'staffcenter/certificateadd' => 'staff_center',
        'staff_center/certificateedit' => 'staff_center',
        'staffcenter/certificateedit' => 'staff_center',
        'staff_center/certificatedelete' => 'staff_center',
        'staffcenter/certificatedelete' => 'staff_center',
        'staff_center/workadd' => 'staff_center',
        'staffcenter/workadd' => 'staff_center',
        'staff_center/workedit' => 'staff_center',
        'staffcenter/workedit' => 'staff_center',
        'staff_center/workdelete' => 'staff_center',
        'staffcenter/workdelete' => 'staff_center',
        'staff_center/packageadd' => 'staff_center',
        'staffcenter/packageadd' => 'staff_center',
        'staff_center/packageupdate' => 'staff_center',
        'staffcenter/packageupdate' => 'staff_center',
        'staff_center/packageremove' => 'staff_center',
        'staffcenter/packageremove' => 'staff_center',
        'staff_center/addonadd' => 'staff_center',
        'staffcenter/addonadd' => 'staff_center',
        'staff_center/addonupdate' => 'staff_center',
        'staffcenter/addonupdate' => 'staff_center',
        'staff_center/addonremove' => 'staff_center',
        'staffcenter/addonremove' => 'staff_center',
        'staff_center/schedulesetstatus' => 'staff_center',
        'staffcenter/schedulesetstatus' => 'staff_center',
        'staff_center/orderconfirm' => 'staff_center',
        'staffcenter/orderconfirm' => 'staff_center',
        'staff_center/ordercomplete' => 'staff_center',
        'staffcenter/ordercomplete' => 'staff_center',
        'staff_center/orderstartservice' => 'staff_center',
        'staffcenter/orderstartservice' => 'staff_center',
        'staff_center/scheduleconfirmlettersaveconfig' => 'staff_center',
        'staffcenter/scheduleconfirmlettersaveconfig' => 'staff_center',
        'staff_center/orderconfirmlettersaveassets' => 'staff_center',
        'staffcenter/orderconfirmlettersaveassets' => 'staff_center',
        'staff_center/orderconfirmletterpush' => 'staff_center',
        'staffcenter/orderconfirmletterpush' => 'staff_center',
        'staff_center/orderconfirmletterregenerateassets' => 'staff_center',
        'staffcenter/orderconfirmletterregenerateassets' => 'staff_center',
        'staff_center/settlementreceive' => 'staff_center',
        'staffcenter/settlementreceive' => 'staff_center',
        'staff_center/settlementsync' => 'staff_center',
        'staffcenter/settlementsync' => 'staff_center',
        'staff_center/dynamicadd' => 'staff_center',
        'staffcenter/dynamicadd' => 'staff_center',
        'staff_center/dynamicedit' => 'staff_center',
        'staffcenter/dynamicedit' => 'staff_center',
        'staff_center/dynamicdelete' => 'staff_center',
        'staffcenter/dynamicdelete' => 'staff_center',
    ];

    public static function getEffectiveRiskRank(int $userId): int
    {
        $user = User::where('id', $userId)
            ->field('id,wechat_risk_rank,manual_risk_rank')
            ->findOrEmpty();
        if ($user->isEmpty()) {
            return self::RANK_NORMAL;
        }

        $manualRank = (int)($user->manual_risk_rank ?? self::MANUAL_FOLLOW_WECHAT);
        if ($manualRank >= self::RANK_NORMAL) {
            return self::normalizeRiskRank($manualRank);
        }

        return self::normalizeRiskRank((int)($user->wechat_risk_rank ?? self::RANK_NORMAL));
    }

    public static function shouldBlockWrite(int $userId): bool
    {
        return self::getEffectiveRiskRank($userId) >= self::RANK_HIGH;
    }

    public static function shouldForceContentReview(int $userId): bool
    {
        $rank = self::getEffectiveRiskRank($userId);
        $permissions = self::getRankPermissions();
        $rankPermission = $permissions[$rank] ?? self::defaultRankPermissions()[$rank];

        return (bool)($rankPermission['comment_force_review'] ?? false);
    }

    public static function isActionAllowed(int $userId, string $controller, string $action): bool
    {
        if ($userId <= 0) {
            return true;
        }

        $ability = self::resolveAbilityByAction($controller, $action);
        if ($ability === null) {
            return true;
        }

        $rank = self::getEffectiveRiskRank($userId);
        $permissions = self::getRankPermissions();
        $rankPermission = $permissions[$rank] ?? self::defaultRankPermissions()[$rank];
        $enabledAbilities = $rankPermission['enabled_abilities'] ?? [];

        return in_array($ability, $enabledAbilities, true);
    }

    public static function buildRiskInfo(array $user): array
    {
        $wechatRank = self::normalizeRiskRank((int)($user['wechat_risk_rank'] ?? self::RANK_NORMAL));
        $manualRank = max(self::MANUAL_FOLLOW_WECHAT, min(self::RANK_CRITICAL, (int)($user['manual_risk_rank'] ?? self::MANUAL_FOLLOW_WECHAT)));
        $effectiveRank = $manualRank >= self::RANK_NORMAL ? $manualRank : $wechatRank;

        return [
            'wechat_risk_rank' => $wechatRank,
            'wechat_risk_rank_desc' => self::riskRankText($wechatRank),
            'manual_risk_rank' => $manualRank,
            'manual_risk_rank_desc' => $manualRank === self::MANUAL_FOLLOW_WECHAT ? '跟随微信检测' : self::riskRankText($manualRank),
            'effective_risk_rank' => $effectiveRank,
            'effective_risk_rank_desc' => self::riskRankText($effectiveRank),
            'risk_rank_source' => $manualRank >= self::RANK_NORMAL ? 'manual' : 'wechat',
            'risk_rank_source_desc' => $manualRank >= self::RANK_NORMAL ? '后台人工' : '微信检测',
        ];
    }

    public static function riskRankText(int $rank): string
    {
        return match ($rank) {
            self::RANK_NORMAL => '正常',
            self::RANK_LOW => '低风险',
            self::RANK_MEDIUM => '中风险',
            self::RANK_HIGH => '高风险',
            self::RANK_CRITICAL => '极高风险',
            default => '未知',
        };
    }

    public static function normalizeManualRank(int $rank): int
    {
        return max(self::MANUAL_FOLLOW_WECHAT, min(self::RANK_CRITICAL, $rank));
    }

    public static function getAbilityOptions(): array
    {
        $options = [];
        foreach (self::ABILITY_OPTIONS as $code => $name) {
            $options[] = [
                'code' => $code,
                'name' => $name,
            ];
        }

        return $options;
    }

    public static function defaultRankPermissions(): array
    {
        $allAbilities = array_keys(self::ABILITY_OPTIONS);

        return [
            self::RANK_NORMAL => [
                'enabled_abilities' => $allAbilities,
                'comment_force_review' => false,
            ],
            self::RANK_LOW => [
                'enabled_abilities' => $allAbilities,
                'comment_force_review' => false,
            ],
            self::RANK_MEDIUM => [
                'enabled_abilities' => $allAbilities,
                'comment_force_review' => true,
            ],
            self::RANK_HIGH => [
                'enabled_abilities' => [],
                'comment_force_review' => false,
            ],
            self::RANK_CRITICAL => [
                'enabled_abilities' => [],
                'comment_force_review' => false,
            ],
        ];
    }

    public static function normalizeRankPermissions($value): array
    {
        $default = self::defaultRankPermissions();
        if (!is_array($value)) {
            return $default;
        }

        $abilityCodes = array_keys(self::ABILITY_OPTIONS);
        $normalized = [];
        for ($rank = self::RANK_NORMAL; $rank <= self::RANK_CRITICAL; $rank++) {
            $rankConfig = $value[$rank] ?? $value[(string)$rank] ?? null;
            if (!is_array($rankConfig)) {
                $normalized[$rank] = $default[$rank];
                continue;
            }

            $enabledAbilities = $rankConfig['enabled_abilities'] ?? $default[$rank]['enabled_abilities'];
            if (!is_array($enabledAbilities)) {
                $enabledAbilities = $default[$rank]['enabled_abilities'];
            }

            $validAbilities = [];
            foreach ($enabledAbilities as $ability) {
                if (is_string($ability) && in_array($ability, $abilityCodes, true)) {
                    $validAbilities[] = $ability;
                }
            }

            $normalized[$rank] = [
                'enabled_abilities' => array_values(array_unique($validAbilities)),
                'comment_force_review' => self::normalizeBoolean($rankConfig['comment_force_review'] ?? $default[$rank]['comment_force_review']),
            ];
        }

        return $normalized;
    }

    public static function validateRankPermissions($value): bool
    {
        if (!is_array($value)) {
            return false;
        }

        $abilityCodes = array_keys(self::ABILITY_OPTIONS);
        for ($rank = self::RANK_NORMAL; $rank <= self::RANK_CRITICAL; $rank++) {
            if (!array_key_exists($rank, $value) && !array_key_exists((string)$rank, $value)) {
                return false;
            }

            $rankConfig = $value[$rank] ?? $value[(string)$rank] ?? null;
            if (!is_array($rankConfig)) {
                return false;
            }

            if (!array_key_exists('enabled_abilities', $rankConfig) || !is_array($rankConfig['enabled_abilities'])) {
                return false;
            }

            foreach ($rankConfig['enabled_abilities'] as $ability) {
                if (!is_string($ability) || !in_array($ability, $abilityCodes, true)) {
                    return false;
                }
            }

            if (!array_key_exists('comment_force_review', $rankConfig) || !self::isBooleanLike($rankConfig['comment_force_review'])) {
                return false;
            }
        }

        return true;
    }

    private static function getRankPermissions(): array
    {
        try {
            $permissions = ConfigService::get('risk_control', 'rank_permissions', self::defaultRankPermissions());
            return self::normalizeRankPermissions($permissions);
        } catch (\Throwable $e) {
            return self::defaultRankPermissions();
        }
    }

    private static function resolveAbilityByAction(string $controller, string $action): ?string
    {
        $key = self::normalizeActionKey($controller, $action);
        return self::ACTION_ABILITY_MAP[$key] ?? null;
    }

    private static function normalizeActionKey(string $controller, string $action): string
    {
        $controller = preg_replace('/(?<!^)[A-Z]/', '_$0', $controller) ?: $controller;
        $controller = strtolower(str_replace(['.', '-'], '_', $controller));
        $action = strtolower(str_replace(['_', '.', '-'], '', $action));

        return strtolower($controller . '/' . $action);
    }

    private static function normalizeRiskRank(int $rank): int
    {
        return max(self::RANK_NORMAL, min(self::RANK_CRITICAL, $rank));
    }

    private static function normalizeBoolean($value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if (is_int($value)) {
            return $value === 1;
        }

        if (is_string($value)) {
            return in_array(strtolower($value), ['1', 'true'], true);
        }

        return false;
    }

    private static function isBooleanLike($value): bool
    {
        return is_bool($value)
            || $value === 0
            || $value === 1
            || $value === '0'
            || $value === '1'
            || $value === ''
            || $value === 'true'
            || $value === 'false';
    }
}
