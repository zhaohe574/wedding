<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 附加服务列表
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\adminapi\lists\service;

use app\adminapi\lists\BaseAdminDataLists;
use app\common\lists\ListsExcelInterface;
use app\common\lists\ListsSearchInterface;
use app\common\model\service\ServiceAddon;
use app\common\model\service\ServiceCategory;
use app\common\model\service\ServicePackageAddon;
use app\common\model\staff\Staff;

/**
 * 附加服务列表
 * Class AddonLists
 * @package app\adminapi\lists\service
 */
class AddonLists extends BaseAdminDataLists implements ListsSearchInterface, ListsExcelInterface
{
    /**
     * @notes 设置搜索条件
     * @return array
     */
    public function setSearch(): array
    {
        return [
            '=' => ['a.is_show', 'a.staff_id'],
            '%like%' => ['a.name'],
        ];
    }

    /**
     * @notes 获取列表
     * @return array
     */
    public function lists(): array
    {
        $query = ServiceAddon::alias('a')
            ->leftJoin('staff s', 's.id = a.staff_id')
            ->where($this->searchWhere)
            ->where('a.staff_id', '>', 0)
            ->whereNull('a.delete_time');

        $staffScopeId = $this->getStaffScopeId();
        if ($staffScopeId > 0) {
            $query->where('a.staff_id', $staffScopeId);
        }

        if (!empty($this->params['category_id'])) {
            $query->where('s.category_id', (int)$this->params['category_id']);
        }

        $list = $query
            ->field('a.*')
            ->order($this->sortOrder ?: ['a.sort' => 'desc', 'a.id' => 'desc'])
            ->limit($this->limitOffset, $this->limitLength)
            ->select()
            ->toArray();

        $list = $this->fillStaffAndCategoryNames($list);
        $list = ServicePackageAddon::attachPackageIds($list);

        foreach ($list as $index => $item) {
            $list[$index]['package_count'] = count($item['package_ids'] ?? []);
        }

        return $list;
    }

    /**
     * @notes 获取数量
     * @return int
     */
    public function count(): int
    {
        $query = ServiceAddon::alias('a')
            ->leftJoin('staff s', 's.id = a.staff_id')
            ->where($this->searchWhere)
            ->where('a.staff_id', '>', 0)
            ->whereNull('a.delete_time');

        $staffScopeId = $this->getStaffScopeId();
        if ($staffScopeId > 0) {
            $query->where('a.staff_id', $staffScopeId);
        }

        if (!empty($this->params['category_id'])) {
            $query->where('s.category_id', (int)$this->params['category_id']);
        }

        return $query->count();
    }

    /**
     * @notes 批量填充人员与分类名称，避免逐行查询
     * @param array $list
     * @return array
     */
    protected function fillStaffAndCategoryNames(array $list): array
    {
        if (empty($list)) {
            return [];
        }

        $staffIds = array_values(array_unique(array_filter(array_map(
            static fn (array $item) => (int)($item['staff_id'] ?? 0),
            $list
        ))));

        $staffMap = [];
        $staffCategoryMap = [];
        if (!empty($staffIds)) {
            $staffRows = Staff::whereIn('id', $staffIds)
                ->whereNull('delete_time')
                ->field('id, name, category_id')
                ->select()
                ->toArray();
            foreach ($staffRows as $staff) {
                $staffId = (int)($staff['id'] ?? 0);
                $staffMap[$staffId] = (string)($staff['name'] ?? '');
                $staffCategoryMap[$staffId] = (int)($staff['category_id'] ?? 0);
            }
        }

        $categoryIds = array_values(array_unique(array_filter(array_map(
            static fn (array $item) => (int)($item['category_id'] ?? 0),
            $list
        ))));
        foreach ($staffCategoryMap as $categoryId) {
            if ($categoryId > 0) {
                $categoryIds[] = $categoryId;
            }
        }
        $categoryIds = array_values(array_unique($categoryIds));

        $categoryMap = [];
        if (!empty($categoryIds)) {
            $categoryRows = ServiceCategory::whereIn('id', $categoryIds)
                ->field('id, name')
                ->select()
                ->toArray();
            foreach ($categoryRows as $category) {
                $categoryMap[(int)($category['id'] ?? 0)] = (string)($category['name'] ?? '');
            }
        }

        foreach ($list as $index => $item) {
            $staffId = (int)($item['staff_id'] ?? 0);
            $categoryId = $staffCategoryMap[$staffId] ?? (int)($item['category_id'] ?? 0);

            $list[$index]['staff_name'] = $staffMap[$staffId] ?? '';
            $list[$index]['category_name'] = $categoryMap[$categoryId] ?? '';
            $list[$index]['is_show_desc'] = !empty($item['is_show']) ? '上架' : '下架';
        }

        return $list;
    }

    /**
     * @notes 导出字段
     * @return array
     */
    public function setExcelFields(): array
    {
        return [
            'id' => 'ID',
            'name' => '附加服务名称',
            'staff_name' => '所属人员',
            'category_name' => '所属分类',
            'price' => '价格',
            'original_price' => '原价',
            'is_show_desc' => '状态',
            'create_time' => '创建时间',
        ];
    }

    /**
     * @notes 导出文件名
     * @return string
     */
    public function setFileName(): string
    {
        return '附加服务列表';
    }
}
