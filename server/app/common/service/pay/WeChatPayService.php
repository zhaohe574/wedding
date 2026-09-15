<?php
// +----------------------------------------------------------------------
// | likeadmin快速开发前后端分离管理后台（PHP版）
// +----------------------------------------------------------------------
// | 欢迎阅读学习系统程序代码，建议反馈是我们前进的动力
// | 开源版本可自由商用，可去除界面版权logo
// | gitee下载：https://gitee.com/likeshop_gitee/likeadmin
// | github下载：https://github.com/likeshop-github/likeadmin
// | 访问官网：https://www.likeadmin.cn
// | likeadmin团队 版权所有 拥有最终解释权
// +----------------------------------------------------------------------
// | author: likeadminTeam
// +----------------------------------------------------------------------


namespace app\common\service\pay;


use app\common\enum\PayEnum;
use app\common\enum\user\UserTerminalEnum;
use app\common\logic\PayNotifyLogic;
use app\common\service\ActivityRegistrationService;
use app\common\model\order\Payment as OrderPayment;
use app\common\service\MoneyService;
use app\common\service\OrderRefundService;
use app\common\service\StaffSettlementRepayService;
use app\common\model\user\UserAuth;
use app\common\service\RequestContextService;
use app\common\service\wechat\WeChatConfigService;
use EasyWeChat\Pay\Application;
use EasyWeChat\Pay\Message;
use Nyholm\Psr7\Response;
use think\facade\Log;


/**
 * 微信支付
 * Class WeChatPayService
 * @package app\common\server
 */
class WeChatPayService extends BasePayService
{
    /**
     * 授权信息
     * @var UserAuth|array|\think\Model
     */
    protected $auth;


    /**
     * 微信配置
     * @var
     */
    protected $config;


    /**
     * easyWeChat实例
     * @var
     */
    protected $app;


    /**
     * 当前使用客户端
     * @var
     */
    protected $terminal;


    /**
     * 初始化微信支付配置
     * @param $terminal //用户终端
     * @param null $userId //用户id(获取授权openid)
     */
    public function __construct($terminal, $userId = null)
    {
        $this->terminal = $terminal;
        $this->config = WeChatConfigService::getPayConfigByTerminal($terminal);
        $this->app = new Application($this->config);
        if ($userId !== null) {
            $this->auth = UserAuth::where(['user_id' => $userId, 'terminal' => $terminal])->findOrEmpty();
        }
    }


    /**
     * @notes 发起微信支付统一下单
     * @param $from
     * @param $order
     * @return array|false|string
     * @author 段誉
     * @date 2021/8/4 15:05
     */
    public function pay($from, $order)
    {
        try {
            if ((int)$this->terminal !== UserTerminalEnum::WECHAT_MMP) {
                throw new \Exception('仅支持微信小程序支付');
            }
            if (empty($this->auth['openid'])) {
                throw new \Exception('请先登录微信小程序');
            }
            $config = WeChatConfigService::getMnpConfig();
            $result = $this->jsapiPay($from, $order, $config['app_id']);

            return [
                'config' => $result,
                'pay_way' => PayEnum::WECHAT_PAY
            ];
        } catch (\Exception $e) {
            $this->setError($e->getMessage());
            return false;
        }
    }

    /**
     * @notes 追加支付截止时间
     * @param array $payload
     * @param array $order
     * @return array
     */
    protected function appendTimeExpire(array $payload, array $order): array
    {
        $timeExpire = $this->buildTimeExpire((int)($order['pay_deadline_time'] ?? 0));
        if ($timeExpire !== '') {
            $payload['time_expire'] = $timeExpire;
        }

        return $payload;
    }

    /**
     * @notes 构造微信支付截止时间
     * @param int $timestamp
     * @return string
     */
    protected function buildTimeExpire(int $timestamp): string
    {
        if ($timestamp <= time()) {
            return '';
        }

        return date('c', $timestamp);
    }


    /**
     * @notes jsapiPay
     * @param $from
     * @param $order
     * @param $appId
     * @return mixed
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidArgumentException
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidConfigException
     * @author 段誉
     * @date 2023/2/28 12:12
     */
    public function jsapiPay($from, $order, $appId)
    {
        $payload = $this->appendTimeExpire([
            "appid" => $appId,
            "mchid" => $this->config['mch_id'],
            "description" => $this->payDesc($from, $order),
            "out_trade_no" => $order['pay_sn'],
            "notify_url" => $this->config['notify_url'],
            "amount" => [
                "total" => MoneyService::yuanToFen($order['order_amount']),
                "currency" => "CNY",
            ],
            "payer" => [
                "openid" => $this->auth['openid']
            ],
            'attach' => $from
        ], $order);

        $response = $this->app->getClient()->postJson("v3/pay/transactions/jsapi", $payload);

        $result = $response->toArray(false);
        $this->checkResultFail($result);
        return $this->getPrepayConfig($result['prepay_id'], $appId);
    }








    /**
     * @notes 退款
     * @param array $refundData
     * @return mixed
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidArgumentException
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidConfigException
     * @author 段誉
     * @date 2023/2/28 16:53
     */
    public function refund(array $refundData)
    {
        $response =  $this->app->getClient()->postJson('v3/refund/domestic/refunds', [
            'transaction_id' => $refundData['transaction_id'],
            'out_refund_no' => $refundData['refund_sn'],
            'notify_url' => $this->config['notify_url'],
            'amount' => [
                'refund' => MoneyService::yuanToFen($refundData['refund_amount']),
                'total' => MoneyService::yuanToFen($refundData['total_amount']),
                'currency' => 'CNY',
            ]
        ]);
        $result = $response->toArray(false);
        $this->checkResultFail($result);
        return $result;
    }


    /**
     * @notes 查询退款
     * @param $refundSn
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidArgumentException
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidConfigException
     * @author 段誉
     * @date 2023/3/1 11:16
     */
    public function queryRefund($refundSn)
    {
        $response = $this->app->getClient()->get("v3/refund/domestic/refunds/{$refundSn}");
        $result = $response->toArray(false);
        $this->checkResultFail($result);
        if (($result['out_refund_no'] ?? '') !== $refundSn) {
            throw new \RuntimeException('微信退款查询单号不匹配');
        }
        return $result;
    }


    /**
     * @notes 支付描述
     * @param $from
     * @param mixed $order
     * @return string
     * @author 段誉
     * @date 2023/2/27 17:54
     */
    public function payDesc($from, $order = [])
    {
        $subject = '';
        if (is_array($order) || $order instanceof \ArrayAccess) {
            $subject = trim((string)($order['pay_subject'] ?? ''));
        }

        if ($subject !== '') {
            return $subject;
        }

        $desc = [
            'order' => '商品',
            ActivityRegistrationService::PAY_FROM => '活动报名',
            StaffSettlementRepayService::PAY_FROM => '平台抽成补交',
        ];
        return $desc[$from] ?? '商品';
    }


    /**
     * @notes 捕获错误
     * @param $result
     * @throws \Exception
     * @author 段誉
     * @date 2023/2/28 12:09
     */
    public function checkResultFail($result)
    {
        if (!empty($result['code']) || !empty($result['message'])) {
            throw new \Exception('微信:'. $result['code'] . '-' . $result['message']);
        }
    }


    /**
     * @notes 预支付配置
     * @param $prepayId
     * @param $appId
     * @return mixed[]
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidArgumentException
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidConfigException
     * @author 段誉
     * @date 2023/2/28 17:38
     */
    public function getPrepayConfig($prepayId, $appId)
    {
        return $this->app->getUtils()->buildBridgeConfig($prepayId, $appId);
    }

    /**
     * @notes 微信支付回调失败响应
     * @param string $message
     * @return Response
     */
    protected function failNotifyResponse(string $message): Response
    {
        return new Response(
            500,
            [],
            json_encode([
                'code' => 'ERROR',
                'message' => $message,
            ], JSON_UNESCAPED_UNICODE)
        );
    }

    /**
     * @notes 记录微信支付回调异常上下文
     * @param string $message
     * @param array $context
     * @return void
     */
    protected function logNotifyError(string $message, array $context = []): void
    {
        Log::write('微信支付回调处理失败：' . json_encode(array_merge([
            'message' => $message,
            'terminal' => $this->terminal,
            'request_id' => RequestContextService::ensureRequestId(),
        ], $context), JSON_UNESCAPED_UNICODE));
    }

    /**
     * @notes 记录微信退款回调异常上下文
     * @param string $message
     * @param array $context
     * @return void
     */
    protected function logRefundNotifyError(string $message, array $context = []): void
    {
        Log::write('微信退款回调处理失败：' . json_encode(array_merge([
            'message' => $message,
            'terminal' => $this->terminal,
            'request_id' => RequestContextService::ensureRequestId(),
        ], $context), JSON_UNESCAPED_UNICODE));
    }

    /**
     * @notes 构造微信支付回调日志上下文
     * @param Message $message
     * @return array
     */
    protected function buildNotifyLogContext(Message $message): array
    {
        $outTradeNo = (string)($message['out_trade_no'] ?? '');
        return [
            'payment_sn' => $outTradeNo,
            'out_trade_no' => $outTradeNo,
            'transaction_id' => (string)($message['transaction_id'] ?? ''),
            'attach' => (string)($message['attach'] ?? ''),
            'trade_state' => (string)($message['trade_state'] ?? ''),
        ];
    }

    /**
     * @notes 构造微信退款回调日志上下文
     * @param Message $message
     * @return array
     */
    protected function buildRefundNotifyLogContext(Message $message): array
    {
        return [
            'out_refund_no' => (string)($message['out_refund_no'] ?? ''),
            'refund_id' => (string)($message['refund_id'] ?? ''),
            'refund_status' => (string)($message['refund_status'] ?? ''),
            'out_trade_no' => (string)($message['out_trade_no'] ?? ''),
            'transaction_id' => (string)($message['transaction_id'] ?? ''),
        ];
    }


    /**
     * @notes 支付回调
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidArgumentException
     * @throws \EasyWeChat\Kernel\Exceptions\RuntimeException
     * @throws \ReflectionException
     * @throws \Throwable
     * @author 段誉
     * @date 2023/2/28 14:20
     */
    public function notify()
    {
        $server = $this->app->getServer();
        // 支付通知
        $server->handlePaid(function (Message $message) {
            try {
                $this->handlePaidResult($message->toArray());
                return true;
            } catch (\Throwable $e) {
                $this->logNotifyError($e->getMessage(), $this->buildNotifyLogContext($message));
                return $this->failNotifyResponse($e->getMessage());
            }
        });

        // 退款通知
        $server->handleRefunded(function (Message $message) {
            $data = $message->toArray();
            if ((string)($data['mchid'] ?? '') !== (string)$this->config['mch_id']) {
                return $this->failNotifyResponse('微信退款商户号不匹配');
            }
            if (
                OrderRefundService::handleWechatRefundCallback($data)
                || ActivityRegistrationService::handleWechatRefundCallback($data)
                || \app\common\service\StaffSettlementRepayService::handleWechatRefundCallback($data)
            ) {
                return true;
            }
            $reason = '微信退款回调处理失败';
            $this->logRefundNotifyError($reason, $this->buildRefundNotifyLogContext($message));
            return $this->failNotifyResponse($reason);
        });
        return $server->serve();
    }






    /** 支付通知和主动查询共用相同的验收与入账入口。 */
    public function handlePaidResult(array $data): void
    {
        $error = self::validateMerchantResult($data,
            (string)WeChatConfigService::getMnpConfig()['app_id'], (string)$this->config['mch_id']);
        if ($error !== '') {
            throw new \RuntimeException($error);
        }
        if (($data['trade_state'] ?? '') !== 'SUCCESS') {
            return;
        }
        $data['source'] = 'wechat_pay_v3';
        $data['source_verified'] = true;
        $data['terminal'] = UserTerminalEnum::WECHAT_MMP;
        $from = (string)($data['attach'] ?? '');
        $sn = (string)($data['out_trade_no'] ?? '');
        $transactionId = (string)($data['transaction_id'] ?? '');
        if ($from === ActivityRegistrationService::PAY_FROM) {
            $result = ActivityRegistrationService::paySuccess($sn, $transactionId, $data);
            if (!($result[0] ?? false)) {
                throw new \RuntimeException((string)($result[1] ?? '活动到账处理失败'));
            }
            return;
        }
        $result = PayNotifyLogic::handle($from, $sn, [
            'transaction_id' => $transactionId,
            'callback_data' => $data,
        ]);
        if (!is_array($result)) {
            throw new \RuntimeException(is_string($result) ? $result : '到账处理失败');
        }
    }

    public static function validateRefundResult(array $data, string $sn, string $transactionId, $total, $refund): string
    {
        if (($data['out_trade_no'] ?? '') !== $sn || ($data['transaction_id'] ?? '') !== $transactionId) {
            return '微信退款对应的支付流水不匹配';
        }
        if (($data['amount']['currency'] ?? '') !== 'CNY'
            || !isset($data['amount']['refund'], $data['amount']['total'])
            || filter_var($data['amount']['refund'], FILTER_VALIDATE_INT) === false
            || filter_var($data['amount']['total'], FILTER_VALIDATE_INT) === false
            || (int)$data['amount']['refund'] !== MoneyService::yuanToFen($refund)
            || (int)$data['amount']['total'] !== MoneyService::yuanToFen($total)) {
            return '微信退款金额或币种不匹配';
        }
        return '';
    }

    public static function validatePaymentResult(array $data, string $sn, $amount, int $userId, string $from): string
    {
        if (empty($data['source_verified']) || ($data['source'] ?? '') !== 'wechat_pay_v3') {
            return '微信支付回调来源未验证';
        }
        if (($data['trade_state'] ?? '') !== 'SUCCESS' || ($data['attach'] ?? '') !== $from) {
            return '微信支付状态或业务类型不匹配';
        }
        if (($data['out_trade_no'] ?? '') !== $sn) {
            return '微信支付单号不匹配';
        }
        if (($data['amount']['currency'] ?? '') !== 'CNY'
            || !isset($data['amount']['total'])
            || filter_var($data['amount']['total'], FILTER_VALIDATE_INT) === false
            || (int)$data['amount']['total'] !== MoneyService::yuanToFen($amount)) {
            return '微信支付金额或币种不匹配';
        }
        $openid = (string)($data['payer']['openid'] ?? '');
        if ($openid === '' || $userId <= 0 || !UserAuth::where('user_id', $userId)
            ->where('terminal', UserTerminalEnum::WECHAT_MMP)->where('openid', $openid)->find()) {
            return '微信支付者身份与平台用户不一致';
        }
        return '';
    }

    public static function validateMerchantResult(array $data, string $appId, string $mchId): string
    {
        if ($appId === '' || (string)($data['appid'] ?? '') !== $appId) {
            return '微信支付小程序标识不匹配';
        }
        if ($mchId === '' || (string)($data['mchid'] ?? '') !== $mchId) {
            return '微信支付商户号不匹配';
        }
        return '';
    }

    /** 原子领取查询任务，网络异常保留原状态供后续恢复。 */
    public static function reconcilePayment($payment, string $snField = 'payment_sn'): void
    {
        if (!$payment || (int)$payment->closed_time > 0) {
            return;
        }
        $model = get_class($payment);
        $pending = $model === \app\common\model\financial\StaffSettlementRepay::class
            ? [0, 2, 3] : [0, 3];
        if (!in_array((int)$payment->pay_status, $pending, true)) {
            return;
        }
        $now = time();
        $claimed = $model::where('id', (int)$payment->id)->whereIn('pay_status', $pending)
            ->where('closed_time', 0)->where('query_time', '<=', $now - 15)
            ->update(['query_time' => $now]);
        if (!$claimed) {
            return;
        }
        try {
            $service = new self(UserTerminalEnum::WECHAT_MMP);
            $expired = (int)$payment->expire_time > 0 && (int)$payment->expire_time <= $now;
            $data = $expired || (int)$payment->pay_status !== 0
                ? $service->closeOrder((string)$payment->{$snField})
                : $service->queryOrder((string)$payment->{$snField});
            if (in_array($data['trade_state'] ?? '', ['CLOSED', 'REVOKED', 'PAYERROR'], true)) {
                $model::where('id', (int)$payment->id)->whereIn('pay_status', $pending)->update([
                    'closed_time' => time(),
                    'pay_status' => $model === \app\common\model\financial\StaffSettlementRepay::class ? 2 : 3,
                ]);
            }
        } catch (\Throwable $e) {
            Log::error('支付结果恢复失败：流水=' . (string)$payment->{$snField} . '，原因=' . $e->getMessage());
        }
    }

    /** 查询应答经 SDK 验签，成功结果重新进入幂等入账流程。 */
    public function queryOrder(string $paymentSn): array
    {
        $response = $this->app->getClient()->get(
            'v3/pay/transactions/out-trade-no/' . rawurlencode($paymentSn),
            ['query' => ['mchid' => $this->config['mch_id']]]
        );
        $data = $response->toArray(false);
        $this->checkResultFail($data);
        if ((string)($data['out_trade_no'] ?? '') !== $paymentSn) {
            throw new \RuntimeException('微信查单返回的支付单号不匹配');
        }
        $this->handlePaidResult($data);
        return $data;
    }

    /** 仅在微信确认关闭后返回；已到账订单先恢复本地结果。 */
    public function closeOrder(string $paymentSn): array
    {
        $data = $this->queryOrder($paymentSn);
        if (in_array($data['trade_state'] ?? '', ['SUCCESS', 'CLOSED', 'REVOKED', 'PAYERROR'], true)) {
            return $data;
        }
        try {
            $this->app->getClient()->postJson(
                'v3/pay/transactions/out-trade-no/' . rawurlencode($paymentSn) . '/close',
                ['mchid' => $this->config['mch_id']]
            );
            $data['trade_state'] = 'CLOSED';
            return $data;
        } catch (\Throwable $e) {
            $latest = $this->queryOrder($paymentSn);
            if (!in_array($latest['trade_state'] ?? '', ['SUCCESS', 'CLOSED'], true)) {
                throw $e;
            }
            return $latest;
        }
    }
}
