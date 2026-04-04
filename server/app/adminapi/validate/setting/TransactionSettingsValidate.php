<?php
// +----------------------------------------------------------------------
// | likeadmin快速开发前后端分离管理后台（PHP版）
// +----------------------------------------------------------------------
// | 欢迎阅读学习系统程序代码，建议反馈是我们前进的动力
// | 开源版本可自由商用，可去除界面版权logo
// | gitee下载：https://gitee.com/likeshop_gitee/likeadmin
// | github下载：https://github.com/likeshop-github/likeadmin
// | 访问官网：https://www.likeadmin.cn
// | likeadmin团队 版权所有 拥有最终解释权
// +----------------------------------------------------------------------
// | author: likeadminTeam
// +----------------------------------------------------------------------

namespace app\adminapi\validate\setting;

use app\common\validate\BaseValidate;

/**
 * 交易设置验证
 * Class TransactionSettingsValidate
 * @package app\adminapi\validate\setting
 */
class TransactionSettingsValidate extends BaseValidate
{
    protected $rule = [
        'cancel_unpaid_orders' => 'require|in:0,1',
        'cancel_unpaid_orders_times' => 'requireIf:cancel_unpaid_orders,1|integer|gt:0',
        'staff_confirm_timeout_enabled' => 'require|in:0,1',
        'staff_confirm_timeout_action' => 'requireIf:staff_confirm_timeout_enabled,1|in:cancel,auto_confirm',
        'staff_confirm_timeout_minutes' => 'requireIf:staff_confirm_timeout_enabled,1|integer|gt:0',
        'verification_orders' => 'require|in:0,1',
        'verification_orders_times' => 'requireIf:verification_orders,1|integer|gt:0',
    ];

    protected $message = [
        'cancel_unpaid_orders.require' => '请选择系统取消待付款订单方式',
        'cancel_unpaid_orders.in' => '系统取消待付款订单状态值有误',
        'cancel_unpaid_orders_times.requireIf' => '系统取消待付款订单时间未填写',
        'cancel_unpaid_orders_times.integer' => '系统取消待付款订单时间须为整型',
        'cancel_unpaid_orders_times.gt' => '系统取消待付款订单时间须大于0',

        'staff_confirm_timeout_enabled.require' => '请选择服务人员确认超时开关',
        'staff_confirm_timeout_enabled.in' => '服务人员确认超时开关值有误',
        'staff_confirm_timeout_action.requireIf' => '请选择服务人员确认超时处理方式',
        'staff_confirm_timeout_action.in' => '服务人员确认超时处理方式有误',
        'staff_confirm_timeout_minutes.requireIf' => '请填写服务人员确认超时时长',
        'staff_confirm_timeout_minutes.integer' => '服务人员确认超时时长须为整型',
        'staff_confirm_timeout_minutes.gt' => '服务人员确认超时时长须大于0',

        'verification_orders.require' => '请选择系统自动核销订单方式',
        'verification_orders.in' => '系统自动核销订单状态值有误',
        'verification_orders_times.requireIf' => '系统自动核销订单时间未填写',
        'verification_orders_times.integer' => '系统自动核销订单时间须为整型',
        'verification_orders_times.gt' => '系统自动核销订单时间须大于0',
    ];

    public function sceneSetConfig()
    {
        return $this->only([
            'cancel_unpaid_orders',
            'cancel_unpaid_orders_times',
            'staff_confirm_timeout_enabled',
            'staff_confirm_timeout_action',
            'staff_confirm_timeout_minutes',
            'verification_orders',
            'verification_orders_times'
        ]);
    }
}
