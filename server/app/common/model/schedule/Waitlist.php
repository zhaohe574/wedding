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

    private const NOTIFY_RESPONSE_SECONDS = 86400;

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
            self::NOTIFY_STATUS_ORDERED => '已转正',
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
        [$isAvailable] = Schedule::checkAvailabilityForUserWithReason(
            $staffId,
            $date,
            $userId,
            Schedule::TIME_SLOT_ALL
        );
        if ($isAvailable) {
            return [false, '当前日期可直接预约，无需加入候补', null];
        }

        $exists = self::where('user_id', $userId)
            ->where('staff_id', $staffId)
            ->where('schedule_date', $date)
            ->where('time_slot', Schedule::TIME_SLOT_ALL)
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
                'time_slot' => Schedule::TIME_SLOT_ALL,
                'package_id' => $packageId,
                'batch_no' => $batchNo,
                'notify_status' => self::NOTIFY_STATUS_PENDING,
                'expire_time' => self::buildPendingExpireTime($date),
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
        [$isAvailable] = Schedule::checkAvailabilityForUserWithReason(
            $staffId,
            $date,
            0,
            Schedule::TIME_SLOT_ALL
        );
        if (!$isAvailable) {
            return [];
        }

        $waitlists = self::where('staff_id', $staffId)
            ->where('schedule_date', $date)
            ->where('time_slot', Schedule::TIME_SLOT_ALL)
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
            ->order('id', 'asc')
            ->limit(1)
            ->select();

        $notifyUsers = [];
        $notifyTime = time();
        foreach ($waitlists as $waitlist) {
            [$canNotify] = self::canNotifyWaitlist($waitlist);
            if (!$canNotify || !self::markWaitlistAsNotified($waitlist, $notifyTime)) {
                continue;
            }

            self::dispatchReleaseNotifications($waitlist);

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
            'expire_time' => 0,
            'update_time' => time(),
        ]) > 0;
    }

    /**
     * @notes 在订单创建成功时消耗已通知候补
     * @param int $waitlistId
     * @param int $orderId
     * @param int $userId
     * @param int $staffId
     * @param string $scheduleDate
     * @return array{0: bool, 1: string}
     */
    public static function consumeForOrder(
        int $waitlistId,
        int $orderId,
        int $userId,
        int $staffId,
        string $scheduleDate
    ): array {
        /** @var Waitlist|null $waitlist */
        $waitlist = self::where('id', $waitlistId)->lock(true)->find();
        if (!$waitlist) {
            return [false, '候补记录不存在'];
        }

        if ((int)$waitlist->user_id !== $userId) {
            return [false, '候补记录不存在或不可用'];
        }

        if ((int)$waitlist->staff_id !== $staffId || (string)$waitlist->schedule_date !== $scheduleDate) {
            return [false, '候补记录与当前预约信息不匹配'];
        }

        if ((int)$waitlist->notify_status === self::NOTIFY_STATUS_ORDERED) {
            return [false, '该候补已完成转正，请勿重复提交'];
        }

        if ((int)$waitlist->notify_status !== self::NOTIFY_STATUS_NOTIFIED) {
            return [false, '当前候补尚未进入可预约状态'];
        }

        if (self::isScheduleDatePast((string)$waitlist->schedule_date)) {
            return [false, '候补日期已过'];
        }

        if ((int)$waitlist->expire_time > 0 && (int)$waitlist->expire_time <= time()) {
            return [false, '候补通知已失效'];
        }

        $waitlist->notify_status = self::NOTIFY_STATUS_ORDERED;
        $waitlist->expire_time = 0;
        $waitlist->update_time = time();
        $waitlist->remark = trim(implode('；', array_filter([
            (string)$waitlist->remark,
            $orderId > 0 ? '已关联订单#' . $orderId : '',
        ])));

        return $waitlist->save()
            ? [true, '']
            : [false, '候补转正状态保存失败'];
    }

    /**
     * @notes 处理过期候补
     * @return int
     */
    public static function dispatchReleaseNotifications(Waitlist $waitlist): void
    {
        self::sendWaitlistSubscribeMessage($waitlist);
        self::sendWaitlistStationNotification($waitlist);
    }

    /**
     * @notes 统一发送候补失效通知
     * @param Waitlist $waitlist
     * @return void
     */
    public static function dispatchExpiredNotifications(Waitlist $waitlist): void
    {
        self::sendWaitlistExpiredStationNotification($waitlist);
        self::sendWaitlistExpiredSubscribeMessage($waitlist);
    }

    /**
     * @notes 处理过期候补
     * @param int $limit
     * @return int
     */
    public static function processExpiredWaitlists(int $limit = 100): int
    {
        $today = date('Y-m-d');
        $now = time();
        $waitlists = self::where('notify_status', 'in', [self::NOTIFY_STATUS_PENDING, self::NOTIFY_STATUS_NOTIFIED])
            ->where(function ($query) use ($today, $now) {
                $query->where(function ($subQuery) use ($now) {
                    $subQuery->where('expire_time', '>', 0)
                        ->where('expire_time', '<=', $now);
                })
                    ->whereOr('schedule_date', '<', $today);
            })
            ->with([
                'staff' => function ($q) {
                    $q->field('id, name');
                },
                'package' => function ($q) {
                    $q->field('id, name');
                },
            ])
            ->limit($limit)
            ->select();

        if ($waitlists->isEmpty()) {
            return 0;
        }

        $handled = 0;
        foreach ($waitlists as $waitlist) {
            $waitlist->notify_status = self::NOTIFY_STATUS_EXPIRED;
            $waitlist->expire_time = max((int)$waitlist->expire_time, $now);
            $waitlist->update_time = $now;
            if ($waitlist->save()) {
                self::dispatchExpiredNotifications($waitlist);
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
        return self::processExpiredWaitlists($limit);
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
            $item['notify_time_text'] = self::formatTimestamp($item['notify_time'] ?? 0);
            $item['expire_time_text'] = self::formatTimestamp($item['expire_time'] ?? 0);
            $item['can_cancel'] = in_array((int)($item['notify_status'] ?? -1), [self::NOTIFY_STATUS_PENDING, self::NOTIFY_STATUS_NOTIFIED], true);
            $item['can_book_now'] = false;
            $item['book_block_reason'] = '';
            $item['status_hint'] = self::buildStatusHint($item, $userId);

            if ((int)($item['notify_status'] ?? -1) === self::NOTIFY_STATUS_NOTIFIED) {
                [$canBook, $message] = Schedule::checkAvailabilityForUserWithReason(
                    (int)($item['staff_id'] ?? 0),
                    (string)($item['schedule_date'] ?? ''),
                    $userId,
                    Schedule::TIME_SLOT_ALL
                );

                if (self::isScheduleDatePast((string)($item['schedule_date'] ?? ''))) {
                    $canBook = false;
                    $message = '预约日期已过';
                } elseif ((int)($item['expire_time'] ?? 0) > 0 && (int)($item['expire_time'] ?? 0) <= time()) {
                    $canBook = false;
                    $message = '候补通知已过期';
                }

                $item['can_book_now'] = $canBook;
                $item['book_block_reason'] = $canBook ? '' : ($message ?: '当前档期暂不可预约');
            }
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
            ['value' => self::NOTIFY_STATUS_ORDERED, 'label' => '已转正'],
            ['value' => self::NOTIFY_STATUS_EXPIRED, 'label' => '已过期'],
        ];
    }

    /**
     * @notes 校验候补是否允许通知
     * @param Waitlist $waitlist
     * @param bool $requireAvailable
     * @return array{0: bool, 1: string}
     */
    public static function canNotifyWaitlist(Waitlist $waitlist, bool $requireAvailable = true): array
    {
        if ((int)$waitlist->notify_status !== self::NOTIFY_STATUS_PENDING) {
            return [false, '只能通知等待中的候补'];
        }

        if (self::isScheduleDatePast((string)$waitlist->schedule_date)) {
            return [false, '候补日期已过，不能继续通知'];
        }

        if ((int)$waitlist->expire_time > 0 && (int)$waitlist->expire_time <= time()) {
            return [false, '候补已失效，不能继续通知'];
        }

        if (self::hasEarlierPendingWaitlist($waitlist)) {
            return [false, '当前仍有更早提交的候补，需按顺序通知'];
        }

        if ($requireAvailable) {
            [$isAvailable, $message] = Schedule::checkAvailabilityForUserWithReason(
                (int)$waitlist->staff_id,
                (string)$waitlist->schedule_date,
                0,
                Schedule::TIME_SLOT_ALL
            );

            if (!$isAvailable) {
                return [false, $message ?: '当前档期未释放，不能通知候补'];
            }
        }

        return [true, ''];
    }

    /**
     * @notes 标记候补为已通知
     * @param Waitlist $waitlist
     * @param int|null $notifyTime
     * @return bool
     */
    public static function markWaitlistAsNotified(Waitlist $waitlist, ?int $notifyTime = null): bool
    {
        $notifyTime = $notifyTime ?? time();
        $waitlist->notify_status = self::NOTIFY_STATUS_NOTIFIED;
        $waitlist->notify_time = $notifyTime;
        $waitlist->expire_time = self::buildNotifiedExpireTime((string)$waitlist->schedule_date, $notifyTime);
        $waitlist->update_time = $notifyTime;
        return (bool)$waitlist->save();
    }

    /**
     * @notes 构建候补状态提示
     * @param array $item
     * @param int $userId
     * @return string
     */
    private static function buildStatusHint(array $item, int $userId): string
    {
        $status = (int)($item['notify_status'] ?? -1);
        $expireTimeText = self::formatTimestamp($item['expire_time'] ?? 0);

        if ($status === self::NOTIFY_STATUS_PENDING) {
            return $expireTimeText ? "候补将保留至 {$expireTimeText}" : '候补排队中';
        }

        if ($status === self::NOTIFY_STATUS_NOTIFIED) {
            if (self::isScheduleDatePast((string)($item['schedule_date'] ?? ''))) {
                return '预约日期已过';
            }

            if ((int)($item['expire_time'] ?? 0) > 0 && (int)($item['expire_time'] ?? 0) <= time()) {
                return '候补通知已过期';
            }

            [$canBook, $message] = Schedule::checkAvailabilityForUserWithReason(
                (int)($item['staff_id'] ?? 0),
                (string)($item['schedule_date'] ?? ''),
                $userId,
                Schedule::TIME_SLOT_ALL
            );
            if ($canBook) {
                return $expireTimeText ? "请在 {$expireTimeText} 前完成预约" : '请尽快完成预约';
            }

            return $message ?: '当前档期暂不可预约';
        }

        if ($status === self::NOTIFY_STATUS_ORDERED) {
            return '已完成候补转正，请在订单中继续跟进';
        }

        return '本次候补已结束';
    }

    /**
     * @notes 检查是否存在更早提交的候补
     * @param Waitlist $waitlist
     * @return bool
     */
    private static function hasEarlierPendingWaitlist(Waitlist $waitlist): bool
    {
        return self::where('staff_id', (int)$waitlist->staff_id)
            ->where('schedule_date', (string)$waitlist->schedule_date)
            ->where('time_slot', Schedule::TIME_SLOT_ALL)
            ->where('notify_status', self::NOTIFY_STATUS_PENDING)
            ->where('expire_time', '>', time())
            ->where(function ($query) use ($waitlist) {
                $query->where('create_time', '<', (int)$waitlist->create_time)
                    ->whereOr(function ($subQuery) use ($waitlist) {
                        $subQuery->where('create_time', '=', (int)$waitlist->create_time)
                            ->where('id', '<', (int)$waitlist->id);
                    });
            })
            ->count() > 0;
    }

    /**
     * @notes 计算待通知候补的默认过期时间
     * @param string $date
     * @return int
     */
    private static function buildPendingExpireTime(string $date): int
    {
        return self::getScheduleDateDeadline($date);
    }

    /**
     * @notes 计算已通知候补的响应截止时间
     * @param string $date
     * @param int|null $notifyTime
     * @return int
     */
    private static function buildNotifiedExpireTime(string $date, ?int $notifyTime = null): int
    {
        $notifyTime = $notifyTime ?? time();
        return min(self::getScheduleDateDeadline($date), $notifyTime + self::NOTIFY_RESPONSE_SECONDS);
    }

    /**
     * @notes 获取档期当日截止时间
     * @param string $date
     * @return int
     */
    private static function getScheduleDateDeadline(string $date): int
    {
        $timestamp = strtotime($date . ' 23:59:59');
        return $timestamp === false ? time() : $timestamp;
    }

    /**
     * @notes 判断档期日期是否已过
     * @param string $date
     * @return bool
     */
    private static function isScheduleDatePast(string $date): bool
    {
        $timestamp = strtotime($date);
        if ($timestamp === false) {
            return false;
        }

        return $timestamp < strtotime(date('Y-m-d'));
    }

    /**
     * @notes 格式化时间戳
     * @param mixed $timestamp
     * @return string
     */
    private static function formatTimestamp($timestamp): string
    {
        if (!is_numeric($timestamp)) {
            return '';
        }

        $value = (int)$timestamp;
        return $value > 0 ? date('Y-m-d H:i:s', $value) : '';
    }
}
