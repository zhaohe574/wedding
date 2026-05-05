<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 客户验证器
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\adminapi\validate\crm;

use app\common\model\crm\Customer;
use app\common\model\crm\SalesAdvisor;
use app\common\validate\BaseValidate;

/**
 * 客户验证器
 * Class CustomerValidate
 * @package app\adminapi\validate\crm
 */
class CustomerValidate extends BaseValidate
{
    /**
     * 验证规则
     * @var array
     */
    protected $rule = [
        'id' => 'require|integer|gt:0|checkCustomer',
        'customer_id' => 'require|integer|gt:0|checkCustomer',
        'advisor_id' => 'require|integer|gt:0|checkAdvisor',
        'customer_name' => 'require|max:50',
        'customer_mobile' => 'max:20',
        'customer_wechat' => 'max:50',
        'gender' => 'require|in:0,1,2',
        'age' => 'integer|between:0,120',
        'city' => 'max:50',
        'district' => 'max:50',
        'intention_level' => 'require|in:A,B,C,D',
        'intention_score' => 'integer|between:0,100',
        'wedding_date' => 'max:20',
        'wedding_venue' => 'max:200',
        'wedding_budget' => 'float|egt:0',
        'budget_range' => 'max:50',
        'source_channel' => 'require|in:1,2,3,4,5,6',
        'source_detail' => 'max:100',
        'customer_status' => 'require|in:1,2,3,4,5',
        'loss_reason' => 'max:200',
        'next_follow_time' => 'max:30',
        'remark' => 'max:500',
        'reason' => 'max:200',
    ];

    /**
     * 错误提示
     * @var array
     */
    protected $message = [
        'id.require' => '请选择客户',
        'id.integer' => '客户参数错误',
        'id.gt' => '客户参数错误',
        'customer_id.require' => '请选择客户',
        'customer_id.integer' => '客户参数错误',
        'customer_id.gt' => '客户参数错误',
        'advisor_id.require' => '请选择目标顾问',
        'advisor_id.integer' => '目标顾问参数错误',
        'advisor_id.gt' => '目标顾问参数错误',
        'customer_name.require' => '请输入客户姓名',
        'customer_name.max' => '客户姓名最多50个字符',
        'customer_mobile.max' => '手机号最多20个字符',
        'customer_wechat.max' => '微信号最多50个字符',
        'gender.require' => '请选择性别',
        'gender.in' => '性别参数错误',
        'age.integer' => '年龄必须为整数',
        'age.between' => '年龄必须在0到120之间',
        'city.max' => '城市最多50个字符',
        'district.max' => '区域最多50个字符',
        'intention_level.require' => '请选择意向等级',
        'intention_level.in' => '意向等级参数错误',
        'intention_score.integer' => '意向评分必须为整数',
        'intention_score.between' => '意向评分必须在0到100之间',
        'wedding_date.max' => '婚期参数错误',
        'wedding_venue.max' => '婚礼场地最多200个字符',
        'wedding_budget.float' => '预算金额参数错误',
        'wedding_budget.egt' => '预算金额必须大于等于0',
        'budget_range.max' => '预算范围最多50个字符',
        'source_channel.require' => '请选择来源渠道',
        'source_channel.in' => '来源渠道参数错误',
        'source_detail.max' => '来源详情最多100个字符',
        'customer_status.require' => '请选择客户状态',
        'customer_status.in' => '客户状态参数错误',
        'loss_reason.max' => '流失原因最多200个字符',
        'next_follow_time.max' => '下次跟进时间参数错误',
        'remark.max' => '备注最多500个字符',
        'reason.max' => '转移原因最多200个字符',
    ];

    /**
     * 验证场景
     * @var array
     */
    protected $scene = [
        'detail' => ['id'],
        'edit' => [
            'id',
            'customer_name',
            'customer_mobile',
            'customer_wechat',
            'gender',
            'age',
            'city',
            'district',
            'intention_level',
            'intention_score',
            'wedding_date',
            'wedding_venue',
            'wedding_budget',
            'budget_range',
            'source_channel',
            'source_detail',
            'customer_status',
            'loss_reason',
            'next_follow_time',
            'remark',
        ],
        'transfer' => ['customer_id', 'advisor_id', 'reason'],
    ];

    /**
     * @notes 验证客户是否存在
     * @param mixed $value
     * @return bool|string
     */
    protected function checkCustomer($value)
    {
        $customer = Customer::find((int)$value);
        if (!$customer) {
            return '客户不存在';
        }
        return true;
    }

    /**
     * @notes 验证顾问是否存在
     * @param mixed $value
     * @return bool|string
     */
    protected function checkAdvisor($value)
    {
        $advisor = SalesAdvisor::find((int)$value);
        if (!$advisor) {
            return '目标顾问不存在';
        }
        return true;
    }
}
