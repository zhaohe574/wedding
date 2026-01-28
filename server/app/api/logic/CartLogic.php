<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 小程序端购物车业务逻辑
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\api\logic;

use app\common\logic\BaseLogic;
use app\common\model\cart\Cart;
use app\common\model\cart\CartPlan;

/**
 * 小程序端购物车业务逻辑
 * Class CartLogic
 * @package app\api\logic
 */
class CartLogic extends BaseLogic
{
    /**
     * @notes 获取用户购物车
     * @param int $userId
     * @return array
     */
    public static function getUserCart(int $userId): array
    {
        $items = Cart::getUserCart($userId);
        [$totalPrice, $totalCount, $conflicts] = Cart::calculateTotal($userId, true);

        return [
            'items' => $items,
            'total_price' => $totalPrice,
            'total_count' => $totalCount,
            'conflicts' => $conflicts,
            'selected_count' => count(array_filter($items, fn($item) => $item['is_selected'])),
        ];
    }

    /**
     * @notes 添加到购物车
     * @param array $params
     * @return array
     */
    public static function addToCart(array $params): array
    {
        $timeSlots = $params['time_slots'] ?? [];
        if (is_array($timeSlots) && !empty($timeSlots)) {
            [$success, $message, $cartIds] = Cart::addToCartMultiple(
                (int)$params['user_id'],
                (int)$params['staff_id'],
                $params['date'],
                $timeSlots,
                (int)($params['package_id'] ?? 0),
                $params['remark'] ?? ''
            );
            return [
                'success' => $success,
                'message' => $message,
                'cart_id' => $cartIds[0] ?? null,
                'cart_ids' => $cartIds,
            ];
        }

        // Explicitly cast to int to handle string parameters from POST requests
        [$success, $message, $cartId] = Cart::addToCart(
            (int)$params['user_id'],
            (int)$params['staff_id'],  // Cast string to int
            $params['date'],
            (int)($params['time_slot'] ?? 0),
            (int)($params['package_id'] ?? 0),
            $params['remark'] ?? ''
        );

        return [
            'success' => $success,
            'message' => $message,
            'cart_id' => $cartId,
            'cart_ids' => $cartId ? [$cartId] : [],
        ];
    }

    /**
     * @notes 更新购物车项
     * @param int $cartId
     * @param int $userId
     * @param array $data
     * @return array
     */
    public static function updateCartItem(int $cartId, int $userId, array $data): array
    {
        return Cart::updateCartItem($cartId, $userId, $data);
    }

    /**
     * @notes 删除购物车项
     * @param int $cartId
     * @param int $userId
     * @return array
     */
    public static function removeFromCart(int $cartId, int $userId): array
    {
        [$success, $message] = Cart::removeFromCart($cartId, $userId);
        return [
            'success' => $success,
            'message' => $message,
        ];
    }

    /**
     * @notes 批量删除
     * @param array $cartIds
     * @param int $userId
     * @return int
     */
    public static function batchRemove(array $cartIds, int $userId): int
    {
        return Cart::batchRemove($cartIds, $userId);
    }

    /**
     * @notes 切换选中状态
     * @param int $cartId
     * @param int $userId
     * @return bool
     */
    public static function toggleSelect(int $cartId, int $userId): bool
    {
        return Cart::toggleSelect($cartId, $userId);
    }

    /**
     * @notes 全选/取消全选
     * @param int $userId
     * @param bool $selected
     * @return int
     */
    public static function selectAll(int $userId, bool $selected): int
    {
        return Cart::selectAll($userId, $selected);
    }

    /**
     * @notes 计算总价
     * @param int $userId
     * @return array
     */
    public static function calculateTotal(int $userId): array
    {
        [$totalPrice, $totalCount, $conflicts] = Cart::calculateTotal($userId, true);
        return [
            'total_price' => $totalPrice,
            'total_count' => $totalCount,
            'conflicts' => $conflicts,
        ];
    }

    /**
     * @notes 检查冲突
     * @param int $userId
     * @return array
     */
    public static function checkConflicts(int $userId): array
    {
        return Cart::checkConflicts($userId);
    }

    /**
     * @notes 清空购物车
     * @param int $userId
     * @return int
     */
    public static function clearCart(int $userId): int
    {
        return Cart::clearCart($userId);
    }

    /**
     * @notes 获取购物车数量
     * @param int $userId
     * @return int
     */
    public static function getCartCount(int $userId): int
    {
        return Cart::getCartCount($userId);
    }

    /**
     * @notes 生成分享码
     * @param int $cartId
     * @param int $userId
     * @return string|null
     */
    public static function generateShareCode(int $cartId, int $userId): ?string
    {
        return Cart::generateShareCode($cartId, $userId);
    }

    /**
     * @notes 通过分享码获取购物车项
     * @param string $shareCode
     * @return array|null
     */
    public static function getByShareCode(string $shareCode): ?array
    {
        return Cart::getByShareCode($shareCode);
    }

    /**
     * @notes 创建方案
     * @param array $params
     * @return array
     */
    public static function createPlan(array $params): array
    {
        [$success, $message, $planId] = CartPlan::createPlan(
            $params['user_id'],
            $params['plan_name'],
            $params['cart_ids'],
            $params['remark'] ?? ''
        );

        return ['success' => $success, 'message' => $message, 'plan_id' => $planId];
    }

    /**
     * @notes 获取用户方案列表
     * @param int $userId
     * @return array
     */
    public static function getUserPlans(int $userId): array
    {
        return CartPlan::getUserPlans($userId);
    }

    /**
     * @notes 获取方案详情
     * @param int $planId
     * @param int $userId
     * @return array|null
     */
    public static function getPlanDetail(int $planId, int $userId): ?array
    {
        return CartPlan::getPlanDetail($planId, $userId);
    }

    /**
     * @notes 删除方案
     * @param int $planId
     * @param int $userId
     * @return array
     */
    public static function deletePlan(int $planId, int $userId): array
    {
        return CartPlan::deletePlan($planId, $userId);
    }

    /**
     * @notes 设为默认方案
     * @param int $planId
     * @param int $userId
     * @return array
     */
    public static function setDefaultPlan(int $planId, int $userId): array
    {
        [$success, $message] = CartPlan::setDefault($planId, $userId);
        return [
            'success' => $success,
            'message' => $message,
        ];
    }

    /**
     * @notes 取消默认方案
     * @param int $userId
     * @return array
     */
    public static function cancelDefaultPlan(int $userId): array
    {
        [$success, $message] = CartPlan::clearDefault($userId);
        return [
            'success' => $success,
            'message' => $message,
        ];
    }

    /**
     * @notes 通过分享码复制方案
     * @param string $shareCode
     * @param int $userId
     * @return array
     */
    public static function copyPlanToCart(string $shareCode, int $userId): array
    {
        [$success, $message, $copiedCount] = CartPlan::copyPlanToCart($shareCode, $userId);
        return ['success' => $success, 'message' => $message, 'copied_count' => $copiedCount];
    }

    /**
     * @notes 应用方案到购物车
     * @param int $planId
     * @param int $userId
     * @return array
     */
    public static function applyPlanToCart(int $planId, int $userId): array
    {
        [$success, $message, $copiedCount] = CartPlan::applyPlanToCart($planId, $userId);
        return ['success' => $success, 'message' => $message, 'copied_count' => $copiedCount];
    }

    /**
     * @notes 比较方案
     * @param int $planId1
     * @param int $planId2
     * @param int $userId
     * @return array|null
     */
    public static function comparePlans(int $planId1, int $planId2, int $userId): ?array
    {
        return CartPlan::comparePlans($planId1, $planId2, $userId);
    }

    /**
     * @notes 生成方案分享码
     * @param int $planId
     * @param int $userId
     * @param bool $force
     * @return string|null
     */
    public static function generatePlanShareCode(int $planId, int $userId, bool $force = false): ?string
    {
        return CartPlan::generateShareCode($planId, $userId, $force);
    }

    /**
     * @notes 通过分享码获取方案
     * @param string $shareCode
     * @return array|null
     */
    public static function getPlanByShareCode(string $shareCode): ?array
    {
        return CartPlan::getByShareCode($shareCode);
    }
}
