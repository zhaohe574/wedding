<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 订单变更业务逻辑
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\adminapi\logic\order;

use app\common\logic\BaseLogic;
use app\common\model\order\Order;
use app\common\model\order\OrderChange;
use app\common\model\order\OrderChangeLog;
use think\facade\Db;

/**
 * 订单变更业务逻辑
 * Class OrderChangeLogic
 * @package app\adminapi\logic\order
 */
class OrderChangeLogic extends BaseLogic
{
    /**
     * @notes 获取变更详情
     * @param int $id
     * @return array|null
     */
    public static function detail(int $id): ?array
    {
        $change = OrderChange::with([
            'order' => function ($query) {
                $query->field('id, order_sn, user_id, order_status, pay_status, total_amount, pay_amount, service_date')
                    ->with(['user' => function ($q) {
                        $q->field('id, nickname, avatar, mobile');
                    }]);
            },
            'user' => function ($query) {
                $query->field('id, nickname, avatar, mobile');
            },
            'orderItem' => function ($query) {
                $query->with(['staff' => function ($q) {
                    $q->field('id, name, avatar');
                }]);
            },
            'oldStaff' => function ($query) {
                $query->field('id, name, avatar, price');
            },
            'newStaff' => function ($query) {
                $query->field('id, name, avatar, price');
            },
            'addStaff' => function ($query) {
                $query->field('id, name, avatar, price');
            },
        ])->find($id);

        if (!$change) {
            return null;
        }

        $data = $change->toArray();
        $data['change_type_desc'] = $change->change_type_desc;
        $data['change_status_desc'] = $change->change_status_desc;

        return $data;
    }

    /**
     * @notes 审核变更申请
     * @param int $changeId
     * @param int $adminId
     * @param bool $approved
     * @param string $remark
     * @param string $rejectReason
     * @return bool
     */
    public static function audit(
        int $changeId,
        int $adminId,
        bool $approved,
        string $remark = '',
        string $rejectReason = ''
    ): bool {
        [$success, $message] = OrderChange::auditChange($changeId, $adminId, $approved, $remark, $rejectReason);
        if (!$success) {
            self::setError($message);
            return false;
        }
        return true;
    }

    /**
     * @notes 执行变更
     * @param int $changeId
     * @param int $adminId
     * @return bool
     */
    public static function execute(int $changeId, int $adminId): bool
    {
        [$success, $message] = OrderChange::executeChange($changeId, $adminId);
        if (!$success) {
            self::setError($message);
            return false;
        }
        return true;
    }

    /**
     * @notes 获取变更操作日志
     * @param int $changeId
     * @return array
     */
    public static function logs(int $changeId): array
    {
        $change = OrderChange::find($changeId);
        if (!$change) {
            return [];
        }

        $logs = OrderChangeLog::where('order_id', $change->order_id)
            ->where('related_type', OrderChangeLog::RELATED_TYPE_CHANGE)
            ->where('related_id', $changeId)
            ->order('create_time', 'desc')
            ->select()
            ->toArray();

        return $logs;
    }

    /**
     * @notes 变更统计
     * @param array $params
     * @return array
     */
    public static function statistics(array $params): array
    {
        $startDate = $params['start_date'] ?? date('Y-m-01');
        $endDate = $params['end_date'] ?? date('Y-m-d');
        
        $startTime = strtotime($startDate);
        $endTime = strtotime($endDate . ' 23:59:59');

        $query = OrderChange::whereBetween('create_time', [$startTime, $endTime]);

        // 总申请数
        $totalChanges = (clone $query)->count();

        // 各类型统计
        $typeCounts = [];
        foreach ([
            OrderChange::TYPE_DATE => '改期',
            OrderChange::TYPE_STAFF => '换人',
            OrderChange::TYPE_ADD_ITEM => '加项',
        ] as $type => $label) {
            $typeCounts[] = [
                'type' => $type,
                'label' => $label,
                'count' => (clone $query)->where('change_type', $type)->count(),
            ];
        }

        // 各状态统计
        $statusCounts = [];
        foreach ([
            OrderChange::STATUS_PENDING => '待审核',
            OrderChange::STATUS_APPROVED => '审核通过',
            OrderChange::STATUS_REJECTED => '审核拒绝',
            OrderChange::STATUS_EXECUTED => '已执行',
            OrderChange::STATUS_CANCELLED => '已取消',
        ] as $status => $label) {
            $statusCounts[] = [
                'status' => $status,
                'label' => $label,
                'count' => (clone $query)->where('change_status', $status)->count(),
            ];
        }

        // 待处理数量（今日）
        $todayStart = strtotime(date('Y-m-d'));
        $pendingToday = OrderChange::where('change_status', OrderChange::STATUS_PENDING)
            ->whereBetween('create_time', [$todayStart, time()])
            ->count();

        // 待执行数量
        $pendingExecute = OrderChange::where('change_status', OrderChange::STATUS_APPROVED)->count();

        // 差价统计（换人类型）
        $priceDiffTotal = OrderChange::where('change_type', OrderChange::TYPE_STAFF)
            ->where('change_status', OrderChange::STATUS_EXECUTED)
            ->whereBetween('create_time', [$startTime, $endTime])
            ->sum('price_diff');

        // 加项金额统计
        $addPriceTotal = OrderChange::where('change_type', OrderChange::TYPE_ADD_ITEM)
            ->where('change_status', OrderChange::STATUS_EXECUTED)
            ->whereBetween('create_time', [$startTime, $endTime])
            ->sum('add_price');

        return [
            'total_changes' => $totalChanges,
            'type_counts' => $typeCounts,
            'status_counts' => $statusCounts,
            'pending_today' => $pendingToday,
            'pending_execute' => $pendingExecute,
            'price_diff_total' => round($priceDiffTotal, 2),
            'add_price_total' => round($addPriceTotal, 2),
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
            [$success, $message] = OrderChange::auditChange($id, $adminId, $approved, $remark, $rejectReason);
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
     * @notes 批量执行
     * @param array $ids
     * @param int $adminId
     * @return array [int $successCount, int $failCount, array $failIds]
     */
    public static function batchExecute(array $ids, int $adminId): array
    {
        $successCount = 0;
        $failCount = 0;
        $failIds = [];

        foreach ($ids as $id) {
            [$success, $message] = OrderChange::executeChange($id, $adminId);
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
     * @notes 获取变更类型选项
     * @return array
     */
    public static function getTypeOptions(): array
    {
        return OrderChange::getTypeOptions();
    }

    /**
     * @notes 获取变更状态选项
     * @return array
     */
    public static function getStatusOptions(): array
    {
        return OrderChange::getStatusOptions();
    }
}
