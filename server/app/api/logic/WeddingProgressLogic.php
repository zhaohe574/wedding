<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 备婚进度聚合逻辑
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\api\logic;

use app\common\model\order\Order;
use app\common\model\questionnaire\CoupleQuestionnaireTask;
use app\common\model\review\Review;

/**
 * 备婚进度（5 个里程碑）。
 *
 * 设计原则：
 *   1. 只认真实表字段，不做时间插值 —— 进度只可能是 0/20/40/60/80/100；
 *   2. 纯函数 buildMilestones() 零 DB，summary() 只负责查询编排；
 *   3. 不新增表/迁移：订单、问卷任务、评价三处现有列足够推导全部节点。
 *
 * 消费方：`/api/v2/me` 的 `wedding.progress`（经由 V2Logic::me() 聚合）。
 */
class WeddingProgressLogic
{
    /** 里程碑总数 */
    public const TOTAL_COUNT = 5;

    /** 里程碑键（与 shared/contracts/dto.ts 的 WeddingMilestoneKey 一致） */
    public const KEY_DEPOSIT = 'deposit';
    public const KEY_QUESTIONNAIRE = 'questionnaire';
    public const KEY_BALANCE = 'balance';
    public const KEY_WEDDING_DAY = 'wedding_day';
    public const KEY_COMPLETED = 'completed';

    /** 取消 / 暂停 / 退款终态：婚礼当天节点不能算完成 */
    private const TERMINAL_STATUSES = [
        Order::STATUS_CANCELLED,
        Order::STATUS_PAUSED,
        Order::STATUS_REFUNDED,
    ];

    /**
     * 纯函数：由订单字段 + 问卷/评价状态推导 5 个里程碑。
     *
     * @param array $order 订单字段（pay_status / deposit_paid / balance_paid / balance_amount /
     *                     paid_amount / pay_amount / total_amount / order_status /
     *                     service_date / pay_time / update_time）
     * @param bool $questionnaireDone 是否已提交新人问卷
     * @param bool $hasReview 是否存在有效评价
     * @param string $today 今天（Y-m-d），便于测试注入
     * @param array $dates 可选时间戳：questionnaire_time / review_time / balance_time
     * @return array{percent:int,done_count:int,total_count:int,current_key:string,milestones:array}
     */
    public static function buildMilestones(
        array $order,
        bool $questionnaireDone,
        bool $hasReview,
        string $today,
        array $dates = []
    ): array {
        $status = (int)($order['order_status'] ?? 0);
        $payStatus = (int)($order['pay_status'] ?? 0);
        $paidAmount = (float)($order['paid_amount'] ?? 0);

        // 应付口径：优先实付金额（含优惠订单），没有再用订单总额
        $payable = (float)($order['pay_amount'] ?? 0) > 0
            ? (float)$order['pay_amount']
            : (float)($order['total_amount'] ?? 0);

        // 1. 锁定档期：已支付，且定金已收或已付满应付金额
        $depositDone = $payStatus === Order::PAY_STATUS_PAID
            && ((int)($order['deposit_paid'] ?? 0) === 1 || ($payable > 0 && $paidAmount >= $payable));

        // 2. 新人问卷：由调用方查询后传入

        // 3. 尾款结清：尾款已收，或尾款为 0 且已付满应付金额（全款订单）
        $balanceDone = (int)($order['balance_paid'] ?? 0) === 1
            || (round((float)($order['balance_amount'] ?? 0), 2) <= 0.0 && $payable > 0 && $paidAmount >= $payable);

        // 4. 婚礼当天：已到婚期且服务已开始，取消/暂停/退款终态不算
        $serviceDate = trim((string)($order['service_date'] ?? ''));
        $serviceTimestamp = $serviceDate !== '' ? strtotime($serviceDate) : false;
        $todayTimestamp = strtotime($today);
        $weddingDayDone = $serviceDate !== ''
            && $serviceTimestamp !== false
            && $todayTimestamp !== false
            && $serviceTimestamp <= $todayTimestamp
            && $status >= Order::STATUS_IN_SERVICE
            && !in_array($status, self::TERMINAL_STATUSES, true);
        // 已有确定婚期就展示预期日期（即使还没到当天，符合 §4.4 示例）
        $weddingDateText = $serviceDate !== '' ? str_replace('-', '.', $serviceDate) : '';

        // 5. 服务完成：订单已完成/已评价，或存在有效评价
        $completedDone = in_array($status, [Order::STATUS_COMPLETED, Order::STATUS_REVIEWED], true)
            || $hasReview;

        $reviewTime = (int)($dates['review_time'] ?? 0);
        $completedTime = $reviewTime > 0 ? $reviewTime : (int)($order['update_time'] ?? 0);

        $milestones = [
            [
                'key' => self::KEY_DEPOSIT,
                'label' => '锁定档期',
                'done' => $depositDone,
                'date_text' => $depositDone ? self::formatTimestamp((int)($order['pay_time'] ?? 0)) : '',
            ],
            [
                'key' => self::KEY_QUESTIONNAIRE,
                'label' => '新人问卷',
                'done' => $questionnaireDone,
                'date_text' => $questionnaireDone
                    ? self::formatTimestamp((int)($dates['questionnaire_time'] ?? 0))
                    : '',
            ],
            [
                'key' => self::KEY_BALANCE,
                'label' => '尾款结清',
                // 未完成时不虚构未来日期
                'done' => $balanceDone,
                'date_text' => $balanceDone
                    ? self::formatTimestamp((int)($dates['balance_time'] ?? 0))
                    : '',
            ],
            [
                'key' => self::KEY_WEDDING_DAY,
                'label' => '婚礼当天',
                'done' => $weddingDayDone,
                'date_text' => $weddingDateText,
            ],
            [
                'key' => self::KEY_COMPLETED,
                'label' => '服务完成',
                'done' => $completedDone,
                'date_text' => $completedDone ? self::formatTimestamp($completedTime) : '',
            ],
        ];

        $doneCount = 0;
        $currentKey = '';
        foreach ($milestones as $milestone) {
            if ($milestone['done']) {
                $doneCount++;
            } elseif ($currentKey === '') {
                $currentKey = $milestone['key'];
            }
        }

        return [
            // 只允许 0/20/40/60/80/100 六种值，严禁时间插值
            'percent' => (int)round($doneCount / self::TOTAL_COUNT * 100),
            'done_count' => $doneCount,
            'total_count' => self::TOTAL_COUNT,
            'current_key' => $currentKey,
            'milestones' => $milestones,
        ];
    }

    /**
     * 查询编排：最近一笔有效婚期订单 + 问卷任务 + 评价，交给纯函数计算。
     *
     * 订单筛选与 UserLogic::weddingDate() 完全同口径；无符合订单返回 null，
     * 由 V2Logic 决定 wedding.progress 不出现。
     */
    public static function summary(int $userId): ?array
    {
        $today = date('Y-m-d');

        $order = Order::where('user_id', $userId)
            ->where('pay_status', Order::PAY_STATUS_PAID)
            ->whereIn('order_status', [Order::STATUS_PENDING_SERVICE, Order::STATUS_IN_SERVICE])
            ->whereNotNull('service_date')
            ->where('service_date', '<>', '')
            ->where('service_date', '>=', $today)
            ->order('service_date', 'asc')
            ->field(
                'id, order_sn, service_date, order_status, pay_status, deposit_paid, balance_paid,'
                . ' balance_amount, paid_amount, pay_amount, total_amount, pay_time, update_time'
            )
            ->findOrEmpty();

        $orderData = $order->toArray();
        $orderId = (int)($orderData['id'] ?? 0);
        if ($orderId <= 0) {
            return null;
        }

        // 已提交问卷：同一订单只可能有一条（uk_order_id），取最近一条兜底脏数据
        $task = CoupleQuestionnaireTask::where('order_id', $orderId)
            ->whereNull('delete_time')
            ->where('status', CoupleQuestionnaireTask::STATUS_SUBMITTED)
            ->order('id', 'desc')
            ->field('id, submitted_time, submit_time, update_time')
            ->find();

        $questionnaireDone = $task !== null;
        $questionnaireTime = $questionnaireDone
            ? (int)($task->getData('submitted_time') ?: $task->getData('submit_time') ?: $task->getData('update_time') ?: 0)
            : 0;

        // Review 使用 SoftDelete，默认查询已排除软删记录
        $review = Review::where('order_id', $orderId)
            ->order('id', 'desc')
            ->field('id, create_time, update_time')
            ->find();

        $hasReview = $review !== null;
        $reviewTime = $hasReview
            ? (int)($review->getData('create_time') ?: $review->getData('update_time') ?: 0)
            : 0;

        return self::buildMilestones($orderData, $questionnaireDone, $hasReview, $today, [
            'questionnaire_time' => $questionnaireTime,
            'review_time' => $reviewTime,
        ]);
    }

    /** 时间戳 → Y.m.d；0 / 非法值返回空串 */
    private static function formatTimestamp(int $timestamp): string
    {
        return $timestamp > 0 ? date('Y.m.d', $timestamp) : '';
    }
}
