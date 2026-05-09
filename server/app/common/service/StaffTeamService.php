<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 服务队伍通用服务
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\common\service;

use app\common\model\staff\StaffTeam;
use app\common\model\staff\StaffTeamMember;

/**
 * 服务队伍通用服务
 */
class StaffTeamService
{
    /**
     * @notes 获取服务人员当前有效队伍
     */
    public static function getActiveTeamByStaffId(int $staffId): array
    {
        if ($staffId <= 0) {
            return [];
        }

        $member = StaffTeamMember::alias('m')
            ->leftJoin((new StaffTeam())->getTable() . ' t', 't.id = m.team_id')
            ->where('m.staff_id', $staffId)
            ->where('m.status', StaffTeamMember::STATUS_ENABLED)
            ->where('t.status', StaffTeam::STATUS_ENABLED)
            ->whereNull('m.delete_time')
            ->whereNull('t.delete_time')
            ->field('m.id as member_id, t.id as team_id, t.name as team_name, t.leader_staff_id')
            ->order('m.id', 'desc')
            ->find();
        if (!$member) {
            $leaderTeam = self::getLeaderTeam($staffId);
            return empty($leaderTeam) ? [] : $leaderTeam;
        }

        return [
            'team_id' => (int)$member->team_id,
            'team_name' => (string)$member->team_name,
            'leader_staff_id' => (int)$member->leader_staff_id,
            'member_id' => (int)$member->member_id,
        ];
    }

    /**
     * @notes 获取队长当前有效队伍
     */
    public static function getLeaderTeam(int $leaderStaffId): array
    {
        if ($leaderStaffId <= 0) {
            return [];
        }

        $team = StaffTeam::where('leader_staff_id', $leaderStaffId)
            ->where('status', StaffTeam::STATUS_ENABLED)
            ->whereNull('delete_time')
            ->order('sort', 'desc')
            ->order('id', 'desc')
            ->find();
        if (!$team) {
            return [];
        }

        return [
            'team_id' => (int)$team->id,
            'team_name' => (string)$team->name,
            'leader_staff_id' => (int)$team->leader_staff_id,
        ];
    }

    /**
     * @notes 当前服务人员是否有效队长
     */
    public static function isLeader(int $staffId): bool
    {
        return !empty(self::getLeaderTeam($staffId));
    }

    /**
     * @notes 获取队长可管理队员ID
     */
    public static function getLeaderMemberStaffIds(int $leaderStaffId, bool $includeSelf = false): array
    {
        $team = self::getLeaderTeam($leaderStaffId);
        if (empty($team['team_id'])) {
            return $includeSelf && $leaderStaffId > 0 ? [$leaderStaffId] : [];
        }

        $ids = StaffTeamMember::where('team_id', (int)$team['team_id'])
            ->where('status', StaffTeamMember::STATUS_ENABLED)
            ->whereNull('delete_time')
            ->column('staff_id');
        $ids = array_values(array_unique(array_filter(array_map('intval', $ids))));

        if (!$includeSelf) {
            return array_values(array_filter($ids, static fn (int $id): bool => $id !== $leaderStaffId));
        }

        $ids[] = $leaderStaffId;
        return array_values(array_unique(array_filter($ids)));
    }

    /**
     * @notes 获取服务人员角色在后台可管理的人员范围
     */
    public static function getManageStaffIdsByStaffId(int $staffId, bool $includeSelf = true): array
    {
        if ($staffId <= 0) {
            return [];
        }

        if (!self::isLeader($staffId)) {
            return $includeSelf ? [$staffId] : [];
        }

        return self::getLeaderMemberStaffIds($staffId, $includeSelf);
    }

    /**
     * @notes 判断队长是否可管理目标人员
     */
    public static function isLeaderOfStaff(int $leaderStaffId, int $targetStaffId): bool
    {
        if ($leaderStaffId <= 0 || $targetStaffId <= 0) {
            return false;
        }

        return in_array($targetStaffId, self::getLeaderMemberStaffIds($leaderStaffId, false), true);
    }
}
