<?php
declare(strict_types=1);

/**
 * 补齐 la_service_category 的预约关联角色默认图。
 *
 * 背景：预约流程里「是否需要婚礼管家 / 是否需要婚礼督导」两页原来用服务人员头像做头图，
 *   但该步骤只让用户选「需不需要」，人员由平台统一安排，拿某个人的头像会让用户误以为
 *   已经指定了人。改为在后台「服务分类 → 预约关联」里配置一张分类级默认图。
 *
 * 用法：
 *   php database/migrations/20261110_service_category_role_image.php           # 只预览
 *   php database/migrations/20261110_service_category_role_image.php --apply   # 落库
 *
 * 幂等：两列都存在时直接跳过。回滚：
 *   ALTER TABLE la_service_category DROP COLUMN booking_butler_image, DROP COLUMN booking_director_image;
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
$table = $prefix . 'service_category';

$existing = array_column($db->query("SHOW COLUMNS FROM `{$table}`"), 'Field');
$missing = array_values(array_diff(['booking_butler_image', 'booking_director_image'], $existing));

if (!$missing) {
    echo "[skip] {$table} 的预约关联默认图字段已存在，无需变更\n";
    exit(0);
}

$sqls = [];
if (in_array('booking_butler_image', $missing, true)) {
    $sqls[] = "ALTER TABLE `{$table}` ADD COLUMN `booking_butler_image` varchar(255) "
        . "COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '婚礼管家步骤默认图' "
        . "AFTER `booking_butler_category_id`";
}
if (in_array('booking_director_image', $missing, true)) {
    $sqls[] = "ALTER TABLE `{$table}` ADD COLUMN `booking_director_image` varchar(255) "
        . "COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '婚礼督导步骤默认图' "
        . "AFTER `booking_director_category_id`";
}

foreach ($sqls as $sql) {
    echo "待执行：\n  {$sql}\n";
}

if (!$apply) {
    echo "\n（预览模式，未落库。确认无误后加 --apply 执行）\n";
    exit(0);
}

foreach ($sqls as $sql) {
    $db->execute($sql);
}

$after = array_column($db->query("SHOW COLUMNS FROM `{$table}`"), 'Field');
$still = array_values(array_diff(['booking_butler_image', 'booking_director_image'], $after));
if ($still) {
    throw new RuntimeException('变更后仍缺少字段：' . implode(', ', $still));
}

echo "\n[ok] {$table} 预约关联默认图字段已补齐\n";
