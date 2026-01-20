<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 服务分类列表
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\adminapi\lists\service;

use app\adminapi\lists\BaseAdminDataLists;
use app\common\lists\ListsSearchInterface;
use app\common\model\service\ServiceCategory;

/**
 * 服务分类列表
 * Class CategoryLists
 * @package app\adminapi\lists\service
 */
class CategoryLists extends BaseAdminDataLists implements ListsSearchInterface
{
    /**
     * @notes 设置搜索条件
     * @return array
     */
    public function setSearch(): array
    {
        return [
            '=' => ['is_show', 'pid', 'level'],
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
        $list = ServiceCategory::where($this->searchWhere)
            ->append(['is_show_desc'])
            ->order(['sort' => 'desc', 'id' => 'asc'])
            ->limit($this->limitOffset, $this->limitLength)
            ->select()
            ->toArray();

        // 获取父级名称
        $parentIds = array_unique(array_column($list, 'pid'));
        $parents = ServiceCategory::whereIn('id', $parentIds)
            ->column('name', 'id');

        foreach ($list as &$item) {
            $item['parent_name'] = $item['pid'] > 0 ? ($parents[$item['pid']] ?? '-') : '顶级分类';
            $item['staff_count'] = \app\common\model\staff\Staff::where('category_id', $item['id'])
                ->where('delete_time', null)
                ->count();
        }

        return $list;
    }

    /**
     * @notes 获取数量
     * @return int
     */
    public function count(): int
    {
        return ServiceCategory::where($this->searchWhere)->count();
    }
}
