<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 订单暂停业务逻辑
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\adminapi\logic\order;

use app\common\logic\BaseLogic;
use app\common\model\order\Order;
use app\common\model\order\OrderPause;
use app\common\model\order\OrderChangeLog;
use think\facade\Db;

/**
 * 订单暂停业务逻辑
 * Class OrderPauseLogic
 * @package app\adminapi\logic\order
 */
class OrderPauseLogic extends BaseLogic
{
    /**
     * @notes 获取暂停详情
     * @param int $id
     * @return array|null
     */
    public static function detail(int $id): ?array
    {
        $pause = OrderPause::with([
            'order' => function ($query) {
                $query->field('id, order_sn, user_id, order_status, pay_status, total_amount, pay_amount, service_date');
            },
            'user' => function ($query) {
                $query->field('id, nickname, avatar, mobile');
            },
        ])->find($id);

        if (!$pause) {
            return null;
        }

        $data = $pause->toArray();
        $data['pause_status_desc'] = $pause->pause_status_desc;
        $data['pause_type_desc'] = $pause->pause_type_desc;

        return $data;
    }

    /**
     * @notes 审核暂停申请
     * @param int $pauseId
     * @param int $adminId
     * @param bool $approved
     * @param string $remark
     * @param string $rejectReason
     * @return bool
     */
    public static function audit(
        int $pauseId,
        int $adminId,
        bool $approved,
        string $remark = '',
        string $rejectReason = ''
    ): bool {
        [$success, $message] = OrderPause::auditPause($pauseId, $adminId, $approved, $remark, $rejectReason);
        if (!$success) {
            self::setError($message);
            return false;
        }
        return true;
    }

    /**
     * @notes 恢复订单
     * @param int $pauseId
     * @param int $adminId
     * @param string $newServiceDate
     * @param string $remark
     * @return bool
     */
    public static function resume(
        int $pauseId,
        int $adminId,
        string $newServiceDate = '',
        string $remark = ''
    ): bool {
        [$success, $message] = OrderPause::resumeOrder($pauseId, $adminId, $newServiceDate, $remark);
        if (!$success) {
            self::setError($message);
            return false;
        }
        return true;
    }

    /**
     * @notes 获取暂停操作日志
     * @param int $pauseId
     * @return array
     */
    public static function logs(int $pauseId): array
    {
        $pause = OrderPause::find($pauseId);
        if (!$pause) {
            return [];
        }

        $logs = OrderChangeLog::where('order_id', $pause->order_id)
            ->where('related_type', OrderChangeLog::RELATED_TYPE_PAUSE)
            ->where('related_id', $pauseId)
            ->order('create_time', 'desc')
            ->select()
            ->toArray();

        return $logs;
    }

    /**
     * @notes 获取即将到期的暂停
     * @param int $days
     * @return array
     */
    public static function expiring(int $days = 3): array
    {
        return OrderPause::getExpiringPauses($days);
    }

    /**
     * @notes 发送到期提醒
     * @param int $pauseId
     * @return bool
     */
    public static function sendReminder(int $pauseId): bool
    {
        $pause = OrderPause::find($pauseId);
        if (!$pause) {
            self::setError('暂停记录不存在');
            return false;
        }

        if ($pause->pause_status != OrderPause::STATUS_PAUSED) {
            self::setError('订单未处于暂停状态');
            return false;
        }

        // TODO: 发送提醒通知
        // NoticeService::send($pause->user_id, 'pause_expiring', [...]);

        // 标记已提醒
        OrderPause::markReminded($pauseId);

        return true;
    }

    /**
     * @notes 暂停统计
     * @param array $params
     * @return array
     */
    public static function statistics(array $params): array
    {
        $startDate = $params['start_date'] ?? date('Y-m-01');
        $endDate = $params['end_date'] ?? date('Y-m-d');
        
        $startTime = strtotime($startDate);
        $endTime = strtotime($endDate . ' 23:59:59');

        $query = OrderPause::whereBetween('create_time', [$startTime, $endTime]);

        // 总申请数
        $totalPauses = (clone $query)->count();

        // 各类型统计
        $typeCounts = [];
        foreach ([
            OrderPause::TYPE_EPIDEMIC => '疫情',
            OrderPause::TYPE_EMERGENCY => '突发事件',
            OrderPause::TYPE_PERSONAL => '个人原因',
            OrderPause::TYPE_OTHER => '其他',
        ] as $type => $label) {
            $typeCounts[] = [
                'type' => $type,
                'label' => $label,
                'count' => (clone $query)->where('pause_type', $type)->count(),
            ];
        }

        // 各状态统计
        $statusCounts = [];
        foreach ([
            OrderPause::STATUS_PENDING => '待审核',
            OrderPause::STATUS_PAUSED => '暂停中',
            OrderPause::STATUS_RESUMED => '已恢复',
            OrderPause::STATUS_REJECTED => '已拒绝',
            OrderPause::STATUS_CANCELLED => '已取消',
        ] as $status => $label) {
            $statusCounts[] = [
                'status' => $status,
                'label' => $label,
                'count' => (clone $query)->where('pause_status', $status)->count(),
            ];
        }

        // 待处理数量
        $pendingCount = OrderPause::where('pause_status', OrderPause::STATUS_PENDING)->count();

        // 当前暂停中数量
        $pausingCount = OrderPause::where('pause_status', OrderPause::STATUS_PAUSED)->count();

        // 即将到期数量（3天内）
        $expiringCount = count(OrderPause::getExpiringPauses(3));

        // 平均暂停天数
        $avgDays = (clone $query)
            ->where('pause_status', OrderPause::STATUS_RESUMED)
            ->avg('actual_pause_days') ?: 0;

        // 今日数据
        $todayStart = strtotime(date('Y-m-d'));
        $todayPauses = OrderPause::whereBetween('create_time', [$todayStart, time()])->count();
        $todayResumed = OrderPause::where('pause_status', OrderPause::STATUS_RESUMED)
            ->whereBetween('resume_time', [$todayStart, time()])
            ->count();

        return [
            'total_pauses' => $totalPauses,
            'type_counts' => $typeCounts,
            'status_counts' => $statusCounts,
            'pending_count' => $pendingCount,
            'pausing_count' => $pausingCount,
            'expiring_count' => $expiringCount,
            'avg_pause_days' => round($avgDays, 1),
            'today' => [
                'pauses' => $todayPauses,
                'resumed' => $todayResumed,
            ],
        ];
    }

    /**
     * @notes 批量审核
     * @param array $ids
     * @param int $adminId
     * @param bool $approved
     * @param string $remark
     * @param string $rejectReason
     * @return array [int $successCount, int $failCount, array $failIds]
     */
    public static function batchAudit(
        array $ids,
        int $adminId,
        bool $approved,
        string $remark = '',
        string $rejectReason = ''
    ): array {
        $successCount = 0;
        $failCount = 0;
        $failIds = [];

        foreach ($ids as $id) {
            [$success, $message] = OrderPause::auditPause($id, $adminId, $approved, $remark, $rejectReason);
            if ($success) {
                $successCount++;
            } else {
                $failCount++;
                $failIds[] = ['id' => $id, 'reason' => $message];
            }
        }

        return [$successCount, $failCount, $failIds];
    }

    /**
     * @notes 延长暂停时间
     * @param int $pauseId
     * @param int $adminId
     * @param string $newEndDate
     * @param string $remark
     * @return bool
     */
    public static function extend(
        int $pauseId,
        int $adminId,
        string $newEndDate,
        string $remark = ''
    ): bool {
        $pause = OrderPause::find($pauseId);
        if (!$pause) {
            self::setError('暂停记录不存在');
            return false;
        }

        if ($pause->pause_status != OrderPause::STATUS_PAUSED) {
            self::setError('订单未处于暂停状态');
            return false;
        }

        if (strtotime($newEndDate) <= strtotime($pause->pause_end_date)) {
            self::setError('新结束日期必须大于原结束日期');
            return false;
        }

        Db::startTrans();
        try {
            $oldEndDate = $pause->pause_end_date;
            $newDays = (strtotime($newEndDate) - strtotime($pause->pause_start_date)) / 86400 + 1;

            $pause->pause_end_date = $newEndDate;
            $pause->pause_days = $newDays;
            $pause->reminded = 0; // 重置提醒状态
            $pause->update_time = time();
            $pause->save();

            // 记录日志
            OrderChangeLog::addLog(
                $pause->order_id,
                OrderChangeLog::RELATED_TYPE_PAUSE,
                $pauseId,
                OrderChangeLog::OPERATOR_ADMIN,
                $adminId,
                'extend',
                OrderPause::STATUS_PAUSED,
                OrderPause::STATUS_PAUSED,
                "延长暂停时间：{$oldEndDate} → {$newEndDate}" . ($remark ? "，备注：{$remark}" : '')
            );

            Db::commit();
            return true;
        } catch (\Exception $e) {
            Db::rollback();
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * @notes 获取暂停类型选项
     * @return array
     */
    public static function getTypeOptions(): array
    {
        return OrderPause::getTypeOptions();
    }

    /**
     * @notes 获取暂停状态选项
     * @return array
     */
    public static function getStatusOptions(): array
    {
        return OrderPause::getStatusOptions();
    }
}
