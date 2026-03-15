<?php
// +----------------------------------------------------------------------
// | likeadmin快速开发前后端分离管理后台（PHP版）
// +----------------------------------------------------------------------
// | 欢迎阅读学习系统程序代码，建议反馈是我们前进的动力
// | 开源版本可自由商用，可去除界面版权logo
// | gitee下载：https://gitee.com/likeshop_gitee/likeadmin
// | github下载：https://github.com/likeshop-github/likeadmin
// | 访问官网：https://www.likeadmin.cn
// | likeadmin团队 版权所有 拥有最终解释权
// +----------------------------------------------------------------------
// | author: likeadminTeam
// +----------------------------------------------------------------------

namespace app\common\service;

class LoginConfigService
{
    public static function getConfig(): array
    {
        $defaultConfig = config('project.login');

        return self::normalizeConfig([
            'login_way' => ConfigService::get('login', 'login_way', $defaultConfig['login_way']),
            'coerce_mobile' => ConfigService::get('login', 'coerce_mobile', $defaultConfig['coerce_mobile']),
            'login_agreement' => ConfigService::get('login', 'login_agreement', $defaultConfig['login_agreement']),
            'third_auth' => ConfigService::get('login', 'third_auth', $defaultConfig['third_auth']),
            'wechat_auth' => ConfigService::get('login', 'wechat_auth', $defaultConfig['wechat_auth']),
            'qq_auth' => ConfigService::get('login', 'qq_auth', $defaultConfig['qq_auth']),
        ]);
    }

    public static function normalizeConfig(array $config): array
    {
        $defaultConfig = config('project.login');
        $loginWay = self::normalizeLoginWay($config['login_way'] ?? $defaultConfig['login_way']);

        if (empty($loginWay)) {
            $loginWay = array_map('strval', $defaultConfig['login_way']);
        }

        return [
            'login_way' => $loginWay,
            'coerce_mobile' => self::normalizeToggleValue($config['coerce_mobile'] ?? $defaultConfig['coerce_mobile']),
            'login_agreement' => self::normalizeToggleValue($config['login_agreement'] ?? $defaultConfig['login_agreement']),
            'third_auth' => self::normalizeToggleValue($config['third_auth'] ?? $defaultConfig['third_auth']),
            'wechat_auth' => self::normalizeToggleValue($config['wechat_auth'] ?? $defaultConfig['wechat_auth']),
            'qq_auth' => self::normalizeToggleValue($config['qq_auth'] ?? $defaultConfig['qq_auth']),
        ];
    }

    private static function normalizeLoginWay($loginWay): array
    {
        if (is_string($loginWay)) {
            $decodedValue = json_decode($loginWay, true);
            $loginWay = is_array($decodedValue)
                ? $decodedValue
                : array_filter(array_map('trim', explode(',', $loginWay)), static fn ($item) => $item !== '');
        } elseif (!is_array($loginWay)) {
            $loginWay = ($loginWay === null || $loginWay === '') ? [] : [$loginWay];
        }

        return array_values(array_unique(array_map('strval', array_filter($loginWay, static function ($item) {
            return $item !== null && $item !== '';
        }))));
    }

    private static function normalizeToggleValue($value): int
    {
        return (int)$value === 1 ? 1 : 0;
    }
}
