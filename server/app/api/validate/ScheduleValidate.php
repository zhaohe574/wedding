<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 小程序端档期验证器
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\api\validate;

use app\common\validate\BaseValidate;

/**
 * 小程序端档期验证器
 * Class ScheduleValidate
 * @package app\api\validate
 */
class ScheduleValidate extends BaseValidate
{
    protected $rule = [
        'id' => 'require|integer|gt:0',
        'staff_id' => 'require|integer|gt:0',
        'date' => 'require|date',
        'time_slot' => 'integer|between:0,3',
        'time_slots' => 'array',
        'year' => 'integer|between:2020,2100',
        'month' => 'integer|between:1,12',
        'package_id' => 'require|integer|gt:0',
        'lock_duration' => 'integer|between:60,3600',
        'remark' => 'max:255',
    ];

    protected $message = [
        'id.require' => '请选择记录',
        'id.integer' => 'ID格式错误',
        'staff_id.require' => '请选择工作人员',
        'staff_id.integer' => '工作人员ID格式错误',
        'date.require' => '请选择日期',
        'date.date' => '日期格式错误',
        'time_slot.between' => '时间段参数错误',
        'year.between' => '年份参数错误',
        'month.between' => '月份参数错误',
        'package_id.require' => '请选择套餐',
        'package_id.gt' => '套餐ID格式错误',
        'time_slots.array' => '时间段列表格式错误',
        'lock_duration.between' => '锁定时长应在60-3600秒之间',
        'remark.max' => '备注最多255个字符',
    ];

    /**
     * @notes 工作人员档期场景
     * @return ScheduleValidate
     */
    public function sceneStaffSchedule()
    {
        return $this->only(['staff_id', 'year', 'month']);
    }

    /**
     * @notes 日历场景
     * @return ScheduleValidate
     */
    public function sceneCalendar()
    {
        return $this->only(['year', 'month']);
    }

    /**
     * @notes 检查场景
     * @return ScheduleValidate
     */
    public function sceneCheck()
    {
        return $this->only(['staff_id', 'date', 'time_slot']);
    }

    /**
     * @notes 锁定场景
     * @return ScheduleValidate
     */
    public function sceneLock()
    {
        return $this->only(['staff_id', 'date', 'time_slot', 'lock_duration']);
    }

    /**
     * @notes 释放场景
     * @return ScheduleValidate
     */
    public function sceneRelease()
    {
        return $this->only(['staff_id', 'date', 'time_slot']);
    }

    /**
     * @notes 候补场景
     * @return ScheduleValidate
     */
    public function sceneWaitlist()
    {
        return $this->only(['staff_id', 'date', 'time_slot', 'time_slots', 'package_id', 'remark']);
    }

    /**
     * @notes 取消候补场景
     * @return ScheduleValidate
     */
    public function sceneCancelWaitlist()
    {
        return $this->only(['id']);
    }
}
