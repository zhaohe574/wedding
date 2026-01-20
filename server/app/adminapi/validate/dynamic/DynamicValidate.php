<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 动态验证器
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\adminapi\validate\dynamic;

use app\common\validate\BaseValidate;

/**
 * 动态验证器
 * Class DynamicValidate
 * @package app\adminapi\validate\dynamic
 */
class DynamicValidate extends BaseValidate
{
    protected $rule = [
        'id' => 'require|integer|gt:0',
        'comment_id' => 'require|integer|gt:0',
        'approved' => 'require|boolean',
        'remark' => 'max:255',
        'reason' => 'max:255',
        'is_top' => 'require|in:0,1',
        'is_hot' => 'require|in:0,1',
    ];

    protected $message = [
        'id.require' => '请选择动态',
        'id.integer' => '动态ID格式错误',
        'comment_id.require' => '请选择评论',
        'comment_id.integer' => '评论ID格式错误',
        'approved.require' => '请选择审核结果',
        'approved.boolean' => '审核结果格式错误',
        'remark.max' => '备注最多255个字符',
        'reason.max' => '原因最多255个字符',
        'is_top.require' => '请选择是否置顶',
        'is_top.in' => '置顶参数错误',
        'is_hot.require' => '请选择是否热门',
        'is_hot.in' => '热门参数错误',
    ];

    /**
     * @notes 详情场景
     * @return DynamicValidate
     */
    public function sceneDetail()
    {
        return $this->only(['id']);
    }

    /**
     * @notes 审核场景
     * @return DynamicValidate
     */
    public function sceneAudit()
    {
        return $this->only(['id', 'approved', 'remark']);
    }

    /**
     * @notes 下架场景
     * @return DynamicValidate
     */
    public function sceneOffline()
    {
        return $this->only(['id', 'reason']);
    }

    /**
     * @notes 设置置顶场景
     * @return DynamicValidate
     */
    public function sceneSetTop()
    {
        return $this->only(['id', 'is_top']);
    }

    /**
     * @notes 设置热门场景
     * @return DynamicValidate
     */
    public function sceneSetHot()
    {
        return $this->only(['id', 'is_hot']);
    }

    /**
     * @notes 评论ID场景
     * @return DynamicValidate
     */
    public function sceneCommentId()
    {
        return $this->only(['comment_id']);
    }

    /**
     * @notes 设置评论置顶场景
     * @return DynamicValidate
     */
    public function sceneSetCommentTop()
    {
        return $this->only(['comment_id', 'is_top']);
    }
}
