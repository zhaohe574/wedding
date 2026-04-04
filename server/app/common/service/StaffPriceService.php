<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\common\service;

use app\common\model\service\ServicePackage;

/**
 * 服务人员展示价格服务
 * 口径：仅取人员专属套餐固定价中的最小值
 */
class StaffPriceService
{
    /**
     * @notes 批量获取人员展示价格映射
     * @param array $staffIds
     * @param array $regionContext
     * @return array [staffId => ['price' => ?float, 'has_price' => bool, 'price_text' => string]]
     */
    public static function getDisplayPriceMap(array $staffIds, array $regionContext = []): array
    {
        $staffIds = array_values(array_unique(array_filter(array_map('intval', $staffIds))));
        if (empty($staffIds)) {
            return [];
        }

        $packages = ServicePackage::whereIn('staff_id', $staffIds)
            ->where('is_show', 1)
            ->whereNull('delete_time')
            ->field(['id', 'staff_id', 'price'])
            ->select()
            ->toArray();

        $priceCandidates = [];
        foreach ($staffIds as $staffId) {
            $priceCandidates[$staffId] = [];
        }

        $normalizedRegion = PackageRegionPriceService::normalizeRegionContext($regionContext);
        $useRegionPrice = PackageRegionPriceService::hasRegionContext($normalizedRegion);
        $resolvedPriceMap = [];
        if ($useRegionPrice) {
            $resolvedPriceMap = PackageRegionPriceService::resolvePackagePriceMap(array_column($packages, 'id'), $normalizedRegion);
        }

        foreach ($packages as $package) {
            $staffId = (int)($package['staff_id'] ?? 0);
            if ($staffId <= 0 || !isset($priceCandidates[$staffId])) {
                continue;
            }

            if ($useRegionPrice) {
                $resolved = $resolvedPriceMap[(int)($package['id'] ?? 0)] ?? ['available' => false];
                if (!($resolved['available'] ?? false)) {
                    continue;
                }
                $priceCandidates[$staffId][] = round((float)($resolved['price'] ?? 0), 2);
                continue;
            }

            $price = round((float)($package['price'] ?? 0), 2);
            if ($price > 0) {
                $priceCandidates[$staffId][] = $price;
            }
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
     * @param array $regionContext
     * @return array
     */
    public static function getDisplayPriceByStaffId(int $staffId, array $regionContext = []): array
    {
        $staffId = (int)$staffId;
        if ($staffId <= 0) {
            return self::buildDisplayPrice(null);
        }
        $map = self::getDisplayPriceMap([$staffId], $regionContext);
        return $map[$staffId] ?? self::buildDisplayPrice(null);
    }

    /**
     * @notes 批量注入展示价格字段
     * @param array $rows
     * @param string $staffIdField
     * @param array $regionContext
     * @return void
     */
    public static function injectDisplayPrice(array &$rows, string $staffIdField = 'id', array $regionContext = []): void
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
        $priceMap = self::getDisplayPriceMap($staffIds, $regionContext);

        foreach ($rows as &$row) {
            $staffId = (int)($row[$staffIdField] ?? 0);
            $display = $priceMap[$staffId] ?? self::buildDisplayPrice(null);
            $row['price'] = $display['price'];
            $row['has_price'] = $display['has_price'];
            $row['price_text'] = $display['price_text'];
        }
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
     * @notes 计算订单项价格（固定价）
     * @param int $staffId
     * @param int $packageId
     * @param int $timeSlot
     * @param array $regionContext
     * @return float
     */
    public static function calculateOrderItemPrice(int $staffId, int $packageId, int $timeSlot = 0, array $regionContext = []): float
    {
        if ($staffId <= 0 || $packageId <= 0) {
            return 0.00;
        }

        $package = ServicePackage::where('id', $packageId)
            ->where('staff_id', $staffId)
            ->where('is_show', 1)
            ->whereNull('delete_time')
            ->find();

        if ($package) {
            $normalizedRegion = PackageRegionPriceService::normalizeRegionContext($regionContext);
            if (PackageRegionPriceService::hasRegionContext($normalizedRegion)) {
                $resolved = PackageRegionPriceService::resolvePackagePrice($packageId, $normalizedRegion);
                if ($resolved['available'] ?? false) {
                    return round((float)($resolved['price'] ?? 0), 2);
                }
                return 0.00;
            }
            return round((float)$package->price, 2);
        }

        return 0.00;
    }
}
