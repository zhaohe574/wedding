<?php
declare(strict_types=1);

/**
 * 增量迁移：初始化平台统一话术模板（la_staff_copy_template，staff_id=0）。
 *
 * 默认只读预检；传入 --apply 执行落库：
 *   php server/database/migrations/20261125_seed_system_copy_templates.php
 *   php server/database/migrations/20261125_seed_system_copy_templates.php --apply
 */

if (PHP_SAPI !== 'cli') {
    exit(1);
}

$serverRoot = dirname(__DIR__, 2);
require $serverRoot . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';

$app = new \think\App($serverRoot . DIRECTORY_SEPARATOR);
$app->initialize();

use app\common\model\staff\StaffCopyTemplate;

set_exception_handler(static function (Throwable $error): void {
    fwrite(STDERR, '迁移失败：' . $error->getMessage() . "\n");
    exit(1);
});

$db = \think\facade\Db::connect();
$prefix = (string)config('database.connections.mysql.prefix', 'la_');
if (!preg_match('/^[a-zA-Z0-9_]*$/', $prefix)) {
    throw new RuntimeException('数据库表前缀不合法');
}

$tableName = $prefix . 'staff_copy_template';
$exists = $db->query("SHOW TABLES LIKE '{$tableName}'");
if (empty($exists)) {
    throw new RuntimeException("表 `{$tableName}` 不存在，请先运行 20261120_staff_copy_template.php --apply");
}

$apply = in_array('--apply', $argv, true);
$existingCount = StaffCopyTemplate::withTrashed()->where('staff_id', 0)->count();

if ($existingCount > 0) {
    echo "已存在 {$existingCount} 条平台统一话术记录（含已停用/已删），跳过初始化。\n";
    exit(0);
}

$defaultCount = count(StaffCopyTemplate::getDefaultSystemTemplates());
echo ($apply ? '[APPLY] ' : '[PLAN]  ') . "将初始化 {$defaultCount} 条平台统一话术模板（staff_id=0）。\n";

if ($apply) {
    StaffCopyTemplate::ensureSystemTemplatesInitialized();
    $afterCount = StaffCopyTemplate::withTrashed()->where('staff_id', 0)->count();
    echo "初始化完成，当前共有 {$afterCount} 条平台统一话术记录。\n";
} else {
    echo "预检完成（未修改数据库，传入 --apply 执行）。\n";
}
