<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 订阅消息发送服务
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\common\service;

use app\common\model\subscribe\SubscribeMessageTemplate;
use app\common\model\subscribe\SubscribeMessageScene;
use app\common\model\subscribe\SubscribeMessageLog;
use app\common\model\subscribe\UserSubscribe;
use app\common\model\user\User;
use think\facade\Log;
use think\facade\Cache;

/**
 * 订阅消息发送服务
 * Class SubscribeMessageService
 * @package app\common\service
 */
class SubscribeMessageService
{
    // 微信API地址
    const SEND_API_URL = 'https://api.weixin.qq.com/cgi-bin/message/subscribe/send';
    const ACCESS_TOKEN_URL = 'https://api.weixin.qq.com/cgi-bin/token';

    /**
     * @notes 发送订阅消息
     * @param int $userId
     * @param string $scene
     * @param array $data 消息数据
     * @param string $businessType 业务类型
     * @param int $businessId 业务ID
     * @param string $page 跳转页面
     * @return array ['success' => bool, 'msg' => string, 'log_id' => int]
     */
    public static function send(
        int $userId,
        string $scene,
        array $data,
        string $businessType = '',
        int $businessId = 0,
        string $page = ''
    ): array {
        try {
            // 获取用户信息
            $user = User::find($userId);
            if (!$user || empty($user->openid)) {
                return ['success' => false, 'msg' => '用户不存在或未绑定微信', 'log_id' => 0];
            }

            // 获取场景配置
            $sceneConfig = SubscribeMessageScene::getByScene($scene);
            if (!$sceneConfig) {
                return ['success' => false, 'msg' => '场景配置不存在或已禁用', 'log_id' => 0];
            }

            // 获取模板配置
            $template = SubscribeMessageTemplate::where('template_id', $sceneConfig->template_id)->find();
            if (!$template || $template->status != SubscribeMessageTemplate::STATUS_ENABLED) {
                return ['success' => false, 'msg' => '消息模板不存在或已禁用', 'log_id' => 0];
            }

            // 检查用户订阅状态
            if (!UserSubscribe::hasSubscription($userId, $template->template_id)) {
                return ['success' => false, 'msg' => '用户未订阅该消息', 'log_id' => 0];
            }

            // 构建消息内容
            $content = self::buildMessageContent($template->content, $data, $sceneConfig->data_mapping);

            // 使用场景配置的页面路径或传入的页面路径
            $targetPage = $page ?: $sceneConfig->page_path;
            if ($businessId && strpos($targetPage, '?') === false) {
                $targetPage .= '?id=' . $businessId;
            }

            // 创建发送日志
            $log = SubscribeMessageLog::createLog(
                $userId,
                $user->openid,
                $template->template_id,
                $scene,
                $businessType ?: $sceneConfig->scene,
                $businessId,
                $content,
                $targetPage
            );

            // 获取access_token
            $accessToken = self::getAccessToken();
            if (!$accessToken) {
                SubscribeMessageLog::updateSendResult($log->id, false, 'TOKEN_ERROR', '获取access_token失败');
                return ['success' => false, 'msg' => '获取access_token失败', 'log_id' => $log->id];
            }

            // 调用微信API发送消息
            $result = self::callWechatApi($accessToken, [
                'touser' => $user->openid,
                'template_id' => $template->template_id,
                'page' => $targetPage,
                'data' => $content,
                'miniprogram_state' => 'formal',
            ]);

            if ($result['errcode'] == 0) {
                // 发送成功，消费订阅次数
                UserSubscribe::consumeSubscription($userId, $template->template_id);
                SubscribeMessageLog::updateSendResult($log->id, true, '', '', $result['msgid'] ?? '');
                return ['success' => true, 'msg' => '发送成功', 'log_id' => $log->id];
            } else {
                SubscribeMessageLog::updateSendResult(
                    $log->id,
                    false,
                    (string)$result['errcode'],
                    $result['errmsg'] ?? '发送失败'
                );
                return ['success' => false, 'msg' => $result['errmsg'] ?? '发送失败', 'log_id' => $log->id];
            }
        } catch (\Exception $e) {
            Log::error('订阅消息发送异常: ' . $e->getMessage());
            return ['success' => false, 'msg' => '发送异常: ' . $e->getMessage(), 'log_id' => 0];
        }
    }

    /**
     * @notes 批量发送订阅消息
     * @param array $userIds
     * @param string $scene
     * @param array $data
     * @param string $businessType
     * @param int $businessId
     * @param string $page
     * @return array
     */
    public static function batchSend(
        array $userIds,
        string $scene,
        array $data,
        string $businessType = '',
        int $businessId = 0,
        string $page = ''
    ): array {
        $results = [
            'total' => count($userIds),
            'success' => 0,
            'failed' => 0,
            'details' => [],
        ];

        foreach ($userIds as $userId) {
            $result = self::send($userId, $scene, $data, $businessType, $businessId, $page);
            if ($result['success']) {
                $results['success']++;
            } else {
                $results['failed']++;
            }
            $results['details'][$userId] = $result;
        }

        return $results;
    }

    /**
     * @notes 构建消息内容
     * @param array $templateContent 模板内容配置
     * @param array $data 实际数据
     * @param array $mapping 数据映射
     * @return array
     */
    protected static function buildMessageContent(array $templateContent, array $data, array $mapping = []): array
    {
        $content = [];
        
        foreach ($templateContent as $key => $config) {
            // 从mapping中获取数据字段名，或使用key本身
            $dataKey = $mapping[$key] ?? $key;
            $value = $data[$dataKey] ?? '';
            
            // 根据类型处理值
            if (is_array($value)) {
                $value = implode(',', $value);
            }
            
            // 截断过长的内容
            if (strlen($value) > 200) {
                $value = mb_substr($value, 0, 60, 'UTF-8') . '...';
            }
            
            $content[$key] = ['value' => (string)$value];
        }

        return $content;
    }

    /**
     * @notes 获取access_token
     * @return string|null
     */
    protected static function getAccessToken(): ?string
    {
        $cacheKey = 'wechat_miniprogram_access_token';
        $token = Cache::get($cacheKey);

        if ($token) {
            return $token;
        }

        // 从配置获取AppID和AppSecret
        $appId = config('wechat.miniprogram.app_id', '');
        $appSecret = config('wechat.miniprogram.app_secret', '');

        if (empty($appId) || empty($appSecret)) {
            Log::error('微信小程序配置缺失');
            return null;
        }

        $url = self::ACCESS_TOKEN_URL . '?grant_type=client_credential&appid=' . $appId . '&secret=' . $appSecret;

        $response = self::httpGet($url);
        if (!$response) {
            return null;
        }

        $result = json_decode($response, true);
        if (isset($result['access_token'])) {
            // 缓存token，过期时间比实际少5分钟
            $expiresIn = ($result['expires_in'] ?? 7200) - 300;
            Cache::set($cacheKey, $result['access_token'], $expiresIn);
            return $result['access_token'];
        }

        Log::error('获取access_token失败: ' . ($result['errmsg'] ?? '未知错误'));
        return null;
    }

    /**
     * @notes 调用微信API
     * @param string $accessToken
     * @param array $data
     * @return array
     */
    protected static function callWechatApi(string $accessToken, array $data): array
    {
        $url = self::SEND_API_URL . '?access_token=' . $accessToken;
        $response = self::httpPost($url, json_encode($data, JSON_UNESCAPED_UNICODE));

        if (!$response) {
            return ['errcode' => -1, 'errmsg' => 'HTTP请求失败'];
        }

        return json_decode($response, true) ?: ['errcode' => -1, 'errmsg' => '响应解析失败'];
    }

    /**
     * @notes HTTP GET请求
     * @param string $url
     * @return string|null
     */
    protected static function httpGet(string $url): ?string
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);

        $response = curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            Log::error('HTTP GET错误: ' . $error);
            return null;
        }

        return $response ?: null;
    }

    /**
     * @notes HTTP POST请求
     * @param string $url
     * @param string $data
     * @return string|null
     */
    protected static function httpPost(string $url, string $data): ?string
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);

        $response = curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            Log::error('HTTP POST错误: ' . $error);
            return null;
        }

        return $response ?: null;
    }

    /**
     * @notes 发送订单创建通知
     * @param int $userId
     * @param array $orderData
     * @return array
     */
    public static function sendOrderCreateNotice(int $userId, array $orderData): array
    {
        return self::send(
            $userId,
            SubscribeMessageTemplate::SCENE_ORDER_CREATE,
            [
                'thing1' => $orderData['service_name'] ?? '婚庆服务',
                'character_string2' => $orderData['order_sn'] ?? '',
                'amount3' => $orderData['total_amount'] ?? '0.00',
                'time4' => date('Y-m-d H:i'),
            ],
            SubscribeMessageLog::BIZ_TYPE_ORDER,
            $orderData['order_id'] ?? 0
        );
    }

    /**
     * @notes 发送支付成功通知
     * @param int $userId
     * @param array $orderData
     * @return array
     */
    public static function sendPaySuccessNotice(int $userId, array $orderData): array
    {
        return self::send(
            $userId,
            SubscribeMessageTemplate::SCENE_ORDER_PAID,
            [
                'character_string1' => $orderData['order_sn'] ?? '',
                'amount2' => $orderData['pay_amount'] ?? '0.00',
                'time3' => date('Y-m-d H:i'),
                'thing4' => $orderData['service_name'] ?? '婚庆服务',
            ],
            SubscribeMessageLog::BIZ_TYPE_ORDER,
            $orderData['order_id'] ?? 0
        );
    }

    /**
     * @notes 发送服务提醒通知
     * @param int $userId
     * @param array $scheduleData
     * @return array
     */
    public static function sendScheduleRemindNotice(int $userId, array $scheduleData): array
    {
        return self::send(
            $userId,
            SubscribeMessageTemplate::SCENE_SCHEDULE_REMIND,
            [
                'thing1' => $scheduleData['service_name'] ?? '婚庆服务',
                'time2' => $scheduleData['service_date'] ?? '',
                'thing3' => $scheduleData['address'] ?? '待确认',
                'thing4' => $scheduleData['staff_name'] ?? '待分配',
            ],
            SubscribeMessageLog::BIZ_TYPE_SCHEDULE,
            $scheduleData['order_id'] ?? 0
        );
    }

    /**
     * @notes 发送退款结果通知
     * @param int $userId
     * @param array $refundData
     * @return array
     */
    public static function sendRefundResultNotice(int $userId, array $refundData): array
    {
        return self::send(
            $userId,
            SubscribeMessageTemplate::SCENE_REFUND_RESULT,
            [
                'character_string1' => $refundData['order_sn'] ?? '',
                'amount2' => $refundData['refund_amount'] ?? '0.00',
                'phrase3' => $refundData['status_text'] ?? '处理中',
                'thing4' => $refundData['reason'] ?? '',
            ],
            SubscribeMessageLog::BIZ_TYPE_REFUND,
            $refundData['refund_id'] ?? 0
        );
    }

    /**
     * @notes 发送工单进度通知
     * @param int $userId
     * @param array $ticketData
     * @return array
     */
    public static function sendTicketUpdateNotice(int $userId, array $ticketData): array
    {
        return self::send(
            $userId,
            SubscribeMessageTemplate::SCENE_TICKET_UPDATE,
            [
                'character_string1' => $ticketData['ticket_sn'] ?? '',
                'phrase2' => $ticketData['status_text'] ?? '',
                'thing3' => $ticketData['handle_note'] ?? '正在处理中',
                'time4' => date('Y-m-d H:i'),
            ],
            SubscribeMessageLog::BIZ_TYPE_TICKET,
            $ticketData['ticket_id'] ?? 0
        );
    }
}
