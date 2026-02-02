<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 工作人员证书列表
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\adminapi\lists\staff;

use app\adminapi\lists\BaseAdminDataLists;
use app\common\lists\ListsSearchInterface;
use app\common\model\staff\StaffCertificate;

/**
 * 工作人员证书列表
 * Class StaffCertificateLists
 * @package app\adminapi\lists\staff
 */
class StaffCertificateLists extends BaseAdminDataLists implements ListsSearchInterface
{
    /**
     * @notes 设置搜索条件
     * @return array
     */
    public function setSearch(): array
    {
        return [
            '=' => ['staff_id', 'type', 'verify_status'],
            '%like%' => ['name', 'sn'],
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
        $query = StaffCertificate::where($this->searchWhere);

        $staffScopeId = $this->getStaffScopeId();
        if ($staffScopeId > 0) {
            $query->where('staff_id', $staffScopeId);
        }

        $list = $query
            ->with(['staff' => function($query) {
                $query->field('id, name, sn');
            }])
            ->append(['verify_status_desc', 'is_expired'])
            ->order(['id' => 'desc'])
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
        $query = StaffCertificate::where($this->searchWhere);
        $staffScopeId = $this->getStaffScopeId();
        if ($staffScopeId > 0) {
            $query->where('staff_id', $staffScopeId);
        }
        return $query->count();
    }
}
