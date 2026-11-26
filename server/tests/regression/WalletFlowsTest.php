<?php
// +----------------------------------------------------------------------
// | 我的钱包（对账只读）回归测试
// +----------------------------------------------------------------------
// | 说明：当前项目未配置 PHPUnit，本脚本沿用 V2AggregateTest 的独立 CLI 模式：
// |   1. 两个钱包端点存在且强制登录
// |   2. 只读边界：不得出现充值/提现/余额写动作
// |   3. 对账覆盖 4 张 C 端实付/实退表，支付方式文案必须走各自模型
// |   4. 端点契约与装修页面白名单已同步
// |   5. computeSummary 使用整数分计算，退款中不计入已退款
// |   6. auto_timestamp 的 datetime 字符串必须统一解析为秒级时间戳
// | 可直接执行：php tests/regression/WalletFlowsTest.php
// +----------------------------------------------------------------------

declare(strict_types=1);

final class WalletFlowsTest
{
    private int $assertions = 0;
    private string $root;

    private const CONTROLLER = 'app\api\controller\WalletController';
    private const LOGIC = 'app\api\logic\WalletLogic';

    private const WALLET_PAGE = '/packages/pkg-user/pages/wallet/wallet';

    public function __construct()
    {
        $this->root = dirname(__DIR__, 2);
        require_once $this->root . '/vendor/autoload.php';
    }

    public function run(): void
    {
        $this->testActionsExistAndRequireLogin();
        $this->testReadOnlyBoundary();
        $this->testReconciliationSources();
        $this->testEndpointContract();
        $this->testDecoratePageName();
        $this->testComputeSummaryMixedFlows();
        $this->testComputeSummaryMonthFilter();
        $this->testTimestampParsing();

        echo 'OK - ' . $this->assertions . " assertions\n";
    }

    /**
     * 钱包是私有资产页，两个接口都必须登录。
     */
    private function testActionsExistAndRequireLogin(): void
    {
        $this->assert(class_exists(self::CONTROLLER), 'WalletController 必须存在');
        foreach (['flows', 'detail'] as $action) {
            $this->assert(
                method_exists(self::CONTROLLER, $action),
                "WalletController::{$action} 必须存在"
            );
        }

        $source = $this->read('app/api/controller/WalletController.php');
        foreach (['flows', 'detail'] as $action) {
            $this->assert(
                !$this->isDeclaredNotNeedLogin($source, $action),
                "WalletController::{$action} 必须登录，不应出现在 notNeedLogin"
            );
        }
    }

    /**
     * 钱包只做对账查询：任何充值/提现/余额写动作都不允许出现。
     */
    private function testReadOnlyBoundary(): void
    {
        foreach (['app/api/controller/WalletController.php', 'app/api/logic/WalletLogic.php'] as $file) {
            $source = $this->read($file);

            foreach (['recharge', 'withdraw', 'user_money', 'user_balance', 'bind_card', 'transfer'] as $keyword) {
                $this->assert(
                    !preg_match('/' . preg_quote($keyword, '/') . '/i', $source),
                    "{$file} 不得出现充值/提现/余额写入口：{$keyword}"
                );
            }

            foreach (['充值', '提现', '账户余额', '转账', '绑卡'] as $keyword) {
                $this->assert(
                    !str_contains($source, $keyword),
                    "{$file} 不得出现充值/提现/余额写入口：{$keyword}"
                );
            }

            foreach (['->save(', '->update(', '->insert(', '->delete(', '->destroy(', 'Db::startTrans', '::create(', '::update('] as $writeCall) {
                $this->assert(
                    !str_contains($source, $writeCall),
                    "{$file} 只能只读查询，不得出现写操作：{$writeCall}"
                );
            }
        }
    }

    /**
     * 对账口径：必须聚合 4 张实付/实退表，按 user_id 过滤，支付方式文案各走各的模型。
     */
    private function testReconciliationSources(): void
    {
        $source = $this->read('app/api/logic/WalletLogic.php');

        $requiredImports = [
            'app\common\model\order\Payment',
            'app\common\model\order\Refund',
            'app\common\model\order\RefundItem',
            'app\common\model\dynamic\ActivityPayment',
            'app\common\model\dynamic\ActivityRefund',
            'app\common\model\dynamic\ActivityRegistration',
            'app\common\model\dynamic\Dynamic',
            'app\common\service\FileService',
            'app\common\service\MoneyService',
        ];
        foreach ($requiredImports as $import) {
            $this->assert(
                str_contains($source, 'use ' . $import . ';'),
                "WalletLogic 必须引入 {$import}"
            );
        }

        $this->assert(
            substr_count($source, "where('user_id'") >= 4,
            'WalletLogic 的 4 类流水查询必须全部按 user_id 过滤'
        );
        $this->assert(
            str_contains($source, 'Payment::getPayWayText'),
            '订单支付/退款必须调用 Payment::getPayWayText'
        );
        $this->assert(
            str_contains($source, 'ActivityPayment::getPayWayText'),
            '活动支付/退款必须调用 ActivityPayment::getPayWayText'
        );
        $this->assert(
            str_contains($source, 'MoneyService::yuanToFen'),
            'computeSummary 必须使用 MoneyService::yuanToFen 做整数分累加'
        );

        foreach ([
            "'biz_sn' =>",
            "'order_id' =>",
            "'order_sn' =>",
            "'registration_id' =>",
            "'dynamic_id' =>",
            "'remark' =>",
            "'event_time_text' =>",
            "'collection_owner' =>",
            "'collection_owner_label' =>",
        ] as $field) {
            $this->assert(
                str_contains($source, $field),
                "WalletLogic 流水条目必须返回字段 {$field}"
            );
        }

        $this->assert(
            !preg_match('/\(int\)\s*\$[A-Za-z_][A-Za-z0-9_]*->(create_time|update_time)/', $source),
            'WalletLogic 不得对 ->create_time / ->update_time 直接做 (int) 强转，必须走 getModelTimestamp'
        );

        foreach ([
            "getModelTimestamp(\$payment, 'pay_time')",
            "getModelTimestamp(\$refund, 'refund_time')",
            "getModelTimestamp(\$refundItem, 'refund_time')",
            "getModelTimestamp(\$dynamic, 'activity_start_time')",
        ] as $call) {
            $this->assert(
                str_contains($source, $call),
                "WalletLogic 时间字段必须走 {$call}"
            );
        }

        $this->assert(class_exists(self::LOGIC), 'WalletLogic 必须存在');
        foreach (['flows', 'detail', 'computeSummary', 'parseTimestamp', 'getModelTimestamp'] as $method) {
            $this->assert(
                method_exists(self::LOGIC, $method),
                "WalletLogic::{$method} 必须存在"
            );
        }

        foreach (['parseTimestamp', 'getModelTimestamp'] as $method) {
            $timestampMethod = new ReflectionMethod(self::LOGIC, $method);
            $this->assert($timestampMethod->isPublic(), "WalletLogic::{$method} 必须为 public");
            $this->assert($timestampMethod->isStatic(), "WalletLogic::{$method} 必须为 static");
        }
        $timestampMethod = new ReflectionMethod(self::LOGIC, 'getModelTimestamp');
        $this->assert(
            (string)$timestampMethod->getParameters()[0]->getType() === 'object',
            'getModelTimestamp 第一个参数必须为 object'
        );

        $summaryMethod = new ReflectionMethod(self::LOGIC, 'computeSummary');
        $this->assert($summaryMethod->isPublic(), 'WalletLogic::computeSummary 必须为 public');
        $this->assert($summaryMethod->isStatic(), 'WalletLogic::computeSummary 必须为 static');
        $params = $summaryMethod->getParameters();
        $this->assert(count($params) === 2, 'WalletLogic::computeSummary 必须接收 2 个参数');
        $this->assert((string)$params[0]->getType() === 'array', 'computeSummary 第一个参数必须为 array');
        $this->assert((string)$params[1]->getType() === 'string', 'computeSummary 第二个参数必须为 string');
        $this->assert((string)$summaryMethod->getReturnType() === 'array', 'computeSummary 返回类型必须为 array');
    }

    private function testEndpointContract(): void
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

        foreach (['/api/wallet/flows', '/api/wallet/detail'] as $path) {
            $this->assert(isset($byPath[$path]), "端点契约缺少 {$path}");
            if (isset($byPath[$path])) {
                $this->assert($byPath[$path]['auth'] === true, "{$path} 的 auth 必须为 true");
            }
        }
    }

    private function testDecoratePageName(): void
    {
        $class = 'app\common\service\DecorateLinkService';
        $this->assert(class_exists($class), 'DecorateLinkService 必须存在');

        $pageNames = (new ReflectionClass($class))->getConstant('PAGE_NAMES');
        $this->assert(is_array($pageNames), 'DecorateLinkService::PAGE_NAMES 必须为数组');
        $this->assert(
            array_key_exists(self::WALLET_PAGE, $pageNames),
            'DecorateLinkService::PAGE_NAMES 必须包含 ' . self::WALLET_PAGE
        );
    }

    /**
     * 混合场景：0.10 + 0.20 订单支出 + 100.00 活动支出
     * + 30.10 已完成退款 + 15.00 退款中。
     */
    private function testComputeSummaryMixedFlows(): void
    {
        $flows = [
            $this->flow('2026-11', 'out', 'completed', 0.10),
            $this->flow('2026-11', 'out', 'completed', 0.20),
            $this->flow('2026-11', 'out', 'completed', 100.00),
            $this->flow('2026-11', 'in', 'completed', 30.10),
            $this->flow('2026-11', 'in', 'processing', 15.00),
        ];

        $summary = call_user_func([self::LOGIC, 'computeSummary'], $flows);

        $this->assert($summary['total_paid'] === 100.30, 'total_paid 必须为 100.30（整数分累加）');
        $this->assert($summary['total_refunded'] === 30.10, 'total_refunded 必须为 30.10');
        $this->assert($summary['net_expense'] === 70.20, 'net_expense 必须为 70.20');
        $this->assert($summary['refunding_amount'] === 15.00, 'refunding_amount 必须为 15.00');
        $this->assert($summary['refunding_count'] === 1, 'refunding_count 必须为 1');
        $this->assert($summary['paid_count'] === 3, 'paid_count 必须为 3');
        $this->assert($summary['refunded_count'] === 1, 'refunded_count 必须为 1');
        $this->assert($summary['total_count'] === 5, 'total_count 必须为 5');
    }

    /**
     * 跨月退款：当月没有支出、只有退款时，对账净支出必须为负数。
     */
    private function testComputeSummaryMonthFilter(): void
    {
        $flows = [
            $this->flow('2026-07', 'out', 'completed', 100.00),
            $this->flow('2026-08', 'in', 'completed', 30.10),
        ];

        $summary = call_user_func([self::LOGIC, 'computeSummary'], $flows, '2026-08');

        $this->assert($summary['month'] === '2026-08', 'summary.month 必须回显筛选月份');
        $this->assert($summary['total_paid'] === 0.0, '跨月场景当月 total_paid 必须为 0');
        $this->assert($summary['total_refunded'] === 30.10, '跨月场景当月 total_refunded 必须为 30.10');
        $this->assert($summary['net_expense'] === -30.10, '跨月场景 net_expense 必须为准确的负数 -30.10');
        $this->assert($summary['refunding_amount'] === 0.0, '跨月场景 refunding_amount 必须为 0');
        $this->assert($summary['paid_count'] === 0, '跨月场景 paid_count 必须为 0');
        $this->assert($summary['refunded_count'] === 1, '跨月场景 refunded_count 必须为 1');
        $this->assert($summary['total_count'] === 1, '跨月场景 total_count 必须为 1');
    }

    // ---------- 工具 ----------

    /**
     * auto_timestamp 会把 create_time / update_time 读成 datetime 字符串，
     * 直接 (int) 强转会被截断成 2026；这里验证统一解析为秒级时间戳。
     */
    private function testTimestampParsing(): void
    {
        $logic = self::LOGIC;
        $dateTime = '2026-11-25 14:30:00';
        $expected = strtotime($dateTime);
        $this->assert(is_int($expected), '测试基准时间必须可解析');

        $this->assert(
            $logic::parseTimestamp($dateTime) === $expected,
            'parseTimestamp 必须把 datetime 字符串解析为对应 Unix 秒'
        );
        $this->assert(
            $logic::parseTimestamp((string)$expected) === $expected,
            'parseTimestamp 必须支持纯数字时间戳字符串'
        );
        $this->assert(
            $logic::parseTimestamp($expected) === $expected,
            'parseTimestamp 必须原样返回整数时间戳'
        );
        $this->assert($logic::parseTimestamp('') === 0, 'parseTimestamp 空值必须返回 0');

        $model = new class($expected) {
            private int $timestamp;

            public function __construct(int $timestamp)
            {
                $this->timestamp = $timestamp;
            }

            public function getData(string $field)
            {
                return $field === 'create_time' ? '2026-11-25 14:30:00' : $this->timestamp;
            }
        };

        $this->assert(
            $logic::getModelTimestamp($model, 'create_time') === $expected,
            'getModelTimestamp 必须通过 getData 读取 datetime 字符串并解析'
        );
        $this->assert(
            $logic::getModelTimestamp($model, 'update_time') === $expected,
            'getModelTimestamp 必须正确处理整数时间戳'
        );
        $this->assert(
            $logic::getModelTimestamp($model, 'refund_time') === $expected,
            'getModelTimestamp 必须统一返回秒级时间戳'
        );
    }

    private function flow(string $month, string $direction, string $status, float $amount): array
    {
        return [
            'month' => $month,
            'direction' => $direction,
            'status' => $status,
            'amount' => $amount,
        ];
    }

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

(new WalletFlowsTest())->run();
