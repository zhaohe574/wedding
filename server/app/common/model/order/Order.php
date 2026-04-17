<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 订单模型
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\common\model\order;

use app\common\model\BaseModel;
use app\common\model\user\User;
use app\common\model\schedule\Schedule;
use app\common\model\schedule\Waitlist;
use app\common\model\package\PackageBooking;
use app\common\service\ConfigService;
use app\common\service\OrderConfirmLetterService;
use app\common\service\OrderNotificationService;
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
    const STATUS_PENDING_CONFIRM = 0; // 待确认
    const STATUS_PENDING_PAY = 1;     // 待支付
    const STATUS_PENDING_SERVICE = 2; // 待服务
    const STATUS_PAID = self::STATUS_PENDING_SERVICE; // 兼容历史常量，等同待服务
    const STATUS_IN_SERVICE = 3;      // 服务中
    const STATUS_COMPLETED = 4;       // 已完成
    const STATUS_REVIEWED = 5;        // 已评价
    const STATUS_CANCELLED = 6;       // 已取消
    const STATUS_PAUSED = 7;          // 已暂停
    const STATUS_REFUNDED = 8;        // 已退款
    const STATUS_USER_DELETED = 9;    // 用户已删除
    const STATUS_REFUNDING = 10;      // 退款中

    // 支付状态
    const PAY_STATUS_UNPAID = 0;        // 未支付
    const PAY_STATUS_PAID = 1;          // 已支付
    const PAY_STATUS_PARTIAL_REFUND = 2; // 部分退款
    const PAY_STATUS_FULL_REFUND = 3;   // 全额退款

    // 支付方式
    const PAY_WAY_NONE = 0;        // 未支付
    const PAY_WAY_WECHAT = 1;      // 微信
    const PAY_WAY_ALIPAY = 2;      // 支付宝
    const PAY_WAY_BALANCE = 3;     // 余额
    const PAY_WAY_OFFLINE = 4;     // 线下
    const PAY_WAY_COMBINATION = 5; // 组合支付

    // 支付渠道
    const PAYMENT_CHANNEL_ONLINE = 1;  // 线上支付
    const PAYMENT_CHANNEL_OFFLINE = 2; // 线下支付

    // 线下支付凭证状态
    const VOUCHER_STATUS_PENDING = 0;  // 待审核
    const VOUCHER_STATUS_APPROVED = 1; // 已通过
    const VOUCHER_STATUS_REJECTED = 2; // 已拒绝

    // 订单来源
    const SOURCE_MINIAPP = 1;   // 小程序
    const SOURCE_H5 = 2;        // H5
    const SOURCE_ADMIN = 3;     // 后台

    const AUTO_CANCEL_REASON = '支付超时自动取消';
    const AUTO_CANCEL_MESSAGE = '订单支付超时，已自动取消';
    const BALANCE_PAYMENT_TIMEOUT_MESSAGE = '尾款支付已超时，请联系管理员处理';
    const STAFF_CONFIRM_TIMEOUT_CANCEL_REASON = '服务人员确认超时自动取消';
    const STAFF_CONFIRM_TIMEOUT_CANCEL_MESSAGE = '订单因服务人员确认超时，已自动取消';
    const STAFF_CONFIRM_TIMEOUT_AUTO_CONFIRM_REASON = '服务人员确认超时自动同意';
    const STAFF_CONFIRM_TIMEOUT_AUTO_CONFIRM_MESSAGE = '订单因服务人员确认超时，系统已自动同意';

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
     * @notes 关联退款记录
     * @return \think\model\relation\HasMany
     */
    public function refunds()
    {
        return $this->hasMany(Refund::class, 'order_id', 'id');
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
        return self::getStatusText((int)($data['order_status'] ?? -1));
    }

    /**
     * @notes 支付状态描述获取器
     * @param $value
     * @param $data
     * @return string
     */
    public function getPayStatusDescAttr($value, $data): string
    {
        return self::getPayStatusText((int)($data['pay_status'] ?? -1));
    }

    /**
     * @notes 支付方式描述获取器
     * @param $value
     * @param $data
     * @return string
     */
    public function getPayTypeDescAttr($value, $data): string
    {
        return self::getPayWayText((int)($data['pay_type'] ?? -1));
    }

    /**
     * @notes 获取订单状态文案
     * @param int $status
     * @return string
     */
    public static function getStatusText(int $status): string
    {
        $map = [
            self::STATUS_PENDING_CONFIRM => '待确认',
            self::STATUS_PENDING_PAY => '待支付',
            self::STATUS_PENDING_SERVICE => '待服务',
            self::STATUS_IN_SERVICE => '服务中',
            self::STATUS_COMPLETED => '已完成',
            self::STATUS_REVIEWED => '已评价',
            self::STATUS_CANCELLED => '已取消',
            self::STATUS_PAUSED => '已暂停',
            self::STATUS_REFUNDED => '已退款',
            self::STATUS_USER_DELETED => '用户已删除',
            self::STATUS_REFUNDING => '退款中',
        ];

        return $map[$status] ?? '未知';
    }

    /**
     * @notes 获取支付状态文案
     * @param int $status
     * @return string
     */
    public static function getPayStatusText(int $status): string
    {
        $map = [
            self::PAY_STATUS_UNPAID => '未支付',
            self::PAY_STATUS_PAID => '已支付',
            self::PAY_STATUS_PARTIAL_REFUND => '部分退款',
            self::PAY_STATUS_FULL_REFUND => '全额退款',
        ];

        return $map[$status] ?? '未知';
    }

    /**
     * @notes 构建订单支付展示态
     * @param array $state
     * @return array{key:string,desc:string}
     */
    public static function buildPayStatusDisplayFromState(array $state): array
    {
        $payStatus = (int)($state['pay_status'] ?? self::PAY_STATUS_UNPAID);
        if ($payStatus === self::PAY_STATUS_PARTIAL_REFUND) {
            return ['key' => 'partial_refund', 'desc' => '部分退款'];
        }

        if ($payStatus === self::PAY_STATUS_FULL_REFUND) {
            return ['key' => 'full_refund', 'desc' => '全额退款'];
        }

        $payAmount = round((float)($state['pay_amount'] ?? 0), 2);
        $paidAmount = round((float)($state['paid_amount'] ?? 0), 2);
        $depositAmount = round((float)($state['deposit_amount'] ?? 0), 2);
        $depositPaid = (int)($state['deposit_paid'] ?? 0) === 1;
        $balancePaid = (int)($state['balance_paid'] ?? 0) === 1;

        if ($depositAmount > 0) {
            if ($depositPaid && !$balancePaid) {
                return ['key' => 'deposit_paid', 'desc' => '已付定金'];
            }

            if ($depositPaid && ($balancePaid || ($payAmount > 0 && $paidAmount >= $payAmount))) {
                return ['key' => 'paid', 'desc' => '已支付'];
            }

            return ['key' => 'unpaid', 'desc' => '未支付'];
        }

        if ($payStatus === self::PAY_STATUS_PAID || ($payAmount > 0 && $paidAmount >= $payAmount)) {
            return ['key' => 'paid', 'desc' => '已支付'];
        }

        return ['key' => 'unpaid', 'desc' => '未支付'];
    }

    /**
     * @notes 获取支付方式文案
     * @param int $payWay
     * @return string
     */
    public static function getPayWayText(int $payWay): string
    {
        $map = [
            self::PAY_WAY_NONE => '未支付',
            self::PAY_WAY_WECHAT => '微信支付',
            self::PAY_WAY_ALIPAY => '支付宝',
            self::PAY_WAY_BALANCE => '余额支付',
            self::PAY_WAY_OFFLINE => '线下支付',
            self::PAY_WAY_COMBINATION => '组合支付',
        ];

        return $map[$payWay] ?? '未知';
    }

    /**
     * @notes 支付渠道描述获取器
     * @param mixed $value
     * @param array $data
     * @return string
     */
    public function getPaymentChannelDescAttr($value, $data): string
    {
        return self::getPaymentChannelText(self::resolvePaymentChannel(
            $data['payment_channel'] ?? null,
            $data['pay_type'] ?? null,
            $data['pay_voucher'] ?? ''
        ));
    }

    /**
     * @notes 线下凭证状态描述获取器
     * @param $value
     * @param $data
     * @return string
     */
    public function getPayVoucherStatusDescAttr($value, $data): string
    {
        if (empty($data['pay_voucher'] ?? '')) {
            return '未上传';
        }
        $map = [
            self::VOUCHER_STATUS_PENDING => '待审核',
            self::VOUCHER_STATUS_APPROVED => '已通过',
            self::VOUCHER_STATUS_REJECTED => '已拒绝',
        ];
        return $map[$data['pay_voucher_status']] ?? '未知';
    }

    /**
     * @notes 服务地区文本获取器
     * @param mixed $value
     * @param array $data
     * @return string
     */
    public function getServiceRegionTextAttr($value, $data): string
    {
        $names = array_filter([
            trim((string)($data['service_province'] ?? '')),
            trim((string)($data['service_city'] ?? '')),
            trim((string)($data['service_district'] ?? '')),
        ]);

        return implode(' ', $names);
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
     * @notes 是否启用未支付自动取消
     * @return bool
     */
    public static function isUnpaidAutoCancelEnabled(): bool
    {
        return (int)ConfigService::get('transaction', 'cancel_unpaid_orders', 1) === 1;
    }

    /**
     * @notes 获取未支付自动取消分钟数
     * @return int
     */
    public static function getUnpaidAutoCancelMinutes(): int
    {
        return max((int)ConfigService::get('transaction', 'cancel_unpaid_orders_times', 30), 1);
    }

    /**
     * @notes 是否启用服务人员确认超时
     * @return bool
     */
    public static function isStaffConfirmTimeoutEnabled(): bool
    {
        return (int)ConfigService::get('transaction', 'staff_confirm_timeout_enabled', 0) === 1;
    }

    /**
     * @notes 获取服务人员确认超时分钟数
     * @return int
     */
    public static function getStaffConfirmTimeoutMinutes(): int
    {
        return max((int)ConfigService::get('transaction', 'staff_confirm_timeout_minutes', 60), 1);
    }

    /**
     * @notes 获取服务人员确认超时动作
     * @return string
     */
    public static function getStaffConfirmTimeoutAction(): string
    {
        $action = (string)ConfigService::get('transaction', 'staff_confirm_timeout_action', 'cancel');
        return in_array($action, ['cancel', 'auto_confirm'], true) ? $action : 'cancel';
    }

    /**
     * @notes 获取服务人员确认超时动作文案
     * @param string|null $action
     * @return string
     */
    public static function getStaffConfirmTimeoutActionDesc(?string $action = null): string
    {
        $resolvedAction = $action ?: self::getStaffConfirmTimeoutAction();
        $map = [
            'cancel' => '自动取消',
            'auto_confirm' => '自动同意',
        ];

        return $map[$resolvedAction] ?? '自动取消';
    }

    /**
     * @notes 获取待支付超时动作文案
     * @return string
     */
    public static function getPayTimeoutActionDesc(): string
    {
        return '自动取消';
    }

    /**
     * @notes 获取定金配置
     * @return array
     */
    public static function getDepositConfig(): array
    {
        $enabled = (int) ConfigService::get('order_payment', 'enable_deposit_mode', 0) === 1;
        $type = (string) ConfigService::get('order_payment', 'deposit_type', 'ratio');
        $value = round((float) ConfigService::get('order_payment', 'deposit_value', 30), 2);

        if (!in_array($type, ['fixed', 'ratio'], true)) {
            $type = 'ratio';
        }

        if ($value < 0) {
            $value = 0;
        }

        return [
            'enabled' => $enabled,
            'deposit_type' => $type,
            'deposit_value' => $value,
            'deposit_remark' => (string) ConfigService::get('order_payment', 'deposit_remark', ''),
        ];
    }

    /**
     * @notes 是否允许用户完成服务
     * @return bool
     */
    public static function canUserCompleteService(): bool
    {
        return (int) ConfigService::get('feature_switch', 'order_complete_by_user', 0) === 1;
    }

    /**
     * @notes 是否允许服务人员完成服务
     * @return bool
     */
    public static function canStaffCompleteService(): bool
    {
        return (int) ConfigService::get('feature_switch', 'order_complete_by_staff', 0) === 1;
    }

    /**
     * @notes 获取完成服务开关配置
     * @return array
     */
    public static function getCompleteServiceSwitchConfig(): array
    {
        return [
            'order_complete_by_user' => self::canUserCompleteService() ? 1 : 0,
            'order_complete_by_staff' => self::canStaffCompleteService() ? 1 : 0,
        ];
    }

    /**
     * @notes 计算订单定金与尾款金额
     * @param float $payAmount
     * @return array
     */
    public static function calculatePaymentSplit(float $payAmount): array
    {
        $normalizedPayAmount = round(max($payAmount, 0), 2);
        $config = self::getDepositConfig();

        $depositAmount = 0.0;
        if ($config['enabled'] && $normalizedPayAmount > 0) {
            if ($config['deposit_type'] === 'fixed') {
                $depositAmount = min($config['deposit_value'], $normalizedPayAmount);
            } else {
                $ratio = min(max($config['deposit_value'], 0), 99.99);
                $depositAmount = round($normalizedPayAmount * $ratio / 100, 2);
            }
        }

        $depositAmount = round(max($depositAmount, 0), 2);
        $balanceAmount = round(max($normalizedPayAmount - $depositAmount, 0), 2);

        if ($depositAmount >= $normalizedPayAmount) {
            $depositAmount = $normalizedPayAmount;
            $balanceAmount = 0.0;
        }

        return [
            'deposit_amount' => $depositAmount,
            'balance_amount' => $balanceAmount,
            'deposit_mode_enabled' => $depositAmount > 0 ? 1 : 0,
            'deposit_type' => $config['deposit_type'],
            'deposit_value' => $config['deposit_value'],
            'deposit_remark' => $config['deposit_remark'],
        ];
    }

    /**
     * @notes 获取订单支付摘要
     * @param Order $order
     * @return array
     */
    public static function buildPaymentSummaryFromState(array $state): array
    {
        $payAmount = round((float) ($state['pay_amount'] ?? 0), 2);
        $depositAmount = round((float) ($state['deposit_amount'] ?? 0), 2);
        $balanceAmount = round((float) ($state['balance_amount'] ?? 0), 2);
        $paidAmount = round((float) ($state['paid_amount'] ?? 0), 2);
        $unpaidAmount = round(max($payAmount - $paidAmount, 0), 2);
        $orderStatus = (int) ($state['order_status'] ?? self::STATUS_PENDING_CONFIRM);
        $payStatus = (int) ($state['pay_status'] ?? self::PAY_STATUS_UNPAID);
        $paymentChannel = self::resolvePaymentChannel(
            $state['payment_channel'] ?? null,
            $state['pay_type'] ?? null,
            $state['pay_voucher'] ?? ''
        );

        $paymentMode = $depositAmount > 0 ? 'deposit' : 'full';
        $needPay = 'none';
        $needPayAmount = 0.0;
        $needPayLabel = '无需支付';
        $currentPayStage = 'paid';

        if ($depositAmount > 0) {
            if (!(int) ($state['deposit_paid'] ?? 0)) {
                $needPay = 'deposit';
                $needPayAmount = $depositAmount;
                $needPayLabel = '支付定金';
                $currentPayStage = 'deposit';
            } elseif (!(int) ($state['balance_paid'] ?? 0)) {
                $needPayAmount = $balanceAmount;
                if ($orderStatus === self::STATUS_PENDING_PAY) {
                    $needPay = 'balance';
                    $needPayLabel = '支付尾款';
                    $currentPayStage = 'balance';
                } else {
                    $needPayLabel = '服务完成后支付尾款';
                    $currentPayStage = 'balance_after_service';
                }
            }
        } elseif ($payStatus !== self::PAY_STATUS_PAID && $payAmount > 0) {
            $needPay = 'full';
            $needPayAmount = $unpaidAmount > 0 ? $unpaidAmount : $payAmount;
            $needPayLabel = '立即支付';
            $currentPayStage = 'full';
        }

        $descMap = [
            'deposit' => '待支付定金',
            'balance' => '待支付尾款',
            'balance_after_service' => '定金已支付，服务完成后支付尾款',
            'full' => '待全额支付',
            'paid' => '已完成支付',
        ];

        return [
            'payment_channel' => $paymentChannel,
            'payment_channel_desc' => self::getPaymentChannelText($paymentChannel),
            'payment_mode' => $paymentMode,
            'payment_mode_desc' => $paymentMode === 'deposit' ? '定金支付' : '全款支付',
            'total_amount' => round((float) ($state['total_amount'] ?? $payAmount), 2),
            'pay_amount' => $payAmount,
            'paid_amount' => $paidAmount,
            'unpaid_amount' => $unpaidAmount,
            'deposit_amount' => $depositAmount,
            'balance_amount' => $balanceAmount,
            'deposit_paid' => (int) ($state['deposit_paid'] ?? 0),
            'balance_paid' => (int) ($state['balance_paid'] ?? 0),
            'need_pay' => $needPay,
            'need_pay_amount' => round($needPayAmount, 2),
            'need_pay_label' => $needPayLabel,
            'current_pay_stage' => $currentPayStage,
            'current_pay_stage_desc' => $descMap[$currentPayStage] ?? '待支付',
            'deposit_remark' => (string) ($state['deposit_remark_snapshot'] ?? ConfigService::get('order_payment', 'deposit_remark', '')),
        ];
    }

    public static function getPaymentSummary(self $order): array
    {
        return self::buildPaymentSummaryFromState($order->toArray());
    }

    /**
     * @notes 获取服务人员确认超时摘要
     * @param Order $order
     * @return array
     */
    public static function getConfirmTimeoutSummary(self $order): array
    {
        $action = self::getStaffConfirmTimeoutAction();

        return [
            'confirm_deadline_time' => (int)($order->confirm_deadline_time ?? 0),
            'confirm_remain_seconds' => $order->getConfirmRemainSeconds(),
            'confirm_timeout_action' => $action,
            'confirm_timeout_action_desc' => $order->shouldDisplayConfirmCountdown()
                ? self::getStaffConfirmTimeoutActionDesc($action)
                : '',
        ];
    }

    /**
     * @notes 根据状态构造服务人员确认超时摘要
     * @param int $orderStatus
     * @param int $confirmDeadlineTime
     * @return array
     */
    public static function buildConfirmTimeoutSummaryFromState(int $orderStatus, int $confirmDeadlineTime): array
    {
        $action = self::getStaffConfirmTimeoutAction();
        $shouldDisplay = self::isStaffConfirmTimeoutEnabled()
            && $orderStatus === self::STATUS_PENDING_CONFIRM
            && $confirmDeadlineTime > 0;

        return [
            'confirm_deadline_time' => $shouldDisplay ? $confirmDeadlineTime : 0,
            'confirm_remain_seconds' => $shouldDisplay ? max($confirmDeadlineTime - time(), 0) : 0,
            'confirm_timeout_action' => $action,
            'confirm_timeout_action_desc' => $shouldDisplay ? self::getStaffConfirmTimeoutActionDesc($action) : '',
        ];
    }

    /**
     * @notes 获取待支付超时摘要
     * @param Order $order
     * @return array
     */
    public static function getPayTimeoutSummary(self $order): array
    {
        $shouldDisplay = $order->shouldDisplayPayCountdown();
        $actionDesc = $shouldDisplay
            ? self::resolvePayTimeoutActionDescFromState($order->toArray())
            : '';

        return [
            'pay_deadline_time' => (int)($order->pay_deadline_time ?? 0),
            'pay_remain_seconds' => $order->getPayRemainSeconds(),
            'pay_timeout_action' => 'cancel',
            'pay_timeout_action_desc' => $actionDesc,
        ];
    }

    /**
     * @notes 根据状态构造待支付超时摘要
     * @param int $orderStatus
     * @param int $payDeadlineTime
     * @return array
     */
    public static function buildPayTimeoutSummaryFromState(int $orderStatus, int $payDeadlineTime, array $state = []): array
    {
        $shouldDisplay = self::isUnpaidAutoCancelEnabled()
            && $orderStatus === self::STATUS_PENDING_PAY
            && $payDeadlineTime > 0;

        return [
            'pay_deadline_time' => $shouldDisplay ? $payDeadlineTime : 0,
            'pay_remain_seconds' => $shouldDisplay ? max($payDeadlineTime - time(), 0) : 0,
            'pay_timeout_action' => 'cancel',
            'pay_timeout_action_desc' => $shouldDisplay ? self::resolvePayTimeoutActionDescFromState($state + ['order_status' => $orderStatus]) : '',
        ];
    }

    /**
     * @notes 根据订单状态推导支付超时动作文案
     * @param array $state
     * @return string
     */
    protected static function resolvePayTimeoutActionDescFromState(array $state): string
    {
        $isBalanceStage = (int)($state['order_status'] ?? 0) === self::STATUS_PENDING_PAY
            && round((float)($state['deposit_amount'] ?? 0), 2) > 0
            && (int)($state['deposit_paid'] ?? 0) === 1
            && (int)($state['balance_paid'] ?? 0) === 0;

        return $isBalanceStage ? '自动完成订单' : self::getPayTimeoutActionDesc();
    }

    /**
     * @notes 是否为线下凭证待审核
     * @return bool
     */
    public function isOfflineVoucherPending(): bool
    {
        return $this->getResolvedPaymentChannel() === self::PAYMENT_CHANNEL_OFFLINE
            && !empty($this->pay_voucher)
            && (int)($this->pay_voucher_status ?? -1) === self::VOUCHER_STATUS_PENDING;
    }

    /**
     * @notes 获取当前订单解析后的支付渠道
     * @return int
     */
    public function getResolvedPaymentChannel(): int
    {
        return self::resolvePaymentChannel(
            $this->payment_channel ?? null,
            $this->pay_type ?? null,
            $this->pay_voucher ?? ''
        );
    }

    /**
     * @notes 是否处于待服务状态
     * @return bool
     */
    public function isPendingServiceStage(): bool
    {
        return (int)($this->order_status ?? -1) === self::STATUS_PENDING_SERVICE;
    }

    /**
     * @notes 是否允许开始服务
     * @return bool
     */
    public function canStartService(): bool
    {
        return $this->isPendingServiceStage();
    }

    /**
     * @notes 判断整单是否全部归属于同一服务人员（排除已取消订单项）
     * @param int $orderId
     * @param int $staffId
     * @return bool
     */
    public static function isWholeOrderOwnedByStaff(int $orderId, int $staffId): bool
    {
        if ($orderId <= 0 || $staffId <= 0) {
            return false;
        }

        $totalCount = OrderItem::where('order_id', $orderId)
            ->where('item_status', '<>', OrderItem::STATUS_CANCELLED)
            ->count();
        if ($totalCount <= 0) {
            return false;
        }

        $ownedCount = OrderItem::where('order_id', $orderId)
            ->where('item_status', '<>', OrderItem::STATUS_CANCELLED)
            ->where('staff_id', $staffId)
            ->count();

        return $totalCount === $ownedCount;
    }

    /**
     * @notes 是否处于首笔待支付阶段
     * @return bool
     */
    public function isInFirstPendingPaymentStage(): bool
    {
        if ((int)$this->order_status !== self::STATUS_PENDING_PAY) {
            return false;
        }

        if ((float)$this->deposit_amount > 0) {
            return !(int)$this->deposit_paid;
        }

        if ((int)$this->pay_status === self::PAY_STATUS_PAID) {
            return false;
        }

        return round(max((float)$this->pay_amount - (float)($this->paid_amount ?? 0), 0), 2) > 0;
    }

    /**
     * @notes 是否处于尾款待支付阶段
     * @return bool
     */
    public function isInBalancePendingPaymentStage(): bool
    {
        return (int)$this->order_status === self::STATUS_PENDING_PAY
            && (float)$this->deposit_amount > 0
            && (int)($this->deposit_paid ?? 0) === 1
            && (int)($this->balance_paid ?? 0) === 0
            && round((float)($this->balance_amount ?? 0), 2) > 0;
    }

    /**
     * @notes 是否处于任意待支付阶段
     * @return bool
     */
    public function isInPendingPaymentStage(): bool
    {
        return $this->isInFirstPendingPaymentStage() || $this->isInBalancePendingPaymentStage();
    }

    /**
     * @notes 是否展示支付倒计时
     * @return bool
     */
    public function shouldDisplayPayCountdown(): bool
    {
        return self::isUnpaidAutoCancelEnabled()
            && $this->isInPendingPaymentStage()
            && (int)($this->pay_deadline_time ?? 0) > 0;
    }

    /**
     * @notes 是否展示服务人员确认倒计时
     * @return bool
     */
    public function shouldDisplayConfirmCountdown(): bool
    {
        return self::isStaffConfirmTimeoutEnabled()
            && (int)($this->order_status ?? self::STATUS_PENDING_CONFIRM) === self::STATUS_PENDING_CONFIRM
            && (int)($this->confirm_deadline_time ?? 0) > 0;
    }

    /**
     * @notes 获取服务人员确认剩余秒数
     * @return int
     */
    public function getConfirmRemainSeconds(): int
    {
        if (
            (int)($this->order_status ?? self::STATUS_PENDING_CONFIRM) !== self::STATUS_PENDING_CONFIRM
            || (int)($this->confirm_deadline_time ?? 0) <= 0
        ) {
            return 0;
        }

        return max((int)($this->confirm_deadline_time ?? 0) - time(), 0);
    }

    /**
     * @notes 获取支付剩余秒数
     * @return int
     */
    public function getPayRemainSeconds(): int
    {
        if (!$this->shouldDisplayPayCountdown()) {
            return 0;
        }

        return max((int)($this->pay_deadline_time ?? 0) - time(), 0);
    }

    /**
     * @notes 是否符合自动取消未支付条件
     * @return bool
     */
    public function shouldAutoCancelExpiredUnpaid(): bool
    {
        return self::isUnpaidAutoCancelEnabled()
            && $this->isInFirstPendingPaymentStage()
            && (int)($this->pay_deadline_time ?? 0) > 0
            && (int)$this->pay_deadline_time <= time();
    }

    /**
     * @notes 是否符合尾款超时自动收口条件
     * @return bool
     */
    public function shouldAutoCloseExpiredBalancePayment(): bool
    {
        return self::isUnpaidAutoCancelEnabled()
            && $this->isInBalancePendingPaymentStage()
            && (int)($this->pay_deadline_time ?? 0) > 0
            && (int)$this->pay_deadline_time <= time();
    }

    /**
     * @notes 是否符合服务人员确认超时自动处理条件
     * @return bool
     */
    public function shouldAutoHandleExpiredConfirm(): bool
    {
        return self::isStaffConfirmTimeoutEnabled()
            && (int)($this->order_status ?? self::STATUS_PENDING_CONFIRM) === self::STATUS_PENDING_CONFIRM
            && (int)($this->confirm_deadline_time ?? 0) > 0
            && (int)($this->confirm_deadline_time ?? 0) <= time();
    }

    /**
     * @notes 构建支付截止时间
     * @param int $startTime
     * @return int
     */
    public static function buildPayDeadlineTime(int $startTime): int
    {
        if (!self::isUnpaidAutoCancelEnabled()) {
            return 0;
        }

        return $startTime + self::getUnpaidAutoCancelMinutes() * 60;
    }

    /**
     * @notes 构建服务人员确认截止时间
     * @param int $startTime
     * @return int
     */
    public static function buildConfirmDeadlineTime(int $startTime): int
    {
        if (!self::isStaffConfirmTimeoutEnabled()) {
            return 0;
        }

        return $startTime + self::getStaffConfirmTimeoutMinutes() * 60;
    }

    /**
     * @notes 同步待确认截止时间
     * @param Order $order
     * @param int|null $startTime
     * @param bool $persist
     * @return int
     */
    public static function syncPendingConfirmDeadline(self $order, ?int $startTime = null, bool $persist = true): int
    {
        $deadlineTime = 0;
        if (
            self::isStaffConfirmTimeoutEnabled()
            && (int)($order->order_status ?? self::STATUS_PENDING_CONFIRM) === self::STATUS_PENDING_CONFIRM
        ) {
            $deadlineTime = self::buildConfirmDeadlineTime($startTime ?? time());
        }

        $order->confirm_deadline_time = $deadlineTime;

        if ($persist) {
            $order->update_time = time();
            $order->save();
        }

        return $deadlineTime;
    }

    /**
     * @notes 清空服务人员确认截止时间
     * @param Order $order
     * @param bool $persist
     * @return void
     */
    public static function clearConfirmDeadline(self $order, bool $persist = true): void
    {
        $order->confirm_deadline_time = 0;

        if ($persist) {
            $order->update_time = time();
            $order->save();
        }
    }

    /**
     * @notes 同步待支付截止时间
     * @param Order $order
     * @param int|null $startTime
     * @param bool $persist
     * @return int
     */
    public static function syncPendingPayDeadline(self $order, ?int $startTime = null, bool $persist = true): int
    {
        $deadlineTime = 0;
        if (self::isUnpaidAutoCancelEnabled() && $order->isInPendingPaymentStage()) {
            $deadlineTime = self::buildPayDeadlineTime($startTime ?? time());
        }

        $order->pay_deadline_time = $deadlineTime;

        if ($persist) {
            $order->update_time = time();
            $order->save();
        }

        return $deadlineTime;
    }

    /**
     * @notes 清空支付截止时间
     * @param Order $order
     * @param bool $persist
     * @return void
     */
    public static function clearPayDeadline(self $order, bool $persist = true): void
    {
        $order->pay_deadline_time = 0;

        if ($persist) {
            $order->update_time = time();
            $order->save();
        }
    }

    /**
     * @notes 用数据库最新状态刷新运行时订单对象，避免并发下继续使用旧状态
     * @param Order $target
     * @param Order $source
     * @return void
     */
    protected static function refreshRuntimeState(self $target, self $source): void
    {
        foreach ([
            'order_status',
            'pay_status',
            'pay_type',
            'payment_channel',
            'pay_time',
            'confirm_deadline_time',
            'pay_deadline_time',
            'cancel_reason',
            'cancel_time',
            'deposit_paid',
            'balance_paid',
            'paid_amount',
            'update_time',
        ] as $field) {
            $target->{$field} = $source->{$field};
        }
    }

    /**
     * @notes 同步触发超时取消
     * @param Order $order
     * @return bool
     */
    public static function syncExpiredAutoCancel(self $order): bool
    {
        if (!$order->shouldAutoCancelExpiredUnpaid()) {
            if (!$order->shouldAutoCloseExpiredBalancePayment()) {
                return false;
            }

            [$success, ] = self::autoCloseExpiredBalancePayment((int)$order->id);
            if ($success) {
                $order->order_status = self::STATUS_COMPLETED;
                $order->pay_deadline_time = 0;
                return true;
            }

            $latestOrder = self::find((int)$order->id);
            if ($latestOrder) {
                self::refreshRuntimeState($order, $latestOrder);
            }

            return false;
        }

        [$success, ] = self::autoCancelExpiredOrder((int)$order->id);
        if ($success) {
            $order->order_status = self::STATUS_CANCELLED;
            $order->cancel_reason = self::AUTO_CANCEL_REASON;
            $order->cancel_time = time();
            $order->confirm_deadline_time = 0;
            $order->pay_deadline_time = 0;
            $order->pay_status = self::PAY_STATUS_UNPAID;
            return true;
        }

        $latestOrder = self::find((int)$order->id);
        if ($latestOrder) {
            self::refreshRuntimeState($order, $latestOrder);
        }

        return false;
    }

    /**
     * @notes 自动取消超时未支付订单
     * @param int $orderId
     * @param string $reason
     * @return array
     */
    public static function autoCancelExpiredOrder(int $orderId, string $reason = self::AUTO_CANCEL_REASON): array
    {
        $order = self::find($orderId);
        if (!$order) {
            return [false, '订单不存在'];
        }

        if (!$order->shouldAutoCancelExpiredUnpaid()) {
            return [false, '订单未达到自动取消条件'];
        }

        [$success, $message] = self::cancelOrder($orderId, 0, OrderLog::OPERATOR_SYSTEM, $reason);
        if (!$success) {
            return [$success, $message];
        }

        Payment::markOrderPendingAsFailed($orderId);
        return [true, $message];
    }

    /**
     * @notes 自动收口超时未支付尾款订单
     * @param int $orderId
     * @return array
     */
    public static function autoCloseExpiredBalancePayment(int $orderId): array
    {
        Db::startTrans();
        try {
            /** @var Order|null $order */
            $order = self::where('id', $orderId)->lock(true)->find();
            if (!$order) {
                throw new \RuntimeException('订单不存在');
            }

            if (!$order->shouldAutoCloseExpiredBalancePayment()) {
                throw new \RuntimeException('订单未达到尾款超时收口条件');
            }

            $beforeStatus = (int)$order->order_status;
            $order->order_status = self::STATUS_COMPLETED;
            $order->pay_deadline_time = 0;
            if ((int)($order->complete_time ?? 0) <= 0) {
                $order->complete_time = time();
            }
            $order->update_time = time();
            $order->save();

            OrderLog::addLog(
                (int)$order->id,
                OrderLog::OPERATOR_SYSTEM,
                0,
                'balance_timeout_close',
                $beforeStatus,
                self::STATUS_COMPLETED,
                self::BALANCE_PAYMENT_TIMEOUT_MESSAGE
            );

            Db::commit();
            return [true, self::BALANCE_PAYMENT_TIMEOUT_MESSAGE];
        } catch (\Throwable $e) {
            Db::rollback();
            return [false, $e->getMessage()];
        }
    }

    /**
     * @notes 自动处理超时待支付订单（首笔未支付取消、尾款超时收口）
     * @param int $orderId
     * @return array
     */
    public static function autoHandleExpiredPendingPay(int $orderId): array
    {
        $order = self::find($orderId);
        if (!$order) {
            return [false, '订单不存在'];
        }

        if ($order->shouldAutoCancelExpiredUnpaid()) {
            return self::autoCancelExpiredOrder($orderId);
        }

        if ($order->shouldAutoCloseExpiredBalancePayment()) {
            return self::autoCloseExpiredBalancePayment($orderId);
        }

        return [false, '订单未达到待支付超时处理条件'];
    }

    /**
     * @notes 自动处理超时未确认订单
     * @param int $orderId
     * @return array
     */
    public static function autoHandleExpiredPendingConfirm(int $orderId): array
    {
        $order = self::find($orderId);
        if (!$order) {
            return [false, '订单不存在'];
        }

        if (!$order->shouldAutoHandleExpiredConfirm()) {
            return [false, '订单未达到确认超时处理条件'];
        }

        $action = self::getStaffConfirmTimeoutAction();
        if ($action === 'auto_confirm') {
            return self::autoConfirmExpiredOrder($orderId);
        }

        return self::autoCancelExpiredPendingConfirm($orderId);
    }

    /**
     * @notes 自动取消超时未确认订单
     * @param int $orderId
     * @return array
     */
    public static function autoCancelExpiredPendingConfirm(int $orderId): array
    {
        return self::cancelOrder(
            $orderId,
            0,
            OrderLog::OPERATOR_SYSTEM,
            self::STAFF_CONFIRM_TIMEOUT_CANCEL_REASON
        );
    }

    /**
     * @notes 自动同意超时未确认订单
     * @param int $orderId
     * @return array
     */
    public static function autoConfirmExpiredOrder(int $orderId): array
    {
        $shouldNotifyUser = false;

        try {
            $result = Db::transaction(function () use ($orderId, &$shouldNotifyUser) {
                /** @var Order|null $order */
                $order = self::where('id', $orderId)->lock(true)->find();
                if (!$order) {
                    throw new \RuntimeException('订单不存在');
                }

                if (!$order->shouldAutoHandleExpiredConfirm()) {
                    throw new \RuntimeException('订单未达到确认超时处理条件');
                }

                $beforeStatus = (int)$order->order_status;
                $pendingItems = OrderItem::where('order_id', $orderId)
                    ->where('item_status', '<>', OrderItem::STATUS_CANCELLED)
                    ->where('confirm_status', 0)
                    ->select();

                foreach ($pendingItems as $item) {
                    if (
                        (int)($item->staff_id ?? 0) > 0
                        && !empty($item->service_date)
                        && in_array(
                            (int)($item->item_type ?? OrderItem::TYPE_SERVICE),
                            [OrderItem::TYPE_SERVICE, OrderItem::TYPE_RELATED_STAFF],
                            true
                        )
                    ) {
                        $scheduleResult = Schedule::confirmBooking(
                            (int)$item->staff_id,
                            (string)$item->service_date,
                            0,
                            $orderId,
                            (int)$order->user_id
                        );
                        if (!($scheduleResult[0] ?? false)) {
                            throw new \RuntimeException((string)($scheduleResult[1] ?? '档期确认失败'));
                        }

                        $scheduleId = (int)($scheduleResult['schedule_id'] ?? 0);
                        if ($scheduleId > 0 && (int)($item->schedule_id ?? 0) <= 0) {
                            $item->schedule_id = $scheduleId;
                            $item->time_slot = 0;
                        }
                    }

                    $item->confirm_status = 1;
                    $item->update_time = time();
                    $item->save();
                }

                $remain = OrderItem::where('order_id', $orderId)
                    ->where('item_status', '<>', OrderItem::STATUS_CANCELLED)
                    ->where('confirm_status', 0)
                    ->count();

                if ($remain > 0) {
                    throw new \RuntimeException('仍存在未确认订单项');
                }

                $order->order_status = self::STATUS_PENDING_PAY;
                $order->confirm_deadline_time = 0;
                $order->update_time = time();
                $order->save();

                self::syncPendingPayDeadline($order);
                $shouldNotifyUser = true;

                OrderLog::addLog(
                    $orderId,
                    OrderLog::OPERATOR_SYSTEM,
                    0,
                    'auto_confirm_timeout',
                    $beforeStatus,
                    self::STATUS_PENDING_PAY,
                    self::STAFF_CONFIRM_TIMEOUT_AUTO_CONFIRM_REASON
                );

                return true;
            });

            if ($result && $shouldNotifyUser) {
                OrderNotificationService::notifyUserOnOrderConfirmed(
                    $orderId,
                    '服务人员确认超时，系统已自动同意订单',
                    '订单%s因服务人员确认超时，系统已自动同意，请尽快完成支付。',
                    '订单已自动同意，请支付'
                );
            }

            return [true, '订单已自动同意'];
        } catch (\Throwable $e) {
            return [false, $e->getMessage()];
        }
    }

    /**
     * @notes 创建订单
     * @param int $userId
     * @param array $selectedItems 已选服务项
     * @param array $orderInfo 订单信息
     * @return array [bool $success, string $message, Order|null $order]
     */
    public static function createOrder(int $userId, array $selectedItems, array $orderInfo): array
    {
        Db::startTrans();
        try {
            // 计算订单金额
            $serviceAmount = 0;
            $addonAmount = 0;
            foreach ($selectedItems as $item) {
                $itemAmount = round((float)$item['price'] * (int)($item['quantity'] ?? 1), 2);
                if ((int)($item['item_type'] ?? OrderItem::TYPE_SERVICE) === OrderItem::TYPE_SERVICE) {
                    $serviceAmount += $itemAmount;
                } else {
                    $addonAmount += $itemAmount;
                }

                foreach (($item['addons'] ?? []) as $addon) {
                    $addonAmount += (float)($addon['price'] ?? 0) * (int)($addon['quantity'] ?? 1);
                }
            }
            $serviceAmount = round($serviceAmount, 2);
            $addonAmount = round($addonAmount, 2);
            $totalAmount = round($serviceAmount + $addonAmount, 2);

            // 计算优惠
            $discountAmount = $orderInfo['discount_amount'] ?? 0;
            $payAmount = $totalAmount - $discountAmount;

            $paymentSplit = self::calculatePaymentSplit((float) $payAmount);
            $depositAmount = (float) $paymentSplit['deposit_amount'];
            $balanceAmount = (float) $paymentSplit['balance_amount'];

            $confirmLockDuration = 3600;
            $createdLegacyAddonSnapshots = false;

            // 创建订单
            $order = self::create([
                'order_sn' => self::generateOrderSn(),
                'user_id' => $userId,
                'order_type' => $orderInfo['order_type'] ?? self::TYPE_NORMAL,
                'order_status' => self::STATUS_PENDING_CONFIRM,
                'pay_status' => self::PAY_STATUS_UNPAID,
                'paid_amount' => 0,
                'total_amount' => $totalAmount,
                'addon_amount' => $addonAmount,
                'discount_amount' => $discountAmount,
                'pay_amount' => $payAmount,
                'deposit_amount' => $depositAmount,
                'balance_amount' => $balanceAmount,
                'deposit_mode_enabled' => (int) $paymentSplit['deposit_mode_enabled'],
                'deposit_type_snapshot' => (string) $paymentSplit['deposit_type'],
                'deposit_value_snapshot' => (float) $paymentSplit['deposit_value'],
                'deposit_remark_snapshot' => (string) $paymentSplit['deposit_remark'],
                'service_date' => $orderInfo['date'] ?? $orderInfo['service_date'] ?? ($selectedItems[0]['schedule_date'] ?? null),
                'service_time_slot' => 0,
                'service_address' => $orderInfo['service_address'] ?? '',
                'service_province_code' => $orderInfo['province_code'] ?? '',
                'service_province' => $orderInfo['province_name'] ?? '',
                'service_city_code' => $orderInfo['city_code'] ?? '',
                'service_city' => $orderInfo['city_name'] ?? '',
                'service_district_code' => $orderInfo['district_code'] ?? '',
                'service_district' => $orderInfo['district_name'] ?? '',
                'contact_name' => $orderInfo['contact_name'] ?? '',
                'contact_mobile' => $orderInfo['contact_mobile'] ?? '',
                'user_remark' => $orderInfo['remark'] ?? '',
                'source' => $orderInfo['source'] ?? self::SOURCE_MINIAPP,
                'pay_type' => self::PAY_WAY_NONE,
                'payment_channel' => self::resolvePaymentChannel(
                    $orderInfo['payment_channel'] ?? self::PAYMENT_CHANNEL_ONLINE
                ),
                'confirm_deadline_time' => self::buildConfirmDeadlineTime(time()),
                'create_time' => time(),
                'update_time' => time(),
            ]);

            // 创建订单项
            foreach ($selectedItems as $item) {
                $orderItem = OrderItem::create([
                    'order_id' => $order->id,
                    'staff_id' => $item['staff_id'],
                    'package_id' => $item['package_id'] ?? 0,
                    'schedule_id' => $item['schedule_id'] ?? 0,
                    'service_date' => $item['schedule_date'],
                    'time_slot' => 0,
                    'staff_name' => $item['staff']['name'] ?? ($item['staff_name'] ?? ''),
                    'package_name' => $item['package']['name'] ?? ($item['package_name'] ?? ''),
                    'package_description' => OrderItem::resolvePackageDescription(
                        (int)($item['package_id'] ?? 0),
                        (string)($item['package']['description'] ?? ($item['package_description'] ?? ''))
                    ),
                    'price' => $item['price'],
                    'quantity' => (int)($item['quantity'] ?? 1),
                    'subtotal' => round((float)$item['price'] * (int)($item['quantity'] ?? 1), 2),
                    'item_type' => (int)($item['item_type'] ?? OrderItem::TYPE_SERVICE),
                    'item_meta' => $item['item_meta'] ?? [],
                    'confirm_status' => 0,
                    'remark' => $item['remark'] ?? '',
                    'create_time' => time(),
                    'update_time' => time(),
                ]);

                if (
                    (int)($item['staff_id'] ?? 0) > 0 &&
                    !empty($item['schedule_date']) &&
                    in_array(
                        (int)($item['item_type'] ?? OrderItem::TYPE_SERVICE),
                        [OrderItem::TYPE_SERVICE, OrderItem::TYPE_RELATED_STAFF],
                        true
                    )
                ) {
                    $scheduleResult = Schedule::confirmBooking(
                        (int)$item['staff_id'],
                        (string)$item['schedule_date'],
                        0,
                        (int)$order->id,
                        $userId
                    );
                    if (!($scheduleResult[0] ?? false)) {
                        throw new \Exception((string)($scheduleResult[1] ?? '档期锁定失败'));
                    }

                    $scheduleId = (int)($scheduleResult['schedule_id'] ?? 0);
                    if ($scheduleId > 0) {
                        $orderItem->schedule_id = $scheduleId;
                        $orderItem->time_slot = 0;
                        $orderItem->save();
                    }
                }

                if (
                    !empty($item['package_id']) &&
                    in_array(
                        (int)($item['item_type'] ?? OrderItem::TYPE_SERVICE),
                        [OrderItem::TYPE_SERVICE, OrderItem::TYPE_RELATED_STAFF],
                        true
                    )
                ) {
                    $confirmed = PackageBooking::confirmSelection(
                        $userId,
                        (int)$item['package_id'],
                        (int)$item['staff_id'],
                        (string)$item['schedule_date'],
                        0,
                        (int)$order->id,
                        (int)$orderItem->id
                    );
                    if (!$confirmed) {
                        $availability = PackageBooking::checkAvailability(
                            (int)$item['package_id'],
                            (string)$item['schedule_date'],
                            (int)$item['staff_id'],
                            0
                        );
                        $message = $availability['available'] ?? false
                            ? '套餐预订锁定失败，请刷新后重试'
                            : ($availability['message'] ?? '套餐预订锁定失败');
                        throw new \Exception($message);
                    }
                }

                if (!empty($item['addons']) && is_array($item['addons'])) {
                    OrderItemAddon::createSnapshots(
                        (int)$order->id,
                        (int)$orderItem->id,
                        $item['addons'],
                        OrderItemAddon::SOURCE_ORDER
                    );
                    $createdLegacyAddonSnapshots = true;
                }
            }

            if ($createdLegacyAddonSnapshots) {
                OrderItemAddon::refreshOrderAmounts((int)$order->id);
            }

            // 记录订单日志
            OrderLog::addLog($order->id, 1, $userId, 'create', 0, self::STATUS_PENDING_CONFIRM, '创建订单');

            $waitlistId = (int)($orderInfo['waitlist_id'] ?? 0);
            if ($waitlistId > 0) {
                $primaryServiceItem = null;
                foreach ($selectedItems as $selectedItem) {
                    if ((int)($selectedItem['item_type'] ?? OrderItem::TYPE_SERVICE) === OrderItem::TYPE_SERVICE) {
                        $primaryServiceItem = $selectedItem;
                        break;
                    }
                }

                if (!$primaryServiceItem) {
                    throw new \RuntimeException('候补转正失败：缺少主服务信息');
                }

                [$consumed, $consumeMessage] = Waitlist::consumeForOrder(
                    $waitlistId,
                    (int)$order->id,
                    $userId,
                    (int)($primaryServiceItem['staff_id'] ?? 0),
                    (string)($primaryServiceItem['schedule_date'] ?? '')
                );

                if (!$consumed) {
                    throw new \RuntimeException($consumeMessage ?: '候补转正失败');
                }

                OrderLog::addLog(
                    (int)$order->id,
                    OrderLog::OPERATOR_SYSTEM,
                    0,
                    'waitlist_convert',
                    self::STATUS_PENDING_CONFIRM,
                    self::STATUS_PENDING_CONFIRM,
                    '候补转正式预约，候补ID：' . $waitlistId
                );
            }

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
        $createdRefundId = 0;
        Db::startTrans();
        try {
            $order = self::where('id', $orderId)->lock(true)->find();
            if (!$order) {
                throw new \RuntimeException('订单不存在');
            }

            if (!in_array($order->order_status, [self::STATUS_PENDING_CONFIRM, self::STATUS_PENDING_PAY, self::STATUS_PENDING_SERVICE], true)) {
                throw new \RuntimeException('当前订单状态不可取消');
            }

            $beforeStatus = $order->order_status;

            // 更新订单状态
            $order->order_status = self::STATUS_CANCELLED;
            $order->cancel_reason = $reason;
            $order->cancel_time = time();
            $order->confirm_deadline_time = 0;
            $order->pay_deadline_time = 0;
            OrderConfirmLetterService::invalidateCurrentLetter($order, false);
            $order->update_time = time();
            $order->save();

            // 释放档期
            $items = OrderItem::where('order_id', $orderId)->select();
            foreach ($items as $item) {
                if ($item->schedule_id > 0) {
                    Schedule::releaseLock($item->schedule_id);
                }
            }

            // 释放套餐预订锁
            PackageBooking::releaseByOrderId($orderId);

            // 记录日志
            OrderLog::addLog($orderId, $operatorType, $operatorId, 'cancel', $beforeStatus, self::STATUS_CANCELLED, '取消订单：' . $reason);

            // 已支付订单自动创建退款申请
            if (
                in_array($beforeStatus, [self::STATUS_PENDING_SERVICE, self::STATUS_IN_SERVICE], true)
                && (float)($order->paid_amount ?? 0) > 0
            ) {
                $refundResult = Refund::createSystemRefund(
                    $orderId,
                    $operatorId,
                    $order->paid_amount > 0 ? $order->paid_amount : $order->pay_amount,
                    '订单取消自动退款：' . $reason,
                    $operatorType == OrderLog::OPERATOR_USER ? Refund::TYPE_USER : Refund::TYPE_ADMIN
                );
                
                if (!$refundResult[0]) {
                    // 退款创建失败，记录日志但不影响取消操作
                    OrderLog::addLog($orderId, OrderLog::OPERATOR_SYSTEM, 0, 'refund_create_fail', 0, 0, '自动退款创建失败：' . $refundResult[1]);
                } elseif (!empty($refundResult[2])) {
                    $createdRefundId = (int)$refundResult[2]->id;
                }
            }

            Db::commit();

            OrderNotificationService::notifyUserAndStaffOnOrderCancelled($orderId, $operatorType, $reason);
            if ($createdRefundId > 0) {
                OrderNotificationService::notifyUserAndStaffOnRefundApplied($createdRefundId);
            }
            return [true, '订单已取消'];
        } catch (\Exception $e) {
            Db::rollback();
            return [false, $e->getMessage()];
        }
    }

    /**
     * @notes 支付成功后同步订单状态
     * @param Order $order
     * @param int $payType
     * @param int|null $paidAt
     * @return int
     */
    public static function applyPaidStateAfterPayment(self $order, int $payType, ?int $paidAt = null): int
    {
        $resolvedPaidAt = $paidAt ?: time();
        $hasBalance = round((float)($order->balance_amount ?? 0), 2) > 0;

        $order->confirm_deadline_time = 0;
        $order->pay_deadline_time = 0;
        $order->pay_time = $resolvedPaidAt;

        if ($payType === Payment::TYPE_DEPOSIT) {
            $order->deposit_paid = 1;
            $order->order_status = self::STATUS_PENDING_SERVICE;
            $order->pay_status = $hasBalance ? self::PAY_STATUS_UNPAID : self::PAY_STATUS_PAID;
            $order->complete_time = 0;
            if (!$hasBalance) {
                $order->balance_paid = 1;
            }
            return (int)$order->order_status;
        }

        if ($payType === Payment::TYPE_BALANCE) {
            $order->balance_paid = 1;
            $order->pay_status = self::PAY_STATUS_PAID;
            $order->order_status = self::STATUS_COMPLETED;
            $order->complete_time = (int)($order->complete_time ?? 0) > 0
                ? (int)$order->complete_time
                : $resolvedPaidAt;
            return (int)$order->order_status;
        }

        if ((float)($order->deposit_amount ?? 0) > 0) {
            $order->deposit_paid = 1;
            $order->balance_paid = 1;
        }

        $order->pay_status = self::PAY_STATUS_PAID;
        $order->order_status = self::STATUS_PENDING_SERVICE;
        $order->complete_time = 0;
        return (int)$order->order_status;
    }

    /**
     * @notes 完成订单
     * @param int $orderId
     * @param int $operatorId
     * @param int $operatorType
     * @return array [bool $success, string $message]
     */
    public static function completeOrder(int $orderId, int $operatorId, int $operatorType = 1, string $triggerRole = 'admin'): array
    {
        Db::startTrans();
        try {
            $order = self::where('id', $orderId)->lock(true)->find();
            if (!$order) {
                throw new \RuntimeException('订单不存在');
            }

            if ((int)$order->order_status !== self::STATUS_IN_SERVICE) {
                throw new \RuntimeException('当前订单状态不可完成');
            }

            if ($triggerRole === 'user' && !self::canUserCompleteService()) {
                throw new \RuntimeException('管理员未开启用户完成服务');
            }

            if ($triggerRole === 'staff' && !self::canStaffCompleteService()) {
                throw new \RuntimeException('管理员未开启服务人员完成服务');
            }

            $beforeStatus = (int)$order->order_status;
            $now = time();
            $hasPendingBalance = round((float)($order->balance_amount ?? 0), 2) > 0
                && !(int)($order->balance_paid ?? 0);
            $afterStatus = self::STATUS_COMPLETED;
            $message = '订单已完成';
            $logContent = '订单完成';

            if ($hasPendingBalance) {
                $afterStatus = self::STATUS_PENDING_PAY;
                $message = '服务已完成，待支付尾款';
                $logContent = '服务完成，进入尾款待支付';
                $order->complete_time = $now;
            } else {
                $order->complete_time = $now;
            }

            $order->order_status = $afterStatus;
            $order->update_time = $now;
            $order->save();

            OrderItem::where('order_id', $orderId)->update([
                'item_status' => 2,
                'update_time' => $now,
            ]);

            self::syncPendingPayDeadline($order, $now);

            OrderLog::addLog($orderId, $operatorType, $operatorId, 'complete', $beforeStatus, $afterStatus, $logContent);

            Db::commit();

            if ($afterStatus === self::STATUS_COMPLETED) {
                OrderNotificationService::notifyOnOrderCompleted($orderId);
            } else {
                OrderNotificationService::notifyOnOrderServiceCompleted($orderId);
            }

            return [true, $message];
        } catch (\Throwable $e) {
            Db::rollback();
            return [false, $e->getMessage()];
        }
    }

    /**
     * @notes 开始服务
     * @param int $orderId
     * @param int $operatorId
     * @param int $operatorType
     * @param string $logContent
     * @return array [bool $success, string $message]
     */
    public static function startService(
        int $orderId,
        int $operatorId,
        int $operatorType = OrderLog::OPERATOR_ADMIN,
        string $logContent = '开始服务'
    ): array {
        Db::startTrans();
        try {
            $order = self::where('id', $orderId)->lock(true)->find();
            if (!$order) {
                throw new \RuntimeException('订单不存在');
            }

            if (!$order->canStartService()) {
                throw new \RuntimeException('只有待服务的订单才能开始服务');
            }

            $beforeStatus = (int)$order->order_status;
            $now = time();

            $order->order_status = self::STATUS_IN_SERVICE;
            $order->start_service_time = $now;
            $order->update_time = $now;
            $order->save();

            OrderItem::where('order_id', $orderId)->update([
                'item_status' => OrderItem::STATUS_IN_SERVICE,
                'update_time' => $now,
            ]);

            OrderLog::addLog(
                $orderId,
                $operatorType,
                $operatorId,
                'start_service',
                $beforeStatus,
                self::STATUS_IN_SERVICE,
                $logContent
            );

            Db::commit();
            return [true, '开始服务成功'];
        } catch (\Throwable $e) {
            Db::rollback();
            return [false, $e->getMessage()];
        }
    }

    /**
     * @notes 获取订单状态选项
     * @return array
     */
    public static function getStatusOptions(): array
    {
        return [
            ['value' => self::STATUS_PENDING_CONFIRM, 'label' => '待确认'],
            ['value' => self::STATUS_PENDING_PAY, 'label' => '待支付'],
            ['value' => self::STATUS_PENDING_SERVICE, 'label' => '待服务'],
            ['value' => self::STATUS_IN_SERVICE, 'label' => '服务中'],
            ['value' => self::STATUS_COMPLETED, 'label' => '已完成'],
            ['value' => self::STATUS_REVIEWED, 'label' => '已评价'],
            ['value' => self::STATUS_CANCELLED, 'label' => '已取消'],
            ['value' => self::STATUS_PAUSED, 'label' => '已暂停'],
            ['value' => self::STATUS_REFUNDING, 'label' => '退款中'],
            ['value' => self::STATUS_REFUNDED, 'label' => '已退款'],
            ['value' => self::STATUS_USER_DELETED, 'label' => '用户已删除'],
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
            ['value' => self::PAY_WAY_COMBINATION, 'label' => '组合支付'],
        ];
    }

    /**
     * @notes 解析支付渠道，兼容历史数据
     * @param mixed $paymentChannel
     * @param mixed $payType
     * @param string $payVoucher
     * @return int
     */
    public static function resolvePaymentChannel($paymentChannel, $payType = null, string $payVoucher = ''): int
    {
        $resolvedChannel = (int)$paymentChannel;
        if (in_array($resolvedChannel, [self::PAYMENT_CHANNEL_ONLINE, self::PAYMENT_CHANNEL_OFFLINE], true)) {
            return $resolvedChannel;
        }

        if ((int)$payType === self::PAY_WAY_OFFLINE || trim($payVoucher) !== '') {
            return self::PAYMENT_CHANNEL_OFFLINE;
        }

        return self::PAYMENT_CHANNEL_ONLINE;
    }

    /**
     * @notes 获取支付渠道文案
     * @param int $paymentChannel
     * @return string
     */
    public static function getPaymentChannelText(int $paymentChannel): string
    {
        return match ($paymentChannel) {
            self::PAYMENT_CHANNEL_OFFLINE => '线下支付',
            default => '线上支付',
        };
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
            'addon_change' => 0,
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
                case OrderChange::TYPE_ADDON:
                    $stats['addon_change']++;
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
