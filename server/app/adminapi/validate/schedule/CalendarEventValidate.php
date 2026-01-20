<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 日历事件验证器
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\adminapi\validate\schedule;

use app\common\validate\BaseValidate;

/**
 * 日历事件验证器
 * Class CalendarEventValidate
 * @package app\adminapi\validate\schedule
 */
class CalendarEventValidate extends BaseValidate
{
    protected $rule = [
        'id' => 'require|integer|gt:0',
        'event_date' => 'require|date',
        'lunar_date' => 'max:20',
        'is_lucky_day' => 'in:0,1',
        'lucky_events' => 'max:255',
        'unlucky_events' => 'max:255',
        'is_holiday' => 'in:0,1',
        'holiday_name' => 'max:50',
        'congestion_level' => 'in:0,1,2,3',
        'remark' => 'max:255',
        'year' => 'integer|between:2020,2100',
        'month' => 'integer|between:1,12',
        'data' => 'require|array',
    ];

    protected $message = [
        'id.require' => '请选择事件',
        'id.integer' => '事件ID格式错误',
        'event_date.require' => '请选择日期',
        'event_date.date' => '日期格式错误',
        'lunar_date.max' => '农历日期最多20个字符',
        'is_lucky_day.in' => '吉日参数错误',
        'lucky_events.max' => '宜事项最多255个字符',
        'unlucky_events.max' => '忌事项最多255个字符',
        'is_holiday.in' => '节假日参数错误',
        'holiday_name.max' => '节假日名称最多50个字符',
        'congestion_level.in' => '拥堵等级参数错误',
        'remark.max' => '备注最多255个字符',
        'year.between' => '年份参数错误',
        'month.between' => '月份参数错误',
        'data.require' => '导入数据不能为空',
        'data.array' => '导入数据格式错误',
    ];

    /**
     * @notes 详情场景
     * @return CalendarEventValidate
     */
    public function sceneDetail()
    {
        return $this->only(['id']);
    }

    /**
     * @notes 日历场景
     * @return CalendarEventValidate
     */
    public function sceneCalendar()
    {
        return $this->only(['year', 'month']);
    }

    /**
     * @notes 添加场景
     * @return CalendarEventValidate
     */
    public function sceneAdd()
    {
        return $this->only(['event_date', 'lunar_date', 'is_lucky_day', 'lucky_events', 'unlucky_events', 'is_holiday', 'holiday_name', 'congestion_level', 'remark']);
    }

    /**
     * @notes 编辑场景
     * @return CalendarEventValidate
     */
    public function sceneEdit()
    {
        return $this->only(['id', 'event_date', 'lunar_date', 'is_lucky_day', 'lucky_events', 'unlucky_events', 'is_holiday', 'holiday_name', 'congestion_level', 'remark']);
    }

    /**
     * @notes 删除场景
     * @return CalendarEventValidate
     */
    public function sceneDelete()
    {
        return $this->only(['id']);
    }

    /**
     * @notes 导入场景
     * @return CalendarEventValidate
     */
    public function sceneImport()
    {
        return $this->only(['data']);
    }
}
