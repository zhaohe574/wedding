<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 图片检测回调超时补偿
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\common\command;

use app\common\service\MediaCheckService;
use think\console\Command;
use think\console\Input;
use think\console\Output;

/**
 * 图片内容安全异步检测的兜底任务。
 *
 * 微信没有 media_check_async 的查询接口，结论只能靠消息推送回来；
 * 这里的“补偿”不是去拉结果，而是把长时间没回调的任务标记出来，
 * 让后台知道这些内容还没有结论、必须人工看，避免一直挂在“待回调”里没人管。
 *
 * 用法：php think media_check_recover
 */
class MediaCheckRecover extends Command
{
    protected function configure()
    {
        $this->setName('media_check_recover')
            ->setDescription('标记超时未回调的图片内容安全检测任务');
    }

    protected function execute(Input $input, Output $output)
    {
        $count = MediaCheckService::sweepTimeout();
        $output->writeln('已标记超时未回调的图片检测任务：' . $count . ' 条');
        return 0;
    }
}
