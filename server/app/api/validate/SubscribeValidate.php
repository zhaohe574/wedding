<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 小程序端订阅消息验证器
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\api\validate;

use app\common\validate\BaseValidate;

/**
 * 小程序端订阅消息验证器
 * Class SubscribeValidate
 * @package app\api\validate
 */
class SubscribeValidate extends BaseValidate
{
    protected $rule = [
        'template_id' => 'require|max:64',
        'scene' => 'max:50',
        'result' => 'require|in:accept,reject',
        'results' => 'require|array',
    ];

    protected $message = [
        'template_id.require' => '模板ID不能为空',
        'template_id.max' => '模板ID长度不能超过64字符',
        'result.require' => '订阅结果不能为空',
        'result.in' => '订阅结果值无效',
        'results.require' => '订阅结果不能为空',
        'results.array' => '订阅结果格式错误',
    ];

    protected $scene = [
        'recordSubscribe' => ['template_id', 'result'],
        'batchRecord' => ['results'],
    ];
}
