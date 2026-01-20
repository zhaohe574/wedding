<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 时间轴业务逻辑
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\adminapi\logic\timeline;

use app\common\logic\BaseLogic;
use app\common\model\timeline\OrderTimeline;
use app\common\model\timeline\TimelineTemplate;
use app\common\model\order\Order;
use think\facade\Db;

/**
 * 时间轴业务逻辑
 * Class TimelineLogic
 * @package app\adminapi\logic\timeline
 */
class TimelineLogic extends BaseLogic
{
    /**
     * @notes 获取任务详情
     * @param int $id
     * @return array|null
     */
    public static function detail(int $id): ?array
    {
        $task = OrderTimeline::with([
            'order' => function ($query) {
                $query->field('id, order_sn, user_id, wedding_date, contact_name, contact_mobile');
            },
            'user' => function ($query) {
                $query->field('id, nickname, avatar, mobile');
            },
            'completeUser' => function ($query) {
                $query->field('id, nickname, avatar');
            }
        ])->find($id);

        if (!$task) {
            return null;
        }

        $data = $task->toArray();
        $data['task_type_desc'] = $task->task_type_desc;
        $data['is_completed_desc'] = $task->is_completed_desc;
        $data['status'] = $task->status;
        $data['days_to_wedding'] = $task->days_to_wedding;

        return $data;
    }

    /**
     * @notes 获取订单的时间轴
     * @param int $orderId
     * @return array
     */
    public static function getOrderTimeline(int $orderId): array
    {
        $order = Order::field('id, order_sn, user_id, wedding_date, contact_name')
            ->find($orderId);

        if (!$order) {
            return ['order' => null, 'timeline' => [], 'stats' => []];
        }

        $timeline = OrderTimeline::where('order_id', $orderId)
            ->order('trigger_date asc, sort desc, id asc')
            ->select()
            ->toArray();

        foreach ($timeline as &$item) {
            $task = new OrderTimeline($item);
            $item['task_type_desc'] = $task->task_type_desc;
            $item['is_completed_desc'] = $task->is_completed_desc;
            $item['status'] = $task->status;
        }

        $stats = OrderTimeline::getTimelineStats($orderId);

        return [
            'order' => $order->toArray(),
            'timeline' => $timeline,
            'stats' => $stats,
        ];
    }

    /**
     * @notes 添加任务
     * @param array $params
     * @return bool
     */
    public static function add(array $params): bool
    {
        try {
            // 检查订单是否存在
            $order = Order::find($params['order_id']);
            if (!$order) {
                self::setError('订单不存在');
                return false;
            }

            // 获取婚期
            $weddingDate = $order->wedding_date;
            if (empty($weddingDate)) {
                self::setError('订单未设置婚期');
                return false;
            }

            // 计算触发日期（如果未提供）
            if (empty($params['trigger_date']) && isset($params['days_before'])) {
                $weddingTimestamp = strtotime($weddingDate);
                $params['trigger_date'] = date('Y-m-d', $weddingTimestamp - $params['days_before'] * 86400);
            }

            OrderTimeline::create([
                'order_id' => $params['order_id'],
                'user_id' => $order->user_id,
                'template_id' => $params['template_id'] ?? 0,
                'wedding_date' => $weddingDate,
                'task_title' => $params['task_title'],
                'task_desc' => $params['task_desc'] ?? '',
                'task_type' => $params['task_type'],
                'days_before' => $params['days_before'] ?? 0,
                'trigger_date' => $params['trigger_date'],
                'trigger_time' => $params['trigger_time'] ?? null,
                'is_system' => 0,
                'sort' => $params['sort'] ?? 0,
                'create_time' => time(),
                'update_time' => time(),
            ]);

            return true;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * @notes 编辑任务
     * @param array $params
     * @return bool
     */
    public static function edit(array $params): bool
    {
        try {
            $task = OrderTimeline::find($params['id']);
            if (!$task) {
                self::setError('任务不存在');
                return false;
            }

            $updateData = [];
            $allowFields = ['task_title', 'task_desc', 'task_type', 'trigger_date', 'trigger_time', 'sort'];

            foreach ($allowFields as $field) {
                if (isset($params[$field])) {
                    $updateData[$field] = $params[$field];
                }
            }

            if (!empty($updateData)) {
                $updateData['update_time'] = time();
                OrderTimeline::where('id', $params['id'])->update($updateData);
            }

            return true;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * @notes 删除任务
     * @param int $id
     * @return bool
     */
    public static function delete(int $id): bool
    {
        try {
            $task = OrderTimeline::find($id);
            if (!$task) {
                self::setError('任务不存在');
                return false;
            }

            $task->delete();
            return true;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * @notes 完成任务
     * @param int $id
     * @param int $adminId
     * @param string $remark
     * @return bool
     */
    public static function complete(int $id, int $adminId, string $remark = ''): bool
    {
        try {
            $task = OrderTimeline::find($id);
            if (!$task) {
                self::setError('任务不存在');
                return false;
            }

            if ($task->is_completed) {
                self::setError('任务已完成');
                return false;
            }

            OrderTimeline::where('id', $id)->update([
                'is_completed' => 1,
                'complete_time' => time(),
                'complete_user_id' => $adminId,
                'complete_remark' => $remark,
                'update_time' => time(),
            ]);

            return true;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * @notes 取消完成任务
     * @param int $id
     * @return bool
     */
    public static function uncomplete(int $id): bool
    {
        try {
            $task = OrderTimeline::find($id);
            if (!$task) {
                self::setError('任务不存在');
                return false;
            }

            if (!$task->is_completed) {
                self::setError('任务未完成');
                return false;
            }

            OrderTimeline::where('id', $id)->update([
                'is_completed' => 0,
                'complete_time' => 0,
                'complete_user_id' => 0,
                'complete_remark' => '',
                'update_time' => time(),
            ]);

            return true;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * @notes 根据模板生成时间轴
     * @param int $orderId
     * @param int $templateId
     * @return bool
     */
    public static function generateFromTemplate(int $orderId, int $templateId = 0): bool
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
                self::setError('订单未设置婚期');
                return false;
            }

            // 获取模板
            $template = null;
            if ($templateId > 0) {
                $template = TimelineTemplate::find($templateId);
            }
            if (!$template) {
                $template = TimelineTemplate::getDefaultTemplate();
            }

            if (!$template) {
                self::setError('未找到可用的时间轴模板');
                return false;
            }

            // 检查是否已生成过
            $existCount = OrderTimeline::where('order_id', $orderId)
                ->where('is_system', 1)
                ->count();
            if ($existCount > 0) {
                self::setError('该订单已生成过时间轴任务');
                return false;
            }

            // 解析模板任务
            $tasks = is_array($template['tasks']) 
                ? $template['tasks'] 
                : json_decode($template['tasks'], true);

            if (empty($tasks)) {
                self::setError('模板任务为空');
                return false;
            }

            $weddingTimestamp = strtotime($weddingDate);
            $now = time();

            foreach ($tasks as $index => $task) {
                $triggerDate = date('Y-m-d', $weddingTimestamp - ($task['days_before'] ?? 0) * 86400);

                OrderTimeline::create([
                    'order_id' => $orderId,
                    'user_id' => $order->user_id,
                    'template_id' => $template['id'],
                    'wedding_date' => $weddingDate,
                    'task_title' => $task['title'] ?? '',
                    'task_desc' => $task['desc'] ?? '',
                    'task_type' => $task['type'] ?? 5,
                    'days_before' => $task['days_before'] ?? 0,
                    'trigger_date' => $triggerDate,
                    'is_system' => 1,
                    'sort' => count($tasks) - $index,
                    'create_time' => $now,
                    'update_time' => $now,
                ]);
            }

            // 更新模板使用次数
            TimelineTemplate::incrementUseCount($template['id']);

            Db::commit();
            return true;
        } catch (\Exception $e) {
            Db::rollback();
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * @notes 批量删除系统生成的任务
     * @param int $orderId
     * @return bool
     */
    public static function clearSystemTasks(int $orderId): bool
    {
        try {
            OrderTimeline::where('order_id', $orderId)
                ->where('is_system', 1)
                ->delete();
            return true;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * @notes 获取时间轴统计
     * @param int $orderId
     * @return array
     */
    public static function getStats(int $orderId): array
    {
        return OrderTimeline::getTimelineStats($orderId);
    }

    /**
     * @notes 获取模板列表
     * @return array
     */
    public static function getTemplates(): array
    {
        return TimelineTemplate::where('is_enabled', 1)
            ->order('is_default desc, sort desc, id asc')
            ->select()
            ->toArray();
    }
}
