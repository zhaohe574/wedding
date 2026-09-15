<?php
declare(strict_types=1);

use app\common\model\auth\Admin;
use app\common\model\financial\FinancialFlow;
use app\common\model\order\Order;
use app\common\model\order\OrderItem;
use app\common\model\order\Payment;
use app\common\model\order\Refund;
use app\common\model\schedule\Schedule;
use app\common\model\staff\Staff;
use app\common\model\user\User;
use app\common\model\user\UserAuth;
use app\common\model\wechat\OaFollower;
use app\common\model\wechat\OaNotificationLog;
use app\common\model\wechat\OaNotificationTemplate;
use app\common\service\ConfigService;
use app\common\service\WechatNotificationService;
use app\common\enum\user\UserTerminalEnum;
use think\facade\Db;

require __DIR__ . '/../../vendor/autoload.php';
require __DIR__ . '/../../vendor/topthink/framework/src/helper.php';

// 只替换微信传输，队列、权限、事务及资金模型均执行项目真实代码。
class LocalOaTransport
{
    public function sendTemplateMessage(array $payload): array
    {
        Db::table('test_delivery')->insert(['payload' => json_encode($payload)]);
        usleep(150000);
        return ['errcode' => ($payload['data']['thing1']['value'] ?? '') === '重试测试' ? -1 : 0, 'msgid' => 'local-test'];
    }
}
class_alias(LocalOaTransport::class, 'app\common\service\wechat\WeChatOaService');

$port = (int)getenv('WEDDING_TEST_MYSQL_PORT');
if ($port <= 0 || $port === 3306) {
    throw new RuntimeException('必须指定独立本机测试数据库端口，禁止使用 3306');
}
$worker = ($argv[1] ?? '') === '--worker';
$database = $worker ? ($argv[2] ?? '') : 'wedding_concurrent_' . getmypid() . '_' . time();
if (!preg_match('/^wedding_concurrent_[0-9]+_[0-9]+$/', $database)) {
    throw new RuntimeException('测试库名称不合法');
}
$password = (string)getenv('WEDDING_TEST_MYSQL_PASSWORD');
$pdo = new PDO('mysql:host=127.0.0.1;port=' . $port . ';charset=utf8mb4', 'root', $password,
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$children = [];
$checks = 0;
$check = static function (bool $ok, string $message) use (&$checks): void {
    $checks++;
    if (!$ok) { throw new RuntimeException($message); }
};
try {
    if (!$worker) {
        $pdo->exec('CREATE DATABASE `' . $database . '` CHARACTER SET utf8mb4');
        $pdo->exec('USE `' . $database . '`');
        $pdo->exec(str_replace('`la_', '`qa_', file_get_contents(__DIR__ . '/../../public/install/db/like.sql')));
        $pdo->exec('CREATE TABLE test_barrier (group_id varchar(32), worker_id int, PRIMARY KEY (group_id, worker_id)) ENGINE=InnoDB');
        $pdo->exec('CREATE TABLE test_delivery (id int AUTO_INCREMENT PRIMARY KEY, payload text) ENGINE=InnoDB');
    }
    $app = new think\App(dirname(__DIR__, 2) . DIRECTORY_SEPARATOR);
    $app->config->set(['default' => 'mysql', 'auto_timestamp' => true, 'connections' => ['mysql' => [
        'type' => 'mysql', 'hostname' => '127.0.0.1', 'hostport' => $port, 'database' => $database,
        'username' => 'root', 'password' => $password, 'charset' => 'utf8mb4', 'prefix' => 'qa_', 'debug' => false,
    ]]], 'database');
    // 各进程独立缓存，避免测试配置写入时混入另一个进程的旧缓存。
    $cache = ['type' => 'File', 'path' => sys_get_temp_dir() . '/wedding-cache-' . $database . '-' . getmypid() . '/'];
    $app->config->set(['default' => 'file', 'stores' => ['file' => $cache, 'config' => $cache]], 'cache');
    $app->config->set(['default' => 'file', 'channels' => ['file' => ['type' => 'File',
        'path' => sys_get_temp_dir() . '/wedding-log-' . $database . '/']]], 'log');
    (new think\service\ModelService($app))->boot();
    require_once __DIR__ . '/../../app/common.php';

    $action = static function (string $kind, int $id) {
        if ($kind === 'payment') {
            $payment = Payment::find($id);
            $result = Db::transaction(static function () use ($payment) {
                $result = Payment::paySuccess((string)$payment->payment_sn, 'TX_' . $payment->id, [
                    'source' => 'wechat_pay_v3', 'source_verified' => true, 'trade_state' => 'SUCCESS',
                    'attach' => 'order', 'out_trade_no' => $payment->payment_sn,
                    'amount' => ['total' => 10000, 'currency' => 'CNY'], 'payer' => ['openid' => 'concurrent_payer'],
                    'terminal' => UserTerminalEnum::WECHAT_MMP,
                ]);
                if (!$result[0]) { throw new RuntimeException($result[1]); }
                return $result;
            });
            if (!empty($result[2]['should_notify'])) {
                \app\common\service\OrderNotificationService::notifyUserAndStaffOnPaymentSuccess((int)$payment->order_id, (int)$payment->pay_type);
            }
            return $result;
        }
        if ($kind === 'flow') {
            return Db::transaction(static fn () => FinancialFlow::createUniqueFlow([
                'flow_type' => FinancialFlow::FLOW_TYPE_INCOME, 'biz_type' => FinancialFlow::BIZ_TYPE_OTHER,
                'biz_id' => $id, 'biz_sn' => 'CONCURRENT_FLOW', 'amount' => 10,
            ])->id);
        }
        if ($kind === 'queue') {
            return WechatNotificationService::send(1, 'concurrent_test', ['title' => '并发通知'], 'order', $id);
        }
        if ($kind === 'dispatch') { return WechatNotificationService::dispatchLog($id); }
        if ($kind === 'binding') {
            try {
                \app\common\service\AccountBindingService::bind($id, 2999, 1, '并发关联测试');
                return true;
            } catch (\Throwable $e) { return false; }
        }
        if ($kind === 'invitation_create') {
            return (int)\app\common\service\wechat\OaInvitationService::create('invitation_concurrent')->id;
        }
        if ($kind === 'invitation_confirm') {
            $token = (string)\app\common\model\wechat\OaBindSession::where('candidate_openid', 'invitation_concurrent')->value('token');
            try {
                return \app\common\service\wechat\OaInvitationService::confirm($id, $token)['state'] === 'completed';
            } catch (\RuntimeException $e) {
                if (get_class($e) !== \RuntimeException::class) throw $e;
                return false;
            }
        }
        if ($kind === 'business_event') {
            return Db::transaction(static fn () => \app\common\service\BusinessNotificationService::record([
                'event' => 'concurrent_business', 'instance' => 'one', 'user_id' => 1, 'audience' => 'user',
                'title' => '并发事件', 'content' => '重复执行只保留一条',
            ]));
        }
        if ($kind === 'offline') {
            return \app\adminapi\logic\order\OrderLogic::confirmOfflinePay([
                'id' => $id, 'admin_id' => 1, 'pay_type' => Payment::TYPE_FULL, 'pay_amount' => 100,
                'collection_owner' => Payment::COLLECTION_PLATFORM, 'voucher' => 'uploads/test.png',
            ]);
        }
        throw new RuntimeException('未知测试动作');
    };
    if ($worker) {
        [$group, $kind, $id, $workerId] = array_slice($argv, 3);
        Db::table('test_barrier')->insert(['group_id' => $group, 'worker_id' => (int)$workerId]);
        $deadline = microtime(true) + 15;
        while (Db::table('test_barrier')->where('group_id', $group)->count() < 2) {
            if (microtime(true) > $deadline) { throw new RuntimeException('并发测试同步超时'); }
            usleep(10000);
        }
        try {
            $result = $action($kind, (int)$id);
        } catch (\Throwable $e) {
            if (!str_contains($e->getMessage(), '1213') && !str_contains($e->getMessage(), '40001')) {
                throw $e;
            }
            usleep(100000);
            $result = $action($kind, (int)$id);
        }
        echo 'RESULT:' . json_encode($result, JSON_UNESCAPED_UNICODE) . "\n";
    } else {
        $parallel = static function (string $kind, array $ids) use ($database, &$children): array {
            $group = bin2hex(random_bytes(6));
            foreach ($ids as $index => $id) {
                $process = proc_open([PHP_BINARY, __FILE__, '--worker', $database, $group, $kind, (string)$id, (string)$index],
                    [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
                if (!is_resource($process)) { throw new RuntimeException('无法启动并发测试进程'); }
                fclose($pipes[0]);
                $children[] = [$process, $pipes];
            }
            $results = [];
            foreach ($children as [$process, $pipes]) {
                $stdout = stream_get_contents($pipes[1]);
                $stderr = stream_get_contents($pipes[2]);
                fclose($pipes[1]);
                fclose($pipes[2]);
                $code = proc_close($process);
                if ($code !== 0 || !preg_match('/RESULT:(.+)/', $stdout, $match)) {
                    throw new RuntimeException('并发动作失败：' . $stdout . $stderr);
                }
                $results[] = json_decode($match[1], true, 512, JSON_THROW_ON_ERROR);
            }
            $children = [];
            return $results;
        };
        User::create(['id' => 1, 'sn' => 987001, 'account' => 'concurrent_test']);
        User::create(['id' => 2999, 'sn' => 9872999, 'account' => 'binding_concurrent']);
        $bindingAdmins = [];
        foreach ([1, 2] as $suffix) {
            $bindingAdmins[] = Admin::create(['id' => 30000 + $suffix, 'account' => 'binding_concurrent_' . $suffix, 'password' => 'test'])->id;
        }
        $bound = $parallel('binding', $bindingAdmins);
        $check(count(array_filter($bound)) === 1, '两个后台账号并发抢绑同一用户，只允许一个成功');
        $check(Admin::where('user_id', 2999)->count() === 1, '并发抢绑不得遗留双重关系');
        $parallel('business_event', [1, 1]);
        $check(Db::name('notification_event')->where('user_id', 1)->count() === 1, '并发记录同一业务事件不得重复');
        UserAuth::create(['user_id' => 1, 'openid' => 'concurrent_payer', 'terminal' => UserTerminalEnum::WECHAT_MMP]);
        ConfigService::set('order_payment', 'offline_collection_enabled', 0);
        ConfigService::set('oa_notification', 'enabled', 1);
        $createOrder = static function (string $sn): Order {
            return Order::create(['order_sn' => $sn, 'user_id' => 1, 'order_status' => Order::STATUS_PENDING_PAY,
                'pay_status' => Order::PAY_STATUS_UNPAID, 'total_amount' => 100, 'pay_amount' => 100,
                'balance_amount' => 100, 'payment_channel' => Order::PAYMENT_CHANNEL_ONLINE]);
        };
        $order = $createOrder('CONCURRENT_REPLAY');
        $payment = Payment::createPayment((int)$order->id, $order->order_sn, 1, Payment::TYPE_FULL, Payment::WAY_WECHAT, 100);
        $results = $parallel('payment', [$payment->id, $payment->id]);
        $check(count(array_filter($results, static fn ($r) => !empty($r[2]['should_notify']))) === 1, '并发重复支付只允许一笔通知');
        $check((float)Order::find($order->id)->paid_amount === 100.0, '并发重复支付必须只入账一次');
        $check(FinancialFlow::where('order_id', $order->id)->count() === 1, '并发重复支付不得重复生成账本');

        $staff = Staff::create(['name' => '并发测试人员', 'status' => 1]);
        $date = date('Y-m-d', strtotime('+30 days'));
        Schedule::create(['staff_id' => $staff->id, 'schedule_date' => $date, 'time_slot' => 0, 'status' => Schedule::STATUS_AVAILABLE]);
        $paymentIds = [];
        foreach (['CONCURRENT_A', 'CONCURRENT_B'] as $sn) {
            $candidate = $createOrder($sn);
            OrderItem::create(['order_id' => $candidate->id, 'staff_id' => $staff->id, 'service_date' => $date,
                'item_type' => OrderItem::TYPE_SERVICE, 'price' => 100, 'subtotal' => 100]);
            $paymentIds[] = Payment::createPayment((int)$candidate->id, $sn, 1, Payment::TYPE_FULL, Payment::WAY_WECHAT, 100)->id;
        }
        $parallel('payment', $paymentIds);
        $check(Payment::whereIn('id', $paymentIds)->where('pay_status', Payment::STATUS_PAID)->count() === 2, '抢档双方实收必须留账');
        $check(Schedule::where('staff_id', $staff->id)->where('status', Schedule::STATUS_BOOKED)->count() === 1, '同档期只可被一个订单占用');
        $check(Refund::whereIn('payment_id', $paymentIds)->where('is_compensation', 1)->count() === 1, '抢档失败必须登记一笔补偿退款');

        $flows = $parallel('flow', [99999, 99999]);
        $check($flows[0] === $flows[1], '并发账本必须返回同一流水');
        foreach ([1, 2] as $unused) {
            FinancialFlow::createUniqueFlow(['flow_type' => FinancialFlow::FLOW_TYPE_INCOME,
                'biz_type' => FinancialFlow::BIZ_TYPE_OTHER, 'biz_id' => 0, 'amount' => 1]);
        }
        $check(FinancialFlow::where('biz_id', 0)->count() === 2, '手工无业务编号流水不得被误去重');
        try {
            FinancialFlow::createUniqueFlow(['flow_type' => FinancialFlow::FLOW_TYPE_INCOME,
                'biz_type' => FinancialFlow::BIZ_TYPE_OTHER, 'biz_id' => 99999, 'biz_sn' => 'CONCURRENT_FLOW', 'amount' => 11]);
            $check(false, '相同业务不同金额不能被视为成功重复');
        } catch (\think\db\exception\PDOException $e) { $check(true, '不一致账本已拒绝'); }

        ConfigService::set('order_payment', 'offline_collection_enabled', 1);
        $offlineOrder = $createOrder('CONCURRENT_OFFLINE');
        Order::where('id', $offlineOrder->id)->update(['payment_channel' => Order::PAYMENT_CHANNEL_OFFLINE]);
        $results = $parallel('offline', [$offlineOrder->id, $offlineOrder->id]);
        $check(count(array_filter($results)) === 1, '线下凭证并发确认仅允许一次成功');
        $check(Payment::where('order_id', $offlineOrder->id)->where('pay_status', Payment::STATUS_PAID)->count() === 1, '线下重复确认只生成一笔实收');

        OaFollower::create(['user_id' => 1, 'openid' => 'oa_concurrent', 'follow_status' => OaFollower::STATUS_FOLLOWED]);
        OaNotificationTemplate::create(['scene' => 'concurrent_test', 'audience' => 'user',
            'template_id' => 'local_template', 'status' => 1, 'data_mapping' => ['thing1' => 'title']]);
        $results = $parallel('queue', [$order->id, $order->id]);
        $logId = (int)$results[0]['log_id'];
        $check($logId > 0 && $logId === (int)$results[1]['log_id'], '并发入队必须去重');
        $parallel('dispatch', [$logId, $logId]);
        $check(Db::table('test_delivery')->count() === 1, '并发领取只允许发送一次');
        $check((int)OaNotificationLog::find($logId)->send_status === OaNotificationLog::STATUS_SUCCESS, '成功派发必须持久化');
        $retry = WechatNotificationService::send(1, 'concurrent_test', ['title' => '重试测试'], 'order', (int)$order->id);
        for ($attempt = 1; $attempt <= 4; $attempt++) {
            WechatNotificationService::dispatchLog((int)$retry['log_id']);
            $row = OaNotificationLog::find($retry['log_id']);
            $check((int)$row->send_status === ($attempt < 4 ? OaNotificationLog::STATUS_PENDING : OaNotificationLog::STATUS_FAILED), '失败重试次数或终态错误');
            OaNotificationLog::where('id', $row->id)->update(['next_retry_time' => 0]);
        }
        $check(WechatNotificationService::retryLog((int)$retry['log_id']), '失败通知应支持人工重试');
        OaFollower::where('user_id', 1)->update(['follow_status' => 0]);
        $before = Db::table('test_delivery')->count();
        WechatNotificationService::dispatchLog((int)$retry['log_id']);
        $check(Db::table('test_delivery')->count() === $before, '发送前取消关注必须停止推送');
        $check(!WechatNotificationService::retryLog((int)$retry['log_id']), '取消关注后不得人工强发');
        Admin::create(['id' => 1, 'account' => 'concurrent_admin', 'password' => 'test', 'name' => '并发管理员', 'user_id' => 1, 'disable' => 0]);
        Staff::where('id', $staff->id)->update(['admin_id' => 1, 'user_id' => 1]);
        $staffOrderId = (int)OrderItem::where('staff_id', $staff->id)->value('order_id');
        $staffLog = new OaNotificationLog(['user_id' => 1, 'business_type' => 'order', 'business_id' => $staffOrderId, 'audience' => 'staff']);
        $check(WechatNotificationService::canReceiveBusiness($staffLog), '有效关联服务人员应可接收所属订单事项');
        Admin::where('id', 1)->update(['disable' => 1]);
        $check(!WechatNotificationService::canReceiveBusiness($staffLog), '禁用后台账号后不得推送服务人员事项');
        ConfigService::set('oa_setting', 'app_id', 'wx1111111111111111');
        ConfigService::set('oa_setting', 'app_secret', 'isolated-test');
        ConfigService::set('mnp_setting', 'app_id', 'wx2222222222222222');
        ConfigService::set('oa_setting', 'invitation_verified_apps', 'wx1111111111111111:wx2222222222222222');
        ConfigService::set('oa_setting', 'invitation_enabled', 1);
        foreach ([30001, 30002] as $uid) {
            \app\common\model\user\User::create(['id' => $uid, 'sn' => $uid, 'account' => 'invite_' . $uid, 'nickname' => '并发邀请用户']);
        }
        OaFollower::create(['openid' => 'invitation_concurrent', 'follow_status' => 1]);
        $invitations = $parallel('invitation_create', [0, 0]);
        $check($invitations[0] === $invitations[1], '并发生成应复用同一邀请');
        $consumed = $parallel('invitation_confirm', [30001, 30002]);
        $check(count(array_filter($consumed)) === 1, '同一邀请只能被一个账号并发消费');
        $winner = (int)OaFollower::where('openid', 'invitation_concurrent')->value('user_id');
        $check($action('invitation_confirm', $winner), '获胜账号重复确认幂等成功');
        echo 'OK - 并发支付、账本与通知队列：' . $checks . " 项\n";
    }
} finally {
    foreach ($children as [$process, $pipes]) {
        if (is_resource($process)) { proc_terminate($process); proc_close($process); }
        foreach ($pipes as $pipe) { if (is_resource($pipe)) { fclose($pipe); } }
    }
    if (!$worker) { $pdo->exec('DROP DATABASE IF EXISTS `' . $database . '`'); }
}
