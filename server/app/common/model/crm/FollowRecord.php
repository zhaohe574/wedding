<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 跟进记录模型
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\common\model\crm;

use app\common\model\BaseModel;
use app\common\model\auth\Admin;

/**
 * 跟进记录模型
 * Class FollowRecord
 * @package app\common\model\crm
 */
class FollowRecord extends BaseModel
{
    protected $name = 'follow_record';
    
    // 跟进方式
    const TYPE_PHONE = 1;       // 电话
    const TYPE_WECHAT = 2;      // 微信
    const TYPE_VISIT = 3;       // 到店
    const TYPE_TRIAL = 4;       // 试妆
    const TYPE_SAMPLE = 5;      // 看样片
    const TYPE_HOME = 6;        // 上门
    const TYPE_OTHER = 7;       // 其他

    // 跟进结果
    const RESULT_CONTINUE = 1;      // 继续跟进
    const RESULT_UPGRADE = 2;       // 意向提升
    const RESULT_DOWNGRADE = 3;     // 意向下降
    const RESULT_SIGNED = 4;        // 已成交
    const RESULT_LOST = 5;          // 已流失

    /**
     * @notes 跟进方式选项
     * @return array
     */
    public static function getTypeOptions(): array
    {
        return [
            self::TYPE_PHONE => '电话',
            self::TYPE_WECHAT => '微信',
            self::TYPE_VISIT => '到店',
            self::TYPE_TRIAL => '试妆',
            self::TYPE_SAMPLE => '看样片',
            self::TYPE_HOME => '上门',
            self::TYPE_OTHER => '其他',
        ];
    }

    /**
     * @notes 跟进结果选项
     * @return array
     */
    public static function getResultOptions(): array
    {
        return [
            self::RESULT_CONTINUE => '继续跟进',
            self::RESULT_UPGRADE => '意向提升',
            self::RESULT_DOWNGRADE => '意向下降',
            self::RESULT_SIGNED => '已成交',
            self::RESULT_LOST => '已流失',
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
     * @notes 关联操作管理员
     * @return \think\model\relation\BelongsTo
     */
    public function admin()
    {
        return $this->belongsTo(Admin::class, 'admin_id', 'id');
    }

    /**
     * @notes 跟进方式描述获取器
     * @param $value
     * @param $data
     * @return string
     */
    public function getFollowTypeDescAttr($value, $data): string
    {
        $options = self::getTypeOptions();
        return $options[$data['follow_type']] ?? '未知';
    }

    /**
     * @notes 跟进结果描述获取器
     * @param $value
     * @param $data
     * @return string
     */
    public function getFollowResultDescAttr($value, $data): string
    {
        $options = self::getResultOptions();
        return $options[$data['follow_result']] ?? '未知';
    }

    /**
     * @notes 附件获取器
     * @param $value
     * @return array
     */
    public function getAttachmentsAttr($value): array
    {
        if (empty($value)) {
            return [];
        }
        return is_array($value) ? $value : json_decode($value, true) ?: [];
    }

    /**
     * @notes 附件设置器
     * @param $value
     * @return string
     */
    public function setAttachmentsAttr($value): string
    {
        return is_array($value) ? json_encode($value, JSON_UNESCAPED_UNICODE) : (string)$value;
    }

    /**
     * @notes 获取客户跟进记录
     * @param int $customerId
     * @param int $limit
     * @return array
     */
    public static function getCustomerRecords(int $customerId, int $limit = 20): array
    {
        return self::where('customer_id', $customerId)
            ->with(['advisor', 'admin'])
            ->order('create_time desc')
            ->limit($limit)
            ->select()
            ->toArray();
    }

    /**
     * @notes 获取顾问今日跟进统计
     * @param int $advisorId
     * @return array
     */
    public static function getAdvisorTodayStats(int $advisorId): array
    {
        $todayStart = strtotime(date('Y-m-d'));
        $todayEnd = $todayStart + 86399;
        
        $records = self::where('advisor_id', $advisorId)
            ->whereBetween('create_time', [$todayStart, $todayEnd])
            ->select();
        
        $stats = [
            'total_count' => $records->count(),
            'total_duration' => 0,
            'by_type' => [],
            'by_result' => [],
        ];
        
        $typeOptions = self::getTypeOptions();
        $resultOptions = self::getResultOptions();
        
        foreach ($typeOptions as $key => $label) {
            $stats['by_type'][$key] = ['label' => $label, 'count' => 0];
        }
        
        foreach ($resultOptions as $key => $label) {
            $stats['by_result'][$key] = ['label' => $label, 'count' => 0];
        }
        
        foreach ($records as $record) {
            $stats['total_duration'] += $record->duration;
            
            if (isset($stats['by_type'][$record->follow_type])) {
                $stats['by_type'][$record->follow_type]['count']++;
            }
            
            if (isset($stats['by_result'][$record->follow_result])) {
                $stats['by_result'][$record->follow_result]['count']++;
            }
        }
        
        return $stats;
    }

    /**
     * @notes 创建跟进记录并更新客户信息
     * @param array $data
     * @return FollowRecord|false
     */
    public static function createRecord(array $data)
    {
        $data['create_time'] = $data['create_time'] ?? time();
        
        $record = self::create($data);
        
        if (!$record) {
            return false;
        }
        
        // 更新客户跟进信息
        Customer::updateFollowInfo(
            $data['customer_id'],
            $data['intention_after'] ?? '',
            $data['next_follow_time'] ?? 0
        );
        
        // 根据跟进结果更新客户状态
        if (!empty($data['follow_result'])) {
            if ($data['follow_result'] == self::RESULT_SIGNED) {
                Customer::markAsSigned($data['customer_id']);
                // 更新顾问成交统计
                if (!empty($data['advisor_id'])) {
                    SalesAdvisor::updateOrderStats($data['advisor_id'], 0);
                    SalesAdvisor::updateConversionRate($data['advisor_id']);
                }
            } elseif ($data['follow_result'] == self::RESULT_LOST) {
                Customer::markAsLost($data['customer_id'], '跟进标记流失');
            }
        }
        
        return $record;
    }

    /**
     * @notes 获取重要跟进记录
     * @param int $customerId
     * @return array
     */
    public static function getImportantRecords(int $customerId): array
    {
        return self::where('customer_id', $customerId)
            ->where('is_important', 1)
            ->order('create_time desc')
            ->select()
            ->toArray();
    }

    /**
     * @notes 获取指定时间段的跟进统计
     * @param int $advisorId
     * @param string $startDate
     * @param string $endDate
     * @return array
     */
    public static function getPeriodStats(int $advisorId, string $startDate, string $endDate): array
    {
        $startTime = strtotime($startDate);
        $endTime = strtotime($endDate . ' 23:59:59');
        
        $query = self::whereBetween('create_time', [$startTime, $endTime]);
        
        if ($advisorId > 0) {
            $query->where('advisor_id', $advisorId);
        }
        
        return [
            'total_count' => $query->count(),
            'total_duration' => $query->sum('duration'),
            'unique_customers' => $query->group('customer_id')->count(),
            'signed_count' => (clone $query)->where('follow_result', self::RESULT_SIGNED)->count(),
            'lost_count' => (clone $query)->where('follow_result', self::RESULT_LOST)->count(),
        ];
    }
}
