<?php
declare(strict_types=1);

/** 默认只读报告；维护窗口执行 --apply。不会按手机号推断身份。 */
if (PHP_SAPI !== 'cli') exit(1);
require dirname(__DIR__, 2) . '/vendor/autoload.php';
$app = new \think\App(dirname(__DIR__, 2) . DIRECTORY_SEPARATOR);
$app->initialize();
foreach ($argv as $argument) {
    if (!str_starts_with($argument, '--test-database=')) continue;
    $testDatabase = substr($argument, strlen('--test-database='));
    $testPort = (int)getenv('WEDDING_TEST_MYSQL_PORT');
    if (!preg_match('/^wedding_binding_[0-9]+_[0-9]+$/', $testDatabase) || $testPort <= 0 || $testPort === 3306) {
        throw new RuntimeException('仅允许独立本机测试数据库');
    }
    $app->config->set(['default' => 'mysql', 'connections' => ['mysql' => [
        'type' => 'mysql', 'hostname' => '127.0.0.1', 'hostport' => $testPort, 'database' => $testDatabase,
        'username' => 'root', 'password' => (string)getenv('WEDDING_TEST_MYSQL_PASSWORD'),
        'charset' => 'utf8mb4', 'prefix' => 'qa_', 'debug' => false,
    ]]], 'database');
}
set_exception_handler(static function (Throwable $e): void { fwrite(STDERR, $e->getMessage() . "\n"); exit(1); });
$db = \think\facade\Db::connect();
$prefix = (string)config('database.connections.mysql.prefix', 'la_');
if (!preg_match('/^[a-zA-Z0-9_]*$/', $prefix)) throw new RuntimeException('数据库前缀不合法');
$apply = in_array('--apply', $argv, true);
$baseline = file_get_contents(dirname(__DIR__, 2) . '/public/install/db/like.sql');
preg_match_all('/CREATE TABLE `la_([^`]+)`\s*\((.*?)\) ENGINE[^;]+;/s', $baseline, $matches, PREG_SET_ORDER);
$defs = [];
foreach ($matches as $m) $defs[$m[1]] = [$m[0], $m[2]];
$conflicts = [];
foreach (['admin' => ['user_id'], 'staff' => ['user_id', 'admin_id']] as $table => $fields) {
    foreach ($fields as $field) {
        $scope = $table === 'staff' ? ' AND delete_time IS NULL' : '';
        $rows = $db->query("SELECT `$field`, GROUP_CONCAT(id) AS ids FROM `{$prefix}{$table}` WHERE `$field` > 0$scope GROUP BY `$field` HAVING COUNT(*) > 1");
        foreach ($rows as $row) $conflicts[] = ['table' => $table, 'field' => $field] + $row;
    }
}
$relations = $db->query("SELECT s.id AS staff_id,s.status AS staff_status,s.admin_id,s.user_id,a.user_id AS admin_user_id,a.id AS found_admin,u.id AS found_user FROM `{$prefix}staff` s LEFT JOIN `{$prefix}admin` a ON a.id=s.admin_id AND a.delete_time IS NULL LEFT JOIN `{$prefix}user` u ON u.id=s.user_id AND u.delete_time IS NULL WHERE s.delete_time IS NULL");
$repair = [];
foreach ($relations as $row) {
    if (!(int)$row['user_id'] && !(int)$row['admin_user_id'] && !(int)$row['staff_status']) continue;
    if (!$row['found_admin'] || !$row['found_user']) { $conflicts[] = ['reason' => '缺少有效后台账号或用户'] + $row; continue; }
    if ((int)$row['admin_user_id'] === (int)$row['user_id']) continue;
    if ((int)$row['admin_user_id'] > 0 || $db->table($prefix . 'admin')->where('user_id', $row['user_id'])->where('id', '<>', $row['admin_id'])->find()) {
        $conflicts[] = ['reason' => '档案与后台用户冲突'] + $row;
    } else { $repair[] = $row; }
}
echo json_encode(['mode' => $apply ? '执行迁移' : '只读报告', 'safe_repairs' => $repair, 'conflicts' => $conflicts], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT), "\n";
if ($apply && $conflicts) throw new RuntimeException('存在关联冲突，请先由管理员核实；本次未执行任何修改');
$plan = [];
$tables = [];
foreach ($db->query('SHOW TABLES') as $row) $tables[(string)reset($row)] = true;
foreach (['account_binding_audit', 'notification_event'] as $table) {
    if (!isset($tables[$prefix . $table])) $plan[] = str_replace('`la_', '`' . $prefix, $defs[$table][0]);
}
foreach (['notification' => ['event_key', 'audience', 'identity_revoked', 'business_type', 'business_id', 'access_options'], 'user' => ['oa_guide_seen_time'], 'staff' => ['active_user_id', 'active_admin_id']] as $table => $fields) {
    $existing = array_column($db->query("SHOW COLUMNS FROM `{$prefix}{$table}`"), 'Field');
    foreach ($fields as $field) if (!in_array($field, $existing, true)) {
        if (!preg_match('/^\s*`' . $field . '`[^\r\n]+/m', $defs[$table][1], $m)) throw new RuntimeException('基线字段缺失');
        $plan[] = "ALTER TABLE `{$prefix}{$table}` ADD COLUMN " . rtrim(trim($m[0]), ',');
    }
}
foreach (['admin' => ['user_id'], 'staff' => ['user_id', 'admin_id']] as $table => $fields) {
    foreach ($fields as $field) {
        $columns = array_column($db->query("SHOW COLUMNS FROM `{$prefix}{$table}`"), null, 'Field');
        if ($columns[$field]['Null'] !== 'YES') $plan[] = "ALTER TABLE `{$prefix}{$table}` MODIFY `$field` int unsigned DEFAULT NULL";
        $plan[] = "UPDATE `{$prefix}{$table}` SET `$field`=NULL WHERE `$field`=0";
    }
}
foreach (['notification' => ['uk_notification_event'], 'staff' => ['uk_staff_user', 'uk_staff_admin']] as $table => $indexes) {
    $indexRows = $db->query("SHOW INDEX FROM `{$prefix}{$table}`");
    $existing = array_column($indexRows, 'Key_name');
    if ($table === 'staff') {
        foreach ($indexRows as $row) {
            if (in_array($row['Key_name'], $indexes, true) && !str_starts_with($row['Column_name'], 'active_')) {
                $plan[] = "ALTER TABLE `{$prefix}{$table}` DROP INDEX `{$row['Key_name']}`";
                $existing = array_diff($existing, [$row['Key_name']]);
            }
        }
    }
    foreach ($indexes as $index) if (!in_array($index, $existing, true)) {
        preg_match('/UNIQUE KEY `' . $index . '` \([^)]+\)/', $defs[$table][1], $m);
        $plan[] = "ALTER TABLE `{$prefix}{$table}` ADD " . $m[0];
    }
}
foreach ($plan as $sql) { echo $sql, ";\n"; if ($apply) $db->execute($sql); }
if (!$apply) exit(0);
$db->transaction(static function () use ($db, $prefix, $repair): void {
    foreach ($repair as $row) {
        $db->table($prefix . 'admin')->where('id', $row['admin_id'])->update(['user_id' => $row['user_id']]);
        $db->table($prefix . 'account_binding_audit')->insert(['admin_id' => $row['admin_id'], 'staff_id' => $row['staff_id'],
            'old_user_id' => 0, 'new_user_id' => $row['user_id'], 'operator_id' => 0, 'reason' => '迁移补齐明确档案关联', 'create_time' => time()]);
    }
    $db->table($prefix . 'notification')->whereIn('target_type', ['staff_order', 'staff_settlement'])->update(['audience' => 'staff']);
    $db->table($prefix . 'notification')->where('target_type', 'staff_order')->where('business_type', '')
        ->update(['business_type' => 'order', 'business_id' => \think\facade\Db::raw('target_id')]);
    $db->table($prefix . 'notification')->where('target_type', 'staff_settlement')->where('business_type', '')
        ->update(['business_type' => 'settlement', 'business_id' => \think\facade\Db::raw('target_id')]);
    if (!$db->table($prefix . 'wechat_oa_notification_template')->where('scene', 'settlement_update')->where('audience', 'staff')->find()) {
        $db->table($prefix . 'wechat_oa_notification_template')->where('scene', 'settlement_update')->where('audience', 'user')->update(['audience' => 'staff']);
    }
    $db->table($prefix . 'wechat_oa_bind_session')->where('admin_id', '>', 0)->where('status', 0)->update(['status' => 2, 'expires_time' => time()]);
    $db->table($prefix . 'config')->where('type', 'feature_switch')->where('name', 'staff_admin')->update(['value' => '1']);
});
echo "迁移完成。请执行 php think sync_admin_permissions 登记新增权限，再由管理员授权账号绑定操作。\n";
