<?php
declare(strict_types=1);

namespace app\common\service;

use app\common\logic\OrderPayLogic;
use app\common\model\auth\Admin;
use app\common\model\order\Order;
use app\common\model\order\OrderLog;
use app\common\model\order\Payment;
use app\common\model\staff\Staff;
use think\facade\Db;

/** 独立收款申请：申请本身不修改实收、支付记录或正式档期。 */
class OrderReceiptService
{
    public static function pending(int $orderId): bool
    {
        return Db::name('order_receipt_request')->where('pending_order_id', $orderId)->count() > 0;
    }

    public static function assertNoPending(int $orderId): void
    {
        if (self::pending($orderId)) throw new \RuntimeException('收款申请审核中，请先处理后再支付或调整订单');
    }

    public static function history(int $orderId): array
    {
        $rows = Db::name('order_receipt_request')->where('order_id', $orderId)->order('id desc')->select()->toArray();
        foreach ($rows as &$row) {
            unset($row['submit_key'], $row['request_hash'], $row['pending_order_id']);
            $row['pay_voucher'] = FileService::getFileUrl($row['pay_voucher']);
            $row['status_desc'] = ['待审核', '已通过', '已驳回'][(int)$row['status']] ?? '未知';
            $row['phase_desc'] = [1 => '定金', 2 => '尾款', 3 => '全款'][(int)$row['pay_type']] ?? '';
        }
        return $rows;
    }

    public static function phaseAmount(Order $order, int $phase): float
    {
        if ((int)$order->order_status !== Order::STATUS_PENDING_PAY) throw new \RuntimeException('当前订单未进入待付款阶段，尾款请在服务完成后提交');
        $remaining = MoneyService::yuanToFen($order->pay_amount) - MoneyService::yuanToFen($order->paid_amount);
        if ($remaining <= 0) throw new \RuntimeException('订单已无剩余应收');
        $context = OrderPayLogic::getCurrentPayContext($order);
        if ($phase === Payment::TYPE_FULL && MoneyService::yuanToFen($order->paid_amount) === 0) {
            return round($remaining / 100, 2);
        }
        if (!$context || (int)$context['pay_type'] !== $phase) throw new \RuntimeException('付款阶段已变化，请刷新订单');
        $amount = MoneyService::yuanToFen($context['pay_amount']);
        if ($amount <= 0 || $amount > $remaining) throw new \RuntimeException('阶段应收金额不正确，请联系管理员');
        return round($amount / 100, 2);
    }

    public static function submit(int $userId, array $params): array
    {
        return Db::transaction(static function () use ($userId, $params) {
            $staff = StaffManualOrderService::identity($userId, true);
            $order = Order::where('id', (int)($params['order_id'] ?? 0))->lock(true)->find();
            if (!$order) throw new \RuntimeException('订单不存在');
            return self::submitLocked($order, $staff, $userId, $params);
        });
    }

    /** 调用方必须持有订单事务锁及已验证的工作身份。 */
    public static function submitLocked(Order $order, Staff $staff, int $userId, array $params): array
    {
        if (!Order::isWholeOrderOwnedByStaff((int)$order->id, (int)$staff->id)) throw new \RuntimeException('只能提交本人完整负责订单的收款申请');
        $key = StaffManualOrderService::submitKey($userId, (string)($params['submit_key'] ?? ''));
        $receipt = Payment::validateOfflineReceipt(['voucher' => $params['voucher'] ?? '',
            'collection_owner' => $params['collection_owner'] ?? Payment::COLLECTION_STAFF]);
        $phase = (int)($params['pay_type'] ?? 0);
        $hash = hash('sha256', json_encode([(int)$order->id, $phase, $receipt], JSON_THROW_ON_ERROR));
        $existing = Db::name('order_receipt_request')->where('submit_key', $key)->lock(true)->find();
        if ($existing) {
            if (!hash_equals($existing['request_hash'], $hash)) throw new \RuntimeException('提交标识已使用，请刷新后重试');
            return ['id' => (int)$existing['id'], 'status' => (int)$existing['status']];
        }
        self::assertNoPending((int)$order->id);
        if ($order->isOfflineVoucherPending()) throw new \RuntimeException('已有线下凭证待审核');
        if (Payment::where('order_id', (int)$order->id)->where('pay_status', Payment::STATUS_PENDING)->lock(true)->find()) {
            throw new \RuntimeException('订单存在进行中的支付，请先核实支付结果');
        }
        $amount = self::phaseAmount($order, $phase);
        $id = (int)Db::name('order_receipt_request')->insertGetId([
            'order_id' => (int)$order->id, 'staff_id' => (int)$staff->id, 'submit_user_id' => $userId,
            'submit_key' => $key, 'request_hash' => $hash, 'pay_type' => $phase, 'amount' => $amount,
            'pay_voucher' => $receipt['pay_voucher'], 'collection_owner' => $receipt['collection_owner'],
            'status' => 0, 'create_time' => time(), 'update_time' => time(),
        ]);
        OrderLog::addLog((int)$order->id, OrderLog::OPERATOR_USER, $userId, 'receipt_submit',
            (int)$order->order_status, (int)$order->order_status, '服务人员提交收款申请，金额：' . $amount);
        $adminIds = array_map('intval', (array)ConfigService::get('order', 'receipt_audit_admin_ids', []));
        if (!$adminIds) $adminIds = Admin::where('root', 1)->where('disable', 0)->column('id');
        InternalNotificationService::send($adminIds, '收款申请待审核', '订单' . $order->order_sn . '有新的收款凭证，请前往后台核实到账。',
            'order_receipt', $id, ['event' => 'order_receipt_submitted', 'instance' => (string)$id]);
        return ['id' => $id, 'status' => 0];
    }

    public static function audit(int $orderId, int $receiptId, int $adminId, bool $approved, string $reason): array
    {
        if (!$approved && trim($reason) === '') throw new \InvalidArgumentException('请填写驳回原因');
        try {
            $result = Db::transaction(static function () use ($orderId, $receiptId, $adminId, $approved, $reason) {
                $order = Order::where('id', $orderId)->lock(true)->find();
                $row = Db::name('order_receipt_request')->where('id', $receiptId)->where('order_id', $orderId)->lock(true)->find();
                if (!$order || !$row) throw new \RuntimeException('收款申请不存在');
                $admin = Admin::where('id', $adminId)->where('disable', 0)->find();
                if (!$admin || (int)$admin->user_id === (int)$row['submit_user_id']) throw new \RuntimeException('不能审核本人提交的收款申请');
                if ((int)$row['status'] !== 0) {
                    if ((int)$row['status'] !== ($approved ? 1 : 2)) throw new \RuntimeException('该申请已经处理，请刷新查看');
                    return ['status' => (int)$row['status'], 'payment_id' => (int)$row['payment_id']];
                }
                $paymentId = 0;
                if ($approved) {
                    $amount = self::phaseAmount($order, (int)$row['pay_type']);
                    if (MoneyService::yuanToFen($amount) !== MoneyService::yuanToFen($row['amount'])) throw new \RuntimeException('当前应收与申请金额不一致，请核实已收款项');
                    if (Payment::where('order_id', $orderId)->where('pay_status', Payment::STATUS_PENDING)->lock(true)->find()) throw new \RuntimeException('存在未核实支付，请先处理支付结果');
                    if (Order::isFirstPaidStage((int)$row['pay_type'])) Order::lockSchedulesAfterFirstPayment($order);
                    $payment = Payment::create([
                        'payment_sn' => Payment::generatePaymentSn(), 'order_id' => $orderId,
                        'order_sn' => (string)$order->order_sn, 'user_id' => (int)$order->user_id,
                        'pay_type' => (int)$row['pay_type'], 'pay_way' => Payment::WAY_OFFLINE,
                        'collection_owner' => (int)$row['collection_owner'], 'pay_voucher' => $row['pay_voucher'],
                        'pay_amount' => $amount, 'pay_status' => Payment::STATUS_PAID,
                        'pay_time' => time(), 'create_time' => time(), 'update_time' => time(),
                    ]);
                    $paymentId = (int)$payment->id;
                    Order::applyPaidStateAfterPayment($order, (int)$row['pay_type'], (int)$payment->pay_time);
                    $order->paid_amount = round((float)$order->paid_amount + $amount, 2);
                    $order->pay_type = Order::PAY_WAY_OFFLINE;
                    $order->update_time = time();
                    $order->save();
                    ManualOrderService::recordSuccessfulPaymentFlow($order, $payment, $adminId);
                    StaffScheduleConfirmLetterService::markOutdatedByOrderId($orderId);
                    OrderNotificationService::notifyUserAndStaffOnPaymentSuccess($orderId, (int)$row['pay_type'], true);
                }
                Db::name('order_receipt_request')->where('id', $receiptId)->update([
                    'status' => $approved ? 1 : 2, 'payment_id' => $paymentId, 'audit_admin_id' => $adminId,
                    'audit_time' => time(), 'reason' => mb_substr(trim($reason), 0, 500), 'update_time' => time(),
                ]);
                OrderLog::addLog($orderId, OrderLog::OPERATOR_ADMIN, $adminId, 'receipt_audit',
                    (int)$order->order_status, (int)$order->order_status, ($approved ? '收款审核通过' : '收款审核驳回') . '：' . $reason);
                $staffUserId = (int)Staff::where('id', $row['staff_id'])->value('user_id');
                BusinessNotificationService::record([
                    'event' => 'order_receipt_audited', 'instance' => (string)$receiptId, 'user_id' => $staffUserId,
                    'audience' => 'staff', 'title' => $approved ? '收款审核通过' : '收款申请已驳回',
                    'content' => '订单' . $order->order_sn . '收款审核' . ($approved ? '通过。' : '未通过：' . $reason),
                    'target_type' => StationNotificationService::TARGET_STAFF_ORDER, 'target_id' => $orderId, 'business_type' => 'order', 'business_id' => $orderId,
                    'scene' => 'staff_order', 'page' => 'packages/pages/staff_order_detail/staff_order_detail?id=' . $orderId,
                ]);
                return ['status' => $approved ? 1 : 2, 'payment_id' => $paymentId];
            });
            if ($approved) {
                try {
                    $phase = (int)Db::name('order_receipt_request')->where('id', $receiptId)->value('pay_type');
                    OrderNotificationService::notifyUserAndStaffOnPaymentSuccess($orderId, $phase);
                    if ($phase === Payment::TYPE_BALANCE) {
                        \app\common\model\aftersale\ServiceCallback::autoCreateAfterServiceCallback($orderId);
                        OrderNotificationService::notifyOnOrderCompleted($orderId);
                    }
                } catch (\Throwable $e) {
                    \think\facade\Log::error('收款审核提交后续处理失败：' . $e->getMessage());
                }
            }
            return $result;
        } catch (\Throwable $e) {
            // 失败事务完全回滚，只在仍待审核时保留核实原因。
            Db::name('order_receipt_request')->where('id', $receiptId)->where('order_id', $orderId)->where('status', 0)
                ->update(['reason' => mb_substr($e->getMessage(), 0, 500), 'update_time' => time()]);
            throw $e;
        }
    }
}
