<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 销售顾问模型
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\common\model\crm;

use app\common\model\BaseModel;
use app\common\model\auth\Admin;
use app\common\model\order\Order;
use think\model\concern\SoftDelete;

/**
 * 销售顾问模型
 * Class SalesAdvisor
 * @package app\common\model\crm
 */
class SalesAdvisor extends BaseModel
{
    use SoftDelete;

    protected $name = 'sales_advisor';
    protected $deleteTime = 'delete_time';

    // 状态
    const STATUS_LEAVE = 0;     // 离职
    const STATUS_NORMAL = 1;    // 正常
    const STATUS_VACATION = 2;  // 休假

    /**
     * @notes 状态选项
     * @return array
     */
    public static function getStatusOptions(): array
    {
        return [
            self::STATUS_LEAVE => '离职',
            self::STATUS_NORMAL => '正常',
            self::STATUS_VACATION => '休假',
        ];
    }

    /**
     * @notes 关联管理员
     * @return \think\model\relation\BelongsTo
     */
    public function admin()
    {
        return $this->belongsTo(Admin::class, 'admin_id', 'id');
    }

    /**
     * @notes 关联客户
     * @return \think\model\relation\HasMany
     */
    public function customers()
    {
        return $this->hasMany(Customer::class, 'advisor_id', 'id');
    }

    /**
     * @notes 关联跟进记录
     * @return \think\model\relation\HasMany
     */
    public function followRecords()
    {
        return $this->hasMany(FollowRecord::class, 'advisor_id', 'id');
    }

    /**
     * @notes 状态描述获取器
     * @param $value
     * @param $data
     * @return string
     */
    public function getStatusDescAttr($value, $data): string
    {
        $options = self::getStatusOptions();
        return $options[$data['status']] ?? '未知';
    }

    /**
     * @notes 头像获取器
     * @param $value
     * @return string
     */
    public function getAvatarAttr($value): string
    {
        return $this->getImageAttr($value);
    }

    /**
     * @notes 头像设置器
     * @param $value
     * @return string
     */
    public function setAvatarAttr($value): string
    {
        return $this->setImageAttr($value);
    }

    /**
     * @notes 负责区域获取器(JSON转数组)
     * @param $value
     * @return array
     */
    public function getAreasAttr($value): array
    {
        if (empty($value)) {
            return [];
        }
        return is_array($value) ? $value : (json_decode($value, true) ?: []);
    }

    /**
     * @notes 负责区域设置器
     * @param $value
     * @return string
     */
    public function setAreasAttr($value): string
    {
        return is_array($value) ? json_encode($value, JSON_UNESCAPED_UNICODE) : (string)$value;
    }

    /**
     * @notes 擅长服务类型获取器
     * @param $value
     * @return array
     */
    public function getSpecialtiesAttr($value): array
    {
        if (empty($value)) {
            return [];
        }
        return is_array($value) ? $value : (json_decode($value, true) ?: []);
    }

    /**
     * @notes 擅长服务类型设置器
     * @param $value
     * @return string
     */
    public function setSpecialtiesAttr($value): string
    {
        return is_array($value) ? json_encode($value, JSON_UNESCAPED_UNICODE) : (string)$value;
    }

    /**
     * @notes 是否可分配新客户
     * @return bool
     */
    public function canAssignCustomer(): bool
    {
        if ($this->status != self::STATUS_NORMAL) {
            return false;
        }
        return $this->current_customer_count < $this->max_customer_count;
    }

    /**
     * @notes 是否可用于用户咨询承接
     * @return bool
     */
    public function canServeConsultation(): bool
    {
        return (int) $this->status === self::STATUS_NORMAL;
    }

    /**
     * @notes 获取可用顾问列表
     * @param string $area 区域筛选
     * @param string $specialty 专长筛选
     * @return array
     */
    public static function getAvailableAdvisors(string $area = '', string $specialty = ''): array
    {
        $query = self::where('status', self::STATUS_NORMAL)
            ->whereRaw('current_customer_count < max_customer_count');
        
        if (!empty($area)) {
            $query->whereLike('areas', '%"' . addslashes($area) . '"%');
        }
        
        if (!empty($specialty)) {
            $query->whereLike('specialties', '%"' . addslashes($specialty) . '"%');
        }
        
        return $query->order('current_customer_count asc, sort desc, conversion_rate desc')
            ->select()
            ->toArray();
    }

    /**
     * @notes 自动分配顾问(轮询+负载均衡)
     * @param string $area 区域
     * @param string $specialty 专长
     * @return int|null 返回顾问ID或null
     */
    public static function autoAssign(string $area = '', string $specialty = ''): ?int
    {
        $advisors = self::getAvailableAdvisors($area, $specialty);
        
        if (empty($advisors)) {
            // 无匹配条件的顾问时，尝试获取任意可用顾问
            $advisors = self::getAvailableAdvisors();
        }
        
        if (empty($advisors)) {
            return null;
        }
        
        // 返回客户数最少的顾问
        return (int)$advisors[0]['id'];
    }

    /**
     * @notes 增加客户数
     * @param int $advisorId
     * @return bool
     */
    public static function incrementCustomerCount(int $advisorId): bool
    {
        return self::where('id', $advisorId)->inc('current_customer_count')->update() !== false;
    }

    /**
     * @notes 减少客户数
     * @param int $advisorId
     * @return bool
     */
    public static function decrementCustomerCount(int $advisorId): bool
    {
        return self::where('id', $advisorId)
            ->where('current_customer_count', '>', 0)
            ->dec('current_customer_count')
            ->update() !== false;
    }

    /**
     * @notes 精确重算顾问当前活跃客户数（新客户 + 跟进中）
     * @param int $advisorId
     * @return int
     */
    public static function recalculateCustomerCount(int $advisorId): int
    {
        if ($advisorId <= 0) {
            return 0;
        }

        $activeCount = Customer::where('advisor_id', $advisorId)
            ->whereIn('customer_status', [Customer::STATUS_NEW, Customer::STATUS_FOLLOWING])
            ->count();

        self::where('id', $advisorId)->update([
            'current_customer_count' => $activeCount,
            'update_time' => time(),
        ]);

        self::updateConversionRate($advisorId);

        return (int)$activeCount;
    }

    /**
     * @notes 更新成交统计
     * @param int $advisorId
     * @param float $orderAmount
     * @return bool
     */
    public static function updateOrderStats(int $advisorId, float $orderAmount): bool
    {
        return self::where('id', $advisorId)->update([
            'total_order_count' => \think\facade\Db::raw('total_order_count + 1'),
            'total_order_amount' => \think\facade\Db::raw("total_order_amount + {$orderAmount}"),
            'update_time' => time(),
        ]) !== false;
    }

    /**
     * @notes 按真实订单口径重算顾问成交业绩与转化率
     * @param int $advisorId
     * @return bool
     */
    public static function recalculateOrderStats(int $advisorId): bool
    {
        if ($advisorId <= 0) {
            return false;
        }

        $customers = Customer::where('advisor_id', $advisorId)
            ->field('id,user_id,order_count,total_amount')
            ->select()
            ->toArray();

        $userIds = array_values(array_unique(array_filter(array_map(
            static fn ($row) => (int)($row['user_id'] ?? 0),
            $customers
        ))));

        $orderMap = Customer::getRealOrderSummaryMap($userIds);

        $totalCount = 0;
        $totalAmount = 0.0;
        foreach ($customers as $customer) {
            $uid = (int)($customer['user_id'] ?? 0);
            if ($uid > 0 && isset($orderMap[$uid])) {
                $totalCount += (int)$orderMap[$uid]['order_count'];
                $totalAmount += (float)$orderMap[$uid]['total_amount'];
            } else {
                $totalCount += (int)($customer['order_count'] ?? 0);
                $totalAmount += (float)($customer['total_amount'] ?? 0);
            }
        }

        $updated = self::where('id', $advisorId)->update([
            'total_order_count' => $totalCount,
            'total_order_amount' => round($totalAmount, 2),
            'update_time' => time(),
        ]) !== false;

        self::updateConversionRate($advisorId);
        return $updated;
    }

    /**
     * @notes 更新转化率（已签单与已完成 / 名下总客户数）
     * @param int $advisorId
     * @return bool
     */
    public static function updateConversionRate(int $advisorId): bool
    {
        $advisor = self::find($advisorId);
        if (!$advisor) {
            return false;
        }

        $totalAssigned = Customer::where('advisor_id', $advisorId)->count();
        $signedCount = Customer::where('advisor_id', $advisorId)
            ->whereIn('customer_status', [Customer::STATUS_SIGNED, Customer::STATUS_COMPLETED])
            ->count();

        $rate = $totalAssigned > 0 ? round($signedCount / $totalAssigned * 100, 2) : 0;

        return self::where('id', $advisorId)->update([
            'conversion_rate' => $rate,
            'update_time' => time(),
        ]) !== false;
    }

    /**
     * @notes 获取顾问业绩统计
     * @param int $advisorId
     * @param string $startDate
     * @param string $endDate
     * @return array
     */
    public static function getPerformanceStats(int $advisorId, string $startDate = '', string $endDate = ''): array
    {
        $advisor = self::find($advisorId);
        if (!$advisor) {
            return [];
        }

        // 基础统计
        $stats = [
            'advisor_name' => $advisor->advisor_name,
            'current_customer_count' => $advisor->current_customer_count,
            'max_customer_count' => $advisor->max_customer_count,
            'total_order_count' => $advisor->total_order_count,
            'total_order_amount' => $advisor->total_order_amount,
            'conversion_rate' => $advisor->conversion_rate,
        ];

        // 时间段内跟进统计
        $followQuery = FollowRecord::where('advisor_id', $advisorId);
        if ($startDate) {
            $followQuery->where('create_time', '>=', strtotime($startDate));
        }
        if ($endDate) {
            $followQuery->where('create_time', '<=', strtotime($endDate . ' 23:59:59'));
        }

        $stats['follow_count'] = $followQuery->count();
        $stats['follow_duration'] = $followQuery->sum('duration');

        return $stats;
    }
}
