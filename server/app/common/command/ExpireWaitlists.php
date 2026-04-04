<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 超过预约日期的候补自动失效命令
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\common\command;

use app\common\model\schedule\Waitlist;
use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\facade\Log;

class ExpireWaitlists extends Command
{
    protected function configure()
    {
        $this->setName('expire_waitlists')
            ->setDescription('超过预约日期的候补自动标记为已过期');
    }

    protected function execute(Input $input, Output $output)
    {
        try {
            $handled = 0;
            while (true) {
                $count = Waitlist::processPastDateWaitlists(100);
                if ($count <= 0) {
                    break;
                }

                $handled += $count;
            }

            $output->writeln('expired waitlists: ' . $handled);
            return true;
        } catch (\Throwable $e) {
            Log::write('候补超期自动失效失败：' . $e->getMessage());
            $output->writeln('expire_waitlists failed: ' . $e->getMessage());
            return false;
        }
    }
}
