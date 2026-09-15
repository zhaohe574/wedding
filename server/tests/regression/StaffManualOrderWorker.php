<?php
declare(strict_types=1);
require __DIR__ . '/../../vendor/autoload.php';
require __DIR__ . '/../../vendor/topthink/framework/src/helper.php';
$database = $argv[1] ?? '';
$port = (int)getenv('WEDDING_TEST_MYSQL_PORT');
$password = (string)getenv('WEDDING_TEST_MYSQL_PASSWORD');
if (!preg_match('/^wedding_binding_[0-9]+_[0-9]+$/', $database) || $port <= 0 || $port === 3306) throw new RuntimeException('仅允许隔离数据库');
$app = new think\App(dirname(__DIR__, 2) . DIRECTORY_SEPARATOR);
    $app->config->set(['default' => 'mysql', 'auto_timestamp' => true, 'connections' => ['mysql' => [
        'type' => 'mysql', 'hostname' => '127.0.0.1', 'hostport' => $port, 'database' => $database,
        'username' => 'root', 'password' => $password, 'charset' => 'utf8mb4', 'prefix' => 'qa_', 'debug' => false,
    ]]], 'database');
    $cache = ['type' => 'File', 'path' => sys_get_temp_dir() . '/wedding-cache-' . $database . '/'];
    $app->config->set(['default' => 'file', 'stores' => ['file' => $cache, 'config' => $cache]], 'cache');
    $app->config->set(['default' => 'file', 'channels' => ['file' => ['type' => 'File',
        'path' => sys_get_temp_dir() . '/wedding-log-' . $database . '/']]], 'log');
    (new think\service\ModelService($app))->boot();
    require_once __DIR__ . '/../../app/common.php';
$payload = json_decode(base64_decode($argv[2]), true, 512, JSON_THROW_ON_ERROR);
if ($payload['action'] === 'create') {
    $result = \app\common\service\StaffManualOrderService::create($payload['user_id'], $payload['params']);
} else {
    $result = \app\common\service\OrderReceiptService::audit($payload['order_id'], $payload['receipt_id'], $payload['admin_id'], true, '并发审核测试');
}
echo json_encode($result, JSON_THROW_ON_ERROR), "\n";

