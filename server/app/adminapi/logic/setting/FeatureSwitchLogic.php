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
            'admin_dashboard' => (int) ConfigService::get('feature_switch', 'admin_dashboard', 1),
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
        ConfigService::set('feature_switch', 'admin_dashboard', (int) $params['admin_dashboard']);
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
}
