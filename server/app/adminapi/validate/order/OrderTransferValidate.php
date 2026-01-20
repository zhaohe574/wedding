<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 订单转让验证器
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\adminapi\validate\order;

use app\common\validate\BaseValidate;

/**
 * 订单转让验证器
 * Class OrderTransferValidate
 * @package app\adminapi\validate\order
 */
class OrderTransferValidate extends BaseValidate
{
    protected $rule = [
        'id' => 'require|integer|gt:0',
        'approved' => 'require|in:0,1',
        'remark' => 'max:500',
        'reject_reason' => 'requireIf:approved,0|max:255',
        'reason' => 'max:255',
    ];

    protected $message = [
        'id.require' => '请选择转让记录',
        'id.integer' => '转让ID格式错误',
        'id.gt' => '转让ID格式错误',
        'approved.require' => '请选择审核结果',
        'approved.in' => '审核结果参数错误',
        'remark.max' => '备注最多500个字符',
        'reject_reason.requireIf' => '请填写拒绝原因',
        'reject_reason.max' => '拒绝原因最多255个字符',
        'reason.max' => '原因最多255个字符',
    ];

    /**
     * @notes 详情场景
     * @return OrderTransferValidate
     */
    public function sceneDetail()
    {
        return $this->only(['id']);
    }

    /**
     * @notes 审核场景
     * @return OrderTransferValidate
     */
    public function sceneAudit()
    {
        return $this->only(['id', 'approved', 'remark', 'reject_reason']);
    }

    /**
     * @notes 完成场景
     * @return OrderTransferValidate
     */
    public function sceneComplete()
    {
        return $this->only(['id']);
    }

    /**
     * @notes 取消场景
     * @return OrderTransferValidate
     */
    public function sceneCancel()
    {
        return $this->only(['id', 'reason']);
    }

    /**
     * @notes 重发验证码场景
     * @return OrderTransferValidate
     */
    public function sceneResendCode()
    {
        return $this->only(['id']);
    }

    /**
     * @notes 日志场景
     * @return OrderTransferValidate
     */
    public function sceneLogs()
    {
        return $this->only(['id']);
    }
}
