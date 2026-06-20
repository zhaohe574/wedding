<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 服务人员档期确认函配置
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\common\model\staff;

use app\common\model\BaseModel;

class StaffScheduleConfirmLetterConfig extends BaseModel
{
    protected $name = 'staff_schedule_confirm_letter_config';

    protected $json = ['design_config'];

    protected $jsonAssoc = true;

    public function staff()
    {
        return $this->belongsTo(Staff::class, 'staff_id', 'id');
    }
}
