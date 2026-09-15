<?php
declare(strict_types=1);

namespace app\common\model\schedule;

use app\common\model\BaseModel;

/** 线下业务档期记录。只保存服务人员自己录入的业务安排。 */
class ManualSchedule extends BaseModel
{
    protected $name = 'manual_schedule';

    public const STATUS_PENDING = 0;
    public const STATUS_COMPLETED = 1;
    public const STATUS_CANCELLED = 2;

    public function toDetail(): array
    {
        $data = $this->toArray();
        $pending = (int)$this->status === self::STATUS_PENDING;
        $data['manual_schedule_id'] = (int)$this->id;
        $data['source'] = 'manual';
        $data['status'] = (int)$this->status;
        $data['version'] = (int)$this->version;
        $data['status_desc'] = $this->status_desc;
        $data['can_edit'] = (int)($pending && (string)$this->schedule_date >= date('Y-m-d'));
        $data['can_cancel'] = (int)$pending;
        $data['can_complete'] = (int)($pending && (string)$this->schedule_date <= date('Y-m-d'));
        return $data;
    }

    public function getStatusDescAttr($value, $data): string
    {
        return [self::STATUS_PENDING => '待履约', self::STATUS_COMPLETED => '已完成', self::STATUS_CANCELLED => '已取消'][(int)($data['status'] ?? 0)] ?? '未知';
    }
}
