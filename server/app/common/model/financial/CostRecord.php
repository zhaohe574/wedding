<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 成本记录模型
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\common\model\financial;

use app\common\model\BaseModel;
use app\common\model\staff\Staff;
use app\common\model\order\Order;
use app\common\model\order\OrderItem;
use think\model\concern\SoftDelete;

/**
 * 成本记录模型
 * Class CostRecord
 * @package app\common\model\financial
 */
class CostRecord extends BaseModel
{
    use SoftDelete;

    protected $name = 'cost_record';
    protected $deleteTime = 'delete_time';

    // 成本类型
    const COST_TYPE_LABOR = 1;      // 人工成本
    const COST_TYPE_MATERIAL = 2;   // 物料成本
    const COST_TYPE_TRANSPORT = 3;  // 交通成本
    const COST_TYPE_EQUIPMENT = 4;  // 设备成本
    const COST_TYPE_OTHER = 5;      // 其他成本

    // 状态
    const STATUS_PENDING = 0;    // 待确认
    const STATUS_CONFIRMED = 1;  // 已确认
    const STATUS_CANCELLED = 2;  // 已取消

    /**
     * @notes 成本类型描述
     */
    public static function getCostTypeDesc($value = true)
    {
        $data = [
            self::COST_TYPE_LABOR => '人工成本',
            self::COST_TYPE_MATERIAL => '物料成本',
            self::COST_TYPE_TRANSPORT => '交通成本',
            self::COST_TYPE_EQUIPMENT => '设备成本',
            self::COST_TYPE_OTHER => '其他成本',
        ];
        if ($value === true) {
            return $data;
        }
        return $data[$value] ?? '';
    }

    /**
     * @notes 状态描述
     */
    public static function getStatusDesc($value = true)
    {
        $data = [
            self::STATUS_PENDING => '待确认',
            self::STATUS_CONFIRMED => '已确认',
            self::STATUS_CANCELLED => '已取消',
        ];
        if ($value === true) {
            return $data;
        }
        return $data[$value] ?? '';
    }

    /**
     * @notes 关联订单
     */
    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id', 'id')
            ->field('id, order_sn, total_amount, pay_amount');
    }

    /**
     * @notes 关联订单项
     */
    public function orderItem()
    {
        return $this->belongsTo(OrderItem::class, 'order_item_id', 'id')
            ->field('id, staff_name, package_name, subtotal');
    }

    /**
     * @notes 关联服务人员
     */
    public function staff()
    {
        return $this->belongsTo(Staff::class, 'staff_id', 'id')
            ->field('id, name, avatar');
    }

    /**
     * @notes 生成成本编号
     */
    public static function generateCostSn(): string
    {
        return 'COST' . date('YmdHis') . mt_rand(1000, 9999);
    }

    /**
     * @notes 创建成本记录
     */
    public static function createCost(array $data): self
    {
        $cost = new self();
        $cost->cost_sn = self::generateCostSn();
        $cost->order_id = $data['order_id'] ?? 0;
        $cost->order_item_id = $data['order_item_id'] ?? 0;
        $cost->staff_id = $data['staff_id'] ?? 0;
        $cost->cost_type = $data['cost_type'];
        $cost->cost_name = $data['cost_name'];
        $cost->unit_price = $data['unit_price'] ?? $data['cost_amount'];
        $cost->quantity = $data['quantity'] ?? 1;
        $cost->cost_amount = $data['cost_amount'];
        $cost->service_date = $data['service_date'] ?? null;
        $cost->remark = $data['remark'] ?? '';
        $cost->status = self::STATUS_PENDING;
        $cost->save();
        return $cost;
    }

    /**
     * @notes 确认成本
     */
    public function confirm(int $adminId): bool
    {
        if ($this->status !== self::STATUS_PENDING) {
            return false;
        }
        $this->status = self::STATUS_CONFIRMED;
        $this->confirm_admin_id = $adminId;
        $this->confirm_time = time();
        return $this->save();
    }

    /**
     * @notes 取消成本
     */
    public function cancel(): bool
    {
        if ($this->status !== self::STATUS_PENDING) {
            return false;
        }
        $this->status = self::STATUS_CANCELLED;
        return $this->save();
    }

    /**
     * @notes 获取订单总成本
     */
    public static function getOrderTotalCost(int $orderId): float
    {
        return (float) self::where('order_id', $orderId)
            ->where('status', self::STATUS_CONFIRMED)
            ->sum('cost_amount');
    }

    /**
     * @notes 获取日期范围内的成本统计
     */
    public static function getCostStats(string $startDate, string $endDate): array
    {
        $query = self::where('status', self::STATUS_CONFIRMED)
            ->whereBetween('service_date', [$startDate, $endDate]);
        
        $total = $query->sum('cost_amount');
        
        $byType = self::where('status', self::STATUS_CONFIRMED)
            ->whereBetween('service_date', [$startDate, $endDate])
            ->group('cost_type')
            ->column('SUM(cost_amount) as amount', 'cost_type');
        
        return [
            'total' => $total,
            'labor' => $byType[self::COST_TYPE_LABOR] ?? 0,
            'material' => $byType[self::COST_TYPE_MATERIAL] ?? 0,
            'transport' => $byType[self::COST_TYPE_TRANSPORT] ?? 0,
            'equipment' => $byType[self::COST_TYPE_EQUIPMENT] ?? 0,
            'other' => $byType[self::COST_TYPE_OTHER] ?? 0,
        ];
    }
}
