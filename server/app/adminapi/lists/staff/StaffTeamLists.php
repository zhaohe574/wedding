<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 服务队伍列表
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\adminapi\lists\staff;

use app\adminapi\lists\BaseAdminDataLists;
use app\common\lists\ListsSearchInterface;
use app\common\model\staff\StaffTeam;

/**
 * 服务队伍列表
 */
class StaffTeamLists extends BaseAdminDataLists implements ListsSearchInterface
{
    /**
     * @notes 搜索条件
     */
    public function setSearch(): array
    {
        return [
            '%like%' => ['name'],
            '=' => ['leader_staff_id', 'status'],
        ];
    }

    /**
     * @notes 列表
     */
    public function lists(): array
    {
        $list = StaffTeam::with(['leader', 'members.staff'])
            ->where($this->searchWhere)
            ->order(['sort' => 'desc', 'id' => 'desc'])
            ->limit($this->limitOffset, $this->limitLength)
            ->select()
            ->toArray();

        foreach ($list as &$item) {
            $members = array_values(array_filter($item['members'] ?? [], static function ($member): bool {
                return (int)($member['status'] ?? 0) === StaffTeam::STATUS_ENABLED && !empty($member['staff']);
            }));
            $item['members'] = $members;
            $item['member_count'] = count($members);
            $item['member_ids'] = array_values(array_filter(array_map(
                static fn ($member): int => (int)($member['staff_id'] ?? 0),
                $members
            )));
            $item['status_text'] = StaffTeam::getStatusDesc((int)$item['status']);
        }
        unset($item);

        return $list;
    }

    /**
     * @notes 总数
     */
    public function count(): int
    {
        return StaffTeam::where($this->searchWhere)->count();
    }
}
