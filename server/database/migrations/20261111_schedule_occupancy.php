<?php
declare(strict_types=1);

/**
 * 档期占用口径改造（2026-11-11）。
 *
 * 1. 为「人员 + 日期 + 订单项类型/状态」占用统计补复合索引；
 * 2. 可选清理历史脏数据：未付款/已取消订单遗留的 `la_schedule.status = 2`（已预约）记录。
 *
 * 默认只读预览，维护窗口执行：
 *   php database/migrations/20261111_schedule_occupancy.php --apply
 *   php database/migrations/20261111_schedule_occupancy.php --apply --repair
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

$apply = in_array('--apply', $argv, true);
$repair = in_array('--repair', $argv, true);

$table = $prefix . 'order_item';
$indexName = 'idx_staff_date_type';
$indexRows = $db->query("SHOW INDEX FROM `{$table}`");
$existing = array_values(array_unique(array_column($indexRows, 'Key_name')));

if (in_array($indexName, $existing, true)) {
    echo "[已存在] `{$table}`.{$indexName}\n";
} else {
    $sql = "ALTER TABLE `{$table}` ADD INDEX `{$indexName}` (`staff_id`, `service_date`, `item_type`, `item_status`) COMMENT '档期占用统计（人员+日期+项类型/状态）'";
    echo $sql . ";\n";
    if ($apply) {
        $db->execute($sql);
        echo "已创建索引 `{$table}`.{$indexName}\n";
    }
}

if (!$repair) {
    echo "[只读报告] 如需清理未付款订单遗留的已预约档期，追加 --repair --apply。\n";
    exit(0);
}

$scheduleTable = $prefix . 'schedule';
$orderTable = $prefix . 'order';
$staleSql = "SELECT s.id, s.staff_id, s.schedule_date, s.order_id
    FROM `{$scheduleTable}` s
    LEFT JOIN `{$orderTable}` o ON o.id = s.order_id
    WHERE s.status = 2
      AND s.manual_schedule_id = 0
      AND (
        s.order_id = 0
        OR o.id IS NULL
        OR o.delete_time IS NOT NULL
        OR o.order_status NOT IN (1,2,3,4,5,7,10)
        OR (o.paid_amount <= 0 AND o.deposit_paid = 0 AND o.pay_status <> 1)
      )";
$staleRows = $db->query($staleSql);
echo '待释放的历史已预约档期：' . count($staleRows) . " 条\n";
foreach ($staleRows as $row) {
    echo "  schedule#{$row['id']} staff#{$row['staff_id']} {$row['schedule_date']} order#{$row['order_id']}\n";
    if ($apply) {
        $db->execute(
            "UPDATE `{$scheduleTable}` SET status = 1, order_id = 0, lock_type = 0, lock_user_id = 0,"
            . " lock_expire_time = 0, version = version + 1, update_time = ? WHERE id = ? AND status = 2",
            [time(), (int)$row['id']]
        );
    }
}

if (!$apply) {
    echo "[只读报告] 追加 --apply 才会真正释放。\n";
    exit(0);
}

echo "历史档期清理完成。\n";
