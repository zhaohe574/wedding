<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 平台统一话术模板验证器
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\adminapi\validate\staff;

use app\common\validate\BaseValidate;

/**
 * 平台统一话术模板验证器
 */
class StaffCopyTemplateValidate extends BaseValidate
{
    protected $rule = [
        'id' => 'require|integer|gt:0',
        'scene' => 'require|in:inquiry,confirm,pre_wedding,service_day,after_service,waitlist',
        'title' => 'require|max:80',
        'content' => 'require|max:1000',
        'sort' => 'integer|between:0,9999',
        'status' => 'in:0,1',
    ];

    protected $message = [
        'id.require' => '请选择模板',
        'id.integer' => '模板参数错误',
        'id.gt' => '模板参数错误',
        'scene.require' => '请选择话术场景',
        'scene.in' => '话术场景参数错误',
        'title.require' => '请输入模板标题',
        'title.max' => '模板标题最多80个字符',
        'content.require' => '请输入文案正文',
        'content.max' => '文案正文最多1000个字符',
        'sort.integer' => '排序必须是整数',
        'sort.between' => '排序需在0-9999之间',
        'status.in' => '状态值错误',
    ];

    protected $scene = [
        'add' => ['scene', 'title', 'content', 'sort', 'status'],
        'edit' => ['id', 'scene', 'title', 'content', 'sort', 'status'],
    ];

    /**
     * @notes 详情场景
     */
    public function sceneDetail(): StaffCopyTemplateValidate
    {
        return $this->only(['id']);
    }

    /**
     * @notes 删除场景
     */
    public function sceneDelete(): StaffCopyTemplateValidate
    {
        return $this->only(['id']);
    }

    /**
     * @notes 启停场景（status 必传）
     */
    public function sceneStatus(): StaffCopyTemplateValidate
    {
        return $this->only(['id', 'status'])
            ->append('status', 'require');
    }
}
