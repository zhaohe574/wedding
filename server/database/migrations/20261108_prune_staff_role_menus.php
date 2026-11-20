<?php
declare(strict_types=1);

/**
 * 清理「服务人员」角色的历史过度授权（B4 配套）。
 *
 * 现象：菜单重排后，服务人员角色在「设置」下能看到「系统维护」与「素材管理」。
 * 这两块是管理员用的（缓存/日志/定时任务/素材库），与服务人员岗位无关，
 * 属于早期批量勾选角色时留下的过度授权。
 *
 * 做法：
 *   1. 先把将被撤销的 (role_id, menu_id) 写进 la_system_role_menu_snapshot，便于回滚；
 *   2. 只删 la_system_role_menu 的授权行，**不删菜单、不动菜单本身**；
 *   3. 默认 dry-run，加 --apply 才落库。
 *
 * 用法：
 *   php server/database/migrations/20261108_prune_staff_role_menus.php
 *   php server/database/migrations/20261108_prune_staff_role_menus.php --apply
 *   php server/database/migrations/20261108_prune_staff_role_menus.php --apply --rollback
 */
require dirname(__DIR__, 2) . '/vendor/autoload.php';
$app = new think\App(dirname(__DIR__, 2) . DIRECTORY_SEPARATOR);
$app->initialize();

set_exception_handler(static function (Throwable $e): void {
    fwrite(STDERR, $e->getMessage() . "\n");
    exit(1);
});

$db = think\facade\Db::connect();
$prefix = (string)config('database.connections.mysql.prefix', 'la_');
if (!preg_match('/^[a-zA-Z0-9_]*$/', $prefix)) {
    throw new RuntimeException('数据库前缀不合法');
}

$apply = in_array('--apply', $argv, true);
$rollback = in_array('--rollback', $argv, true);
$batch = '20261108B4prune';

$menuTable = $prefix . 'system_menu';
$roleMenuTable = $prefix . 'system_role_menu';
$snapshot = $prefix . 'system_role_menu_snapshot';

/** 服务人员角色 */
$roleId = (int)$db->table($prefix . 'system_role')->where('name', '服务人员')->value('id');
if ($roleId <= 0) {
    throw new RuntimeException('找不到「服务人员」角色');
}

/** 需要撤销的子树根：按 menu_key 定位，不写死 id */
$rootKeys = ['nav.setting'];

// ---------------- 回滚 ----------------
if ($rollback) {
    $rows = $db->table($snapshot)->where('batch_id', $batch)->select()->toArray();
    if (!$rows) {
        echo "批次 {$batch} 没有快照，无需回滚。\n";
        exit(0);
    }
    if (!$apply) {
        echo "将恢复 " . count($rows) . " 条授权（dry-run，加 --apply 执行）。\n";
        exit(0);
    }
    $db->startTrans();
    try {
        foreach ($rows as $r) {
            $exists = $db->table($roleMenuTable)
                ->where('role_id', (int)$r['role_id'])->where('menu_id', (int)$r['menu_id'])->count();
            if (!$exists) {
                $db->table($roleMenuTable)->insert([
                    'role_id' => (int)$r['role_id'],
                    'menu_id' => (int)$r['menu_id'],
                ]);
            }
        }
        $db->commit();
    } catch (Throwable $e) {
        $db->rollback();
        throw $e;
    }
    echo "已恢复 " . count($rows) . " 条授权。\n";
    exit(0);
}

// ---------------- 计算待撤销 ----------------
$toRevoke = [];
foreach ($rootKeys as $key) {
    $rootId = (int)$db->table($menuTable)->where('menu_key', $key)->value('id');
    if ($rootId <= 0) {
        continue;
    }
    // 子树（含自身）：父链递归，避免依赖层级深度
    $ids = [$rootId];
    $cursor = [$rootId];
    while ($cursor) {
        $children = $db->table($menuTable)->whereIn('pid', $cursor)->column('id');
        $children = array_map('intval', $children);
        $children = array_values(array_diff($children, $ids));
        if (!$children) {
            break;
        }
        $ids = array_merge($ids, $children);
        $cursor = $children;
    }
    $granted = $db->table($roleMenuTable)
        ->where('role_id', $roleId)->whereIn('menu_id', $ids)->column('menu_id');
    foreach ($granted as $menuId) {
        $toRevoke[(int)$menuId] = $key;
    }
}

if (!$toRevoke) {
    echo "无需处理：服务人员角色在目标子树下已无授权。\n";
    exit(0);
}

$rows = $db->table($menuTable)->whereIn('id', array_keys($toRevoke))->field('id,name,type')->select();
echo "角色：服务人员（id={$roleId}）\n";
echo '将撤销 ' . count($toRevoke) . " 条授权：\n";
foreach ($rows as $r) {
    echo "  - {$r['id']} {$r['type']} {$r['name']}（{$toRevoke[(int)$r['id']]}）\n";
}

if (!$apply) {
    echo "\n[DRY-RUN] 未落库。加 --apply 执行；撤销后可用 --apply --rollback 恢复。\n";
    exit(0);
}

$db->startTrans();
try {
    foreach (array_keys($toRevoke) as $menuId) {
        $db->table($snapshot)->insert([
            'batch_id' => $batch,
            'role_id' => $roleId,
            'menu_id' => $menuId,
            'action' => 'revoke',
            'create_time' => time(),
        ]);
        $db->table($roleMenuTable)->where('role_id', $roleId)->where('menu_id', $menuId)->delete();
    }
    $db->commit();
    if (class_exists(\app\common\cache\AdminAuthCache::class)) {
        (new \app\common\cache\AdminAuthCache())->deleteTag();
    }
} catch (Throwable $e) {
    $db->rollback();
    throw $e;
}

echo "已撤销 " . count($toRevoke) . " 条授权（快照批次 {$batch}，可回滚）。\n";
