<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 客户验证器
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\adminapi\validate\crm;

use app\common\validate\BaseValidate;

/**
 * 客户验证器
 * Class CustomerValidate
 * @package app\adminapi\validate\crm
 */
class CustomerValidate extends BaseValidate
{
    protected $rule = [
        'id' => 'require|integer|gt:0',
        'customer_name' => 'require|max:50',
        'customer_mobile' => 'mobile',
        'customer_wechat' => 'max:50',
        'gender' => 'integer|in:0,1,2',
        'age' => 'integer|between:0,150',
        'city' => 'max:50',
        'district' => 'max:50',
        'intention_level' => 'in:A,B,C,D',
        'intention_score' => 'integer|between:0,100',
        'wedding_date' => 'date',
        'wedding_venue' => 'max:200',
        'wedding_budget' => 'float|egt:0',
        'budget_range' => 'max:50',
        'service_needs' => 'array',
        'source_channel' => 'integer|between:1,6',
        'source_detail' => 'max:100',
        'tags' => 'array',
        'customer_status' => 'integer|between:1,5',
        'advisor_id' => 'integer|egt:0',
        'remark' => 'max:500',
        'next_follow_time' => 'integer|egt:0',
        'loss_reason' => 'max:200',
    ];

    protected $message = [
        'id.require' => '请选择客户',
        'id.integer' => '客户ID格式错误',
        'customer_name.require' => '请输入客户姓名',
        'customer_name.max' => '客户姓名最多50个字符',
        'customer_mobile.mobile' => '手机号格式不正确',
        'gender.in' => '性别选择不正确',
        'age.between' => '年龄范围不正确',
        'intention_level.in' => '意向等级选择不正确',
        'intention_score.between' => '意向评分范围为0-100',
        'wedding_date.date' => '婚期日期格式错误',
        'wedding_budget.egt' => '预算金额不能为负数',
        'source_channel.between' => '来源渠道选择不正确',
        'customer_status.between' => '客户状态选择不正确',
    ];

    /**
     * @notes 详情场景
     */
    public function sceneDetail()
    {
        return $this->only(['id']);
    }

    /**
     * @notes 添加场景
     */
    public function sceneAdd()
    {
        return $this->only([
            'customer_name', 'customer_mobile', 'customer_wechat', 
            'gender', 'age', 'city', 'district',
            'intention_level', 'intention_score',
            'wedding_date', 'wedding_venue', 'wedding_budget', 'budget_range',
            'service_needs', 'source_channel', 'source_detail',
            'tags', 'advisor_id', 'remark', 'next_follow_time'
        ]);
    }

    /**
     * @notes 编辑场景
     */
    public function sceneEdit()
    {
        return $this->only([
            'id', 'customer_name', 'customer_mobile', 'customer_wechat', 
            'gender', 'age', 'city', 'district',
            'intention_level', 'intention_score',
            'wedding_date', 'wedding_venue', 'wedding_budget', 'budget_range',
            'service_needs', 'source_channel', 'source_detail',
            'tags', 'remark', 'next_follow_time'
        ])->remove('customer_name', 'require');
    }

    /**
     * @notes 删除场景
     */
    public function sceneDelete()
    {
        return $this->only(['id']);
    }

    /**
     * @notes 分配顾问场景
     */
    public function sceneAssign()
    {
        return $this->only(['id', 'advisor_id'])
            ->append('advisor_id', 'require|gt:0');
    }

    /**
     * @notes 标记流失场景
     */
    public function sceneLoss()
    {
        return $this->only(['id', 'loss_reason']);
    }

    /**
     * @notes 更新意向等级场景
     */
    public function sceneUpdateIntention()
    {
        return $this->only(['id', 'intention_level', 'intention_score'])
            ->append('intention_level', 'require');
    }
}
