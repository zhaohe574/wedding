<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 客户模型
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\common\model\crm;

use app\common\model\BaseModel;
use app\common\model\service\ServiceCategory;
use app\common\model\staff\Staff;
use app\common\model\user\User;
use app\common\model\order\Order;
use think\model\concern\SoftDelete;

/**
 * 客户模型
 * Class Customer
 * @package app\common\model\crm
 */
class Customer extends BaseModel
{
    use SoftDelete;

    protected $name = 'customer';
    protected $deleteTime = 'delete_time';

    // 意向等级
    const INTENTION_A = 'A';    // 高意向
    const INTENTION_B = 'B';    // 中意向
    const INTENTION_C = 'C';    // 低意向
    const INTENTION_D = 'D';    // 待跟进

    // 客户状态
    const STATUS_NEW = 1;           // 新客户
    const STATUS_FOLLOWING = 2;     // 跟进中
    const STATUS_SIGNED = 3;        // 已签单
    const STATUS_LOST = 4;          // 已流失
    const STATUS_COMPLETED = 5;     // 已完成

    // 来源渠道
    const SOURCE_MINIAPP = 1;       // 小程序
    const SOURCE_OFFLINE = 3;       // 线下
    const SOURCE_REFERRAL = 4;      // 转介绍
    const SOURCE_ADS = 5;           // 广告
    const SOURCE_OTHER = 6;         // 其他

    // 性别
    const GENDER_UNKNOWN = 0;       // 未知
    const GENDER_MALE = 1;          // 男
    const GENDER_FEMALE = 2;        // 女

    /**
     * @notes 统一解析时间值，兼容时间戳和日期字符串
     * @param mixed $value
     * @return int
     */
    public static function parseTimestampValue($value): int
    {
        if ($value === null || $value === '' || $value === false) {
            return 0;
        }

        if (is_numeric($value)) {
            return (int)$value;
        }

        $timestamp = strtotime((string)$value);
        return $timestamp === false ? 0 : $timestamp;
    }

    /**
     * @notes 意向等级选项
     * @return array
     */
    public static function getIntentionOptions(): array
    {
        return [
            self::INTENTION_A => '高意向',
            self::INTENTION_B => '中意向',
            self::INTENTION_C => '低意向',
            self::INTENTION_D => '待跟进',
        ];
    }

    /**
     * @notes 客户状态选项
     * @return array
     */
    public static function getStatusOptions(): array
    {
        return [
            self::STATUS_NEW => '新客户',
            self::STATUS_FOLLOWING => '跟进中',
            self::STATUS_SIGNED => '已签单',
            self::STATUS_LOST => '已流失',
            self::STATUS_COMPLETED => '已完成',
        ];
    }

    /**
     * @notes 来源渠道选项
     * @return array
     */
    public static function getSourceOptions(): array
    {
        return [
            self::SOURCE_MINIAPP => '小程序',
            self::SOURCE_OFFLINE => '线下',
            self::SOURCE_REFERRAL => '转介绍',
            self::SOURCE_ADS => '广告',
            self::SOURCE_OTHER => '其他',
        ];
    }

    /**
     * @notes 将来源详情原始标识（如 consult:staff_detail#1）转为人类可读文案
     * @param string|null $sourceDetail
     * @return string
     */
    public static function formatSourceDetailText(?string $sourceDetail): string
    {
        $raw = trim((string)$sourceDetail);
        if ($raw === '') {
            return '';
        }

        if (!preg_match('/^consult:([a-z_]+)(?:#(\d+))?$/', $raw, $matches)) {
            return $raw;
        }

        $scene = (string)($matches[1] ?? '');
        $targetId = isset($matches[2]) ? (int)$matches[2] : 0;

        $sceneLabelMap = [
            'home' => '首页咨询',
            'staff_detail' => '服务人员详情咨询',
            'staff' => '服务人员详情咨询',
            'order_detail' => '订单详情咨询',
            'order' => '订单详情咨询',
            'aftersale' => '售后咨询',
            'package_detail' => '套餐详情咨询',
            'intention_form' => '小程序预约回电留资',
        ];
        $sceneLabel = $sceneLabelMap[$scene] ?? '小程序咨询';

        if ($targetId <= 0) {
            return $sceneLabel;
        }

        $targetLabel = '';
        try {
            if (in_array($scene, ['staff_detail', 'staff'], true)) {
                $name = trim((string)Staff::where('id', $targetId)->value('name'));
                $targetLabel = $name !== '' ? "{$name}（#{$targetId}）" : "服务人员 #{$targetId}";
            } elseif (in_array($scene, ['order_detail', 'order', 'aftersale'], true)) {
                $sn = trim((string)Order::where('id', $targetId)->value('order_sn'));
                $targetLabel = $sn !== '' ? "订单 {$sn}" : "订单 #{$targetId}";
            } elseif (in_array($scene, ['package_detail', 'home'], true)) {
                $catName = trim((string)ServiceCategory::where('id', $targetId)->value('name'));
                $targetLabel = $catName !== '' ? $catName : "分类 #{$targetId}";
            } else {
                $targetLabel = "#{$targetId}";
            }
        } catch (\Throwable $e) {
            $targetLabel = "#{$targetId}";
        }

        return $targetLabel !== '' ? "{$sceneLabel} · {$targetLabel}" : $sceneLabel;
    }

    /**
     * @notes 性别选项
     * @return array
     */
    public static function getGenderOptions(): array
    {
        return [
            self::GENDER_UNKNOWN => '未知',
            self::GENDER_MALE => '男',
            self::GENDER_FEMALE => '女',
        ];
    }

    /**
     * @notes 关联用户
     * @return \think\model\relation\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
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
     * @notes 关联跟进记录
     * @return \think\model\relation\HasMany
     */
    public function followRecords()
    {
        return $this->hasMany(FollowRecord::class, 'customer_id', 'id');
    }

    /**
     * @notes 关联分配日志
     * @return \think\model\relation\HasMany
     */
    public function assignLogs()
    {
        return $this->hasMany(CustomerAssignLog::class, 'customer_id', 'id');
    }

    /**
     * @notes 关联流失预警
     * @return \think\model\relation\HasMany
     */
    public function lossWarnings()
    {
        return $this->hasMany(CustomerLossWarning::class, 'customer_id', 'id');
    }

    /**
     * @notes 意向等级描述获取器
     * @param $value
     * @param $data
     * @return string
     */
    public function getIntentionLevelDescAttr($value, $data): string
    {
        $options = self::getIntentionOptions();
        return $options[$data['intention_level']] ?? '未知';
    }

    /**
     * @notes 客户状态描述获取器
     * @param $value
     * @param $data
     * @return string
     */
    public function getCustomerStatusDescAttr($value, $data): string
    {
        $options = self::getStatusOptions();
        return $options[$data['customer_status']] ?? '未知';
    }

    /**
     * @notes 来源渠道描述获取器
     * @param $value
     * @param $data
     * @return string
     */
    public function getSourceChannelDescAttr($value, $data): string
    {
        $options = self::getSourceOptions();
        return $options[$data['source_channel']] ?? '未知';
    }

    /**
     * @notes 性别描述获取器
     * @param $value
     * @param $data
     * @return string
     */
    public function getGenderDescAttr($value, $data): string
    {
        $options = self::getGenderOptions();
        return $options[$data['gender']] ?? '未知';
    }

    /**
     * @notes 服务需求获取器(JSON转数组)
     * @param $value
     * @return array
     */
    public function getServiceNeedsAttr($value): array
    {
        if (empty($value)) {
            return [];
        }
        return is_array($value) ? $value : (json_decode($value, true) ?: []);
    }

    /**
     * @notes 服务需求设置器
     * @param $value
     * @return string
     */
    public function setServiceNeedsAttr($value): string
    {
        return is_array($value) ? json_encode($value, JSON_UNESCAPED_UNICODE) : (string)$value;
    }

    /**
     * @notes 标签获取器
     * @param $value
     * @return array
     */
    public function getTagsAttr($value): array
    {
        if (empty($value)) {
            return [];
        }
        return is_array($value) ? $value : (json_decode($value, true) ?: []);
    }

    /**
     * @notes 标签设置器
     * @param $value
     * @return string
     */
    public function setTagsAttr($value): string
    {
        return is_array($value) ? json_encode($value, JSON_UNESCAPED_UNICODE) : (string)$value;
    }

    /**
     * @notes 距婚期天数获取器
     * @param $value
     * @param $data
     * @return int|null
     */
    public function getDaysToWeddingAttr($value, $data): ?int
    {
        if (empty($data['wedding_date'])) {
            return null;
        }
        $weddingTime = strtotime($data['wedding_date']);
        $now = strtotime(date('Y-m-d'));
        return (int)ceil(($weddingTime - $now) / 86400);
    }

    /**
     * @notes 未跟进天数获取器
     * @param $value
     * @param $data
     * @return int
     */
    public function getDaysNoFollowAttr($value, $data): int
    {
        $lastFollowTime = self::parseTimestampValue($data['last_follow_time'] ?? null);
        $createTime = self::parseTimestampValue($data['create_time'] ?? null);
        $baseTime = $lastFollowTime > 0 ? $lastFollowTime : $createTime;

        if ($baseTime <= 0) {
            return 0;
        }

        return (int)floor(max(0, time() - $baseTime) / 86400);
    }

    /**
     * @notes 是否需要跟进
     * @return bool
     */
    public function needsFollowUp(): bool
    {
        // 已签单、已流失、已完成的不需要跟进
        if (in_array($this->customer_status, [self::STATUS_SIGNED, self::STATUS_LOST, self::STATUS_COMPLETED])) {
            return false;
        }
        
        // 超过下次跟进时间
        if ($this->next_follow_time > 0 && $this->next_follow_time < time()) {
            return true;
        }
        
        return false;
    }

    /**
     * @notes 获取待跟进客户列表
     * @param int $advisorId
     * @return array
     */
    public static function getPendingFollowCustomers(int $advisorId): array
    {
        return self::where('advisor_id', $advisorId)
            ->whereIn('customer_status', [self::STATUS_NEW, self::STATUS_FOLLOWING])
            ->where(function ($query) {
                $query->where('next_follow_time', '<=', time())
                    ->whereOr('next_follow_time', 0);
            })
            ->order('next_follow_time asc, intention_level asc, create_time asc')
            ->select()
            ->toArray();
    }

    /**
     * @notes 获取按意向等级统计
     * @param int $advisorId 顾问ID，0表示全部
     * @return array
     */
    public static function getIntentionStats(int $advisorId = 0): array
    {
        $query = self::whereIn('customer_status', [self::STATUS_NEW, self::STATUS_FOLLOWING]);
        
        if ($advisorId > 0) {
            $query->where('advisor_id', $advisorId);
        }
        
        $stats = $query->field('intention_level, COUNT(*) as count')
            ->group('intention_level')
            ->select()
            ->toArray();
        
        $result = [
            'A' => 0,
            'B' => 0,
            'C' => 0,
            'D' => 0,
        ];
        
        foreach ($stats as $item) {
            if (isset($result[$item['intention_level']])) {
                $result[$item['intention_level']] = $item['count'];
            }
        }
        
        return $result;
    }

    /**
     * @notes 获取今日新增客户数
     * @param int $advisorId
     * @return int
     */
    public static function getTodayNewCount(int $advisorId = 0): int
    {
        $todayStart = strtotime(date('Y-m-d'));
        
        $query = self::where('create_time', '>=', $todayStart);
        
        if ($advisorId > 0) {
            $query->where('advisor_id', $advisorId);
        }
        
        return $query->count();
    }

    /**
     * @notes 更新跟进信息
     * @param int $customerId
     * @param string $intentionLevel
     * @param int $nextFollowTime
     * @param bool $reactivateLost 是否将已流失客户重新激活为跟进中
     * @return bool
     */
    public static function updateFollowInfo(
        int $customerId,
        string $intentionLevel = '',
        int $nextFollowTime = 0,
        bool $reactivateLost = false
    ): bool {
        $now = time();
        $updateData = [
            'last_follow_time' => $now,
            'follow_count' => \think\facade\Db::raw('follow_count + 1'),
            'update_time' => $now,
        ];

        $customer = self::find($customerId);
        if (!$customer) {
            return false;
        }

        if ((int)$customer->first_contact_time <= 0) {
            $updateData['first_contact_time'] = $now;
        }

        $statusChangedToActive = false;
        $currentStatus = (int)$customer->customer_status;
        if ($currentStatus === self::STATUS_NEW) {
            $updateData['customer_status'] = self::STATUS_FOLLOWING;
        } elseif ($reactivateLost && $currentStatus === self::STATUS_LOST) {
            $updateData['customer_status'] = self::STATUS_FOLLOWING;
            $updateData['loss_reason'] = '';
            $updateData['loss_time'] = 0;
            $statusChangedToActive = true;
        }

        if (!empty($intentionLevel)) {
            $updateData['intention_level'] = $intentionLevel;
        }

        if ($nextFollowTime > 0) {
            $updateData['next_follow_time'] = $nextFollowTime;
        }

        $ok = self::where('id', $customerId)->update($updateData) !== false;
        if ($ok && $statusChangedToActive && (int)$customer->advisor_id > 0) {
            SalesAdvisor::recalculateCustomerCount((int)$customer->advisor_id);
        }

        return $ok;
    }

    /**
     * @notes 标记客户流失
     * @param int $customerId
     * @param string $reason
     * @return bool
     */
    public static function markAsLost(int $customerId, string $reason = ''): bool
    {
        $customer = self::find($customerId);
        if (!$customer) {
            return false;
        }

        $ok = self::where('id', $customerId)->update([
            'customer_status' => self::STATUS_LOST,
            'loss_reason' => $reason,
            'loss_time' => time(),
            'update_time' => time(),
        ]) !== false;

        if ($ok) {
            CustomerLossWarning::resolveAllPendingWarningsByCustomer($customerId, '客户已标记流失，自动解除');
            if ((int)$customer->advisor_id > 0) {
                SalesAdvisor::recalculateCustomerCount((int)$customer->advisor_id);
            }
        }

        return $ok;
    }

    /**
     * @notes 标记客户成交
     * @param int $customerId
     * @param int $orderId
     * @param float $amount
     * @return bool
     */
    public static function markAsSigned(int $customerId, int $orderId = 0, float $amount = 0): bool
    {
        $customer = self::find($customerId);
        if (!$customer) {
            return false;
        }

        $updateData = [
            'customer_status' => self::STATUS_SIGNED,
            'update_time' => time(),
        ];

        $userId = (int)$customer->user_id;
        if ($orderId > 0 && $userId > 0) {
            $summaryMap = self::getRealOrderSummaryMap([$userId]);
            if (isset($summaryMap[$userId])) {
                $updateData['order_count'] = (int)$summaryMap[$userId]['order_count'];
                $updateData['total_amount'] = (float)$summaryMap[$userId]['total_amount'];
            } else {
                $updateData['order_count'] = \think\facade\Db::raw('order_count + 1');
                $updateData['total_amount'] = \think\facade\Db::raw('total_amount + ' . max(0, $amount));
            }
        } elseif ($orderId > 0) {
            $updateData['order_count'] = \think\facade\Db::raw('order_count + 1');
            $updateData['total_amount'] = \think\facade\Db::raw('total_amount + ' . max(0, $amount));
        }

        $ok = self::where('id', $customerId)->update($updateData) !== false;
        if ($ok) {
            CustomerLossWarning::resolveAllPendingWarningsByCustomer($customerId, '客户已签单，自动解除');
            if ((int)$customer->advisor_id > 0) {
                SalesAdvisor::recalculateCustomerCount((int)$customer->advisor_id);
            }
        }

        return $ok;
    }

    /**
     * @notes 批量聚合真实订单成交统计（按 user_id）
     * @param array $userIds
     * @return array
     */
    public static function getRealOrderSummaryMap(array $userIds): array
    {
        $userIds = array_values(array_unique(array_filter(array_map('intval', $userIds))));
        if (empty($userIds)) {
            return [];
        }

        $rows = Order::whereIn('user_id', $userIds)
            ->where(function ($query) {
                $query->whereIn('pay_status', [Order::PAY_STATUS_PAID, Order::PAY_STATUS_PARTIAL_REFUND])
                    ->whereOr('paid_amount', '>', 0)
                    ->whereOr('deposit_paid', 1);
            })
            ->whereNotIn('order_status', [Order::STATUS_CANCELLED, Order::STATUS_REFUNDED, Order::STATUS_USER_DELETED])
            ->field('user_id, COUNT(*) as order_count, COALESCE(SUM(paid_amount), 0) as total_amount')
            ->group('user_id')
            ->select()
            ->toArray();

        $map = [];
        foreach ($rows as $row) {
            $map[(int)$row['user_id']] = [
                'order_count' => (int)($row['order_count'] ?? 0),
                'total_amount' => round((float)($row['total_amount'] ?? 0), 2),
            ];
        }
        return $map;
    }

    /**
     * @notes 分配顾问
     * @param int $customerId
     * @param int $advisorId
     * @param int $adminId 操作管理员ID
     * @param int $assignType 分配类型
     * @param string $reason 分配原因
     * @return bool
     */
    public static function assignAdvisor(int $customerId, int $advisorId, int $adminId = 0, int $assignType = 1, string $reason = ''): bool
    {
        $customer = self::find($customerId);
        if (!$customer) {
            return false;
        }
        
        $oldAdvisorId = (int)$customer->advisor_id;
        if ($oldAdvisorId === $advisorId) {
            return true;
        }
        
        // 更新客户的顾问
        $result = self::where('id', $customerId)->update([
            'advisor_id' => $advisorId,
            'assign_time' => time(),
            'update_time' => time(),
        ]);
        
        if ($result === false) {
            return false;
        }
        
        // 同步待处理流失预警归属
        if ($advisorId > 0) {
            CustomerLossWarning::syncAdvisorByCustomer($customerId, $advisorId);
        }
        
        // 精确重算原顾问与新顾问的活跃客户负载
        if ($oldAdvisorId > 0) {
            SalesAdvisor::recalculateCustomerCount($oldAdvisorId);
        }
        if ($advisorId > 0) {
            SalesAdvisor::recalculateCustomerCount($advisorId);
        }
        
        // 记录分配日志
        CustomerAssignLog::create([
            'customer_id' => $customerId,
            'from_advisor_id' => $oldAdvisorId,
            'to_advisor_id' => $advisorId,
            'assign_type' => $assignType,
            'assign_reason' => $reason,
            'admin_id' => $adminId,
            'create_time' => time(),
        ]);
        
        return true;
    }

    /**
     * @notes 根据手机号查找客户
     * @param string $mobile
     * @return Customer|null
     */
    public static function findByMobile(string $mobile): ?Customer
    {
        return self::where('customer_mobile', $mobile)->find();
    }

    /**
     * @notes 根据用户ID查找客户
     * @param int $userId
     * @return Customer|null
     */
    public static function findByUserId(int $userId): ?Customer
    {
        return self::where('user_id', $userId)->find();
    }

    /**
     * @notes 获取流失风险客户
     * @param int $daysNoFollow 未跟进天数阈值
     * @return array
     */
    public static function getLossRiskCustomers(int $daysNoFollow = 7): array
    {
        $threshold = time() - ($daysNoFollow * 86400);
        
        return self::whereIn('customer_status', [self::STATUS_NEW, self::STATUS_FOLLOWING])
            ->where(function ($query) use ($threshold) {
                $query->where('last_follow_time', '<', $threshold)
                    ->whereOr(function ($q) use ($threshold) {
                        $q->where('last_follow_time', 0)
                            ->where('create_time', '<', $threshold);
                    });
            })
            ->order('last_follow_time asc')
            ->select()
            ->toArray();
    }

    /**
     * @notes 获取客户统计概览
     * @param int $advisorId
     * @return array
     */
    public static function getCustomerOverview(int $advisorId = 0): array
    {
        if ($advisorId < 0) {
            return [
                'total' => 0,
                'new' => 0,
                'following' => 0,
                'signed' => 0,
                'lost' => 0,
                'completed' => 0,
                'today_new' => 0,
                'unassigned' => 0,
                'today_follow' => 0,
                'overdue_follow' => 0,
                'intention_a' => 0,
                'month_signed' => 0,
                'intention_stats' => ['A' => 0, 'B' => 0, 'C' => 0, 'D' => 0],
            ];
        }

        $now = time();
        $todayStart = strtotime(date('Y-m-d'));
        $todayEnd = $todayStart + 86399;
        $monthStart = strtotime(date('Y-m-01 00:00:00'));

        $baseQuery = new static();
        if ($advisorId > 0) {
            $baseQuery = $baseQuery->where('advisor_id', $advisorId);
        }

        $activeQuery = (clone $baseQuery)->whereIn('customer_status', [self::STATUS_NEW, self::STATUS_FOLLOWING]);

        return [
            'total' => (clone $baseQuery)->count(),
            'new' => (clone $baseQuery)->where('customer_status', self::STATUS_NEW)->count(),
            'following' => (clone $baseQuery)->where('customer_status', self::STATUS_FOLLOWING)->count(),
            'signed' => (clone $baseQuery)->where('customer_status', self::STATUS_SIGNED)->count(),
            'lost' => (clone $baseQuery)->where('customer_status', self::STATUS_LOST)->count(),
            'completed' => (clone $baseQuery)->where('customer_status', self::STATUS_COMPLETED)->count(),
            'today_new' => self::getTodayNewCount($advisorId),
            'unassigned' => (clone $activeQuery)->where('advisor_id', 0)->count(),
            'today_follow' => (clone $activeQuery)->whereBetween('next_follow_time', [$todayStart, $todayEnd])->count(),
            'overdue_follow' => (clone $activeQuery)->where('next_follow_time', '>', 0)->where('next_follow_time', '<', $now)->count(),
            'intention_a' => (clone $activeQuery)->where('intention_level', self::INTENTION_A)->count(),
            'month_signed' => (clone $baseQuery)
                ->whereIn('customer_status', [self::STATUS_SIGNED, self::STATUS_COMPLETED])
                ->where('update_time', '>=', $monthStart)
                ->count(),
            'intention_stats' => self::getIntentionStats($advisorId),
        ];
    }
}
