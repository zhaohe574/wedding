<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 重算服务人员服务统计
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\common\command;

use app\common\model\staff\Staff;
use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\console\input\Option;
use think\facade\Log;

class RefreshStaffServiceStats extends Command
{
    protected function configure()
    {
        $this->setName('refresh_staff_service_stats')
            ->setDescription('重算服务人员已服务场次、近一年评分和评价数量')
            ->addOption('staff_id', null, Option::VALUE_OPTIONAL, '指定服务人员ID');
    }

    protected function execute(Input $input, Output $output)
    {
        $staffId = (int) $input->getOption('staff_id');

        try {
            if ($staffId > 0) {
                $stats = Staff::refreshServiceStats($staffId);
                $output->writeln(sprintf(
                    'staff #%d refreshed. order_count=%d review_count=%d rating=%.1f',
                    $staffId,
                    (int) $stats['order_count'],
                    (int) $stats['review_count'],
                    (float) $stats['rating']
                ));
                return true;
            }

            $count = Staff::refreshAllServiceStats();
            $output->writeln(sprintf('done. refreshed_staff_count=%d', $count));
            return true;
        } catch (\Throwable $e) {
            Log::write('重算服务人员服务统计失败：' . $e->getMessage());
            $output->writeln('refresh_staff_service_stats failed: ' . $e->getMessage());
            return false;
        }
    }
}
