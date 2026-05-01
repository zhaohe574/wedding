<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 档期规则模型
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\common\model\schedule;

use app\common\model\BaseModel;
use app\common\model\order\Order;
use app\common\model\order\OrderItem;
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
     * 不占用接单名额的订单状态
     */
    private const NON_OCCUPY_ORDER_STATUSES = [
        Order::STATUS_CANCELLED,
        Order::STATUS_REFUNDED,
        Order::STATUS_USER_DELETED,
    ];

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
                'advance_days' => 3,
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
        return self::checkDateByRule($rule, $date);
    }

    /**
     * @notes 检查指定日期是否符合规则日期要求
     * @param array $rule
     * @param string $date
     * @return array
     */
    public static function checkDateByRule(array $rule, string $date): array
    {
        $today = date('Y-m-d');
        $targetDate = strtotime($date);
        $todayDate = strtotime($today);
        if ($targetDate === false) {
            return [false, '预约日期格式错误'];
        }
        if ($targetDate < $todayDate) {
            return [false, '不可预约过去日期'];
        }

        $advanceDays = max(0, (int)($rule['advance_days'] ?? 0));
        $daysDiff = (int)floor(($targetDate - $todayDate) / 86400);
        if ($daysDiff < $advanceDays) {
            $message = $advanceDays > 0
                ? "至少需要提前{$advanceDays}天预约"
                : '当天不支持预约';
            return [false, $message];
        }

        // 检查休息日
        if (!empty($rule['rest_days'])) {
            $weekDay = (int)date('w', strtotime($date));
            $restDays = self::normalizeRestDays($rule['rest_days']);
            if (in_array($weekDay, $restDays, true)) {
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
        $maxOrders = max(1, (int)($rule['max_orders_per_day'] ?? 1));

        $bookedCount = self::countOccupiedOrders($staffId, $date);

        $remaining = $maxOrders - $bookedCount;

        return [$remaining > 0, max(0, $remaining)];
    }

    /**
     * @notes 检查完整接单规则
     * @param int $staffId
     * @param string $date
     * @return array [bool $allowed, string $reason, array $meta]
     */
    public static function checkBookingRule(int $staffId, string $date): array
    {
        $rule = self::getStaffRule($staffId);

        [$dateAllowed, $dateReason] = self::checkDateByRule($rule, $date);
        if (!$dateAllowed) {
            return [false, $dateReason, ['rule' => $rule]];
        }

        [$limitAllowed, $remaining] = self::checkDayLimit($staffId, $date);
        if (!$limitAllowed) {
            $maxOrders = max(1, (int)($rule['max_orders_per_day'] ?? 1));
            return [false, "该日期已达到单日最大接单数（{$maxOrders}单）", [
                'rule' => $rule,
                'remaining' => $remaining,
            ]];
        }

        return [true, '', [
            'rule' => $rule,
            'remaining' => $remaining,
        ]];
    }

    /**
     * @notes 统计当天已占用接单名额的订单项
     * @param int $staffId
     * @param string $date
     * @return int
     */
    public static function countOccupiedOrders(int $staffId, string $date): int
    {
        if ($staffId <= 0 || $date === '') {
            return 0;
        }

        $orderTable = (new Order())->getTable();

        return OrderItem::alias('oi')
            ->join($orderTable . ' o', 'o.id = oi.order_id')
            ->where('oi.staff_id', $staffId)
            ->where('oi.service_date', $date)
            ->whereIn('oi.item_type', [OrderItem::TYPE_SERVICE, OrderItem::TYPE_RELATED_STAFF])
            ->where('oi.item_status', '<>', OrderItem::STATUS_CANCELLED)
            ->whereNotIn('o.order_status', self::NON_OCCUPY_ORDER_STATUSES)
            ->count();
    }

    /**
     * @notes 规范化休息日
     * @param mixed $restDays
     * @return array
     */
    public static function normalizeRestDays($restDays): array
    {
        if (is_string($restDays)) {
            $restDays = $restDays === '' ? [] : explode(',', $restDays);
        }
        if (!is_array($restDays)) {
            return [];
        }

        $restDays = array_map('intval', $restDays);
        $restDays = array_filter($restDays, static fn (int $day) => $day >= 0 && $day <= 6);
        return array_values(array_unique($restDays));
    }
}
