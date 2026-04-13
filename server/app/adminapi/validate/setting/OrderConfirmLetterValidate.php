<?php
declare(strict_types=1);

namespace app\adminapi\validate\setting;

use app\common\validate\BaseValidate;

class OrderConfirmLetterValidate extends BaseValidate
{
    protected $rule = [
        'remark_template' => 'require|max:1000',
    ];

    protected $message = [
        'remark_template.require' => '备注模板不能为空',
        'remark_template.max' => '备注模板长度不能超过1000个字符',
    ];

    public function sceneSetConfig()
    {
        return $this->only(['remark_template']);
    }
}
