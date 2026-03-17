<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 小程序端购物车业务逻辑
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\api\logic;

use app\common\logic\BaseLogic;

/**
 * 小程序端购物车业务逻辑
 * Class CartLogic
 * @package app\api\logic
 */
class CartLogic extends BaseLogic
{
    /**
     * @notes 购物车模块统一下线
     * @return array
     */
    public static function offlineResult(): array
    {
        return [
            'success' => false,
            'message' => '购物车功能已下线，请直接下单',
        ];
    }
}
