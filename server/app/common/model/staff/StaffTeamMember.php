<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 服务队伍成员模型
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\common\model\staff;

use app\common\model\BaseModel;
use think\model\concern\SoftDelete;

/**
 * 服务队伍成员模型
 */
class StaffTeamMember extends BaseModel
{
    use SoftDelete;

    protected $name = 'staff_team_member';
    protected $deleteTime = 'delete_time';

    public const STATUS_DISABLED = 0;
    public const STATUS_ENABLED = 1;

    /**
     * @notes 所属队伍
     */
    public function team()
    {
        return $this->belongsTo(StaffTeam::class, 'team_id', 'id');
    }

    /**
     * @notes 服务人员
     */
    public function staff()
    {
        return $this->belongsTo(Staff::class, 'staff_id', 'id')
            ->field('id, sn, name, avatar, mobile, category_id, status, audit_status');
    }
}
