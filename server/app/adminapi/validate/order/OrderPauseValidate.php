<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 订单暂停验证器
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\adminapi\validate\order;

use app\common\validate\BaseValidate;

/**
 * 订单暂停验证器
 * Class OrderPauseValidate
 * @package app\adminapi\validate\order
 */
class OrderPauseValidate extends BaseValidate
{
    protected $rule = [
        'id' => 'require|integer|gt:0',
        'ids' => 'require|array|min:1',
        'approved' => 'require|in:0,1',
        'remark' => 'max:500',
        'reject_reason' => 'requireIf:approved,0|max:255',
        'new_service_date' => 'date',
        'new_end_date' => 'require|date',
        'days' => 'integer|between:1,30',
    ];

    protected $message = [
        'id.require' => '请选择暂停记录',
        'id.integer' => '暂停ID格式错误',
        'id.gt' => '暂停ID格式错误',
        'ids.require' => '请选择暂停记录',
        'ids.array' => '暂停记录格式错误',
        'ids.min' => '至少选择一条暂停记录',
        'approved.require' => '请选择审核结果',
        'approved.in' => '审核结果参数错误',
        'remark.max' => '备注最多500个字符',
        'reject_reason.requireIf' => '请填写拒绝原因',
        'reject_reason.max' => '拒绝原因最多255个字符',
        'new_service_date.date' => '新服务日期格式错误',
        'new_end_date.require' => '请选择新结束日期',
        'new_end_date.date' => '新结束日期格式错误',
        'days.integer' => '天数必须为整数',
        'days.between' => '天数应在1-30之间',
    ];

    /**
     * @notes 详情场景
     * @return OrderPauseValidate
     */
    public function sceneDetail()
    {
        return $this->only(['id']);
    }

    /**
     * @notes 审核场景
     * @return OrderPauseValidate
     */
    public function sceneAudit()
    {
        return $this->only(['id', 'approved', 'remark', 'reject_reason']);
    }

    /**
     * @notes 恢复场景
     * @return OrderPauseValidate
     */
    public function sceneResume()
    {
        return $this->only(['id', 'new_service_date', 'remark']);
    }

    /**
     * @notes 批量审核场景
     * @return OrderPauseValidate
     */
    public function sceneBatchAudit()
    {
        return $this->only(['ids', 'approved', 'remark', 'reject_reason']);
    }

    /**
     * @notes 延长场景
     * @return OrderPauseValidate
     */
    public function sceneExtend()
    {
        return $this->only(['id', 'new_end_date', 'remark']);
    }

    /**
     * @notes 即将到期查询场景
     * @return OrderPauseValidate
     */
    public function sceneExpiring()
    {
        return $this->only(['days'])
            ->remove('days', 'require');
    }

    /**
     * @notes 日志场景
     * @return OrderPauseValidate
     */
    public function sceneLogs()
    {
        return $this->only(['id']);
    }
}
