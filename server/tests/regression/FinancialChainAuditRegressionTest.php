<?php
// +----------------------------------------------------------------------
// | 财务链路审计回归测试（Batch 2）
// +----------------------------------------------------------------------
// | 说明：与 PaymentScheduleReliabilityTest 同风格，轻量断言 + 纯函数执行 + 源码扫描。
// | 只做内存计算与源码检查，不连接 3306 线上数据库执行写操作。
// | 可直接执行：php tests/regression/FinancialChainAuditRegressionTest.php
// +----------------------------------------------------------------------

declare(strict_types=1);

use app\common\model\financial\StaffSettlementTransfer;
use app\common\service\StaffSettlementService;

require __DIR__ . '/../../vendor/autoload.php';

$app = new \think\App(dirname(__DIR__, 2));
$app->initialize();

final class FinancialChainAuditRegressionTest
{
    private int $assertions = 0;

    public function run(): void
    {
        $this->testSettlementListsCountUsesAlias();
        $this->testBuildTransferSummaryPureBehaviour();
        $this->testStaffSettlementServiceGuards();
        $this->testSettlementBatchSemantics();
        $this->testSettlementLogicStatisticsAndBatch();
        $this->testMonthlyFeeReleaseAndZeroAmountSettle();
        $this->testWechatNotifyUrlFallbacks();
        $this->testFinancialReconciliationBatchCreate();
        $this->testFinancialReportLogicFallbacksAndClosedLoop();
        $this->testCrossModuleAuditChain();

        echo 'OK - ' . $this->assertions . " assertions\n";
    }

    // 1) StaffSettlementLists::count() 必须带 alias('settlement')，否则搜索时报 Unknown column
    private function testSettlementListsCountUsesAlias(): void
    {
        $source = $this->readSource('app/adminapi/lists/financial/StaffSettlementLists.php');
        $countBody = $this->extractFunctionBody($source, 'count');
        $this->assertContains('StaffSettlement::alias(\'settlement\')->where($this->searchWhere)', $countBody,
            'count() 必须与 lists() 一样使用 alias(settlement)，否则订单号/人员名搜索触发 Unknown column');

        $listsBody = $this->extractFunctionBody($source, 'lists');
        $this->assertContains('alias(\'settlement\')', $listsBody, 'lists() 必须保持 alias(settlement)');

        // 转账摘要必须委托统一服务，避免列表口径与详情口径分叉
        $summaryBody = $this->extractFunctionBody($source, 'buildTransferSummary');
        $this->assertContains('StaffSettlementService::buildTransferSummary($transfers)', $summaryBody,
            '列表转账摘要必须委托 StaffSettlementService::buildTransferSummary');
    }

    // 2) 直接运行 StaffSettlementService::buildTransferSummary 验证纯函数行为
    private function testBuildTransferSummaryPureBehaviour(): void
    {
        $svc = StaffSettlementService::class;
        $build = static fn(array $transfers) => $svc::buildTransferSummary($transfers);

        // 空列表
        $summary = $build([]);
        $this->assertSame(0, $summary['count'], '空转账列表 count 必须为 0');
        $this->assertSame('', $summary['status_text'], '空转账列表状态文案必须为空');

        // 单条失败
        $summary = $build([
            $this->transfer(1, StaffSettlementTransfer::STATUS_FAILED, [
                'out_bill_no' => 'OB1', 'fail_reason' => '余额不足',
            ]),
        ]);
        $this->assertSame(1, $summary['count'], '单条失败 count 必须为 1');
        $this->assertSame(0, $summary['success_count'], '单条失败 success_count 必须为 0');
        $this->assertSame(0, $summary['wait_confirm_count'], '单条失败 wait_confirm_count 必须为 0');
        $this->assertSame('转账失败', $summary['status_text'], '单条失败状态文案必须为转账失败');
        $this->assertSame('OB1', $summary['out_bill_no'], '单条失败必须回显商户单号');
        $this->assertSame('', $summary['transfer_bill_no'], '失败单无微信单号');
        $this->assertSame('余额不足', $summary['fail_reason'], '单条失败必须回显失败原因');

        // 先失败后待确认：以最新一条为准，失败原因不得透传
        $summary = $build([
            $this->transfer(1, StaffSettlementTransfer::STATUS_FAILED, ['out_bill_no' => 'OB1', 'fail_reason' => '余额不足']),
            $this->transfer(2, StaffSettlementTransfer::STATUS_WAIT_USER_CONFIRM, [
                'out_bill_no' => 'OB2', 'transfer_bill_no' => 'TB2', 'package_info' => 'PKG2',
            ]),
        ]);
        $this->assertSame(0, $summary['success_count'], '先失败后待确认 success_count 必须为 0');
        $this->assertSame(1, $summary['wait_confirm_count'], '先失败后待确认 wait_confirm_count 必须为 1');
        $this->assertSame('待确认收款', $summary['status_text'], '先失败后待确认必须展示最新待确认状态');
        $this->assertSame('OB2', $summary['out_bill_no'], '摘要商户单号必须取最新一条');
        $this->assertSame('TB2', $summary['transfer_bill_no'], '摘要微信单号必须取最新一条');
        $this->assertSame('PKG2', $summary['package_info'], '摘要 package_info 必须取最新一条以便确认收款');
        $this->assertSame('', $summary['fail_reason'], '最新待确认时不得透传旧失败原因');

        // 先失败后成功：以成功单为准
        $summary = $build([
            $this->transfer(1, StaffSettlementTransfer::STATUS_FAILED, ['out_bill_no' => 'OB1', 'fail_reason' => '余额不足']),
            $this->transfer(2, StaffSettlementTransfer::STATUS_SUCCESS, [
                'out_bill_no' => 'OB2', 'transfer_bill_no' => 'TB2', 'package_info' => 'PKG2',
            ]),
        ]);
        $this->assertSame(1, $summary['success_count'], '先失败后成功 success_count 必须为 1');
        $this->assertSame('已到账', $summary['status_text'], '存在成功单时状态必须为已到账');
        $this->assertSame('OB2', $summary['out_bill_no'], '成功单商户单号必须胜出');
        $this->assertSame('TB2', $summary['transfer_bill_no'], '成功单微信单号必须胜出');
        $this->assertSame('', $summary['fail_reason'], '已到账时不得透传旧失败原因');

        // 先关闭后重试成功：以重试成功的新单为准
        $summary = $build([
            $this->transfer(1, StaffSettlementTransfer::STATUS_CLOSED, ['out_bill_no' => 'OB1', 'fail_reason' => '已关闭']),
            $this->transfer(2, StaffSettlementTransfer::STATUS_SUCCESS, ['out_bill_no' => 'OB2', 'transfer_bill_no' => 'TB2']),
        ]);
        $this->assertSame(1, $summary['success_count'], '先关闭后重试成功 success_count 必须为 1');
        $this->assertSame('已到账', $summary['status_text'], '先关闭后重试成功必须展示已到账');
        $this->assertSame('OB2', $summary['out_bill_no'], '重试成功单商户单号必须胜出');
        $this->assertSame('TB2', $summary['transfer_bill_no'], '重试成功单微信单号必须胜出');
        $this->assertSame('', $summary['fail_reason'], '重试成功后不得透传旧关闭原因');

        // 全部成功
        $summary = $build([
            $this->transfer(1, StaffSettlementTransfer::STATUS_SUCCESS, ['out_bill_no' => 'OB1', 'transfer_bill_no' => 'TB1']),
            $this->transfer(2, StaffSettlementTransfer::STATUS_SUCCESS, ['out_bill_no' => 'OB2', 'transfer_bill_no' => 'TB2']),
        ]);
        $this->assertSame(2, $summary['success_count'], '全部成功 success_count 必须为 2');
        $this->assertSame('全部已到账', $summary['status_text'], '全部成功必须展示全部已到账');

        // SettlementLogic / StaffSettlementLists 的列表与详情摘要口径必须一致
        $this->assertTrue(
            $build([
                $this->transfer(1, StaffSettlementTransfer::STATUS_FAILED, ['out_bill_no' => 'OB1', 'fail_reason' => 'X']),
            ]) === $svc::buildTransferSummary([
                $this->transfer(1, StaffSettlementTransfer::STATUS_FAILED, ['out_bill_no' => 'OB1', 'fail_reason' => 'X']),
            ]),
            '同一入参两次计算结果必须一致'
        );
    }

    // 3) StaffSettlementService 关键防护必须齐全
    private function testStaffSettlementServiceGuards(): void
    {
        $this->assertTrue(method_exists(StaffSettlementService::class, 'isOrderRefundBlocked'),
            '必须提供 isOrderRefundBlocked 静态判断');
        $this->assertTrue(defined(StaffSettlementService::class . '::REFUND_PAYOUT_BLOCKED_MESSAGE'),
            '必须提供 REFUND_PAYOUT_BLOCKED_MESSAGE 稳定提示');

        $source = $this->readSource('app/common/service/StaffSettlementService.php');

        // 事务外发起微信转账：行锁占位 -> HTTP 出事务 -> 事务+行锁回写
        $sendBody = $this->extractFunctionBody($source, 'sendSettlementTransfer');
        $this->assertContains('行锁占位：先在事务内锁单并推进状态，微信 HTTP 请求严禁放进事务', $sendBody,
            '发起转账必须先在事务内行锁占位');
        $this->assertContains('微信 HTTP 请求在事务外执行', $sendBody, '微信 HTTP 请求必须在事务外执行');
        $prepLockPos = strpos($sendBody, 'StaffSettlement::where(\'id\', (int)$settlement->id)->lock(true)->find()');
        $httpPos = strpos($sendBody, 'createTransferBill($transfer)');
        $this->assertTrue($prepLockPos !== false && $httpPos !== false && $prepLockPos < $httpPos,
            '行锁占位必须发生在事务外微信 HTTP 请求之前');
        $this->assertContains('isOrderRefundBlocked((int)$locked->order_id)', $sendBody,
            '行锁占位事务内必须再次校验退款阻断');

        // 退款前检查：已补交金额与补交记录均阻断
        $guardBody = $this->extractFunctionBody($source, 'guardRefundForOrder');
        $this->assertContains('staff_due_collected_amount', $guardBody, '退款检查必须阻断已补交平台抽成的结算');
        $this->assertContains('StaffSettlementRepay::where(\'settlement_id\'', $guardBody,
            '退款检查必须阻断存在补交记录的结算');
        $this->assertContains('cancelForRefund', $guardBody, '可退款结算必须在退款事务内自动取消');

        // ensureTransferRow：按最新状态复用或新建转账单
        $ensureBody = $this->extractFunctionBody($source, 'ensureTransferRow');
        $this->assertContains('order(\'id\', \'desc\')', $ensureBody, '转账明细必须按最新在前查询');
        $this->assertContains('(int)$latest->status === StaffSettlementTransfer::STATUS_PENDING', $ensureBody,
            '最新待发起单必须允许沿用');
        $this->assertContains('StaffSettlementTransfer::createTransfer', $ensureBody,
            '无可用明细时必须新建转账单');

        // refreshSettlementFromTransfers：事务 + 行锁刷新
        $refreshBody = $this->extractFunctionBody($source, 'refreshSettlementFromTransfers');
        $this->assertContains('Db::transaction', $refreshBody, '结算刷新必须包事务');
        $this->assertContains('StaffSettlement::where(\'id\', (int)$settlement->id)->lock(true)->find()', $refreshBody,
            '结算刷新必须行锁后重读最新状态');
    }

    // 4) SettlementBatch 状态语义
    private function testSettlementBatchSemantics(): void
    {
        $source = $this->readSource('app/common/model/financial/SettlementBatch.php');

        $this->assertTrue(substr_count($source, '(int)$this->status') >= 4,
            '批次状态比较必须统一强转 (int)$this->status');

        $cancelBody = $this->extractFunctionBody($source, 'cancel');
        $this->assertContains('update([\'batch_id\' => 0', $cancelBody,
            '取消批次必须解绑结算的 batch_id => 0');
        $this->assertNotContains('\'status\' => self::STATUS_CANCELLED', $cancelBody,
            '取消批次不得把结算状态改成 STATUS_CANCELLED，只解绑批次');

        $executeBody = $this->extractFunctionBody($source, 'execute');
        $this->assertContains('StaffSettlementService::isOrderRefundBlocked((int)$settlement->order_id)', $executeBody,
            '批次执行必须跳过退款中/已退款订单');
        $this->assertContains('isNoPayout()', $executeBody, '批次执行必须跳过无需打款记录');
        $this->assertTrue(method_exists(\app\common\model\financial\SettlementBatch::class, 'updateStats'),
            '批次必须提供 updateStats 统计刷新');
    }

    // 5) SettlementLogic 统计排除已取消、空日期回退、批次抢占防护
    private function testSettlementLogicStatisticsAndBatch(): void
    {
        $source = $this->readSource('app/adminapi/logic/financial/SettlementLogic.php');

        $statisticsBody = $this->extractFunctionBody($source, 'statistics');
        $this->assertContains('trim((string)($params[\'start_date\'] ?? \'\')) ?: date', $statisticsBody,
            'statistics 空日期必须回退默认值，不得把空串传入 whereBetween');
        $this->assertContains('where(\'status\', \'<>\', StaffSettlement::STATUS_CANCELLED)', $statisticsBody,
            'statistics 必须排除已取消结算');
        foreach (['pending_amount', 'settled_amount', 'pending_count', 'settled_count',
                     'transfer_processing_count', 'transfer_processing_amount', 'no_payout_count',
                     'platform_commission_amount', 'platform_paid_share_amount',
                     'staff_due_platform_amount', 'staff_due_collected_amount',
                     'staff_due_left_amount', 'total_amount', 'total_count', 'failed_count', 'failed_amount'] as $key) {
            $this->assertContains("'" . $key . "'", $statisticsBody, 'statistics 必须保留返回键 ' . $key);
        }

        $staffSummaryBody = $this->extractFunctionBody($source, 'staffSummary');
        $this->assertContains('trim((string)($params[\'start_date\'] ?? \'\')) ?: date', $staffSummaryBody,
            'staffSummary 空日期必须回退默认值');
        $this->assertContains('where(\'s.status\', \'<>\', StaffSettlement::STATUS_CANCELLED)', $staffSummaryBody,
            'staffSummary 必须排除已取消结算');

        $generateBody = $this->extractFunctionBody($source, 'generate');
        $this->assertContains('trim((string)($params[\'start_date\'] ?? \'\')) ?: date', $generateBody,
            'generate 空日期必须回退默认值');

        $createBatchBody = $this->extractFunctionBody($source, 'createBatch');
        $this->assertContains('结算结束日期不能早于开始日期', $createBatchBody,
            'createBatch 必须校验结束日期不早于开始日期');
        $this->assertContains('where(\'batch_id\', 0)', $createBatchBody,
            'createBatch 查询必须过滤 batch_id = 0 防止跨批次抢占');
        $this->assertContains('lock(true)', $createBatchBody, 'createBatch 必须行锁待结算记录');
        $emptyMsgPos = strpos($createBatchBody, '没有找到待结算的记录');
        $rollbackPos = strpos($createBatchBody, 'Db::rollback();');
        $this->assertTrue($rollbackPos !== false && $emptyMsgPos !== false && $rollbackPos < $emptyMsgPos,
            '空结果必须先 Db::rollback() 再 setError，避免事务悬挂');
        $this->assertContains('whereIn(\'id\', $settlementIds)', $createBatchBody,
            'createBatch 必须按锁定到的 settlementIds 精确绑定批次');

        // settle / batchSettle / cancel 防护
        $settleBody = $this->extractFunctionBody($source, 'settle');
        $this->assertContains('StaffSettlementService::isOrderRefundBlocked((int)$settlement->order_id)', $settleBody,
            '单笔结算发起前必须校验退款阻断');
        $this->assertContains('StaffSettlementService::REFUND_PAYOUT_BLOCKED_MESSAGE', $settleBody,
            '单笔结算必须返回稳定阻断提示');
        $batchSettleBody = $this->extractFunctionBody($source, 'batchSettle');
        $this->assertContains('!StaffSettlementService::isOrderRefundBlocked((int)$settlement->order_id)', $batchSettleBody,
            '批量结算必须跳过退款阻断记录');
        $this->assertContains('catch (\Throwable $e)', $batchSettleBody, '批量结算必须捕获 Throwable');
        $cancelBody = $this->extractFunctionBody($source, 'cancel');
        $this->assertContains('Db::transaction', $cancelBody, '取消结算必须包事务');
        $this->assertContains('StaffSettlement::where(\'id\', $id)->lock(true)->find()', $cancelBody,
            '取消结算必须行锁后重读');
        $this->assertContains('当前结算状态不可取消', $cancelBody, '取消结算失败必须返回稳定提示');
        $this->assertContains('$batch->updateStats()', $cancelBody, '取消结算后必须刷新批次统计');
    }

    // 6) 包月释放与 0 元结算、退款阻断契约
    private function testMonthlyFeeReleaseAndZeroAmountSettle(): void
    {
        $this->assertTrue(method_exists(\app\common\model\financial\StaffCompanyFeeUsage::class, 'releaseMonthlyFee'),
            '必须提供 StaffCompanyFeeUsage::releaseMonthlyFee');

        $model = $this->readSource('app/common/model/financial/StaffSettlement.php');

        $cancelBody = $this->extractFunctionBody($model, 'cancel');
        $this->assertContains('StaffCompanyFeeUsage::releaseMonthlyFee', $cancelBody,
            '取消结算必须释放包月扣款');
        $this->assertContains('(int)$this->batch_id > 0', $cancelBody, '取消结算必须与批次解绑');

        $cancelForRefundBody = $this->extractFunctionBody($model, 'cancelForRefund');
        $this->assertContains('StaffCompanyFeeUsage::releaseMonthlyFee', $cancelForRefundBody,
            '退款取消结算必须释放包月扣款');

        $settleBody = $this->extractFunctionBody($model, 'settle');
        $this->assertContains('StaffSettlementService::isOrderRefundBlocked((int)$this->order_id)', $settleBody,
            '模型层 settle 也必须阻断退款订单');
        $this->assertContains('round((float)$this->actual_amount, 2) > 0', $settleBody,
            '0 元结算不得写资金流水');
        $this->assertContains('FinancialFlow::createUniqueFlow', $settleBody, '正额结算必须落唯一流水');
    }

    // 7) 回调地址 CLI 回退与合法 URL 校验
    private function testWechatNotifyUrlFallbacks(): void
    {
        $configBody = $this->extractFunctionBody(
            $this->readSource('app/common/service/wechat/WeChatConfigService.php'), 'buildApiNotifyUrl');
        $this->assertContains('config(\'app.app_host\'', $configBody, 'API 回调地址必须回退全局应用域名');
        $this->assertContains('preg_match(\'#^https?://[^/]+#i\', $domain)', $configBody,
            'API 回调地址必须校验合法 URL');
        $this->assertContains('return \'\';', $configBody, '无法解析合法域名时必须返回空串');

        $transferBody = $this->extractFunctionBody(
            $this->readSource('app/common/service/WeChatMerchantTransferService.php'), 'buildNotifyUrl');
        $this->assertContains('config(\'app.app_host\'', $transferBody, '商家转账回调地址必须回退全局应用域名');
        $this->assertContains('preg_match(\'#^https?://[^/]+#i\', $domain)', $transferBody,
            '商家转账回调地址必须校验合法 URL');
        $this->assertContains('return \'\';', $transferBody, '无法解析合法域名时必须返回空串');

        $paySource = $this->readSource('app/common/service/pay/WeChatPayService.php');
        $refundBody = $this->extractFunctionBody($paySource, 'refund');
        $this->assertContains('preg_match(\'#^https?://[^/]+#i\', $notifyUrl) === 1', $refundBody,
            '退款回调地址必须校验合法 URL，CLI 下不得透传非法值');
        $this->assertContains('$payload[\'notify_url\'] = $notifyUrl;', $refundBody,
            '仅合法回调地址才写入退款请求');
    }

    // 8) FinancialReconciliation::batchCreate 必须定义 $channels
    private function testFinancialReconciliationBatchCreate(): void
    {
        $source = $this->readSource('app/common/model/financial/FinancialReconciliation.php');
        $body = $this->extractFunctionBody($source, 'batchCreate');
        $this->assertContains('$channels = $channel > 0 ? [$channel] : [self::CHANNEL_WECHAT];', $body,
            'batchCreate 必须定义 $channels，修复未定义变量');
        $defPos = strpos($body, '$channels =');
        $usePos = strpos($body, 'foreach ($channels as $ch)');
        $this->assertTrue($defPos !== false && $usePos !== false && $defPos < $usePos,
            '$channels 必须在 foreach 使用前定义');

        $statsBody = $this->extractFunctionBody($source, 'getSystemStats');
        $this->assertContains('whereIn(\'pay_status\', [Payment::STATUS_PAID, Payment::STATUS_REFUNDED])', $statsBody,
            '对账系统口径必须包含已退款流水');
        $this->assertContains('where(\'collection_owner\', Payment::COLLECTION_PLATFORM)', $statsBody,
            '对账系统口径必须只统计平台收款');

        $this->assertTrue(substr_count($source, '(int)$this->status') >= 2,
            'startReconcile/handleDiff 状态比较必须强转 (int)$this->status');
    }

    // 9) FinancialReportLogic 空日期回退与资金闭环指标
    private function testFinancialReportLogicFallbacksAndClosedLoop(): void
    {
        $source = $this->readSource('app/adminapi/logic/financial/FinancialReportLogic.php');

        foreach (['overview', 'incomeStats', 'payWayAnalysis', 'refundStats'] as $method) {
            $body = $this->extractFunctionBody($source, $method);
            $this->assertContains('trim((string)($params[\'start_date\'] ?? \'\')) ?: date', $body,
                $method . ' 空开始日期必须回退默认值');
            $this->assertContains('trim((string)($params[\'end_date\'] ?? \'\')) ?: date', $body,
                $method . ' 空结束日期必须回退默认值');
        }
        $trendBody = $this->extractFunctionBody($source, 'incomeTrend');
        $this->assertContains('trim((string)($params[\'start_date\'] ?? \'\')) ?: date', $trendBody,
            'incomeTrend 空开始日期必须回退默认值');
        $this->assertContains('trim((string)($params[\'end_date\'] ?? \'\')) ?: date', $trendBody,
            'incomeTrend 空结束日期必须回退默认值');

        $overviewBody = $this->extractFunctionBody($source, 'overview');
        $this->assertContains('结束日期不能早于开始日期', $overviewBody, 'overview 必须拒绝倒置日期区间');
        $this->assertContains('max(1, (int)round((strtotime($endDate) - strtotime($startDate)) / 86400) + 1)', $overviewBody,
            'overview 天数必须至少为 1');
        $this->assertContains('FinancialFlow::BIZ_TYPE_STAFF_SETTLE', $overviewBody,
            'overview 必须统计人员结算支出');
        $this->assertContains('FinancialFlow::BIZ_TYPE_PLATFORM_FEE', $overviewBody,
            'overview 必须统计平台抽成补交收入');
        $this->assertContains('FinancialFlow::BIZ_TYPE_PLATFORM_FEE_REFUND', $overviewBody,
            'overview 必须扣减抽成补偿退款');
        $this->assertContains("'total_settlement'", $overviewBody, 'overview 必须返回 total_settlement');
        $this->assertContains("'total_platform_fee_repay'", $overviewBody, 'overview 必须返回 total_platform_fee_repay');
        $this->assertContains("'platform_net_retained'", $overviewBody, 'overview 必须返回 platform_net_retained');
        $this->assertContains('round($netIncome - $totalSettlement + $totalPlatformFeeRepay, 2)', $overviewBody,
            'platform_net_retained 必须按闭环公式计算');

        $refundStatsBody = $this->extractFunctionBody($source, 'refundStats');
        $this->assertContains("'failed' =>", $refundStatsBody, '退款统计必须包含 failed 分组');
        $this->assertContains("Refund::STATUS_FAILED", $refundStatsBody, 'failed 分组必须使用 Refund::STATUS_FAILED 常量');
    }

    // 10) 跨模块审计链路
    private function testCrossModuleAuditChain(): void
    {
        // OrderReceiptService::audit 支持 collectionOwner
        $audit = new ReflectionMethod(\app\common\service\OrderReceiptService::class, 'audit');
        $paramNames = array_map(static fn($p) => $p->getName(), $audit->getParameters());
        $this->assertInArray('collectionOwner', $paramNames, '收款审核必须支持指定收款归属');
        $ownerParam = $audit->getParameters()[array_search('collectionOwner', $paramNames, true)];
        $this->assertTrue($ownerParam->isOptional(), 'collectionOwner 必须可选，兼容旧调用');

        // OrderController::auditVoucher 透传 collection_owner
        $auditVoucherBody = $this->extractFunctionBody(
            $this->readSource('app/adminapi/controller/order/OrderController.php'), 'auditVoucher');
        $this->assertContains('collection_owner', $auditVoucherBody, '凭证审核必须读取 collection_owner');
        $this->assertContains('OrderReceiptService::audit((int)$params[\'id\'], $receiptId,', $auditVoucherBody,
            '凭证审核必须走 OrderReceiptService::audit 并透传归属');

        // OrderRefundService::confirmOfflineRefund 锁序与结算防护
        $refundSource = $this->readSource('app/common/service/OrderRefundService.php');
        $confirmBody = $this->extractFunctionBody($refundSource, 'confirmOfflineRefund');
        $orderLockPos = strpos($confirmBody, 'Order::where(\'id\', (int)$refund->order_id)->lock(true)->find()');
        $refundLockPos = strpos($confirmBody, 'Refund::where(\'id\', (int)$refund->id)->lock(true)->find()');
        $this->assertTrue($orderLockPos !== false && $refundLockPos !== false && $orderLockPos < $refundLockPos,
            '线下退款确认必须先锁订单再锁退款，防死锁');
        $this->assertContains('StaffSettlementService::guardRefundForOrder', $confirmBody,
            '线下退款确认必须做结算退款防护');

        // RefundController::retry 派发通知
        $retryBody = $this->extractFunctionBody(
            $this->readSource('app/adminapi/controller/order/RefundController.php'), 'retry');
        $this->assertContains('dispatchRefundNotifications', $retryBody, '重试退款后必须派发通知');

        // StaffCenterLogic::formatSettlementItem 使用统一摘要
        $formatBody = $this->extractFunctionBody(
            $this->readSource('app/api/logic/StaffCenterLogic.php'), 'formatSettlementItem');
        $this->assertContains('StaffSettlementService::buildTransferSummary($transfers)', $formatBody,
            '人员端结算列表必须与后台共用同一转账摘要口径');

        // SettlementLogic 委托统一摘要
        $logicSummaryBody = $this->extractFunctionBody(
            $this->readSource('app/adminapi/logic/financial/SettlementLogic.php'), 'buildTransferSummary');
        $this->assertContains('StaffSettlementService::buildTransferSummary($transfers)', $logicSummaryBody,
            '后台结算详情摘要必须委托统一服务');
    }

    private function transfer(int $id, int $status, array $extra): array
    {
        return array_merge([
            'id' => $id,
            'status' => $status,
            'out_bill_no' => '',
            'transfer_bill_no' => '',
            'package_info' => '',
            'fail_reason' => '',
        ], $extra);
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

    private function assertInArray(string $needle, array $haystack, string $message): void
    {
        $this->assertions++;
        if (!in_array($needle, $haystack, true)) {
            throw new RuntimeException($message . '，缺少元素：' . $needle);
        }
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
}

try {
    (new FinancialChainAuditRegressionTest())->run();
} catch (Throwable $e) {
    fwrite(STDERR, 'FAIL - ' . $e->getMessage() . "\n");
    exit(1);
}
