<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 预约流程服务
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\common\service;

use app\common\model\schedule\Schedule;
use app\common\model\service\ServiceAddon;
use app\common\model\service\ServiceCategory;
use app\common\model\service\ServicePackage;
use app\common\model\staff\Staff;

/**
 * 预约流程统一服务
 */
class BookingFlowService
{
    public const ROLE_BUTLER = 'butler';
    public const ROLE_DIRECTOR = 'director';

    public const ROLE_SKIP_LABEL = '否，后续自行预约';

    /**
     * @notes 获取服务人员预约附加项
     * @param int $staffId
     * @return array
     */
    public static function getStaffBookingAddons(int $staffId): array
    {
        if ($staffId <= 0) {
            return [];
        }

        return ServiceAddon::where('staff_id', $staffId)
            ->whereNull('delete_time')
            ->where('is_show', 1)
            ->field('id, staff_id, category_id, name, price, original_price, image, description, sort')
            ->order('sort', 'desc')
            ->order('id', 'desc')
            ->select()
            ->toArray();
    }

    /**
     * @notes 规范化预约附加项 ID
     * @param mixed $addonIds
     * @return array
     */
    public static function normalizeAddonIds($addonIds): array
    {
        if (is_string($addonIds)) {
            $addonIds = $addonIds === '' ? [] : explode(',', $addonIds);
        }

        if (!is_array($addonIds)) {
            return [];
        }

        $addonIds = array_map('intval', $addonIds);
        $addonIds = array_filter($addonIds, static fn (int $addonId) => $addonId > 0);
        return array_values(array_unique($addonIds));
    }

    /**
     * @notes 校验并获取已选预约附加项
     * @param int $staffId
     * @param array $addonIds
     * @return array
     */
    public static function resolveSelectedAddons(int $staffId, array $addonIds): array
    {
        $addonIds = self::normalizeAddonIds($addonIds);
        if ($staffId <= 0 || empty($addonIds)) {
            return [];
        }

        $addonMap = [];
        foreach (self::getStaffBookingAddons($staffId) as $addon) {
            $addonMap[(int)($addon['id'] ?? 0)] = $addon;
        }

        $result = [];
        foreach ($addonIds as $addonId) {
            if (!isset($addonMap[$addonId])) {
                throw new \InvalidArgumentException('预约附加项已失效，请重新选择');
            }
            $result[] = $addonMap[$addonId];
        }

        return $result;
    }

    /**
     * @notes 获取服务分类关联角色配置
     * @param int $categoryId
     * @return array
     */
    public static function getCategoryRoleConfigs(int $categoryId): array
    {
        if ($categoryId <= 0) {
            return [];
        }

        $category = ServiceCategory::find($categoryId);
        if (!$category) {
            return [];
        }

        $categoryData = $category->getData();
        $result = [];

        foreach ([
            self::ROLE_BUTLER => [
                'enabled_field' => 'booking_butler_enabled',
                'category_field' => 'booking_butler_category_id',
            ],
            self::ROLE_DIRECTOR => [
                'enabled_field' => 'booking_director_enabled',
                'category_field' => 'booking_director_category_id',
            ],
        ] as $roleKey => $config) {
            $enabled = (int)($categoryData[$config['enabled_field']] ?? 0) === 1;
            $relatedCategoryId = (int)($categoryData[$config['category_field']] ?? 0);
            if (!$enabled || $relatedCategoryId <= 0) {
                continue;
            }

            $relatedCategory = ServiceCategory::find($relatedCategoryId);
            $result[] = [
                'role_key' => $roleKey,
                'role_label' => self::getRoleLabel($roleKey),
                'related_category_id' => $relatedCategoryId,
                'related_category_name' => $relatedCategory ? (string)$relatedCategory->name : '',
                'skip_option_label' => self::ROLE_SKIP_LABEL,
            ];
        }

        return $result;
    }

    /**
     * @notes 获取单个角色配置
     * @param int $categoryId
     * @param string $roleKey
     * @return array
     */
    public static function getCategoryRoleConfig(int $categoryId, string $roleKey): array
    {
        foreach (self::getCategoryRoleConfigs($categoryId) as $config) {
            if (($config['role_key'] ?? '') === $roleKey) {
                return $config;
            }
        }

        return [];
    }

    /**
     * @notes 获取预约角色候选人员
     * @param int $serviceStaffId
     * @param string $roleKey
     * @param array $regionContext
     * @param string $date
     * @return array
     */
    public static function getRoleCandidates(
        int $serviceStaffId,
        string $roleKey,
        array $regionContext = [],
        string $date = '',
        int $userId = 0
    ): array {
        if ($serviceStaffId <= 0 || !in_array($roleKey, [self::ROLE_BUTLER, self::ROLE_DIRECTOR], true)) {
            return [];
        }

        $serviceStaff = Staff::where('id', $serviceStaffId)
            ->where('status', Staff::STATUS_ENABLE)
            ->where('audit_status', Staff::AUDIT_PASS)
            ->whereNull('delete_time')
            ->find();
        if (!$serviceStaff) {
            return [];
        }

        $config = self::getCategoryRoleConfig((int)$serviceStaff->category_id, $roleKey);
        if (empty($config)) {
            return [];
        }

        $normalizedRegion = [];
        if (PackageRegionPriceService::hasRegionContext($regionContext)) {
            $normalizedRegion = PackageRegionPriceService::validateEnabledRegion($regionContext);
        }

        return self::fetchRoleCandidatesByCategory(
            (int)$config['related_category_id'],
            $roleKey,
            $normalizedRegion,
            $date,
            $userId
        );
    }

    /**
     * @notes 校验并返回角色已选候选人
     * @param int $serviceStaffId
     * @param string $roleKey
     * @param int $selectedStaffId
     * @param int $selectedPackageId
     * @param array $regionContext
     * @param string $date
     * @return array
     */
    public static function resolveSelectedRoleCandidate(
        int $serviceStaffId,
        string $roleKey,
        int $selectedStaffId,
        int $selectedPackageId,
        array $regionContext = [],
        string $date = '',
        int $userId = 0
    ): array {
        if ($selectedStaffId <= 0 || $selectedPackageId <= 0) {
            throw new \InvalidArgumentException('预约人选参数错误');
        }

        $candidates = self::getRoleCandidates(
            $serviceStaffId,
            $roleKey,
            $regionContext,
            $date,
            $userId
        );
        foreach ($candidates as $candidate) {
            if ((int)($candidate['staff_id'] ?? 0) !== $selectedStaffId) {
                continue;
            }

            if ((int)($candidate['package_id'] ?? 0) !== $selectedPackageId) {
                throw new \InvalidArgumentException('预约人选套餐已变更，请重新选择');
            }

            if (isset($candidate['schedule_available']) && !$candidate['schedule_available']) {
                $message = (string)($candidate['schedule_message'] ?? '');
                throw new \InvalidArgumentException($message !== '' ? $message : '所选服务人员当前档期不可预约');
            }

            return $candidate;
        }

        throw new \InvalidArgumentException('所选预约人选已失效，请重新选择');
    }

    /**
     * @notes 获取角色中文名称
     * @param string $roleKey
     * @return string
     */
    public static function getRoleLabel(string $roleKey): string
    {
        $map = [
            self::ROLE_BUTLER => '婚礼管家',
            self::ROLE_DIRECTOR => '婚礼督导',
        ];

        return $map[$roleKey] ?? '关联服务';
    }

    /**
     * @notes 查询某分类下的推荐候选人
     * @param int $categoryId
     * @param string $roleKey
     * @param array $regionContext
     * @param string $date
     * @return array
     */
    protected static function fetchRoleCandidatesByCategory(
        int $categoryId,
        string $roleKey,
        array $regionContext = [],
        string $date = '',
        int $userId = 0
    ): array {
        if ($categoryId <= 0) {
            return [];
        }

        $staffList = Staff::where('category_id', $categoryId)
            ->where('status', Staff::STATUS_ENABLE)
            ->where('audit_status', Staff::AUDIT_PASS)
            ->where('is_recommend', 1)
            ->whereNull('delete_time')
            ->order('sort', 'desc')
            ->order('rating', 'desc')
            ->order('order_count', 'desc')
            ->select();

        $result = [];
        foreach ($staffList as $staff) {
            $package = self::getRecommendedPackage((int)$staff->id, $regionContext);
            if (empty($package)) {
                continue;
            }

            $scheduleStatus = self::resolveScheduleStatus((int)$staff->id, $date, $userId);
            $result[] = [
                'role_key' => $roleKey,
                'role_label' => self::getRoleLabel($roleKey),
                'staff_id' => (int)$staff->id,
                'name' => (string)$staff->name,
                'avatar' => (string)$staff->avatar,
                'category_id' => (int)$staff->category_id,
                'package_id' => (int)$package['id'],
                'package_name' => (string)$package['name'],
                'package_description' => (string)($package['description'] ?? ''),
                'price' => round((float)$package['price'], 2),
                'original_price' => round((float)($package['original_price'] ?? 0), 2),
                'schedule_available' => $scheduleStatus['available'],
                'schedule_message' => $scheduleStatus['message'],
            ];
        }

        return $result;
    }

    /**
     * @notes 获取推荐套餐
     * @param int $staffId
     * @param array $regionContext
     * @return array
     */
    protected static function getRecommendedPackage(int $staffId, array $regionContext = []): array
    {
        $package = ServicePackage::where('staff_id', $staffId)
            ->where('is_show', 1)
            ->where('is_recommend', 1)
            ->whereNull('delete_time')
            ->order('sort', 'desc')
            ->order('id', 'desc')
            ->find();
        if (!$package) {
            return [];
        }

        $price = round((float)$package->price, 2);
        if (PackageRegionPriceService::hasRegionContext($regionContext)) {
            $resolvedPrice = PackageRegionPriceService::resolvePackagePrice((int)$package->id, $regionContext);
            if (!($resolvedPrice['available'] ?? false)) {
                return [];
            }
            $price = round((float)($resolvedPrice['price'] ?? 0), 2);
        }

        return [
            'id' => (int)$package->id,
            'name' => (string)$package->name,
            'description' => (string)($package->description ?? ''),
            'price' => $price,
            'original_price' => round((float)$package->original_price, 2),
            'category_id' => (int)$package->category_id,
        ];
    }

    /**
     * @notes 查询档期状态
     * @param int $staffId
     * @param string $date
     * @return array
     */
    protected static function resolveScheduleStatus(int $staffId, string $date, int $userId = 0): array
    {
        $date = trim($date);
        if ($staffId <= 0 || $date === '') {
            return [
                'available' => true,
                'message' => '',
            ];
        }

        [$available, $message] = Schedule::checkAvailabilityForUserWithReason(
            $staffId,
            $date,
            $userId,
            0
        );
        return [
            'available' => $available,
            'message' => (string)$message,
        ];
    }
}
