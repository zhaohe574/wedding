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

namespace app\common\logic;

use app\common\model\aftersale\ServiceCallback;
use app\common\model\order\Payment as OrderPayment;
use app\common\service\OrderNotificationService;
use app\common\service\StaffSettlementRepayService;
use think\facade\Db;
use think\facade\Log;

/**
 * 支付成功后处理订单状态
 * Class PayNotifyLogic
 * @package app\api\logic
 */
class PayNotifyLogic extends BaseLogic
{

    public static function handle($action, $orderSn, $extra = [])
    {
        if (!in_array($action, ['order', StaffSettlementRepayService::PAY_FROM], true)) {
            return '不支持的支付业务';
        }
        for ($attempt = 0; $attempt < 2; $attempt++) {
            Db::startTrans();
            try {
            $result = self::$action($orderSn, $extra);
            Db::commit();
            try {
                self::dispatchAfterCommit($action, $result);
            } catch (\Throwable $e) {
                Log::error('到账后通知失败：' . $e->getMessage());
            }
                return $result;
            } catch (\Throwable $e) {
                Db::rollback();
                if ($attempt === 0 && (str_contains($e->getMessage(), '1213') || str_contains($e->getMessage(), '40001'))) {
                    usleep(50000);
                    continue;
                }
            Log::write(implode('-', [
                __CLASS__,
                __FUNCTION__,
                $e->getFile(),
                $e->getLine(),
                $e->getMessage()
            ]));
            self::setError($e->getMessage());
            return $e->getMessage();
            }
        }
        return '支付回调处理失败';
    }



    /**
     * @notes 订单支付回调
     * @param string $paymentSn
     * @param array $extra
     * @return array
     */
    public static function order(string $paymentSn, array $extra = []): array
    {
        [$success, $message, $context] = OrderPayment::paySuccess(
            $paymentSn,
            $extra['transaction_id'] ?? '',
            $extra['callback_data'] ?? []
        );

        if (!$success) {
            throw new \Exception($message ?: '订单支付回调处理失败');
        }

        return $context;
    }

    /**
     * @notes 服务人员补交平台抽成回调
     */
    public static function staff_settlement_repay(string $paySn, array $extra = []): array
    {
        [$success, $message, $context] = StaffSettlementRepayService::paySuccess(
            $paySn,
            $extra['transaction_id'] ?? '',
            $extra['callback_data'] ?? []
        );

        if (!$success) {
            throw new \Exception($message ?: '服务人员补交平台抽成回调处理失败');
        }

        return $context;
    }

    /**
     * @notes 事务提交后补发站内消息
     * @param string $action
     * @param mixed $result
     * @return void
     */
    private static function dispatchAfterCommit(string $action, $result): void
    {
        if ($action !== 'order' || !is_array($result)) {
            return;
        }

        if (!empty($result['refund_id'])) {
            OrderNotificationService::notifyUserAndStaffOnRefundApplied((int)$result['refund_id']);
        }

        if (empty($result['should_notify']) || empty($result['order_id']) || empty($result['pay_type'])) {
            return;
        }

        OrderNotificationService::notifyUserAndStaffOnPaymentSuccess(
            (int)$result['order_id'],
            (int)$result['pay_type']
        );

        if (!empty($result['should_notify_completed'])) {
            ServiceCallback::autoCreateAfterServiceCallback((int)$result['order_id']);
            OrderNotificationService::notifyOnOrderCompleted((int)$result['order_id']);
        }
    }


}
