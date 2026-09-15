<?php

declare(strict_types=1);

use app\adminapi\http\middleware\AuthMiddleware;
use app\adminapi\logic\financial\FinancialReportLogic;
use app\adminapi\logic\order\OrderCustomerLogic;
use app\common\command\Crontab;
use app\common\model\aftersale\Complaint;
use app\common\model\staff\Staff;
use app\common\service\RedisLockService;
use think\facade\Db;

require __DIR__ . '/../../vendor/autoload.php';
require __DIR__ . '/../../vendor/topthink/framework/src/helper.php';

// 隔离实例只创建随机测试库，不加载真实环境配置。
$port = (int)getenv('WEDDING_TEST_MYSQL_PORT');
if ($port <= 0 || $port === 3306) {
    throw new RuntimeException('请指定独立本机测试数据库端口，禁止使用 3306');
}
$password = (string)getenv('WEDDING_TEST_MYSQL_PASSWORD');
$pdo = new PDO('mysql:host=127.0.0.1;port=' . $port . ';charset=utf8mb4', 'root', $password,
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$database = 'wedding_audit_' . getmypid() . '_' . time();
$pdo->exec('CREATE DATABASE `' . $database . '` CHARACTER SET utf8mb4');
$checks = 0;
$check = static function (bool $ok, string $message) use (&$checks): void {
    $checks++;
    if (!$ok) throw new RuntimeException($message);
};
try {
    $pdo->exec('USE `' . $database . '`');
    $pdo->exec(str_replace('`la_', '`qa_', file_get_contents(__DIR__ . '/../../public/install/db/like.sql')));
    $app = new think\App(dirname(__DIR__, 2) . DIRECTORY_SEPARATOR);
    $app->config->set(['default' => 'mysql', 'auto_timestamp' => true, 'connections' => ['mysql' => [
        'type' => 'mysql', 'hostname' => '127.0.0.1', 'hostport' => $port, 'database' => $database,
        'username' => 'root', 'password' => $password, 'charset' => 'utf8mb4', 'prefix' => 'qa_', 'debug' => false,
    ]]], 'database');
    $cache = ['type' => 'File', 'path' => sys_get_temp_dir() . '/wedding-cache-' . $database . '/'];
    $app->config->set(['default' => 'file', 'stores' => ['file' => $cache, 'config' => $cache]], 'cache');
    $app->config->set(['default' => 'file', 'channels' => ['file' => ['type' => 'File',
        'path' => sys_get_temp_dir() . '/wedding-log-' . $database . '/']]], 'log');
    (new think\service\ModelService($app))->boot();
    require_once __DIR__ . '/../../app/common.php';

    $middleware = new class extends AuthMiddleware {
        protected function isLoginIpCheckEnabled(): bool { return false; }
    };
    $request = new think\Request();
    $request->controllerObject = new class {
        public function isNotNeedLogin(): bool { return false; }
    };
    $request->adminInfo = ['admin_id' => 10001, 'root' => 0, 'role_id' => []];
    $request->setController('setting.storage');
    $request->setAction('detail');
    $next = static fn () => 'allowed';
    $check($middleware->handle($request, $next) !== 'allowed', '普通账号不能读取存储凭据');
    $request->setAction('unknownAction');
    $check($middleware->handle($request, $next) !== 'allowed', '未知接口必须默认拒绝');
    $request->setController('auth.admin');
    $request->setAction('mySelf');
    $check($middleware->handle($request, $next) === 'allowed', '当前账号资料入口应保留');

    $menuId = (int)Db::name('system_menu')->where('perms', 'setting.storage/setup')->value('id');
    Db::name('admin_role')->insert(['admin_id' => 10002, 'role_id' => 10002]);
    Db::name('system_role_menu')->insert(['role_id' => 10002, 'menu_id' => $menuId]);
    $request->adminInfo = ['admin_id' => 10002, 'root' => 0, 'role_id' => [10002]];
    $request->setController('setting.storage');
    $request->setAction('detail');
    $check($middleware->handle($request, $next) === 'allowed', '有存储配置权限的角色可以读取详情');
    Db::name('system_menu')->where('id', $menuId)->update(['is_disable' => 1]);
    $check($middleware->handle($request, $next) !== 'allowed', '停用权限不能退化为公开访问');

    Db::name('user')->insert(['id' => 10001, 'sn' => 10001, 'account' => 'audit_customer',
        'nickname' => '测试客户', 'mobile' => '13800000001']);
    foreach (['', '138', '测试客户', '%'] as $keyword) {
        $rejected = false;
        try { OrderCustomerLogic::options($keyword, 10001); } catch (InvalidArgumentException $e) { $rejected = true; }
        $check($rejected, '客户查询必须拒绝模糊条件：' . $keyword);
    }
    $customers = OrderCustomerLogic::options('13800000001', 10001);
    $check(count($customers) === 1 && (int)$customers[0]['id'] === 10001, '完整手机号可精确定位客户');
    OrderCustomerLogic::assertSelected(10001, 10001);
    $selectionRejected = false;
    try { OrderCustomerLogic::assertSelected(10002, 10001); } catch (InvalidArgumentException $e) { $selectionRejected = true; }
    $check($selectionRejected, '另一个后台账号不能复用客户查询授权');
    $check(!array_key_exists('account', $customers[0]) && !array_key_exists('manual_risk_rank', $customers[0]), '客户选项不得暴露账号和风控信息');
    $check(OrderCustomerLogic::options('13800000002', 10001) === [], '不存在的手机号返回空列表');

    Db::name('admin')->insert(['id' => 10001, 'account' => 'audit_staff', 'name' => '测试服务人员', 'password' => 'unused', 'create_time' => time()]);
    Db::name('staff')->insert(['id' => 10001, 'name' => '测试服务人员', 'admin_id' => 10001, 'status' => 1]);
    Db::name('admin_session')->insert(['admin_id' => 10001, 'token' => 'audit_cached_session', 'expire_time' => time() + 3600]);
    $cachedAdmin = (new \app\common\cache\AdminTokenCache())->setAdminInfo('audit_cached_session');
    $check(!empty($cachedAdmin), '停用前必须建立有效登录缓存');
    Db::name('complaint')->insert(['id' => 10001, 'complaint_sn' => 'AUDIT_COMPLAINT', 'order_id' => 10001,
        'user_id' => 10001, 'staff_id' => 10001, 'title' => '禁用测试', 'content' => '测试', 'status' => Complaint::STATUS_PENDING]);
    [$ok] = Complaint::handleComplaint(10001, 1, ['result' => '停止新增接单', 'action' => Complaint::ACTION_DISABLE]);
    $check($ok && (int)Staff::find(10001)->status === Staff::STATUS_DISABLE, '投诉禁用必须实际停用服务人员');
    $check((int)Db::name('admin')->where('id', 10001)->value('disable') === 1, '关联后台账号必须停用');
    $request->withHeader(['token' => 'audit_cached_session']);
    $loginResult = (new \app\adminapi\http\middleware\LoginMiddleware())->handle($request, $next);
    $check($loginResult !== 'allowed' && $loginResult->getData()['code'] === -1, '停用后已有登录缓存也必须立即失效');
    [$repeated] = Complaint::handleComplaint(10001, 1, ['result' => '重复处理', 'action' => Complaint::ACTION_DISABLE]);
    $check(!$repeated, '已处理投诉不能重复执行');
    Db::name('complaint')->insert(['id' => 10002, 'complaint_sn' => 'AUDIT_MISSING', 'order_id' => 10001,
        'user_id' => 10001, 'staff_id' => 99999, 'title' => '缺失人员', 'content' => '测试', 'status' => Complaint::STATUS_PENDING]);
    [$missing] = Complaint::handleComplaint(10002, 1, ['result' => '禁用失败', 'action' => Complaint::ACTION_DISABLE]);
    $check(!$missing && (int)Complaint::find(10002)->status === Complaint::STATUS_PENDING, '动作失败必须回滚投诉状态');

    foreach ([['A', 10001, 60, 1], ['B', 10001, 40, 1], ['C', 10002, 500, 2]] as [$sn, $orderId, $amount, $owner]) {
        Db::name('payment')->insert(['payment_sn' => 'AUDIT_' . $sn, 'order_id' => $orderId, 'user_id' => 10001,
            'pay_status' => 1, 'pay_amount' => $amount, 'collection_owner' => $owner, 'pay_time' => strtotime('2026-09-10')]);
    }
    $overview = FinancialReportLogic::overview(['start_date' => '2026-09-01', 'end_date' => '2026-09-14']);
    $check((int)$overview['order_count'] === 0 && (int)$overview['receipt_order_count'] === 1, '跨期收款应按收款订单去重');
    $check((float)$overview['avg_order_amount'] === 100.0, '均值不能计入人员代收，也不能按付款笔数计算');

    $command = new class extends think\console\Command {
        protected function configure() { $this->setName('audit_failure'); }
        protected function execute(think\console\Input $input, think\console\Output $output) {
            $output->writeln('测试任务执行失败'); return 1;
        }
    };
    // 使用真实命令调度实现，但禁止其初始化器读取本机 .env。
    $console = new class($app) extends think\Console {
        protected function initialize(): void {}
    };
    $app->instance('console', $console);
    $console->addCommand($command);
    $menuCount = Db::name('system_menu')->count();
    $grantCount = Db::name('system_role_menu')->count();
    $sync = new \app\common\command\SyncAdminPermissions();
    $console->addCommand($sync);
    $check($sync->run(new think\console\Input(['sync_admin_permissions']), new think\console\Output('buffer')) === 0, '权限升级命令应成功');
    $check(Db::name('system_menu')->count() === $menuCount && Db::name('system_role_menu')->count() === $grantCount, '重复升级不能增加重复权限或扩大角色授权');
    $cron = Db::name('dev_crontab')->find(1);
    $cron['command'] = 'audit_failure';
    $cron['params'] = '';
    $check(Crontab::start($cron) === false, '非零退出状态必须上报失败');
    $saved = Db::name('dev_crontab')->find($cron['id']);
    $check((int)$saved['status'] === 3 && str_contains($saved['error'], '测试任务执行失败'), '调度记录应保存真实失败原因');
    $success = new class extends think\console\Command {
        public int $runs = 0;
        protected function configure() { $this->setName('audit_success'); }
        protected function execute(think\console\Input $input, think\console\Output $output) { $this->runs++; return 0; }
    };
    $console->addCommand($success);
    $saved['command'] = 'audit_success';
    $check(Crontab::start($saved) === true, '零退出状态应正常完成');
    $check(Db::name('dev_crontab')->where('id', $saved['id'])->value('error') === '', '成功执行后应清除旧错误');
    Db::name('dev_crontab')->where('id', '>', 0)->update(['status' => 2]);
    Db::name('dev_crontab')->where('id', $saved['id'])->update(['status' => 1, 'command' => 'audit_success', 'expression' => '* * * * *', 'last_time' => time() - 180]);
    $scheduler = new Crontab();
    $console->addCommand($scheduler);
    $beforeRuns = $success->runs;
    $check($scheduler->run(new think\console\Input(['crontab']), new think\console\Output('buffer')) === 0 && $success->runs === $beforeRuns + 1, '调度入口必须真正执行已到期任务，不能把整数时间误解释为现在');
    $check((int)Db::name('dev_crontab')->where('id', $saved['id'])->value('last_time') >= time() - 5, '自动调度必须推进最后执行时间');
    Db::name('dev_crontab')->where('id', $saved['id'])->update(['last_time' => time() + 60]);
    $scheduler->run(new think\console\Input(['crontab']), new think\console\Output('buffer'));
    $check($success->runs === $beforeRuns + 1, '未到期任务不得执行');
    $check(RedisLockService::acquire('audit-lock', 10, 0) === false, '没有 Redis 原子锁时必须拒绝获取');
    echo 'OK - 项目审查数据库回归：' . $checks . " 项\n";
} finally {
    $pdo->exec('DROP DATABASE `' . $database . '`');
}
