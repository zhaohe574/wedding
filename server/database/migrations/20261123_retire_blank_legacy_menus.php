<?php
declare(strict_types=1);

/**
 * 退役已删前端页面导致后台空白的未托管历史菜单。
 *
 * 涉及 3 条未托管遗留记录（对应前端 .vue 早已移除，菜单本身已废弃）：
 *   - channel/h5（h5设置）
 *   - channel/open_setting（微信开发平台）
 *   - setting/wecom/index（企微通知）
 *
 * 处置方式：is_disable=1 + is_show=0（不物理删除，随时可恢复）。
 * 按 menu_key IS NULL + component 匹配，不写死主键 id。
 *
 * 默认 dry-run；加 --apply 落库。
 *
 * 用法：
 *   php server/database/migrations/20261123_retire_blank_legacy_menus.php
 *   php server/database/migrations/20261123_retire_blank_legacy_menus.php --apply
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

$targets = [
    'channel/h5' => '前端 channel/h5.vue 已不存在，H5 渠道已下线',
    'channel/open_setting' => '前端 channel/open_setting.vue 已不存在，微信开放平台入口已废弃',
    'setting/wecom/index' => '前端 setting/wecom/index.vue 已不存在，企微通知入口已废弃',
];

$rows = $db->table($table)
    ->whereNull('menu_key')
    ->whereIn('component', array_keys($targets))
    ->field('id,name,type,component,is_disable,is_show')
    ->select()
    ->toArray();

$plan = [];
foreach ($rows as $row) {
    if ((int)$row['is_disable'] === 1 && (int)$row['is_show'] === 0) {
        continue;
    }
    $comp = (string)$row['component'];
    $plan[] = ['row' => $row, 'why' => $targets[$comp] ?? ''];
}

echo '将退役 ' . count($plan) . " 个空白历史菜单：\n";
foreach ($plan as $item) {
    echo "  - id={$item['row']['id']} {$item['row']['name']} ({$item['row']['component']}) —— {$item['why']}\n";
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
        $db->table($table)
            ->where('id', $item['row']['id'])
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

echo '已退役 ' . count($plan) . " 个空白历史菜单（记录保留，随时可恢复）。\n";
