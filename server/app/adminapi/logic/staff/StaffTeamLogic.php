<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 服务队伍逻辑
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\adminapi\logic\staff;

use app\common\logic\BaseLogic;
use app\common\model\staff\Staff;
use app\common\model\staff\StaffTeam;
use app\common\model\staff\StaffTeamMember;
use think\facade\Db;

/**
 * 服务队伍逻辑
 */
class StaffTeamLogic extends BaseLogic
{
    /**
     * @notes 详情
     */
    public static function detail(int $id): array
    {
        $team = StaffTeam::with(['leader', 'members.staff'])->find($id);
        if (!$team) {
            return [];
        }

        $data = $team->toArray();
        $data['member_ids'] = array_values(array_filter(array_map(
            static fn ($member): int => (int)($member['staff_id'] ?? 0),
            $data['members'] ?? []
        )));
        $data['status_text'] = StaffTeam::getStatusDesc((int)$data['status']);
        return $data;
    }

    /**
     * @notes 新增队伍
     */
    public static function add(array $params): bool
    {
        try {
            self::saveTeam(0, $params);
            return true;
        } catch (\Throwable $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * @notes 编辑队伍
     */
    public static function edit(array $params): bool
    {
        try {
            self::saveTeam((int)$params['id'], $params);
            return true;
        } catch (\Throwable $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * @notes 删除队伍
     */
    public static function delete(int $id): bool
    {
        try {
            $team = StaffTeam::find($id);
            if (!$team) {
                self::setError('服务队伍不存在');
                return false;
            }

            Db::transaction(function () use ($team) {
                StaffTeamMember::where('team_id', (int)$team->id)->delete();
                $team->delete();
            });
            return true;
        } catch (\Throwable $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * @notes 修改状态
     */
    public static function changeStatus(array $params): bool
    {
        try {
            $team = StaffTeam::find((int)$params['id']);
            if (!$team) {
                self::setError('服务队伍不存在');
                return false;
            }
            $team->status = (int)$params['status'];
            $team->update_time = time();
            return $team->save();
        } catch (\Throwable $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * @notes 队伍选项
     */
    public static function options(): array
    {
        return StaffTeam::with(['leader'])
            ->where('status', StaffTeam::STATUS_ENABLED)
            ->whereNull('delete_time')
            ->order(['sort' => 'desc', 'id' => 'desc'])
            ->select()
            ->map(static function (StaffTeam $team): array {
                return [
                    'id' => (int)$team->id,
                    'name' => (string)$team->name,
                    'leader_staff_id' => (int)$team->leader_staff_id,
                    'leader_name' => $team->leader ? (string)$team->leader->name : '',
                ];
            })
            ->toArray();
    }

    /**
     * @notes 保存队伍及成员
     */
    protected static function saveTeam(int $id, array $params): void
    {
        Db::transaction(function () use ($id, $params) {
            $leaderStaffId = (int)$params['leader_staff_id'];
            $leader = Staff::where('id', $leaderStaffId)->whereNull('delete_time')->find();
            if (!$leader) {
                throw new \RuntimeException('队长服务人员不存在');
            }

            $memberIds = self::normalizeMemberIds($params['member_ids'] ?? []);
            if (in_array($leaderStaffId, $memberIds, true)) {
                $memberIds = array_values(array_filter($memberIds, static fn (int $memberId): bool => $memberId !== $leaderStaffId));
            }

            self::assertStaffExists($memberIds);
            self::assertMembersAvailable($memberIds, $id);

            $team = $id > 0 ? StaffTeam::find($id) : new StaffTeam();
            if (!$team) {
                throw new \RuntimeException('服务队伍不存在');
            }

            $team->name = trim((string)$params['name']);
            $team->leader_staff_id = $leaderStaffId;
            $team->status = (int)($params['status'] ?? StaffTeam::STATUS_ENABLED);
            $team->sort = (int)($params['sort'] ?? 0);
            $team->remark = (string)($params['remark'] ?? '');
            $team->update_time = time();
            if ($id <= 0) {
                $team->create_time = time();
            }
            $team->save();

            StaffTeamMember::where('team_id', (int)$team->id)->delete();
            foreach ($memberIds as $memberId) {
                $member = new StaffTeamMember();
                $member->team_id = (int)$team->id;
                $member->staff_id = $memberId;
                $member->status = StaffTeamMember::STATUS_ENABLED;
                $member->create_time = time();
                $member->update_time = time();
                $member->save();
            }
        });
    }

    /**
     * @notes 归一化队员ID
     */
    protected static function normalizeMemberIds($memberIds): array
    {
        if (!is_array($memberIds)) {
            return [];
        }

        return array_values(array_unique(array_filter(array_map('intval', $memberIds))));
    }

    /**
     * @notes 校验队员真实存在
     */
    protected static function assertStaffExists(array $memberIds): void
    {
        if (empty($memberIds)) {
            return;
        }

        $existsIds = Staff::whereIn('id', $memberIds)
            ->whereNull('delete_time')
            ->column('id');
        $existsIds = array_values(array_unique(array_map('intval', $existsIds)));
        $missingIds = array_values(array_diff($memberIds, $existsIds));
        if (!empty($missingIds)) {
            throw new \RuntimeException('队员服务人员不存在：#' . implode('、#', $missingIds));
        }
    }

    /**
     * @notes 校验队员没有归属其他有效队伍
     */
    protected static function assertMembersAvailable(array $memberIds, int $currentTeamId): void
    {
        if (empty($memberIds)) {
            return;
        }

        $existing = StaffTeamMember::alias('m')
            ->leftJoin((new StaffTeam())->getTable() . ' t', 't.id = m.team_id')
            ->whereIn('m.staff_id', $memberIds)
            ->where('m.status', StaffTeamMember::STATUS_ENABLED)
            ->where('t.status', StaffTeam::STATUS_ENABLED)
            ->whereNull('m.delete_time')
            ->whereNull('t.delete_time')
            ->when($currentTeamId > 0, static function ($query) use ($currentTeamId) {
                $query->where('m.team_id', '<>', $currentTeamId);
            })
            ->field('m.staff_id, t.name as team_name')
            ->select()
            ->toArray();

        if (!empty($existing)) {
            $staffId = (int)($existing[0]['staff_id'] ?? 0);
            $teamName = (string)($existing[0]['team_name'] ?? '');
            throw new \RuntimeException('服务人员 #' . $staffId . ' 已归属有效队伍' . ($teamName ? '：' . $teamName : ''));
        }
    }
}
