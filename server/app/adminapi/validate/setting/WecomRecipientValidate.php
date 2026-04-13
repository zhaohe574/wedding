<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 企微接收人维护验证器
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\adminapi\validate\setting;

use app\common\validate\BaseValidate;

class WecomRecipientValidate extends BaseValidate
{
    protected $rule = [
        'id' => 'require|integer|gt:0',
        'wecom_userid' => 'max:64',
    ];

    protected $message = [
        'id.require' => '请选择顾问',
        'id.integer' => '顾问参数错误',
        'id.gt' => '顾问参数错误',
        'wecom_userid.max' => '企微成员ID长度不能超过64个字符',
    ];

    public function sceneUpdateAdvisor(): WecomRecipientValidate
    {
        return $this->only(['id', 'wecom_userid']);
    }
}
