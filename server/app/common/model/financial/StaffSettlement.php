<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 服务人员结算模型
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\common\model\financial;

use app\common\model\BaseModel;
use app\common\model\staff\Staff;
use app\common\model\staff\StaffTeam;
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
    const STATUS_TRANSFER_PROCESSING = 4; // 转账处理中/待确认
    const STATUS_NO_PAYOUT = 5;  // 无需打款，仅核算平台抽成

    // 结算方式
    const SETTLE_WAY_BALANCE = 1;   // 余额
    const SETTLE_WAY_BANK = 2;      // 银行卡
    const SETTLE_WAY_WECHAT = 3;    // 微信
    const SETTLE_WAY_ALIPAY = 4;    // 支付宝
    const SETTLE_WAY_NO_PAYOUT = 5; // 无需打款

    // 补收状态
    const DUE_COLLECT_STATUS_NONE = 0;    // 无需补收
    const DUE_COLLECT_STATUS_PENDING = 1; // 待补收
    const DUE_COLLECT_STATUS_PARTIAL = 2; // 部分补收
    const DUE_COLLECT_STATUS_PAID = 3;    // 已补收

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
            self::STATUS_TRANSFER_PROCESSING => '转账处理中',
            self::STATUS_NO_PAYOUT => '无需打款',
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
            self::SETTLE_WAY_WECHAT => '微信转账',
            self::SETTLE_WAY_ALIPAY => '支付宝',
            self::SETTLE_WAY_NO_PAYOUT => '无需打款',
        ];
        if ($value === true) {
            return $data;
        }
        return $data[$value] ?? '';
    }

    /**
     * @notes 补收状态描述
     */
    public static function getDueCollectStatusDesc($value = true)
    {
        $data = [
            self::DUE_COLLECT_STATUS_NONE => '无需补收',
            self::DUE_COLLECT_STATUS_PENDING => '待补收',
            self::DUE_COLLECT_STATUS_PARTIAL => '部分补收',
            self::DUE_COLLECT_STATUS_PAID => '已补收',
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
            ->field('id, order_sn, total_amount, pay_amount, pay_type, payment_channel, pay_voucher');
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
     * @notes 关联服务队伍
     */
    public function team()
    {
        return $this->belongsTo(StaffTeam::class, 'team_id', 'id')
            ->field('id, name, leader_staff_id');
    }

    /**
     * @notes 关联队长
     */
    public function leader()
    {
        return $this->belongsTo(Staff::class, 'leader_staff_id', 'id')
            ->field('id, name, avatar, mobile');
    }

    /**
     * @notes 关联结算规则
     */
    public function config()
    {
        return $this->belongsTo(StaffSettlementConfig::class, 'config_id', 'id')
            ->field('id, scope_type, settlement_mode, settlement_rate, company_rate, leader_rate, monthly_fee');
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
     * @notes 关联转账明细
     */
    public function transfers()
    {
        return $this->hasMany(StaffSettlementTransfer::class, 'settlement_id', 'id')
            ->order('id', 'asc');
    }

    /**
     * @notes 关联补收记录
     */
    public function repays()
    {
        return $this->hasMany(StaffSettlementRepay::class, 'settlement_id', 'id')
            ->order('id', 'desc');
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
        $settlement->team_id = $data['team_id'] ?? 0;
        $settlement->leader_staff_id = $data['leader_staff_id'] ?? 0;
        $settlement->config_id = $data['config_id'] ?? 0;
        $settlement->scope_type = $data['scope_type'] ?? StaffSettlementConfig::SCOPE_DEFAULT;
        $settlement->settlement_mode = $data['settlement_mode'] ?? StaffSettlementConfig::MODE_RATE;
        $settlement->rule_source = $data['rule_source'] ?? '';
        $settlement->order_id = $data['order_id'] ?? 0;
        $orderItemId = (int)($data['order_item_id'] ?? 0);
        $settlement->order_item_id = $orderItemId > 0 ? $orderItemId : null;
        $settlement->service_date = $data['service_date'] ?? null;
        $settlement->order_amount = $data['order_amount'];
        $settlement->settlement_rate = $data['settlement_rate'];
        $settlement->company_rate = $data['company_rate'] ?? 0;
        $settlement->company_amount = $data['company_amount'] ?? ($data['platform_amount'] ?? 0);
        $settlement->leader_rate = $data['leader_rate'] ?? 0;
        $settlement->leader_amount = $data['leader_amount'] ?? 0;
        $settlement->monthly_fee_amount = $data['monthly_fee_amount'] ?? 0;
        $settlement->monthly_fee_deduct_amount = $data['monthly_fee_deduct_amount'] ?? 0;
        $settlement->settlement_amount = $data['settlement_amount'];
        $settlement->platform_amount = $data['platform_amount'] ?? 0;
        $settlement->platform_paid_share_amount = $data['platform_paid_share_amount'] ?? 0;
        $settlement->staff_due_platform_amount = $data['staff_due_platform_amount'] ?? 0;
        $settlement->staff_due_collected_amount = $data['staff_due_collected_amount'] ?? 0;
        $settlement->staff_due_collect_status = $data['staff_due_collect_status'] ?? self::DUE_COLLECT_STATUS_NONE;
        $settlement->staff_due_collect_time = $data['staff_due_collect_time'] ?? 0;
        $settlement->staff_due_collect_admin_id = $data['staff_due_collect_admin_id'] ?? 0;
        $settlement->staff_due_collect_remark = $data['staff_due_collect_remark'] ?? '';
        $settlement->cost_amount = $data['cost_amount'] ?? 0;
        $settlement->actual_amount = $data['actual_amount'];
        $settlement->settlement_type = $data['settlement_type'] ?? self::TYPE_AUTO;
        $settlement->status = $data['status'] ?? self::STATUS_PENDING;
        $settlement->settle_way = $data['settle_way'] ?? self::SETTLE_WAY_BALANCE;
        $settlement->remark = $data['remark'] ?? '';
        $settlement->save();
        return $settlement;
    }

    /**
     * @notes 是否为无需打款的结算核算记录
     */
    public function isNoPayout(): bool
    {
        return (int)$this->status === self::STATUS_NO_PAYOUT
            || (int)$this->settle_way === self::SETTLE_WAY_NO_PAYOUT;
    }

    /**
     * @notes 获取应补平台剩余金额
     */
    public function getDuePlatformLeftAmount(): float
    {
        return round(max((float)$this->staff_due_platform_amount - (float)$this->staff_due_collected_amount, 0), 2);
    }

    /**
     * @notes 按补收金额刷新结算补收状态
     */
    public function applyDueCollection(float $amount, int $adminId = 0, string $remark = ''): bool
    {
        $amount = round(max($amount, 0), 2);
        if ($amount <= 0) {
            return false;
        }

        $dueAmount = round((float)$this->staff_due_platform_amount, 2);
        if ($dueAmount <= 0) {
            return false;
        }

        $collected = round((float)$this->staff_due_collected_amount + $amount, 2);
        if ($collected > $dueAmount) {
            $collected = $dueAmount;
        }

        $this->staff_due_collected_amount = $collected;
        $this->staff_due_collect_status = $collected >= $dueAmount
            ? self::DUE_COLLECT_STATUS_PAID
            : self::DUE_COLLECT_STATUS_PARTIAL;
        $this->staff_due_collect_time = time();
        if ($adminId > 0) {
            $this->staff_due_collect_admin_id = $adminId;
        }
        if ($remark !== '') {
            $this->staff_due_collect_remark = mb_substr($remark, 0, 255);
        }
        return $this->save();
    }

    /**
     * @notes 执行结算
     */
    public function settle(string $transactionId = '', int $settleWay = 0): bool
    {
        if ($this->isNoPayout()) {
            return false;
        }

        if (!in_array((int)$this->status, [
            self::STATUS_PENDING,
            self::STATUS_FAILED,
            self::STATUS_TRANSFER_PROCESSING,
        ], true)) {
            return false;
        }
        
        $this->status = self::STATUS_SETTLED;
        $this->settle_time = time();
        $this->transaction_id = $transactionId;
        if ($settleWay > 0) {
            $this->settle_way = $settleWay;
        }
        $this->fail_reason = '';
        
        if ($this->save()) {
            // 创建资金流水
            FinancialFlow::createUniqueFlow([
                'flow_type' => FinancialFlow::FLOW_TYPE_SETTLEMENT,
                'biz_type' => FinancialFlow::BIZ_TYPE_STAFF_SETTLE,
                'biz_id' => $this->id,
                'biz_sn' => $this->settlement_sn,
                'order_id' => $this->order_id,
                'staff_id' => $this->staff_id,
                'amount' => $this->actual_amount,
                'direction' => FinancialFlow::DIRECTION_OUT,
                'pay_way' => (int)$this->settle_way === self::SETTLE_WAY_WECHAT
                    ? FinancialFlow::PAY_WAY_WECHAT
                    : FinancialFlow::PAY_WAY_SYSTEM,
                'transaction_id' => $transactionId,
                'remark' => '服务人员结算',
            ]);
            return true;
        }
        return false;
    }

    /**
     * @notes 记录平台补收资金流水
     */
    public static function recordDueCollectionFlow(self $settlement, StaffSettlementRepay $repay): void
    {
        FinancialFlow::safeCreateUniqueFlow([
            'flow_type' => FinancialFlow::FLOW_TYPE_INCOME,
            'biz_type' => FinancialFlow::BIZ_TYPE_PLATFORM_FEE,
            'biz_id' => (int)$repay->id,
            'biz_sn' => (string)$repay->repay_sn,
            'order_id' => (int)$settlement->order_id,
            'staff_id' => (int)$settlement->staff_id,
            'amount' => round((float)$repay->amount, 2),
            'direction' => FinancialFlow::DIRECTION_IN,
            'pay_way' => match ((int)$repay->collect_way) {
                StaffSettlementRepay::COLLECT_WAY_WECHAT => FinancialFlow::PAY_WAY_WECHAT,
                StaffSettlementRepay::COLLECT_WAY_ALIPAY => FinancialFlow::PAY_WAY_ALIPAY,
                StaffSettlementRepay::COLLECT_WAY_OFFLINE => FinancialFlow::PAY_WAY_OFFLINE,
                default => FinancialFlow::PAY_WAY_SYSTEM,
            },
            'transaction_id' => (string)$repay->transaction_id,
            'remark' => '服务人员补交平台抽成',
            'operator_type' => (int)$repay->admin_id > 0 ? 1 : 0,
            'operator_id' => (int)$repay->admin_id,
        ]);
    }

    /**
     * @notes 标记转账处理中
     */
    public function markTransferProcessing(): bool
    {
        if ($this->isNoPayout()) {
            return true;
        }

        if ((int)$this->status === self::STATUS_SETTLED) {
            return true;
        }
        $this->status = self::STATUS_TRANSFER_PROCESSING;
        $this->fail_reason = '';
        return $this->save();
    }

    /**
     * @notes 标记失败
     */
    public function markFailed(string $reason): bool
    {
        if ($this->isNoPayout()) {
            return true;
        }

        if ((int)$this->status === self::STATUS_SETTLED) {
            return true;
        }
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
     * @notes 订单退款时取消尚未进入资金链路的结算
     */
    public function cancelForRefund(): bool
    {
        if (!in_array((int)$this->status, [self::STATUS_PENDING, self::STATUS_FAILED, self::STATUS_NO_PAYOUT], true)) {
            return false;
        }

        $this->status = self::STATUS_CANCELLED;
        $this->fail_reason = '';
        $remark = trim((string)$this->remark);
        $this->remark = mb_substr($remark === '' ? '订单退款取消结算' : $remark . '，订单退款取消结算', 0, 255);
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
