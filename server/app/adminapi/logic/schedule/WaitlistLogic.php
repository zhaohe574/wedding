<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 候补业务逻辑
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\adminapi\logic\schedule;

use app\common\logic\BaseLogic;
use app\common\model\notification\Notification;
use app\common\model\schedule\Schedule;
use app\common\model\schedule\Waitlist;
use app\common\service\StationNotificationService;
use think\facade\Db;

/**
 * 候补业务逻辑
 * Class WaitlistLogic
 * @package app\adminapi\logic\schedule
 */
class WaitlistLogic extends BaseLogic
{
    /**
     * @notes 候补详情
     * @param int $id
     * @return array|null
     */
    public static function detail(int $id): ?array
    {
        $waitlist = Waitlist::where('id', $id)
            ->with(['user', 'staff', 'package'])
            ->find();

        if (!$waitlist) {
            return null;
        }

        return $waitlist->toArray();
    }

    /**
     * @notes 批量通知
     * @param array $params
     * @return int|false
     */
    public static function batchNotify(array $params)
    {
        $ids = $params['ids'] ?? [];

        if (empty($ids)) {
            self::setError('请选择要通知的候补记录');
            return false;
        }

        Db::startTrans();
        try {
            $waitlists = Waitlist::whereIn('id', $ids)
                ->where('notify_status', Waitlist::NOTIFY_STATUS_PENDING)
                ->with([
                    'staff' => function ($q) {
                        $q->field('id, name');
                    },
                    'package' => function ($q) {
                        $q->field('id, name');
                    },
                ])
                ->order('create_time', 'asc')
                ->order('id', 'asc')
                ->select();

            if ($waitlists->isEmpty()) {
                self::setError('没有可通知的候补记录');
                Db::rollback();
                return false;
            }

            $notifiedWaitlists = [];
            $processedKeys = [];
            foreach ($waitlists as $waitlist) {
                $releaseKey = $waitlist->staff_id . '|' . $waitlist->schedule_date;
                if (isset($processedKeys[$releaseKey])) {
                    continue;
                }

                [$canNotify] = Waitlist::canNotifyWaitlist($waitlist);
                if (!$canNotify) {
                    continue;
                }

                if (Waitlist::markWaitlistAsNotified($waitlist)) {
                    $processedKeys[$releaseKey] = true;
                    $notifiedWaitlists[] = $waitlist;
                }
            }

            $count = count($notifiedWaitlists);
            if ($count === 0) {
                self::setError('当前档期未释放或存在更早候补，无法执行通知');
                Db::rollback();
                return false;
            }

            Db::commit();

            foreach ($notifiedWaitlists as $waitlist) {
                self::sendWaitlistNotification($waitlist);
            }

            return $count;
        } catch (\Exception $e) {
            Db::rollback();
            self::setError('通知失败：' . $e->getMessage());
            return false;
        }
    }

    /**
     * @notes 通知单个候补
     * @param array $params
     * @return bool
     */
    public static function notify(array $params): bool
    {
        $waitlist = Waitlist::where('id', $params['id'])
            ->with([
                'staff' => function ($q) {
                    $q->field('id, name');
                },
                'package' => function ($q) {
                    $q->field('id, name');
                },
            ])
            ->find();

        if (!$waitlist) {
            self::setError('候补记录不存在');
            return false;
        }

        if ($waitlist->notify_status != Waitlist::NOTIFY_STATUS_PENDING) {
            self::setError('只能通知等待中的候补');
            return false;
        }

        [$canNotify, $message] = Waitlist::canNotifyWaitlist($waitlist);
        if (!$canNotify) {
            self::setError($message);
            return false;
        }

        Db::startTrans();
        try {
            if (!Waitlist::markWaitlistAsNotified($waitlist)) {
                Db::rollback();
                self::setError('通知状态更新失败');
                return false;
            }

            Db::commit();

            self::sendWaitlistNotification($waitlist);
            return true;
        } catch (\Exception $e) {
            Db::rollback();
            self::setError('通知失败：' . $e->getMessage());
            return false;
        }
    }

    /**
     * @notes 发送候补站内通知
     * @param Waitlist $waitlist
     * @return void
     */
    private static function sendWaitlistNotification(Waitlist $waitlist): void
    {
        Waitlist::dispatchReleaseNotifications($waitlist);
    }

    /**
     * @notes 转正预约
     * @param array $params
     * @return bool
     */
    public static function convert(array $params): bool
    {
        $waitlist = Waitlist::where('id', $params['id'])->find();

        if (!$waitlist) {
            self::setError('候补记录不存在');
            return false;
        }

        if ($waitlist->notify_status != Waitlist::NOTIFY_STATUS_NOTIFIED) {
            self::setError('只能转正已通知的候补');
            return false;
        }

        if (strtotime((string)$waitlist->schedule_date) < strtotime(date('Y-m-d'))) {
            self::setError('候补日期已过，不能转正');
            return false;
        }

        Db::startTrans();
        try {
            $schedule = Schedule::where('staff_id', (int)$waitlist->staff_id)
                ->where('schedule_date', (string)$waitlist->schedule_date)
                ->where('time_slot', Schedule::TIME_SLOT_ALL)
                ->find();

            if ($schedule && (int)$schedule->status === Schedule::STATUS_LOCKED && (int)$schedule->lock_expire_time > 0 && (int)$schedule->lock_expire_time < time()) {
                Schedule::releaseLock((int)$schedule->id);
                $schedule = Schedule::where('id', (int)$schedule->id)->find();
            }

            if ($schedule) {
                if ((int)$schedule->status === Schedule::STATUS_BOOKED) {
                    Db::rollback();
                    self::setError('当前档期已被预约，无法转正');
                    return false;
                }

                if ((int)$schedule->status === Schedule::STATUS_RESERVED) {
                    Db::rollback();
                    self::setError('当前档期已被内部预留，无法转正');
                    return false;
                }

                if (
                    (int)$schedule->status === Schedule::STATUS_LOCKED
                    && (int)$schedule->lock_user_id > 0
                    && (int)$schedule->lock_user_id !== (int)$waitlist->user_id
                    && (int)$schedule->lock_expire_time > time()
                ) {
                    Db::rollback();
                    self::setError('当前档期已被其他用户锁定，无法转正');
                    return false;
                }

                $schedule->status = Schedule::STATUS_RESERVED;
                $schedule->lock_type = Schedule::LOCK_TYPE_INTERNAL;
                $schedule->lock_user_id = 0;
                $schedule->lock_expire_time = 0;
                $schedule->lock_reason = '候补转正占位';
                $schedule->update_time = time();
                $schedule->save();
            } else {
                Schedule::create([
                    'staff_id' => (int)$waitlist->staff_id,
                    'schedule_date' => (string)$waitlist->schedule_date,
                    'time_slot' => Schedule::TIME_SLOT_ALL,
                    'status' => Schedule::STATUS_RESERVED,
                    'lock_type' => Schedule::LOCK_TYPE_INTERNAL,
                    'lock_user_id' => 0,
                    'lock_expire_time' => 0,
                    'lock_reason' => '候补转正占位',
                    'version' => 1,
                    'create_time' => time(),
                    'update_time' => time(),
                ]);
            }

            $waitlist->notify_status = Waitlist::NOTIFY_STATUS_ORDERED;
            $waitlist->expire_time = 0;
            $waitlist->update_time = time();
            $waitlist->save();

            Db::commit();
            return true;
        } catch (\Throwable $e) {
            Db::rollback();
            self::setError('转正失败：' . $e->getMessage());
            return false;
        }
    }

    /**
     * @notes 标记失效
     * @param array $params
     * @return bool
     */
    public static function invalidate(array $params): bool
    {
        $waitlist = Waitlist::where('id', $params['id'])->find();

        if (!$waitlist) {
            self::setError('候补记录不存在');
            return false;
        }

        if ($waitlist->notify_status == Waitlist::NOTIFY_STATUS_ORDERED) {
            self::setError('已下单的候补不能标记为失效');
            return false;
        }

        if ((int)$waitlist->notify_status === Waitlist::NOTIFY_STATUS_EXPIRED) {
            self::setError('该候补已是失效状态');
            return false;
        }

        $waitlist->notify_status = Waitlist::NOTIFY_STATUS_EXPIRED;
        $waitlist->expire_time = max((int)$waitlist->expire_time, time());
        $waitlist->update_time = time();
        $waitlist->save();

        return true;
    }

    /**
     * @notes 候补统计
     * @return array
     */
    public static function statistics(array $params = []): array
    {
        $staffId = (int)($params['staff_id'] ?? 0);
        $baseQuery = \app\common\model\schedule\Waitlist::where([]);
        if ($staffId > 0) {
            $baseQuery->where('staff_id', $staffId);
        }

        $total = (clone $baseQuery)->count();
        $waiting = (clone $baseQuery)->where('notify_status', \app\common\model\schedule\Waitlist::NOTIFY_STATUS_PENDING)->count();
        $notified = (clone $baseQuery)->where('notify_status', \app\common\model\schedule\Waitlist::NOTIFY_STATUS_NOTIFIED)->count();
        $ordered = (clone $baseQuery)->where('notify_status', \app\common\model\schedule\Waitlist::NOTIFY_STATUS_ORDERED)->count();
        $expired = (clone $baseQuery)->where('notify_status', \app\common\model\schedule\Waitlist::NOTIFY_STATUS_EXPIRED)->count();

        return [
            'total' => $total,
            'waiting' => $waiting,
            'notified' => $notified,
            'converted' => $ordered,
            'expired' => $expired,
        ];
    }
}
