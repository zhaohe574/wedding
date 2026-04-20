<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 重建订单确认函图片资产
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\common\command;

use app\common\model\order\OrderConfirmLetter;
use app\common\service\OrderConfirmLetterService;
use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\console\input\Option;
use think\facade\Log;

class RebuildOrderConfirmLetterAssets extends Command
{
    protected function configure()
    {
        $this->setName('rebuild_order_confirm_letter_assets')
            ->setDescription('重建订单确认函图片资产')
            ->addOption('order_id', null, Option::VALUE_OPTIONAL, '指定订单ID')
            ->addOption('all', null, Option::VALUE_NONE, '重建所有确认函版本，不仅限当前有效或已推送版本');
    }

    protected function execute(Input $input, Output $output)
    {
        $orderId = (int) $input->getOption('order_id');
        $includeAll = (bool) $input->getOption('all');

        try {
            $query = OrderConfirmLetter::order('id', 'asc');
            if ($orderId > 0) {
                $query->where('order_id', $orderId);
            }

            if (!$includeAll) {
                $query->where(function ($subQuery) {
                    $subQuery->where('is_outdated', OrderConfirmLetter::STATUS_ACTIVE)
                        ->whereOr('is_pushed', 1);
                });
            }

            $letterIds = $query->column('id');
            if (empty($letterIds)) {
                $output->writeln('no confirm letters to rebuild');
                return true;
            }

            $successCount = 0;
            $failCount = 0;
            foreach ($letterIds as $letterId) {
                try {
                    OrderConfirmLetterService::regenerateAssets((int) $letterId, '', true);
                    $successCount++;
                    $output->writeln('rebuilt letter #' . (int) $letterId);
                } catch (\Throwable $e) {
                    $failCount++;
                    Log::write('重建订单确认函图片失败，letter_id=' . (int) $letterId . '，错误：' . $e->getMessage());
                    $output->writeln('failed letter #' . (int) $letterId . ': ' . $e->getMessage());
                }
            }

            $output->writeln(sprintf('done. success=%d fail=%d', $successCount, $failCount));
            return $failCount === 0;
        } catch (\Throwable $e) {
            Log::write('重建订单确认函图片资产命令失败：' . $e->getMessage());
            $output->writeln('rebuild_order_confirm_letter_assets failed: ' . $e->getMessage());
            return false;
        }
    }
}
