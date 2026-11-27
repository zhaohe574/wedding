<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\common\model\service;

use app\common\model\BaseModel;

/**
 * 套餐附加服务关联模型
 * Class ServicePackageAddon
 * @package app\common\model\service
 */
class ServicePackageAddon extends BaseModel
{
    protected $name = 'service_package_addon';

    /**
     * @notes 归一化附加服务ID
     * @param mixed $addonIds
     * @return array
     */
    public static function normalizeAddonIds($addonIds): array
    {
        if (!is_array($addonIds)) {
            return [];
        }

        $addonIds = array_map('intval', $addonIds);
        $addonIds = array_filter($addonIds, static fn (int $addonId) => $addonId > 0);
        return array_values(array_unique($addonIds));
    }

    /**
     * @notes 获取单个套餐绑定的附加服务ID（仅未软删除的附加服务）
     * @param int $packageId
     * @return array
     */
    public static function getAddonIds(int $packageId): array
    {
        if ($packageId <= 0) {
            return [];
        }

        $addonIds = self::alias('pa')
            ->join('service_addon a', 'a.id = pa.addon_id')
            ->where('pa.package_id', $packageId)
            ->whereNull('a.delete_time')
            ->column('pa.addon_id');

        return array_values(array_unique(array_map('intval', $addonIds)));
    }

    /**
     * @notes 批量获取套餐绑定的附加服务ID映射（仅未软删除的附加服务）
     * @param array $packageIds
     * @return array
     */
    public static function getAddonIdsMap(array $packageIds): array
    {
        $packageIds = array_values(array_unique(array_filter(array_map('intval', $packageIds))));
        if (empty($packageIds)) {
            return [];
        }

        $map = [];
        foreach ($packageIds as $packageId) {
            $map[$packageId] = [];
        }

        $relations = self::alias('pa')
            ->join('service_addon a', 'a.id = pa.addon_id')
            ->whereIn('pa.package_id', $packageIds)
            ->whereNull('a.delete_time')
            ->field('pa.package_id, pa.addon_id')
            ->select()
            ->toArray();

        foreach ($relations as $relation) {
            $packageId = (int)($relation['package_id'] ?? 0);
            $addonId = (int)($relation['addon_id'] ?? 0);
            if ($packageId > 0 && $addonId > 0) {
                $map[$packageId][] = $addonId;
            }
        }

        foreach ($map as $packageId => $addonIds) {
            $map[$packageId] = array_values(array_unique(array_map('intval', $addonIds)));
        }

        return $map;
    }

    /**
     * @notes 为套餐数组补充 addon_ids
     * @param array $packages
     * @return array
     */
    public static function attachAddonIds(array $packages): array
    {
        if (empty($packages)) {
            return [];
        }

        $packageIds = array_map(static fn (array $item) => (int)($item['id'] ?? 0), $packages);
        $addonIdsMap = self::getAddonIdsMap($packageIds);

        foreach ($packages as $index => $package) {
            $packageId = (int)($package['id'] ?? 0);
            $packages[$index]['addon_ids'] = $addonIdsMap[$packageId] ?? [];
        }

        return $packages;
    }

    /**
     * @notes 获取单个附加服务绑定的套餐ID（仅未软删除的套餐）
     * @param int $addonId
     * @return array
     */
    public static function getPackageIds(int $addonId): array
    {
        if ($addonId <= 0) {
            return [];
        }

        $packageIds = self::alias('pa')
            ->join('service_package p', 'p.id = pa.package_id')
            ->where('pa.addon_id', $addonId)
            ->whereNull('p.delete_time')
            ->column('pa.package_id');

        return array_values(array_unique(array_map('intval', $packageIds)));
    }

    /**
     * @notes 批量获取附加服务绑定的套餐ID映射（仅未软删除的套餐）
     * @param array $addonIds
     * @return array
     */
    public static function getPackageIdsMap(array $addonIds): array
    {
        $addonIds = array_values(array_unique(array_filter(array_map('intval', $addonIds))));
        if (empty($addonIds)) {
            return [];
        }

        $map = [];
        foreach ($addonIds as $addonId) {
            $map[$addonId] = [];
        }

        $relations = self::alias('pa')
            ->join('service_package p', 'p.id = pa.package_id')
            ->whereIn('pa.addon_id', $addonIds)
            ->whereNull('p.delete_time')
            ->field('pa.addon_id, pa.package_id')
            ->select()
            ->toArray();

        foreach ($relations as $relation) {
            $addonId = (int)($relation['addon_id'] ?? 0);
            $packageId = (int)($relation['package_id'] ?? 0);
            if ($addonId > 0 && $packageId > 0) {
                $map[$addonId][] = $packageId;
            }
        }

        foreach ($map as $addonId => $packageIds) {
            $map[$addonId] = array_values(array_unique(array_map('intval', $packageIds)));
        }

        return $map;
    }

    /**
     * @notes 为附加服务数组补充 package_ids
     * @param array $addons
     * @return array
     */
    public static function attachPackageIds(array $addons): array
    {
        if (empty($addons)) {
            return [];
        }

        $addonIds = array_map(static fn (array $item) => (int)($item['id'] ?? 0), $addons);
        $packageIdsMap = self::getPackageIdsMap($addonIds);

        foreach ($addons as $index => $addon) {
            $addonId = (int)($addon['id'] ?? 0);
            $addons[$index]['package_ids'] = $packageIdsMap[$addonId] ?? [];
        }

        return $addons;
    }

    /**
     * @notes 同步套餐可选附加服务
     * @param int $packageId
     * @param int $staffId
     * @param mixed $addonIds
     * @return void
     */
    public static function syncPackageAddons(int $packageId, int $staffId, $addonIds): void
    {
        if ($packageId <= 0 || $staffId <= 0) {
            throw new \RuntimeException('套餐或人员参数错误');
        }

        $addonIds = self::normalizeAddonIds($addonIds);
        self::where('package_id', $packageId)->delete();

        if (empty($addonIds)) {
            return;
        }

        $validAddonIds = ServiceAddon::where('staff_id', $staffId)
            ->whereNull('delete_time')
            ->whereIn('id', $addonIds)
            ->column('id');
        $validAddonIds = array_values(array_unique(array_map('intval', $validAddonIds)));

        if (count($validAddonIds) !== count($addonIds)) {
            throw new \RuntimeException('所选附加服务不存在或不属于当前人员');
        }

        $time = time();
        $saveData = [];
        foreach ($addonIds as $addonId) {
            $saveData[] = [
                'package_id' => $packageId,
                'addon_id' => $addonId,
                'create_time' => $time,
            ];
        }

        (new self())->saveAll($saveData);
    }

    /**
     * @notes 同步附加服务适用套餐
     * @param int $addonId
     * @param int $staffId
     * @param mixed $packageIds
     * @return void
     */
    public static function syncAddonPackages(int $addonId, int $staffId, $packageIds): void
    {
        if ($addonId <= 0 || $staffId <= 0) {
            throw new \RuntimeException('附加服务或人员参数错误');
        }

        $packageIds = self::normalizeAddonIds($packageIds);
        self::where('addon_id', $addonId)->delete();

        if (empty($packageIds)) {
            return;
        }

        $validPackageIds = ServicePackage::where('staff_id', $staffId)
            ->whereNull('delete_time')
            ->whereIn('id', $packageIds)
            ->column('id');
        $validPackageIds = array_values(array_unique(array_map('intval', $validPackageIds)));

        if (count($validPackageIds) !== count($packageIds)) {
            throw new \RuntimeException('所选套餐不存在或不属于当前人员');
        }

        $time = time();
        $saveData = [];
        foreach ($packageIds as $packageId) {
            $saveData[] = [
                'package_id' => $packageId,
                'addon_id' => $addonId,
                'create_time' => $time,
            ];
        }

        (new self())->saveAll($saveData);
    }

    /**
     * @notes 清理套餐关联
     * @param int $packageId
     * @return void
     */
    public static function clearByPackageId(int $packageId): void
    {
        if ($packageId <= 0) {
            return;
        }

        self::where('package_id', $packageId)->delete();
    }

    /**
     * @notes 清理附加服务关联
     * @param int $addonId
     * @return void
     */
    public static function clearByAddonId(int $addonId): void
    {
        if ($addonId <= 0) {
            return;
        }

        self::where('addon_id', $addonId)->delete();
    }
}
