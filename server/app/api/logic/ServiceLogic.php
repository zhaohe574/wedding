<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 小程序端服务逻辑
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\api\logic;

use app\common\logic\BaseLogic;
use app\common\model\service\ServiceCategory;
use app\common\model\service\ServicePackage;
use app\common\model\service\StyleTag;
use app\common\model\package\PackageBooking;

/**
 * 服务逻辑（小程序端）
 * Class ServiceLogic
 * @package app\api\logic
 */
class ServiceLogic extends BaseLogic
{
    /**
     * @notes 服务分类列表
     * @param int $pid
     * @return array
     */
    public static function categories(int $pid = 0): array
    {
        return ServiceCategory::where('delete_time', null)
            ->where('is_show', 1)
            ->where('pid', $pid)
            ->order('sort desc, id asc')
            ->field('id, pid, name, icon')
            ->select()
            ->toArray();
    }

    /**
     * @notes 服务分类树形结构
     * @return array
     */
    public static function categoryTree(): array
    {
        return ServiceCategory::getCategoryTree();
    }

    /**
     * @notes 服务套餐列表
     * @param int $categoryId
     * @return array
     */
    public static function packages(int $categoryId = 0): array
    {
        $query = ServicePackage::where('delete_time', null)
            ->where('is_show', 1)
            ->where('staff_id', '>', 0);

        if ($categoryId > 0) {
            $query->where('category_id', $categoryId);
        }

        return $query->order('is_recommend desc, sort desc, id desc')
            ->field('id, staff_id, category_id, name, price, original_price, description, is_recommend, image')
            ->append(['category_name'])
            ->select()
            ->toArray();
    }

    /**
     * @notes 服务套餐详情
     * @param int $id
     * @return array
     */
    public static function packageDetail(int $id): array
    {
        $package = ServicePackage::where('id', $id)
            ->where('delete_time', null)
            ->where('is_show', 1)
            ->with(['category'])
            ->find();

        if (!$package) {
            return [];
        }

        return $package->toArray();
    }

    /**
     * @notes 风格标签列表
     * @param int $type 标签类型 0=全部
     * @param bool $grouped 是否按类型分组
     * @return array
     */
    public static function tags(int $type = 0, bool $grouped = false, int $categoryId = 0): array
    {
        $query = StyleTag::where('delete_time', null)
            ->where('is_show', 1)
            ->order('sort desc, id asc')
            ->field('id, name, type, category_id');

        if ($type > 0) {
            $query->where('type', $type);
        }
        if ($categoryId > 0) {
            // 选择具体分类时，同时返回全局标签(category_id=0)
            $query->whereIn('category_id', [0, $categoryId]);
        }

        $list = $query->select()->toArray();

        if ($grouped) {
            $result = [];
            $typeMap = [
                StyleTag::TYPE_STYLE => '风格',
                StyleTag::TYPE_SKILL => '特长',
                StyleTag::TYPE_OTHER => '其他',
            ];
            foreach ($list as $item) {
                $typeName = $typeMap[$item['type']] ?? '其他';
                if (!isset($result[$typeName])) {
                    $result[$typeName] = [];
                }
                $result[$typeName][] = $item;
            }
            return $result;
        }

        return $list;
    }

    /**
     * @notes 检查套餐可用性（单日唯一限制）
     * @param int $packageId
     * @param string $date
     * @return array
     */
    public static function checkPackageAvailability(int $packageId, string $date, int $staffId = 0, int $timeSlot = 0): array
    {
        return PackageBooking::checkAvailability($packageId, $date, $staffId, 0);
    }

    /**
     * @notes 批量检查套餐可用性
     * @param array $packageIds
     * @param string $date
     * @return array
     */
    public static function batchCheckAvailability(array $packageIds, string $date, int $staffId = 0, int $timeSlot = 0): array
    {
        return PackageBooking::batchCheckAvailability($packageIds, $date, $staffId, 0);
    }

    /**
     * @notes 获取套餐时段价格
     * @param int $packageId
     * @param int $staffId
     * @return array
     */
    public static function getPackageSlotPrices(int $packageId, int $staffId = 0): array
    {
        $package = ServicePackage::find($packageId);
        if (!$package) {
            return [];
        }

        $result = [
            'package_id' => $packageId,
            'price' => (float)$package->price,
        ];

        return $result;
    }

    /**
     * @notes 计算套餐最终价格
     * @param int $packageId
     * @param int $staffId
     * @param string $startTime
     * @param string $endTime
     * @return array
     */
    public static function calculatePrice(
        int $packageId,
        int $staffId = 0,
        string $startTime = '',
        string $endTime = '',
        int $timeSlot = -1
    ): array {
        $package = ServicePackage::find($packageId);
        if (!$package) {
            return ['price' => 0, 'source' => 'not_found'];
        }

        if ($staffId > 0 && (int)$package->staff_id !== $staffId) {
            return ['price' => 0, 'source' => 'not_match_staff'];
        }
        return ['price' => $package->calculatePrice(), 'source' => 'package_default'];
    }
}
