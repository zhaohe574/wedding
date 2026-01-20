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
            '=' => ['category_id', 'is_show', 'is_recommend'],
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
        $list = ServicePackage::where($this->searchWhere)
            ->append(['category_name', 'is_show_desc', 'is_recommend_desc', 'duration_desc'])
            ->order($this->sortOrder ?: ['sort' => 'desc', 'id' => 'desc'])
            ->limit($this->limitOffset, $this->limitLength)
            ->select()
            ->toArray();

        return $list;
    }

    /**
     * @notes 获取数量
     * @return int
     */
    public function count(): int
    {
        return ServicePackage::where($this->searchWhere)->count();
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
            'category_name' => '所属分类',
            'price' => '价格',
            'original_price' => '原价',
            'duration_desc' => '服务时长',
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
