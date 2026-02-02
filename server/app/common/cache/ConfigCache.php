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

declare(strict_types=1);

namespace app\common\cache;

/**
 * 配置缓存
 * Class ConfigCache
 * @package app\common\cache
 */
class ConfigCache extends BaseCache
{
    public const CACHE_MISS = '__config_cache_miss__';
    public const NULL_VALUE = '__config_null__';

    private string $prefix = 'config_';

    public function getValue(string $type, string $name = '')
    {
        $key = $this->buildKey($type, $name);
        $value = $this->get($key);
        return $value === null ? self::CACHE_MISS : $value;
    }

    public function setValue(string $type, string $name, $value, int $ttl = 3600): bool
    {
        $key = $this->buildKey($type, $name);
        return $this->set($key, $value, $ttl);
    }

    public function buildKey(string $type, string $name = ''): string
    {
        return $this->prefix . $type . ($name !== '' ? '_' . $name : '');
    }
}
