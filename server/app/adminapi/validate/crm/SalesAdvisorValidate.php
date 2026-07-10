<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 销售顾问验证器
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\adminapi\validate\crm;

use app\common\model\crm\SalesAdvisor;
use app\common\validate\BaseValidate;

/**
 * 销售顾问验证器
 * Class SalesAdvisorValidate
 * @package app\adminapi\validate\crm
 */
class SalesAdvisorValidate extends BaseValidate
{
    /**
     * 验证规则
     * @var array
     */
    protected $rule = [
        'id' => 'require|integer|gt:0|checkAdvisor',
        'admin_id' => 'integer|egt:0',
        'advisor_name' => 'require|max:50',
        'avatar' => 'max:255',
        'mobile' => 'max:20',
        'wecom_userid' => 'max:64',
        'email' => 'max:100',
        'max_customer_count' => 'require|integer|gt:0',
        'status' => 'require|in:0,1,2',
        'sort' => 'integer|egt:0',
    ];

    /**
     * 错误提示
     * @var array
     */
    protected $message = [
        'id.require' => '请选择顾问',
        'id.integer' => '顾问参数错误',
        'id.gt' => '顾问参数错误',
        'admin_id.integer' => '关联管理员参数错误',
        'admin_id.egt' => '关联管理员参数错误',
        'advisor_name.require' => '请输入顾问姓名',
        'advisor_name.max' => '顾问姓名最多50个字符',
        'avatar.max' => '头像地址最多255个字符',
        'mobile.max' => '手机号最多20个字符',
        'wecom_userid.max' => '企微成员ID最多64个字符',
        'email.max' => '邮箱最多100个字符',
        'max_customer_count.require' => '请输入最大客户数',
        'max_customer_count.integer' => '最大客户数必须为整数',
        'max_customer_count.gt' => '最大客户数必须大于0',
        'status.require' => '请选择顾问状态',
        'status.in' => '顾问状态参数错误',
        'sort.integer' => '排序必须为整数',
        'sort.egt' => '排序必须大于等于0',
    ];

    /**
     * 验证场景
     * @var array
     */
    protected $scene = [
        'add' => ['admin_id', 'advisor_name', 'avatar', 'mobile', 'wecom_userid', 'email', 'max_customer_count', 'status', 'sort'],
        'edit' => ['id', 'admin_id', 'advisor_name', 'avatar', 'mobile', 'wecom_userid', 'email', 'max_customer_count', 'status', 'sort'],
        'detail' => ['id'],
        'delete' => ['id'],
        'status' => ['id', 'status'],
        'sync' => ['id'],
    ];

    /**
     * @notes 验证顾问是否存在
     * @param mixed $value
     * @return bool|string
     */
    protected function checkAdvisor($value)
    {
        $advisor = SalesAdvisor::find((int)$value);
        if (!$advisor) {
            return '顾问不存在';
        }
        return true;
    }
}
