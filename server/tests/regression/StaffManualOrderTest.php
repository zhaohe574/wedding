<?php

declare(strict_types=1);

use app\adminapi\http\middleware\AuthMiddleware;
use app\adminapi\logic\financial\FinancialReportLogic;
use app\adminapi\logic\order\OrderCustomerLogic;
use app\common\command\Crontab;
use app\common\model\aftersale\Complaint;
use app\common\model\staff\Staff;
use app\common\service\RedisLockService;
use app\common\service\AccountBindingService;
use app\common\service\BusinessNotificationService;
use app\common\service\StationNotificationService;
use app\common\service\StaffService;
use app\common\service\wechat\WechatOaBindingService;
use app\common\model\notification\Notification;
use app\common\model\wechat\OaBindSession;
use app\common\model\wechat\OaFollower;
use app\common\model\auth\Admin;
use app\common\model\user\User;
use think\facade\Db;
use app\common\service\StaffManualOrderService as Manual;
use app\common\service\OrderReceiptService as Receipt;
use app\common\service\ConfigService;
use app\common\model\order\Order;
use app\common\model\order\Payment;

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
$database = 'wedding_binding_' . getmypid() . '_' . time();
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



    $reject = static function (callable $action, string $message) use ($check): void {
        $thrown = false;
        try { $action(); } catch (Throwable $e) { $thrown = true; }
        $check($thrown, $message);
    };
    foreach ([31001, 31002, 31003] as $id) User::create(['id' => $id, 'sn' => $id, 'account' => 'manual_' . $id, 'nickname' => '录单测试', 'mobile' => '138000' . $id]);
    $admin = Admin::create(['account' => 'manual_staff', 'password' => 'test', 'user_id' => 31001, 'disable' => 0]);
    $staff = Staff::create(['sn' => 'MANUAL_TEST', 'name' => '本人录单测试', 'status' => 1, 'user_id' => 31001, 'admin_id' => $admin->id]);
    $reviewer = Admin::create(['account' => 'manual_reviewer', 'password' => 'test', 'user_id' => 31003, 'root' => 1]);
    $region = ['province_code' => '330000', 'province_name' => '浙江省', 'city_code' => '330100', 'city_name' => '杭州市', 'district_code' => '330102', 'district_name' => '上城区'];
    Db::name('service_city_pool')->insert(array_intersect_key($region, array_flip(['province_code', 'province_name', 'city_code', 'city_name'])) + ['status' => 1]);
    $packageId = (int)Db::name('service_package')->insertGetId(['staff_id' => $staff->id, 'name' => '本人套餐', 'price' => 2000, 'is_show' => 1]);
    Db::name('service_package_region_price')->insert($region + ['package_id' => $packageId, 'staff_id' => $staff->id, 'region_level' => 3, 'price' => 1000]);
    ConfigService::set('order_payment', 'enable_deposit_mode', 1);
    ConfigService::set('order_payment', 'deposit_type', 'ratio');
    ConfigService::set('order_payment', 'deposit_value', 30);
    $params = $region + ['main_package_id' => $packageId, 'service_date' => date('Y-m-d', strtotime('+30 days')),
        'contact_name' => '临时联系人', 'contact_mobile' => '13800031002', 'service_address' => '测试酒店',
        'payment_entry_mode' => 'offline_voucher', 'submit_key' => 'manual_order_submit_001'];
    $quote = Manual::preview(31001, $params);
    $check((float)$quote['pay_amount'] === 1000.0, '报价使用地区价格');
    $options = Manual::options(31001, $params);
    $check(count($options['packages']) === 1 && $options['staff_id'] === (int)$staff->id, '只返回本人套餐');
    $params['quote_hash'] = $quote['quote_hash'];
    $params['main_staff_id'] = 99999;
    $params['discount_amount'] = 999;
    $params['pay_amount'] = 1;
    $params['user_id'] = 31002;
    $params['butler_staff_id'] = 99999;
    $result = Manual::create(31001, $params);
    $orderId = (int)$result['order_id'];
    $order = Order::find($orderId);
    $check((int)$order->user_id === 0 && (float)$order->pay_amount === 1000.0, '不接受伪造客户编号和价格');
    $check((int)$order->source === Order::SOURCE_STAFF && (int)$order->creator_user_id === 31001, '记录服务人员来源和创建人');
    $check(Db::name('order_item')->where('order_id', $orderId)->count() === 1, '不能添加协作人员');
    $check(Db::name('schedule')->where('order_id', $orderId)->count() === 0 && (float)$order->paid_amount === 0.0, '建单不到账、不锁档');
    $again = Manual::create(31001, $params);
    $check((int)$again['order_id'] === $orderId, '重复建单返回同一订单');
    $reject(fn () => Manual::create(31001, array_replace($params, ['contact_name' => '篡改'])), '同一标识不能修改订单');
    $reject(fn () => Manual::preview(31002, $params), '普通客户不能录单');
    Admin::where('id', $admin->id)->update(['disable' => 1]);
    $reject(fn () => Manual::preview(31001, $params), '停用后台账号不能录单');
    Admin::where('id', $admin->id)->update(['disable' => 0]);
    $changed = Manual::create(31001, array_replace($params, ['submit_key' => 'manual_order_changed_001', 'quote_hash' => 'old']));
    $check(!empty($changed['quote_changed']) && !isset($changed['order_id']), '报价不一致只返回新摘要');
    $reject(fn () => Manual::create(31001, array_replace($params, ['submit_key' => 'manual_bad_customer_001', 'selection_token' => 'expired'])), '失效客户凭据不能建单');
    $reject(fn () => Manual::create(31001, array_replace($params, ['submit_key' => 'manual_online_temp_001', 'payment_entry_mode' => 'online_pending'])), '临时联系人不能线上支付');
    $customers = Manual::customers(31001, '13800031002');
    $check(count($customers) === 1 && $customers[0]['mobile'] === '138****1002' && !isset($customers[0]['id']), '客户仅展示昵称脱敏手机号和选择凭据');
    $reject(fn () => Manual::customers(31001, '138'), '不能用部分手机号枚举客户');
    $badPackage = (int)Db::name('service_package')->insertGetId(['staff_id' => 99999, 'name' => '他人套餐', 'is_show' => 1]);
    $reject(fn () => Manual::preview(31001, array_replace($params, ['main_package_id' => $badPackage])), '不能选择他人套餐');
    Db::name('service_package')->where('id', $packageId)->update(['is_show' => 0]);
    $reject(fn () => Manual::preview(31001, $params), '下架套餐不可预览');
    Db::name('service_package')->where('id', $packageId)->update(['is_show' => 1]);

    $receiptParams = ['order_id' => $orderId, 'submit_key' => 'receipt_full_submit_001', 'pay_type' => 3, 'voucher' => 'uploads/test-receipt.png', 'collection_owner' => 2, 'amount' => 1];
    $request = Receipt::submit(31001, $receiptParams);
    $check(Receipt::pending($orderId) && (float)Db::name('order_receipt_request')->where('id', $request['id'])->value('amount') === 1000.0, '收款金额由阶段决定，忽略伪造金额');
    $check((int)Receipt::submit(31001, $receiptParams)['id'] === (int)$request['id'], '重复收款申请返回同一记录');
    $reject(fn () => Receipt::submit(31001, array_replace($receiptParams, ['submit_key' => 'receipt_duplicate_002'])), '一单只允许一条待审核');
    $reject(fn () => Receipt::audit($orderId, $request['id'], $admin->id, true, ''), '不能审核本人收款');
    $check(!\app\common\logic\OrderPayLogic::getPayableOrder(0, $orderId, true), '待审核禁止在线支付');
    $check(!\app\adminapi\logic\order\OrderLogic::confirmOfflinePay(['id' => $orderId, 'admin_id' => $reviewer->id, 'voucher' => 'uploads/test.png', 'collection_owner' => 1]), '待审期间不得后台直接确认收款');
    $reject(function () use ($orderId) { $order = Order::find($orderId); $order->pay_amount = 999; $order->save(); }, '待审期间不能调整应收');
    [$cancelled] = Order::cancelOrder($orderId, $reviewer->id);
    $check(!$cancelled, '待审不能取消并遗失已收款事项');
    Receipt::audit($orderId, $request['id'], $reviewer->id, false, '凭证不清晰');
    $check(!Receipt::pending($orderId) && count(Receipt::history($orderId)) === 1, '驳回释放待审限制并保留历史');
    $request2 = Receipt::submit(31001, array_replace($receiptParams, ['submit_key' => 'receipt_resubmit_002']));
    $approved = Receipt::audit($orderId, $request2['id'], $reviewer->id, true, '核实到账');
    $check((int)$approved['status'] === 1 && (float)Order::find($orderId)->paid_amount === 1000.0, '全款审核通过才到账');
    Receipt::audit($orderId, $request2['id'], $reviewer->id, true, '');
    $check(Payment::where('order_id', $orderId)->count() === 1, '重复审核不重复创建支付');
    $check(Db::name('financial_flow')->where('order_id', $orderId)->count() === 0, '人员代收不计为平台收入');
    $check(Db::name('schedule')->where('order_id', $orderId)->count() > 0, '首次付款审核通过才锁档');
    $check(count(Receipt::history($orderId)) === 2, '重新提交不覆盖原申请');
    $reject(fn () => Receipt::submit(31001, array_replace($receiptParams, ['submit_key' => 'receipt_overpay_002'])), '已全款不能重复收款');

    $params2 = array_replace($params, ['service_date' => date('Y-m-d', strtotime('+31 days')), 'submit_key' => 'manual_deposit_order_001', 'selection_token' => $customers[0]['selection_token']]);
    $quote2 = Manual::preview(31001, $params2);
    $params2['quote_hash'] = $quote2['quote_hash'];
    $params2['receipt'] = ['pay_type' => 1, 'voucher' => 'uploads/deposit.png', 'collection_owner' => 1];
    $result2 = Manual::create(31001, $params2);
    $id2 = (int)$result2['order_id'];
    $check((int)Order::find($id2)->user_id === 31002, '选择凭据明确关联客户');
    $deposit = Receipt::audit($id2, (int)$result2['receipt']['id'], $reviewer->id, true, '');
    $check((float)Order::find($id2)->paid_amount === (float)$quote2['deposit_amount'], '线下定金审核支持阶段实收');
    Order::where('id', $id2)->update(['order_status' => Order::STATUS_PENDING_PAY, 'complete_time' => time()]);
    $balance = Receipt::submit(31001, ['order_id' => $id2, 'submit_key' => 'receipt_balance_submit_001', 'pay_type' => 2, 'voucher' => 'uploads/balance.png', 'collection_owner' => 1]);
    Receipt::audit($id2, $balance['id'], $reviewer->id, true, '');
    $check((float)Order::find($id2)->paid_amount === 1000.0 && (int)Order::find($id2)->order_status === Order::STATUS_COMPLETED, '尾款审核完成支付状态');
    $check((float)Db::name('financial_flow')->where('order_id', $id2)->sum('amount') === 1000.0, '平台收款各阶段账本合计正确');
    $parallel = static function (array $payload) use ($database, $check): array {
        $processes = [];
        for ($i = 0; $i < 2; $i++) {
            $process = proc_open([PHP_BINARY, __DIR__ . '/StaffManualOrderWorker.php', $database,
                base64_encode(json_encode($payload, JSON_THROW_ON_ERROR))], [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
            $processes[] = [$process, $pipes];
        }
        $results = [];
        foreach ($processes as [$process, $pipes]) {
            $output = stream_get_contents($pipes[1]);
            $error = stream_get_contents($pipes[2]);
            fclose($pipes[1]); fclose($pipes[2]);
            $check(proc_close($process) === 0, '并发子进程成功：' . $output . $error);
            preg_match('/\{[^\n]+\}\s*$/', $output, $matches);
            $results[] = json_decode($matches[0] ?? '', true, 512, JSON_THROW_ON_ERROR);
        }
        return $results;
    };
    $params3 = array_replace($params, ['service_date' => date('Y-m-d', strtotime('+32 days')), 'submit_key' => 'manual_concurrent_001']);
    $params3['quote_hash'] = Manual::preview(31001, $params3)['quote_hash'];
    $parallelOrders = $parallel(['action' => 'create', 'user_id' => 31001, 'params' => $params3]);
    $id3 = (int)$parallelOrders[0]['order_id'];
    $check($id3 === (int)$parallelOrders[1]['order_id'], '并发重复建单只产生一个订单');
    $r3 = Receipt::submit(31001, ['order_id' => $id3, 'submit_key' => 'manual_concurrent_receipt', 'pay_type' => 3, 'voucher' => 'uploads/concurrent.png', 'collection_owner' => 1]);
    $parallelAudits = $parallel(['action' => 'audit', 'order_id' => $id3, 'receipt_id' => $r3['id'], 'admin_id' => (int)$reviewer->id]);
    $check((int)$parallelAudits[0]['payment_id'] === (int)$parallelAudits[1]['payment_id'], '并发审核返回同一支付记录');
    $check((float)Db::name('financial_flow')->where('order_id', $id3)->sum('amount') === 1000.0, '并发审核仅记账一次');

    $conflictParams = array_replace($params, ['service_date' => date('Y-m-d', strtotime('+33 days')), 'submit_key' => 'manual_conflict_order_001']);
    $conflictParams['quote_hash'] = Manual::preview(31001, $conflictParams)['quote_hash'];
    $first = Manual::create(31001, $conflictParams);
    $second = Manual::create(31001, array_replace($conflictParams, ['submit_key' => 'manual_conflict_order_002']));
    $firstReceipt = Receipt::submit(31001, ['order_id' => $first['order_id'], 'submit_key' => 'manual_conflict_receipt_001', 'pay_type' => 3, 'voucher' => 'uploads/first.png', 'collection_owner' => 1]);
    $secondReceipt = Receipt::submit(31001, ['order_id' => $second['order_id'], 'submit_key' => 'manual_conflict_receipt_002', 'pay_type' => 3, 'voucher' => 'uploads/second.png', 'collection_owner' => 1]);
    Receipt::audit($first['order_id'], $firstReceipt['id'], $reviewer->id, true, '');
    $reject(fn () => Receipt::audit($second['order_id'], $secondReceipt['id'], $reviewer->id, true, ''), '档期冲突禁止审核通过');
    $check(Receipt::pending($second['order_id']) && (float)Order::find($second['order_id'])->paid_amount === 0.0, '冲突时保留待审核并回滚实收');
    $check(Payment::where('order_id', $second['order_id'])->count() === 0 && Db::name('financial_flow')->where('order_id', $second['order_id'])->count() === 0, '冲突不生成支付和账本');
    $check((string)Db::name('order_receipt_request')->where('id', $secondReceipt['id'])->value('reason') !== '', '档期冲突记录待处理原因');
    $check((int)Order::find($second['order_id'])->order_status === Order::STATUS_PENDING_PAY, '冲突待审核订单不会自动取消');

    $rollbackParams = array_replace($params, ['service_date' => date('Y-m-d', strtotime('+34 days')), 'submit_key' => 'manual_rollback_order_001']);
    $rollbackParams['quote_hash'] = Manual::preview(31001, $rollbackParams)['quote_hash'];
    $beforeOrders = Order::count();
    $beforeEvents = Db::name('notification_event')->count();
    $reject(fn () => Manual::create(31001, $rollbackParams + ['receipt' => ['pay_type' => 1, 'voucher' => '', 'collection_owner' => 1]]), '建单附带无效凭证整体拒绝');
    $check(Order::count() === $beforeOrders && Db::name('notification_event')->count() === $beforeEvents, '建单失败回滚订单和业务通知事件');
    $check(str_contains(\app\common\service\BindingPrivacyService::redact('{"selection_token":"secret"}'), '[已脱敏]'), '日志脱敏客户选择凭据');
    $legacyParams = array_replace($params, ['service_date' => date('Y-m-d', strtotime('+35 days')),
        'main_staff_id' => (int)$staff->id, 'butler_staff_id' => 0, 'bind_mode' => 'temp', 'admin_id' => (int)$reviewer->id,
        'payment_entry_mode' => 'offline_paid', 'discount_amount' => 50, 'collection_owner' => 1, 'voucher' => 'uploads/admin.png']);
    $check(\app\adminapi\logic\order\OrderLogic::addOffline($legacyParams), '后台原手动建单继续成功');
    $legacyOrder = Order::where('source', Order::SOURCE_ADMIN)->order('id desc')->find();
    $check($legacyOrder && (float)$legacyOrder->paid_amount === 950.0, '后台原改价和直接确认到账行为保持兼容');
    $check(Db::name('order_receipt_request')->where('order_id', $legacyOrder->id)->count() === 0, '后台原流程不生成重复审核申请');
    $beforeMigration = Db::name('order')->order('id')->column('source,pay_status,paid_amount', 'id');
    Db::execute('DROP TABLE qa_order_receipt_request');
    Db::execute('ALTER TABLE qa_order DROP INDEX uk_staff_submit, DROP COLUMN creator_user_id, DROP COLUMN staff_submit_key, DROP COLUMN staff_submit_hash');
    foreach ([false, true, true] as $apply) {
        $args = [PHP_BINARY, __DIR__ . '/../../database/migrations/20260915_staff_manual_order.php', '--test-database=' . $database];
        if ($apply) $args[] = '--apply';
        $process = proc_open($args, [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
        $output = stream_get_contents($pipes[1]) . stream_get_contents($pipes[2]);
        fclose($pipes[1]); fclose($pipes[2]);
        $check(proc_close($process) === 0, '录单迁移支持只读检查及重复执行：' . $output);
        if (!$apply) $check(!in_array('creator_user_id', array_column(Db::query('SHOW COLUMNS FROM qa_order'), 'Field'), true), '旧库只读预检不修改结构');
    }
    $check($beforeMigration === Db::name('order')->order('id')->column('source,pay_status,paid_amount', 'id'), '增量迁移不修改历史来源、支付状态和实收');
    echo "OK - 本人录单与独立收款审核：{$checks} 项\n";
} finally {
    $pdo->exec('DROP DATABASE IF EXISTS `' . $database . '`');
}
