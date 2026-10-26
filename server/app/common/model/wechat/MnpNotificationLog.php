<?php

declare(strict_types=1);

namespace app\common\model\wechat;

use app\common\model\BaseModel;

/**
 * 微信小程序订阅通知发送日志模型
 */
class MnpNotificationLog extends BaseModel
{
    protected $name = 'wechat_mnp_notification_log';

    public const STATUS_PENDING = 0;
    public const STATUS_SENT = 1;
    public const STATUS_FAILED = 2;

    public function getPayloadAttr($value): array
    {
        if (is_array($value)) {
            return $value;
        }

        $decoded = json_decode((string) $value, true);
        return is_array($decoded) ? $decoded : [];
    }

    public function setPayloadAttr($value): string
    {
        return is_array($value) ? json_encode($value, JSON_UNESCAPED_UNICODE) : (string) $value;
    }
}
