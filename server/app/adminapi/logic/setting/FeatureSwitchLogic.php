<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 功能开关配置
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\adminapi\logic\setting;

use app\common\logic\BaseLogic;
use app\common\service\ConfigService;
use app\common\service\UserRiskControlService;
use think\facade\Log;

/**
 * 功能开关逻辑
 * Class FeatureSwitchLogic
 * @package app\adminapi\logic\setting
 */
class FeatureSwitchLogic extends BaseLogic
{
    /**
     * @notes 获取功能开关
     */
    public static function getConfig(): array
    {
        return [
            'staff_center' => (int) ConfigService::get('feature_switch', 'staff_center', 1),
            // 服务人员中心写能力灰度（默认 0：小程序写入口关闭）
            'staff_center_write' => (int) ConfigService::get('feature_switch', 'staff_center_write', 0),
            'staff_admin' => 1,
            'staff_tag_review_enabled' => (int) ConfigService::get('feature_switch', 'staff_tag_review_enabled', 0),
            'comment_review_enabled' => (int) ConfigService::get('feature_switch', 'comment_review_enabled', 0),
            'wechat_text_check_enabled' => (int) ConfigService::get('feature_switch', 'wechat_text_check_enabled', 1),
            'wechat_text_check_profile_prob' => self::normalizePercent(
                (int) ConfigService::get('feature_switch', 'wechat_text_check_profile_prob', 80),
                80
            ),
            'wechat_text_check_comment_prob' => self::normalizePercent(
                (int) ConfigService::get('feature_switch', 'wechat_text_check_comment_prob', 70),
                70
            ),
            'wechat_text_check_review_as_hit' => (int) ConfigService::get('feature_switch', 'wechat_text_check_review_as_hit', 1),
            'risk_rank_ability_options' => UserRiskControlService::getAbilityOptions(),
            'risk_rank_default_permissions' => UserRiskControlService::defaultRankPermissions(),
            'risk_rank_permissions' => UserRiskControlService::normalizeRankPermissions(
                ConfigService::get('risk_control', 'rank_permissions', UserRiskControlService::defaultRankPermissions())
            ),
            'mini_program_review_mode' => (int) ConfigService::get('feature_switch', 'mini_program_review_mode', 0),
            'admin_dashboard' => (int) ConfigService::get('feature_switch', 'admin_dashboard', 1),
            'order_complete_by_user' => (int) ConfigService::get('feature_switch', 'order_complete_by_user', 0),
            'order_complete_by_staff' => (int) ConfigService::get('feature_switch', 'order_complete_by_staff', 0),
            'enable_deposit_mode' => (int) ConfigService::get('order_payment', 'enable_deposit_mode', 0),
            'deposit_type' => self::normalizeDepositType((string) ConfigService::get('order_payment', 'deposit_type', 'ratio')),
            'deposit_value' => round((float) ConfigService::get('order_payment', 'deposit_value', 30), 2),
            'deposit_remark' => (string) ConfigService::get('order_payment', 'deposit_remark', ''),
            'deposit_rounding_enabled' => (int) ConfigService::get('order_payment', 'deposit_rounding_enabled', 0),
            'deposit_rounding_unit' => self::normalizeDepositRoundingUnit((int) ConfigService::get('order_payment', 'deposit_rounding_unit', 1)),
            'offline_collection_enabled' => (int) ConfigService::get('order_payment', 'offline_collection_enabled', 1),
        ];
    }

    /**
     * @notes 设置功能开关
     * @param array $params
     * @param int $adminId 操作人，用于送审模式开关留痕
     */
    public static function setConfig(array $params, int $adminId = 0): void
    {
        ConfigService::set('feature_switch', 'staff_center', (int) $params['staff_center']);
        // 服务人员中心写能力灰度：旧版后台前端不传该字段时不覆盖现有配置，保证后端先行上线期间后台保存不报错
        if (array_key_exists('staff_center_write', $params)) {
            ConfigService::set('feature_switch', 'staff_center_write', (int) $params['staff_center_write']);
        }
        ConfigService::set('feature_switch', 'staff_admin', 1);
        ConfigService::set('feature_switch', 'staff_tag_review_enabled', (int) $params['staff_tag_review_enabled']);
        ConfigService::set('feature_switch', 'comment_review_enabled', (int) $params['comment_review_enabled']);
        ConfigService::set('feature_switch', 'wechat_text_check_enabled', (int) $params['wechat_text_check_enabled']);
        ConfigService::set(
            'feature_switch',
            'wechat_text_check_profile_prob',
            self::normalizePercent((int) $params['wechat_text_check_profile_prob'], 80)
        );
        ConfigService::set(
            'feature_switch',
            'wechat_text_check_comment_prob',
            self::normalizePercent((int) $params['wechat_text_check_comment_prob'], 70)
        );
        ConfigService::set('feature_switch', 'wechat_text_check_review_as_hit', (int) $params['wechat_text_check_review_as_hit']);
        ConfigService::set(
            'risk_control',
            'rank_permissions',
            UserRiskControlService::normalizeRankPermissions($params['risk_rank_permissions'] ?? UserRiskControlService::defaultRankPermissions())
        );
        // 送审模式是提审敏感开关：每次变更都要留痕，便于说明何时开/关、由谁操作
        $reviewModeBefore = (int) ConfigService::get('feature_switch', 'mini_program_review_mode', 0);
        $reviewModeAfter = (int) $params['mini_program_review_mode'];
        ConfigService::set('feature_switch', 'mini_program_review_mode', $reviewModeAfter);
        if ($reviewModeBefore !== $reviewModeAfter) {
            Log::info(
                '小程序送审模式开关变更：admin_id=' . $adminId
                . '，before=' . $reviewModeBefore
                . '，after=' . $reviewModeAfter
                . '，ip=' . request()->ip()
            );
        }
        ConfigService::set('feature_switch', 'admin_dashboard', (int) $params['admin_dashboard']);
        ConfigService::set('feature_switch', 'order_complete_by_user', (int) ($params['order_complete_by_user'] ?? 0));
        ConfigService::set('feature_switch', 'order_complete_by_staff', (int) ($params['order_complete_by_staff'] ?? 0));
        ConfigService::set('order_payment', 'enable_deposit_mode', (int) ($params['enable_deposit_mode'] ?? 0));
        ConfigService::set('order_payment', 'deposit_type', self::normalizeDepositType((string) ($params['deposit_type'] ?? 'ratio')));
        ConfigService::set('order_payment', 'deposit_value', round((float) ($params['deposit_value'] ?? 0), 2));
        ConfigService::set('order_payment', 'deposit_remark', trim((string) ($params['deposit_remark'] ?? '')));
        ConfigService::set('order_payment', 'deposit_rounding_enabled', (int) ($params['deposit_rounding_enabled'] ?? 0));
        ConfigService::set('order_payment', 'deposit_rounding_unit', self::normalizeDepositRoundingUnit((int) ($params['deposit_rounding_unit'] ?? 1)));
        ConfigService::set('order_payment', 'offline_collection_enabled', (int) ($params['offline_collection_enabled'] ?? 1));
    }

    /**
     * @notes 规范化定金类型
     */
    private static function normalizeDepositType(string $depositType): string
    {
        return in_array($depositType, ['fixed', 'ratio'], true)
            ? $depositType
            : 'ratio';
    }

    /**
     * @notes 规范化微信文本检测置信度阈值
     */
    private static function normalizePercent(int $value, int $default): int
    {
        if ($value < 0 || $value > 100) {
            return $default;
        }

        return $value;
    }

    /**
     * @notes 规范化比例定金拆分的尾款凑整单位
     */
    private static function normalizeDepositRoundingUnit(int $unit): int
    {
        return in_array($unit, [1, 10, 100], true) ? $unit : 1;
    }
}
