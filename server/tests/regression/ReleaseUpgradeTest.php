<?php

declare(strict_types=1);

// 迁移验证只使用独立本机实例和随机数据库，不加载业务数据库连接。
$port = (int)getenv('WEDDING_TEST_MYSQL_PORT');
if ($port <= 0 || $port === 3306) throw new RuntimeException('必须指定非 3306 的独立本机测试端口');
if (is_file(dirname(__DIR__, 2).'/.env')) throw new RuntimeException('迁移回归必须在不含真实 server/.env 的隔离检出目录执行');
$password = (string)getenv('WEDDING_TEST_MYSQL_PASSWORD');
$pdo = new PDO('mysql:host=127.0.0.1;port='.$port.';charset=utf8mb4', 'root', $password, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$database = 'wedding_release_'.getmypid().'_'.time();
$pdo->exec('CREATE DATABASE `'.$database.'` CHARACTER SET utf8mb4');
$checks = 0;
$check = static function (bool $ok, string $message) use (&$checks): void {
    if (!$ok) throw new RuntimeException($message);
    $checks++;
};
$run = static function (bool $apply) use ($database, $port, $password): string {
    $env = array_merge(getenv(), [
        'PHP_DATABASE_HOSTNAME' => '127.0.0.1', 'PHP_DATABASE_HOSTPORT' => (string)$port,
        'PHP_DATABASE_DATABASE' => $database, 'PHP_DATABASE_USERNAME' => 'root',
        'PHP_DATABASE_PASSWORD' => $password, 'PHP_DATABASE_PREFIX' => 'qa_', 'PHP_APP_DEBUG' => 'false',
    ]);
    $command = [PHP_BINARY, dirname(__DIR__, 2).'/database/migrations/20260914_release_upgrade.php'];
    if ($apply) $command[] = '--apply';
    $process = proc_open($command, [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes, dirname(__DIR__, 2), $env);
    fclose($pipes[0]);
    $output = stream_get_contents($pipes[1]);
    $error = stream_get_contents($pipes[2]);
    fclose($pipes[1]); fclose($pipes[2]);
    if (proc_close($process) !== 0) throw new RuntimeException($output.$error);
    return $output;
};
try {
    $pdo->exec('USE `'.$database.'`');
    $pdo->exec(str_replace('`la_', '`qa_', file_get_contents(dirname(__DIR__, 2).'/public/install/db/like.sql')));
    foreach (['manual_schedule', 'staff_settlement_repay', 'wechat_binding_attempt'] as $table) $pdo->exec('DROP TABLE qa_'.$table);
    $pdo->exec('ALTER TABLE qa_admin DROP INDEX uk_user_id, DROP COLUMN user_id');
    $pdo->exec('ALTER TABLE qa_financial_flow DROP INDEX uk_flow_business, DROP COLUMN unique_biz_id');
    $pdo->exec('ALTER TABLE qa_payment DROP COLUMN query_time, DROP COLUMN closed_time, DROP COLUMN collection_owner, DROP COLUMN pay_voucher');
    $pdo->exec('ALTER TABLE qa_wechat_oa_bind_session DROP INDEX uk_binding_code, DROP COLUMN binding_code');
    $pdo->exec('ALTER TABLE qa_staff_schedule_confirm_letter DROP INDEX uk_order_staff_version, DROP INDEX idx_manual_current, DROP COLUMN manual_schedule_id, ADD UNIQUE KEY uk_order_staff_version(order_id,staff_id,version)');
    $pdo->exec('ALTER TABLE qa_staff_settlement DROP INDEX uk_order_item_id, MODIFY COLUMN order_item_id int unsigned NOT NULL DEFAULT 0');
    $pdo->exec('ALTER TABLE qa_wechat_oa_follower MODIFY COLUMN user_id int unsigned NOT NULL DEFAULT 0');
    $pdo->exec("INSERT INTO qa_wechat_oa_bind_session(user_id,token,expires_time) VALUES(7,'legacy-session',2000000000)");
    $pdo->exec("INSERT INTO qa_wechat_oa_follower(openid,user_id) VALUES('legacy-follower',0)");
    $pdo->exec("DELETE FROM qa_dev_crontab WHERE command='query_payments'");
    $pdo->exec("INSERT INTO qa_dev_crontab(name,command,status,type,`system`,expression,params) VALUES('旧任务','send_subscribe_messages',1,1,1,'* * * * *','')");
    $run(false);
    $check(!$pdo->query("SHOW TABLES LIKE 'qa_manual_schedule'")->fetch(), '预检不得创建表');
    $run(true);
    $check((bool)$pdo->query("SHOW TABLES LIKE 'qa_manual_schedule'")->fetch(), '新增档期表');
    $check((bool)$pdo->query("SHOW COLUMNS FROM qa_payment LIKE 'collection_owner'")->fetch(), '补齐支付字段');
    $session = $pdo->query("SELECT * FROM qa_wechat_oa_bind_session WHERE token='legacy-session'")->fetch(PDO::FETCH_ASSOC);
    $check($session && (int)$session['expires_time'] === 0 && strlen($session['binding_code']) === 10, '保留旧会话并使其过期');
    $check($pdo->query("SELECT user_id FROM qa_wechat_oa_follower WHERE openid='legacy-follower'")->fetchColumn() === null, '未绑定粉丝使用空值');
    $check((int)$pdo->query("SELECT status FROM qa_dev_crontab WHERE command='send_subscribe_messages'")->fetchColumn() === 2, '停用退役任务');
    $repeat = $run(true);
    $check(!str_contains($repeat, 'ALTER TABLE') && !str_contains($repeat, 'CREATE TABLE'), '重复执行不重复修改结构');
    $check((int)$pdo->query("SELECT COUNT(*) FROM qa_dev_crontab WHERE command='query_payments'")->fetchColumn() === 1, '查单任务只登记一次');
    $check((int)$pdo->query("SELECT COUNT(*) FROM qa_wechat_oa_bind_session WHERE token='legacy-session'")->fetchColumn() === 1, '重复迁移保留历史记录');
    $pdo->exec('DROP TABLE qa_financial_flow');
    $failed = false;
    try { $run(false); } catch (RuntimeException $error) { $failed = str_contains($error->getMessage(), '迁移失败'); }
    $check($failed, '迁移异常必须返回非零退出码');
    echo '发布迁移回归通过：'.$checks." 项。\n";
} finally {
    $pdo->exec('DROP DATABASE IF EXISTS `'.$database.'`');
}
