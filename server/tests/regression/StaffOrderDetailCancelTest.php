<?php
// +----------------------------------------------------------------------
// | 服务人员订单详情 / 取消订单回归测试（Batch 1B）
// +----------------------------------------------------------------------
// | 说明：与 OaNotificationChainAuditTest 同风格，Reflection + 内存模型 + 源码扫描。
// | 不依赖 WEDDING_TEST_MYSQL_PORT 等外部数据库，不做业务库读写。
// | 可直接执行：php tests/regression/StaffOrderDetailCancelTest.php
// +----------------------------------------------------------------------

declare(strict_types=1);

use app\api\controller\OrderController;
use app\api\logic\OrderLogic;
use app\api\logic\StaffCenterLogic;
use app\api\validate\OrderValidate;
use app\api\validate\StaffCenterValidate;
use app\common\model\order\Order;
use app\common\model\order\OrderItem;
use app\common\model\order\OrderLog;
use app\common\service\OrderNotificationService;

require __DIR__ . '/../../vendor/autoload.php';

$app = new \think\App(dirname(__DIR__, 2));
$app->initialize();

final class StaffOrderDetailCancelTest
{
    private int $assertions = 0;

    public function run(): void
    {
        $this->testOrderLogOperatorStaff();
        $this->testResolveCancelReasonText();
        $this->testValidateOrderCancelScene();
        $this->testApplyStaffOrderAmountsDepositTolerance();
        $this->testApplyStaffOrderAmountsFullPayment();
        $this->testResolveStaffCancelCapabilityMatrix();
        $this->testResolveStaffOrderRole();
        $this->testResolveStaffCancelCapabilityStaffRole();
        $this->testCollaboratorCancellationContracts();
        $this->testControllerOrderCancelContract();
        $this->testOrderDetailContract();
        $this->testIsWholeOrderOwnedByStaffCancelledFallback();
        $this->testApplyStaffOrderAmountsCancelledItems();
        $this->testExtractPendingReselectItems();
        $this->testReselectValidateScenes();
        $this->testReselectControllerContract();
        $this->testV2OrderPendingReselectContract();

        echo 'OK - ' . $this->assertions . " assertions\n";
    }

    // 1) OrderLog 操作者类型：服务人员 = 4，描述为「服务人员」
    private function testOrderLogOperatorStaff(): void
    {
        $this->assertSame(4, OrderLog::OPERATOR_STAFF, 'OrderLog::OPERATOR_STAFF 必须为 4');

        $log = new OrderLog(['operator_type' => OrderLog::OPERATOR_STAFF]);
        $this->assertSame('服务人员', $log->getOperatorTypeDescAttr(null, ['operator_type' => OrderLog::OPERATOR_STAFF]),
            'getOperatorTypeDescAttr 必须把 4 映射为「服务人员」');
        $this->assertSame('服务人员', $log->operator_type_desc,
            'operator_type_desc 访问器必须返回「服务人员」');
    }

    // 2) 取消原因文案：服务人员取消 / 空原因兜底
    private function testResolveCancelReasonText(): void
    {
        $method = (new ReflectionClass(OrderNotificationService::class))->getMethod('resolveCancelReasonText');
        $method->setAccessible(true);

        $this->assertSame('服务人员取消：档期冲突无法承接',
            $method->invoke(null, OrderLog::OPERATOR_STAFF, '档期冲突无法承接'),
            '有原因时必须拼接「服务人员取消：」前缀');
        $this->assertSame('服务人员取消订单',
            $method->invoke(null, OrderLog::OPERATOR_STAFF, ''),
            '空原因时必须回落到固定文案「服务人员取消订单」');
        $this->assertSame('服务人员取消订单',
            $method->invoke(null, OrderLog::OPERATOR_STAFF, '   '),
            '仅空白字符的原因必须视为空原因');
    }

    // 3) StaffCenterValidate 场景 orderCancel：id 正整数 + 去空格后 2~200 字原因
    private function testValidateOrderCancelScene(): void
    {
        $validate = new StaffCenterValidate();
        $scene = $validate->scene('orderCancel');
        $this->assertTrue($scene === $validate, 'scene orderCancel 必须存在并返回自身');

        $this->assertFalse($validate->check(['id' => 1, 'reason' => '']),
            '空原因必须被拒绝');
        $this->assertFalse($validate->check(['id' => 1, 'reason' => '拒']),
            '1 个字符的原因必须被拒绝');
        $this->assertFalse($validate->check(['id' => 1, 'reason' => '  拒  ']),
            '去空格后仅 1 个字符的原因必须被拒绝');
        $this->assertFalse($validate->check(['id' => 1, 'reason' => str_repeat('因', 201)]),
            '超过 200 个字符的原因必须被拒绝');
        $this->assertFalse($validate->check(['id' => 0, 'reason' => '档期冲突']),
            'id 非正整数必须被拒绝');
        $this->assertFalse($validate->check(['id' => 1]),
            '缺少 reason 必须被拒绝');

        $this->assertTrue($validate->check(['id' => 1, 'reason' => '档期冲突']),
            '正整数 id + 2 字原因必须通过校验');
        $this->assertTrue($validate->check(['id' => 12, 'reason' => '  档期冲突无法承接  ']),
            '去空格后 2~200 字原因必须通过校验');
        $this->assertTrue($validate->check(['id' => 12, 'reason' => str_repeat('因', 200)]),
            '恰好 200 个字符的原因必须通过校验');
    }

    // 4) applyStaffOrderAmounts 浮点边界：定金已付容差
    private function testApplyStaffOrderAmountsDepositTolerance(): void
    {
        $apply = $this->getApplyStaffOrderAmounts();

        // 4.1 已付 999.995，定金 1000.00：四舍五入/容差后必须判定定金已付
        $result = $apply->invoke(null, $this->buildOrderArray([
            'total_amount' => 1000.00,
            'pay_amount' => 1000.00,
            'deposit_amount' => 1000.00,
            'discount_amount' => 0,
            'paid_amount' => 999.995,
            'deposit_paid' => 1,
            'balance_paid' => 0,
        ]));
        $this->assertSame(1, (int)$result['deposit_paid'],
            '已付 999.995 与定金 1000.00 相差在 0.01 容差内，deposit_paid 必须为 1');
        $this->assertSame(Order::PAY_STATUS_PAID, (int)$result['pay_status'],
            '已付金额在 0.01 容差内视为已付清，pay_status 必须与 deposit_paid 一致');
        $this->assertFloatEquals(1000.00, (float)$result['pay_amount'], '服务人员应付金额必须为 1000.00');
        $this->assertFloatEquals(1000.00, (float)$result['deposit_amount'], '服务人员定金必须为 1000.00');
        $this->assertFloatEquals(0.00, (float)$result['balance_amount'], '定金全额时必须无尾款');

        // 4.2 已付 999.99：严格小于应付 1000.00，只能靠 0.01 容差通过
        $result = $apply->invoke(null, $this->buildOrderArray([
            'total_amount' => 1000.00,
            'pay_amount' => 1000.00,
            'deposit_amount' => 1000.00,
            'discount_amount' => 0,
            'paid_amount' => 999.99,
            'deposit_paid' => 1,
            'balance_paid' => 0,
        ]));
        $this->assertSame(1, (int)$result['deposit_paid'],
            '已付 999.99 与定金 1000.00 仅差 0.01，必须触发容差判定定金已付');
        $this->assertSame(Order::PAY_STATUS_PAID, (int)$result['pay_status'],
            '容差内必须判定 pay_status 为已支付');

        // 4.3 已付 999.98：超出 0.01 容差，不得判定定金已付
        $result = $apply->invoke(null, $this->buildOrderArray([
            'total_amount' => 1000.00,
            'pay_amount' => 1000.00,
            'deposit_amount' => 1000.00,
            'discount_amount' => 0,
            'paid_amount' => 999.98,
            'deposit_paid' => 1,
            'balance_paid' => 0,
        ]));
        $this->assertSame(0, (int)$result['deposit_paid'],
            '已付 999.98 超出 0.01 容差，deposit_paid 必须为 0');
        $this->assertTrue((int)$result['pay_status'] !== Order::PAY_STATUS_PAID,
            '超出容差时 pay_status 不得被判定为已支付');

        // 4.4 已付超过定金：deposit_paid 为 1，尾款沿用原单 balance_paid
        $result = $apply->invoke(null, $this->buildOrderArray([
            'total_amount' => 1000.00,
            'pay_amount' => 1000.00,
            'deposit_amount' => 1000.00,
            'discount_amount' => 0,
            'paid_amount' => 1000.00,
            'deposit_paid' => 1,
            'balance_paid' => 1,
        ]));
        $this->assertSame(1, (int)$result['deposit_paid'], '全额支付时 deposit_paid 必须为 1');
        $this->assertSame(1, (int)$result['balance_paid'], '全额支付时 balance_paid 必须为 1');
        $this->assertFloatEquals(0.00, (float)$result['unpaid_amount'], '全额支付时未付金额必须为 0');
    }

    // 5) applyStaffOrderAmounts 全款模式：无定金
    private function testApplyStaffOrderAmountsFullPayment(): void
    {
        $apply = $this->getApplyStaffOrderAmounts();

        $result = $apply->invoke(null, $this->buildOrderArray([
            'total_amount' => 5000.00,
            'pay_amount' => 5000.00,
            'deposit_amount' => 0,
            'discount_amount' => 0,
            'paid_amount' => 5000.00,
            'deposit_paid' => 0,
            'balance_paid' => 1,
        ]));
        $this->assertFloatEquals(0.00, (float)$result['deposit_amount'],
            '全款模式无定金时 deposit_amount 必须为 0');
        $this->assertFloatEquals(0.00, (float)$result['balance_amount'],
            '全款模式无定金时 balance_amount 必须为 0（尾款口径由详情层补全）');
        $this->assertSame(1, (int)$result['balance_paid'], '全款模式已付清时 balance_paid 必须为 1');
        $this->assertSame(Order::PAY_STATUS_PAID, (int)$result['pay_status'],
            '全款模式已付清时 pay_status 必须为已支付');
        $this->assertFloatEquals(0.00, (float)$result['unpaid_amount'], '全款模式已付清时未付金额必须为 0');

        $result = $apply->invoke(null, $this->buildOrderArray([
            'total_amount' => 5000.00,
            'pay_amount' => 5000.00,
            'deposit_amount' => 0,
            'discount_amount' => 0,
            'paid_amount' => 0,
            'deposit_paid' => 0,
            'balance_paid' => 1,
        ]));
        $this->assertSame(0, (int)$result['balance_paid'],
            '全款模式未付款时 balance_paid 必须为 0（不得继承原单标记）');
        $this->assertSame(Order::PAY_STATUS_UNPAID, (int)$result['pay_status'],
            '全款模式未付款时 pay_status 必须保持未支付');
        $this->assertFloatEquals(5000.00, (float)$result['unpaid_amount'], '全款模式未付款时未付金额必须为 5000.00');
    }

    // 6) resolveStaffCancelCapability 完整矩阵
    private function testResolveStaffCancelCapabilityMatrix(): void
    {
        // 6.1 整单 + 待确认 + 未付款 -> 拒绝接单
        $cap = StaffCenterLogic::resolveStaffCancelCapability(
            $this->makeOrder(['order_status' => Order::STATUS_PENDING_CONFIRM, 'paid_amount' => 0]),
            ['payments' => [], 'receipt_pending' => false],
            true
        );
        $this->assertSame(true, $cap['can_staff_cancel'], '整单待确认未付款必须允许取消');
        $this->assertSame('拒绝接单', $cap['staff_cancel_label'], '待确认状态的按钮文案必须为「拒绝接单」');
        $this->assertSame('', $cap['staff_cancel_disabled_reason'], '允许取消时不得给出禁用说明');

        // 6.2 整单 + 待支付 + 未付款 -> 取消订单
        $cap = StaffCenterLogic::resolveStaffCancelCapability(
            $this->makeOrder(['order_status' => Order::STATUS_PENDING_PAY, 'paid_amount' => 0]),
            ['payments' => [], 'receipt_pending' => false],
            true
        );
        $this->assertSame(true, $cap['can_staff_cancel'], '整单待支付未付款必须允许取消');
        $this->assertSame('取消订单', $cap['staff_cancel_label'], '待支付状态的按钮文案必须为「取消订单」');

        // 6.3 非整单归属 -> 禁止
        $cap = StaffCenterLogic::resolveStaffCancelCapability(
            $this->makeOrder(['order_status' => Order::STATUS_PENDING_PAY, 'paid_amount' => 0]),
            ['payments' => []],
            false
        );
        $this->assertSame(false, $cap['can_staff_cancel'], '非整单归属必须禁止服务人员取消');
        $this->assertTrue(trim((string)$cap['staff_cancel_disabled_reason']) !== '',
            '非整单归属必须给出禁用说明');

        // 6.4 已付款（金额） -> 禁止
        $cap = StaffCenterLogic::resolveStaffCancelCapability(
            $this->makeOrder(['order_status' => Order::STATUS_PENDING_PAY, 'paid_amount' => 0.02]),
            ['payments' => []],
            true
        );
        $this->assertSame(false, $cap['can_staff_cancel'], '已付款订单必须禁止服务人员取消');
        $this->assertTrue(trim((string)$cap['staff_cancel_disabled_reason']) !== '', '已付款必须给出禁用说明');

        // 6.5 已付款（支付流水） -> 禁止
        $cap = StaffCenterLogic::resolveStaffCancelCapability(
            $this->makeOrder(['order_status' => Order::STATUS_PENDING_PAY, 'paid_amount' => 0]),
            ['payments' => [['id' => 1, 'pay_amount' => 100]]],
            true
        );
        $this->assertSame(false, $cap['can_staff_cancel'], '存在支付流水时必须禁止取消');

        // 6.6 待审核线下凭证 -> 禁止
        $cap = StaffCenterLogic::resolveStaffCancelCapability(
            $this->makeOrder(['order_status' => Order::STATUS_PENDING_PAY, 'paid_amount' => 0]),
            ['payments' => [], 'receipt_pending' => true],
            true
        );
        $this->assertSame(false, $cap['can_staff_cancel'], '存在待审核线下凭证时必须禁止取消');
        $this->assertTrue(str_contains((string)$cap['staff_cancel_disabled_reason'], '凭证'),
            '待审核凭证的禁用说明必须提及凭证');

        // 6.7 已完成服务时间 -> 禁止
        $cap = StaffCenterLogic::resolveStaffCancelCapability(
            $this->makeOrder([
                'order_status' => Order::STATUS_PENDING_PAY,
                'paid_amount' => 0,
                'complete_time' => time(),
            ]),
            ['payments' => []],
            true
        );
        $this->assertSame(false, $cap['can_staff_cancel'], 'complete_time 已写入时必须禁止取消');

        // 6.8 非待确认/待付款状态 -> 禁止
        $statuses = [
            Order::STATUS_PENDING_SERVICE,
            Order::STATUS_IN_SERVICE,
            Order::STATUS_COMPLETED,
            Order::STATUS_CANCELLED,
        ];
        foreach ($statuses as $status) {
            $cap = StaffCenterLogic::resolveStaffCancelCapability(
                $this->makeOrder(['order_status' => $status, 'paid_amount' => 0]),
                ['payments' => []],
                true
            );
            $this->assertSame(false, $cap['can_staff_cancel'], '订单状态 ' . $status . ' 必须禁止服务人员取消');
            $this->assertTrue(trim((string)$cap['staff_cancel_disabled_reason']) !== '',
                '订单状态 ' . $status . ' 必须给出禁用说明');
        }

        // 6.9 已取消状态说明必须明确
        $cap = StaffCenterLogic::resolveStaffCancelCapability(
            $this->makeOrder(['order_status' => Order::STATUS_CANCELLED, 'paid_amount' => 0]),
            ['payments' => []],
            true
        );
        $this->assertTrue(str_contains((string)$cap['staff_cancel_disabled_reason'], '已取消'),
            '已取消状态的禁用说明必须说明已取消');
    }

    // 6b) resolveStaffOrderRole：主服务 / 协作 / 无角色
    private function testResolveStaffOrderRole(): void
    {
        $main = StaffCenterLogic::resolveStaffOrderRole([
            ['staff_id' => 7, 'item_type' => OrderItem::TYPE_SERVICE, 'item_status' => OrderItem::STATUS_PENDING],
        ], 7);
        $this->assertSame('main', $main, '存在未取消主服务项时必须判定为 main');

        $collaborator = StaffCenterLogic::resolveStaffOrderRole([
            ['staff_id' => 7, 'item_type' => OrderItem::TYPE_SERVICE, 'item_status' => OrderItem::STATUS_PENDING, 'x' => 1],
            ['staff_id' => 9, 'item_type' => OrderItem::TYPE_RELATED_STAFF, 'item_status' => OrderItem::STATUS_PENDING],
        ], 9);
        $this->assertSame('collaborator', $collaborator, '仅有未取消协作项时必须判定为 collaborator');

        $fallback = StaffCenterLogic::resolveStaffOrderRole([
            ['staff_id' => 9, 'item_type' => OrderItem::TYPE_RELATED_STAFF, 'item_status' => OrderItem::STATUS_CANCELLED],
        ], 9);
        $this->assertSame('collaborator', $fallback, '本人协作项全部取消时必须回退识别出 collaborator');

        $both = StaffCenterLogic::resolveStaffOrderRole([
            ['staff_id' => 7, 'item_type' => OrderItem::TYPE_RELATED_STAFF, 'item_status' => OrderItem::STATUS_PENDING],
            ['staff_id' => 7, 'item_type' => OrderItem::TYPE_SERVICE, 'item_status' => OrderItem::STATUS_PENDING],
        ], 7);
        $this->assertSame('main', $both, '同时存在主服务与协作项时必须优先判定为 main');

        $this->assertSame('', StaffCenterLogic::resolveStaffOrderRole([
            ['staff_id' => 7, 'item_type' => OrderItem::TYPE_SERVICE, 'item_status' => OrderItem::STATUS_PENDING],
        ], 8), '非本单服务人员必须返回空角色');
        $this->assertSame('', StaffCenterLogic::resolveStaffOrderRole([], 7), '空订单项必须返回空角色');
    }

    // 6c) resolveStaffCancelCapability 角色分流矩阵（含 4 参新签名与旧 3 参兼容）
    private function testResolveStaffCancelCapabilityStaffRole(): void
    {
        // 6c.1 main + 非整单归属：仍可整单取消，并提示协作服务同步取消
        $cap = StaffCenterLogic::resolveStaffCancelCapability(
            $this->makeOrder(['order_status' => Order::STATUS_PENDING_CONFIRM, 'paid_amount' => 0]),
            ['payments' => [], 'receipt_pending' => false, 'has_active_collaborator_item' => true],
            false,
            'main'
        );
        $this->assertSame(true, $cap['can_staff_cancel'], '主服务人员带协作人时必须允许取消');
        $this->assertSame('main', $cap['staff_role'], '主服务角色必须回传 main');
        $this->assertSame('whole', $cap['staff_cancel_scope'], '主服务取消范围必须为 whole');
        $this->assertSame('拒绝接单', $cap['staff_cancel_label'], '主服务待确认文案必须为「拒绝接单」');
        $this->assertTrue(str_contains((string)$cap['staff_cancel_notice'], '同步取消'),
            '主服务非整单归属时提示必须说明协作服务将同步取消');
        $this->assertTrue(str_contains((string)$cap['staff_cancel_notice'], '协作服务'),
            '主服务提示必须提及协作服务');

        // 6c.2 主服务 + 整单归属：不再提示同步取消
        $cap = StaffCenterLogic::resolveStaffCancelCapability(
            $this->makeOrder(['order_status' => Order::STATUS_PENDING_CONFIRM, 'paid_amount' => 0]),
            ['payments' => [], 'receipt_pending' => false, 'has_active_collaborator_item' => false],
            true,
            'main'
        );
        $this->assertSame(true, $cap['can_staff_cancel'], '整单归属主服务必须允许取消');
        $this->assertSame('', $cap['staff_cancel_notice'], '整单归属主服务取消提示必须为空');

        // 6c.3 协作人员 + 待确认：拒绝协作
        $cap = StaffCenterLogic::resolveStaffCancelCapability(
            $this->makeOrder(['order_status' => Order::STATUS_PENDING_CONFIRM, 'paid_amount' => 0]),
            ['payments' => [], 'receipt_pending' => false, 'has_active_collaborator_item' => true],
            false,
            'collaborator'
        );
        $this->assertSame(true, $cap['can_staff_cancel'], '协作人员待确认必须允许取消本人协作服务');
        $this->assertSame('collaborator', $cap['staff_role'], '协作角色必须回传 collaborator');
        $this->assertSame('拒绝协作', $cap['staff_cancel_label'], '协作人员待确认文案必须为「拒绝协作」');
        $this->assertSame('item', $cap['staff_cancel_scope'], '协作人员取消范围必须为 item');
        $this->assertTrue(str_contains((string)$cap['staff_cancel_notice'], '不影响主服务'),
            '协作人员提示必须说明不影响主服务');

        // 6c.4 协作人员 + 待支付未付款：取消协作
        $cap = StaffCenterLogic::resolveStaffCancelCapability(
            $this->makeOrder(['order_status' => Order::STATUS_PENDING_PAY, 'paid_amount' => 0]),
            ['payments' => [], 'receipt_pending' => false, 'has_active_collaborator_item' => true],
            false,
            'collaborator'
        );
        $this->assertSame(true, $cap['can_staff_cancel'], '协作人员待支付未付款必须允许取消本人协作服务');
        $this->assertSame('取消协作', $cap['staff_cancel_label'], '协作人员待支付文案必须为「取消协作」');
        $this->assertSame('item', $cap['staff_cancel_scope'], '协作人员待支付取消范围必须为 item');

        // 6c.5 本人协作项已取消：禁止重复操作
        $cap = StaffCenterLogic::resolveStaffCancelCapability(
            $this->makeOrder(['order_status' => Order::STATUS_PENDING_PAY, 'paid_amount' => 0]),
            ['payments' => [], 'receipt_pending' => false, 'has_active_collaborator_item' => false],
            false,
            'collaborator'
        );
        $this->assertSame(false, $cap['can_staff_cancel'], '本人协作项已取消时必须禁止重复操作');
        $this->assertSame('none', $cap['staff_cancel_scope'], '禁止取消时范围必须为 none');
        $this->assertTrue(str_contains((string)$cap['staff_cancel_disabled_reason'], '已取消'),
            '协作项已取消的禁用说明必须说明已取消');

        // 6c.6 协作人员 + 已付款：禁止
        $cap = StaffCenterLogic::resolveStaffCancelCapability(
            $this->makeOrder(['order_status' => Order::STATUS_PENDING_PAY, 'paid_amount' => 0.02]),
            ['payments' => [], 'has_active_collaborator_item' => true],
            false,
            'collaborator'
        );
        $this->assertSame(false, $cap['can_staff_cancel'], '协作人员遇已付款订单必须禁止取消');

        // 6c.7 旧 3 参兼容：staffRole=null + 非整单归属 -> 不可取消
        $capThreeArgs = StaffCenterLogic::resolveStaffCancelCapability(
            $this->makeOrder(['order_status' => Order::STATUS_PENDING_PAY, 'paid_amount' => 0]),
            ['payments' => []],
            false
        );
        $this->assertSame(false, $capThreeArgs['can_staff_cancel'], '旧 3 参非整单归属必须保持不可取消');
        $this->assertSame('none', $capThreeArgs['staff_role'], '旧 3 参非整单归属的角色必须为 none');
        $this->assertSame('none', $capThreeArgs['staff_cancel_scope'], '旧 3 参非整单归属的范围必须为 none');
        $this->assertSame('', $capThreeArgs['staff_cancel_notice'], '禁止取消时取消提示必须为空');

        // 6c.8 旧 3 参兼容：staffRole=null + 整单归属 -> 等同 main
        $capThreeArgsMain = StaffCenterLogic::resolveStaffCancelCapability(
            $this->makeOrder(['order_status' => Order::STATUS_PENDING_CONFIRM, 'paid_amount' => 0]),
            ['payments' => []],
            true
        );
        $this->assertSame(true, $capThreeArgsMain['can_staff_cancel'], '旧 3 参整单归属必须保持可取消');
        $this->assertSame('main', $capThreeArgsMain['staff_role'], '旧 3 参整单归属的角色必须为 main');
        $this->assertSame('whole', $capThreeArgsMain['staff_cancel_scope'], '旧 3 参整单归属的范围必须为 whole');

        // 6c.9 未知角色：不可取消
        $capUnknown = StaffCenterLogic::resolveStaffCancelCapability(
            $this->makeOrder(['order_status' => Order::STATUS_PENDING_CONFIRM, 'paid_amount' => 0]),
            ['payments' => []],
            true,
            ''
        );
        $this->assertSame(false, $capUnknown['can_staff_cancel'], '未知角色必须禁止取消');
        $this->assertTrue(str_contains((string)$capUnknown['staff_cancel_disabled_reason'], '不支持'),
            '未知角色的禁用说明必须说明暂不支持');
    }

    // 6d) 协作取消能力：方法存在性与签名契约
    private function testCollaboratorCancellationContracts(): void
    {
        $release = new ReflectionMethod(\app\common\model\package\PackageBooking::class, 'releaseByOrderItemId');
        $this->assertTrue($release->isStatic(), 'PackageBooking::releaseByOrderItemId 必须为静态方法');
        $this->assertSame(1, $release->getNumberOfParameters(), 'releaseByOrderItemId 必须只接收一个参数');
        $this->assertSame('orderItemId', $release->getParameters()[0]->getName(),
            'releaseByOrderItemId 参数名必须为 orderItemId');
        $this->assertSame('int', (string)$release->getParameters()[0]->getType(),
            'releaseByOrderItemId 参数必须为 int');
        $this->assertSame('int', (string)$release->getReturnType(), 'releaseByOrderItemId 必须返回 int');

        $cancel = new ReflectionMethod(Order::class, 'cancelCollaboratorItems');
        $this->assertTrue($cancel->isStatic(), 'Order::cancelCollaboratorItems 必须为静态方法');
        $cancelParams = array_map(static fn (\ReflectionParameter $param): string => $param->getName(), $cancel->getParameters());
        $this->assertSame(['orderId', 'staffId', 'operatorId', 'operatorType', 'reason', 'guardCallback'], $cancelParams,
            'cancelCollaboratorItems 参数顺序必须为 orderId/staffId/operatorId/operatorType/reason/guardCallback');
        $this->assertSame(3, $cancel->getNumberOfRequiredParameters(),
            'cancelCollaboratorItems 必须有 3 个必填参数');
        $this->assertTrue($cancel->getParameters()[3]->isDefaultValueAvailable(),
            'cancelCollaboratorItems 的 operatorType 必须有默认值');
        $this->assertSame('?callable', (string)$cancel->getParameters()[5]->getType(),
            'cancelCollaboratorItems 的 guardCallback 必须为 ?callable');
        $this->assertSame('array', (string)$cancel->getReturnType(), 'cancelCollaboratorItems 必须返回数组');

        $notifyUser = new ReflectionMethod(OrderNotificationService::class, 'notifyUserOnCollaboratorCancelled');
        $this->assertTrue($notifyUser->isStatic(), 'notifyUserOnCollaboratorCancelled 必须为静态方法');
        $notifyUserParams = array_map(static fn (\ReflectionParameter $param): string => $param->getName(), $notifyUser->getParameters());
        $this->assertSame(['orderId', 'cancelledItems', 'reason'], $notifyUserParams,
            'notifyUserOnCollaboratorCancelled 参数必须为 orderId/cancelledItems/reason');
        $this->assertSame(2, $notifyUser->getNumberOfRequiredParameters(),
            'notifyUserOnCollaboratorCancelled 必须有 2 个必填参数');
        $this->assertSame('void', (string)$notifyUser->getReturnType(),
            'notifyUserOnCollaboratorCancelled 必须返回 void');

        $notifyStaff = new ReflectionMethod(OrderNotificationService::class, 'notifyStaffOnCollaboratorReselected');
        $this->assertTrue($notifyStaff->isStatic(), 'notifyStaffOnCollaboratorReselected 必须为静态方法');
        $notifyStaffParams = array_map(static fn (\ReflectionParameter $param): string => $param->getName(), $notifyStaff->getParameters());
        $this->assertSame(['orderId', 'newOrderItemId'], $notifyStaffParams,
            'notifyStaffOnCollaboratorReselected 参数必须为 orderId/newOrderItemId');
        $this->assertSame(2, $notifyStaff->getNumberOfRequiredParameters(),
            'notifyStaffOnCollaboratorReselected 的 2 个参数都必须必填');
        $this->assertSame('void', (string)$notifyStaff->getReturnType(),
            'notifyStaffOnCollaboratorReselected 必须返回 void');

        $roleMethod = new ReflectionMethod(StaffCenterLogic::class, 'resolveStaffOrderRole');
        $this->assertSame(['allItems', 'staffId'], array_map(
            static fn (\ReflectionParameter $param): string => $param->getName(),
            $roleMethod->getParameters()
        ), 'resolveStaffOrderRole 参数必须为 allItems/staffId');
        $this->assertSame('string', (string)$roleMethod->getReturnType(),
            'resolveStaffOrderRole 必须返回 string');
    }

    // 7) 控制器契约：feature switch + orderCancel 场景 + Logic 调用
    private function testControllerOrderCancelContract(): void    {
        $source = $this->readSource('app/api/controller/StaffCenterController.php');
        $body = $this->extractFunctionBody($source, 'orderCancel');

        $this->assertContains('checkFeatureSwitch()', $body, 'orderCancel 必须校验服务人员中心功能开关');
        $this->assertContains("goCheck('orderCancel')", $body, 'orderCancel 必须使用 StaffCenterValidate 的 orderCancel 场景');
        $this->assertContains('new StaffCenterValidate()', $body, 'orderCancel 必须实例化 StaffCenterValidate');
        $this->assertContains('StaffCenterLogic::orderCancel(', $body, 'orderCancel 必须调用 StaffCenterLogic::orderCancel');
        $this->assertContains("'订单已取消'", $body, 'orderCancel 成功文案必须为「订单已取消」');
    }

    // 8) 详情契约：金额/支付/取消字段齐全且调用能力判定
    private function testOrderDetailContract(): void
    {
        $source = $this->readSource('app/api/logic/StaffCenterLogic.php');
        $body = $this->extractFunctionBody($source, 'orderDetail');

        $requiredKeys = [
            'is_whole_order',
            'deposit_paid_amount',
            'deposit_unpaid_amount',
            'balance_paid_amount',
            'balance_unpaid_amount',
            'payments',
            'cancel_reason',
            'cancel_time_text',
            'has_active_collaborator_item',
            'staff_role',
        ];
        foreach ($requiredKeys as $key) {
            $this->assertContains("['" . $key . "']", $body, 'orderDetail 必须下发字段 ' . $key);
        }
        $this->assertContains('array_merge($data, self::resolveStaffCancelCapability(', $body,
            'orderDetail 必须把取消能力合并进详情数据');
        $this->assertContains('resolveStaffCancelCapability(', $body,
            'orderDetail 必须调用 resolveStaffCancelCapability 生成取消能力');
        $this->assertContains('$staffRole', $body,
            'orderDetail 必须按订单项计算服务人员角色并传给取消能力判定');
        $this->assertContains('applyStaffOrderAmounts(', $body,
            'orderDetail 必须基于 applyStaffOrderAmounts 计算服务人员视角金额');

        $capabilityKeys = array_keys(StaffCenterLogic::resolveStaffCancelCapability(
            $this->makeOrder(['order_status' => Order::STATUS_PENDING_PAY, 'paid_amount' => 0]),
            ['payments' => []],
            true
        ));
        foreach (['can_staff_cancel', 'staff_cancel_label', 'staff_cancel_disabled_reason', 'staff_role', 'staff_cancel_scope', 'staff_cancel_notice'] as $key) {
            $this->assertTrue(in_array($key, $capabilityKeys, true),
                'resolveStaffCancelCapability 必须返回字段 ' . $key . ' 供 orderDetail 合并');
        }
    }

    // 9) isWholeOrderOwnedByStaff 整单取消时必须回退统计全部订单项
    private function testIsWholeOrderOwnedByStaffCancelledFallback(): void
    {
        $source = $this->readSource('app/common/model/order/Order.php');
        $body = $this->extractFunctionBody($source, 'isWholeOrderOwnedByStaff');

        $this->assertContains("where('item_status', '<>', OrderItem::STATUS_CANCELLED)", $body,
            'isWholeOrderOwnedByStaff 必须排除已取消订单项统计未取消项');
        $this->assertContains('if ($totalCount <= 0) {', $body,
            'isWholeOrderOwnedByStaff 必须显式处理无未取消项的场景');
        $this->assertContains('return $totalCount === $ownedCount;', $body,
            '存在未取消项时必须沿用 $totalCount === $ownedCount 判定');

        $fallbackStart = strpos($body, 'if ($totalCount <= 0) {');
        $ownedStart = strpos($body, '$ownedCount = OrderItem::where(');
        $this->assertTrue($fallbackStart !== false && $ownedStart !== false && $fallbackStart < $ownedStart,
            '整单取消回退逻辑必须位于 $totalCount <= 0 分支内，并先于 $ownedCount 统计');

        $fallback = substr($body, (int)$fallbackStart, (int)$ownedStart - (int)$fallbackStart);
        $this->assertContains("OrderItem::where('order_id', \$orderId)->count()", $fallback,
            '整单取消时必须回退统计该订单全部订单项（不按 item_status 过滤）');
        $this->assertContains("->where('staff_id', \$staffId)", $fallback,
            '回退统计必须按当前服务人员过滤归属');
        $this->assertContains('return $allCount === $allOwnedCount;', $fallback,
            '整单取消时必须以全部订单项归属结果判定整单归属');
    }

    // 10) applyStaffOrderAmounts 整单取消保留金额 / 部分取消只统计未取消项
    private function testApplyStaffOrderAmountsCancelledItems(): void
    {
        $apply = $this->getApplyStaffOrderAmounts();

        // 10.1 全部订单项已取消：服务金额、应付金额、定金与尾款不得归零
        $allCancelled = $apply->invoke(null, $this->buildOrderArray([
            'items' => [
                [
                    'id' => 1,
                    'item_type' => OrderItem::TYPE_SERVICE,
                    'item_status' => OrderItem::STATUS_CANCELLED,
                    'price' => 600.00,
                    'quantity' => 1,
                    'subtotal' => 600.00,
                    'addons' => [],
                ],
                [
                    'id' => 2,
                    'item_type' => OrderItem::TYPE_SERVICE,
                    'item_status' => OrderItem::STATUS_CANCELLED,
                    'price' => 400.00,
                    'quantity' => 1,
                    'subtotal' => 400.00,
                    'addons' => [],
                ],
            ],
            'total_amount' => 1000.00,
            'pay_amount' => 1000.00,
            'deposit_amount' => 400.00,
            'discount_amount' => 0,
            'paid_amount' => 400.00,
            'deposit_paid' => 1,
            'balance_paid' => 0,
        ]));
        $this->assertFloatEquals(1000.00, (float)$allCancelled['service_amount'],
            '整单取消时必须保留服务金额 1000.00，不得归零');
        $this->assertFloatEquals(1000.00, (float)$allCancelled['pay_amount'],
            '整单取消时必须保留应付金额 1000.00，不得归零');
        $this->assertFloatEquals(400.00, (float)$allCancelled['deposit_amount'],
            '整单取消时必须保留定金 400.00，不得归零');
        $this->assertFloatEquals(600.00, (float)$allCancelled['balance_amount'],
            '整单取消时必须保留尾款 600.00，不得归零');
        $this->assertSame(1, (int)$allCancelled['deposit_paid'],
            '整单取消时已付定金标记必须保留');

        // 10.2 部分取消：仅统计未取消项
        $partial = $apply->invoke(null, $this->buildOrderArray([
            'items' => [
                [
                    'id' => 1,
                    'item_type' => OrderItem::TYPE_SERVICE,
                    'item_status' => OrderItem::STATUS_CANCELLED,
                    'price' => 700.00,
                    'quantity' => 1,
                    'subtotal' => 700.00,
                    'addons' => [],
                ],
                [
                    'id' => 2,
                    'item_type' => OrderItem::TYPE_SERVICE,
                    'item_status' => OrderItem::STATUS_PENDING,
                    'price' => 300.00,
                    'quantity' => 1,
                    'subtotal' => 300.00,
                    'addons' => [],
                ],
            ],
            'total_amount' => 1000.00,
            'pay_amount' => 1000.00,
            'deposit_amount' => 400.00,
            'discount_amount' => 0,
            'paid_amount' => 0,
            'deposit_paid' => 0,
            'balance_paid' => 0,
        ]));
        $this->assertFloatEquals(300.00, (float)$partial['service_amount'],
            '存在未取消项时只能统计未取消项金额 300.00');
        $this->assertFloatEquals(300.00, (float)$partial['pay_amount'],
            '存在未取消项时应付金额必须按未取消项折算为 300.00');
        $this->assertFloatEquals(120.00, (float)$partial['deposit_amount'],
            '存在未取消项时定金必须按未取消项占比折算为 120.00');
        $this->assertFloatEquals(300.00, (float)$partial['unpaid_amount'],
            '存在未取消项时未付金额必须为未取消项应付 300.00');
    }

    // 11) extractPendingReselectItems：待重选提取 + 状态/角色排除矩阵
    private function testExtractPendingReselectItems(): void
    {
        $baseOrder = [
            'order_status' => Order::STATUS_PENDING_CONFIRM,
            'paid_amount' => 0,
            'complete_time' => 0,
            'receipt_pending' => false,
            'service_date' => '2026-12-01',
        ];

        $mainItem = [
            'id' => 1,
            'item_type' => OrderItem::TYPE_SERVICE,
            'item_status' => OrderItem::STATUS_PENDING,
            'staff_id' => 7,
            'service_date' => '2026-12-01',
        ];
        $pendingItem = [
            'id' => 2,
            'item_type' => OrderItem::TYPE_RELATED_STAFF,
            'item_status' => OrderItem::STATUS_CANCELLED,
            'staff_id' => 9,
            'staff_name' => '张管家',
            'service_date' => '2026-12-01',
            'item_meta' => [
                'role_key' => 'butler',
                'role_label' => '婚礼管家',
                'reselect_status' => 'pending',
                'reselect_role_key' => 'butler',
                'reselect_role_label' => '婚礼管家',
                'original_staff_name' => '张管家',
                'cancel_reason' => '档期冲突',
                'cancel_time' => 1700000000,
            ],
        ];
        $activeSameRoleItem = [
            'id' => 3,
            'item_type' => OrderItem::TYPE_RELATED_STAFF,
            'item_status' => OrderItem::STATUS_PENDING,
            'staff_id' => 11,
            'staff_name' => '李管家',
            'service_date' => '2026-12-01',
            'item_meta' => ['role_key' => 'butler', 'role_label' => '婚礼管家'],
        ];

        // 11.1 正常提取
        $result = OrderLogic::extractPendingReselectItems($baseOrder, [$mainItem, $pendingItem]);
        $this->assertSame(1, count($result), '待重选的协作项必须被提取');
        $item = $result[0];
        $this->assertSame(2, (int)$item['order_item_id'], 'order_item_id 必须为订单项主键');
        $this->assertSame('butler', (string)$item['role_key'], 'role_key 必须取重选角色键');
        $this->assertSame('婚礼管家', (string)$item['role_label'], 'role_label 必须回传角色中文名');
        $this->assertSame(9, (int)$item['original_staff_id'], 'original_staff_id 必须取订单项服务人员');
        $this->assertSame('张管家', (string)$item['original_staff_name'], 'original_staff_name 必须回传原服务人员姓名');
        $this->assertSame('档期冲突', (string)$item['cancel_reason'], 'cancel_reason 必须回传取消原因');
        $this->assertSame(1700000000, (int)$item['cancel_time'], 'cancel_time 必须回传取消时间戳');
        $this->assertSame(date('Y-m-d H:i:s', 1700000000), (string)$item['cancel_time_text'],
            'cancel_time_text 必须按时间戳格式化');
        $this->assertSame('2026-12-01', (string)$item['service_date'], 'service_date 必须回传服务日期');

        // 11.2 同角色已有生效协作项：不得重复提示
        $result = OrderLogic::extractPendingReselectItems($baseOrder, [$mainItem, $pendingItem, $activeSameRoleItem]);
        $this->assertSame(0, count($result), '同角色已有生效协作项时必须排除该待重选项');

        // 11.3 已付款：不提示
        $result = OrderLogic::extractPendingReselectItems(
            array_merge($baseOrder, ['paid_amount' => 100.00]),
            [$mainItem, $pendingItem]
        );
        $this->assertSame(0, count($result), '已付款订单不得输出待重选项');

        // 11.3b 0.01 容差内仍视为未付款
        $result = OrderLogic::extractPendingReselectItems(
            array_merge($baseOrder, ['paid_amount' => 0.01]),
            [$mainItem, $pendingItem]
        );
        $this->assertSame(1, count($result), '已付金额在 0.01 容差内必须仍视为未付款');

        // 11.4 已完成：不提示
        $result = OrderLogic::extractPendingReselectItems(
            array_merge($baseOrder, ['complete_time' => time()]),
            [$mainItem, $pendingItem]
        );
        $this->assertSame(0, count($result), '已完成订单不得输出待重选项');

        // 11.5 待审核凭证：不提示
        $result = OrderLogic::extractPendingReselectItems(
            array_merge($baseOrder, ['receipt_pending' => true]),
            [$mainItem, $pendingItem]
        );
        $this->assertSame(0, count($result), '存在待审核凭证时不得输出待重选项');

        // 11.6 非待确认/待付款状态：不提示
        foreach ([Order::STATUS_PAID, Order::STATUS_COMPLETED, Order::STATUS_CANCELLED] as $status) {
            $result = OrderLogic::extractPendingReselectItems(
                array_merge($baseOrder, ['order_status' => $status]),
                [$mainItem, $pendingItem]
            );
            $this->assertSame(0, count($result), '订单状态 ' . $status . ' 不得输出待重选项');
        }

        // 11.7 无生效主服务项：不提示
        $cancelledMain = array_merge($mainItem, ['item_status' => OrderItem::STATUS_CANCELLED]);
        $result = OrderLogic::extractPendingReselectItems($baseOrder, [$cancelledMain, $pendingItem]);
        $this->assertSame(0, count($result), '整单无生效主服务项时不得输出待重选项');

        // 11.8 非 pending 状态（已替换/已忽略）：不提示
        $replacedItem = $pendingItem;
        $replacedItem['item_meta']['reselect_status'] = 'replaced';
        $result = OrderLogic::extractPendingReselectItems($baseOrder, [$mainItem, $replacedItem]);
        $this->assertSame(0, count($result), '已替换的协作项不得再次输出');

        // 11.9 cancel_time 为 0 时文案必须为空
        $noTimeItem = $pendingItem;
        $noTimeItem['item_meta']['cancel_time'] = 0;
        $result = OrderLogic::extractPendingReselectItems($baseOrder, [$mainItem, $noTimeItem]);
        $this->assertSame(1, count($result), '取消时间缺失时仍应输出待重选项');
        $this->assertSame('', (string)$result[0]['cancel_time_text'], 'cancel_time 为 0 时文案必须为空');
    }

    // 12) OrderValidate 三个重选场景的校验规则
    private function testReselectValidateScenes(): void
    {
        foreach (['reselectCandidates', 'dismissReselect'] as $sceneName) {
            $validate = new OrderValidate();
            $this->assertTrue($validate->scene($sceneName) === $validate, '场景 ' . $sceneName . ' 必须存在并返回自身');
            $this->assertTrue($validate->check(['order_id' => 1, 'order_item_id' => 2]),
                '场景 ' . $sceneName . ' 正整数 order_id/order_item_id 必须通过');
            $this->assertFalse($validate->check(['order_id' => 1]),
                '场景 ' . $sceneName . ' 缺少 order_item_id 必须被拒绝');
            $this->assertFalse($validate->check(['order_item_id' => 2]),
                '场景 ' . $sceneName . ' 缺少 order_id 必须被拒绝');
            $this->assertFalse($validate->check(['order_id' => 0, 'order_item_id' => 2]),
                '场景 ' . $sceneName . ' order_id 为 0 必须被拒绝');
            $this->assertFalse($validate->check(['order_id' => 1, 'order_item_id' => 0]),
                '场景 ' . $sceneName . ' order_item_id 为 0 必须被拒绝');
            $this->assertFalse($validate->check(['order_id' => 'abc', 'order_item_id' => 2]),
                '场景 ' . $sceneName . ' 非整数 order_id 必须被拒绝');
        }

        $validate = new OrderValidate();
        $this->assertTrue($validate->scene('reselectCollaborator') === $validate,
            '场景 reselectCollaborator 必须存在并返回自身');
        $this->assertFalse($validate->check(['order_id' => 1, 'order_item_id' => 2]),
            'reselectCollaborator 缺少 staff_id/package_id 必须被拒绝');
        $this->assertFalse($validate->check(['order_id' => 1, 'order_item_id' => 2, 'staff_id' => 3]),
            'reselectCollaborator 缺少 package_id 必须被拒绝');
        $this->assertTrue($validate->check([
            'order_id' => 1,
            'order_item_id' => 2,
            'staff_id' => 3,
            'package_id' => 4,
        ]), 'reselectCollaborator 四个正整数参数必须通过');
        $this->assertFalse($validate->check([
            'order_id' => 1,
            'order_item_id' => 2,
            'staff_id' => 0,
            'package_id' => 4,
        ]), 'reselectCollaborator 的 staff_id 为 0 必须被拒绝');
    }

    // 13) OrderController 三个重选端点
    private function testReselectControllerContract(): void
    {
        foreach (['reselectCandidates', 'reselectCollaborator', 'dismissReselect'] as $method) {
            $this->assertTrue(method_exists(OrderController::class, $method),
                'OrderController::' . $method . ' 必须存在');
        }

        $source = $this->readSource('app/api/controller/OrderController.php');
        $candidatesBody = $this->extractFunctionBody($source, 'reselectCandidates');
        $this->assertContains("goCheck('reselectCandidates')", $candidatesBody,
            'reselectCandidates 必须使用 reselectCandidates 场景');
        $this->assertContains('OrderLogic::getReselectCandidates(', $candidatesBody,
            'reselectCandidates 必须调用 OrderLogic::getReselectCandidates');

        $collaboratorBody = $this->extractFunctionBody($source, 'reselectCollaborator');
        $this->assertContains("goCheck('reselectCollaborator')", $collaboratorBody,
            'reselectCollaborator 必须使用 reselectCollaborator 场景');
        $this->assertContains('OrderLogic::reselectCollaborator(', $collaboratorBody,
            'reselectCollaborator 必须调用 OrderLogic::reselectCollaborator');
        $this->assertContains("'已重新挑选协作人员，等待服务人员确认'", $collaboratorBody,
            'reselectCollaborator 成功文案必须明确等待服务人员确认');

        $dismissBody = $this->extractFunctionBody($source, 'dismissReselect');
        $this->assertContains("goCheck('dismissReselect')", $dismissBody,
            'dismissReselect 必须使用 dismissReselect 场景');
        $this->assertContains('OrderLogic::dismissReselect(', $dismissBody,
            'dismissReselect 必须调用 OrderLogic::dismissReselect');
        $this->assertContains("'已关闭重新挑选提醒'", $dismissBody,
            'dismissReselect 成功文案必须为已关闭提醒');
    }

    // 14) V2Logic::order 聚合待重选项与动作位
    private function testV2OrderPendingReselectContract(): void
    {
        $source = $this->readSource('app/api/logic/V2Logic.php');
        $body = $this->extractFunctionBody($source, 'order');

        $this->assertContains("'pending_reselect_items' =>", $body,
            'V2Logic::order 必须下发 pending_reselect_items');
        $this->assertContains("'reselect_collaborator' =>", $body,
            'V2Logic::order 动作矩阵必须包含 reselect_collaborator');
        $this->assertContains("!empty(\$pendingReselectItems)", $body,
            'reselect_collaborator 必须由待重选项是否为空决定');
    }

    private function getApplyStaffOrderAmounts(): ReflectionMethod
    {
        $method = (new ReflectionClass(StaffCenterLogic::class))->getMethod('applyStaffOrderAmounts');
        $method->setAccessible(true);
        return $method;
    }

    /**
     * 构造最小订单数组：单个服务项，单价与 subtotal 跟随 total_amount。
     */
    private function buildOrderArray(array $overrides): array
    {
        $totalAmount = (float)($overrides['total_amount'] ?? 1000.00);

        return array_merge([
            'id' => 10001,
            'items' => [
                [
                    'id' => 1,
                    'item_type' => 1,
                    'item_status' => 1,
                    'price' => $totalAmount,
                    'quantity' => 1,
                    'subtotal' => $totalAmount,
                    'addons' => [],
                ],
            ],
            'total_amount' => 1000.00,
            'pay_amount' => 1000.00,
            'deposit_amount' => 0,
            'discount_amount' => 0,
            'paid_amount' => 0,
            'deposit_paid' => 0,
            'balance_paid' => 0,
            'pay_status' => Order::PAY_STATUS_UNPAID,
        ], $overrides);
    }

    private function makeOrder(array $attributes): Order
    {
        return new Order($attributes);
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

    private function assertSame($expected, $actual, string $message): void
    {
        $this->assertions++;
        if ($expected !== $actual) {
            throw new RuntimeException($message . '，期望：' . var_export($expected, true) . '，实际：' . var_export($actual, true));
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

    private function assertFloatEquals(float $expected, float $actual, string $message): void
    {
        $this->assertions++;
        if (abs($expected - $actual) > 0.0001) {
            throw new RuntimeException($message . '，期望：' . $expected . '，实际：' . $actual);
        }
    }
}

try {
    (new StaffOrderDetailCancelTest())->run();
} catch (Throwable $e) {
    fwrite(STDERR, 'FAIL - ' . $e->getMessage() . "\n");
    exit(1);
}
