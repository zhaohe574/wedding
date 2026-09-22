<?php
declare(strict_types=1);

require dirname(__DIR__, 2) . '/vendor/autoload.php';

$app = new think\App(dirname(__DIR__, 2));
$app->initialize();

use app\common\model\order\Order;
use app\common\model\wechat\OaNotificationTemplate;
use app\common\service\OrderNotificationService;
use app\common\service\WechatNotificationService;

$check = static function (bool $condition, string $message): void {
    if (!$condition) {
        throw new RuntimeException('断言失败: ' . $message);
    }
};

echo "[TEST] 1. 验证 WechatNotificationService::formatValue 格式化逻辑...\n";
$reflection = new ReflectionClass(WechatNotificationService::class);
$formatMethod = $reflection->getMethod('formatValue');
$formatMethod->setAccessible(true);

// 1. time 类型：如果只有 Y-m-d，自动补全 09:00
$timeVal = $formatMethod->invoke(null, 'time8', '2026-10-20');
$check($timeVal === '2026-10-20 09:00', "time8 单日期补全时间失败，实际: {$timeVal}");

// 2. time 类型：如果已有完整时间，保持原样
$timeValFull = $formatMethod->invoke(null, 'time8', '2026-10-20 14:30');
$check($timeValFull === '2026-10-20 14:30', "time8 完整时间应保持原样，实际: {$timeValFull}");

// 3. thing 类型：换行符过滤且截断不超过20字
$thingVal = $formatMethod->invoke(null, 'thing12', "测试服务团队\n第二行超长内容测试测试测试测试测试测试");
$check(mb_strlen($thingVal, 'UTF-8') <= 20, "thing12 长度不应超过20，实际: " . mb_strlen($thingVal, 'UTF-8'));
$check(strpos($thingVal, "\n") === false, "thing12 不应包含换行符");

// 4. amount 类型：金额格式化
$amountVal = $formatMethod->invoke(null, 'amount13', '5888');
$check($amountVal === '5888.00', "amount13 金额格式化失败，实际: {$amountVal}");

$amountValWithSymbol = $formatMethod->invoke(null, 'amount13', '￥5888.5');
$check($amountValWithSymbol === '5888.50', "amount13 带符号格式化失败，实际: {$amountValWithSymbol}");

echo "[TEST] 2. 验证 OrderNotificationService::resolveOrderNotificationData 数据包装...\n";
$order = [
    'id' => 9999,
    'order_sn' => '202609209999',
    'package_name' => '法式唯美婚礼套系',
    'staff_name' => '李主策',
    'total_amount' => 6888.00,
    'pay_amount' => 6888.00,
    'service_date' => '2026-11-15',
    'service_address' => '深圳中洲万豪酒店3楼宴会厅',
    'contact_name' => '新人王女士',
    'contact_mobile' => '13800138000',
];

$resolved = OrderNotificationService::resolveOrderNotificationData($order);
$check(isset($resolved['staff_name']), '缺失 staff_name');
$check(isset($resolved['package_name']), '缺失 package_name');
$check($resolved['service_date'] === '2026-11-15 09:00', "执行时间应带 09:00，实际: {$resolved['service_date']}");
$check($resolved['hotel_name'] === '深圳中洲万豪酒店3楼宴会厅', "酒店名称不一致，实际: {$resolved['hotel_name']}");
$check($resolved['total_amount'] === '6888.00', "总金额不一致，实际: {$resolved['total_amount']}");
$check($resolved['thing12'] === $resolved['staff_name'], 'thing12 映射不匹配');
$check($resolved['thing2'] === $resolved['package_name'], 'thing2 映射不匹配');
$check($resolved['time8'] === '2026-11-15 09:00', 'time8 映射不匹配');
$check($resolved['thing10'] === '深圳中洲万豪酒店3楼宴会厅', 'thing10 映射不匹配');
$check($resolved['amount13'] === '6888.00', 'amount13 映射不匹配');

echo "[TEST] 3. 验证 OaNotificationTemplate 常量与映射...\n";
$check(defined(OaNotificationTemplate::class . '::SCENE_ORDER_UPDATE'), '缺失 SCENE_ORDER_UPDATE 常量');
$check(defined(OaNotificationTemplate::class . '::SCENE_ORDER_CREATE'), '缺失 SCENE_ORDER_CREATE 常量');

echo "[TEST] 4. 验证 WeChatOaService::getAllPrivateTemplates 接口方法定义...\n";
$oaRef = new ReflectionClass(\app\common\service\wechat\WeChatOaService::class);
$check($oaRef->hasMethod('getAllPrivateTemplates'), '缺失 getAllPrivateTemplates 方法');

echo "[TEST] 5. 验证 OaNotificationLogic::syncTemplates 绑定功能...\n";
$syncRes = \app\adminapi\logic\notification\OaNotificationLogic::syncTemplates([
    'bindings' => [
        [
            'scene' => 'order_update',
            'template_id' => 'H5dTD9xT90-dOXf1GUXIGuOs_ROKUCaqDASOZmR2zt8',
            'status' => 1,
            'remark' => '订单生成成功通知（婚庆服务类目模板48211）',
        ]
    ]
]);
$check(($syncRes['updated_count'] ?? 0) === 1, 'syncTemplates 手动绑定单场景失败');

echo "[PASS] 微信公众号订单模板测试全部通过！\n";

