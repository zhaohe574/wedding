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
        if (isset($event['options'])) $event['options'] = array_intersect_key($event['options'],
            array_flip(['admin_ids', 'allow_cancelled', 'planned_send_time']));
        foreach (['event', 'instance', 'audience'] as $field) {
            if (empty($event[$field])) throw new \InvalidArgumentException('通知缺少字段：' . $field);
        }
        $key = hash('sha256', implode(':', [$event['event'], $event['instance'], $event['user_id'], $event['audience']]));
        if (Db::name('notification_event')->where('event_key', $key)->find()) return true;
        try {
            Db::name('notification_event')->insert([
                'event_key' => $key, 'user_id' => $event['user_id'], 'audience' => $event['audience'],
                'payload' => json_encode($event, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
                'status' => 0, 'create_time' => time(), 'update_time' => time(),
            ]);
        } catch (\Throwable $e) {
            // 唯一键竞争后使用当前读，避免事务快照看不到另一事务刚提交的事件。
            if (!Db::name('notification_event')->where('event_key', $key)->lock(true)->find()) throw $e;
        }
        return true;
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
                        $result = WechatNotificationService::sendScene($userId, $event['scene'],
                            ['event_key' => $row['event_key']] + $eventData + ['title' => $event['title'], 'content' => $event['content'], 'status_text' => $event['title'],
                                'remark_text' => $event['content']],
                            $event['business_type'], (int)$event['business_id'],
                            $event['audience'] === 'user' ? 'user' : 'staff', $event['page'] ?? '',
                            $event['options'] ?? []);
                    $error = !empty($result['success']) ? '' : (string)($result['msg'] ?? '服务号任务未生成');
                    if (empty($result['success']) && empty($result['log_id'])) throw new \RuntimeException($error);
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
