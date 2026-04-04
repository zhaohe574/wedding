<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 风格标签列表
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\adminapi\lists\service;

use app\adminapi\lists\BaseAdminDataLists;
use app\common\lists\ListsSearchInterface;
use app\common\model\service\StyleTag;
use app\common\model\service\ServiceCategory;
use app\common\model\staff\StaffTag;

/**
 * 风格标签列表
 * Class StyleTagLists
 * @package app\adminapi\lists\service
 */
class StyleTagLists extends BaseAdminDataLists implements ListsSearchInterface
{
    /**
     * @notes 设置搜索条件
     * @return array
     */
    public function setSearch(): array
    {
        return [
            '=' => ['type', 'category_id', 'is_show'],
            '%like%' => ['name'],
        ];
    }

    /**
     * @notes 获取列表
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function lists(): array
    {
        $list = StyleTag::where($this->searchWhere)
            ->append(['type_desc', 'is_show_desc'])
            ->order(['sort' => 'desc', 'id' => 'asc'])
            ->limit($this->limitOffset, $this->limitLength)
            ->select()
            ->toArray();

        $categoryIds = array_values(array_unique(array_map('intval', array_column($list, 'category_id'))));
        $categoryIds = array_values(array_filter($categoryIds, function (int $id): bool {
            return $id > 0;
        }));
        $categories = [];
        if (!empty($categoryIds)) {
            $categories = ServiceCategory::whereIn('id', $categoryIds)
                ->column('name', 'id');
        }

        // 获取使用该标签的工作人员数量
        foreach ($list as &$item) {
            $categoryId = (int)($item['category_id'] ?? 0);
            $item['category_name'] = $categoryId === 0
                ? '全部人员可用'
                : ($categories[$categoryId] ?? '-');
            $item['staff_count'] = StaffTag::where('tag_id', $item['id'])->count();
        }

        return $list;
    }

    /**
     * @notes 获取数量
     * @return int
     */
    public function count(): int
    {
        return StyleTag::where($this->searchWhere)->count();
    }
}
