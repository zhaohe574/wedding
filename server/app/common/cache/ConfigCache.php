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
    private string $storeName = 'config';

    public function getValue(string $type, string $name = '')
    {
        $key = $this->buildKey($type, $name);
        $value = $this->store($this->storeName)->get($key);
        return $value === null ? self::CACHE_MISS : $value;
    }

    public function setValue(string $type, string $name, $value, int $ttl = 3600): bool
    {
        $key = $this->buildKey($type, $name);
        return $this->store($this->storeName)->tag($this->tagName)->set($key, $value, $ttl);
    }

    /**
     * @notes 精确删除单个配置缓存，规避历史孤儿缓存文件无法通过 tag 清理的问题
     */
    public function deleteValue(string $type, string $name = ''): bool
    {
        $key = $this->buildKey($type, $name);
        return $this->store($this->storeName)->delete($key);
    }

    public function buildKey(string $type, string $name = ''): string
    {
        return $this->prefix . $type . ($name !== '' ? '_' . $name : '');
    }

    /**
     * @notes 清除配置缓存标签（使用共享缓存通道）
     * @return bool
     */
    public function deleteTag(): bool
    {
        return $this->store($this->storeName)->tag($this->tagName)->clear();
    }
}
