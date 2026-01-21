<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 消息通知验证器
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\adminapi\validate\notification;

use app\common\validate\BaseValidate;
use app\common\model\notification\Notification;

/**
 * 消息通知验证器
 * Class NotificationValidate
 * @package app\adminapi\validate\notification
 */
class NotificationValidate extends BaseValidate
{
    protected $rule = [
        'id' => 'require|integer|gt:0',
        'ids' => 'require|array',
        'user_id' => 'require|integer|gt:0',
        'user_ids' => 'require|array',
        'notify_type' => 'require|in:1,2,3,4',
        'title' => 'require|max:100',
        'content' => 'require|max:500',
        'target_type' => 'max:50',
        'target_id' => 'integer|egt:0',
    ];

    protected $message = [
        'id.require' => '通知ID不能为空',
        'id.integer' => '通知ID必须是整数',
        'id.gt' => '通知ID必须大于0',
        'ids.require' => '通知ID不能为空',
        'ids.array' => '通知ID格式错误',
        'user_id.require' => '用户ID不能为空',
        'user_id.integer' => '用户ID必须是整数',
        'user_id.gt' => '用户ID必须大于0',
        'user_ids.require' => '用户ID不能为空',
        'user_ids.array' => '用户ID格式错误',
        'notify_type.require' => '通知类型不能为空',
        'notify_type.in' => '通知类型值错误',
        'title.require' => '标题不能为空',
        'title.max' => '标题最多100个字符',
        'content.require' => '内容不能为空',
        'content.max' => '内容最多500个字符',
        'target_type.max' => '目标类型最多50个字符',
        'target_id.integer' => '目标ID必须是整数',
        'target_id.egt' => '目标ID不能小于0',
    ];

    protected $scene = [
        'detail' => ['id'],
        'send' => ['user_id', 'notify_type', 'title', 'content', 'target_type', 'target_id'],
        'batchSend' => ['user_ids', 'notify_type', 'title', 'content', 'target_type', 'target_id'],
        'sendToAll' => ['notify_type', 'title', 'content', 'target_type', 'target_id'],
        'batchDelete' => ['ids'],
    ];
}
