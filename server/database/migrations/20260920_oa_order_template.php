<?php
declare(strict_types=1);

/** 默认只读预览；更新微信公众号订单生成成功通知模板配置（类目模板48211）。维护窗口执行 --apply。 */
require dirname(__DIR__, 2) . '/vendor/autoload.php';
$app = new think\App(dirname(__DIR__, 2) . DIRECTORY_SEPARATOR);
$app->initialize();

foreach ($argv as $argument) {
    if (!str_starts_with($argument, '--test-database=')) continue;
    $name = substr($argument, strlen('--test-database='));
    $port = (int)getenv('WEDDING_TEST_MYSQL_PORT');
    if (!preg_match('/^wedding_binding_[0-9]+_[0-9]+$/', $name) || $port <= 0 || $port === 3306) {
        throw new RuntimeException('仅允许独立本机测试数据库');
    }
    $app->config->set(['default' => 'mysql', 'connections' => ['mysql' => [
        'type' => 'mysql', 'hostname' => '127.0.0.1', 'hostport' => $port, 'database' => $name,
        'username' => 'root', 'password' => (string)getenv('WEDDING_TEST_MYSQL_PASSWORD'), 'charset' => 'utf8mb4', 'prefix' => 'qa_', 'debug' => false,
    ]]], 'database');
}

set_exception_handler(static function (Throwable $e): void {
    fwrite(STDERR, $e->getMessage() . "\n");
    exit(1);
});

$db = think\facade\Db::connect();
$prefix = (string)config('database.connections.mysql.prefix', 'la_');
if (!preg_match('/^[a-zA-Z0-9_]*$/', $prefix)) {
    throw new RuntimeException('数据库前缀不合法');
}

$table = $prefix . 'wechat_oa_notification_template';
$apply = in_array('--apply', $argv, true);

$templateId = 'H5dTD9xT90-dOXf1GUXIGuOs_ROKUCaqDASOZmR2zt8';
$mapping = json_encode([
    'thing12' => 'staff_name',
    'thing2' => 'package_name',
    'time8' => 'service_date',
    'thing10' => 'hotel_name',
    'amount13' => 'total_amount',
], JSON_UNESCAPED_UNICODE);

$existing = $db->table($table)->where('scene', 'order_update')->where('audience', 'user')->find();
if ($existing) {
    $sql = "UPDATE `{$table}` SET `template_id` = '{$templateId}', `data_mapping` = '{$mapping}', `page_path` = 'packages/pages/order_detail/order_detail', `status` = 1, `sort` = 100, `remark` = '订单生成成功通知（婚庆服务类目模板48211）', `update_time` = " . time() . " WHERE `scene` = 'order_update' AND `audience` = 'user'";
    echo $sql . ";\n";
    if ($apply) {
        $db->execute($sql);
        echo "已成功更新 order_update 模板配置（模板ID: {$templateId}）。\n";
    } else {
        echo "[只读报告] 检测到现有记录（ID: {$existing['id']}），添加 --apply 可提交更新。\n";
    }
} else {
    $sql = "INSERT INTO `{$table}` (`scene`, `audience`, `template_id`, `data_mapping`, `page_path`, `status`, `sort`, `remark`, `create_time`, `update_time`) VALUES ('order_update', 'user', '{$templateId}', '{$mapping}', 'packages/pages/order_detail/order_detail', 1, 100, '订单生成成功通知（婚庆服务类目模板48211）', " . time() . ", " . time() . ")";
    echo $sql . ";\n";
    if ($apply) {
        $db->execute($sql);
        echo "已成功创建 order_update 模板配置（模板ID: {$templateId}）。\n";
    } else {
        echo "[只读报告] 未检测到现有记录，添加 --apply 可写入新配置。\n";
    }
}
