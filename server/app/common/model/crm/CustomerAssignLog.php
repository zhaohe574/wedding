<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 客户分配日志模型
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\common\model\crm;

use app\common\model\BaseModel;
use app\common\model\auth\Admin;

/**
 * 客户分配日志模型
 * Class CustomerAssignLog
 * @package app\common\model\crm
 */
class CustomerAssignLog extends BaseModel
{
    protected $name = 'customer_assign_log';
    
    // 分配类型
    const TYPE_AUTO = 1;        // 自动分配
    const TYPE_MANUAL = 2;      // 手动分配
    const TYPE_TRANSFER = 3;    // 转交
    const TYPE_RECYCLE = 4;     // 回收重分

    /**
     * @notes 分配类型选项
     * @return array
     */
    public static function getTypeOptions(): array
    {
        return [
            self::TYPE_AUTO => '自动分配',
            self::TYPE_MANUAL => '手动分配',
            self::TYPE_TRANSFER => '转交',
            self::TYPE_RECYCLE => '回收重分',
        ];
    }

    /**
     * @notes 关联客户
     * @return \think\model\relation\BelongsTo
     */
    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id', 'id');
    }

    /**
     * @notes 关联原顾问
     * @return \think\model\relation\BelongsTo
     */
    public function fromAdvisor()
    {
        return $this->belongsTo(SalesAdvisor::class, 'from_advisor_id', 'id');
    }

    /**
     * @notes 关联新顾问
     * @return \think\model\relation\BelongsTo
     */
    public function toAdvisor()
    {
        return $this->belongsTo(SalesAdvisor::class, 'to_advisor_id', 'id');
    }

    /**
     * @notes 关联操作管理员
     * @return \think\model\relation\BelongsTo
     */
    public function admin()
    {
        return $this->belongsTo(Admin::class, 'admin_id', 'id');
    }

    /**
     * @notes 分配类型描述获取器
     * @param $value
     * @param $data
     * @return string
     */
    public function getAssignTypeDescAttr($value, $data): string
    {
        $options = self::getTypeOptions();
        return $options[$data['assign_type']] ?? '未知';
    }

    /**
     * @notes 获取客户分配历史
     * @param int $customerId
     * @return array
     */
    public static function getCustomerHistory(int $customerId): array
    {
        return self::where('customer_id', $customerId)
            ->with(['fromAdvisor', 'toAdvisor', 'admin'])
            ->order('create_time desc')
            ->select()
            ->toArray();
    }

    /**
     * @notes 获取顾问接收客户记录
     * @param int $advisorId
     * @param string $startDate
     * @param string $endDate
     * @return array
     */
    public static function getAdvisorReceiveHistory(int $advisorId, string $startDate = '', string $endDate = ''): array
    {
        $query = self::where('to_advisor_id', $advisorId);
        
        if ($startDate) {
            $query->where('create_time', '>=', strtotime($startDate));
        }
        if ($endDate) {
            $query->where('create_time', '<=', strtotime($endDate . ' 23:59:59'));
        }
        
        return $query->with(['customer', 'fromAdvisor'])
            ->order('create_time desc')
            ->select()
            ->toArray();
    }

    /**
     * @notes 获取顾问转出客户记录
     * @param int $advisorId
     * @param string $startDate
     * @param string $endDate
     * @return array
     */
    public static function getAdvisorTransferHistory(int $advisorId, string $startDate = '', string $endDate = ''): array
    {
        $query = self::where('from_advisor_id', $advisorId)
            ->where('from_advisor_id', '>', 0);
        
        if ($startDate) {
            $query->where('create_time', '>=', strtotime($startDate));
        }
        if ($endDate) {
            $query->where('create_time', '<=', strtotime($endDate . ' 23:59:59'));
        }
        
        return $query->with(['customer', 'toAdvisor'])
            ->order('create_time desc')
            ->select()
            ->toArray();
    }

    /**
     * @notes 获取分配统计
     * @param string $startDate
     * @param string $endDate
     * @return array
     */
    public static function getAssignStats(string $startDate, string $endDate): array
    {
        $startTime = strtotime($startDate);
        $endTime = strtotime($endDate . ' 23:59:59');
        
        $logs = self::whereBetween('create_time', [$startTime, $endTime])->select();
        
        $stats = [
            'total' => $logs->count(),
            'by_type' => [],
        ];
        
        $typeOptions = self::getTypeOptions();
        foreach ($typeOptions as $key => $label) {
            $stats['by_type'][$key] = [
                'label' => $label,
                'count' => 0,
            ];
        }
        
        foreach ($logs as $log) {
            if (isset($stats['by_type'][$log->assign_type])) {
                $stats['by_type'][$log->assign_type]['count']++;
            }
        }
        
        return $stats;
    }

    /**
     * @notes 记录分配日志
     * @param int $customerId
     * @param int $fromAdvisorId
     * @param int $toAdvisorId
     * @param int $assignType
     * @param string $reason
     * @param int $adminId
     * @return CustomerAssignLog|false
     */
    public static function record(
        int $customerId,
        int $fromAdvisorId,
        int $toAdvisorId,
        int $assignType = self::TYPE_MANUAL,
        string $reason = '',
        int $adminId = 0
    ) {
        return self::create([
            'customer_id' => $customerId,
            'from_advisor_id' => $fromAdvisorId,
            'to_advisor_id' => $toAdvisorId,
            'assign_type' => $assignType,
            'assign_reason' => $reason,
            'admin_id' => $adminId,
            'create_time' => time(),
        ]);
    }
}
