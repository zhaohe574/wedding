<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 预约验证器
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\adminapi\validate\schedule;

use app\common\validate\BaseValidate;

/**
 * 预约验证器
 * Class BookingValidate
 * @package app\adminapi\validate\schedule
 */
class BookingValidate extends BaseValidate
{
    protected $rule = [
        'id' => 'require|integer|gt:0',
        'reason' => 'max:255',
    ];

    protected $message = [
        'id.require' => '请选择预约项',
        'id.integer' => '预约项ID格式错误',
        'id.gt' => '预约项ID格式错误',
        'reason.max' => '原因最多255个字符',
    ];

    /**
     * @notes 详情场景
     * @return BookingValidate
     */
    public function sceneDetail(): BookingValidate
    {
        return $this->only(['id']);
    }

    /**
     * @notes 确认场景
     * @return BookingValidate
     */
    public function sceneConfirm(): BookingValidate
    {
        return $this->only(['id']);
    }

    /**
     * @notes 取消场景
     * @return BookingValidate
     */
    public function sceneCancel(): BookingValidate
    {
        return $this->only(['id', 'reason'])->remove('reason', 'require');
    }
}
