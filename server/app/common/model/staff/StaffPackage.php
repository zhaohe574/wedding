<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统
// +----------------------------------------------------------------------

namespace app\common\model\staff;

use app\common\model\BaseModel;

/**
 * 工作人员套餐关联模型
 * Class StaffPackage
 * @package app\common\model\staff
 */
class StaffPackage extends BaseModel
{
    protected $name = 'staff_package';

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
        return $this->belongsTo(\app\common\model\service\ServicePackage::class, 'package_id', 'id');
    }

    /**
     * @notes 个人时段价格获取器(JSON转数组)
     * @param $value
     * @return array
     */
    public function getCustomSlotPricesAttr($value)
    {
        return $value ? json_decode($value, true) : [];
    }

    /**
     * @notes 个人时段价格设置器(数组转JSON)
     * @param $value
     * @return string
     */
    public function setCustomSlotPricesAttr($value)
    {
        if (is_array($value)) {
            return json_encode($value, JSON_UNESCAPED_UNICODE);
        }
        if ($value === '' || $value === null) {
            return null;
        }
        return $value;
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
    public function setAllowedTimeSlotsAttr($value): ?string
    {
        if (is_array($value)) {
            return json_encode(array_values($value), JSON_UNESCAPED_UNICODE);
        }
        if ($value === '' || $value === null) {
            return null;
        }
        return $value;
    }

    /**
     * @notes 获取指定时段的个人定制价格
     * @param string $startTime 开始时间 HH:mm
     * @param string $endTime 结束时间 HH:mm
     * @return float|null 匹配的时段价格，未找到返回null
     */
    public function getCustomSlotPrice(string $startTime, string $endTime): ?float
    {
        $slotPrices = $this->custom_slot_prices ?? [];
        foreach ($slotPrices as $slot) {
            if (isset($slot['start_time']) && isset($slot['end_time']) &&
                $slot['start_time'] === $startTime && $slot['end_time'] === $endTime) {
                return isset($slot['price']) ? (float)$slot['price'] : null;
            }
        }
        return null;
    }

    /**
     * @notes 获取指定场次的个人定制价格
     * @param int $timeSlot
     * @return float|null
     */
    public function getCustomSlotPriceByTimeSlot(int $timeSlot): ?float
    {
        $slotPrices = $this->custom_slot_prices ?? [];
        foreach ($slotPrices as $slot) {
            if (isset($slot['time_slot']) && (int)$slot['time_slot'] === $timeSlot) {
                return isset($slot['price']) ? (float)$slot['price'] : null;
            }
        }
        return null;
    }

    /**
     * @notes 获取指定场次的最终价格
     * 优先级：个人时段价格 > 个人统一价格 > 套餐时段价格 > 套餐默认价格
     * @param int $timeSlot
     * @return float
     */
    public function calculatePriceByTimeSlot(int $timeSlot = 0): float
    {
        $customSlotPrice = $this->getCustomSlotPriceByTimeSlot($timeSlot);
        if ($customSlotPrice !== null) {
            return $customSlotPrice;
        }

        if ($this->custom_price !== null && $this->custom_price !== '') {
            return (float)$this->custom_price;
        }

        $package = $this->package;
        if (!$package) {
            return 0;
        }

        $slotPrice = $package->getSlotPriceByTimeSlot($timeSlot);
        if ($slotPrice !== null) {
            return $slotPrice;
        }

        return (float)$package->price;
    }

    /**
     * @notes 计算最终价格（6级优先级）
     * 优先级：个人时段价格 > 个人统一价格 > 套餐时段价格 > 套餐默认价格
     * @param string $startTime 开始时间（可选）
     * @param string $endTime 结束时间（可选）
     * @return float
     */
    public function calculatePrice(string $startTime = '', string $endTime = ''): float
    {
        // 优先级1：个人时段价格
        if ($startTime && $endTime) {
            $customSlotPrice = $this->getCustomSlotPrice($startTime, $endTime);
            if ($customSlotPrice !== null) {
                return $customSlotPrice;
            }
        }

        // 优先级2：个人统一价格
        if ($this->custom_price !== null && $this->custom_price !== '') {
            return (float)$this->custom_price;
        }

        // 获取关联的套餐
        $package = $this->package;
        if (!$package) {
            return 0;
        }

        // 优先级3：套餐时段价格
        if ($startTime && $endTime) {
            $slotPrice = $package->getSlotPrice($startTime, $endTime);
            if ($slotPrice !== null) {
                return $slotPrice;
            }
        }

        // 优先级4：套餐默认价格
        return (float)$package->price;
    }

    /**
     * @notes 批量设置套餐（增强版，支持个人价格和时段价格）
     * @param int $staffId
     * @param array $packages 格式: [['package_id' => 1, 'price' => 100.00, 'original_price' => 200.00, 'custom_price' => 100.00, 'custom_slot_prices' => [...], 'status' => 1], ...]
     * @return void
     */
    public static function setPackages(int $staffId, array $packages): void
    {
        // 删除原有套餐
        self::where('staff_id', $staffId)->delete();

        // 添加新套餐
        if (!empty($packages)) {
            $data = [];
            $time = time();
            foreach ($packages as $pkg) {
                $item = [
                    'staff_id' => $staffId,
                    'package_id' => $pkg['package_id'],
                    'price' => $pkg['price'] ?? 0,
                    'original_price' => $pkg['original_price'] ?? null,
                    'is_default' => $pkg['is_default'] ?? 0,
                    'custom_price' => $pkg['custom_price'] ?? null,
                    'custom_slot_prices' => isset($pkg['custom_slot_prices']) && is_array($pkg['custom_slot_prices'])
                        ? json_encode($pkg['custom_slot_prices'], JSON_UNESCAPED_UNICODE)
                        : null,
                    'booking_type' => $pkg['booking_type'] ?? null,
                    'allowed_time_slots' => isset($pkg['allowed_time_slots']) && is_array($pkg['allowed_time_slots'])
                        ? json_encode($pkg['allowed_time_slots'], JSON_UNESCAPED_UNICODE)
                        : ($pkg['allowed_time_slots'] ?? null),
                    'status' => $pkg['status'] ?? 1,
                    'create_time' => $time,
                    'update_time' => $time,
                ];
                $data[] = $item;
            }
            (new self())->saveAll($data);
        }
    }

    /**
     * @notes 获取工作人员的套餐列表（增强版）
     * @param int $staffId
     * @param bool $onlyActive 是否只返回启用的套餐
     * @return array
     */
    public static function getPackages(int $staffId, bool $onlyActive = false): array
    {
        $query = self::where('staff_id', $staffId)->with(['package']);
        
        if ($onlyActive) {
            $query->where('status', 1);
        }
        
        return $query->select()->toArray();
    }

    /**
     * @notes 获取套餐配置详情（包含价格信息）
     * @param int $staffId
     * @param int $packageId
     * @return array|null
     */
    public static function getPackageConfig(int $staffId, int $packageId): ?array
    {
        $config = self::where('staff_id', $staffId)
            ->where('package_id', $packageId)
            ->with(['package'])
            ->find();

        return $config ? $config->toArray() : null;
    }

    /**
     * @notes 更新单个套餐配置
     * @param int $staffId
     * @param int $packageId
     * @param array $data
     * @return bool
     */
    public static function updatePackageConfig(int $staffId, int $packageId, array $data): bool
    {
        $config = self::where('staff_id', $staffId)
            ->where('package_id', $packageId)
            ->find();

        if (!$config) {
            return false;
        }

        $updateData = [];
        if (isset($data['price'])) {
            $updateData['price'] = $data['price'];
        }
        if (array_key_exists('original_price', $data)) {
            $updateData['original_price'] = $data['original_price'];
        }
        if (isset($data['custom_price'])) {
            $updateData['custom_price'] = $data['custom_price'];
        }
        if (isset($data['custom_slot_prices'])) {
            if (is_array($data['custom_slot_prices'])) {
                $updateData['custom_slot_prices'] = json_encode($data['custom_slot_prices'], JSON_UNESCAPED_UNICODE);
            } else {
                $updateData['custom_slot_prices'] = $data['custom_slot_prices'] === '' ? null : $data['custom_slot_prices'];
            }
        }
        if (array_key_exists('booking_type', $data)) {
            $updateData['booking_type'] = $data['booking_type'];
        }
        if (isset($data['allowed_time_slots'])) {
            if (is_array($data['allowed_time_slots'])) {
                $updateData['allowed_time_slots'] = json_encode($data['allowed_time_slots'], JSON_UNESCAPED_UNICODE);
            } else {
                $updateData['allowed_time_slots'] = $data['allowed_time_slots'] === '' ? null : $data['allowed_time_slots'];
            }
        }
        if (isset($data['status'])) {
            $updateData['status'] = $data['status'];
        }

        if (!empty($updateData)) {
            $updateData['update_time'] = time();
            return $config->save($updateData);
        }

        return true;
    }

    /**
     * @notes 状态描述获取器
     * @param $value
     * @param $data
     * @return string
     */
    public function getStatusDescAttr($value, $data)
    {
        return ($data['status'] ?? 1) ? '启用' : '禁用';
    }
}
