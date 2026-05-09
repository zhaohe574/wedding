<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 服务队伍模型
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\common\model\staff;

use app\common\model\BaseModel;
use think\model\concern\SoftDelete;

/**
 * 服务队伍模型
 */
class StaffTeam extends BaseModel
{
    use SoftDelete;

    protected $name = 'staff_team';
    protected $deleteTime = 'delete_time';

    public const STATUS_DISABLED = 0;
    public const STATUS_ENABLED = 1;

    /**
     * @notes 队长
     */
    public function leader()
    {
        return $this->belongsTo(Staff::class, 'leader_staff_id', 'id')
            ->field('id, sn, name, avatar, mobile');
    }

    /**
     * @notes 队员
     */
    public function members()
    {
        return $this->hasMany(StaffTeamMember::class, 'team_id', 'id');
    }

    /**
     * @notes 状态描述
     */
    public static function getStatusDesc(int $value): string
    {
        return [
            self::STATUS_DISABLED => '禁用',
            self::STATUS_ENABLED => '启用',
        ][$value] ?? '未知';
    }
}
