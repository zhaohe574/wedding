<?php
declare(strict_types=1);

/**
 * 热路径索引补齐（T25）。
 * 默认只读预览：仅打印待执行的 ALTER TABLE 语句；维护窗口执行 --apply 落库。
 * 索引名与 like.sql 既有风格保持一致（idx_*）。
 * 注意：同一批索引必须同步维护在 server/public/install/db/like.sql 的建表语句中，
 * 否则新装环境仍然缺索引（Q-07 同款问题）。
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

/**
 * 待补索引清单：表名（不含前缀） => [索引名, 字段列表, 说明]
 * - sms_log：验证码发送频控与校验均按 mobile + scene_id + send_status + is_verify 过滤（SmsDriver::sendLimit/verify）
 * - notice_record：通知未读数按 user_id + read 统计
 * - article_collect：文章收藏列表按 user_id + article_id 过滤
 * - user_auth：登录态/支付 openid 查询按 user_id 过滤
 */
$indexList = [
    'sms_log' => [
        ['idx_mobile_scene_status', ['mobile', 'scene_id', 'send_status', 'is_verify'], '短信频控与验证码校验热路径'],
    ],
    'notice_record' => [
        ['idx_user_read', ['user_id', 'read'], '用户通知未读数统计'],
    ],
    'article_collect' => [
        ['idx_user_article', ['user_id', 'article_id'], '文章收藏查询'],
    ],
    'user_auth' => [
        ['idx_user_id', ['user_id'], '按用户查授权记录'],
    ],
];

$pending = 0;
foreach ($indexList as $tableName => $indexes) {
    $table = $prefix . $tableName;
    $columns = $db->query("SHOW COLUMNS FROM `{$table}`");
    $columnNames = array_column($columns, 'Field');
    $indexRows = $db->query("SHOW INDEX FROM `{$table}`");
    $existingIndexNames = array_values(array_unique(array_column($indexRows, 'Key_name')));

    foreach ($indexes as [$indexName, $fields, $remark]) {
        $missingFields = array_values(array_diff($fields, $columnNames));
        if ($missingFields) {
            echo "[跳过] `{$table}` 缺少字段 " . implode(',', $missingFields) . "，索引 {$indexName} 未创建（{$remark}）。\n";
            continue;
        }
        if (in_array($indexName, $existingIndexNames, true)) {
            echo "[已存在] `{$table}`.{$indexName}\n";
            continue;
        }
        $fieldSql = implode(', ', array_map(static fn (string $field): string => "`{$field}`", $fields));
        $sql = "ALTER TABLE `{$table}` ADD INDEX `{$indexName}` ({$fieldSql}) COMMENT '{$remark}'";
        $pending++;
        echo $sql . ";\n";
        if ($apply) {
            $db->execute($sql);
            echo "已创建索引 `{$table}`.{$indexName}\n";
        }
    }
}

if ($pending === 0) {
    echo "热路径索引检查完成：无缺失索引。\n";
} elseif ($apply) {
    echo "热路径索引检查完成：已创建 {$pending} 个索引。\n";
} else {
    echo "[只读报告] 检测到 {$pending} 个缺失索引，添加 --apply 可提交创建。\n";
}
