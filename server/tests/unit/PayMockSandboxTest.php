<?php
declare(strict_types=1);

/**
 * 支付 Mock 沙箱门控与 WeChatPayService 沙箱分支单元测试。
 *
 * 零数据库 / 零证书依赖，直接执行：
 *   php server/tests/unit/PayMockSandboxTest.php
 */

$serverRoot = dirname(__DIR__, 2);
require $serverRoot . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';

$app = new \think\App($serverRoot);
$app->initialize();

use app\api\validate\PayValidate;
use app\common\enum\PayEnum;
use app\common\enum\user\UserTerminalEnum;
use app\common\service\pay\PayMockService;
use app\common\service\pay\WeChatPayService;
use think\facade\Env;

$assertions = 0;

$assertTrue = static function (bool $condition, string $label) use (&$assertions): void {
    $assertions++;
    if (!$condition) {
        throw new RuntimeException('[FAIL] ' . $label);
    }
};

$assertSame = static function ($actual, $expected, string $label) use (&$assertions): void {
    $assertions++;
    if ($actual !== $expected) {
        throw new RuntimeException(sprintf(
            "[FAIL] %s\n  expected: %s\n  actual:   %s",
            $label,
            var_export($expected, true),
            var_export($actual, true)
        ));
    }
};

$setMockEnabled = static function (bool $enabled) use ($app): void {
    $app->config->set(['pay_mock' => ['enabled' => $enabled]], 'project');
};
$setEnv = static function (string $env): void {
    Env::set('APP_ENV', $env);
};

try {
    // ----------------------------------------------------------------
    // 1. 三重门控（APP_DEBUG / pay_mock.enabled / 非生产环境）
    // ----------------------------------------------------------------
    $app->debug(false);
    $setMockEnabled(true);
    $setEnv('');
    $assertSame(false, PayMockService::isEnabled(), 'APP_DEBUG=false 时门控必须关闭');

    $app->debug(true);
    $setMockEnabled(false);
    $assertSame(false, PayMockService::isEnabled(), 'pay_mock.enabled=false 时门控必须关闭');

    $setMockEnabled(true);
    $setEnv('production');
    $assertSame(false, PayMockService::isEnabled(), 'APP_ENV=production 时门控必须关闭');

    $setEnv('PROD');
    $assertSame(false, PayMockService::isEnabled(), 'APP_ENV=PROD 时门控必须关闭');

    $setEnv('');
    $assertSame(true, PayMockService::isEnabled(), 'APP_DEBUG=true + enabled=true + 非生产时门控开启');

    // ----------------------------------------------------------------
    // 2. isMockTransactionId / buildFallbackOpenid / isMockCallback 边界
    // ----------------------------------------------------------------
    $assertSame(true, PayMockService::isMockTransactionId('MOCK_TX_ABC123'), '沙箱交易号可识别');
    $assertSame(true, PayMockService::isMockTransactionId('  MOCK_TX_ABC123  '), '沙箱交易号 trim 后可识别');
    $assertSame(false, PayMockService::isMockTransactionId('WX_TX_ABC123'), '真实交易号不识别');
    $assertSame(false, PayMockService::isMockTransactionId('mock_tx_abc123'), '前缀大小写敏感');
    $assertSame(false, PayMockService::isMockTransactionId(''), '空交易号不识别');

    $assertSame('mock_openid_u17', PayMockService::buildFallbackOpenid(17), '兜底 openid 含用户 id');
    $assertSame('mock_openid_u0', PayMockService::buildFallbackOpenid(0), '兜底 openid 允许 0');

    $mockCallback = [
        'mock_sandbox' => true,
        'source_verified' => true,
        'source' => 'wechat_pay_v3',
    ];
    $assertSame(true, PayMockService::isMockCallback($mockCallback), '完整沙箱回调可通过');
    $assertSame(false, PayMockService::isMockCallback([]), '空数组不是沙箱回调');
    $assertSame(false, PayMockService::isMockCallback([
        'source_verified' => true,
        'source' => 'wechat_pay_v3',
    ]), '缺少 mock_sandbox 不是沙箱回调');
    $assertSame(false, PayMockService::isMockCallback([
        'mock_sandbox' => true,
        'source' => 'wechat_pay_v3',
    ]), '缺少 source_verified 不是沙箱回调');
    $assertSame(false, PayMockService::isMockCallback([
        'mock_sandbox' => true,
        'source_verified' => true,
        'source' => 'wechat_pay_v2',
    ]), '来源不是 wechat_pay_v3 不是沙箱回调');

    $setMockEnabled(false);
    $assertSame(false, PayMockService::isMockCallback($mockCallback), '门控关闭时沙箱回调判定为 false');
    $setMockEnabled(true);

    // ----------------------------------------------------------------
    // 3. 开启沙箱时 pay() 无需证书即可返回沙箱预支付结构
    // ----------------------------------------------------------------
    $payService = new WeChatPayService(UserTerminalEnum::WECHAT_MMP);
    $prepay = $payService->pay('order', [
        'id' => 88,
        'pay_sn' => 'PAY_MOCK_0001',
        'order_amount' => 199.5,
        'pay_subject' => '测试婚礼服务',
    ]);
    $assertTrue(is_array($prepay), '沙箱 pay() 返回数组（无需商户证书）');
    $assertSame(true, $prepay['config']['mock'] ?? null, 'config.mock 为 true');
    $assertSame('order', $prepay['config']['from'] ?? null, 'config.from 透传业务来源');
    $assertSame('PAY_MOCK_0001', $prepay['config']['payment_sn'] ?? null, 'config.payment_sn 为支付流水号');
    $assertSame(88, $prepay['config']['order_id'] ?? null, 'config.order_id 为业务主键');
    $assertSame(199.5, $prepay['config']['amount'] ?? null, 'config.amount 为支付金额');
    $assertSame('测试婚礼服务', $prepay['config']['subject'] ?? null, 'config.subject 为支付主题');
    $assertSame(PayEnum::WECHAT_PAY, $prepay['pay_way'] ?? null, 'pay_way 为微信支付');
    $assertSame(false, $payService->pay('order', ['id' => 1, 'order_amount' => 1]), '缺少支付流水号时返回 false');

    // ----------------------------------------------------------------
    // 4. 沙箱退款结果应通过真实 validateRefundResult 严格校验
    // ----------------------------------------------------------------
    $refund = $payService->refund([
        'transaction_id' => 'MOCK_TX_REFUND0001',
        'out_trade_no' => 'PAY_MOCK_0001',
        'refund_sn' => 'RF_MOCK_0001',
        'refund_amount' => 50,
        'total_amount' => 199.5,
    ]);
    $assertTrue(is_array($refund), '沙箱 refund() 返回数组');
    $assertSame('SUCCESS', $refund['status'] ?? null, '沙箱退款状态为 SUCCESS');
    $assertTrue(str_starts_with((string)($refund['refund_id'] ?? ''), PayMockService::MOCK_REFUND_PREFIX), '退款单号带沙箱前缀');
    $assertSame('', WeChatPayService::validateRefundResult(
        $refund,
        'PAY_MOCK_0001',
        'MOCK_TX_REFUND0001',
        199.5,
        50
    ), '沙箱退款结果通过严格校验');

    // ----------------------------------------------------------------
    // 5. PayValidate：mockConfirm 接受三种来源，默认 prepay 仍拒绝活动报名
    // ----------------------------------------------------------------
    foreach (['order', 'staff_settlement_repay', 'activity_registration'] as $from) {
        $assertSame(true, (new PayValidate())->scene('mockConfirm')->check([
            'from' => $from,
            'payment_sn' => 'PAY_MOCK_0001',
        ]), 'mockConfirm 场景接受来源：' . $from);
    }
    $assertSame(false, (new PayValidate())->scene('mockConfirm')->check([
        'from' => 'unknown',
        'payment_sn' => 'PAY_MOCK_0001',
    ]), 'mockConfirm 场景拒绝未知来源');
    $assertSame(false, (new PayValidate())->scene('mockConfirm')->check([
        'from' => 'order',
    ]), 'mockConfirm 场景要求支付流水号');

    $assertSame(false, (new PayValidate())->check([
        'from' => 'activity_registration',
        'order_id' => 1,
        'pay_way' => 2,
    ]), '默认 prepay 场景仍拒绝活动报名来源');
    $assertSame(true, (new PayValidate())->check([
        'from' => 'order',
        'order_id' => 1,
        'pay_way' => 2,
    ]), '默认 prepay 场景仍接受订单来源');
} catch (Throwable $e) {
    fwrite(STDERR, $e->getMessage() . PHP_EOL);
    exit(1);
}

echo 'PayMockSandboxTest: ' . $assertions . ' assertions passed' . PHP_EOL;
exit(0);
