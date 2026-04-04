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
     * @notes 获取单个套餐绑定的附加服务ID
     * @param int $packageId
     * @return array
     */
    public static function getAddonIds(int $packageId): array
    {
        if ($packageId <= 0) {
            return [];
        }

        return array_map('intval', self::where('package_id', $packageId)->column('addon_id'));
    }

    /**
     * @notes 批量获取套餐绑定的附加服务ID映射
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

        $relations = self::whereIn('package_id', $packageIds)
            ->field('package_id, addon_id')
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
