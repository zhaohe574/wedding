<?php
declare(strict_types=1);

/**
 * 退役未托管的历史菜单（B2 配套）。
 *
 * 背景：菜单清单只对「已托管」（menu_key 非空）的节点负责，未托管行原样保留。
 * 重排后这些历史根节点会与新的 9 大类并列显示，把侧边栏撑成 11 个一级：
 *
 *   - 148 模板示例（149 组件示例 + 7 个组件演示页）—— likeadmin 的组件样板，业务从未使用
 *   - 166 财务管理（充值记录 / 余额明细 / 退款记录 / 用户充值）—— 模板自带的钱包体系，
 *     本项目走微信支付 + 线下收款，没有余额概念
 *
 * 处置方式：is_disable=1 + is_show=0。**不删除**记录，授权保留，随时可恢复。
 * 只按 component 前缀 / 菜单名匹配，不写死 id（本库 id 曾整体偏移过）。
 *
 * 默认只打印；加 --apply 落库。
 *
 * 用法：
 *   php server/database/migrations/20261108_retire_legacy_menus.php
 *   php server/database/migrations/20261108_retire_legacy_menus.php --apply
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
$table = $prefix . 'system_menu';

/** 待退役的根节点：按名字 + component 双条件锁定，避免误伤同名菜单 */
$targets = [
    ['name' => '模板示例', 'component' => '', 'why' => 'likeadmin 组件样板，业务未使用'],
    ['name' => '财务管理', 'component' => '', 'why' => '模板自带的钱包体系（充值/余额/退款），本项目无余额概念']
];

$rows = $db->table($table)->where('pid', 0)->whereNull('menu_key')
    ->field('id,name,type,component,is_disable,is_show')->select()->toArray();

$plan = [];
foreach ($rows as $row) {
    foreach ($targets as $t) {
        if ($row['name'] !== $t['name']) {
            continue;
        }
        if ($t['component'] !== '' && (string)$row['component'] !== $t['component']) {
            continue;
        }
        if ((int)$row['is_disable'] === 1 && (int)$row['is_show'] === 0) {
            continue;
        }
        // 统计子孙，便于人工确认影响面
        $children = (int)$db->table($table)->where('pid', $row['id'])->count();
        $plan[] = ['row' => $row, 'children' => $children, 'why' => $t['why']];
    }
}

echo '将退役 ' . count($plan) . ' 个历史根节点：' . "\n";
foreach ($plan as $item) {
    echo "  - id={$item['row']['id']} {$item['row']['name']}"
        . "（{$item['children']} 个直接子节点）—— {$item['why']}\n";
}

if (!$plan) {
    echo "无需处理。\n";
    exit(0);
}

if (!$apply) {
    echo "\n[DRY-RUN] 未落库。加 --apply 执行。\n";
    exit(0);
}

$db->startTrans();
try {
    foreach ($plan as $item) {
        $db->table($table)->where('id', $item['row']['id'])
            ->update(['is_disable' => 1, 'is_show' => 0]);
    }
    $db->commit();
    if (class_exists(\app\common\cache\AdminAuthCache::class)) {
        (new \app\common\cache\AdminAuthCache())->deleteTag();
    }
} catch (Throwable $e) {
    $db->rollback();
    throw $e;
}

echo "已退役 " . count($plan) . " 个根节点（记录保留，可随时把 is_disable 改回 0 恢复）。\n";
