<?php

declare(strict_types=1);

namespace app\common\service;

use app\common\model\wechat\OaFollower;
use app\common\model\wechat\OaNotificationLog;
use app\common\model\wechat\OaNotificationTemplate;
use app\common\service\wechat\WeChatConfigService;
use app\common\service\wechat\WeChatOaService;
use think\facade\Log;

/**
 * 统一处理服务号业务通知，站内消息由业务事件保留。
 */
class WechatNotificationService
{
    public const AUDIENCE_USER = OaNotificationTemplate::AUDIENCE_USER;
    public const AUDIENCE_STAFF = OaNotificationTemplate::AUDIENCE_STAFF;
    public const CHANNEL_OA = 'oa';

    private const MAX_RETRY_COUNT = 3;
    private const RETRY_DELAYS = [60, 300, 900];
    public const DEFAULT_SCHEDULE_REMIND_REMARK_TEXT = '您预约的档期即将开始，请提前做好准备。';

    public static function send(
        int $userId,
        string $scene,
        array $data,
        string $businessType = '',
        int $businessId = 0,
        string $page = '',
        array $options = []
    ): array {
        return self::sendScene($userId, $scene, $data, $businessType, $businessId, self::AUDIENCE_USER, $page, $options);
    }

    /**
     * 发送业务场景通知。
     */
    public static function sendScene(
        int $userId,
        string $scene,
        array $data,
        string $businessType = '',
        int $businessId = 0,
        string $audience = self::AUDIENCE_USER,
        string $page = '',
        array $options = []
    ): array {
        $options['audience'] = $audience;
        if ($userId <= 0) {
            return self::failure('接收用户不存在');
        }

        if ((int) ConfigService::get('oa_notification', 'enabled', 0) !== 1) {
            return self::recordSkipped($userId, $scene, $data, $businessType, $businessId, $page, $options, '服务号通知未开启');
        }

        $plannedSendTime = (int) ($options['planned_send_time'] ?? time());
        $dedupeKey = self::buildDedupeKey($userId, $scene, $businessType, $businessId, $audience, $data, $plannedSendTime);

        try {
            $existing = OaNotificationLog::findByDedupeKey($dedupeKey);
            if ($existing) {
                return self::formatExistingLog($existing);
            }
        } catch (\Throwable $e) {
            Log::warning('服务号通知日志表不可用：' . $e->getMessage());
            return self::recordSkipped($userId, $scene, $data, $businessType, $businessId, $page, $options);
        }

        $template = self::findTemplate($scene, $audience);
        if (!$template) {
            return self::recordSkipped($userId, $scene, $data, $businessType, $businessId, $page, $options, '公众号模板未配置');
        }

        $follower = self::findFollowedFollower($userId);
        if (!$follower) {
            $bound = OaFollower::findByUserId($userId);
            return self::recordSkipped($userId, $scene, $data, $businessType, $businessId, $page, $options,
                $bound ? '用户未关注服务号' : '用户未绑定服务号');
        }

        try {
            $payload = self::buildPayload($template, $follower, $scene, $data, $businessId, $page);
            $log = OaNotificationLog::create([
                'user_id' => $userId,
                'openid' => (string) $follower->openid,
                'audience' => $audience,
                'scene' => $scene,
                'business_type' => $businessType ?: $scene,
                'business_id' => $businessId,
                'template_id' => (string) $template->template_id,
                'payload' => json_encode([
                    'source_data' => $data,
                    'payload' => $payload,
                    'page' => $page,
                    'options' => self::safeOptions($options),
                ], JSON_UNESCAPED_UNICODE),
                'dedupe_key' => $dedupeKey,
                'planned_send_time' => $plannedSendTime,
                'next_retry_time' => $plannedSendTime,
                'send_status' => OaNotificationLog::STATUS_PENDING,
                'create_time' => time(),
                'update_time' => time(),
            ]);
        } catch (\Throwable $e) {
            $existing = OaNotificationLog::findByDedupeKey($dedupeKey);
            if ($existing) {
                return self::formatExistingLog($existing);
            }
            Log::error('公众号通知入队失败：' . $e->getMessage());
            return self::recordSkipped($userId, $scene, $data, $businessType, $businessId, $page, $options, '公众号通知入队失败');
        }

        if ($plannedSendTime > time()) {
            return [
                'success' => true,
                'queued' => true,
                'sent' => false,
                'channel' => self::CHANNEL_OA,
                'msg' => '公众号通知已加入发送队列',
                'log_id' => (int) $log->id,
            ];
        }

        return ['success' => true, 'queued' => true, 'sent' => false,
            'channel' => self::CHANNEL_OA, 'msg' => '服务号通知已加入队列', 'log_id' => (int)$log->id];
    }

    /**
     * 给服务人员发送统一业务通知。
     */
    public static function sendStaffNotice(
        array $userIds,
        string $title,
        string $content,
        string $businessType = '',
        int $businessId = 0,
        string $page = '',
        array $options = []
    ): array {
        $results = ['total' => 0, 'success' => 0, 'failed' => 0, 'details' => []];
        $userIds = array_values(array_unique(array_filter(array_map('intval', $userIds))));
        $scene = trim((string) ($options['scene'] ?? '')) ?: self::resolveStaffScene($businessType, $title);
        $options['event_key'] = $options['event_key'] ?? hash('sha256', json_encode([$businessType, $businessId, $scene, $options['instance'] ?? '']));
        foreach ($userIds as $userId) {
            $results['total']++;
            BusinessNotificationService::record([
                'event' => $options['event'] ?? $scene, 'instance' => $options['instance'] ?? (string)$businessId,
                'user_id' => $userId, 'audience' => 'staff', 'title' => $title, 'content' => $content,
                'station' => false, 'scene' => $scene, 'business_type' => $businessType,
                'business_id' => $businessId, 'page' => $page, 'options' => $options,
            ]);
            $result = ['success' => true, 'queued' => true, 'sent' => false, 'msg' => '业务通知已记录'];
            $results['details'][$userId] = $result;
            ($result['success'] ?? false) ? $results['success']++ : $results['failed']++;
        }

        return $results;
    }

    protected static function resolveStaffScene(string $businessType, string $title): string
    {
        $businessType = strtolower(trim($businessType));
        if ($businessType === 'schedule') {
            return 'staff_schedule';
        }
        if ($businessType === 'pause') {
            return 'staff_pause';
        }
        if (in_array($businessType, ['refund', 'refund_apply'], true)) {
            return 'staff_refund';
        }
        if (in_array($businessType, ['ticket', 'aftersale'], true)) {
            return 'staff_aftersale';
        }
        if (in_array($businessType, ['change', 'schedule_change'], true)) {
            return 'staff_change';
        }
        return 'staff_order';
    }

    public static function dispatchLog(int $logId, bool $force = false): array
    {
        $claimed = false;
        $claimToken = bin2hex(random_bytes(16));
        try {
            $log = OaNotificationLog::find($logId);
            if (!$log) {
                return self::failure('公众号通知日志不存在', $logId);
            }

            if (!$force && (int) $log->planned_send_time > time()) {
                return ['success' => true, 'queued' => true, 'sent' => false, 'channel' => self::CHANNEL_OA, 'msg' => '等待计划发送时间', 'log_id' => $logId];
            }

            $now = time();
            $claimed = OaNotificationLog::where('id', $logId)
                ->where('planned_send_time', '<=', $now)
                ->where('next_retry_time', '<=', $now)
                ->where(function ($query) use ($now) {
                    $query->where('send_status', OaNotificationLog::STATUS_PENDING)
                        ->whereOr(function ($expired) use ($now) {
                            $expired->where('send_status', OaNotificationLog::STATUS_SENDING)->where('lock_until', '<=', $now);
                        });
                })
                ->update([
                    'send_status' => OaNotificationLog::STATUS_SENDING,
                    'lock_until' => time() + 120,
                    'lock_token' => $claimToken,
                    'update_time' => time(),
                ]);
            if (!$claimed) {
                return self::failure('公众号通知当前不可派发', $logId);
            }

            $log->lock_token = $claimToken;
            $follower = self::findFollowedFollower((int) $log->user_id);
            if ((int) ConfigService::get('oa_notification', 'enabled', 0) !== 1
                || !$follower || !hash_equals((string) $log->openid, (string) $follower->openid)
                || !self::findTemplate((string) $log->scene, (string) $log->audience)
                || !self::canReceiveBusiness($log)) {
                return self::handleFailure($log, 'NOT_ELIGIBLE', '接收人已取消关注、解除绑定、业务权限变化或模板已停用');
            }
            $payloadData = $log->payload;
            $template = self::findTemplate((string)$log->scene, (string)$log->audience);
            $payload = self::buildPayload($template, $follower, (string)$log->scene,
                (array)($payloadData['source_data'] ?? []), (int)$log->business_id, (string)($payloadData['page'] ?? ''));
            $result = (new WeChatOaService())->sendTemplateMessage($payload);
            $errCode = (int) ($result['errcode'] ?? -1);
            if ($errCode === 0) {
                OaNotificationLog::where('id', $logId)->where('lock_token', $claimToken)->update([
                    'send_status' => OaNotificationLog::STATUS_SUCCESS,
                    'request_id' => (string) ($result['msgid'] ?? $result['request_id'] ?? ''),
                    'send_time' => time(),
                    'lock_until' => 0,
                    'update_time' => time(),
                ]);
                return ['success' => true, 'queued' => false, 'sent' => true, 'channel' => self::CHANNEL_OA, 'msg' => '公众号通知发送成功', 'log_id' => $logId];
            }

            return self::handleFailure($log, (string) $errCode, (string) ($result['errmsg'] ?? '公众号通知发送失败'), (string) ($result['request_id'] ?? ''));
        } catch (\Throwable $e) {
            Log::error('公众号通知派发异常：' . $e->getMessage());
            return $claimed && isset($log) ? self::handleFailure($log, 'DISPATCH_ERROR', $e->getMessage()) : self::failure($e->getMessage(), $logId);
        }
    }

    public static function retryLog(int $id): bool
    {
        $log = OaNotificationLog::find($id);
        if (!$log || (int)$log->send_status !== OaNotificationLog::STATUS_FAILED || !self::canReceiveBusiness($log)) {
            return false;
        }
        $follower = self::findFollowedFollower((int)$log->user_id);
        if (!$follower || !self::findTemplate((string)$log->scene, (string)$log->audience)) {
            return false;
        }
        return OaNotificationLog::where('id', $id)->where('send_status', OaNotificationLog::STATUS_FAILED)
            ->update(['send_status' => OaNotificationLog::STATUS_PENDING, 'openid' => $follower->openid,
                'retry_count' => 0, 'next_retry_time' => time(), 'lock_until' => 0, 'lock_token' => '']) > 0;
    }

    public static function dispatchPendingLogs(int $limit = 100): array
    {
        $logs = OaNotificationLog::whereIn('send_status', [OaNotificationLog::STATUS_PENDING, OaNotificationLog::STATUS_SENDING])
            ->where('planned_send_time', '<=', time())
            ->where('next_retry_time', '<=', time())
            ->where(function ($query) {
                $query->where('lock_until', '<=', time())->whereOr('lock_until', 0);
            })
            ->order('id asc')
            ->limit(max(1, $limit))
            ->select();

        $result = ['processed' => 0, 'success' => 0, 'failed' => 0];
        foreach ($logs as $log) {
            $item = self::dispatchLog((int) $log->id, true);
            $result['processed']++;
            ($item['success'] ?? false) ? $result['success']++ : $result['failed']++;
        }

        return $result;
    }

    /** 在真正推送前按当前业务归属重新核对接收者。 */
    public static function canReceiveBusiness(OaNotificationLog $log): bool
    {
        $userId = (int)$log->user_id;
        if ($userId <= 0 || !\app\common\model\user\User::where('id', $userId)->where('is_disable', 0)->find()) {
            return false;
        }
        $id = (int)$log->business_id;
        $type = (string)$log->business_type;
        $staffAudience = (string)$log->audience === self::AUDIENCE_STAFF;
        if ($type === 'admin_test') {
            return true;
        }
        $options = (array)($log->payload['options'] ?? []);
        if (!empty($options['admin_ids'])
            && !in_array($userId, InternalNotificationService::userIds((array)$options['admin_ids']), true)) {
            return false;
        }
        if (in_array($type, ['crm_consult', 'crm_warning'], true)) {
            $customer = $type === 'crm_consult'
                ? \app\common\model\crm\Customer::find($id)
                : \app\common\model\crm\Customer::find((int)\app\common\model\crm\CustomerLossWarning::where('id', $id)->value('customer_id'));
            $advisor = $customer ? \app\common\model\crm\SalesAdvisor::find((int)$customer->advisor_id) : null;
            return $advisor && (int)$advisor->status !== \app\common\model\crm\SalesAdvisor::STATUS_LEAVE
                && in_array($userId, InternalNotificationService::userIds([(int)$advisor->admin_id]), true);
        }
        if ($type === 'staff_admin_opened') {
            return !empty($options['admin_ids']);
        }
        if ($type === 'order_receipt') {
            return $staffAudience && !empty($options['admin_ids'])
                && \think\facade\Db::name('order_receipt_request')->where('id', $id)->count() > 0;
        }
        if (in_array($type, ['ticket', 'aftersale'], true)) {
            $ticket = \app\common\model\aftersale\AfterSaleTicket::find($id);
            if (!$ticket) {
                return false;
            }
            if (!$staffAudience) {
                return (int)$ticket->user_id === $userId;
            }
            $admins = array_merge((array)ConfigService::get('customer_service', 'aftersale_admin_ids', []),
                [(int)$ticket->assign_admin_id]);
            return in_array($userId, InternalNotificationService::userIds($admins), true);
        }
        if ($type === 'waitlist') {
            return (int)\app\common\model\schedule\Waitlist::where('id', $id)->value('user_id') === $userId;
        }
        if ($type === 'activity') {
            return (int)\app\common\model\dynamic\ActivityRegistration::where('id', $id)->value('user_id') === $userId;
        }
        if ($type === 'settlement') {
            $staffId = (int)\app\common\model\financial\StaffSettlement::where('id', $id)->value('staff_id');
            return $staffId > 0 && StaffService::getStaffIdByUserId($userId) === $staffId;
        }
        if ($type === 'questionnaire') {
            $task = \think\facade\Db::name('couple_questionnaire_task')->where('id', $id)->find();
            if (!$task) return false;
            return (int)($task['user_id'] ?? 0) === $userId;
        }
        if ($type === 'refund') {
            $id = (int)\app\common\model\order\Refund::where('id', $id)->value('order_id');
        } elseif ($type === 'pause') {
            $id = (int)\app\common\model\order\OrderPause::where('id', $id)->value('order_id');
        } elseif ($type === 'change') {
            $id = (int)\app\common\model\order\OrderChange::where('id', $id)->value('order_id');
        } elseif (!in_array($type, ['order', 'schedule'], true)) {
            return false;
        }
        if (!$staffAudience) {
            return (int)\app\common\model\order\Order::where('id', $id)->value('user_id') === $userId;
        }
        $staffId = StaffService::getStaffIdByUserId($userId);
        if ($staffId <= 0) return false;
        $items = \app\common\model\order\OrderItem::where('order_id', $id)->where('staff_id', $staffId);
        if (empty($options['allow_cancelled'])) $items->where('item_status', '<>', \app\common\model\order\OrderItem::STATUS_CANCELLED);
        return $items->count() > 0;
    }

    protected static function findTemplate(string $scene, string $audience): ?OaNotificationTemplate
    {
        try {
            $template = OaNotificationTemplate::findEnabled($scene, $audience);
            if (!$template || trim((string) $template->template_id) === '') {
                return null;
            }
            return $template;
        } catch (\Throwable $e) {
            Log::warning('读取公众号模板失败：' . $e->getMessage());
            return null;
        }
    }

    protected static function findFollowedFollower(int $userId): ?OaFollower
    {
        try {
            return OaFollower::where('user_id', $userId)
                ->where('follow_status', OaFollower::STATUS_FOLLOWED)
                ->where('openid', '<>', '')
                ->find();
        } catch (\Throwable $e) {
            Log::warning('读取公众号关注状态失败：' . $e->getMessage());
            return null;
        }
    }

    protected static function buildPayload(
        OaNotificationTemplate $template,
        OaFollower $follower,
        string $scene,
        array $data,
        int $businessId,
        string $page
    ): array {
        if (empty($data) || !isset($data['staff_name']) || !isset($data['package_name'])) {
            if ($businessId > 0 && in_array($scene, ['order_update', 'order_create', 'order_created', 'order_confirm', 'order_confirmed'], true)) {
                $orderData = OrderNotificationService::resolveOrderNotificationData($businessId, (string) ($data['title'] ?? ''));
                if (!empty($orderData)) {
                    $data = array_merge($orderData, $data);
                }
            }
        }

        $mapping = $template->data_mapping;
        if (empty($mapping)) {
            foreach (array_keys($data) as $key) {
                $mapping[$key] = $key;
            }
        }

        $content = [];
        foreach ($mapping as $keyword => $dataKey) {
            $keyword = trim((string) $keyword);
            $dataKey = trim((string) $dataKey);
            if ($keyword === '' || $dataKey === '') {
                continue;
            }
            $value = $data[$dataKey] ?? $data[$keyword] ?? '';
            if (is_array($value)) {
                $value = implode('，', array_map(static fn ($item) => (string) $item, $value));
            }
            $value = self::formatValue($keyword, $value);
            if ($value !== '') {
                $content[$keyword] = ['value' => $value];
            }
        }

        if (empty($content)) {
            throw new \RuntimeException('公众号通知模板字段不能为空');
        }

        $pagePath = self::buildPagePath($page ?: (string) $template->page_path, $scene, $businessId, (string) $template->audience);
        $payload = [
            'touser' => (string) $follower->openid,
            'template_id' => (string) $template->template_id,
            'data' => $content,
        ];
        $mnpConfig = WeChatConfigService::getMnpConfig();
        $appid = trim((string) ($mnpConfig['app_id'] ?? ''));
        if ($appid !== '' && $pagePath !== '') {
            $payload['miniprogram'] = [
                'appid' => $appid,
                'pagepath' => $pagePath,
            ];
        }

        return $payload;
    }

    protected static function buildPagePath(string $page, string $scene, int $businessId, string $audience): string
    {
        $page = trim($page);
        if ($page === '') {
            $page = $audience === self::AUDIENCE_STAFF
                ? 'packages/pages/staff_order_detail/staff_order_detail'
                : match ($scene) {
                    OaNotificationTemplate::SCENE_TICKET_UPDATE => 'packages/pages/aftersale/ticket_detail',
                    OaNotificationTemplate::SCENE_WAITLIST_RELEASE,
                    OaNotificationTemplate::SCENE_WAITLIST_EXPIRED => 'packages/pages/waitlist/waitlist',
                    default => 'packages/pages/order_detail/order_detail',
                };
        }
        $page = ltrim($page, '/');
        if ($businessId > 0 && strpos($page, '?') === false) {
            $page .= '?id=' . $businessId;
        }
        return preg_match('#^[A-Za-z0-9_\-/\?=&.%]+$#', $page) ? $page : '';
    }

    protected static function handleFailure(OaNotificationLog $log, string $errorCode, string $errorMsg, string $requestId = ''): array
    {
        $retryCount = (int) ($log->retry_count ?? 0);
        if (self::isTransientError($errorCode) && $retryCount < self::MAX_RETRY_COUNT) {
            if (self::isAccessTokenError($errorCode)) {
                try {
                    (new WeChatOaService())->refreshAccessToken();
                } catch (\Throwable $e) {
                    Log::warning('公众号AccessToken刷新失败：' . $e->getMessage());
                }
            }
            $delayIndex = min($retryCount, count(self::RETRY_DELAYS) - 1);
            $delay = self::RETRY_DELAYS[$delayIndex];
            OaNotificationLog::where('id', (int) $log->id)->where('lock_token', (string)$log->lock_token)->update([
                'send_status' => OaNotificationLog::STATUS_PENDING,
                'retry_count' => $retryCount + 1,
                'next_retry_time' => time() + $delay,
                'last_error_code' => $errorCode,
                'error_msg' => $errorMsg,
                'request_id' => $requestId,
                'lock_until' => 0,
                'update_time' => time(),
            ]);
            return ['success' => false, 'queued' => true, 'sent' => false, 'channel' => self::CHANNEL_OA, 'msg' => '公众号通知失败，已进入重试队列：' . $errorMsg, 'log_id' => (int) $log->id];
        }

        OaNotificationLog::where('id', (int) $log->id)->where('lock_token', (string)$log->lock_token)->update([
            'send_status' => $errorCode === 'NOT_ELIGIBLE' ? OaNotificationLog::STATUS_SKIPPED : OaNotificationLog::STATUS_FAILED,
            'last_error_code' => $errorCode,
            'error_msg' => $errorMsg,
            'request_id' => $requestId,
            'lock_until' => 0,
            'update_time' => time(),
        ]);

        return self::failure($errorMsg, (int) $log->id);
    }

    protected static function recordSkipped(
        int $userId, string $scene, array $data, string $businessType,
        int $businessId, string $page, array $options, string $reason = ''
    ): array {
        $reason = $reason ?: '服务号通知未发送';
        try {
            $key = self::buildDedupeKey($userId, $scene, $businessType, $businessId,
                (string) ($options['audience'] ?? self::AUDIENCE_USER), $data, (int) ($options['planned_send_time'] ?? time()));
            $log = OaNotificationLog::findByDedupeKey($key);
            if (!$log) {
                $log = OaNotificationLog::create([
                    'user_id' => $userId, 'openid' => '', 'scene' => $scene,
                    'audience' => (string) ($options['audience'] ?? self::AUDIENCE_USER),
                    'business_type' => $businessType, 'business_id' => $businessId,
                    'template_id' => '', 'dedupe_key' => $key,
                    'payload' => json_encode(['source_data' => $data, 'page' => $page, 'options' => self::safeOptions($options)], JSON_UNESCAPED_UNICODE),
                    'send_status' => OaNotificationLog::STATUS_SKIPPED,
                    'error_msg' => $reason, 'last_error_code' => 'NOT_ELIGIBLE',
                    'planned_send_time' => time(), 'next_retry_time' => 0,
                ]);
            }
            return self::failure($reason, (int) $log->id);
        } catch (\Throwable $e) {
            Log::error('服务号通知跳过：' . $reason . '；记录失败：' . $e->getMessage());
            return self::failure($reason);
        }
    }

    protected static function formatExistingLog(OaNotificationLog $log): array
    {
        $status = (int) $log->send_status;
        if (in_array($status, [OaNotificationLog::STATUS_FAILED, OaNotificationLog::STATUS_SKIPPED], true)) {
            return [
                'success' => false,
                'queued' => false,
                'sent' => false,
                'channel' => self::CHANNEL_OA,
                'msg' => (string) ($log->error_msg ?: '公众号通知发送失败'),
                'log_id' => (int) $log->id,
            ];
        }

        return [
            'success' => in_array($status, [OaNotificationLog::STATUS_PENDING, OaNotificationLog::STATUS_SENDING, OaNotificationLog::STATUS_SUCCESS], true),
            'queued' => in_array($status, [OaNotificationLog::STATUS_PENDING, OaNotificationLog::STATUS_SENDING], true),
            'sent' => $status === OaNotificationLog::STATUS_SUCCESS,
            'channel' => self::CHANNEL_OA,
            'msg' => $status === OaNotificationLog::STATUS_SUCCESS ? '公众号通知已发送' : '公众号通知已在发送队列中',
            'log_id' => (int) $log->id,
        ];
    }

    protected static function buildDedupeKey(int $userId, string $scene, string $businessType, int $businessId, string $audience, array $data, int $plannedSendTime): string
    {
        if (!empty($data['event_key'])) return hash('sha256', $data['event_key'] . ':oa:' . $userId . ':' . $audience);
        $businessPart = $businessId > 0 ? (string) $businessId : substr(sha1(json_encode($data, JSON_UNESCAPED_UNICODE)), 0, 16);
        $key = implode(':', [$audience, $scene, $userId, $businessType ?: $scene, $businessPart, substr(hash('sha256', json_encode($data, JSON_UNESCAPED_UNICODE)), 0, 16), date('Ymd', $plannedSendTime)]);
        return strlen($key) <= 128 ? $key : substr($key, 0, 80) . ':' . sha1($key);
    }

    protected static function formatValue(string $key, $value): string
    {
        $text = trim((string) $value);
        if ($text === '') {
            return '';
        }
        $type = preg_replace('/\d+$/', '', $key) ?: '';
        if ($type === 'amount') {
            $num = preg_replace('/[^\d.]/', '', $text);
            if (is_numeric($num)) {
                $text = number_format((float) $num, 2, '.', '');
            }
        }
        if (in_array($type, ['time', 'date'], true) && preg_match('/^\d{10}$/', $text)) {
            $text = date($type === 'date' ? 'Y-m-d' : 'Y-m-d H:i', (int) $text);
        }
        if ($type === 'time' && preg_match('/^\d{4}[-\/]\d{1,2}[-\/]\d{1,2}$/', $text)) {
            $text .= ' 09:00';
        }
        if ($type === 'thing') {
            $text = str_replace(["\r", "\n"], ' ', $text);
        }
        if ($type === 'character_string') {
            $text = preg_replace('/[^A-Za-z0-9_\-.\/]+/', '', $text) ?: 'UNKNOWN';
        }
        if ($type === 'phrase') {
            // 常量枚举字段（微信严格限制≤5个汉字，且必须与服务号后台配置的枚举项精确匹配）
            if (mb_strpos($text, '通过') !== false) {
                $text = '审核通过';
            } elseif (mb_strpos($text, '驳回') !== false) {
                $text = '审核驳回';
            } elseif (mb_strpos($text, '拒绝') !== false) {
                $text = '审核拒绝';
            } elseif (mb_strpos($text, '取消') !== false) {
                $text = '已取消';
            } elseif (mb_strpos($text, '主持') !== false) {
                $text = '主持人';
            } elseif (mb_strpos($text, '摄像') !== false) {
                $text = '摄像';
            } elseif (mb_strpos($text, '摄影') !== false) {
                $text = '摄影';
            } elseif (mb_strpos($text, '化妆') !== false) {
                $text = '化妆';
            } elseif (mb_strpos($text, '策划') !== false) {
                $text = '策划';
            } elseif (mb_strpos($text, '全天') !== false) {
                $text = '全天';
            } elseif (mb_strpos($text, '定制') !== false) {
                $text = '定制';
            } elseif (mb_strpos($text, '完成') !== false) {
                $text = '处理完成';
            }
        }
        $limit = ['thing' => 20, 'phrase' => 5, 'character_string' => 32, 'name' => 10][$type] ?? 200;
        return mb_substr($text, 0, $limit, 'UTF-8');
    }

    protected static function isTransientError(string $errorCode): bool
    {
        if ($errorCode === 'DISPATCH_ERROR') {
            return true;
        }
        $code = (int) $errorCode;
        return in_array($code, [-1, 40001, 40014, 42001, 45009, 45011, 50001, 50002], true) || $code >= 50000;
    }

    protected static function isAccessTokenError(string $errorCode): bool
    {
        return in_array((int) $errorCode, [40001, 40014, 42001], true);
    }

    protected static function safeOptions(array $options): array
    {
        unset($options['secret'], $options['password'], $options['token']);
        return $options;
    }

    protected static function failure(string $message, int $logId = 0): array
    {
        return ['success' => false, 'queued' => false, 'sent' => false, 'channel' => self::CHANNEL_OA, 'msg' => $message, 'log_id' => $logId];
    }
}
