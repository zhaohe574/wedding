<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 吉日设置验证器
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\adminapi\validate\schedule;

use app\common\validate\BaseValidate;

/**
 * 吉日设置验证器
 * Class CalendarEventValidate
 * @package app\adminapi\validate\schedule
 */
class CalendarEventValidate extends BaseValidate
{
    protected $rule = [
        'id' => 'require|integer|gt:0',
        'event_date' => 'require|date',
        'start_date' => 'require|date',
        'end_date' => 'require|date|egt:start_date',
        'lunar_date' => 'max:20',
        'is_lucky_day' => 'in:0,1',
        'lucky_events' => 'max:255',
        'unlucky_events' => 'max:255',
        'is_holiday' => 'in:0,1',
        'holiday_name' => 'max:50',
        'congestion_level' => 'integer|between:0,3',
        'remark' => 'max:255',
    ];

    protected $message = [
        'id.require' => '请选择吉日记录',
        'id.integer' => '吉日记录ID格式错误',
        'event_date.require' => '请选择日期',
        'event_date.date' => '日期格式错误',
        'start_date.require' => '请选择开始日期',
        'start_date.date' => '开始日期格式错误',
        'end_date.require' => '请选择结束日期',
        'end_date.date' => '结束日期格式错误',
        'end_date.egt' => '结束日期不能早于开始日期',
        'lunar_date.max' => '农历最多20个字符',
        'is_lucky_day.in' => '吉日标记参数错误',
        'lucky_events.max' => '宜事项最多255个字符',
        'unlucky_events.max' => '忌事项最多255个字符',
        'is_holiday.in' => '节假日标记参数错误',
        'holiday_name.max' => '节假日名称最多50个字符',
        'congestion_level.between' => '拥堵等级参数错误',
        'remark.max' => '备注最多255个字符',
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
     * @notes 删除场景
     * @return CalendarEventValidate
     */
    public function sceneDelete()
    {
        return $this->only(['id']);
    }

    /**
     * @notes 保存场景
     * @return CalendarEventValidate
     */
    public function sceneSave()
    {
        return $this->only([
            'event_date',
            'lunar_date',
            'is_lucky_day',
            'lucky_events',
            'unlucky_events',
            'is_holiday',
            'holiday_name',
            'congestion_level',
            'remark',
        ]);
    }

    /**
     * @notes 批量保存场景
     * @return CalendarEventValidate
     */
    public function sceneBatchSave()
    {
        return $this->only([
            'start_date',
            'end_date',
            'lunar_date',
            'is_lucky_day',
            'lucky_events',
            'unlucky_events',
            'is_holiday',
            'holiday_name',
            'congestion_level',
            'remark',
        ]);
    }
}
