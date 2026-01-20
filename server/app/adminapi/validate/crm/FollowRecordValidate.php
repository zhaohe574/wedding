<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 跟进记录验证器
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\adminapi\validate\crm;

use app\common\validate\BaseValidate;

/**
 * 跟进记录验证器
 * Class FollowRecordValidate
 * @package app\adminapi\validate\crm
 */
class FollowRecordValidate extends BaseValidate
{
    protected $rule = [
        'id' => 'require|integer|gt:0',
        'customer_id' => 'require|integer|gt:0',
        'follow_type' => 'require|integer|between:1,7',
        'follow_content' => 'require|max:5000',
        'follow_result' => 'require|integer|between:1,5',
        'intention_before' => 'in:A,B,C,D',
        'intention_after' => 'in:A,B,C,D',
        'duration' => 'integer|egt:0',
        'next_follow_time' => 'integer|egt:0',
        'next_follow_content' => 'max:255',
        'attachments' => 'array',
        'is_important' => 'integer|in:0,1',
    ];

    protected $message = [
        'id.require' => '请选择跟进记录',
        'id.integer' => '跟进记录ID格式错误',
        'customer_id.require' => '请选择客户',
        'customer_id.integer' => '客户ID格式错误',
        'follow_type.require' => '请选择跟进方式',
        'follow_type.between' => '跟进方式选择不正确',
        'follow_content.require' => '请输入跟进内容',
        'follow_content.max' => '跟进内容最多5000个字符',
        'follow_result.require' => '请选择跟进结果',
        'follow_result.between' => '跟进结果选择不正确',
        'intention_before.in' => '跟进前意向等级选择不正确',
        'intention_after.in' => '跟进后意向等级选择不正确',
        'duration.egt' => '沟通时长不能为负数',
        'next_follow_content.max' => '下次跟进计划最多255个字符',
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
            'customer_id', 'follow_type', 'follow_content', 'follow_result',
            'intention_before', 'intention_after', 'duration',
            'next_follow_time', 'next_follow_content', 'attachments', 'is_important'
        ]);
    }

    /**
     * @notes 编辑场景
     */
    public function sceneEdit()
    {
        return $this->only([
            'id', 'follow_type', 'follow_content', 'follow_result',
            'intention_after', 'duration',
            'next_follow_time', 'next_follow_content', 'attachments', 'is_important'
        ])->remove('follow_type', 'require')
            ->remove('follow_content', 'require')
            ->remove('follow_result', 'require');
    }

    /**
     * @notes 删除场景
     */
    public function sceneDelete()
    {
        return $this->only(['id']);
    }

    /**
     * @notes 客户跟进记录列表场景
     */
    public function sceneCustomerRecords()
    {
        return $this->only(['customer_id']);
    }
}
