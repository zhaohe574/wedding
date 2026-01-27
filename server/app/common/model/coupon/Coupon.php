<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 优惠券模型
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\common\model\coupon;

use app\common\model\BaseModel;
use think\model\concern\SoftDelete;

/**
 * 优惠券模型
 * Class Coupon
 * @package app\common\model\coupon
 */
class Coupon extends BaseModel
{
    use SoftDelete;

    protected $name = 'coupon';
    protected $deleteTime = 'delete_time';
    
    // 自动时间戳
    protected $autoWriteTimestamp = true;
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';
    protected $dateFormat = false; // 保持时间戳格式，不自动转换为日期时间字符串

    // 优惠券类型
    const TYPE_FULL_REDUCTION = 1;  // 满减券
    const TYPE_DISCOUNT = 2;        // 折扣券
    const TYPE_DIRECT = 3;          // 立减券

    // 有效期类型
    const VALID_TYPE_FIXED = 1;     // 固定日期
    const VALID_TYPE_DAYS = 2;      // 领取后N天

    // 使用范围
    const SCOPE_ALL = 1;            // 全部可用
    const SCOPE_CATEGORY = 2;       // 指定分类
    const SCOPE_STAFF = 3;          // 指定人员

    // 状态
    const STATUS_DISABLED = 0;      // 禁用
    const STATUS_ENABLED = 1;       // 启用

    /**
     * @notes 类型描述
     * @param bool|int $value
     * @return array|string
     */
    public static function getTypeDesc($value = true)
    {
        $data = [
            self::TYPE_FULL_REDUCTION => '满减券',
            self::TYPE_DISCOUNT => '折扣券',
            self::TYPE_DIRECT => '立减券',
        ];
        if ($value === true) {
            return $data;
        }
        return $data[$value] ?? '';
    }

    /**
     * @notes 有效期类型描述
     * @param bool|int $value
     * @return array|string
     */
    public static function getValidTypeDesc($value = true)
    {
        $data = [
            self::VALID_TYPE_FIXED => '固定日期',
            self::VALID_TYPE_DAYS => '领取后N天有效',
        ];
        if ($value === true) {
            return $data;
        }
        return $data[$value] ?? '';
    }

    /**
     * @notes 使用范围描述
     * @param bool|int $value
     * @return array|string
     */
    public static function getScopeDesc($value = true)
    {
        $data = [
            self::SCOPE_ALL => '全部可用',
            self::SCOPE_CATEGORY => '指定分类',
            self::SCOPE_STAFF => '指定人员',
        ];
        if ($value === true) {
            return $data;
        }
        return $data[$value] ?? '';
    }

    /**
     * @notes 状态描述
     * @param bool|int $value
     * @return array|string
     */
    public static function getStatusDesc($value = true)
    {
        $data = [
            self::STATUS_DISABLED => '禁用',
            self::STATUS_ENABLED => '启用',
        ];
        if ($value === true) {
            return $data;
        }
        return $data[$value] ?? '';
    }

    /**
     * @notes 关联用户优惠券
     * @return \think\model\relation\HasMany
     */
    public function userCoupons()
    {
        return $this->hasMany(UserCoupon::class, 'coupon_id', 'id');
    }

    /**
     * @notes 适用范围ID获取器
     * @param $value
     * @return array
     */
    public function getScopeIdsAttr($value)
    {
        return $value ? json_decode($value, true) : [];
    }

    /**
     * @notes 适用范围ID设置器
     * @param $value
     * @return false|string
     */
    public function setScopeIdsAttr($value)
    {
        return $value ? json_encode($value, JSON_UNESCAPED_UNICODE) : '';
    }

    /**
     * @notes 类型文本获取器
     * @param $value
     * @param $data
     * @return string
     */
    public function getCouponTypeTextAttr($value, $data)
    {
        return self::getTypeDesc($data['coupon_type'] ?? 1);
    }

    /**
     * @notes 有效期类型文本获取器
     * @param $value
     * @param $data
     * @return string
     */
    public function getValidTypeTextAttr($value, $data)
    {
        return self::getValidTypeDesc($data['valid_type'] ?? 1);
    }

    /**
     * @notes 使用范围文本获取器
     * @param $value
     * @param $data
     * @return string
     */
    public function getUseScopeTextAttr($value, $data)
    {
        return self::getScopeDesc($data['use_scope'] ?? 1);
    }

    /**
     * @notes 状态文本获取器
     * @param $value
     * @param $data
     * @return string
     */
    public function getStatusTextAttr($value, $data)
    {
        return self::getStatusDesc($data['status'] ?? 1);
    }

    /**
     * @notes 有效期显示获取器
     * @param $value
     * @param $data
     * @return string
     */
    public function getValidPeriodAttr($value, $data)
    {
        // PHP 8 类型转换
        $validType = (int)($data['valid_type'] ?? 1);
        $validStartTime = (int)($data['valid_start_time'] ?? 0);
        $validEndTime = (int)($data['valid_end_time'] ?? 0);
        $validDays = (int)($data['valid_days'] ?? 0);
        
        if ($validType == self::VALID_TYPE_FIXED) {
            $start = $validStartTime ? date('Y-m-d', $validStartTime) : '';
            $end = $validEndTime ? date('Y-m-d', $validEndTime) : '';
            return $start . ' 至 ' . $end;
        } else {
            return '领取后' . $validDays . '天内有效';
        }
    }

    /**
     * @notes 剩余数量获取器
     * @param $value
     * @param $data
     * @return int|string
     */
    public function getRemainCountAttr($value, $data)
    {
        // PHP 8 类型转换
        $total = (int)($data['total_count'] ?? 0);
        if ($total == 0) {
            return '不限';
        }
        $received = (int)($data['receive_count'] ?? 0);
        return max(0, $total - $received);
    }

    /**
     * @notes 使用率获取器
     * @param $value
     * @param $data
     * @return string
     */
    public function getUseRateAttr($value, $data)
    {
        // PHP 8 类型转换
        $received = (int)($data['receive_count'] ?? 0);
        $used = (int)($data['used_count'] ?? 0);
        if ($received == 0) {
            return '0%';
        }
        return round($used / $received * 100, 2) . '%';
    }

    /**
     * @notes 优惠描述获取器
     * @param $value
     * @param $data
     * @return string
     */
    public function getDiscountDescAttr($value, $data)
    {
        // PHP 8 类型转换
        $type = (int)($data['coupon_type'] ?? 1);
        $threshold = (float)($data['threshold_amount'] ?? 0);
        $discount = (float)($data['discount_amount'] ?? 0);
        $maxDiscount = (float)($data['max_discount'] ?? 0);

        $thresholdText = $threshold > 0 ? "满{$threshold}元" : '无门槛';

        switch ($type) {
            case self::TYPE_FULL_REDUCTION:
                return "{$thresholdText}减{$discount}元";
            case self::TYPE_DISCOUNT:
                $discountRate = $discount / 10;
                $text = "{$thresholdText}打{$discountRate}折";
                if ($maxDiscount > 0) {
                    $text .= "，最多优惠{$maxDiscount}元";
                }
                return $text;
            case self::TYPE_DIRECT:
                return "立减{$discount}元";
            default:
                return '';
        }
    }

    /**
     * @notes 检查优惠券是否可领取
     * @param int $userId
     * @return array [bool, string]
     */
    public function canReceive(int $userId): array
    {
        // 检查状态
        if ($this->status != self::STATUS_ENABLED) {
            return [false, '优惠券已下架'];
        }

        // 检查库存
        if ($this->total_count > 0 && $this->receive_count >= $this->total_count) {
            return [false, '优惠券已领完'];
        }

        // 检查有效期
        if ($this->valid_type == self::VALID_TYPE_FIXED) {
            $now = time();
            if ($this->valid_start_time > $now) {
                return [false, '优惠券未到领取时间'];
            }
            if ($this->valid_end_time > 0 && $this->valid_end_time < $now) {
                return [false, '优惠券已过期'];
            }
        }

        // 检查每人限领
        if ($this->per_limit > 0) {
            $userCount = UserCoupon::where([
                'user_id' => $userId,
                'coupon_id' => $this->id,
            ])->count();
            if ($userCount >= $this->per_limit) {
                return [false, '已达到领取上限'];
            }
        }

        return [true, ''];
    }

    /**
     * @notes 计算优惠金额
     * @param float $orderAmount 订单金额
     * @return float
     */
    public function calculateDiscount(float $orderAmount): float
    {
        // 检查门槛
        if ($this->threshold_amount > 0 && $orderAmount < $this->threshold_amount) {
            return 0;
        }

        switch ($this->coupon_type) {
            case self::TYPE_FULL_REDUCTION:
            case self::TYPE_DIRECT:
                return min($this->discount_amount, $orderAmount);
            case self::TYPE_DISCOUNT:
                $discount = $orderAmount * (100 - $this->discount_amount) / 100;
                if ($this->max_discount > 0) {
                    $discount = min($discount, $this->max_discount);
                }
                return round($discount, 2);
            default:
                return 0;
        }
    }

    /**
     * @notes 增加领取数量
     * @return void
     */
    public function incrementReceive(): void
    {
        $this->inc('receive_count')->update();
    }

    /**
     * @notes 增加使用数量
     * @return void
     */
    public function incrementUsed(): void
    {
        $this->inc('used_count')->update();
    }

    /**
     * @notes 获取可领取的优惠券列表
     * @return \think\Collection
     */
    public static function getAvailableList()
    {
        $now = time();
        return self::where('status', self::STATUS_ENABLED)
            ->where(function ($query) use ($now) {
                $query->where(function ($q) use ($now) {
                    // 固定日期类型：在有效期内
                    $q->where('valid_type', self::VALID_TYPE_FIXED)
                        ->where('valid_start_time', '<=', $now)
                        ->where(function ($q2) use ($now) {
                            $q2->where('valid_end_time', '>=', $now)
                                ->whereOr('valid_end_time', 0);
                        });
                })->whereOr(function ($q) {
                    // 领取后N天有效类型：只要启用就可领取
                    $q->where('valid_type', self::VALID_TYPE_DAYS);
                });
            })
            ->where(function ($query) {
                // 库存检查
                $query->where('total_count', 0)
                    ->whereOr('receive_count', '<', \think\facade\Db::raw('total_count'));
            })
            ->order('create_time', 'desc')
            ->select();
    }
}
