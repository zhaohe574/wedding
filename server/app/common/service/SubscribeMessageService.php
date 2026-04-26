<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 订阅消息发送服务
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\common\service;

use app\common\enum\user\UserTerminalEnum;
use app\common\model\subscribe\SubscribeMessageLog;
use app\common\model\subscribe\SubscribeMessageScene;
use app\common\model\subscribe\SubscribeMessageTemplate;
use app\common\model\subscribe\UserSubscribe;
use app\common\model\user\User;
use app\common\model\user\UserAuth;
use app\common\service\wechat\WeChatMnpService;
use think\facade\Db;
use think\facade\Log;

/**
 * 订阅消息发送服务
 * Class SubscribeMessageService
 * @package app\common\service
 */
class SubscribeMessageService
{
    /**
     * 强制立即派发选项
     */
    public const OPTION_FORCE_DISPATCH = 'force_dispatch';

    private const MAX_RETRY_COUNT = 3;
    private const RETRY_DELAYS = [60, 300, 900];

    /**
     * @notes 发送订阅消息
     * @param int $userId
     * @param string $scene
     * @param array $data 消息数据
     * @param string $businessType 业务类型
     * @param int $businessId 业务ID
     * @param string $page 跳转页面
     * @param array $options 额外选项
     * @return array ['success' => bool, 'msg' => string, 'log_id' => int]
     */
    public static function send(
        int $userId,
        string $scene,
        array $data,
        string $businessType = '',
        int $businessId = 0,
        string $page = '',
        array $options = []
    ): array {
        try {
            $forceDispatch = (bool) ($options[self::OPTION_FORCE_DISPATCH] ?? false);
            $context = self::buildSendContext($userId, $scene, $data, $businessType, $businessId, $page);

            if (!$forceDispatch && (int) ($context['scene_config']->is_auto ?? 1) !== 1) {
                Log::info('订阅消息跳过：场景为手动触发，scene=' . $scene);
                return ['success' => false, 'msg' => '场景配置为手动触发', 'log_id' => 0];
            }

            $plannedSendTime = self::resolvePlannedSendTime($context['scene_config'], $forceDispatch);
            $dedupeKey = self::buildDedupeKey($userId, $scene, $context['business_type'], $businessId, $data, $plannedSendTime);
            $createdLog = false;

            try {
                $log = Db::transaction(function () use (
                    $userId,
                    $scene,
                    $businessId,
                    $context,
                    $plannedSendTime,
                    $dedupeKey,
                    &$createdLog
                ) {
                    $existingLog = SubscribeMessageLog::findByDedupeKey($dedupeKey);
                    if ($existingLog) {
                        return $existingLog;
                    }

                    if (!UserSubscribe::reserveSubscription($userId, $context['template_id'])) {
                        Log::info('订阅消息跳过：用户无可用订阅次数，user_id=' . $userId . '，template_id=' . $context['template_id']);
                        throw new \RuntimeException('用户无可用订阅次数');
                    }

                    $createdLog = true;
                    return SubscribeMessageLog::createLog(
                        $userId,
                        $context['openid'],
                        $context['template_id'],
                        $scene,
                        $context['business_type'],
                        $businessId,
                        $context['content'],
                        $context['page'],
                        $plannedSendTime,
                        $context['miniprogram_state'],
                        $dedupeKey
                    );
                });
            } catch (\Throwable $e) {
                $existingLog = SubscribeMessageLog::findByDedupeKey($dedupeKey);
                if ($existingLog) {
                    return self::formatExistingLogResult($existingLog);
                }
                throw $e;
            }

            if (!$createdLog) {
                return self::formatExistingLogResult($log);
            }

            if (!$forceDispatch && $plannedSendTime > time()) {
                return [
                    'success' => true,
                    'queued' => true,
                    'sent' => false,
                    'msg' => '已加入发送队列',
                    'log_id' => (int) $log->id,
                ];
            }

            return self::dispatchLog((int) $log->id, true);
        } catch (\RuntimeException $e) {
            return ['success' => false, 'msg' => $e->getMessage(), 'log_id' => 0];
        } catch (\Throwable $e) {
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
        string $page = '',
        array $options = []
    ): array {
        $results = [
            'total' => count($userIds),
            'success' => 0,
            'failed' => 0,
            'details' => [],
        ];

        foreach ($userIds as $userId) {
            $result = self::send($userId, $scene, $data, $businessType, $businessId, $page, $options);
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
     * @notes 返回已存在幂等日志的发送结果
     * @param SubscribeMessageLog $log
     * @return array
     */
    protected static function formatExistingLogResult(SubscribeMessageLog $log): array
    {
        $status = (int) $log->send_status;
        if ($status === SubscribeMessageLog::SEND_STATUS_SUCCESS) {
            return [
                'success' => true,
                'queued' => false,
                'sent' => true,
                'msg' => '订阅消息已发送',
                'log_id' => (int) $log->id,
            ];
        }

        if (in_array($status, [SubscribeMessageLog::SEND_STATUS_PENDING, SubscribeMessageLog::SEND_STATUS_SENDING], true)) {
            return [
                'success' => true,
                'queued' => true,
                'sent' => false,
                'msg' => '订阅消息已在发送队列中',
                'log_id' => (int) $log->id,
            ];
        }

        return [
            'success' => false,
            'queued' => false,
            'sent' => false,
            'msg' => (string) ($log->error_msg ?: '订阅消息已存在失败记录'),
            'log_id' => (int) $log->id,
        ];
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
            $dataKey = $mapping[$key] ?? $key;
            $value = $data[$dataKey] ?? ($data[$key] ?? '');

            if (is_array($value)) {
                $value = implode('，', array_map(static function ($item) {
                    return self::sanitizeTemplateValue($item);
                }, $value));
            }

            $value = self::formatTemplateValue($key, $value);
            if ($value === '') {
                throw new \RuntimeException('订阅消息字段不能为空：' . $key);
            }

            $content[$key] = ['value' => (string)$value];
        }

        return $content;
    }

    /**
     * @notes 派发单条发送日志
     * @param int $logId
     * @param bool $force
     * @return array
     */
    public static function dispatchLog(int $logId, bool $force = false): array
    {
        $log = SubscribeMessageLog::claimLog($logId, $force);
        if (!$log) {
            return ['success' => false, 'msg' => '发送日志不存在或当前记录不可派发', 'log_id' => $logId];
        }

        if (empty($log->openid)) {
            SubscribeMessageLog::updateSendResult((int) $log->id, false, 'OPENID_EMPTY', '用户未绑定小程序');
            UserSubscribe::releaseReservedSubscription((int) $log->user_id, (string) $log->template_id);
            return ['success' => false, 'msg' => '用户未绑定小程序', 'log_id' => (int) $log->id];
        }

        [$sceneUsable, $sceneError] = self::checkLogSceneUsable($log);
        if (!$sceneUsable) {
            SubscribeMessageLog::updateSendResult((int) $log->id, false, 'SCENE_INVALID', $sceneError);
            UserSubscribe::releaseReservedSubscription((int) $log->user_id, (string) $log->template_id);
            return ['success' => false, 'msg' => $sceneError, 'log_id' => (int) $log->id];
        }

        try {
            $wechat = new WeChatMnpService();
            $result = $wechat->sendSubscribeMessage([
                'touser' => (string) $log->openid,
                'template_id' => (string) $log->template_id,
                'page' => (string) $log->page,
                'data' => $log->content,
                'miniprogram_state' => (string) ($log->miniprogram_state ?: 'formal'),
            ]);
        } catch (\Throwable $e) {
            Log::error('订阅消息派发异常: log_id=' . (int) $log->id . '，error=' . $e->getMessage());
            return self::handleDispatchFailure($log, 'DISPATCH_ERROR', $e->getMessage());
        }

        $errCode = (int) ($result['errcode'] ?? -1);
        if ($errCode === 0) {
            UserSubscribe::consumeReservedSubscription((int) $log->user_id, (string) $log->template_id);
            SubscribeMessageLog::updateSendResult(
                (int) $log->id,
                true,
                '',
                '',
                (string) ($result['msgid'] ?? $result['request_id'] ?? '')
            );

            return [
                'success' => true,
                'queued' => false,
                'sent' => true,
                'msg' => '发送成功',
                'log_id' => (int) $log->id,
            ];
        }

        $errorMsg = (string) ($result['errmsg'] ?? '发送失败');
        return self::handleDispatchFailure(
            $log,
            (string) $errCode,
            $errorMsg,
            (string) ($result['request_id'] ?? $result['msgid'] ?? '')
        );
    }

    /**
     * @notes 处理派发失败
     * @param SubscribeMessageLog $log
     * @param string $errorCode
     * @param string $errorMsg
     * @param string $requestId
     * @return array
     */
    protected static function handleDispatchFailure(
        SubscribeMessageLog $log,
        string $errorCode,
        string $errorMsg,
        string $requestId = ''
    ): array {
        $retryCount = (int) ($log->retry_count ?? 0);
        if (self::isTransientError($errorCode) && $retryCount < self::MAX_RETRY_COUNT) {
            $nextRetryTime = time() + self::getRetryDelay($retryCount);
            SubscribeMessageLog::markTransientFailure(
                (int) $log->id,
                $errorCode,
                $errorMsg,
                $requestId,
                $nextRetryTime
            );

            return [
                'success' => false,
                'queued' => true,
                'sent' => false,
                'msg' => '发送失败，已加入重试队列：' . $errorMsg,
                'log_id' => (int) $log->id,
            ];
        }

        SubscribeMessageLog::updateSendResult(
            (int) $log->id,
            false,
            $errorCode,
            $errorMsg,
            $requestId
        );

        if (self::shouldClearSubscriptionQuota($errorCode)) {
            UserSubscribe::clearAvailableSubscription((int) $log->user_id, (string) $log->template_id);
        } else {
            UserSubscribe::releaseReservedSubscription((int) $log->user_id, (string) $log->template_id);
        }

        return [
            'success' => false,
            'queued' => false,
            'sent' => false,
            'msg' => $errorMsg,
            'log_id' => (int) $log->id,
        ];
    }

    /**
     * @notes 判断是否为可重试错误
     */
    protected static function isTransientError(string $errorCode): bool
    {
        if ($errorCode === 'DISPATCH_ERROR') {
            return true;
        }

        $code = (int) $errorCode;
        return in_array($code, [-1, 40001, 40014, 42001, 45009, 45011, 50001, 50002], true)
            || $code >= 50000;
    }

    /**
     * @notes 判断是否需要清空本地授权额度
     */
    protected static function shouldClearSubscriptionQuota(string $errorCode): bool
    {
        return in_array((int) $errorCode, [43101], true);
    }

    /**
     * @notes 获取重试延迟秒数
     */
    protected static function getRetryDelay(int $retryCount): int
    {
        $delays = self::RETRY_DELAYS;
        return $delays[$retryCount] ?? $delays[array_key_last($delays)];
    }

    /**
     * @notes 扫描并派发到期日志
     * @param int $limit
     * @return array
     */
    public static function dispatchPendingLogs(int $limit = 100): array
    {
        $logs = SubscribeMessageLog::getDuePendingLogs($limit);
        $result = [
            'processed' => 0,
            'success' => 0,
            'failed' => 0,
        ];

        foreach ($logs as $log) {
            $dispatchResult = self::dispatchLog((int) ($log['id'] ?? 0), true);
            $result['processed']++;
            if ($dispatchResult['success'] ?? false) {
                $result['success']++;
            } else {
                $result['failed']++;
            }
        }

        return $result;
    }

    /**
     * @notes 构建发送上下文
     * @param int $userId
     * @param string $scene
     * @param array $data
     * @param string $businessType
     * @param int $businessId
     * @param string $page
     * @return array
     */
    protected static function buildSendContext(
        int $userId,
        string $scene,
        array $data,
        string $businessType,
        int $businessId,
        string $page
    ): array {
        if (!SubscribeMessageTemplate::isActiveScene($scene)) {
            Log::info('订阅消息跳过：场景已下线，scene=' . $scene);
            throw new \RuntimeException('订阅消息场景已下线');
        }

        $user = User::find($userId);
        if (!$user) {
            Log::info('订阅消息跳过：用户不存在，user_id=' . $userId . '，scene=' . $scene);
            throw new \RuntimeException('用户不存在');
        }

        $userAuth = UserAuth::where('user_id', $userId)
            ->where('terminal', UserTerminalEnum::WECHAT_MMP)
            ->where('openid', '<>', '')
            ->order('id', 'desc')
            ->find();
        if (!$userAuth) {
            Log::info('订阅消息跳过：用户未绑定小程序，user_id=' . $userId . '，scene=' . $scene);
            throw new \RuntimeException('用户未绑定小程序');
        }

        $sceneConfig = SubscribeMessageScene::getByScene($scene);
        if (!$sceneConfig) {
            Log::info('订阅消息跳过：场景未配置或已禁用，scene=' . $scene);
            throw new \RuntimeException('场景配置不存在或已禁用');
        }

        $template = null;
        if (!empty($sceneConfig->template_id)) {
            $template = SubscribeMessageTemplate::where('template_id', $sceneConfig->template_id)->find();
        }
        if (!$template) {
            $template = SubscribeMessageTemplate::getByScene($scene);
        }
        if (!$template || (int) $template->status !== SubscribeMessageTemplate::STATUS_ENABLED) {
            Log::info('订阅消息跳过：模板未绑定或已禁用，scene=' . $scene . '，template_id=' . ($sceneConfig->template_id ?? ''));
            throw new \RuntimeException('消息模板不存在或已禁用');
        }

        if (!SubscribeMessageTemplate::isUsableTemplateId((string) $template->template_id)) {
            Log::info('订阅消息跳过：模板ID仍为占位值，scene=' . $scene . '，template_id=' . (string) $template->template_id);
            throw new \RuntimeException('消息模板尚未配置真实模板ID');
        }

        if (!UserSubscribe::hasSubscription($userId, (string) $template->template_id)) {
            Log::info('订阅消息跳过：用户未订阅模板，user_id=' . $userId . '，template_id=' . $template->template_id);
            throw new \RuntimeException('用户未订阅该消息');
        }

        return [
            'openid' => (string) $userAuth->openid,
            'template_id' => (string) $template->template_id,
            'scene_config' => $sceneConfig,
            'business_type' => $businessType ?: (string) $sceneConfig->scene,
            'content' => self::buildMessageContent($template->content, $data, $sceneConfig->data_mapping),
            'page' => self::buildTargetPage($page ?: (string) $sceneConfig->page_path, $businessId),
            'miniprogram_state' => 'formal',
        ];
    }

    /**
     * @notes 计算计划发送时间
     * @param SubscribeMessageScene $sceneConfig
     * @param bool $forceDispatch
     * @return int
     */
    protected static function resolvePlannedSendTime(SubscribeMessageScene $sceneConfig, bool $forceDispatch): int
    {
        $now = time();
        if ($forceDispatch) {
            return $now;
        }

        $delaySeconds = max((int) ($sceneConfig->delay_seconds ?? 0), 0);
        return $delaySeconds > 0 ? $now + $delaySeconds : $now;
    }

    /**
     * @notes 生成业务幂等键
     */
    protected static function buildDedupeKey(
        int $userId,
        string $scene,
        string $businessType,
        int $businessId,
        array $data,
        int $plannedSendTime
    ): string {
        $statusText = self::normalizeDedupePart((string) ($data['status_text'] ?? $data['phrase2'] ?? $data['phrase3'] ?? ''));
        $businessPart = $businessId > 0 ? (string) $businessId : substr(sha1(json_encode($data, JSON_UNESCAPED_UNICODE)), 0, 12);

        $key = match ($scene) {
            SubscribeMessageTemplate::SCENE_ORDER_CONFIRM =>
                "order_confirm:user:{$userId}:order:{$businessPart}:confirmed",
            SubscribeMessageTemplate::SCENE_SCHEDULE_REMIND =>
                'schedule_remind:user:' . $userId . ':order:' . $businessPart
                    . ':date:' . self::normalizeScheduleDate($data, $plannedSendTime),
            SubscribeMessageTemplate::SCENE_REFUND_RESULT =>
                "refund_result:user:{$userId}:refund:{$businessPart}:status:{$statusText}",
            SubscribeMessageTemplate::SCENE_TICKET_UPDATE =>
                "ticket_update:user:{$userId}:ticket:{$businessPart}:status:{$statusText}",
            SubscribeMessageTemplate::SCENE_WAITLIST_RELEASE,
            SubscribeMessageTemplate::SCENE_WAITLIST_EXPIRED =>
                "{$scene}:user:{$userId}:waitlist:{$businessPart}",
            default =>
                "{$scene}:user:{$userId}:{$businessType}:{$businessPart}:data:" . substr(sha1(json_encode($data, JSON_UNESCAPED_UNICODE)), 0, 12),
        };

        return strlen($key) <= 128 ? $key : substr($key, 0, 84) . ':' . sha1($key);
    }

    /**
     * @notes 归一化档期提醒日期
     */
    protected static function normalizeScheduleDate(array $data, int $plannedSendTime): string
    {
        $value = (string) ($data['service_date'] ?? $data['time2'] ?? '');
        $timestamp = $value !== '' ? strtotime($value) : false;
        if ($timestamp !== false) {
            return date('Ymd', $timestamp);
        }

        return $plannedSendTime > 0 ? date('Ymd', $plannedSendTime) : date('Ymd');
    }

    /**
     * @notes 归一化幂等键片段
     */
    protected static function normalizeDedupePart(string $value): string
    {
        $value = trim($value);
        if ($value === '') {
            return 'default';
        }

        $ascii = preg_replace('/[^A-Za-z0-9]+/', '', $value) ?: '';
        return $ascii !== '' ? substr($ascii, 0, 24) : substr(sha1($value), 0, 12);
    }

    /**
     * @notes 检查日志关联场景和模板是否可用
     * @param SubscribeMessageLog $log
     * @return array{0: bool, 1: string}
     */
    protected static function checkLogSceneUsable(SubscribeMessageLog $log): array
    {
        if (!SubscribeMessageTemplate::isActiveScene((string) $log->scene)) {
            return [false, '订阅消息场景已下线'];
        }

        $sceneConfig = SubscribeMessageScene::where('scene', (string) $log->scene)->find();
        if (!$sceneConfig || (int) $sceneConfig->status !== SubscribeMessageScene::STATUS_ENABLED) {
            return [false, '场景配置不存在或已禁用'];
        }

        $template = SubscribeMessageTemplate::where('template_id', (string) $log->template_id)->find();
        if (!$template || (int) $template->status !== SubscribeMessageTemplate::STATUS_ENABLED) {
            return [false, '消息模板不存在或已禁用'];
        }

        if (!SubscribeMessageTemplate::isUsableTemplateId((string) $template->template_id)) {
            return [false, '消息模板尚未配置真实模板ID'];
        }

        return [true, ''];
    }

    /**
     * @notes 构建安全页面路径
     */
    protected static function buildTargetPage(string $page, int $businessId = 0): string
    {
        $targetPage = self::sanitizeMiniProgramPage($page);
        if ($targetPage === '') {
            return '';
        }

        if ($businessId > 0 && strpos($targetPage, '?') === false) {
            $targetPage .= '?id=' . $businessId;
        }

        return $targetPage;
    }

    /**
     * @notes 清洗订阅消息页面路径
     */
    protected static function sanitizeMiniProgramPage(string $page): string
    {
        $page = trim($page);
        if ($page === '') {
            return '';
        }

        $page = preg_replace('/[\x00-\x1F\x7F]/u', '', $page) ?: '';
        $page = ltrim($page, '/');
        if ($page === '' || str_contains($page, '..') || preg_match('#^(?:https?:)?//#i', $page)) {
            Log::warning('订阅消息页面路径已拒绝', ['page' => $page]);
            return '';
        }

        if (!preg_match('#^[A-Za-z0-9_\\-/\\?=&.%]+$#', $page)) {
            Log::warning('订阅消息页面路径格式非法', ['page' => $page]);
            return '';
        }

        return mb_strlen($page, 'UTF-8') > 512 ? mb_substr($page, 0, 512, 'UTF-8') : $page;
    }

    /**
     * @notes 按微信订阅消息字段类型格式化字段值
     */
    protected static function formatTemplateValue(string $key, $value): string
    {
        $text = self::sanitizeTemplateValue($value);
        if ($text === '') {
            return '';
        }

        $type = self::getTemplateFieldType($key);
        if ($type === 'amount' && is_numeric($text)) {
            $text = number_format((float) $text, 2, '.', '');
        } elseif (in_array($type, ['time', 'date'], true) && preg_match('/^\d{10}$/', $text)) {
            $text = date($type === 'date' ? 'Y-m-d' : 'Y-m-d H:i', (int) $text);
        } elseif ($type === 'character_string') {
            $text = preg_replace('/[^A-Za-z0-9_\\-.\\/]+/', '', $text) ?: 'UNKNOWN';
        }

        $limits = [
            'thing' => 20,
            'phrase' => 5,
            'character_string' => 32,
            'amount' => 10,
            'time' => 20,
            'date' => 20,
            'number' => 32,
            'name' => 10,
            'phone_number' => 20,
            'car_number' => 8,
        ];
        $limit = $limits[$type] ?? 200;

        return mb_strlen($text, 'UTF-8') > $limit
            ? mb_substr($text, 0, $limit, 'UTF-8')
            : $text;
    }

    /**
     * @notes 获取微信模板字段类型
     */
    protected static function getTemplateFieldType(string $key): string
    {
        if (preg_match('/^([a-z_]+)\d*$/', $key, $matches)) {
            return $matches[1];
        }

        return '';
    }

    /**
     * @notes 清洗模板字段值
     */
    protected static function sanitizeTemplateValue($value): string
    {
        if (is_bool($value)) {
            $value = $value ? '是' : '否';
        } elseif (!is_scalar($value) && $value !== null) {
            $value = '';
        }

        $text = trim((string)$value);
        if ($text === '') {
            return '';
        }

        $text = preg_replace('/[\x00-\x1F\x7F]/u', '', $text) ?: '';
        if ($text === '') {
            return '';
        }

        return mb_strlen($text, 'UTF-8') > 200
            ? mb_substr($text, 0, 200, 'UTF-8')
            : $text;
    }

    /**
     * @notes 发送订单确认通知
     * @param int $userId
     * @param array $orderData
     * @return array
     */
    public static function sendOrderConfirmNotice(int $userId, array $orderData): array
    {
        return self::send(
            $userId,
            SubscribeMessageTemplate::SCENE_ORDER_CONFIRM,
            [
                'character_string1' => $orderData['character_string1'] ?? ($orderData['order_sn'] ?? 'UNKNOWN'),
                'thing2' => $orderData['thing2'] ?? ($orderData['status_text'] ?? '服务人员已确认'),
                'amount3' => $orderData['amount3'] ?? ($orderData['pay_amount'] ?? '0.00'),
                'time4' => $orderData['time4'] ?? ($orderData['service_date'] ?? date('Y-m-d H:i')),
                'order_sn' => $orderData['order_sn'] ?? 'UNKNOWN',
                'status_text' => $orderData['status_text'] ?? '服务人员已确认',
                'pay_amount' => $orderData['pay_amount'] ?? '0.00',
                'service_date' => $orderData['service_date'] ?? date('Y-m-d H:i'),
                'service_name' => $orderData['service_name'] ?? '婚庆服务',
            ],
            SubscribeMessageLog::BIZ_TYPE_ORDER,
            (int) ($orderData['order_id'] ?? 0)
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
                'service_name' => $scheduleData['service_name'] ?? '婚庆服务',
                'service_date' => $scheduleData['service_date'] ?? date('Y-m-d H:i'),
                'address' => $scheduleData['address'] ?? '待确认',
                'staff_name' => $scheduleData['staff_name'] ?? '待分配',
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
        $page = '';
        if (!empty($refundData['order_id'])) {
            $page = 'pages/order_detail/order_detail?id=' . (int) $refundData['order_id'];
        }

        return self::send(
            $userId,
            SubscribeMessageTemplate::SCENE_REFUND_RESULT,
            [
                'order_sn' => $refundData['order_sn'] ?? 'UNKNOWN',
                'refund_amount' => $refundData['refund_amount'] ?? '0.00',
                'status_text' => $refundData['status_text'] ?? '处理中',
                'reason' => $refundData['reason'] ?? '退款申请已处理',
            ],
            SubscribeMessageLog::BIZ_TYPE_REFUND,
            $refundData['refund_id'] ?? 0,
            $page
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
                'ticket_sn' => $ticketData['ticket_sn'] ?? 'UNKNOWN',
                'status_text' => $ticketData['status_text'] ?? '处理中',
                'handle_note' => $ticketData['handle_note'] ?? '正在处理中',
                'update_time' => $ticketData['update_time'] ?? date('Y-m-d H:i'),
            ],
            SubscribeMessageLog::BIZ_TYPE_TICKET,
            $ticketData['ticket_id'] ?? 0
        );
    }
}
