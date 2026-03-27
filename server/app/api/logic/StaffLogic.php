<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 小程序端工作人员逻辑
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\api\logic;

use app\common\logic\BaseLogic;
use app\common\model\schedule\Schedule;
use app\common\model\staff\Staff;
use app\common\model\staff\StaffWork;
use app\common\model\staff\Favorite;
use app\common\model\staff\StaffTag;
use app\common\model\service\ServiceAddon;
use app\common\model\service\ServicePackage;
use app\common\service\BookingFlowService;
use app\common\service\ConfigService;
use app\common\service\PackageRegionPriceService;
use app\common\service\StaffPriceService;

/**
 * 工作人员逻辑（小程序端）
 * Class StaffLogic
 * @package app\api\logic
 */
class StaffLogic extends BaseLogic
{
    /**
     * @notes 推荐工作人员
     * @param int $limit
     * @return array
     */
    public static function recommend(int $limit = 10): array
    {
        $result = Staff::where('delete_time', null)
            ->where('status', Staff::STATUS_ENABLE)
            ->where('is_recommend', 1)
            ->where('audit_status', Staff::AUDIT_PASS)
            ->order('sort desc, rating desc, order_count desc')
            ->field('id, sn, name, avatar, category_id, rating, order_count, experience_years, profile')
            ->append(['category_name', 'tag_names'])
            ->limit($limit)
            ->select()
            ->toArray();

        StaffPriceService::injectDisplayPrice($result);

        foreach ($result as &$item) {
            $item['tags'] = $item['tag_names'] ?? [];
            unset($item['tag_names'], $item['status_desc'], $item['audit_status_desc']);
        }

        return $result;
    }

    /**
     * @notes 工作人员详情
     * @param int $id
     * @param int $userId
     * @return array
     */
    public static function detail(
        int $id,
        int $userId = 0,
        array $regionContext = [],
        string $date = ''
    ): array
    {
        $resolvedRegion = self::resolveRegionContext($regionContext);
        $scheduleStatus = self::resolveScheduleStatus($id, $date);

        $staff = Staff::where('id', $id)
            ->where('delete_time', null)
            ->where('status', Staff::STATUS_ENABLE)
            ->where('audit_status', Staff::AUDIT_PASS)
            ->with(['category', 'certificates' => function($query) {
                $query->where('audit_status', 1)->limit(10);
            }])
            ->find();

        if (!$staff) {
            return [];
        }

        // 增加浏览量
        Staff::incrementViewCount($id);

        $data = $staff->toArray();
        $displayPrice = StaffPriceService::getDisplayPriceByStaffId((int)$staff->id, $resolvedRegion);
        $data['price'] = $displayPrice['price'];
        $data['has_price'] = $displayPrice['has_price'];
        $data['price_text'] = $displayPrice['price_text'];
        $data['schedule_available'] = $scheduleStatus['available'];
        $data['schedule_message'] = $scheduleStatus['message'];

        // 详情页风格（后台配置）
        $data['staff_detail_style'] = (string) ConfigService::get('feature_switch', 'staff_detail_style', 'classic');

        // 获取标签
        $tagData = \app\common\model\staff\StaffTag::alias('st')
            ->leftJoin('style_tag tag', 'st.tag_id = tag.id')
            ->where('st.staff_id', $id)
            ->where('tag.delete_time', null)
            ->where('tag.is_show', 1)
            ->column('tag.name');
        $data['tags'] = array_values(array_filter($tagData));

        $packages = ServicePackage::where('staff_id', $id)
            ->where('delete_time', null)
            ->where('is_show', 1)
            ->order('sort desc, id desc')
            ->select()
            ->toArray();
        $data['packages'] = self::transformPackages($packages, $resolvedRegion);
        $data['booking_options'] = BookingFlowService::getStaffBookingOptions($staff);
        $data['related_role_configs'] = BookingFlowService::getCategoryRoleConfigs((int)$staff->category_id);

        // 获取轮播图
        $data['banners'] = \app\common\model\staff\StaffBanner::where('staff_id', $id)
            ->order('sort', 'asc')
            ->order('id', 'asc')
            ->select()
            ->toArray();

        // 是否已收藏
        $data['is_favorite'] = false;
        if ($userId > 0) {
            $data['is_favorite'] = Favorite::where('user_id', $userId)
                ->where('staff_id', $id)
                ->find() ? true : false;
        }

        // 移除敏感信息
        unset($data['mobile_full']);

        return $data;
    }

    /**
     * @notes 工作人员作品列表
     * @param int $staffId
     * @return array
     */
    public static function works(int $staffId): array
    {
        return StaffWork::where('staff_id', $staffId)
            ->where('delete_time', null)
            ->where('is_show', 1)
            ->order('sort desc, id desc')
            ->select()
            ->toArray();
    }

    /**
     * @notes 作品详情（客户端）
     * @param int $workId
     * @return array
     */
    public static function workDetail(int $workId): array
    {
        $work = StaffWork::with(['staff' => function ($query) {
                $query->field('id, name, avatar, category_id, rating, order_count, review_count, favorite_count, sn');
            }])
            ->where('id', $workId)
            ->where('delete_time', null)
            ->where('is_show', 1)
            ->where('audit_status', StaffWork::AUDIT_PASS)
            ->find();

        if (!$work) {
            return [];
        }

        StaffWork::incrementViewCount($workId);
        $data = $work->toArray();
        if (!empty($data['staff']['id'])) {
            $displayPrice = StaffPriceService::getDisplayPriceByStaffId((int)$data['staff']['id']);
            $data['staff']['price'] = $displayPrice['price'];
            $data['staff']['has_price'] = $displayPrice['has_price'];
            $data['staff']['price_text'] = $displayPrice['price_text'];
        }
        return $data;
    }

    /**
     * @notes 获取工作人员套餐列表（用于选择）
     * @param int $staffId
     * @return array
     */
    public static function packages(int $staffId, array $regionContext = [], string $date = ''): array
    {
        if ($staffId <= 0) {
            return [];
        }

        self::resolveScheduleStatus($staffId, $date);
        $resolvedRegion = self::resolveRegionContext($regionContext);
        $packages = ServicePackage::where('staff_id', $staffId)
            ->where('delete_time', null)
            ->where('is_show', 1);
        return self::transformPackages(
            $packages->order('sort desc, id desc')->select()->toArray(),
            $resolvedRegion
        );
    }

    /**
     * @notes 获取工作人员附加服务列表（用于确认页选择）
     * @param int $staffId
     * @return array
     */
    public static function addons(int $staffId, int $packageId = 0): array
    {
        if ($staffId <= 0) {
            return [];
        }

        $query = ServiceAddon::alias('addon')
            ->where('addon.staff_id', $staffId)
            ->whereNull('addon.delete_time')
            ->where('addon.is_show', 1)
            ->field('addon.id, addon.staff_id, addon.category_id, addon.name, addon.price, addon.original_price, addon.image, addon.description, addon.sort, addon.is_show');

        if ($packageId > 0) {
            $package = ServicePackage::where('id', $packageId)
                ->where('staff_id', $staffId)
                ->whereNull('delete_time')
                ->find();
            if (!$package) {
                return [];
            }

            $query->join(
                'service_package_addon relation',
                'relation.addon_id = addon.id AND relation.package_id = ' . $packageId
            );
        }

        return $query->order('addon.sort desc, addon.id desc')
            ->select()
            ->toArray();
    }

    /**
     * @notes 获取预约角色候选人
     * @param int $staffId
     * @param string $roleKey
     * @param array $regionContext
     * @param string $date
     * @return array
     */
    public static function bookingRoleCandidates(
        int $staffId,
        string $roleKey,
        array $regionContext = [],
        string $date = ''
    ): array {
        return BookingFlowService::getRoleCandidates($staffId, $roleKey, $regionContext, $date);
    }

    /**
     * @notes 收藏/取消收藏
     * @param int $staffId
     * @param int $userId
     * @return array ['is_favorited' => bool, 'message' => string]
     */
    public static function toggleFavorite(int $staffId, int $userId): array
    {
        return Favorite::toggleFavorite($userId, $staffId);
    }

    /**
     * @notes 我收藏的工作人员
     * @param int $userId
     * @return array
     */
    public static function myFavorites(int $userId): array
    {
        $favoriteIds = Favorite::where('user_id', $userId)
            ->order('id desc')
            ->column('staff_id');

        if (empty($favoriteIds)) {
            return [];
        }

        $result = Staff::whereIn('id', $favoriteIds)
            ->where('delete_time', null)
            ->where('status', Staff::STATUS_ENABLE)
            ->field('id, sn, name, avatar, category_id, rating, order_count')
            ->append(['category_name'])
            ->select()
            ->toArray();

        StaffPriceService::injectDisplayPrice($result);

        // 获取工作人员的标签信息
        if (!empty($result)) {
            $staffIds = array_column($result, 'id');
            
            // 查询标签关联
            $staffTags = StaffTag::alias('st')
                ->leftJoin('style_tag tag', 'st.tag_id = tag.id')
                ->whereIn('st.staff_id', $staffIds)
                ->where('tag.delete_time', null)
                ->where('tag.is_show', 1)
                ->field('st.staff_id, tag.name')
                ->select()
                ->toArray();
            
            // 按工作人员ID分组标签
            $tagsByStaff = [];
            foreach ($staffTags as $staffTag) {
                $tagsByStaff[$staffTag['staff_id']][] = $staffTag['name'];
            }
            
            // 将标签添加到结果中
            foreach ($result as &$item) {
                $item['tags'] = $tagsByStaff[$item['id']] ?? [];
            }
        }

        return $result;
    }

    /**
     * @notes 批量获取员工信息（用于装修组件）
     * @param array $ids 员工ID列表
     * @param array $fields 需要的字段（可选）
     * @return array 员工数据列表，以ID为键的关联数组
     */
    public static function batchGetByIds(array $ids, array $fields = []): array
    {
        if (empty($ids)) {
            return [];
        }

        // 默认字段
        $defaultFields = [
            'id', 'name', 'avatar', 'sn', 'category_id',
            'rating', 'order_count', 'status'
        ];

        $fields = empty($fields) ? $defaultFields : $fields;

        try {
            $staffList = Staff::whereIn('id', $ids)
                ->where('delete_time', null)
                ->where('status', Staff::STATUS_ENABLE)
                ->where('audit_status', Staff::AUDIT_PASS)
                ->field($fields)
                ->select()
                ->toArray();

            // 转换为以 ID 为键的映射，方便查找
            $result = [];
            foreach ($staffList as $staff) {
                $result[$staff['id']] = $staff;
            }

            return $result;
        } catch (\Exception $e) {
            // 记录错误日志，返回空数组
            trace('批量查询员工数据失败: ' . $e->getMessage(), 'error');
            return [];
        }
    }

    /**
     * @notes 兼容旧前端结构，统一补齐 package_id 与 package 嵌套
     * @param array $packages
     * @return array
     */
    protected static function transformPackages(array $packages, array $regionContext = []): array
    {
        if (PackageRegionPriceService::hasRegionContext($regionContext)) {
            $packages = PackageRegionPriceService::applyResolvedPrices($packages, $regionContext, true);
        }

        $result = [];
        foreach ($packages as $package) {
            $result[] = array_merge($package, [
                'package_id' => (int)($package['id'] ?? 0),
                'package' => $package,
                'status' => 1,
            ]);
        }
        return $result;
    }

    /**
     * @notes 解析地区上下文
     * @param array $regionContext
     * @return array
     */
    protected static function resolveRegionContext(array $regionContext): array
    {
        $normalized = PackageRegionPriceService::normalizeRegionContext($regionContext);
        $hasRegionInput = $normalized['province_code'] !== ''
            || $normalized['city_code'] !== ''
            || $normalized['district_code'] !== '';

        if (!$hasRegionInput) {
            return [];
        }

        return PackageRegionPriceService::validateEnabledRegion($normalized);
    }

    /**
     * @notes 解析档期状态
     * @param int $staffId
     * @param string $date
     * @return array
     */
    protected static function resolveScheduleStatus(int $staffId, string $date): array
    {
        $date = trim($date);
        if ($staffId <= 0 || $date === '') {
            return [
                'available' => true,
                'message' => '',
            ];
        }

        [$available, $message] = Schedule::checkAvailabilityWithReason($staffId, $date, 0);
        return [
            'available' => $available,
            'message' => $message,
        ];
    }
}
