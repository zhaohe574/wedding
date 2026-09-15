<?php

declare(strict_types=1);

namespace app\common\model\wechat;

use app\common\model\BaseModel;

/**
 * 公众号通知发送日志。
 */
class OaNotificationLog extends BaseModel
{
    protected $name = 'wechat_oa_notification_log';

    public const STATUS_PENDING = 0;
    public const STATUS_SUCCESS = 1;
    public const STATUS_FAILED = 2;
    public const STATUS_SENDING = 3;
    public const STATUS_SKIPPED = 4;

    public function getPayloadAttr($value): array
    {
        if (is_array($value)) {
            return $value;
        }

        $decoded = json_decode((string) $value, true);
        return is_array($decoded) ? $decoded : [];
    }

    public static function findByDedupeKey(string $dedupeKey): ?self
    {
        return self::where('dedupe_key', $dedupeKey)->find();
    }
}
