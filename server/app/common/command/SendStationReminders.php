<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 站内提醒命令
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\common\command;

use app\common\model\order\Order;
use app\common\model\order\OrderPause;
use app\common\service\OrderNotificationService;
use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\facade\Log;

class SendStationReminders extends Command
{
    protected function configure()
    {
        $this->setName('send_station_reminders')
            ->setDescription('发送服务前提醒与暂停到期站内提醒');
    }

    protected function execute(Input $input, Output $output)
    {
        try {
            $serviceCount = $this->sendServiceReminders();
            $pauseCount = $this->sendPauseReminders();

            $output->writeln('service reminders: ' . $serviceCount);
            $output->writeln('pause reminders: ' . $pauseCount);

            return true;
        } catch (\Throwable $e) {
            Log::error('站内提醒命令执行失败：' . $e->getMessage());
            $output->writeln('send_station_reminders failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * @notes 发送服务前一天提醒
     * @return int
     */
    private function sendServiceReminders(): int
    {
        $targetDate = date('Y-m-d', strtotime('+1 day'));

        $orderIds = Order::where(function ($query) use ($targetDate) {
                $query->where('service_date', $targetDate)
                    ->whereOr('id', 'in', function ($subQuery) use ($targetDate) {
                        $subQuery->name('order_item')
                            ->where('service_date', $targetDate)
                            ->field('order_id');
                    });
            })
            ->where('is_paused', 0)
            ->whereIn('order_status', [Order::STATUS_PENDING_PAY, Order::STATUS_PAID, Order::STATUS_IN_SERVICE])
            ->where(function ($query) {
                $query->whereIn('order_status', [Order::STATUS_PAID, Order::STATUS_IN_SERVICE])
                    ->whereOr(function ($subQuery) {
                        $subQuery->where('order_status', Order::STATUS_PENDING_PAY)
                            ->where('deposit_amount', '>', 0)
                            ->where('deposit_paid', 1);
                    });
            })
            ->distinct(true)
            ->column('id');

        $handled = 0;
        foreach ($orderIds as $orderId) {
            try {
                OrderNotificationService::sendServiceReminder((int)$orderId);
                $handled++;
            } catch (\Throwable $e) {
                Log::error('发送服务提醒失败，订单ID：' . (int)$orderId . '，错误：' . $e->getMessage());
            }
        }

        return $handled;
    }

    /**
     * @notes 发送暂停到期提醒
     * @return int
     */
    private function sendPauseReminders(): int
    {
        $maxDays = (int)OrderPause::where('pause_status', OrderPause::STATUS_PAUSED)
            ->max('remind_before_days');
        $maxDays = max($maxDays, 3, 1);

        $pauses = OrderPause::getExpiringPauses($maxDays);
        $handled = 0;

        foreach ($pauses as $pause) {
            $pauseId = (int)($pause['id'] ?? 0);
            if ($pauseId <= 0) {
                continue;
            }

            $days = max((int)($pause['remind_before_days'] ?? 3), 1);
            $targetDate = date('Y-m-d', strtotime("+{$days} days"));
            $pauseEndDate = (string)($pause['pause_end_date'] ?? '');

            if ($pauseEndDate === '' || $pauseEndDate > $targetDate) {
                continue;
            }

            try {
                if (OrderNotificationService::sendPauseExpiringReminder($pauseId)) {
                    OrderPause::markReminded($pauseId);
                    $handled++;
                }
            } catch (\Throwable $e) {
                Log::error('发送暂停到期提醒失败，暂停ID：' . $pauseId . '，错误：' . $e->getMessage());
            }
        }

        return $handled;
    }
}
