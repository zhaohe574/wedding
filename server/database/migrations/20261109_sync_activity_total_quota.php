<?php
declare(strict_types=1);

/**
 * 同步 la_dynamic.activity_total_quota 的口径与注释。
 *
 * 背景：总名额不再允许后台手填，改为按 la_activity_ticket 中各启用票种的
 *   stock 之和计算。新保存的活动由 ActivityRegistrationService::saveTickets()
 *   自动回写；本脚本处理存量数据，并把列注释从旧的「0=不限」改为自动汇总说明。
 *
 * 说明：activity_total_quota 保留为派生缓存列，不删除（迁移规范禁止 DROP COLUMN）。
 *   业务读取以票种实时汇总为准，后台列表/详情/小程序均走同一口径。
 *
 * 用法：
 *   php database/migrations/20261109_sync_activity_total_quota.php           # 只预览
 *   php database/migrations/20261109_sync_activity_total_quota.php --apply   # 落库
 *
 * 幂等：重复执行时数据差异为 0、注释一致时直接跳过。
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
$dynamicTable = $prefix . 'dynamic';
$ticketTable = $prefix . 'activity_ticket';
$targetComment = '活动总名额（按启用票种库存自动汇总，禁止手填）';

$diffSql = "SELECT COUNT(*) AS c FROM `{$dynamicTable}` d "
    . "LEFT JOIN ("
    . "SELECT `dynamic_id`, SUM(`stock`) AS total_stock FROM `{$ticketTable}` "
    . "WHERE `status` = 1 GROUP BY `dynamic_id`"
    . ") t ON t.`dynamic_id` = d.`id` "
    . "WHERE d.`dynamic_type` = 4 "
    . "AND d.`activity_total_quota` <> COALESCE(t.`total_stock`, 0)";
$diffCount = (int)($db->query($diffSql)[0]['c'] ?? 0);

$dataSql = "UPDATE `{$dynamicTable}` d "
    . "LEFT JOIN ("
    . "SELECT `dynamic_id`, SUM(`stock`) AS total_stock FROM `{$ticketTable}` "
    . "WHERE `status` = 1 GROUP BY `dynamic_id`"
    . ") t ON t.`dynamic_id` = d.`id` "
    . "SET d.`activity_total_quota` = COALESCE(t.`total_stock`, 0) "
    . "WHERE d.`dynamic_type` = 4 "
    . "AND d.`activity_total_quota` <> COALESCE(t.`total_stock`, 0)";

$columnComment = '';
foreach ($db->query("SHOW FULL COLUMNS FROM `{$dynamicTable}`") as $column) {
    if (($column['Field'] ?? '') === 'activity_total_quota') {
        $columnComment = (string)($column['Comment'] ?? '');
        break;
    }
}
$commentSql = "ALTER TABLE `{$dynamicTable}` MODIFY COLUMN `activity_total_quota` "
    . "int(10) unsigned NOT NULL DEFAULT '0' COMMENT '{$targetComment}'";

echo "待同步活动数：{$diffCount}\n";
echo "当前列注释：" . ($columnComment === '' ? '（空）' : $columnComment) . "\n";

if ($diffCount === 0 && $columnComment === $targetComment) {
    echo "[skip] 总名额数据与列注释均已同步\n";
    exit(0);
}

if ($diffCount > 0) {
    echo "待执行：\n  {$dataSql}\n";
}
if ($columnComment !== $targetComment) {
    echo "待执行：\n  {$commentSql}\n";
}

if (!$apply) {
    echo "\n（预览模式，未落库。确认无误后加 --apply 执行）\n";
    exit(0);
}

if ($diffCount > 0) {
    $db->execute($dataSql);
}
if ($columnComment !== $targetComment) {
    $db->execute($commentSql);
}

echo "\n[ok] 已同步 {$diffCount} 个活动的总名额，列注释已更新\n";
