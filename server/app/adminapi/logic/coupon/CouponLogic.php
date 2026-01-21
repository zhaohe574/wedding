<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 优惠券管理逻辑层
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\adminapi\logic\coupon;

use app\common\logic\BaseLogic;
use app\common\model\coupon\Coupon;
use app\common\model\coupon\UserCoupon;
use app\common\model\user\User;
use think\facade\Db;

/**
 * 优惠券管理逻辑层
 * Class CouponLogic
 * @package app\adminapi\logic\coupon
 */
class CouponLogic extends BaseLogic
{
    /**
     * @notes 优惠券详情
     * @param int $id
     * @return array
     */
    public static function detail(int $id): array
    {
        $coupon = Coupon::find($id);
        if (!$coupon) {
            return [];
        }

        $data = $coupon->toArray();
        $data['coupon_type_text'] = Coupon::getTypeDesc($data['coupon_type']);
        $data['valid_type_text'] = Coupon::getValidTypeDesc($data['valid_type']);
        $data['use_scope_text'] = Coupon::getScopeDesc($data['use_scope']);
        $data['status_text'] = Coupon::getStatusDesc($data['status']);
        $data['valid_period'] = $coupon->valid_period;
        $data['remain_count'] = $coupon->remain_count;
        $data['use_rate'] = $coupon->use_rate;
        $data['discount_desc'] = $coupon->discount_desc;

        // 有效期时间格式化
        if ($data['valid_start_time']) {
            $data['valid_start_time_text'] = date('Y-m-d H:i:s', $data['valid_start_time']);
        }
        if ($data['valid_end_time']) {
            $data['valid_end_time_text'] = date('Y-m-d H:i:s', $data['valid_end_time']);
        }

        return $data;
    }

    /**
     * @notes 添加优惠券
     * @param array $params
     * @return bool
     */
    public static function add(array $params): bool
    {
        try {
            $data = [
                'name' => $params['name'],
                'coupon_type' => $params['coupon_type'],
                'threshold_amount' => $params['threshold_amount'] ?? 0,
                'discount_amount' => $params['discount_amount'],
                'max_discount' => $params['max_discount'] ?? 0,
                'total_count' => $params['total_count'] ?? 0,
                'per_limit' => $params['per_limit'] ?? 1,
                'valid_type' => $params['valid_type'],
                'use_scope' => $params['use_scope'] ?? Coupon::SCOPE_ALL,
                'scope_ids' => $params['scope_ids'] ?? [],
                'status' => $params['status'] ?? Coupon::STATUS_ENABLED,
                'remark' => $params['remark'] ?? '',
            ];

            // 处理有效期
            if ($params['valid_type'] == Coupon::VALID_TYPE_FIXED) {
                $data['valid_start_time'] = strtotime($params['valid_start_time']);
                $data['valid_end_time'] = strtotime($params['valid_end_time'] . ' 23:59:59');
                $data['valid_days'] = 0;
            } else {
                $data['valid_start_time'] = 0;
                $data['valid_end_time'] = 0;
                $data['valid_days'] = $params['valid_days'] ?? 7;
            }

            Coupon::create($data);
            return true;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * @notes 编辑优惠券
     * @param array $params
     * @return bool
     */
    public static function edit(array $params): bool
    {
        try {
            $coupon = Coupon::find($params['id']);
            if (!$coupon) {
                self::setError('优惠券不存在');
                return false;
            }

            // 如果已有人领取，部分字段不允许修改
            if ($coupon->receive_count > 0) {
                $data = [
                    'name' => $params['name'],
                    'total_count' => $params['total_count'] ?? 0,
                    'status' => $params['status'] ?? Coupon::STATUS_ENABLED,
                    'remark' => $params['remark'] ?? '',
                ];

                // 固定日期类型可以延长结束时间
                if ($coupon->valid_type == Coupon::VALID_TYPE_FIXED && !empty($params['valid_end_time'])) {
                    $newEndTime = strtotime($params['valid_end_time'] . ' 23:59:59');
                    if ($newEndTime > $coupon->valid_end_time) {
                        $data['valid_end_time'] = $newEndTime;
                    }
                }
            } else {
                $data = [
                    'name' => $params['name'],
                    'coupon_type' => $params['coupon_type'],
                    'threshold_amount' => $params['threshold_amount'] ?? 0,
                    'discount_amount' => $params['discount_amount'],
                    'max_discount' => $params['max_discount'] ?? 0,
                    'total_count' => $params['total_count'] ?? 0,
                    'per_limit' => $params['per_limit'] ?? 1,
                    'valid_type' => $params['valid_type'],
                    'use_scope' => $params['use_scope'] ?? Coupon::SCOPE_ALL,
                    'scope_ids' => $params['scope_ids'] ?? [],
                    'status' => $params['status'] ?? Coupon::STATUS_ENABLED,
                    'remark' => $params['remark'] ?? '',
                ];

                // 处理有效期
                if ($params['valid_type'] == Coupon::VALID_TYPE_FIXED) {
                    $data['valid_start_time'] = strtotime($params['valid_start_time']);
                    $data['valid_end_time'] = strtotime($params['valid_end_time'] . ' 23:59:59');
                    $data['valid_days'] = 0;
                } else {
                    $data['valid_start_time'] = 0;
                    $data['valid_end_time'] = 0;
                    $data['valid_days'] = $params['valid_days'] ?? 7;
                }
            }

            $coupon->save($data);
            return true;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * @notes 删除优惠券
     * @param int $id
     * @return bool
     */
    public static function delete(int $id): bool
    {
        try {
            $coupon = Coupon::find($id);
            if (!$coupon) {
                self::setError('优惠券不存在');
                return false;
            }

            // 检查是否有用户持有且未使用
            $unusedCount = UserCoupon::where('coupon_id', $id)
                ->where('status', UserCoupon::STATUS_UNUSED)
                ->count();
            if ($unusedCount > 0) {
                self::setError("还有{$unusedCount}张优惠券未使用，无法删除");
                return false;
            }

            $coupon->delete();
            return true;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * @notes 启用/禁用优惠券
     * @param int $id
     * @return bool
     */
    public static function toggleStatus(int $id): bool
    {
        try {
            $coupon = Coupon::find($id);
            if (!$coupon) {
                self::setError('优惠券不存在');
                return false;
            }

            $coupon->save([
                'status' => $coupon->status ? Coupon::STATUS_DISABLED : Coupon::STATUS_ENABLED,
            ]);

            return true;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * @notes 手动发放优惠券
     * @param array $params
     * @return bool
     */
    public static function send(array $params): bool
    {
        try {
            $coupon = Coupon::find($params['coupon_id']);
            if (!$coupon) {
                self::setError('优惠券不存在');
                return false;
            }

            $user = User::find($params['user_id']);
            if (!$user) {
                self::setError('用户不存在');
                return false;
            }

            $userCoupon = UserCoupon::grantToUser($params['user_id'], $params['coupon_id'], 'admin_send');
            if (!$userCoupon) {
                list($canReceive, $error) = $coupon->canReceive($params['user_id']);
                self::setError($error ?: '发放失败');
                return false;
            }

            return true;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * @notes 批量发放优惠券
     * @param array $params
     * @return bool
     */
    public static function batchSend(array $params): bool
    {
        try {
            $coupon = Coupon::find($params['coupon_id']);
            if (!$coupon) {
                self::setError('优惠券不存在');
                return false;
            }

            $userIds = $params['user_ids'] ?? [];
            if (empty($userIds)) {
                self::setError('请选择用户');
                return false;
            }

            Db::startTrans();
            try {
                $successCount = 0;
                $failCount = 0;

                foreach ($userIds as $userId) {
                    $userCoupon = UserCoupon::grantToUser($userId, $params['coupon_id'], 'admin_batch_send');
                    if ($userCoupon) {
                        $successCount++;
                    } else {
                        $failCount++;
                    }
                }

                Db::commit();

                if ($failCount > 0) {
                    self::setError("成功发放{$successCount}张，失败{$failCount}张");
                    return $successCount > 0;
                }

                return true;
            } catch (\Exception $e) {
                Db::rollback();
                throw $e;
            }
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * @notes 撤回用户优惠券
     * @param int $userCouponId
     * @return bool
     */
    public static function revoke(int $userCouponId): bool
    {
        try {
            $userCoupon = UserCoupon::find($userCouponId);
            if (!$userCoupon) {
                self::setError('用户优惠券不存在');
                return false;
            }

            if ($userCoupon->status != UserCoupon::STATUS_UNUSED) {
                self::setError('只能撤回未使用的优惠券');
                return false;
            }

            $userCoupon->delete();
            return true;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * @notes 优惠券统计
     * @param array $params
     * @return array
     */
    public static function statistics(array $params): array
    {
        // 总优惠券数
        $totalCount = Coupon::count();

        // 启用的优惠券数
        $enabledCount = Coupon::where('status', Coupon::STATUS_ENABLED)->count();

        // 总发放量
        $totalReceive = UserCoupon::count();

        // 总使用量
        $totalUsed = UserCoupon::where('status', UserCoupon::STATUS_USED)->count();

        // 总使用率
        $useRate = $totalReceive > 0 ? round($totalUsed / $totalReceive * 100, 2) : 0;

        // 总优惠金额（已使用优惠券的抵扣总额）
        $totalDiscount = Db::name('order')
            ->where('coupon_id', '>', 0)
            ->sum('coupon_amount');

        // 按类型统计
        $typeStats = [];
        foreach (Coupon::getTypeDesc() as $type => $name) {
            $typeStats[] = [
                'type' => $type,
                'name' => $name,
                'count' => Coupon::where('coupon_type', $type)->count(),
            ];
        }

        return [
            'total_count' => $totalCount,
            'enabled_count' => $enabledCount,
            'total_receive' => $totalReceive,
            'total_used' => $totalUsed,
            'use_rate' => $useRate . '%',
            'total_discount' => round($totalDiscount, 2),
            'type_stats' => $typeStats,
        ];
    }

    /**
     * @notes 优惠券使用统计（按时间）
     * @param array $params
     * @return array
     */
    public static function useStatistics(array $params): array
    {
        $days = $params['days'] ?? 30;
        $startTime = strtotime("-{$days} days");

        $result = [];
        for ($i = $days - 1; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime("-{$i} days"));
            $dayStart = strtotime($date);
            $dayEnd = strtotime($date . ' 23:59:59');

            $receiveCount = UserCoupon::where('receive_time', '>=', $dayStart)
                ->where('receive_time', '<=', $dayEnd)
                ->count();

            $useCount = UserCoupon::where('use_time', '>=', $dayStart)
                ->where('use_time', '<=', $dayEnd)
                ->count();

            $result[] = [
                'date' => $date,
                'receive_count' => $receiveCount,
                'use_count' => $useCount,
            ];
        }

        return $result;
    }

    /**
     * @notes 优惠券排行榜
     * @param array $params
     * @return array
     */
    public static function ranking(array $params): array
    {
        $orderBy = $params['order_by'] ?? 'receive_count';
        $limit = $params['limit'] ?? 10;

        return Coupon::field('id,name,coupon_type,threshold_amount,discount_amount,receive_count,used_count')
            ->order($orderBy, 'desc')
            ->limit($limit)
            ->select()
            ->each(function ($item) {
                $item->coupon_type_text = Coupon::getTypeDesc($item->coupon_type);
                $item->use_rate = $item->receive_count > 0 
                    ? round($item->used_count / $item->receive_count * 100, 2) . '%' 
                    : '0%';
                return $item;
            })
            ->toArray();
    }

    /**
     * @notes 获取优惠券类型选项
     * @return array
     */
    public static function typeOptions(): array
    {
        return [
            'coupon_types' => self::formatOptions(Coupon::getTypeDesc()),
            'valid_types' => self::formatOptions(Coupon::getValidTypeDesc()),
            'use_scopes' => self::formatOptions(Coupon::getScopeDesc()),
            'statuses' => self::formatOptions(Coupon::getStatusDesc()),
        ];
    }

    /**
     * @notes 格式化选项
     * @param array $data
     * @return array
     */
    private static function formatOptions(array $data): array
    {
        $result = [];
        foreach ($data as $value => $label) {
            $result[] = [
                'value' => $value,
                'label' => $label,
            ];
        }
        return $result;
    }

    /**
     * @notes 获取启用的优惠券列表
     * @return array
     */
    public static function enabledList(): array
    {
        return Coupon::where('status', Coupon::STATUS_ENABLED)
            ->field('id,name,coupon_type,threshold_amount,discount_amount')
            ->order('create_time', 'desc')
            ->select()
            ->each(function ($item) {
                $item->coupon_type_text = Coupon::getTypeDesc($item->coupon_type);
                return $item;
            })
            ->toArray();
    }
}
