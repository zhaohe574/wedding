<?php

declare(strict_types=1);

// 仅供部署命令行使用。默认只显示计划，维护窗口备份后加 --apply 执行。
if (PHP_SAPI !== 'cli') exit(1);
$root = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR;
require $root . 'vendor/autoload.php';
$app = new \think\App($root);
$app->initialize();
// 框架的默认异常渲染不能代表迁移成功，必须向部署脚本返回非零状态。
set_exception_handler(static function (Throwable $error): void {
    fwrite(STDERR, '迁移失败：' . $error->getMessage() . "\n");
    exit(1);
});
$db = \think\facade\Db::connect();
$prefix = (string)config('database.connections.mysql.prefix', 'la_');
if (!preg_match('/^[a-zA-Z0-9_]*$/', $prefix)) throw new RuntimeException('数据库前缀不合法');
$apply = in_array('--apply', $argv, true);
$baseline = file_get_contents($root . 'public/install/db/like.sql');
preg_match_all('/CREATE TABLE `la_([^`]+)`\s*\((.*?)\) ENGINE[^;]+;/s', $baseline, $matches, PREG_SET_ORDER);
$definitions = [];
foreach ($matches as $match) $definitions[$match[1]] = [$match[0], $match[2]];

$tables = ['manual_schedule', 'staff_settlement_repay', 'wechat_binding_attempt'];
$columns = [
    'activity_payment' => ['query_time', 'closed_time'],
    'activity_refund' => ['is_compensation', 'query_time'],
    'activity_registration' => ['quota_released'],
    'admin' => ['user_id'],
    'financial_flow' => ['unique_biz_id'],
    'payment' => ['query_time', 'closed_time', 'collection_owner', 'pay_voucher'],
    'refund' => ['is_compensation'],
    'refund_item' => ['refund_voucher'],
    'schedule' => ['manual_schedule_id'],
    'staff_schedule_confirm_letter' => ['manual_schedule_id'],
    'staff_settlement' => ['platform_paid_share_amount', 'staff_due_platform_amount', 'staff_due_collected_amount', 'staff_due_collect_status', 'staff_due_collect_time', 'staff_due_collect_admin_id', 'staff_due_collect_remark'],
    'wechat_oa_bind_session' => ['admin_id', 'candidate_user_id', 'candidate_openid', 'binding_code'],
    'wechat_oa_notification_log' => ['lock_token'],
];
$indexes = [
    'admin' => ['uk_user_id'],
    'financial_flow' => ['uk_flow_business'],
    'staff_schedule_confirm_letter' => ['uk_order_staff_version', 'idx_manual_current'],
    'staff_settlement' => ['uk_order_item_id', 'idx_due_collect_status'],
    'wechat_oa_bind_session' => ['uk_binding_code'],
];

// 唯一约束冲突必须先处理，不能在迁移时删除或合并真实业务记录。
foreach ([
    ['financial_flow', 'biz_type,biz_id,flow_type', 'biz_id <> 0'],
    ['staff_settlement', 'order_item_id', 'order_item_id > 0'],
] as [$table, $group, $where]) {
    if ($db->query("SELECT $group FROM `{$prefix}{$table}` WHERE $where GROUP BY $group HAVING COUNT(*) > 1 LIMIT 1")) {
        throw new RuntimeException($table . ' 存在重复业务记录，停止迁移');
    }
}
$plan = [];
$exists = [];
foreach ($db->query('SHOW TABLES') as $row) $exists[(string)reset($row)] = true;
foreach ($tables as $table) {
    if (!isset($exists[$prefix . $table])) {
        // 新增表的文本字段兼容线上 MySQL 5.7，保留已有表的排序规则。
        $plan[] = str_replace(['`la_', 'utf8mb4_0900_ai_ci'], ['`' . $prefix, 'utf8mb4_general_ci'], $definitions[$table][0]);
    }
}
$fieldInfo = [];
foreach ($columns as $table => $names) {
    $fieldInfo[$table] = array_column($db->query("SHOW COLUMNS FROM `{$prefix}{$table}`"), null, 'Field');
    $parts = [];
    foreach ($names as $name) {
        if (isset($fieldInfo[$table][$name])) continue;
        if (!preg_match('/^\s*`' . preg_quote($name, '/') . '`[^\r\n]+/m', $definitions[$table][1], $match)) {
            throw new RuntimeException('安装基线缺少字段：' . $table . '.' . $name);
        }
        $definition = rtrim(trim($match[0]), ',');
        if ($name === 'binding_code') $definition .= " DEFAULT ''";
        $parts[] = 'ADD COLUMN ' . $definition;
    }
    if ($parts) $plan[] = "ALTER TABLE `{$prefix}{$table}` " . implode(', ', $parts);
}

foreach (['staff_settlement' => 'order_item_id', 'wechat_oa_follower' => 'user_id'] as $table => $column) {
    $fields = $fieldInfo[$table] ?? array_column($db->query("SHOW COLUMNS FROM `{$prefix}{$table}`"), null, 'Field');
    if ($fields[$column]['Null'] !== 'YES') $plan[] = "ALTER TABLE `{$prefix}{$table}` MODIFY COLUMN `$column` int unsigned DEFAULT NULL";
    $plan[] = "UPDATE `{$prefix}{$table}` SET `$column` = NULL WHERE `$column` = 0";
}
// 旧版绑定会话保留用于追溯，但不允许继续用于新版双向确认。
$plan[] = "UPDATE `{$prefix}wechat_oa_bind_session` SET binding_code = CONCAT('M', LPAD(id, 9, '0')), expires_time = 0 WHERE binding_code = ''";
foreach ($indexes as $table => $names) {
    $current = [];
    foreach ($db->query("SHOW INDEX FROM `{$prefix}{$table}`") as $row) $current[$row['Key_name']][] = $row['Column_name'];
    foreach ($names as $name) {
        if (!preg_match('/^\s*(?:UNIQUE KEY|KEY) `' . preg_quote($name, '/') . '` \(([^)]+)\)/m', $definitions[$table][1], $match)) {
            throw new RuntimeException('安装基线缺少索引：' . $name);
        }
        preg_match_all('/`([^`]+)`/', $match[1], $keys);
        if (($current[$name] ?? []) === $keys[1]) continue;
        $drop = isset($current[$name]) ? "DROP INDEX `$name`, " : '';
        $plan[] = "ALTER TABLE `{$prefix}{$table}` $drop ADD " . trim($match[0]);
    }
}
foreach ($plan as $sql) {
    echo $sql, ";\n";
    if ($apply) $db->execute($sql);
}

$retiredComponents = ['channel/h5', 'channel/open_setting', 'app/recharge/index', 'finance/recharge_record', 'finance/balance_details', 'finance/refund_record', 'subscribe/template/index', 'setting/wecom/index'];
$retiredIds = $db->table($prefix . 'system_menu')->whereIn('component', $retiredComponents)->column('id');
$allMenus = $db->table($prefix . 'system_menu')->field('id,pid')->select()->toArray();
do {
    $previousCount = count($retiredIds);
    foreach ($allMenus as $menu) {
        if (in_array($menu['pid'], $retiredIds) && !in_array($menu['id'], $retiredIds)) $retiredIds[] = $menu['id'];
    }
} while (count($retiredIds) !== $previousCount);
echo "任务配置：停用已退役订阅消息任务，补登记支付查单任务，不改变其他任务状态。\n";
echo '退役页面及子操作：停用 '.count($retiredIds)." 个菜单记录，保留授权记录以便追溯。\n";
if ($apply) {
    $db->transaction(static function () use ($db, $prefix, $retiredIds) {
        if ($retiredIds) $db->table($prefix . 'system_menu')->whereIn('id', $retiredIds)->update(['is_disable' => 1, 'is_show' => 0, 'update_time' => time()]);
        $db->table($prefix . 'dev_crontab')->where('command', 'send_subscribe_messages')->update(['status' => 2, 'update_time' => time()]);
        if (!$db->table($prefix . 'dev_crontab')->where('command', 'query_payments')->find()) {
            $db->table($prefix . 'dev_crontab')->insert([
                'name' => '微信支付查单与关单', 'type' => 1, 'system' => 1,
                'remark' => '每分钟恢复订单、活动与抽成补交付款结果，并关闭超时流水',
                'command' => 'query_payments', 'params' => '', 'status' => 1,
                'expression' => '* * * * *', 'error' => '', 'last_time' => time(),
                'time' => 0, 'max_time' => 0, 'create_time' => time(), 'update_time' => time(),
            ]);
        }
    });
    (new \app\common\cache\AdminAuthCache())->deleteTag();
}
echo $apply ? "结构迁移完成。\n" : "只读预检完成，未修改数据库。\n";
