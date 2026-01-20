<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 订单模型
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\common\model\order;

use app\common\model\BaseModel;
use app\common\model\user\User;
use app\common\model\schedule\Schedule;
use think\model\concern\SoftDelete;
use think\facade\Db;

/**
 * 订单模型
 * Class Order
 * @package app\common\model\order
 */
class Order extends BaseModel
{
    use SoftDelete;

    protected $name = 'order';
    protected $deleteTime = 'delete_time';

    // 订单类型
    const TYPE_NORMAL = 1;      // 普通订单
    const TYPE_PACKAGE = 2;     // 套餐订单
    const TYPE_COMBO = 3;       // 组合订单

    // 订单状态
    const STATUS_PENDING = 0;       // 待支付
    const STATUS_PAID = 1;          // 已支付
    const STATUS_IN_SERVICE = 2;    // 服务中
    const STATUS_COMPLETED = 3;     // 已完成
    const STATUS_CANCELLED = 4;     // 已取消
    const STATUS_REFUNDED = 5;      // 已退款

    // 支付状态
    const PAY_STATUS_UNPAID = 0;        // 未支付
    const PAY_STATUS_PAID = 1;          // 已支付
    const PAY_STATUS_PARTIAL_REFUND = 2; // 部分退款
    const PAY_STATUS_FULL_REFUND = 3;   // 全额退款

    // 支付方式
    const PAY_WAY_NONE = 0;     // 未支付
    const PAY_WAY_WECHAT = 1;   // 微信
    const PAY_WAY_ALIPAY = 2;   // 支付宝
    const PAY_WAY_BALANCE = 3;  // 余额
    const PAY_WAY_OFFLINE = 4;  // 线下

    // 订单来源
    const SOURCE_MINIAPP = 1;   // 小程序
    const SOURCE_H5 = 2;        // H5
    const SOURCE_ADMIN = 3;     // 后台

    /**
     * @notes 关联用户
     * @return \think\model\relation\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    /**
     * @notes 关联订单项
     * @return \think\model\relation\HasMany
     */
    public function items()
    {
        return $this->hasMany(OrderItem::class, 'order_id', 'id');
    }

    /**
     * @notes 关联支付记录
     * @return \think\model\relation\HasMany
     */
    public function payments()
    {
        return $this->hasMany(Payment::class, 'order_id', 'id');
    }

    /**
     * @notes 关联订单日志
     * @return \think\model\relation\HasMany
     */
    public function logs()
    {
        return $this->hasMany(OrderLog::class, 'order_id', 'id');
    }

    /**
     * @notes 订单状态描述获取器
     * @param $value
     * @param $data
     * @return string
     */
    public function getOrderStatusDescAttr($value, $data): string
    {
        $map = [
            self::STATUS_PENDING => '待支付',
            self::STATUS_PAID => '已支付',
            self::STATUS_IN_SERVICE => '服务中',
            self::STATUS_COMPLETED => '已完成',
            self::STATUS_CANCELLED => '已取消',
            self::STATUS_REFUNDED => '已退款',
        ];
        return $map[$data['order_status']] ?? '未知';
    }

    /**
     * @notes 支付状态描述获取器
     * @param $value
     * @param $data
     * @return string
     */
    public function getPayStatusDescAttr($value, $data): string
    {
        $map = [
            self::PAY_STATUS_UNPAID => '未支付',
            self::PAY_STATUS_PAID => '已支付',
            self::PAY_STATUS_PARTIAL_REFUND => '部分退款',
            self::PAY_STATUS_FULL_REFUND => '全额退款',
        ];
        return $map[$data['pay_status']] ?? '未知';
    }

    /**
     * @notes 支付方式描述获取器
     * @param $value
     * @param $data
     * @return string
     */
    public function getPayTypeDescAttr($value, $data): string
    {
        $map = [
            self::PAY_WAY_NONE => '未支付',
            self::PAY_WAY_WECHAT => '微信支付',
            self::PAY_WAY_ALIPAY => '支付宝',
            self::PAY_WAY_BALANCE => '余额支付',
            self::PAY_WAY_OFFLINE => '线下支付',
        ];
        return $map[$data['pay_type']] ?? '未知';
    }

    /**
     * @notes 生成订单号
     * @return string
     */
    public static function generateOrderSn(): string
    {
        return date('YmdHis') . str_pad((string)mt_rand(1, 999999), 6, '0', STR_PAD_LEFT);
    }

    /**
     * @notes 创建订单
     * @param int $userId
     * @param array $cartItems 购物车项
     * @param array $orderInfo 订单信息
     * @return array [bool $success, string $message, Order|null $order]
     */
    public static function createOrder(int $userId, array $cartItems, array $orderInfo): array
    {
        Db::startTrans();
        try {
            // 计算订单金额
            $totalAmount = 0;
            foreach ($cartItems as $item) {
                $totalAmount += $item['price'] * ($item['quantity'] ?? 1);
            }

            // 计算优惠
            $discountAmount = $orderInfo['discount_amount'] ?? 0;
            $couponAmount = $orderInfo['coupon_amount'] ?? 0;
            $payAmount = $totalAmount - $discountAmount - $couponAmount;
            
            // 定金/尾款模式
            $depositAmount = 0;
            $balanceAmount = 0;
            if (!empty($orderInfo['deposit_ratio'])) {
                $depositAmount = round($payAmount * $orderInfo['deposit_ratio'] / 100, 2);
                $balanceAmount = $payAmount - $depositAmount;
            }

            // 创建订单
            $order = self::create([
                'order_sn' => self::generateOrderSn(),
                'user_id' => $userId,
                'order_type' => $orderInfo['order_type'] ?? self::TYPE_NORMAL,
                'order_status' => self::STATUS_PENDING,
                'pay_status' => self::PAY_STATUS_UNPAID,
                'total_amount' => $totalAmount,
                'discount_amount' => $discountAmount,
                'coupon_amount' => $couponAmount,
                'pay_amount' => $payAmount,
                'deposit_amount' => $depositAmount,
                'balance_amount' => $balanceAmount,
                'service_date' => $orderInfo['service_date'] ?? null,
                'service_time_slot' => $orderInfo['time_slot'] ?? 0,
                'service_address' => $orderInfo['service_address'] ?? '',
                'contact_name' => $orderInfo['contact_name'] ?? '',
                'contact_mobile' => $orderInfo['contact_mobile'] ?? '',
                'wedding_date' => $orderInfo['wedding_date'] ?? null,
                'wedding_venue' => $orderInfo['wedding_venue'] ?? '',
                'user_remark' => $orderInfo['remark'] ?? '',
                'coupon_id' => $orderInfo['coupon_id'] ?? 0,
                'source' => $orderInfo['source'] ?? self::SOURCE_MINIAPP,
                'create_time' => time(),
                'update_time' => time(),
            ]);

            // 创建订单项
            foreach ($cartItems as $item) {
                OrderItem::create([
                    'order_id' => $order->id,
                    'staff_id' => $item['staff_id'],
                    'package_id' => $item['package_id'] ?? 0,
                    'schedule_id' => $item['schedule_id'] ?? 0,
                    'service_date' => $item['schedule_date'],
                    'time_slot' => $item['time_slot'] ?? 0,
                    'staff_name' => $item['staff']['name'] ?? '',
                    'package_name' => $item['package']['name'] ?? '',
                    'price' => $item['price'],
                    'quantity' => $item['quantity'] ?? 1,
                    'subtotal' => $item['price'] * ($item['quantity'] ?? 1),
                    'remark' => $item['remark'] ?? '',
                    'create_time' => time(),
                    'update_time' => time(),
                ]);

                // 确认档期预约
                if (!empty($item['schedule_id'])) {
                    Schedule::confirmBooking(
                        $item['staff_id'],
                        $item['schedule_date'],
                        $item['time_slot'] ?? 0,
                        $order->id,
                        $userId
                    );
                }
            }

            // 记录订单日志
            OrderLog::addLog($order->id, 1, $userId, 'create', 0, self::STATUS_PENDING, '创建订单');

            Db::commit();
            return [true, '订单创建成功', $order];
        } catch (\Exception $e) {
            Db::rollback();
            return [false, '订单创建失败：' . $e->getMessage(), null];
        }
    }

    /**
     * @notes 取消订单
     * @param int $orderId
     * @param int $operatorId
     * @param int $operatorType
     * @param string $reason
     * @return array [bool $success, string $message]
     */
    public static function cancelOrder(int $orderId, int $operatorId, int $operatorType = 1, string $reason = ''): array
    {
        Db::startTrans();
        try {
            $order = self::find($orderId);
            if (!$order) {
                return [false, '订单不存在'];
            }

            if (!in_array($order->order_status, [self::STATUS_PENDING, self::STATUS_PAID])) {
                return [false, '当前订单状态不可取消'];
            }

            $beforeStatus = $order->order_status;

            // 更新订单状态
            $order->order_status = self::STATUS_CANCELLED;
            $order->cancel_reason = $reason;
            $order->cancel_time = time();
            $order->update_time = time();
            $order->save();

            // 释放档期
            $items = OrderItem::where('order_id', $orderId)->select();
            foreach ($items as $item) {
                if ($item->schedule_id > 0) {
                    Schedule::releaseLock($item->schedule_id);
                }
            }

            // 记录日志
            OrderLog::addLog($orderId, $operatorType, $operatorId, 'cancel', $beforeStatus, self::STATUS_CANCELLED, '取消订单：' . $reason);

            // TODO: 已支付订单需要退款

            Db::commit();
            return [true, '订单已取消'];
        } catch (\Exception $e) {
            Db::rollback();
            return [false, '取消失败：' . $e->getMessage()];
        }
    }

    /**
     * @notes 完成订单
     * @param int $orderId
     * @param int $operatorId
     * @param int $operatorType
     * @return array [bool $success, string $message]
     */
    public static function completeOrder(int $orderId, int $operatorId, int $operatorType = 1): array
    {
        $order = self::find($orderId);
        if (!$order) {
            return [false, '订单不存在'];
        }

        if ($order->order_status != self::STATUS_IN_SERVICE) {
            return [false, '当前订单状态不可完成'];
        }

        $beforeStatus = $order->order_status;
        $order->order_status = self::STATUS_COMPLETED;
        $order->complete_time = time();
        $order->update_time = time();
        $order->save();

        // 更新订单项状态
        OrderItem::where('order_id', $orderId)->update([
            'item_status' => 2,
            'update_time' => time(),
        ]);

        // 记录日志
        OrderLog::addLog($orderId, $operatorType, $operatorId, 'complete', $beforeStatus, self::STATUS_COMPLETED, '订单完成');

        return [true, '订单已完成'];
    }

    /**
     * @notes 获取订单状态选项
     * @return array
     */
    public static function getStatusOptions(): array
    {
        return [
            ['value' => self::STATUS_PENDING, 'label' => '待支付'],
            ['value' => self::STATUS_PAID, 'label' => '已支付'],
            ['value' => self::STATUS_IN_SERVICE, 'label' => '服务中'],
            ['value' => self::STATUS_COMPLETED, 'label' => '已完成'],
            ['value' => self::STATUS_CANCELLED, 'label' => '已取消'],
            ['value' => self::STATUS_REFUNDED, 'label' => '已退款'],
        ];
    }

    /**
     * @notes 获取支付方式选项
     * @return array
     */
    public static function getPayWayOptions(): array
    {
        return [
            ['value' => self::PAY_WAY_WECHAT, 'label' => '微信支付'],
            ['value' => self::PAY_WAY_ALIPAY, 'label' => '支付宝'],
            ['value' => self::PAY_WAY_BALANCE, 'label' => '余额支付'],
            ['value' => self::PAY_WAY_OFFLINE, 'label' => '线下支付'],
        ];
    }

    /**
     * @notes 关联变更记录
     * @return \think\model\relation\HasMany
     */
    public function orderChanges()
    {
        return $this->hasMany(OrderChange::class, 'order_id', 'id');
    }

    /**
     * @notes 关联转让记录
     * @return \think\model\relation\HasOne
     */
    public function orderTransfer()
    {
        return $this->hasOne(OrderTransfer::class, 'order_id', 'id')
            ->order('id', 'desc');
    }

    /**
     * @notes 关联暂停记录
     * @return \think\model\relation\HasOne
     */
    public function orderPause()
    {
        return $this->hasOne(OrderPause::class, 'order_id', 'id')
            ->where('pause_status', OrderPause::STATUS_PAUSED);
    }

    /**
     * @notes 关联所有暂停记录
     * @return \think\model\relation\HasMany
     */
    public function orderPauses()
    {
        return $this->hasMany(OrderPause::class, 'order_id', 'id');
    }

    /**
     * @notes 关联变更日志
     * @return \think\model\relation\HasMany
     */
    public function changeLogs()
    {
        return $this->hasMany(OrderChangeLog::class, 'order_id', 'id');
    }

    /**
     * @notes 是否暂停描述获取器
     * @param $value
     * @param $data
     * @return string
     */
    public function getIsPausedDescAttr($value, $data): string
    {
        return ($data['is_paused'] ?? 0) ? '暂停中' : '正常';
    }

    /**
     * @notes 检查订单是否可变更
     * @return array [bool $canChange, string $message]
     */
    public function canChange(): array
    {
        return OrderChange::checkCanChange($this->id);
    }

    /**
     * @notes 检查订单是否可转让
     * @return array [bool $canTransfer, string $message]
     */
    public function canTransfer(): array
    {
        return OrderTransfer::checkCanTransfer($this->id, $this->user_id);
    }

    /**
     * @notes 检查订单是否可暂停
     * @return array [bool $canPause, string $message]
     */
    public function canPause(): array
    {
        return OrderPause::checkCanPause($this->id, $this->user_id);
    }

    /**
     * @notes 获取订单的变更统计
     * @return array
     */
    public function getChangeStatistics(): array
    {
        $changes = OrderChange::where('order_id', $this->id)->select();
        
        $stats = [
            'total' => count($changes),
            'date_change' => 0,
            'staff_change' => 0,
            'add_item' => 0,
            'pending' => 0,
            'approved' => 0,
            'executed' => 0,
        ];

        foreach ($changes as $change) {
            switch ($change->change_type) {
                case OrderChange::TYPE_DATE:
                    $stats['date_change']++;
                    break;
                case OrderChange::TYPE_STAFF:
                    $stats['staff_change']++;
                    break;
                case OrderChange::TYPE_ADD_ITEM:
                    $stats['add_item']++;
                    break;
            }

            switch ($change->change_status) {
                case OrderChange::STATUS_PENDING:
                    $stats['pending']++;
                    break;
                case OrderChange::STATUS_APPROVED:
                    $stats['approved']++;
                    break;
                case OrderChange::STATUS_EXECUTED:
                    $stats['executed']++;
                    break;
            }
        }

        return $stats;
    }
}
