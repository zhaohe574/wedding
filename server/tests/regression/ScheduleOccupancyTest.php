<?php
// +----------------------------------------------------------------------
// | 档期占用口径回归测试（未付款不占用 / 已确认且已付款才占用）
// +----------------------------------------------------------------------
// | 说明：项目未安装 phpunit，沿用轻量断言 + 源码扫描风格。
// | 执行：php tests/regression/ScheduleOccupancyTest.php
// +----------------------------------------------------------------------

declare(strict_types=1);

use app\common\model\order\Order;
use app\common\service\ScheduleOccupancyService;

require __DIR__ . '/../../vendor/autoload.php';

final class ScheduleOccupancyTest
{
    private int $assertions = 0;

    public function run(): void
    {
        $this->testUnpaidOrdersDoNotOccupy();
        $this->testPaidConfirmedOrdersOccupy();
        $this->testClosedOrdersNeverOccupy();
        $this->testSingleOccupancyEntrypoint();
        $this->testConfirmEntrypointsAreGuarded();
        $this->testPaymentEntrypointsAreGuarded();
        $this->testOrderCreationReleasesUserHold();
        $this->testStaleBookingRepairExists();
        $this->testInstallSqlIndexExists();

        echo 'OK - ' . $this->assertions . " assertions\n";
    }

    private function testUnpaidOrdersDoNotOccupy(): void
    {
        $this->assertFalse(
            ScheduleOccupancyService::isOrderOccupying([
                'order_status' => Order::STATUS_PENDING_CONFIRM,
                'paid_amount' => 0,
                'deposit_paid' => 0,
                'pay_status' => Order::PAY_STATUS_UNPAID,
            ]),
            '待确认未付款订单不得占用档期'
        );

        $this->assertFalse(
            ScheduleOccupancyService::isOrderOccupying([
                'order_status' => Order::STATUS_PENDING_PAY,
                'paid_amount' => 0,
                'deposit_paid' => 0,
                'pay_status' => Order::PAY_STATUS_UNPAID,
            ]),
            '已确认但未付款订单不得占用档期'
        );
    }

    private function testPaidConfirmedOrdersOccupy(): void
    {
        $this->assertTrue(
            ScheduleOccupancyService::isOrderOccupying([
                'order_status' => Order::STATUS_PENDING_SERVICE,
                'paid_amount' => 100,
                'deposit_paid' => 0,
                'pay_status' => Order::PAY_STATUS_UNPAID,
            ]),
            '已确认且已付全款订单必须占用档期'
        );

        $this->assertTrue(
            ScheduleOccupancyService::isOrderOccupying([
                'order_status' => Order::STATUS_PENDING_SERVICE,
                'paid_amount' => 30,
                'deposit_paid' => 1,
                'pay_status' => Order::PAY_STATUS_UNPAID,
            ]),
            '已确认且已付定金订单必须占用档期'
        );

        $this->assertTrue(
            ScheduleOccupancyService::isOrderOccupying([
                'order_status' => Order::STATUS_IN_SERVICE,
                'paid_amount' => 0,
                'deposit_paid' => 0,
                'pay_status' => Order::PAY_STATUS_PAID,
            ]),
            'pay_status=已支付的历史订单必须占用档期'
        );
    }

    private function testClosedOrdersNeverOccupy(): void
    {
        foreach ([
            Order::STATUS_CANCELLED,
            Order::STATUS_REFUNDED,
            Order::STATUS_USER_DELETED,
        ] as $status) {
            $this->assertFalse(
                ScheduleOccupancyService::isOrderOccupying([
                    'order_status' => $status,
                    'paid_amount' => 100,
                    'deposit_paid' => 1,
                    'pay_status' => Order::PAY_STATUS_PAID,
                ]),
                '已关闭订单不得占用档期：' . $status
            );
        }

        $this->assertFalse(
            ScheduleOccupancyService::isOrderOccupying([
                'order_status' => Order::STATUS_PENDING_SERVICE,
                'paid_amount' => 100,
                'deposit_paid' => 0,
                'pay_status' => Order::PAY_STATUS_PAID,
                'delete_time' => time(),
            ]),
            '已删除订单不得占用档期'
        );
    }

    private function testSingleOccupancyEntrypoint(): void
    {
        $service = $this->readSource('app/common/service/ScheduleOccupancyService.php');
        $this->assertContains('CONFIRMED_ORDER_STATUSES', $service, '占用服务必须显式定义「已确认」状态集合');
        $this->assertContains("where('o.paid_amount', '>', 0)", $service, '占用查询必须要求已付款');
        $this->assertContains('COUNT(DISTINCT o.id)', $service, '占用统计必须按订单去重，避免一单多项重复计数');
        $this->assertContains('PAID_ORDER_CAPACITY_PER_DAY', $service, '必须声明单日已付款订单容量');

        $rule = $this->readSource('app/common/model/schedule/ScheduleRule.php');
        $this->assertContains('ScheduleOccupancyService::countOccupied(', $rule, '档期规则必须复用统一占用统计');
        $this->assertContains('ScheduleOccupancyService::countOccupiedBatch(', $rule, '批量占用统计必须复用统一口径');
        $this->assertNotContains('o.source', $rule, '档期规则不得再按订单来源自行判断占用');

        $availability = $this->readSource('app/common/service/ScheduleAvailabilityService.php');
        $this->assertContains('PAID_ORDER_CAPACITY_PER_DAY', $availability, '日历可约人数必须与已付款容量一致');
    }

    private function testConfirmEntrypointsAreGuarded(): void
    {
        $staffCenter = $this->readSource('app/api/logic/StaffCenterLogic.php');
        $this->assertContains('ScheduleOccupancyService::assertOrderItemsFree($orderId, \'确认订单\', false, $staffId)', $staffCenter, '服务人员确认订单前必须校验已付款占用');

        $adminOrder = $this->readSource('app/adminapi/logic/order/OrderLogic.php');
        $this->assertContains('ScheduleOccupancyService::assertOrderItemsFree($orderId, \'确认订单\', false, $staffId)', $adminOrder, '后台代服务人员确认前必须校验已付款占用');
        $this->assertContains('ScheduleOccupancyService::assertOrderItemsFree($orderId, \'确认订单\')', $adminOrder, '后台整单确认前必须校验已付款占用');

        $booking = $this->readSource('app/adminapi/logic/schedule/BookingLogic.php');
        $this->assertContains('ScheduleOccupancyService::assertDateFree(', $booking, '预约项确认前必须校验已付款占用');
    }

    private function testPaymentEntrypointsAreGuarded(): void
    {
        $payLogic = $this->readSource('app/common/logic/OrderPayLogic.php');
        $this->assertContains('ScheduleOccupancyService::assertOrderItemsFree((int)$order->id, \'支付\')', $payLogic, '发起支付前必须校验已付款占用');

        $order = $this->readSource('app/common/model/order/Order.php');
        $this->assertContains('ScheduleOccupancyService::assertDateFree($staffId, $serviceDate, (int)$order->id, \'占用该档期\')', $order, '首笔支付锁档必须校验已付款占用');
        $this->assertContains('该日期已有已付款订单，无法重复占用', $order, '锁档冲突必须返回明确提示');
        $this->assertContains('releaseOrderCreationHolds', $order, '下单成功后必须释放临时锁');

        $schedule = $this->readSource('app/common/model/schedule/Schedule.php');
        $this->assertContains('ScheduleOccupancyService::assertDateFree($staffId, $date, $orderId, \'占用该档期\')', $schedule, '档期确认必须统一校验已付款占用');
        $this->assertContains('releaseStaleBookingRecord', $schedule, '必须能释放未付款订单遗留的已预约记录');
    }

    private function testOrderCreationReleasesUserHold(): void
    {
        $order = $this->readSource('app/common/model/order/Order.php');
        $createBody = $this->extractFunctionBody($order, 'createOrderInsideAvailabilityCheck');
        $commitPos = strpos($createBody, 'Db::commit()');
        $releasePos = strpos($createBody, 'releaseOrderCreationHolds');
        $this->assertTrue(
            $commitPos !== false && $releasePos !== false && $commitPos < $releasePos,
            '订单创建成功后必须释放用户临时锁'
        );
    }

    private function testStaleBookingRepairExists(): void
    {
        $migration = $this->readSource('database/migrations/20261111_schedule_occupancy.php');
        $this->assertContains('--repair', $migration, '迁移脚本必须提供历史已预约记录清理入口');
        $this->assertContains('paid_amount <= 0', $migration, '清理条件必须识别未付款订单');
    }

    private function testInstallSqlIndexExists(): void
    {
        $installSql = $this->readSource('public/install/db/like.sql');
        $this->assertContains(
            'KEY `idx_staff_date_type` (`staff_id`,`service_date`,`item_type`,`item_status`)',
            $installSql,
            '安装库必须内置档期占用统计复合索引'
        );
    }

    private function readSource(string $relativePath): string
    {
        $path = dirname(__DIR__, 2) . '/' . $relativePath;
        $contents = file_get_contents($path);
        if ($contents === false) {
            throw new RuntimeException('无法读取文件：' . $path);
        }
        return $contents;
    }

    private function extractFunctionBody(string $source, string $functionName): string
    {
        $position = strpos($source, 'function ' . $functionName . '(');
        if ($position === false) {
            throw new RuntimeException('无法定位函数：' . $functionName);
        }

        $braceStart = strpos($source, '{', $position);
        if ($braceStart === false) {
            throw new RuntimeException('无法定位函数体：' . $functionName);
        }

        $depth = 0;
        $length = strlen($source);
        for ($i = $braceStart; $i < $length; $i++) {
            if ($source[$i] === '{') {
                $depth++;
            } elseif ($source[$i] === '}') {
                $depth--;
                if ($depth === 0) {
                    return substr($source, $braceStart, $i - $braceStart + 1);
                }
            }
        }

        throw new RuntimeException('函数体未闭合：' . $functionName);
    }

    private function assertContains(string $needle, string $haystack, string $message): void
    {
        $this->assertions++;
        if (!str_contains($haystack, $needle)) {
            throw new RuntimeException($message . '，缺少片段：' . $needle);
        }
    }

    private function assertNotContains(string $needle, string $haystack, string $message): void
    {
        $this->assertions++;
        if (str_contains($haystack, $needle)) {
            throw new RuntimeException($message . '，不应包含片段：' . $needle);
        }
    }

    private function assertTrue(bool $condition, string $message): void
    {
        $this->assertions++;
        if (!$condition) {
            throw new RuntimeException($message);
        }
    }

    private function assertFalse(bool $condition, string $message): void
    {
        $this->assertions++;
        if ($condition) {
            throw new RuntimeException($message);
        }
    }
}

try {
    (new ScheduleOccupancyTest())->run();
} catch (Throwable $e) {
    fwrite(STDERR, 'FAIL - ' . $e->getMessage() . "\n");
    exit(1);
}
