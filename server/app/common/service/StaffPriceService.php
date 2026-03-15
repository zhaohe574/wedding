<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\common\service;

use app\common\model\service\ServicePackage;
use app\common\model\staff\StaffPackage;

/**
 * 服务人员展示价格服务
 * 口径：取可售套餐候选价格中的最小值
 */
class StaffPriceService
{
    /**
     * @notes 批量获取人员展示价格映射
     * @param array $staffIds
     * @return array [staffId => ['price' => ?float, 'has_price' => bool, 'price_text' => string]]
     */
    public static function getDisplayPriceMap(array $staffIds): array
    {
        $staffIds = array_values(array_unique(array_filter(array_map('intval', $staffIds))));
        if (empty($staffIds)) {
            return [];
        }

        $priceCandidates = [];
        $linkedPackageMap = [];

        foreach ($staffIds as $staffId) {
            $priceCandidates[$staffId] = [];
            $linkedPackageMap[$staffId] = [];
        }

        // 1) 人员已关联且启用的套餐（以 staff_package 配置为准）
        $linkedPackages = StaffPackage::alias('sp')
            ->join('service_package p', 'p.id = sp.package_id')
            ->whereIn('sp.staff_id', $staffIds)
            ->where('sp.status', 1)
            ->where('p.is_show', 1)
            ->whereNull('p.delete_time')
            ->field([
                'sp.staff_id',
                'sp.package_id',
                'sp.price as staff_price',
                'sp.custom_price',
                'sp.custom_slot_prices',
                'p.price as package_price',
                'p.slot_prices',
            ])
            ->select()
            ->toArray();

        foreach ($linkedPackages as $item) {
            $staffId = (int)($item['staff_id'] ?? 0);
            $packageId = (int)($item['package_id'] ?? 0);
            if ($staffId <= 0 || $packageId <= 0 || !isset($priceCandidates[$staffId])) {
                continue;
            }

            $linkedPackageMap[$staffId][$packageId] = true;
            $priceCandidates[$staffId] = array_merge(
                $priceCandidates[$staffId],
                self::extractPriceCandidates($item, true)
            );
        }

        // 2) 人员专属套餐（仅补充未在 staff_package 中显式关联的部分）
        $staffOnlyPackages = ServicePackage::whereIn('staff_id', $staffIds)
            ->where('package_type', ServicePackage::TYPE_STAFF_ONLY)
            ->where('is_show', 1)
            ->whereNull('delete_time')
            ->field(['id as package_id', 'staff_id', 'price as package_price', 'slot_prices'])
            ->select()
            ->toArray();

        foreach ($staffOnlyPackages as $item) {
            $staffId = (int)($item['staff_id'] ?? 0);
            $packageId = (int)($item['package_id'] ?? 0);
            if ($staffId <= 0 || $packageId <= 0 || !isset($priceCandidates[$staffId])) {
                continue;
            }

            // 去重：同一 staff_id + package_id 已有关联配置时，优先关联配置路径
            if (!empty($linkedPackageMap[$staffId][$packageId])) {
                continue;
            }

            $priceCandidates[$staffId] = array_merge(
                $priceCandidates[$staffId],
                self::extractPriceCandidates($item, false)
            );
        }

        $result = [];
        foreach ($staffIds as $staffId) {
            $validPrices = $priceCandidates[$staffId] ?? [];
            $price = empty($validPrices) ? null : min($validPrices);
            $result[$staffId] = self::buildDisplayPrice($price);
        }

        return $result;
    }

    /**
     * @notes 获取单个人员展示价格
     * @param int $staffId
     * @return array
     */
    public static function getDisplayPriceByStaffId(int $staffId): array
    {
        $staffId = (int)$staffId;
        if ($staffId <= 0) {
            return self::buildDisplayPrice(null);
        }
        $map = self::getDisplayPriceMap([$staffId]);
        return $map[$staffId] ?? self::buildDisplayPrice(null);
    }

    /**
     * @notes 批量注入展示价格字段
     * @param array $rows
     * @param string $staffIdField
     * @return void
     */
    public static function injectDisplayPrice(array &$rows, string $staffIdField = 'id'): void
    {
        if (empty($rows)) {
            return;
        }

        $staffIds = [];
        foreach ($rows as $row) {
            $staffId = (int)($row[$staffIdField] ?? 0);
            if ($staffId > 0) {
                $staffIds[] = $staffId;
            }
        }
        $priceMap = self::getDisplayPriceMap($staffIds);

        foreach ($rows as &$row) {
            $staffId = (int)($row[$staffIdField] ?? 0);
            $display = $priceMap[$staffId] ?? self::buildDisplayPrice(null);
            $row['price'] = $display['price'];
            $row['has_price'] = $display['has_price'];
            $row['price_text'] = $display['price_text'];
        }
    }

    /**
     * @notes 提取候选价格
     * 规则顺序：
     * 1. staff_package.custom_slot_prices[*].price
     * 2. staff_package.custom_price
     * 3. staff_package.price
     * 4. service_package.slot_prices[*].price
     * 5. service_package.price
     */
    protected static function extractPriceCandidates(array $row, bool $withStaffPackage): array
    {
        $prices = [];

        if ($withStaffPackage) {
            $prices = array_merge(
                $prices,
                self::extractSlotPrices($row['custom_slot_prices'] ?? null),
                self::extractSinglePrice($row['custom_price'] ?? null),
                self::extractSinglePrice($row['staff_price'] ?? null)
            );
        }

        $prices = array_merge(
            $prices,
            self::extractSlotPrices($row['slot_prices'] ?? null),
            self::extractSinglePrice($row['package_price'] ?? null)
        );

        return $prices;
    }

    /**
     * @notes 提取单个价格（仅保留大于0）
     */
    protected static function extractSinglePrice($value): array
    {
        if ($value === null || $value === '') {
            return [];
        }
        $price = (float)$value;
        return $price > 0 ? [round($price, 2)] : [];
    }

    /**
     * @notes 提取时段价格数组中的价格（仅保留大于0）
     */
    protected static function extractSlotPrices($value): array
    {
        $items = [];
        if (is_array($value)) {
            $items = $value;
        } elseif (is_string($value) && $value !== '') {
            $decoded = json_decode($value, true);
            if (is_array($decoded)) {
                $items = $decoded;
            }
        }

        if (empty($items)) {
            return [];
        }

        $prices = [];
        foreach ($items as $item) {
            if (!is_array($item) || !array_key_exists('price', $item)) {
                continue;
            }
            $price = (float)$item['price'];
            if ($price > 0) {
                $prices[] = round($price, 2);
            }
        }
        return $prices;
    }

    /**
     * @notes 构造统一展示结构
     * @param float|null $price
     * @return array
     */
    protected static function buildDisplayPrice(?float $price): array
    {
        if ($price === null) {
            return [
                'price' => null,
                'has_price' => false,
                'price_text' => '面议',
            ];
        }

        $price = round($price, 2);
        return [
            'price' => $price,
            'has_price' => true,
            'price_text' => self::formatPriceText($price),
        ];
    }

    /**
     * @notes 价格文本格式化
     */
    protected static function formatPriceText(float $price): string
    {
        $formatted = number_format($price, 2, '.', '');
        $formatted = rtrim(rtrim($formatted, '0'), '.');
        return $formatted === '' ? '0' : $formatted;
    }

    /**
     * @notes 计算订单项价格（统一价格计算入口）
     * 优先级：人员场次价 > 人员统一价 > 人员套餐默认价 > 套餐场次价 > 套餐默认价
     * @param int $staffId
     * @param int $packageId
     * @param int $timeSlot
     * @return float
     */
    public static function calculateOrderItemPrice(int $staffId, int $packageId, int $timeSlot = 0): float
    {
        if ($staffId <= 0 || $packageId <= 0) {
            return 0.00;
        }

        $staffPackage = StaffPackage::where('staff_id', $staffId)
            ->where('package_id', $packageId)
            ->where('status', 1)
            ->find();

        if ($staffPackage) {
            $customSlotPrice = $staffPackage->getCustomSlotPriceByTimeSlot($timeSlot);
            if ($customSlotPrice !== null && $customSlotPrice > 0) {
                return round((float)$customSlotPrice, 2);
            }
            if ($staffPackage->custom_price !== null && $staffPackage->custom_price !== '' && (float)$staffPackage->custom_price > 0) {
                return round((float)$staffPackage->custom_price, 2);
            }
            if ($staffPackage->price !== null && $staffPackage->price !== '' && (float)$staffPackage->price > 0) {
                return round((float)$staffPackage->price, 2);
            }
        }

        $package = ServicePackage::where('id', $packageId)
            ->where('is_show', 1)
            ->whereNull('delete_time')
            ->find();

        if ($package) {
            $slotPrice = $package->getSlotPriceByTimeSlot($timeSlot);
            if ($slotPrice !== null && $slotPrice > 0) {
                return round((float)$slotPrice, 2);
            }
            return round((float)$package->price, 2);
        }

        return 0.00;
    }
}
