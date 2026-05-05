<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - CRM 流失预警生成命令
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\common\command;

use app\adminapi\logic\crm\LossWarningLogic;
use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\facade\Log;

/**
 * CRM 流失预警生成与推送命令。
 */
class GenerateLossWarnings extends Command
{
    protected function configure()
    {
        $this->setName('generate_loss_warnings')
            ->setDescription('生成长期未跟进客户流失预警并推送企业微信消息');
    }

    protected function execute(Input $input, Output $output)
    {
        try {
            $result = LossWarningLogic::generateAndPushForCrontab();
            $output->writeln('generated: ' . (int)($result['generated'] ?? 0));
            $output->writeln('push_success: ' . (int)($result['pushed']['success'] ?? 0));
            $output->writeln('push_failed: ' . (int)($result['pushed']['failed'] ?? 0));
            return true;
        } catch (\Throwable $e) {
            Log::error('CRM流失预警命令执行失败：' . $e->getMessage());
            $output->writeln('generate_loss_warnings failed: ' . $e->getMessage());
            return false;
        }
    }
}
