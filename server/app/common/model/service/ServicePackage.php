<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统
// +----------------------------------------------------------------------

namespace app\common\model\service;

use app\common\model\BaseModel;
use app\common\model\schedule\Schedule;
use app\common\model\staff\Staff;
use app\common\model\staff\StaffPackage;
use think\model\concern\SoftDelete;

/**
 * 服务套餐模型
 * Class ServicePackage
 * @package app\common\model\service
 */
class ServicePackage extends BaseModel
{
    use SoftDelete;

    protected $name = 'service_package';
    protected $deleteTime = 'delete_time';

    // 套餐类型常量
    const TYPE_GLOBAL = 1;      // 全局套餐
    const TYPE_STAFF_ONLY = 2;  // 人员专属套餐

    // 预约类型常量
    const BOOKING_TYPE_FULL_DAY = 0;   // 全天套餐
    const BOOKING_TYPE_MULTI_SLOT = 1; // 分场次套餐

    /**
     * @notes 获取所属分类
     * @return \think\model\relation\BelongsTo
     */
    public function category()
    {
        return $this->belongsTo(ServiceCategory::class, 'category_id', 'id');
    }

    /**
     * @notes 关联工作人员（人员专属套餐）
     * @return \think\model\relation\BelongsTo
     */
    public function staff()
    {
        return $this->belongsTo(Staff::class, 'staff_id', 'id');
    }

    /**
     * @notes 套餐内容获取器(JSON转数组)
     * @param $value
     * @return array
     */
    public function getContentAttr($value)
    {
        return $value ? json_decode($value, true) : [];
    }

    /**
     * @notes 套餐内容设置器(数组转JSON)
     * @param $value
     * @return false|string
     */
    public function setContentAttr($value)
    {
        return $value ? json_encode($value, JSON_UNESCAPED_UNICODE) : '';
    }

    /**
     * @notes 时段价格获取器(JSON转数组)
     * @param $value
     * @return array
     */
    public function getSlotPricesAttr($value)
    {
        return $value ? json_decode($value, true) : [];
    }

    /**
     * @notes 时段价格设置器(数组转JSON)
     * @param $value
     * @return string
     */
    public function setSlotPricesAttr($value)
    {
        return $value ? json_encode($value, JSON_UNESCAPED_UNICODE) : '';
    }

    /**
     * @notes 允许场次获取器(JSON转数组)
     * @param $value
     * @return array
     */
    public function getAllowedTimeSlotsAttr($value): array
    {
        return $value ? (json_decode($value, true) ?: []) : [];
    }

    /**
     * @notes 允许场次设置器(数组转JSON)
     * @param $value
     * @return string
     */
    public function setAllowedTimeSlotsAttr($value): string
    {
        if (is_array($value)) {
            return json_encode(array_values($value), JSON_UNESCAPED_UNICODE);
        }
        return $value ?: '';
    }

    /**
     * @notes 获取套餐的有效预约配置（含人员覆盖）
     * @param int $packageId
     * @param int $staffId
     * @return array|null
     */
    public static function resolveBookingConfig(int $packageId, int $staffId = 0): ?array
    {
        $package = self::find($packageId);
        if (!$package) {
            return null;
        }

        $bookingType = isset($package->booking_type) ? (int)$package->booking_type : self::BOOKING_TYPE_FULL_DAY;
        $allowedTimeSlots = $package->allowed_time_slots ?? [];

        if ($staffId > 0) {
            $staffPackage = StaffPackage::where('staff_id', $staffId)
                ->where('package_id', $packageId)
                ->where('status', 1)
                ->find();
            if ($staffPackage) {
                if ($staffPackage->booking_type !== null && $staffPackage->booking_type !== '') {
                    $bookingType = (int)$staffPackage->booking_type;
                }
                if ($staffPackage->allowed_time_slots !== null && $staffPackage->allowed_time_slots !== '') {
                    $allowedTimeSlots = $staffPackage->allowed_time_slots;
                }
            }
        }

        $bookingType = in_array($bookingType, [self::BOOKING_TYPE_FULL_DAY, self::BOOKING_TYPE_MULTI_SLOT], true)
            ? $bookingType
            : self::BOOKING_TYPE_FULL_DAY;
        $allowedTimeSlots = self::normalizeTimeSlots($allowedTimeSlots);
        $allowedTimeSlots = array_values(array_intersect($allowedTimeSlots, [
            Schedule::TIME_SLOT_MORNING,
            Schedule::TIME_SLOT_AFTERNOON,
            Schedule::TIME_SLOT_EVENING,
        ]));

        if ($bookingType === self::BOOKING_TYPE_MULTI_SLOT && empty($allowedTimeSlots)) {
            $allowedTimeSlots = [
                Schedule::TIME_SLOT_MORNING,
                Schedule::TIME_SLOT_AFTERNOON,
                Schedule::TIME_SLOT_EVENING,
            ];
        }

        if ($bookingType === self::BOOKING_TYPE_FULL_DAY) {
            $allowedTimeSlots = [];
        }

        return [
            'booking_type' => $bookingType,
            'allowed_time_slots' => $allowedTimeSlots,
        ];
    }

    /**
     * @notes 校验套餐场次选择是否匹配配置
     * @param int $packageId
     * @param int $staffId
     * @param array $timeSlots
     * @return array [bool, string, array|null]
     */
    public static function validateTimeSlots(int $packageId, int $staffId, array $timeSlots): array
    {
        $config = self::resolveBookingConfig($packageId, $staffId);
        if (!$config) {
            return [false, '套餐不存在', null];
        }

        $timeSlots = self::normalizeTimeSlots($timeSlots);
        if (empty($timeSlots)) {
            return [false, '请选择时间段', $config];
        }

        if ($config['booking_type'] === self::BOOKING_TYPE_FULL_DAY) {
            if (count($timeSlots) !== 1 || $timeSlots[0] !== Schedule::TIME_SLOT_ALL) {
                return [false, '全天套餐仅支持选择全天', $config];
            }
            return [true, '', $config];
        }

        foreach ($timeSlots as $slot) {
            if ($slot === Schedule::TIME_SLOT_ALL) {
                return [false, '分场次套餐不支持选择全天', $config];
            }
            if (!in_array($slot, $config['allowed_time_slots'], true)) {
                return [false, '所选场次不在允许范围内', $config];
            }
        }

        return [true, '', $config];
    }

    /**
     * @notes 归一化时间段数组
     * @param mixed $timeSlots
     * @return array
     */
    protected static function normalizeTimeSlots($timeSlots): array
    {
        if (!is_array($timeSlots)) {
            return [];
        }

        $normalized = array_map('intval', $timeSlots);
        return array_values(array_unique($normalized));
    }

    /**
     * @notes 获取指定时段的价格
     * @param string $startTime 开始时间 HH:mm
     * @param string $endTime 结束时间 HH:mm
     * @return float|null 匹配的时段价格，未找到返回null
     */
    public function getSlotPrice(string $startTime, string $endTime): ?float
    {
        $slotPrices = $this->slot_prices ?? [];
        foreach ($slotPrices as $slot) {
            if (isset($slot['start_time']) && isset($slot['end_time']) &&
                $slot['start_time'] === $startTime && $slot['end_time'] === $endTime) {
                return isset($slot['price']) ? (float)$slot['price'] : null;
            }
        }
        return null;
    }

    /**
     * @notes 获取指定场次的价格
     * @param int $timeSlot
     * @return float|null
     */
    public function getSlotPriceByTimeSlot(int $timeSlot): ?float
    {
        $slotPrices = $this->slot_prices ?? [];
        foreach ($slotPrices as $slot) {
            if (isset($slot['time_slot']) && (int)$slot['time_slot'] === $timeSlot) {
                return isset($slot['price']) ? (float)$slot['price'] : null;
            }
        }
        return null;
    }

    /**
     * @notes 获取指定场次的价格（未设置则返回默认价）
     * @param int $timeSlot
     * @return float
     */
    public function calculatePriceByTimeSlot(int $timeSlot = 0): float
    {
        $slotPrice = $this->getSlotPriceByTimeSlot($timeSlot);
        return $slotPrice !== null ? $slotPrice : (float)$this->price;
    }

    /**
     * @notes 计算最终价格（考虑时段）
     * @param string $startTime 开始时间（可选）
     * @param string $endTime 结束时间（可选）
     * @return float
     */
    public function calculatePrice(string $startTime = '', string $endTime = ''): float
    {
        // 如果指定了时段，优先使用时段价格
        if ($startTime && $endTime) {
            $slotPrice = $this->getSlotPrice($startTime, $endTime);
            if ($slotPrice !== null) {
                return $slotPrice;
            }
        }
        // 返回默认价格
        return (float)$this->price;
    }

    /**
     * @notes 检查是否为人员专属套餐
     * @return bool
     */
    public function isStaffOnly(): bool
    {
        return $this->package_type == self::TYPE_STAFF_ONLY && $this->staff_id > 0;
    }

    /**
     * @notes 检查是否为全局套餐
     * @return bool
     */
    public function isGlobal(): bool
    {
        return $this->package_type == self::TYPE_GLOBAL || $this->staff_id == 0;
    }

    /**
     * @notes 分类名称获取器
     * @param $value
     * @param $data
     * @return string
     */
    public function getCategoryNameAttr($value, $data)
    {
        $category = ServiceCategory::find($data['category_id']);
        return $category ? $category->name : '';
    }

    /**
     * @notes 状态描述获取器
     * @param $value
     * @param $data
     * @return string
     */
    public function getIsShowDescAttr($value, $data)
    {
        return $data['is_show'] ? '上架' : '下架';
    }

    /**
     * @notes 推荐描述获取器
     * @param $value
     * @param $data
     * @return string
     */
    public function getIsRecommendDescAttr($value, $data)
    {
        return $data['is_recommend'] ? '是' : '否';
    }

    /**
     * @notes 服务时长格式化获取器
     * @param $value
     * @param $data
     * @return string
     */
    public function getDurationDescAttr($value, $data)
    {
        return $data['duration'] . '小时';
    }

    /**
     * @notes 套餐类型描述获取器
     * @param $value
     * @param $data
     * @return string
     */
    public function getPackageTypeDescAttr($value, $data)
    {
        $typeMap = [
            self::TYPE_GLOBAL => '全局套餐',
            self::TYPE_STAFF_ONLY => '人员专属',
        ];
        $packageType = $data['package_type'] ?? self::TYPE_GLOBAL;
        return $typeMap[$packageType] ?? '未知';
    }

    /**
     * @notes 获取所属工作人员名称
     * @param $value
     * @param $data
     * @return string
     */
    public function getStaffNameAttr($value, $data)
    {
        if (empty($data['staff_id'])) {
            return '';
        }
        try {
            $staff = Staff::find($data['staff_id']);
            return $staff ? $staff->name : '';
        } catch (\Exception $e) {
            return '';
        }
    }
}
