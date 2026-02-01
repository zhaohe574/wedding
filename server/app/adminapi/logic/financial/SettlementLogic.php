<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 结算管理逻辑层
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\adminapi\logic\financial;

use app\common\logic\BaseLogic;
use app\common\model\financial\StaffSettlement;
use app\common\model\financial\SettlementBatch;
use app\common\model\financial\StaffSettlementConfig;
use app\common\model\financial\CostRecord;
use app\common\model\order\Order;
use app\common\model\order\OrderItem;
use think\facade\Db;

/**
 * 结算管理逻辑层
 * Class SettlementLogic
 * @package app\adminapi\logic\financial
 */
class SettlementLogic extends BaseLogic
{
    /**
     * @notes 结算详情
     */
    public static function detail(int $id): array
    {
        $settlement = StaffSettlement::with(['staff', 'order', 'orderItem', 'batch'])
            ->find($id);
        
        if (!$settlement) {
            return [];
        }
        
        $data = $settlement->toArray();
        $data['status_text'] = StaffSettlement::getStatusDesc($settlement->status);
        $data['type_text'] = StaffSettlement::getTypeDesc($settlement->settlement_type);
        $data['settle_way_text'] = StaffSettlement::getSettleWayDesc($settlement->settle_way);
        
        return $data;
    }

    /**
     * @notes 执行单笔结算
     */
    public static function settle(int $id): bool
    {
        try {
            $settlement = StaffSettlement::find($id);
            if (!$settlement) {
                self::setError('结算记录不存在');
                return false;
            }
            
            if ($settlement->status !== StaffSettlement::STATUS_PENDING) {
                self::setError('结算状态不正确');
                return false;
            }
            
            return $settlement->settle();
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * @notes 批量结算
     */
    public static function batchSettle(array $ids): array|bool
    {
        try {
            $successCount = 0;
            $failCount = 0;
            
            foreach ($ids as $id) {
                $settlement = StaffSettlement::find($id);
                if ($settlement && $settlement->status === StaffSettlement::STATUS_PENDING) {
                    if ($settlement->settle()) {
                        $successCount++;
                    } else {
                        $failCount++;
                    }
                } else {
                    $failCount++;
                }
            }
            
            return [
                'success_count' => $successCount,
                'fail_count' => $failCount,
            ];
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * @notes 取消结算
     */
    public static function cancel(int $id): bool
    {
        try {
            $settlement = StaffSettlement::find($id);
            if (!$settlement) {
                self::setError('结算记录不存在');
                return false;
            }
            
            return $settlement->cancel();
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * @notes 结算统计
     */
    public static function statistics(array $params): array
    {
        $startDate = $params['start_date'] ?? date('Y-m-01');
        $endDate = $params['end_date'] ?? date('Y-m-d');
        
        $query = StaffSettlement::whereBetween('service_date', [$startDate, $endDate]);
        
        $totalPending = (clone $query)->where('status', StaffSettlement::STATUS_PENDING)->sum('actual_amount');
        $totalSettled = (clone $query)->where('status', StaffSettlement::STATUS_SETTLED)->sum('actual_amount');
        $pendingCount = (clone $query)->where('status', StaffSettlement::STATUS_PENDING)->count();
        $settledCount = (clone $query)->where('status', StaffSettlement::STATUS_SETTLED)->count();
        
        return [
            'pending_amount' => round($totalPending, 2),
            'settled_amount' => round($totalSettled, 2),
            'pending_count' => $pendingCount,
            'settled_count' => $settledCount,
            'total_amount' => round($totalPending + $totalSettled, 2),
            'total_count' => $pendingCount + $settledCount,
        ];
    }

    /**
     * @notes 人员结算汇总
     */
    public static function staffSummary(array $params): array
    {
        $startDate = $params['start_date'] ?? date('Y-m-01');
        $endDate = $params['end_date'] ?? date('Y-m-d');
        
        $list = StaffSettlement::alias('s')
            ->leftJoin('la_staff st', 's.staff_id = st.id')
            ->whereBetween('s.service_date', [$startDate, $endDate])
            ->group('s.staff_id')
            ->field([
                's.staff_id',
                'st.name as staff_name',
                'st.avatar as staff_avatar',
                'COUNT(*) as total_count',
                'SUM(s.order_amount) as total_order_amount',
                'SUM(s.actual_amount) as total_settlement_amount',
                'SUM(CASE WHEN s.status = ' . StaffSettlement::STATUS_PENDING . ' THEN s.actual_amount ELSE 0 END) as pending_amount',
                'SUM(CASE WHEN s.status = ' . StaffSettlement::STATUS_SETTLED . ' THEN s.actual_amount ELSE 0 END) as settled_amount',
            ])
            ->order('total_settlement_amount', 'desc')
            ->select()
            ->toArray();
        
        return $list;
    }

    /**
     * @notes 创建结算批次
     */
    public static function createBatch(array $params): array|bool
    {
        Db::startTrans();
        try {
            $startDate = $params['settle_start_date'];
            $endDate = $params['settle_end_date'];
            
            // 查找待结算记录
            $pendingSettlements = StaffSettlement::where('status', StaffSettlement::STATUS_PENDING)
                ->whereBetween('service_date', [$startDate, $endDate])
                ->select();
            
            if ($pendingSettlements->isEmpty()) {
                self::setError('没有找到待结算的记录');
                return false;
            }
            
            $totalAmount = $pendingSettlements->sum('actual_amount');
            
            // 创建批次
            $batch = SettlementBatch::createBatch([
                'batch_name' => $params['batch_name'] ?? '结算批次-' . date('Y-m-d'),
                'settle_start_date' => $startDate,
                'settle_end_date' => $endDate,
                'total_count' => count($pendingSettlements),
                'total_amount' => $totalAmount,
                'remark' => $params['remark'] ?? '',
            ]);
            
            // 关联结算记录到批次
            StaffSettlement::where('status', StaffSettlement::STATUS_PENDING)
                ->whereBetween('service_date', [$startDate, $endDate])
                ->update(['batch_id' => $batch->id]);
            
            Db::commit();
            return [
                'batch_id' => $batch->id,
                'batch_sn' => $batch->batch_sn,
                'total_count' => count($pendingSettlements),
                'total_amount' => round($totalAmount, 2),
            ];
        } catch (\Exception $e) {
            Db::rollback();
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * @notes 审核批次
     */
    public static function auditBatch(array $params, int $adminId): bool
    {
        try {
            $batch = SettlementBatch::find($params['batch_id']);
            if (!$batch) {
                self::setError('批次不存在');
                return false;
            }
            
            if ($params['status'] == 1) {
                return $batch->approve($adminId, $params['remark'] ?? '');
            } else {
                return $batch->cancel();
            }
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * @notes 执行批次结算
     */
    public static function executeBatch(array $params, int $adminId): array|bool
    {
        try {
            $batch = SettlementBatch::find($params['batch_id']);
            if (!$batch) {
                self::setError('批次不存在');
                return false;
            }
            
            if (!$batch->startExecute($adminId)) {
                self::setError('批次状态不正确');
                return false;
            }
            
            return $batch->execute();
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * @notes 取消批次
     */
    public static function cancelBatch(array $params): bool
    {
        try {
            $batch = SettlementBatch::find($params['batch_id']);
            if (!$batch) {
                self::setError('批次不存在');
                return false;
            }
            
            return $batch->cancel();
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * @notes 结算配置列表
     */
    public static function configLists(): array
    {
        $list = StaffSettlementConfig::with(['staff', 'category'])
            ->order('is_default', 'desc')
            ->order('id', 'asc')
            ->select()
            ->toArray();
        
        foreach ($list as &$item) {
            $item['cycle_text'] = StaffSettlementConfig::getCycleDesc($item['settle_cycle']);
            $item['status_text'] = StaffSettlementConfig::getStatusDesc($item['status']);
        }
        
        return $list;
    }

    /**
     * @notes 添加结算配置
     */
    public static function addConfig(array $params): bool
    {
        try {
            StaffSettlementConfig::createConfig($params);
            return true;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * @notes 编辑结算配置
     */
    public static function editConfig(array $params): bool
    {
        try {
            $config = StaffSettlementConfig::find($params['id']);
            if (!$config) {
                self::setError('配置不存在');
                return false;
            }
            
            $config->staff_id = $params['staff_id'] ?? $config->staff_id;
            $config->category_id = $params['category_id'] ?? $config->category_id;
            $config->settlement_rate = $params['settlement_rate'] ?? $config->settlement_rate;
            $config->min_amount = $params['min_amount'] ?? $config->min_amount;
            $config->settle_cycle = $params['settle_cycle'] ?? $config->settle_cycle;
            $config->settle_delay_days = $params['settle_delay_days'] ?? $config->settle_delay_days;
            $config->status = $params['status'] ?? $config->status;
            $config->remark = $params['remark'] ?? $config->remark;
            
            if (!empty($params['is_default']) && $params['is_default'] == 1) {
                $config->setAsDefault();
            }
            
            return $config->save();
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * @notes 删除结算配置
     */
    public static function deleteConfig(int $id): bool
    {
        try {
            $config = StaffSettlementConfig::find($id);
            if (!$config) {
                self::setError('配置不存在');
                return false;
            }
            
            if ($config->is_default) {
                self::setError('默认配置不能删除');
                return false;
            }
            
            return $config->delete();
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * @notes 生成结算记录(从已完成订单)
     */
    public static function generateFromOrders(string $startDate, string $endDate): int
    {
        $count = 0;
        
        // 查找已完成且未生成结算的订单项
        $orderItems = OrderItem::alias('oi')
            ->leftJoin('la_order o', 'oi.order_id = o.id')
            ->leftJoin('la_staff_settlement ss', 'ss.order_item_id = oi.id')
            ->where('o.order_status', Order::STATUS_COMPLETED)
            ->whereBetween('oi.service_date', [$startDate, $endDate])
            ->whereNull('ss.id')
            ->field('oi.*')
            ->select();
        
        foreach ($orderItems as $item) {
            // 计算结算金额
            $calcResult = StaffSettlementConfig::calculateSettlement(
                (float) $item->subtotal,
                $item->staff_id,
                0 // TODO: 获取分类ID
            );
            
            // 获取成本
            $cost = CostRecord::getOrderTotalCost($item->order_id);
            
            // 创建结算记录
            StaffSettlement::createSettlement([
                'staff_id' => $item->staff_id,
                'order_id' => $item->order_id,
                'order_item_id' => $item->id,
                'service_date' => $item->service_date,
                'order_amount' => $item->subtotal,
                'settlement_rate' => $calcResult['settlement_rate'],
                'settlement_amount' => $calcResult['settlement_amount'],
                'platform_amount' => $calcResult['platform_amount'],
                'cost_amount' => $cost,
                'actual_amount' => $calcResult['settlement_amount'] - $cost,
            ]);
            
            $count++;
        }
        
        return $count;
    }
}
