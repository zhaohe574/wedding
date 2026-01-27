<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 工作人员管理逻辑
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\adminapi\logic\staff;

use app\common\logic\BaseLogic;
use app\common\model\staff\Staff;
use app\common\model\staff\StaffTag;
use app\common\model\staff\StaffPackage;
use app\common\model\staff\StaffWork;
use app\common\model\staff\StaffCertificate;
use app\common\model\service\ServicePackage;
use app\adminapi\logic\service\PackageLogic;
use think\facade\Db;

/**
 * 工作人员管理逻辑
 * Class StaffLogic
 * @package app\adminapi\logic\staff
 */
class StaffLogic extends BaseLogic
{
    /**
     * @notes 获取工作人员详情
     * @param int $id
     * @return array
     */
    public static function detail(int $id): array
    {
        $staff = Staff::with(['category', 'works', 'certificates'])
            ->find($id);

        if (!$staff) {
            return [];
        }

        $data = $staff->toArray();
        $data['tag_ids'] = StaffTag::getTagIds($id);
        $data['packages'] = StaffPackage::getPackages($id);
        // 编辑场景需要完整手机号：优先 mobile_full，否则用原始 mobile（toArray 中 mobile 已被 getMobileAttr 脱敏）
        $fullMobile = $staff->getData('mobile_full') ?: $staff->getData('mobile');
        $data['mobile'] = $fullMobile;
        $data['mobile_full'] = $fullMobile;

        return $data;
    }

    /**
     * @notes 添加工作人员
     * @param array $params
     * @return bool
     */
    public static function add(array $params): bool
    {
        Db::startTrans();
        try {
            // 生成工号
            $params['sn'] = Staff::generateSn();
            $params['create_time'] = time();
            $params['update_time'] = time();

            // 处理手机号
            if (!empty($params['mobile'])) {
                $params['mobile_full'] = $params['mobile'];
            }

            // 创建工作人员
            $staff = Staff::create([
                'sn' => $params['sn'],
                'name' => $params['name'],
                'avatar' => $params['avatar'] ?? '',
                'mobile' => $params['mobile'] ?? '',
                'mobile_full' => $params['mobile_full'] ?? '',
                'category_id' => $params['category_id'] ?? 0,
                'price' => $params['price'] ?? 0,
                'experience_years' => $params['experience_years'] ?? 0,
                'profile' => $params['profile'] ?? '',
                'service_desc' => $params['service_desc'] ?? '',
                'sort' => $params['sort'] ?? 0,
                'is_recommend' => $params['is_recommend'] ?? 0,
                'status' => $params['status'] ?? 1,
                'audit_status' => Staff::AUDIT_PASS,
                'create_time' => time(),
                'update_time' => time(),
            ]);

            // 设置标签
            if (!empty($params['tag_ids'])) {
                StaffTag::setTags($staff->id, $params['tag_ids']);
            }

            // 设置套餐
            if (!empty($params['packages'])) {
                StaffPackage::setPackages($staff->id, $params['packages']);
            }

            Db::commit();
            return true;
        } catch (\Exception $e) {
            Db::rollback();
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * @notes 编辑工作人员
     * @param array $params
     * @return bool
     */
    public static function edit(array $params): bool
    {
        Db::startTrans();
        try {
            $staff = Staff::find($params['id']);
            if (!$staff) {
                throw new \Exception('工作人员不存在');
            }

            // 处理手机号
            if (!empty($params['mobile'])) {
                $params['mobile_full'] = $params['mobile'];
            }
            // 未传 mobile 时用原始完整号，不能用 $staff->mobile（getter 已脱敏）
            $rawMobile = $staff->getData('mobile_full') ?: $staff->getData('mobile');

            // 更新工作人员信息
            $staff->save([
                'name' => $params['name'],
                'avatar' => $params['avatar'] ?? $staff->avatar,
                'mobile' => $params['mobile'] ?? $rawMobile,
                'mobile_full' => $params['mobile_full'] ?? $rawMobile,
                'category_id' => $params['category_id'] ?? $staff->category_id,
                'price' => $params['price'] ?? $staff->price,
                'experience_years' => $params['experience_years'] ?? $staff->experience_years,
                'profile' => $params['profile'] ?? $staff->profile,
                'service_desc' => $params['service_desc'] ?? $staff->service_desc,
                'sort' => $params['sort'] ?? $staff->sort,
                'is_recommend' => $params['is_recommend'] ?? $staff->is_recommend,
                'status' => $params['status'] ?? $staff->status,
                'update_time' => time(),
            ]);

            // 更新标签（setTags/setPackages 要求 int，POST 的 id 为字符串）
            $staffId = (int) $params['id'];
            if (isset($params['tag_ids'])) {
                StaffTag::setTags($staffId, $params['tag_ids']);
            }

            // 更新套餐
            if (isset($params['packages'])) {
                StaffPackage::setPackages($staffId, $params['packages']);
            }

            Db::commit();
            return true;
        } catch (\Throwable $e) {
            Db::rollback();
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * @notes 删除工作人员
     * @param array $params
     * @return bool
     */
    public static function delete(array $params): bool
    {
        return Staff::destroy($params['id']);
    }

    /**
     * @notes 修改工作人员状态
     * @param array $params
     * @return bool
     */
    public static function changeStatus(array $params): bool
    {
        try {
            Staff::update([
                'id' => $params['id'],
                'status' => $params['status'],
                'update_time' => time(),
            ]);
            return true;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * @notes 获取所有工作人员
     * @param array $params
     * @return array
     */
    public static function getAll(array $params = []): array
    {
        $query = Staff::where('delete_time', null)
            ->where('status', Staff::STATUS_ENABLE);

        if (!empty($params['category_id'])) {
            $query->where('category_id', $params['category_id']);
        }

        return $query->order('sort desc, id desc')
            ->field('id, sn, name, avatar, category_id, price, rating')
            ->select()
            ->toArray();
    }

    /**
     * @notes 统计数据
     * @return array
     */
    public static function statistics(): array
    {
        $total = Staff::where('delete_time', null)->count();
        $enable = Staff::where('delete_time', null)->where('status', Staff::STATUS_ENABLE)->count();
        $disable = Staff::where('delete_time', null)->where('status', Staff::STATUS_DISABLE)->count();
        $recommend = Staff::where('delete_time', null)->where('is_recommend', 1)->count();

        return [
            'total' => $total,
            'enable' => $enable,
            'disable' => $disable,
            'recommend' => $recommend,
        ];
    }

    /**
     * @notes 配置员工关联套餐（增强版，支持个人价格和时段价格）
     * @param int $staffId 员工ID
     * @param array $packages 套餐配置数组
     *        格式: [
     *            ['package_id' => 1, 'custom_price' => 100.00, 'custom_slot_prices' => [...], 'status' => 1],
     *            ...
     *        ]
     * @return bool
     */
    public static function configurePackages(int $staffId, array $packages): bool
    {
        try {
            $staff = Staff::find($staffId);
            if (!$staff) {
                self::setError('员工不存在');
                return false;
            }

            // 验证套餐是否存在
            foreach ($packages as $pkg) {
                $package = ServicePackage::find($pkg['package_id'] ?? 0);
                if (!$package) {
                    self::setError('套餐ID ' . ($pkg['package_id'] ?? 0) . ' 不存在');
                    return false;
                }
                // 人员专属套餐不能被其他员工关联
                if ($package->package_type == ServicePackage::TYPE_STAFF_ONLY && $package->staff_id != $staffId) {
                    self::setError('套餐 ' . $package->name . ' 是其他员工的专属套餐，无法关联');
                    return false;
                }
            }

            StaffPackage::setPackages($staffId, $packages);
            return true;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * @notes 获取员工套餐配置
     * @param int $staffId 员工ID
     * @param bool $includeGlobal 是否包含可关联的全局套餐
     * @return array
     */
    public static function getPackageConfig(int $staffId, bool $includeGlobal = false): array
    {
        $result = [
            'configured_packages' => [],  // 已配置的套餐
            'staff_packages' => [],       // 员工专属套餐
            'available_packages' => [],   // 可关联的全局套餐
        ];

        // 获取已配置的套餐
        $result['configured_packages'] = StaffPackage::getPackages($staffId);

        // 获取员工专属套餐
        $result['staff_packages'] = ServicePackage::where('staff_id', $staffId)
            ->where('package_type', ServicePackage::TYPE_STAFF_ONLY)
            ->where('delete_time', null)
            ->select()
            ->toArray();

        // 获取可关联的全局套餐
        if ($includeGlobal) {
            $configuredIds = array_column($result['configured_packages'], 'package_id');
            $result['available_packages'] = ServicePackage::where('package_type', ServicePackage::TYPE_GLOBAL)
                ->where('delete_time', null)
                ->where('is_show', 1)
                ->whereNotIn('id', $configuredIds)
                ->select()
                ->toArray();
        }

        return $result;
    }

    /**
     * @notes 创建员工专属套餐
     * @param int $staffId 员工ID
     * @param array $packageData 套餐数据
     * @return int|false 成功返回套餐ID，失败返回false
     */
    public static function createStaffPackage(int $staffId, array $packageData)
    {
        try {
            $staff = Staff::find($staffId);
            if (!$staff) {
                self::setError('员工不存在');
                return false;
            }

            // 使用 PackageLogic 创建人员专属套餐
            $packageId = PackageLogic::addStaffPackage($staffId, $packageData);
            if (!$packageId) {
                self::setError(PackageLogic::getError());
                return false;
            }

            return $packageId;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * @notes 更新单个套餐配置
     * @param int $staffId 员工ID
     * @param int $packageId 套餐ID
     * @param array $data 配置数据 ['custom_price' => ..., 'custom_slot_prices' => [...], 'status' => ...]
     * @return bool
     */
    public static function updatePackageConfig(int $staffId, int $packageId, array $data): bool
    {
        try {
            return StaffPackage::updatePackageConfig($staffId, $packageId, $data);
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * @notes 删除员工专属套餐
     * @param int $staffId 员工ID
     * @param int $packageId 套餐ID
     * @return bool
     */
    public static function deleteStaffPackage(int $staffId, int $packageId): bool
    {
        try {
            $package = ServicePackage::find($packageId);
            if (!$package) {
                self::setError('套餐不存在');
                return false;
            }

            // 只能删除自己的专属套餐
            if ($package->package_type != ServicePackage::TYPE_STAFF_ONLY || $package->staff_id != $staffId) {
                self::setError('只能删除自己的专属套餐');
                return false;
            }

            return PackageLogic::delete(['id' => $packageId]);
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }
}
