<?php
declare(strict_types=1);

namespace app\common\service\wechat;

use app\common\model\user\User;
use app\common\model\wechat\OaBindSession;
use app\common\model\wechat\OaFollower;
use app\common\service\ConfigService;
use think\facade\Db;

/** 服务号发起的邀请只在用户明确确认后建立关联。 */
class OaInvitationService
{
    public const SOURCE = 'official_account';
    public const MENU_KEY = 'OA_BIND_ACCOUNT';
    public const QUERY_MENU_KEY = 'OA_BIND_QUERY';
    public const PAGE = 'pages/oa_subscribe/oa_subscribe';
    public const TTL = 600;

    public static function enabled(): bool
    {
        return (int)ConfigService::get('oa_setting', 'invitation_enabled', 0) === 1;
    }

    public static function configuration(): array
    {
        $oa = (string)ConfigService::get('oa_setting', 'app_id', '');
        $appid = (string)(WeChatConfigService::getMnpConfig()['app_id'] ?? '');
        $verified = (string)ConfigService::get('oa_setting', 'invitation_verified_apps', '');
        $checks = [
            'oa_configured' => preg_match('/^wx[a-zA-Z0-9]{16}$/', $oa) === 1
                && (string)ConfigService::get('oa_setting', 'app_secret', '') !== '',
            'miniapp_configured' => preg_match('/^wx[a-zA-Z0-9]{16}$/', $appid) === 1,
            'link_verified' => $verified !== '' && hash_equals($oa . ':' . $appid, $verified),
        ];
        return ['enabled' => self::enabled(), 'ready' => !in_array(false, $checks, true),
            'miniapp_appid' => $appid, 'page' => self::PAGE, 'menu_key' => self::MENU_KEY,
            'query_menu_key' => self::QUERY_MENU_KEY, 'checks' => $checks];
    }

    /** 未知粉丝通过微信接口核验关注，菜单点击本身不代表已关注。 */
    public static function ensureFollower(string $openid): void
    {
        if (OaFollower::findByOpenid($openid)) return;
        $info = (new WeChatOaService())->getFollowerInfo($openid);
        if ((int)($info['subscribe'] ?? 0) !== 1 || (string)($info['openid'] ?? '') !== $openid) {
            throw new \RuntimeException('请先关注服务号，再点击“绑定账号”。');
        }
        WechatOaBindingService::handleEvent(['Event' => 'subscribe', 'FromUserName' => $openid,
            'CreateTime' => (int)($info['subscribe_time'] ?? time())]);
    }

    public static function create(string $openid): OaBindSession
    {
        if (!self::enabled() || !self::configuration()['ready']) throw new \RuntimeException('绑定入口暂未开放，请稍后重试或联系管理员。');
        return Db::transaction(static function () use ($openid) {
            $follower = OaFollower::where('openid', $openid)->lock(true)->find();
            if (!$follower || (int)$follower->follow_status !== OaFollower::STATUS_FOLLOWED) {
                throw new \RuntimeException('请先关注服务号，再点击“绑定账号”。');
            }
            $session = OaBindSession::where('source', self::SOURCE)->where('candidate_openid', $openid)
                ->where('status', OaBindSession::STATUS_PENDING)->where('expires_time', '>', time())->order('id desc')->lock(true)->find();
            if ($session) return $session;
            if (!WechatOaBindingService::allowAttempt('invitation:' . $openid)) throw new \RuntimeException('操作频繁，请稍后重试。');
            return OaBindSession::create(['source' => self::SOURCE, 'user_id' => 0, 'admin_id' => 0,
                'candidate_openid' => $openid, 'token' => bin2hex(random_bytes(32)),
                'binding_code' => strtoupper(bin2hex(random_bytes(5))), 'status' => OaBindSession::STATUS_PENDING,
                'expires_time' => time() + self::TTL, 'create_time' => time(), 'update_time' => time()]);
        });
    }

    public static function reply(string $openid): string
    {
        if (!self::enabled() || !self::configuration()['ready']) return '绑定入口暂未开放，请稍后重试或联系管理员。';
        try {
            self::ensureFollower($openid);
            $session = self::create($openid);
            $config = self::configuration();
            $escape = static fn(string $value): string => htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            return '请使用本人账号进入小程序，确认后开启服务号通知。入口十分钟内有效，请勿转发。' . "\n"
                . '<a href="' . $escape(rtrim(request()->domain(), '/') . '/pc/') . '" data-miniprogram-appid="'
                . $escape($config['miniapp_appid']) . '" data-miniprogram-path="' . self::PAGE . '?invitation=' . $session->token
                . '">绑定账号，接收提醒</a>';
        } catch (\Throwable $e) {
            // 不将微信响应或邀请凭据写入日志、消息及异常响应。
            return '暂时无法获取绑定入口，请确认已关注，再点击“绑定账号”重试。';
        }
    }

    public static function welcome(string $openid, string $content): string
    {
        return trim($content) . (trim($content) !== '' ? "\n\n" : '') . self::reply($openid);
    }

    /** 只读查询当前微信的绑定状态，不写库、不调用微信写接口、不泄露敏感标识。 */
    public static function queryReply(string $openid): string
    {
        $openid = trim($openid);
        if ($openid === '') return '暂时无法查询绑定状态，请稍后重试。';
        try {
            $follower = OaFollower::findByOpenid($openid);
            $config = self::configuration();
            $canLinkMiniapp = self::enabled() && $config['ready'];

            if (!$follower || (int)$follower->user_id <= 0) {
                $text = '当前微信尚未绑定平台账号，暂无法接收服务号提醒。';
                $text .= "\n" . ($canLinkMiniapp
                    ? '请点击菜单“绑定账号”或发送“绑定账号”获取专属绑定入口。'
                    : '请前往小程序“服务号通知”页面完成绑定。');
                return $text;
            }

            $user = User::find((int)$follower->user_id);
            if (!$user || (int)$user->is_disable !== 0) {
                return '当前微信已绑定平台账号，但该账号已停用或不可用，暂无法接收服务号提醒。请联系管理员核实处理。';
            }

            $nickname = trim((string)($user->getData('nickname') ?? ''));
            $rawMobile = trim((string)($user->getData('mobile') ?? ''));
            $maskedMobile = $rawMobile !== ''
                ? (string)preg_replace('/^(\d{3})\d{4}(\d+)$/', '$1****$2', $rawMobile)
                : '';
            if ($nickname !== '' && $maskedMobile !== '') {
                $accountLabel = $nickname . '（' . $maskedMobile . '）';
            } elseif ($nickname !== '') {
                $accountLabel = $nickname;
            } elseif ($maskedMobile !== '') {
                $accountLabel = $maskedMobile;
            } else {
                $accountLabel = trim((string)($user->getData('account') ?? '')) ?: ('ID:' . (int)$user->id);
            }

            $status = WechatOaBindingService::getStatus((int)$user->id);
            if ($status['can_receive']) {
                $statusLine = $status['channel_available']
                    ? '通知状态：可正常接收服务号提醒'
                    : '通知状态：已绑定（平台服务号通知暂未开启）';
            } else {
                $statusLine = '通知状态：当前未处于关注状态，重新关注后自动恢复接收提醒';
            }

            $lines = ['当前微信已绑定平台账号', '绑定账号：' . $accountLabel, $statusLine];
            if ($canLinkMiniapp) {
                $escape = static fn(string $value): string => htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
                $lines[] = '<a href="' . $escape(rtrim(request()->domain(), '/') . '/pc/') . '" data-miniprogram-appid="'
                    . $escape($config['miniapp_appid']) . '" data-miniprogram-path="' . self::PAGE
                    . '">前往小程序查看或管理绑定</a>';
            } else {
                $lines[] = '如需解绑或管理，请前往小程序“服务号通知”页面操作。';
            }
            return implode("\n", $lines);
        } catch (\Throwable $e) {
            // 不泄露微信标识、邀请凭据或底层异常信息。
            return '暂时无法查询绑定状态，请稍后重试。';
        }
    }

    private static function find(string $token): ?OaBindSession
    {
        if (!preg_match('/^[a-f0-9]{64}$/D', $token)) return null;
        return OaBindSession::where('source', self::SOURCE)->where('token', $token)->where('admin_id', 0)->find();
    }

    private static function state(?OaBindSession $session, int $userId, bool $lock = false): array
    {
        $result = ['state' => 'invalid', 'can_confirm' => false, 'expires_time' => 0,
            'message' => '入口无效，请回服务号点击“绑定账号”重新获取。'];
        if (!$session) return $result;
        $result['expires_time'] = (int)$session->expires_time;
        $follower = OaFollower::where('openid', (string)$session->candidate_openid)->lock($lock)->find();
        $existing = OaFollower::where('user_id', $userId)->lock($lock)->find();
        if ((int)$session->status === OaBindSession::STATUS_CONSUMED) {
            if ((int)$session->user_id === $userId && $follower && (int)$follower->user_id === $userId) {
                return array_replace($result, ['state' => 'completed', 'message' => '已完成绑定。']);
            }
            return array_replace($result, ['state' => 'conflict', 'message' => '此入口已被使用，请重新获取绑定入口。']);
        }
        if ((int)$session->status !== OaBindSession::STATUS_PENDING || (int)$session->expires_time <= time()) {
            return array_replace($result, ['state' => 'expired', 'message' => '入口已过期，请回服务号点击“绑定账号”重新获取。']);
        }
        if (!$follower || (int)$follower->follow_status !== OaFollower::STATUS_FOLLOWED) {
            return array_replace($result, ['state' => 'unfollowed', 'message' => '请先关注服务号，再点击“绑定账号”。']);
        }
        if (((int)$follower->user_id > 0 && (int)$follower->user_id !== $userId)
            || ($existing && (string)$existing->openid !== (string)$follower->openid)) {
            return array_replace($result, ['state' => 'conflict', 'message' => '微信或平台账号已有其他绑定，请先处理原绑定。']);
        }
        return array_replace($result, ['state' => 'ready', 'can_confirm' => true,
            'message' => '确认将当前平台账号绑定至发出此邀请的服务号微信，接收订单、档期及工作提醒。']);
    }

    public static function status(int $userId, string $token): array
    {
        if (!self::enabled() || !self::configuration()['ready']) return ['state' => 'unavailable', 'can_confirm' => false, 'expires_time' => 0, 'message' => '绑定入口暂未开放。'];
        return self::state(self::find($token), $userId) + ['binding' => WechatOaBindingService::getStatus($userId)];
    }

    public static function confirm(int $userId, string $token): array
    {
        if (!self::enabled() || !self::configuration()['ready']) throw new \RuntimeException('绑定入口暂不可用，请稍后重试。');
        return Db::transaction(static function () use ($userId, $token): array {
            if (!User::where('id', $userId)->where('is_disable', 0)->lock(true)->find()) throw new \RuntimeException('平台账号不可用。');
            $snapshot = self::find($token);
            if (!$snapshot) throw new \RuntimeException('入口无效，请回服务号重新获取。');
            $follower = OaFollower::where('openid', $snapshot->candidate_openid)->lock(true)->find();
            $session = OaBindSession::where('id', $snapshot->id)->lock(true)->find();
            $state = self::state($session, $userId, true);
            if ($state['state'] === 'completed') return self::status($userId, $token);
            if (!$state['can_confirm']) throw new \RuntimeException($state['message']);
            $follower->save(['user_id' => $userId, 'update_time' => time()]);
            $session->user_id = $userId;
            $session->consume();
            return self::status($userId, $token);
        });
    }
}
