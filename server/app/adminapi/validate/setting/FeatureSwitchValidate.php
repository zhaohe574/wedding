<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 功能开关验证
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\adminapi\validate\setting;

use app\common\validate\BaseValidate;

/**
 * 功能开关验证
 * Class FeatureSwitchValidate
 * @package app\adminapi\validate\setting
 */
class FeatureSwitchValidate extends BaseValidate
{
    protected $rule = [
        'staff_center' => 'require|in:0,1',
        'staff_admin' => 'require|in:0,1',
        'admin_dashboard' => 'require|in:0,1',
    ];

    protected $message = [
        'staff_center.require' => '请选择服务人员中心开关',
        'staff_center.in' => '服务人员中心开关值错误',
        'staff_admin.require' => '请选择服务人员后台开关',
        'staff_admin.in' => '服务人员后台开关值错误',
        'admin_dashboard.require' => '请选择管理员看板开关',
        'admin_dashboard.in' => '管理员看板开关值错误',
    ];

    public function sceneSetConfig(): FeatureSwitchValidate
    {
        return $this->only(['staff_center', 'staff_admin', 'admin_dashboard']);
    }
}
