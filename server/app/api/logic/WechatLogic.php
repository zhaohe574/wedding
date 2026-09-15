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

use app\common\logic\BaseLogic;
use app\common\service\wechat\WechatOaBindingService;

/**
 * 微信
 * Class WechatLogic
 * @package app\api\logic
 */
class WechatLogic extends BaseLogic
{
    /**
     * 获取公众号通知绑定入口。
     */
    public static function oaSubscribeEntry(int $userId): array|false
    {
        try {
            return WechatOaBindingService::createEntry($userId);
        } catch (\Throwable $e) {
            self::setError('生成公众号订阅入口失败：' . $e->getMessage());
            return false;
        }
    }

    /**
     * 获取当前用户公众号关注状态。
     */
    public static function oaSubscribeStatus(int $userId): array
    {
        return WechatOaBindingService::getStatus($userId);
    }

}
