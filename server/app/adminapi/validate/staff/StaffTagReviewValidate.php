<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 服务人员标签审核验证
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\adminapi\validate\staff;

use app\common\validate\BaseValidate;

class StaffTagReviewValidate extends BaseValidate
{
    protected $rule = [
        'id' => 'require|integer|gt:0',
        'status' => 'in:0,1,2',
        'source' => 'in:1,2',
        'staff_id' => 'integer|gt:0',
        'category_id' => 'integer|gt:0',
        'keyword' => 'max:100',
        'reject_reason' => 'require|max:255',
    ];

    protected $message = [
        'id.require' => '请选择标签申请',
        'id.integer' => '标签申请参数错误',
        'id.gt' => '标签申请参数错误',
        'status.in' => '审核状态值错误',
        'source.in' => '来源值错误',
        'staff_id.integer' => '服务人员参数错误',
        'staff_id.gt' => '服务人员参数错误',
        'category_id.integer' => '分类参数错误',
        'category_id.gt' => '分类参数错误',
        'keyword.max' => '关键词长度不能超过100个字符',
        'reject_reason.require' => '请输入拒绝原因',
        'reject_reason.max' => '拒绝原因长度不能超过255个字符',
    ];

    public function sceneDetail(): StaffTagReviewValidate
    {
        return $this->only(['id']);
    }

    public function sceneApprove(): StaffTagReviewValidate
    {
        return $this->only(['id']);
    }

    public function sceneReject(): StaffTagReviewValidate
    {
        return $this->only(['id', 'reject_reason']);
    }
}
