<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 档期验证器
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\adminapi\validate\schedule;

use app\common\validate\BaseValidate;

/**
 * 档期验证器
 * Class ScheduleValidate
 * @package app\adminapi\validate\schedule
 */
class ScheduleValidate extends BaseValidate
{
    protected $rule = [
        'id' => 'require|integer|gt:0',
        'staff_id' => 'require|integer|gt:0',
        'staff_ids' => 'require|array',
        'date' => 'require|date',
        'start_date' => 'require|date',
        'end_date' => 'require|date|egt:start_date',
        'time_slot' => 'integer|between:0,3',
        'time_slots' => 'array',
        'status' => 'require|integer|between:0,4',
        'lock_type' => 'integer|between:0,2',
        'year' => 'integer|between:2020,2100',
        'month' => 'integer|between:1,12',
        'price' => 'float|egt:0',
        'reason' => 'max:255',
        'remark' => 'max:255',
    ];

    protected $message = [
        'id.require' => '请选择档期',
        'id.integer' => '档期ID格式错误',
        'staff_id.require' => '请选择工作人员',
        'staff_id.integer' => '工作人员ID格式错误',
        'staff_ids.require' => '请选择工作人员',
        'staff_ids.array' => '工作人员ID格式错误',
        'date.require' => '请选择日期',
        'date.date' => '日期格式错误',
        'start_date.require' => '请选择开始日期',
        'start_date.date' => '开始日期格式错误',
        'end_date.require' => '请选择结束日期',
        'end_date.date' => '结束日期格式错误',
        'end_date.egt' => '结束日期不能早于开始日期',
        'time_slot.between' => '时间段参数错误',
        'status.require' => '请选择状态',
        'status.between' => '状态参数错误',
        'lock_type.between' => '锁定类型参数错误',
        'year.between' => '年份参数错误',
        'month.between' => '月份参数错误',
        'price.egt' => '价格不能为负数',
        'reason.max' => '原因最多255个字符',
        'remark.max' => '备注最多255个字符',
    ];

    /**
     * @notes 详情场景
     * @return ScheduleValidate
     */
    public function sceneDetail()
    {
        return $this->only(['id']);
    }

    /**
     * @notes 日历场景
     * @return ScheduleValidate
     */
    public function sceneCalendar()
    {
        return $this->only(['staff_id', 'year', 'month'])
            ->remove('staff_id', 'require');
    }

    /**
     * @notes 设置状态场景
     * @return ScheduleValidate
     */
    public function sceneSetStatus()
    {
        return $this->only(['staff_id', 'date', 'time_slot', 'status', 'remark']);
    }

    /**
     * @notes 批量设置场景
     * @return ScheduleValidate
     */
    public function sceneBatchSet()
    {
        return $this->only(['staff_ids', 'start_date', 'end_date', 'time_slots', 'status', 'price']);
    }

    /**
     * @notes 锁定场景
     * @return ScheduleValidate
     */
    public function sceneLock()
    {
        return $this->only(['staff_id', 'date', 'time_slot', 'lock_type', 'reason']);
    }

    /**
     * @notes 释放锁定场景
     * @return ScheduleValidate
     */
    public function sceneUnlock()
    {
        return $this->only(['id', 'reason'])
            ->remove('reason', 'require');
    }

    /**
     * @notes 预留场景
     * @return ScheduleValidate
     */
    public function sceneReserve()
    {
        return $this->only(['staff_id', 'date', 'time_slot', 'reason']);
    }
}
