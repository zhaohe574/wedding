<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 跟进记录验证器
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\adminapi\validate\crm;

use app\common\model\crm\Customer;
use app\common\model\crm\FollowRecord;
use app\common\validate\BaseValidate;

/**
 * 跟进记录验证器
 */
class FollowRecordValidate extends BaseValidate
{
    protected $rule = [
        'id' => 'require|integer|gt:0|checkRecord',
        'customer_id' => 'require|integer|gt:0|checkCustomer',
        'follow_type' => 'require|in:1,2,3,4,5,6,7',
        'follow_content' => 'require|max:2000',
        'follow_result' => 'require|in:1,2,3,4,5',
        'intention_after' => 'in:A,B,C,D',
        'duration' => 'integer|between:0,10000',
        'next_follow_time' => 'max:30',
        'next_follow_content' => 'max:255',
        'is_important' => 'in:0,1',
    ];

    protected $message = [
        'id.require' => '请选择跟进记录',
        'id.integer' => '跟进记录参数错误',
        'id.gt' => '跟进记录参数错误',
        'customer_id.require' => '请选择客户',
        'customer_id.integer' => '客户参数错误',
        'customer_id.gt' => '客户参数错误',
        'follow_type.require' => '请选择跟进方式',
        'follow_type.in' => '跟进方式参数错误',
        'follow_content.require' => '请输入跟进内容',
        'follow_content.max' => '跟进内容最多2000个字符',
        'follow_result.require' => '请选择跟进结果',
        'follow_result.in' => '跟进结果参数错误',
        'intention_after.in' => '跟进后意向参数错误',
        'duration.integer' => '沟通时长必须为整数',
        'duration.between' => '沟通时长必须在0到10000之间',
        'next_follow_time.max' => '下次跟进时间参数错误',
        'next_follow_content.max' => '下次跟进计划最多255个字符',
        'is_important.in' => '重要标记参数错误',
    ];

    protected $scene = [
        'detail' => ['id'],
        'add' => [
            'customer_id',
            'follow_type',
            'follow_content',
            'follow_result',
            'intention_after',
            'duration',
            'next_follow_time',
            'next_follow_content',
            'is_important',
        ],
    ];

    /**
     * @notes 验证记录是否存在
     */
    protected function checkRecord($value)
    {
        return FollowRecord::find((int)$value) ? true : '跟进记录不存在';
    }

    /**
     * @notes 验证客户是否存在
     */
    protected function checkCustomer($value)
    {
        return Customer::find((int)$value) ? true : '客户不存在';
    }
}
