<?php
declare(strict_types=1);
/** 默认只读；新增本人录单审计字段与收款申请表，不修改历史付款状态。 */
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
$table = $prefix . 'order';
$columns = array_column($db->query("SHOW COLUMNS FROM `$table`"), 'Field');
$changes = [
    'creator_user_id' => "ADD COLUMN `creator_user_id` int unsigned NOT NULL DEFAULT 0",
    'staff_submit_key' => "ADD COLUMN `staff_submit_key` char(64) DEFAULT NULL, ADD UNIQUE KEY `uk_staff_submit` (`staff_submit_key`)",
    'staff_submit_hash' => "ADD COLUMN `staff_submit_hash` char(64) NOT NULL DEFAULT ''",
];
foreach ($changes as $field => $definition) {
    if (in_array($field, $columns, true)) continue;
    $sql = "ALTER TABLE `$table` $definition";
    echo $sql, ";\n";
    if (in_array('--apply', $argv, true)) $db->execute($sql);
}
$sql = str_replace('la_order_receipt_request', $prefix . 'order_receipt_request',
    file_get_contents(dirname(__DIR__) . '/schema/staff_order_receipt.sql'));
echo $sql, "\n";
if (in_array('--apply', $argv, true)) $db->execute($sql);
echo "本人录单结构检查完成。\n";

