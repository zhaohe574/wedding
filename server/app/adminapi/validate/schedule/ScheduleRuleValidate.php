<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 档期规则验证器
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\adminapi\validate\schedule;

use app\common\validate\BaseValidate;

/**
 * 档期规则验证器
 * Class ScheduleRuleValidate
 * @package app\adminapi\validate\schedule
 */
class ScheduleRuleValidate extends BaseValidate
{
    protected $rule = [
        'id' => 'require|integer|gt:0',
        'staff_id' => 'integer|egt:0',
        'advance_days' => 'integer|between:0,365',
        'max_orders_per_day' => 'integer|between:1,10',
        'rest_days' => 'array|max:7',
        'is_enabled' => 'in:0,1',
    ];

    protected $message = [
        'id.require' => '请选择规则',
        'id.integer' => '规则ID格式错误',
        'staff_id.integer' => '工作人员ID格式错误',
        'staff_id.egt' => '工作人员ID不能为负数',
        'advance_days.between' => '提前预约天数应在0-365之间',
        'max_orders_per_day.between' => '单日最大接单数应在1-10之间',
        'rest_days.array' => '休息日格式错误',
        'rest_days.max' => '休息日最多7天',
        'is_enabled.in' => '启用状态参数错误',
    ];

    /**
     * @notes 详情场景
     * @return ScheduleRuleValidate
     */
    public function sceneDetail()
    {
        return $this->only(['id']);
    }

    /**
     * @notes 添加场景
     * @return ScheduleRuleValidate
     */
    public function sceneAdd()
    {
        return $this->only(['staff_id', 'advance_days', 'max_orders_per_day', 'rest_days', 'is_enabled']);
    }

    /**
     * @notes 编辑场景
     * @return ScheduleRuleValidate
     */
    public function sceneEdit()
    {
        return $this->only(['id', 'advance_days', 'max_orders_per_day', 'rest_days', 'is_enabled']);
    }

    /**
     * @notes 删除场景
     * @return ScheduleRuleValidate
     */
    public function sceneDelete()
    {
        return $this->only(['id']);
    }

    /**
     * @notes 状态场景
     * @return ScheduleRuleValidate
     */
    public function sceneStatus()
    {
        return $this->only(['id']);
    }

    /**
     * @notes 工作人员规则场景
     * @return ScheduleRuleValidate
     */
    public function sceneStaffRule()
    {
        return $this->only(['staff_id'])
            ->append('staff_id', 'require|gt:0');
    }
}
