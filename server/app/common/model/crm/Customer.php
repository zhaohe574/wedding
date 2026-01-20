<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 客户模型
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\common\model\crm;

use app\common\model\BaseModel;
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
    const SOURCE_H5 = 2;            // H5
    const SOURCE_OFFLINE = 3;       // 线下
    const SOURCE_REFERRAL = 4;      // 转介绍
    const SOURCE_ADS = 5;           // 广告
    const SOURCE_OTHER = 6;         // 其他

    // 性别
    const GENDER_UNKNOWN = 0;       // 未知
    const GENDER_MALE = 1;          // 男
    const GENDER_FEMALE = 2;        // 女

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
            self::SOURCE_H5 => 'H5',
            self::SOURCE_OFFLINE => '线下',
            self::SOURCE_REFERRAL => '转介绍',
            self::SOURCE_ADS => '广告',
            self::SOURCE_OTHER => '其他',
        ];
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
        return is_array($value) ? $value : json_decode($value, true) ?: [];
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
        return is_array($value) ? $value : json_decode($value, true) ?: [];
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
        if (empty($data['last_follow_time'])) {
            // 从创建时间算起
            return (int)ceil((time() - $data['create_time']) / 86400);
        }
        return (int)ceil((time() - $data['last_follow_time']) / 86400);
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
     * @return bool
     */
    public static function updateFollowInfo(int $customerId, string $intentionLevel = '', int $nextFollowTime = 0): bool
    {
        $updateData = [
            'last_follow_time' => time(),
            'follow_count' => \think\facade\Db::raw('follow_count + 1'),
            'update_time' => time(),
        ];
        
        // 更新状态为跟进中
        $customer = self::find($customerId);
        if ($customer && $customer->customer_status == self::STATUS_NEW) {
            $updateData['customer_status'] = self::STATUS_FOLLOWING;
        }
        
        if (!empty($intentionLevel)) {
            $updateData['intention_level'] = $intentionLevel;
        }
        
        if ($nextFollowTime > 0) {
            $updateData['next_follow_time'] = $nextFollowTime;
        }
        
        return self::where('id', $customerId)->update($updateData) !== false;
    }

    /**
     * @notes 标记客户流失
     * @param int $customerId
     * @param string $reason
     * @return bool
     */
    public static function markAsLost(int $customerId, string $reason = ''): bool
    {
        return self::where('id', $customerId)->update([
            'customer_status' => self::STATUS_LOST,
            'loss_reason' => $reason,
            'loss_time' => time(),
            'update_time' => time(),
        ]) !== false;
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
        return self::where('id', $customerId)->update([
            'customer_status' => self::STATUS_SIGNED,
            'order_count' => \think\facade\Db::raw('order_count + 1'),
            'total_amount' => \think\facade\Db::raw("total_amount + {$amount}"),
            'update_time' => time(),
        ]) !== false;
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
        
        $oldAdvisorId = $customer->advisor_id;
        
        // 更新客户的顾问
        $result = self::where('id', $customerId)->update([
            'advisor_id' => $advisorId,
            'assign_time' => time(),
            'update_time' => time(),
        ]);
        
        if ($result === false) {
            return false;
        }
        
        // 更新顾问客户数
        if ($oldAdvisorId > 0) {
            SalesAdvisor::decrementCustomerCount($oldAdvisorId);
        }
        SalesAdvisor::incrementCustomerCount($advisorId);
        
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
        $query = new static();
        if ($advisorId > 0) {
            $query = $query->where('advisor_id', $advisorId);
        }
        
        return [
            'total' => (clone $query)->count(),
            'new' => (clone $query)->where('customer_status', self::STATUS_NEW)->count(),
            'following' => (clone $query)->where('customer_status', self::STATUS_FOLLOWING)->count(),
            'signed' => (clone $query)->where('customer_status', self::STATUS_SIGNED)->count(),
            'lost' => (clone $query)->where('customer_status', self::STATUS_LOST)->count(),
            'today_new' => self::getTodayNewCount($advisorId),
            'intention_stats' => self::getIntentionStats($advisorId),
        ];
    }
}
