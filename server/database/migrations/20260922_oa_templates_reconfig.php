<?php
declare(strict_types=1);

/**
 * 微信公众号服务号获批模板重新编排与精准绑定迁移脚本。
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

$table = $prefix . 'wechat_oa_notification_template';
$apply = in_array('--apply', $argv, true);

$configs = [
    [
        'scene' => 'order_update',
        'audience' => 'user',
        'template_id' => 'H5dTD9xT90-dOXf1GUXIGuOs_ROKUCaqDASOZmR2zt8',
        'data_mapping' => [
            'thing12' => 'staff_name',
            'thing2' => 'package_name',
            'time8' => 'service_date',
            'thing10' => 'hotel_name',
            'amount13' => 'total_amount',
        ],
        'page_path' => 'packages/pages/order_detail/order_detail',
        'remark' => '订单生成成功通知（婚庆服务类目模板48211）',
        'status' => 1,
        'sort' => 100,
    ],
    [
        'scene' => 'staff_order',
        'audience' => 'staff',
        'template_id' => 'zRgTnFCTLiXN54GNSYTqaU9b_aTywGmL34ROId_qN_4',
        'data_mapping' => [
            'character_string1' => 'order_sn',
            'time2' => 'order_time',
        ],
        'page_path' => 'packages/pages/staff_order_detail/staff_order_detail',
        'remark' => '接单成功通知（服务人员新订单）',
        'status' => 1,
        'sort' => 90,
    ],
    [
        'scene' => 'staff_refund',
        'audience' => 'staff',
        'template_id' => 'ZvVDAtr4cJNdP-SFKotwCQVzX5HPtUns5vTFO6C05nI',
        'data_mapping' => [
            'time1' => 'service_date',
            'thing2' => 'hotel_name',
            'thing4' => 'staff_name',
        ],
        'page_path' => 'packages/pages/staff_order_detail/staff_order_detail',
        'remark' => '拒单通知（服务人员退款/拒单提醒）',
        'status' => 1,
        'sort' => 85,
    ],
    [
        'scene' => 'ticket_update',
        'audience' => 'user',
        'template_id' => '2LfkTjUakVHJhvVPvhMvFcTytY2K-6lN3XkjEI_6Fo4',
        'data_mapping' => [
            'character_string2' => 'ticket_sn',
            'thing3' => 'package_name',
            'time4' => 'service_date',
            'thing5' => 'hotel_name',
        ],
        'page_path' => 'packages/pages/aftersale/ticket_detail',
        'remark' => '工单处理提醒（客户售后进度）',
        'status' => 1,
        'sort' => 80,
    ],
    [
        'scene' => 'staff_aftersale',
        'audience' => 'staff',
        'template_id' => '2LfkTjUakVHJhvVPvhMvFcTytY2K-6lN3XkjEI_6Fo4',
        'data_mapping' => [
            'character_string2' => 'ticket_sn',
            'thing3' => 'package_name',
            'time4' => 'service_date',
            'thing5' => 'hotel_name',
        ],
        'page_path' => 'packages/pages/staff_order_detail/staff_order_detail',
        'remark' => '工单处理提醒（服务人员工单协同）',
        'status' => 1,
        'sort' => 75,
    ],
    [
        'scene' => 'staff_schedule',
        'audience' => 'staff',
        'template_id' => 'GkWtioQrVrB6JMxpoW37lniOKa2iwIpWwNFrfgJ1YLA',
        'data_mapping' => [
            'thing1' => 'user_name',
            'thing2' => 'staff_name',
            'time3' => 'service_date',
            'amount4' => 'total_amount',
            'thing5' => 'hotel_name',
        ],
        'page_path' => 'packages/pages/staff_order_detail/staff_order_detail',
        'remark' => '团队成员预约成功通知（服务人员档期锁定）',
        'status' => 1,
        'sort' => 70,
    ],
    [
        'scene' => 'waitlist_release',
        'audience' => 'user',
        'template_id' => 'GkWtioQrVrB6JMxpoW37lniOKa2iwIpWwNFrfgJ1YLA',
        'data_mapping' => [
            'thing1' => 'user_name',
            'thing2' => 'staff_name',
            'time3' => 'service_date',
            'amount4' => 'total_amount',
            'thing5' => 'hotel_name',
        ],
        'page_path' => 'packages/pages/waitlist/waitlist',
        'remark' => '团队成员预约成功通知（候补释放预约）',
        'status' => 1,
        'sort' => 65,
    ],
    [
        'scene' => 'settlement_update',
        'audience' => 'staff',
        'template_id' => 'J9T67NXYPSph493Kj15GThADKf8z1IET-7vD8Hz6VhE',
        'data_mapping' => [
            'thing1' => 'title',
            'amount2' => 'total_amount',
            'time3' => 'pay_time',
            'thing4' => 'store_name',
            'time10' => 'service_date',
        ],
        'page_path' => 'packages/pages/staff_settlement/staff_settlement',
        'remark' => '收款成功通知（财务打款/结算到账通知）',
        'status' => 1,
        'sort' => 60,
    ],
    [
        'scene' => 'waitlist_expired',
        'audience' => 'user',
        'template_id' => 'R0_nz9FV0E7IMnXeAe9A1bmTNLld6fjlCGVIi3kYZOU',
        'data_mapping' => [
            'character_string6' => 'order_sn',
            'thing1' => 'user_name',
            'time4' => 'service_date',
            'thing5' => 'hotel_name',
            'time3' => 'cancel_time',
        ],
        'page_path' => 'packages/pages/waitlist/waitlist',
        'remark' => '订单取消通知（候补/订单超时取消）',
        'status' => 1,
        'sort' => 55,
    ],
    [
        'scene' => 'staff_pause',
        'audience' => 'staff',
        'template_id' => 'tNllylZSW07VXCXg1KjKr-XphzhxRlagUVJxjrrwdSg',
        'data_mapping' => [
            'character_string1' => 'order_sn',
            'time2' => 'apply_time',
            'const3' => 'status_text',
        ],
        'page_path' => 'packages/pages/staff_order_detail/staff_order_detail',
        'remark' => '订单申诉结果通知（停单/申诉结果，常量：通过/不通过）',
        'status' => 1,
        'sort' => 50,
    ],
    [
        'scene' => 'change_result',
        'audience' => 'user',
        'template_id' => 'tNllylZSW07VXCXg1KjKr-XphzhxRlagUVJxjrrwdSg',
        'data_mapping' => [
            'character_string1' => 'order_sn',
            'time2' => 'apply_time',
            'const3' => 'status_text',
        ],
        'page_path' => 'packages/pages/order_detail/order_detail',
        'remark' => '订单申诉结果通知（改期申诉结果，常量：通过/不通过）',
        'status' => 1,
        'sort' => 45,
    ],
    [
        'scene' => 'staff_change',
        'audience' => 'staff',
        'template_id' => 'lmRMUgGCOMyjzRppYO8OxxpbCIFHraqr7kVLweQt560',
        'data_mapping' => [
            'thing1' => 'user_name',
            'character_string2' => 'order_sn',
            'const3' => 'category_name',
            'time4' => 'old_service_date',
            'time5' => 'new_service_date',
        ],
        'page_path' => 'packages/pages/staff_order_detail/staff_order_detail',
        'remark' => '顾客拍摄已改期提醒（服务人员改期提醒，常量：婚纱照/婚礼跟拍/婚礼策划/主持/化妆）',
        'status' => 1,
        'sort' => 40,
    ],
    [
        'scene' => 'schedule_remind',
        'audience' => 'user',
        'template_id' => 't9EOrYDjmTfHBYzlIIw2Tg7YCLzaVUReF7agfcUeffo',
        'data_mapping' => [
            'time2' => 'service_date',
            'thing3' => 'staff_name',
        ],
        'page_path' => 'packages/pages/order_detail/order_detail',
        'remark' => '服务时间预约成功通知（档期/履约提醒）',
        'status' => 1,
        'sort' => 35,
    ],
    [
        'scene' => 'staff_internal',
        'audience' => 'staff',
        'template_id' => 'CiCHAwhKVNbQnyiWYevaj807sxyye5fJDMaUrZ2twfo',
        'data_mapping' => [
            'content' => 'content',
        ],
        'page_path' => 'packages/pages/notification/index',
        'remark' => '订阅模板消息（服务号订阅通知，非模板消息，停用避免40037）',
        'status' => 0,
        'sort' => 30,
    ],
    [
        'scene' => 'questionnaire_update',
        'audience' => 'user',
        'template_id' => '',
        'data_mapping' => [],
        'page_path' => 'packages/pages/couple_questionnaire/detail',
        'remark' => '微信驳回备婚需求通知模板，建议通过小程序订阅消息触达',
        'status' => 0,
        'sort' => 25,
    ],
    [
        'scene' => 'activity_update',
        'audience' => 'user',
        'template_id' => '',
        'data_mapping' => [],
        'page_path' => 'packages/pages/dynamic_detail/dynamic_detail',
        'remark' => '微信驳回活动报名通知模板，建议通过小程序订阅消息触达',
        'status' => 0,
        'sort' => 20,
    ],
];

echo "=== 微信服务号获批模板重新编排与迁移 ===\n";
$now = time();
$count = 0;

foreach ($configs as $cfg) {
    $mappingJson = json_encode($cfg['data_mapping'], JSON_UNESCAPED_UNICODE);
    $existing = $db->table($table)
        ->where('scene', $cfg['scene'])
        ->where('audience', $cfg['audience'])
        ->find();

    if ($existing) {
        $sql = "UPDATE `{$table}` SET `template_id` = '{$cfg['template_id']}', `data_mapping` = '{$mappingJson}', `page_path` = '{$cfg['page_path']}', `status` = {$cfg['status']}, `sort` = {$cfg['sort']}, `remark` = '{$cfg['remark']}', `update_time` = {$now} WHERE `id` = {$existing['id']}";
        echo "[UPDATE] Scene: {$cfg['scene']}({$cfg['audience']}) -> {$cfg['template_id']}\n";
        if ($apply) {
            $db->execute($sql);
            $count++;
        }
    } else {
        $sql = "INSERT INTO `{$table}` (`scene`, `audience`, `template_id`, `data_mapping`, `page_path`, `status`, `sort`, `remark`, `create_time`, `update_time`) VALUES ('{$cfg['scene']}', '{$cfg['audience']}', '{$cfg['template_id']}', '{$mappingJson}', '{$cfg['page_path']}', {$cfg['status']}, {$cfg['sort']}, '{$cfg['remark']}', {$now}, {$now})";
        echo "[INSERT] Scene: {$cfg['scene']}({$cfg['audience']}) -> {$cfg['template_id']}\n";
        if ($apply) {
            $db->execute($sql);
            $count++;
        }
    }
}

// 清理旧的无用场景（如历史占位的 order_confirm, refund_result user 端旧记录）
$cleanupScenes = [
    ['scene' => 'order_confirm', 'audience' => 'user'],
    ['scene' => 'refund_result', 'audience' => 'user'],
];
foreach ($cleanupScenes as $c) {
    $found = $db->table($table)->where('scene', $c['scene'])->where('audience', $c['audience'])->find();
    if ($found) {
        echo "[CLEANUP] Scene: {$c['scene']}({$c['audience']})\n";
        if ($apply) {
            $db->table($table)->where('id', $found['id'])->delete();
        }
    }
}

echo $apply ? "迁移执行成功，共更新/插入 {$count} 条配置记录。\n" : "预检完成。添加 --apply 执行迁移。\n";
