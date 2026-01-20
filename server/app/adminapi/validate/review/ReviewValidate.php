<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 评价验证器
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\adminapi\validate\review;

use app\common\validate\BaseValidate;
use app\common\model\review\Review;

/**
 * 评价验证器
 * Class ReviewValidate
 * @package app\adminapi\validate\review
 */
class ReviewValidate extends BaseValidate
{
    protected $rule = [
        'id' => 'require|integer|gt:0',
        'ids' => 'require|array',
        'status' => 'require|in:1,2',
        'reject_reason' => 'requireIf:status,2|max:255',
        'content' => 'require|max:500',
    ];

    protected $message = [
        'id.require' => '评价ID不能为空',
        'id.integer' => '评价ID必须是整数',
        'id.gt' => '评价ID必须大于0',
        'ids.require' => '评价ID不能为空',
        'ids.array' => '评价ID格式错误',
        'status.require' => '审核状态不能为空',
        'status.in' => '审核状态值错误',
        'reject_reason.requireIf' => '拒绝原因不能为空',
        'reject_reason.max' => '拒绝原因最多255个字符',
        'content.require' => '回复内容不能为空',
        'content.max' => '回复内容最多500个字符',
    ];

    protected $scene = [
        'detail' => ['id'],
        'audit' => ['id', 'status', 'reject_reason'],
        'batchAudit' => ['ids', 'status', 'reject_reason'],
        'reply' => ['id', 'content'],
    ];
}
