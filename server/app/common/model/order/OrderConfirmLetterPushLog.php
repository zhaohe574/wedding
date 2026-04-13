<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 订单确认函推送日志模型
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\common\model\order;

use app\common\model\BaseModel;

class OrderConfirmLetterPushLog extends BaseModel
{
    protected $name = 'order_confirm_letter_push_log';

    public const STATUS_SUCCESS = 1;
    public const STATUS_FAILED = 0;
}
