<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\common\service;

use app\common\model\service\ServiceCityPool;
use app\common\model\service\ServicePackageRegionPrice;

/**
 * 套餐地区价格服务
 */
class PackageRegionPriceService
{
    /**
     * @notes 同步套餐地区价格
     * @param int $packageId
     * @param int $staffId
     * @param array $regionPrices
     * @return void
     */
    public static function syncPackageRegionPrices(int $packageId, int $staffId, array $regionPrices): void
    {
        if ($packageId <= 0 || $staffId <= 0) {
            throw new \InvalidArgumentException('套餐地区价格参数错误');
        }

        $rows = self::normalizeRegionPrices($regionPrices, true);
        ServicePackageRegionPrice::where('package_id', $packageId)->delete();

        if (empty($rows)) {
            return;
        }

        $now = time();
        foreach ($rows as &$row) {
            $row['package_id'] = $packageId;
            $row['staff_id'] = $staffId;
            $row['create_time'] = $now;
            $row['update_time'] = $now;
        }

        (new ServicePackageRegionPrice())->saveAll($rows);
    }

    /**
     * @notes 获取套餐地区价映射
     * @param array $packageIds
     * @return array
     */
    public static function getRegionPriceMap(array $packageIds): array
    {
        $packageIds = array_values(array_unique(array_filter(array_map('intval', $packageIds))));
        if (empty($packageIds)) {
            return [];
        }

        $rows = ServicePackageRegionPrice::whereIn('package_id', $packageIds)
            ->order('region_level', 'desc')
            ->order('id', 'asc')
            ->select()
            ->toArray();

        $map = [];
        foreach ($rows as $row) {
            $packageId = (int)($row['package_id'] ?? 0);
            if ($packageId <= 0) {
                continue;
            }
            $map[$packageId][] = $row;
        }

        return $map;
    }

    /**
     * @notes 给套餐追加地区价格
     * @param array $packages
     * @return array
     */
    public static function attachRegionPrices(array $packages): array
    {
        if (empty($packages)) {
            return [];
        }

        $map = self::getRegionPriceMap(array_column($packages, 'id'));
        foreach ($packages as &$package) {
            $package['region_prices'] = array_values($map[(int)($package['id'] ?? 0)] ?? []);
        }

        return $packages;
    }

    /**
     * @notes 解析单个套餐地区价
     * @param int $packageId
     * @param array $regionContext
     * @return array
     */
    public static function resolvePackagePrice(int $packageId, array $regionContext): array
    {
        $map = self::resolvePackagePriceMap([$packageId], $regionContext);
        return $map[$packageId] ?? self::buildUnavailableResult();
    }

    /**
     * @notes 批量解析套餐地区价
     * @param array $packageIds
     * @param array $regionContext
     * @return array
     */
    public static function resolvePackagePriceMap(array $packageIds, array $regionContext): array
    {
        $context = self::normalizeRegionContext($regionContext);
        $packageIds = array_values(array_unique(array_filter(array_map('intval', $packageIds))));
        if (empty($packageIds) || !self::hasRegionContext($context)) {
            return [];
        }

        $priceMap = self::getRegionPriceMap($packageIds);
        $result = [];
        foreach ($packageIds as $packageId) {
            $result[$packageId] = self::resolveFromRows($priceMap[$packageId] ?? [], $context);
        }

        return $result;
    }

    /**
     * @notes 按地区覆盖套餐价格
     * @param array $packages
     * @param array $regionContext
     * @param bool $filterUnavailable
     * @return array
     */
    public static function applyResolvedPrices(array $packages, array $regionContext, bool $filterUnavailable = true): array
    {
        if (empty($packages)) {
            return [];
        }

        $resolvedMap = self::resolvePackagePriceMap(array_column($packages, 'id'), $regionContext);
        $result = [];

        foreach ($packages as $package) {
            $packageId = (int)($package['id'] ?? 0);
            $resolved = $resolvedMap[$packageId] ?? self::buildUnavailableResult();
            if (!$resolved['available'] && $filterUnavailable) {
                continue;
            }

            $package['base_price'] = round((float)($package['price'] ?? 0), 2);
            $package['region_price'] = $resolved['price'];
            $package['region_price_text'] = $resolved['price_text'];
            $package['region_match_level'] = $resolved['match_level'];
            $package['region_match_name'] = $resolved['match_name'];
            $package['has_region_price'] = $resolved['available'];

            if ($resolved['available']) {
                $package['price'] = $resolved['price'];
            }

            $result[] = $package;
        }

        return $result;
    }

    /**
     * @notes 归一化地区上下文
     * @param array $regionContext
     * @return array
     */
    public static function normalizeRegionContext(array $regionContext): array
    {
        return RegionDataService::fillRegionContext([
            'province_code' => trim((string)($regionContext['province_code'] ?? '')),
            'province_name' => trim((string)($regionContext['province_name'] ?? '')),
            'city_code' => trim((string)($regionContext['city_code'] ?? '')),
            'city_name' => trim((string)($regionContext['city_name'] ?? '')),
            'district_code' => trim((string)($regionContext['district_code'] ?? '')),
            'district_name' => trim((string)($regionContext['district_name'] ?? '')),
        ]);
    }

    /**
     * @notes 校验地区是否已开通
     * @param array $regionContext
     * @return array
     */
    public static function validateEnabledRegion(array $regionContext): array
    {
        $context = self::normalizeRegionContext($regionContext);
        if ($context['city_code'] === '') {
            throw new \InvalidArgumentException('请选择服务城市');
        }

        $enabled = ServiceCityPool::where('city_code', $context['city_code'])
            ->where('status', 1)
            ->find();
        if (!$enabled) {
            throw new \InvalidArgumentException('当前城市暂未开通服务');
        }

        if ($context['district_code'] === '') {
            throw new \InvalidArgumentException('请选择服务区县');
        }

        if (!RegionDataService::isDistrictInCity($context['district_code'], $context['city_code'])) {
            throw new \InvalidArgumentException('服务区县与城市不匹配');
        }

        return $context;
    }

    /**
     * @notes 是否已经选择到区县
     * @param array $regionContext
     * @return bool
     */
    public static function hasRegionContext(array $regionContext): bool
    {
        return trim((string)($regionContext['city_code'] ?? '')) !== ''
            && trim((string)($regionContext['district_code'] ?? '')) !== '';
    }

    /**
     * @notes 归一化套餐地区价格
     * @param array $regionPrices
     * @param bool $validateEnabledCity
     * @return array
     */
    private static function normalizeRegionPrices(array $regionPrices, bool $validateEnabledCity = false): array
    {
        $result = [];
        $uniq = [];

        foreach ($regionPrices as $item) {
            if (!is_array($item)) {
                continue;
            }

            $regionLevel = (int)($item['region_level'] ?? 0);
            if (!in_array(
                $regionLevel,
                [
                    ServicePackageRegionPrice::LEVEL_PROVINCE,
                    ServicePackageRegionPrice::LEVEL_CITY,
                    ServicePackageRegionPrice::LEVEL_DISTRICT,
                ],
                true
            )) {
                throw new \InvalidArgumentException('地区价格层级错误');
            }

            $price = round((float)($item['price'] ?? 0), 2);
            if ($price < 0) {
                throw new \InvalidArgumentException('地区价格不能小于0');
            }

            $context = self::normalizeRegionContext($item);
            if ($regionLevel === ServicePackageRegionPrice::LEVEL_PROVINCE) {
                if ($context['province_code'] === '') {
                    throw new \InvalidArgumentException('请选择服务省份');
                }

                if ($validateEnabledCity) {
                    $enabled = ServiceCityPool::where('province_code', $context['province_code'])
                        ->where('status', 1)
                        ->find();
                    if (!$enabled) {
                        throw new \InvalidArgumentException('存在未开通的服务省份');
                    }
                }

                $context['city_code'] = '';
                $context['city_name'] = '';
                $context['district_code'] = '';
                $context['district_name'] = '';
            } else {
                if ($context['city_code'] === '') {
                    throw new \InvalidArgumentException('请选择服务城市');
                }

                if ($validateEnabledCity) {
                    $enabled = ServiceCityPool::where('city_code', $context['city_code'])
                        ->where('status', 1)
                        ->find();
                    if (!$enabled) {
                        throw new \InvalidArgumentException('存在未开通的服务城市');
                    }
                }
            }

            if ($regionLevel === ServicePackageRegionPrice::LEVEL_DISTRICT) {
                if ($context['district_code'] === '') {
                    throw new \InvalidArgumentException('区县价格必须选择区县');
                }
                if (!RegionDataService::isDistrictInCity($context['district_code'], $context['city_code'])) {
                    throw new \InvalidArgumentException('区县不属于当前城市');
                }
            } elseif ($regionLevel === ServicePackageRegionPrice::LEVEL_CITY) {
                $context['district_code'] = '';
                $context['district_name'] = '';
            }

            $uniqKey = implode(':', [
                $regionLevel,
                $context['province_code'],
                $context['city_code'],
                $context['district_code'],
            ]);
            if (isset($uniq[$uniqKey])) {
                throw new \InvalidArgumentException('地区价格存在重复配置');
            }
            $uniq[$uniqKey] = true;

            $result[] = [
                'region_level' => $regionLevel,
                'province_code' => $context['province_code'],
                'province_name' => $context['province_name'],
                'city_code' => $context['city_code'],
                'city_name' => $context['city_name'],
                'district_code' => $context['district_code'],
                'district_name' => $context['district_name'],
                'price' => $price,
            ];
        }

        return $result;
    }

    /**
     * @notes 从地区价列表中匹配有效价格
     * @param array $rows
     * @param array $context
     * @return array
     */
    private static function resolveFromRows(array $rows, array $context): array
    {
        $provinceCode = trim((string)($context['province_code'] ?? ''));
        $cityCode = trim((string)($context['city_code'] ?? ''));
        $districtCode = trim((string)($context['district_code'] ?? ''));

        foreach ($rows as $row) {
            if ((int)($row['region_level'] ?? 0) !== ServicePackageRegionPrice::LEVEL_DISTRICT) {
                continue;
            }
            if ((string)($row['district_code'] ?? '') !== $districtCode) {
                continue;
            }

            $price = round((float)($row['price'] ?? 0), 2);
            return [
                'available' => true,
                'price' => $price,
                'price_text' => self::formatPriceText($price),
                'match_level' => ServicePackageRegionPrice::LEVEL_DISTRICT,
                'match_name' => (string)($row['district_name'] ?? ''),
            ];
        }

        foreach ($rows as $row) {
            if ((int)($row['region_level'] ?? 0) !== ServicePackageRegionPrice::LEVEL_CITY) {
                continue;
            }
            if ((string)($row['city_code'] ?? '') !== $cityCode) {
                continue;
            }

            $price = round((float)($row['price'] ?? 0), 2);
            return [
                'available' => true,
                'price' => $price,
                'price_text' => self::formatPriceText($price),
                'match_level' => ServicePackageRegionPrice::LEVEL_CITY,
                'match_name' => (string)($row['city_name'] ?? ''),
            ];
        }

        foreach ($rows as $row) {
            if ((int)($row['region_level'] ?? 0) !== ServicePackageRegionPrice::LEVEL_PROVINCE) {
                continue;
            }
            if ((string)($row['province_code'] ?? '') !== $provinceCode) {
                continue;
            }

            $price = round((float)($row['price'] ?? 0), 2);
            return [
                'available' => true,
                'price' => $price,
                'price_text' => self::formatPriceText($price),
                'match_level' => ServicePackageRegionPrice::LEVEL_PROVINCE,
                'match_name' => (string)($row['province_name'] ?? ''),
            ];
        }

        return self::buildUnavailableResult();
    }

    /**
     * @notes 不可售结果
     * @return array
     */
    private static function buildUnavailableResult(): array
    {
        return [
            'available' => false,
            'price' => null,
            'price_text' => '',
            'match_level' => 0,
            'match_name' => '',
        ];
    }

    /**
     * @notes 格式化价格文本
     * @param float $price
     * @return string
     */
    private static function formatPriceText(float $price): string
    {
        $formatted = number_format($price, 2, '.', '');
        return rtrim(rtrim($formatted, '0'), '.');
    }
}
