<?php

declare(strict_types=1);

use app\common\model\order\Order;
use app\common\model\order\Payment;
use app\common\model\order\Refund;
use app\common\model\order\RefundItem;
use app\common\service\ConfigService;
use app\common\service\OrderRefundService;
use app\common\service\wechat\WechatOaBindingService;
use app\common\model\wechat\OaBindSession;
use app\common\model\user\User;
use app\common\model\dynamic\ActivityRegistration;
use app\common\model\dynamic\ActivityPayment;
use app\common\model\dynamic\ActivityRefund;
use app\common\service\ActivityRegistrationService;
use app\common\service\wechat\AdminBindingService;
use app\common\model\auth\Admin;
use think\facade\Db;

require __DIR__ . '/../../vendor/autoload.php';
require __DIR__ . '/../../vendor/topthink/framework/src/helper.php';

// 仅使用显式指定的本机测试端口，不加载项目真实环境配置。
$port = (int)getenv('WEDDING_TEST_MYSQL_PORT');
if ($port <= 0 || $port === 3306) {
    throw new RuntimeException('请设置独立本机测试实例端口 WEDDING_TEST_MYSQL_PORT，禁止使用 3306');
}
$password = (string)getenv('WEDDING_TEST_MYSQL_PASSWORD');
$pdo = new PDO('mysql:host=127.0.0.1;port=' . $port . ';charset=utf8mb4', 'root', $password,
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$database = 'wedding_test_' . getmypid() . '_' . time();
$pdo->exec('CREATE DATABASE `' . $database . '` CHARACTER SET utf8mb4');
$assertions = 0;
$check = static function (bool $condition, string $message) use (&$assertions): void {
    $assertions++;
    if (!$condition) {
        throw new RuntimeException($message);
    }
};
try {
    $pdo->exec('USE `' . $database . '`');
    $pdo->exec(str_replace('`la_', '`qa_', file_get_contents(__DIR__ . '/../../public/install/db/like.sql')));
    $app = new think\App(dirname(__DIR__, 2) . DIRECTORY_SEPARATOR);
    $databaseConfig = ['default' => 'mysql', 'auto_timestamp' => true,
        'datetime_format' => 'Y-m-d H:i:s', 'connections' => ['mysql' => [
        'type' => 'mysql', 'hostname' => '127.0.0.1', 'hostport' => $port,
        'database' => $database, 'username' => 'root', 'password' => $password,
        'charset' => 'utf8mb4', 'prefix' => 'qa_', 'debug' => false,
    ]]];
    $app->config->set($databaseConfig, 'database');
    $app->config->set(['default' => 'file', 'channels' => ['file' => ['type' => 'File', 'path' => sys_get_temp_dir() . '/wedding-log-' . $database . '/']]], 'log');
    $cacheConfig = ['type' => 'File', 'path' => sys_get_temp_dir() . '/wedding-cache-' . $database . '/'];
    $app->config->set(['default' => 'file', 'stores' => ['file' => $cacheConfig, 'config' => $cacheConfig]], 'cache');
    (new think\service\ModelService($app))->boot();
    require_once __DIR__ . '/../../app/common.php';

    $check(!\app\common\logic\NoticeLogic::noticeByScene(['scene_id' => 999]), '业务短信场景必须在发送前拒绝');
    $check(\app\common\logic\NoticeLogic::getError() === '短信仅用于验证码', '业务短信拒绝原因必须明确');
    $jobs = Db::name('dev_crontab')->where('status', 1)->column('command');
    foreach (['query_payments', 'query_refund', 'send_oa_notifications', 'auto_staff_settlement'] as $command) {
        $check(in_array($command, $jobs, true), '安装后必须自动调度关键任务：' . $command);
    }
    foreach (['service_package', 'staff_package'] as $table) {
        $columns = $pdo->query('SHOW COLUMNS FROM qa_' . $table)->fetchAll(PDO::FETCH_COLUMN);
        $check(!array_intersect(['booking_type', 'slot_prices', 'custom_slot_prices'], $columns), '安装基线不得保留分场次字段');
    }
    foreach ([
        'user' => ['order_update', 'change_result', 'ticket_update', 'questionnaire_update', 'activity_update',
            'waitlist_release', 'waitlist_expired'],
        'staff' => ['settlement_update', 'staff_order', 'staff_schedule', 'staff_pause', 'staff_refund', 'staff_aftersale', 'staff_change', 'staff_internal'],
    ] as $audience => $scenes) {
        $installed = Db::name('wechat_oa_notification_template')->where('audience', $audience)->column('scene');
        $check(!array_diff($scenes, $installed), '安装基线缺少业务通知模板：' . $audience);
    }
    ConfigService::set('oa_setting', 'app_id', 'wx_local_callback_test');
    ConfigService::set('oa_setting', 'token', 'local_callback_token');
    ConfigService::set('oa_setting', 'encoding_aes_key', rtrim(base64_encode(random_bytes(32)), '='));
    $oa = new \EasyWeChat\OfficialAccount\Application(\app\common\service\wechat\WeChatConfigService::getOaConfig());
    $encrypted = \EasyWeChat\Kernel\Support\Xml::parse($oa->getEncryptor()->encrypt('<xml><Event>subscribe</Event></xml>'));
    $check($oa->getEncryptor()->decrypt($encrypted['Encrypt'], $encrypted['MsgSignature'], $encrypted['Nonce'], $encrypted['TimeStamp'])
        === '<xml><Event>subscribe</Event></xml>', '服务号配置必须支持加密回调验签解密');
    try {
        $oa->getEncryptor()->decrypt($encrypted['Encrypt'], str_repeat('0', 40), $encrypted['Nonce'], $encrypted['TimeStamp']);
        $check(false, '伪造服务号加密回调签名必须拒绝');
    } catch (\EasyWeChat\Kernel\Exceptions\RuntimeException $error) {
        $check($error->getCode() === \EasyWeChat\Kernel\Encryptor::ERROR_INVALID_SIGNATURE, '服务号签名校验异常');
    }

    foreach ([1, 2, 3, 4, 5, 6, 0, 99] as $terminal) {
        $check((new \app\api\validate\LoginAccountValidate())->only(['terminal'])->check(['terminal' => $terminal]) === ($terminal === 1),
            '登录终端必须仅允许微信小程序：' . $terminal);
        $check((new \app\api\validate\RegisterValidate())->only(['channel'])->check(['channel' => $terminal]) === ($terminal === 1),
            '注册来源必须仅允许微信小程序：' . $terminal);
    }
    $check(!(new \app\api\validate\LoginAccountValidate())->only(['terminal'])->check([]), '缺少登录终端必须拒绝');
    $check(!(new \app\api\validate\RegisterValidate())->only(['channel'])->check([]), '缺少注册来源必须拒绝');
    $check(!(new \app\adminapi\validate\crm\CustomerValidate())->only(['source_channel'])->check(['source_channel' => 2]),
        '后台客户不得再使用 H5 来源');
    $check(!Order::isUserSideOrderSource(2) && Order::isUserSideOrderSource(1), '用户订单来源只接受微信小程序');
    $check(array_column(Order::getPayWayOptions(), 'value') === [Order::PAY_WAY_WECHAT, Order::PAY_WAY_OFFLINE],
        '订单支付选项仅保留微信与线下');
    $check((int)Db::name('system_menu')->whereLike('perms', '%web_page_setting/%')->count() === 0, '安装基线不得包含 H5 菜单');
    foreach (Db::name('wechat_oa_notification_template')->column('page_path') as $pagePath) {
        $check(is_file(dirname(__DIR__, 3) . '/uniapp/src/' . explode('?', $pagePath)[0] . '.vue'),
            '服务号模板必须跳转现有小程序页面：' . $pagePath);
    }

    $check((int)$pdo->query('SELECT COUNT(*) FROM qa_payment')->fetchColumn() === 0, '全新安装不得含支付业务数据');
    ConfigService::set('order_payment', 'offline_collection_enabled', 0);
    $order = Order::create([
        'order_sn' => 'TEST_FIRST_PAY', 'user_id' => 1, 'source' => Order::SOURCE_MINIAPP,
        'order_status' => Order::STATUS_PENDING_PAY, 'pay_status' => Order::PAY_STATUS_UNPAID,
        'total_amount' => 100, 'pay_amount' => 100, 'paid_amount' => 0,
        'deposit_amount' => 0, 'balance_amount' => 100,
        'payment_channel' => Order::PAYMENT_CHANNEL_ONLINE,
    ]);
    Db::startTrans();
    $payment = Payment::createPayment((int)$order->id, (string)$order->order_sn, 1,
        Payment::TYPE_FULL, Payment::WAY_WECHAT, 100);
    $reused = Payment::createPayment((int)$order->id, (string)$order->order_sn, 1,
        Payment::TYPE_FULL, Payment::WAY_WECHAT, 100);
    Db::commit();
    $check((int)$payment->id === (int)$reused->id, '重复付款必须复用当前待确认流水');
    ConfigService::set('order_payment', 'offline_collection_enabled', 1);
    $check($order->applyOfflineCollectionPaymentChannelPolicy(false) === Order::PAYMENT_CHANNEL_ONLINE,
        '切换配置不得改变已创建微信付款流水的渠道');
    $payment->pay_status = Payment::STATUS_FAILED;
    $payment->closed_time = time();
    $payment->save();
    $check($order->applyOfflineCollectionPaymentChannelPolicy(false) === Order::PAYMENT_CHANNEL_OFFLINE,
        '已确认关闭的流水不再冻结付款渠道');
    $order->pay_voucher = 'uploads/test-voucher.png';
    $order->pay_voucher_status = Order::VOUCHER_STATUS_PENDING;
    ConfigService::set('order_payment', 'offline_collection_enabled', 0);
    $check($order->applyOfflineCollectionPaymentChannelPolicy(false) === Order::PAYMENT_CHANNEL_OFFLINE,
        '审核中的线下凭证不得被配置变化切换渠道');
    $check(!$order->shouldAutoCloseExpiredBalancePayment(), '尾款应收不得按超时完成订单');
    $order->pay_voucher_status = Order::VOUCHER_STATUS_PENDING;
    $order->pay_voucher = '';
    $order->order_status = Order::STATUS_PAID;
    $order->pay_status = Order::PAY_STATUS_PAID;
    $order->paid_amount = 100;
    $order->save();
    $payment->pay_way = Payment::WAY_OFFLINE;
    $payment->pay_status = Payment::STATUS_PAID;
    $payment->pay_time = time();
    $payment->transaction_id = 'OFFLINE_TEST';
    $payment->save();
    $refund = Refund::create([
        'refund_sn' => 'TEST_PARTIAL_REFUND', 'order_id' => $order->id, 'user_id' => 1,
        'refund_type' => Refund::TYPE_ADMIN, 'refund_amount' => 30,
        'refund_status' => Refund::STATUS_APPROVED,
        'source_order_status' => Order::STATUS_PAID, 'source_pay_status' => Order::PAY_STATUS_PAID,
    ]);
    [$ok, $message] = Db::transaction(static fn () => OrderRefundService::executeApprovedRefund($refund));
    $check($ok, '部分退款拆分失败：' . $message);
    $check(OrderRefundService::getRefundableAmount((int)$order->id) === 70.0, '在途退款必须占用原流水额度');
    [$ok] = Db::transaction(static fn () => OrderRefundService::confirmOfflineRefund($refund, 'REFUND_TEST', 'uploads/test.png'));
    $check($ok, '线下部分退款确认失败');
    [$ok] = Db::transaction(static fn () => OrderRefundService::confirmOfflineRefund($refund, 'REFUND_TEST', 'uploads/test.png'));
    $check($ok && (float)Payment::find($payment->id)->refund_amount === 30.0, '重复退款确认不得重复出账');
    $check((int)Order::find($order->id)->pay_status === Order::PAY_STATUS_PARTIAL_REFUND, '部分退款应保留剩余实收');
    $check(RefundItem::where('refund_id', $refund->id)->count() === 1, '重复处理不得重复拆分退款子项');
    $lateOrder = Order::create([
        'order_sn' => 'TEST_CLOSED_ORDER', 'user_id' => 1, 'source' => Order::SOURCE_MINIAPP,
        'order_status' => Order::STATUS_CANCELLED, 'pay_status' => Order::PAY_STATUS_UNPAID,
        'total_amount' => 25, 'pay_amount' => 25, 'paid_amount' => 0,
    ]);
    $latePayment = Payment::createPayment((int)$lateOrder->id, (string)$lateOrder->order_sn,
        1, Payment::TYPE_FULL, Payment::WAY_OFFLINE, 25);
    [$ok, $message] = Db::transaction(static fn () => Payment::paySuccess((string)$latePayment->payment_sn, 'LATE_TEST'));
    $check($ok, '迟到实收登记失败：' . $message);
    $compensation = Refund::where('payment_id', $latePayment->id)->where('is_compensation', 1)->find();
    $check($compensation && (float)$compensation->refund_amount === 25.0, '迟到付款必须按原流水登记补偿退款');
    $check(OrderRefundService::getRefundableAmount((int)$lateOrder->id) === 0.0, '补偿实收不得进入普通退款额度');
    Db::transaction(static fn () => Payment::paySuccess((string)$latePayment->payment_sn, 'LATE_TEST'));
    $check(Refund::where('payment_id', $latePayment->id)->count() === 1, '重复迟到通知不得重复登记补偿退款');
    Db::transaction(static fn () => OrderRefundService::executeApprovedRefund($compensation));
    Db::transaction(static fn () => OrderRefundService::confirmOfflineRefund($compensation, 'LATE_REFUND', 'uploads/test.png'));
    $check((int)Order::find($lateOrder->id)->order_status === Order::STATUS_CANCELLED, '补偿退款完成不得恢复已取消订单');
    ConfigService::set('order_payment', 'offline_collection_enabled', 1);
    foreach ([Payment::COLLECTION_PLATFORM, Payment::COLLECTION_STAFF] as $owner) {
        $offlineOrder = Order::create([
            'order_sn' => 'OFFLINE_RECEIPT_' . $owner, 'user_id' => 1,
            'source' => Order::SOURCE_ADMIN, 'order_status' => Order::STATUS_PENDING_PAY,
            'pay_status' => Order::PAY_STATUS_UNPAID, 'total_amount' => 100, 'pay_amount' => 100,
            'paid_amount' => 0, 'deposit_mode_enabled' => 0, 'balance_amount' => 100,
            'payment_channel' => Order::PAYMENT_CHANNEL_OFFLINE,
        ]);
        $params = ['id' => (int)$offlineOrder->id, 'admin_id' => 1, 'pay_type' => Payment::TYPE_FULL,
            'pay_amount' => 99, 'collection_owner' => $owner, 'voucher' => 'uploads/receipt.png'];
        $check(!\app\adminapi\logic\order\OrderLogic::confirmOfflinePay($params), '少收不得自动降低应收');
        $check((float)Order::find($offlineOrder->id)->pay_amount === 100.0, '拒绝少收后订单报价必须不变');
        $params['pay_amount'] = 101;
        $check(!\app\adminapi\logic\order\OrderLogic::confirmOfflinePay($params), '不得超额收款');
        $params['pay_amount'] = 100;
        $params['voucher'] = '';
        $check(!\app\adminapi\logic\order\OrderLogic::confirmOfflinePay($params), '确认线下到账必须提供凭证');
        $params['voucher'] = 'uploads/receipt.png';
        $check(\app\adminapi\logic\order\OrderLogic::confirmOfflinePay($params),
            '足额确认线下到账失败：' . \app\adminapi\logic\order\OrderLogic::getError());
        $receipt = Payment::where('order_id', $offlineOrder->id)->find();
        $check((int)$receipt->collection_owner === $owner && str_contains($receipt->pay_voucher, 'receipt.png'),
            '每笔付款必须保存收款归属及凭证');
        $check(!\app\adminapi\logic\order\OrderLogic::confirmOfflinePay($params), '重复确认不得重复到账');
        $platformAmount = $owner === Payment::COLLECTION_PLATFORM ? 100.0 : 0.0;
        $check(\app\common\service\StaffSettlementService::getOrderPlatformPaidNetAmount((int)$offlineOrder->id) === $platformAmount,
            '平台线下收款与人员代收必须区分结算口径');
        $check((float)Db::name('financial_flow')->where('order_id', $offlineOrder->id)->sum('amount') === $platformAmount,
            '平台资金流水不得计入人员代收');
        $offlineRefund = Refund::create(['refund_sn' => 'OFFLINE_REFUND_' . $owner,
            'order_id' => $offlineOrder->id, 'user_id' => 1, 'refund_amount' => 25,
            'refund_status' => Refund::STATUS_APPROVED, 'refund_type' => Refund::TYPE_ADMIN,
            'source_order_status' => Order::STATUS_PENDING_SERVICE, 'source_pay_status' => Order::PAY_STATUS_PAID]);
        [$ok, $message] = Db::transaction(static fn () => OrderRefundService::executeApprovedRefund($offlineRefund));
        $check($ok, '线下退款拆分失败：' . $message);
        [$ok] = Db::transaction(static fn () => OrderRefundService::confirmOfflineRefund($offlineRefund, 'REFUND_' . $owner, 'uploads/refund.png'));
        $check($ok, '线下退款应按原流水完成');
        $check(\app\common\service\StaffSettlementService::getOrderPlatformPaidNetAmount((int)$offlineOrder->id)
            === ($owner === Payment::COLLECTION_PLATFORM ? 75.0 : 0.0), '退款后按归属扣减平台实收');
        $check((float)Db::name('financial_flow')->where('order_id', $offlineOrder->id)
            ->where('biz_type', \app\common\model\financial\FinancialFlow::BIZ_TYPE_ORDER_REFUND)->sum('amount')
            === ($owner === Payment::COLLECTION_PLATFORM ? 25.0 : 0.0), '人员代收退款不得记平台出账');
    }
    $user = User::create(['sn' => 100001, 'account' => 'bind_test', 'nickname' => '绑定测试']);
    $entry = WechatOaBindingService::createEntry((int)$user->id);
    $check(!$entry['bound'] && !$entry['can_receive'], '首次进入不得误判绑定');
    $event = ['FromUserName' => 'oa_test', 'CreateTime' => time(), 'Event' => 'subscribe'];
    WechatOaBindingService::handleEvent($event);
    $reply = WechatOaBindingService::handleText(['FromUserName' => 'oa_test', 'Content' => '绑定 ' . $entry['binding_code']]);
    $check(str_contains((string)$reply, '已收到'), '服务号文字绑定码应能识别');
    $status = WechatOaBindingService::getStatus((int)$user->id);
    $check(!$status['bound'] && $status['follow_status'] === 'followed' && !$status['can_receive'], '已关注待确认不得误判绑定');
    $status = WechatOaBindingService::confirm((int)$user->id, $entry['binding_code']);
    $check($status['bound'] && $status['can_receive'], '确认绑定后应可接收');
    $check(WechatOaBindingService::confirm((int)$user->id, $entry['binding_code'])['bound'], '重复确认本人已完成会话应安全返回');
    WechatOaBindingService::handleEvent(array_merge($event, ['Event' => 'unsubscribe', 'CreateTime' => time() + 1]));
    WechatOaBindingService::handleEvent($event);
    $status = WechatOaBindingService::getStatus((int)$user->id);
    $check($status['bound'] && !$status['can_receive'], '取消关注后保留绑定且忽略旧事件');
    WechatOaBindingService::handleEvent(array_merge($event, ['CreateTime' => time() + 2]));
    $check(WechatOaBindingService::getStatus((int)$user->id)['can_receive'], '重新关注恢复接收');
    $another = User::create(['sn' => 100002, 'account' => 'bind_other']);
    $conflictEntry = WechatOaBindingService::createEntry((int)$another->id);
    $reply = WechatOaBindingService::handleText(['FromUserName' => 'oa_test', 'Content' => '绑定' . $conflictEntry['binding_code']]);
    $check(str_contains((string)$reply, '冲突'), '同一微信不能认领其他平台账号');
    for ($i = 0; $i < 5; $i++) {
        $check(WechatOaBindingService::allowAttempt('limit_test'), '限次窗口内应允许前五次');
    }
    $check(!WechatOaBindingService::allowAttempt('limit_test'), '超过五次必须拒绝');
    OaBindSession::where('binding_code', $conflictEntry['binding_code'])->update(['expires_time' => time() - 1]);
    $check(!WechatOaBindingService::canClaim(OaBindSession::where('binding_code', $conflictEntry['binding_code'])->find()->toArray(), 'new_oa', time()), '过期会话不可认领');
    $admin = Admin::create(['account' => 'test_finance', 'password' => 'test-only', 'name' => '财务测试', 'disable' => 0]);
    $association = AdminBindingService::entry((int)$admin->id);
    try {
        AdminBindingService::claim((int)$user->id, 'disabled');
        $check(false, '旧自助关联写接口必须关闭');
    } catch (RuntimeException $e) {
        $check(!(int)Admin::find($admin->id)->user_id, '关闭的接口不能改变账号关系');
    }
    \app\common\service\AccountBindingService::bind((int)$admin->id, (int)$user->id, 1, '安装验收绑定');
    $check((int)Admin::find($admin->id)->user_id === (int)$user->id, '后台确认后财务账号关联生效');
    $check(in_array((int)$user->id, \app\common\service\InternalNotificationService::userIds([(int)$admin->id]), true),
        '验证关联后允许接收后台事项');
    $advisor = \app\common\model\crm\SalesAdvisor::create(['admin_id' => $admin->id, 'advisor_name' => '顾问测试', 'status' => 1]);
    $customer = \app\common\model\crm\Customer::create(['customer_name' => '通知测试客户', 'advisor_id' => $advisor->id]);
    $internalLog = new \app\common\model\wechat\OaNotificationLog(['user_id' => $user->id,
        'business_type' => 'crm_consult', 'business_id' => $customer->id, 'audience' => 'staff',
        'payload' => ['options' => ['admin_ids' => [(int)$admin->id]]]]);
    $check(\app\common\service\WechatNotificationService::canReceiveBusiness($internalLog), '已关联顾问可接收本人客户摘要');
    $customer->save(['advisor_id' => 0]);
    $check(!\app\common\service\WechatNotificationService::canReceiveBusiness($internalLog), '客户改派后原顾问不得继续接收');
    $customer->save(['advisor_id' => $advisor->id]);
    $advisor->save(['status' => 0]);
    $check(!\app\common\service\WechatNotificationService::canReceiveBusiness($internalLog), '离职顾问不得接收队列通知');
    $advisor->save(['status' => 1]);
    $admin->save(['disable' => 1]);
    $check(!\app\common\service\WechatNotificationService::canReceiveBusiness($internalLog), '禁用后台账号不得接收通知');
    $admin->save(['disable' => 0]);
    $user->save(['is_disable' => 1]);
    $check(!\app\common\service\WechatNotificationService::canReceiveBusiness($internalLog), '禁用平台账号不得接收通知');
    $user->save(['is_disable' => 0]);
    \app\common\service\AccountBindingService::bind((int)$admin->id, 0, 1, '安装验收解绑');
    $check(!\app\common\service\WechatNotificationService::canReceiveBusiness($internalLog), '撤销关联后已排队通知不得继续推送');
    $check((int)Admin::find($admin->id)->user_id === 0, '撤销后清除后台身份关联');
    $check(!\app\common\service\InternalNotificationService::userIds([(int)$admin->id]), '撤销后不得接收工作人员通知');
    $check(WechatOaBindingService::getStatus((int)$user->id)['bound'], '撤销后台身份不应解除用户自身服务号绑定');
    try {
        AdminBindingService::confirm((int)$admin->id, 'disabled');
        $check(false, '旧自助确认写接口必须关闭');
    } catch (RuntimeException $e) {
        $check(!(int)Admin::find($admin->id)->user_id, '旧会话不能恢复已撤销的工作身份');
    }
    $activityId = Db::name('dynamic')->insertGetId(['user_id' => $user->id, 'activity_registered_count' => 2]);
    $ticketId = Db::name('activity_ticket')->insertGetId(['dynamic_id' => $activityId, 'name' => '测试票', 'sold_count' => 2]);
    $registration = ActivityRegistration::create(['dynamic_id' => $activityId, 'ticket_id' => $ticketId,
        'user_id' => $user->id, 'ticket_name' => '测试票', 'payment_sn' => 'VALID_PAYMENT',
        'registration_status' => ActivityRegistration::STATUS_REGISTERED, 'pay_status' => ActivityRegistration::PAY_STATUS_PAID]);
    $extra = ActivityPayment::create(['payment_sn' => 'EXTRA_PAYMENT', 'registration_id' => $registration->id,
        'dynamic_id' => $activityId, 'ticket_id' => $ticketId, 'user_id' => $user->id,
        'pay_way' => ActivityPayment::WAY_WECHAT, 'pay_amount' => 20]);
    $record = new ReflectionMethod(ActivityRegistrationService::class, 'recordExceptionalPaidPayment');
    $record->setAccessible(true);
    $extraRefund = Db::transaction(static fn () => $record->invoke(null, $extra, $registration, 'EXTRA_TX', []));
    $check((int)$extraRefund->is_compensation === 1 && (int)$extraRefund->refund_status === ActivityRefund::STATUS_APPROVED,
        '重复实收自动进入补偿退款');
    $check(ActivityRegistration::find($registration->id)->payment_sn === 'VALID_PAYMENT', '补偿实收不得覆盖正常报名流水');
    $callback = ['out_refund_no' => $extraRefund->refund_sn, 'out_trade_no' => $extra->payment_sn,
        'transaction_id' => 'EXTRA_TX', 'refund_id' => 'REFUND_EXTRA_TX', 'refund_status' => 'SUCCESS',
        'amount' => ['total' => 2000, 'refund' => 2000, 'currency' => 'CNY']];
    $check(ActivityRegistrationService::handleWechatRefundCallback($callback), '活动补偿退款应完成');
    $check(ActivityRegistrationService::handleWechatRefundCallback($callback), '活动重复退款回调应幂等');
    $check((int)Db::name('activity_ticket')->where('id', $ticketId)->value('sold_count') === 2,
        '补偿退款不得释放正常报名名额');
    $callback['amount']['refund'] = 1999;
    $check(!ActivityRegistrationService::handleWechatRefundCallback($callback), '已完成退款的伪造重复回调仍须拒绝');
    $release = new ReflectionMethod(ActivityRegistrationService::class, 'releaseRegistrationQuota');
    $release->setAccessible(true);
    Db::transaction(static fn () => $release->invoke(null, ActivityRegistration::find($registration->id)));
    ActivityRegistration::where('id', $registration->id)->update(['registration_status' => ActivityRegistration::STATUS_REFUND_FAILED]);
    Db::transaction(static fn () => $release->invoke(null, ActivityRegistration::find($registration->id)));
    $check((int)Db::name('activity_ticket')->where('id', $ticketId)->value('sold_count') === 1,
        '退款重试不得重复释放活动名额');
    $payer = User::create(['sn' => 100003, 'account' => 'payment_test']);
    \app\common\model\user\UserAuth::create(['user_id' => $payer->id, 'openid' => 'mnp_test_payer',
        'terminal' => \app\common\enum\user\UserTerminalEnum::WECHAT_MMP]);
    ConfigService::set('order_payment', 'offline_collection_enabled', 0);
    $wechatOrder = Order::create(['order_sn' => 'WECHAT_STAGES', 'user_id' => $payer->id,
        'order_status' => Order::STATUS_PENDING_PAY, 'pay_status' => Order::PAY_STATUS_UNPAID,
        'total_amount' => 100, 'pay_amount' => 100, 'deposit_amount' => 30, 'balance_amount' => 70,
        'deposit_mode_enabled' => 1, 'payment_channel' => Order::PAYMENT_CHANNEL_ONLINE]);
    $wechatPayment = Payment::createPayment((int)$wechatOrder->id, 'WECHAT_STAGES', (int)$payer->id,
        Payment::TYPE_DEPOSIT, Payment::WAY_WECHAT, 30);
    $verified = ['source' => 'wechat_pay_v3', 'source_verified' => true, 'trade_state' => 'SUCCESS',
        'attach' => 'order', 'out_trade_no' => $wechatPayment->payment_sn,
        'amount' => ['total' => 3000, 'currency' => 'CNY'], 'payer' => ['openid' => 'mnp_test_payer'],
        'terminal' => \app\common\enum\user\UserTerminalEnum::WECHAT_MMP];
    foreach ([
        array_replace_recursive($verified, ['source_verified' => false]),
        array_replace_recursive($verified, ['amount' => ['total' => 2999]]),
        array_replace_recursive($verified, ['amount' => ['currency' => 'USD']]),
        array_replace_recursive($verified, ['payer' => ['openid' => 'wrong_payer']]),
    ] as $forged) {
        [$ok] = Db::transaction(static fn () => Payment::paySuccess((string)$wechatPayment->payment_sn, 'WX_DEPOSIT', $forged));
        $check(!$ok, '伪造来源、金额、币种或付款人必须拒绝');
    }
    [$ok, $message] = Db::transaction(static fn () => Payment::paySuccess((string)$wechatPayment->payment_sn, 'WX_DEPOSIT', $verified));
    $check($ok, '微信定金到账失败：' . $message);
    [$ok, , $replay] = Db::transaction(static fn () => Payment::paySuccess((string)$wechatPayment->payment_sn, 'WX_DEPOSIT', $verified));
    $check($ok && !$replay['should_notify'] && (float)Order::find($wechatOrder->id)->paid_amount === 30.0,
        '重复定金回调不得重复入账或通知');
    [$started, $message] = Order::startService((int)$wechatOrder->id, 1);
    $check($started, '开始履约失败：' . $message);
    [$finished, $message] = Order::completeOrder((int)$wechatOrder->id, 1);
    $check($finished, '完成履约失败：' . $message);
    $completedService = Order::find($wechatOrder->id);
    $check((int)$completedService->order_status === Order::STATUS_PENDING_PAY && (float)$completedService->paid_amount === 30.0,
        '服务完成但尾款未收必须保留应收');
    $check(!$completedService->shouldAutoCancelExpiredUnpaid(), '履约后尾款不得超时取消');
    [$cancelled] = Order::cancelOrder((int)$wechatOrder->id, 1);
    $check(!$cancelled && (int)Order::find($wechatOrder->id)->order_status === Order::STATUS_PENDING_PAY,
        '履约后尾款不得手工取消');
    $balance = Payment::createPayment((int)$wechatOrder->id, 'WECHAT_STAGES', (int)$payer->id,
        Payment::TYPE_BALANCE, Payment::WAY_WECHAT, 70);
    $balanceCallback = array_replace_recursive($verified, ['out_trade_no' => $balance->payment_sn, 'amount' => ['total' => 7000]]);
    [$ok] = Db::transaction(static fn () => Payment::paySuccess((string)$balance->payment_sn, 'WX_BALANCE', $balanceCallback));
    $check($ok && (int)Order::find($wechatOrder->id)->order_status === Order::STATUS_COMPLETED, '尾款结清后才完成订单');
    $check((float)Order::find($wechatOrder->id)->paid_amount === 100.0, '定金与尾款累计到账错误');
    foreach (['addition', 'ranking', 'top'] as $type) {
        $preview = \app\common\service\MonthlyReportService::templatePreview(['template_type' => $type]);
        $check(str_starts_with($preview['image_data_url'], 'data:image/jpeg;base64,'), '月报默认模板必须可渲染：' . $type);
    }
    $staffPreview = \app\common\service\StaffScheduleConfirmLetterService::previewConfig(0);
    $check(str_starts_with($staffPreview['preview']['image_data_url'], 'data:image/jpeg;base64,'), '档期默认模板必须可预览');
    echo 'OK - 全新安装、支付与服务号数据库检查：' . $assertions . " 项\n";
} finally {
    $pdo->exec('DROP DATABASE `' . $database . '`');
}
