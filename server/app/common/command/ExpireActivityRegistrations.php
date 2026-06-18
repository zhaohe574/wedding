<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 活动报名待支付过期释放命令
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\common\command;

use app\common\service\ActivityRegistrationService;
use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\facade\Log;

class ExpireActivityRegistrations extends Command
{
    protected function configure()
    {
        $this->setName('expire_activity_registrations')
            ->setDescription('活动报名待支付超时自动释放');
    }

    protected function execute(Input $input, Output $output)
    {
        try {
            $handled = 0;
            $failed = 0;

            while (true) {
                $result = ActivityRegistrationService::expirePendingRegistrations(200);
                $handled += (int)($result['expired'] ?? 0);
                $failed += (int)($result['failed'] ?? 0);

                if ((int)($result['scanned'] ?? 0) < 200) {
                    break;
                }
            }

            $output->writeln('expired activity registrations: ' . $handled . ', failed: ' . $failed);
            return true;
        } catch (\Throwable $e) {
            Log::write('活动报名待支付超时自动释放失败：' . $e->getMessage());
            $output->writeln('expire_activity_registrations failed: ' . $e->getMessage());
            return false;
        }
    }
}
