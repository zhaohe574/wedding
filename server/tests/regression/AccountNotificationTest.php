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


    foreach ([20001, 20002, 20003] as $id) {
        User::create(['id' => $id, 'sn' => $id, 'account' => 'binding_' . $id, 'nickname' => '绑定测试', 'mobile' => '138000' . $id]);
    }
    $admin = Admin::create(['account' => 'binding_test', 'password' => 'test', 'name' => '绑定测试']);
    $staff = Staff::create(['sn' => 'BINDING_TEST', 'name' => '绑定测试', 'status' => 1]);
    AccountBindingService::bind((int)$admin->id, 20001, 1, '测试绑定', (int)$staff->id);
    $check((int)Admin::find($admin->id)->user_id === 20001, '后台绑定必须同步');
    $check(StaffService::getStaffIdByUserId(20001) === (int)$staff->id, '一致绑定可以进入工作台');
    $other = Admin::create(['account' => 'binding_other', 'password' => 'test']);
    $rejected = false;
    try { AccountBindingService::bind((int)$other->id, 20001, 1, '冲突测试'); } catch (Throwable $e) { $rejected = true; }
    $check($rejected && !(int)Admin::find($other->id)->user_id, '重复占用必须整体拒绝');
    $event = ['event' => 'binding_test', 'instance' => 'opening:1', 'user_id' => 20001, 'audience' => 'admin',
        'title' => '测试通知', 'content' => '请前往后台处理', 'target_type' => 'admin_business', 'target_id' => 1,
        'business_type' => 'staff_admin_opened', 'business_id' => (int)$staff->id,
        'options' => ['admin_ids' => [(int)$admin->id]], 'scene' => 'staff_internal'];
    BusinessNotificationService::record($event);
    BusinessNotificationService::record($event);
    $check(Db::name('notification_event')->count() === 1, '相同事件重放不重复');
    BusinessNotificationService::dispatch();
    $check(Notification::visibleQuery(20001)->count() === 1, '站内消息不依赖服务号开启');
    $check(Db::name('wechat_oa_notification_log')->where('send_status', 4)->count() === 1, '渠道关闭必须记录跳过');
    $event['instance'] = 'opening:2';
    BusinessNotificationService::record($event);
    AccountBindingService::bind((int)$admin->id, 20002, 1, '本人换账号');
    $check(StaffService::getStaffIdByUserId(20001) === 0 && StaffService::getStaffIdByUserId(20002) === (int)$staff->id, '换绑立即撤销旧身份');
    $check(Notification::visibleQuery(20001)->count() === 0, '旧用户不能读取历史工作消息');
    BusinessNotificationService::dispatch();
    $check(Db::name('notification_event')->where('status', 2)->count() === 1, '待发事件必须重验关联');
    $check(Notification::visibleQuery(20002)->count() === 0, '历史消息不迁移');
    $before = Db::name('notification_event')->count();
    Db::startTrans();
    BusinessNotificationService::record($event + []);
    $rollbackEvent = array_replace($event, ['instance' => 'rollback']);
    BusinessNotificationService::record($rollbackEvent);
    Db::rollback();
    $check(Db::name('notification_event')->count() === $before, '业务回滚必须回滚事件');
    AccountBindingService::bind((int)$admin->id, 0, 1, '解绑测试');
    $check((int)Admin::find($admin->id)->disable === 1 && (int)Staff::find($staff->id)->status === 0, '解绑同步停用');
    $check((int)User::find(20002)->is_disable === 0, '解绑不影响普通客户账号');
    $check(WechatOaBindingService::claimGuide(20003), '首次登录引导领取');
    $check(!WechatOaBindingService::claimGuide(20003), '跨设备不重复领取');
    $before = OaBindSession::count();
    $state = WechatOaBindingService::getStatus(20003);
    $check($state['follow_status'] === 'unknown' && !$state['bound'], '未知关注不能显示未关注');
    $check(OaBindSession::count() === $before, '状态查询不能创建会话');
    $entry = WechatOaBindingService::createEntry(20003);
    $check(strlen($entry['binding_code']) === 10, '首次生成返回当前口令');
    $check(WechatOaBindingService::createEntry(20003)['binding_code'] === $entry['binding_code'], '未过期会话复用');
    $session = OaBindSession::where('binding_code', $entry['binding_code'])->find();
    WechatOaBindingService::handleEvent(['Event' => 'subscribe', 'FromUserName' => 'binding_openid', 'CreateTime' => time(), 'EventKey' => 'qrscene_oa_bind_' . $session->token]);
    $state = WechatOaBindingService::getStatus(20003);
    $check($state['candidate_ready'] && !$state['bound'], '扫码仅候选，必须本人确认');
    WechatOaBindingService::confirm(20003, $entry['binding_code']);
    $check(WechatOaBindingService::getStatus(20003)['can_receive'], '本人确认后完成绑定');
    WechatOaBindingService::handleEvent(['Event' => 'unsubscribe', 'FromUserName' => 'binding_openid', 'CreateTime' => time()+1]);
    $state = WechatOaBindingService::getStatus(20003);
    $check($state['bound'] && !$state['can_receive'], '取消关注保留绑定');
    $before = OaBindSession::count();
    WechatOaBindingService::createEntry(20003);
    $check(OaBindSession::count() === $before, '重新关注不要求新建绑定会话');
    WechatOaBindingService::handleEvent(['Event' => 'subscribe', 'FromUserName' => 'binding_openid', 'CreateTime' => time()+2]);
    $check(WechatOaBindingService::getStatus(20003)['can_receive'], '重新关注自动恢复接收资格');
    WechatOaBindingService::unbind(20003);
    $check(!WechatOaBindingService::getStatus(20003)['bound'], '显式解绑清理关系');
    $entry = WechatOaBindingService::createEntry(20003);
    OaBindSession::where('binding_code', $entry['binding_code'])->update(['expires_time' => time()-1]);
    $state = WechatOaBindingService::getStatus(20003);
    $check($state['session_expired'] && !$state['candidate_ready'] && !$state['binding_code'], '过期不保留口令和待确认状态');
    $newUser = User::create(['sn' => 20004, 'account' => 'new_staff_test', 'mobile' => '13800020004']);
    $created = \app\adminapi\logic\staff\StaffLogic::add(['user_id' => $newUser->id, 'name' => '新增人员验收', 'operator_id' => 1]);
    $check(is_array($created), '新增入口必须成功：' . \app\adminapi\logic\staff\StaffLogic::getError());
    $newStaff = Staff::find($created['staff_id']);
    $newAdmin = Admin::find($newStaff->admin_id);
    $check((int)$newAdmin->user_id === (int)$newUser->id && StaffService::getStaffIdByUserId((int)$newUser->id) === (int)$newStaff->id, '新增入口同步三方关系');
    $check(\app\common\service\PasswordService::verify('13800020004', $newAdmin->password) && (int)$newAdmin->force_password_reset === 1, '初始手机号密码必须强制修改');
    $openingEvents = Db::name('notification_event')->where('user_id', $newUser->id)->column('payload');
    $check(count($openingEvents) === 1 && json_decode($openingEvents[0], true)['event'] === 'staff_admin_opened', '账号开通在事务内记录通知');
    $check(!\app\adminapi\logic\staff\StaffLogic::edit(['id' => $newStaff->id, 'name' => '新增人员验收', 'user_id' => 20003]), '普通资料编辑拒绝换绑');
    \app\common\model\auth\AdminSession::create(['admin_id' => $newAdmin->id, 'token' => 'reset_test_token', 'expire_time' => time()+3600]);
    $reset = \app\adminapi\logic\staff\StaffLogic::resetAdminPassword((int)$newStaff->id);
    $check(is_array($reset) && (int)Db::name('admin_session')->where('token', 'reset_test_token')->value('expire_time') <= time(), '重置密码撤销旧登录会话');
    Db::name('notification_event')->where('user_id', $newUser->id)->update(['status' => 3]);
    $check(\app\adminapi\logic\staff\StaffLogic::edit(['id' => $newStaff->id, 'name' => '新增人员验收', 'status' => 0]) && StaffService::getStaffIdByUserId((int)$newUser->id) === 0, '停用立即撤销工作入口');
    $check((int)Db::name('notification_event')->where('user_id', $newUser->id)->value('status') === 2, '停用必须作废失败待重试的工作事件');
    $check((int)User::find($newUser->id)->is_disable === 0, '后台停用保留客户身份');
    $check(\app\adminapi\logic\staff\StaffLogic::edit(['id' => $newStaff->id, 'name' => '新增人员验收', 'status' => 1]) && StaffService::getStaffIdByUserId((int)$newUser->id) === (int)$newStaff->id, '恢复一致关系后允许进入工作台');
    $legacyAdmin = Admin::create(['account' => 'legacy_binding', 'password' => 'test']);
    $archivedId = Db::name('staff')->insertGetId(['sn' => 'ARCHIVED_BINDING', 'name' => '历史已删除档案', 'user_id' => 20003, 'admin_id' => $legacyAdmin->id, 'delete_time' => time()]);
    Staff::create(['sn' => 'LEGACY_BINDING', 'name' => '旧数据', 'user_id' => 20003, 'admin_id' => $legacyAdmin->id]);
    // 在隔离库模拟旧版结构，验证真实增量升级而不只是重复执行空迁移。
    Db::execute('DROP TABLE qa_account_binding_audit, qa_notification_event');
    Db::execute('ALTER TABLE qa_notification DROP INDEX uk_notification_event, DROP COLUMN event_key, DROP COLUMN audience, DROP COLUMN identity_revoked, DROP COLUMN business_type, DROP COLUMN business_id, DROP COLUMN access_options');
    Db::execute('ALTER TABLE qa_user DROP COLUMN oa_guide_seen_time');
    Db::execute('ALTER TABLE qa_staff DROP INDEX uk_staff_user, DROP INDEX uk_staff_admin, DROP COLUMN active_user_id, DROP COLUMN active_admin_id');
    $runMigration = static function (bool $apply) use ($database): array {
        $args = [PHP_BINARY, __DIR__ . '/../../database/migrations/20260915_account_notifications.php', '--test-database=' . $database];
        if ($apply) $args[] = '--apply';
        $process = proc_open($args, [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
        $output = stream_get_contents($pipes[1]) . stream_get_contents($pipes[2]);
        fclose($pipes[1]); fclose($pipes[2]);
        return [proc_close($process), $output];
    };
    [$code, $output] = $runMigration(false);
    $check($code === 0 && str_contains($output, 'safe_repairs'), '只读迁移报告可执行：' . $output);
    $check(!(int)Admin::find($legacyAdmin->id)->user_id, '默认报告不得修改真实关系');
    [$code, $output] = $runMigration(true);
    $check($code === 0, '迁移执行成功：' . $output);
    $check((int)Admin::find($legacyAdmin->id)->user_id === 20003, '迁移只补齐明确档案关系');
    $check((int)Db::name('staff')->where('id', $archivedId)->value('user_id') === 20003, '已删除档案保留历史关联，不占用当前身份');
    [$code, $output] = $runMigration(true);
    $check($code === 0, '重复迁移可安全执行：' . $output);
    $cfg = \app\common\service\ConfigService::class;
    $invites = \app\common\service\wechat\OaInvitationService::class;
    $cfg::set('oa_setting', 'app_id', 'wx1111111111111111');
    $cfg::set('oa_setting', 'app_secret', 'isolated-test');
    $cfg::set('mnp_setting', 'app_id', 'wx2222222222222222');
    $cfg::set('oa_setting', 'invitation_verified_apps', 'wx1111111111111111:wx2222222222222222');
    $check($invites::configuration()['ready'] && !$invites::enabled(), '配置就绪不自动发布新入口');
    $check(!str_contains($invites::reply('invitation-openid'), 'invitation='), '入口未发布不发送无效跳转');
    $cfg::set('oa_setting', 'invitation_enabled', 1);
    foreach ([20005, 20006] as $id) User::create(['id' => $id, 'sn' => $id, 'account' => 'invite_' . $id]);
    WechatOaBindingService::handleEvent(['Event' => 'subscribe', 'FromUserName' => 'invitation-openid', 'CreateTime' => time()]);
    $invite = $invites::create('invitation-openid');
    $check(strlen($invite->token) === 64 && (int)$invite->user_id === 0 && (int)$invite->expires_time <= time()+600, '邀请仅关联服务号微信并限制十分钟');
    $check($invites::create('invitation-openid')->id === $invite->id, '同一微信有效邀请复用');
    $state = $invites::status(20005, $invite->token);
    $check($state['can_confirm'] && !OaFollower::findByUserId(20005), '状态查询不能自动绑定');
    $check(!$invites::status(20005, 'invalid')['can_confirm'], '无效凭据不能确认');
    $reply = $invites::welcome('invitation-openid', '欢迎使用');
    $check(str_starts_with($reply, '欢迎使用') && str_contains($reply, 'data-miniprogram-appid="wx2222222222222222"') && str_contains($reply, 'invitation='.$invite->token), '欢迎消息保留原文并携带专属小程序链接');
    $check(!str_contains($reply, 'invitation-openid'), '消息链接不得暴露微信标识');
    $check(str_contains(WechatOaBindingService::handleText(['Content' => '绑定账号', 'FromUserName' => 'invitation-openid']), 'data-miniprogram-path'), '保留关键词提供绑定入口');
    $menus = \app\adminapi\logic\channel\OfficialAccountMenuLogic::normalizeBindingMenu([
        ['name' => '服务', 'sub_button' => [['name' => '绑定账号', 'type' => 'oa_binding']]], ['name' => '官网', 'type' => 'view', 'url' => 'https://example.com']]);
    $check($menus[0]['sub_button'][0]['type'] === 'click' && $menus[0]['sub_button'][0]['key'] === $invites::MENU_KEY && $menus[1]['url'] === 'https://example.com', '菜单生成专属点击事件且保留其他项');
    $check($invites::confirm(20005, $invite->token)['state'] === 'completed', '一次确认完成绑定');
    $check($invites::confirm(20005, $invite->token)['state'] === 'completed', '同一账号重复确认幂等');
    $check(!$invites::status(20006, $invite->token)['can_confirm'], '其他账号不能消费已使用邀请');
    $blocked = false;
    try { $invites::confirm(20006, $invite->token); } catch (RuntimeException $e) { $blocked = true; }
    $check($blocked && !OaFollower::findByUserId(20006), '拒绝跨账号重复消费且不改变绑定');
    WechatOaBindingService::handleEvent(['Event' => 'subscribe', 'FromUserName' => 'other-invitation-openid', 'CreateTime' => time()]);
    $otherInvite = $invites::create('other-invitation-openid');
    $check($invites::status(20005, $otherInvite->token)['state'] === 'conflict', '平台账号已绑定其他微信时拒绝覆盖');
    $anotherInvite = $invites::create('invitation-openid');
    $check($invites::status(20006, $anotherInvite->token)['state'] === 'conflict', '微信已绑定其他账号时拒绝覆盖');
    OaBindSession::where('id', $otherInvite->id)->update(['expires_time' => time()-1]);
    $check($invites::status(20006, $otherInvite->token)['state'] === 'expired', '过期入口明确提示重新获取');
    $check($invites::create('other-invitation-openid')->id !== $otherInvite->id, '过期后获取新的邀请');
    WechatOaBindingService::handleEvent(['Event' => 'unsubscribe', 'FromUserName' => 'invitation-openid', 'CreateTime' => time()+1]);
    $check((int)OaFollower::findByUserId(20005)->user_id === 20005 && !$invites::status(20005, $anotherInvite->token)['can_confirm'], '取消关注保留绑定且停止待确认');
    WechatOaBindingService::handleEvent(['Event' => 'subscribe', 'FromUserName' => 'invitation-openid', 'CreateTime' => time()+2]);
    $check(WechatOaBindingService::getStatus(20005)['can_receive'], '重新关注恢复资格');
    $blocked = false;
    try { WechatOaBindingService::createEntry(20006); } catch (RuntimeException $e) { $blocked = true; }
    $check($blocked, '新流程启用后关闭旧入口');
    $safe = \app\common\service\BindingPrivacyService::redact(['invitation' => $invite->token, 'url' => '/page?invitation='.$invite->token, 'nested' => ['binding_code' => 'secret']]);
    $check(!str_contains(json_encode($safe), $invite->token) && !str_contains(json_encode($safe), 'secret'), '邀请参数、链接及嵌套日志统一脱敏');
    Db::execute('ALTER TABLE qa_wechat_oa_bind_session DROP INDEX idx_source_candidate, DROP COLUMN source');
    foreach ([false, true, true] as $apply) {
        $args = [PHP_BINARY, __DIR__.'/../../database/migrations/20260915_oa_invitation.php', '--test-database='.$database];
        if ($apply) $args[] = '--apply';
        $process = proc_open($args, [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
        $output = stream_get_contents($pipes[1]).stream_get_contents($pipes[2]);
        fclose($pipes[1]); fclose($pipes[2]);
        $check(proc_close($process) === 0, '邀请来源迁移可预检、执行并重复运行：'.$output);
    }
    $beforeReminder = WechatOaBindingService::getStatus(20005);
    $check(!$beforeReminder['reminder_snoozed_today'], '新用户默认没有免提醒偏好');
    $reminder = WechatOaBindingService::skipReminderToday(20005);
    $expectedUntil = (new DateTimeImmutable('tomorrow', new DateTimeZone('Asia/Shanghai')))->setTime(0, 0)->getTimestamp();
    $check($reminder['reminder_skip_until'] === $expectedUntil, '免提醒截止于北京时间次日零点');
    $check(WechatOaBindingService::skipReminderToday(20005) === $reminder, '同一天重复保存幂等');
    $check(WechatOaBindingService::getStatus(20005)['reminder_snoozed_today'], '新设备查询能够读取账号偏好');
    $check(!WechatOaBindingService::getStatus(20006)['reminder_snoozed_today'], '免提醒偏好按账号隔离');
    $check(WechatOaBindingService::getStatus(20005)['bound'] === $beforeReminder['bound'], '跳过提醒不得修改绑定');
    User::where('id', 20005)->update(['oa_reminder_skip_until' => time() - 1]);
    $check(!WechatOaBindingService::getStatus(20005)['reminder_snoozed_today'], '截止时间到达后恢复提醒');
    Db::execute('ALTER TABLE qa_user DROP COLUMN oa_reminder_skip_until');
    foreach ([false, true, true] as $apply) {
        $args = [PHP_BINARY, __DIR__.'/../../database/migrations/20260915_oa_reminder.php', '--test-database='.$database];
        if ($apply) $args[] = '--apply';
        $process = proc_open($args, [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
        $output = stream_get_contents($pipes[1]).stream_get_contents($pipes[2]);
        fclose($pipes[1]); fclose($pipes[2]);
        $check(proc_close($process) === 0, '提醒偏好迁移可预检、执行并重复运行：'.$output);
        if (!$apply) $check(!in_array('oa_reminder_skip_until', array_column(Db::query('SHOW COLUMNS FROM qa_user'), 'Field'), true), '只读预检不得修改结构');
    }
    echo "OK - 账号绑定、服务号流程与业务事件：{$checks} 项\n";
} finally {
    $pdo->exec('DROP DATABASE IF EXISTS `' . $database . '`');
}
