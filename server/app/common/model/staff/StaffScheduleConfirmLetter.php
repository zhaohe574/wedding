<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 服务人员档期确认函版本
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\common\model\staff;

use app\common\model\BaseModel;
use app\common\model\order\Order;

class StaffScheduleConfirmLetter extends BaseModel
{
    protected $name = 'staff_schedule_confirm_letter';

    protected $json = ['rendered_snapshot'];

    protected $jsonAssoc = true;

    public const STATUS_ACTIVE = 0;
    public const STATUS_OUTDATED = 1;

    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id', 'id');
    }

    public function staff()
    {
        return $this->belongsTo(Staff::class, 'staff_id', 'id');
    }

    public function scopeCurrent($query)
    {
        return $query->where('is_outdated', self::STATUS_ACTIVE);
    }
}
