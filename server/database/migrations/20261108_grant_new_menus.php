<?php
declare(strict_types=1);

/**
 * 给「管理员」角色授予 B3/B4 新增菜单（只增不减）。
 *
 * 新增的 5 个菜单默认没有任何角色授权 —— 除了超管，谁都看不到：
 *   工作台 › 经营驾驶舱、订单与履约 › 暂停审核、服务与内容 › 附加项/评价标签/敏感词、
 *   财务 › 财务概览、售后 › 投诉管理/回访管理
 * 管理员（role=管理员）本就该看到全部管理页，这里按 menu_key 幂等补授权，
 * 并沿父链补齐祖先（否则 linear_to_tree 会把整棵子树丢掉）。
 *
 * 默认 dry-run，加 --apply 落库。
 *
 * 用法：
 *   php server/database/migrations/20261108_grant_new_menus.php
 *   php server/database/migrations/20261108_grant_new_menus.php --apply
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
$menuTable = $prefix . 'system_menu';
$roleMenuTable = $prefix . 'system_role_menu';

$roleId = (int)$db->table($prefix . 'system_role')->where('name', '管理员')->value('id');
if ($roleId <= 0) {
    throw new RuntimeException('找不到「管理员」角色');
}

$keys = [
    'page.workbench.dashboard',
    'nav.catalog',
    'page.order.pause',
    'page.service.addon',
    'page.review.tag',
    'page.review.sensitive',
    'page.financial.overview',
    'page.aftersale.complaint',
    'page.aftersale.callback',
];

$grant = [];
foreach ($keys as $key) {
    $id = (int)$db->table($menuTable)->where('menu_key', $key)->value('id');
    if ($id <= 0) {
        echo "跳过（库中无此 key）：{$key}\n";
        continue;
    }
    // 自身 + 全部祖先
    $cursor = $id;
    while ($cursor > 0) {
        $grant[$cursor] = $key;
        $cursor = (int)$db->table($menuTable)->where('id', $cursor)->value('pid');
    }
}

$existing = $db->table($roleMenuTable)->where('role_id', $roleId)->column('menu_id');
$existing = array_flip(array_map('intval', $existing));
$pending = array_values(array_filter(array_keys($grant), fn($id) => !isset($existing[$id])));

echo "角色：管理员（id={$roleId}）\n";
echo '待补授权 ' . count($pending) . ' 条（含祖先）' . "\n";
$names = $db->table($menuTable)->whereIn('id', $pending)->column('name', 'id');
foreach ($pending as $id) {
    echo "  + {$id} {$names[$id]}（来源 {$grant[$id]}）\n";
}

if (!$pending) {
    echo "无需处理。\n";
    exit(0);
}
if (!$apply) {
    echo "\n[DRY-RUN] 未落库。加 --apply 执行。\n";
    exit(0);
}

$db->startTrans();
try {
    foreach ($pending as $id) {
        $db->table($roleMenuTable)->insert(['role_id' => $roleId, 'menu_id' => $id]);
    }
    $db->commit();
    if (class_exists(\app\common\cache\AdminAuthCache::class)) {
        (new \app\common\cache\AdminAuthCache())->deleteTag();
    }
} catch (Throwable $e) {
    $db->rollback();
    throw $e;
}

echo "已补授权 " . count($pending) . " 条。\n";
