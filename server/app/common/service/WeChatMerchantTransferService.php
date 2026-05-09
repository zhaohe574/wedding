<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 微信商家转账服务
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\common\service;

use app\common\enum\user\UserTerminalEnum;
use app\common\model\financial\StaffSettlementTransfer;
use app\common\service\wechat\WeChatConfigService;
use EasyWeChat\Pay\Application;
use Nyholm\Psr7\Response;
use Psr\Http\Message\ResponseInterface;
use think\facade\Log;

/**
 * 微信商家转账接口封装
 * Class WeChatMerchantTransferService
 * @package app\common\service
 */
class WeChatMerchantTransferService
{
    private const TRANSFER_BILL_URL = 'v3/fund-app/mch-transfer/transfer-bills';
    private const QUERY_BY_OUT_BILL_NO_URL = 'v3/fund-app/mch-transfer/transfer-bills/out-bill-no/%s';

    protected array $payConfig = [];
    protected array $mnpConfig = [];
    protected ?Application $app = null;

    /**
     * @notes 获取商家转账配置
     */
    public static function getConfig(): array
    {
        $config = ConfigService::get('staff_settlement_transfer') ?: [];
        if (!is_array($config)) {
            $config = [];
        }

        $reportInfos = $config['transfer_scene_report_infos']
            ?? ConfigService::get('staff_settlement_transfer', 'transfer_scene_report_infos', '');
        if (is_array($reportInfos)) {
            $reportInfos = json_encode($reportInfos, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        return [
            'enabled' => (int)($config['enabled'] ?? ConfigService::get('staff_settlement_transfer', 'enabled', 0)),
            'auto_send' => (int)($config['auto_send'] ?? ConfigService::get('staff_settlement_transfer', 'auto_send', 1)),
            'transfer_scene_id' => (string)($config['transfer_scene_id'] ?? ConfigService::get('staff_settlement_transfer', 'transfer_scene_id', '')),
            'transfer_remark' => (string)($config['transfer_remark'] ?? ConfigService::get('staff_settlement_transfer', 'transfer_remark', '服务人员结算')),
            'user_recv_perception' => (string)($config['user_recv_perception'] ?? ConfigService::get('staff_settlement_transfer', 'user_recv_perception', '服务结算')),
            'quota_hint' => (string)($config['quota_hint'] ?? ConfigService::get('staff_settlement_transfer', 'quota_hint', '单笔转账额度以微信商户平台配置为准，金额达到实名校验阈值时必须配置收款实名。')),
            'manual_fallback' => (int)($config['manual_fallback'] ?? ConfigService::get('staff_settlement_transfer', 'manual_fallback', 1)),
            'amount_name_threshold' => round((float)($config['amount_name_threshold'] ?? ConfigService::get('staff_settlement_transfer', 'amount_name_threshold', 2000)), 2),
            'wechatpay_serial' => (string)($config['wechatpay_serial'] ?? ConfigService::get('staff_settlement_transfer', 'wechatpay_serial', '')),
            'wechatpay_public_key' => (string)($config['wechatpay_public_key'] ?? ConfigService::get('staff_settlement_transfer', 'wechatpay_public_key', '')),
            'transfer_scene_report_infos' => (string)($reportInfos ?: '[{"info_type":"岗位","info_content":"服务人员"}]'),
        ];
    }

    /**
     * @notes 保存商家转账配置
     */
    public static function saveConfig(array $params): array
    {
        $config = self::getConfig();
        $fields = [
            'enabled',
            'auto_send',
            'transfer_scene_id',
            'transfer_remark',
            'user_recv_perception',
            'quota_hint',
            'manual_fallback',
            'amount_name_threshold',
            'wechatpay_serial',
            'wechatpay_public_key',
            'transfer_scene_report_infos',
        ];

        foreach ($fields as $field) {
            if (array_key_exists($field, $params)) {
                $config[$field] = $params[$field];
            }
        }

        $config['enabled'] = (int)($config['enabled'] ?? 0) === 1 ? 1 : 0;
        $config['auto_send'] = (int)($config['auto_send'] ?? 1) === 1 ? 1 : 0;
        $config['manual_fallback'] = (int)($config['manual_fallback'] ?? 1) === 1 ? 1 : 0;
        $config['transfer_scene_id'] = trim((string)($config['transfer_scene_id'] ?? ''));
        $config['transfer_remark'] = mb_substr(trim((string)($config['transfer_remark'] ?? '服务人员结算')), 0, 32);
        $config['user_recv_perception'] = mb_substr(trim((string)($config['user_recv_perception'] ?? '服务结算')), 0, 32);
        $config['quota_hint'] = mb_substr(trim((string)($config['quota_hint'] ?? '')), 0, 255);
        $config['amount_name_threshold'] = max(round((float)($config['amount_name_threshold'] ?? 2000), 2), 0.01);
        $config['wechatpay_serial'] = trim((string)($config['wechatpay_serial'] ?? ''));
        $config['wechatpay_public_key'] = trim((string)($config['wechatpay_public_key'] ?? ''));
        if (is_array($config['transfer_scene_report_infos'] ?? null)) {
            $config['transfer_scene_report_infos'] = json_encode($config['transfer_scene_report_infos'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '';
        } else {
            $config['transfer_scene_report_infos'] = trim((string)($config['transfer_scene_report_infos'] ?? ''));
        }

        foreach ($fields as $field) {
            ConfigService::set('staff_settlement_transfer', $field, (string)$config[$field]);
        }

        return $config;
    }

    /**
     * @notes 是否启用商家转账
     */
    public static function isEnabled(): bool
    {
        return (int)self::getConfig()['enabled'] === 1;
    }

    /**
     * @notes 发起转账单
     */
    public function createTransferBill(StaffSettlementTransfer $transfer): array
    {
        $config = self::getConfig();
        if ((int)$config['enabled'] !== 1) {
            return ['success' => false, 'skipped' => true, 'message' => '微信商家转账未启用'];
        }

        try {
            $wechatConfig = $this->getWechatConfig();
            $amountFen = (int)$transfer->amount_fen;
            $thresholdFen = MoneyService::yuanToFen((float)$config['amount_name_threshold']);
            $userName = trim((string)$transfer->user_name);

            if ($amountFen <= 0) {
                return ['success' => false, 'message' => '转账金额必须大于0'];
            }
            if ($amountFen >= $thresholdFen && $userName === '') {
                return ['success' => false, 'message' => '转账金额达到实名校验阈值，必须维护收款实名姓名'];
            }

            $payload = [
                'appid' => (string)$wechatConfig['app_id'],
                'out_bill_no' => (string)$transfer->out_bill_no,
                'transfer_scene_id' => (string)$transfer->transfer_scene_id,
                'openid' => (string)$transfer->openid,
                'transfer_amount' => $amountFen,
                'transfer_remark' => mb_substr((string)$transfer->transfer_remark, 0, 32),
            ];

            $notifyUrl = $this->buildNotifyUrl();
            if ($notifyUrl !== '') {
                $payload['notify_url'] = $notifyUrl;
            }
            if ((string)$transfer->user_recv_perception !== '') {
                $payload['user_recv_perception'] = mb_substr((string)$transfer->user_recv_perception, 0, 32);
            }

            $reportInfos = $this->normalizeReportInfos((string)$config['transfer_scene_report_infos']);
            if (!$reportInfos) {
                return [
                    'success' => false,
                    'message' => '微信商家转账场景报备信息未配置，请按商户平台场景要求填写',
                ];
            }
            $payload['transfer_scene_report_infos'] = $reportInfos;

            $headers = [];
            if (!empty($config['wechatpay_serial'])) {
                $headers['Wechatpay-Serial'] = (string)$config['wechatpay_serial'];
            }
            if ($userName !== '') {
                $encryptResult = $this->encryptUserName($userName, $config, $amountFen >= $thresholdFen);
                if (!($encryptResult['success'] ?? false)) {
                    return [
                        'success' => false,
                        'message' => (string)($encryptResult['message'] ?? '收款实名加密失败'),
                    ];
                }
                if (!empty($encryptResult['ciphertext'])) {
                    $payload['user_name'] = (string)$encryptResult['ciphertext'];
                }
            }

            $transfer->markProcessing($this->maskPayload($payload));

            $client = $this->getApp()->getClient();
            if ($headers) {
                $client = $client->withHeaders($headers);
            }
            $response = $client->postJson(self::TRANSFER_BILL_URL, $payload);
            $result = $response->toArray(false);

            if (!empty($result['code']) || !empty($result['message'])) {
                return [
                    'success' => false,
                    'message' => $this->formatWechatError($result),
                    'response' => $result,
                ];
            }

            return [
                'success' => true,
                'message' => '微信商家转账已受理',
                'response' => $result,
            ];
        } catch (\Throwable $e) {
            Log::write('微信商家转账发起失败：' . $e->getMessage());
            return [
                'success' => false,
                'message' => $e->getMessage(),
                'response' => ['exception' => $e->getMessage()],
            ];
        }
    }

    /**
     * @notes 按商户单号查询转账单
     */
    public function queryTransferBill(StaffSettlementTransfer $transfer): array
    {
        try {
            $url = sprintf(self::QUERY_BY_OUT_BILL_NO_URL, rawurlencode((string)$transfer->out_bill_no));
            $config = self::getConfig();
            $client = $this->getApp()->getClient();
            if (!empty($config['wechatpay_serial'])) {
                $client = $client->withHeaders(['Wechatpay-Serial' => (string)$config['wechatpay_serial']]);
            }
            $response = $client->get($url);
            $result = $response->toArray(false);

            if (!empty($result['code']) || !empty($result['message'])) {
                return [
                    'success' => false,
                    'message' => $this->formatWechatError($result),
                    'response' => $result,
                ];
            }

            return [
                'success' => true,
                'message' => '查询成功',
                'response' => $result,
            ];
        } catch (\Throwable $e) {
            Log::write('微信商家转账查询失败：' . $e->getMessage());
            return [
                'success' => false,
                'message' => $e->getMessage(),
                'response' => ['exception' => $e->getMessage()],
            ];
        }
    }

    /**
     * @notes 获取确认收款前端参数
     */
    public function buildConfirmPayload(StaffSettlementTransfer $transfer): array
    {
        $wechatConfig = $this->getWechatConfig(false);
        return [
            'mch_id' => (string)($wechatConfig['mch_id'] ?? $transfer->mch_id),
            'app_id' => (string)($wechatConfig['app_id'] ?? $transfer->appid),
            'package' => (string)$transfer->package_info,
            'package_info' => (string)$transfer->package_info,
            'out_bill_no' => (string)$transfer->out_bill_no,
            'transfer_bill_no' => (string)$transfer->transfer_bill_no,
            'status' => (int)$transfer->status,
            'status_text' => StaffSettlementTransfer::getStatusDesc((int)$transfer->status),
        ];
    }

    /**
     * @notes 微信商家转账回调
     */
    public function notify(): ResponseInterface
    {
        try {
            $server = $this->getApp()->getServer();
            $server->with(function ($message) {
                $payload = is_array($message) ? $message : $message->toArray();
                (new StaffSettlementService())->handleTransferNotify($payload);
                return true;
            });
            return $server->serve();
        } catch (\Throwable $e) {
            Log::write('微信商家转账回调处理失败：' . $e->getMessage());
            return new Response(
                500,
                [],
                json_encode(['code' => 'ERROR', 'message' => $e->getMessage()], JSON_UNESCAPED_UNICODE)
            );
        }
    }

    /**
     * @notes 获取并校验微信配置
     */
    protected function getWechatConfig(bool $strict = true): array
    {
        if (!$this->payConfig) {
            $this->payConfig = WeChatConfigService::getPayConfigByTerminal(UserTerminalEnum::WECHAT_MMP);
        }
        if (!$this->mnpConfig) {
            $this->mnpConfig = WeChatConfigService::getMnpConfig();
        }

        $config = [
            'mch_id' => trim((string)($this->payConfig['mch_id'] ?? '')),
            'secret_key' => trim((string)($this->payConfig['secret_key'] ?? '')),
            'app_id' => trim((string)($this->mnpConfig['app_id'] ?? '')),
            'certificate' => (string)($this->payConfig['certificate'] ?? ''),
            'private_key' => (string)($this->payConfig['private_key'] ?? ''),
        ];

        if (!$strict) {
            return $config;
        }

        if ($config['mch_id'] === '' || $config['secret_key'] === '' || $config['app_id'] === '') {
            throw new \RuntimeException('微信支付商户号、APIv3密钥或小程序AppID未配置');
        }
        if ($config['certificate'] === '' || !is_file($config['certificate']) || filesize($config['certificate']) <= 0) {
            throw new \RuntimeException('微信支付API证书未配置或证书文件不可用');
        }
        if ($config['private_key'] === '' || !is_file($config['private_key']) || filesize($config['private_key']) <= 0) {
            throw new \RuntimeException('微信支付API私钥未配置或私钥文件不可用');
        }

        return $config;
    }

    /**
     * @notes 获取EasyWeChat支付实例
     */
    protected function getApp(): Application
    {
        if (!$this->app) {
            $this->getWechatConfig();
            $this->app = new Application($this->payConfig);
        }
        return $this->app;
    }

    /**
     * @notes 构造回调地址
     */
    protected function buildNotifyUrl(): string
    {
        $domain = rtrim((string)request()->domain(), '/');
        if ($domain === '') {
            $domain = rtrim((string)request()->root(true), '/');
        }
        if ($domain === '') {
            return '';
        }
        return $domain . '/api/pay/notifyMerchantTransfer';
    }

    /**
     * @notes 解析场景报备信息
     */
    protected function normalizeReportInfos(string $raw): array
    {
        $raw = trim($raw);
        if ($raw === '') {
            return [];
        }

        $decoded = json_decode($raw, true);
        if (!is_array($decoded)) {
            return [];
        }

        $result = [];
        foreach ($decoded as $item) {
            if (!is_array($item)) {
                continue;
            }
            $type = mb_substr(trim((string)($item['info_type'] ?? '')), 0, 15);
            $content = mb_substr(trim((string)($item['info_content'] ?? '')), 0, 32);
            if ($type === '' || $content === '') {
                continue;
            }
            $result[] = [
                'info_type' => $type,
                'info_content' => $content,
            ];
        }

        return $result;
    }

    /**
     * @notes 加密收款实名字段
     */
    protected function encryptUserName(string $userName, array $config, bool $required): array
    {
        $serial = trim((string)($config['wechatpay_serial'] ?? ''));
        $publicKey = trim((string)($config['wechatpay_public_key'] ?? ''));
        if ($serial === '' || $publicKey === '') {
            if ($required) {
                return [
                    'success' => false,
                    'message' => '转账金额达到实名校验阈值，需配置微信支付公钥ID和微信支付公钥后才能加密收款实名',
                ];
            }
            return ['success' => true, 'ciphertext' => ''];
        }

        $pem = str_replace(["\r\n", "\r"], "\n", $publicKey);
        if (!str_contains($pem, 'BEGIN PUBLIC KEY')) {
            $pem = "-----BEGIN PUBLIC KEY-----\n"
                . chunk_split(str_replace(["\n", ' '], '', $pem), 64, "\n")
                . "-----END PUBLIC KEY-----\n";
        }

        $encrypted = '';
        $ok = openssl_public_encrypt($userName, $encrypted, $pem, OPENSSL_PKCS1_OAEP_PADDING);
        if (!$ok) {
            return ['success' => false, 'message' => '收款实名加密失败，请检查微信支付公钥配置'];
        }

        return ['success' => true, 'ciphertext' => base64_encode($encrypted)];
    }

    /**
     * @notes 微信错误信息
     */
    protected function formatWechatError(array $result): string
    {
        $code = (string)($result['code'] ?? '');
        $message = (string)($result['message'] ?? '微信商家转账请求失败');
        return $code !== '' ? '微信商家转账：' . $code . '-' . $message : $message;
    }

    /**
     * @notes 脱敏请求参数
     */
    protected function maskPayload(array $payload): array
    {
        if (isset($payload['openid'])) {
            $payload['openid'] = $this->maskOpenid((string)$payload['openid']);
        }
        if (isset($payload['user_name'])) {
            $payload['user_name'] = '***';
        }
        return $payload;
    }

    /**
     * @notes 脱敏openid
     */
    protected function maskOpenid(string $openid): string
    {
        if (strlen($openid) <= 8) {
            return '***';
        }
        return substr($openid, 0, 4) . '***' . substr($openid, -4);
    }
}
