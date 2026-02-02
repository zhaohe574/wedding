<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 优惠券验证器
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\adminapi\validate\coupon;

use app\common\validate\BaseValidate;
use app\common\model\coupon\Coupon;

/**
 * 优惠券验证器
 * Class CouponValidate
 * @package app\adminapi\validate\coupon
 */
class CouponValidate extends BaseValidate
{
    protected $rule = [
        'id' => 'require|integer|gt:0',
        'name' => 'require|max:100',
        'coupon_type' => 'require|in:1,2,3',
        'threshold_amount' => 'float|egt:0',
        'discount_amount' => 'require|float|gt:0',
        'max_discount' => 'float|egt:0',
        'total_count' => 'integer|egt:0',
        'per_limit' => 'integer|egt:0',
        'receive_start_time' => 'max:20',
        'receive_end_time' => 'max:20',
        'valid_type' => 'require|in:1,2',
        'valid_start_time' => 'requireIf:valid_type,1',
        'valid_end_time' => 'requireIf:valid_type,1',
        'valid_days' => 'requireIf:valid_type,2|integer',
        'use_scope' => 'in:1,2,3',
        'scope_ids' => 'array',
        'status' => 'in:0,1',
        'remark' => 'max:255',
        'coupon_id' => 'require|integer|gt:0',
        'user_id' => 'require|integer|gt:0',
        'user_ids' => 'require|array',
        'user_coupon_id' => 'require|integer|gt:0',
    ];

    protected $message = [
        'id.require' => '优惠券ID不能为空',
        'id.integer' => '优惠券ID必须是整数',
        'id.gt' => '优惠券ID必须大于0',
        'name.require' => '优惠券名称不能为空',
        'name.max' => '优惠券名称最多100个字符',
        'coupon_type.require' => '优惠券类型不能为空',
        'coupon_type.in' => '优惠券类型值错误',
        'threshold_amount.float' => '使用门槛必须是数字',
        'threshold_amount.egt' => '使用门槛不能小于0',
        'discount_amount.require' => '优惠金额不能为空',
        'discount_amount.float' => '优惠金额必须是数字',
        'discount_amount.gt' => '优惠金额必须大于0',
        'max_discount.float' => '最大优惠金额必须是数字',
        'max_discount.egt' => '最大优惠金额不能小于0',
        'total_count.integer' => '发放总量必须是整数',
        'total_count.egt' => '发放总量不能小于0',
        'per_limit.integer' => '每人限领数量必须是整数',
        'per_limit.egt' => '每人限领数量不能小于0',
        'receive_start_time.max' => '领取开始时间格式错误',
        'receive_end_time.max' => '领取结束时间格式错误',
        'valid_type.require' => '有效期类型不能为空',
        'valid_type.in' => '有效期类型值错误',
        'valid_start_time.requireIf' => '有效期开始时间不能为空',
        'valid_start_time.date' => '有效期开始时间格式错误',
        'valid_end_time.requireIf' => '有效期结束时间不能为空',
        'valid_end_time.date' => '有效期结束时间格式错误',
        'valid_days.requireIf' => '有效天数不能为空',
        'valid_days.integer' => '有效天数必须是整数',
        'valid_days.gt' => '有效天数必须大于0',
        'use_scope.in' => '使用范围值错误',
        'scope_ids.array' => '适用范围ID格式错误',
        'status.in' => '状态值错误',
        'remark.max' => '备注最多255个字符',
        'coupon_id.require' => '优惠券ID不能为空',
        'coupon_id.integer' => '优惠券ID必须是整数',
        'coupon_id.gt' => '优惠券ID必须大于0',
        'user_id.require' => '用户ID不能为空',
        'user_id.integer' => '用户ID必须是整数',
        'user_id.gt' => '用户ID必须大于0',
        'user_ids.require' => '用户ID不能为空',
        'user_ids.array' => '用户ID格式错误',
        'user_coupon_id.require' => '用户优惠券ID不能为空',
        'user_coupon_id.integer' => '用户优惠券ID必须是整数',
        'user_coupon_id.gt' => '用户优惠券ID必须大于0',
    ];

    protected $scene = [
        'detail' => ['id'],
        'add' => ['name', 'coupon_type', 'threshold_amount', 'discount_amount', 'max_discount', 'total_count', 'per_limit', 'receive_start_time', 'receive_end_time', 'valid_type', 'valid_start_time', 'valid_end_time', 'valid_days', 'use_scope', 'scope_ids', 'status', 'remark'],
        'edit' => ['id', 'name', 'coupon_type', 'threshold_amount', 'discount_amount', 'max_discount', 'total_count', 'per_limit', 'receive_start_time', 'receive_end_time', 'valid_type', 'valid_start_time', 'valid_end_time', 'valid_days', 'use_scope', 'scope_ids', 'status', 'remark'],
        'send' => ['coupon_id', 'user_id'],
        'batchSend' => ['coupon_id', 'user_ids'],
        'revoke' => ['user_coupon_id'],
    ];

    /**
     * @notes 添加场景额外验证
     * @return CouponValidate
     */
    public function sceneAdd(): CouponValidate
    {
        return $this->only(['name', 'coupon_type', 'threshold_amount', 'discount_amount', 'max_discount', 'total_count', 'per_limit', 'receive_start_time', 'receive_end_time', 'valid_type', 'valid_start_time', 'valid_end_time', 'valid_days', 'use_scope', 'scope_ids', 'status', 'remark'])
            ->append('discount_amount', 'checkDiscountAmount')
            ->append('valid_days', 'checkValidDays')
            ->append('valid_start_time', 'checkValidTime')
            ->append('receive_start_time', 'checkReceiveTime');
    }

    /**
     * @notes 编辑场景额外验证
     * @return CouponValidate
     */
    public function sceneEdit(): CouponValidate
    {
        return $this->only(['id', 'name', 'coupon_type', 'threshold_amount', 'discount_amount', 'max_discount', 'total_count', 'per_limit', 'receive_start_time', 'receive_end_time', 'valid_type', 'valid_start_time', 'valid_end_time', 'valid_days', 'use_scope', 'scope_ids', 'status', 'remark'])
            ->append('discount_amount', 'checkDiscountAmount')
            ->append('valid_days', 'checkValidDays')
            ->append('valid_start_time', 'checkValidTime')
            ->append('receive_start_time', 'checkReceiveTime');
    }

    /**
     * @notes 验证折扣金额
     * @param $value
     * @param $rule
     * @param $data
     * @return bool|string
     */
    protected function checkDiscountAmount($value, $rule, $data)
    {
        $couponType = $data['coupon_type'] ?? 1;

        // 折扣券时，折扣率应该在1-99之间
        if ($couponType == Coupon::TYPE_DISCOUNT) {
            if ($value < 1 || $value > 99) {
                return '折扣率应在1-99之间';
            }
        }

        return true;
    }

    /**
     * @notes 验证有效天数
     * @param $value
     * @param $rule
     * @param $data
     * @return bool|string
     */
    protected function checkValidDays($value, $rule, $data)
    {
        // 只有当有效期类型为领取后生效时，才验证有效天数
        if (($data['valid_type'] ?? 1) == Coupon::VALID_TYPE_DAYS) {
            if (empty($value) || $value <= 0) {
                return '有效天数必须大于0';
            }
        }

        return true;
    }

    /**
     * @notes 验证有效期
     * @param $value
     * @param $rule
     * @param $data
     * @return bool|string
     */
    protected function checkValidTime($value, $rule, $data)
    {
        if (($data['valid_type'] ?? 1) == Coupon::VALID_TYPE_FIXED) {
            if (empty($data['valid_start_time']) || empty($data['valid_end_time'])) {
                return '固定日期类型必须填写开始和结束时间';
            }

            // 验证日期格式
            $startTime = strtotime($data['valid_start_time']);
            $endTime = strtotime($data['valid_end_time']);

            if ($startTime === false) {
                return '有效期开始时间格式错误';
            }

            if ($endTime === false) {
                return '有效期结束时间格式错误';
            }

            if ($endTime <= $startTime) {
                return '结束时间必须大于开始时间';
            }
        }

        return true;
    }

    /**
     * @notes 验证领取时间段
     * @param $value
     * @param $rule
     * @param $data
     * @return bool|string
     */
    protected function checkReceiveTime($value, $rule, $data)
    {
        $startRaw = $data['receive_start_time'] ?? '';
        $endRaw = $data['receive_end_time'] ?? '';

        if ($startRaw === '' && $endRaw === '') {
            return true;
        }

        if (empty($startRaw) || empty($endRaw)) {
            return '领取时间段必须同时填写开始和结束时间';
        }

        $startTime = is_numeric($startRaw) ? (int)$startRaw : strtotime($startRaw);
        $endTime = is_numeric($endRaw) ? (int)$endRaw : strtotime($endRaw);

        if ($startTime <= 0 || $endTime <= 0) {
            return '领取时间格式错误';
        }

        if ($endTime <= $startTime) {
            return '领取结束时间必须大于开始时间';
        }

        return true;
    }
}
