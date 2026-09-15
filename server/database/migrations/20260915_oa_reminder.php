<?php
declare(strict_types=1);
/** 默认只读；新增用户当天免提醒时间，不改变绑定、关注和邀请开关。 */
require dirname(__DIR__, 2) . '/vendor/autoload.php';
$app = new think\App(dirname(__DIR__, 2) . DIRECTORY_SEPARATOR);
$app->initialize();
foreach ($argv as $argument) {
    if (!str_starts_with($argument, '--test-database=')) continue;
    $name = substr($argument, strlen('--test-database='));
    $port = (int)getenv('WEDDING_TEST_MYSQL_PORT');
    if (!preg_match('/^wedding_binding_[0-9]+_[0-9]+$/', $name) || $port <= 0 || $port === 3306) throw new RuntimeException('仅允许独立本机测试数据库');
    $app->config->set(['default' => 'mysql', 'connections' => ['mysql' => [
        'type' => 'mysql', 'hostname' => '127.0.0.1', 'hostport' => $port, 'database' => $name,
        'username' => 'root', 'password' => (string)getenv('WEDDING_TEST_MYSQL_PASSWORD'), 'charset' => 'utf8mb4', 'prefix' => 'qa_', 'debug' => false,
    ]]], 'database');
}
set_exception_handler(static function (Throwable $e): void { fwrite(STDERR, $e->getMessage() . "\n"); exit(1); });
$db = think\facade\Db::connect();
$prefix = (string)config('database.connections.mysql.prefix', 'la_');
if (!preg_match('/^[a-zA-Z0-9_]*$/', $prefix)) throw new RuntimeException('数据库前缀不合法');
$table = $prefix . 'user';
if (!in_array('oa_reminder_skip_until', array_column($db->query("SHOW COLUMNS FROM `$table`"), 'Field'), true)) {
    $sql = "ALTER TABLE `$table` ADD COLUMN `oa_reminder_skip_until` int unsigned NOT NULL DEFAULT 0";
    echo $sql, ";\n";
    if (in_array('--apply', $argv, true)) $db->execute($sql);
}
echo "提醒偏好结构检查完成。\n";
