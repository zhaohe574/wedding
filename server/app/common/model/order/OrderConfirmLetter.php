<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 订单确认函版本模型
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\common\model\order;

use app\common\model\BaseModel;

class OrderConfirmLetter extends BaseModel
{
    protected $name = 'order_confirm_letter';

    protected $json = ['rendered_snapshot'];

    public const STATUS_ACTIVE = 0;
    public const STATUS_OUTDATED = 1;

    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id', 'id');
    }

    public function scopeCurrent($query)
    {
        return $query->where('is_outdated', self::STATUS_ACTIVE);
    }
}
