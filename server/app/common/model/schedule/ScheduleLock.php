<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 档期锁定记录模型
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\common\model\schedule;

use app\common\model\BaseModel;
use app\common\model\staff\Staff;

/**
 * 档期锁定记录模型
 * Class ScheduleLock
 * @package app\common\model\schedule
 */
class ScheduleLock extends BaseModel
{
    protected $name = 'schedule_lock';

    // 锁定类型
    const LOCK_TYPE_VIP = 1;        // VIP锁定
    const LOCK_TYPE_INTERNAL = 2;   // 内部预留
    const LOCK_TYPE_TEMP = 3;       // 临时锁定（下单过程中）

    // 状态
    const STATUS_RELEASED = 0;      // 已释放
    const STATUS_LOCKED = 1;        // 锁定中
    const STATUS_CONVERTED = 2;     // 已转订单

    /**
     * @notes 关联档期
     * @return \think\model\relation\BelongsTo
     */
    public function schedule()
    {
        return $this->belongsTo(Schedule::class, 'schedule_id', 'id');
    }

    /**
     * @notes 关联工作人员
     * @return \think\model\relation\BelongsTo
     */
    public function staff()
    {
        return $this->belongsTo(Staff::class, 'staff_id', 'id');
    }

    /**
     * @notes 锁定类型描述获取器
     * @param $value
     * @param $data
     * @return string
     */
    public function getLockTypeDescAttr($value, $data)
    {
        $map = [
            self::LOCK_TYPE_VIP => 'VIP锁定',
            self::LOCK_TYPE_INTERNAL => '内部预留',
            self::LOCK_TYPE_TEMP => '临时锁定',
        ];
        return $map[$data['lock_type']] ?? '未知';
    }

    /**
     * @notes 状态描述获取器
     * @param $value
     * @param $data
     * @return string
     */
    public function getStatusDescAttr($value, $data)
    {
        $map = [
            self::STATUS_RELEASED => '已释放',
            self::STATUS_LOCKED => '锁定中',
            self::STATUS_CONVERTED => '已转订单',
        ];
        return $map[$data['status']] ?? '未知';
    }

    /**
     * @notes 创建锁定记录
     * @param int $scheduleId
     * @param int $staffId
     * @param int $userId
     * @param int $lockType
     * @param int $duration 锁定时长（秒）
     * @param string $reason
     * @param int $adminId
     * @return ScheduleLock
     */
    public static function createLock(int $scheduleId, int $staffId, int $userId, int $lockType, int $duration, string $reason = '', int $adminId = 0): ScheduleLock
    {
        return self::create([
            'schedule_id' => $scheduleId,
            'staff_id' => $staffId,
            'user_id' => $userId,
            'lock_type' => $lockType,
            'lock_start_time' => time(),
            'lock_end_time' => time() + $duration,
            'lock_reason' => $reason,
            'status' => self::STATUS_LOCKED,
            'admin_id' => $adminId,
            'create_time' => time(),
            'update_time' => time(),
        ]);
    }

    /**
     * @notes 释放锁定
     * @param int $lockId
     * @param string $reason
     * @return bool
     */
    public static function releaseLock(int $lockId, string $reason = ''): bool
    {
        $lock = self::find($lockId);
        if (!$lock || $lock->status != self::STATUS_LOCKED) {
            return false;
        }

        $lock->save([
            'status' => self::STATUS_RELEASED,
            'release_time' => time(),
            'release_reason' => $reason,
            'update_time' => time(),
        ]);

        // 同时释放档期锁定
        Schedule::releaseLock($lock->schedule_id);

        return true;
    }

    /**
     * @notes 自动释放过期锁定
     * @return int 释放的数量
     */
    public static function releaseExpiredLocks(): int
    {
        $expiredLocks = self::where('status', self::STATUS_LOCKED)
            ->where('lock_end_time', '<', time())
            ->select();

        $count = 0;
        foreach ($expiredLocks as $lock) {
            if (self::releaseLock($lock->id, '锁定已过期，自动释放')) {
                $count++;
            }
        }

        return $count;
    }
}
