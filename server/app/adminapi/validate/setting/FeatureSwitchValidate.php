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
        'admin_dashboard_user_ids' => 'checkAdminDashboardUserIds',
        'staff_detail_style' => 'in:classic,immersive,conversion',
    ];

    protected $message = [
        'staff_center.require' => '请选择服务人员中心开关',
        'staff_center.in' => '服务人员中心开关值错误',
        'staff_admin.require' => '请选择服务人员后台开关',
        'staff_admin.in' => '服务人员后台开关值错误',
        'admin_dashboard.require' => '请选择管理员看板开关',
        'admin_dashboard.in' => '管理员看板开关值错误',
        'admin_dashboard_user_ids.checkAdminDashboardUserIds' => '管理员可访问用户ID格式错误',
        'staff_detail_style.in' => '服务人员详情页风格值错误',
    ];

    public function sceneSetConfig(): FeatureSwitchValidate
    {
        return $this->only([
            'staff_center',
            'staff_admin',
            'admin_dashboard',
            'admin_dashboard_user_ids',
            'staff_detail_style',
        ]);
    }

    /**
     * @notes 校验管理员可访问用户ID
     */
    public function checkAdminDashboardUserIds($value): bool
    {
        $input = trim((string) $value);
        if ($input === '') {
            return true;
        }

        $items = preg_split('/[\s,，]+/u', $input) ?: [];
        foreach ($items as $item) {
            $id = trim((string) $item);
            if ($id === '') {
                continue;
            }
            if (!preg_match('/^[1-9]\d*$/', $id)) {
                return false;
            }
        }

        return true;
    }
}
