<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 小程序端购物车验证器
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\api\validate;

use app\common\validate\BaseValidate;

/**
 * 购物车验证器
 * 购物车能力已下线，保留空验证器仅用于兼容旧类引用。
 */
class CartValidate extends BaseValidate
{
    protected $rule = [];
}
