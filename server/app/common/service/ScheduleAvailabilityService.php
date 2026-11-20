<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 批量档期可用性计算
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\common\service;

use app\common\model\schedule\Schedule;
use app\common\model\schedule\ScheduleRule;

/**
 * 批量计算「某日有多少位老师可约」。
 *
 * 为什么单独写一个服务：
 *   `Schedule::isAvailable($staffId, $date)` 的语义是「单人单日」，内部会做
 *   规则查询、单日订单数统计、档期记录查询，逐人逐日调用会产生 3×N×M 次 SQL。
 *   日历要给整月每天铺状态点，必须把这三类数据一次性批量取回再在内存里判定，
 *   否则 31 天 × 几十位老师会把接口拖成秒级。
 *
 * 口径必须与 `StaffLists::count()` 完全一致，否则日历上的点会和点进去的人数对不上：
 *   1. 规则：`ScheduleRule::checkDateByRule()` —— 提前期 + 休息日 + 过去日期；
 *   2. 名额：`ScheduleRule::checkDayLimit()`   —— 单日已付款订单数（未付款不占名额）；
 *   3. 档期：`Schedule::isScheduleRecordAvailableWithReason()` —— 记录状态。
 *
 * 已付款占用的统计统一走 `ScheduleOccupancyService`，禁止在本类里另写一套条件。
 */
class ScheduleAvailabilityService
{
    /**
     * @notes 批量统计每天可约人数
     * @param array $staffIds 已按地区/类型/标签/关键词筛完的人员 ID
     * @param array $dates YYYY-MM-DD 列表
     * @return array<string,int> date => available_count
     */
    public static function countByDates(array $staffIds, array $dates): array
    {
        $staffIds = array_values(array_unique(array_filter(
            array_map('intval', $staffIds),
            static fn (int $id): bool => $id > 0
        )));
        $dates = array_values(array_unique(array_filter(
            array_map(static fn ($d): string => trim((string)$d), $dates),
            static fn (string $d): bool => (bool)preg_match('/^\d{4}-\d{2}-\d{2}$/', $d)
        )));
        sort($dates);

        $result = array_fill_keys($dates, 0);
        if (empty($staffIds) || empty($dates)) {
            return $result;
        }

        $rules = self::loadStaffRules($staffIds);
        $globalRule = ScheduleRule::getGlobalRule();
        $records = self::loadScheduleRecords($staffIds, $dates);
        $occupied = ScheduleRule::countOccupiedOrdersBatch($staffIds, $dates);

        $now = time();
        $todayTs = strtotime(date('Y-m-d'));

        foreach ($dates as $date) {
            $targetTs = strtotime($date);
            if ($targetTs === false) {
                continue;
            }

            // 与 checkDateByRule() 对齐：过去日期一律不可约
            $daysDiff = (int)floor(($targetTs - $todayTs) / 86400);
            if ($daysDiff < 0) {
                $result[$date] = 0;
                continue;
            }

            $weekday = (int)date('w', $targetTs);
            $count = 0;

            foreach ($staffIds as $staffId) {
                $rule = $rules[$staffId] ?? $globalRule;

                $advanceDays = max(0, (int)($rule['advance_days'] ?? 0));
                if ($daysDiff < $advanceDays) {
                    continue;
                }

                $restDays = ScheduleRule::normalizeRestDays($rule['rest_days'] ?? []);
                if (in_array($weekday, $restDays, true)) {
                    continue;
                }

                $maxOrders = max(1, (int)($rule['max_orders_per_day'] ?? 1));
                $capacity = min($maxOrders, ScheduleOccupancyService::PAID_ORDER_CAPACITY_PER_DAY);
                $booked = (int)($occupied[$staffId][$date] ?? 0);
                if ($capacity - $booked <= 0) {
                    continue;
                }

                if (!self::isRecordAvailable($records[$staffId][$date] ?? null, $now)) {
                    continue;
                }

                $count++;
            }

            $result[$date] = $count;
        }

        return $result;
    }

    /**
     * @notes 一次性取回人员的启用规则
     * @param array $staffIds
     * @return array [staff_id => rule]
     */
    private static function loadStaffRules(array $staffIds): array
    {
        $rows = ScheduleRule::whereIn('staff_id', $staffIds)
            ->where('is_enabled', 1)
            ->field('staff_id, advance_days, max_orders_per_day, rest_days')
            ->select()
            ->toArray();

        $map = [];
        foreach ($rows as $row) {
            $staffId = (int)($row['staff_id'] ?? 0);
            // 与 getStaffRule() 的 find() 语义一致：同一人有多条时取第一条
            if ($staffId <= 0 || isset($map[$staffId])) {
                continue;
            }
            $map[$staffId] = [
                'advance_days' => $row['advance_days'] ?? null,
                'max_orders_per_day' => $row['max_orders_per_day'] ?? null,
                'rest_days' => ScheduleRule::normalizeRestDays($row['rest_days'] ?? []),
            ];
        }

        return $map;
    }

    /**
     * @notes 一次性取回区间内的全天档期记录
     * @param array $staffIds
     * @param array $dates
     * @return array [staff_id => [date => record]]
     */
    private static function loadScheduleRecords(array $staffIds, array $dates): array
    {
        $rows = Schedule::whereIn('staff_id', $staffIds)
            ->whereIn('schedule_date', $dates)
            ->where('time_slot', Schedule::TIME_SLOT_ALL)
            ->field('id, staff_id, schedule_date, status, lock_user_id, lock_expire_time')
            ->select()
            ->toArray();

        $map = [];
        foreach ($rows as $row) {
            $staffId = (int)($row['staff_id'] ?? 0);
            $date = (string)($row['schedule_date'] ?? '');
            if ($staffId <= 0 || $date === '') {
                continue;
            }
            $map[$staffId][$date] = $row;
        }

        return $map;
    }

    /**
     * @notes 档期记录是否可约（只读判定，不做过期锁释放）
     *
     * 对应 `Schedule::isScheduleRecordAvailableWithReason($record, 0)`：
     * 无记录 / 已过期锁定 / 可预约 / 已预约 → 可约；其余（不可用、锁定中、内部预留）→ 不可约。
     *
     * @param array|null $record
     * @param int $now
     * @return bool
     */
    private static function isRecordAvailable(?array $record, int $now): bool
    {
        if (!$record) {
            return true;
        }

        $status = (int)($record['status'] ?? Schedule::STATUS_AVAILABLE);
        $lockExpireTime = (int)($record['lock_expire_time'] ?? 0);

        if ($status === Schedule::STATUS_LOCKED && $lockExpireTime > 0 && $lockExpireTime < $now) {
            return true;
        }

        if ($status === Schedule::STATUS_AVAILABLE || $status === Schedule::STATUS_BOOKED) {
            return true;
        }

        return false;
    }
}
