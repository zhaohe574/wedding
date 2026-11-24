<?php
declare(strict_types=1);

/**
 * 增量迁移：创建服务人员文案话术模板库表 la_staff_copy_template。
 *
 * 默认只读预检；传入 --apply 执行变更：
 *   php server/database/migrations/20261120_staff_copy_template.php
 *   php server/database/migrations/20261120_staff_copy_template.php --apply
 */

if (PHP_SAPI !== 'cli') {
    exit(1);
}

$serverRoot = dirname(__DIR__, 2);
require $serverRoot . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';

$app = new \think\App($serverRoot . DIRECTORY_SEPARATOR);
$app->initialize();

set_exception_handler(static function (Throwable $error): void {
    fwrite(STDERR, '迁移失败：' . $error->getMessage() . "\n");
    exit(1);
});

$db = \think\facade\Db::connect();
$prefix = (string)config('database.connections.mysql.prefix', 'la_');
if (!preg_match('/^[a-zA-Z0-9_]*$/', $prefix)) {
    throw new RuntimeException('数据库表前缀不合法');
}

$apply = in_array('--apply', $argv, true);
$tableName = $prefix . 'staff_copy_template';
$existingTables = array_map(static fn (array $row): string => (string)array_values($row)[0], $db->query('SHOW TABLES'));

if (in_array($tableName, $existingTables, true)) {
    echo "{$tableName} 已存在，无需变更。\n";
    exit(0);
}

$sql = <<<SQL
CREATE TABLE `{$tableName}` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `staff_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '服务人员ID',
  `scene` varchar(32) COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'inquiry' COMMENT '话术场景',
  `title` varchar(80) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '模板标题',
  `content` text COLLATE utf8mb4_general_ci NOT NULL COMMENT '文案正文',
  `sort` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '排序权重(越大越靠前)',
  `use_count` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '累计使用次数',
  `status` tinyint(1) unsigned NOT NULL DEFAULT '1' COMMENT '状态：0=停用,1=启用',
  `create_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  `update_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  `delete_time` int(10) unsigned DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`),
  KEY `idx_staff_scene_status` (`staff_id`,`scene`,`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='服务人员文案话术模板库表'
SQL;

echo ($apply ? '[APPLY] ' : '[PLAN]  ') . "CREATE TABLE `{$tableName}` (...)\n";
if ($apply) {
    $db->execute($sql);
}

echo $apply ? "执行完成。\n" : "预检完成（未修改数据库，传入 --apply 执行）。\n";
