<?php
declare(strict_types=1);

namespace app\adminapi\validate\setting;

use app\common\validate\BaseValidate;

class OrderConfirmLetterValidate extends BaseValidate
{
    protected $rule = [
        'remark_template' => 'require|max:1000',
        'payment_node' => 'max:60',
        'sans_file' => 'require|max:255',
        'serif_file' => 'require|max:255',
        'file' => 'require|max:255',
    ];

    protected $message = [
        'remark_template.require' => '备注模板不能为空',
        'remark_template.max' => '备注模板长度不能超过1000个字符',
        'payment_node.max' => '支付节点文案长度不能超过60个字符',
        'sans_file.require' => '请选择正文字体',
        'sans_file.max' => '正文字体参数过长',
        'serif_file.require' => '请选择衬线字体',
        'serif_file.max' => '衬线字体参数过长',
        'file.require' => '请选择字体文件',
        'file.max' => '字体文件参数过长',
    ];

    public function sceneSetConfig()
    {
        return $this->only(['remark_template', 'payment_node']);
    }

    public function sceneSetFont()
    {
        return $this->only(['sans_file', 'serif_file']);
    }

    public function sceneDeleteFont()
    {
        return $this->only(['file']);
    }
}
