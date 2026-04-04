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

namespace app\common\service;

use app\common\cache\ConfigCache;
use app\common\model\Config;

class ConfigService
{
    /**
     * @notes 设置配置值
     * @param $type
     * @param $name
     * @param $value
     * @return mixed
     * @author 段誉
     * @date 2021/12/27 15:00
     */
    public static function set(string $type, string $name, $value)
    {
        $original = $value;
        if (is_array($value)) {
            $value = json_encode($value, JSON_UNESCAPED_UNICODE);
        }

        $data = Config::where(['type' => $type, 'name' => $name])->findOrEmpty();

        if ($data->isEmpty()) {
            Config::create([
                'type' => $type,
                'name' => $name,
                'value' => $value,
            ]);
        } else {
            $data->value = $value;
            $data->save();
        }

        (new ConfigCache())->deleteTag();

        // 返回原始值
        return $original;
    }

    /**
     * @notes 获取配置值
     * @param $type
     * @param string $name
     * @param null $default_value
     * @return array|int|mixed|string
     * @author Tab
     * @date 2021/7/15 15:16
     */
    public static function get(string $type, string $name = '', $default_value = null)
    {
        if (!empty($name)) {
            $cache = new ConfigCache();
            $cachedValue = $cache->getValue($type, $name);
            if ($cachedValue !== ConfigCache::CACHE_MISS) {
                return self::resolveValue($cachedValue, $type, $name, $default_value);
            }

            $value = Config::where(['type' => $type, 'name' => $name])->value('value');
            if (!is_null($value)) {
                $value = self::decodeValue($value);
            } else {
                $value = ConfigCache::NULL_VALUE;
            }
            $cache->setValue($type, $name, $value);
            return self::resolveValue($value, $type, $name, $default_value);
        }

        // 取某个类型下的所有name的值
        $cache = new ConfigCache();
        $cachedList = $cache->getValue($type);
        if ($cachedList !== ConfigCache::CACHE_MISS) {
            return $cachedList === ConfigCache::NULL_VALUE ? null : $cachedList;
        }

        $data = Config::where(['type' => $type])->column('value', 'name');
        foreach ($data as $k => $v) {
            $data[$k] = self::decodeValue($v);
        }
        if ($data) {
            $cache->setValue($type, '', $data);
            return $data;
        }

        $cache->setValue($type, '', ConfigCache::NULL_VALUE);
    }

    /**
     * @notes 解析配置值
     * @param mixed $value
     * @return mixed
     */
    protected static function decodeValue($value)
    {
        if (!is_string($value)) {
            return $value;
        }
        $json = json_decode($value, true);
        return json_last_error() === JSON_ERROR_NONE ? $json : $value;
    }

    /**
     * @notes 处理默认值与特殊值
     * @param mixed $value
     * @param string $type
     * @param string $name
     * @param mixed $defaultValue
     * @return mixed
     */
    protected static function resolveValue($value, string $type, string $name, $defaultValue)
    {
        if ($value === ConfigCache::NULL_VALUE) {
            $value = null;
        }

        if ($value) {
            return $value;
        }
        // 返回特殊值 0 '0'
        if ($value === 0 || $value === '0') {
            return $value;
        }
        // 返回默认值
        if ($defaultValue !== null) {
            return $defaultValue;
        }
        // 返回本地配置文件中的值
        return config('project.' . $type . '.' . $name);
    }
}
