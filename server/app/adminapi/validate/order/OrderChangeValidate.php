<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 订单变更验证器
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\adminapi\validate\order;

use app\common\validate\BaseValidate;

/**
 * 订单变更验证器
 * Class OrderChangeValidate
 * @package app\adminapi\validate\order
 */
class OrderChangeValidate extends BaseValidate
{
    protected $rule = [
        'id' => 'require|integer|gt:0',
        'ids' => 'require|array|min:1',
        'approved' => 'require|in:0,1',
        'remark' => 'max:500',
        'reject_reason' => 'requireIf:approved,0|max:255',
    ];

    protected $message = [
        'id.require' => '请选择变更记录',
        'id.integer' => '变更ID格式错误',
        'id.gt' => '变更ID格式错误',
        'ids.require' => '请选择变更记录',
        'ids.array' => '变更记录格式错误',
        'ids.min' => '至少选择一条变更记录',
        'approved.require' => '请选择审核结果',
        'approved.in' => '审核结果参数错误',
        'remark.max' => '备注最多500个字符',
        'reject_reason.requireIf' => '请填写拒绝原因',
        'reject_reason.max' => '拒绝原因最多255个字符',
    ];

    /**
     * @notes 详情场景
     * @return OrderChangeValidate
     */
    public function sceneDetail()
    {
        return $this->only(['id']);
    }

    /**
     * @notes 审核场景
     * @return OrderChangeValidate
     */
    public function sceneAudit()
    {
        return $this->only(['id', 'approved', 'remark', 'reject_reason']);
    }

    /**
     * @notes 执行场景
     * @return OrderChangeValidate
     */
    public function sceneExecute()
    {
        return $this->only(['id']);
    }

    /**
     * @notes 批量审核场景
     * @return OrderChangeValidate
     */
    public function sceneBatchAudit()
    {
        return $this->only(['ids', 'approved', 'remark', 'reject_reason']);
    }

    /**
     * @notes 批量执行场景
     * @return OrderChangeValidate
     */
    public function sceneBatchExecute()
    {
        return $this->only(['ids']);
    }

    /**
     * @notes 日志场景
     * @return OrderChangeValidate
     */
    public function sceneLogs()
    {
        return $this->only(['id']);
    }
}
