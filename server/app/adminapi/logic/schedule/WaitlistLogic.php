<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 候补业务逻辑
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\adminapi\logic\schedule;

use app\common\logic\BaseLogic;
use app\common\model\schedule\Waitlist;

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

        $count = Waitlist::whereIn('id', $ids)
            ->where('notify_status', Waitlist::NOTIFY_STATUS_PENDING)
            ->update([
                'notify_status' => Waitlist::NOTIFY_STATUS_NOTIFIED,
                'notify_time' => time(),
                'update_time' => time(),
            ]);

        return $count;
    }

    /**
     * @notes 通知单个候补
     * @param array $params
     * @return bool
     */
    public static function notify(array $params): bool
    {
        $waitlist = Waitlist::where('id', $params['id'])->find();

        if (!$waitlist) {
            self::setError('候补记录不存在');
            return false;
        }

        if ($waitlist->notify_status != Waitlist::NOTIFY_STATUS_PENDING) {
            self::setError('只能通知等待中的候补');
            return false;
        }

        $waitlist->notify_status = Waitlist::NOTIFY_STATUS_NOTIFIED;
        $waitlist->notify_time = time();
        $waitlist->update_time = time();
        $waitlist->save();

        return true;
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
    public static function statistics(): array
    {
        $total = \app\common\model\schedule\Waitlist::count();

        $waiting = \app\common\model\schedule\Waitlist::where('notify_status', \app\common\model\schedule\Waitlist::NOTIFY_STATUS_PENDING)->count();

        $notified = \app\common\model\schedule\Waitlist::where('notify_status', \app\common\model\schedule\Waitlist::NOTIFY_STATUS_NOTIFIED)->count();

        $ordered = \app\common\model\schedule\Waitlist::where('notify_status', \app\common\model\schedule\Waitlist::NOTIFY_STATUS_ORDERED)->count();

        $expired = \app\common\model\schedule\Waitlist::where('notify_status', \app\common\model\schedule\Waitlist::NOTIFY_STATUS_EXPIRED)->count();

        return [
            'total' => $total,
            'waiting' => $waiting,
            'notified' => $notified,
            'converted' => $ordered,
            'expired' => $expired,
        ];
    }
}
