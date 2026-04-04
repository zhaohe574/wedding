<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 结算批次模型
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\common\model\financial;

use app\common\model\BaseModel;
use think\facade\Db;

/**
 * 结算批次模型
 * Class SettlementBatch
 * @package app\common\model\financial
 */
class SettlementBatch extends BaseModel
{
    protected $name = 'settlement_batch';

    // 状态
    const STATUS_PENDING = 0;      // 待审核
    const STATUS_APPROVED = 1;     // 审核通过
    const STATUS_PROCESSING = 2;   // 处理中
    const STATUS_COMPLETED = 3;    // 已完成
    const STATUS_CANCELLED = 4;    // 已取消

    /**
     * @notes 状态描述
     */
    public static function getStatusDesc($value = true)
    {
        $data = [
            self::STATUS_PENDING => '待审核',
            self::STATUS_APPROVED => '审核通过',
            self::STATUS_PROCESSING => '处理中',
            self::STATUS_COMPLETED => '已完成',
            self::STATUS_CANCELLED => '已取消',
        ];
        if ($value === true) {
            return $data;
        }
        return $data[$value] ?? '';
    }

    /**
     * @notes 关联结算记录
     */
    public function settlements()
    {
        return $this->hasMany(StaffSettlement::class, 'batch_id', 'id');
    }

    /**
     * @notes 生成批次编号
     */
    public static function generateBatchSn(): string
    {
        return 'BAT' . date('YmdHis') . mt_rand(1000, 9999);
    }

    /**
     * @notes 创建结算批次
     */
    public static function createBatch(array $data): self
    {
        $batch = new self();
        $batch->batch_sn = self::generateBatchSn();
        $batch->batch_name = $data['batch_name'] ?? '结算批次-' . date('Y-m-d');
        $batch->settle_start_date = $data['settle_start_date'];
        $batch->settle_end_date = $data['settle_end_date'];
        $batch->total_count = $data['total_count'] ?? 0;
        $batch->total_amount = $data['total_amount'] ?? 0;
        $batch->status = self::STATUS_PENDING;
        $batch->remark = $data['remark'] ?? '';
        $batch->save();
        return $batch;
    }

    /**
     * @notes 审核通过
     */
    public function approve(int $adminId, string $remark = ''): bool
    {
        if ($this->status !== self::STATUS_PENDING) {
            return false;
        }
        $this->status = self::STATUS_APPROVED;
        $this->audit_admin_id = $adminId;
        $this->audit_time = time();
        $this->audit_remark = $remark;
        return $this->save();
    }

    /**
     * @notes 开始执行
     */
    public function startExecute(int $adminId): bool
    {
        if ($this->status !== self::STATUS_APPROVED) {
            return false;
        }
        $this->status = self::STATUS_PROCESSING;
        $this->execute_admin_id = $adminId;
        $this->execute_time = time();
        return $this->save();
    }

    /**
     * @notes 执行结算
     */
    public function execute(): array
    {
        if ($this->status !== self::STATUS_PROCESSING) {
            return ['success' => false, 'message' => '批次状态不正确'];
        }
        
        $successCount = 0;
        $failCount = 0;
        $successAmount = 0;
        $failAmount = 0;
        
        $settlements = StaffSettlement::where('batch_id', $this->id)
            ->where('status', StaffSettlement::STATUS_PENDING)
            ->select();
        
        foreach ($settlements as $settlement) {
            try {
                if ($settlement->settle()) {
                    $successCount++;
                    $successAmount += $settlement->actual_amount;
                } else {
                    $failCount++;
                    $failAmount += $settlement->actual_amount;
                }
            } catch (\Exception $e) {
                $settlement->markFailed($e->getMessage());
                $failCount++;
                $failAmount += $settlement->actual_amount;
            }
        }
        
        // 更新批次统计
        $this->success_count = $successCount;
        $this->fail_count = $failCount;
        $this->success_amount = $successAmount;
        $this->fail_amount = $failAmount;
        $this->status = self::STATUS_COMPLETED;
        $this->complete_time = time();
        $this->save();
        
        return [
            'success' => true,
            'success_count' => $successCount,
            'fail_count' => $failCount,
            'success_amount' => $successAmount,
            'fail_amount' => $failAmount,
        ];
    }

    /**
     * @notes 取消批次
     */
    public function cancel(): bool
    {
        if (!in_array($this->status, [self::STATUS_PENDING, self::STATUS_APPROVED])) {
            return false;
        }
        
        // 取消关联的结算记录
        StaffSettlement::where('batch_id', $this->id)
            ->where('status', StaffSettlement::STATUS_PENDING)
            ->update(['status' => StaffSettlement::STATUS_CANCELLED]);
        
        $this->status = self::STATUS_CANCELLED;
        return $this->save();
    }

    /**
     * @notes 更新统计数据
     */
    public function updateStats(): bool
    {
        $stats = StaffSettlement::where('batch_id', $this->id)
            ->field([
                'COUNT(*) as total_count',
                'SUM(actual_amount) as total_amount',
                'SUM(CASE WHEN status = ' . StaffSettlement::STATUS_SETTLED . ' THEN 1 ELSE 0 END) as success_count',
                'SUM(CASE WHEN status = ' . StaffSettlement::STATUS_SETTLED . ' THEN actual_amount ELSE 0 END) as success_amount',
                'SUM(CASE WHEN status = ' . StaffSettlement::STATUS_FAILED . ' THEN 1 ELSE 0 END) as fail_count',
                'SUM(CASE WHEN status = ' . StaffSettlement::STATUS_FAILED . ' THEN actual_amount ELSE 0 END) as fail_amount',
            ])
            ->find();
        
        $this->total_count = $stats['total_count'] ?? 0;
        $this->total_amount = $stats['total_amount'] ?? 0;
        $this->success_count = $stats['success_count'] ?? 0;
        $this->success_amount = $stats['success_amount'] ?? 0;
        $this->fail_count = $stats['fail_count'] ?? 0;
        $this->fail_amount = $stats['fail_amount'] ?? 0;
        return $this->save();
    }
}
