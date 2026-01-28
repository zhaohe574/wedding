<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 档期规则模型
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\common\model\schedule;

use app\common\model\BaseModel;
use app\common\model\staff\Staff;

/**
 * 档期规则模型
 * Class ScheduleRule
 * @package app\common\model\schedule
 */
class ScheduleRule extends BaseModel
{
    protected $name = 'schedule_rule';

    /**
     * @notes 关联工作人员
     * @return \think\model\relation\BelongsTo
     */
    public function staff()
    {
        return $this->belongsTo(Staff::class, 'staff_id', 'id');
    }

    /**
     * @notes 休息日数组获取器
     * @param $value
     * @return array
     */
    public function getRestDaysArrayAttr($value, $data)
    {
        if (empty($data['rest_days'])) {
            return [];
        }
        return array_map('intval', explode(',', $data['rest_days']));
    }

    /**
     * @notes 休息日描述获取器
     * @param $value
     * @param $data
     * @return string
     */
    public function getRestDaysDescAttr($value, $data)
    {
        if (empty($data['rest_days'])) {
            return '无';
        }
        $dayMap = ['周日', '周一', '周二', '周三', '周四', '周五', '周六'];
        $days = explode(',', $data['rest_days']);
        $result = [];
        foreach ($days as $day) {
            if (isset($dayMap[(int)$day])) {
                $result[] = $dayMap[(int)$day];
            }
        }
        return implode('、', $result);
    }

    /**
     * @notes 获取工作人员的档期规则
     * @param int $staffId
     * @return array
     */
    public static function getStaffRule(int $staffId): array
    {
        // 优先获取个人规则
        $rule = self::where('staff_id', $staffId)
            ->where('is_enabled', 1)
            ->find();

        // 没有个人规则，使用全局规则
        if (!$rule) {
            $rule = self::where('staff_id', 0)
                ->where('is_enabled', 1)
                ->find();
        }

        if (!$rule) {
            // 返回默认规则
            return [
                'advance_days' => 1,
                'max_orders_per_day' => 1,
                'interval_hours' => 0,
                'work_start_time' => '09:00',
                'work_end_time' => '18:00',
                'rest_days' => [],
            ];
        }

        return $rule->toArray();
    }

    /**
     * @notes 检查日期是否在规则允许范围内
     * @param int $staffId
     * @param string $date
     * @return array [bool $allowed, string $reason]
     */
    public static function checkDate(int $staffId, string $date): array
    {
        $rule = self::getStaffRule($staffId);

        $today = date('Y-m-d');
        $targetDate = strtotime($date);
        $todayDate = strtotime($today);
        if ($targetDate < $todayDate) {
            return [false, '不可预约过去日期'];
        }
        if ($targetDate === $todayDate) {
            return [false, '当天不支持预约'];
        }

        // 检查休息日
        if (!empty($rule['rest_days'])) {
            $weekDay = (int)date('w', strtotime($date));
            $restDays = is_array($rule['rest_days']) ? $rule['rest_days'] : explode(',', $rule['rest_days']);
            if (in_array($weekDay, $restDays)) {
                $dayMap = ['周日', '周一', '周二', '周三', '周四', '周五', '周六'];
                return [false, "{$dayMap[$weekDay]}休息，不接单"];
            }
        }

        return [true, ''];
    }

    /**
     * @notes 检查单日订单数是否超限
     * @param int $staffId
     * @param string $date
     * @return array [bool $allowed, int $remaining]
     */
    public static function checkDayLimit(int $staffId, string $date): array
    {
        $rule = self::getStaffRule($staffId);
        $maxOrders = $rule['max_orders_per_day'];

        // 统计当日已预约数量
        $bookedCount = Schedule::where('staff_id', $staffId)
            ->where('schedule_date', $date)
            ->where('status', Schedule::STATUS_BOOKED)
            ->count();

        $remaining = $maxOrders - $bookedCount;

        return [$remaining > 0, max(0, $remaining)];
    }
}
