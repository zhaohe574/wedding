<?php
declare(strict_types=1);

/**
 * 微信小程序订阅消息模板与发送日志数据表初始化迁移脚本。
 * 默认只读预览，添加 --apply 参数执行真实更新。
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

$templateTable = $prefix . 'wechat_mnp_notification_template';
$logTable = $prefix . 'wechat_mnp_notification_log';
$apply = in_array('--apply', $argv, true);

echo "=== 小程序订阅消息数据结构迁移检查 ===\n";
echo "模板配置表: {$templateTable}\n";
echo "发送日志表: {$logTable}\n";
echo "运行模式: " . ($apply ? "真实应用 (--apply)" : "只读预检 (Dry Run)") . "\n\n";

$sqlCreateTemplate = <<<SQL
CREATE TABLE IF NOT EXISTS `{$templateTable}` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `scene` varchar(50) NOT NULL DEFAULT '' COMMENT '业务场景代码',
  `title` varchar(100) NOT NULL DEFAULT '' COMMENT '模板名称',
  `template_id` varchar(64) NOT NULL DEFAULT '' COMMENT '小程序订阅消息模板ID(priTmplId)',
  `data_mapping` text COMMENT '模板字段到业务字段映射JSON',
  `page_path` varchar(255) NOT NULL DEFAULT '' COMMENT '小程序跳转页面路径',
  `status` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '状态：0关闭，1开启',
  `sort` int(11) NOT NULL DEFAULT '0' COMMENT '排序',
  `remark` varchar(500) NOT NULL DEFAULT '' COMMENT '备注说明',
  `create_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  `update_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_scene` (`scene`),
  KEY `idx_template_status` (`template_id`, `status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='微信小程序订阅通知模板配置';
SQL;

$sqlCreateLog = <<<SQL
CREATE TABLE IF NOT EXISTS `{$logTable}` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `user_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '接收用户ID',
  `openid` varchar(128) NOT NULL DEFAULT '' COMMENT '小程序OpenID',
  `scene` varchar(50) NOT NULL DEFAULT '' COMMENT '业务场景',
  `business_type` varchar(50) NOT NULL DEFAULT '' COMMENT '业务类型',
  `business_id` bigint(20) unsigned NOT NULL DEFAULT '0' COMMENT '业务ID',
  `template_id` varchar(64) NOT NULL DEFAULT '' COMMENT '模板ID',
  `payload` text COMMENT '发送报文JSON',
  `dedupe_key` varchar(128) NOT NULL DEFAULT '' COMMENT '幂等键',
  `send_status` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '状态：0待发送，1成功，2失败',
  `last_error_code` varchar(50) NOT NULL DEFAULT '' COMMENT '微信错误码',
  `error_msg` varchar(500) NOT NULL DEFAULT '' COMMENT '错误或提示信息',
  `request_id` varchar(64) NOT NULL DEFAULT '' COMMENT '微信请求ID/msgid',
  `send_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '发送时间',
  `create_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  `update_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_dedupe_key` (`dedupe_key`),
  KEY `idx_user_scene` (`user_id`, `scene`),
  KEY `idx_business` (`business_type`, `business_id`),
  KEY `idx_create_time` (`create_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='微信小程序订阅通知发送日志';
SQL;

$defaultPresets = [
    [
        'scene' => 'questionnaire_update',
        'title' => '新人问卷填写通知',
        'template_id' => '',
        'data_mapping' => [
            'thing1' => 'title',
            'thing2' => 'staff_name',
            'time3' => 'service_date',
            'thing4' => 'remark_text',
        ],
        'page_path' => 'packages/pages/couple_questionnaire/detail',
        'status' => 1,
        'sort' => 100,
        'remark' => '新人备婚需求与仪式问卷填写提醒，引导新人补充信息',
    ],
    [
        'scene' => 'activity_update',
        'title' => '活动报名成功通知',
        'template_id' => '',
        'data_mapping' => [
            'thing1' => 'title',
            'thing2' => 'ticket_name',
            'time3' => 'service_date',
            'thing4' => 'remark_text',
        ],
        'page_path' => 'packages/pages/activity_registration/detail',
        'status' => 1,
        'sort' => 90,
        'remark' => '活动报名结果与凭证确认通知',
    ],
    [
        'scene' => 'ticket_update',
        'title' => '工单处理进度通知',
        'template_id' => '',
        'data_mapping' => [
            'character_string1' => 'ticket_sn',
            'thing2' => 'title',
            'time3' => 'service_date',
            'thing4' => 'remark_text',
        ],
        'page_path' => 'packages/pages/aftersale/ticket_detail',
        'status' => 1,
        'sort' => 80,
        'remark' => '客户售后与工单进度流转通知',
    ],
    [
        'scene' => 'order_update',
        'title' => '订单状态更新通知',
        'template_id' => '',
        'data_mapping' => [
            'character_string1' => 'order_sn',
            'thing2' => 'package_name',
            'time3' => 'service_date',
            'amount4' => 'total_amount',
        ],
        'page_path' => 'packages/pages/order_detail/order_detail',
        'status' => 1,
        'sort' => 70,
        'remark' => '婚礼订单状态与安排提醒',
    ],
];

if (!$apply) {
    echo "[DRY RUN] 将会创建如下数据表（如果不存在）：\n";
    echo "  - {$templateTable}\n";
    echo "  - {$logTable}\n";
    echo "[DRY RUN] 将会初始化/检查 " . count($defaultPresets) . " 个常用小程序订阅消息场景配置：\n";
    foreach ($defaultPresets as $p) {
        echo "  - {$p['scene']} ({$p['title']})\n";
    }
    echo "\n若要执行真实变更，请使用命令：\n";
    echo "php server/database/migrations/20260922_mnp_subscribe_notification_init.php --apply\n";
    exit(0);
}

// 执行表创建
echo "正在创建表结构...\n";
$db->execute($sqlCreateTemplate);
$db->execute($sqlCreateLog);
echo "数据表创建或已存在成功！\n";

// 初始化预设
$now = time();
$inserted = 0;
$updated = 0;

foreach ($defaultPresets as $item) {
    $scene = $item['scene'];
    $exists = $db->table($templateTable)->where('scene', $scene)->find();
    $data = [
        'scene' => $scene,
        'title' => $item['title'],
        'data_mapping' => json_encode($item['data_mapping'], JSON_UNESCAPED_UNICODE),
        'page_path' => $item['page_path'],
        'status' => $item['status'],
        'sort' => $item['sort'],
        'remark' => $item['remark'],
        'update_time' => $now,
    ];

    if ($exists) {
        // 如果未填过 template_id，更新标题与默认字段映射；若已配置 template_id 则保留
        if (empty($exists['template_id'])) {
            $db->table($templateTable)->where('scene', $scene)->update($data);
            $updated++;
        }
    } else {
        $data['template_id'] = $item['template_id'];
        $data['create_time'] = $now;
        $db->table($templateTable)->insert($data);
        $inserted++;
    }
}

echo "预设场景写入完成：新增 {$inserted} 条，更新 {$updated} 条。\n";
echo "=== 小程序订阅消息数据结构迁移成功！ ===\n";
