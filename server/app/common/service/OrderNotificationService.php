<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 订单通知服务
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\common\service;

use app\common\model\notification\Notification;
use app\common\model\order\Order;
use app\common\model\order\OrderChange;
use app\common\model\order\OrderItem;
use app\common\model\order\OrderPause;
use app\common\model\order\OrderTransfer;
use app\common\model\staff\Staff;
use think\facade\Log;

/**
 * 统一处理订单相关的站内通知与订阅消息。
 */
class OrderNotificationService
{
    /**
     * 下单成功后通知相关服务人员。
     */
    public static function notifyStaffOnOrderCreated(int $orderId): void
    {
        try {
            $order = Order::find($orderId);
            if (!$order) {
                return;
            }

            $staffIds = OrderItem::where('order_id', $orderId)
                ->where('staff_id', '>', 0)
                ->distinct(true)
                ->column('staff_id');
            if (empty($staffIds)) {
                return;
            }

            $staffUserIds = Staff::whereIn('id', array_map('intval', $staffIds))
                ->where('user_id', '>', 0)
                ->distinct(true)
                ->column('user_id');
            if (empty($staffUserIds)) {
                return;
            }

            Notification::batchSend(
                array_values(array_unique(array_map('intval', $staffUserIds))),
                Notification::TYPE_ORDER,
                '您有新的待确认订单',
                sprintf('订单%s已提交，请尽快确认。', (string) $order->order_sn),
                'staff_order',
                $orderId
            );

            $contactName = trim((string) ($order->contact_name ?? ''));
            $contactMobile = trim((string) ($order->contact_mobile ?? ''));
            $message = "您有新的待确认订单\n"
                . '订单号：' . (string) $order->order_sn . "\n"
                . ($contactName !== '' ? '联系人：' . $contactName . "\n" : '')
                . ($contactMobile !== '' ? '联系电话：' . $contactMobile . "\n" : '')
                . '请尽快登录系统处理。';
            WeComMessageService::sendToStaff(array_map('intval', $staffIds), trim($message));
        } catch (\Throwable $e) {
            Log::error('订单创建通知服务人员失败：' . $e->getMessage());
        }
    }

    /**
     * 改期申请提交后通知服务人员。
     */
    public static function notifyStaffOnDateChangeApplied(int $changeId): void
    {
        try {
            $change = OrderChange::find($changeId);
            if (!$change || (int) $change->change_type !== OrderChange::TYPE_DATE) {
                return;
            }

            $summary = sprintf('改期申请，拟改为%s。', (string) $change->new_service_date);
            self::sendStaffOrderNotice(
                (int) $change->order_id,
                '订单改期申请待处理',
                self::formatOrderContent(
                    (int) $change->order_id,
                    '提交了改期申请，拟改为%s。',
                    [(string) $change->new_service_date]
                ),
                $summary
            );
        } catch (\Throwable $e) {
            Log::error('改期申请通知服务人员失败：' . $e->getMessage());
        }
    }

    /**
     * 改期审核后通知服务人员。
     */
    public static function notifyStaffOnDateChangeAudited(int $changeId): void
    {
        try {
            $change = OrderChange::find($changeId);
            if (!$change || (int) $change->change_type !== OrderChange::TYPE_DATE) {
                return;
            }

            if ((int) $change->change_status === OrderChange::STATUS_APPROVED) {
                $summary = sprintf('改期申请已审核通过，待执行时间为%s。', (string) $change->new_service_date);
                self::sendStaffOrderNotice(
                    (int) $change->order_id,
                    '订单改期申请已通过',
                    self::formatOrderContent(
                        (int) $change->order_id,
                        '的改期申请已审核通过，待执行时间为%s。',
                        [(string) $change->new_service_date]
                    ),
                    $summary
                );
            }

            if ((int) $change->change_status === OrderChange::STATUS_REJECTED) {
                self::sendStaffOrderNotice(
                    (int) $change->order_id,
                    '订单改期申请已拒绝',
                    self::formatOrderContent((int) $change->order_id, '的改期申请已被拒绝。'),
                    '改期申请已被拒绝。'
                );
            }
        } catch (\Throwable $e) {
            Log::error('改期审核通知服务人员失败：' . $e->getMessage());
        }
    }

    /**
     * 改期执行完成后通知服务人员。
     */
    public static function notifyStaffOnDateChangeExecuted(int $changeId): void
    {
        try {
            $change = OrderChange::find($changeId);
            if (!$change || (int) $change->change_type !== OrderChange::TYPE_DATE || (int) $change->change_status !== OrderChange::STATUS_EXECUTED) {
                return;
            }

            $summary = sprintf('服务时间已改为%s，请按新时间安排服务。', (string) $change->new_service_date);
            self::sendStaffOrderNotice(
                (int) $change->order_id,
                '订单服务时间已改期',
                self::formatOrderContent(
                    (int) $change->order_id,
                    '已改期为%s，请按新时间安排服务。',
                    [(string) $change->new_service_date]
                ),
                $summary
            );
        } catch (\Throwable $e) {
            Log::error('改期执行通知服务人员失败：' . $e->getMessage());
        }
    }

    /**
     * 改期申请取消后通知服务人员。
     */
    public static function notifyStaffOnDateChangeCancelled(int $changeId): void
    {
        try {
            $change = OrderChange::find($changeId);
            if (!$change || (int) $change->change_type !== OrderChange::TYPE_DATE || (int) $change->change_status !== OrderChange::STATUS_CANCELLED) {
                return;
            }

            self::sendStaffOrderNotice(
                (int) $change->order_id,
                '订单改期申请已取消',
                self::formatOrderContent((int) $change->order_id, '的改期申请已取消。'),
                '改期申请已取消。'
            );
        } catch (\Throwable $e) {
            Log::error('改期取消通知服务人员失败：' . $e->getMessage());
        }
    }

    /**
     * 附加服务变更申请提交后通知服务人员。
     */
    public static function notifyStaffOnAddonChangeApplied(int $changeId): void
    {
        try {
            $change = OrderChange::with(['addonItems'])->find($changeId);
            if (!$change || (int) $change->change_type !== OrderChange::TYPE_ADDON) {
                return;
            }

            $summary = self::buildAddonChangeSummary($change);
            self::sendStaffOrderNotice(
                (int) $change->order_id,
                '订单附加服务变更待处理',
                self::formatOrderContent(
                    (int) $change->order_id,
                    '提交了附加服务变更申请：%s。',
                    [$summary]
                ),
                $summary
            );
        } catch (\Throwable $e) {
            Log::error('附加服务变更申请通知服务人员失败：' . $e->getMessage());
        }
    }

    /**
     * 附加服务变更审核后通知服务人员。
     */
    public static function notifyStaffOnAddonChangeAudited(int $changeId): void
    {
        try {
            $change = OrderChange::with(['addonItems'])->find($changeId);
            if (!$change || (int) $change->change_type !== OrderChange::TYPE_ADDON) {
                return;
            }

            $summary = self::buildAddonChangeSummary($change);
            if ((int) $change->change_status === OrderChange::STATUS_APPROVED) {
                self::sendStaffOrderNotice(
                    (int) $change->order_id,
                    '订单附加服务变更已通过',
                    self::formatOrderContent(
                        (int) $change->order_id,
                        '的附加服务变更申请已审核通过：%s。',
                        [$summary]
                    ),
                    '附加服务变更申请已审核通过。' . $summary
                );
            }

            if ((int) $change->change_status === OrderChange::STATUS_REJECTED) {
                self::sendStaffOrderNotice(
                    (int) $change->order_id,
                    '订单附加服务变更已拒绝',
                    self::formatOrderContent(
                        (int) $change->order_id,
                        '的附加服务变更申请已被拒绝：%s。',
                        [$summary]
                    ),
                    '附加服务变更申请已被拒绝。' . $summary
                );
            }
        } catch (\Throwable $e) {
            Log::error('附加服务变更审核通知服务人员失败：' . $e->getMessage());
        }
    }

    /**
     * 附加服务变更执行完成后通知服务人员。
     */
    public static function notifyStaffOnAddonChangeExecuted(int $changeId): void
    {
        try {
            $change = OrderChange::with(['addonItems'])->find($changeId);
            if (!$change || (int) $change->change_type !== OrderChange::TYPE_ADDON || (int) $change->change_status !== OrderChange::STATUS_EXECUTED) {
                return;
            }

            $summary = self::buildAddonChangeSummary($change);
            self::sendStaffOrderNotice(
                (int) $change->order_id,
                '订单附加服务已更新',
                self::formatOrderContent(
                    (int) $change->order_id,
                    '已完成附加服务变更：%s。',
                    [$summary]
                ),
                $summary
            );
        } catch (\Throwable $e) {
            Log::error('附加服务变更执行通知服务人员失败：' . $e->getMessage());
        }
    }

    /**
     * 附加服务变更申请取消后通知服务人员。
     */
    public static function notifyStaffOnAddonChangeCancelled(int $changeId): void
    {
        try {
            $change = OrderChange::with(['addonItems'])->find($changeId);
            if (!$change || (int) $change->change_type !== OrderChange::TYPE_ADDON || (int) $change->change_status !== OrderChange::STATUS_CANCELLED) {
                return;
            }

            $summary = self::buildAddonChangeSummary($change);
            self::sendStaffOrderNotice(
                (int) $change->order_id,
                '订单附加服务变更已取消',
                self::formatOrderContent(
                    (int) $change->order_id,
                    '的附加服务变更申请已取消：%s。',
                    [$summary]
                ),
                '附加服务变更申请已取消。' . $summary
            );
        } catch (\Throwable $e) {
            Log::error('附加服务变更取消通知服务人员失败：' . $e->getMessage());
        }
    }

    /**
     * 暂停申请提交后通知服务人员。
     */
    public static function notifyStaffOnPauseApplied(int $pauseId): void
    {
        try {
            $pause = OrderPause::find($pauseId);
            if (!$pause) {
                return;
            }

            $summary = sprintf('暂停申请，暂停时间为%s 至 %s。', (string) $pause->pause_start_date, (string) $pause->pause_end_date);
            self::sendStaffOrderNotice(
                (int) $pause->order_id,
                '订单暂停申请待处理',
                self::formatOrderContent(
                    (int) $pause->order_id,
                    '提交了暂停申请，暂停时间为%s 至 %s。',
                    [(string) $pause->pause_start_date, (string) $pause->pause_end_date]
                ),
                $summary
            );
        } catch (\Throwable $e) {
            Log::error('暂停申请通知服务人员失败：' . $e->getMessage());
        }
    }

    /**
     * 暂停审核后通知服务人员。
     */
    public static function notifyStaffOnPauseAudited(int $pauseId): void
    {
        try {
            $pause = OrderPause::find($pauseId);
            if (!$pause) {
                return;
            }

            if ((int) $pause->pause_status === OrderPause::STATUS_PAUSED) {
                $summary = sprintf('订单已暂停，暂停时间为%s 至 %s。', (string) $pause->pause_start_date, (string) $pause->pause_end_date);
                self::sendStaffOrderNotice(
                    (int) $pause->order_id,
                    '订单已暂停',
                    self::formatOrderContent(
                        (int) $pause->order_id,
                        '已暂停，暂停时间为%s 至 %s。',
                        [(string) $pause->pause_start_date, (string) $pause->pause_end_date]
                    ),
                    $summary
                );
            }

            if ((int) $pause->pause_status === OrderPause::STATUS_REJECTED) {
                self::sendStaffOrderNotice(
                    (int) $pause->order_id,
                    '订单暂停申请已拒绝',
                    self::formatOrderContent((int) $pause->order_id, '的暂停申请已被拒绝。'),
                    '暂停申请已被拒绝。'
                );
            }
        } catch (\Throwable $e) {
            Log::error('暂停审核通知服务人员失败：' . $e->getMessage());
        }
    }

    /**
     * 订单恢复后通知服务人员。
     */
    public static function notifyStaffOnPauseResumed(int $pauseId): void
    {
        try {
            $pause = OrderPause::find($pauseId);
            if (!$pause || (int) $pause->pause_status !== OrderPause::STATUS_RESUMED) {
                return;
            }

            $newServiceDate = trim((string) $pause->new_service_date);
            if ($newServiceDate !== '') {
                self::sendStaffOrderNotice(
                    (int) $pause->order_id,
                    '订单已恢复',
                    self::formatOrderContent((int) $pause->order_id, '已恢复，新的服务日期为%s。', [$newServiceDate]),
                    sprintf('订单已恢复，新的服务日期为%s。', $newServiceDate)
                );
                return;
            }

            self::sendStaffOrderNotice(
                (int) $pause->order_id,
                '订单已恢复',
                self::formatOrderContent((int) $pause->order_id, '已恢复，请关注后续服务安排。'),
                '订单已恢复，请关注后续服务安排。'
            );
        } catch (\Throwable $e) {
            Log::error('订单恢复通知服务人员失败：' . $e->getMessage());
        }
    }

    /**
     * 延长暂停后通知服务人员。
     */
    public static function notifyStaffOnPauseExtended(int $pauseId): void
    {
        try {
            $pause = OrderPause::find($pauseId);
            if (!$pause || (int) $pause->pause_status !== OrderPause::STATUS_PAUSED) {
                return;
            }

            self::sendStaffOrderNotice(
                (int) $pause->order_id,
                '订单暂停时间已延长',
                self::formatOrderContent((int) $pause->order_id, '暂停时间已延长至%s。', [(string) $pause->pause_end_date]),
                sprintf('暂停时间已延长至%s。', (string) $pause->pause_end_date)
            );
        } catch (\Throwable $e) {
            Log::error('延长暂停通知服务人员失败：' . $e->getMessage());
        }
    }

    /**
     * 暂停申请取消后通知服务人员。
     */
    public static function notifyStaffOnPauseCancelled(int $pauseId): void
    {
        try {
            $pause = OrderPause::find($pauseId);
            if (!$pause || (int) $pause->pause_status !== OrderPause::STATUS_CANCELLED) {
                return;
            }

            self::sendStaffOrderNotice(
                (int) $pause->order_id,
                '订单暂停申请已取消',
                self::formatOrderContent((int) $pause->order_id, '的暂停申请已取消。'),
                '暂停申请已取消。'
            );
        } catch (\Throwable $e) {
            Log::error('暂停取消通知服务人员失败：' . $e->getMessage());
        }
    }

    /**
     * 转让申请提交后通知服务人员。
     */
    public static function notifyStaffOnTransferApplied(int $transferId): void
    {
        try {
            $transfer = OrderTransfer::find($transferId);
            if (!$transfer) {
                return;
            }

            self::sendStaffOrderNotice(
                (int) $transfer->order_id,
                '订单转让申请待处理',
                self::formatOrderContent((int) $transfer->order_id, '提交了转让申请，请关注客户变更。'),
                '转让申请已提交，请关注客户变更。'
            );
        } catch (\Throwable $e) {
            Log::error('转让申请通知服务人员失败：' . $e->getMessage());
        }
    }

    /**
     * 转让审核后通知服务人员。
     */
    public static function notifyStaffOnTransferAudited(int $transferId): void
    {
        try {
            $transfer = OrderTransfer::find($transferId);
            if (!$transfer) {
                return;
            }

            if ((int) $transfer->transfer_status === OrderTransfer::STATUS_WAITING) {
                self::sendStaffOrderNotice(
                    (int) $transfer->order_id,
                    '订单转让申请已通过',
                    self::formatOrderContent((int) $transfer->order_id, '的转让申请已审核通过，等待接收方确认。'),
                    '转让申请已审核通过，等待接收方确认。'
                );
            }

            if ((int) $transfer->transfer_status === OrderTransfer::STATUS_REJECTED) {
                self::sendStaffOrderNotice(
                    (int) $transfer->order_id,
                    '订单转让申请已拒绝',
                    self::formatOrderContent((int) $transfer->order_id, '的转让申请已被拒绝。'),
                    '转让申请已被拒绝。'
                );
            }
        } catch (\Throwable $e) {
            Log::error('转让审核通知服务人员失败：' . $e->getMessage());
        }
    }

    /**
     * 转让完成后通知服务人员。
     */
    public static function notifyStaffOnTransferCompleted(int $transferId): void
    {
        try {
            $transfer = OrderTransfer::find($transferId);
            if (!$transfer || (int) $transfer->transfer_status !== OrderTransfer::STATUS_COMPLETED) {
                return;
            }

            $summary = sprintf(
                '订单客户已变更，新联系人为%s（%s）。',
                (string) $transfer->to_user_name,
                (string) $transfer->to_user_mobile
            );
            self::sendStaffOrderNotice(
                (int) $transfer->order_id,
                '订单客户已变更',
                self::formatOrderContent(
                    (int) $transfer->order_id,
                    '已完成转让，新联系人为%s（%s）。',
                    [(string) $transfer->to_user_name, (string) $transfer->to_user_mobile]
                ),
                $summary
            );
        } catch (\Throwable $e) {
            Log::error('转让完成通知服务人员失败：' . $e->getMessage());
        }
    }

    /**
     * 转让取消后通知服务人员。
     */
    public static function notifyStaffOnTransferCancelled(int $transferId, bool $isAdmin = false): void
    {
        try {
            $transfer = OrderTransfer::find($transferId);
            if (!$transfer || (int) $transfer->transfer_status !== OrderTransfer::STATUS_CANCELLED) {
                return;
            }

            if ($isAdmin) {
                self::sendStaffOrderNotice(
                    (int) $transfer->order_id,
                    '订单转让流程已取消',
                    self::formatOrderContent((int) $transfer->order_id, '的转让流程已由后台取消。'),
                    '转让流程已由后台取消。'
                );
                return;
            }

            self::sendStaffOrderNotice(
                (int) $transfer->order_id,
                '订单转让申请已取消',
                self::formatOrderContent((int) $transfer->order_id, '的转让申请已取消。'),
                '转让申请已取消。'
            );
        } catch (\Throwable $e) {
            Log::error('转让取消通知服务人员失败：' . $e->getMessage());
        }
    }

    /**
     * 订单全部确认后通知用户。
     */
    public static function notifyUserOnOrderConfirmed(int $orderId): void
    {
        $order = Order::with(['items'])->find($orderId);
        if (!$order || (int) $order->user_id <= 0) {
            return;
        }

        try {
            Notification::send(
                (int) $order->user_id,
                Notification::TYPE_ORDER,
                '您的订单已确认',
                sprintf('订单%s已由服务人员确认，请尽快完成支付。', (string) $order->order_sn),
                'order',
                $orderId
            );
        } catch (\Throwable $e) {
            Log::error('订单确认站内通知失败：' . $e->getMessage());
        }

        try {
            $result = SubscribeMessageService::sendOrderConfirmNotice(
                (int) $order->user_id,
                self::buildOrderConfirmData($order)
            );
            if (!$result['success']) {
                Log::info('订单确认订阅消息未发送：' . ($result['msg'] ?? '未知原因'));
            }
        } catch (\Throwable $e) {
            Log::error('订单确认订阅消息发送失败：' . $e->getMessage());
        }
    }

    /**
     * 组装订单确认订阅消息数据。
     */
    private static function buildOrderConfirmData(Order $order): array
    {
        $serviceDate = self::resolveServiceDate($order);
        $serviceName = self::resolveServiceName($order);
        $payAmount = number_format((float) ($order->pay_amount ?? 0), 2, '.', '');

        return [
            'order_id' => (int) $order->id,
            'order_sn' => (string) ($order->order_sn ?? ''),
            'service_date' => $serviceDate,
            'service_name' => $serviceName,
            'pay_amount' => $payAmount,
            'status_text' => '服务人员已确认',
            // 默认模板骨架直接使用字段名；已配置映射时也保留语义字段可兼容。
            'character_string1' => (string) ($order->order_sn ?? ''),
            'thing2' => '服务人员已确认',
            'amount3' => $payAmount,
            'time4' => $serviceDate,
        ];
    }

    /**
     * 优先取订单服务日期，兜底取首个订单项日期。
     */
    private static function resolveServiceDate(Order $order): string
    {
        $serviceDate = (string) ($order->service_date ?? '');
        if ($serviceDate !== '') {
            return $serviceDate;
        }

        $items = $order->items;
        if (is_object($items) && method_exists($items, 'toArray')) {
            $items = $items->toArray();
        }
        if (!is_array($items)) {
            return '';
        }

        foreach ($items as $item) {
            if (!empty($item['service_date'])) {
                return (string) $item['service_date'];
            }
        }

        return '';
    }

    /**
     * 提取服务名称，优先订单项套餐名。
     */
    private static function resolveServiceName(Order $order): string
    {
        $items = $order->items;
        if (is_object($items) && method_exists($items, 'toArray')) {
            $items = $items->toArray();
        }
        if (!is_array($items)) {
            return '婚庆服务';
        }

        $packageNames = [];
        foreach ($items as $item) {
            $name = trim((string) ($item['package_name'] ?? ''));
            if ($name !== '') {
                $packageNames[] = $name;
            }
        }

        $packageNames = array_values(array_unique($packageNames));
        if (empty($packageNames)) {
            return '婚庆服务';
        }

        return implode('、', array_slice($packageNames, 0, 3));
    }

    /**
     * 给订单关联服务人员发送站内消息与企微内部提醒。
     */
    private static function sendStaffOrderNotice(int $orderId, string $title, string $content, string $summary = ''): void
    {
        $order = Order::find($orderId);
        if (!$order) {
            return;
        }

        $staffIds = self::getOrderStaffIds($orderId);
        if (empty($staffIds)) {
            return;
        }

        $staffUserIds = self::getOrderStaffUserIds($staffIds);
        if (!empty($staffUserIds)) {
            try {
                Notification::batchSend(
                    $staffUserIds,
                    Notification::TYPE_ORDER,
                    $title,
                    $content,
                    'staff_order',
                    $orderId
                );
            } catch (\Throwable $e) {
                Log::error('服务人员订单站内通知失败：' . $e->getMessage());
            }
        }

        try {
            $message = $title . "\n"
                . '订单号：' . (string) $order->order_sn . "\n"
                . '摘要：' . ($summary !== '' ? $summary : $content) . "\n"
                . '请登录系统查看详情。';
            WeComMessageService::sendToStaff($staffIds, trim($message));
        } catch (\Throwable $e) {
            Log::error('服务人员订单企微提醒失败：' . $e->getMessage());
        }
    }

    /**
     * 获取订单关联服务人员ID。
     */
    private static function getOrderStaffIds(int $orderId): array
    {
        $staffIds = OrderItem::where('order_id', $orderId)
            ->where('staff_id', '>', 0)
            ->distinct(true)
            ->column('staff_id');

        return array_values(array_unique(array_map('intval', $staffIds)));
    }

    /**
     * 获取已绑定用户账号的服务人员用户ID。
     */
    private static function getOrderStaffUserIds(array $staffIds): array
    {
        $staffIds = array_values(array_unique(array_map('intval', $staffIds)));
        if (empty($staffIds)) {
            return [];
        }

        $userIds = Staff::whereIn('id', $staffIds)
            ->where('user_id', '>', 0)
            ->distinct(true)
            ->column('user_id');

        return array_values(array_unique(array_map('intval', $userIds)));
    }

    /**
     * 生成带订单号的站内消息内容。
     */
    private static function formatOrderContent(int $orderId, string $template, array $args = []): string
    {
        $order = Order::find($orderId);
        if (!$order) {
            return '';
        }

        $suffix = empty($args) ? $template : vsprintf($template, $args);
        return sprintf('订单%s%s', (string) $order->order_sn, $suffix);
    }

    /**
     * 组装附加服务变更摘要。
     */
    private static function buildAddonChangeSummary(OrderChange $change): string
    {
        $items = $change->addonItems;
        if (is_object($items) && method_exists($items, 'toArray')) {
            $items = $items->toArray();
        }
        if (!is_array($items)) {
            $items = [];
        }

        $names = array_values(array_unique(array_filter(array_map(static function ($item) {
            return trim((string) ($item['addon_name'] ?? ''));
        }, $items))));

        $actionText = (int) $change->addon_action === OrderChange::ADDON_ACTION_REMOVE ? '移除附加服务' : '新增附加服务';
        $nameText = empty($names) ? '未命名附加服务' : implode('、', $names);
        $priceDiff = round((float) ($change->price_diff ?? 0), 2);
        $priceText = $priceDiff > 0
            ? sprintf('，净差额+%.2f元', $priceDiff)
            : ($priceDiff < 0 ? sprintf('，净差额%.2f元', $priceDiff) : '');

        return $actionText . '：' . $nameText . $priceText;
    }

}
