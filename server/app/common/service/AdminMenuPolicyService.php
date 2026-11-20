<?php

declare(strict_types=1);

namespace app\common\service;

use think\facade\Db;
use think\facade\Log;

/**
 * 菜单访问策略（替代写死在 MenuLogic / AuthLogic 里的魔法数字 id）。
 *
 * 为什么要有这一层：
 *   原来「公共菜单」是 `[497, 501, 502, 503]` 四个写死的主键，而 AuthLogic 里还有一份
 *   `[498, 499, 500]` —— 那三个 id 在安装基线里根本不存在，属于纯死代码。
 *   主键在不同环境并不一致（本库 440 段整体偏移过），一旦重建菜单就会静默失效。
 *
 * 现在改成读 `database/menu/menu_manifest.php` 的 policy，按 menu_key 解析成 id：
 *   public      所有后台账号可见（不依赖角色授权）
 *   staff_only  仅绑定服务人员的账号可见
 *   leader_only 仅服务人员队长可见
 *   never_ship  永不进侧边栏
 *
 * key 解析不到时**写日志**而不是静默跳过 —— 否则又是「菜单莫名消失、查不出原因」。
 */
class AdminMenuPolicyService
{
    /** @var array<string, int[]>|null */
    private static ?array $resolved = null;

    /** @return array<string, mixed> */
    public static function manifest(): array
    {
        static $manifest = null;
        if ($manifest === null) {
            $path = root_path() . 'database/menu/menu_manifest.php';
            $manifest = is_file($path) ? (require $path) : ['policy' => [], 'menus' => []];
        }
        return $manifest;
    }

    /**
     * 把 policy 的 key 列表解析成菜单 id。
     * @return int[]
     */
    public static function ids(string $bucket): array
    {
        $all = self::resolveAll();
        return $all[$bucket] ?? [];
    }

    /** @return int[] */
    public static function publicIds(): array
    {
        return self::ids('public');
    }

    /** @return int[] */
    public static function staffOnlyIds(): array
    {
        return self::ids('staff_only');
    }

    /** @return int[] */
    public static function leaderOnlyIds(): array
    {
        return self::ids('leader_only');
    }

    /** @return int[] */
    public static function neverShipIds(): array
    {
        return self::ids('never_ship');
    }

    /** 清缓存（同步命令落库后调用） */
    public static function flush(): void
    {
        self::$resolved = null;
    }

    /** @return array<string, int[]> */
    private static function resolveAll(): array
    {
        if (self::$resolved !== null) {
            return self::$resolved;
        }

        $policy = (array)(self::manifest()['policy'] ?? []);
        $out = [];

        // 一次性把所有 key 查出来，避免每个 bucket 都打一次库
        $keys = [];
        foreach ($policy as $bucket => $list) {
            foreach ((array)$list as $key) {
                $keys[] = (string)$key;
            }
        }
        $keys = array_values(array_unique(array_filter($keys)));

        $idByKey = [];
        if ($keys) {
            $prefix = (string)config('database.connections.' . config('database.default') . '.prefix', 'la_');
            $rows = Db::table($prefix . 'system_menu')
                ->whereIn('menu_key', $keys)
                ->field('id,menu_key')
                ->select();
            foreach ($rows as $row) {
                $idByKey[(string)$row['menu_key']] = (int)$row['id'];
            }
        }

        foreach ($policy as $bucket => $list) {
            $out[$bucket] = [];
            foreach ((array)$list as $key) {
                $key = (string)$key;
                if (isset($idByKey[$key])) {
                    $out[$bucket][] = $idByKey[$key];
                    continue;
                }
                // 不静默：key 解析不到说明清单与库不一致，必须能在日志里看见
                Log::warning("[admin_menu] policy.{$bucket} 的 key 在库中不存在：{$key}");
            }
        }

        self::$resolved = $out;
        return $out;
    }
}
