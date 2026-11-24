<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 订单与 CRM 客户自动联动服务
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\common\service;

use app\common\model\crm\Customer;
use app\common\model\crm\CustomerLossWarning;
use app\common\model\crm\SalesAdvisor;
use app\common\model\order\Order;
use think\facade\Log;

/**
 * 订单支付成功后自动联动更新 CRM 客户状态、真实成交金额与顾问业绩。
 * 容错设计：任何异常仅记录警告日志，绝不阻塞主支付与通知链路。
 */
class CrmOrderSyncService
{
    public static function syncOnOrderPaid(int $orderId): void
    {
        if ($orderId <= 0) {
            return;
        }

        try {
            $order = Order::find($orderId);
            if (!$order) {
                return;
            }

            $hasPaidAmount = (float)($order->paid_amount ?? 0) > 0 || (int)($order->deposit_paid ?? 0) === 1;
            $hasPaidStatus = in_array((int)$order->pay_status, [Order::PAY_STATUS_PAID, Order::PAY_STATUS_PARTIAL_REFUND], true);
            if (!$hasPaidAmount && !$hasPaidStatus) {
                return;
            }
            if (in_array((int)$order->order_status, [Order::STATUS_CANCELLED, Order::STATUS_REFUNDED, Order::STATUS_USER_DELETED], true)) {
                return;
            }

            $userId = (int)$order->user_id;
            $customer = $userId > 0 ? Customer::findByUserId($userId) : null;
            if (!$customer) {
                $mobile = trim((string)($order->contact_mobile ?? ''));
                if ($mobile !== '') {
                    $customer = Customer::findByMobile($mobile);
                }
            }
            if (!$customer) {
                return;
            }

            $now = time();
            $updateData = ['update_time' => $now];
            if ((int)$customer->user_id <= 0 && $userId > 0) {
                $updateData['user_id'] = $userId;
                $customer->user_id = $userId;
            }

            $effectiveUserId = (int)($updateData['user_id'] ?? $customer->user_id);
            if ($effectiveUserId > 0) {
                $summaryMap = Customer::getRealOrderSummaryMap([$effectiveUserId]);
                if (isset($summaryMap[$effectiveUserId])) {
                    $updateData['order_count'] = (int)$summaryMap[$effectiveUserId]['order_count'];
                    $updateData['total_amount'] = (float)$summaryMap[$effectiveUserId]['total_amount'];
                }
            }

            $wasActive = in_array((int)$customer->customer_status, [Customer::STATUS_NEW, Customer::STATUS_FOLLOWING, Customer::STATUS_LOST], true);
            if ($wasActive) {
                $updateData['customer_status'] = Customer::STATUS_SIGNED;
                if ((int)$customer->customer_status === Customer::STATUS_LOST) {
                    $updateData['loss_reason'] = '';
                    $updateData['loss_time'] = 0;
                }
            }

            Customer::where('id', (int)$customer->id)->update($updateData);

            if ($wasActive) {
                CustomerLossWarning::resolveAllPendingWarningsByCustomer(
                    (int)$customer->id,
                    '订单' . (string)$order->order_sn . '支付成功自动解除'
                );
            }

            $advisorId = (int)$customer->advisor_id;
            if ($advisorId > 0) {
                SalesAdvisor::recalculateCustomerCount($advisorId);
                SalesAdvisor::recalculateOrderStats($advisorId);
            }
        } catch (\Throwable $e) {
            Log::warning('CRM 订单支付联动同步失败：order_id=' . $orderId . '，error=' . $e->getMessage());
        }
    }
}
