<?php
declare(strict_types=1);

/**
 * 新人问卷自动推送时间条件字段（2026-11-25）。
 *
 * 默认只读预览；维护窗口追加 --apply 执行 ALTER：
 *   php server/database/migrations/20261125_couple_questionnaire_auto_push_schedule.php
 *   php server/database/migrations/20261125_couple_questionnaire_auto_push_schedule.php --apply
 */
require dirname(__DIR__, 2) . '/vendor/autoload.php';
$app = new think\App(dirname(__DIR__, 2) . DIRECTORY_SEPARATOR);
$app->initialize();

foreach ($argv as $argument) {
    if (!str_starts_with($argument, '--test-database=')) {
        continue;
    }
    $name = substr($argument, strlen('--test-database='));
    $port = (int) getenv('WEDDING_TEST_MYSQL_PORT');
    if (!preg_match('/^wedding_binding_[0-9]+_[0-9]+$/', $name) || $port <= 0 || $port === 3306) {
        throw new RuntimeException('仅允许独立本机测试数据库');
    }
    $app->config->set([
        'default' => 'mysql',
        'connections' => [
            'mysql' => [
                'type' => 'mysql',
                'hostname' => '127.0.0.1',
                'hostport' => $port,
                'database' => $name,
                'username' => 'root',
                'password' => (string) getenv('WEDDING_TEST_MYSQL_PASSWORD'),
                'charset' => 'utf8mb4',
                'prefix' => 'qa_',
                'debug' => false,
            ],
        ],
    ], 'database');
}

set_exception_handler(static function (Throwable $e): void {
    fwrite(STDERR, $e->getMessage() . "\n");
    exit(1);
});

$db = think\facade\Db::connect();
$prefix = (string) config('database.connections.mysql.prefix', 'la_');
if (!preg_match('/^[a-zA-Z0-9_]*$/', $prefix)) {
    throw new RuntimeException('数据库前缀不合法');
}

$apply = in_array('--apply', $argv, true);
$questionnaireTable = $prefix . 'couple_questionnaire';
$taskTable = $prefix . 'couple_questionnaire_task';

$columnChanges = [
    [
        $questionnaireTable,
        'auto_push_trigger',
        "ALTER TABLE `{$questionnaireTable}` ADD COLUMN `auto_push_trigger` tinyint(1) unsigned NOT NULL DEFAULT '1' COMMENT '自动推送时机:1支付后立即,2距服务日前N天,3支付后延迟N天' AFTER `push_mode`",
    ],
    [
        $questionnaireTable,
        'auto_push_days',
        "ALTER TABLE `{$questionnaireTable}` ADD COLUMN `auto_push_days` smallint(5) unsigned NOT NULL DEFAULT '0' COMMENT '自动推送条件天数' AFTER `auto_push_trigger`",
    ],
    [
        $questionnaireTable,
        'auto_push_time',
        "ALTER TABLE `{$questionnaireTable}` ADD COLUMN `auto_push_time` varchar(5) NOT NULL DEFAULT '10:00' COMMENT '自动推送时间点(HH:MM)' AFTER `auto_push_days`",
    ],
    [
        $taskTable,
        'auto_push_trigger',
        "ALTER TABLE `{$taskTable}` ADD COLUMN `auto_push_trigger` tinyint(1) unsigned NOT NULL DEFAULT '1' COMMENT '自动推送时机快照:1支付后立即,2距服务日前N天,3支付后延迟N天' AFTER `push_mode`",
    ],
    [
        $taskTable,
        'auto_push_days',
        "ALTER TABLE `{$taskTable}` ADD COLUMN `auto_push_days` smallint(5) unsigned NOT NULL DEFAULT '0' COMMENT '自动推送条件天数快照' AFTER `auto_push_trigger`",
    ],
    [
        $taskTable,
        'auto_push_time',
        "ALTER TABLE `{$taskTable}` ADD COLUMN `auto_push_time` varchar(5) NOT NULL DEFAULT '10:00' COMMENT '自动推送时间点快照(HH:MM)' AFTER `auto_push_days`",
    ],
    [
        $taskTable,
        'planned_send_time',
        "ALTER TABLE `{$taskTable}` ADD COLUMN `planned_send_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '计划自动推送时间戳(0=立即或手动)' AFTER `auto_push_time`",
    ],
];

$indexChanges = [
    [
        $taskTable,
        'idx_planned_send',
        "ALTER TABLE `{$taskTable}` ADD KEY `idx_planned_send` (`push_mode`,`send_status`,`planned_send_time`)",
    ],
];

$pending = [];
foreach ($columnChanges as [$table, $column, $sql]) {
    $exists = $db->query("SHOW COLUMNS FROM `{$table}` LIKE '{$column}'");
    if (!empty($exists)) {
        echo "[已存在] `{$table}`.{$column}\n";
        continue;
    }
    echo $sql . ";\n";
    $pending[] = [$sql, "已添加列 `{$table}`.{$column}"];
}

foreach ($indexChanges as [$table, $index, $sql]) {
    $exists = $db->query("SHOW INDEX FROM `{$table}` WHERE Key_name = '{$index}'");
    if (!empty($exists)) {
        echo "[已存在] `{$table}` 索引 {$index}\n";
        continue;
    }
    echo $sql . ";\n";
    $pending[] = [$sql, "已添加索引 `{$table}`.{$index}"];
}

if (empty($pending)) {
    echo "[完成] 所有字段与索引均已存在，无需变更。\n";
    exit(0);
}

if (!$apply) {
    echo "[只读预览] 未修改数据库；确认无误后追加 --apply 执行。\n";
    exit(0);
}

foreach ($pending as [$sql, $message]) {
    $db->execute($sql);
    echo $message . "\n";
}
