<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 时间轴生成服务
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\common\service;

use app\common\model\timeline\OrderTimeline;
use app\common\model\timeline\TimelineTemplate;
use app\common\model\order\Order;
use think\facade\Db;
use think\facade\Log;

/**
 * 时间轴生成服务
 * 用于根据婚期自动生成时间轴任务
 * Class TimelineGeneratorService
 * @package app\common\service
 */
class TimelineGeneratorService
{
    /**
     * @var string 错误信息
     */
    protected static string $error = '';

    /**
     * @notes 获取错误信息
     * @return string
     */
    public static function getError(): string
    {
        return self::$error;
    }

    /**
     * @notes 设置错误信息
     * @param string $error
     */
    protected static function setError(string $error): void
    {
        self::$error = $error;
    }

    /**
     * @notes 为订单生成时间轴
     * @param int $orderId 订单ID
     * @param int $templateId 模板ID（0=使用默认模板）
     * @param bool $force 是否强制重新生成
     * @return bool
     */
    public static function generateForOrder(int $orderId, int $templateId = 0, bool $force = false): bool
    {
        Db::startTrans();
        try {
            // 检查订单
            $order = Order::find($orderId);
            if (!$order) {
                self::setError('订单不存在');
                return false;
            }

            $weddingDate = $order->wedding_date;
            if (empty($weddingDate)) {
                self::setError('订单未设置婚期，无法生成时间轴');
                return false;
            }

            // 检查是否已生成
            $existCount = OrderTimeline::where('order_id', $orderId)
                ->where('is_system', 1)
                ->count();

            if ($existCount > 0 && !$force) {
                self::setError('该订单已生成过时间轴，如需重新生成请使用强制模式');
                return false;
            }

            // 强制模式下先删除已有任务
            if ($force && $existCount > 0) {
                OrderTimeline::where('order_id', $orderId)
                    ->where('is_system', 1)
                    ->delete();
            }

            // 获取模板
            $template = self::getTemplate($templateId);
            if (!$template) {
                self::setError('未找到可用的时间轴模板');
                return false;
            }

            // 生成任务
            $result = self::createTasksFromTemplate($order, $template);
            if (!$result) {
                Db::rollback();
                return false;
            }

            // 更新模板使用次数
            TimelineTemplate::incrementUseCount($template['id']);

            Db::commit();
            
            Log::info('时间轴生成成功', [
                'order_id' => $orderId,
                'template_id' => $template['id'],
                'wedding_date' => $weddingDate,
            ]);

            return true;
        } catch (\Exception $e) {
            Db::rollback();
            self::setError($e->getMessage());
            Log::error('时间轴生成失败', [
                'order_id' => $orderId,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * @notes 批量为订单生成时间轴
     * @param array $orderIds 订单ID数组
     * @param int $templateId 模板ID
     * @return array ['success' => [], 'failed' => []]
     */
    public static function batchGenerate(array $orderIds, int $templateId = 0): array
    {
        $result = ['success' => [], 'failed' => []];

        foreach ($orderIds as $orderId) {
            if (self::generateForOrder($orderId, $templateId)) {
                $result['success'][] = $orderId;
            } else {
                $result['failed'][] = [
                    'order_id' => $orderId,
                    'error' => self::getError(),
                ];
            }
        }

        return $result;
    }

    /**
     * @notes 获取模板
     * @param int $templateId
     * @return array|null
     */
    protected static function getTemplate(int $templateId): ?array
    {
        if ($templateId > 0) {
            $template = TimelineTemplate::where('id', $templateId)
                ->where('is_enabled', 1)
                ->find();
            if ($template) {
                return $template->toArray();
            }
        }

        // 获取默认模板
        return TimelineTemplate::getDefaultTemplate();
    }

    /**
     * @notes 根据模板创建任务
     * @param Order $order
     * @param array $template
     * @return bool
     */
    protected static function createTasksFromTemplate(Order $order, array $template): bool
    {
        $tasks = $template['tasks'];
        if (is_string($tasks)) {
            $tasks = json_decode($tasks, true);
        }

        if (empty($tasks) || !is_array($tasks)) {
            self::setError('模板任务配置无效');
            return false;
        }

        $weddingDate = $order->wedding_date;
        $weddingTimestamp = strtotime($weddingDate);
        $now = time();

        foreach ($tasks as $index => $task) {
            $daysBefore = $task['days_before'] ?? 0;
            $triggerDate = date('Y-m-d', $weddingTimestamp - $daysBefore * 86400);

            OrderTimeline::create([
                'order_id' => $order->id,
                'user_id' => $order->user_id,
                'template_id' => $template['id'],
                'wedding_date' => $weddingDate,
                'task_title' => $task['title'] ?? '待办事项',
                'task_desc' => $task['desc'] ?? '',
                'task_type' => $task['type'] ?? 5,
                'days_before' => $daysBefore,
                'trigger_date' => $triggerDate,
                'is_system' => 1,
                'sort' => count($tasks) - $index,
                'create_time' => $now,
                'update_time' => $now,
            ]);
        }

        return true;
    }

    /**
     * @notes 当订单婚期变更时更新时间轴
     * @param int $orderId
     * @param string $newWeddingDate
     * @return bool
     */
    public static function updateOnWeddingDateChange(int $orderId, string $newWeddingDate): bool
    {
        try {
            $newWeddingTimestamp = strtotime($newWeddingDate);
            
            // 获取所有系统任务
            $tasks = OrderTimeline::where('order_id', $orderId)
                ->where('is_system', 1)
                ->select();

            foreach ($tasks as $task) {
                // 根据days_before重新计算触发日期
                $triggerDate = date('Y-m-d', $newWeddingTimestamp - $task->days_before * 86400);
                
                $task->wedding_date = $newWeddingDate;
                $task->trigger_date = $triggerDate;
                $task->update_time = time();
                $task->save();
            }

            Log::info('时间轴已更新婚期', [
                'order_id' => $orderId,
                'new_wedding_date' => $newWeddingDate,
            ]);

            return true;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * @notes 获取今日需要提醒的任务
     * @return array
     */
    public static function getTodayRemindTasks(): array
    {
        $today = date('Y-m-d');
        
        return OrderTimeline::with([
            'order' => function ($query) {
                $query->field('id, order_sn, user_id, contact_name, contact_mobile');
            },
            'user' => function ($query) {
                $query->field('id, nickname, mobile');
            }
        ])
            ->where('trigger_date', $today)
            ->where('is_completed', 0)
            ->where('is_reminded', 0)
            ->select()
            ->toArray();
    }

    /**
     * @notes 获取逾期未完成的任务
     * @return array
     */
    public static function getOverdueTasks(): array
    {
        $today = date('Y-m-d');
        
        return OrderTimeline::with([
            'order' => function ($query) {
                $query->field('id, order_sn, user_id, contact_name, contact_mobile');
            },
            'user' => function ($query) {
                $query->field('id, nickname, mobile');
            }
        ])
            ->where('trigger_date', '<', $today)
            ->where('is_completed', 0)
            ->select()
            ->toArray();
    }

    /**
     * @notes 发送任务提醒（定时任务调用）
     * @return array
     */
    public static function sendReminders(): array
    {
        $tasks = self::getTodayRemindTasks();
        $result = ['success' => 0, 'failed' => 0];

        foreach ($tasks as $task) {
            try {
                // TODO: 调用消息通知服务发送提醒
                // NotificationService::sendTimelineReminder($task);

                // 标记已提醒
                OrderTimeline::markReminded($task['id']);
                $result['success']++;
            } catch (\Exception $e) {
                $result['failed']++;
                Log::error('时间轴提醒发送失败', [
                    'task_id' => $task['id'],
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $result;
    }

    /**
     * @notes 获取用户的时间轴概览
     * @param int $userId
     * @return array
     */
    public static function getUserTimelineOverview(int $userId): array
    {
        $today = date('Y-m-d');

        // 今日任务
        $todayTasks = OrderTimeline::where('user_id', $userId)
            ->where('trigger_date', $today)
            ->where('is_completed', 0)
            ->count();

        // 逾期任务
        $overdueTasks = OrderTimeline::where('user_id', $userId)
            ->where('trigger_date', '<', $today)
            ->where('is_completed', 0)
            ->count();

        // 本周任务
        $weekStart = date('Y-m-d', strtotime('monday this week'));
        $weekEnd = date('Y-m-d', strtotime('sunday this week'));
        $weekTasks = OrderTimeline::where('user_id', $userId)
            ->whereBetween('trigger_date', [$weekStart, $weekEnd])
            ->where('is_completed', 0)
            ->count();

        // 总任务
        $totalTasks = OrderTimeline::where('user_id', $userId)->count();
        $completedTasks = OrderTimeline::where('user_id', $userId)
            ->where('is_completed', 1)
            ->count();

        return [
            'today' => $todayTasks,
            'overdue' => $overdueTasks,
            'week' => $weekTasks,
            'total' => $totalTasks,
            'completed' => $completedTasks,
            'pending' => $totalTasks - $completedTasks,
            'progress' => $totalTasks > 0 ? round($completedTasks / $totalTasks * 100, 1) : 0,
        ];
    }
}
