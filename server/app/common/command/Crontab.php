<?php
// +----------------------------------------------------------------------
// | likeadmin快速开发前后端分离管理后台（PHP版）
// +----------------------------------------------------------------------
// | 欢迎阅读学习系统程序代码，建议反馈是我们前进的动力
// | 开源版本可自由商用，可去除界面版权logo
// | gitee下载：https://gitee.com/likeshop_gitee/likeadmin
// | github下载：https://github.com/likeshop-github/likeadmin
// | 访问官网：https://www.likeadmin.cn
// | likeadmin团队 版权所有 拥有最终解释权
// +----------------------------------------------------------------------
// | author: likeadminTeam
// +----------------------------------------------------------------------

namespace app\common\command;

use app\common\enum\CrontabEnum;
use think\console\Command;
use think\console\Input;
use think\console\Output;
use Cron\CronExpression;
use think\facade\Console;
use think\facade\Db;
use app\common\model\Crontab as CrontabModel;

/**
 * 定时任务
 * Class Crontab
 * @package app\command
 */
class Crontab extends Command
{
    protected function configure()
    {
        $this->setName('crontab')
            ->setDescription('定时任务');
    }

    protected function execute(Input $input, Output $output)
    {
        $lists = CrontabModel::where('status', CrontabEnum::START)->select()
            ->map(static fn ($task) => $task->getData())->all();
        if (empty($lists)) {
            return 0;
        }
        $time =  time();
        $failed = false;
        foreach ($lists as $item) {
            if (empty($item['last_time'])) {
                $lastTime = (new CronExpression($item['expression']))
                    ->getPreviousRunDate()
                    ->getTimestamp();
                CrontabModel::where('id', $item['id'])->update([
                    'last_time' => $lastTime,
                ]);
                continue;
            }

            $nextTime = (new CronExpression($item['expression']))
                ->getNextRunDate((new \DateTimeImmutable())->setTimestamp((int)$item['last_time']))
                ->getTimestamp();
            if ($nextTime > $time) {
                // 未到时间，不执行
                continue;
            }
            // 开始执行
            $failed = !self::start($item) || $failed;
        }
        return $failed ? 1 : 0;
    }

    public static function start($item)
    {
        // 命名锁由数据库连接持有，进程退出时自动释放，避免长任务重叠执行。
        $lockSuffix = ':crontab:' . (int)$item['id'];
        $lock = Db::query('SELECT GET_LOCK(SHA2(CONCAT(DATABASE(), ?), 256), 0) AS acquired', [$lockSuffix]);
        if ((int)($lock[0]['acquired'] ?? 0) !== 1) {
            return true;
        }
        // 开始执行
        $startTime = microtime(true);
        $executed = false;
        try {
            $latest = CrontabModel::find((int)$item['id']);
            $snapshotTime = $item['last_time'] ?? 0;
            $snapshotTime = is_numeric($snapshotTime) ? (int)$snapshotTime : (int)strtotime((string)$snapshotTime);
            if (!$latest || (int)$latest->getData('last_time') !== $snapshotTime) {
                return true;
            }
            $executed = true;
            $params = trim((string)($item['params'] ?? ''));
            $arguments = $params === '' ? [] : str_getcsv($params, ' ');
            $output = new Output('buffer');
            $exitCode = Console::find($item['command'])->run(
                new Input(array_merge([$item['command']], $arguments)), $output
            );
            if ($exitCode !== 0) {
                throw new \RuntimeException(trim($output->fetch()) ?: '任务返回失败状态：' . $exitCode);
            }
            // 清除错误信息
            CrontabModel::where('id', $item['id'])->update(['error' => '']);
            return true;
        } catch (\Throwable $e) {
            // 记录错误信息
            CrontabModel::where('id', $item['id'])->update([
                'error' => $e->getMessage(),
                'status' => CrontabEnum::ERROR
            ]);
            return false;
        } finally {
            $endTime = microtime(true);
            // 本次执行时间
            $useTime = round(($endTime - $startTime), 2);
            // 最大执行时间
            $maxTime = max($useTime, $item['max_time']);
            // 更新最后执行时间
            try {
                if ($executed) {
                    CrontabModel::where('id', $item['id'])->update([
                        'last_time' => time(),
                        'time' => $useTime,
                        'max_time' => $maxTime
                    ]);
                }
            } finally {
                Db::query('SELECT RELEASE_LOCK(SHA2(CONCAT(DATABASE(), ?), 256))', [$lockSuffix]);
            }
        }
    }
}
