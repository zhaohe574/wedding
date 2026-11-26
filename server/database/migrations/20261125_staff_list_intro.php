<?php
declare(strict_types=1);

/**
 * 批次1：服务人员资料信息链路修复 —— 新增「列表页简介」字段 list_intro。
 *
 * 列表页（小程序服务人员列表 / 后台人员列表 / 搜索）需要一段独立于
 * profile 的短简介，最长 36 个字符，落在 staff.list_intro。
 *
 * 默认 dry-run，加 --apply 才实际执行 DDL。
 *
 * 用法：
 *   php server/database/migrations/20261125_staff_list_intro.php
 *   php server/database/migrations/20261125_staff_list_intro.php --apply
 */
require dirname(__DIR__, 2) . '/vendor/autoload.php';
$app = new think\App(dirname(__DIR__, 2) . DIRECTORY_SEPARATOR);
$app->initialize();

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
$staffTable = $prefix . 'staff';

$exists = $db->query("SHOW COLUMNS FROM `{$staffTable}` LIKE 'list_intro'");
if (!empty($exists)) {
    echo "{$staffTable} 已存在 list_intro 列，无需处理。\n";
    exit(0);
}

$sql = "ALTER TABLE `{$staffTable}` ADD COLUMN `list_intro` varchar(100) NOT NULL DEFAULT '' COMMENT '列表页简介' AFTER `profile`";

if (!$apply) {
    echo "[DRY-RUN] 待执行：\n{$sql}\n\n加 --apply 执行。\n";
    exit(0);
}

$db->execute($sql);
echo "已为 {$staffTable} 添加 list_intro 列。\n";
