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

            $pendingCount = SubscribeMessageLog::countPendingByUserTemplate($userId, $context['template_id']);
            if (!UserSubscribe::canReserveSubscription($userId, $context['template_id'], $pendingCount)) {
                Log::info('订阅消息跳过：用户无可用订阅次数，user_id=' . $userId . '，template_id=' . $context['template_id']);
                return ['success' => false, 'msg' => '用户无可用订阅次数', 'log_id' => 0];
            }

            $plannedSendTime = self::resolvePlannedSendTime($context['scene_config'], $forceDispatch);
            $log = SubscribeMessageLog::createLog(
                $userId,
                $context['openid'],
                $context['template_id'],
                $scene,
                $context['business_type'],
                $businessId,
                $context['content'],
                $context['page'],
                $plannedSendTime,
                $context['miniprogram_state']
            );

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
                $value = implode('，', array_map(static function ($item) {
                    return self::sanitizeTemplateValue($item);
                }, $value));
            }

            // 截断过长的内容
            $value = self::sanitizeTemplateValue($value);

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
        $log = SubscribeMessageLog::find($logId);
        if (!$log) {
            return ['success' => false, 'msg' => '发送日志不存在', 'log_id' => 0];
        }

        if (!$force && (int) $log->send_status !== SubscribeMessageLog::SEND_STATUS_PENDING) {
            return ['success' => false, 'msg' => '当前记录不可派发', 'log_id' => (int) $log->id];
        }

        if (empty($log->openid)) {
            SubscribeMessageLog::updateSendResult((int) $log->id, false, 'OPENID_EMPTY', '用户未绑定小程序');
            return ['success' => false, 'msg' => '用户未绑定小程序', 'log_id' => (int) $log->id];
        }

        [$sceneUsable, $sceneError] = self::checkLogSceneUsable($log);
        if (!$sceneUsable) {
            SubscribeMessageLog::updateSendResult((int) $log->id, false, 'SCENE_INVALID', $sceneError);
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
            SubscribeMessageLog::updateSendResult((int) $log->id, false, 'DISPATCH_ERROR', $e->getMessage());
            Log::error('订阅消息派发异常: log_id=' . (int) $log->id . '，error=' . $e->getMessage());
            return ['success' => false, 'msg' => '发送异常: ' . $e->getMessage(), 'log_id' => (int) $log->id];
        }

        $errCode = (int) ($result['errcode'] ?? -1);
        if ($errCode === 0) {
            UserSubscribe::consumeSubscription((int) $log->user_id, (string) $log->template_id);
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
        SubscribeMessageLog::updateSendResult(
            (int) $log->id,
            false,
            (string) $errCode,
            $errorMsg,
            (string) ($result['request_id'] ?? $result['msgid'] ?? '')
        );

        return [
            'success' => false,
            'queued' => false,
            'sent' => false,
            'msg' => $errorMsg,
            'log_id' => (int) $log->id,
        ];
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
            $dispatchResult = self::dispatchLog((int) ($log['id'] ?? 0));
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
                'character_string1' => $orderData['character_string1'] ?? ($orderData['order_sn'] ?? ''),
                'thing2' => $orderData['thing2'] ?? ($orderData['status_text'] ?? '服务人员已确认'),
                'amount3' => $orderData['amount3'] ?? ($orderData['pay_amount'] ?? '0.00'),
                'time4' => $orderData['time4'] ?? ($orderData['service_date'] ?? ''),
                'order_sn' => $orderData['order_sn'] ?? '',
                'status_text' => $orderData['status_text'] ?? '服务人员已确认',
                'pay_amount' => $orderData['pay_amount'] ?? '0.00',
                'service_date' => $orderData['service_date'] ?? '',
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
        $page = '';
        if (!empty($refundData['order_id'])) {
            $page = 'pages/order_detail/order_detail?id=' . (int) $refundData['order_id'];
        }

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
