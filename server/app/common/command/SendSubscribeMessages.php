<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 订阅消息派发命令
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\common\command;

use app\common\service\SubscribeMessageService;
use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\facade\Log;

class SendSubscribeMessages extends Command
{
    protected function configure()
    {
        $this->setName('send_subscribe_messages')
            ->setDescription('扫描并派发到期的订阅消息');
    }

    protected function execute(Input $input, Output $output)
    {
        try {
            $processed = 0;
            $success = 0;
            $failed = 0;

            while (true) {
                $result = SubscribeMessageService::dispatchPendingLogs(100);
                if (($result['processed'] ?? 0) <= 0) {
                    break;
                }

                $processed += (int) ($result['processed'] ?? 0);
                $success += (int) ($result['success'] ?? 0);
                $failed += (int) ($result['failed'] ?? 0);
            }

            $output->writeln('processed: ' . $processed);
            $output->writeln('success: ' . $success);
            $output->writeln('failed: ' . $failed);

            return true;
        } catch (\Throwable $e) {
            Log::error('订阅消息派发命令执行失败：' . $e->getMessage());
            $output->writeln('send_subscribe_messages failed: ' . $e->getMessage());
            return false;
        }
    }
}
