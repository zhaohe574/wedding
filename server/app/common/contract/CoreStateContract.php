<?php
// +----------------------------------------------------------------------
// | 核心业务状态契约
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\common\contract;

use app\common\model\order\Order;
use app\common\model\order\OrderItem;
use app\common\model\order\Payment;
use app\common\model\questionnaire\CoupleQuestionnaireTask;
use app\common\model\schedule\Schedule;

/**
 * 订单、支付、档期、问卷的状态读模型与流转白名单。
 *
 * 目的：为接口契约、测试断言、后续重构提供单一可读来源；不在本类中
 * 直接改写业务流程，避免与现有 Order/Payment/Schedule 服务产生双写。
 */
class CoreStateContract
{
    /** 订单状态允许的正向/补偿流转。 */
    public const ORDER_TRANSITIONS = [
        Order::STATUS_PENDING_CONFIRM => [
            Order::STATUS_PENDING_PAY,
            Order::STATUS_CANCELLED,
        ],
        Order::STATUS_PENDING_PAY => [
            Order::STATUS_PENDING_SERVICE,
            Order::STATUS_CANCELLED,
            Order::STATUS_REFUNDING,
            Order::STATUS_REFUNDED,
        ],
        Order::STATUS_PENDING_SERVICE => [
            Order::STATUS_IN_SERVICE,
            Order::STATUS_COMPLETED,
            Order::STATUS_PAUSED,
            Order::STATUS_REFUNDING,
            Order::STATUS_REFUNDED,
            Order::STATUS_CANCELLED,
        ],
        Order::STATUS_IN_SERVICE => [
            Order::STATUS_COMPLETED,
            Order::STATUS_PAUSED,
            Order::STATUS_REFUNDING,
        ],
        Order::STATUS_PAUSED => [
            Order::STATUS_PENDING_SERVICE,
            Order::STATUS_IN_SERVICE,
            Order::STATUS_CANCELLED,
            Order::STATUS_REFUNDING,
        ],
        Order::STATUS_COMPLETED => [
            Order::STATUS_REVIEWED,
            Order::STATUS_REFUNDING,
        ],
        Order::STATUS_REVIEWED => [
            Order::STATUS_REFUNDING,
        ],
        Order::STATUS_REFUNDING => [
            Order::STATUS_REFUNDED,
            Order::STATUS_PENDING_SERVICE,
            Order::STATUS_COMPLETED,
        ],
        Order::STATUS_CANCELLED => [],
        Order::STATUS_REFUNDED => [],
        Order::STATUS_USER_DELETED => [],
    ];

    /** 支付流水状态允许流转。 */
    public const PAYMENT_TRANSITIONS = [
        Payment::STATUS_PENDING => [
            Payment::STATUS_PAID,
            Payment::STATUS_FAILED,
        ],
        Payment::STATUS_PAID => [
            Payment::STATUS_REFUNDED,
        ],
        Payment::STATUS_FAILED => [],
        Payment::STATUS_REFUNDED => [],
    ];

    /** 档期状态允许流转。 */
    public const SCHEDULE_TRANSITIONS = [
        Schedule::STATUS_AVAILABLE => [
            Schedule::STATUS_LOCKED,
            Schedule::STATUS_BOOKED,
            Schedule::STATUS_UNAVAILABLE,
            Schedule::STATUS_RESERVED,
        ],
        Schedule::STATUS_LOCKED => [
            Schedule::STATUS_AVAILABLE,
            Schedule::STATUS_BOOKED,
        ],
        Schedule::STATUS_BOOKED => [
            Schedule::STATUS_AVAILABLE,
            Schedule::STATUS_UNAVAILABLE,
        ],
        Schedule::STATUS_UNAVAILABLE => [
            Schedule::STATUS_AVAILABLE,
            Schedule::STATUS_RESERVED,
        ],
        Schedule::STATUS_RESERVED => [
            Schedule::STATUS_AVAILABLE,
            Schedule::STATUS_UNAVAILABLE,
            Schedule::STATUS_BOOKED,
        ],
    ];

    /** 订单项状态允许流转。 */
    public const ORDER_ITEM_TRANSITIONS = [
        OrderItem::STATUS_PENDING => [
            OrderItem::STATUS_IN_SERVICE,
            OrderItem::STATUS_COMPLETED,
            OrderItem::STATUS_CANCELLED,
        ],
        OrderItem::STATUS_IN_SERVICE => [
            OrderItem::STATUS_COMPLETED,
            OrderItem::STATUS_CANCELLED,
        ],
        OrderItem::STATUS_COMPLETED => [],
        OrderItem::STATUS_CANCELLED => [],
    ];

    /** 新人问卷任务状态允许流转。 */
    public const QUESTIONNAIRE_TASK_TRANSITIONS = [
        CoupleQuestionnaireTask::STATUS_PENDING => [
            CoupleQuestionnaireTask::STATUS_SUBMITTED,
            CoupleQuestionnaireTask::STATUS_CANCELLED,
            CoupleQuestionnaireTask::STATUS_VIEWED,
            CoupleQuestionnaireTask::STATUS_EXPIRED,
        ],
        CoupleQuestionnaireTask::STATUS_VIEWED => [
            CoupleQuestionnaireTask::STATUS_SUBMITTED,
            CoupleQuestionnaireTask::STATUS_CANCELLED,
            CoupleQuestionnaireTask::STATUS_EXPIRED,
        ],
        CoupleQuestionnaireTask::STATUS_SUBMITTED => [],
        CoupleQuestionnaireTask::STATUS_CANCELLED => [],
        CoupleQuestionnaireTask::STATUS_EXPIRED => [],
    ];

    /**
     * 判断某一类状态流转是否被契约允许。
     */
    public static function canTransit(string $domain, int $from, int $to): bool
    {
        $map = self::transitionMap($domain);
        if ($from === $to) {
            return true;
        }

        return in_array($to, $map[$from] ?? [], true);
    }

    /**
     * 输出某一类状态流转图，供测试或文档生成使用。
     */
    public static function transitionMap(string $domain): array
    {
        return match ($domain) {
            'order' => self::ORDER_TRANSITIONS,
            'payment' => self::PAYMENT_TRANSITIONS,
            'schedule' => self::SCHEDULE_TRANSITIONS,
            'order_item' => self::ORDER_ITEM_TRANSITIONS,
            'questionnaire_task' => self::QUESTIONNAIRE_TASK_TRANSITIONS,
            default => [],
        };
    }

    /**
     * 业务稳定态：进入后必须通过补偿单/人工介入才能再变化。
     */
    public static function terminalStates(string $domain): array
    {
        return match ($domain) {
            'order' => [Order::STATUS_CANCELLED, Order::STATUS_REFUNDED, Order::STATUS_USER_DELETED],
            'payment' => [Payment::STATUS_FAILED, Payment::STATUS_REFUNDED],
            'schedule' => [],
            'order_item' => [OrderItem::STATUS_COMPLETED, OrderItem::STATUS_CANCELLED],
            'questionnaire_task' => [CoupleQuestionnaireTask::STATUS_SUBMITTED, CoupleQuestionnaireTask::STATUS_CANCELLED, CoupleQuestionnaireTask::STATUS_EXPIRED],
            default => [],
        };
    }
}
