<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 晒单奖励验证器
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\adminapi\validate\review;

use app\common\validate\BaseValidate;

class ReviewShareRewardValidate extends BaseValidate
{
    protected $rule = [
        'id' => 'require|integer|gt:0',
        'status' => 'require|in:1,2',
        'audit_remark' => 'max:255',
    ];

    protected $message = [
        'id.require' => '记录ID不能为空',
        'id.integer' => '记录ID格式错误',
        'id.gt' => '记录ID格式错误',
        'status.require' => '审核状态不能为空',
        'status.in' => '审核状态值错误',
        'audit_remark.max' => '审核备注最多255个字符',
    ];

    protected $scene = [
        'detail' => ['id'],
        'audit' => ['id', 'status', 'audit_remark'],
    ];
}
