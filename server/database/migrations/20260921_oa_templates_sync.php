<?php
declare(strict_types=1);

/**
 * 微信公众号服务号模板同步与绑定迁移脚本。
 * 默认只读预览，添加 --apply 参数执行真实更新。
 */
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

$bindings = [
    [
        'scene' => 'staff_order',
        'audience' => 'staff',
        'template_id' => 'zRgTnFCTLiXN54GNSYTqaU9b_aTywGmL34ROId_qN_4',
        'data_mapping' => ['character_string1' => 'order_sn', 'time2' => 'order_time'],
        'page_path' => 'packages/pages/staff_order_detail/staff_order_detail',
        'remark' => '接单成功通知',
        'status' => 1,
    ],
    [
        'scene' => 'staff_refund',
        'audience' => 'staff',
        'template_id' => 'ZvVDAtr4cJNdP-SFKotwCQVzX5HPtUns5vTFO6C05nI',
        'data_mapping' => ['time1' => 'service_date', 'thing2' => 'hotel_name', 'thing4' => 'staff_name'],
        'page_path' => 'packages/pages/staff_order_detail/staff_order_detail',
        'remark' => '拒单通知',
        'status' => 1,
    ],
    [
        'scene' => 'staff_aftersale',
        'audience' => 'staff',
        'template_id' => '2LfkTjUakVHJhvVPvhMvFcTytY2K-6lN3XkjEI_6Fo4',
        'data_mapping' => ['character_string2' => 'ticket_sn', 'thing3' => 'package_name', 'time4' => 'service_date', 'thing5' => 'hotel_name'],
        'page_path' => 'packages/pages/staff_order_detail/staff_order_detail',
        'remark' => '工单处理提醒',
        'status' => 1,
    ],
    [
        'scene' => 'ticket_update',
        'audience' => 'user',
        'template_id' => '2LfkTjUakVHJhvVPvhMvFcTytY2K-6lN3XkjEI_6Fo4',
        'data_mapping' => ['character_string2' => 'ticket_sn', 'thing3' => 'package_name', 'time4' => 'service_date', 'thing5' => 'hotel_name'],
        'page_path' => 'packages/pages/aftersale/ticket_detail',
        'remark' => '工单处理提醒',
        'status' => 1,
    ],
    [
        'scene' => 'staff_internal',
        'audience' => 'staff',
        'template_id' => 'CiCHAwhKVNbQnyiWYevaj807sxyye5fJDMaUrZ2twfo',
        'data_mapping' => ['content' => 'content'],
        'page_path' => 'packages/pages/notification/index',
        'remark' => '订阅模板消息（系统通用内部通知）',
        'status' => 1,
    ],
];

echo "=== 微信服务号模板更新迁移 ===\n";
$now = time();
$count = 0;
foreach ($bindings as $b) {
    $mappingJson = json_encode($b['data_mapping'], JSON_UNESCAPED_UNICODE);
    $existing = $db->table($table)->where('scene', $b['scene'])->where('audience', $b['audience'])->find();
    if ($existing) {
        $sql = "UPDATE `{$table}` SET `template_id` = '{$b['template_id']}', `data_mapping` = '{$mappingJson}', `page_path` = '{$b['page_path']}', `status` = {$b['status']}, `remark` = '{$b['remark']}', `update_time` = {$now} WHERE `scene` = '{$b['scene']}' AND `audience` = '{$b['audience']}'";
        echo $sql . ";\n";
        if ($apply) {
            $db->execute($sql);
            $count++;
        }
    } else {
        $sql = "INSERT INTO `{$table}` (`scene`, `audience`, `template_id`, `data_mapping`, `page_path`, `status`, `sort`, `remark`, `create_time`, `update_time`) VALUES ('{$b['scene']}', '{$b['audience']}', '{$b['template_id']}', '{$mappingJson}', '{$b['page_path']}', {$b['status']}, 0, '{$b['remark']}', {$now}, {$now})";
        echo $sql . ";\n";
        if ($apply) {
            $db->execute($sql);
            $count++;
        }
    }
}

if ($apply) {
    echo "\n[OK] 成功应用 {$count} 条模板更新到 {$table} 表。\n";
} else {
    echo "\n[只读预览] 共检测到 " . count($bindings) . " 条待更新模板，添加 --apply 可提交更新。\n";
}
