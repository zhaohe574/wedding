<?php
declare(strict_types=1);

/**
 * 从数据库反向导出菜单清单（menu_manifest.php）。
 *
 * 用途：清单被手工改坏、或线上已经用后台改过结构时，以库为准重建清单。
 * 只在库结构已经正确的前提下使用 —— 它是「导出当前状态」，不是「修复结构」。
 *
 * 用法：
 *   php server/database/menu/dump-manifest.php            # 打印到 stdout
 *   php server/database/menu/dump-manifest.php --write    # 覆盖写入 menu_manifest.php
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
$table = $prefix . 'system_menu';

$rows = $db->table($table)->whereNotNull('menu_key')->whereIn('type', ['M', 'C'])
    ->order('sort', 'desc')->order('id', 'asc')->select()->toArray();

$byPid = [];
foreach ($rows as $r) {
    $byPid[(int)$r['pid']][] = $r;
}

$q = static fn($s) => "'" . str_replace(["\\", "'"], ["\\\\", "\\'"], (string)$s) . "'";

$emit = static function (array $node, int $indent) use (&$emit, $byPid, $q): string {
    $pad = str_repeat('    ', $indent);
    $out = [$pad . '['];
    $out[] = $pad . "    'key' => " . $q($node['menu_key']) . ',';
    $out[] = $pad . "    'type' => " . $q($node['type']) . ',';
    $out[] = $pad . "    'name' => " . $q($node['name']) . ',';
    if ($node['icon'] !== '') {
        $out[] = $pad . "    'icon' => " . $q($node['icon']) . ',';
    }
    $out[] = $pad . "    'sort' => " . (int)$node['sort'] . ',';
    if ($node['paths'] !== '') {
        $out[] = $pad . "    'path' => " . $q($node['paths']) . ',';
    }
    if ($node['component'] !== '') {
        $out[] = $pad . "    'component' => " . $q($node['component']) . ',';
    }
    if ($node['perms'] !== '') {
        $out[] = $pad . "    'perms' => " . $q($node['perms']) . ',';
    }
    if ($node['selected'] !== '') {
        $out[] = $pad . "    'selected' => " . $q($node['selected']) . ',';
    }
    if ($node['params'] !== '') {
        $out[] = $pad . "    'params' => " . $q($node['params']) . ',';
    }
    if ((int)$node['is_cache'] === 1) {
        $out[] = $pad . "    'is_cache' => 1,";
    }
    if ((int)$node['is_show'] === 0) {
        $out[] = $pad . "    'is_show' => 0,";
    }
    if ((int)$node['is_disable'] === 1) {
        $out[] = $pad . "    'retired' => true,";
    }
    $out[] = $pad . "    'legacy_ids' => [" . (int)$node['id'] . '],';

    $children = $byPid[(int)$node['id']] ?? [];
    if ($children) {
        $out[] = $pad . "    'children' => [";
        foreach ($children as $child) {
            $out[] = $emit($child, $indent + 2);
        }
        $out[] = $pad . '    ],';
    }
    $out[] = $pad . '],';
    return implode("\n", $out);
};

$roots = $byPid[0] ?? [];
$body = [];
foreach ($roots as $root) {
    $body[] = $emit($root, 2);
}

$policy = [
    'public' => [
        'nav.quick-tools',
        'page.tools.quick.human_split',
        'page.tools.quick.compress',
        'page.tools.quick.convert',
    ],
    'staff_only' => [],
    'leader_only' => [],
    'never_ship' => [],
];

$policyBlock = '';
foreach ($policy as $bucket => $keys) {
    $policyBlock .= "        '{$bucket}' => [\n";
    foreach ($keys as $k) {
        $policyBlock .= '            ' . $q($k) . ",\n";
    }
    $policyBlock .= "        ],\n";
}

$php = <<<PHP
<?php
// +----------------------------------------------------------------------
// | 后台菜单单一事实源
// +----------------------------------------------------------------------
// | ⚠️ 手工维护。改动后必须执行：
// |   php think admin_menu:sync --dry-run
// |   php think admin_menu:sync --apply --batch=xxx
// |   php think admin_menu:check --strict
// |
// | 结构：按「运营任务」组织，不按控制器命名。
// |   key          稳定业务键；重排只改父子，不改 key
// |   type         M=目录 / C=菜单（A 型按钮权限不在此清单）
// |   path         仅本段路径（前端由 vue-router 嵌套拼接）；同级不得重名，否则路由互相覆盖
// |   component    必须与 admin/src/views 下相对路径完全一致（模糊匹配会白屏）
// |   perms        必须等于后端控制器的「点分路径/方法」
// |   legacy_ids   库内既有行 id；空数组=同步时新建
// |   retired      true = 保留记录但 is_disable=1 / is_show=0（永不物理删除）
// |   staff_only   true = 仅绑定服务人员的账号可见
// +----------------------------------------------------------------------

return [
    'version' => '2026-11-08.1',

    'policy' => [
{$policyBlock}    ],

    'menus' => [
__BODY__
    ]
];

PHP;

$out = str_replace('__BODY__', implode("\n", $body), $php);

if (in_array('--write', $argv, true)) {
    file_put_contents(__DIR__ . '/menu_manifest.php', $out);
    echo '已写入 menu_manifest.php，节点 ' . count($rows) . " 个\n";
} else {
    echo $out;
}
