<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 小程序端优惠券验证器
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\api\validate;

use app\common\validate\BaseValidate;

/**
 * 小程序端优惠券验证器
 * Class CouponValidate
 * @package app\api\validate
 */
class CouponValidate extends BaseValidate
{
    protected $rule = [
        'id' => 'require|integer|gt:0',
        'coupon_id' => 'require|integer|gt:0',
        'user_coupon_id' => 'require|integer|gt:0',
        'order_id' => 'require|integer|gt:0',
        'order_amount' => 'require|float|egt:0',
        'code' => 'require|alphaNum|length:8,32',
    ];

    protected $message = [
        'id.require' => '优惠券ID不能为空',
        'id.integer' => '优惠券ID必须是整数',
        'id.gt' => '优惠券ID必须大于0',
        'coupon_id.require' => '优惠券ID不能为空',
        'coupon_id.integer' => '优惠券ID必须是整数',
        'coupon_id.gt' => '优惠券ID必须大于0',
        'user_coupon_id.require' => '用户优惠券ID不能为空',
        'user_coupon_id.integer' => '用户优惠券ID必须是整数',
        'user_coupon_id.gt' => '用户优惠券ID必须大于0',
        'order_id.require' => '订单ID不能为空',
        'order_id.integer' => '订单ID必须是整数',
        'order_id.gt' => '订单ID必须大于0',
        'order_amount.require' => '订单金额不能为空',
        'order_amount.float' => '订单金额必须是数字',
        'order_amount.egt' => '订单金额不能小于0',
        'code.require' => '兑换码不能为空',
        'code.alphaNum' => '兑换码格式错误',
        'code.length' => '兑换码长度为8-32位',
    ];

    protected $scene = [
        'detail' => ['id'],
        'receive' => ['coupon_id'],
        'calculate' => ['user_coupon_id', 'order_amount'],
        'use' => ['user_coupon_id', 'order_id'],
        'exchange' => ['code'],
    ];
}
