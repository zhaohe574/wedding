<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 订单附加服务快照模型
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\common\model\order;

use app\common\model\BaseModel;

/**
 * 订单附加服务快照模型
 * Class OrderItemAddon
 * @package app\common\model\order
 */
class OrderItemAddon extends BaseModel
{
    protected $name = 'order_item_addon';

    public const STATUS_ACTIVE = 1;
    public const STATUS_REMOVED = 2;

    public const SOURCE_ORDER = 1;
    public const SOURCE_CHANGE = 2;

    /**
     * @notes 关联订单
     * @return \think\model\relation\BelongsTo
     */
    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id', 'id');
    }

    /**
     * @notes 关联主订单项
     * @return \think\model\relation\BelongsTo
     */
    public function orderItem()
    {
        return $this->belongsTo(OrderItem::class, 'order_item_id', 'id');
    }

    /**
     * @notes 获取主订单项当前生效的附加服务ID
     * @param int $orderItemId
     * @return array
     */
    public static function getActiveAddonIds(int $orderItemId): array
    {
        if ($orderItemId <= 0) {
            return [];
        }

        $ids = self::where('order_item_id', $orderItemId)
            ->where('status', self::STATUS_ACTIVE)
            ->column('addon_id');

        return array_map('intval', $ids);
    }

    /**
     * @notes 批量创建附加服务快照
     * @param int $orderId
     * @param int $orderItemId
     * @param array $addons
     * @param int $createSource
     * @param int $changeId
     * @return array
     */
    public static function createSnapshots(
        int $orderId,
        int $orderItemId,
        array $addons,
        int $createSource = self::SOURCE_ORDER,
        int $changeId = 0
    ): array {
        if ($orderId <= 0 || $orderItemId <= 0 || empty($addons)) {
            return [];
        }

        $result = [];
        $seenAddonIds = [];
        $now = time();

        foreach ($addons as $addon) {
            $addonId = (int)($addon['id'] ?? $addon['addon_id'] ?? 0);
            if ($addonId <= 0 || isset($seenAddonIds[$addonId])) {
                continue;
            }
            $seenAddonIds[$addonId] = true;

            $price = round((float)($addon['price'] ?? 0), 2);
            $record = self::create([
                'order_id' => $orderId,
                'order_item_id' => $orderItemId,
                'addon_id' => $addonId,
                'addon_name' => (string)($addon['name'] ?? $addon['addon_name'] ?? ''),
                'price' => $price,
                'quantity' => 1,
                'subtotal' => $price,
                'status' => self::STATUS_ACTIVE,
                'create_source' => $createSource,
                'create_change_id' => $createSource === self::SOURCE_CHANGE ? $changeId : 0,
                'remove_change_id' => 0,
                'create_time' => $now,
                'update_time' => $now,
            ]);
            $result[] = $record;
        }

        return $result;
    }

    /**
     * @notes 批量标记附加服务为已移除
     * @param int $orderItemId
     * @param array $addonIds
     * @param int $changeId
     * @return int
     */
    public static function markRemoved(int $orderItemId, array $addonIds, int $changeId = 0): int
    {
        $addonIds = array_values(array_unique(array_filter(array_map('intval', $addonIds))));
        if ($orderItemId <= 0 || empty($addonIds)) {
            return 0;
        }

        return self::where('order_item_id', $orderItemId)
            ->whereIn('addon_id', $addonIds)
            ->where('status', self::STATUS_ACTIVE)
            ->update([
                'status' => self::STATUS_REMOVED,
                'remove_change_id' => $changeId,
                'update_time' => time(),
            ]);
    }

    /**
     * @notes 重算订单附加服务金额与应付金额
     * @param int $orderId
     * @return bool
     */
    public static function refreshOrderAmounts(int $orderId): bool
    {
        if ($orderId <= 0) {
            return false;
        }

        $order = Order::find($orderId);
        if (!$order) {
            return false;
        }

        $itemAmount = round((float)OrderItem::where('order_id', $orderId)->sum('subtotal'), 2);
        $addonAmount = round((float)self::where('order_id', $orderId)
            ->where('status', self::STATUS_ACTIVE)
            ->sum('subtotal'), 2);
        $totalAmount = round($itemAmount + $addonAmount, 2);
        $payAmount = round($totalAmount - (float)$order->discount_amount, 2);

        $order->save([
            'addon_amount' => $addonAmount,
            'total_amount' => $totalAmount,
            'pay_amount' => $payAmount,
            'update_time' => time(),
        ]);

        return true;
    }
}
