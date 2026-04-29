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
        'staff_tag_review_enabled' => 'require|in:0,1',
        'comment_review_enabled' => 'require|in:0,1',
        'admin_dashboard' => 'require|in:0,1',
        'order_complete_by_user' => 'require|in:0,1',
        'order_complete_by_staff' => 'require|in:0,1',
        'admin_dashboard_user_ids' => 'checkAdminDashboardUserIds',
        'enable_deposit_mode' => 'require|in:0,1',
        'deposit_type' => 'requireIf:enable_deposit_mode,1|in:fixed,ratio',
        'deposit_value' => 'requireIf:enable_deposit_mode,1|float|gt:0|checkDepositValue',
        'deposit_remark' => 'max:255',
    ];

    protected $message = [
        'staff_center.require' => '请选择服务人员中心开关',
        'staff_center.in' => '服务人员中心开关值错误',
        'staff_admin.require' => '请选择服务人员后台开关',
        'staff_admin.in' => '服务人员后台开关值错误',
        'staff_tag_review_enabled.require' => '请选择标签审核开关',
        'staff_tag_review_enabled.in' => '标签审核开关值错误',
        'comment_review_enabled.require' => '请选择评论审核开关',
        'comment_review_enabled.in' => '评论审核开关值错误',
        'admin_dashboard.require' => '请选择管理员看板开关',
        'admin_dashboard.in' => '管理员看板开关值错误',
        'order_complete_by_user.require' => '请选择用户完成服务开关',
        'order_complete_by_user.in' => '用户完成服务开关值错误',
        'order_complete_by_staff.require' => '请选择服务人员完成服务开关',
        'order_complete_by_staff.in' => '服务人员完成服务开关值错误',
        'admin_dashboard_user_ids.checkAdminDashboardUserIds' => '管理员可访问用户ID格式错误',
        'enable_deposit_mode.require' => '请选择是否开启定金模式',
        'enable_deposit_mode.in' => '定金模式开关值错误',
        'deposit_type.requireIf' => '请选择定金计算方式',
        'deposit_type.in' => '定金计算方式错误',
        'deposit_value.requireIf' => '请填写定金值',
        'deposit_value.float' => '定金值格式错误',
        'deposit_value.gt' => '定金值必须大于0',
        'deposit_value.checkDepositValue' => '定金比例必须大于0且小于100',
        'deposit_remark.max' => '定金说明最多255个字符',
    ];

    public function sceneSetConfig(): FeatureSwitchValidate
    {
        return $this->only([
            'staff_center',
            'staff_admin',
            'staff_tag_review_enabled',
            'comment_review_enabled',
            'admin_dashboard',
            'order_complete_by_user',
            'order_complete_by_staff',
            'admin_dashboard_user_ids',
            'enable_deposit_mode',
            'deposit_type',
            'deposit_value',
            'deposit_remark',
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

    /**
     * @notes 校验定金值
     */
    public function checkDepositValue($value, $rule, array $data = []): bool
    {
        if ((int) ($data['enable_deposit_mode'] ?? 0) !== 1) {
            return true;
        }

        $type = (string) ($data['deposit_type'] ?? '');
        $depositValue = (float) $value;
        if ($type === 'ratio' && ($depositValue <= 0 || $depositValue >= 100)) {
            return false;
        }

        return true;
    }
}
