<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 客户流失预警模型
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\common\model\crm;

use app\common\model\BaseModel;

/**
 * 客户流失预警模型
 * Class CustomerLossWarning
 * @package app\common\model\crm
 */
class CustomerLossWarning extends BaseModel
{
    protected $name = 'customer_loss_warning';
    
    // 预警类型
    const TYPE_NO_FOLLOW = 1;       // 长期未跟进
    const TYPE_INTENTION_DOWN = 2;  // 意向下降
    const TYPE_COMPETITOR = 3;      // 竞品流失
    const TYPE_BUDGET = 4;          // 预算不足
    const TYPE_OTHER = 5;           // 其他

    // 预警等级
    const LEVEL_LOW = 1;            // 低
    const LEVEL_MEDIUM = 2;         // 中
    const LEVEL_HIGH = 3;           // 高

    // 处理状态
    const STATUS_PENDING = 0;       // 待处理
    const STATUS_HANDLED = 1;       // 已处理
    const STATUS_IGNORED = 2;       // 已忽略

    /**
     * @notes 预警类型选项
     * @return array
     */
    public static function getTypeOptions(): array
    {
        return [
            self::TYPE_NO_FOLLOW => '长期未跟进',
            self::TYPE_INTENTION_DOWN => '意向下降',
            self::TYPE_COMPETITOR => '竞品流失',
            self::TYPE_BUDGET => '预算不足',
            self::TYPE_OTHER => '其他',
        ];
    }

    /**
     * @notes 预警等级选项
     * @return array
     */
    public static function getLevelOptions(): array
    {
        return [
            self::LEVEL_LOW => '低',
            self::LEVEL_MEDIUM => '中',
            self::LEVEL_HIGH => '高',
        ];
    }

    /**
     * @notes 处理状态选项
     * @return array
     */
    public static function getStatusOptions(): array
    {
        return [
            self::STATUS_PENDING => '待处理',
            self::STATUS_HANDLED => '已处理',
            self::STATUS_IGNORED => '已忽略',
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
     * @notes 关联销售顾问
     * @return \think\model\relation\BelongsTo
     */
    public function advisor()
    {
        return $this->belongsTo(SalesAdvisor::class, 'advisor_id', 'id');
    }

    /**
     * @notes 预警类型描述获取器
     * @param $value
     * @param $data
     * @return string
     */
    public function getWarningTypeDescAttr($value, $data): string
    {
        $options = self::getTypeOptions();
        return $options[$data['warning_type']] ?? '未知';
    }

    /**
     * @notes 预警等级描述获取器
     * @param $value
     * @param $data
     * @return string
     */
    public function getWarningLevelDescAttr($value, $data): string
    {
        $options = self::getLevelOptions();
        return $options[$data['warning_level']] ?? '未知';
    }

    /**
     * @notes 处理状态描述获取器
     * @param $value
     * @param $data
     * @return string
     */
    public function getWarningStatusDescAttr($value, $data): string
    {
        $options = self::getStatusOptions();
        return $options[$data['warning_status']] ?? '未知';
    }

    /**
     * @notes 获取顾问待处理预警
     * @param int $advisorId
     * @return array
     */
    public static function getAdvisorPendingWarnings(int $advisorId): array
    {
        return self::where('advisor_id', $advisorId)
            ->where('warning_status', self::STATUS_PENDING)
            ->with(['customer'])
            ->order('warning_level desc, create_time desc')
            ->select()
            ->toArray();
    }

    /**
     * @notes 获取全部待处理预警
     * @param int $level 筛选等级，0表示全部
     * @return array
     */
    public static function getAllPendingWarnings(int $level = 0): array
    {
        $query = self::where('warning_status', self::STATUS_PENDING);
        
        if ($level > 0) {
            $query->where('warning_level', $level);
        }
        
        return $query->with(['customer', 'advisor'])
            ->order('warning_level desc, create_time desc')
            ->select()
            ->toArray();
    }

    /**
     * @notes 处理预警
     * @param int $warningId
     * @param string $remark
     * @return bool
     */
    public static function handleWarning(int $warningId, string $remark = ''): bool
    {
        return self::where('id', $warningId)->update([
            'warning_status' => self::STATUS_HANDLED,
            'handle_time' => time(),
            'handle_remark' => $remark,
            'update_time' => time(),
        ]) !== false;
    }

    /**
     * @notes 忽略预警
     * @param int $warningId
     * @param string $remark
     * @return bool
     */
    public static function ignoreWarning(int $warningId, string $remark = ''): bool
    {
        return self::where('id', $warningId)->update([
            'warning_status' => self::STATUS_IGNORED,
            'handle_time' => time(),
            'handle_remark' => $remark,
            'update_time' => time(),
        ]) !== false;
    }

    /**
     * @notes 创建预警记录
     * @param int $customerId
     * @param int $advisorId
     * @param int $warningType
     * @param int $warningLevel
     * @param string $reason
     * @param int $daysNoFollow
     * @return CustomerLossWarning|false
     */
    public static function createWarning(
        int $customerId,
        int $advisorId,
        int $warningType,
        int $warningLevel,
        string $reason = '',
        int $daysNoFollow = 0
    ) {
        // 检查是否已有同类型待处理预警
        $exists = self::where('customer_id', $customerId)
            ->where('warning_type', $warningType)
            ->where('warning_status', self::STATUS_PENDING)
            ->find();
        
        if ($exists) {
            // 更新现有预警
            $exists->warning_level = max($exists->warning_level, $warningLevel);
            $exists->warning_reason = $reason;
            $exists->days_no_follow = $daysNoFollow;
            $exists->update_time = time();
            return $exists->save() ? $exists : false;
        }
        
        return self::create([
            'customer_id' => $customerId,
            'advisor_id' => $advisorId,
            'warning_type' => $warningType,
            'warning_level' => $warningLevel,
            'warning_reason' => $reason,
            'days_no_follow' => $daysNoFollow,
            'warning_status' => self::STATUS_PENDING,
            'create_time' => time(),
            'update_time' => time(),
        ]);
    }

    /**
     * @notes 批量生成未跟进预警
     * @param int $threshold7 7天阈值产生低预警
     * @param int $threshold14 14天阈值产生中预警
     * @param int $threshold30 30天阈值产生高预警
     * @return int 生成的预警数量
     */
    public static function generateNoFollowWarnings(int $threshold7 = 7, int $threshold14 = 14, int $threshold30 = 30): int
    {
        $count = 0;
        
        // 获取流失风险客户
        $customers = Customer::getLossRiskCustomers($threshold7);
        
        foreach ($customers as $customer) {
            // 计算未跟进天数
            $lastFollowTime = $customer['last_follow_time'] ?: $customer['create_time'];
            $daysNoFollow = (int)ceil((time() - $lastFollowTime) / 86400);
            
            // 确定预警等级
            if ($daysNoFollow >= $threshold30) {
                $level = self::LEVEL_HIGH;
            } elseif ($daysNoFollow >= $threshold14) {
                $level = self::LEVEL_MEDIUM;
            } else {
                $level = self::LEVEL_LOW;
            }
            
            $reason = "客户已{$daysNoFollow}天未跟进";
            
            $result = self::createWarning(
                $customer['id'],
                $customer['advisor_id'],
                self::TYPE_NO_FOLLOW,
                $level,
                $reason,
                $daysNoFollow
            );
            
            if ($result) {
                $count++;
            }
        }
        
        return $count;
    }

    /**
     * @notes 获取预警统计
     * @param int $advisorId
     * @return array
     */
    public static function getWarningStats(int $advisorId = 0): array
    {
        $query = new static();
        if ($advisorId > 0) {
            $query = $query->where('advisor_id', $advisorId);
        }
        
        $pending = (clone $query)->where('warning_status', self::STATUS_PENDING);
        
        return [
            'total_pending' => $pending->count(),
            'high_level' => (clone $pending)->where('warning_level', self::LEVEL_HIGH)->count(),
            'medium_level' => (clone $pending)->where('warning_level', self::LEVEL_MEDIUM)->count(),
            'low_level' => (clone $pending)->where('warning_level', self::LEVEL_LOW)->count(),
            'today_handled' => (clone $query)
                ->where('warning_status', self::STATUS_HANDLED)
                ->where('handle_time', '>=', strtotime(date('Y-m-d')))
                ->count(),
        ];
    }
}
