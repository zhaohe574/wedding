<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 支付验证器
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\adminapi\validate\order;

use app\common\validate\BaseValidate;

/**
 * 支付验证器
 * Class PaymentValidate
 * @package app\adminapi\validate\order
 */
class PaymentValidate extends BaseValidate
{
    protected $rule = [
        'id' => 'require|integer|gt:0',
    ];

    protected $message = [
        'id.require' => '请选择支付记录',
        'id.integer' => '支付ID格式错误',
    ];

    /**
     * @notes 详情场景
     * @return PaymentValidate
     */
    public function sceneDetail()
    {
        return $this->only(['id']);
    }
}
