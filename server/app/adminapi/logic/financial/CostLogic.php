<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 成本管理逻辑层
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\adminapi\logic\financial;

use app\common\logic\BaseLogic;
use app\common\model\financial\CostRecord;

/**
 * 成本管理逻辑层
 * Class CostLogic
 * @package app\adminapi\logic\financial
 */
class CostLogic extends BaseLogic
{
    /**
     * @notes 成本详情
     */
    public static function detail(int $id): array
    {
        $cost = CostRecord::with(['order', 'orderItem', 'staff'])->find($id);
        if (!$cost) {
            return [];
        }
        
        $data = $cost->toArray();
        $data['cost_type_text'] = CostRecord::getCostTypeDesc($cost->cost_type);
        $data['status_text'] = CostRecord::getStatusDesc($cost->status);
        
        return $data;
    }

    /**
     * @notes 添加成本
     */
    public static function add(array $params): bool
    {
        try {
            CostRecord::createCost($params);
            return true;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * @notes 编辑成本
     */
    public static function edit(array $params): bool
    {
        try {
            $cost = CostRecord::find($params['id']);
            if (!$cost) {
                self::setError('成本记录不存在');
                return false;
            }
            
            if ($cost->status !== CostRecord::STATUS_PENDING) {
                self::setError('只能编辑待确认的成本记录');
                return false;
            }
            
            $cost->order_id = $params['order_id'] ?? $cost->order_id;
            $cost->order_item_id = $params['order_item_id'] ?? $cost->order_item_id;
            $cost->staff_id = $params['staff_id'] ?? $cost->staff_id;
            $cost->cost_type = $params['cost_type'] ?? $cost->cost_type;
            $cost->cost_name = $params['cost_name'] ?? $cost->cost_name;
            $cost->unit_price = $params['unit_price'] ?? $cost->unit_price;
            $cost->quantity = $params['quantity'] ?? $cost->quantity;
            $cost->cost_amount = $params['cost_amount'] ?? $cost->cost_amount;
            $cost->service_date = $params['service_date'] ?? $cost->service_date;
            $cost->remark = $params['remark'] ?? $cost->remark;
            
            return $cost->save();
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * @notes 删除成本
     */
    public static function delete(int $id): bool
    {
        try {
            $cost = CostRecord::find($id);
            if (!$cost) {
                self::setError('成本记录不存在');
                return false;
            }
            
            if ($cost->status !== CostRecord::STATUS_PENDING) {
                self::setError('只能删除待确认的成本记录');
                return false;
            }
            
            return $cost->delete();
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * @notes 确认成本
     */
    public static function confirm(int $id, int $adminId): bool
    {
        try {
            $cost = CostRecord::find($id);
            if (!$cost) {
                self::setError('成本记录不存在');
                return false;
            }
            
            return $cost->confirm($adminId);
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * @notes 批量确认
     */
    public static function batchConfirm(array $ids, int $adminId): array|bool
    {
        try {
            $successCount = 0;
            $failCount = 0;
            
            foreach ($ids as $id) {
                $cost = CostRecord::find($id);
                if ($cost && $cost->confirm($adminId)) {
                    $successCount++;
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
     * @notes 成本统计
     */
    public static function statistics(array $params): array
    {
        $startDate = $params['start_date'] ?? date('Y-m-01');
        $endDate = $params['end_date'] ?? date('Y-m-d');
        
        return CostRecord::getCostStats($startDate, $endDate);
    }

    /**
     * @notes 成本类型选项
     */
    public static function typeOptions(): array
    {
        $types = CostRecord::getCostTypeDesc();
        $result = [];
        foreach ($types as $value => $label) {
            $result[] = [
                'value' => $value,
                'label' => $label,
            ];
        }
        return $result;
    }
}
