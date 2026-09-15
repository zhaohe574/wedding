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
namespace app\adminapi\logic\user;

use app\common\enum\user\UserTerminalEnum;
use app\common\logic\BaseLogic;
use app\common\model\user\User;
use app\common\service\UserRiskControlService;
use think\facade\Db;

/**
 * 用户逻辑层
 * Class UserLogic
 * @package app\adminapi\logic\user
 */
class UserLogic extends BaseLogic
{

    /**
     * @notes 用户详情
     * @param int $userId
     * @return array
     * @author 段誉
     * @date 2022/9/22 16:32
     */
    public static function detail(int $userId): array
    {
        $field = [
            'id', 'sn', 'account', 'nickname', 'avatar', 'real_name',
            'sex', 'mobile', 'create_time', 'login_time', 'channel',
            'wechat_risk_rank', 'manual_risk_rank',
            'risk_rank_update_time',
        ];

        $user = User::where(['id' => $userId])->field($field)
            ->findOrEmpty();

        $user['channel'] = UserTerminalEnum::getTermInalDesc($user['channel']);
        $user->sex = $user->getData('sex');
        $data = $user->toArray();
        $data = array_merge($data, UserRiskControlService::buildRiskInfo($data));
        $data['risk_rank_update_time'] = !empty($data['risk_rank_update_time'])
            ? date('Y-m-d H:i:s', (int)$data['risk_rank_update_time'])
            : '';
        return $data;
    }


    /**
     * @notes 更新用户信息
     * @param array $params
     * @return User
     * @author 段誉
     * @date 2022/9/22 16:38
     */
    public static function setUserInfo(array $params)
    {
        if ($params['field'] === 'manual_risk_rank') {
            return User::update([
                'id' => $params['id'],
                'manual_risk_rank' => UserRiskControlService::normalizeManualRank((int)$params['value']),
                'risk_rank_update_time' => time(),
            ]);
        }

        return User::update([
            'id' => $params['id'],
            $params['field'] => $params['value']
        ]);
    }



}
