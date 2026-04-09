<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 售后工单验证器
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\adminapi\validate\aftersale;

use app\common\validate\BaseValidate;

/**
 * 售后工单验证器
 * Class AfterSaleValidate
 * @package app\adminapi\validate\aftersale
 */
class AfterSaleValidate extends BaseValidate
{
    protected $rule = [
        'id' => 'require|integer|gt:0',
        'user_id' => 'require|integer|gt:0',
        'order_id' => 'integer|egt:0',
        'admin_id' => 'require|integer|gt:0',
        'type' => 'require|in:1,2,3,4,5',
        'priority' => 'in:1,2,3,4',
        'title' => 'require|max:200',
        'content' => 'max:5000',
        'result' => 'require|max:5000',
        'reason' => 'require|max:500',
        'remark' => 'max:500',
        'score' => 'integer|between:0,5',
        'score_service' => 'integer|between:0,5',
        'score_professional' => 'integer|between:0,5',
        'score_punctual' => 'integer|between:0,5',
        'score_overall' => 'integer|between:0,5',
        'duration' => 'integer|egt:0',
        'has_problem' => 'in:0,1',
        'problem_type' => 'max:100',
        'problem_desc' => 'max:1000',
        'plan_time' => 'integer|gt:0',
        'method' => 'in:1,2,3,4',
        'action' => 'in:0,1,2,3,4',
        'amount' => 'float|egt:0',
        'level' => 'in:1,2,3',
        'images' => 'array',
        'videos' => 'array',
    ];

    protected $message = [
        'id.require' => 'ID不能为空',
        'id.integer' => 'ID必须为整数',
        'id.gt' => 'ID必须大于0',
        'user_id.require' => '用户ID不能为空',
        'admin_id.require' => '管理员ID不能为空',
        'type.require' => '类型不能为空',
        'type.in' => '类型值不正确',
        'priority.in' => '优先级值不正确',
        'title.require' => '标题不能为空',
        'title.max' => '标题最多200个字符',
        'content.max' => '内容最多5000个字符',
        'result.require' => '处理结果不能为空',
        'result.max' => '处理结果最多5000个字符',
        'reason.require' => '原因不能为空',
        'reason.max' => '原因最多500个字符',
        'score.between' => '评分必须在0-5之间',
    ];

    protected $scene = [
        // 工单相关
        'ticketDetail' => ['id'],
        'createTicket' => ['user_id', 'type', 'title'],
        'assignTicket' => ['id', 'admin_id'],
        'handleTicket' => ['id', 'result'],
        'closeTicket' => ['id', 'reason'],

        // 投诉相关
        'complaintDetail' => ['id'],
        'handleComplaint' => ['id', 'result', 'action'],

        // 回访相关
        'callbackDetail' => ['id'],
        'createCallback' => ['order_id', 'user_id'],
        'completeCallback' => ['id'],
    ];
}
