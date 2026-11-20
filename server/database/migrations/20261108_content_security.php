<?php
declare(strict_types=1);

/**
 * 小程序发版前内容安全改造所需的表结构 / 配置补齐。
 *
 * 1. 新建 la_content_media_check_task：图片内容安全异步检测任务（trace_id 反查用）
 * 2. la_review 增加 security_remark：记录“为什么这条评价在待审队列里”
 * 3. mnp_setting.token：微信消息推送的签名 token（为空则回调入口直接拒绝）
 * 4. la_dev_crontab 增加 media_check_recover：30 分钟没回调的任务打上超时标记
 *
 * 用法：
 *   php database/migrations/20261108_content_security.php           # 只预览
 *   php database/migrations/20261108_content_security.php --apply   # 落库
 *
 * 幂等：表/列/配置/任务都存在时全部跳过。
 * 回滚：
 *   DROP TABLE la_content_media_check_task;
 *   ALTER TABLE la_review DROP COLUMN security_remark;
 *   DELETE FROM la_config WHERE type='mnp_setting' AND name='token';
 *   DELETE FROM la_dev_crontab WHERE command='media_check_recover';
 */

use think\facade\Config;
use think\facade\Db;

require dirname(__DIR__, 2) . '/vendor/autoload.php';
$app = new think\App(dirname(__DIR__, 2) . DIRECTORY_SEPARATOR);
$app->initialize();

set_exception_handler(static function (Throwable $e): void {
    fwrite(STDERR, $e->getMessage() . "\n");
    exit(1);
});

$db = Db::connect();
$prefix = (string)Config::get('database.connections.mysql.prefix', 'la_');
if (!preg_match('/^[a-zA-Z0-9_]*$/', $prefix)) {
    throw new RuntimeException('数据库前缀不合法');
}

$apply = in_array('--apply', $argv, true);
$plan = [];
$statements = [];

// 1. 图片检测任务表
$taskTable = $prefix . 'content_media_check_task';
$exists = $db->query("SHOW TABLES LIKE '{$taskTable}'");
if (empty($exists)) {
    $statements[] = "CREATE TABLE `{$taskTable}` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '提交用户',
  `scene` tinyint(3) unsigned NOT NULL DEFAULT '2' COMMENT '微信场景值',
  `biz_type` varchar(32) NOT NULL DEFAULT '' COMMENT '业务类型 review/review_reply/ticket/complaint',
  `biz_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '业务主键',
  `media_type` tinyint(3) unsigned NOT NULL DEFAULT '2' COMMENT '1音频 2图片',
  `media_urls` text COMMENT '送检图片绝对地址',
  `media_count` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '图片数量',
  `trace_id` varchar(64) NOT NULL DEFAULT '' COMMENT '微信 trace_id',
  `status` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '0待回调 1通过 2命中 3提交失败 4超时',
  `suggest` varchar(20) NOT NULL DEFAULT '' COMMENT '微信 suggest',
  `label` smallint(5) unsigned NOT NULL DEFAULT '0' COMMENT '微信 label',
  `callback_detail` text COMMENT '微信回调原文',
  `callback_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '结论时间',
  `create_time` int(10) unsigned NOT NULL DEFAULT '0',
  `update_time` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `idx_trace_id` (`trace_id`),
  KEY `idx_biz` (`biz_type`,`biz_id`),
  KEY `idx_status_create` (`status`,`create_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='图片内容安全异步检测任务'";
} else {
    $plan[] = "[skip] {$taskTable} 已存在";
}

// 2. la_review.security_remark
$reviewTable = $prefix . 'review';
$columns = array_column($db->query("SHOW COLUMNS FROM `{$reviewTable}`"), 'Field');
if (!in_array('security_remark', $columns, true)) {
    $statements[] = "ALTER TABLE `{$reviewTable}` "
        . "ADD COLUMN `security_remark` varchar(255) NOT NULL DEFAULT '' "
        . "COMMENT '内容安全备注（微信命中/高风险强制审核原因）' AFTER `reject_reason`";
} else {
    $plan[] = "[skip] {$reviewTable}.security_remark 已存在";
}

// 3. 消息推送 token
$token = (string)($db->name('config')->where(['type' => 'mnp_setting', 'name' => 'token'])->value('value') ?? '');
if ($token === '') {
    $token = bin2hex(random_bytes(16));
    $statements[] = "INSERT INTO `{$prefix}config` (`type`,`name`,`value`,`create_time`,`update_time`) "
        . "VALUES ('mnp_setting','token','{$token}'," . time() . ',' . time() . ')';
} else {
    $plan[] = "[skip] mnp_setting.token 已存在：" . $token;
}

// 4. 超时补偿定时任务
$crontabTable = $prefix . 'dev_crontab';
$cronExists = $db->name('dev_crontab')->where('command', 'media_check_recover')->count();
if ((int)$cronExists === 0) {
    $now = time();
    $statements[] = "INSERT INTO `{$crontabTable}` "
        . "(`name`,`type`,`system`,`remark`,`command`,`params`,`status`,`expression`,`error`,`last_time`,`time`,`max_time`,`create_time`,`update_time`) "
        . "VALUES ('图片检测回调超时标记',1,0,'30分钟未回调的图片检测任务标记超时，内容继续人工审核','media_check_recover','',1,'*/10 * * * *','',0,'0','0',{$now},{$now})";
} else {
    $plan[] = "[skip] 定时任务 media_check_recover 已存在";
}

foreach ($plan as $line) {
    echo $line . "\n";
}

if (empty($statements)) {
    echo "全部变更已存在，无需执行\n";
    exit(0);
}

echo "\n待执行：\n";
foreach ($statements as $sql) {
    echo "  " . preg_replace('/\s+/', ' ', mb_substr($sql, 0, 160)) . "\n";
}

if (!$apply) {
    echo "\n（预览模式，未落库。确认无误后加 --apply 执行）\n";
    exit(0);
}

foreach ($statements as $sql) {
    $db->execute($sql);
    echo "[done] " . preg_replace('/\s+/', ' ', mb_substr($sql, 0, 80)) . "\n";
}

// 配置写入后清缓存，避免 ConfigService 仍读到旧值
if (class_exists(\app\common\cache\ConfigCache::class)) {
    (new \app\common\cache\ConfigCache())->deleteValue('mnp_setting');
    (new \app\common\cache\ConfigCache())->deleteTag();
}

echo "\n完成。消息推送 Token：" . $token . "\n";
echo "请到「微信公众平台 → 开发管理 → 开发设置 → 消息推送」填写：\n";
echo "  URL   https://<你的域名>/api/wechat_media_check/callback\n";
echo "  Token " . $token . "\n";
echo "  数据格式 JSON，消息加解密方式建议先选「明文模式」\n";
