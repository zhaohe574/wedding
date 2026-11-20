<?php
// +----------------------------------------------------------------------
// | 服务人员详情页「当前日期有效订单」回归测试
// +----------------------------------------------------------------------
// | 规则：当前用户 + 当前服务人员 + 当前服务日期，状态为
// | 待确认/待支付/待服务/服务中/已暂停/退款中时，详情页主按钮显示「查看订单」。
// | 执行：php tests/regression/StaffActiveOrderTest.php
// +----------------------------------------------------------------------

declare(strict_types=1);

use app\common\model\order\Order;
use app\common\service\StaffActiveOrderService;

require __DIR__ . '/../../vendor/autoload.php';

final class StaffActiveOrderTest
{
    private int $assertions = 0;

    public function run(): void
    {
        $this->testStatusScope();
        $this->testDateNormalization();
        $this->testBackendEntrypoints();
        $this->testFrontendEntrypoints();

        echo 'OK - ' . $this->assertions . " assertions\n";
    }

    private function testStatusScope(): void
    {
        foreach ([
            Order::STATUS_PENDING_CONFIRM,
            Order::STATUS_PENDING_PAY,
            Order::STATUS_PENDING_SERVICE,
            Order::STATUS_IN_SERVICE,
            Order::STATUS_PAUSED,
            Order::STATUS_REFUNDING,
        ] as $status) {
            $this->assertTrue(
                StaffActiveOrderService::isEffectiveStatus($status),
                '未完成/未关闭状态必须算有效订单：' . $status
            );
        }

        foreach ([
            Order::STATUS_COMPLETED,
            Order::STATUS_REVIEWED,
            Order::STATUS_CANCELLED,
            Order::STATUS_REFUNDED,
            Order::STATUS_USER_DELETED,
        ] as $status) {
            $this->assertFalse(
                StaffActiveOrderService::isEffectiveStatus($status),
                '已完成/已关闭状态不得算有效订单：' . $status
            );
        }
    }

    private function testDateNormalization(): void
    {
        $today = date('Y-m-d');
        $this->assertSame($today, StaffActiveOrderService::normalizeDate(''), '空日期必须回落为今天');
        $this->assertSame($today, StaffActiveOrderService::normalizeDate('not-a-date'), '非法日期必须回落为今天');
        $this->assertSame('2026-12-01', StaffActiveOrderService::normalizeDate('2026-12-01'), '合法日期必须原样保留');
        $this->assertSame('2026-12-01', StaffActiveOrderService::normalizeDate('2026-12-01 18:00:00'), '带时间日期必须截断到自然日');
    }

    private function testBackendEntrypoints(): void
    {
        $staffLogic = $this->readSource('app/api/logic/StaffLogic.php');
        $this->assertContains("'my_order'", $staffLogic, 'v1 详情必须返回 my_order');
        $this->assertContains('StaffActiveOrderService::findForUser', $staffLogic, 'v1 详情必须调用有效订单服务');

        $v2 = $this->readSource('app/api/logic/V2Logic.php');
        $this->assertContains("'my_order' => \$detail['my_order']", $v2, 'v2 聚合必须透传 my_order');

        $controller = $this->readSource('app/api/controller/StaffController.php');
        $this->assertContains('public function activeOrder()', $controller, '必须提供 activeOrder 轻量刷新接口');
        $this->assertNotContains("'activeOrder'", $controller, 'activeOrder 必须要求登录，不能放进 notNeedLogin');
        $this->assertContains('StaffActiveOrderService::findForUser', $controller, 'activeOrder 必须复用统一服务');
    }

    private function testFrontendEntrypoints(): void
    {
        $page = $this->readSource('../newuniapp/src/packages/pkg-catalog/pages/staff-detail/staff-detail.vue');
        $this->assertContains('hasActiveOrder', $page, '详情页必须根据有效订单切换按钮');
        $this->assertContains('查看订单', $page, '有有效订单时主按钮必须显示「查看订单」');
        $this->assertContains('viewMyOrder', $page, '「查看订单」必须有跳转处理');
        $this->assertContains('/packages/pkg-order/pages/detail/detail', $page, '必须优先跳转订单详情');
        $this->assertContains('/pages/order/order', $page, '缺少订单号时必须回订单列表');
        $this->assertContains('refreshActiveOrder', $page, '必须提供轻量刷新');
        $this->assertContains('onShow', $page, '返回详情页时必须刷新有效订单');
        $this->assertContains('该日已有你的预约', $page, '有有效订单时必须给出明确提示');

        $api = $this->readSource('../newuniapp/src/api/catalog.ts');
        $this->assertContains('getStaffActiveOrder', $api, '前端必须封装 activeOrder 接口');
        $this->assertContains("'/staff/activeOrder'", $api, 'activeOrder 接口路径必须稳定');
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
}

try {
    (new StaffActiveOrderTest())->run();
} catch (Throwable $e) {
    fwrite(STDERR, 'FAIL - ' . $e->getMessage() . "\n");
    exit(1);
}
