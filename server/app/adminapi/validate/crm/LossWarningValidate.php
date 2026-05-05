<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 流失预警验证器
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\adminapi\validate\crm;

use app\common\model\crm\CustomerLossWarning;
use app\common\validate\BaseValidate;

/**
 * 流失预警验证器
 */
class LossWarningValidate extends BaseValidate
{
    protected $rule = [
        'id' => 'require|integer|gt:0|checkWarning',
        'remark' => 'max:255',
    ];

    protected $message = [
        'id.require' => '请选择预警',
        'id.integer' => '预警参数错误',
        'id.gt' => '预警参数错误',
        'remark.max' => '处理备注最多255个字符',
    ];

    protected $scene = [
        'detail' => ['id'],
        'handle' => ['id', 'remark'],
        'ignore' => ['id', 'remark'],
    ];

    /**
     * @notes 验证预警是否存在
     */
    protected function checkWarning($value)
    {
        return CustomerLossWarning::find((int)$value) ? true : '预警不存在';
    }
}
