<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 小程序端售后工单验证器
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\api\validate;

use app\common\validate\BaseValidate;

/**
 * 小程序端售后工单验证器
 * Class AfterSaleValidate
 * @package app\api\validate
 */
class AfterSaleValidate extends BaseValidate
{
    protected $rule = [
        'id' => 'require|integer|gt:0',
        'order_id' => 'require|integer|gt:0',
        'staff_id' => 'integer|egt:0',
        'type' => 'require|in:1,2,3,4,5',
        'title' => 'require|max:200',
        'content' => 'max:5000',
        'images' => 'array',
        'videos' => 'array',
        'satisfaction' => 'integer|between:1,5',
        'remark' => 'max:500',
        'level' => 'in:1,2,3',
        'expect_result' => 'max:500',
        'reason_type' => 'in:1,2,3,4,5',
        'reason' => 'max:2000',
        'expect_date' => 'date',
        'expect_time_slot' => 'max:50',
        'score' => 'integer|between:1,5',
        'score_service' => 'integer|between:1,5',
        'score_professional' => 'integer|between:1,5',
        'score_punctual' => 'integer|between:1,5',
        'score_overall' => 'integer|between:1,5',
        'feedback' => 'max:1000',
        'questionnaire_id' => 'integer|egt:0',
        'answers' => 'array',
    ];

    protected $message = [
        'id.require' => 'ID不能为空',
        'id.integer' => 'ID必须为整数',
        'id.gt' => 'ID必须大于0',
        'order_id.require' => '订单ID不能为空',
        'type.require' => '类型不能为空',
        'type.in' => '类型值不正确',
        'title.require' => '标题不能为空',
        'title.max' => '标题最多200个字符',
        'content.max' => '内容最多5000个字符',
        'satisfaction.between' => '满意度评分必须在1-5之间',
        'level.in' => '等级值不正确',
        'reason_type.in' => '原因类型值不正确',
        'expect_date.date' => '日期格式不正确',
        'score.between' => '评分必须在1-5之间',
    ];

    protected $scene = [
        'detail' => ['id'],
        'createTicket' => ['type', 'title'],
        'confirm' => ['id'],
        'submitComplaint' => ['order_id', 'type', 'title'],
        'rate' => ['id', 'satisfaction'],
        'applyReshoot' => ['order_id', 'type', 'reason_type'],
        'submitQuestionnaire' => ['id'],
    ];
}
