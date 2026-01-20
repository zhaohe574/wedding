<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 订单验证器
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\adminapi\validate\order;

use app\common\validate\BaseValidate;

/**
 * 订单验证器
 * Class OrderValidate
 * @package app\adminapi\validate\order
 */
class OrderValidate extends BaseValidate
{
    protected $rule = [
        'id' => 'require|integer|gt:0',
        'user_id' => 'require|integer|gt:0',
        'order_type' => 'integer|in:1,2,3',
        'items' => 'require|array|min:1',
        'service_date' => 'date',
        'time_slot' => 'integer|between:0,3',
        'service_address' => 'max:255',
        'contact_name' => 'require|max:50',
        'contact_mobile' => 'require|mobile',
        'wedding_date' => 'date',
        'wedding_venue' => 'max:255',
        'discount_amount' => 'float|egt:0',
        'deposit_ratio' => 'integer|between:0,100',
        'admin_remark' => 'max:500',
        'reason' => 'max:255',
        'remark' => 'max:500',
        'pay_type' => 'require|integer|in:1,2,3',
        'pay_amount' => 'require|float|gt:0',
    ];

    protected $message = [
        'id.require' => '请选择订单',
        'id.integer' => '订单ID格式错误',
        'user_id.require' => '请选择用户',
        'user_id.integer' => '用户ID格式错误',
        'order_type.in' => '订单类型参数错误',
        'items.require' => '请添加订单项',
        'items.array' => '订单项格式错误',
        'items.min' => '至少添加一个订单项',
        'service_date.date' => '服务日期格式错误',
        'time_slot.between' => '时间段参数错误',
        'service_address.max' => '服务地址最多255个字符',
        'contact_name.require' => '请填写联系人',
        'contact_name.max' => '联系人姓名最多50个字符',
        'contact_mobile.require' => '请填写联系电话',
        'contact_mobile.mobile' => '联系电话格式错误',
        'wedding_date.date' => '婚礼日期格式错误',
        'wedding_venue.max' => '婚礼地点最多255个字符',
        'discount_amount.egt' => '优惠金额不能为负数',
        'deposit_ratio.between' => '定金比例应在0-100之间',
        'admin_remark.max' => '备注最多500个字符',
        'reason.max' => '原因最多255个字符',
        'remark.max' => '备注最多500个字符',
        'pay_type.require' => '请选择支付类型',
        'pay_type.in' => '支付类型参数错误',
        'pay_amount.require' => '请填写支付金额',
        'pay_amount.gt' => '支付金额必须大于0',
    ];

    /**
     * @notes 详情场景
     * @return OrderValidate
     */
    public function sceneDetail()
    {
        return $this->only(['id']);
    }

    /**
     * @notes 新增场景
     * @return OrderValidate
     */
    public function sceneAdd()
    {
        return $this->only(['user_id', 'order_type', 'items', 'service_date', 'time_slot', 'service_address', 'contact_name', 'contact_mobile', 'wedding_date', 'wedding_venue', 'discount_amount', 'deposit_ratio', 'admin_remark']);
    }

    /**
     * @notes 编辑场景
     * @return OrderValidate
     */
    public function sceneEdit()
    {
        return $this->only(['id', 'service_date', 'time_slot', 'service_address', 'contact_name', 'contact_mobile', 'wedding_date', 'wedding_venue', 'admin_remark'])
            ->remove('contact_name', 'require')
            ->remove('contact_mobile', 'require');
    }

    /**
     * @notes 取消场景
     * @return OrderValidate
     */
    public function sceneCancel()
    {
        return $this->only(['id', 'reason']);
    }

    /**
     * @notes 备注场景
     * @return OrderValidate
     */
    public function sceneRemark()
    {
        return $this->only(['id', 'remark'])
            ->append('remark', 'require');
    }

    /**
     * @notes 确认支付场景
     * @return OrderValidate
     */
    public function sceneConfirmPay()
    {
        return $this->only(['id', 'pay_type', 'pay_amount']);
    }
}
