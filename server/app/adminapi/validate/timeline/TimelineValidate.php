<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 时间轴验证器
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\adminapi\validate\timeline;

use app\common\validate\BaseValidate;

/**
 * 时间轴验证器
 * Class TimelineValidate
 * @package app\adminapi\validate\timeline
 */
class TimelineValidate extends BaseValidate
{
    protected $rule = [
        'id' => 'require|integer|gt:0',
        'order_id' => 'require|integer|gt:0',
        'template_id' => 'integer|egt:0',
        'task_title' => 'require|max:100',
        'task_desc' => 'max:500',
        'task_type' => 'require|integer|between:1,5',
        'days_before' => 'require|integer',
        'trigger_date' => 'require|date',
        'trigger_time' => 'date',
        'is_completed' => 'integer|in:0,1',
        'complete_remark' => 'max:255',
        'sort' => 'integer|egt:0',
    ];

    protected $message = [
        'id.require' => '请选择时间轴任务',
        'id.integer' => '任务ID格式错误',
        'order_id.require' => '请选择订单',
        'order_id.integer' => '订单ID格式错误',
        'task_title.require' => '请输入任务标题',
        'task_title.max' => '任务标题最多100个字符',
        'task_desc.max' => '任务描述最多500个字符',
        'task_type.require' => '请选择任务类型',
        'task_type.between' => '任务类型不正确',
        'days_before.require' => '请输入提前天数',
        'trigger_date.require' => '请选择触发日期',
        'trigger_date.date' => '触发日期格式错误',
    ];

    /**
     * @notes 详情场景
     */
    public function sceneDetail()
    {
        return $this->only(['id']);
    }

    /**
     * @notes 订单时间轴场景
     */
    public function sceneOrderTimeline()
    {
        return $this->only(['order_id']);
    }

    /**
     * @notes 添加任务场景
     */
    public function sceneAdd()
    {
        return $this->only([
            'order_id', 'task_title', 'task_desc', 'task_type',
            'days_before', 'trigger_date', 'trigger_time', 'sort'
        ]);
    }

    /**
     * @notes 编辑任务场景
     */
    public function sceneEdit()
    {
        return $this->only([
            'id', 'task_title', 'task_desc', 'task_type',
            'trigger_date', 'trigger_time', 'sort'
        ])->remove('task_title', 'require')
            ->remove('task_type', 'require')
            ->remove('trigger_date', 'require');
    }

    /**
     * @notes 删除场景
     */
    public function sceneDelete()
    {
        return $this->only(['id']);
    }

    /**
     * @notes 完成任务场景
     */
    public function sceneComplete()
    {
        return $this->only(['id', 'complete_remark']);
    }

    /**
     * @notes 根据模板生成场景
     */
    public function sceneGenerate()
    {
        return $this->only(['order_id', 'template_id']);
    }
}
