<?php
// +----------------------------------------------------------------------
// | 订单全链路审计回归测试（零数据库依赖）
// +----------------------------------------------------------------------
// | 执行：php server/tests/regression/OrderLifecycleAuditRegressionTest.php
// +----------------------------------------------------------------------

declare(strict_types=1);

use app\common\contract\CoreStateContract;
use app\common\lists\ListsSearchInterface;
use app\common\model\order\Order;
use app\common\model\order\Payment;
use app\common\service\pay\WeChatPayService;

require __DIR__ . '/../../vendor/autoload.php';

final class OrderLifecycleAuditRegressionTest
{
    private int $assertions = 0;
    private string $serverRoot;

    public function __construct()
    {
        $this->serverRoot = dirname(__DIR__, 2);
    }

    public function run(): void
    {
        $this->testOrderCreateWhitelistAndClamping();
        $this->testApplyPaidStateAfterPaymentFullPaySetsBalancePaid();
        $this->testManualOrderOfflinePaidSetsBalancePaidAndBlocksTempOnline();
        $this->testCronCommandsUseCursorPagination();
        $this->testOrderCancelGuardsAndCascades();
        $this->testStaffSettlementSupportsFullPayAndReviewedOrders();
        $this->testStrictTypesControllerCastsAndScopeGuards();
        $this->testOrderChangeAndPauseSafety();
        $this->testWechatRefundCurrencyOptional();
        $this->testReviewOwnershipAndReviewedTransition();
        $this->testCoreStateContractTransitions();
        $this->testAdminListsSearchAndFields();
        $this->testV2LogicOrderActionsAndFields();

        echo 'OK - ' . $this->assertions . " assertions\n";
    }

    private function testOrderCreateWhitelistAndClamping(): void
    {
        $apiOrderLogic = $this->read('app/api/logic/OrderLogic.php');
        $this->assertContains("'discount_amount' => 0", $apiOrderLogic, '小程序建单必须强制 discount_amount=0');
        $this->assertContains("'source' => Order::SOURCE_MINIAPP", $apiOrderLogic, '小程序建单必须强制 SOURCE_MINIAPP');
        $this->assertContains("'payment_channel' => Order::PAYMENT_CHANNEL_ONLINE", $apiOrderLogic, '小程序建单必须强制 PAYMENT_CHANNEL_ONLINE');

        $orderModel = $this->read('app/common/model/order/Order.php');
        $this->assertContains('round(max($totalAmount - $discountAmount, 0), 2)', $orderModel, '订单金额必须有非负钳制');
    }

    private function testApplyPaidStateAfterPaymentFullPaySetsBalancePaid(): void
    {
        $order = new Order();
        $order->deposit_amount = 0;
        $order->balance_amount = 0;
        $order->balance_paid = 0;
        $order->deposit_paid = 0;

        $status = Order::applyPaidStateAfterPayment($order, Payment::TYPE_FULL, 1700000000);
        $this->assertSame(Order::STATUS_PENDING_SERVICE, $status, '全款支付后应进入待服务');
        $this->assertSame(1, (int)$order->balance_paid, '全款支付必须同步设置 balance_paid=1');
        $this->assertSame(Order::PAY_STATUS_PAID, (int)$order->pay_status, '全款支付必须设置 pay_status=1');
    }

    private function testManualOrderOfflinePaidSetsBalancePaidAndBlocksTempOnline(): void
    {
        $manual = $this->read('app/common/service/ManualOrderService.php');
        $this->assertContains("'balance_paid' => \$isOfflinePaid ? 1 : 0", $manual, '线下已收款建单必须置 balance_paid=1');
        $this->assertContains('临时客户未绑定账号，不支持待线上支付模式', $manual, '禁止临时客户+待线上支付组合');

        $controller = $this->read('app/adminapi/controller/order/OrderController.php');
        $this->assertContains('服务人员不可直接登记为线下已收款', $controller, '服务人员角色严禁直接登记 offline_paid');
        $this->assertContains('Order::SOURCE_STAFF', $controller, '服务人员后台建单必须记录 SOURCE_STAFF');
    }

    private function testCronCommandsUseCursorPagination(): void
    {
        $cancelCmd = $this->read('app/common/command/CancelUnpaidOrders.php');
        $this->assertContains("where('id', '>', \$lastId)", $cancelCmd, 'CancelUnpaidOrders 必须使用递增游标分页防死循环');
        $this->assertContains("->order('id', 'asc')", $cancelCmd, 'CancelUnpaidOrders 必须按 id 升序分页');

        $confirmCmd = $this->read('app/common/command/HandlePendingConfirmOrders.php');
        $this->assertContains("where('id', '>', \$lastId)", $confirmCmd, 'HandlePendingConfirmOrders 必须使用递增游标分页防死循环');
        $this->assertContains("->order('id', 'asc')", $confirmCmd, 'HandlePendingConfirmOrders 必须按 id 升序分页');
    }

    private function testOrderCancelGuardsAndCascades(): void
    {
        $orderModel = $this->read('app/common/model/order/Order.php');
        $this->assertContains('!$this->isOfflineVoucherPending()', $orderModel, '待审线下凭证不得被超时自动取消');
        $this->assertContains('?callable $guardCallback = null', $orderModel, 'cancelOrder 必须支持锁内二次守卫回调');
        $this->assertContains('ScheduleOccupancyService::assertOrderItemsFree($orderId, \'自动确认订单\')', $orderModel, '自动确认必须校验档期占用');
        $this->assertContains('handleCompletedPostCommit', $orderModel, '完成订单后置逻辑必须统一收敛');
    }

    private function testStaffSettlementSupportsFullPayAndReviewedOrders(): void
    {
        $settlement = $this->read('app/common/service/StaffSettlementService.php');
        $this->assertContains('Order::STATUS_REVIEWED', $settlement, '结算必须覆盖已评价状态订单');
        $this->assertContains("whereOr('o.balance_amount', '<=', 0)", $settlement, '结算查询必须兼容全款订单');
        $this->assertContains("where('is_compensation', 1)", $settlement, '平台净额必须排除补偿支付');
    }

    private function testStrictTypesControllerCastsAndScopeGuards(): void
    {
        $changeCtrl = $this->read('app/adminapi/controller/order/OrderChangeController.php');
        $this->assertContains('$approved = (int)$params[\'approved\'] === 1;', $changeCtrl, 'OrderChangeController::audit 必须转换 bool');
        $this->assertContains('checkChangeScope', $changeCtrl, 'OrderChangeController 必须有人员范围鉴权');

        $pauseCtrl = $this->read('app/adminapi/controller/order/OrderPauseController.php');
        $this->assertContains('$approved = (int)$params[\'approved\'] === 1;', $pauseCtrl, 'OrderPauseController::audit 必须转换 bool');
        $this->assertContains('OrderPauseLogic::detail((int)$params[\'id\'])', $pauseCtrl, 'OrderPauseController::detail 必须强转 int');
        $this->assertContains('checkPauseScope', $pauseCtrl, 'OrderPauseController 必须有人员范围鉴权');

        $payCtrl = $this->read('app/adminapi/controller/order/PaymentController.php');
        $this->assertContains('PaymentLogic::detail((int)$params[\'id\'])', $payCtrl, 'PaymentController::detail 必须强转 int');

        $apiChangeCtrl = $this->read('app/api/controller/OrderChangeController.php');
        $this->assertContains('OrderChangeLogic::getChangeDetail((int)$params[\'id\']', $apiChangeCtrl, 'api/OrderChangeController::detail 必须强转 int');
        $this->assertContains('OrderChangeLogic::checkCanChange((int)$params[\'order_id\']', $apiChangeCtrl, 'api/OrderChangeController::checkCanChange 必须强转 int');
    }

    private function testOrderChangeAndPauseSafety(): void
    {
        $change = $this->read('app/common/model/order/OrderChange.php');
        $this->assertContains("return [false, (string)(\$result['message'] ?? '变更执行失败')];", $change, 'executeChange 必须返回位置元组');
        $this->assertContains('Schedule::confirmBooking(', $change, 'executeAddItem 必须走 Schedule::confirmBooking');

        $schedule = $this->read('app/common/model/schedule/Schedule.php');
        $this->assertContains('bool $lockedOnly = false', $schedule, 'Schedule::releaseLock 必须支持仅释放临时锁保护');

        $addon = $this->read('app/common/model/order/OrderItemAddon.php');
        $this->assertContains('OrderItem::STATUS_CANCELLED', $addon, 'refreshOrderAmounts 必须过滤已取消订单项');
        $this->assertContains("'balance_amount'", $addon, 'refreshOrderAmounts 必须同步尾款字段');
    }

    private function testWechatRefundCurrencyOptional(): void
    {
        $noCurrency = [
            'out_trade_no' => 'P1',
            'transaction_id' => 'WX1',
            'amount' => ['total' => 10000, 'refund' => 2500],
        ];
        $this->assertSame('', WeChatPayService::validateRefundResult($noCurrency, 'P1', 'WX1', '100.00', '25.00'), '缺省 optional currency 应放行');

        $badCurrency = [
            'out_trade_no' => 'P1',
            'transaction_id' => 'WX1',
            'amount' => ['total' => 10000, 'refund' => 2500, 'currency' => 'USD'],
        ];
        $this->assertContains('币种', WeChatPayService::validateRefundResult($badCurrency, 'P1', 'WX1', '100.00', '25.00'), '非法币种必须拒绝');
    }

    private function testReviewOwnershipAndReviewedTransition(): void
    {
        $reviewLogic = $this->read('app/api/logic/ReviewLogic.php');
        $this->assertContains('(int)$orderItem->order->user_id !== $userId', $reviewLogic, 'publish 必须校验订单归属');
        $this->assertContains('Order::STATUS_REVIEWED', $reviewLogic, '全项评价后必须推进订单至 STATUS_REVIEWED');
    }

    private function testCoreStateContractTransitions(): void
    {
        $transitions = CoreStateContract::ORDER_TRANSITIONS;
        $this->assertTrue(in_array(Order::STATUS_PENDING_PAY, $transitions[Order::STATUS_IN_SERVICE], true), '服务中应允许流转至待支付尾款');
        $this->assertTrue(in_array(Order::STATUS_COMPLETED, $transitions[Order::STATUS_PENDING_PAY], true), '待支付尾款付清应允许流转至已完成');
        $this->assertTrue(in_array(Order::STATUS_USER_DELETED, $transitions[Order::STATUS_COMPLETED], true), '已完成应允许用户删除');
        $this->assertTrue(!in_array(Order::STATUS_COMPLETED, $transitions[Order::STATUS_PENDING_SERVICE], true), '待服务不得跳过服务中直接完成');
        $this->assertTrue(!in_array(Order::STATUS_CANCELLED, $transitions[Order::STATUS_PAUSED], true), '已暂停不得直接取消');
    }

    private function testAdminListsSearchAndFields(): void
    {
        foreach (['OrderLists.php', 'OrderChangeLists.php', 'OrderPauseLists.php', 'RefundLists.php', 'PaymentLists.php'] as $file) {
            $src = $this->read('app/adminapi/lists/order/' . $file);
            $this->assertContains("'between_time' => 'create_time'", $src, $file . ' 的 between_time 必须为字符串');
        }
        $this->assertTrue(is_subclass_of(\app\adminapi\lists\order\RefundLists::class, ListsSearchInterface::class), 'RefundLists 必须实现 ListsSearchInterface');
        $this->assertTrue(is_subclass_of(\app\adminapi\lists\order\PaymentLists::class, ListsSearchInterface::class), 'PaymentLists 必须实现 ListsSearchInterface');

        $orderLists = $this->read('app/adminapi/lists/order/OrderLists.php');
        $this->assertContains("item['source_desc'] = \$this->getSourceDesc", $orderLists, 'OrderLists 必须填充 source_desc');
        $this->assertContains("item['can_admin_cancel']", $orderLists, 'OrderLists 必须返回 can_admin_cancel');
    }

    private function testV2LogicOrderActionsAndFields(): void
    {
        $v2 = $this->read('app/api/logic/V2Logic.php');
        $this->assertContains('!$offlineActive', $v2, 'V2Logic 线下单不得激活线上支付按钮');
        $this->assertContains("'refundable_amount' => \$detail['refundable_amount']", $v2, 'V2Logic::order 必须返回 refundable_amount');
        $this->assertContains("'pay_voucher_status' => \$detail['pay_voucher_status']", $v2, 'V2Logic::order 必须返回 pay_voucher_status');
    }

    private function read(string $relativePath): string
    {
        $path = $this->serverRoot . '/' . $relativePath;
        $content = file_get_contents($path);
        if ($content === false) {
            throw new RuntimeException('读取失败：' . $path);
        }
        return $content;
    }

    private function assertContains(string $needle, string $haystack, string $message): void
    {
        $this->assertions++;
        if (!str_contains($haystack, $needle)) {
            throw new RuntimeException($message . '，缺少：' . $needle);
        }
    }

    private function assertSame($expected, $actual, string $message): void
    {
        $this->assertions++;
        if ($expected !== $actual) {
            throw new RuntimeException($message . '，期望 ' . var_export($expected, true) . '，实际 ' . var_export($actual, true));
        }
    }

    private function assertTrue(bool $cond, string $message): void
    {
        $this->assertions++;
        if (!$cond) {
            throw new RuntimeException($message);
        }
    }
}

try {
    (new OrderLifecycleAuditRegressionTest())->run();
} catch (Throwable $e) {
    fwrite(STDERR, 'FAIL - ' . $e->getMessage() . "\n");
    exit(1);
}
