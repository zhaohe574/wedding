<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 小程序端工作人员逻辑
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\api\logic;

use app\common\logic\BaseLogic;
use app\common\model\schedule\Schedule;
use app\common\model\staff\Staff;
use app\common\model\staff\StaffCertificate;
use app\common\model\staff\StaffWork;
use app\common\model\staff\Favorite;
use app\common\model\staff\StaffTag;
use app\common\model\service\ServiceAddon;
use app\common\model\service\ServicePackage;
use app\common\service\BookingFlowService;
use app\common\service\ConfigService;
use app\common\service\MobileImageService;
use app\common\service\PackageRegionPriceService;
use app\common\service\StaffPriceService;
use think\facade\Db;

/**
 * 工作人员逻辑（小程序端）
 * Class StaffLogic
 * @package app\api\logic
 */
class StaffLogic extends BaseLogic
{
    private static ?string $staffCertificateStatusField = null;

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
        $scheduleStatus = self::resolveScheduleStatus($id, $date, $userId);

        $staff = Staff::where('id', $id)
            ->where('delete_time', null)
            ->where('status', Staff::STATUS_ENABLE)
            ->where('audit_status', Staff::AUDIT_PASS)
            ->with(['category', 'certificates' => function($query) {
                $query->where(self::getStaffCertificateStatusField(), StaffCertificate::VERIFY_PASS)
                    ->limit(10);
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
        $data['addons'] = BookingFlowService::getStaffBookingAddons((int)$staff->id);
        $data['related_role_configs'] = BookingFlowService::getCategoryRoleConfigs((int)$staff->category_id);

        // 获取轮播图
        $data['banners'] = \app\common\model\staff\StaffBanner::where('staff_id', $id)
            ->order('sort', 'asc')
            ->order('id', 'asc')
            ->select()
            ->toArray();
        $data['banners'] = self::optimizeBannerList($data['banners']);
        $data['certificates'] = self::optimizeCertificateList($data['certificates'] ?? []);
        $data['long_detail'] = self::optimizeLongDetailContent((string) ($data['long_detail'] ?? ''));

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
        $staff = Staff::where('id', $staffId)
            ->where('delete_time', null)
            ->where('status', Staff::STATUS_ENABLE)
            ->where('audit_status', Staff::AUDIT_PASS)
            ->find();

        if (!$staff) {
            return [];
        }

        $works = StaffWork::where('staff_id', $staffId)
            ->where('delete_time', null)
            ->where('is_show', 1)
            ->where('audit_status', StaffWork::AUDIT_PASS)
            ->order('sort desc, id desc')
            ->select()
            ->toArray();

        return self::optimizeWorkList($works);
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
        $data['cover'] = MobileImageService::toMobileDisplayUrl((string) ($data['cover'] ?? ''));
        $data['images'] = array_values(array_filter(array_map(static function ($image) {
            return MobileImageService::toMobileDisplayUrl((string) $image);
        }, is_array($data['images'] ?? null) ? $data['images'] : [])));
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

        return ServiceAddon::alias('addon')
            ->where('addon.staff_id', $staffId)
            ->whereNull('addon.delete_time')
            ->where('addon.is_show', 1)
            ->field('addon.id, addon.staff_id, addon.category_id, addon.name, addon.price, addon.original_price, addon.image, addon.description, addon.sort, addon.is_show')
            ->order('addon.sort desc, addon.id desc')
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
        string $date = '',
        int $userId = 0
    ): array {
        return BookingFlowService::getRoleCandidates(
            $staffId,
            $roleKey,
            $regionContext,
            $date,
            $userId
        );
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
            $duration = (int) ($package['duration'] ?? 0);
            $packageWithMeta = array_merge($package, [
                'duration' => $duration,
                'duration_desc' => $duration > 0 ? $duration . '小时' : '',
            ]);
            $result[] = array_merge($packageWithMeta, [
                'package_id' => (int)($package['id'] ?? 0),
                'package' => $packageWithMeta,
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
            'message' => $message,
        ];
    }

    /**
     * @notes 优化详情轮播图资源
     * @param array $banners
     * @return array
     */
    protected static function optimizeBannerList(array $banners): array
    {
        foreach ($banners as &$banner) {
            $type = (int) ($banner['type'] ?? 1);
            if ($type === 1) {
                $banner['file_url'] = MobileImageService::toMobileDisplayUrl(
                    (string) ($banner['file_url'] ?? '')
                );
                continue;
            }

            $banner['cover_url'] = MobileImageService::toMobileDisplayUrl(
                (string) ($banner['cover_url'] ?? '')
            );
        }

        return $banners;
    }

    /**
     * @notes 优化证书图片
     * @param array $certificates
     * @return array
     */
    protected static function optimizeCertificateList(array $certificates): array
    {
        foreach ($certificates as &$certificate) {
            $certificate['image'] = MobileImageService::toMobileDisplayUrl(
                (string) ($certificate['image'] ?? '')
            );
        }

        return $certificates;
    }

    /**
     * @notes 优化作品列表图片
     * @param array $works
     * @return array
     */
    protected static function optimizeWorkList(array $works): array
    {
        foreach ($works as &$work) {
            $work['cover'] = MobileImageService::toMobileDisplayUrl((string) ($work['cover'] ?? ''));
            $images = is_array($work['images'] ?? null) ? $work['images'] : [];
            $work['images'] = array_values(array_filter(array_map(static function ($image) {
                return MobileImageService::toMobileDisplayUrl((string) $image);
            }, $images)));
        }

        return $works;
    }

    /**
     * @notes 优化长图详情中的图片地址
     * @param string $content
     * @return string
     */
    protected static function optimizeLongDetailContent(string $content): string
    {
        $content = trim($content);
        if ($content === '') {
            return $content;
        }

        $blocks = json_decode($content, true);
        if (!is_array($blocks)) {
            return $content;
        }

        foreach ($blocks as &$block) {
            if (($block['type'] ?? '') !== 'image' || !is_array($block['images'] ?? null)) {
                continue;
            }

            $block['images'] = array_values(array_filter(array_map(static function ($image) {
                return MobileImageService::toMobileDisplayUrl((string) $image);
            }, $block['images'])));
        }

        return json_encode($blocks, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: $content;
    }

    /**
     * @notes 获取证书状态字段名（兼容 verify_status / audit_status）
     */
    protected static function getStaffCertificateStatusField(): string
    {
        if (self::$staffCertificateStatusField !== null) {
            return self::$staffCertificateStatusField;
        }

        $fields = Db::name('staff_certificate')->getFields();
        self::$staffCertificateStatusField = isset($fields['verify_status'])
            ? 'verify_status'
            : 'audit_status';

        return self::$staffCertificateStatusField;
    }
}
