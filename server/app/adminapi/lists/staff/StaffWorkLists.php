<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 工作人员作品列表
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\adminapi\lists\staff;

use app\adminapi\lists\BaseAdminDataLists;
use app\common\lists\ListsSearchInterface;
use app\common\model\staff\StaffWork;

/**
 * 工作人员作品列表
 * Class StaffWorkLists
 * @package app\adminapi\lists\staff
 */
class StaffWorkLists extends BaseAdminDataLists implements ListsSearchInterface
{
    /**
     * @notes 设置搜索条件
     * @return array
     */
    public function setSearch(): array
    {
        return [
            '=' => ['staff_id', 'type', 'is_show', 'is_cover'],
            '%like%' => ['title'],
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
        $query = StaffWork::where($this->searchWhere);

        $staffScopeId = $this->getStaffScopeId();
        if ($staffScopeId > 0) {
            $query->where('staff_id', $staffScopeId);
        }

        $list = $query
            ->with(['staff' => function($query) {
                $query->field('id, name, sn');
            }])
            ->append(['type_desc', 'is_show_desc'])
            ->order(['sort' => 'desc', 'id' => 'desc'])
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
        $query = StaffWork::where($this->searchWhere);
        $staffScopeId = $this->getStaffScopeId();
        if ($staffScopeId > 0) {
            $query->where('staff_id', $staffScopeId);
        }
        return $query->count();
    }
}
