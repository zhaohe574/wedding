<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 档期业务逻辑
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\adminapi\logic\schedule;

use app\common\logic\BaseLogic;
use app\common\model\schedule\Schedule;
use app\common\model\schedule\ScheduleLock;
use app\common\model\schedule\ScheduleRule;
use app\common\model\schedule\CalendarEvent;
use app\common\model\staff\Staff;
use think\facade\Db;

/**
 * 档期业务逻辑
 * Class ScheduleLogic
 * @package app\adminapi\logic\schedule
 */
class ScheduleLogic extends BaseLogic
{
    /**
     * @notes 获取档期详情
     * @param int $id
     * @return array
     */
    public static function detail(int $id): array
    {
        $schedule = Schedule::with(['staff'])->find($id);
        if (!$schedule) {
            return [];
        }
        $data = $schedule->toArray();
        $data['time_slot_desc'] = $schedule->time_slot_desc;
        $data['status_desc'] = $schedule->status_desc;
        return $data;
    }

    /**
     * @notes 获取月度日历数据
     * @param array $params
     * @return array
     */
    public static function getMonthCalendar(array $params): array
    {
        $staffId = $params['staff_id'] ?? 0;
        $year = $params['year'] ?? date('Y');
        $month = $params['month'] ?? date('m');

        $startDate = sprintf('%04d-%02d-01', $year, $month);
        $endDate = date('Y-m-t', strtotime($startDate));

        // 获取档期数据
        $query = Schedule::whereBetween('schedule_date', [$startDate, $endDate]);
        if ($staffId > 0) {
            $query->where('staff_id', $staffId);
        }
        $schedules = $query->select()->toArray();

        // 获取黄历数据
        $calendarEvents = CalendarEvent::getMonthCalendar((int)$year, (int)$month);

        // 按日期组织数据
        $result = [];
        $currentDate = $startDate;
        while ($currentDate <= $endDate) {
            $dayData = [
                'date' => $currentDate,
                'schedules' => [],
                'calendar' => $calendarEvents[$currentDate] ?? null,
            ];

            // 筛选该日期的档期
            foreach ($schedules as $schedule) {
                if ($schedule['schedule_date'] == $currentDate) {
                    $dayData['schedules'][] = $schedule;
                }
            }

            $result[$currentDate] = $dayData;
            $currentDate = date('Y-m-d', strtotime($currentDate . ' +1 day'));
        }

        return [
            'year' => $year,
            'month' => $month,
            'days' => $result,
        ];
    }

    /**
     * @notes 设置档期状态
     * @param array $params
     * @return bool
     */
    public static function setStatus(array $params): bool
    {
        try {
            $schedule = Schedule::where('staff_id', $params['staff_id'])
                ->where('schedule_date', $params['date'])
                ->where('time_slot', $params['time_slot'] ?? 0)
                ->find();

            if ($schedule) {
                $schedule->status = $params['status'];
                $schedule->remark = $params['remark'] ?? '';
                $schedule->update_time = time();
                $schedule->save();
            } else {
                Schedule::create([
                    'staff_id' => $params['staff_id'],
                    'schedule_date' => $params['date'],
                    'time_slot' => $params['time_slot'] ?? 0,
                    'status' => $params['status'],
                    'remark' => $params['remark'] ?? '',
                    'version' => 1,
                    'create_time' => time(),
                    'update_time' => time(),
                ]);
            }
            return true;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * @notes 批量设置档期
     * @param array $params
     * @return int|false
     */
    public static function batchSet(array $params)
    {
        Db::startTrans();
        try {
            $staffIds = $params['staff_ids'];
            $startDate = $params['start_date'];
            $endDate = $params['end_date'];
            $timeSlots = $params['time_slots'] ?? [0];
            $status = $params['status'];
            $price = $params['price'] ?? 0;

            $count = 0;
            $currentDate = $startDate;

            while ($currentDate <= $endDate) {
                // 检查是否是休息日（如果需要跳过）
                if (!empty($params['skip_rest_days'])) {
                    $weekDay = date('w', strtotime($currentDate));
                    if (in_array($weekDay, $params['skip_rest_days'])) {
                        $currentDate = date('Y-m-d', strtotime($currentDate . ' +1 day'));
                        continue;
                    }
                }

                foreach ($staffIds as $staffId) {
                    foreach ($timeSlots as $timeSlot) {
                        $schedule = Schedule::where('staff_id', $staffId)
                            ->where('schedule_date', $currentDate)
                            ->where('time_slot', $timeSlot)
                            ->find();

                        if ($schedule) {
                            // 已预约的不能修改
                            if ($schedule->status == Schedule::STATUS_BOOKED) {
                                continue;
                            }
                            $schedule->status = $status;
                            if ($price > 0) {
                                $schedule->price = $price;
                            }
                            $schedule->update_time = time();
                            $schedule->save();
                        } else {
                            Schedule::create([
                                'staff_id' => $staffId,
                                'schedule_date' => $currentDate,
                                'time_slot' => $timeSlot,
                                'status' => $status,
                                'price' => $price,
                                'version' => 1,
                                'create_time' => time(),
                                'update_time' => time(),
                            ]);
                        }
                        $count++;
                    }
                }
                $currentDate = date('Y-m-d', strtotime($currentDate . ' +1 day'));
            }

            Db::commit();
            return $count;
        } catch (\Exception $e) {
            Db::rollback();
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * @notes 锁定档期（管理员，使用Redis分布式锁防超卖）
     * @param array $params
     * @return bool
     */
    public static function lockSchedule(array $params): bool
    {
        Db::startTrans();
        try {
            $lockType = $params['lock_type'] ?? Schedule::LOCK_TYPE_VIP;
            
            // 使用Redis分布式锁保证并发安全
            [$success, $message] = Schedule::lockScheduleWithRedis(
                $params['staff_id'],
                $params['date'],
                $params['time_slot'] ?? 0,
                0, // 管理员操作，user_id为0
                $lockType,
                86400 * 365 // 管理员锁定默认1年
            );

            if (!$success) {
                Db::rollback();
                self::setError($message);
                return false;
            }

            // 记录锁定日志
            $schedule = Schedule::where('staff_id', $params['staff_id'])
                ->where('schedule_date', $params['date'])
                ->where('time_slot', $params['time_slot'] ?? 0)
                ->find();

            ScheduleLock::create([
                'schedule_id' => $schedule->id,
                'staff_id' => $params['staff_id'],
                'user_id' => 0,
                'lock_type' => $lockType,
                'lock_start_time' => time(),
                'lock_end_time' => time() + 86400 * 365,
                'lock_reason' => $params['reason'] ?? '',
                'status' => 1,
                'admin_id' => $params['admin_id'],
                'create_time' => time(),
                'update_time' => time(),
            ]);

            Db::commit();
            return true;
        } catch (\Exception $e) {
            Db::rollback();
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * @notes 释放锁定
     * @param array $params
     * @return bool
     */
    public static function unlockSchedule(array $params): bool
    {
        Db::startTrans();
        try {
            $schedule = Schedule::find($params['id']);
            if (!$schedule) {
                self::setError('档期不存在');
                return false;
            }

            if ($schedule->status != Schedule::STATUS_LOCKED && $schedule->status != Schedule::STATUS_RESERVED) {
                self::setError('该档期未被锁定');
                return false;
            }

            Schedule::releaseLock($schedule->id);

            // 更新锁定记录
            ScheduleLock::where('schedule_id', $schedule->id)
                ->where('status', 1)
                ->update([
                    'status' => 0,
                    'release_time' => time(),
                    'release_reason' => $params['reason'] ?? '管理员手动释放',
                    'update_time' => time(),
                ]);

            Db::commit();
            return true;
        } catch (\Exception $e) {
            Db::rollback();
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * @notes 内部预留
     * @param array $params
     * @return bool
     */
    public static function reserveSchedule(array $params): bool
    {
        Db::startTrans();
        try {
            $schedule = Schedule::where('staff_id', $params['staff_id'])
                ->where('schedule_date', $params['date'])
                ->where('time_slot', $params['time_slot'] ?? 0)
                ->find();

            if ($schedule) {
                if ($schedule->status == Schedule::STATUS_BOOKED) {
                    Db::rollback();
                    self::setError('该档期已被预约');
                    return false;
                }
                $schedule->status = Schedule::STATUS_RESERVED;
                $schedule->lock_type = Schedule::LOCK_TYPE_INTERNAL;
                $schedule->lock_reason = $params['reason'] ?? '';
                $schedule->update_time = time();
                $schedule->save();
            } else {
                $schedule = Schedule::create([
                    'staff_id' => $params['staff_id'],
                    'schedule_date' => $params['date'],
                    'time_slot' => $params['time_slot'] ?? 0,
                    'status' => Schedule::STATUS_RESERVED,
                    'lock_type' => Schedule::LOCK_TYPE_INTERNAL,
                    'lock_reason' => $params['reason'] ?? '',
                    'version' => 1,
                    'create_time' => time(),
                    'update_time' => time(),
                ]);
            }

            // 记录锁定日志
            ScheduleLock::create([
                'schedule_id' => $schedule->id,
                'staff_id' => $params['staff_id'],
                'user_id' => 0,
                'lock_type' => Schedule::LOCK_TYPE_INTERNAL,
                'lock_start_time' => time(),
                'lock_end_time' => 0,
                'lock_reason' => $params['reason'] ?? '',
                'status' => 1,
                'admin_id' => $params['admin_id'],
                'create_time' => time(),
                'update_time' => time(),
            ]);

            Db::commit();
            return true;
        } catch (\Exception $e) {
            Db::rollback();
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * @notes 获取锁定记录
     * @param array $params
     * @return array
     */
    public static function getLockRecords(array $params): array
    {
        $query = ScheduleLock::with(['staff']);

        if (!empty($params['staff_id'])) {
            $query->where('staff_id', $params['staff_id']);
        }

        if (!empty($params['status'])) {
            $query->where('status', $params['status']);
        }

        return $query->order('create_time', 'desc')
            ->paginate($params['page_size'] ?? 15)
            ->toArray();
    }

    /**
     * @notes 获取时间段选项
     * @return array
     */
    public static function getTimeSlotOptions(): array
    {
        return Schedule::getTimeSlotOptions();
    }

    /**
     * @notes 获取状态选项
     * @return array
     */
    public static function getStatusOptions(): array
    {
        return Schedule::getStatusOptions();
    }

    /**
     * @notes 档期统计
     * @param array $params
     * @return array
     */
    public static function statistics(array $params): array
    {
        $staffId = $params['staff_id'] ?? 0;
        $year = $params['year'] ?? date('Y');
        $month = $params['month'] ?? date('m');

        $startDate = sprintf('%04d-%02d-01', $year, $month);
        $endDate = date('Y-m-t', strtotime($startDate));

        $query = Schedule::whereBetween('schedule_date', [$startDate, $endDate]);
        if ($staffId > 0) {
            $query->where('staff_id', $staffId);
        }

        $total = (clone $query)->count();
        $available = (clone $query)->where('status', Schedule::STATUS_AVAILABLE)->count();
        $booked = (clone $query)->where('status', Schedule::STATUS_BOOKED)->count();
        $locked = (clone $query)->where('status', Schedule::STATUS_LOCKED)->count();
        $reserved = (clone $query)->where('status', Schedule::STATUS_RESERVED)->count();

        return [
            'total' => $total,
            'available' => $available,
            'booked' => $booked,
            'locked' => $locked,
            'reserved' => $reserved,
            'booking_rate' => $total > 0 ? round($booked / $total * 100, 2) : 0,
        ];
    }
}
