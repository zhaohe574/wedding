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
            '=' => ['a.is_show', 'a.staff_id', 'a.category_id'],
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
            ->where($this->searchWhere)
            ->whereNull('a.delete_time');

        $staffScopeId = $this->getStaffScopeId();
        if ($staffScopeId > 0) {
            $query->where('a.staff_id', $staffScopeId);
        }

        return $query
            ->field('a.*')
            ->with(['staff'])
            ->append(['category_name', 'staff_name', 'is_show_desc'])
            ->order($this->sortOrder ?: ['a.sort' => 'desc', 'a.id' => 'desc'])
            ->limit($this->limitOffset, $this->limitLength)
            ->select()
            ->toArray();
    }

    /**
     * @notes 获取数量
     * @return int
     */
    public function count(): int
    {
        $query = ServiceAddon::alias('a')
            ->where($this->searchWhere)
            ->whereNull('a.delete_time');

        $staffScopeId = $this->getStaffScopeId();
        if ($staffScopeId > 0) {
            $query->where('a.staff_id', $staffScopeId);
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
