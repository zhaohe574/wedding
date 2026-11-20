<?php

namespace app\adminapi\logic\setting;

use app\common\logic\BaseLogic;
use app\common\service\ConfigService;

class CustomerServiceLogic extends BaseLogic
{
    /**
     * 配置项白名单。
     *
     * `name` / `role` 是小程序「统一客服」兜底卡片要显示的字段
     * （见 app\api\logic\CustomerServiceLogic::getFallbackContact），
     * 但后台一直没暴露，运营只能改库。这里补齐后，setConfig 会按本方法的键自动收录，
     * 不需要再单独维护一份写入白名单。
     */
    public static function getConfig(): array
    {
        return [
            'name' => ConfigService::get('customer_service', 'name', ''),
            'role' => ConfigService::get('customer_service', 'role', ''),
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
