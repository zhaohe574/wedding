<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 订阅消息验证器
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\adminapi\validate\subscribe;

use app\common\validate\BaseValidate;

/**
 * 订阅消息验证器
 * Class SubscribeValidate
 * @package app\adminapi\validate\subscribe
 */
class SubscribeValidate extends BaseValidate
{
    protected $rule = [
        'id' => 'require|integer|gt:0',
        'template_id' => 'require|max:64',
        'name' => 'require|max:100',
        'title' => 'max:200',
        'scene' => 'require|max:50',
        'content' => 'array',
        'example' => 'max:1000',
        'keywords' => 'max:500',
        'category_id' => 'max:50',
        'status' => 'in:0,1',
        'sort' => 'integer|egt:0',
        'remark' => 'max:500',
        'scene_id' => 'require|integer|gt:0',
        'description' => 'max:500',
        'trigger_event' => 'max:100',
        'data_mapping' => 'array',
        'page_path' => 'max:200',
        'is_auto' => 'in:0,1',
        'delay_seconds' => 'integer|egt:0',
        'user_id' => 'require|integer|gt:0',
        'data' => 'array',
        'page' => 'max:200',
    ];

    protected $message = [
        'id.require' => 'ID不能为空',
        'id.integer' => 'ID必须为整数',
        'id.gt' => 'ID必须大于0',
        'template_id.require' => '模板ID不能为空',
        'template_id.max' => '模板ID长度不能超过64字符',
        'name.require' => '模板名称不能为空',
        'name.max' => '模板名称长度不能超过100字符',
        'scene.require' => '使用场景不能为空',
        'scene.max' => '使用场景长度不能超过50字符',
        'scene_id.require' => '场景ID不能为空',
        'user_id.require' => '用户ID不能为空',
    ];

    protected $scene = [
        'templateDetail' => ['id'],
        'addTemplate' => ['template_id', 'name', 'scene'],
        'editTemplate' => ['id', 'template_id', 'name', 'scene'],
        'sceneDetail' => ['id'],
        'editScene' => ['id'],
        'bindTemplate' => ['scene_id', 'template_id'],
        'logDetail' => ['id'],
        'testSend' => ['user_id', 'scene'],
    ];
}
