<?php

declare(strict_types=1);

namespace app\common\service\wechat;

use app\common\model\wechat\OaBindSession;
use app\common\model\wechat\OaFollower;
use think\facade\Db;

/** 微信事件只认领会话，由登录用户最终确认绑定。 */
class WechatOaBindingService
{
    public const SCENE_PREFIX = 'oa_bind_';
    public const SESSION_EXPIRE_SECONDS = 900;

    public static function createEntry(int $userId): array
    {
        self::assertLegacyEnabled();
        return Db::transaction(function () use ($userId) {
            if ($userId <= 0 || !\app\common\model\user\User::where('id', $userId)->lock(true)->find()) {
                throw new \RuntimeException('平台账号不存在');
            }
            return self::createUserEntry($userId);
        });
    }

    private static function createUserEntry(int $userId): array
    {
        $status = self::getStatus($userId);
        if ($status['bound']) {
            return $status + ['binding_code' => '', 'qr_url' => '', 'expires_time' => 0];
        }
        $session = OaBindSession::where('user_id', $userId)->where('admin_id', 0)
            ->where('status', OaBindSession::STATUS_PENDING)->where('expires_time', '>', time())
            ->order('id desc')->find();
        if (!$session) {
            if (OaBindSession::where('user_id', $userId)->where('create_time', '>', time() - 60)->count() >= 5) {
                throw new \RuntimeException('操作频繁，请稍后重试');
            }
            $session = OaBindSession::createSession($userId, self::SESSION_EXPIRE_SECONDS);
        }
        return array_merge($status, [
            'binding_code' => (string) $session->binding_code,
            'expires_time' => (int) $session->expires_time,
            'candidate_ready' => trim((string) $session->candidate_openid) !== '',
            'session_expired' => false,
        ]);
    }

    public static function getQrCode(int $userId, string $code = ''): array
    {
        self::assertLegacyEnabled();
        $entry = self::getStatus($userId);
        if ($entry['bound']) {
            return $entry + ['qr_url' => $entry['official_qr_url']];
        }
        if ($code === '' || !hash_equals($entry['binding_code'], $code)) throw new \RuntimeException('绑定会话已更新，请刷新页面');
        $session = OaBindSession::where('user_id', $userId)->where('binding_code', $entry['binding_code'])->find();
        if (!$session || (int)$session->status !== OaBindSession::STATUS_PENDING || (int)$session->expires_time <= time()) {
            throw new \RuntimeException('绑定会话已失效，请重新获取');
        }
        $qr = (new WeChatOaService())->createTemporaryQrCode(self::SCENE_PREFIX . $session->token, max(1, (int)$session->expires_time - time()));
        if (empty($qr['ticket'])) {
            throw new \RuntimeException('服务号二维码生成失败，请使用绑定码');
        }
        return $entry + ['qr_url' => 'https://mp.weixin.qq.com/cgi-bin/showqrcode?ticket=' . rawurlencode($qr['ticket'])];
    }

    public static function getStatus(int $userId): array
    {
        $session = OaBindSession::where('user_id', $userId)->where('admin_id', 0)
            ->where('status', OaBindSession::STATUS_PENDING)->order('id desc')->find();
        $active = $session && (int)$session->expires_time > time();
        $follower = OaFollower::findByUserId($userId);
        $bound = $follower && trim((string) $follower->openid) !== '';
        if (!$follower) {
            $candidate = OaBindSession::where('user_id', $userId)->where('admin_id', 0)
                ->where('status', OaBindSession::STATUS_PENDING)->where('expires_time', '>', time())
                ->order('id', 'desc')->value('candidate_openid');
            $follower = $candidate ? OaFollower::findByOpenid((string)$candidate) : null;
        }
        $followed = $follower && (int)$follower->follow_status === OaFollower::STATUS_FOLLOWED;
        $qr = (string)\app\common\service\ConfigService::get('oa_setting', 'qr_code', '');
        $skipUntil = (int)\app\common\model\user\User::where('id', $userId)->value('oa_reminder_skip_until');
        return [
            'reminder_snoozed_today' => $skipUntil > time(),
            'reminder_skip_until' => $skipUntil,
            'bound' => (bool) $bound,
            'follow_status' => $follower ? ($followed ? 'followed' : 'unfollowed') : 'unknown',
            'can_receive' => (bool) ($bound && $followed),
            'followed_time' => (int) ($follower->followed_time ?? 0),
            'candidate_ready' => !$bound && $active && trim((string)$session->candidate_openid) !== '',
            'binding_code' => !$bound && $active ? (string)$session->binding_code : '',
            'expires_time' => !$bound && $session ? (int)$session->expires_time : 0,
            'session_expired' => !$bound && $session && !$active,
            'channel_available' => (int)\app\common\service\ConfigService::get('oa_notification', 'enabled', 0) === 1
                && (string)\app\common\service\ConfigService::get('oa_setting', 'app_id', '') !== ''
                && (string)\app\common\service\ConfigService::get('oa_setting', 'app_secret', '') !== ''
                && \app\common\model\wechat\OaNotificationTemplate::where('status', 1)->where('template_id', '<>', '')->count() > 0,
            'official_name' => (string)\app\common\service\ConfigService::get('oa_setting', 'name', ''),
            'official_account' => (string)\app\common\service\ConfigService::get('oa_setting', 'account', ''),
            'official_qr_url' => $qr ? \app\common\service\FileService::getFileUrl($qr) : '',
        ];
    }

    /** 按北京时间次日零点保存当前用户的免提醒截止时间。 */
    public static function skipReminderToday(int $userId): array
    {
        $until = (new \DateTimeImmutable('now', new \DateTimeZone('Asia/Shanghai')))->modify('tomorrow')->setTime(0, 0)->getTimestamp();
        if (!\app\common\model\user\User::where('id', $userId)->where('is_disable', 0)->find()) throw new \RuntimeException('平台账号不可用');
        \app\common\model\user\User::where('id', $userId)->update(['oa_reminder_skip_until' => $until]);
        return ['reminder_snoozed_today' => true, 'reminder_skip_until' => $until];
    }

    /** 旧客户端的首次引导领取接口保留兼容。 */
    public static function claimGuide(int $userId): bool
    {
        return \app\common\model\user\User::where('id', $userId)->where('oa_guide_seen_time', 0)
            ->update(['oa_guide_seen_time' => time()]) > 0;
    }

    public static function handleEvent(array $message): array
    {
        $event = strtolower(trim((string) ($message['Event'] ?? '')));
        $openid = trim((string) ($message['FromUserName'] ?? ''));
        if ($openid === '' || !in_array($event, ['subscribe', 'scan', 'unsubscribe'], true)) {
            return ['handled' => false];
        }
        $eventTime = (int) ($message['CreateTime'] ?? time());
        Db::transaction(function () use ($openid, $event, $eventTime) {
            $follower = OaFollower::where('openid', $openid)->lock(true)->find();
            if ($follower && ((int)$follower->last_event_time > $eventTime
                || ((int)$follower->last_event_time === $eventTime
                    && (int)$follower->follow_status === OaFollower::STATUS_UNFOLLOWED && $event !== 'unsubscribe'))) {
                return;
            }
            $follower = $follower ?: new OaFollower(['openid' => $openid, 'user_id' => null]);
            $follower->follow_status = $event === 'unsubscribe' ? OaFollower::STATUS_UNFOLLOWED : OaFollower::STATUS_FOLLOWED;
            $follower->last_event_time = $eventTime;
            $follower->update_time = time();
            if ($event === 'unsubscribe') {
                $follower->unfollowed_time = $eventTime;
            } else {
                $follower->followed_time = $eventTime;
                $follower->unfollowed_time = 0;
            }
            $follower->save();
        });
        if ($event !== 'unsubscribe' && !OaInvitationService::enabled()) {
            $eventKey = preg_replace('/^qrscene_/', '', trim((string) ($message['EventKey'] ?? '')));
            if (str_starts_with($eventKey, self::SCENE_PREFIX)) {
                try {
                    self::claim('token', substr($eventKey, strlen(self::SCENE_PREFIX)), $openid);
                } catch (\RuntimeException $e) {
                    return ['handled' => true, 'binding_error' => $e->getMessage()];
                }
            }
        }
        return ['handled' => true];
    }

    public static function handleText(array $message): ?string
    {
        $content = trim((string) ($message['Content'] ?? ''));
        if ($content === '绑定账号') return OaInvitationService::reply(trim((string)($message['FromUserName'] ?? '')));
        if (OaInvitationService::enabled() && preg_match('/^绑定\s*[A-Fa-f0-9]{10}$/u', $content)) {
            return '旧口令绑定已停用，请发送“绑定账号”获取小程序确认入口。';
        }
        if (!preg_match('/^绑定\\s*([A-Fa-f0-9]{10})$/u', $content, $matches)) {
            return null;
        }
        $openid = trim((string) ($message['FromUserName'] ?? ''));
        if (!self::allowAttempt('oa:' . $openid)) {
            return '尝试次数过多，请十分钟后重试。';
        }
        try {
            self::claim('binding_code', strtoupper($matches[1]), $openid);
            return '已收到绑定申请，请返回小程序确认绑定。';
        } catch (\Throwable $e) {
            return '绑定码无效、已被使用或账号冲突，请在小程序重新获取。';
        }
    }

    public static function allowAttempt(string $subject): bool
    {
        $key = hash('sha256', $subject);
        $now = time();
        $cutoff = $now - 600;
        return Db::transaction(static function () use ($key, $now, $cutoff): bool {
            $table = Db::name('wechat_binding_attempt')->getTable();
            Db::execute("INSERT INTO " . $table . " (subject, attempts, window_start) VALUES (?, 1, ?)
                ON DUPLICATE KEY UPDATE attempts = IF(window_start <= ?, 1, attempts + 1),
                    window_start = IF(window_start <= ?, VALUES(window_start), window_start)",
                [$key, $now, $cutoff, $cutoff]);
            return (int)Db::name('wechat_binding_attempt')->where('subject', $key)->value('attempts') <= 5;
        });
    }

    private static function claim(string $field, string $value, string $openid): void
    {
        Db::transaction(function () use ($field, $value, $openid) {
            $session = OaBindSession::where($field, $value)->where('admin_id', 0)->lock(true)->find();
            if (!$session || !self::canClaim($session->toArray(), $openid, time())) {
                throw new \RuntimeException('绑定会话不可用');
            }
            $follower = OaFollower::findByOpenid($openid);
            if (!$follower || (int) $follower->follow_status !== OaFollower::STATUS_FOLLOWED) {
                throw new \RuntimeException('请先关注服务号');
            }
            if ((int) $follower->user_id > 0 && (int) $follower->user_id !== (int) $session->user_id) {
                throw new \RuntimeException('该微信已绑定其他账号');
            }
            $session->candidate_openid = $openid;
            $session->save();
        });
    }

    public static function canClaim(array $session, string $openid, int $now): bool
    {
        return $openid !== '' && (int) ($session['status'] ?? -1) === OaBindSession::STATUS_PENDING
            && (int) ($session['expires_time'] ?? 0) > $now
            && (empty($session['candidate_openid']) || hash_equals((string) $session['candidate_openid'], $openid));
    }

    public static function confirm(int $userId, string $code): array
    {
        self::assertLegacyEnabled();
        return Db::transaction(function () use ($userId, $code) {
            \app\common\model\user\User::where('id', $userId)->lock(true)->find();
            $session = OaBindSession::where('user_id', $userId)->where('binding_code', strtoupper(trim($code)))
                ->where('admin_id', 0)->lock(true)->find();
            if ($session && (int)$session->status === OaBindSession::STATUS_CONSUMED) {
                $existing = OaFollower::findByUserId($userId);
                if ($existing && hash_equals((string)$existing->openid, (string)$session->candidate_openid)) return self::getStatus($userId);
            }
            if (!$session || !self::canClaim($session->toArray(), (string) $session->candidate_openid, time())) {
                throw new \RuntimeException('请先在服务号提交有效绑定码');
            }
            $follower = OaFollower::where('openid', $session->candidate_openid)->lock(true)->find();
            if (!$follower || (int) $follower->follow_status !== OaFollower::STATUS_FOLLOWED) {
                throw new \RuntimeException('请先关注服务号');
            }
            if ((int) $follower->user_id > 0 && (int) $follower->user_id !== $userId) {
                throw new \RuntimeException('该微信已绑定其他账号');
            }
            $existing = OaFollower::where('user_id', $userId)->lock(true)->find();
            if ($existing && (string) $existing->openid !== (string) $follower->openid) {
                throw new \RuntimeException('账号已绑定其他微信，请先解除绑定');
            }
            $follower->user_id = $userId;
            $follower->save();
            $session->consume();
            return self::getStatus($userId);
        });
    }

    public static function unbind(int $userId): void
    {
        Db::transaction(function () use ($userId) {
            \app\common\model\user\User::where('id', $userId)->lock(true)->find();
            OaFollower::where('user_id', $userId)->update(['user_id' => null, 'update_time' => time()]);
            OaBindSession::where('user_id', $userId)->where('admin_id', 0)->update(['status' => OaBindSession::STATUS_EXPIRED]);
        });
    }

    private static function assertLegacyEnabled(): void
    {
        if (OaInvitationService::enabled()) throw new \RuntimeException('旧绑定入口已停用，请在服务号点击“绑定账号”进入小程序确认。');
    }
}
