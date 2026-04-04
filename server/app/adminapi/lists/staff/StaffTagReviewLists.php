<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 服务人员标签审核列表
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\adminapi\lists\staff;

use app\adminapi\lists\BaseAdminDataLists;
use app\common\model\service\ServiceCategory;
use app\common\model\staff\Staff;
use app\common\model\staff\StaffTagApply;
use app\common\service\StaffTagReviewService;

class StaffTagReviewLists extends BaseAdminDataLists
{
    public function lists(): array
    {
        $query = $this->buildQuery();

        $lists = $query
            ->order($this->sortOrder ?: ['a.id' => 'desc'])
            ->limit($this->limitOffset, $this->limitLength)
            ->select()
            ->toArray();

        foreach ($lists as &$item) {
            $item = StaffTagReviewService::appendApplyDisplay($item);
        }

        return $lists;
    }

    public function count(): int
    {
        return $this->buildQuery()->count();
    }

    protected function buildQuery()
    {
        $staffTable = (new Staff())->getTable();
        $categoryTable = (new ServiceCategory())->getTable();

        $query = StaffTagApply::alias('a')
            ->leftJoin($staffTable . ' s', 's.id = a.staff_id')
            ->leftJoin($categoryTable . ' c', 'c.id = s.category_id')
            ->field([
                'a.id',
                'a.staff_id',
                'a.current_tag_ids',
                'a.apply_tag_ids',
                'a.source',
                'a.status',
                'a.reject_reason',
                'a.submit_user_id',
                'a.submit_admin_id',
                'a.audit_admin_id',
                'a.audit_time',
                'a.create_time',
                'a.update_time',
                's.name' => 'staff_name',
                's.avatar' => 'staff_avatar',
                's.category_id',
                'c.name' => 'category_name',
            ]);

        if (($this->params['status'] ?? '') !== '') {
            $query->where('a.status', (int) $this->params['status']);
        }
        if (($this->params['source'] ?? '') !== '') {
            $query->where('a.source', (int) $this->params['source']);
        }
        if (!empty($this->params['staff_id'])) {
            $query->where('a.staff_id', (int) $this->params['staff_id']);
        }
        if (!empty($this->params['category_id'])) {
            $query->where('s.category_id', (int) $this->params['category_id']);
        }

        $keyword = trim((string) ($this->params['keyword'] ?? ''));
        if ($keyword !== '') {
            $query->where(function ($subQuery) use ($keyword) {
                $subQuery->whereLike('s.name', '%' . $keyword . '%')
                    ->whereOr('s.mobile_full', 'like', '%' . $keyword . '%')
                    ->whereOr('s.sn', 'like', '%' . $keyword . '%');
            });
        }

        return $query;
    }
}
