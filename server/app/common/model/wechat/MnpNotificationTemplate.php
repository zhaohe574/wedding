<?php

declare(strict_types=1);

namespace app\common\model\wechat;

use app\common\model\BaseModel;

/**
 * 微信小程序订阅通知模板配置模型
 */
class MnpNotificationTemplate extends BaseModel
{
    protected $name = 'wechat_mnp_notification_template';

    public const SCENE_QUESTIONNAIRE_UPDATE = 'questionnaire_update';
    public const SCENE_ACTIVITY_UPDATE = 'activity_update';
    public const SCENE_TICKET_UPDATE = 'ticket_update';
    public const SCENE_ORDER_UPDATE = 'order_update';

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

    public static function findEnabled(string $scene): ?self
    {
        $scene = trim($scene);
        return self::where('scene', $scene)
            ->where('status', self::STATUS_ENABLED)
            ->where('template_id', '<>', '')
            ->find();
    }
}
