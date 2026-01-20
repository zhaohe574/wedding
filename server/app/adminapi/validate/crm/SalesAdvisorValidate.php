<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 销售顾问验证器
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\adminapi\validate\crm;

use app\common\validate\BaseValidate;

/**
 * 销售顾问验证器
 * Class SalesAdvisorValidate
 * @package app\adminapi\validate\crm
 */
class SalesAdvisorValidate extends BaseValidate
{
    protected $rule = [
        'id' => 'require|integer|gt:0',
        'admin_id' => 'integer|egt:0',
        'advisor_name' => 'require|max:50',
        'avatar' => 'max:255',
        'mobile' => 'mobile',
        'wechat' => 'max:50',
        'email' => 'email',
        'areas' => 'array',
        'specialties' => 'array',
        'max_customer_count' => 'integer|gt:0',
        'status' => 'integer|in:0,1,2',
        'sort' => 'integer|egt:0',
    ];

    protected $message = [
        'id.require' => '请选择销售顾问',
        'id.integer' => '顾问ID格式错误',
        'advisor_name.require' => '请输入顾问姓名',
        'advisor_name.max' => '顾问姓名最多50个字符',
        'mobile.mobile' => '手机号格式不正确',
        'email.email' => '邮箱格式不正确',
        'max_customer_count.gt' => '最大客户数必须大于0',
        'status.in' => '状态选择不正确',
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
            'admin_id', 'advisor_name', 'avatar', 'mobile', 'wechat', 'email',
            'areas', 'specialties', 'max_customer_count', 'status', 'sort'
        ]);
    }

    /**
     * @notes 编辑场景
     */
    public function sceneEdit()
    {
        return $this->only([
            'id', 'admin_id', 'advisor_name', 'avatar', 'mobile', 'wechat', 'email',
            'areas', 'specialties', 'max_customer_count', 'status', 'sort'
        ])->remove('advisor_name', 'require');
    }

    /**
     * @notes 删除场景
     */
    public function sceneDelete()
    {
        return $this->only(['id']);
    }

    /**
     * @notes 更新状态场景
     */
    public function sceneUpdateStatus()
    {
        return $this->only(['id', 'status'])
            ->append('status', 'require');
    }
}
