<?php

declare(strict_types=1);

namespace app\common\command;

use app\common\service\WechatNotificationService;
use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\facade\Log;

/**
 * 公众号通知队列派发命令。
 */
class SendOaNotifications extends Command
{
    protected function configure()
    {
        $this->setName('send_oa_notifications')
            ->setDescription('扫描并派发到期的公众号服务通知');
    }

    protected function execute(Input $input, Output $output)
    {
        try {
            $processed = 0;
            $success = 0;
            $failed = 0;

            // 单次最多处理五千条，持续新增消息留给下一轮，避免长期占用调度器。
            for ($batch = 0; $batch < 50; $batch++) {
                $events = \app\common\service\BusinessNotificationService::dispatch(100);
                $result = WechatNotificationService::dispatchPendingLogs(100);
                if (($result['processed'] ?? 0) <= 0 && !$events) {
                    break;
                }

                $processed += (int) ($result['processed'] ?? 0);
                $success += (int) ($result['success'] ?? 0);
                $failed += (int) ($result['failed'] ?? 0);
            }

            $output->writeln('processed: ' . $processed);
            $output->writeln('success: ' . $success);
            $output->writeln('failed: ' . $failed);
            return 0;
        } catch (\Throwable $e) {
            Log::error('公众号通知派发命令执行失败：' . $e->getMessage());
            $output->writeln('send_oa_notifications failed: ' . $e->getMessage());
            return 1;
        }
    }
}
