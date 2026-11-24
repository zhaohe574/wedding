<?php
// +----------------------------------------------------------------------
// | 客户与跟进（CRM + C端咨询）全链路回归测试
// +----------------------------------------------------------------------
// | 覆盖：
// | 1. 顾问搜索 SQL 与 N+1 修复、负载精准重算与转化率公式
// | 2. 跟进自动消警、意向下降自动生警、防重复签单虚增订单数
// | 3. 客户新增防重、批量转移、顾问休假/离职一键交接与预警归属同步
// | 4. C 端咨询防重复通知、上下文透传、主动留资接口、订单支付自动推进签单
// | 5. 后台权限依赖映射完整性
// | 执行：php tests/regression/CrmWorkflowTest.php
// +----------------------------------------------------------------------

declare(strict_types=1);

use app\common\model\crm\Customer;
use app\common\model\crm\CustomerLossWarning;
use app\common\model\crm\FollowRecord;
use app\common\model\crm\SalesAdvisor;
use app\common\service\AdminPermissionService;

require __DIR__ . '/../../vendor/autoload.php';
require __DIR__ . '/../../vendor/topthink/framework/src/helper.php';

final class CrmWorkflowTest
{
    private int $assertions = 0;

    public function run(): void
    {
        $this->testPermissionDependencies();
        $this->testDaysNoFollowAndModels();
        $this->testBackendLogicAndSqlFixes();
        $this->testMiniappConsultEntrypoints();
        $this->testAdminWorkstationEntrypoints();
        $this->testDatabaseWorkflowIfConfigured();

        echo 'OK - ' . $this->assertions . " assertions\n";
    }

    private function testPermissionDependencies(): void
    {
        $deps = AdminPermissionService::dependencies();
        $this->assertTrue(isset($deps['crm.customer/lists']), '必须声明 crm.customer/lists 依赖映射');
        $this->assertTrue(in_array('crm.customer/overview', $deps['crm.customer/lists'], true), '客户列表需继承 overview');
        $this->assertTrue(in_array('crm.customer/advisorOptions', $deps['crm.customer/lists'], true), '客户列表需继承 advisorOptions');
        $this->assertTrue(in_array('crm.customer/add', $deps['crm.customer/edit'] ?? [], true), '编辑权限需兼容新增客户');
        $this->assertTrue(in_array('crm.customer/batchTransfer', $deps['crm.customer/transferAdvisor'] ?? [], true), '转移权限需兼容批量转移');
        $this->assertTrue(in_array('crm.followRecord/stats', $deps['crm.followRecord/lists'] ?? [], true), '跟进列表需继承 stats');
        $this->assertTrue(in_array('crm.followRecord/customerOptions', $deps['crm.followRecord/lists'] ?? [], true), '跟进列表需继承 customerOptions');
        $this->assertTrue(in_array('crm.lossWarning/stats', $deps['crm.lossWarning/lists'] ?? [], true), '预警列表需继承 stats');
        $this->assertTrue(in_array('auth.admin/all', $deps['crm.salesAdvisor/add'] ?? [], true), '新增顾问需继承 auth.admin/all');
        $this->assertTrue(in_array('crm.salesAdvisor/handover', $deps['crm.salesAdvisor/changeStatus'] ?? [], true), '切换顾问状态需兼容交接接口');
    }

    private function testDaysNoFollowAndModels(): void
    {
        $customer = new Customer();
        $daysZero = $customer->getDaysNoFollowAttr(0, ['last_follow_time' => time() - 3600, 'create_time' => time() - 3600]);
        $this->assertSame(0, $daysZero, '刚刚跟进或建档未满24小时不得显示1天未跟进');

        $daysTwo = $customer->getDaysNoFollowAttr(0, ['last_follow_time' => time() - 2 * 86400 - 120, 'create_time' => time() - 5 * 86400]);
        $this->assertSame(2, $daysTwo, '满48小时应精确计算为2天未跟进');

        $this->assertSame('首页咨询', Customer::formatSourceDetailText('consult:home'), '首页咨询标识应转为中文');
        $this->assertSame('小程序预约回电留资', Customer::formatSourceDetailText('consult:intention_form'), '留资标识应转为中文');
        $this->assertSame('小红书私信', Customer::formatSourceDetailText('小红书私信'), '手工来源详情应原样保留');
        $this->assertContains('服务人员详情咨询', Customer::formatSourceDetailText('consult:staff_detail#1'), '带ID来源标识应包含中文场景前缀');
    }

    private function testBackendLogicAndSqlFixes(): void
    {
        $advisorLists = $this->readSource('app/adminapi/lists/crm/SalesAdvisorLists.php');
        $this->assertNotContains("whereOr('like',", $advisorLists, 'SalesAdvisorLists 不得残留缺少字段名的 whereOr(like) 语法错');
        $this->assertContains("whereOr('email', 'like'", $advisorLists, 'SalesAdvisorLists 关键词搜索必须包含 email 字段');
        $this->assertContains('getCustomerStatsMap', $advisorLists, 'SalesAdvisorLists 必须批量聚合客户统计消除 N+1');

        $customerLogic = $this->readSource('app/adminapi/logic/crm/CustomerLogic.php');
        $this->assertNotContains('::append(', $customerLogic, 'CustomerLogic 不得静态调用 think\\Model::append()');

        $customerModel = $this->readSource('app/common/model/crm/Customer.php');
        $this->assertContains('SalesAdvisor::recalculateCustomerCount', $customerModel, 'Customer 状态与分配变更必须重算顾问活跃负载');
        $this->assertContains('CustomerLossWarning::syncAdvisorByCustomer', $customerModel, '转移顾问必须同步待处理预警归属');
        $this->assertContains('CustomerLossWarning::resolveAllPendingWarningsByCustomer', $customerModel, '签单或流失必须结清待处理预警');

        $followModel = $this->readSource('app/common/model/crm/FollowRecord.php');
        $this->assertContains('resolveNoFollowWarningsByCustomer', $followModel, '新增跟进必须自动解除长期未跟进预警');
        $this->assertContains('TYPE_INTENTION_DOWN', $followModel, '意向下降必须自动触发流失预警');
        $this->assertNotContains('SalesAdvisor::updateOrderStats($data[\'advisor_id\'], 0)', $followModel, '不得再通过手工跟进虚增金额为0的成交单数');

        $consultLogic = $this->readSource('app/api/logic/CustomerServiceLogic.php');
        $this->assertContains('public static function submitIntention', $consultLogic, '必须提供客户主动留资接口 submitIntention');
        $this->assertSame(
            1,
            substr_count($consultLogic, "InternalNotificationService::advisor(\$advisorId, \$notifyCard['title']"),
            'startConsult 仅允许在事务提交后发送一次顾问通知'
        );
        $this->assertContains('hasSubmittedIntention', $consultLogic, '必须提供留资提交状态判定方法 hasSubmittedIntention');
        $this->assertContains('INTENTION_FOLLOW_PREFIX', $consultLogic, '留资跟进内容必须使用统一前缀常量 INTENTION_FOLLOW_PREFIX');
        $this->assertContains("'intention_submitted' =>", $consultLogic, 'customer_profile 必须输出明确留资态字段 intention_submitted');

        $submitBody = $this->extractMethodBody($consultLogic, 'submitIntention');
        $this->assertContains('FollowRecord::createRecord', $submitBody, 'submitIntention 必须始终落库留资跟进记录');
        $this->assertContains('self::INTENTION_FOLLOW_PREFIX', $submitBody, '留资跟进内容必须使用统一前缀常量拼接');
        $this->assertNotContains(
            'if ($advisorId > 0) {' . "\n" . '                $summaryParts = array_filter([',
            $submitBody,
            'submitIntention 不得再以 if ($advisorId > 0) 门控 FollowRecord::createRecord 落库'
        );

        $orderNotify = $this->readSource('app/common/service/OrderNotificationService.php');
        $this->assertContains('CrmOrderSyncService::syncOnOrderPaid', $orderNotify, '订单支付成功通知必须触发 CRM 自动同步');

        $internalNotify = $this->readSource('app/common/service/InternalNotificationService.php');
        $this->assertNotContains('$ids ?: [0]', $internalNotify, '不得向 la_notification_event 写入 user_id=0 的脏记录');
        $this->assertContains('advisorDispatch', $internalNotify, 'InternalNotificationService 必须支持结构化即时派发');

        $bizNotify = $this->readSource('app/common/service/BusinessNotificationService.php');
        $this->assertContains('dispatchByEventKey', $bizNotify, 'BusinessNotificationService 必须支持单条事件即时派发');

        $warningLogic = $this->readSource('app/adminapi/logic/crm/LossWarningLogic.php');
        $this->assertContains('crm_warning_manual:', $warningLogic, '手动推送应区分定时任务幂等键并支持即时派发');
        $this->assertNotContains('(int)ceil(max(0, time() - $baseTime)', $warningLogic, '顾问预警扫描天数计算不得使用 ceil');

        $warningLists = $this->readSource('app/adminapi/lists/crm/LossWarningLists.php');
        $this->assertContains('getPushStatusMap', $warningLists, '预警列表必须聚合展示真实推送状态');
    }

    private function testMiniappConsultEntrypoints(): void
    {
        $csPage = $this->readSource('../newuniapp/src/pages/customer-service/customer-service.vue');
        $this->assertContains('onLoad((options', $csPage, 'customer-service.vue 必须读取路由场景与 staff_id/order_id 参数');
        $this->assertContains('resolveBookingContext', $csPage, 'customer-service.vue 必须透传 booking-context 中的婚期与城市');
        $this->assertContains('submitConsultIntention', $csPage, 'customer-service.vue 必须集成预约顾问回电留资表单');
        $this->assertContains('profile?.intention_submitted', $csPage, 'customer-service.vue 必须以后端 intention_submitted 字段判定留资态');
        $this->assertNotContains(
            'profile.customer_mobile && (profile.wedding_date || profile.budget_range || profile.service_needs?.length)',
            $csPage,
            'customer-service.vue 不得再用手机号+画像字段启发式误判已预约回电'
        );

        $serviceApi = $this->readSource('../newuniapp/src/api/service.ts');
        $this->assertContains('/customer_service/submitIntention', $serviceApi, 'service.ts 必须封装 submitIntention 接口');
    }

    private function testAdminWorkstationEntrypoints(): void
    {
        $customerView = $this->readSource('../admin/src/views/crm/customer/index.vue');
        $this->assertContains('CustomerDetailDrawer', $customerView, '客户管理必须集成 360° 详情抽屉');
        $this->assertContains('customerBatchTransfer', $customerView, '客户管理必须支持批量转移顾问');
        $this->assertContains('customerAdd', $customerView, '客户管理必须支持手工新增客户');

        $warningView = $this->readSource('../admin/src/views/crm/loss-warning/index.vue');
        $this->assertContains('openFollowForWarning', $warningView, '流失预警页必须支持一键立即跟进');
        $this->assertContains('openTransferForWarning', $warningView, '流失预警页必须支持一键转派顾问');

        $advisorView = $this->readSource('../admin/src/views/crm/advisor/index.vue');
        $this->assertContains('advisorHandover', $advisorView, '顾问管理页必须支持休假/离职客户交接');
        $this->assertContains('advisorSyncAllCustomerCount', $advisorView, '顾问管理页必须支持一键全量校准');
    }

    private function testDatabaseWorkflowIfConfigured(): void
    {
        $port = (int)getenv('WEDDING_TEST_MYSQL_PORT');
        if ($port <= 0 || $port === 3306) {
            return;
        }

        $password = (string)getenv('WEDDING_TEST_MYSQL_PASSWORD');
        $pdo = new PDO(
            'mysql:host=127.0.0.1;port=' . $port . ';charset=utf8mb4',
            'root',
            $password,
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );
        $database = 'wedding_crm_' . getmypid() . '_' . time();
        $pdo->exec('CREATE DATABASE `' . $database . '` CHARACTER SET utf8mb4');
        try {
            $pdo->exec('USE `' . $database . '`');
            $pdo->exec(str_replace('`la_', '`qa_', file_get_contents(__DIR__ . '/../../public/install/db/like.sql')));
            $upgradeSql = file_get_contents(__DIR__ . '/../../database/migrations/20261122_crm_workflow_upgrade.sql');
            if ($upgradeSql) {
                $pdo->exec(str_replace('`la_', '`qa_', $upgradeSql));
            }

            $app = new think\App(dirname(__DIR__, 2) . DIRECTORY_SEPARATOR);
            $app->config->set([
                'default' => 'mysql',
                'auto_timestamp' => true,
                'connections' => [
                    'mysql' => [
                        'type' => 'mysql',
                        'hostname' => '127.0.0.1',
                        'hostport' => $port,
                        'database' => $database,
                        'username' => 'root',
                        'password' => $password,
                        'charset' => 'utf8mb4',
                        'prefix' => 'qa_',
                        'debug' => false,
                    ],
                ],
            ], 'database');
            $cache = ['type' => 'File', 'path' => sys_get_temp_dir() . '/wedding-crm-cache-' . $database . '/'];
            $app->config->set(['default' => 'file', 'stores' => ['file' => $cache, 'config' => $cache]], 'cache');
            (new think\service\ModelService($app))->boot();
            require_once __DIR__ . '/../../app/common.php';

            $now = time();
            $advisorA = SalesAdvisor::create([
                'advisor_name' => '顾问甲',
                'mobile' => '13900000001',
                'email' => 'jia@example.com',
                'max_customer_count' => 20,
                'status' => SalesAdvisor::STATUS_NORMAL,
                'create_time' => $now,
                'update_time' => $now,
            ]);
            $advisorB = SalesAdvisor::create([
                'advisor_name' => '顾问乙',
                'mobile' => '13900000002',
                'email' => 'yi@example.com',
                'max_customer_count' => 20,
                'status' => SalesAdvisor::STATUS_NORMAL,
                'create_time' => $now,
                'update_time' => $now,
            ]);

            // 验证 SalesAdvisorLists 带关键词搜索不报错
            $listsObj = new \app\adminapi\lists\crm\SalesAdvisorLists();
            $listsObj->params = ['keyword' => 'jia@example.com'];
            $searchRes = $listsObj->lists();
            $this->assertSame(1, count($searchRes), 'SalesAdvisorLists 按邮箱关键词搜索应精准命中 1 条');

            // 手工新增客户 + 创建未跟进预警 + 写跟进自动消警
            $addRes = \app\adminapi\logic\crm\CustomerLogic::add([
                'customer_name' => '测试新人',
                'customer_mobile' => '13811112222',
                'gender' => 2,
                'intention_level' => 'C',
                'source_channel' => Customer::SOURCE_OFFLINE,
                'customer_status' => Customer::STATUS_NEW,
                'assign_mode' => 'manual',
                'advisor_id' => (int)$advisorA->id,
            ], 1);
            $this->assertTrue(is_array($addRes) && ($addRes['id'] ?? 0) > 0, '手工新增客户必须成功');
            $cid = (int)$addRes['id'];
            $this->assertSame(1, (int)SalesAdvisor::where('id', (int)$advisorA->id)->value('current_customer_count'), '新增客户后顾问负载应为 1');

            CustomerLossWarning::createWarning($cid, (int)$advisorA->id, CustomerLossWarning::TYPE_NO_FOLLOW, CustomerLossWarning::LEVEL_MEDIUM, '测试未跟进', 15);
            $this->assertSame(1, CustomerLossWarning::where('customer_id', $cid)->where('warning_status', CustomerLossWarning::STATUS_PENDING)->count());

            $followed = \app\adminapi\logic\crm\FollowRecordLogic::add([
                'customer_id' => $cid,
                'follow_type' => FollowRecord::TYPE_PHONE,
                'follow_content' => '已电话沟通场地预算',
                'follow_result' => FollowRecord::RESULT_DOWNGRADE,
                'intention_after' => 'C',
            ], 1);
            $this->assertTrue($followed, '新增跟进记录应成功');
            $this->assertSame(
                0,
                CustomerLossWarning::where('customer_id', $cid)->where('warning_type', CustomerLossWarning::TYPE_NO_FOLLOW)->where('warning_status', CustomerLossWarning::STATUS_PENDING)->count(),
                '写跟进后长期未跟进预警必须自动解除'
            );
            $this->assertSame(
                1,
                CustomerLossWarning::where('customer_id', $cid)->where('warning_type', CustomerLossWarning::TYPE_INTENTION_DOWN)->where('warning_status', CustomerLossWarning::STATUS_PENDING)->count(),
                '意向下降跟进必须自动生成意向下降预警'
            );

            // 一键交接验证：预警归属与双方负载同步迁移
            $handoverRes = \app\adminapi\logic\crm\SalesAdvisorLogic::handover([
                'from_advisor_id' => (int)$advisorA->id,
                'to_advisor_id' => (int)$advisorB->id,
                'mode' => 'target',
                'reason' => '休假交接',
            ], 1);
            $this->assertSame(1, (int)($handoverRes['transferred'] ?? 0), '顾问交接应转移 1 名活跃客户');
            $this->assertSame(0, (int)SalesAdvisor::where('id', (int)$advisorA->id)->value('current_customer_count'), '转出顾问负载应归零');
            $this->assertSame(1, (int)SalesAdvisor::where('id', (int)$advisorB->id)->value('current_customer_count'), '目标顾问负载应变为 1');
            $this->assertSame((int)$advisorB->id, (int)CustomerLossWarning::where('customer_id', $cid)->where('warning_status', CustomerLossWarning::STATUS_PENDING)->value('advisor_id'), '待处理预警必须随客户交接同步归属给新顾问');
        } finally {
            $pdo->exec('DROP DATABASE IF EXISTS `' . $database . '`');
        }
    }

    private function readSource(string $relativePath): string
    {
        $path = __DIR__ . '/../../' . $relativePath;
        $content = file_get_contents($path);
        $this->assertTrue($content !== false, '无法读取文件：' . $relativePath);
        return (string)$content;
    }

    private function extractMethodBody(string $source, string $method): string
    {
        $start = strpos($source, 'function ' . $method . '(');
        $this->assertTrue($start !== false, '未找到方法：' . $method);
        $end = strpos($source, "\n    }", $start);
        $this->assertTrue($end !== false, '未能截取方法体：' . $method);
        return (string)substr($source, $start, $end - $start);
    }

    private function assertTrue(bool $condition, string $message): void
    {
        $this->assertions++;
        if (!$condition) {
            throw new RuntimeException($message);
        }
    }

    private function assertSame(mixed $expected, mixed $actual, string $message = ''): void
    {
        $this->assertions++;
        if ($expected !== $actual) {
            throw new RuntimeException(($message ?: '断言失败') . ' expected=' . var_export($expected, true) . ' actual=' . var_export($actual, true));
        }
    }

    private function assertContains(string $needle, string $haystack, string $message): void
    {
        $this->assertTrue(str_contains($haystack, $needle), $message);
    }

    private function assertNotContains(string $needle, string $haystack, string $message): void
    {
        $this->assertTrue(!str_contains($haystack, $needle), $message);
    }
}

(new CrmWorkflowTest())->run();
