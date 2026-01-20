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
}
