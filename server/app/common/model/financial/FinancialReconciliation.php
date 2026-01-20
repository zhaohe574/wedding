<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 财务对账模型
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\common\model\financial;

use app\common\model\BaseModel;
use app\common\model\order\Payment;

/**
 * 财务对账记录模型
 * Class FinancialReconciliation
 * @package app\common\model\financial
 */
class FinancialReconciliation extends BaseModel
{
    protected $name = 'financial_reconciliation';

    // 支付渠道
    const CHANNEL_WECHAT = 1;   // 微信
    const CHANNEL_ALIPAY = 2;   // 支付宝

    // 状态
    const STATUS_PENDING = 0;      // 待对账
    const STATUS_PROCESSING = 1;   // 对账中
    const STATUS_BALANCED = 2;     // 已平账
    const STATUS_DIFF = 3;         // 有差异
    const STATUS_HANDLED = 4;      // 已处理

    /**
     * @notes 支付渠道描述
     */
    public static function getChannelDesc($value = true)
    {
        $data = [
            self::CHANNEL_WECHAT => '微信支付',
            self::CHANNEL_ALIPAY => '支付宝',
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
            self::STATUS_PENDING => '待对账',
            self::STATUS_PROCESSING => '对账中',
            self::STATUS_BALANCED => '已平账',
            self::STATUS_DIFF => '有差异',
            self::STATUS_HANDLED => '已处理',
        ];
        if ($value === true) {
            return $data;
        }
        return $data[$value] ?? '';
    }

    /**
     * @notes 生成对账编号
     */
    public static function generateReconcileSn(): string
    {
        return 'REC' . date('YmdHis') . mt_rand(1000, 9999);
    }

    /**
     * @notes 创建对账记录
     */
    public static function createReconciliation(string $date, int $channel): self
    {
        // 检查是否已存在
        $existing = self::where('reconcile_date', $date)
            ->where('pay_channel', $channel)
            ->find();
        
        if ($existing) {
            return $existing;
        }
        
        $reconcile = new self();
        $reconcile->reconcile_sn = self::generateReconcileSn();
        $reconcile->reconcile_date = $date;
        $reconcile->pay_channel = $channel;
        $reconcile->status = self::STATUS_PENDING;
        
        // 获取系统数据
        $systemStats = self::getSystemStats($date, $channel);
        $reconcile->system_count = $systemStats['count'];
        $reconcile->system_amount = $systemStats['amount'];
        
        $reconcile->save();
        return $reconcile;
    }

    /**
     * @notes 获取系统统计数据
     */
    public static function getSystemStats(string $date, int $channel): array
    {
        $startTime = strtotime($date . ' 00:00:00');
        $endTime = strtotime($date . ' 23:59:59');
        
        $stats = Payment::whereBetweenTime('pay_time', $startTime, $endTime)
            ->where('pay_status', 1)
            ->where('pay_way', $channel)
            ->field([
                'COUNT(*) as count',
                'SUM(pay_amount) as amount',
            ])
            ->find();
        
        return [
            'count' => $stats['count'] ?? 0,
            'amount' => $stats['amount'] ?? 0,
        ];
    }

    /**
     * @notes 开始对账
     */
    public function startReconcile(): bool
    {
        if ($this->status !== self::STATUS_PENDING) {
            return false;
        }
        $this->status = self::STATUS_PROCESSING;
        return $this->save();
    }

    /**
     * @notes 设置渠道数据
     */
    public function setChannelData(int $count, float $amount, string $billFile = ''): bool
    {
        $this->channel_count = $count;
        $this->channel_amount = $amount;
        $this->bill_file = $billFile;
        
        // 计算差异
        $this->diff_count = $this->system_count - $count;
        $this->diff_amount = round($this->system_amount - $amount, 2);
        
        // 判断是否平账
        if ($this->diff_count == 0 && abs($this->diff_amount) < 0.01) {
            $this->status = self::STATUS_BALANCED;
        } else {
            $this->status = self::STATUS_DIFF;
        }
        
        return $this->save();
    }

    /**
     * @notes 处理差异
     */
    public function handleDiff(int $adminId, string $remark, string $resultFile = ''): bool
    {
        if ($this->status !== self::STATUS_DIFF) {
            return false;
        }
        
        $this->status = self::STATUS_HANDLED;
        $this->handle_admin_id = $adminId;
        $this->handle_time = time();
        $this->handle_remark = $remark;
        $this->result_file = $resultFile;
        return $this->save();
    }

    /**
     * @notes 批量创建对账记录
     */
    public static function batchCreate(string $startDate, string $endDate, int $channel = 0): int
    {
        $count = 0;
        $current = strtotime($startDate);
        $end = strtotime($endDate);
        $channels = $channel > 0 ? [$channel] : [self::CHANNEL_WECHAT, self::CHANNEL_ALIPAY];
        
        while ($current <= $end) {
            $date = date('Y-m-d', $current);
            foreach ($channels as $ch) {
                self::createReconciliation($date, $ch);
                $count++;
            }
            $current = strtotime('+1 day', $current);
        }
        
        return $count;
    }

    /**
     * @notes 获取对账统计
     */
    public static function getReconcileStats(string $startDate = '', string $endDate = ''): array
    {
        $query = self::where('1=1');
        
        if ($startDate && $endDate) {
            $query->whereBetween('reconcile_date', [$startDate, $endDate]);
        }
        
        return [
            'total_count' => (clone $query)->count(),
            'pending_count' => (clone $query)->where('status', self::STATUS_PENDING)->count(),
            'balanced_count' => (clone $query)->where('status', self::STATUS_BALANCED)->count(),
            'diff_count' => (clone $query)->where('status', self::STATUS_DIFF)->count(),
            'handled_count' => (clone $query)->where('status', self::STATUS_HANDLED)->count(),
            'total_system_amount' => (clone $query)->sum('system_amount'),
            'total_channel_amount' => (clone $query)->sum('channel_amount'),
            'total_diff_amount' => (clone $query)->sum('diff_amount'),
        ];
    }
}
