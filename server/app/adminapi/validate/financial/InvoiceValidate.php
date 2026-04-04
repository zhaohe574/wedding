<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 发票验证器
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\adminapi\validate\financial;

use app\common\validate\BaseValidate;

/**
 * 发票验证器
 * Class InvoiceValidate
 * @package app\adminapi\validate\financial
 */
class InvoiceValidate extends BaseValidate
{
    protected $rule = [
        'id' => 'require|integer|gt:0',
        'invoice_no' => 'require|max:50',
        'invoice_url' => 'max:255',
        'fail_reason' => 'require|max:255',
        'void_reason' => 'require|max:255',
    ];

    protected $message = [
        'id.require' => 'ID不能为空',
        'invoice_no.require' => '发票号码不能为空',
        'fail_reason.require' => '失败原因不能为空',
        'void_reason.require' => '作废原因不能为空',
    ];

    protected $scene = [
        'detail' => ['id'],
        'issue' => ['id', 'invoice_no'],
        'fail' => ['id', 'fail_reason'],
        'void' => ['id', 'void_reason'],
    ];
}
