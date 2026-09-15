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

namespace app\common\enum\user;

/**
 * 业务账号登录终端
 * Class terminalEnum
 * @package app\common\enum
 */
class UserTerminalEnum
{
    const WECHAT_MMP = 1; //微信小程序


    const ALL_TERMINAL = [
        self::WECHAT_MMP,
    ];

    /**
     * @notes 获取终端
     * @param bool $from
     * @return array|mixed|string
     * @author cjhao
     * @date 2021/7/30 18:09
     */
    public static function getTermInalDesc($from = true)
    {
        $desc = [
            self::WECHAT_MMP    => '微信小程序',
        ];
        if(true === $from){
            return $desc;
        }
        return $desc[$from] ?? '';
    }
}
