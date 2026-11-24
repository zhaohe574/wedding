<?php
declare(strict_types=1);

namespace app\common\service;

use app\common\model\notification\Notification;
use think\facade\Db;

/** 业务事件持久化后由任务投递，事务回滚时事件一起回滚。 */
class BusinessNotificationService
{
    public static function record(array $event): bool
    {
        $res = self::recordWithStatus($event);
        return !empty($res['recorded']) || !empty($res['duplicate']);
    }

    /**
     * @notes 记录业务事件并返回入队/去重详情
     */
    public static function recordWithStatus(array $event): array
    {
        if (isset($event['options'])) {
            $event['options'] = array_intersect_key(
                $event['options'],
                array_flip(['admin_ids', 'allow_cancelled', 'planned_send_time'])
            );
        }
        foreach (['event', 'instance', 'audience'] as $field) {
            if (empty($event[$field])) {
                throw new \InvalidArgumentException('通知缺少字段：' . $field);
            }
        }
        $key = hash('sha256', implode(':', [$event['event'], $event['instance'], $event['user_id'], $event['audience']]));
        if (Db::name('notification_event')->where('event_key', $key)->find()) {
            return ['recorded' => false, 'duplicate' => true, 'event_key' => $key];
        }
        try {
            Db::name('notification_event')->insert([
                'event_key' => $key,
                'user_id' => $event['user_id'],
                'audience' => $event['audience'],
                'payload' => json_encode($event, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
                'status' => 0,
                'create_time' => time(),
                'update_time' => time(),
            ]);
        } catch (\Throwable $e) {
            // 唯一键竞争后使用当前读，避免事务快照看不到另一事务刚提交的事件。
            if (!Db::name('notification_event')->where('event_key', $key)->lock(true)->find()) {
                throw $e;
            }
            return ['recorded' => false, 'duplicate' => true, 'event_key' => $key];
        }
        return ['recorded' => true, 'duplicate' => false, 'event_key' => $key];
    }

    /**
     * @notes 即时派发单条业务通知事件（站内信 + 可选同步触发公众号模板发送）
     */
    public static function dispatchByEventKey(string $eventKey, bool $forceOaDispatch = false): array
    {
        $summary = [
            'ok' => false,
            'station_sent' => false,
            'oa_sent' => false,
            'oa_queued' => false,
            'oa_status' => 'skipped',
            'oa_msg' => '',
            'error' => '',
        ];
        if ($eventKey === '') {
            $summary['error'] = '缺少通知事件标识';
            return $summary;
        }

        // 事务外续用的状态：公众号发送日志ID、事件快照、接收用户与发送数据。
        $oaLogId = 0;
        $eventSnapshot = [];
        $userId = 0;
        $sendData = [];

        try {
            Db::transaction(function () use ($eventKey, &$summary, &$oaLogId, &$eventSnapshot, &$userId, &$sendData) {
                $row = Db::name('notification_event')->where('event_key', $eventKey)->lock(true)->find();
                if (!$row) {
                    $summary['error'] = '通知事件不存在';
                    return;
                }
                if ((int)$row['status'] === 1) {
                    $summary['ok'] = true;
                    $summary['station_sent'] = true;
                    $summary['oa_msg'] = '通知已派发';
                    return;
                }

                $event = json_decode($row['payload'], true, 512, JSON_THROW_ON_ERROR);
                $userId = (int)$row['user_id'];
                if (!self::canReceive($event)) {
                    $msg = '接收账号缺失、已停用或业务权限已变更';
                    Db::name('notification_event')->where('id', (int)$row['id'])->update([
                        'status' => 2,
                        'error' => $msg,
                        'update_time' => time(),
                    ]);
                    $summary['error'] = $msg;
                    return;
                }
                $eventSnapshot = $event;

                if (($event['station'] ?? true) && !Notification::where('event_key', $row['event_key'])->find()) {
                    Notification::create([
                        'event_key' => $row['event_key'],
                        'user_id' => $userId,
                        'audience' => $event['audience'],
                        'sender_id' => (int)($event['sender_id'] ?? 0),
                        'notify_type' => (int)($event['notify_type'] ?? 2),
                        'title' => $event['title'],
                        'content' => $event['content'],
                        'target_type' => $event['target_type'] ?? '',
                        'target_id' => $event['target_id'] ?? 0,
                        'business_type' => $event['business_type'] ?? '',
                        'business_id' => $event['business_id'] ?? 0,
                        'access_options' => json_encode($event['options'] ?? []),
                        'create_time' => time(),
                    ]);
                }
                $summary['station_sent'] = true;
                $summary['ok'] = true;

                $oaError = '';
                $markRetryable = false;
                if (!empty($event['scene'])) {
                    try {
                        $eventData = (array)($event['data'] ?? []);
                        if (empty($eventData) && ($event['business_type'] ?? '') === 'order' && (int)($event['business_id'] ?? 0) > 0) {
                            $eventData = \app\common\service\OrderNotificationService::resolveOrderNotificationData((int)$event['business_id'], (string)($event['title'] ?? ''));
                        }
                        $sendData = ['event_key' => $row['event_key']] + $eventData + [
                            'title' => $event['title'],
                            'content' => $event['content'],
                            'status_text' => $event['title'],
                            'remark_text' => $event['content'],
                        ];
                        // 注意：持有数据库行锁期间严禁传入 force_dispatch 发起外部微信 HTTP 请求，
                        // 强制同步派发在事务提交后统一执行（见下方 dispatchLog）。
                        $result = WechatNotificationService::sendScene(
                            $userId,
                            $event['scene'],
                            $sendData,
                            $event['business_type'] ?? '',
                            (int)($event['business_id'] ?? 0),
                            $event['audience'] === 'user' ? 'user' : 'staff',
                            $event['page'] ?? '',
                            (array)($event['options'] ?? [])
                        );
                        $oaLogId = (int)($result['log_id'] ?? 0);
                        $summary['oa_sent'] = !empty($result['sent']);
                        $summary['oa_queued'] = !empty($result['queued']);
                        $summary['oa_msg'] = (string)($result['msg'] ?? '');
                        if ($summary['oa_sent']) {
                            $summary['oa_status'] = 'sent';
                        } elseif ($summary['oa_queued']) {
                            $summary['oa_status'] = 'queued';
                        } elseif ($oaLogId > 0) {
                            $summary['oa_status'] = 'skipped';
                            $oaError = $summary['oa_msg'];
                        } else {
                            $summary['oa_status'] = 'failed';
                            $oaError = $summary['oa_msg'] ?: '服务号发送失败';
                        }
                        // 未生成发送日志（无 success 且无 log_id）视为可重试，交由重试队列再次派发。
                        $markRetryable = empty($result['success']) && $oaLogId <= 0;
                    } catch (\Throwable $oaEx) {
                        $summary['oa_status'] = 'failed';
                        $summary['oa_msg'] = $oaEx->getMessage();
                        $oaError = $summary['oa_msg'];
                        $markRetryable = true;
                    }
                }

                if ($markRetryable) {
                    Db::name('notification_event')->where('id', (int)$row['id'])->update([
                        'status' => 3,
                        'error' => mb_substr($oaError, 0, 500),
                        'retry_count' => (int)$row['retry_count'] + 1,
                        'next_retry_time' => time() + 60,
                        'update_time' => time(),
                    ]);
                    return;
                }

                Db::name('notification_event')->where('id', (int)$row['id'])->update([
                    'status' => 1,
                    'error' => mb_substr($oaError, 0, 500),
                    'update_time' => time(),
                ]);
            });
        } catch (\Throwable $e) {
            $summary['error'] = $e->getMessage();
        }

        // 事务提交成功后再同步派发公众号请求，避免持有行锁期间发起外部 HTTP 调用。
        if (empty($summary['error']) && $forceOaDispatch && $oaLogId > 0 && $summary['oa_queued']) {
            try {
                $dispatchResult = WechatNotificationService::dispatchLog($oaLogId, true);
                $summary['oa_sent'] = !empty($dispatchResult['sent']);
                $summary['oa_queued'] = !empty($dispatchResult['queued']);
                $summary['oa_status'] = $summary['oa_sent'] ? 'sent' : ($summary['oa_queued'] ? 'queued' : 'failed');
                $summary['oa_msg'] = (string)($dispatchResult['msg'] ?? '');
                if (!$summary['oa_sent'] && !$summary['oa_queued']) {
                    // 同步派发失败时把原因写回事件表，便于后台排查。
                    try {
                        Db::name('notification_event')->where('event_key', $eventKey)->update([
                            'error' => mb_substr($summary['oa_msg'], 0, 500),
                            'update_time' => time(),
                        ]);
                    } catch (\Throwable $ignore) {
                    }
                }
            } catch (\Throwable $e) {
                $summary['oa_status'] = 'failed';
                $summary['oa_msg'] = $e->getMessage();
            }
        }

        // 与 dispatch() 行为对齐：用户侧事件同步触发小程序订阅消息（如已配置并启用）。
        if (empty($summary['error']) && !empty($eventSnapshot['scene']) && ($eventSnapshot['audience'] ?? 'user') === 'user') {
            try {
                \app\common\service\WechatMnpNotificationService::sendScene(
                    $userId,
                    $eventSnapshot['scene'],
                    $sendData,
                    $eventSnapshot['business_type'] ?? '',
                    (int)($eventSnapshot['business_id'] ?? 0),
                    $eventSnapshot['page'] ?? '',
                    $eventSnapshot['options'] ?? []
                );
            } catch (\Throwable $mnpE) {
                \think\facade\Log::error('MNP subscribe dispatch error: ' . $mnpE->getMessage());
            }
        }

        return $summary;
    }

    public static function dispatch(int $limit = 100): int
    {
        $ids = Db::name('notification_event')->whereIn('status', [0, 3])->where('retry_count', '<', 3)
            ->where('next_retry_time', '<=', time())->order('id')->limit($limit)->column('id');
        foreach ($ids as $id) {
            try {
            Db::transaction(function () use ($id) {
                $row = Db::name('notification_event')->where('id', $id)->lock(true)->find();
                if (!$row || !in_array((int)$row['status'], [0, 3], true)) return;
                $event = json_decode($row['payload'], true, 512, JSON_THROW_ON_ERROR);
                $userId = (int)$row['user_id'];
                if (!self::canReceive($event)) {
                    Db::name('notification_event')->where('id', $id)->update(['status' => 2, 'error' => '接收账号缺失、已停用或业务权限已变更', 'update_time' => time()]);
                    return;
                }
                if (($event['station'] ?? true) && !Notification::where('event_key', $row['event_key'])->find()) {
                    Notification::create([
                        'event_key' => $row['event_key'], 'user_id' => $userId, 'audience' => $event['audience'],
                        'sender_id' => (int)($event['sender_id'] ?? 0), 'notify_type' => (int)($event['notify_type'] ?? 2),
                        'title' => $event['title'], 'content' => $event['content'],
                        'target_type' => $event['target_type'] ?? '', 'target_id' => $event['target_id'] ?? 0,
                        'business_type' => $event['business_type'] ?? '', 'business_id' => $event['business_id'] ?? 0,
                        'access_options' => json_encode($event['options'] ?? []),
                        'create_time' => time(),
                    ]);
                }
                if (!empty($event['scene'])) {
                    try {
                        $eventData = (array)($event['data'] ?? []);
                        if (empty($eventData) && ($event['business_type'] ?? '') === 'order' && (int)($event['business_id'] ?? 0) > 0) {
                            $eventData = \app\common\service\OrderNotificationService::resolveOrderNotificationData((int)$event['business_id'], (string)($event['title'] ?? ''));
                        }
                        $sendData = ['event_key' => $row['event_key']] + $eventData + [
                            'title' => $event['title'],
                            'content' => $event['content'],
                            'status_text' => $event['title'],
                            'remark_text' => $event['content'],
                        ];
                        $result = WechatNotificationService::sendScene(
                            $userId,
                            $event['scene'],
                            $sendData,
                            $event['business_type'] ?? '',
                            (int)($event['business_id'] ?? 0),
                            $event['audience'] === 'user' ? 'user' : 'staff',
                            $event['page'] ?? '',
                            $event['options'] ?? []
                        );
                        $error = !empty($result['success']) ? '' : (string)($result['msg'] ?? '服务号任务未生成');
                        if (empty($result['success']) && empty($result['log_id'])) throw new \RuntimeException($error);

                        // 同步触发小程序订阅消息（如已配置并启用）
                        if (($event['audience'] ?? 'user') === 'user') {
                            try {
                                WechatMnpNotificationService::sendScene(
                                    $userId,
                                    $event['scene'],
                                    $sendData,
                                    $event['business_type'] ?? '',
                                    (int)($event['business_id'] ?? 0),
                                    $event['page'] ?? '',
                                    $event['options'] ?? []
                                );
                            } catch (\Throwable $mnpE) {
                                \think\facade\Log::error('MNP subscribe dispatch error: ' . $mnpE->getMessage());
                            }
                        }
                    } catch (\Throwable $e) {
                        Db::name('notification_event')->where('id', $id)->update(['status' => 3, 'error' => mb_substr($e->getMessage(), 0, 500),
                            'retry_count' => (int)$row['retry_count'] + 1, 'next_retry_time' => time() + 60, 'update_time' => time()]);
                        return;
                    }
                }
                Db::name('notification_event')->where('id', $id)->update(['status' => 1, 'error' => $error ?? '', 'update_time' => time()]);
            });
            } catch (\Throwable $e) {
                Db::name('notification_event')->where('id', $id)->inc('retry_count')->update(['status' => 3,
                    'error' => mb_substr($e->getMessage(), 0, 500), 'next_retry_time' => time() + 60, 'update_time' => time()]);
            }
        }
        return count($ids);
    }

    public static function canReceive(array $event): bool
    {
        $userId = (int)$event['user_id'];
        if (!\app\common\model\user\User::where('id', $userId)->where('is_disable', 0)->find()) return false;
        if (($event['audience'] ?? 'user') === 'user' && empty($event['business_type'])) return true;
        if (empty($event['business_type'])) {
            return StaffService::getStaffIdByUserId($userId) > 0;
        }
        $log = new \app\common\model\wechat\OaNotificationLog([
            'user_id' => $userId, 'audience' => $event['audience'] === 'user' ? 'user' : 'staff',
            'business_type' => $event['business_type'], 'business_id' => $event['business_id'],
            'payload' => json_encode(['options' => $event['options'] ?? []]),
        ]);
        return WechatNotificationService::canReceiveBusiness($log);
    }

    public static function retry(int $id): bool
    {
        return Db::transaction(static function () use ($id) {
            $row = Db::name('notification_event')->where('id', $id)->where('status', 3)->lock(true)->find();
            if (!$row || !self::canReceive(json_decode($row['payload'], true) ?: [])) return false;
            return Db::name('notification_event')->where('id', $id)->update([
                'status' => 0, 'retry_count' => 0, 'next_retry_time' => 0, 'error' => '', 'update_time' => time(),
            ]) > 0;
        });
    }
}
