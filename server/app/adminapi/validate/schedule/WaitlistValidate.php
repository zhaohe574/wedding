<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 候补验证器
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\adminapi\validate\schedule;

use app\common\validate\BaseValidate;

/**
 * 候补验证器
 * Class WaitlistValidate
 * @package app\adminapi\validate\schedule
 */
class WaitlistValidate extends BaseValidate
{
    protected $rule = [
        'id' => 'require|integer|gt:0',
        'ids' => 'require|array',
        'remark' => 'max:255',
    ];

    protected $message = [
        'id.require' => '请选择候补记录',
        'id.integer' => '候补记录ID格式错误',
        'ids.require' => '请选择候补记录',
        'ids.array' => '候补记录ID格式错误',
        'remark.max' => '备注最多255个字符',
    ];

    /**
     * @notes 详情场景
     * @return WaitlistValidate
     */
    public function sceneDetail()
    {
        return $this->only(['id']);
    }

    /**
     * @notes 批量通知场景
     * @return WaitlistValidate
     */
    public function sceneBatchNotify()
    {
        return $this->only(['ids']);
    }

    /**
     * @notes 通知场景
     * @return WaitlistValidate
     */
    public function sceneNotify()
    {
        return $this->only(['id']);
    }

    /**
     * @notes 转正场景
     * @return WaitlistValidate
     */
    public function sceneConvert()
    {
        return $this->only(['id']);
    }

    /**
     * @notes 失效场景
     * @return WaitlistValidate
     */
    public function sceneInvalidate()
    {
        return $this->only(['id', 'remark'])->remove('remark', 'require');
    }
}
