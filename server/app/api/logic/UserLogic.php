<?php
// +----------------------------------------------------------------------
// | likeadmin快速开发前后端分离管理后台（PHP版）
// +----------------------------------------------------------------------
// | 欢迎阅读学习系统程序代码，建议反馈是我们前进的动力
// | 开源版本可自由商用，可去除界面版权logo
// | gitee下载：https://gitee.com/likeshop_gitee/likeadmin
// | github下载：https://github.com/likeshop-github/likeadmin
// | 访问官网：https://www.likeadmin.cn
// | likeadmin团队 版权所有 拥有最终解释权
// +----------------------------------------------------------------------
// | author: likeadminTeam
// +----------------------------------------------------------------------

namespace app\api\logic;


use app\common\{enum\notice\NoticeEnum,
    model\crm\Customer,
    enum\user\UserTerminalEnum,
    enum\YesNoEnum,
    logic\BaseLogic,
    model\user\User,
    model\user\UserAuth,
    model\user\UserSession,
    cache\UserTokenCache,
    model\order\Order,
    service\FileService,
    service\WechatSecurityService,
    service\DashboardAccessService,
    service\sms\SmsDriver,
    service\wechat\WeChatMnpService};
use think\facade\Cache;
use think\facade\Config;

/**
 * 会员逻辑层
 * Class UserLogic
 * @package app\shopapi\logic
 */
class UserLogic extends BaseLogic
{

    /**
     * 同一手机号在统计窗口内允许重置密码的最大次数
     */
    const RESET_PASSWORD_MAX_TIMES = 3;

    /**
     * 重置密码频率统计窗口（秒）：1 小时
     */
    const RESET_PASSWORD_WINDOW = 3600;

    /**
     * 重置密码频率统计缓存键前缀
     */
    const RESET_PASSWORD_LIMIT_PREFIX = 'reset_password_limit:';


    /**
     * @notes 个人中心
     * @param array $userInfo
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @author 段誉
     * @date 2022/9/16 18:04
     */
    public static function center(array $userInfo): array
    {
        $user = User::where(['id' => $userInfo['user_id']])
            ->field('id,sn,sex,account,nickname,real_name,avatar,mobile,create_time,is_new_user,password')
            ->findOrEmpty();

        if ((int)$userInfo['terminal'] === UserTerminalEnum::WECHAT_MMP) {
            $auth = UserAuth::where(['user_id' => $userInfo['user_id'], 'terminal' => $userInfo['terminal']])->find();
            $user['is_auth'] = $auth ? YesNoEnum::YES : YesNoEnum::NO;
        }

        $user['has_password'] = !empty($user['password']);
        $staffId = \app\common\service\StaffService::getStaffIdByUserId((int) $userInfo['user_id']);
        $user['staff_id'] = $staffId;
        $user['is_staff'] = $staffId > 0 ? 1 : 0;
        // 经营看板入口权限：由「绑定后台管理员 + RBAC 节点」决定，不再依赖用户ID白名单
        $user['admin_dashboard_access'] = DashboardAccessService::canAccess((int) $userInfo['user_id']) ? 1 : 0;
        $user->hidden(['password']);
        return $user->toArray();
    }


    /**
     * @notes 个人信息
     * @param $userId
     * @return array
     * @author 段誉
     * @date 2022/9/20 19:45
     */
    public static function info(int $userId)
    {
        $user = User::where(['id' => $userId])
            ->field('id,sn,sex,account,password,nickname,real_name,avatar,mobile,create_time')
            ->findOrEmpty();
        $user['has_password'] = !empty($user['password']);
        $user['has_auth'] = self::hasWechatAuth($userId);
        $user['admin_dashboard_access'] = DashboardAccessService::canAccess($userId) ? 1 : 0;
        $user['version'] = config('project.version');
        $user->hidden(['password']);
        return $user->toArray();
    }


    /**
     * @notes 设置用户信息
     * @param int $userId
     * @param array $params
     * @return User|false
     * @author 段誉
     * @date 2022/9/21 16:53
     */
    public static function setInfo(int $userId, array $params)
    {
        try {
            if (in_array($params['field'], ['nickname', 'real_name'], true)) {
                $checkResult = WechatSecurityService::checkText(
                    $userId,
                    (string)$params['value'],
                    WechatSecurityService::SCENE_PROFILE,
                    [$params['field'] => (string)$params['value']]
                );
                if ($checkResult['hit']) {
                    throw new \Exception(($params['field'] === 'nickname' ? '昵称' : '真实姓名') . '内容可能存在违规风险，请修改后重试');
                }
            }

            if ($params['field'] == "avatar") {
                $params['value'] = FileService::setFileUrl($params['value']);
            }

            return User::update([
                    'id' => $userId,
                    $params['field'] => $params['value']]
            );
        } catch (\Exception $e) {
            self::$error = $e->getMessage();
            return false;
        }
    }


    /**
     * @notes 批量设置用户资料，文本安全检测通过后再统一落库
     * @param int $userId
     * @param array $params
     * @return User|false
     */
    public static function setProfile(int $userId, array $params)
    {
        try {
            $updates = [];

            if (array_key_exists('nickname', $params)) {
                $nickname = trim((string)$params['nickname']);
                if ($nickname === '') {
                    throw new \Exception('昵称不能为空');
                }
                if (mb_strlen($nickname, 'UTF-8') > 32) {
                    throw new \Exception('昵称长度不能超过32位');
                }
                self::checkProfileText($userId, 'nickname', $nickname);
                $updates['nickname'] = $nickname;
            }

            if (array_key_exists('real_name', $params)) {
                $realName = trim((string)$params['real_name']);
                if (mb_strlen($realName, 'UTF-8') > 32) {
                    throw new \Exception('真实姓名长度不能超过32位');
                }
                if ($realName !== '') {
                    self::checkProfileText($userId, 'real_name', $realName);
                }
                $updates['real_name'] = $realName;
            }

            if (array_key_exists('sex', $params)) {
                $sex = (int)$params['sex'];
                if (!in_array($sex, [0, 1, 2], true)) {
                    throw new \Exception('性别参数错误');
                }
                $updates['sex'] = $sex;
            }

            if (array_key_exists('avatar', $params)) {
                $updates['avatar'] = FileService::setFileUrl((string)$params['avatar']);
            }

            if (empty($updates)) {
                throw new \Exception('暂无可保存的修改');
            }

            $updates['id'] = $userId;
            return User::update($updates);
        } catch (\Exception $e) {
            self::$error = $e->getMessage();
            return false;
        }
    }

    /**
     * @notes 检测用户资料文本安全
     * @throws \Exception
     */
    private static function checkProfileText(int $userId, string $field, string $value): void
    {
        $checkResult = WechatSecurityService::checkText(
            $userId,
            $value,
            WechatSecurityService::SCENE_PROFILE,
            [$field => $value]
        );
        if ($checkResult['hit']) {
            throw new \Exception(($field === 'nickname' ? '昵称' : '真实姓名') . '内容可能存在违规风险，请修改后重试');
        }
    }


    /**
     * @notes 是否有微信授权信息
     * @param $userId
     * @return bool
     * @author 段誉
     * @date 2022/9/20 19:36
     */
    public static function hasWechatAuth(int $userId)
    {
        //是否有微信授权登录
        $auth = UserAuth::where(['user_id' => $userId])
            ->where('terminal', UserTerminalEnum::WECHAT_MMP)
            ->findOrEmpty();
        return !$auth->isEmpty();
    }


    /**
     * @notes 重置登录密码
     * @param $params
     * @return bool
     * @author 段誉
     * @date 2022/9/16 18:06
     */
    public static function resetPassword(array $params)
    {
        try {
            $mobile = trim((string)($params['mobile'] ?? ''));
            if ($mobile === '') {
                throw new \Exception('手机号不能为空');
            }

            // 频率闸门：同一手机号 1 小时内最多重置 3 次（防止验证码被绕过后的持续撞库）
            // 注意顺序：闸门必须在「验证码校验之前」只读判定，计数必须在「验证码通过之后」才写入，
            // 否则无效请求（验证码错误）也能消耗额度，攻击者可借此锁死正常用户的重置入口。
            $limitKey = self::RESET_PASSWORD_LIMIT_PREFIX . md5($mobile);
            $resetTimes = (int)Cache::get($limitKey, 0);
            if ($resetTimes >= self::RESET_PASSWORD_MAX_TIMES) {
                throw new \Exception('重置密码过于频繁，请1小时后再试');
            }

            // 校验验证码
            $smsDriver = new SmsDriver();
            if (!$smsDriver->verify($mobile, $params['code'], NoticeEnum::FIND_LOGIN_PASSWORD_CAPTCHA)) {
                throw new \Exception('验证码错误');
            }

            $user = User::where('mobile', $mobile)->findOrEmpty();
            if ($user->isEmpty()) {
                throw new \Exception('账号不存在');
            }

            // 验证码通过后才计数，避免无效请求提前耗尽额度。
            // 使用 Cache::inc() 原子自增，避免「读 → 改 → 写」在并发下丢失更新导致实际可重置次数超过 3 次/小时。
            self::increaseResetPasswordTimes($limitKey);

            // 重置密码
            $passwordSalt = Config::get('project.unique_identification');
            $password = create_password($params['password'], $passwordSalt);

            // 更新
            User::where('id', $user['id'])->update([
                'password' => $password
            ]);

            // 密码已变更，立即失效该用户全部终端的登录态，强制重新登录
            self::expireAllUserSession((int)$user['id']);

            return true;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }


    /**
     * @notes 原子自增「重置密码」计数并维护 1 小时窗口
     * 说明：File/Redis 驱动在键首次创建时不会自动带上 TTL，因此首次自增后补设窗口，
     * 保证「1 小时内最多 3 次」的窗口语义。
     * @param string $limitKey
     * @return void
     */
    private static function increaseResetPasswordTimes(string $limitKey): void
    {
        $times = Cache::inc($limitKey);
        if ($times === false) {
            // 缓存不可用时降级为直接写入，避免因缓存异常放行无限次重置
            Cache::set($limitKey, 1, self::RESET_PASSWORD_WINDOW);
            return;
        }

        if ((int)$times <= 1) {
            Cache::set($limitKey, (int)$times, self::RESET_PASSWORD_WINDOW);
        }
    }


    /**
     * @notes 失效指定用户的全部登录态（强制重新登录）
     * 说明：token 的权威存储为 la_user_session + 缓存，故失效会话即可让旧 token 全部失效；
     * 不删除 la_user_auth（微信 openid 绑定），否则会导致用户微信授权登录被破坏。
     * @param int $userId
     * @return void
     */
    private static function expireAllUserSession(int $userId): void
    {
        $sessions = UserSession::where('user_id', $userId)->select();
        if ($sessions->isEmpty()) {
            return;
        }

        $tokenCache = new UserTokenCache();
        $now = time();
        foreach ($sessions as $session) {
            $tokenCache->deleteUserInfo((string)$session['token']);
            UserSession::where('id', $session['id'])->update([
                'expire_time' => $now,
                'update_time' => $now,
            ]);
        }
    }


    /**
     * @notes 修稿密码
     * @param $params
     * @param $userId
     * @return bool
     * @author 段誉
     * @date 2022/9/20 19:13
     */
    public static function changePassword(array $params, int $userId)
    {
        try {
            $user = User::findOrEmpty($userId);
            if ($user->isEmpty()) {
                throw new \Exception('用户不存在');
            }

            // 密码盐
            $passwordSalt = Config::get('project.unique_identification');

            if (!empty($user['password'])) {
                if (empty($params['old_password'])) {
                    throw new \Exception('请填写旧密码');
                }
                $oldPassword = create_password($params['old_password'], $passwordSalt);
                if ($oldPassword != $user['password']) {
                    throw new \Exception('原密码不正确');
                }
            }

            // 保存密码
            $password = create_password($params['password'], $passwordSalt);
            $user->password = $password;
            $user->save();

            return true;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }


    /**
     * @notes 获取小程序手机号
     * @param array $params
     * @return bool
     * @throws \Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface
     * @author 段誉
     * @date 2023/2/27 11:49
     */
    public static function getMobileByMnp(array $params)
    {
        try {
            $response = (new WeChatMnpService())->getUserPhoneNumber($params['code']);
            $phoneNumber = $response['phone_info']['purePhoneNumber'] ?? '';
            if (empty($phoneNumber)) {
                throw new \Exception('获取手机号码失败');
            }

            $user = User::where([
                ['mobile', '=', $phoneNumber],
                ['id', '<>', $params['user_id']]
            ])->findOrEmpty();

            if (!$user->isEmpty()) {
                throw new \Exception('手机号已被其他账号绑定');
            }

            // 绑定手机号
            User::update([
                'id' => $params['user_id'],
                'mobile' => $phoneNumber
            ]);

            return true;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }


    /**
     * @notes 绑定手机号
     * @param $params
     * @return bool
     * @author 段誉
     * @date 2022/9/21 17:28
     */
    public static function bindMobile(array $params)
    {
        try {
            // 变更手机号场景
            $sceneId = NoticeEnum::CHANGE_MOBILE_CAPTCHA;
            $where = [
                ['id', '=', $params['user_id']],
                ['mobile', '=', $params['mobile']]
            ];

            // 绑定手机号场景
            if ($params['type'] == 'bind') {
                $sceneId = NoticeEnum::BIND_MOBILE_CAPTCHA;
                $where = [
                    ['mobile', '=', $params['mobile']]
                ];
            }

            // 校验短信
            $checkSmsCode = (new SmsDriver())->verify($params['mobile'], $params['code'], $sceneId);
            if (!$checkSmsCode) {
                throw new \Exception('验证码错误');
            }

            $user = User::where($where)->findOrEmpty();
            if (!$user->isEmpty()) {
                throw new \Exception('该手机号已被使用');
            }

            User::update([
                'id' => $params['user_id'],
                'mobile' => $params['mobile'],
            ]);

            return true;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * @notes 获取用户婚期信息
     * @param int $userId 用户ID
     * @return array 婚期信息
     * @author AI
     * @date 2026/01/22
     */
    public static function weddingDate(int $userId): array
    {
        try {
            $customer = Customer::findByUserId($userId);
            $weddingDate = trim((string) ($customer->wedding_date ?? ''));
            $weddingVenue = trim((string) ($customer->wedding_venue ?? ''));

            // 查询用户最近的已支付订单
            $order = Order::where('user_id', $userId)
                ->where('pay_status', 1) // 已支付
                ->order('service_date', 'asc')
                ->field('id, order_sn, service_date, contact_name')
                ->findOrEmpty();

            // 如果客户资料中没有婚期
            if ($weddingDate === '') {
                return [
                    'has_order' => false,
                    'wedding_date' => '',
                    'wedding_date_text' => '',
                    'days_remaining' => 0,
                    'service_date' => '',
                    'order_sn' => '',
                    'wedding_venue' => '',
                ];
            }

            // 计算剩余天数
            $weddingTimestamp = strtotime($weddingDate);
            $currentTimestamp = strtotime(date('Y-m-d'));
            $daysRemaining = (int)ceil(($weddingTimestamp - $currentTimestamp) / 86400);

            // 格式化日期文本
            $weddingDateText = date('Y年m月d日', $weddingTimestamp);

            return [
                'has_order' => true,
                'wedding_date' => $weddingDate,
                'wedding_date_text' => $weddingDateText,
                'days_remaining' => $daysRemaining,
                'service_date' => $order->service_date ?? '',
                'order_sn' => $order->order_sn ?? '',
                'contact_name' => $order->contact_name ?? '',
                'wedding_venue' => $weddingVenue,
            ];
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return [
                'has_order' => false,
                'wedding_date' => '',
                'wedding_date_text' => '',
                'days_remaining' => 0,
                'service_date' => '',
                'order_sn' => '',
                'wedding_venue' => '',
            ];
        }
    }

}
