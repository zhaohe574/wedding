<?php
declare(strict_types=1);
/** 默认只读，新增来源字段；新入口必须待新版小程序发布后显式启用。 */
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
$table = $prefix . 'wechat_oa_bind_session';
$plan = [];
if (!in_array('source', array_column($db->query("SHOW COLUMNS FROM `$table`"), 'Field'), true)) {
    $plan[] = "ALTER TABLE `$table` ADD COLUMN `source` varchar(20) NOT NULL DEFAULT 'miniapp'";
}
if (!in_array('idx_source_candidate', array_column($db->query("SHOW INDEX FROM `$table`"), 'Key_name'), true)) {
    $plan[] = "ALTER TABLE `$table` ADD KEY `idx_source_candidate` (`source`,`candidate_openid`,`status`)";
}
foreach ($plan as $sql) {
    echo $sql, ";\n";
    if (in_array('--apply', $argv, true)) $db->execute($sql);
}
echo "结构检查完成。新版小程序发布并验证消息跳转前，不启用邀请入口。\n";
