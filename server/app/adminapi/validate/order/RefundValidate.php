<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 退款验证器
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\adminapi\validate\order;

use app\common\validate\BaseValidate;

/**
 * 退款验证器
 * Class RefundValidate
 * @package app\adminapi\validate\order
 */
class RefundValidate extends BaseValidate
{
    protected $rule = [
        'id' => 'require|integer|gt:0',
        'order_id' => 'require|integer|gt:0',
        'approved' => 'require|boolean',
        'remark' => 'max:500',
        'reason' => 'max:255',
        'refund_amount' => 'require|float|gt:0',
        'transaction_id' => 'max:64',
    ];

    protected $message = [
        'id.require' => '请选择退款记录',
        'id.integer' => '退款ID格式错误',
        'order_id.require' => '请选择订单',
        'order_id.integer' => '订单ID格式错误',
        'approved.require' => '请选择审核结果',
        'approved.boolean' => '审核结果格式错误',
        'remark.max' => '备注最多500个字符',
        'reason.max' => '原因最多255个字符',
        'refund_amount.require' => '请填写退款金额',
        'refund_amount.gt' => '退款金额必须大于0',
        'transaction_id.max' => '交易号最多64个字符',
    ];

    /**
     * @notes 详情场景
     * @return RefundValidate
     */
    public function sceneDetail()
    {
        return $this->only(['id']);
    }

    /**
     * @notes 审核场景
     * @return RefundValidate
     */
    public function sceneAudit()
    {
        return $this->only(['id', 'approved', 'remark']);
    }

    /**
     * @notes 确认退款场景
     * @return RefundValidate
     */
    public function sceneConfirm()
    {
        return $this->only(['id', 'transaction_id']);
    }

    /**
     * @notes 申请退款场景
     * @return RefundValidate
     */
    public function sceneApply()
    {
        return $this->only(['order_id', 'refund_amount', 'reason']);
    }
}
