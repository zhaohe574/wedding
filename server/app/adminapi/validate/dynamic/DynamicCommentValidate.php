<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 动态评论审核验证器
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\adminapi\validate\dynamic;

use app\common\validate\BaseValidate;

/**
 * 动态评论审核验证器
 * Class DynamicCommentValidate
 * @package app\adminapi\validate\dynamic
 */
class DynamicCommentValidate extends BaseValidate
{
    protected $rule = [
        'id' => 'require|integer',
        'ids' => 'require|array',
        'remark' => 'max:255',
    ];

    protected $message = [
        'id.require' => '请选择评论',
        'id.integer' => '评论ID格式错误',
        'ids.require' => '请选择评论',
        'ids.array' => '评论ID格式错误',
        'remark.max' => '备注不能超过255个字符',
    ];

    /**
     * @notes 审核通过场景
     * @return DynamicCommentValidate
     */
    public function sceneApprove()
    {
        return $this->only(['id']);
    }

    /**
     * @notes 详情场景
     * @return DynamicCommentValidate
     */
    public function sceneDetail()
    {
        return $this->only(['id']);
    }

    /**
     * @notes 拒绝场景
     * @return DynamicCommentValidate
     */
    public function sceneReject()
    {
        return $this->only(['id', 'remark']);
    }

    /**
     * @notes 批量审核通过场景
     * @return DynamicCommentValidate
     */
    public function sceneBatchApprove()
    {
        return $this->only(['ids']);
    }

    /**
     * @notes 批量拒绝场景
     * @return DynamicCommentValidate
     */
    public function sceneBatchReject()
    {
        return $this->only(['ids', 'remark']);
    }

    /**
     * @notes 删除场景
     * @return DynamicCommentValidate
     */
    public function sceneDelete()
    {
        return $this->only(['id']);
    }

    /**
     * @notes 批量删除场景
     * @return DynamicCommentValidate
     */
    public function sceneBatchDelete()
    {
        return $this->only(['ids']);
    }
}
