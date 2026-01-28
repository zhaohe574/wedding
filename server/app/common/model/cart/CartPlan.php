<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 购物车方案模型
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\common\model\cart;

use app\common\model\BaseModel;
use think\model\concern\SoftDelete;

/**
 * 购物车方案模型
 * Class CartPlan
 * @package app\common\model\cart
 */
class CartPlan extends BaseModel
{
    use SoftDelete;

    protected $name = 'cart_plan';
    protected $deleteTime = 'delete_time';

    /**
     * @notes 购物车项ID数组获取器
     * @param $value
     * @return array
     */
    public function getCartIdsAttr($value): array
    {
        if (empty($value)) {
            return [];
        }
        $decoded = json_decode($value, true);
        return is_array($decoded) ? $decoded : [];
    }

    /**
     * @notes 购物车项ID数组设置器
     * @param $value
     * @return string
     */
    public function setCartIdsAttr($value): string
    {
        if (is_array($value)) {
            return json_encode($value, JSON_UNESCAPED_UNICODE);
        }
        return $value;
    }

    /**
     * @notes 方案明细快照获取器
     * @param $value
     * @return array
     */
    public function getItemsSnapshotAttr($value): array
    {
        if (empty($value)) {
            return [];
        }
        $decoded = json_decode($value, true);
        return is_array($decoded) ? $decoded : [];
    }

    /**
     * @notes 方案明细快照设置器
     * @param $value
     * @return string
     */
    public function setItemsSnapshotAttr($value): string
    {
        if (is_array($value)) {
            return json_encode($value, JSON_UNESCAPED_UNICODE);
        }
        return $value;
    }

    /**
     * @notes 创建方案
     * @param int $userId
     * @param string $planName
     * @param array $cartIds
     * @param string $remark
     * @return array [bool $success, string $message, int|null $planId]
     */
    public static function createPlan(int $userId, string $planName, array $cartIds, string $remark = ''): array
    {
        if (empty($cartIds)) {
            return [false, '请选择购物车项', null];
        }

        // 验证购物车项属于该用户
        $validCount = Cart::whereIn('id', $cartIds)
            ->where('user_id', $userId)
            ->count();

        if ($validCount != count($cartIds)) {
            return [false, '包含无效的购物车项', null];
        }

        // 计算总价
        $totalPrice = Cart::whereIn('id', $cartIds)
            ->where('user_id', $userId)
            ->sum('price');

        $itemsSnapshot = self::buildItemsSnapshot($userId, $cartIds);
        try {
            $plan = self::create([
                'user_id' => $userId,
                'plan_name' => $planName,
                'cart_ids' => $cartIds,
                'items_snapshot' => $itemsSnapshot,
                'total_price' => $totalPrice,
                'remark' => $remark,
                'is_default' => 0,
                'create_time' => time(),
                'update_time' => time(),
            ]);
            return [true, '方案创建成功', $plan->id];
        } catch (\Exception $e) {
            return [false, '创建失败：' . $e->getMessage(), null];
        }
    }

    /**
     * @notes 更新方案
     * @param int $planId
     * @param int $userId
     * @param array $data
     * @return array [bool $success, string $message]
     */
    public static function updatePlan(int $planId, int $userId, array $data): array
    {
        $plan = self::where('id', $planId)
            ->where('user_id', $userId)
            ->find();

        if (!$plan) {
            return [false, '方案不存在'];
        }

        // 如果更新了购物车项，重新计算总价
        if (isset($data['cart_ids'])) {
            $cartIds = $data['cart_ids'];
            $validCount = Cart::whereIn('id', $cartIds)
                ->where('user_id', $userId)
                ->count();

            if ($validCount != count($cartIds)) {
                return [false, '包含无效的购物车项'];
            }

            $data['total_price'] = Cart::whereIn('id', $cartIds)
                ->where('user_id', $userId)
                ->sum('price');
            $data['items_snapshot'] = self::buildItemsSnapshot($userId, $cartIds);
        }

        $data['update_time'] = time();
        $plan->save($data);

        return [true, '更新成功'];
    }

    /**
     * @notes 删除方案
     * @param int $planId
     * @param int $userId
     * @return array [bool $success, string $message]
     */
    public static function deletePlan(int $planId, int $userId): array
    {
        $plan = self::where('id', $planId)
            ->where('user_id', $userId)
            ->find();

        if (!$plan) {
            return [false, '方案不存在'];
        }

        if (!empty($plan->is_default)) {
            return [false, '默认方案不能删除'];
        }

        $plan->delete();
        return [true, '删除成功'];
    }

    /**
     * @notes 设为默认方案
     * @param int $planId
     * @param int $userId
     * @return array [bool $success, string $message]
     */
    public static function setDefault(int $planId, int $userId): array
    {
        $plan = self::where('id', $planId)
            ->where('user_id', $userId)
            ->find();

        if (!$plan) {
            return [false, '方案不存在'];
        }

        // 取消其他默认方案
        self::where('user_id', $userId)
            ->where('id', '<>', $planId)
            ->update(['is_default' => 0, 'update_time' => time()]);

        // 设置当前方案为默认
        $plan->is_default = 1;
        $plan->update_time = time();
        $plan->save();

        return [true, '设置成功'];
    }

    /**
     * @notes 取消默认方案
     * @param int $userId
     * @return array [bool $success, string $message]
     */
    public static function clearDefault(int $userId): array
    {
        $count = self::where('user_id', $userId)
            ->where('is_default', 1)
            ->update(['is_default' => 0, 'update_time' => time()]);

        if ($count > 0) {
            return [true, '已取消默认方案'];
        }

        return [true, '暂无默认方案'];
    }

    /**
     * @notes 获取用户方案列表
     * @param int $userId
     * @return array
     */
    public static function getUserPlans(int $userId): array
    {
        $plans = self::where('user_id', $userId)
            ->order('is_default', 'desc')
            ->order('create_time', 'desc')
            ->select()
            ->toArray();

        // 加载每个方案的购物车详情
        foreach ($plans as &$plan) {
            $cartIds = $plan['cart_ids'];
            $items = $plan['items_snapshot'] ?? [];
            if (empty($items) && !empty($cartIds)) {
                $items = self::buildItemsSnapshot($userId, $cartIds);
            }
            $plan['cart_items'] = $items;
            $actualPrice = 0;
            foreach ($items as $item) {
                $actualPrice += ($item['price'] ?? 0) * ($item['quantity'] ?? 0);
            }
            $plan['actual_total_price'] = round($actualPrice, 2);
        }

        return $plans;
    }

    /**
     * @notes 获取方案详情
     * @param int $planId
     * @param int $userId
     * @return array|null
     */
    public static function getPlanDetail(int $planId, int $userId): ?array
    {
        $plan = self::where('id', $planId)
            ->where('user_id', $userId)
            ->find();

        if (!$plan) {
            return null;
        }

        $plan = $plan->toArray();
        $cartIds = $plan['cart_ids'];

        $items = $plan['items_snapshot'] ?? [];
        if (empty($items) && !empty($cartIds)) {
            $items = self::buildItemsSnapshot($userId, $cartIds);
        }
        $plan['cart_items'] = $items;

        return $plan;
    }

    /**
     * @notes 生成分享码
     * @param int $planId
     * @param int $userId
     * @param bool $force
     * @return string|null
     */
    public static function generateShareCode(int $planId, int $userId, bool $force = false): ?string
    {
        $plan = self::where('id', $planId)
            ->where('user_id', $userId)
            ->find();

        if (!$plan) {
            return null;
        }

        if (!$force && !empty($plan->share_code)) {
            return $plan->share_code;
        }

        $shareCode = md5($planId . $userId . time() . mt_rand(1000, 9999));
        $shareCode = substr($shareCode, 0, 16);

        $plan->share_code = $shareCode;
        $plan->update_time = time();
        $plan->save();

        return $shareCode;
    }

    /**
     * @notes 通过分享码获取方案
     * @param string $shareCode
     * @return array|null
     */
    public static function getByShareCode(string $shareCode): ?array
    {
        $plan = self::where('share_code', $shareCode)->find();

        if (!$plan) {
            return null;
        }

        $plan = $plan->toArray();
        $cartIds = $plan['cart_ids'];

        $items = $plan['items_snapshot'] ?? [];
        if (empty($items) && !empty($cartIds)) {
            $items = self::buildItemsSnapshot((int)($plan['user_id'] ?? 0), $cartIds);
        }
        $plan['cart_items'] = $items;

        return $plan;
    }

    /**
     * @notes 复制方案到自己的购物车
     * @param string $shareCode
     * @param int $userId
     * @return array [bool $success, string $message, int $copiedCount]
     */
    public static function copyPlanToCart(string $shareCode, int $userId): array
    {
        $plan = self::getByShareCode($shareCode);

        if (!$plan) {
            return [false, '方案不存在或已失效', 0];
        }

        $items = $plan['items_snapshot'] ?? $plan['cart_items'] ?? [];
        if (empty($items)) {
            return [false, '方案中没有可复制的项目', 0];
        }

        $copiedCount = 0;
        foreach ($items as $item) {
            [$success] = Cart::addToCart(
                $userId,
                $item['staff_id'],
                $item['schedule_date'],
                $item['time_slot'],
                $item['package_id'],
                $item['remark']
            );
            if ($success) {
                $copiedCount++;
            }
        }

        if ($copiedCount == 0) {
            return [false, '所有项目都已存在或档期不可用', 0];
        }

        return [true, "成功复制 {$copiedCount} 个项目", $copiedCount];
    }

    /**
     * @notes 应用方案到购物车（覆盖）
     * @param int $planId
     * @param int $userId
     * @return array [bool $success, string $message, int $copiedCount]
     */
    public static function applyPlanToCart(int $planId, int $userId): array
    {
        $plan = self::where('id', $planId)
            ->where('user_id', $userId)
            ->find();

        if (!$plan) {
            return [false, '方案不存在', 0];
        }

        $plan = $plan->toArray();
        $cartIds = $plan['cart_ids'] ?? [];
        $items = $plan['items_snapshot'] ?? [];
        if (empty($items) && !empty($cartIds)) {
            $items = self::buildItemsSnapshot($userId, $cartIds);
        }

        if (empty($items)) {
            return [false, '方案中没有可复制的项目', 0];
        }

        Cart::clearCart($userId);

        $copiedCount = 0;
        foreach ($items as $item) {
            [$success] = Cart::addToCart(
                $userId,
                (int)($item['staff_id'] ?? 0),
                (string)($item['schedule_date'] ?? ''),
                (int)($item['time_slot'] ?? 0),
                (int)($item['package_id'] ?? 0),
                (string)($item['remark'] ?? '')
            );
            if ($success) {
                $copiedCount++;
            }
        }

        if ($copiedCount === 0) {
            return [false, '所有项目档期不可用', 0];
        }

        return [true, "成功应用 {$copiedCount} 个项目", $copiedCount];
    }

    /**
     * @notes 组装方案明细快照
     * @param int $userId
     * @param array $cartIds
     * @return array
     */
    protected static function buildItemsSnapshot(int $userId, array $cartIds): array
    {
        if (empty($cartIds)) {
            return [];
        }
        $items = Cart::whereIn('id', $cartIds)
            ->where('user_id', $userId)
            ->with(['staff' => function ($q) {
                $q->field('id, name, avatar');
            }, 'package' => function ($q) {
                $q->field('id, name');
            }])
            ->select()
            ->toArray();

        $slotMap = [
            0 => '全天',
            1 => '早礼',
            2 => '午宴',
            3 => '晚宴',
        ];

        $snapshot = [];
        foreach ($items as $item) {
            $timeSlot = (int)($item['time_slot'] ?? 0);
            $snapshot[] = [
                'cart_id' => $item['id'] ?? 0,
                'staff_id' => $item['staff_id'] ?? 0,
                'staff_name' => $item['staff']['name'] ?? '',
                'staff_avatar' => $item['staff']['avatar'] ?? '',
                'package_id' => $item['package_id'] ?? 0,
                'package_name' => $item['package']['name'] ?? '',
                'schedule_date' => $item['schedule_date'] ?? '',
                'time_slot' => $timeSlot,
                'time_slot_desc' => $slotMap[$timeSlot] ?? '未知场次',
                'price' => $item['price'] ?? 0,
                'quantity' => $item['quantity'] ?? 1,
                'remark' => $item['remark'] ?? '',
            ];
        }

        return $snapshot;
    }

    /**
     * @notes 比较两个方案
     * @param int $planId1
     * @param int $planId2
     * @param int $userId
     * @return array|null
     */
    public static function comparePlans(int $planId1, int $planId2, int $userId): ?array
    {
        $plan1 = self::getPlanDetail($planId1, $userId);
        $plan2 = self::getPlanDetail($planId2, $userId);

        if (!$plan1 || !$plan2) {
            return null;
        }

        return [
            'plan1' => $plan1,
            'plan2' => $plan2,
            'price_diff' => $plan1['total_price'] - $plan2['total_price'],
            'items_diff' => count($plan1['cart_items']) - count($plan2['cart_items']),
        ];
    }
}
