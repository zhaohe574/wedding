<?php
declare(strict_types=1);

/**
 * 补齐订单表开始履约时间戳字段 `start_service_time`（2026-11-20）。
 *
 * 默认只读预览；维护窗口追加 --apply 执行 ALTER：
 *   php server/database/migrations/20261120_order_start_service_time.php
 *   php server/database/migrations/20261120_order_start_service_time.php --apply
 */
require dirname(__DIR__, 2) . '/vendor/autoload.php';
$app = new think\App(dirname(__DIR__, 2) . DIRECTORY_SEPARATOR);
$app->initialize();

foreach ($argv as $argument) {
    if (!str_starts_with($argument, '--test-database=')) {
        continue;
    }
    $name = substr($argument, strlen('--test-database='));
    $port = (int) getenv('WEDDING_TEST_MYSQL_PORT');
    if (!preg_match('/^wedding_binding_[0-9]+_[0-9]+$/', $name) || $port <= 0 || $port === 3306) {
        throw new RuntimeException('仅允许独立本机测试数据库');
    }
    $app->config->set([
        'default' => 'mysql',
        'connections' => [
            'mysql' => [
                'type' => 'mysql',
                'hostname' => '127.0.0.1',
                'hostport' => $port,
                'database' => $name,
                'username' => 'root',
                'password' => (string) getenv('WEDDING_TEST_MYSQL_PASSWORD'),
                'charset' => 'utf8mb4',
                'prefix' => 'qa_',
                'debug' => false,
            ],
        ],
    ], 'database');
}

set_exception_handler(static function (Throwable $e): void {
    fwrite(STDERR, $e->getMessage() . "\n");
    exit(1);
});

$db = think\facade\Db::connect();
$prefix = (string) config('database.connections.mysql.prefix', 'la_');
if (!preg_match('/^[a-zA-Z0-9_]*$/', $prefix)) {
    throw new RuntimeException('数据库前缀不合法');
}

$apply = in_array('--apply', $argv, true);
$table = $prefix . 'order';
$exists = $db->query("SHOW COLUMNS FROM `{$table}` LIKE 'start_service_time'");

if (!empty($exists)) {
    echo "[已存在] `{$table}`.start_service_time\n";
    exit(0);
}

$sql = "ALTER TABLE `{$table}` ADD COLUMN `start_service_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '开始服务时间' AFTER `cancel_time`";
echo $sql . ";\n";

if ($apply) {
    $db->execute($sql);
    echo "已添加列 `{$table}`.start_service_time\n";
} else {
    echo "[只读预览] 未修改数据库；确认无误后追加 --apply 执行。\n";
}
