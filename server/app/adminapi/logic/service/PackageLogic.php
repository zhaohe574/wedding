<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 服务套餐管理逻辑
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\adminapi\logic\service;

use app\common\logic\BaseLogic;
use app\common\model\package\PackageBooking;
use app\common\model\service\ServicePackage;
use app\common\model\service\ServicePackageAddon;
use app\common\model\service\ServicePackageRegionPrice;
use app\common\service\PackageRegionPriceService;
use app\common\model\staff\Staff;
use think\facade\Db;

/**
 * 服务套餐管理逻辑
 * Class PackageLogic
 * @package app\adminapi\logic\service
 */
class PackageLogic extends BaseLogic
{
    /**
     * @notes 获取套餐详情
     * @param int $id
     * @return array
     */
    public static function detail(int $id): array
    {
        try {
            $package = ServicePackage::where('id', $id)
                ->where('staff_id', '>', 0)
                ->whereNull('delete_time')
                ->find();
            if (!$package) {
                return [];
            }

            $data = $package->append(['category_name', 'staff_name'])->toArray();
            $list = PackageRegionPriceService::attachRegionPrices([$data]);
            $list = ServicePackageAddon::attachAddonIds($list);
            return $list[0] ?? $data;
        } catch (\Exception $e) {
            // 记录错误日志并返回空数组
            trace('获取套餐详情失败: ' . $e->getMessage() . ' - File: ' . $e->getFile() . ' Line: ' . $e->getLine(), 'error');
            // 不要抛出异常，返回空数组让控制器处理
            return [];
        }
    }

    /**
     * @notes 添加套餐
     * @param array $params
     * @return bool
     */
    public static function add(array $params): bool
    {
        try {
            $staffId = (int)($params['staff_id'] ?? 0);
            if ($staffId <= 0) {
                throw new \Exception('请选择所属人员');
            }
            $categoryId = self::resolveStaffCategoryId($staffId);

            Db::transaction(function () use ($categoryId, $params, $staffId) {
                $package = ServicePackage::create([
                    'category_id' => $categoryId,
                    'staff_id' => $staffId,
                    'name' => $params['name'],
                    'price' => $params['price'] ?? 0,
                    'original_price' => $params['original_price'] ?? 0,
                    'duration' => $params['duration'] ?? 0,
                    'image' => $params['image'] ?? '',
                    'description' => $params['description'] ?? '',
                    'sort' => $params['sort'] ?? 0,
                    'is_show' => $params['is_show'] ?? 1,
                    'is_recommend' => $params['is_recommend'] ?? 0,
                    'create_time' => time(),
                    'update_time' => time(),
                ]);

                PackageRegionPriceService::syncPackageRegionPrices(
                    (int)$package->id,
                    $staffId,
                    $params['region_prices'] ?? []
                );
                ServicePackageAddon::syncPackageAddons(
                    (int)$package->id,
                    $staffId,
                    $params['addon_ids'] ?? []
                );
            });

            return true;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * @notes 编辑套餐
     * @param array $params
     * @return bool
     */
    public static function edit(array $params): bool
    {
        try {
            $package = ServicePackage::find($params['id']);
            if (!$package) {
                throw new \Exception('套餐不存在');
            }

            $staffId = (int)($params['staff_id'] ?? $package->staff_id);
            if ($staffId <= 0) {
                throw new \Exception('请选择所属人员');
            }
            $categoryId = self::resolveStaffCategoryId($staffId);

            $updateData = [
                'category_id' => $categoryId,
                'staff_id' => $staffId,
                'name' => $params['name'],
                'price' => $params['price'] ?? $package->price,
                'original_price' => $params['original_price'] ?? $package->original_price,
                'duration' => $params['duration'] ?? $package->duration,
                'image' => $params['image'] ?? $package->image,
                'description' => $params['description'] ?? $package->description,
                'sort' => $params['sort'] ?? $package->sort,
                'is_show' => $params['is_show'] ?? $package->is_show,
                'is_recommend' => $params['is_recommend'] ?? $package->is_recommend,
                'update_time' => time(),
            ];

            Db::transaction(function () use ($package, $updateData, $staffId, $params) {
                $package->save($updateData);
                PackageRegionPriceService::syncPackageRegionPrices(
                    (int)$package->id,
                    $staffId,
                    $params['region_prices'] ?? []
                );
                ServicePackageAddon::syncPackageAddons(
                    (int)$package->id,
                    $staffId,
                    array_key_exists('addon_ids', $params)
                        ? $params['addon_ids']
                        : ServicePackageAddon::getAddonIds((int)$package->id)
                );
            });

            return true;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * @notes 删除套餐
     * @param array $params
     * @return bool
     */
    public static function delete(array $params): bool
    {
        try {
            $package = ServicePackage::find($params['id']);
            if (!$package) {
                throw new \Exception('套餐不存在');
            }

            // 检查是否有未完成的预订
            $bookingCount = PackageBooking::where('package_id', $params['id'])
                ->whereIn('status', [PackageBooking::STATUS_TEMP_LOCK, PackageBooking::STATUS_CONFIRMED])
                ->count();
            if ($bookingCount > 0) {
                throw new \Exception('该套餐存在有效预订，无法删除');
            }

            Db::transaction(function () use ($params) {
                ServicePackageAddon::clearByPackageId((int)$params['id']);
                ServicePackageRegionPrice::where('package_id', (int)$params['id'])->delete();
                ServicePackage::destroy($params['id']);
            });
            return true;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * @notes 修改套餐状态
     * @param array $params
     * @return bool
     */
    public static function changeStatus(array $params): bool
    {
        try {
            ServicePackage::update([
                'id' => $params['id'],
                'is_show' => $params['is_show'],
                'update_time' => time(),
            ]);
            return true;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * @notes 获取所有套餐
     * @param array $params
     * @return array
     */
    public static function getAll(array $params = []): array
    {
        $query = ServicePackage::where('delete_time', null)
            ->where('is_show', 1)
            ->where('staff_id', '>', 0);

        if (!empty($params['category_id'])) {
            $staffIds = Staff::where('category_id', (int)$params['category_id'])
                ->whereNull('delete_time')
                ->column('id');
            if (empty($staffIds)) {
                return [];
            }
            $query->whereIn('staff_id', $staffIds);
        }

        if (isset($params['staff_id'])) {
            $query->where('staff_id', $params['staff_id']);
        }

        $list = $query->order('sort desc, id desc')
            ->field('id, staff_id, category_id, name, price, original_price, description, image, sort, is_show, is_recommend')
            ->select()
            ->toArray();

        return $list;
    }

    /**
     * @notes 检查套餐可用性（单日唯一限制）
     * @param int $packageId 套餐ID
     * @param string $date 预订日期 Y-m-d
     * @param int $staffId 服务人员ID（可选）
     * @return array ['available' => bool, 'message' => string]
     */
    public static function checkAvailability(int $packageId, string $date, int $staffId = 0, int $timeSlot = 0): array
    {
        return PackageBooking::checkAvailability($packageId, $date, $staffId, 0);
    }

    /**
     * @notes 计算最终价格（固定价）
     * @param int $packageId 套餐ID
     * @param int $staffId 服务人员ID
     * @param string $startTime 开始时间（可选）
     * @param string $endTime 结束时间（可选）
     * @return float
     */
    public static function calculateFinalPrice(
        int $packageId,
        int $staffId,
        string $startTime = '',
        string $endTime = ''
    ): float {
        $package = ServicePackage::find($packageId);
        if (!$package) {
            return 0;
        }

        if ($staffId > 0 && (int)$package->staff_id !== $staffId) {
            return 0;
        }
        return $package->calculatePrice();
    }

    /**
     * @notes 添加人员专属套餐
     * @param int $staffId 员工ID
     * @param array $packageData 套餐数据
     * @return int|false 成功返回套餐ID，失败返回false
     */
    public static function addStaffPackage(int $staffId, array $packageData)
    {
        try {
            $categoryId = self::resolveStaffCategoryId($staffId);
            $package = null;
            Db::transaction(function () use ($categoryId, $packageData, $staffId, &$package) {
                $package = ServicePackage::create([
                    'category_id' => $categoryId,
                    'staff_id' => $staffId,
                    'name' => $packageData['name'],
                    'price' => $packageData['price'] ?? 0,
                    'original_price' => $packageData['original_price'] ?? 0,
                    'duration' => $packageData['duration'] ?? 0,
                    'image' => $packageData['image'] ?? '',
                    'description' => $packageData['description'] ?? '',
                    'sort' => $packageData['sort'] ?? 0,
                    'is_show' => $packageData['is_show'] ?? 1,
                    'is_recommend' => $packageData['is_recommend'] ?? 0,
                    'create_time' => time(),
                    'update_time' => time(),
                ]);

                PackageRegionPriceService::syncPackageRegionPrices(
                    (int)$package->id,
                    $staffId,
                    $packageData['region_prices'] ?? []
                );
                ServicePackageAddon::syncPackageAddons(
                    (int)$package->id,
                    $staffId,
                    $packageData['addon_ids'] ?? []
                );
            });

            return $package->id;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * @notes 读取所属人员当前服务分类
     * @param int $staffId
     * @return int
     */
    protected static function resolveStaffCategoryId(int $staffId): int
    {
        $staff = Staff::find($staffId);
        if (!$staff) {
            throw new \Exception('所属人员不存在');
        }

        $categoryId = (int)($staff->category_id ?? 0);
        if ($categoryId <= 0) {
            throw new \Exception('请先为所属人员设置服务分类');
        }

        return $categoryId;
    }

    /**
     * @notes 批量获取套餐在指定日期的可用性
     * @param array $packageIds 套餐ID数组
     * @param string $date 预订日期
     * @return array package_id => available 的映射
     */
    public static function batchCheckAvailability(array $packageIds, string $date, int $staffId = 0, int $timeSlot = 0): array
    {
        return PackageBooking::batchCheckAvailability($packageIds, $date, $staffId, 0);
    }

    /**
     * @notes 获取套餐在日期范围内的预订情况
     * @param int $packageId
     * @param string $startDate
     * @param string $endDate
     * @return array
     */
    public static function getBookingCalendar(int $packageId, string $startDate, string $endDate): array
    {
        return PackageBooking::getBookingsByDateRange($packageId, $startDate, $endDate);
    }

    /**
     * @notes 批量获取套餐信息（用于装修组件）
     * @param array $ids 套餐ID列表
     * @param array $fields 需要的字段（可选）
     * @return array 套餐数据列表，以ID为键的关联数组
     */
    public static function batchGetByIds(array $ids, array $fields = []): array
    {
        if (empty($ids)) {
            return [];
        }

        // 默认字段
        $defaultFields = [
            'id', 'name', 'image', 'description as desc',
            'price', 'original_price', 'status'
        ];

        $fields = empty($fields) ? $defaultFields : $fields;

        try {
            $packageList = ServicePackage::whereIn('id', $ids)
                ->where('delete_time', null)
                ->where('is_show', 1)
                ->where('staff_id', '>', 0)
                ->field($fields)
                ->select()
                ->toArray();

            // 转换为以 ID 为键的映射，方便查找
            $result = [];
            foreach ($packageList as $package) {
                $result[$package['id']] = $package;
            }

            return $result;
        } catch (\Exception $e) {
            // 记录错误日志，返回空数组
            trace('批量查询套餐数据失败: ' . $e->getMessage(), 'error');
            return [];
        }
    }

}
