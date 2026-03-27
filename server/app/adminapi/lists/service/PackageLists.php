<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 服务套餐列表
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\adminapi\lists\service;

use app\adminapi\lists\BaseAdminDataLists;
use app\common\lists\ListsSearchInterface;
use app\common\lists\ListsExcelInterface;
use app\common\model\service\ServicePackage;
use app\common\service\PackageRegionPriceService;

/**
 * 服务套餐列表
 * Class PackageLists
 * @package app\adminapi\lists\service
 */
class PackageLists extends BaseAdminDataLists implements ListsSearchInterface, ListsExcelInterface
{
    /**
     * @notes 设置搜索条件
     * @return array
     */
    public function setSearch(): array
    {
        return [
            '=' => ['p.is_show', 'p.is_recommend', 'p.staff_id'],
            '%like%' => ['p.name'],
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
        $query = ServicePackage::alias('p')
            ->leftJoin('staff s', 's.id = p.staff_id')
            ->where($this->searchWhere)
            ->where('p.staff_id', '>', 0)
            ->whereNull('p.delete_time');

        if (!empty($this->params['category_id'])) {
            $query->where('s.category_id', (int)$this->params['category_id']);
        }

        $list = $query
            ->field('p.*')
            ->with(['staff'])
            ->append(['category_name', 'is_show_desc', 'is_recommend_desc', 'staff_name'])
            ->order($this->sortOrder ?: ['p.sort' => 'desc', 'p.id' => 'desc'])
            ->limit($this->limitOffset, $this->limitLength)
            ->select()
            ->toArray();

        return PackageRegionPriceService::attachRegionPrices($list);
    }

    /**
     * @notes 获取数量
     * @return int
     */
    public function count(): int
    {
        $query = ServicePackage::alias('p')
            ->leftJoin('staff s', 's.id = p.staff_id')
            ->where($this->searchWhere)
            ->where('p.staff_id', '>', 0)
            ->whereNull('p.delete_time');

        if (!empty($this->params['category_id'])) {
            $query->where('s.category_id', (int)$this->params['category_id']);
        }

        return $query->count();
    }

    /**
     * @notes 导出字段
     * @return array
     */
    public function setExcelFields(): array
    {
        return [
            'id' => 'ID',
            'name' => '套餐名称',
            'staff_name' => '所属员工',
            'price' => '价格',
            'original_price' => '原价',
            'is_show_desc' => '状态',
            'is_recommend_desc' => '推荐',
            'create_time' => '创建时间',
        ];
    }

    /**
     * @notes 导出文件名
     * @return string
     */
    public function setFileName(): string
    {
        return '服务套餐列表';
    }
}
