<?php
declare(strict_types=1);

/**
 * 经营看板权限节点登记（T22）。
 * 看板数据迁回 adminapi 后，AuthMiddleware 采用「未登记即拒绝」策略，
 * 因此必须把 4 个节点写入 la_system_menu，否则非超管调用一律 403。
 * 默认只读预览：仅打印待执行的 INSERT；维护窗口执行 --apply 落库。
 * 节点权限标识必须与 ThinkPHP 的 controller/action 匹配规则一致：
 * app/adminapi/controller/dashboard/DashboardController::overview() => dashboard.dashboard/overview
 *
 * 本脚本同时覆盖两条路径，两者必须保持一致：
 *   1) 新装基线 server/public/install/db/like.sql（已含 4 个菜单节点 + role 2 的 la_system_role_menu 授权）；
 *   2) 已升级库（节点可能已登记但缺少授权行，需本脚本按 perms 反查真实 id 幂等补授权）。
 * 若只登记 la_system_menu 而不补 la_system_role_menu，已升级库上非超管账号仍会 403。
 */
require dirname(__DIR__, 2) . '/vendor/autoload.php';
$app = new think\App(dirname(__DIR__, 2) . DIRECTORY_SEPARATOR);
$app->initialize();

foreach ($argv as $argument) {
    if (!str_starts_with($argument, '--test-database=')) continue;
    $name = substr($argument, strlen('--test-database='));
    $port = (int)getenv('WEDDING_TEST_MYSQL_PORT');
    if (!preg_match('/^wedding_binding_[0-9]+_[0-9]+$/', $name) || $port <= 0 || $port === 3306) {
        throw new RuntimeException('仅允许独立本机测试数据库');
    }
    $app->config->set(['default' => 'mysql', 'connections' => ['mysql' => [
        'type' => 'mysql', 'hostname' => '127.0.0.1', 'hostport' => $port, 'database' => $name,
        'username' => 'root', 'password' => (string)getenv('WEDDING_TEST_MYSQL_PASSWORD'), 'charset' => 'utf8mb4', 'prefix' => 'qa_', 'debug' => false,
    ]]], 'database');
}

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

// 挂到「工作台」菜单下；找不到时退化为顶级按钮，避免因菜单结构差异中断
$parentId = (int)$db->table($menuTable)->where('perms', 'workbench/index')->value('id');

$nodes = [
    ['dashboard.dashboard/overview', '经营看板-财务概览', 100],
    ['dashboard.dashboard/incomeTrend', '经营看板-收入趋势', 99],
    ['dashboard.dashboard/orderStats', '经营看板-订单统计', 98],
    ['dashboard.dashboard/teamOverview', '经营看板-团队总览', 97],
];

$pending = 0;
foreach ($nodes as [$perms, $name, $sort]) {
    $exists = (int)$db->table($menuTable)->where('perms', $perms)->count();
    if ($exists > 0) {
        echo "[已存在] {$perms}\n";
        continue;
    }
    $sql = "INSERT INTO `{$menuTable}` (`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`)"
        . " VALUES ({$parentId}, 'A', '{$name}', '', {$sort}, '{$perms}', '', '', '', '', 0, 0, 0, " . time() . ", " . time() . ")";
    $pending++;
    echo $sql . ";\n";
    if ($apply) {
        $db->execute($sql);
        echo "已登记权限节点 {$perms}\n";
    }
}

if ($pending === 0) {
    echo "经营看板权限节点检查完成：无缺失节点。\n";
} elseif ($apply) {
    echo "经营看板权限节点检查完成：已登记 {$pending} 个节点。\n";
} else {
    echo "[只读报告] 检测到 {$pending} 个缺失权限节点，添加 --apply 可提交登记。\n";
}

// ---------------------------------------------------------------------------
// 角色授权：与 like.sql 基线保持一致，给 role_id = 2 授予这 4 个节点。
// 已升级库的 la_system_menu 里往往已有节点、却没有 la_system_role_menu 授权行，
// 只补节点不补授权，非超管账号仍然 403。这里按 perms 反查真实菜单 id（不硬编码 546-549），
// 再对 (role_id, menu_id) 做存在性判断，实现幂等补授权。
// ---------------------------------------------------------------------------
$roleMenuTable = $prefix . 'system_role_menu';
$grantRoleId = 2;

// 先解析 4 个节点在目标库里的真实 id，供预览明细与授权共用
$resolvedNodes = [];
foreach ($nodes as [$perms]) {
    $resolvedNodes[] = [$perms, (int)$db->table($menuTable)->where('perms', $perms)->value('id')];
}
echo '识别到 ' . count($resolvedNodes) . ' 个经营看板权限节点：'
    . implode('，', array_map(
        static fn (array $item): string => $item[1] > 0 ? "{$item[0]}=#{$item[1]}" : "{$item[0]}=未登记",
        $resolvedNodes
    )) . "\n";

$grantPending = 0;
$grantSkipped = 0;
$grantUnresolved = 0;
foreach ($resolvedNodes as [$perms, $menuId]) {
    if ($menuId <= 0) {
        // 节点尚未登记（只读模式下尚未落库），无法确定 menu_id，跳过并如实计入
        $grantUnresolved++;
        echo "[无法授权] {$perms} 未找到菜单节点，跳过角色授权\n";
        continue;
    }
    // 幂等判断：显式 SELECT，避免 INSERT IGNORE 掩盖主键/唯一键等真实错误
    $granted = (int)$db->table($roleMenuTable)
        ->where('role_id', $grantRoleId)
        ->where('menu_id', $menuId)
        ->count();
    if ($granted > 0) {
        $grantSkipped++;
        echo "[已存在] 授权已存在：role_id={$grantRoleId} menu_id={$menuId}（{$perms}）\n";
        continue;
    }
    $sql = "INSERT INTO `{$roleMenuTable}` (`role_id`,`menu_id`) VALUES ({$grantRoleId}, {$menuId})";
    $grantPending++;
    echo $sql . ";\n";
    if ($apply) {
        $db->execute($sql);
        echo "已授权 {$perms} 给 role_id={$grantRoleId}（menu_id={$menuId}）\n";
    }
}

if ($grantPending === 0 && $grantUnresolved === 0) {
    echo "经营看板角色授权检查完成：{$grantSkipped} 个节点已授权，无缺失授权。\n";
} elseif ($apply) {
    echo "经营看板角色授权检查完成：新授权 {$grantPending} 个，已存在跳过 {$grantSkipped} 个"
        . ($grantUnresolved > 0 ? "，无法授权 {$grantUnresolved} 个" : '') . "。\n";
} else {
    echo "[只读报告] 待授权 {$grantPending} 个节点，已存在跳过 {$grantSkipped} 个"
        . ($grantUnresolved > 0 ? "，无法授权 {$grantUnresolved} 个" : '')
        . "；添加 --apply 可提交授权。\n";
}
