<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 服务人员结算模型
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\common\model\financial;

use app\common\model\BaseModel;
use app\common\model\staff\Staff;
use app\common\model\order\Order;
use app\common\model\order\OrderItem;

/**
 * 服务人员结算模型
 * Class StaffSettlement
 * @package app\common\model\financial
 */
class StaffSettlement extends BaseModel
{
    protected $name = 'staff_settlement';

    // 结算类型
    const TYPE_AUTO = 1;    // 自动结算
    const TYPE_MANUAL = 2;  // 手动结算

    // 状态
    const STATUS_PENDING = 0;    // 待结算
    const STATUS_SETTLED = 1;    // 已结算
    const STATUS_CANCELLED = 2;  // 已取消
    const STATUS_FAILED = 3;     // 结算失败

    // 结算方式
    const SETTLE_WAY_BALANCE = 1;   // 余额
    const SETTLE_WAY_BANK = 2;      // 银行卡
    const SETTLE_WAY_WECHAT = 3;    // 微信
    const SETTLE_WAY_ALIPAY = 4;    // 支付宝

    /**
     * @notes 结算类型描述
     */
    public static function getTypeDesc($value = true)
    {
        $data = [
            self::TYPE_AUTO => '自动结算',
            self::TYPE_MANUAL => '手动结算',
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
            self::STATUS_PENDING => '待结算',
            self::STATUS_SETTLED => '已结算',
            self::STATUS_CANCELLED => '已取消',
            self::STATUS_FAILED => '结算失败',
        ];
        if ($value === true) {
            return $data;
        }
        return $data[$value] ?? '';
    }

    /**
     * @notes 结算方式描述
     */
    public static function getSettleWayDesc($value = true)
    {
        $data = [
            self::SETTLE_WAY_BALANCE => '余额',
            self::SETTLE_WAY_BANK => '银行卡',
            self::SETTLE_WAY_WECHAT => '微信',
            self::SETTLE_WAY_ALIPAY => '支付宝',
        ];
        if ($value === true) {
            return $data;
        }
        return $data[$value] ?? '';
    }

    /**
     * @notes 关联服务人员
     */
    public function staff()
    {
        return $this->belongsTo(Staff::class, 'staff_id', 'id')
            ->field('id, name, avatar, mobile');
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
     * @notes 关联批次
     */
    public function batch()
    {
        return $this->belongsTo(SettlementBatch::class, 'batch_id', 'id')
            ->field('id, batch_sn, batch_name, status');
    }

    /**
     * @notes 生成结算编号
     */
    public static function generateSettlementSn(): string
    {
        return 'STL' . date('YmdHis') . mt_rand(1000, 9999);
    }

    /**
     * @notes 创建结算记录
     */
    public static function createSettlement(array $data): self
    {
        $settlement = new self();
        $settlement->settlement_sn = self::generateSettlementSn();
        $settlement->batch_id = $data['batch_id'] ?? 0;
        $settlement->staff_id = $data['staff_id'];
        $settlement->order_id = $data['order_id'] ?? 0;
        $settlement->order_item_id = $data['order_item_id'] ?? 0;
        $settlement->service_date = $data['service_date'] ?? null;
        $settlement->order_amount = $data['order_amount'];
        $settlement->settlement_rate = $data['settlement_rate'];
        $settlement->settlement_amount = $data['settlement_amount'];
        $settlement->platform_amount = $data['platform_amount'] ?? 0;
        $settlement->cost_amount = $data['cost_amount'] ?? 0;
        $settlement->actual_amount = $data['actual_amount'];
        $settlement->settlement_type = $data['settlement_type'] ?? self::TYPE_AUTO;
        $settlement->status = self::STATUS_PENDING;
        $settlement->settle_way = $data['settle_way'] ?? self::SETTLE_WAY_BALANCE;
        $settlement->remark = $data['remark'] ?? '';
        $settlement->save();
        return $settlement;
    }

    /**
     * @notes 执行结算
     */
    public function settle(string $transactionId = ''): bool
    {
        if ($this->status !== self::STATUS_PENDING) {
            return false;
        }
        
        $this->status = self::STATUS_SETTLED;
        $this->settle_time = time();
        $this->transaction_id = $transactionId;
        
        if ($this->save()) {
            // 创建资金流水
            FinancialFlow::createFlow([
                'flow_type' => FinancialFlow::FLOW_TYPE_SETTLEMENT,
                'biz_type' => FinancialFlow::BIZ_TYPE_STAFF_SETTLE,
                'biz_id' => $this->id,
                'biz_sn' => $this->settlement_sn,
                'order_id' => $this->order_id,
                'staff_id' => $this->staff_id,
                'amount' => $this->actual_amount,
                'direction' => FinancialFlow::DIRECTION_OUT,
                'remark' => '服务人员结算',
            ]);
            return true;
        }
        return false;
    }

    /**
     * @notes 标记失败
     */
    public function markFailed(string $reason): bool
    {
        $this->status = self::STATUS_FAILED;
        $this->fail_reason = $reason;
        return $this->save();
    }

    /**
     * @notes 取消结算
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
     * @notes 获取人员结算统计
     */
    public static function getStaffSettlementStats(int $staffId, string $startDate = '', string $endDate = ''): array
    {
        $query = self::where('staff_id', $staffId)
            ->where('status', self::STATUS_SETTLED);
        
        if ($startDate && $endDate) {
            $query->whereBetweenTime('settle_time', $startDate, $endDate . ' 23:59:59');
        }
        
        return [
            'total_count' => $query->count(),
            'total_amount' => $query->sum('actual_amount'),
            'pending_count' => self::where('staff_id', $staffId)->where('status', self::STATUS_PENDING)->count(),
            'pending_amount' => self::where('staff_id', $staffId)->where('status', self::STATUS_PENDING)->sum('actual_amount'),
        ];
    }

    /**
     * @notes 获取待结算列表
     */
    public static function getPendingSettlements(int $staffId = 0): \think\Collection
    {
        $query = self::where('status', self::STATUS_PENDING);
        if ($staffId > 0) {
            $query->where('staff_id', $staffId);
        }
        return $query->with(['staff', 'order'])->order('create_time', 'asc')->select();
    }
}
