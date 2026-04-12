<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 工作人员管理逻辑
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\adminapi\logic\staff;

use app\common\logic\BaseLogic;
use app\common\model\auth\Admin;
use app\common\model\auth\AdminRole;
use app\common\model\service\ServiceAddon;
use app\common\model\service\ServicePackage;
use app\common\model\service\ServicePackageAddon;
use app\common\service\PackageRegionPriceService;
use app\common\model\staff\Staff;
use app\common\model\staff\StaffCertificate;
use app\common\model\staff\StaffTag;
use app\common\model\staff\StaffWork;
use app\common\model\user\User;
use app\common\service\ConfigService;
use app\common\service\FileService;
use app\common\service\StaffPriceService;
use app\common\service\StaffService;
use app\common\service\StaffTagReviewService;
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
        $displayPrice = StaffPriceService::getDisplayPriceByStaffId((int)$staff->id);
        $data['price'] = $displayPrice['price'];
        $data['has_price'] = $displayPrice['has_price'];
        $data['price_text'] = $displayPrice['price_text'];
        $data['tag_ids'] = StaffTag::getTagIds($id);
        $data['packages'] = self::getStaffOwnedPackages($id);
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

        $data = array_merge($data, StaffTagReviewService::getProfileTagState((int) $staff->id));

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
                'wecom_userid' => $params['wecom_userid'] ?? '',
                'category_id' => $params['category_id'] ?? 0,
                'experience_years' => $params['experience_years'] ?? 0,
                'profile' => $params['profile'] ?? '',
                'service_desc' => $params['service_desc'] ?? '',
                'long_detail' => $params['long_detail'] ?? '',
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
                StaffTagReviewService::syncEffectiveTags((int) $staff->id, (int) ($params['category_id'] ?? 0), $params['tag_ids']);
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
            $oldCategoryId = (int)$staff->category_id;
            $newCategoryId = (int)($params['category_id'] ?? $oldCategoryId);

            // 更新工作人员信息
            $staff->save([
                'user_id' => $userId,
                'name' => $params['name'],
                'avatar' => $params['avatar'] ?? $staff->avatar,
                'mobile' => $params['mobile'] ?? $rawMobile,
                'mobile_full' => $params['mobile_full'] ?? $rawMobile,
                'wecom_userid' => $params['wecom_userid'] ?? $staff->wecom_userid,
                'category_id' => $params['category_id'] ?? $staff->category_id,
                'experience_years' => $params['experience_years'] ?? $staff->experience_years,
                'profile' => $params['profile'] ?? $staff->profile,
                'service_desc' => $params['service_desc'] ?? $staff->service_desc,
                'long_detail' => $params['long_detail'] ?? $staff->long_detail,
                'sort' => $params['sort'] ?? $staff->sort,
                'is_recommend' => $params['is_recommend'] ?? $staff->is_recommend,
                'status' => $params['status'] ?? $staff->status,
                'update_time' => time(),
            ]);

            if ($newCategoryId > 0 && $newCategoryId !== $oldCategoryId) {
                self::syncOwnedPackageCategory($staff->id, $newCategoryId);
                self::syncOwnedAddonCategory($staff->id, $newCategoryId);
            }

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
                StaffTagReviewService::syncEffectiveTags($staffId, $newCategoryId, $params['tag_ids']);
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
     * @notes 更新我的资料
     */
    public static function updateSelfProfile(int $staffId, array $params): array|bool
    {
        Db::startTrans();
        try {
            $staff = Staff::find($staffId);
            if (!$staff) {
                throw new \RuntimeException('服务人员不存在');
            }

            if (!empty($params['mobile'])) {
                $params['mobile_full'] = $params['mobile'];
            }
            $rawMobile = $staff->getData('mobile_full') ?: $staff->getData('mobile');
            $categoryId = (int) $staff->category_id;

            $staff->save([
                'name' => $params['name'] ?? $staff->name,
                'avatar' => $params['avatar'] ?? $staff->avatar,
                'mobile' => $params['mobile'] ?? $rawMobile,
                'mobile_full' => $params['mobile_full'] ?? $rawMobile,
                'experience_years' => $params['experience_years'] ?? $staff->experience_years,
                'profile' => $params['profile'] ?? $staff->profile,
                'service_desc' => $params['service_desc'] ?? $staff->service_desc,
                'long_detail' => $params['long_detail'] ?? $staff->long_detail,
                'update_time' => time(),
            ]);

            $tagResult = [
                'action' => 'applied',
                'message' => '标签未变化',
            ];
            if (array_key_exists('tag_ids', $params)) {
                $tagResult = StaffTagReviewService::handleSelfTagUpdate(
                    $staffId,
                    $categoryId,
                    is_array($params['tag_ids']) ? $params['tag_ids'] : [],
                    [
                        'source' => \app\common\model\staff\StaffTagApply::SOURCE_STAFF_ADMIN,
                        'submit_admin_id' => (int) ($staff->admin_id ?? 0),
                    ]
                );
            }

            Db::commit();

            return [
                'tag_action' => $tagResult['action'] ?? 'applied',
                'tag_message' => $tagResult['message'] ?? '',
            ];
        } catch (\Throwable $e) {
            Db::rollback();
            self::setError($e->getMessage());
            return false;
        }
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

        $result = $query->order('sort desc, id desc')
            ->field('id, sn, name, avatar, category_id, rating')
            ->select()
            ->toArray();
        StaffPriceService::injectDisplayPrice($result);
        return $result;
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
     * @notes 旧的全局套餐关联入口已下线
     * @param int $staffId 员工ID
     * @param array $packages 套餐配置数组
     *        格式: [
     *            ['package_id' => 1, 'price' => 100.00, 'status' => 1],
     *            ...
     *        ]
     * @return bool
     */
    public static function configurePackages(int $staffId, array $packages): bool
    {
        self::setError('请直接维护人员套餐');
        return false;
    }

    /**
     * @notes 获取员工套餐配置
     * @param int $staffId 员工ID
     * @param bool $includeGlobal 是否包含可关联的全局套餐
     * @return array
     */
    public static function getPackageConfig(int $staffId, bool $includeGlobal = false): array
    {
        return self::getStaffOwnedPackages($staffId);
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

            if ((int) $package->staff_id !== $staffId) {
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
     * @notes 更新单个套餐配置（兼容旧入口，直接修改人员专属套餐）
     * @param int $staffId 员工ID
     * @param int $packageId 套餐ID
     * @param array $data 配置数据 ['price' => ..., 'original_price' => ..., 'status' => ...]
     * @return bool
     */
    public static function updatePackageConfig(int $staffId, int $packageId, array $data): bool
    {
        try {
            $package = ServicePackage::where('id', $packageId)
                ->where('staff_id', $staffId)
                ->find();
            if (!$package) {
                self::setError('套餐不存在');
                return false;
            }

            $updateData = [];
            foreach (['price', 'original_price', 'name', 'description', 'image', 'sort', 'is_show', 'is_recommend'] as $field) {
                if (array_key_exists($field, $data)) {
                    $updateData[$field] = $data[$field];
                }
            }
            $needSyncRegionPrices = array_key_exists('region_prices', $data) && is_array($data['region_prices']);
            if (empty($updateData) && !$needSyncRegionPrices) {
                return true;
            }

            Db::transaction(function () use ($package, $updateData, $staffId, $needSyncRegionPrices, $data) {
                if (!empty($updateData)) {
                    $updateData['staff_id'] = $staffId;
                    $updateData['update_time'] = time();
                    $package->save($updateData);
                }

                if ($needSyncRegionPrices) {
                    PackageRegionPriceService::syncPackageRegionPrices(
                        (int)$package->id,
                        $staffId,
                        $data['region_prices'] ?? []
                    );
                }
            });
            return true;
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
            if ((int)$package->staff_id !== $staffId) {
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
     * @notes 获取员工附加服务配置
     * @param int $staffId
     * @return array
     */
    public static function getAddonConfig(int $staffId): array
    {
        return self::getStaffOwnedAddons($staffId);
    }

    /**
     * @notes 创建员工专属附加服务
     * @param int $staffId
     * @param array $addonData
     * @return bool
     */
    public static function createStaffAddon(int $staffId, array $addonData): bool
    {
        try {
            $staff = Staff::find($staffId);
            if (!$staff) {
                self::setError('员工不存在');
                return false;
            }

            ServiceAddon::create(self::buildAddonPayload($staffId, $addonData));
            return true;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * @notes 编辑员工专属附加服务
     * @param int $staffId
     * @param int $addonId
     * @param array $addonData
     * @return bool
     */
    public static function updateStaffAddon(int $staffId, int $addonId, array $addonData): bool
    {
        try {
            $addon = ServiceAddon::find($addonId);
            if (!$addon) {
                self::setError('附加服务不存在');
                return false;
            }

            if ((int)$addon->staff_id !== $staffId) {
                self::setError('只能编辑自己的附加服务');
                return false;
            }

            $addon->save(self::buildAddonPayload($staffId, $addonData, false));
            return true;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * @notes 删除员工专属附加服务
     * @param int $staffId
     * @param int $addonId
     * @return bool
     */
    public static function deleteStaffAddon(int $staffId, int $addonId): bool
    {
        try {
            $addon = ServiceAddon::find($addonId);
            if (!$addon) {
                self::setError('附加服务不存在');
                return false;
            }

            if ((int)$addon->staff_id !== $staffId) {
                self::setError('只能删除自己的附加服务');
                return false;
            }

            ServicePackageAddon::clearByAddonId($addonId);
            $addon->delete();
            return true;
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

            $user = User::field('id,mobile')->find((int)$staff->user_id);
            if (!$user) {
                self::setError('绑定的前端用户不存在');
                return false;
            }

            $password = trim((string)$user->mobile);
            if ($password === '') {
                self::setError('绑定的前端用户未设置手机号，无法重置密码');
                return false;
            }

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

        $user = User::field('id,mobile')->find((int)$staff->user_id);
        if (!$user) {
            throw new \Exception('绑定的前端用户不存在');
        }

        $mobile = trim((string)$user->mobile);
        if ($mobile === '') {
            throw new \Exception('绑定的前端用户未设置手机号，无法创建后台账号');
        }

        if (Admin::where('account', $mobile)->find()) {
            throw new \Exception('手机号已被占用，无法创建后台账号');
        }

        // 按需求：后台账号=绑定前端用户手机号，初始密码=手机号
        $account = $mobile;
        $password = $mobile;

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
     * @notes 获取人员直属套餐
     * @param int $staffId
     * @return array
     */
    protected static function getStaffOwnedPackages(int $staffId): array
    {
        $packages = ServicePackage::where('staff_id', $staffId)
            ->whereNull('delete_time')
            ->field('id, staff_id, category_id, name, price, original_price, description, image, sort, is_show, is_recommend')
            ->append(['category_name', 'staff_name'])
            ->order('sort', 'desc')
            ->order('id', 'desc')
            ->select()
            ->toArray();

        return PackageRegionPriceService::attachRegionPrices($packages);
    }

    /**
     * @notes 获取人员直属附加服务
     * @param int $staffId
     * @return array
     */
    protected static function getStaffOwnedAddons(int $staffId): array
    {
        return ServiceAddon::where('staff_id', $staffId)
            ->whereNull('delete_time')
            ->field('id, staff_id, category_id, name, price, original_price, description, image, sort, is_show')
            ->append(['category_name', 'staff_name'])
            ->order('sort', 'desc')
            ->order('id', 'desc')
            ->select()
            ->toArray();
    }

    /**
     * @notes 同步人员名下套餐分类
     * @param int $staffId
     * @param int $categoryId
     * @return void
     */
    protected static function syncOwnedPackageCategory(int $staffId, int $categoryId): void
    {
        if ($staffId <= 0 || $categoryId <= 0) {
            return;
        }

        ServicePackage::where('staff_id', $staffId)
            ->whereNull('delete_time')
            ->update([
                'category_id' => $categoryId,
                'update_time' => time(),
            ]);
    }

    /**
     * @notes 同步人员名下附加服务分类
     * @param int $staffId
     * @param int $categoryId
     * @return void
     */
    protected static function syncOwnedAddonCategory(int $staffId, int $categoryId): void
    {
        if ($staffId <= 0 || $categoryId <= 0) {
            return;
        }

        ServiceAddon::where('staff_id', $staffId)
            ->whereNull('delete_time')
            ->update([
                'category_id' => $categoryId,
                'update_time' => time(),
            ]);
    }

    /**
     * @notes 构造附加服务写入数据
     * @param int $staffId
     * @param array $params
     * @param bool $withCreateFields
     * @return array
     */
    protected static function buildAddonPayload(int $staffId, array $params, bool $withCreateFields = true): array
    {
        $staff = Staff::find($staffId);
        if (!$staff) {
            throw new \RuntimeException('员工不存在');
        }

        $categoryId = (int)($staff->category_id ?? 0);
        if ($categoryId <= 0) {
            throw new \RuntimeException('请先为员工设置服务分类');
        }

        $payload = [
            'staff_id' => $staffId,
            'category_id' => $categoryId,
            'name' => trim((string)($params['name'] ?? '')),
            'price' => round((float)($params['price'] ?? 0), 2),
            'original_price' => round((float)($params['original_price'] ?? 0), 2),
            'description' => (string)($params['description'] ?? ''),
            'image' => (string)($params['image'] ?? ''),
            'sort' => (int)($params['sort'] ?? 0),
            'is_show' => (int)($params['is_show'] ?? 1),
            'update_time' => time(),
        ];

        if ($withCreateFields) {
            $payload['create_time'] = time();
        }

        return $payload;
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
