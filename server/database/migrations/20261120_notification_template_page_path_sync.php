<?php
declare(strict_types=1);

/**
 * 微信服务号 / 小程序订阅通知模板 page_path 对齐 newuniapp 路由（2026-11-20）。
 *
 * 使用 MiniappPagePathService::LEGACY_TO_CURRENT 与 normalize() 将存量数据库中的
 * uniapp 旧路径（packages/pages/*、pages/notification/index）规范化为 newuniapp 路径，
 * 并校验目标页面文件在 newuniapp/src/ 下真实存在。
 *
 * 默认只读预览；确认无误后在维护窗口追加 --apply 落库：
 *   php server/database/migrations/20261120_notification_template_page_path_sync.php
 *   php server/database/migrations/20261120_notification_template_page_path_sync.php --apply
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
$newuniappSrc = dirname(__DIR__, 3) . DIRECTORY_SEPARATOR . 'newuniapp' . DIRECTORY_SEPARATOR . 'src';
if (!is_dir($newuniappSrc)) {
    throw new RuntimeException('未找到 newuniapp/src 目录: ' . $newuniappSrc);
}

$tables = [
    'wechat_oa_notification_template' => '服务号模板',
    'wechat_mnp_notification_template' => '小程序订阅模板',
];

$pendingUpdates = 0;
$appliedUpdates = 0;
$alignedCount = 0;
$unmappedCount = 0;

foreach ($tables as $baseTable => $label) {
    $table = $prefix . $baseTable;
    $exists = $db->query("SHOW TABLES LIKE '{$table}'");
    if (empty($exists)) {
        echo "[跳过] 表 `{$table}` 不存在\n";
        continue;
    }

    $rows = $db->query("SELECT id, scene, page_path FROM `{$table}` ORDER BY id ASC");
    echo "[{$label}] `{$table}` 共 " . count($rows) . " 条\n";

    foreach ($rows as $row) {
        $id = (int) ($row['id'] ?? 0);
        $scene = (string) ($row['scene'] ?? '');
        $rawPagePath = (string) ($row['page_path'] ?? '');
        $trimmed = trim($rawPagePath);

        if ($trimmed === '') {
            echo "  [告警] #{$id} ({$scene}) page_path 为空\n";
            $unmappedCount++;
            continue;
        }

        $normalized = app\common\service\MiniappPagePathService::normalize($trimmed, false);
        if ($scene === 'settlement_update' && $normalized === 'packages/pkg-staff/pages/center/center') {
            $normalized = 'packages/pkg-staff/pages/settlement-list/settlement-list';
        }
        $route = explode('?', $normalized, 2)[0];
        $vueFile = $newuniappSrc . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $route) . '.vue';

        if (!is_file($vueFile)) {
            echo "  [缺失目标] #{$id} ({$scene}): '{$rawPagePath}' -> '{$normalized}'（未找到 {$vueFile}）\n";
            $unmappedCount++;
            continue;
        }

        if ($normalized === $rawPagePath) {
            $alignedCount++;
            echo "  [已对齐] #{$id} ({$scene}): {$normalized}\n";
            continue;
        }

        $pendingUpdates++;
        echo "  [待更新] #{$id} ({$scene}): '{$rawPagePath}' => '{$normalized}'\n";
        if ($apply) {
            $db->execute(
                "UPDATE `{$table}` SET `page_path` = ?, `update_time` = ? WHERE `id` = ?",
                [$normalized, time(), $id]
            );
            $appliedUpdates++;
        }
    }
}

echo "\n汇总：已对齐 {$alignedCount} 条，待更新 {$pendingUpdates} 条，已更新 {$appliedUpdates} 条，未映射/异常 {$unmappedCount} 条。\n";

if ($unmappedCount > 0) {
    throw new RuntimeException("存在 {$unmappedCount} 条未映射或目标页面缺失的模板记录，请先核对后再执行。");
}

if (!$apply) {
    echo "[只读预览] 未修改数据库；确认无误后追加 --apply 执行更新。\n";
}
