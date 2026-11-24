<?php
// +----------------------------------------------------------------------
// | v2 聚合接口契约回归测试
// +----------------------------------------------------------------------
// | 说明：当前项目未配置 PHPUnit，本脚本通过反射 + 源码契约扫描验证：
// |   1. 五个 v2 聚合端点存在且鉴权标记正确
// |   2. V2Logic 只做编排、复用 v1 Logic，不重写业务规则
// |   3. 订单动作矩阵覆盖到 Order 的全部状态常量（漏一个就等于按钮永不显示）
// | 可直接执行：php tests/regression/V2AggregateTest.php
// +----------------------------------------------------------------------

declare(strict_types=1);

final class V2AggregateTest
{
    private int $assertions = 0;
    private string $root;

    /** 期望的 v2 端点：[action => 是否需要登录] */
    private const EXPECTED_ACTIONS = [
        'home' => false,
        'staff' => false,
        'me' => true,
        'order' => true,
        'bookingPreview' => true,
    ];

    public function __construct()
    {
        $this->root = dirname(__DIR__, 2);
        require_once $this->root . '/vendor/autoload.php';
    }

    public function run(): void
    {
        $this->testControllerActionsExist();
        $this->testNotNeedLoginMatchesExpectation();
        $this->testLogicReusesV1Logic();
        $this->testStaffTodoSummary();
        $this->testOrderActionsCoverAllStatuses();
        $this->testEndpointContractContainsV2();

        echo 'OK - ' . $this->assertions . " assertions\n";
    }

    private function testControllerActionsExist(): void
    {
        $class = 'app\api\controller\V2Controller';
        $this->assert(class_exists($class), 'V2Controller 必须存在');

        foreach (array_keys(self::EXPECTED_ACTIONS) as $action) {
            $this->assert(method_exists($class, $action), "V2Controller::{$action} 必须存在");
        }
    }

    private function testNotNeedLoginMatchesExpectation(): void
    {
        $source = $this->read('app/api/controller/V2Controller.php');

        foreach (self::EXPECTED_ACTIONS as $action => $needLogin) {
            $declared = $this->isDeclaredNotNeedLogin($source, $action);
            if ($needLogin) {
                $this->assert(!$declared, "V2Controller::{$action} 必须登录，不应出现在 notNeedLogin");
            } else {
                $this->assert($declared, "V2Controller::{$action} 必须免登录");
            }
        }
    }

    /**
     * 架构约束：V2Logic 只能编排，不能重写业务规则。
     * 一旦有人在 V2Logic 里直接写 SQL 算价格/状态，两套口径就会漂移。
     */
    private function testLogicReusesV1Logic(): void
    {
        $source = $this->read('app/api/logic/V2Logic.php');

        $required = [
            'IndexLogic::getIndexData',
            'IndexLogic::getConfigData',
            'StaffLogic::recommend',
            'StaffLogic::detail',
            'StaffLogic::works',
            'NotificationLogic::unreadCount',
            'UserLogic::center',
            'UserLogic::weddingDate',
            'OrderLogic::getOrderDetail',
            'OrderLogic::getUserOrderStatistics',
            'OrderLogic::previewOrder',
            'ReviewLogic::staffReviews',
            'ReviewLogic::staffStats',
            'ReviewLogic::pendingOrders',
            'OrderChange::checkCanChange',
            'StaffCenterLogic::getTodoSummary',
        ];

        foreach ($required as $call) {
            $this->assert(
                str_contains($source, $call),
                "V2Logic 必须复用 {$call}，不得重写该业务规则"
            );
        }

        // 禁止在聚合层直接写查询构造（Order::where / Db::table 等）
        $this->assert(
            !preg_match('/\bDb::(table|name|query)\s*\(/', $source),
            'V2Logic 不得直接拼 SQL，必须走既有 Logic / Model'
        );
    }

    /**
     * 服务人员待办：v2/me 只做聚合，计数口径必须由 StaffCenterLogic 提供。
     */
    private function testStaffTodoSummary(): void
    {
        $source = $this->read('app/api/logic/V2Logic.php');

        $this->assert(
            (bool)preg_match("/'staff_todo'\s*=>/", $source),
            "V2Logic::me() 必须返回 'staff_todo' 键"
        );
        $this->assert(
            str_contains($source, 'StaffCenterLogic::getTodoSummary($userId)'),
            'V2Logic::me() 必须复用 StaffCenterLogic::getTodoSummary($userId)'
        );

        $class = 'app\api\logic\StaffCenterLogic';
        $this->assert(class_exists($class), 'StaffCenterLogic 必须存在');
        $this->assert(method_exists($class, 'getTodoSummary'), 'StaffCenterLogic::getTodoSummary 必须存在');

        $method = new ReflectionMethod($class, 'getTodoSummary');
        $this->assert($method->isPublic(), 'StaffCenterLogic::getTodoSummary 必须为 public');
        $this->assert($method->isStatic(), 'StaffCenterLogic::getTodoSummary 必须为 static');

        $params = $method->getParameters();
        $this->assert(count($params) === 1, 'StaffCenterLogic::getTodoSummary 必须只接收 1 个参数');
        $this->assert(
            (string)$params[0]->getType() === 'int',
            'StaffCenterLogic::getTodoSummary 参数必须为 int'
        );
        $this->assert(
            (string)$method->getReturnType() === 'array',
            'StaffCenterLogic::getTodoSummary 返回类型必须为 array'
        );
    }

    /**
     * 动作矩阵漏状态 = 按钮永不出现，属于静默故障，必须逐状态覆盖。
     */
    private function testOrderActionsCoverAllStatuses(): void
    {
        $source = $this->read('app/api/logic/V2Logic.php');

        $statuses = [
            'STATUS_PENDING_CONFIRM',
            'STATUS_PENDING_PAY',
            'STATUS_IN_SERVICE',
            'STATUS_COMPLETED',
            'STATUS_REVIEWED',
            'STATUS_CANCELLED',
            'STATUS_REFUNDED',
        ];

        foreach ($statuses as $status) {
            $this->assert(
                str_contains($source, 'Order::' . $status),
                "动作矩阵必须显式引用 Order::{$status}"
            );
        }

        foreach (['pay', 'cancel', 'confirm_complete', 'apply_refund', 'delete', 'change_date', 'pause', 'review'] as $key) {
            $this->assert(
                str_contains($source, "'{$key}' =>"),
                "动作矩阵缺少 {$key} 键"
            );
        }
    }

    private function testEndpointContractContainsV2(): void
    {
        $file = dirname($this->root, 1) . '/shared/contracts/generated/endpoints.json';
        $this->assert(is_file($file), '端点契约 endpoints.json 必须已生成');

        $json = json_decode((string)file_get_contents($file), true);
        $endpoints = $json['endpoints'] ?? [];
        $this->assert(!empty($endpoints), '端点契约不得为空');

        $byPath = [];
        foreach ($endpoints as $endpoint) {
            $byPath[$endpoint['path']] = $endpoint;
        }

        foreach (self::EXPECTED_ACTIONS as $action => $needLogin) {
            $path = '/api/v2/' . $action;
            $this->assert(isset($byPath[$path]), "端点契约缺少 {$path}");
            if (isset($byPath[$path])) {
                $this->assert(
                    $byPath[$path]['auth'] === $needLogin,
                    "{$path} 的 auth 应为 " . ($needLogin ? 'true' : 'false')
                );
            }
        }
    }

    // ---------- 工具 ----------

    private function isDeclaredNotNeedLogin(string $source, string $action): bool
    {
        if (!preg_match('/\$notNeedLogin\s*=\s*\[([^\]]*)\]/', $source, $m)) {
            return false;
        }
        return (bool)preg_match("/['\"]" . preg_quote($action, '/') . "['\"]/", $m[1]);
    }

    private function read(string $relative): string
    {
        $path = $this->root . '/' . ltrim($relative, '/');
        if (!is_file($path)) {
            throw new RuntimeException('文件不存在：' . $relative);
        }
        return (string)file_get_contents($path);
    }

    private function assert(bool $condition, string $message): void
    {
        $this->assertions++;
        if (!$condition) {
            fwrite(STDERR, "FAIL - {$message}\n");
            exit(1);
        }
    }
}

(new V2AggregateTest())->run();
