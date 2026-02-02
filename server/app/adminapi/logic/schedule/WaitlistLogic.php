<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 候补业务逻辑
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\adminapi\logic\schedule;

use app\common\logic\BaseLogic;
use app\common\model\notification\Notification;
use app\common\model\schedule\Waitlist;
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
                ->select();

            if ($waitlists->isEmpty()) {
                self::setError('没有可通知的候补记录');
                Db::rollback();
                return false;
            }

            $now = time();
            $waitlistIds = [];
            foreach ($waitlists as $waitlist) {
                $waitlistIds[] = $waitlist->id;
            }

            $count = Waitlist::whereIn('id', $waitlistIds)
                ->update([
                    'notify_status' => Waitlist::NOTIFY_STATUS_NOTIFIED,
                    'notify_time' => $now,
                    'update_time' => $now,
                ]);

            foreach ($waitlists as $waitlist) {
                self::sendWaitlistNotification($waitlist);
            }

            Db::commit();
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

        Db::startTrans();
        try {
            $now = time();
            $waitlist->notify_status = Waitlist::NOTIFY_STATUS_NOTIFIED;
            $waitlist->notify_time = $now;
            $waitlist->update_time = $now;
            $waitlist->save();

            self::sendWaitlistNotification($waitlist);

            Db::commit();
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
        $staffName = $waitlist->staff->name ?? '服务人员';
        $timeSlotDesc = $waitlist->time_slot_desc ?? '全天';
        $scheduleDate = $waitlist->schedule_date ?? '';
        $packageName = $waitlist->package->name ?? '';
        $packageText = $packageName ? "，套餐：{$packageName}" : '';

        $title = '候补档期已释放';
        $content = "您候补的{$staffName}档期（{$scheduleDate} {$timeSlotDesc}{$packageText}）已释放，请尽快预约。";

        Notification::send(
            (int) $waitlist->user_id,
            Notification::TYPE_ORDER,
            $title,
            $content,
            'waitlist',
            (int) $waitlist->id
        );
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

        $waitlist->notify_status = Waitlist::NOTIFY_STATUS_ORDERED;
        $waitlist->update_time = time();
        $waitlist->save();

        return true;
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

        $waitlist->notify_status = Waitlist::NOTIFY_STATUS_EXPIRED;
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
