<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 功能开关配置
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\adminapi\logic\setting;

use app\common\logic\BaseLogic;
use app\common\service\ConfigService;

/**
 * 功能开关逻辑
 * Class FeatureSwitchLogic
 * @package app\adminapi\logic\setting
 */
class FeatureSwitchLogic extends BaseLogic
{
    /**
     * 详情页风格模式
     */
    private const STAFF_DETAIL_STYLE_MODES = ['classic', 'immersive', 'conversion'];

    /**
     * @notes 获取功能开关
     */
    public static function getConfig(): array
    {
        return [
            'staff_center' => (int) ConfigService::get('feature_switch', 'staff_center', 1),
            'staff_admin' => (int) ConfigService::get('feature_switch', 'staff_admin', 1),
            'staff_tag_review_enabled' => (int) ConfigService::get('feature_switch', 'staff_tag_review_enabled', 0),
            'admin_dashboard' => (int) ConfigService::get('feature_switch', 'admin_dashboard', 1),
            'admin_dashboard_user_ids' => self::normalizeUserIds(
                (string) ConfigService::get('feature_switch', 'admin_dashboard_user_ids', '')
            ),
            'staff_detail_style' => self::normalizeStyleMode(
                (string) ConfigService::get('feature_switch', 'staff_detail_style', 'classic')
            ),
        ];
    }

    /**
     * @notes 设置功能开关
     */
    public static function setConfig(array $params): void
    {
        ConfigService::set('feature_switch', 'staff_center', (int) $params['staff_center']);
        ConfigService::set('feature_switch', 'staff_admin', (int) $params['staff_admin']);
        ConfigService::set('feature_switch', 'staff_tag_review_enabled', (int) $params['staff_tag_review_enabled']);
        ConfigService::set('feature_switch', 'admin_dashboard', (int) $params['admin_dashboard']);
        ConfigService::set(
            'feature_switch',
            'admin_dashboard_user_ids',
            self::normalizeUserIds((string) ($params['admin_dashboard_user_ids'] ?? ''))
        );
        ConfigService::set(
            'feature_switch',
            'staff_detail_style',
            self::normalizeStyleMode((string) ($params['staff_detail_style'] ?? 'classic'))
        );
    }

    /**
     * @notes 规范化详情页风格
     */
    private static function normalizeStyleMode(string $styleMode): string
    {
        return in_array($styleMode, self::STAFF_DETAIL_STYLE_MODES, true)
            ? $styleMode
            : 'classic';
    }

    /**
     * @notes 规范化管理员看板可访问用户ID
     */
    private static function normalizeUserIds(string $userIds): string
    {
        if (trim($userIds) === '') {
            return '';
        }

        $items = preg_split('/[\s,，]+/u', trim($userIds)) ?: [];
        $normalized = [];
        foreach ($items as $item) {
            $value = trim((string) $item);
            if ($value === '' || !preg_match('/^[1-9]\d*$/', $value)) {
                continue;
            }
            $normalized[] = (int) $value;
        }

        $normalized = array_values(array_unique($normalized));
        if (empty($normalized)) {
            return '';
        }

        return implode(',', $normalized);
    }
}
