<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 候补订单模型
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\common\model\schedule;

use app\common\model\BaseModel;
use app\common\model\staff\Staff;
use app\common\model\service\ServicePackage;
use app\common\model\user\User;

/**
 * 候补订单模型
 * Class Waitlist
 * @package app\common\model\schedule
 */
class Waitlist extends BaseModel
{
    protected $name = 'waitlist';

    // 通知状态
    const NOTIFY_STATUS_PENDING = 0;    // 未通知
    const NOTIFY_STATUS_NOTIFIED = 1;   // 已通知
    const NOTIFY_STATUS_ORDERED = 2;    // 已下单
    const NOTIFY_STATUS_EXPIRED = 3;    // 已过期

    /**
     * @notes 关联用户
     * @return \think\model\relation\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    /**
     * @notes 关联工作人员
     * @return \think\model\relation\BelongsTo
     */
    public function staff()
    {
        return $this->belongsTo(Staff::class, 'staff_id', 'id');
    }

    /**
     * @notes 关联套餐
     * @return \think\model\relation\BelongsTo
     */
    public function package()
    {
        return $this->belongsTo(ServicePackage::class, 'package_id', 'id');
    }

    /**
     * @notes 通知状态描述获取器
     * @param $value
     * @param $data
     * @return string
     */
    public function getNotifyStatusDescAttr($value, $data): string
    {
        $map = [
            self::NOTIFY_STATUS_PENDING => '等待中',
            self::NOTIFY_STATUS_NOTIFIED => '已通知',
            self::NOTIFY_STATUS_ORDERED => '已下单',
            self::NOTIFY_STATUS_EXPIRED => '已过期',
        ];
        return $map[$data['notify_status']] ?? '未知';
    }

    /**
     * @notes 时间段描述获取器
     * @param $value
     * @param $data
     * @return string
     */
    public function getTimeSlotDescAttr($value, $data): string
    {
        return Schedule::getTimeSlotOptions()[$data['time_slot']]['label'] ?? '未知';
    }

    /**
     * @notes 添加候补
     * @param int $userId
     * @param int $staffId
     * @param string $date
     * @param int $timeSlot
     * @param int $packageId
     * @param string $remark
     * @param int $expireHours 过期时间（小时）
     * @return array [bool $success, string $message, int|null $waitlistId]
     */
    public static function addToWaitlist(int $userId, int $staffId, string $date, int $timeSlot = 0, int $packageId = 0, string $remark = '', int $expireHours = 72): array
    {
        // 检查是否已在候补名单中
        $exists = self::where('user_id', $userId)
            ->where('staff_id', $staffId)
            ->where('schedule_date', $date)
            ->where('time_slot', $timeSlot)
            ->where('notify_status', 'in', [self::NOTIFY_STATUS_PENDING, self::NOTIFY_STATUS_NOTIFIED])
            ->find();

        if ($exists) {
            return [false, '您已在该档期的候补名单中', null];
        }

        try {
            $waitlist = self::create([
                'user_id' => $userId,
                'staff_id' => $staffId,
                'schedule_date' => $date,
                'time_slot' => $timeSlot,
                'package_id' => $packageId,
                'notify_status' => self::NOTIFY_STATUS_PENDING,
                'expire_time' => time() + ($expireHours * 3600),
                'remark' => $remark,
                'create_time' => time(),
                'update_time' => time(),
            ]);
            return [true, '已加入候补名单', $waitlist->id];
        } catch (\Exception $e) {
            return [false, '加入候补失败：' . $e->getMessage(), null];
        }
    }

    /**
     * @notes 当档期释放时通知候补用户
     * @param int $staffId
     * @param string $date
     * @param int $timeSlot
     * @return array 需要通知的用户列表
     */
    public static function notifyWaitlistUsers(int $staffId, string $date, int $timeSlot): array
    {
        $waitlists = self::where('staff_id', $staffId)
            ->where('schedule_date', $date)
            ->where('time_slot', $timeSlot)
            ->where('notify_status', self::NOTIFY_STATUS_PENDING)
            ->where('expire_time', '>', time())
            ->order('create_time', 'asc')
            ->select();

        $notifyUsers = [];
        foreach ($waitlists as $waitlist) {
            $waitlist->notify_status = self::NOTIFY_STATUS_NOTIFIED;
            $waitlist->notify_time = time();
            $waitlist->save();

            $notifyUsers[] = [
                'waitlist_id' => $waitlist->id,
                'user_id' => $waitlist->user_id,
                'staff_id' => $waitlist->staff_id,
                'schedule_date' => $waitlist->schedule_date,
                'time_slot' => $waitlist->time_slot,
            ];
        }

        return $notifyUsers;
    }

    /**
     * @notes 标记为已下单
     * @param int $waitlistId
     * @return bool
     */
    public static function markAsOrdered(int $waitlistId): bool
    {
        return self::where('id', $waitlistId)->update([
            'notify_status' => self::NOTIFY_STATUS_ORDERED,
            'update_time' => time(),
        ]) > 0;
    }

    /**
     * @notes 处理过期候补
     * @return int 处理数量
     */
    public static function processExpiredWaitlists(): int
    {
        return self::where('notify_status', 'in', [self::NOTIFY_STATUS_PENDING, self::NOTIFY_STATUS_NOTIFIED])
            ->where('expire_time', '<', time())
            ->update([
                'notify_status' => self::NOTIFY_STATUS_EXPIRED,
                'update_time' => time(),
            ]);
    }

    /**
     * @notes 获取用户的候补列表
     * @param int $userId
     * @param int $status
     * @return array
     */
    public static function getUserWaitlist(int $userId, int $status = -1): array
    {
        $query = self::where('user_id', $userId);
        
        if ($status >= 0) {
            $query->where('notify_status', $status);
        }

        return $query->with(['staff' => function ($q) {
                $q->field('id, name, avatar, category_id');
            }])
            ->order('create_time', 'desc')
            ->select()
            ->toArray();
    }

    /**
     * @notes 取消候补
     * @param int $waitlistId
     * @param int $userId
     * @return array [bool $success, string $message]
     */
    public static function cancelWaitlist(int $waitlistId, int $userId): array
    {
        $waitlist = self::where('id', $waitlistId)
            ->where('user_id', $userId)
            ->find();

        if (!$waitlist) {
            return [false, '候补记录不存在'];
        }

        if ($waitlist->notify_status == self::NOTIFY_STATUS_ORDERED) {
            return [false, '已下单的候补不能取消'];
        }

        $waitlist->delete();
        return [true, '取消成功'];
    }

    /**
     * @notes 获取通知状态选项
     * @return array
     */
    public static function getNotifyStatusOptions(): array
    {
        return [
            ['value' => self::NOTIFY_STATUS_PENDING, 'label' => '等待中'],
            ['value' => self::NOTIFY_STATUS_NOTIFIED, 'label' => '已通知'],
            ['value' => self::NOTIFY_STATUS_ORDERED, 'label' => '已下单'],
            ['value' => self::NOTIFY_STATUS_EXPIRED, 'label' => '已过期'],
        ];
    }
}
