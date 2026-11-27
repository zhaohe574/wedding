<?php
declare(strict_types=1);

/**
 * 备婚进度纯函数单元测试（零数据库）。
 *
 * 直接执行：
 *   php server/tests/unit/WeddingProgressTest.php
 */

$serverRoot = dirname(__DIR__, 2);
require $serverRoot . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';

$app = new \think\App($serverRoot);
$app->initialize();

use app\api\logic\WeddingProgressLogic;
use app\common\model\order\Order;

$assertions = 0;

$assertTrue = static function (bool $condition, string $label) use (&$assertions): void {
    $assertions++;
    if (!$condition) {
        throw new RuntimeException('[FAIL] ' . $label);
    }
};

$assertSame = static function ($actual, $expected, string $label) use (&$assertions): void {
    $assertions++;
    if ($actual !== $expected) {
        throw new RuntimeException(sprintf(
            "[FAIL] %s\n  expected: %s\n  actual:   %s",
            $label,
            var_export($expected, true),
            var_export($actual, true)
        ));
    }
};

$build = static fn (array $order, bool $questionnaireDone, bool $hasReview, string $today = '2026-11-27', array $dates = []): array =>
    WeddingProgressLogic::buildMilestones($order, $questionnaireDone, $hasReview, $today, $dates);

$milestoneOf = static function (array $result, string $key): array {
    foreach ($result['milestones'] as $milestone) {
        if ($milestone['key'] === $key) {
            return $milestone;
        }
    }
    throw new RuntimeException('[FAIL] 缺少里程碑：' . $key);
};

$allowedPercents = [0, 20, 40, 60, 80, 100];

$payTime = strtotime('2026-09-12 10:30:00');
$questionnaireTime = strtotime('2026-09-20 15:00:00');
$reviewTime = strtotime('2026-12-21 09:00:00');
$updateTime = strtotime('2026-12-21 10:00:00');

/** 待服务 + 定金已付的基础订单 */
$baseOrder = [
    'pay_status' => Order::PAY_STATUS_PAID,
    'deposit_paid' => 1,
    'balance_paid' => 0,
    'balance_amount' => '7000.00',
    'paid_amount' => '3000.00',
    'pay_amount' => '10000.00',
    'total_amount' => '12000.00',
    'order_status' => Order::STATUS_PENDING_SERVICE,
    'service_date' => '2026-12-20',
    'pay_time' => $payTime,
    'update_time' => $payTime,
];

try {
    // ----------------------------------------------------------------
    // 1. 0%：空订单 / 未付定金
    // ----------------------------------------------------------------
    $result = $build([], false, false);
    $assertSame(0, $result['percent'], '空订单进度 0%');
    $assertSame(0, $result['done_count'], '空订单完成数 0');
    $assertSame(5, $result['total_count'], '总节点数恒为 5');
    $assertSame('deposit', $result['current_key'], '空订单当前节点为 deposit');
    $assertSame('', $milestoneOf($result, 'balance')['date_text'], '未完成尾款不显示日期');
    $assertSame('', $milestoneOf($result, 'wedding_day')['date_text'], '无婚期不显示日期');

    $unpaid = $baseOrder;
    $unpaid['pay_status'] = Order::PAY_STATUS_UNPAID;
    $unpaid['deposit_paid'] = 0;
    $unpaid['paid_amount'] = '0.00';
    $result = $build($unpaid, false, false);
    $assertSame(0, $result['percent'], '未付定金进度 0%');
    $assertSame(false, $milestoneOf($result, 'deposit')['done'], '未付定金不算锁定档期');

    // ----------------------------------------------------------------
    // 2. 20%：定金已付
    // ----------------------------------------------------------------
    $result = $build($baseOrder, false, false);
    $assertSame(20, $result['percent'], '定金已付进度 20%');
    $assertSame(1, $result['done_count'], '定金已付完成数 1');
    $assertSame('questionnaire', $result['current_key'], '20% 当前节点为 questionnaire');
    $assertSame(true, $milestoneOf($result, 'deposit')['done'], '定金节点完成');
    $assertSame('2026.09.12', $milestoneOf($result, 'deposit')['date_text'], '定金日期为 Y.m.d');
    $assertSame(false, $milestoneOf($result, 'wedding_day')['done'], '未来婚期不算婚礼当天完成');
    $assertSame('2026.12.20', $milestoneOf($result, 'wedding_day')['date_text'], '已有婚期即展示预期日期');

    // ----------------------------------------------------------------
    // 3. 40%：定金 + 问卷
    // ----------------------------------------------------------------
    $result = $build($baseOrder, true, false, '2026-11-27', [
        'questionnaire_time' => $questionnaireTime,
    ]);
    $assertSame(40, $result['percent'], '定金+问卷进度 40%');
    $assertSame(2, $result['done_count'], '定金+问卷完成数 2');
    $assertSame('balance', $result['current_key'], '40% 当前节点为 balance');
    $assertSame('2026.09.20', $milestoneOf($result, 'questionnaire')['date_text'], '问卷日期为 Y.m.d');
    $assertSame('', $milestoneOf($result, 'balance')['date_text'], '未完成尾款日期保持空串');

    // ----------------------------------------------------------------
    // 4. 60%：定金 + 问卷 + 尾款
    // ----------------------------------------------------------------
    $balanceOrder = $baseOrder;
    $balanceOrder['balance_paid'] = 1;
    $balanceOrder['paid_amount'] = '10000.00';
    $result = $build($balanceOrder, true, false);
    $assertSame(60, $result['percent'], '尾款已结清进度 60%');
    $assertSame(3, $result['done_count'], '尾款已结清完成数 3');
    $assertSame('wedding_day', $result['current_key'], '60% 当前节点为 wedding_day');

    // 全款实付优惠订单：total 10000 / pay_amount 8000 / paid 8000 / 尾款 0
    $discountOrder = [
        'pay_status' => Order::PAY_STATUS_PAID,
        'deposit_paid' => 0,
        'balance_paid' => 0,
        'balance_amount' => '0.00',
        'paid_amount' => '8000.00',
        'pay_amount' => '8000.00',
        'total_amount' => '10000.00',
        'order_status' => Order::STATUS_PENDING_SERVICE,
        'service_date' => '2026-12-20',
        'pay_time' => $payTime,
        'update_time' => $payTime,
    ];
    $result = $build($discountOrder, true, false);
    $assertSame(60, $result['percent'], '全款优惠订单按实付口径进度 60%');
    $assertSame(true, $milestoneOf($result, 'deposit')['done'], '全款优惠订单锁定档期完成');
    $assertSame(true, $milestoneOf($result, 'balance')['done'], '尾款为 0 且付满应付即结清');

    // ----------------------------------------------------------------
    // 5. 80%：婚礼当天已到且服务中
    // ----------------------------------------------------------------
    $inServiceOrder = $balanceOrder;
    $inServiceOrder['order_status'] = Order::STATUS_IN_SERVICE;
    $inServiceOrder['service_date'] = '2026-11-27';
    $result = $build($inServiceOrder, true, false, '2026-11-27');
    $assertSame(80, $result['percent'], '婚礼当天已到进度 80%');
    $assertSame(4, $result['done_count'], '婚礼当天已到完成数 4');
    $assertSame('completed', $result['current_key'], '80% 当前节点为 completed');
    $assertSame(true, $milestoneOf($result, 'wedding_day')['done'], '婚期当天且服务中算婚礼当天完成');
    $assertSame('2026.11.27', $milestoneOf($result, 'wedding_day')['date_text'], '婚礼当天日期为 Y.m.d');

    $pastServiceOrder = $inServiceOrder;
    $pastServiceOrder['service_date'] = '2026-11-20';
    $result = $build($pastServiceOrder, true, false, '2026-11-27');
    $assertSame(80, $result['percent'], '婚期已过且服务中进度 80%');

    // 取消/暂停/退款终态：即使日期已到也不算婚礼当天
    $refundedOrder = $inServiceOrder;
    $refundedOrder['order_status'] = Order::STATUS_REFUNDED;
    $result = $build($refundedOrder, true, false, '2026-11-27');
    $assertSame(false, $milestoneOf($result, 'wedding_day')['done'], '退款终态不算婚礼当天');

    // ----------------------------------------------------------------
    // 6. 100%：已评价 / 已完成
    // ----------------------------------------------------------------
    $reviewedOrder = $inServiceOrder;
    $reviewedOrder['order_status'] = Order::STATUS_REVIEWED;
    $result = $build($reviewedOrder, true, true, '2026-11-27', [
        'review_time' => $reviewTime,
    ]);
    $assertSame(100, $result['percent'], '已评价进度 100%');
    $assertSame(5, $result['done_count'], '已评价完成数 5');
    $assertSame('', $result['current_key'], '全部完成时当前节点为空串');
    $assertSame('2026.12.21', $milestoneOf($result, 'completed')['date_text'], '服务完成日期取评价时间');

    $completedOrder = $inServiceOrder;
    $completedOrder['order_status'] = Order::STATUS_COMPLETED;
    $completedOrder['update_time'] = $updateTime;
    $result = $build($completedOrder, true, false, '2026-11-27');
    $assertSame(100, $result['percent'], '订单已完成（无评价）进度 100%');
    $assertSame('2026.12.21', $milestoneOf($result, 'completed')['date_text'], '无评价时服务完成日期取订单更新时间');

    // ----------------------------------------------------------------
    // 7. 百分比只能是 5 等分值
    // ----------------------------------------------------------------
    foreach ([
        $build([], false, false),
        $build($baseOrder, false, false),
        $build($baseOrder, true, false),
        $build($balanceOrder, true, false),
        $build($inServiceOrder, true, false, '2026-11-27'),
        $build($reviewedOrder, true, true, '2026-11-27'),
    ] as $index => $sample) {
        $assertTrue(
            in_array($sample['percent'], $allowedPercents, true),
            sprintf('第 %d 组 percent=%d 属于 5 等分允许值', $index + 1, $sample['percent'])
        );
        $assertSame(5, $sample['total_count'], '每组 total_count 恒为 5');
        $assertSame(count($sample['milestones']), 5, '每组固定 5 个里程碑');
    }

    echo sprintf("WeddingProgressTest 通过：%d 条断言\n", $assertions);
} catch (Throwable $e) {
    fwrite(STDERR, $e->getMessage() . "\n");
    exit(1);
}
