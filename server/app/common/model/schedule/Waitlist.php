<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 候补订单模型
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\common\model\schedule;

use app\common\model\BaseModel;
use app\common\model\notification\Notification;
use app\common\model\service\ServicePackage;
use app\common\model\staff\Staff;
use app\common\model\subscribe\SubscribeMessageTemplate;
use app\common\model\user\User;
use app\common\service\SubscribeMessageService;
use app\common\service\StationNotificationService;

/**
 * 候补订单模型
 * Class Waitlist
 * @package app\common\model\schedule
 */
class Waitlist extends BaseModel
{
    protected $name = 'waitlist';

    protected $append = [
        'notify_status_desc',
    ];

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
     * @param mixed $value
     * @param array $data
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
        return $map[(int)($data['notify_status'] ?? -1)] ?? '未知';
    }

    /**
     * @notes 添加候补
     * @param int $userId
     * @param int $staffId
     * @param string $date
     * @param int $timeSlot
     * @param int $packageId
     * @param string $remark
     * @param int $expireHours
     * @param string $batchNo
     * @return array
     */
    public static function addToWaitlist(int $userId, int $staffId, string $date, int $timeSlot = 0, int $packageId = 0, string $remark = '', int $expireHours = 72, string $batchNo = ''): array
    {
        $exists = self::where('user_id', $userId)
            ->where('staff_id', $staffId)
            ->where('schedule_date', $date)
            ->where('time_slot', 0)
            ->where('package_id', $packageId)
            ->where('notify_status', 'in', [self::NOTIFY_STATUS_PENDING, self::NOTIFY_STATUS_NOTIFIED])
            ->find();

        if ($exists) {
            return [false, '您已在该日期的候补名单中', null];
        }

        try {
            $waitlist = self::create([
                'user_id' => $userId,
                'staff_id' => $staffId,
                'schedule_date' => $date,
                'time_slot' => 0,
                'package_id' => $packageId,
                'batch_no' => $batchNo,
                'notify_status' => self::NOTIFY_STATUS_PENDING,
                'expire_time' => time() + ($expireHours * 3600),
                'remark' => $remark,
                'create_time' => time(),
                'update_time' => time(),
            ]);
            return [true, '已加入候补名单', (int)$waitlist->id];
        } catch (\Throwable $e) {
            return [false, '加入候补失败：' . $e->getMessage(), null];
        }
    }

    /**
     * @notes 当档期释放时通知候补用户
     * @param int $staffId
     * @param string $date
     * @param int $timeSlot
     * @return array
     */
    public static function notifyWaitlistUsers(int $staffId, string $date, int $timeSlot): array
    {
        $waitlists = self::where('staff_id', $staffId)
            ->where('schedule_date', $date)
            ->where('time_slot', 0)
            ->where('notify_status', self::NOTIFY_STATUS_PENDING)
            ->where('expire_time', '>', time())
            ->with([
                'staff' => function ($q) {
                    $q->field('id, name');
                },
                'package' => function ($q) {
                    $q->field('id, name');
                },
            ])
            ->order('create_time', 'asc')
            ->select();

        $notifyUsers = [];
        $notifyTime = time();
        foreach ($waitlists as $waitlist) {
            $waitlist->notify_status = self::NOTIFY_STATUS_NOTIFIED;
            $waitlist->notify_time = $notifyTime;
            $waitlist->save();

            self::sendWaitlistSubscribeMessage($waitlist);
            self::sendWaitlistStationNotification($waitlist);

            $notifyUsers[] = [
                'waitlist_id' => (int)$waitlist->id,
                'user_id' => (int)$waitlist->user_id,
                'staff_id' => (int)$waitlist->staff_id,
                'schedule_date' => (string)$waitlist->schedule_date,
                'package_id' => (int)$waitlist->package_id,
            ];
        }

        return $notifyUsers;
    }

    /**
     * @notes 发送候补释放订阅消息
     * @param Waitlist $waitlist
     * @return void
     */
    private static function sendWaitlistSubscribeMessage(Waitlist $waitlist): void
    {
        $data = [
            'staff_name' => $waitlist->staff->name ?? '服务人员',
            'schedule_date' => $waitlist->schedule_date ?? '',
            'package_name' => $waitlist->package->name ?? '',
            'remark' => $waitlist->remark ?? '',
            'waitlist_id' => (string)$waitlist->id,
        ];

        SubscribeMessageService::send(
            (int)$waitlist->user_id,
            SubscribeMessageTemplate::SCENE_WAITLIST_RELEASE,
            $data,
            'waitlist',
            (int)$waitlist->id
        );
    }

    /**
     * @notes 发送候补释放站内消息
     * @param Waitlist $waitlist
     * @return void
     */
    private static function sendWaitlistStationNotification(Waitlist $waitlist): void
    {
        $staffName = $waitlist->staff->name ?? '服务人员';
        $scheduleDate = $waitlist->schedule_date ?? '';
        $packageName = $waitlist->package->name ?? '';
        $packageText = $packageName ? "，套餐：{$packageName}" : '';

        StationNotificationService::send(
            (int)$waitlist->user_id,
            Notification::TYPE_ORDER,
            '候补档期已释放',
            "您候补的{$staffName}档期（{$scheduleDate}{$packageText}）已释放，请尽快预约。",
            StationNotificationService::TARGET_WAITLIST,
            (int)$waitlist->id
        );
    }

    /**
     * @notes 发送候补失效站内消息
     * @param Waitlist $waitlist
     * @return void
     */
    private static function sendWaitlistExpiredStationNotification(Waitlist $waitlist): void
    {
        $staffName = $waitlist->staff->name ?? '服务人员';
        $scheduleDate = $waitlist->schedule_date ?? '';
        $packageName = $waitlist->package->name ?? '';
        $packageText = $packageName ? "，套餐：{$packageName}" : '';

        StationNotificationService::sendUnique(
            (int)$waitlist->user_id,
            Notification::TYPE_ORDER,
            '候补已失效',
            "您候补的{$staffName}档期（{$scheduleDate}{$packageText}）因已失效，系统已自动取消。",
            StationNotificationService::TARGET_WAITLIST,
            (int)$waitlist->id
        );
    }

    /**
     * @notes 发送候补失效订阅消息
     * @param Waitlist $waitlist
     * @return void
     */
    private static function sendWaitlistExpiredSubscribeMessage(Waitlist $waitlist): void
    {
        $data = [
            'staff_name' => $waitlist->staff->name ?? '服务人员',
            'schedule_date' => $waitlist->schedule_date ?? '',
            'package_name' => $waitlist->package->name ?? '',
            'remark' => '已超过预约日期，系统已自动取消',
            'waitlist_id' => (string)$waitlist->id,
        ];

        SubscribeMessageService::send(
            (int)$waitlist->user_id,
            SubscribeMessageTemplate::SCENE_WAITLIST_EXPIRED,
            $data,
            'waitlist',
            (int)$waitlist->id
        );
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
     * @return int
     */
    public static function processExpiredWaitlists(): int
    {
        $waitlists = self::where('notify_status', 'in', [self::NOTIFY_STATUS_PENDING, self::NOTIFY_STATUS_NOTIFIED])
            ->where('expire_time', '<', time())
            ->with([
                'staff' => function ($q) {
                    $q->field('id, name');
                },
                'package' => function ($q) {
                    $q->field('id, name');
                },
            ])
            ->limit(100)
            ->select();

        if ($waitlists->isEmpty()) {
            return 0;
        }

        $handled = 0;
        $now = time();
        foreach ($waitlists as $waitlist) {
            $waitlist->notify_status = self::NOTIFY_STATUS_EXPIRED;
            $waitlist->update_time = $now;
            if ($waitlist->save()) {
                self::sendWaitlistExpiredStationNotification($waitlist);
                self::sendWaitlistExpiredSubscribeMessage($waitlist);
                $handled++;
            }
        }

        return $handled;
    }

    /**
     * @notes 处理预约日期已过的候补
     * @param int $limit
     * @return int
     */
    public static function processPastDateWaitlists(int $limit = 100): int
    {
        $today = date('Y-m-d');
        $waitlists = self::where('notify_status', 'in', [self::NOTIFY_STATUS_PENDING, self::NOTIFY_STATUS_NOTIFIED])
            ->where('schedule_date', '<', $today)
            ->limit($limit)
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
            return 0;
        }

        $handled = 0;
        $now = time();
        foreach ($waitlists as $waitlist) {
            $waitlist->notify_status = self::NOTIFY_STATUS_EXPIRED;
            $waitlist->update_time = $now;
            if ($waitlist->save()) {
                self::sendWaitlistExpiredStationNotification($waitlist);
                self::sendWaitlistExpiredSubscribeMessage($waitlist);
                $handled++;
            }
        }

        return $handled;
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

        $lists = $query->with([
                'staff' => function ($q) {
                    $q->field('id, name, avatar, category_id');
                },
                'package' => function ($q) {
                    $q->field('id, name');
                },
            ])
            ->order('create_time', 'desc')
            ->select()
            ->toArray();

        foreach ($lists as &$item) {
            unset($item['time_slot']);
        }
        unset($item);

        return $lists;
    }

    /**
     * @notes 取消候补
     * @param int $waitlistId
     * @param int $userId
     * @return array
     */
    public static function cancelWaitlist(int $waitlistId, int $userId): array
    {
        $waitlist = self::where('id', $waitlistId)
            ->where('user_id', $userId)
            ->find();

        if (!$waitlist) {
            return [false, '候补记录不存在'];
        }

        if ((int)$waitlist->notify_status === self::NOTIFY_STATUS_ORDERED) {
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
