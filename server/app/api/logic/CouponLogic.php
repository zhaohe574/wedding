<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 小程序端优惠券逻辑层
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\api\logic;

use app\common\logic\BaseLogic;
use app\common\model\coupon\Coupon;
use app\common\model\coupon\UserCoupon;
use think\facade\Db;

/**
 * 小程序端优惠券逻辑层
 * Class CouponLogic
 * @package app\api\logic
 */
class CouponLogic extends BaseLogic
{
    /**
     * @notes 可领取的优惠券列表
     * @param array $params
     * @return array
     */
    public static function availableList(array $params): array
    {
        $page = (int)($params['page'] ?? 1);
        $limit = (int)($params['limit'] ?? 10);
        $userId = (int)($params['user_id'] ?? 0);

        $now = time();

        $query = Coupon::where('status', Coupon::STATUS_ENABLED);
        Coupon::applyReceiveTimeCondition($query, $now, Coupon::RECEIVE_PREVIEW_SECONDS);
        $query->where(function ($q) {
            // 库存检查
            $q->where('total_count', 0)
                ->whereOr('receive_count', '<', Db::raw('total_count'));
        });

        $total = $query->count();

        $lists = $query->order('create_time desc')
            ->page($page, $limit)
            ->select()
            ->toArray();

        // 处理每个优惠券的领取状态
        foreach ($lists as &$item) {
            $item['coupon_type_text'] = Coupon::getTypeDesc($item['coupon_type']);
            $item['discount_desc'] = self::getDiscountDesc($item);

            // 有效期显示
            if ($item['valid_type'] == Coupon::VALID_TYPE_FIXED) {
                $item['valid_period'] = date('Y.m.d H:i:s', $item['valid_start_time']) . '-' . date('Y.m.d H:i:s', $item['valid_end_time']);
            } else {
                $item['valid_period'] = '领取后' . $item['valid_days'] . '天有效';
            }

            // 剩余数量
            if ($item['total_count'] == 0) {
                $item['remain_count'] = -1; // -1表示不限
            } else {
                $item['remain_count'] = max(0, $item['total_count'] - $item['receive_count']);
            }

            // 领取时间状态
            [$timeOk, $statusText, $countdown] = Coupon::getReceiveTimeStatus($item, $now);
            $item['receive_countdown'] = $countdown;
            $item['receive_status_text'] = $statusText;
            if (!$timeOk && $statusText === '未开始' && $countdown > 0) {
                $item['receive_status_text'] = Coupon::formatCountdownText($countdown);
            }

            // 用户是否已领取
            $item['is_received'] = false;
            $item['can_receive'] = $timeOk;
            if ($userId > 0) {
                $userReceivedCount = UserCoupon::where([
                    'user_id' => $userId,
                    'coupon_id' => $item['id'],
                ])->count();
                $item['is_received'] = $userReceivedCount > 0;
                $item['can_receive'] = $timeOk && ($item['per_limit'] == 0 || $userReceivedCount < $item['per_limit']);
            }
        }

        return [
            'lists' => $lists,
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
            'has_more' => $total > $page * $limit,
        ];
    }

    /**
     * @notes 优惠券详情
     * @param array $params
     * @return array|false
     */
    public static function detail(array $params)
    {
        $coupon = Coupon::find($params['id']);
        if (!$coupon) {
            self::setError('优惠券不存在');
            return false;
        }

        $data = $coupon->toArray();
        $data['coupon_type_text'] = Coupon::getTypeDesc($data['coupon_type']);
        $data['discount_desc'] = self::getDiscountDesc($data);
        $data['use_scope_text'] = Coupon::getScopeDesc($data['use_scope']);

        // 有效期显示
        if ($data['valid_type'] == Coupon::VALID_TYPE_FIXED) {
            $data['valid_period'] = date('Y.m.d H:i:s', $data['valid_start_time']) . '-' . date('Y.m.d H:i:s', $data['valid_end_time']);
        } else {
            $data['valid_period'] = '领取后' . $data['valid_days'] . '天有效';
        }

        // 剩余数量
        if ($data['total_count'] == 0) {
            $data['remain_count'] = -1;
        } else {
            $data['remain_count'] = max(0, $data['total_count'] - $data['receive_count']);
        }

        $now = time();
        [$timeOk, $statusText, $countdown] = Coupon::getReceiveTimeStatus($data, $now);
        $data['receive_countdown'] = $countdown;
        $data['receive_status_text'] = $statusText;
        if (!$timeOk && $statusText === '未开始' && $countdown > 0) {
            $data['receive_status_text'] = Coupon::formatCountdownText($countdown);
        }

        $stockOk = $data['total_count'] == 0 || $data['receive_count'] < $data['total_count'];

        // 用户领取状态
        $userId = $params['user_id'] ?? 0;
        if ($userId > 0) {
            $userReceivedCount = UserCoupon::where([
                'user_id' => $userId,
                'coupon_id' => $params['id'],
            ])->count();
            $data['user_received_count'] = $userReceivedCount;
            $data['is_received'] = $userReceivedCount > 0;
            $data['can_receive'] = $timeOk && ($data['per_limit'] == 0 || $userReceivedCount < $data['per_limit']);
        } else {
            $data['user_received_count'] = 0;
            $data['is_received'] = false;
            $data['can_receive'] = $timeOk;
        }

        if (!$stockOk) {
            $data['can_receive'] = false;
            $data['receive_status_text'] = '已领完';
        }

        return $data;
    }

    /**
     * @notes 领取优惠券
     * @param array $params
     * @return array|false
     */
    public static function receive(array $params)
    {
        try {
            $couponId = (int)$params['coupon_id'];
            $coupon = Coupon::find($couponId);
            if (!$coupon) {
                self::setError('优惠券不存在');
                return false;
            }

            // 检查是否可领取
            list($canReceive, $error) = $coupon->canReceive($params['user_id']);
            if (!$canReceive) {
                self::setError($error);
                return false;
            }

            // 发放优惠券
            $userCoupon = UserCoupon::grantToUser($params['user_id'], $couponId, 'user_receive');
            if (!$userCoupon) {
                self::setError('领取失败，请稍后重试');
                return false;
            }

            return [
                'user_coupon_id' => $userCoupon->id,
                'coupon_sn' => $userCoupon->coupon_sn,
            ];
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * @notes 我的优惠券列表
     * @param array $params
     * @return array
     */
    public static function myCoupons(array $params): array
    {
        $page = (int)($params['page'] ?? 1);
        $limit = (int)($params['limit'] ?? 10);
        $status = $params['status'] ?? ''; // 空=全部, 0=未使用, 1=已使用, 2=已过期

        $now = time();

        // 先更新过期状态
        UserCoupon::where('user_id', $params['user_id'])
            ->where('status', UserCoupon::STATUS_UNUSED)
            ->where('valid_end_time', '>', 0)
            ->where('valid_end_time', '<', $now)
            ->update(['status' => UserCoupon::STATUS_EXPIRED]);

        $where = [
            ['user_id', '=', $params['user_id']],
        ];

        if ($status !== '') {
            $where[] = ['status', '=', (int)$status];
        }

        $total = UserCoupon::where($where)->count();

        $lists = UserCoupon::with(['coupon'])
            ->where($where)
            ->order('status asc, valid_end_time asc')
            ->page($page, $limit)
            ->select()
            ->toArray();

        foreach ($lists as &$item) {
            $item['status_text'] = UserCoupon::getStatusDesc($item['status']);
            $item['valid_period'] = date('Y.m.d H:i:s', $item['valid_start_time']) . '-' . date('Y.m.d H:i:s', $item['valid_end_time']);
            $item['receive_time_text'] = date('Y-m-d H:i:s', $item['receive_time']);

            // 是否即将过期（3天内）
            $item['is_expiring'] = $item['status'] == UserCoupon::STATUS_UNUSED 
                && $item['valid_end_time'] > 0 
                && $item['valid_end_time'] - $now < 3 * 86400;

            // 优惠券信息
            if (!empty($item['coupon'])) {
                $item['coupon_name'] = $item['coupon']['name'];
                $item['coupon_type'] = $item['coupon']['coupon_type'];
                $item['coupon_type_text'] = Coupon::getTypeDesc($item['coupon']['coupon_type']);
                $item['threshold_amount'] = $item['coupon']['threshold_amount'];
                $item['discount_amount'] = $item['coupon']['discount_amount'];
                $item['max_discount'] = $item['coupon']['max_discount'];
                $item['discount_desc'] = self::getDiscountDesc($item['coupon']);
                $item['use_scope'] = $item['coupon']['use_scope'];
                $item['use_scope_text'] = Coupon::getScopeDesc($item['coupon']['use_scope']);
            }
        }

        return [
            'lists' => $lists,
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
            'has_more' => $total > $page * $limit,
        ];
    }

    /**
     * @notes 我的优惠券统计
     * @param int $userId
     * @return array
     */
    public static function myStats(int $userId): array
    {
        return UserCoupon::getUserCouponStats($userId);
    }

    /**
     * @notes 订单可用优惠券列表
     * @param array $params
     * @return array
     */
    public static function orderAvailable(array $params): array
    {
        $orderAmount = (float)($params['order_amount'] ?? 0);
        $staffIds = $params['staff_ids'] ?? [];
        $categoryIds = $params['category_ids'] ?? [];

        if (is_string($staffIds)) {
            $staffIds = explode(',', $staffIds);
        }
        if (is_string($categoryIds)) {
            $categoryIds = explode(',', $categoryIds);
        }

        $list = UserCoupon::getAvailableForOrder(
            $params['user_id'],
            $orderAmount,
            array_map('intval', $staffIds),
            array_map('intval', $categoryIds)
        );

        $result = [];
        foreach ($list as $item) {
            $coupon = $item->coupon;
            $result[] = [
                'user_coupon_id' => $item->id,
                'coupon_id' => $item->coupon_id,
                'coupon_sn' => $item->coupon_sn,
                'coupon_name' => $coupon->name ?? '',
                'coupon_type' => $coupon->coupon_type ?? 1,
                'coupon_type_text' => Coupon::getTypeDesc($coupon->coupon_type ?? 1),
                'threshold_amount' => $coupon->threshold_amount ?? 0,
                'discount_amount' => $item->discount_amount ?? 0,
                'discount_desc' => self::getDiscountDesc($coupon->toArray()),
                'valid_period' => date('Y.m.d H:i:s', $item->valid_start_time) . '-' . date('Y.m.d H:i:s', $item->valid_end_time),
            ];
        }

        // 按优惠金额降序排列
        usort($result, function ($a, $b) {
            return $b['discount_amount'] <=> $a['discount_amount'];
        });

        return [
            'lists' => $result,
            'total' => count($result),
            'best_coupon' => !empty($result) ? $result[0] : null,
        ];
    }

    /**
     * @notes 计算优惠金额
     * @param array $params
     * @return array|false
     */
    public static function calculate(array $params)
    {
        $userCoupon = UserCoupon::with(['coupon'])
            ->where('id', $params['user_coupon_id'])
            ->where('user_id', $params['user_id'])
            ->find();

        if (!$userCoupon) {
            self::setError('优惠券不存在');
            return false;
        }

        if ($userCoupon->status != UserCoupon::STATUS_UNUSED) {
            self::setError('优惠券已使用或已过期');
            return false;
        }

        // 检查有效期
        $now = time();
        if ($userCoupon->valid_start_time > 0 && $userCoupon->valid_start_time > $now) {
            self::setError('优惠券未到使用时间');
            return false;
        }
        if ($userCoupon->valid_end_time > 0 && $userCoupon->valid_end_time < $now) {
            self::setError('优惠券已过期');
            return false;
        }

        $coupon = $userCoupon->coupon;
        if (!$coupon || $coupon->status != Coupon::STATUS_ENABLED) {
            self::setError('优惠券已下架');
            return false;
        }

        $orderAmount = (float)($params['order_amount'] ?? 0);

        // 检查门槛
        if ($coupon->threshold_amount > 0 && $orderAmount < $coupon->threshold_amount) {
            self::setError('订单金额未达到使用门槛');
            return false;
        }

        // 计算优惠金额
        $discountAmount = $coupon->calculateDiscount($orderAmount);

        return [
            'order_amount' => $orderAmount,
            'discount_amount' => $discountAmount,
            'pay_amount' => max(0, $orderAmount - $discountAmount),
            'coupon_name' => $coupon->name,
            'discount_desc' => self::getDiscountDesc($coupon->toArray()),
        ];
    }

    /**
     * @notes 使用优惠券
     * @param array $params
     * @return array|false
     */
    public static function useCoupon(array $params)
    {
        try {
            $userCoupon = UserCoupon::where('id', $params['user_coupon_id'])
                ->where('user_id', $params['user_id'])
                ->find();

            if (!$userCoupon) {
                self::setError('优惠券不存在');
                return false;
            }

            if ($userCoupon->status != UserCoupon::STATUS_UNUSED) {
                self::setError('优惠券已使用或已过期');
                return false;
            }

            // 检查有效期
            $now = time();
            if ($userCoupon->valid_start_time > 0 && $userCoupon->valid_start_time > $now) {
                self::setError('优惠券未到使用时间');
                return false;
            }
            if ($userCoupon->valid_end_time > 0 && $userCoupon->valid_end_time < $now) {
                $userCoupon->save(['status' => UserCoupon::STATUS_EXPIRED]);
                self::setError('优惠券已过期');
                return false;
            }

            // 使用优惠券
            $success = $userCoupon->use($params['order_id']);
            if (!$success) {
                self::setError('优惠券使用失败');
                return false;
            }

            return [
                'user_coupon_id' => $userCoupon->id,
                'order_id' => $params['order_id'],
            ];
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * @notes 兑换码兑换优惠券
     * @param array $params
     * @return array|false
     */
    public static function exchange(array $params)
    {
        // 这里预留兑换码功能，目前返回错误
        self::setError('兑换码功能暂未开放');
        return false;
    }

    /**
     * @notes 获取优惠描述
     * @param array $item
     * @return string
     */
    private static function getDiscountDesc(array $item): string
    {
        $threshold = $item['threshold_amount'] ?? 0;
        $discount = $item['discount_amount'] ?? 0;
        $maxDiscount = $item['max_discount'] ?? 0;
        $type = $item['coupon_type'] ?? 1;

        $thresholdText = $threshold > 0 ? "满{$threshold}元" : '无门槛';

        switch ($type) {
            case Coupon::TYPE_FULL_REDUCTION:
                return "{$thresholdText}减{$discount}元";
            case Coupon::TYPE_DISCOUNT:
                $discountRate = $discount / 10;
                $text = "{$thresholdText}打{$discountRate}折";
                if ($maxDiscount > 0) {
                    $text .= "，最多优惠{$maxDiscount}元";
                }
                return $text;
            case Coupon::TYPE_DIRECT:
                return "立减{$discount}元";
            default:
                return '';
        }
    }

    /**
     * @notes 退还优惠券（订单取消/退款时调用）
     * @param int $userCouponId
     * @return bool
     */
    public static function refundCoupon(int $userCouponId): bool
    {
        try {
            $userCoupon = UserCoupon::find($userCouponId);
            if (!$userCoupon) {
                return false;
            }

            return $userCoupon->refund();
        } catch (\Exception $e) {
            return false;
        }
    }
}
