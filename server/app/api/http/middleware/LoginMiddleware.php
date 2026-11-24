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
declare (strict_types=1);

namespace app\api\http\middleware;


use app\common\cache\UserTokenCache;
use app\common\enum\ErrorCodeEnum;
use app\common\model\user\User;
use app\common\service\LoginConfigService;
use app\common\service\JsonService;
use app\api\service\UserTokenService;
use think\facade\Config;

class LoginMiddleware
{
    /**
     * 强制绑定手机开启时放行的接口（controller/action，全小写）
     */
    private const COERCE_MOBILE_WHITELIST = [
        'login/updateuser',
        'login/logout',
        'user/getmobilebymnp',
        'user/bindmobile',
        'user/info',
        'upload/image',
    ];
    /**
     * @notes 登录验证
     * @param $request
     * @param \Closure $next
     * @return mixed|\think\response\Json
     * @author 令狐冲
     * @date 2021/7/1 17:33
     */
    public function handle($request, \Closure $next)
    {
        $token = $request->header('token');
        //判断接口是否免登录
        $isNotNeedLogin = $request->controllerObject->isNotNeedLogin();

        //不直接判断$isNotNeedLogin结果，使不需要登录的接口通过，为了兼容某些接口可以登录或不登录访问
        if (empty($token) && !$isNotNeedLogin) {
            //没有token并且该地址需要登录才能访问, 指定show为0，前端不弹出此报错
            return JsonService::fail('请求参数缺token', [], 0, 0, ErrorCodeEnum::TOKEN_EXPIRED);
        }

        $userInfo = (new UserTokenCache())->getUserInfo($token);

        if (empty($userInfo) && !$isNotNeedLogin) {
            //token过期无效并且该地址需要登录才能访问
            return JsonService::fail('登录超时，请重新登录', [], -1, 0, ErrorCodeEnum::TOKEN_EXPIRED);
        }

        //token临近过期，自动续期
        if ($userInfo) {
            //获取临近过期自动续期时长
            $beExpireDuration = Config::get('project.user_token.be_expire_duration');
            //token续期
            if (time() > ($userInfo['expire_time'] - $beExpireDuration)) {
                $result = UserTokenService::overtimeToken($token);
                //续期失败（数据表被删除导致）
                if (empty($result)) {
                    return JsonService::fail('登录过期', [], -1, 1, ErrorCodeEnum::TOKEN_EXPIRED);
                }
            }
        }

        //强制绑定手机：拦截未绑定手机号的已登录账号（放行完成绑定所需白名单接口）
        if (!empty($userInfo) && !$isNotNeedLogin
            && (int)(LoginConfigService::getConfig()['coerce_mobile'] ?? 0) === 1) {
            $mobile = trim((string)($userInfo['mobile'] ?? ''));
            if ($mobile === '') {
                //缓存未带手机号时查库兜底，命中则回写缓存后放行
                $dbMobile = trim((string)User::where('id', '=', (int)($userInfo['user_id'] ?? 0))->value('mobile'));
                if ($dbMobile !== '') {
                    $userInfo['mobile'] = $dbMobile;
                    (new UserTokenCache())->setUserInfo($token);
                } else {
                    $accessUri = strtolower((string)$request->controller() . '/' . (string)$request->action());
                    if (!in_array($accessUri, self::COERCE_MOBILE_WHITELIST, true)) {
                        return JsonService::fail('请先绑定手机号', ['need_bind_mobile' => 1], -1, 1, ErrorCodeEnum::TOKEN_EXPIRED);
                    }
                }
            }
        }

        //给request赋值，用于控制器
        $request->userInfo = $userInfo;
        $request->userId = $userInfo['user_id'] ?? 0;

        return $next($request);
    }

}