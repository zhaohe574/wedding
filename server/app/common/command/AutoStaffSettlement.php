<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 自动服务人员结算命令
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\common\command;

use app\common\service\StaffSettlementService;
use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\facade\Log;

/**
 * 自动服务人员结算命令
 * Class AutoStaffSettlement
 * @package app\common\command
 */
class AutoStaffSettlement extends Command
{
    protected function configure()
    {
        $this->setName('auto_staff_settlement')
            ->setDescription('自动生成服务人员结算并处理微信商家转账状态');
    }

    protected function execute(Input $input, Output $output)
    {
        try {
            $service = new StaffSettlementService();
            $generated = $service->generateFromCompletedOrders(date('Y-m-d', strtotime('-90 days')), date('Y-m-d'));
            $processed = $service->processPendingTransfers(100);

            $output->writeln('generated settlements: ' . $generated);
            $output->writeln('transfer sent: ' . (int)($processed['sent_count'] ?? 0));
            $output->writeln('transfer success: ' . (int)($processed['success_count'] ?? 0));
            $output->writeln('transfer wait confirm: ' . (int)($processed['wait_confirm_count'] ?? 0));
            $output->writeln('transfer failed: ' . (int)($processed['fail_count'] ?? 0));
            return true;
        } catch (\Throwable $e) {
            Log::write('自动服务人员结算失败：' . $e->getMessage());
            $output->writeln('auto_staff_settlement failed: ' . $e->getMessage());
            return false;
        }
    }
}
