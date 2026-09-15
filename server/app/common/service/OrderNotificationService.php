<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 订单通知服务
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\common\service;

use app\common\model\notification\Notification;
use app\common\model\order\Payment as OrderPayment;
use app\common\model\order\Order;
use app\common\model\order\OrderChange;
use app\common\model\order\OrderItem;
use app\common\model\order\OrderLog;
use app\common\model\order\OrderPause;
use app\common\model\order\Refund;
use app\common\model\order\RefundItem;
use app\common\model\aftersale\AfterSaleTicket;
use app\common\model\review\Review;
use app\common\model\staff\Staff;
use app\common\model\user\User;
use app\common\service\ConfigService;
use think\facade\Log;

/**
 * 统一处理订单相关的站内通知与服务号通知。
 */
class OrderNotificationService
{
    /** 在变更事务提交前记录事件，原调用入口重放时由事件唯一键去重。 */
    public static function recordChange(int $changeId, string $phase): void
    {
        $change = OrderChange::find($changeId);
        if (!$change) return;
        $prefix = (int)$change->change_type === OrderChange::TYPE_DATE ? 'Date' : 'Addon';
        if (in_array((int)$change->change_type, [OrderChange::TYPE_DATE, OrderChange::TYPE_ADDON], true)) {
            foreach (['User', 'Staff'] as $audience) {
                $method = 'notify' . $audience . 'On' . $prefix . 'Change' . $phase;
                self::$method($changeId);
            }
            return;
        }
        $order = Order::find((int)$change->order_id);
        if (!$order) return;
        $title = '订单服务项目变更';
        $content = sprintf('订单%s的服务项目变更状态已更新，请查看详情。', $order->order_sn);
        $options = ['event' => 'service_item_change_' . $phase, 'instance' => (string)$changeId];
        StationNotificationService::send((int)$order->user_id, Notification::TYPE_ORDER, $title, $content,
            StationNotificationService::TARGET_CHANGE, $changeId, 0, $options);
        self::sendStaffOrderNotice((int)$order->id, $title, $content, '', $options + ['scene' => 'staff_change']);
    }

    public static function recordPause(int $pauseId, string $phase): void
    {
        foreach (['User', 'Staff'] as $audience) {
            $method = 'notify' . $audience . 'OnPause' . $phase;
            self::$method($pauseId);
        }
    }

    private static function ticketInstance(int $ticketId, array $actions): string
    {
        return $ticketId . ':' . (int)\app\common\model\aftersale\AfterSaleTicketLog::where('ticket_id', $ticketId)
            ->whereIn('action', $actions)->max('id');
    }

    /**
     * 下单成功后通知相关服务人员。
     */
    public static function notifyStaffOnOrderCreated(int $orderId): void
    {
        try {
            $order = Order::with(['items'])->find($orderId);
            if (!$order) {
                return;
            }

            self::sendStaffOrderNotice($orderId,
            '您有新的待确认订单',
            sprintf('订单%s已提交，请尽快确认。', (string) $order->order_sn),
            '订单已提交，等待服务人员确认',
            ['event' => 'notifyStaffOnOrderCreated', 'instance' => json_encode([$orderId]), 'scene' => 'staff_order'] + ([
                    'card_order' => $order,
                    'card_action' => '新订单待确认',
                    'card_reason' => '客户已提交订单，等待服务人员确认。',
                    'card_notice' => '请尽快确认订单，避免客户等待。',
                ]));
        } catch (\Throwable $e) {
            Log::error('订单创建通知服务人员失败：' . $e->getMessage());
            throw $e;
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
            self::sendStaffOrderNotice((int) $change->order_id,
            '订单改期申请待处理',
            self::formatOrderContent(
                    (int) $change->order_id,
                    '提交了改期申请，拟改为%s。',
                    [(string) $change->new_service_date]
                ),
            $summary,
            ['event' => 'notifyStaffOnDateChangeApplied', 'instance' => json_encode([$changeId]), 'scene' => 'staff_change']);
        } catch (\Throwable $e) {
            Log::error('改期申请通知服务人员失败：' . $e->getMessage());
            throw $e;
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
                self::sendStaffOrderNotice((int) $change->order_id,
            '订单改期申请已通过',
            self::formatOrderContent(
                        (int) $change->order_id,
                        '的改期申请已审核通过，待执行时间为%s。',
                        [(string) $change->new_service_date]
                    ),
            $summary,
            ['event' => 'notifyStaffOnDateChangeAudited', 'instance' => json_encode([$changeId]), 'scene' => 'staff_change']);
            }

            if ((int) $change->change_status === OrderChange::STATUS_REJECTED) {
                self::sendStaffOrderNotice((int) $change->order_id,
            '订单改期申请已拒绝',
            self::formatOrderContent((int) $change->order_id, '的改期申请已被拒绝。'),
            '改期申请已被拒绝。',
            ['event' => 'notifyStaffOnDateChangeAudited', 'instance' => json_encode([$changeId]), 'scene' => 'staff_change']);
            }
        } catch (\Throwable $e) {
            Log::error('改期审核通知服务人员失败：' . $e->getMessage());
            throw $e;
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
            self::sendStaffOrderNotice((int) $change->order_id,
            '订单服务时间已改期',
            self::formatOrderContent(
                    (int) $change->order_id,
                    '已改期为%s，请按新时间安排服务。',
                    [(string) $change->new_service_date]
                ),
            $summary,
            ['event' => 'notifyStaffOnDateChangeExecuted', 'instance' => json_encode([$changeId]), 'scene' => 'staff_change']);
        } catch (\Throwable $e) {
            Log::error('改期执行通知服务人员失败：' . $e->getMessage());
            throw $e;
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

            self::sendStaffOrderNotice((int) $change->order_id,
            '订单改期申请已取消',
            self::formatOrderContent((int) $change->order_id, '的改期申请已取消。'),
            '改期申请已取消。',
            ['event' => 'notifyStaffOnDateChangeCancelled', 'instance' => json_encode([$changeId]), 'scene' => 'staff_change', 'allow_cancelled' => true]);
        } catch (\Throwable $e) {
            Log::error('改期取消通知服务人员失败：' . $e->getMessage());
            throw $e;
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
            self::sendStaffOrderNotice((int) $change->order_id,
            '订单附加服务变更待处理',
            self::formatOrderContent(
                    (int) $change->order_id,
                    '提交了附加服务变更申请：%s。',
                    [$summary]
                ),
            $summary,
            ['event' => 'notifyStaffOnAddonChangeApplied', 'instance' => json_encode([$changeId]), 'scene' => 'staff_change']);
        } catch (\Throwable $e) {
            Log::error('附加服务变更申请通知服务人员失败：' . $e->getMessage());
            throw $e;
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
                self::sendStaffOrderNotice((int) $change->order_id,
            '订单附加服务变更已通过',
            self::formatOrderContent(
                        (int) $change->order_id,
                        '的附加服务变更申请已审核通过：%s。',
                        [$summary]
                    ),
            '附加服务变更申请已审核通过。' . $summary,
            ['event' => 'notifyStaffOnAddonChangeAudited', 'instance' => json_encode([$changeId]), 'scene' => 'staff_change']);
            }

            if ((int) $change->change_status === OrderChange::STATUS_REJECTED) {
                self::sendStaffOrderNotice((int) $change->order_id,
            '订单附加服务变更已拒绝',
            self::formatOrderContent(
                        (int) $change->order_id,
                        '的附加服务变更申请已被拒绝：%s。',
                        [$summary]
                    ),
            '附加服务变更申请已被拒绝。' . $summary,
            ['event' => 'notifyStaffOnAddonChangeAudited', 'instance' => json_encode([$changeId]), 'scene' => 'staff_change']);
            }
        } catch (\Throwable $e) {
            Log::error('附加服务变更审核通知服务人员失败：' . $e->getMessage());
            throw $e;
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
            self::sendStaffOrderNotice((int) $change->order_id,
            '订单附加服务已更新',
            self::formatOrderContent(
                    (int) $change->order_id,
                    '已完成附加服务变更：%s。',
                    [$summary]
                ),
            $summary,
            ['event' => 'notifyStaffOnAddonChangeExecuted', 'instance' => json_encode([$changeId]), 'scene' => 'staff_change']);
        } catch (\Throwable $e) {
            Log::error('附加服务变更执行通知服务人员失败：' . $e->getMessage());
            throw $e;
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
            self::sendStaffOrderNotice((int) $change->order_id,
            '订单附加服务变更已取消',
            self::formatOrderContent(
                    (int) $change->order_id,
                    '的附加服务变更申请已取消：%s。',
                    [$summary]
                ),
            '附加服务变更申请已取消。' . $summary,
            ['event' => 'notifyStaffOnAddonChangeCancelled', 'instance' => json_encode([$changeId]), 'scene' => 'staff_change', 'allow_cancelled' => true]);
        } catch (\Throwable $e) {
            Log::error('附加服务变更取消通知服务人员失败：' . $e->getMessage());
            throw $e;
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
            self::sendStaffOrderNotice((int) $pause->order_id,
            '订单暂停申请待处理',
            self::formatOrderContent(
                    (int) $pause->order_id,
                    '提交了暂停申请，暂停时间为%s 至 %s。',
                    [(string) $pause->pause_start_date, (string) $pause->pause_end_date]
                ),
            $summary,
            ['event' => 'notifyStaffOnPauseApplied', 'instance' => json_encode([$pauseId]), 'scene' => 'staff_order']);
        } catch (\Throwable $e) {
            Log::error('暂停申请通知服务人员失败：' . $e->getMessage());
            throw $e;
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
                self::sendStaffOrderNotice((int) $pause->order_id,
            '订单已暂停',
            self::formatOrderContent(
                        (int) $pause->order_id,
                        '已暂停，暂停时间为%s 至 %s。',
                        [(string) $pause->pause_start_date, (string) $pause->pause_end_date]
                    ),
            $summary,
            ['event' => 'notifyStaffOnPauseAudited', 'instance' => json_encode([$pauseId]), 'scene' => 'staff_order']);
            }

            if ((int) $pause->pause_status === OrderPause::STATUS_REJECTED) {
                self::sendStaffOrderNotice((int) $pause->order_id,
            '订单暂停申请已拒绝',
            self::formatOrderContent((int) $pause->order_id, '的暂停申请已被拒绝。'),
            '暂停申请已被拒绝。',
            ['event' => 'notifyStaffOnPauseAudited', 'instance' => json_encode([$pauseId]), 'scene' => 'staff_order']);
            }
        } catch (\Throwable $e) {
            Log::error('暂停审核通知服务人员失败：' . $e->getMessage());
            throw $e;
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
                self::sendStaffOrderNotice((int) $pause->order_id,
            '订单已恢复',
            self::formatOrderContent((int) $pause->order_id, '已恢复，新的服务日期为%s。', [$newServiceDate]),
            sprintf('订单已恢复，新的服务日期为%s。', $newServiceDate),
            ['event' => 'notifyStaffOnPauseResumed', 'instance' => json_encode([$pauseId]), 'scene' => 'staff_order']);
                return;
            }

            self::sendStaffOrderNotice((int) $pause->order_id,
            '订单已恢复',
            self::formatOrderContent((int) $pause->order_id, '已恢复，请关注后续服务安排。'),
            '订单已恢复，请关注后续服务安排。',
            ['event' => 'notifyStaffOnPauseResumed', 'instance' => json_encode([$pauseId]), 'scene' => 'staff_order']);
        } catch (\Throwable $e) {
            Log::error('订单恢复通知服务人员失败：' . $e->getMessage());
            throw $e;
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

            self::sendStaffOrderNotice((int) $pause->order_id,
            '订单暂停时间已延长',
            self::formatOrderContent((int) $pause->order_id, '暂停时间已延长至%s。', [(string) $pause->pause_end_date]),
            sprintf('暂停时间已延长至%s。', (string) $pause->pause_end_date),
            ['event' => 'notifyStaffOnPauseExtended', 'instance' => json_encode([$pauseId, (string)$pause->pause_end_date]), 'scene' => 'staff_pause']);
        } catch (\Throwable $e) {
            Log::error('延长暂停通知服务人员失败：' . $e->getMessage());
            throw $e;
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

            self::sendStaffOrderNotice((int) $pause->order_id,
            '订单暂停申请已取消',
            self::formatOrderContent((int) $pause->order_id, '的暂停申请已取消。'),
            '暂停申请已取消。',
            ['event' => 'notifyStaffOnPauseCancelled', 'instance' => json_encode([$pauseId]), 'scene' => 'staff_order', 'allow_cancelled' => true]);
        } catch (\Throwable $e) {
            Log::error('暂停取消通知服务人员失败：' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * 订单全部确认后通知用户。
     */
    public static function notifyUserOnOrderConfirmed(
        int $orderId,
        string $statusText = '服务人员已确认',
        string $contentTemplate = '订单%s已由服务人员确认，请尽快完成支付。',
        string $title = '您的订单已确认',
        string $eventName = 'order_confirmed'
    ): void
    {
        $order = Order::with(['items'])->find($orderId);
        if (!$order || (int) $order->user_id <= 0) {
            return;
        }

        try {
            StationNotificationService::send((int) $order->user_id,
                Notification::TYPE_ORDER,
                $title,
                sprintf($contentTemplate, (string) $order->order_sn),
                StationNotificationService::TARGET_ORDER_DETAIL,
                $orderId,
                0,
                ['event' => $eventName, 'instance' => json_encode([$orderId])]);
        } catch (\Throwable $e) {
            Log::error('订单确认站内通知失败：' . $e->getMessage());
            throw $e;
        }

    }

    /**
     * 下单成功后通知用户留痕。
     */
    public static function notifyUserOnOrderCreated(int $orderId): void
    {
        $order = Order::with(['items'])->find($orderId);
        if (!$order || (int)$order->user_id <= 0) {
            return;
        }

        StationNotificationService::send((int)$order->user_id,
                Notification::TYPE_ORDER,
                '订单已提交',
                sprintf('订单%s已提交，待服务人员确认。', (string)$order->order_sn),
                StationNotificationService::TARGET_ORDER_DETAIL,
                $orderId,
                0,
                ['event' => 'notifyUserOnOrderCreated', 'instance' => json_encode([$orderId])]);
    }

    /**
     * 支付成功后通知用户与服务人员。
     */
    public static function notifyUserAndStaffOnPaymentSuccess(int $orderId, int $payType, bool $recordOnly = false): void
    {
        $order = Order::with(['items'])->find($orderId);
        if (!$order) {
            return;
        }

        // 付款提交后再处理其他订单，避免抢档双方交叉持锁。
        if (!$recordOnly && Order::isFirstPaidStage($payType)) {
            Order::cancelUnpaidConflictingOrdersAfterScheduleBooked($order, $order->items->all());
        }

        $stageText = self::getPayStageText($payType);
        $serviceDate = self::resolveServiceDate($order);
        $serviceName = self::resolveServiceName($order);

        if ((int)$order->user_id > 0) {
            StationNotificationService::send((int)$order->user_id,
                Notification::TYPE_ORDER,
                $stageText['user_title'],
                sprintf($stageText['user_content'], (string)$order->order_sn, $serviceName, $serviceDate),
                StationNotificationService::TARGET_ORDER_DETAIL,
                $orderId,
                0,
                ['event' => 'notifyUserAndStaffOnPaymentSuccess', 'instance' => json_encode([$orderId, $payType])]);
        }

        self::sendStaffOrderNotice($orderId,
            $stageText['staff_title'],
            sprintf($stageText['staff_content'], (string)$order->order_sn, $serviceDate),
            $serviceName,
            ['event' => 'notifyUserAndStaffOnPaymentSuccess', 'instance' => json_encode([$orderId, $payType]), 'scene' => 'staff_order']);

        if (!$recordOnly) CoupleQuestionnaireService::createTaskAfterOrderPendingService($orderId);
    }

    /**
     * 线下支付凭证审核拒绝通知用户。
     */
    public static function notifyUserOnOfflineVoucherRejected(int $orderId): void
    {
        $order = Order::find($orderId);
        if (!$order || (int)$order->user_id <= 0) {
            return;
        }

        $remark = trim((string)($order->pay_voucher_audit_remark ?? ''));
        $content = sprintf(
            '订单%s的线下支付凭证审核未通过%s',
            (string)$order->order_sn,
            $remark !== '' ? '，原因：' . $remark . '，请重新处理。' : '，请重新处理。'
        );

        StationNotificationService::send((int)$order->user_id,
                Notification::TYPE_ORDER,
                '线下支付凭证审核未通过',
                $content,
                StationNotificationService::TARGET_ORDER_DETAIL,
                $orderId,
                0,
                ['event' => 'notifyUserOnOfflineVoucherRejected', 'instance' => json_encode([$orderId])]);
    }

    /**
     * 订单取消通知用户与服务人员。
     */
    public static function notifyUserAndStaffOnOrderCancelled(int $orderId, int $operatorType, string $reason = ''): void
    {
        $order = Order::with(['items'])->find($orderId);
        if (!$order) {
            return;
        }

        $reasonText = self::resolveCancelReasonText($operatorType, $reason);
        $content = sprintf('订单%s已取消，原因：%s。', (string)$order->order_sn, $reasonText);

        if ((int)$order->user_id > 0) {
            StationNotificationService::send((int)$order->user_id,
                Notification::TYPE_ORDER,
                '订单已取消',
                $content,
                StationNotificationService::TARGET_ORDER_DETAIL,
                $orderId,
                0,
                ['event' => 'notifyUserAndStaffOnOrderCancelled', 'instance' => json_encode([$orderId, $operatorType])]);
        }

        self::sendStaffOrderNotice($orderId,
            '订单已取消',
            $content,
            $reasonText,
            ['event' => 'notifyUserAndStaffOnOrderCancelled', 'instance' => json_encode([$orderId, $operatorType]), 'scene' => 'staff_order', 'allow_cancelled' => true] + ([
            'card_order' => $order,
            'card_action' => '订单已取消',
            'card_notice' => '请关注客户沟通与后续档期安排。',
            'card_reason' => $reasonText,
        ]));
    }

    /**
     * 订单自动同意后通知服务人员。
     */
    public static function notifyStaffOnOrderAutoConfirmed(int $orderId): void
    {
        $order = Order::with(['items'])->find($orderId);
        if (!$order) {
            return;
        }

        self::sendStaffOrderNotice($orderId,
            '订单已自动同意',
            sprintf('订单%s因服务人员确认超时，系统已自动同意，订单进入待支付。', (string)$order->order_sn),
            '服务人员确认超时，系统已自动同意，订单进入待支付',
            ['event' => 'notifyStaffOnOrderAutoConfirmed', 'instance' => json_encode([$orderId]), 'scene' => 'staff_order'] + ([
                'skip_station' => true,
                'card_order' => $order,
                'card_action' => '订单已自动同意',
                'card_notice' => '系统已将订单推进到待支付，请关注客户支付进展。',
                'card_reason' => '服务人员确认超时，系统已自动同意',
            ]));
    }

    /**
     * 订单完成后通知用户评价，并通知服务人员订单完成。
     */
    public static function notifyOnOrderCompleted(int $orderId): void
    {
        $order = Order::find($orderId);
        if (!$order) {
            return;
        }

        if ((int)$order->user_id > 0) {
            StationNotificationService::send((int)$order->user_id,
                Notification::TYPE_ORDER,
                '订单已完成，邀请您评价',
                sprintf('订单%s已完成，欢迎前往评价本次服务。', (string)$order->order_sn),
                StationNotificationService::TARGET_REVIEW_LIST,
                0,
                0,
                ['event' => 'notifyOnOrderCompleted', 'instance' => json_encode([0, $orderId])]);
        }

        self::sendStaffOrderNotice($orderId,
            '订单已完成',
            sprintf('订单%s已完成，请关注后续评价反馈。', (string)$order->order_sn),
            '订单已完成',
            ['event' => 'notifyOnOrderCompleted', 'instance' => json_encode([$orderId]), 'scene' => 'staff_order']);
    }

    /**
     * 服务完成但仍需支付尾款时通知用户与服务人员。
     */
    public static function notifyOnOrderServiceCompleted(int $orderId): void
    {
        $order = Order::find($orderId);
        if (!$order) {
            return;
        }

        if ((int)$order->user_id > 0) {
            StationNotificationService::send((int)$order->user_id,
                Notification::TYPE_ORDER,
                '服务已完成，请支付尾款',
                sprintf('订单%s服务已完成，请尽快支付尾款以完成订单闭环。', (string)$order->order_sn),
                StationNotificationService::TARGET_ORDER_DETAIL,
                $orderId,
                0,
                ['event' => 'notifyOnOrderServiceCompleted', 'instance' => json_encode([$orderId])]);
        }

        self::sendStaffOrderNotice($orderId,
            '订单服务已完成，等待尾款',
            sprintf('订单%s服务已完成，当前等待客户支付尾款。', (string)$order->order_sn),
            '等待尾款支付',
            ['event' => 'notifyOnOrderServiceCompleted', 'instance' => json_encode([$orderId]), 'scene' => 'staff_order']);
    }

    /**
     * 评价审核结果通知用户。
     */
    public static function notifyUserOnReviewAudited(int $reviewId): void
    {
        $review = Review::with(['order', 'staff'])->find($reviewId);
        if (!$review || (int)$review->user_id <= 0) {
            return;
        }

        $orderSn = (string)($review->order->order_sn ?? '');
        if ((int)$review->status === Review::STATUS_APPROVED) {
            StationNotificationService::send((int)$review->user_id,
                Notification::TYPE_ORDER,
                '您的评价已通过审核',
                sprintf('您对订单%s的评价已通过审核，感谢您的反馈。', $orderSn),
                StationNotificationService::TARGET_REVIEW_DETAIL,
                $reviewId,
                0,
                ['event' => 'notifyUserOnReviewAudited', 'instance' => json_encode([$reviewId])]);
            return;
        }

        $rejectReason = trim((string)($review->reject_reason ?? ''));
        StationNotificationService::send((int)$review->user_id,
                Notification::TYPE_ORDER,
                '您的评价未通过审核',
                sprintf(
                '您对订单%s的评价未通过审核%s',
                $orderSn,
                $rejectReason !== '' ? '，原因：' . $rejectReason . '。' : '。'
            ),
                StationNotificationService::TARGET_REVIEW_DETAIL,
                $reviewId,
                0,
                ['event' => 'notifyUserOnReviewAudited', 'instance' => json_encode([$reviewId])]);
    }

    /**
     * 新评价通过审核后通知服务人员。
     */
    public static function notifyStaffOnNewReview(int $reviewId): void
    {
        $review = Review::with(['order', 'staff'])->find($reviewId);
        if (!$review || (int)$review->staff_id <= 0 || (int)$review->status !== Review::STATUS_APPROVED) {
            return;
        }

        $staff = Staff::field('id, user_id, name')->find((int)$review->staff_id);
        if (!$staff || (int)$staff->user_id <= 0) {
            return;
        }

        StationNotificationService::send((int)$staff->user_id,
                Notification::TYPE_ORDER,
                '您收到一条新评价',
                sprintf(
                '订单%s有新的%s，快去看看用户反馈吧。',
                (string)($review->order->order_sn ?? ''),
                Review::getScoreLevel((int)$review->score)
            ),
                StationNotificationService::TARGET_REVIEW_DETAIL,
                $reviewId,
                0,
                ['event' => 'notifyStaffOnNewReview', 'instance' => json_encode([$reviewId]), 'audience' => 'staff']);
    }

    /**
     * 改期申请提交后通知用户留痕。
     */
    public static function notifyUserOnDateChangeApplied(int $changeId): void
    {
        $change = OrderChange::find($changeId);
        if (!$change || (int)$change->user_id <= 0 || (int)$change->change_type !== OrderChange::TYPE_DATE) {
            return;
        }

        StationNotificationService::send((int)$change->user_id,
                Notification::TYPE_ORDER,
                '改期申请已提交',
                sprintf('订单%s的改期申请已提交，拟改为%s。', (string)$change->order_sn, (string)$change->new_service_date),
                StationNotificationService::TARGET_CHANGE,
                $changeId,
                0,
                ['event' => 'notifyUserOnDateChangeApplied', 'instance' => json_encode([$changeId])]);
    }

    /**
     * 改期审核结果通知用户。
     */
    public static function notifyUserOnDateChangeAudited(int $changeId): void
    {
        $change = OrderChange::find($changeId);
        if (!$change || (int)$change->user_id <= 0 || (int)$change->change_type !== OrderChange::TYPE_DATE) {
            return;
        }

        if ((int)$change->change_status === OrderChange::STATUS_APPROVED) {
            StationNotificationService::send((int)$change->user_id,
                Notification::TYPE_ORDER,
                '改期申请已通过',
                sprintf('订单%s的改期申请已通过审核，待执行日期为%s。', (string)$change->order_sn, (string)$change->new_service_date),
                StationNotificationService::TARGET_CHANGE,
                $changeId,
                0,
                ['event' => 'notifyUserOnDateChangeAudited', 'instance' => json_encode([$changeId])]);
            return;
        }

        if ((int)$change->change_status === OrderChange::STATUS_REJECTED) {
            $reason = trim((string)($change->reject_reason ?? ''));
            StationNotificationService::send((int)$change->user_id,
                Notification::TYPE_ORDER,
                '改期申请未通过',
                sprintf(
                    '订单%s的改期申请未通过%s',
                    (string)$change->order_sn,
                    $reason !== '' ? '，原因：' . $reason . '。' : '。'
                ),
                StationNotificationService::TARGET_CHANGE,
                $changeId,
                0,
                ['event' => 'notifyUserOnDateChangeAudited', 'instance' => json_encode([$changeId])]);
        }
    }

    /**
     * 改期执行完成通知用户。
     */
    public static function notifyUserOnDateChangeExecuted(int $changeId): void
    {
        $change = OrderChange::find($changeId);
        if (!$change || (int)$change->user_id <= 0 || (int)$change->change_type !== OrderChange::TYPE_DATE) {
            return;
        }

        StationNotificationService::send((int)$change->user_id,
                Notification::TYPE_ORDER,
                '改期已执行完成',
                sprintf('订单%s已完成改期，新的服务日期为%s。', (string)$change->order_sn, (string)$change->new_service_date),
                StationNotificationService::TARGET_CHANGE,
                $changeId,
                0,
                ['event' => 'notifyUserOnDateChangeExecuted', 'instance' => json_encode([$changeId])]);
    }

    /**
     * 改期申请取消通知用户。
     */
    public static function notifyUserOnDateChangeCancelled(int $changeId): void
    {
        $change = OrderChange::find($changeId);
        if (!$change || (int)$change->user_id <= 0 || (int)$change->change_type !== OrderChange::TYPE_DATE) {
            return;
        }

        StationNotificationService::send((int)$change->user_id,
                Notification::TYPE_ORDER,
                '改期申请已取消',
                sprintf('订单%s的改期申请已取消。', (string)$change->order_sn),
                StationNotificationService::TARGET_CHANGE,
                $changeId,
                0,
                ['event' => 'notifyUserOnDateChangeCancelled', 'instance' => json_encode([$changeId])]);
    }

    /**
     * 附加服务变更申请提交后通知用户留痕。
     */
    public static function notifyUserOnAddonChangeApplied(int $changeId): void
    {
        $change = OrderChange::with(['addonItems'])->find($changeId);
        if (!$change || (int)$change->user_id <= 0 || (int)$change->change_type !== OrderChange::TYPE_ADDON) {
            return;
        }

        StationNotificationService::send((int)$change->user_id,
                Notification::TYPE_ORDER,
                '附加服务变更申请已提交',
                sprintf('订单%s的附加服务变更申请已提交：%s。', (string)$change->order_sn, self::buildAddonChangeSummary($change)),
                StationNotificationService::TARGET_CHANGE,
                $changeId,
                0,
                ['event' => 'notifyUserOnAddonChangeApplied', 'instance' => json_encode([$changeId])]);
    }

    /**
     * 附加服务变更审核结果通知用户。
     */
    public static function notifyUserOnAddonChangeAudited(int $changeId): void
    {
        $change = OrderChange::with(['addonItems'])->find($changeId);
        if (!$change || (int)$change->user_id <= 0 || (int)$change->change_type !== OrderChange::TYPE_ADDON) {
            return;
        }

        if ((int)$change->change_status === OrderChange::STATUS_APPROVED) {
            StationNotificationService::send((int)$change->user_id,
                Notification::TYPE_ORDER,
                '附加服务变更申请已通过',
                sprintf('订单%s的附加服务变更申请已通过：%s。', (string)$change->order_sn, self::buildAddonChangeSummary($change)),
                StationNotificationService::TARGET_CHANGE,
                $changeId,
                0,
                ['event' => 'notifyUserOnAddonChangeAudited', 'instance' => json_encode([$changeId])]);
            return;
        }

        if ((int)$change->change_status === OrderChange::STATUS_REJECTED) {
            $reason = trim((string)($change->reject_reason ?? ''));
            StationNotificationService::send((int)$change->user_id,
                Notification::TYPE_ORDER,
                '附加服务变更申请未通过',
                sprintf(
                    '订单%s的附加服务变更申请未通过%s',
                    (string)$change->order_sn,
                    $reason !== '' ? '，原因：' . $reason . '。' : '。'
                ),
                StationNotificationService::TARGET_CHANGE,
                $changeId,
                0,
                ['event' => 'notifyUserOnAddonChangeAudited', 'instance' => json_encode([$changeId])]);
        }
    }

    /**
     * 附加服务变更执行完成通知用户。
     */
    public static function notifyUserOnAddonChangeExecuted(int $changeId): void
    {
        $change = OrderChange::with(['addonItems'])->find($changeId);
        if (!$change || (int)$change->user_id <= 0 || (int)$change->change_type !== OrderChange::TYPE_ADDON) {
            return;
        }

        StationNotificationService::send((int)$change->user_id,
                Notification::TYPE_ORDER,
                '附加服务变更已执行',
                sprintf('订单%s已完成附加服务变更：%s。', (string)$change->order_sn, self::buildAddonChangeSummary($change)),
                StationNotificationService::TARGET_CHANGE,
                $changeId,
                0,
                ['event' => 'notifyUserOnAddonChangeExecuted', 'instance' => json_encode([$changeId])]);
    }

    /**
     * 附加服务变更取消通知用户。
     */
    public static function notifyUserOnAddonChangeCancelled(int $changeId): void
    {
        $change = OrderChange::with(['addonItems'])->find($changeId);
        if (!$change || (int)$change->user_id <= 0 || (int)$change->change_type !== OrderChange::TYPE_ADDON) {
            return;
        }

        StationNotificationService::send((int)$change->user_id,
                Notification::TYPE_ORDER,
                '附加服务变更申请已取消',
                sprintf('订单%s的附加服务变更申请已取消：%s。', (string)$change->order_sn, self::buildAddonChangeSummary($change)),
                StationNotificationService::TARGET_CHANGE,
                $changeId,
                0,
                ['event' => 'notifyUserOnAddonChangeCancelled', 'instance' => json_encode([$changeId])]);
    }

    /**
     * 暂停申请提交后通知用户留痕。
     */
    public static function notifyUserOnPauseApplied(int $pauseId): void
    {
        $pause = OrderPause::find($pauseId);
        if (!$pause || (int)$pause->user_id <= 0) {
            return;
        }

        StationNotificationService::send((int)$pause->user_id,
                Notification::TYPE_ORDER,
                '暂停申请已提交',
                sprintf('订单%s的暂停申请已提交，暂停时间为%s 至 %s。', (string)$pause->order_sn, (string)$pause->pause_start_date, (string)$pause->pause_end_date),
                StationNotificationService::TARGET_PAUSE,
                $pauseId,
                0,
                ['event' => 'notifyUserOnPauseApplied', 'instance' => json_encode([$pauseId])]);
    }

    /**
     * 暂停审核结果通知用户。
     */
    public static function notifyUserOnPauseAudited(int $pauseId): void
    {
        $pause = OrderPause::find($pauseId);
        if (!$pause || (int)$pause->user_id <= 0) {
            return;
        }

        if ((int)$pause->pause_status === OrderPause::STATUS_PAUSED) {
            StationNotificationService::send((int)$pause->user_id,
                Notification::TYPE_ORDER,
                '暂停申请已通过',
                sprintf('订单%s已进入暂停，暂停时间为%s 至 %s。', (string)$pause->order_sn, (string)$pause->pause_start_date, (string)$pause->pause_end_date),
                StationNotificationService::TARGET_PAUSE,
                $pauseId,
                0,
                ['event' => 'notifyUserOnPauseAudited', 'instance' => json_encode([$pauseId])]);
            return;
        }

        if ((int)$pause->pause_status === OrderPause::STATUS_REJECTED) {
            $reason = trim((string)($pause->reject_reason ?? ''));
            StationNotificationService::send((int)$pause->user_id,
                Notification::TYPE_ORDER,
                '暂停申请未通过',
                sprintf(
                    '订单%s的暂停申请未通过%s',
                    (string)$pause->order_sn,
                    $reason !== '' ? '，原因：' . $reason . '。' : '。'
                ),
                StationNotificationService::TARGET_PAUSE,
                $pauseId,
                0,
                ['event' => 'notifyUserOnPauseAudited', 'instance' => json_encode([$pauseId])]);
        }
    }

    /**
     * 订单恢复通知用户。
     */
    public static function notifyUserOnPauseResumed(int $pauseId): void
    {
        $pause = OrderPause::find($pauseId);
        if (!$pause || (int)$pause->user_id <= 0) {
            return;
        }

        $content = sprintf('订单%s已恢复。', (string)$pause->order_sn);
        if (trim((string)$pause->new_service_date) !== '') {
            $content = sprintf('订单%s已恢复，新的服务日期为%s。', (string)$pause->order_sn, (string)$pause->new_service_date);
        }

        StationNotificationService::send((int)$pause->user_id,
                Notification::TYPE_ORDER,
                '订单已恢复',
                $content,
                StationNotificationService::TARGET_PAUSE,
                $pauseId,
                0,
                ['event' => 'notifyUserOnPauseResumed', 'instance' => json_encode([$pauseId])]);
    }

    /**
     * 延长暂停通知用户。
     */
    public static function notifyUserOnPauseExtended(int $pauseId): void
    {
        $pause = OrderPause::find($pauseId);
        if (!$pause || (int)$pause->user_id <= 0) {
            return;
        }

        StationNotificationService::send((int)$pause->user_id,
                Notification::TYPE_ORDER,
                '暂停时间已延长',
                sprintf('订单%s的暂停时间已延长至%s。', (string)$pause->order_sn, (string)$pause->pause_end_date),
                StationNotificationService::TARGET_PAUSE,
                $pauseId,
                0,
                ['event' => 'notifyUserOnPauseExtended', 'instance' => json_encode([$pauseId, (string)$pause->pause_end_date])]);
    }

    /**
     * 暂停申请取消通知用户。
     */
    public static function notifyUserOnPauseCancelled(int $pauseId): void
    {
        $pause = OrderPause::find($pauseId);
        if (!$pause || (int)$pause->user_id <= 0) {
            return;
        }

        StationNotificationService::send((int)$pause->user_id,
                Notification::TYPE_ORDER,
                '暂停申请已取消',
                sprintf('订单%s的暂停申请已取消。', (string)$pause->order_sn),
                StationNotificationService::TARGET_PAUSE,
                $pauseId,
                0,
                ['event' => 'notifyUserOnPauseCancelled', 'instance' => json_encode([$pauseId])]);
    }

    /**
     * 退款申请提交后通知用户与服务人员。
     */
    public static function notifyUserAndStaffOnRefundApplied(int $refundId): void
    {
        $refund = Refund::find($refundId);
        if (!$refund) {
            return;
        }

        $refundTypeText = (int)$refund->refund_type === Refund::TYPE_USER ? '退款申请已提交' : '退款申请已创建';
        $orderSn = self::resolveOrderSnById((int)$refund->order_id);
        $content = sprintf(
            '订单%s的退款单%s已提交，金额%.2f元。',
            $orderSn,
            (string)$refund->refund_sn,
            (float)$refund->refund_amount
        );

        if ((int)$refund->user_id > 0) {
            StationNotificationService::send((int)$refund->user_id,
                Notification::TYPE_ORDER,
                $refundTypeText,
                $content,
                StationNotificationService::TARGET_ORDER_DETAIL,
                (int)$refund->order_id,
                0,
                ['event' => 'notifyUserAndStaffOnRefundApplied', 'instance' => json_encode([(int)$refund->order_id, $refundId])]);
        }

        self::sendStaffOrderNotice((int)$refund->order_id,
            '订单退款申请待处理',
            $content,
            (string)$refund->refund_sn,
            ['event' => 'notifyUserAndStaffOnRefundApplied', 'instance' => json_encode([$refundId]), 'scene' => 'staff_refund']);
    }

    /**
     * 退款审核通过通知用户。
     */
    public static function notifyUserOnRefundApproved(int $refundId): void
    {
        $refund = Refund::find($refundId);
        if (!$refund || (int)$refund->user_id <= 0) {
            return;
        }

        StationNotificationService::send((int)$refund->user_id,
                Notification::TYPE_ORDER,
                '退款审核已通过',
                sprintf('退款单%s审核已通过，金额%.2f元。', (string)$refund->refund_sn, (float)$refund->refund_amount),
                StationNotificationService::TARGET_ORDER_DETAIL,
                (int)$refund->order_id,
                0,
                ['event' => 'notifyUserOnRefundApproved', 'instance' => json_encode([(int)$refund->order_id, $refundId])]);
    }

    /**
     * 退款进入处理中通知用户。
     */
    public static function notifyUserOnRefundProcessing(int $refundId): void
    {
        $refund = Refund::find($refundId);
        if (!$refund || (int)$refund->user_id <= 0) {
            return;
        }

        StationNotificationService::send((int)$refund->user_id,
                Notification::TYPE_ORDER,
                '退款处理中',
                sprintf('退款单%s已进入处理中，请耐心等待结果。', (string)$refund->refund_sn),
                StationNotificationService::TARGET_ORDER_DETAIL,
                (int)$refund->order_id,
                0,
                ['event' => 'notifyUserOnRefundProcessing', 'instance' => json_encode([(int)$refund->order_id, $refundId])]);
    }

    /**
     * 退款拒绝通知用户。
     */
    public static function notifyUserOnRefundRejected(int $refundId): void
    {
        $refund = Refund::find($refundId);
        if (!$refund || (int)$refund->user_id <= 0) {
            return;
        }

        $remark = trim((string)($refund->audit_remark ?? ''));
        StationNotificationService::send((int)$refund->user_id,
                Notification::TYPE_ORDER,
                '退款申请未通过',
                sprintf(
                '退款单%s未通过审核%s',
                (string)$refund->refund_sn,
                $remark !== '' ? '，原因：' . $remark . '。' : '。'
            ),
                StationNotificationService::TARGET_ORDER_DETAIL,
                (int)$refund->order_id,
                0,
                ['event' => 'notifyUserOnRefundRejected', 'instance' => json_encode([(int)$refund->order_id, $refundId])]);

    }

    /**
     * 退款失败通知用户。
     */
    public static function notifyUserOnRefundFailed(int $refundId): void
    {
        $refund = Refund::find($refundId);
        if (!$refund || (int)$refund->user_id <= 0) {
            return;
        }

        $failedMessages = [];
        if (RefundItem::isTableReady()) {
            $failedMessages = RefundItem::where('refund_id', $refundId)
                ->where('refund_status', RefundItem::STATUS_FAILED)
                ->column('refund_msg');
        }
        $message = trim((string)implode('；', array_filter($failedMessages)));

        StationNotificationService::send((int)$refund->user_id,
                Notification::TYPE_ORDER,
                '退款处理失败',
                sprintf(
                '退款单%s处理失败%s',
                (string)$refund->refund_sn,
                $message !== '' ? '，原因：' . $message . '。' : '。'
            ),
                StationNotificationService::TARGET_ORDER_DETAIL,
                (int)$refund->order_id,
                0,
                ['event' => 'notifyUserOnRefundFailed', 'instance' => json_encode([(int)$refund->order_id, $refundId])]);

    }

    /**
     * 退款完成通知用户与服务人员。
     */
    public static function notifyUserAndStaffOnRefundCompleted(int $refundId): void
    {
        $refund = Refund::find($refundId);
        if (!$refund) {
            return;
        }

        $content = sprintf(
            '退款单%s已完成，退款金额%.2f元。',
            (string)$refund->refund_sn,
            (float)($refund->actual_refund_amount ?: $refund->refund_amount)
        );
        if ((int)$refund->user_id > 0) {
            StationNotificationService::send((int)$refund->user_id,
                Notification::TYPE_ORDER,
                '退款已完成',
                $content,
                StationNotificationService::TARGET_ORDER_DETAIL,
                (int)$refund->order_id,
                0,
                ['event' => 'notifyUserAndStaffOnRefundCompleted', 'instance' => json_encode([(int)$refund->order_id, $refundId])]);
        }


        self::sendStaffOrderNotice((int)$refund->order_id,
            '订单退款已完成',
            $content,
            (string)$refund->refund_sn,
            ['event' => 'notifyUserAndStaffOnRefundCompleted', 'instance' => json_encode([$refundId]), 'scene' => 'staff_refund']);
    }

    /**
     * 工单创建通知用户。
     */
    public static function notifyUserOnTicketCreated(int $ticketId): void
    {
        $ticket = AfterSaleTicket::find($ticketId);
        if (!$ticket || (int)$ticket->user_id <= 0) {
            return;
        }

        StationNotificationService::send((int)$ticket->user_id,
                Notification::TYPE_ORDER,
                '工单已创建',
                sprintf('工单%s已创建，我们会尽快为您处理。', (string)$ticket->ticket_sn),
                StationNotificationService::TARGET_TICKET_DETAIL,
                $ticketId,
                0,
                ['event' => 'notifyUserOnTicketCreated', 'instance' => self::ticketInstance($ticketId, ['create'])]);

    }

    /**
     * 工单创建通知售后处理组。
     */
    public static function notifyInternalOnTicketCreated(int $ticketId): void
    {
        $ticket = AfterSaleTicket::with(['user', 'order'])->find($ticketId);
        if (!$ticket) {
            return;
        }

        InternalNotificationService::send(
            (array)ConfigService::get('customer_service', 'aftersale_admin_ids', []),
            '新售后工单待处理',
            sprintf('用户提交了售后工单%s，请在管理后台处理。', (string)$ticket->ticket_sn),
            'aftersale', $ticketId, ['event' => 'ticket_created', 'instance' => self::ticketInstance($ticketId, ['create'])]
        );
    }

    /**
     * 工单受理通知用户。
     */
    public static function notifyUserOnTicketAccepted(int $ticketId): void
    {
        $ticket = AfterSaleTicket::find($ticketId);
        if (!$ticket || (int)$ticket->user_id <= 0) {
            return;
        }

        StationNotificationService::send((int)$ticket->user_id,
                Notification::TYPE_ORDER,
                '工单已受理',
                sprintf('工单%s已受理，处理人员正在跟进。', (string)$ticket->ticket_sn),
                StationNotificationService::TARGET_TICKET_DETAIL,
                $ticketId,
                0,
                ['event' => 'notifyUserOnTicketAccepted', 'instance' => self::ticketInstance($ticketId, ['assign', 'auto_assign'])]);

    }

    /**
     * 工单分配通知处理人。
     */
    public static function notifyAssigneeOnTicketAssigned(int $ticketId, int $adminId): void
    {
        $ticket = AfterSaleTicket::with(['user', 'order'])->find($ticketId);
        if (!$ticket || $adminId <= 0) {
            return;
        }

        InternalNotificationService::send([$adminId], '售后工单已分配给您',
            sprintf('售后工单%s待处理，请在管理后台查看。', (string)$ticket->ticket_sn),
            'aftersale', $ticketId, ['event' => 'ticket_assigned', 'instance' => self::ticketInstance($ticketId, ['assign', 'auto_assign'])]);
    }

    /**
     * 工单处理完成待确认通知用户。
     */
    public static function notifyUserOnTicketPendingConfirm(int $ticketId): void
    {
        $ticket = AfterSaleTicket::find($ticketId);
        if (!$ticket || (int)$ticket->user_id <= 0) {
            return;
        }

        StationNotificationService::send((int)$ticket->user_id,
                Notification::TYPE_ORDER,
                '请确认工单处理结果',
                sprintf('工单%s已处理完成，请确认处理结果。', (string)$ticket->ticket_sn),
                StationNotificationService::TARGET_TICKET_DETAIL,
                $ticketId,
                0,
                ['event' => 'notifyUserOnTicketPendingConfirm', 'instance' => self::ticketInstance($ticketId, ['handle'])]);

    }

    /**
     * 工单关闭通知用户。
     */
    public static function notifyUserOnTicketClosed(int $ticketId): void
    {
        $ticket = AfterSaleTicket::find($ticketId);
        if (!$ticket || (int)$ticket->user_id <= 0) {
            return;
        }

        $reason = trim((string)($ticket->close_reason ?? ''));
        StationNotificationService::send((int)$ticket->user_id,
                Notification::TYPE_ORDER,
                '工单已关闭',
                sprintf(
                '工单%s已关闭%s',
                (string)$ticket->ticket_sn,
                $reason !== '' ? '，原因：' . $reason . '。' : '。'
            ),
                StationNotificationService::TARGET_TICKET_DETAIL,
                $ticketId,
                0,
                ['event' => 'notifyUserOnTicketClosed', 'instance' => self::ticketInstance($ticketId, ['close'])]);

    }

    /**
     * 工单完成通知用户。
     */
    public static function notifyUserOnTicketCompleted(int $ticketId): void
    {
        $ticket = AfterSaleTicket::find($ticketId);
        if (!$ticket || (int)$ticket->user_id <= 0) {
            return;
        }

        StationNotificationService::send((int)$ticket->user_id,
                Notification::TYPE_ORDER,
                '工单已完成',
                sprintf('工单%s已完成，感谢您的确认。', (string)$ticket->ticket_sn),
                StationNotificationService::TARGET_TICKET_DETAIL,
                $ticketId,
                0,
                ['event' => 'notifyUserOnTicketCompleted', 'instance' => self::ticketInstance($ticketId, ['confirm'])]);

    }



    /**
     * 服务前一天提醒用户与服务人员。
     */
    public static function sendServiceReminder(int $orderId): void
    {
        $order = Order::with(['items'])->find($orderId);
        if (!$order) {
            return;
        }

        $serviceDate = self::resolveServiceDate($order);
        $serviceName = self::resolveServiceName($order);
        $staffName = self::resolvePrimaryStaffName($orderId);
        $address = trim((string)($order->service_address ?? ''));
        $remarkText = WechatNotificationService::DEFAULT_SCHEDULE_REMIND_REMARK_TEXT;

        if ((int)$order->user_id > 0) {
            StationNotificationService::sendUnique((int)$order->user_id,
                Notification::TYPE_ORDER,
                '服务即将开始',
                sprintf(
                    '订单%s将于%s开始服务%s%s',
                    (string)$order->order_sn,
                    $serviceDate,
                    $staffName !== '' ? '，服务人员：' . $staffName : '',
                    $address !== '' ? '，地址：' . $address : ''
                ),
                StationNotificationService::TARGET_ORDER_DETAIL,
                $orderId,
                0,
                ['event' => 'sendServiceReminder', 'instance' => json_encode([$orderId, $serviceDate])]);

        }

        $staffUserIds = self::getOrderStaffUserIds(self::getOrderStaffIds($orderId));
        foreach ($staffUserIds as $staffUserId) {
            StationNotificationService::sendUnique((int)$staffUserId,
                Notification::TYPE_ORDER,
                '服务提醒',
                sprintf('订单%s将于%s提供%s服务，请提前做好准备。', (string)$order->order_sn, $serviceDate, $serviceName),
                StationNotificationService::TARGET_STAFF_ORDER,
                $orderId,
                0,
                ['event' => 'sendServiceReminder', 'instance' => json_encode([$orderId, $serviceDate]), 'scene' => 'staff_schedule']);
        }

    }

    /**
     * 暂停即将到期提醒。
     */
    public static function sendPauseExpiringReminder(int $pauseId): bool
    {
        $pause = OrderPause::find($pauseId);
        if (!$pause || (int)$pause->user_id <= 0 || (int)$pause->pause_status !== OrderPause::STATUS_PAUSED) {
            return false;
        }

        $days = max((int)($pause->remind_before_days ?? 3), 1);
        $title = '暂停即将到期提醒';
        $content = sprintf('订单%s的暂停将于%s到期，请提前安排恢复事宜。', (string)$pause->order_sn, (string)$pause->pause_end_date);

        StationNotificationService::sendUnique((int)$pause->user_id,
                Notification::TYPE_ORDER,
                $title,
                $content,
                StationNotificationService::TARGET_PAUSE,
                $pauseId,
                0,
                ['event' => 'sendPauseExpiringReminder', 'instance' => json_encode([$pauseId, (string)$pause->pause_end_date])]);

        $staffUserIds = self::getOrderStaffUserIds(self::getOrderStaffIds((int)$pause->order_id));
        foreach ($staffUserIds as $staffUserId) {
            StationNotificationService::sendUnique((int)$staffUserId,
                Notification::TYPE_ORDER,
                '暂停到期提醒',
                sprintf('订单%s的暂停将在%s天后到期，请留意恢复安排。', (string)$pause->order_sn, (string)$days),
                StationNotificationService::TARGET_STAFF_ORDER,
                (int)$pause->order_id,
                0,
                ['event' => 'sendPauseExpiringReminder', 'instance' => json_encode([(int)$pause->order_id, $pauseId, (string)$pause->pause_end_date]), 'scene' => 'staff_pause']);
        }


        return true;
    }

    /**
     * 组装订单确认服务号通知数据。
     */
    private static function buildOrderConfirmData(Order $order, string $statusText = '服务人员已确认'): array
    {
        $serviceDate = self::resolveServiceDate($order);
        $serviceName = self::resolveServiceName($order);
        $payAmount = number_format((float) ($order->pay_amount ?? 0), 2, '.', '');
        $remarkText = self::buildOrderConfirmRemarkText($order);

        return [
            'order_id' => (int) $order->id,
            'order_sn' => (string) ($order->order_sn ?? ''),
            'service_date' => $serviceDate,
            'service_name' => $serviceName,
            'pay_amount' => $payAmount,
            'status_text' => $statusText,
            'remark_text' => $remarkText,
            // 默认模板骨架直接使用字段名；已配置映射时也保留语义字段可兼容。
            'character_string1' => (string) ($order->order_sn ?? ''),
            'thing2' => $statusText,
            'amount3' => $payAmount,
            'time4' => $serviceDate,
            'thing4' => $remarkText,
        ];
    }

    /**
     * 根据订单支付截止时间生成服务号通知备注。
     */
    private static function buildOrderConfirmRemarkText(Order $order): string
    {
        $deadlineTime = (int) ($order->pay_deadline_time ?? 0);
        if ($deadlineTime <= 0) {
            return '请及时支付定金';
        }

        return '请及时支付定金，' . date('n月j日G：i', $deadlineTime) . '超时';
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


    private static function sendStaffOrderNotice(
        int $orderId,
        string $title,
        string $content,
        string $summary = '',
        array $options = []
    ): void
    {
        $order = $options['card_order'] ?? null;
        if (!$order instanceof Order) {
            $order = Order::find($orderId);
        }
        if (!$order) {
            return;
        }

        $staffIds = self::getOrderStaffIds($orderId);
        if (empty($staffIds)) {
            return;
        }

        $staffUserIds = self::getOrderStaffUserIds($staffIds);
        foreach ($staffUserIds ?: [0] as $userId) {
            StationNotificationService::send($userId, Notification::TYPE_ORDER, $title, $content,
                StationNotificationService::TARGET_STAFF_ORDER, $orderId, 0, $options + ['audience' => 'staff']);
        }
    }

    /**
     * 构造服务人员订单详情跳转地址。
     */









    /**
     * 构造后台售后工单详情跳转地址。
     */





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
            ->where('status', 1)
            ->where('user_id', '>', 0)
            ->distinct(true)
            ->column('user_id');

        return array_values(array_filter(array_unique(array_map('intval', $userIds)),
            static fn (int $userId): bool => StaffService::getStaffIdByUserId($userId) > 0));
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

    /**
     * 解析支付阶段文案。
     */
    private static function getPayStageText(int $payType): array
    {
        if ($payType === OrderPayment::TYPE_DEPOSIT) {
            return [
                'user_title' => '定金支付成功，订单进入待服务',
                'user_content' => '订单%s定金支付成功，订单已进入待服务。剩余尾款请在服务完成后按约定支付，以完成%s服务。',
                'staff_title' => '订单定金已支付',
                'staff_content' => '订单%s定金已支付，订单已进入待服务，服务日期为%s，请提前做好准备。',
            ];
        }

        if ($payType === OrderPayment::TYPE_BALANCE) {
            return [
                'user_title' => '尾款支付成功',
                'user_content' => '订单%s尾款支付成功，感谢您的支付。',
                'staff_title' => '订单尾款已支付',
                'staff_content' => '订单%s尾款已支付，服务日期为%s。',
            ];
        }

        return [
            'user_title' => '支付成功，订单进入待服务',
            'user_content' => '订单%s支付成功，订单已进入待服务。请按约定时间享受%s服务。',
            'staff_title' => '订单已支付，可进入待服务',
            'staff_content' => '订单%s已支付成功，订单已进入待服务，服务日期为%s，请提前做好准备。',
        ];
    }

    /**
     * 解析本次支付阶段金额。
     */
    private static function resolvePaidStageAmount(Order $order, int $payType): float
    {
        if ($payType === OrderPayment::TYPE_DEPOSIT) {
            return round((float)($order->deposit_amount ?? 0), 2);
        }

        if ($payType === OrderPayment::TYPE_BALANCE) {
            return round((float)($order->balance_amount ?? 0), 2);
        }

        return round((float)($order->pay_amount ?? 0), 2);
    }

    /**
     * 解析取消原因文案。
     */
    private static function resolveCancelReasonText(int $operatorType, string $reason = ''): string
    {
        $reason = trim($reason);
        if ($operatorType === OrderLog::OPERATOR_USER) {
            return $reason !== '' ? '用户主动取消：' . $reason : '用户主动取消';
        }

        if ($operatorType === OrderLog::OPERATOR_ADMIN) {
            return $reason !== '' ? '后台取消：' . $reason : '后台取消';
        }

        if ($operatorType === OrderLog::OPERATOR_SYSTEM) {
            return $reason !== '' ? $reason : '支付超时自动取消';
        }

        return $reason !== '' ? $reason : '订单已取消';
    }

    /**
     * 获取订单主服务人员名称。
     */
    private static function resolvePrimaryStaffName(int $orderId): string
    {
        $item = OrderItem::where('order_id', $orderId)
            ->where('staff_id', '>', 0)
            ->order('id', 'asc')
            ->find();

        return trim((string)($item->staff_name ?? ''));
    }

    /**
     * 通过订单ID解析订单号。
     */
    private static function resolveOrderSnById(int $orderId): string
    {
        $order = Order::field('order_sn')->find($orderId);
        return (string)($order->order_sn ?? '');
    }

}
