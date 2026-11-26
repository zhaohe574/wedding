<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 支付 Mock 沙箱服务（仅测试环境）
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\common\service\pay;

use app\common\enum\PayEnum;
use app\common\enum\user\UserTerminalEnum;
use app\common\logic\PayNotifyLogic;
use app\common\model\dynamic\ActivityPayment;
use app\common\model\dynamic\ActivityRefund;
use app\common\model\financial\StaffSettlementRepay;
use app\common\model\order\Payment as OrderPayment;
use app\common\model\order\RefundItem;
use app\common\model\pay\PayConfig;
use app\common\model\staff\Staff;
use app\common\model\user\UserAuth;
use app\common\service\ActivityRegistrationService;
use app\common\service\MoneyService;
use app\common\service\StaffSettlementRepayService;
use app\common\service\wechat\WeChatConfigService;

/**
 * 支付 Mock 沙箱：仅非生产 + APP_DEBUG=true + PAY.MOCK_ENABLED=true 三重门控同时满足才生效。
 * 关闭开关后所有入口退回真实微信支付 V3 链路，行为与改造前一致。
 */
class PayMockService
{
    /** 沙箱支付交易号前缀（用于识别可走沙箱退款的流水） */
    public const MOCK_TX_PREFIX = 'MOCK_TX_';

    /** 沙箱退款单号前缀 */
    public const MOCK_REFUND_PREFIX = 'MOCK_RF_';

    /**
     * 门控：APP_DEBUG + 配置开关 + 非生产环境，缺一不可。
     * 生产环境即使误开配置，只要 APP_DEBUG=false 或 APP_ENV=production 就会被强制拦截。
     */
    public static function isEnabled(): bool
    {
        if (app()->isDebug() !== true) {
            return false;
        }
        if ((bool)config('project.pay_mock.enabled', false) !== true) {
            return false;
        }
        $env = strtolower(trim((string)env('app.env', '')));

        return !in_array($env, ['prod', 'production'], true);
    }

    /**
     * 是否为沙箱交易号
     */
    public static function isMockTransactionId(string $transactionId): bool
    {
        return str_starts_with(trim($transactionId), self::MOCK_TX_PREFIX);
    }

    /**
     * 测试账号完全没有 UserAuth 记录时的兜底 openid
     */
    public static function buildFallbackOpenid(int $userId): string
    {
        return 'mock_openid_u' . $userId;
    }

    /**
     * 是否为沙箱回调载荷（需同时满足门控、沙箱标记、来源校验）
     */
    public static function isMockCallback(array $data): bool
    {
        return self::isEnabled()
            && !empty($data['mock_sandbox'])
            && !empty($data['source_verified'])
            && ($data['source'] ?? '') === 'wechat_pay_v3';
    }

    /**
     * 构造沙箱预支付配置：结构与真实 bridge config 不同，前端据此弹出测试面板
     * @throws \RuntimeException
     */
    public static function buildPrepayConfig(string $from, array $order, string $subject): array
    {
        $paymentSn = trim((string)($order['pay_sn'] ?? $order['payment_sn'] ?? $order['sn'] ?? ''));
        if ($paymentSn === '') {
            throw new \RuntimeException('缺少支付流水号');
        }

        return [
            'config' => [
                'mock' => true,
                'from' => $from,
                'payment_sn' => $paymentSn,
                'order_id' => (int)($order['id'] ?? 0),
                'amount' => round((float)($order['order_amount'] ?? 0), 2),
                'subject' => $subject,
            ],
            'pay_way' => PayEnum::WECHAT_PAY,
        ];
    }

    /**
     * 模拟微信支付回调入账：严格校验流水归属后走真实入账入口
     * @return array [bool $ok, string $message, array $data]
     */
    public static function confirm(int $userId, string $from, string $paymentSn): array
    {
        if (!self::isEnabled()) {
            return [false, '当前环境未开启支付沙箱', []];
        }
        $paymentSn = trim($paymentSn);
        if ($userId <= 0) {
            return [false, '请先登录', []];
        }
        if ($paymentSn === '') {
            return [false, '缺少支付流水号', []];
        }

        $record = null;
        $amount = 0.0;
        $paid = false;

        if ($from === 'order') {
            $record = OrderPayment::where('payment_sn', $paymentSn)->where('user_id', $userId)->find();
            if ($record) {
                $amount = (float)$record->pay_amount;
                $paid = (int)$record->pay_status === OrderPayment::STATUS_PAID;
            }
        } elseif ($from === ActivityRegistrationService::PAY_FROM) {
            $record = ActivityPayment::where('payment_sn', $paymentSn)->where('user_id', $userId)->find();
            if ($record) {
                $amount = (float)$record->pay_amount;
                $paid = (int)$record->pay_status === ActivityPayment::STATUS_PAID;
            }
        } elseif ($from === StaffSettlementRepayService::PAY_FROM) {
            $repay = StaffSettlementRepay::where('pay_sn', $paymentSn)->find();
            if ($repay && (int)Staff::where('id', (int)$repay->staff_id)->value('user_id') === $userId) {
                $record = $repay;
                $amount = (float)$repay->amount;
                $paid = (int)$repay->pay_status === StaffSettlementRepay::PAY_STATUS_PAID;
            }
        } else {
            return [false, '不支持的支付业务', []];
        }

        if (!$record) {
            return [false, '支付流水不存在或无权操作', []];
        }
        if ($paid) {
            return [true, '支付已完成', [
                'payment_sn' => $paymentSn,
                'transaction_id' => (string)($record->transaction_id ?? ''),
                'already_paid' => 1,
            ]];
        }

        $openid = trim((string)UserAuth::where('user_id', $userId)
            ->where('terminal', UserTerminalEnum::WECHAT_MMP)->value('openid'));
        if ($openid === '') {
            $openid = self::buildFallbackOpenid($userId);
        }

        $appId = trim((string)(WeChatConfigService::getMnpConfig()['app_id'] ?? ''));
        if ($appId === '') {
            $appId = 'MOCK_MNP_APPID';
        }
        $mchId = trim((string)(PayConfig::where(['pay_way' => PayEnum::WECHAT_PAY])
            ->findOrEmpty()->toArray()['config']['mch_id'] ?? ''));
        if ($mchId === '') {
            $mchId = 'MOCK_MCH_ID';
        }

        $transactionId = self::MOCK_TX_PREFIX . date('ymdHis') . strtoupper(bin2hex(random_bytes(5)));
        $callbackData = [
            'appid' => $appId,
            'mchid' => $mchId,
            'out_trade_no' => $paymentSn,
            'transaction_id' => $transactionId,
            'trade_state' => 'SUCCESS',
            'attach' => $from,
            'amount' => [
                'total' => MoneyService::yuanToFen($amount),
                'currency' => 'CNY',
            ],
            'payer' => ['openid' => $openid],
            'source' => 'wechat_pay_v3',
            'source_verified' => true,
            'terminal' => UserTerminalEnum::WECHAT_MMP,
            'mock_sandbox' => true,
        ];

        // 复用真实入账入口：锁档期、改订单态、记流水、发通知全部走正式代码
        if ($from === ActivityRegistrationService::PAY_FROM) {
            $result = ActivityRegistrationService::paySuccess($paymentSn, $transactionId, $callbackData);
            if (!($result[0] ?? false)) {
                return [false, (string)($result[1] ?? '支付处理失败'), []];
            }

            return [true, (string)($result[1] ?? '支付成功'), [
                'payment_sn' => $paymentSn,
                'transaction_id' => $transactionId,
                'already_paid' => 0,
            ]];
        }

        $result = PayNotifyLogic::handle($from, $paymentSn, [
            'transaction_id' => $transactionId,
            'callback_data' => $callbackData,
        ]);
        if (!is_array($result)) {
            return [false, is_string($result) ? $result : '支付处理失败', []];
        }

        return [true, '支付成功', [
            'payment_sn' => $paymentSn,
            'transaction_id' => $transactionId,
            'already_paid' => 0,
        ]];
    }

    /**
     * 沙箱退款：返回满足 WeChatPayService::validateRefundResult 的 V3 结构
     * @throws \RuntimeException
     */
    public static function mockRefund(array $refundData): array
    {
        if (!self::isEnabled()) {
            throw new \RuntimeException('当前环境未开启支付沙箱');
        }
        $transactionId = (string)($refundData['transaction_id'] ?? '');
        if (!self::isMockTransactionId($transactionId)) {
            throw new \RuntimeException('非沙箱交易号不允许走沙箱退款');
        }

        $outTradeNo = trim((string)($refundData['out_trade_no'] ?? ''));
        if ($outTradeNo === '') {
            $outTradeNo = trim((string)(OrderPayment::where('transaction_id', $transactionId)->value('payment_sn') ?? ''));
        }
        if ($outTradeNo === '') {
            $outTradeNo = trim((string)(ActivityPayment::where('transaction_id', $transactionId)->value('payment_sn') ?? ''));
        }
        if ($outTradeNo === '') {
            $outTradeNo = trim((string)(StaffSettlementRepay::where('transaction_id', $transactionId)->value('pay_sn') ?? ''));
        }

        return self::buildRefundResult(
            (string)($refundData['refund_sn'] ?? ''),
            $transactionId,
            $outTradeNo,
            $refundData['refund_amount'] ?? 0,
            $refundData['total_amount'] ?? 0
        );
    }

    /**
     * 沙箱退款查询：仅当原支付流水为沙箱交易号时返回成功结构，否则交给真实微信 API
     */
    public static function tryMockQueryRefund(string $refundSn): ?array
    {
        $refundSn = trim($refundSn);
        if (!self::isEnabled() || $refundSn === '') {
            return null;
        }

        $item = RefundItem::where('out_refund_no', $refundSn)->find();
        if ($item) {
            $payment = OrderPayment::where('id', (int)$item->payment_id)->find();
            $transactionId = (string)($payment->transaction_id ?? '');
            if (!self::isMockTransactionId($transactionId)) {
                return null;
            }

            return self::buildRefundResult(
                $refundSn,
                $transactionId,
                (string)($payment->payment_sn ?? ''),
                $item->refund_amount,
                $payment->pay_amount
            );
        }

        $activityRefund = ActivityRefund::where('refund_sn', $refundSn)->find();
        if ($activityRefund) {
            $payment = ActivityPayment::where('id', (int)$activityRefund->payment_id)->find();
            $transactionId = (string)($payment->transaction_id ?? '');
            if (!self::isMockTransactionId($transactionId)) {
                return null;
            }

            return self::buildRefundResult(
                $refundSn,
                $transactionId,
                (string)($payment->payment_sn ?? ''),
                $activityRefund->refund_amount,
                $payment->pay_amount
            );
        }

        $repay = StaffSettlementRepay::where('refund_sn', $refundSn)->find();
        if ($repay) {
            $transactionId = (string)($repay->transaction_id ?? '');
            if (!self::isMockTransactionId($transactionId)) {
                return null;
            }

            return self::buildRefundResult(
                $refundSn,
                $transactionId,
                (string)($repay->pay_sn ?? ''),
                $repay->amount,
                $repay->amount
            );
        }

        return null;
    }

    /**
     * 构造标准 V3 退款应答
     */
    protected static function buildRefundResult(
        string $refundSn,
        string $transactionId,
        string $outTradeNo,
        $refundAmount,
        $totalAmount
    ): array {
        return [
            'refund_id' => self::MOCK_REFUND_PREFIX . date('ymdHis') . strtoupper(bin2hex(random_bytes(4))),
            'out_refund_no' => $refundSn,
            'transaction_id' => $transactionId,
            'out_trade_no' => $outTradeNo,
            'status' => 'SUCCESS',
            'amount' => [
                'refund' => MoneyService::yuanToFen($refundAmount),
                'total' => MoneyService::yuanToFen($totalAmount),
                'currency' => 'CNY',
            ],
            'mock_sandbox' => true,
        ];
    }
}
