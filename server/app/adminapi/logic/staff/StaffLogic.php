<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 工作人员管理逻辑
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\adminapi\logic\staff;

use app\common\logic\BaseLogic;
use app\common\model\auth\Admin;
use app\common\model\auth\AdminRole;
use app\common\model\staff\Staff;
use app\common\model\staff\StaffTag;
use app\common\model\staff\StaffPackage;
use app\common\model\staff\StaffWork;
use app\common\model\staff\StaffCertificate;
use app\common\model\service\ServicePackage;
use app\common\model\user\User;
use app\common\service\ConfigService;
use app\common\service\FileService;
use app\common\service\StaffService;
use app\adminapi\logic\service\PackageLogic;
use think\facade\Db;
use think\facade\Config;

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
        $data['admin_id'] = (int)($staff->admin_id ?? 0);
        $data['admin_account'] = '';
        $data['admin_disable'] = 0;
        if (!empty($staff->admin_id)) {
            $admin = Admin::field('id, account, disable')->find($staff->admin_id);
            if ($admin) {
                $data['admin_account'] = $admin->account;
                $data['admin_disable'] = (int)$admin->disable;
            }
        }

        return $data;
    }

    /**
     * @notes 添加工作人员
     * @param array $params
     * @return bool
     */
    public static function add(array $params)
    {
        Db::startTrans();
        try {
            $userId = (int) ($params['user_id'] ?? 0);
            if ($userId <= 0) {
                throw new \Exception('请选择系统用户');
            }
            $user = User::find($userId);
            if (!$user) {
                throw new \Exception('系统用户不存在');
            }
            $boundStaff = Staff::where('user_id', $userId)->find();
            if ($boundStaff) {
                throw new \Exception('该用户已绑定服务人员');
            }

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
                'user_id' => $userId,
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

            $adminAccount = '';
            $adminPassword = '';
            if (self::shouldCreateAdmin()) {
                [$adminId, $adminAccount, $adminPassword] = self::createStaffAdmin($staff);
                if ($adminId > 0) {
                    $staff->save([
                        'admin_id' => $adminId,
                        'update_time' => time(),
                    ]);
                }
            }

            // 设置标签
            if (!empty($params['tag_ids'])) {
                StaffTag::setTags($staff->id, $params['tag_ids']);
            }

            // 设置套餐
            if (!empty($params['packages'])) {
                StaffPackage::setPackages($staff->id, $params['packages']);
            }

            Db::commit();
            return [
                'staff_id' => $staff->id,
                'admin_account' => $adminAccount,
                'admin_password' => $adminPassword,
            ];
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

            $userId = array_key_exists('user_id', $params) ? (int) $params['user_id'] : (int) $staff->user_id;
            if ($userId > 0) {
                $user = User::find($userId);
                if (!$user) {
                    throw new \Exception('系统用户不存在');
                }
                $boundStaff = Staff::where('user_id', $userId)
                    ->where('id', '<>', $staff->id)
                    ->find();
                if ($boundStaff) {
                    throw new \Exception('该用户已绑定服务人员');
                }
            }

            // 处理手机号
            if (!empty($params['mobile'])) {
                $params['mobile_full'] = $params['mobile'];
            }
            // 未传 mobile 时用原始完整号，不能用 $staff->mobile（getter 已脱敏）
            $rawMobile = $staff->getData('mobile_full') ?: $staff->getData('mobile');

            // 更新工作人员信息
            $staff->save([
                'user_id' => $userId,
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

            if (!empty($staff->admin_id)) {
                $adminUpdate = [];
                if (array_key_exists('name', $params)) {
                    $adminUpdate['name'] = $params['name'];
                }
                if (array_key_exists('status', $params)) {
                    $adminUpdate['disable'] = (int)($params['status'] ? 0 : 1);
                }
                if (!empty($adminUpdate)) {
                    $adminUpdate['id'] = $staff->admin_id;
                    Admin::update($adminUpdate);
                }
            } elseif (self::shouldCreateAdmin()) {
                [$adminId] = self::createStaffAdmin($staff);
                if ($adminId > 0) {
                    $staff->save([
                        'admin_id' => $adminId,
                        'update_time' => time(),
                    ]);
                }
            }

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

            $staff = Staff::find($params['id']);
            if ($staff && !empty($staff->admin_id)) {
                Admin::update([
                    'id' => $staff->admin_id,
                    'disable' => (int)($params['status'] ? 0 : 1),
                ]);
            }
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

        if (!empty($params['ids']) && is_array($params['ids'])) {
            $query->whereIn('id', $params['ids']);
        }

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
    public static function statistics(int $staffId = 0): array
    {
        $query = Staff::where('delete_time', null);
        if ($staffId > 0) {
            $query->where('id', $staffId);
        }
        $total = (clone $query)->count();
        $enable = (clone $query)->where('status', Staff::STATUS_ENABLE)->count();
        $disable = (clone $query)->where('status', Staff::STATUS_DISABLE)->count();
        $recommend = (clone $query)->where('is_recommend', 1)->count();

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
     * @notes 编辑员工专属套餐
     * @param int $staffId 员工ID
     * @param int $packageId 套餐ID
     * @param array $packageData 套餐数据
     * @return bool
     */
    public static function updateStaffPackage(int $staffId, int $packageId, array $packageData): bool
    {
        try {
            $package = ServicePackage::find($packageId);
            if (!$package) {
                self::setError('套餐不存在');
                return false;
            }

            if ($package->package_type != ServicePackage::TYPE_STAFF_ONLY || (int) $package->staff_id !== $staffId) {
                self::setError('只能编辑自己的专属套餐');
                return false;
            }

            $payload = $packageData;
            $payload['id'] = $packageId;
            $payload['staff_id'] = $staffId;

            $result = PackageLogic::edit($payload);
            if (!$result) {
                self::setError(PackageLogic::getError());
                return false;
            }

            return true;
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

    /**
     * @notes 重置工作人员后台密码
     * @param int $staffId
     * @return array|false
     */
    public static function resetAdminPassword(int $staffId)
    {
        try {
            $staff = Staff::find($staffId);
            if (!$staff) {
                self::setError('工作人员不存在');
                return false;
            }
            if (empty($staff->admin_id)) {
                self::setError('未关联后台账号');
                return false;
            }

            $admin = Admin::find($staff->admin_id);
            if (!$admin) {
                self::setError('后台账号不存在');
                return false;
            }

            $password = self::generateRandomPassword();
            $passwordSalt = Config::get('project.unique_identification');
            $admin->password = create_password($password, $passwordSalt);
            $admin->save();

            return [
                'admin_account' => $admin->account,
                'admin_password' => $password,
            ];
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * @notes 是否启用后台账号自动创建
     */
    protected static function shouldCreateAdmin(): bool
    {
        return (int)ConfigService::get('feature_switch', 'staff_admin', 1) === 1;
    }

    /**
     * @notes 创建工作人员后台账号
     * @param Staff $staff
     * @return array [adminId, account, password]
     * @throws \Exception
     */
    protected static function createStaffAdmin(Staff $staff): array
    {
        $roleId = StaffService::getStaffRoleId();
        if ($roleId <= 0) {
            throw new \Exception('服务人员角色不存在');
        }

        $baseAccount = 'staff_' . strtolower($staff->sn ?: (string)$staff->id);
        $account = self::generateUniqueAccount($baseAccount);
        $password = self::generateRandomPassword();

        $passwordSalt = Config::get('project.unique_identification');
        $passwordHash = create_password($password, $passwordSalt);

        $avatarRaw = $staff->getData('avatar') ?: '';
        $avatar = $avatarRaw ? FileService::setFileUrl($avatarRaw) : config('project.default_image.admin_avatar');

        $admin = Admin::create([
            'name' => $staff->name,
            'account' => $account,
            'avatar' => $avatar,
            'password' => $passwordHash,
            'create_time' => time(),
            'disable' => 0,
            'multipoint_login' => 1,
        ]);

        AdminRole::create([
            'admin_id' => $admin->id,
            'role_id' => $roleId,
        ]);

        return [$admin->id, $account, $password];
    }

    /**
     * @notes 生成唯一后台账号
     */
    protected static function generateUniqueAccount(string $baseAccount): string
    {
        $account = $baseAccount;
        $suffix = 1;
        while (Admin::where('account', $account)->find()) {
            $account = $baseAccount . '_' . $suffix;
            $suffix++;
        }
        return $account;
    }

    /**
     * @notes 生成随机密码
     */
    protected static function generateRandomPassword(int $length = 12): string
    {
        $raw = bin2hex(random_bytes((int)ceil($length / 2)));
        return substr($raw, 0, $length);
    }

    /**
     * @notes 获取人员轮播图列表
     * @param int $staffId
     * @return array
     */
    public static function getBannerList(int $staffId): array
    {
        $banners = \app\common\model\staff\StaffBanner::where('staff_id', $staffId)
            ->order('sort', 'asc')
            ->order('id', 'asc')
            ->select()
            ->toArray();

        return $banners;
    }

    /**
     * @notes 添加轮播图
     * @param int $staffId
     * @param array $params
     * @return int|false
     */
    public static function addBanner(int $staffId, array $params)
    {
        try {
            $data = [
                'staff_id' => $staffId,
                'type' => intval($params['type'] ?? 1),
                'file_url' => $params['file_url'] ?? '',
                'cover_url' => $params['cover_url'] ?? '',
                'is_autoplay' => intval($params['is_autoplay'] ?? 0),
                'sort' => intval($params['sort'] ?? 0),
                'create_time' => time(),
                'update_time' => time(),
            ];

            $banner = \app\common\model\staff\StaffBanner::create($data);
            return $banner->id;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * @notes 编辑轮播图
     * @param int $id
     * @param array $params
     * @return bool
     */
    public static function editBanner(int $id, array $params): bool
    {
        try {
            $banner = \app\common\model\staff\StaffBanner::find($id);
            if (!$banner) {
                self::setError('轮播图不存在');
                return false;
            }

            $data = [];
            if (isset($params['type'])) {
                $data['type'] = intval($params['type']);
            }
            if (isset($params['file_url'])) {
                $data['file_url'] = $params['file_url'];
            }
            if (isset($params['cover_url'])) {
                $data['cover_url'] = $params['cover_url'];
            }
            if (isset($params['is_autoplay'])) {
                $data['is_autoplay'] = intval($params['is_autoplay']);
            }
            if (isset($params['sort'])) {
                $data['sort'] = intval($params['sort']);
            }
            $data['update_time'] = time();

            $banner->save($data);
            return true;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * @notes 删除轮播图
     * @param int $id
     * @return bool
     */
    public static function deleteBanner(int $id): bool
    {
        try {
            $banner = \app\common\model\staff\StaffBanner::find($id);
            if (!$banner) {
                self::setError('轮播图不存在');
                return false;
            }

            $banner->delete();
            return true;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * @notes 更新轮播图排序
     * @param int $staffId
     * @param array $sortData 格式：[['id' => 1, 'sort' => 0], ['id' => 2, 'sort' => 1]]
     * @return bool
     */
    public static function sortBanner(int $staffId, array $sortData): bool
    {
        try {
            foreach ($sortData as $item) {
                $id = intval($item['id'] ?? 0);
                $sort = intval($item['sort'] ?? 0);

                if ($id > 0) {
                    \app\common\model\staff\StaffBanner::where('id', $id)
                        ->where('staff_id', $staffId)
                        ->update([
                            'sort' => $sort,
                            'update_time' => time()
                        ]);
                }
            }

            return true;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * @notes 更新轮播图配置
     * @param int $staffId
     * @param array $params
     * @return bool
     */
    public static function updateBannerConfig(int $staffId, array $params): bool
    {
        try {
            $staff = Staff::find($staffId);
            if (!$staff) {
                self::setError('人员不存在');
                return false;
            }

            $data = [];
            if (isset($params['banner_mode'])) {
                $data['banner_mode'] = intval($params['banner_mode']);
            }
            if (isset($params['banner_small_height'])) {
                $data['banner_small_height'] = intval($params['banner_small_height']);
            }
            if (isset($params['banner_large_height'])) {
                $data['banner_large_height'] = intval($params['banner_large_height']);
            }
            if (isset($params['banner_indicator_style'])) {
                $data['banner_indicator_style'] = intval($params['banner_indicator_style']);
            }
            if (isset($params['banner_autoplay'])) {
                $data['banner_autoplay'] = intval($params['banner_autoplay']);
            }
            if (isset($params['banner_interval'])) {
                $data['banner_interval'] = intval($params['banner_interval']);
            }
            $data['update_time'] = time();

            $staff->save($data);
            return true;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }
}
