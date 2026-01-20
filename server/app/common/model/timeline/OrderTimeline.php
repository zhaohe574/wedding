<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 订单时间轴任务模型
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\common\model\timeline;

use app\common\model\BaseModel;
use app\common\model\order\Order;
use app\common\model\user\User;
use think\model\concern\SoftDelete;

/**
 * 订单时间轴任务模型
 * Class OrderTimeline
 * @package app\common\model\timeline
 */
class OrderTimeline extends BaseModel
{
    use SoftDelete;

    protected $name = 'order_timeline';
    protected $deleteTime = 'delete_time';

    // 任务类型
    const TYPE_PREPARE = 1;     // 准备物料
    const TYPE_CONFIRM = 2;     // 确认事项
    const TYPE_CONTACT = 3;     // 沟通联系
    const TYPE_ONSITE = 4;      // 现场安排
    const TYPE_OTHER = 5;       // 其他

    /**
     * @notes 任务类型选项
     * @return array
     */
    public static function getTaskTypeOptions(): array
    {
        return [
            self::TYPE_PREPARE => '准备物料',
            self::TYPE_CONFIRM => '确认事项',
            self::TYPE_CONTACT => '沟通联系',
            self::TYPE_ONSITE => '现场安排',
            self::TYPE_OTHER => '其他',
        ];
    }

    /**
     * @notes 关联订单
     * @return \think\model\relation\BelongsTo
     */
    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id', 'id');
    }

    /**
     * @notes 关联用户
     * @return \think\model\relation\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    /**
     * @notes 关联完成人
     * @return \think\model\relation\BelongsTo
     */
    public function completeUser()
    {
        return $this->belongsTo(User::class, 'complete_user_id', 'id');
    }

    /**
     * @notes 关联模板
     * @return \think\model\relation\BelongsTo
     */
    public function template()
    {
        return $this->belongsTo(TimelineTemplate::class, 'template_id', 'id');
    }

    /**
     * @notes 任务类型描述获取器
     * @param $value
     * @param $data
     * @return string
     */
    public function getTaskTypeDescAttr($value, $data): string
    {
        $options = self::getTaskTypeOptions();
        return $options[$data['task_type']] ?? '未知';
    }

    /**
     * @notes 完成状态描述获取器
     * @param $value
     * @param $data
     * @return string
     */
    public function getIsCompletedDescAttr($value, $data): string
    {
        return $data['is_completed'] ? '已完成' : '未完成';
    }

    /**
     * @notes 计算距离婚期天数
     * @param $value
     * @param $data
     * @return int
     */
    public function getDaysToWeddingAttr($value, $data): int
    {
        if (empty($data['wedding_date'])) {
            return 0;
        }
        $weddingTime = strtotime($data['wedding_date']);
        $now = strtotime(date('Y-m-d'));
        return (int)ceil(($weddingTime - $now) / 86400);
    }

    /**
     * @notes 任务状态获取器
     * @param $value
     * @param $data
     * @return string
     */
    public function getStatusAttr($value, $data): string
    {
        if (!empty($data['is_completed'])) {
            return 'completed';
        }
        
        $triggerDate = strtotime($data['trigger_date']);
        $today = strtotime(date('Y-m-d'));
        
        if ($triggerDate < $today) {
            return 'overdue';  // 已过期未完成
        } elseif ($triggerDate == $today) {
            return 'today';    // 今日任务
        } else {
            return 'pending';  // 待完成
        }
    }

    /**
     * @notes 获取订单的时间轴任务列表
     * @param int $orderId
     * @return array
     */
    public static function getOrderTimeline(int $orderId): array
    {
        return self::where('order_id', $orderId)
            ->order('trigger_date asc, sort desc, id asc')
            ->select()
            ->toArray();
    }

    /**
     * @notes 获取用户的所有时间轴任务
     * @param int $userId
     * @param bool $onlyPending 仅未完成
     * @return array
     */
    public static function getUserTimeline(int $userId, bool $onlyPending = false): array
    {
        $query = self::where('user_id', $userId);
        
        if ($onlyPending) {
            $query->where('is_completed', 0);
        }
        
        return $query->order('trigger_date asc, sort desc, id asc')
            ->select()
            ->toArray();
    }

    /**
     * @notes 获取今日待完成任务
     * @param int $userId
     * @return array
     */
    public static function getTodayTasks(int $userId): array
    {
        $today = date('Y-m-d');
        
        return self::where('user_id', $userId)
            ->where('trigger_date', '<=', $today)
            ->where('is_completed', 0)
            ->order('trigger_date asc, sort desc')
            ->select()
            ->toArray();
    }

    /**
     * @notes 获取需要提醒的任务
     * @param string $date 日期
     * @return array
     */
    public static function getTasksToRemind(string $date): array
    {
        return self::where('trigger_date', $date)
            ->where('is_completed', 0)
            ->where('is_reminded', 0)
            ->select()
            ->toArray();
    }

    /**
     * @notes 标记任务完成
     * @param int $taskId
     * @param int $userId
     * @param string $remark
     * @return bool
     */
    public static function completeTask(int $taskId, int $userId, string $remark = ''): bool
    {
        return self::where('id', $taskId)->update([
            'is_completed' => 1,
            'complete_time' => time(),
            'complete_user_id' => $userId,
            'complete_remark' => $remark,
            'update_time' => time(),
        ]) !== false;
    }

    /**
     * @notes 取消任务完成状态
     * @param int $taskId
     * @return bool
     */
    public static function uncompleteTask(int $taskId): bool
    {
        return self::where('id', $taskId)->update([
            'is_completed' => 0,
            'complete_time' => 0,
            'complete_user_id' => 0,
            'complete_remark' => '',
            'update_time' => time(),
        ]) !== false;
    }

    /**
     * @notes 标记已提醒
     * @param int $taskId
     * @return bool
     */
    public static function markReminded(int $taskId): bool
    {
        return self::where('id', $taskId)->update([
            'is_reminded' => 1,
            'remind_time' => time(),
            'update_time' => time(),
        ]) !== false;
    }

    /**
     * @notes 获取时间轴统计
     * @param int $orderId
     * @return array
     */
    public static function getTimelineStats(int $orderId): array
    {
        $total = self::where('order_id', $orderId)->count();
        $completed = self::where('order_id', $orderId)->where('is_completed', 1)->count();
        $today = date('Y-m-d');
        $overdue = self::where('order_id', $orderId)
            ->where('is_completed', 0)
            ->where('trigger_date', '<', $today)
            ->count();
        
        return [
            'total' => $total,
            'completed' => $completed,
            'pending' => $total - $completed,
            'overdue' => $overdue,
            'progress' => $total > 0 ? round($completed / $total * 100, 1) : 0,
        ];
    }
}
