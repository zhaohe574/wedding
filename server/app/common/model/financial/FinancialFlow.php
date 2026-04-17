<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 资金流水模型
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\common\model\financial;

use app\common\model\BaseModel;
use app\common\model\order\Order;
use app\common\model\staff\Staff;
use app\common\model\user\User;
use think\facade\Log;

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
        $data = self::normalizeFlowData($data);

        $flow = new self();
        $flow->flow_sn = self::generateFlowSn();
        $flow->flow_type = $data['flow_type'];
        $flow->biz_type = $data['biz_type'];
        $flow->biz_id = $data['biz_id'];
        $flow->biz_sn = $data['biz_sn'];
        $flow->order_id = $data['order_id'];
        $flow->user_id = $data['user_id'];
        $flow->staff_id = $data['staff_id'];
        $flow->amount = $data['amount'];
        $flow->direction = $data['direction'];
        $flow->balance_before = $data['balance_before'];
        $flow->balance_after = $data['balance_after'];
        $flow->pay_way = $data['pay_way'];
        $flow->transaction_id = $data['transaction_id'];
        $flow->remark = $data['remark'];
        $flow->operator_type = $data['operator_type'];
        $flow->operator_id = $data['operator_id'];
        $flow->create_time = $data['create_time'];
        $flow->save();
        return $flow;
    }

    /**
     * @notes 按业务主键幂等创建流水
     */
    public static function createUniqueFlow(array $data): self
    {
        $data = self::normalizeFlowData($data);

        if ($data['biz_type'] > 0 && $data['biz_id'] > 0) {
            $exists = self::where('biz_type', $data['biz_type'])
                ->where('biz_id', $data['biz_id'])
                ->where('flow_type', $data['flow_type'])
                ->find();
            if ($exists) {
                return $exists;
            }
        }

        return self::createFlow($data);
    }

    /**
     * @notes 安全记录流水，避免影响主流程
     */
    public static function safeCreateUniqueFlow(array $data): bool
    {
        try {
            self::createUniqueFlow($data);
            return true;
        } catch (\Throwable $e) {
            Log::error('资金流水记录失败: ' . $e->getMessage(), [
                'biz_type' => (int)($data['biz_type'] ?? 0),
                'biz_id' => (int)($data['biz_id'] ?? 0),
                'flow_type' => (int)($data['flow_type'] ?? 0),
                'order_id' => (int)($data['order_id'] ?? 0),
            ]);
            return false;
        }
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

    /**
     * @notes 规范化流水数据
     */
    protected static function normalizeFlowData(array $data): array
    {
        $amount = round(abs((float)($data['amount'] ?? 0)), 2);
        if ($amount <= 0) {
            throw new \InvalidArgumentException('流水金额必须大于0');
        }

        $flowType = (int)($data['flow_type'] ?? 0);
        if (!array_key_exists($flowType, self::getFlowTypeDesc())) {
            throw new \InvalidArgumentException('流水类型错误');
        }

        $bizType = (int)($data['biz_type'] ?? self::BIZ_TYPE_OTHER);
        if (!array_key_exists($bizType, self::getBizTypeDesc())) {
            $bizType = self::BIZ_TYPE_OTHER;
        }

        return [
            'flow_type' => $flowType,
            'biz_type' => $bizType,
            'biz_id' => max(0, (int)($data['biz_id'] ?? 0)),
            'biz_sn' => self::trimText((string)($data['biz_sn'] ?? ''), 32),
            'order_id' => max(0, (int)($data['order_id'] ?? 0)),
            'user_id' => max(0, (int)($data['user_id'] ?? 0)),
            'staff_id' => max(0, (int)($data['staff_id'] ?? 0)),
            'amount' => $amount,
            'direction' => (int)($data['direction'] ?? self::DIRECTION_IN) === self::DIRECTION_OUT
                ? self::DIRECTION_OUT
                : self::DIRECTION_IN,
            'balance_before' => round((float)($data['balance_before'] ?? 0), 2),
            'balance_after' => round((float)($data['balance_after'] ?? 0), 2),
            'pay_way' => array_key_exists((int)($data['pay_way'] ?? self::PAY_WAY_SYSTEM), self::getPayWayDesc())
                ? (int)($data['pay_way'] ?? self::PAY_WAY_SYSTEM)
                : self::PAY_WAY_SYSTEM,
            'transaction_id' => self::trimText((string)($data['transaction_id'] ?? ''), 64),
            'remark' => self::trimText((string)($data['remark'] ?? ''), 255),
            'operator_type' => max(0, (int)($data['operator_type'] ?? 0)),
            'operator_id' => max(0, (int)($data['operator_id'] ?? 0)),
            'create_time' => max(1, (int)($data['create_time'] ?? time())),
        ];
    }

    /**
     * @notes 截断文案
     */
    protected static function trimText(string $value, int $maxLength): string
    {
        $value = trim($value);
        if ($value === '') {
            return '';
        }

        return mb_strlen($value, 'UTF-8') > $maxLength
            ? mb_substr($value, 0, $maxLength, 'UTF-8')
            : $value;
    }
}
