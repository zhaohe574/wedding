<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 服务人员客户跟进提醒模型
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\common\model\staff;

use app\common\model\BaseModel;
use think\model\concern\SoftDelete;

/**
 * 服务人员客户跟进提醒模型
 * Class StaffFollowupReminder
 * @package app\common\model\staff
 */
class StaffFollowupReminder extends BaseModel
{
    use SoftDelete;

    protected $name = 'staff_followup_reminder';
    protected $deleteTime = 'delete_time';

    public const SOURCE_ORDER = 'order';
    public const SOURCE_MANUAL = 'manual';
    public const SOURCE_GENERAL = 'general';

    public const STATUS_PENDING = 0;
    public const STATUS_DONE = 1;
    public const STATUS_IGNORED = 2;

    public const TYPE_PRE_COMM = 'pre_comm';
    public const TYPE_QUESTIONNAIRE = 'questionnaire';
    public const TYPE_REHEARSAL = 'rehearsal';
    public const TYPE_BALANCE = 'balance';
    public const TYPE_AFTERCARE = 'aftercare';
    public const TYPE_OTHER = 'other';

    public const REMIND_TYPES = [
        self::TYPE_PRE_COMM => '婚前沟通',
        self::TYPE_QUESTIONNAIRE => '催填问卷',
        self::TYPE_REHEARSAL => '预约彩排',
        self::TYPE_BALANCE => '尾款确认',
        self::TYPE_AFTERCARE => '婚后回访',
        self::TYPE_OTHER => '其他提醒',
    ];

    /**
     * @notes 获取提醒类型选项
     */
    public static function getRemindTypeOptions(): array
    {
        $options = [];
        foreach (self::REMIND_TYPES as $value => $label) {
            $options[] = [
                'value' => $value,
                'label' => $label,
            ];
        }
        return $options;
    }

    /**
     * @notes 获取状态文案
     */
    public static function getStatusDesc(int $status): string
    {
        return [
            self::STATUS_PENDING => '待跟进',
            self::STATUS_DONE => '已完成',
            self::STATUS_IGNORED => '已忽略',
        ][$status] ?? '未知';
    }
}
