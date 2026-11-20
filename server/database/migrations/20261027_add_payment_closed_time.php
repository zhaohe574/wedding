<?php
declare(strict_types=1);

/**
 * 补齐 la_payment.closed_time（微信关单确认时间）。
 *
 * 背景：Order::resolvePaymentChannelByOfflineCollectionPolicy() 会查
 *   Payment::where('pay_status', PENDING)->where('closed_time', 0)
 * 而线上/开发库的 la_payment 表没有该列，导致：
 *   SQLSTATE[42S22] Unknown column 'closed_time' in 'where clause'
 * 触发面很广 —— 订单列表 / 订单详情 / 支付渠道判定全部 500。
 *
 * 该列在 server/public/install/db/like.sql 的 la_payment 建表语句里是有的，
 * 说明是历史库未执行过这次变更（新装环境不受影响）。
 *
 * 用法：
 *   php database/migrations/20261027_add_payment_closed_time.php           # 只预览
 *   php database/migrations/20261027_add_payment_closed_time.php --apply   # 落库
 *
 * 幂等：已存在该列时直接跳过。回滚：ALTER TABLE la_payment DROP COLUMN closed_time;
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
$table = $prefix . 'payment';

$existing = array_column($db->query("SHOW COLUMNS FROM `{$table}`"), 'Field');
if (in_array('closed_time', $existing, true)) {
    echo "[skip] {$table}.closed_time 已存在，无需变更\n";
    exit(0);
}

$sql = "ALTER TABLE `{$table}` "
    . "ADD COLUMN `closed_time` int(10) unsigned NOT NULL DEFAULT '0' "
    . "COMMENT '微信关单确认时间' AFTER `pay_time`";

echo "待执行：\n  {$sql}\n";

if (!$apply) {
    echo "\n（预览模式，未落库。确认无误后加 --apply 执行）\n";
    exit(0);
}

$db->execute($sql);

$after = array_column($db->query("SHOW COLUMNS FROM `{$table}`"), 'Field');
if (!in_array('closed_time', $after, true)) {
    throw new RuntimeException('变更后仍未检测到 closed_time，请人工核查');
}

echo "\n[ok] {$table}.closed_time 已补齐\n";
