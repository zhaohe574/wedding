<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 用户优惠券模型
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\common\model\coupon;

use app\common\model\BaseModel;
use app\common\model\user\User;
use app\common\model\order\Order;

/**
 * 用户优惠券模型
 * Class UserCoupon
 * @package app\common\model\coupon
 */
class UserCoupon extends BaseModel
{
    protected $name = 'user_coupon';
    
    // 自动时间戳
    protected $autoWriteTimestamp = true;
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';
    protected $dateFormat = false; // 保持时间戳格式，不自动转换为日期时间字符串

    // 状态
    const STATUS_UNUSED = 0;    // 未使用
    const STATUS_USED = 1;      // 已使用
    const STATUS_EXPIRED = 2;   // 已过期

    /**
     * @notes 状态描述
     * @param bool|int $value
     * @return array|string
     */
    public static function getStatusDesc($value = true)
    {
        $data = [
            self::STATUS_UNUSED => '未使用',
            self::STATUS_USED => '已使用',
            self::STATUS_EXPIRED => '已过期',
        ];
        if ($value === true) {
            return $data;
        }
        return $data[$value] ?? '';
    }

    /**
     * @notes 关联用户
     * @return \think\model\relation\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id')
            ->field('id,nickname,avatar,mobile');
    }

    /**
     * @notes 关联优惠券
     * @return \think\model\relation\BelongsTo
     */
    public function coupon()
    {
        return $this->belongsTo(Coupon::class, 'coupon_id', 'id');
    }

    /**
     * @notes 关联订单
     * @return \think\model\relation\BelongsTo
     */
    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id', 'id')
            ->field('id,order_sn,total_amount,pay_amount');
    }

    /**
     * @notes 状态文本获取器
     * @param $value
     * @param $data
     * @return string
     */
    public function getStatusTextAttr($value, $data)
    {
        return self::getStatusDesc($data['status'] ?? 0);
    }

    /**
     * @notes 有效期显示获取器
     * @param $value
     * @param $data
     * @return string
     */
    public function getValidPeriodAttr($value, $data)
    {
        $start = $data['valid_start_time'] ? date('Y-m-d H:i:s', $data['valid_start_time']) : '';
        $end = $data['valid_end_time'] ? date('Y-m-d H:i:s', $data['valid_end_time']) : '';
        return $start . ' 至 ' . $end;
    }

    /**
     * @notes 是否可用获取器
     * @param $value
     * @param $data
     * @return bool
     */
    public function getCanUseAttr($value, $data)
    {
        // 已使用或已过期
        if (($data['status'] ?? 0) != self::STATUS_UNUSED) {
            return false;
        }
        // 检查有效期
        $now = time();
        if (($data['valid_end_time'] ?? 0) > 0 && $data['valid_end_time'] < $now) {
            return false;
        }
        if (($data['valid_start_time'] ?? 0) > $now) {
            return false;
        }
        return true;
    }

    /**
     * @notes 生成优惠券码
     * @return string
     */
    public static function generateCouponSn(): string
    {
        return 'CPN' . date('YmdHis') . strtoupper(substr(md5(uniqid()), 0, 8));
    }

    /**
     * @notes 发放优惠券给用户
     * @param int $userId
     * @param int $couponId
     * @param string $source 来源
     * @return UserCoupon|null
     */
    public static function grantToUser(int $userId, int $couponId, string $source = ''): ?UserCoupon
    {
        $coupon = Coupon::find($couponId);
        if (!$coupon) {
            return null;
        }

        // 检查是否可领取
        list($canReceive, $error) = $coupon->canReceive($userId);
        if (!$canReceive) {
            return null;
        }

        // 计算有效期
        $now = time();
        if ($coupon->valid_type == Coupon::VALID_TYPE_FIXED) {
            $validStartTime = $coupon->valid_start_time;
            $validEndTime = $coupon->valid_end_time;
        } else {
            $validStartTime = $now;
            $validEndTime = strtotime("+{$coupon->valid_days} days", $now);
        }

        // 创建用户优惠券
        $userCoupon = new self();
        $userCoupon->save([
            'user_id' => $userId,
            'coupon_id' => $couponId,
            'coupon_sn' => self::generateCouponSn(),
            'status' => self::STATUS_UNUSED,
            'valid_start_time' => $validStartTime,
            'valid_end_time' => $validEndTime,
            'receive_time' => $now,
        ]);

        // 增加优惠券领取数
        $coupon->incrementReceive();

        return $userCoupon;
    }

    /**
     * @notes 使用优惠券
     * @param int $orderId
     * @return bool
     */
    public function use(int $orderId): bool
    {
        if ($this->status != self::STATUS_UNUSED) {
            return false;
        }

        $this->save([
            'status' => self::STATUS_USED,
            'order_id' => $orderId,
            'use_time' => time(),
        ]);

        // 增加优惠券使用数
        $coupon = Coupon::find($this->coupon_id);
        if ($coupon) {
            $coupon->incrementUsed();
        }

        return true;
    }

    /**
     * @notes 退还优惠券（订单取消/退款时）
     * @return bool
     */
    public function refund(): bool
    {
        if ($this->status != self::STATUS_USED) {
            return false;
        }

        // 检查是否在有效期内
        $now = time();
        $newStatus = self::STATUS_UNUSED;
        if ($this->valid_end_time > 0 && $this->valid_end_time < $now) {
            $newStatus = self::STATUS_EXPIRED;
        }

        $this->save([
            'status' => $newStatus,
            'order_id' => 0,
            'use_time' => 0,
        ]);

        return true;
    }

    /**
     * @notes 检查并更新过期状态
     * @return int 更新数量
     */
    public static function checkExpired(): int
    {
        $now = time();
        return self::where('status', self::STATUS_UNUSED)
            ->where('valid_end_time', '>', 0)
            ->where('valid_end_time', '<', $now)
            ->update(['status' => self::STATUS_EXPIRED]);
    }

    /**
     * @notes 获取用户可用优惠券列表
     * @param int $userId
     * @param float $orderAmount 订单金额（用于过滤门槛）
     * @param array $staffIds 服务人员ID（用于过滤使用范围）
     * @param array $categoryIds 分类ID（用于过滤使用范围）
     * @return \think\Collection
     */
    public static function getAvailableForOrder(int $userId, float $orderAmount = 0, array $staffIds = [], array $categoryIds = [])
    {
        $now = time();

        $query = self::with(['coupon'])
            ->where('user_id', $userId)
            ->where('status', self::STATUS_UNUSED)
            ->where('valid_start_time', '<=', $now)
            ->where(function ($q) use ($now) {
                $q->where('valid_end_time', '>=', $now)
                    ->whereOr('valid_end_time', 0);
            });

        $list = $query->order('valid_end_time', 'asc')->select();

        // 过滤不满足条件的优惠券
        $result = [];
        foreach ($list as $item) {
            $coupon = $item->coupon;
            if (!$coupon || $coupon->status != Coupon::STATUS_ENABLED) {
                continue;
            }

            // 检查门槛金额
            if ($orderAmount > 0 && $coupon->threshold_amount > $orderAmount) {
                continue;
            }

            // 检查使用范围
            if ($coupon->use_scope == Coupon::SCOPE_CATEGORY && !empty($categoryIds)) {
                $scopeIds = $coupon->scope_ids;
                if (!empty($scopeIds) && empty(array_intersect($scopeIds, $categoryIds))) {
                    continue;
                }
            }

            if ($coupon->use_scope == Coupon::SCOPE_STAFF && !empty($staffIds)) {
                $scopeIds = $coupon->scope_ids;
                if (!empty($scopeIds) && empty(array_intersect($scopeIds, $staffIds))) {
                    continue;
                }
            }

            // 计算优惠金额
            $item->discount_amount = $coupon->calculateDiscount($orderAmount);
            $result[] = $item;
        }

        return collect($result);
    }

    /**
     * @notes 获取用户优惠券数量统计
     * @param int $userId
     * @return array
     */
    public static function getUserCouponStats(int $userId): array
    {
        $now = time();

        // 先更新过期状态
        self::where('user_id', $userId)
            ->where('status', self::STATUS_UNUSED)
            ->where('valid_end_time', '>', 0)
            ->where('valid_end_time', '<', $now)
            ->update(['status' => self::STATUS_EXPIRED]);

        $total = self::where('user_id', $userId)->count();
        $unused = self::where('user_id', $userId)
            ->where('status', self::STATUS_UNUSED)
            ->count();
        $used = self::where('user_id', $userId)
            ->where('status', self::STATUS_USED)
            ->count();
        $expired = self::where('user_id', $userId)
            ->where('status', self::STATUS_EXPIRED)
            ->count();

        return [
            'total' => $total,
            'unused' => $unused,
            'used' => $used,
            'expired' => $expired,
        ];
    }
}
