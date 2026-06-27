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
    model\order\Order,
    service\FileService,
    service\WechatSecurityService,
    service\sms\SmsDriver,
    service\wechat\WeChatMnpService};
use think\facade\Config;

/**
 * 会员逻辑层
 * Class UserLogic
 * @package app\shopapi\logic
 */
class UserLogic extends BaseLogic
{

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
            ->field('id,sn,sex,account,nickname,real_name,avatar,mobile,create_time,is_new_user,user_money,user_points,password')
            ->findOrEmpty();

        if (in_array($userInfo['terminal'], [UserTerminalEnum::WECHAT_MMP, UserTerminalEnum::WECHAT_OA])) {
            $auth = UserAuth::where(['user_id' => $userInfo['user_id'], 'terminal' => $userInfo['terminal']])->find();
            $user['is_auth'] = $auth ? YesNoEnum::YES : YesNoEnum::NO;
        }

        $user['has_password'] = !empty($user['password']);
        $staffId = \app\common\service\StaffService::getStaffIdByUserId((int) $userInfo['user_id']);
        $user['staff_id'] = $staffId;
        $user['is_staff'] = $staffId > 0 ? 1 : 0;
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
            ->field('id,sn,sex,account,password,nickname,real_name,avatar,mobile,create_time,user_money,user_points')
            ->findOrEmpty();
        $user['has_password'] = !empty($user['password']);
        $user['has_auth'] = self::hasWechatAuth($userId);
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
        $terminal = [UserTerminalEnum::WECHAT_MMP, UserTerminalEnum::WECHAT_OA,UserTerminalEnum::PC];
        $auth = UserAuth::where(['user_id' => $userId])
            ->whereIn('terminal', $terminal)
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
            // 校验验证码
            $smsDriver = new SmsDriver();
            if (!$smsDriver->verify($params['mobile'], $params['code'], NoticeEnum::FIND_LOGIN_PASSWORD_CAPTCHA)) {
                throw new \Exception('验证码错误');
            }

            // 重置密码
            $passwordSalt = Config::get('project.unique_identification');
            $password = create_password($params['password'], $passwordSalt);

            // 更新
            User::where('mobile', $params['mobile'])->update([
                'password' => $password
            ]);

            return true;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
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
