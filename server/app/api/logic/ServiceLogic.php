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
use app\common\model\staff\StaffPackage;

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
            ->field('id, pid, name, icon, level')
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
            ->where('is_show', 1);

        if ($categoryId > 0) {
            $query->where('category_id', $categoryId);
        }

        return $query->order('is_recommend desc, sort desc, id desc')
            ->field('id, category_id, name, price, original_price, duration, description, is_recommend')
            ->append(['category_name', 'duration_desc'])
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
    public static function tags(int $type = 0, bool $grouped = false): array
    {
        $query = StyleTag::where('delete_time', null)
            ->where('is_show', 1)
            ->order('sort desc, id asc')
            ->field('id, name, type');

        if ($type > 0) {
            $query->where('type', $type);
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
    public static function checkPackageAvailability(int $packageId, string $date): array
    {
        return PackageBooking::checkAvailability($packageId, $date);
    }

    /**
     * @notes 批量检查套餐可用性
     * @param array $packageIds
     * @param string $date
     * @return array
     */
    public static function batchCheckAvailability(array $packageIds, string $date): array
    {
        return PackageBooking::batchCheckAvailability($packageIds, $date);
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
            'default_price' => (float)$package->price,
            'slot_prices' => $package->slot_prices ?? [],
            'custom_price' => null,
            'custom_slot_prices' => [],
        ];

        // 如果指定了员工，获取员工的个人配置
        if ($staffId > 0) {
            $staffPackage = StaffPackage::where('staff_id', $staffId)
                ->where('package_id', $packageId)
                ->where('status', 1)
                ->find();

            if ($staffPackage) {
                $result['custom_price'] = $staffPackage->custom_price;
                $result['custom_slot_prices'] = $staffPackage->custom_slot_prices ?? [];
            }
        }

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
        string $endTime = ''
    ): array {
        $package = ServicePackage::find($packageId);
        if (!$package) {
            return ['price' => 0, 'source' => 'not_found'];
        }

        // 如果指定了员工，尝试获取员工的个人配置
        if ($staffId > 0) {
            $staffPackage = StaffPackage::where('staff_id', $staffId)
                ->where('package_id', $packageId)
                ->where('status', 1)
                ->find();

            if ($staffPackage) {
                $price = $staffPackage->calculatePrice($startTime, $endTime);
                $source = 'staff_config';
                
                // 判断价格来源
                if (!empty($startTime) && !empty($endTime)) {
                    $customSlotPrice = $staffPackage->getCustomSlotPrice($startTime, $endTime);
                    if ($customSlotPrice !== null) {
                        $source = 'staff_slot_price';
                    } elseif ($staffPackage->custom_price !== null) {
                        $source = 'staff_custom_price';
                    } else {
                        $packageSlotPrice = $package->getSlotPrice($startTime, $endTime);
                        if ($packageSlotPrice !== null) {
                            $source = 'package_slot_price';
                        } else {
                            $source = 'package_default';
                        }
                    }
                } elseif ($staffPackage->custom_price !== null) {
                    $source = 'staff_custom_price';
                }
                
                return ['price' => $price, 'source' => $source];
            }
        }

        // 没有员工配置，使用套餐本身的价格计算
        $price = $package->calculatePrice($startTime, $endTime);
        $source = 'package_default';
        
        if (!empty($startTime) && !empty($endTime)) {
            $slotPrice = $package->getSlotPrice($startTime, $endTime);
            if ($slotPrice !== null) {
                $source = 'package_slot_price';
            }
        }
        
        return ['price' => $price, 'source' => $source];
    }
}
