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
     * @notes 获取功能开关
     */
    public static function getConfig(): array
    {
        return [
            'staff_center' => (int) ConfigService::get('feature_switch', 'staff_center', 1),
            'staff_admin' => (int) ConfigService::get('feature_switch', 'staff_admin', 1),
            'admin_dashboard' => (int) ConfigService::get('feature_switch', 'admin_dashboard', 1),
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
    }
}
