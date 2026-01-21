<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 服务套餐管理逻辑
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\adminapi\logic\service;

use app\common\logic\BaseLogic;
use app\common\model\service\ServicePackage;
use app\common\model\staff\StaffPackage;
use app\common\model\package\PackageBooking;

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
        $package = ServicePackage::with(['category', 'staff'])->find($id);
        if (!$package) {
            return [];
        }
        $data = $package->toArray();
        // 添加额外的描述字段
        $data['package_type_desc'] = $package->package_type_desc;
        $data['staff_name'] = $package->staff_name;
        return $data;
    }

    /**
     * @notes 添加套餐
     * @param array $params
     * @return bool
     */
    public static function add(array $params): bool
    {
        try {
            // 确定套餐类型
            $staffId = $params['staff_id'] ?? 0;
            $packageType = $staffId > 0 ? ServicePackage::TYPE_STAFF_ONLY : ServicePackage::TYPE_GLOBAL;

            ServicePackage::create([
                'category_id' => $params['category_id'] ?? 0,
                'staff_id' => $staffId,
                'package_type' => $params['package_type'] ?? $packageType,
                'name' => $params['name'],
                'price' => $params['price'] ?? 0,
                'original_price' => $params['original_price'] ?? 0,
                'slot_prices' => $params['slot_prices'] ?? [],
                'duration' => $params['duration'] ?? 0,
                'content' => $params['content'] ?? [],
                'description' => $params['description'] ?? '',
                'sort' => $params['sort'] ?? 0,
                'is_show' => $params['is_show'] ?? 1,
                'is_recommend' => $params['is_recommend'] ?? 0,
                'create_time' => time(),
                'update_time' => time(),
            ]);

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

            $updateData = [
                'category_id' => $params['category_id'] ?? $package->category_id,
                'name' => $params['name'],
                'price' => $params['price'] ?? $package->price,
                'original_price' => $params['original_price'] ?? $package->original_price,
                'duration' => $params['duration'] ?? $package->duration,
                'content' => $params['content'] ?? $package->content,
                'description' => $params['description'] ?? $package->description,
                'sort' => $params['sort'] ?? $package->sort,
                'is_show' => $params['is_show'] ?? $package->is_show,
                'is_recommend' => $params['is_recommend'] ?? $package->is_recommend,
                'update_time' => time(),
            ];

            // 支持时段价格编辑
            if (isset($params['slot_prices'])) {
                $updateData['slot_prices'] = $params['slot_prices'];
            }

            // 支持套餐类型和所属人员编辑（仅限未被预订的套餐）
            if (isset($params['staff_id'])) {
                $updateData['staff_id'] = $params['staff_id'];
                $updateData['package_type'] = $params['staff_id'] > 0 
                    ? ServicePackage::TYPE_STAFF_ONLY 
                    : ServicePackage::TYPE_GLOBAL;
            }

            if (isset($params['package_type'])) {
                $updateData['package_type'] = $params['package_type'];
            }

            $package->save($updateData);

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

            // 检查是否有关联的工作人员
            $staffCount = StaffPackage::where('package_id', $params['id'])->count();
            if ($staffCount > 0) {
                throw new \Exception('该套餐已被工作人员关联，无法删除');
            }

            // 检查是否有未完成的预订
            $bookingCount = PackageBooking::where('package_id', $params['id'])
                ->whereIn('status', [PackageBooking::STATUS_TEMP_LOCK, PackageBooking::STATUS_CONFIRMED])
                ->count();
            if ($bookingCount > 0) {
                throw new \Exception('该套餐存在有效预订，无法删除');
            }

            ServicePackage::destroy($params['id']);
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
            ->where('is_show', 1);

        if (!empty($params['category_id'])) {
            $query->where('category_id', $params['category_id']);
        }

        // 支持按套餐类型过滤
        if (isset($params['package_type'])) {
            $query->where('package_type', $params['package_type']);
        }

        // 支持按员工ID过滤（获取员工专属套餐）
        if (isset($params['staff_id'])) {
            $query->where('staff_id', $params['staff_id']);
        }

        // 只获取全局套餐
        if (!empty($params['global_only'])) {
            $query->where('package_type', ServicePackage::TYPE_GLOBAL);
        }

        return $query->order('sort desc, id desc')
            ->field('id, name, category_id, staff_id, package_type, price, slot_prices, duration')
            ->select()
            ->toArray();
    }

    /**
     * @notes 检查套餐可用性（单日唯一限制）
     * @param int $packageId 套餐ID
     * @param string $date 预订日期 Y-m-d
     * @param int $staffId 服务人员ID（可选）
     * @return array ['available' => bool, 'message' => string]
     */
    public static function checkAvailability(int $packageId, string $date, int $staffId = 0): array
    {
        return PackageBooking::checkAvailability($packageId, $date);
    }

    /**
     * @notes 计算最终价格（6级优先级）
     * 优先级：个人时段价格 > 个人统一价格 > 套餐时段价格 > 套餐默认价格
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
        // 获取套餐信息
        $package = ServicePackage::find($packageId);
        if (!$package) {
            return 0;
        }

        // 如果指定了员工，尝试获取员工的个人配置
        if ($staffId > 0) {
            $staffPackage = StaffPackage::where('staff_id', $staffId)
                ->where('package_id', $packageId)
                ->where('status', 1)
                ->find();

            if ($staffPackage) {
                return $staffPackage->calculatePrice($startTime, $endTime);
            }
        }

        // 没有员工配置，使用套餐本身的价格计算
        return $package->calculatePrice($startTime, $endTime);
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
            $package = ServicePackage::create([
                'category_id' => $packageData['category_id'] ?? 0,
                'staff_id' => $staffId,
                'package_type' => ServicePackage::TYPE_STAFF_ONLY,
                'name' => $packageData['name'],
                'price' => $packageData['price'] ?? 0,
                'original_price' => $packageData['original_price'] ?? 0,
                'slot_prices' => $packageData['slot_prices'] ?? [],
                'duration' => $packageData['duration'] ?? 0,
                'content' => $packageData['content'] ?? [],
                'description' => $packageData['description'] ?? '',
                'sort' => $packageData['sort'] ?? 0,
                'is_show' => $packageData['is_show'] ?? 1,
                'is_recommend' => $packageData['is_recommend'] ?? 0,
                'create_time' => time(),
                'update_time' => time(),
            ]);

            return $package->id;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * @notes 获取套餐的时段价格配置
     * @param int $packageId
     * @param int $staffId 可选，获取员工的个人时段价格
     * @return array
     */
    public static function getSlotPrices(int $packageId, int $staffId = 0): array
    {
        $package = ServicePackage::find($packageId);
        if (!$package) {
            return [];
        }

        $result = [
            'package_prices' => $package->slot_prices ?? [],
            'default_price' => (float)$package->price,
            'custom_prices' => [],
            'custom_price' => null,
        ];

        // 如果指定了员工，获取员工的个人配置
        if ($staffId > 0) {
            $staffPackage = StaffPackage::where('staff_id', $staffId)
                ->where('package_id', $packageId)
                ->find();

            if ($staffPackage) {
                $result['custom_prices'] = $staffPackage->custom_slot_prices ?? [];
                $result['custom_price'] = $staffPackage->custom_price;
            }
        }

        return $result;
    }

    /**
     * @notes 批量获取套餐在指定日期的可用性
     * @param array $packageIds 套餐ID数组
     * @param string $date 预订日期
     * @return array package_id => available 的映射
     */
    public static function batchCheckAvailability(array $packageIds, string $date): array
    {
        return PackageBooking::batchCheckAvailability($packageIds, $date);
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
     * @notes 更新套餐时段价格
     * @param array $params
     * @return bool
     */
    public static function updateSlotPrices(array $params): bool
    {
        try {
            $package = ServicePackage::find($params['id']);
            if (!$package) {
                throw new \Exception('套餐不存在');
            }

            $package->save([
                'slot_prices' => $params['slot_prices'] ?? [],
                'update_time' => time(),
            ]);

            return true;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }
}
