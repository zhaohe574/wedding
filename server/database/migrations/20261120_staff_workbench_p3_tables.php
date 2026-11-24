<?php
declare(strict_types=1);

/**
 * 增量迁移：创建工作台 P3 三张核心表：
 * - la_staff_service_note（服务手记）
 * - la_staff_followup_reminder（客户跟进提醒）
 * - la_staff_wedding_checklist（婚礼 Checklist）
 *
 * 默认只读预检；传入 --apply 执行变更：
 *   php server/database/migrations/20261120_staff_workbench_p3_tables.php
 *   php server/database/migrations/20261120_staff_workbench_p3_tables.php --apply
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
$existingTables = array_map(
    static fn (array $row): string => (string)array_values($row)[0],
    $db->query('SHOW TABLES')
);

$definitions = [
    $prefix . 'staff_service_note' => <<<SQL
CREATE TABLE `{$prefix}staff_service_note` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `staff_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '服务人员ID',
  `source_type` varchar(16) COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'order' COMMENT '来源类型:order/manual',
  `target_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '关联订单或线下档期ID',
  `category` varchar(32) COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'communication' COMMENT '手记分类',
  `content` text COLLATE utf8mb4_general_ci NOT NULL COMMENT '手记正文',
  `images` text COLLATE utf8mb4_general_ci COMMENT '附图JSON数组',
  `is_pinned` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '是否置顶:0=否,1=是',
  `create_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  `update_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  `delete_time` int(10) unsigned DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`),
  KEY `idx_staff_target` (`staff_id`,`source_type`,`target_id`),
  KEY `idx_staff_pinned` (`staff_id`,`is_pinned`,`update_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='服务人员服务手记表'
SQL,
    $prefix . 'staff_followup_reminder' => <<<SQL
CREATE TABLE `{$prefix}staff_followup_reminder` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `staff_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '服务人员ID',
  `source_type` varchar(16) COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'general' COMMENT '来源类型:order/manual/general',
  `target_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '关联订单或线下档期ID',
  `remind_type` varchar(32) COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'pre_comm' COMMENT '提醒类型',
  `title` varchar(100) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '提醒标题',
  `remind_date` date NOT NULL COMMENT '提醒日期',
  `remind_time` varchar(8) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '10:00' COMMENT '提醒时间',
  `remind_at` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '提醒时间戳',
  `remark` varchar(500) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '跟进备注',
  `status` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '状态:0=待跟进,1=已完成,2=已忽略',
  `done_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '完成时间戳',
  `create_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  `update_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  `delete_time` int(10) unsigned DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`),
  KEY `idx_staff_status_date` (`staff_id`,`status`,`remind_date`),
  KEY `idx_staff_target` (`staff_id`,`source_type`,`target_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='服务人员客户跟进提醒表'
SQL,
    $prefix . 'staff_wedding_checklist' => <<<SQL
CREATE TABLE `{$prefix}staff_wedding_checklist` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `staff_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '服务人员ID',
  `source_type` varchar(16) COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'order' COMMENT '来源类型:order/manual',
  `target_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '关联订单或线下档期ID',
  `stage` varchar(32) COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'pre_wedding' COMMENT '筹备阶段',
  `title` varchar(120) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '待办事项标题',
  `remark` varchar(255) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '备注说明',
  `is_done` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '是否已完成:0=否,1=是',
  `done_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '完成时间戳',
  `sort` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '排序权重',
  `is_preset` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '是否标准预设:0=自定义,1=预设',
  `create_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  `update_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  `delete_time` int(10) unsigned DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`),
  KEY `idx_staff_target_stage` (`staff_id`,`source_type`,`target_id`,`stage`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='服务人员婚礼Checklist表'
SQL,
];

$pendingCount = 0;
foreach ($definitions as $tableName => $sql) {
    if (in_array($tableName, $existingTables, true)) {
        echo "{$tableName} 已存在，跳过。\n";
        continue;
    }
    $pendingCount++;
    echo ($apply ? '[APPLY] ' : '[PLAN]  ') . "CREATE TABLE `{$tableName}` (...)\n";
    if ($apply) {
        $db->execute($sql);
    }
}

if ($pendingCount === 0) {
    echo "工作台 P3 数据表均已存在，无需变更。\n";
    exit(0);
}

echo $apply ? "执行完成（新建 {$pendingCount} 张表）。\n" : "预检完成（待建 {$pendingCount} 张表，传入 --apply 执行）。\n";
