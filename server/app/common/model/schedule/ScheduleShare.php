<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 档期共享模型
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\common\model\schedule;

use app\common\model\BaseModel;
use app\common\model\staff\Staff;
use think\model\concern\SoftDelete;

/**
 * 档期共享模型
 * Class ScheduleShare
 * @package app\common\model\schedule
 */
class ScheduleShare extends BaseModel
{
    use SoftDelete;

    protected $name = 'schedule_share';
    protected $deleteTime = 'delete_time';

    // 共享类型
    const SHARE_TYPE_SYNC = 1;      // 档期同步
    const SHARE_TYPE_COMBO = 2;     // 组合套餐

    /**
     * @notes 获取工作人员ID数组
     * @param $value
     * @return array
     */
    public function getStaffIdsAttr($value): array
    {
        if (empty($value)) {
            return [];
        }
        return array_map('intval', explode(',', $value));
    }

    /**
     * @notes 设置工作人员ID数组
     * @param $value
     * @return string
     */
    public function setStaffIdsAttr($value): string
    {
        if (is_array($value)) {
            return implode(',', $value);
        }
        return $value;
    }

    /**
     * @notes 共享类型描述获取器
     * @param $value
     * @param $data
     * @return string
     */
    public function getShareTypeDescAttr($value, $data): string
    {
        $map = [
            self::SHARE_TYPE_SYNC => '档期同步',
            self::SHARE_TYPE_COMBO => '组合套餐',
        ];
        return $map[$data['share_type']] ?? '未知';
    }

    /**
     * @notes 获取关联的工作人员列表
     * @return array
     */
    public function getStaffListAttr($value, $data): array
    {
        $staffIds = $this->getStaffIdsAttr($data['staff_ids'] ?? '');
        if (empty($staffIds)) {
            return [];
        }
        return Staff::whereIn('id', $staffIds)
            ->field('id, name, avatar')
            ->select()
            ->toArray();
    }

    /**
     * @notes 检查多个工作人员档期是否都可用
     * @param array $staffIds
     * @param string $date
     * @param int $timeSlot
     * @return array [bool $allAvailable, array $unavailableStaffs]
     */
    public static function checkGroupAvailability(array $staffIds, string $date, int $timeSlot = 0): array
    {
        $unavailable = [];
        foreach ($staffIds as $staffId) {
            if (!Schedule::isAvailable($staffId, $date, $timeSlot)) {
                $staff = Staff::find($staffId);
                $unavailable[] = [
                    'staff_id' => $staffId,
                    'name' => $staff ? $staff->name : '未知',
                ];
            }
        }
        return [empty($unavailable), $unavailable];
    }

    /**
     * @notes 同时锁定多个工作人员档期
     * @param array $staffIds
     * @param string $date
     * @param int $timeSlot
     * @param int $userId
     * @param int $lockDuration
     * @return array [bool $success, string $message, array $lockedSchedules]
     */
    public static function lockGroupSchedule(array $staffIds, string $date, int $timeSlot, int $userId, int $lockDuration = 900): array
    {
        $lockedSchedules = [];
        $failed = false;
        $failMessage = '';

        // 先检查所有档期是否可用
        [$allAvailable, $unavailable] = self::checkGroupAvailability($staffIds, $date, $timeSlot);
        if (!$allAvailable) {
            $names = array_column($unavailable, 'name');
            return [false, '以下工作人员档期不可用：' . implode('、', $names), []];
        }

        // 逐个锁定
        foreach ($staffIds as $staffId) {
            [$success, $message] = Schedule::lockSchedule($staffId, $date, $timeSlot, $userId, Schedule::LOCK_TYPE_NORMAL, $lockDuration);
            if ($success) {
                $lockedSchedules[] = $staffId;
            } else {
                $failed = true;
                $failMessage = $message;
                break;
            }
        }

        // 如果有失败，回滚已锁定的档期
        if ($failed) {
            foreach ($lockedSchedules as $staffId) {
                $schedule = Schedule::where('staff_id', $staffId)
                    ->where('schedule_date', $date)
                    ->where('time_slot', $timeSlot)
                    ->find();
                if ($schedule) {
                    Schedule::releaseLock($schedule->id);
                }
            }
            return [false, $failMessage, []];
        }

        return [true, '组合档期锁定成功', $lockedSchedules];
    }

    /**
     * @notes 计算组合折扣价格
     * @param array $staffIds
     * @param float $totalPrice
     * @return float
     */
    public function calculateComboPrice(array $staffIds, float $totalPrice): float
    {
        if ($this->discount_rate <= 0 || $this->discount_rate >= 100) {
            return $totalPrice;
        }
        return round($totalPrice * $this->discount_rate / 100, 2);
    }

    /**
     * @notes 获取共享类型选项
     * @return array
     */
    public static function getShareTypeOptions(): array
    {
        return [
            ['value' => self::SHARE_TYPE_SYNC, 'label' => '档期同步'],
            ['value' => self::SHARE_TYPE_COMBO, 'label' => '组合套餐'],
        ];
    }

    /**
     * @notes 根据工作人员ID查找所属的共享组
     * @param int $staffId
     * @return array
     */
    public static function findStaffGroups(int $staffId): array
    {
        return self::where('is_enabled', 1)
            ->where('staff_ids', 'like', '%' . $staffId . '%')
            ->select()
            ->filter(function ($item) use ($staffId) {
                return in_array($staffId, $item->staff_ids);
            })
            ->toArray();
    }
}
