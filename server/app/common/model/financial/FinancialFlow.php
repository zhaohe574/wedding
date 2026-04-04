<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 资金流水模型
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\common\model\financial;

use app\common\model\BaseModel;
use app\common\model\user\User;
use app\common\model\staff\Staff;
use app\common\model\order\Order;

/**
 * 资金流水模型
 * Class FinancialFlow
 * @package app\common\model\financial
 */
class FinancialFlow extends BaseModel
{
    protected $name = 'financial_flow';

    // 流水类型
    const FLOW_TYPE_INCOME = 1;      // 收入
    const FLOW_TYPE_EXPENSE = 2;     // 支出
    const FLOW_TYPE_REFUND = 3;      // 退款
    const FLOW_TYPE_SETTLEMENT = 4;  // 分账
    const FLOW_TYPE_WITHDRAW = 5;    // 提现

    // 业务类型
    const BIZ_TYPE_ORDER_PAY = 1;      // 订单支付
    const BIZ_TYPE_ORDER_REFUND = 2;   // 订单退款
    const BIZ_TYPE_STAFF_SETTLE = 3;   // 人员结算
    const BIZ_TYPE_PLATFORM_FEE = 4;   // 平台抽成
    const BIZ_TYPE_OTHER = 5;          // 其他

    // 支付方式
    const PAY_WAY_SYSTEM = 0;    // 系统
    const PAY_WAY_WECHAT = 1;    // 微信
    const PAY_WAY_ALIPAY = 2;    // 支付宝
    const PAY_WAY_BALANCE = 3;   // 余额
    const PAY_WAY_OFFLINE = 4;   // 线下

    // 方向
    const DIRECTION_IN = 1;      // 收入
    const DIRECTION_OUT = -1;    // 支出

    /**
     * @notes 流水类型描述
     */
    public static function getFlowTypeDesc($value = true)
    {
        $data = [
            self::FLOW_TYPE_INCOME => '收入',
            self::FLOW_TYPE_EXPENSE => '支出',
            self::FLOW_TYPE_REFUND => '退款',
            self::FLOW_TYPE_SETTLEMENT => '分账',
            self::FLOW_TYPE_WITHDRAW => '提现',
        ];
        if ($value === true) {
            return $data;
        }
        return $data[$value] ?? '';
    }

    /**
     * @notes 业务类型描述
     */
    public static function getBizTypeDesc($value = true)
    {
        $data = [
            self::BIZ_TYPE_ORDER_PAY => '订单支付',
            self::BIZ_TYPE_ORDER_REFUND => '订单退款',
            self::BIZ_TYPE_STAFF_SETTLE => '人员结算',
            self::BIZ_TYPE_PLATFORM_FEE => '平台抽成',
            self::BIZ_TYPE_OTHER => '其他',
        ];
        if ($value === true) {
            return $data;
        }
        return $data[$value] ?? '';
    }

    /**
     * @notes 支付方式描述
     */
    public static function getPayWayDesc($value = true)
    {
        $data = [
            self::PAY_WAY_SYSTEM => '系统',
            self::PAY_WAY_WECHAT => '微信支付',
            self::PAY_WAY_ALIPAY => '支付宝',
            self::PAY_WAY_BALANCE => '余额支付',
            self::PAY_WAY_OFFLINE => '线下支付',
        ];
        if ($value === true) {
            return $data;
        }
        return $data[$value] ?? '';
    }

    /**
     * @notes 关联用户
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id')
            ->field('id, nickname, avatar, mobile');
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
     * @notes 生成流水编号
     */
    public static function generateFlowSn(): string
    {
        return 'FL' . date('YmdHis') . mt_rand(1000, 9999);
    }

    /**
     * @notes 创建资金流水
     */
    public static function createFlow(array $data): self
    {
        $flow = new self();
        $flow->flow_sn = self::generateFlowSn();
        $flow->flow_type = $data['flow_type'];
        $flow->biz_type = $data['biz_type'] ?? self::BIZ_TYPE_OTHER;
        $flow->biz_id = $data['biz_id'] ?? 0;
        $flow->biz_sn = $data['biz_sn'] ?? '';
        $flow->order_id = $data['order_id'] ?? 0;
        $flow->user_id = $data['user_id'] ?? 0;
        $flow->staff_id = $data['staff_id'] ?? 0;
        $flow->amount = $data['amount'];
        $flow->direction = $data['direction'] ?? self::DIRECTION_IN;
        $flow->balance_before = $data['balance_before'] ?? 0;
        $flow->balance_after = $data['balance_after'] ?? 0;
        $flow->pay_way = $data['pay_way'] ?? self::PAY_WAY_SYSTEM;
        $flow->transaction_id = $data['transaction_id'] ?? '';
        $flow->remark = $data['remark'] ?? '';
        $flow->operator_type = $data['operator_type'] ?? 0;
        $flow->operator_id = $data['operator_id'] ?? 0;
        $flow->save();
        return $flow;
    }

    /**
     * @notes 获取日期范围内的流水统计
     */
    public static function getFlowStats(string $startDate, string $endDate, int $flowType = 0): array
    {
        $query = self::whereBetweenTime('create_time', $startDate, $endDate . ' 23:59:59');
        
        if ($flowType > 0) {
            $query->where('flow_type', $flowType);
        }
        
        return [
            'total_count' => $query->count(),
            'total_income' => (clone $query)->where('direction', self::DIRECTION_IN)->sum('amount'),
            'total_expense' => (clone $query)->where('direction', self::DIRECTION_OUT)->sum('amount'),
        ];
    }

    /**
     * @notes 按支付方式统计收入
     */
    public static function getIncomeByPayWay(string $startDate, string $endDate): array
    {
        return self::whereBetweenTime('create_time', $startDate, $endDate . ' 23:59:59')
            ->where('direction', self::DIRECTION_IN)
            ->where('flow_type', self::FLOW_TYPE_INCOME)
            ->group('pay_way')
            ->column('SUM(amount) as amount', 'pay_way');
    }
}
