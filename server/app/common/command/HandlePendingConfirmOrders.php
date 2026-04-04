<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 服务人员确认超时自动处理命令
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\common\command;

use app\common\model\order\Order;
use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\facade\Log;

class HandlePendingConfirmOrders extends Command
{
    protected function configure()
    {
        $this->setName('handle_pending_confirm_orders')
            ->setDescription('服务人员确认超时自动处理');
    }

    protected function execute(Input $input, Output $output)
    {
        try {
            if (!Order::isStaffConfirmTimeoutEnabled()) {
                $output->writeln('staff confirm timeout disabled');
                return true;
            }

            $handled = 0;
            while (true) {
                $orderIds = Order::where('order_status', Order::STATUS_PENDING_CONFIRM)
                    ->where('confirm_deadline_time', '>', 0)
                    ->where('confirm_deadline_time', '<=', time())
                    ->limit(100)
                    ->column('id');

                if (empty($orderIds)) {
                    break;
                }

                foreach ($orderIds as $orderId) {
                    [$success, ] = Order::autoHandleExpiredPendingConfirm((int)$orderId);
                    if ($success) {
                        $handled++;
                    }
                }
            }

            $output->writeln('handled pending confirm orders: ' . $handled);
            return true;
        } catch (\Throwable $e) {
            Log::write('服务人员确认超时自动处理失败：' . $e->getMessage());
            $output->writeln('handle_pending_confirm_orders failed: ' . $e->getMessage());
            return false;
        }
    }
}
