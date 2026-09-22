<?php

declare(strict_types=1);

namespace app\common\model\wechat;

use app\common\model\BaseModel;

/**
 * 公众号业务通知模板配置。
 */
class OaNotificationTemplate extends BaseModel
{
    protected $name = 'wechat_oa_notification_template';

    public const SCENE_ORDER_UPDATE = 'order_update';
    public const SCENE_ORDER_CREATE = 'order_create';
    public const SCENE_TICKET_UPDATE = 'ticket_update';
    public const SCENE_CHANGE_RESULT = 'change_result';
    public const SCENE_WAITLIST_RELEASE = 'waitlist_release';
    public const SCENE_WAITLIST_EXPIRED = 'waitlist_expired';

    public const AUDIENCE_USER = 'user';
    public const AUDIENCE_STAFF = 'staff';

    public const STATUS_DISABLED = 0;
    public const STATUS_ENABLED = 1;

    public function getDataMappingAttr($value): array
    {
        if (is_array($value)) {
            return $value;
        }

        $decoded = json_decode((string) $value, true);
        return is_array($decoded) ? $decoded : [];
    }

    public function setDataMappingAttr($value): string
    {
        return is_array($value) ? json_encode($value, JSON_UNESCAPED_UNICODE) : (string) $value;
    }

    public static function findEnabled(string $scene, string $audience): ?self
    {
        $scene = trim($scene);
        $audience = trim($audience);
        $template = self::where('scene', $scene)
            ->where('audience', $audience)
            ->where('status', self::STATUS_ENABLED)
            ->find();

        if (!$template && $audience === self::AUDIENCE_USER && in_array($scene, ['order_create', 'order_created', 'order_confirm', 'order_confirmed'], true)) {
            $template = self::where('scene', self::SCENE_ORDER_UPDATE)
                ->where('audience', $audience)
                ->where('status', self::STATUS_ENABLED)
                ->find();
        }

        return $template;
    }
}
