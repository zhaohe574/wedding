<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\adminapi\logic\service;

use app\common\logic\BaseLogic;
use app\common\model\service\ServiceCityPool;
use app\common\model\service\ServicePackageRegionPrice;
use app\common\service\RegionDataService;

/**
 * 服务地区逻辑
 */
class RegionLogic extends BaseLogic
{
    /**
     * @notes 获取全部城市选项
     * @return array
     */
    public static function cityOptions(): array
    {
        return RegionDataService::getCityOptions();
    }

    /**
     * @notes 获取启用城市选项
     * @return array
     */
    public static function enabledCityOptions(): array
    {
        $enabledCities = ServiceCityPool::where('status', 1)
            ->order('sort', 'desc')
            ->order('id', 'desc')
            ->select()
            ->toArray();

        if (empty($enabledCities)) {
            return [];
        }

        $optionMap = [];
        foreach (RegionDataService::getCityOptions() as $option) {
            $optionMap[$option['city_code']] = $option;
        }

        $result = [];
        foreach ($enabledCities as $city) {
            $cityCode = (string)($city['city_code'] ?? '');
            if ($cityCode === '' || !isset($optionMap[$cityCode])) {
                continue;
            }
            $option = $optionMap[$cityCode];
            $option['id'] = (int)($city['id'] ?? 0);
            $option['status'] = (int)($city['status'] ?? 0);
            $option['sort'] = (int)($city['sort'] ?? 0);
            $result[] = $option;
        }

        return $result;
    }

    /**
     * @notes 获取区县选项
     * @param string $cityCode
     * @return array
     */
    public static function districtOptions(string $cityCode): array
    {
        return RegionDataService::getDistrictOptions($cityCode);
    }

    /**
     * @notes 新增城市池
     * @param array $params
     * @return bool
     */
    public static function add(array $params): bool
    {
        try {
            $cityInfo = RegionDataService::getCityInfo((string)($params['city_code'] ?? ''));
            if (empty($cityInfo)) {
                throw new \RuntimeException('所选城市不存在');
            }

            if (ServiceCityPool::where('city_code', $cityInfo['city_code'])->find()) {
                throw new \RuntimeException('该城市已在服务地区中');
            }

            ServiceCityPool::create([
                'province_code' => $cityInfo['province_code'],
                'province_name' => $cityInfo['province_name'],
                'city_code' => $cityInfo['city_code'],
                'city_name' => $cityInfo['city_name'],
                'sort' => (int)($params['sort'] ?? 0),
                'status' => (int)($params['status'] ?? 1),
                'create_time' => time(),
                'update_time' => time(),
            ]);

            return true;
        } catch (\Throwable $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * @notes 编辑城市池
     * @param array $params
     * @return bool
     */
    public static function edit(array $params): bool
    {
        try {
            $region = ServiceCityPool::find((int)$params['id']);
            if (!$region) {
                throw new \RuntimeException('服务地区不存在');
            }

            $cityInfo = RegionDataService::getCityInfo((string)($params['city_code'] ?? ''));
            if (empty($cityInfo)) {
                throw new \RuntimeException('所选城市不存在');
            }

            $newCityCode = (string)$cityInfo['city_code'];
            $oldCityCode = (string)$region->city_code;
            if ($newCityCode !== $oldCityCode) {
                $exists = ServiceCityPool::where('city_code', $newCityCode)
                    ->where('id', '<>', (int)$region->id)
                    ->find();
                if ($exists) {
                    throw new \RuntimeException('该城市已在服务地区中');
                }

                $inUse = ServicePackageRegionPrice::where('city_code', $oldCityCode)->count();
                if ($inUse > 0) {
                    throw new \RuntimeException('该城市已被套餐地区价引用，暂不支持直接更换城市');
                }
            }

            $region->save([
                'province_code' => $cityInfo['province_code'],
                'province_name' => $cityInfo['province_name'],
                'city_code' => $cityInfo['city_code'],
                'city_name' => $cityInfo['city_name'],
                'sort' => (int)($params['sort'] ?? $region->sort),
                'status' => (int)($params['status'] ?? $region->status),
                'update_time' => time(),
            ]);

            return true;
        } catch (\Throwable $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * @notes 删除城市池
     * @param array $params
     * @return bool
     */
    public static function delete(array $params): bool
    {
        try {
            $region = ServiceCityPool::find((int)$params['id']);
            if (!$region) {
                throw new \RuntimeException('服务地区不存在');
            }

            $inUse = ServicePackageRegionPrice::where('city_code', (string)$region->city_code)->count();
            if ($inUse > 0) {
                throw new \RuntimeException('该城市已被套餐地区价引用，无法删除');
            }

            $region->delete();
            return true;
        } catch (\Throwable $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * @notes 修改状态
     * @param array $params
     * @return bool
     */
    public static function changeStatus(array $params): bool
    {
        try {
            ServiceCityPool::update([
                'id' => (int)$params['id'],
                'status' => (int)$params['status'],
                'update_time' => time(),
            ]);

            return true;
        } catch (\Throwable $e) {
            self::setError($e->getMessage());
            return false;
        }
    }
}
