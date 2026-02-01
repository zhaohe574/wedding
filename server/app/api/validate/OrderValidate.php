<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 小程序端订单验证器
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\api\validate;

use app\common\validate\BaseValidate;

/**
 * 订单验证器
 * Class OrderValidate
 * @package app\api\validate
 */
class OrderValidate extends BaseValidate
{
    protected $rule = [
        'id' => 'require|integer|gt:0',
        'cart_ids' => 'array',
        'contact_name' => 'require|max:50',
        'contact_mobile' => 'require|mobile',
        'service_date' => 'date',
        'service_address' => 'max:255',
        'wedding_date' => 'date',
        'wedding_venue' => 'max:255',
        'remark' => 'max:500',
        'reason' => 'max:255',
        'coupon_id' => 'integer|egt:0',
        'deposit_ratio' => 'integer|between:0,100',
        'pay_way' => 'require|integer|in:1,2,3,4,5',
        'pay_type' => 'integer|in:1,2,3',
        'amount' => 'require|float|gt:0',
        'voucher' => 'require|max:500',
    ];

    protected $message = [
        'id.require' => '请选择订单',
        'id.integer' => '订单ID格式错误',
        'contact_name.require' => '请填写联系人',
        'contact_name.max' => '联系人姓名最多50个字符',
        'contact_mobile.require' => '请填写联系电话',
        'contact_mobile.mobile' => '联系电话格式错误',
        'service_date.date' => '服务日期格式错误',
        'service_address.max' => '服务地址最多255个字符',
        'wedding_date.date' => '婚礼日期格式错误',
        'wedding_venue.max' => '婚礼地点最多255个字符',
        'remark.max' => '备注最多500个字符',
        'reason.max' => '原因最多255个字符',
        'deposit_ratio.between' => '定金比例应在0-100之间',
        'pay_way.require' => '请选择支付方式',
        'pay_way.in' => '支付方式参数错误',
        'pay_type.in' => '支付类型参数错误',
        'amount.require' => '请填写退款金额',
        'amount.gt' => '退款金额必须大于0',
        'voucher.require' => '请上传支付凭证',
        'voucher.max' => '支付凭证地址过长',
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
     * @notes 创建订单场景
     * @return OrderValidate
     */
    public function sceneCreate()
    {
        return $this->only(['cart_ids', 'contact_name', 'contact_mobile', 'service_date', 'service_address', 'wedding_date', 'wedding_venue', 'remark', 'coupon_id', 'deposit_ratio']);
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
     * @notes 支付场景
     * @return OrderValidate
     */
    public function scenePay()
    {
        return $this->only(['id', 'pay_way', 'pay_type']);
    }

    /**
     * @notes 退款场景
     * @return OrderValidate
     */
    public function sceneRefund()
    {
        return $this->only(['id', 'amount', 'reason'])
            ->append('reason', 'require');
    }

    /**
     * @notes 上传凭证场景
     * @return OrderValidate
     */
    public function sceneUploadVoucher()
    {
        return $this->only(['id', 'voucher']);
    }
}
