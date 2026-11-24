<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 工作台团队成员协作与管理服务
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\common\service;

use app\common\logic\BaseLogic;
use app\common\model\order\Order;
use app\common\model\order\OrderItem;
use app\common\model\schedule\ManualSchedule;
use app\common\model\schedule\Schedule;
use app\common\model\staff\Staff;
use app\common\model\staff\StaffTeam;
use app\common\model\staff\StaffTeamMember;
use think\facade\Db;

/**
 * 服务人员工作台 P3 团队成员管理服务
 */
class StaffTeamWorkbenchService extends BaseLogic
{
    /**
     * @notes 获取工作台团队概览、成员列表与近 14 天档期热力视图
     */
    public static function teamOverview(int $staffId): array
    {
        if ($staffId <= 0) {
            self::setError('未绑定服务人员');
            return [];
        }

        $leaderTeam = StaffTeamService::getLeaderTeam($staffId);
        $activeTeam = $leaderTeam ?: StaffTeamService::getActiveTeamByStaffId($staffId);
        if (empty($activeTeam['team_id'])) {
            return [
                'role' => 'none',
                'is_leader' => false,
                'team' => null,
                'summary' => null,
                'members' => [],
            ];
        }

        $teamId = (int)$activeTeam['team_id'];
        $team = StaffTeam::where('id', $teamId)
            ->where('status', StaffTeam::STATUS_ENABLED)
            ->whereNull('delete_time')
            ->find();
        if (!$team) {
            return [
                'role' => 'none',
                'is_leader' => false,
                'team' => null,
                'summary' => null,
                'members' => [],
            ];
        }

        $leaderStaffId = (int)$team->leader_staff_id;
        $isLeader = $leaderStaffId === $staffId;
        $role = $isLeader ? 'leader' : 'member';

        $memberRows = StaffTeamMember::where('team_id', $teamId)
            ->whereNull('delete_time')
            ->order('status', 'desc')
            ->order('id', 'asc')
            ->select();

        $allStaffIds = [$leaderStaffId];
        foreach ($memberRows as $mr) {
            $sid = (int)$mr->staff_id;
            if ($sid > 0 && !in_array($sid, $allStaffIds, true)) {
                $allStaffIds[] = $sid;
            }
        }

        $staffMap = [];
        $staffCollection = Staff::whereIn('id', $allStaffIds)
            ->whereNull('delete_time')
            ->append(['category_name'])
            ->select();
        foreach ($staffCollection as $st) {
            $staffMap[(int)$st->id] = $st;
        }

        $today = date('Y-m-d');
        $end14 = date('Y-m-d', strtotime($today . ' +13 days'));
        $end7 = date('Y-m-d', strtotime($today . ' +6 days'));
        $monthStart = date('Y-m-01');
        $monthEnd = date('Y-m-t');
        $weekNames = ['日', '一', '二', '三', '四', '五', '六'];

        // 近 14 天档期表
        $scheduleRows = Schedule::whereIn('staff_id', $allStaffIds)
            ->where('time_slot', Schedule::TIME_SLOT_ALL)
            ->whereBetween('schedule_date', [$today, $end14])
            ->field('staff_id, schedule_date, status')
            ->select()
            ->toArray();
        $scheduleMap = [];
        foreach ($scheduleRows as $sr) {
            $scheduleMap[(int)$sr['staff_id']][(string)$sr['schedule_date']] = (int)$sr['status'];
        }

        // 近 14 天线下自主档期表
        $manualRows = ManualSchedule::whereIn('staff_id', $allStaffIds)
            ->where('status', '<>', ManualSchedule::STATUS_CANCELLED)
            ->whereBetween('schedule_date', [$today, $end14])
            ->field('staff_id, schedule_date')
            ->select()
            ->toArray();
        $manualMap = [];
        foreach ($manualRows as $mr) {
            $manualMap[(int)$mr['staff_id']][(string)$mr['schedule_date']] = true;
        }

        // 本月已占用天数
        $occupiedStatuses = [
            Schedule::STATUS_BOOKED,
            Schedule::STATUS_LOCKED,
            Schedule::STATUS_RESERVED,
        ];
        $monthScheduleRows = Schedule::whereIn('staff_id', $allStaffIds)
            ->where('time_slot', Schedule::TIME_SLOT_ALL)
            ->whereBetween('schedule_date', [$monthStart, $monthEnd])
            ->whereIn('status', $occupiedStatuses)
            ->field('staff_id, schedule_date')
            ->select()
            ->toArray();
        $monthManualRows = ManualSchedule::whereIn('staff_id', $allStaffIds)
            ->where('status', '<>', ManualSchedule::STATUS_CANCELLED)
            ->whereBetween('schedule_date', [$monthStart, $monthEnd])
            ->field('staff_id, schedule_date')
            ->select()
            ->toArray();

        $monthDatesByStaff = [];
        foreach ($monthScheduleRows as $r) {
            $monthDatesByStaff[(int)$r['staff_id']][(string)$r['schedule_date']] = true;
        }
        foreach ($monthManualRows as $r) {
            $monthDatesByStaff[(int)$r['staff_id']][(string)$r['schedule_date']] = true;
        }

        // 待确认订单统计
        $pendingConfirmRows = OrderItem::alias('oi')
            ->join('order o', 'o.id = oi.order_id')
            ->whereIn('oi.staff_id', $allStaffIds)
            ->where('o.order_status', Order::STATUS_PENDING_CONFIRM)
            ->where('oi.confirm_status', 0)
            ->where('oi.item_status', '<>', OrderItem::STATUS_CANCELLED)
            ->whereNull('o.delete_time')
            ->field('oi.staff_id, COUNT(DISTINCT oi.order_id) AS cnt')
            ->group('oi.staff_id')
            ->select()
            ->toArray();
        $pendingConfirmMap = [];
        foreach ($pendingConfirmRows as $pr) {
            $pendingConfirmMap[(int)$pr['staff_id']] = (int)$pr['cnt'];
        }

        // 构造单人 14 天热力图方法
        $buildMemberCard = static function (int $sid, int $memberId, int $memberStatus, bool $rowIsLeader) use (
            $staffMap,
            $scheduleMap,
            $manualMap,
            $monthDatesByStaff,
            $pendingConfirmMap,
            $isLeader,
            $today,
            $weekNames
        ): array {
            $st = $staffMap[$sid] ?? null;
            $rawMobile = $st ? trim((string)($st->getData('mobile_full') ?: $st->getData('mobile'))) : '';

            $unavailableStatuses = [
                Schedule::STATUS_BOOKED,
                Schedule::STATUS_LOCKED,
                Schedule::STATUS_RESERVED,
                Schedule::STATUS_UNAVAILABLE,
            ];

            $upcoming14d = [];
            $availableDays = 0;
            for ($i = 0; $i < 14; $i++) {
                $ts = strtotime($today . ' +' . $i . ' days');
                $d = date('Y-m-d', $ts);
                $hasManual = !empty($manualMap[$sid][$d]);
                $schStatus = $scheduleMap[$sid][$d] ?? Schedule::STATUS_AVAILABLE;
                $available = !$hasManual && !in_array($schStatus, $unavailableStatuses, true);
                if ($available) {
                    $availableDays++;
                }
                $upcoming14d[] = [
                    'date' => $d,
                    'md_label' => date('m.d', $ts),
                    'weekday' => '周' . ($weekNames[(int)date('w', $ts)] ?? ''),
                    'available' => $available,
                ];
            }

            return [
                'member_id' => $memberId,
                'staff_id' => $sid,
                'is_leader' => $rowIsLeader,
                'name' => $st ? (string)$st->name : '成员 #' . $sid,
                'avatar' => $st ? (string)$st->avatar : '',
                'category_name' => $st ? ((string)($st->category_name ?? '') ?: '婚礼人') : '婚礼人',
                'rating' => $st ? round((float)$st->rating, 1) : 5.0,
                'experience_years' => $st ? (int)$st->experience_years : 0,
                'mobile' => $isLeader ? $rawMobile : '',
                'mobile_masked' => self::maskMobile($rawMobile),
                'status' => $memberStatus,
                'status_desc' => $rowIsLeader ? '队长' : StaffTeamMember::getStatusDesc($memberStatus),
                'available_days_14d' => $availableDays,
                'month_booked_days' => isset($monthDatesByStaff[$sid]) ? count($monthDatesByStaff[$sid]) : 0,
                'pending_confirm_count' => $pendingConfirmMap[$sid] ?? 0,
                'upcoming_14d' => $upcoming14d,
                'can_toggle' => $isLeader && !$rowIsLeader,
                'can_remove' => $isLeader && !$rowIsLeader,
            ];
        };

        $members = [];
        $activeStaffIds = [$leaderStaffId];
        $members[] = $buildMemberCard($leaderStaffId, 0, StaffTeamMember::STATUS_ENABLED, true);

        $enabledCount = 1;
        foreach ($memberRows as $mr) {
            $sid = (int)$mr->staff_id;
            $st = (int)$mr->status;
            if ($sid <= 0 || $sid === $leaderStaffId) {
                continue;
            }
            if ($st === StaffTeamMember::STATUS_ENABLED) {
                $enabledCount++;
                $activeStaffIds[] = $sid;
            }
            $members[] = $buildMemberCard($sid, (int)$mr->id, $st, false);
        }

        // 团队本月订单与近 7 日排班数
        $monthOrderIds = OrderItem::alias('oi')
            ->join('order o', 'o.id = oi.order_id')
            ->whereIn('oi.staff_id', $activeStaffIds)
            ->whereBetween('oi.service_date', [$monthStart, $monthEnd])
            ->whereNotIn('o.order_status', [
                Order::STATUS_CANCELLED,
                Order::STATUS_REFUNDED,
                Order::STATUS_USER_DELETED,
            ])
            ->where('oi.item_status', '<>', OrderItem::STATUS_CANCELLED)
            ->whereNull('o.delete_time')
            ->distinct(true)
            ->column('oi.order_id');
        $monthOrderCount = count($monthOrderIds);

        $upcoming7dSchedules = 0;
        for ($i = 0; $i < 7; $i++) {
            $d = date('Y-m-d', strtotime($today . ' +' . $i . ' days'));
            foreach ($activeStaffIds as $sid) {
                $schStatus = $scheduleMap[$sid][$d] ?? Schedule::STATUS_AVAILABLE;
                if (!empty($manualMap[$sid][$d]) || in_array($schStatus, [Schedule::STATUS_BOOKED, Schedule::STATUS_LOCKED, Schedule::STATUS_RESERVED], true)) {
                    $upcoming7dSchedules++;
                }
            }
        }

        $totalPendingConfirm = 0;
        foreach ($activeStaffIds as $sid) {
            $totalPendingConfirm += $pendingConfirmMap[$sid] ?? 0;
        }

        $leaderStaff = $staffMap[$leaderStaffId] ?? null;

        return [
            'role' => $role,
            'is_leader' => $isLeader,
            'team' => [
                'id' => (int)$team->id,
                'name' => (string)$team->name,
                'remark' => (string)$team->remark,
                'leader_staff_id' => $leaderStaffId,
                'leader_name' => $leaderStaff ? (string)$leaderStaff->name : '',
            ],
            'summary' => [
                'total_members' => count($members),
                'enabled_members' => $enabledCount,
                'month_order_count' => $monthOrderCount,
                'upcoming_7d_schedules' => $upcoming7dSchedules,
                'pending_confirm_orders' => $totalPendingConfirm,
            ],
            'members' => $members,
        ];
    }

    /**
     * @notes 更新团队名称与简介（仅队长）
     */
    public static function teamUpdateInfo(int $userId, int $staffId, array $params): array|false
    {
        $leaderTeam = StaffTeamService::getLeaderTeam($staffId);
        if (empty($leaderTeam['team_id'])) {
            self::setError('仅团队队长可修改团队信息');
            return false;
        }

        $name = trim((string)($params['name'] ?? ''));
        $remark = trim((string)($params['remark'] ?? ''));
        if ($name === '') {
            self::setError('请输入团队名称');
            return false;
        }

        if (!StaffContentGuardService::textGuard($userId, ['name' => $name, 'remark' => $remark], WechatSecurityService::SCENE_DYNAMIC)) {
            self::setError(StaffContentGuardService::getError() ?: '内容包含敏感词，请修改后重试');
            return false;
        }

        $team = StaffTeam::where('id', (int)$leaderTeam['team_id'])->find();
        if (!$team) {
            self::setError('服务队伍不存在');
            return false;
        }

        $team->name = $name;
        $team->remark = $remark;
        $team->update_time = time();
        $team->save();

        return [
            'id' => (int)$team->id,
            'name' => (string)$team->name,
            'remark' => (string)$team->remark,
        ];
    }

    /**
     * @notes 搜索可加入本队的候选服务人员（仅队长）
     */
    public static function teamMemberCandidates(int $staffId, string $keyword = ''): array|false
    {
        $leaderTeam = StaffTeamService::getLeaderTeam($staffId);
        if (empty($leaderTeam['team_id'])) {
            self::setError('仅团队队长可管理团队成员');
            return false;
        }

        $busyLeaderIds = StaffTeam::where('status', StaffTeam::STATUS_ENABLED)
            ->whereNull('delete_time')
            ->column('leader_staff_id');

        $busyMemberIds = StaffTeamMember::alias('m')
            ->join((new StaffTeam())->getTable() . ' t', 't.id = m.team_id')
            ->where('m.status', StaffTeamMember::STATUS_ENABLED)
            ->where('t.status', StaffTeam::STATUS_ENABLED)
            ->whereNull('m.delete_time')
            ->whereNull('t.delete_time')
            ->column('m.staff_id');

        $excludeIds = array_values(array_unique(array_filter(array_map(
            'intval',
            array_merge([$staffId], $busyLeaderIds, $busyMemberIds)
        ))));

        $query = Staff::where('status', Staff::STATUS_ENABLE)
            ->where('audit_status', Staff::AUDIT_PASS)
            ->whereNull('delete_time')
            ->whereNotIn('id', $excludeIds)
            ->append(['category_name']);

        $keyword = trim($keyword);
        if ($keyword !== '') {
            $query->where('name|sn|mobile', 'like', '%' . $keyword . '%');
        }

        $rows = $query->order('sort', 'desc')
            ->order('id', 'desc')
            ->limit(20)
            ->select();

        $lists = [];
        foreach ($rows as $row) {
            $rawMobile = trim((string)($row->getData('mobile_full') ?: $row->getData('mobile')));
            $lists[] = [
                'staff_id' => (int)$row->id,
                'name' => (string)$row->name,
                'avatar' => (string)$row->avatar,
                'category_name' => (string)($row->category_name ?? '') ?: '婚礼人',
                'rating' => round((float)$row->rating, 1),
                'experience_years' => (int)$row->experience_years,
                'mobile_masked' => self::maskMobile($rawMobile),
            ];
        }

        return ['lists' => $lists];
    }

    /**
     * @notes 队长添加队员到本队
     */
    public static function teamMemberAdd(int $staffId, int $targetStaffId): array|false
    {
        $leaderTeam = StaffTeamService::getLeaderTeam($staffId);
        if (empty($leaderTeam['team_id'])) {
            self::setError('仅团队队长可添加成员');
            return false;
        }
        $teamId = (int)$leaderTeam['team_id'];

        if ($targetStaffId <= 0 || $targetStaffId === $staffId) {
            self::setError('请选择其他有效服务人员');
            return false;
        }

        try {
            $memberId = Db::transaction(function () use ($teamId, $targetStaffId): int {
                $target = Staff::where('id', $targetStaffId)
                    ->where('status', Staff::STATUS_ENABLE)
                    ->where('audit_status', Staff::AUDIT_PASS)
                    ->whereNull('delete_time')
                    ->find();
                if (!$target) {
                    throw new \RuntimeException('目标服务人员不存在或尚未通过审核');
                }

                if (StaffTeamService::isLeader($targetStaffId)) {
                    throw new \RuntimeException('该服务人员已是其他有效团队队长');
                }

                $otherActive = StaffTeamMember::alias('m')
                    ->join((new StaffTeam())->getTable() . ' t', 't.id = m.team_id')
                    ->where('m.staff_id', $targetStaffId)
                    ->where('m.status', StaffTeamMember::STATUS_ENABLED)
                    ->where('t.status', StaffTeam::STATUS_ENABLED)
                    ->whereNull('m.delete_time')
                    ->whereNull('t.delete_time')
                    ->where('m.team_id', '<>', $teamId)
                    ->find();
                if ($otherActive) {
                    throw new \RuntimeException('该服务人员已归属其他有效团队');
                }

                $now = time();
                $sameTeam = StaffTeamMember::where('team_id', $teamId)
                    ->where('staff_id', $targetStaffId)
                    ->find();
                if ($sameTeam) {
                    $sameTeam->status = StaffTeamMember::STATUS_ENABLED;
                    $sameTeam->update_time = $now;
                    $sameTeam->save();
                    return (int)$sameTeam->id;
                }

                $member = new StaffTeamMember();
                $member->team_id = $teamId;
                $member->staff_id = $targetStaffId;
                $member->status = StaffTeamMember::STATUS_ENABLED;
                $member->create_time = $now;
                $member->update_time = $now;
                $member->save();
                return (int)$member->id;
            });

            return ['member_id' => $memberId, 'staff_id' => $targetStaffId];
        } catch (\Throwable $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * @notes 队长启停团队成员状态
     */
    public static function teamMemberToggleStatus(int $staffId, int $memberId, int $status): array|false
    {
        $leaderTeam = StaffTeamService::getLeaderTeam($staffId);
        if (empty($leaderTeam['team_id'])) {
            self::setError('仅团队队长可调整成员状态');
            return false;
        }

        $member = StaffTeamMember::where('id', $memberId)
            ->where('team_id', (int)$leaderTeam['team_id'])
            ->find();
        if (!$member) {
            self::setError('团队成员不存在');
            return false;
        }
        if ((int)$member->staff_id === $staffId) {
            self::setError('不可停用队长自身');
            return false;
        }

        $nextStatus = $status === StaffTeamMember::STATUS_ENABLED
            ? StaffTeamMember::STATUS_ENABLED
            : StaffTeamMember::STATUS_DISABLED;
        $member->status = $nextStatus;
        $member->update_time = time();
        $member->save();

        return ['member_id' => (int)$member->id, 'status' => $nextStatus];
    }

    /**
     * @notes 队长移除团队成员
     */
    public static function teamMemberRemove(int $staffId, int $memberId): bool
    {
        $leaderTeam = StaffTeamService::getLeaderTeam($staffId);
        if (empty($leaderTeam['team_id'])) {
            self::setError('仅团队队长可移除成员');
            return false;
        }

        $member = StaffTeamMember::where('id', $memberId)
            ->where('team_id', (int)$leaderTeam['team_id'])
            ->find();
        if (!$member) {
            self::setError('团队成员不存在');
            return false;
        }
        if ((int)$member->staff_id === $staffId) {
            self::setError('不可移除队长自身');
            return false;
        }

        $member->delete();
        return true;
    }

    /**
     * @notes 获取当前人员所属有效队伍成员数（含队长，用于工作台入口副标题）
     */
    public static function countMyTeamMembers(int $staffId): int
    {
        $leaderTeam = StaffTeamService::getLeaderTeam($staffId);
        $activeTeam = $leaderTeam ?: StaffTeamService::getActiveTeamByStaffId($staffId);
        $teamId = (int)($activeTeam['team_id'] ?? 0);
        if ($teamId <= 0) {
            return 0;
        }
        $memberCount = (int)StaffTeamMember::where('team_id', $teamId)
            ->where('staff_id', '<>', (int)($activeTeam['leader_staff_id'] ?? 0))
            ->whereNull('delete_time')
            ->count();
        return $memberCount + 1;
    }

    private static function maskMobile(string $mobile): string
    {
        $mobile = trim($mobile);
        if ($mobile === '') {
            return '';
        }
        if (preg_match('/^(\d{3})\d{4}(\d{4})$/', $mobile)) {
            return (string)preg_replace('/^(\d{3})\d{4}(\d{4})$/', '$1****$2', $mobile);
        }
        $len = strlen($mobile);
        if ($len >= 7) {
            return substr($mobile, 0, 3) . '****' . substr($mobile, -4);
        }
        return $mobile;
    }
}
