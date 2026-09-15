<?php

namespace app\adminapi\logic\setting;

use app\common\logic\BaseLogic;
use app\common\service\ConfigService;

class CustomerServiceLogic extends BaseLogic
{
    public static function getConfig(): array
    {
        return [
            'service_time' => ConfigService::get('customer_service', 'service_time', ''),
            'tips' => ConfigService::get('customer_service', 'tips', ''),
            'mobile' => ConfigService::get('customer_service', 'mobile', ''),
            'aftersale_admin_ids' => ConfigService::get('customer_service', 'aftersale_admin_ids', []),
        ];
    }

    public static function setConfig($params): void
    {
        foreach (array_keys(self::getConfig()) as $key) {
            if (!array_key_exists($key, $params)) continue;
            $value = $key === 'aftersale_admin_ids'
                ? array_values(array_unique(array_filter(array_map('intval', (array) $params[$key]))))
                : mb_substr(trim((string) $params[$key]), 0, 255);
            ConfigService::set('customer_service', $key, $value);
        }
    }
}
