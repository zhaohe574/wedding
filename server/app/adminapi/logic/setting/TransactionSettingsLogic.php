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

namespace app\adminapi\logic\setting;

use app\common\logic\BaseLogic;
use app\common\service\ConfigService;
use think\facade\Db;

/**
 * 交易设置逻辑
 * Class TransactionSettingsLogic
 * @package app\adminapi\logic\setting
 */
class TransactionSettingsLogic extends BaseLogic
{
    /**
     * @notes 获取交易设置
     * @return array
     * @author ljj
     * @date 2022/2/15 11:40 上午
     */
    public static function getConfig()
    {
        $config = [
            'cancel_unpaid_orders' => (int) ConfigService::get('transaction', 'cancel_unpaid_orders', 1),
            'cancel_unpaid_orders_times' => (int) ConfigService::get('transaction', 'cancel_unpaid_orders_times', 30),
            'staff_confirm_timeout_enabled' => (int) ConfigService::get('transaction', 'staff_confirm_timeout_enabled', 0),
            'staff_confirm_timeout_action' => ConfigService::get('transaction', 'staff_confirm_timeout_action', 'cancel'),
            'staff_confirm_timeout_minutes' => (int) ConfigService::get('transaction', 'staff_confirm_timeout_minutes', 60),
            'verification_orders' => (int) ConfigService::get('transaction', 'verification_orders', 1),
            'verification_orders_times' => (int) ConfigService::get('transaction', 'verification_orders_times', 24),
        ];

        return $config;
    }

    /**
     * @notes 设置交易设置
     * @param $params
     * @author ljj
     * @date 2022/2/15 11:49 上午
     */
    public static function setConfig($params)
    {
        $oldStaffConfirmEnabled = (int) ConfigService::get('transaction', 'staff_confirm_timeout_enabled', 0);

        ConfigService::set('transaction', 'cancel_unpaid_orders', (int) ($params['cancel_unpaid_orders'] ?? 1));
        ConfigService::set('transaction', 'staff_confirm_timeout_enabled', (int) ($params['staff_confirm_timeout_enabled'] ?? 0));
        ConfigService::set('transaction', 'staff_confirm_timeout_action', (string) ($params['staff_confirm_timeout_action'] ?? 'cancel'));
        ConfigService::set('transaction', 'verification_orders', (int) ($params['verification_orders'] ?? 1));

        if (isset($params['cancel_unpaid_orders_times'])) {
            ConfigService::set('transaction', 'cancel_unpaid_orders_times', $params['cancel_unpaid_orders_times']);
        }

        if (isset($params['staff_confirm_timeout_minutes'])) {
            ConfigService::set('transaction', 'staff_confirm_timeout_minutes', $params['staff_confirm_timeout_minutes']);
        }

        if (isset($params['verification_orders_times'])) {
            ConfigService::set('transaction', 'verification_orders_times', $params['verification_orders_times']);
        }

        if (
            $oldStaffConfirmEnabled !== 1
            && (int) $params['staff_confirm_timeout_enabled'] === 1
            && isset($params['staff_confirm_timeout_minutes'])
        ) {
            \app\common\model\order\Order::where('order_status', \app\common\model\order\Order::STATUS_PENDING_CONFIRM)
                ->where('confirm_deadline_time', 0)
                ->update([
                    'confirm_deadline_time' => Db::raw('create_time + ' . ((int) $params['staff_confirm_timeout_minutes'] * 60)),
                    'update_time' => time(),
                ]);
        }
    }
}
