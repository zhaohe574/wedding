<?php
// +----------------------------------------------------------------------
// | P0 预约/支付/档期核心可靠性回归测试
// +----------------------------------------------------------------------
// | 说明：当前项目 composer 未安装 phpunit 包，vendor/bin/phpunit 代理不可用。
// | 本脚本采用轻量断言 + 反射/源码扫描，覆盖支付回调业务校验与并发保护关键逻辑，
// | 可直接执行：php tests/regression/PaymentScheduleReliabilityTest.php
// +----------------------------------------------------------------------

declare(strict_types=1);

use app\common\enum\user\UserTerminalEnum;
use app\common\model\order\Payment;

require __DIR__ . '/../../vendor/autoload.php';

final class PaymentScheduleReliabilityTest
{
    private int $assertions = 0;

    public function run(): void
    {
        $this->testWechatAmountValidationRejectsMismatch();
        $this->testWechatPayerValidationRejectsWrongOpenid();
        $this->testPaidReplayRejectsDifferentTransactionId();
        $this->testWechatCallbackRejectsUnverifiedSource();
        $this->testDuplicateTransactionGuardExists();
        $this->testOrderConsistencyGuardsExist();
        $this->testScheduleConcurrencyGuardsExist();
        $this->testCancelReleaseIsOrderScoped();
        $this->testNotifySourceVerificationEntrypointsExist();
        $this->testWechatNotifyAcknowledgesLateCallbackAfterCompensation();

        echo 'OK - ' . $this->assertions . " assertions\n";
    }

    private function testWechatAmountValidationRejectsMismatch(): void
    {
        $payment = $this->buildPayment([
            'id' => 1001,
            'payment_sn' => 'PAY_TEST_1001',
            'user_id' => 2001,
            'pay_way' => Payment::WAY_WECHAT,
            'pay_amount' => 88.66,
        ]);

        $error = $this->invokeValidatePaidCallback($payment, [
            'trade_state' => 'SUCCESS',
            'out_trade_no' => 'PAY_TEST_1001',
            'attach' => 'order',
            'amount' => ['total' => 1, 'currency' => 'CNY'],
            'payer' => ['openid' => 'openid-2001'],
            'terminal' => UserTerminalEnum::WECHAT_MMP,
            'source' => 'wechat_pay_v3',
            'source_verified' => true,
        ], 'WX_TXN_1001', true);

        $this->assertContains('金额不一致', $error, '微信支付回调金额不一致必须被拒绝');
    }

    private function testWechatPayerValidationRejectsWrongOpenid(): void
    {
        $payment = $this->buildPayment([
            'id' => 1002,
            'payment_sn' => 'PAY_TEST_1002',
            'user_id' => 2002,
            'pay_way' => Payment::WAY_WECHAT,
            'pay_amount' => 1.00,
        ]);

        $error = $this->invokeValidateWechatPayer($payment, [
            'payer' => [],
            'terminal' => UserTerminalEnum::WECHAT_MMP,
        ]);

        $this->assertSame('微信支付回调缺少支付者openid', $error, '微信 JSAPI 回调必须校验 payer/openid，不可无条件放行');
    }

    private function testPaidReplayRejectsDifferentTransactionId(): void
    {
        $payment = $this->buildPayment([
            'id' => 1003,
            'payment_sn' => 'PAY_TEST_1003',
            'user_id' => 2003,
            'pay_way' => Payment::WAY_WECHAT,
            'pay_amount' => 1.00,
            'transaction_id' => 'WX_TXN_ORIGINAL',
        ]);

        $error = $this->invokeValidatePaidCallback($payment, [
            'trade_state' => 'SUCCESS',
            'out_trade_no' => 'PAY_TEST_1003',
            'attach' => 'order',
            'amount' => ['total' => 100, 'currency' => 'CNY'],
            'payer' => ['openid' => 'openid-2003'],
            'terminal' => UserTerminalEnum::IOS,
            'source' => 'wechat_pay_v3',
            'source_verified' => true,
        ], 'WX_TXN_TAMPERED', true);

        $this->assertSame('重复支付回调交易号不一致', $error, '重复回调必须校验 transaction_id 与原流水一致');
    }

    private function testWechatCallbackRejectsUnverifiedSource(): void
    {
        $payment = $this->buildPayment([
            'id' => 1004,
            'payment_sn' => 'PAY_TEST_1004',
            'user_id' => 2004,
            'pay_way' => Payment::WAY_WECHAT,
            'pay_amount' => 1.00,
        ]);

        $error = $this->invokeValidatePaidCallback($payment, [
            'trade_state' => 'SUCCESS',
            'out_trade_no' => 'PAY_TEST_1004',
            'attach' => 'order',
            'amount' => ['total' => 100, 'currency' => 'CNY'],
            'payer' => ['openid' => 'openid-2004'],
            'terminal' => UserTerminalEnum::IOS,
        ], 'WX_TXN_1004', true);

        $this->assertSame('微信支付回调来源未验证', $error, '微信订单回调必须来自已验签通知入口');
    }

    private function testDuplicateTransactionGuardExists(): void
    {
        $paymentSource = $this->readSource('app/common/model/order/Payment.php');
        $this->assertContains('validateUniqueTransactionId', $paymentSource, '必须存在 transaction_id 唯一校验');
        $this->assertContains('self::STATUS_FAILED', $paymentSource, '取消/超时后标记失败的流水仍需能登记迟到真实回调并触发补偿');
        $this->assertContains('where(\'transaction_id\', $transactionId)', $paymentSource, '必须按 transaction_id 查询冲突流水');
        $this->assertContains('where(\'id\', \'<>\', (int)$payment->id)', $paymentSource, 'transaction_id 冲突查询必须排除当前流水');
        $this->assertContains('UNIQUE KEY `uk_transaction_id` (`transaction_id`)', $this->readSource('public/install/db/like.sql'), '安装库必须对非空 transaction_id 建唯一索引');
        $this->assertContains('ADD UNIQUE KEY `uk_transaction_id` (`transaction_id`)', $this->readSource('sql/20260523_payment_schedule_reliability.sql'), '升级脚本必须补齐 transaction_id 唯一索引');
    }

    private function testOrderConsistencyGuardsExist(): void
    {
        $paymentSource = $this->readSource('app/common/model/order/Payment.php');
        $this->assertContains('支付流水订单号与订单不一致', $paymentSource, '支付回调必须校验支付流水绑定订单与订单表一致');
        $this->assertContains('trim((string)$payment->order_sn) !== trim((string)$order->order_sn)', $paymentSource, '支付回调订单号一致性校验必须比较 payment.order_sn 与 order.order_sn');
    }

    private function testScheduleConcurrencyGuardsExist(): void
    {
        $scheduleSource = $this->readSource('app/common/model/schedule/Schedule.php');
        $this->assertContains("UNIQUE KEY `uk_staff_date_slot`", $this->readSource('public/install/db/like.sql'), '档期表必须有人员+日期+时段唯一约束');
        $this->assertContains('where(\'version\', (int)$schedule->version)', $scheduleSource, '档期更新必须带版本条件');
        $this->assertContains('where(\'status\', self::STATUS_AVAILABLE)', $scheduleSource, '临时锁定必须只从可预约状态抢占');
        $this->assertContains('where(\'lock_user_id\', $userId)', $scheduleSource, '确认预约必须要求当前用户持有锁或档期可预约');
        $this->assertContains('where(\'order_id\', $orderId)', $scheduleSource, '释放预约必须限定订单归属');
    }

    private function testCancelReleaseIsOrderScoped(): void
    {
        $orderSource = $this->readSource('app/common/model/order/Order.php');
        $this->assertContains('releaseBookingForOrder((int)$item->schedule_id, $orderId)', $orderSource, '取消订单释放档期必须按订单归属保护');
        $this->assertContains('releaseBookingForOrder($scheduleId, $orderId)', $orderSource, '首付锁档失败回滚必须按订单归属保护');
    }

    private function testNotifySourceVerificationEntrypointsExist(): void
    {
        $wechatSource = $this->readSource('app/common/service/pay/WeChatPayService.php');
        $aliSource = $this->readSource('app/common/service/pay/AliPayService.php');
        $this->assertContains('$server = $this->app->getServer();', $wechatSource, '微信支付通知必须经过 EasyWeChat server 验签入口');
        $this->assertContains('source_verified', $wechatSource, '微信支付业务上下文必须标记已通过通知来源校验');
        $this->assertNotContains('pay_status === OrderPayment::STATUS_PAID', $wechatSource, '微信已支付重复通知也必须进入 Payment 幂等校验，不可在服务层直接短路');
        $this->assertContains('verifyNotify($data)', $aliSource, '支付宝异步通知必须经过 SDK 验签');
    }

    private function testWechatNotifyAcknowledgesLateCallbackAfterCompensation(): void
    {
        $source = $this->readSource('app/common/service/pay/WeChatPayService.php');
        $this->assertContains('late_callback_exception', $source, '微信通知必须识别取消/超时后的异常支付回调');
        $this->assertContains('已按异常支付登记补偿', $source, '异常支付回调必须登记补偿上下文');
        $this->assertContains('return true;', $source, '异常支付登记补偿后应答成功，避免微信无限重试');
    }

    private function buildPayment(array $data): Payment
    {
        $payment = new Payment();
        foreach ($data as $key => $value) {
            $payment->{$key} = $value;
        }
        return $payment;
    }

    private function invokeValidatePaidCallback(Payment $payment, array $callbackData, string $transactionId, bool $isReplay = false): string
    {
        $method = new ReflectionMethod(Payment::class, 'validatePaidCallback');
        $method->setAccessible(true);
        return (string)$method->invoke(null, $payment, $callbackData, $transactionId, $isReplay);
    }

    private function invokeValidateWechatPayer(Payment $payment, array $callbackData): string
    {
        $method = new ReflectionMethod(Payment::class, 'validateWechatPayer');
        $method->setAccessible(true);
        return (string)$method->invoke(null, $payment, $callbackData, false);
    }

    private function readSource(string $relativePath): string
    {
        $path = dirname(__DIR__, 2) . '/' . $relativePath;
        $contents = file_get_contents($path);
        if ($contents === false) {
            throw new RuntimeException('无法读取文件：' . $path);
        }
        return $contents;
    }

    private function assertContains(string $needle, string $haystack, string $message): void
    {
        $this->assertions++;
        if (!str_contains($haystack, $needle)) {
            throw new RuntimeException($message . '，缺少片段：' . $needle);
        }
    }

    private function assertNotContains(string $needle, string $haystack, string $message): void
    {
        $this->assertions++;
        if (str_contains($haystack, $needle)) {
            throw new RuntimeException($message . '，不应包含片段：' . $needle);
        }
    }

    private function assertSame($expected, $actual, string $message): void
    {
        $this->assertions++;
        if ($expected !== $actual) {
            throw new RuntimeException($message . '，期望：' . var_export($expected, true) . '，实际：' . var_export($actual, true));
        }
    }

    private function assertTrue(bool $condition, string $message): void
    {
        $this->assertions++;
        if (!$condition) {
            throw new RuntimeException($message);
        }
    }
}

try {
    (new PaymentScheduleReliabilityTest())->run();
} catch (Throwable $e) {
    fwrite(STDERR, 'FAIL - ' . $e->getMessage() . "\n");
    exit(1);
}
