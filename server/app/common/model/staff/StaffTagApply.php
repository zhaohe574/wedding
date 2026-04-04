<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 服务人员标签变更申请模型
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\common\model\staff;

use app\common\model\BaseModel;

class StaffTagApply extends BaseModel
{
    protected $name = 'staff_tag_apply';

    public const STATUS_PENDING = 0;
    public const STATUS_APPROVED = 1;
    public const STATUS_REJECTED = 2;

    public const SOURCE_UNIAPP = 1;
    public const SOURCE_STAFF_ADMIN = 2;

    public function staff()
    {
        return $this->belongsTo(Staff::class, 'staff_id', 'id');
    }

    public static function getStatusDesc(int $status): string
    {
        $map = [
            self::STATUS_PENDING => '待审核',
            self::STATUS_APPROVED => '已通过',
            self::STATUS_REJECTED => '已拒绝',
        ];

        return $map[$status] ?? '未知';
    }

    public static function getSourceDesc(int $source): string
    {
        $map = [
            self::SOURCE_UNIAPP => 'uniapp',
            self::SOURCE_STAFF_ADMIN => '后台自助',
        ];

        return $map[$source] ?? '未知';
    }
}
