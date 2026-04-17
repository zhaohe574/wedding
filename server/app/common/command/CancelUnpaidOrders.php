<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 超时未支付订单自动取消命令
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\common\command;

use app\common\model\order\Order;
use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\facade\Log;

class CancelUnpaidOrders extends Command
{
    protected function configure()
    {
        $this->setName('cancel_unpaid_orders')
            ->setDescription('超时未支付订单自动取消');
    }

    protected function execute(Input $input, Output $output)
    {
        try {
            if (!Order::isUnpaidAutoCancelEnabled()) {
                $output->writeln('unpaid auto cancel disabled');
                return true;
            }

            $handled = 0;
            while (true) {
                $orderIds = Order::where('order_status', Order::STATUS_PENDING_PAY)
                    ->where('pay_status', Order::PAY_STATUS_UNPAID)
                    ->where('pay_deadline_time', '>', 0)
                    ->where('pay_deadline_time', '<=', time())
                    ->limit(100)
                    ->column('id');

                if (empty($orderIds)) {
                    break;
                }

                foreach ($orderIds as $orderId) {
                    [$success, ] = Order::autoHandleExpiredPendingPay((int)$orderId);
                    if ($success) {
                        $handled++;
                    }
                }
            }

            $output->writeln('cancelled orders: ' . $handled);
            return true;
        } catch (\Throwable $e) {
            Log::write('超时未支付订单自动取消失败：' . $e->getMessage());
            $output->writeln('cancel_unpaid_orders failed: ' . $e->getMessage());
            return false;
        }
    }
}
