<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 售后工单逻辑层
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\adminapi\logic\aftersale;

use app\common\logic\BaseLogic;
use app\common\model\aftersale\AfterSaleTicket;
use app\common\model\aftersale\AfterSaleTicketLog;
use app\common\model\aftersale\Complaint;
use app\common\model\aftersale\Reshoot;
use app\common\model\aftersale\ServiceCallback;
use think\facade\Db;

/**
 * 售后工单逻辑层
 * Class AfterSaleLogic
 * @package app\adminapi\logic\aftersale
 */
class AfterSaleLogic extends BaseLogic
{
    // ==================== 工单管理 ====================

    /**
     * @notes 获取工单详情
     * @param int $id
     * @return array
     */
    public static function getTicketDetail(int $id): array
    {
        $ticket = AfterSaleTicket::with(['user', 'order', 'assignAdmin'])->find($id);
        if (!$ticket) {
            return [];
        }

        $data = $ticket->toArray();
        $data['type_desc'] = $ticket->type_desc;
        $data['priority_desc'] = $ticket->priority_desc;
        $data['status_desc'] = $ticket->status_desc;
        $data['logs'] = AfterSaleTicketLog::getLogsByTicket($id);

        return $data;
    }

    /**
     * @notes 创建工单
     * @param array $params
     * @return bool|string
     */
    public static function createTicket(array $params)
    {
        $result = AfterSaleTicket::createTicket($params);
        if (!$result[0]) {
            return $result[1];
        }
        return true;
    }

    /**
     * @notes 分配工单
     * @param int $ticketId
     * @param int $adminId
     * @param int $operatorId
     * @return bool|string
     */
    public static function assignTicket(int $ticketId, int $adminId, int $operatorId)
    {
        $result = AfterSaleTicket::assignTicket($ticketId, $adminId, $operatorId);
        if (!$result[0]) {
            return $result[1];
        }
        return true;
    }

    /**
     * @notes 处理工单
     * @param int $ticketId
     * @param int $adminId
     * @param string $handleResult
     * @param array $images
     * @return bool|string
     */
    public static function handleTicket(int $ticketId, int $adminId, string $handleResult, array $images = [])
    {
        $result = AfterSaleTicket::handleTicket($ticketId, $adminId, $handleResult);
        if (!$result[0]) {
            return $result[1];
        }

        // 添加处理图片到日志
        if (!empty($images)) {
            AfterSaleTicketLog::addLog($ticketId, 2, $adminId, 'handle_images', 0, 0, '处理附件', $images);
        }

        return true;
    }

    /**
     * @notes 关闭工单
     * @param int $ticketId
     * @param int $adminId
     * @param string $reason
     * @return bool|string
     */
    public static function closeTicket(int $ticketId, int $adminId, string $reason)
    {
        $result = AfterSaleTicket::closeTicket($ticketId, $adminId, $reason);
        if (!$result[0]) {
            return $result[1];
        }
        return true;
    }

    /**
     * @notes 升级工单
     * @param int $ticketId
     * @param int $operatorId
     * @return bool|string
     */
    public static function escalateTicket(int $ticketId, int $operatorId)
    {
        $result = AfterSaleTicket::escalateTicket($ticketId, $operatorId);
        if (!$result[0]) {
            return $result[1];
        }
        return true;
    }

    /**
     * @notes 获取工单日志
     * @param int $ticketId
     * @return array
     */
    public static function getTicketLogs(int $ticketId): array
    {
        return AfterSaleTicketLog::getLogsByTicket($ticketId);
    }

    // ==================== 投诉管理 ====================

    /**
     * @notes 获取投诉详情
     * @param int $id
     * @return array
     */
    public static function getComplaintDetail(int $id): array
    {
        $complaint = Complaint::with(['user', 'staff', 'order'])->find($id);
        if (!$complaint) {
            return [];
        }

        $data = $complaint->toArray();
        $data['type_desc'] = $complaint->type_desc;
        $data['level_desc'] = $complaint->level_desc;
        $data['status_desc'] = $complaint->status_desc;

        return $data;
    }

    /**
     * @notes 处理投诉
     * @param int $complaintId
     * @param int $adminId
     * @param array $handleData
     * @return bool|string
     */
    public static function handleComplaint(int $complaintId, int $adminId, array $handleData)
    {
        $result = Complaint::handleComplaint($complaintId, $adminId, $handleData);
        if (!$result[0]) {
            return $result[1];
        }
        return true;
    }

    // ==================== 补拍申请管理 ====================

    /**
     * @notes 获取补拍申请详情
     * @param int $id
     * @return array
     */
    public static function getReshootDetail(int $id): array
    {
        $reshoot = Reshoot::with(['user', 'staff', 'newStaff', 'order'])->find($id);
        if (!$reshoot) {
            return [];
        }

        $data = $reshoot->toArray();
        $data['type_desc'] = $reshoot->type_desc;
        $data['reason_type_desc'] = $reshoot->reason_type_desc;
        $data['status_desc'] = $reshoot->status_desc;

        return $data;
    }

    /**
     * @notes 审核补拍申请
     * @param int $reshootId
     * @param int $adminId
     * @param bool $approved
     * @param array $auditData
     * @return bool|string
     */
    public static function auditReshoot(int $reshootId, int $adminId, bool $approved, array $auditData = [])
    {
        $result = Reshoot::auditReshoot($reshootId, $adminId, $approved, $auditData);
        if (!$result[0]) {
            return $result[1];
        }
        return true;
    }

    /**
     * @notes 安排补拍
     * @param int $reshootId
     * @param int $adminId
     * @param array $scheduleData
     * @return bool|string
     */
    public static function scheduleReshoot(int $reshootId, int $adminId, array $scheduleData)
    {
        $result = Reshoot::scheduleReshoot($reshootId, $adminId, $scheduleData);
        if (!$result[0]) {
            return $result[1];
        }
        return true;
    }

    /**
     * @notes 完成补拍
     * @param int $reshootId
     * @param int $adminId
     * @param string $remark
     * @return bool|string
     */
    public static function completeReshoot(int $reshootId, int $adminId, string $remark = '')
    {
        $result = Reshoot::completeReshoot($reshootId, $adminId, $remark);
        if (!$result[0]) {
            return $result[1];
        }
        return true;
    }

    // ==================== 回访管理 ====================

    /**
     * @notes 获取回访详情
     * @param int $id
     * @return array
     */
    public static function getCallbackDetail(int $id): array
    {
        $callback = ServiceCallback::with(['user', 'staff', 'order', 'admin'])->find($id);
        if (!$callback) {
            return [];
        }

        $data = $callback->toArray();
        $data['type_desc'] = $callback->type_desc;
        $data['method_desc'] = $callback->method_desc;
        $data['status_desc'] = $callback->status_desc;

        return $data;
    }

    /**
     * @notes 创建回访任务
     * @param array $params
     * @return bool|string
     */
    public static function createCallback(array $params)
    {
        $result = ServiceCallback::createCallback($params);
        if (!$result[0]) {
            return $result[1];
        }
        return true;
    }

    /**
     * @notes 完成回访
     * @param int $callbackId
     * @param int $adminId
     * @param array $callbackData
     * @return bool|string
     */
    public static function completeCallback(int $callbackId, int $adminId, array $callbackData)
    {
        $result = ServiceCallback::completeCallback($callbackId, $adminId, $callbackData);
        if (!$result[0]) {
            return $result[1];
        }
        return true;
    }

    /**
     * @notes 标记无法联系
     * @param int $callbackId
     * @param int $adminId
     * @return bool|string
     */
    public static function markUnreachable(int $callbackId, int $adminId)
    {
        $result = ServiceCallback::markUnreachable($callbackId, $adminId);
        if (!$result[0]) {
            return $result[1];
        }
        return true;
    }

    /**
     * @notes 问题升级
     * @param int $callbackId
     * @param int $adminId
     * @return array|string
     */
    public static function escalateProblem(int $callbackId, int $adminId)
    {
        $result = ServiceCallback::escalateProblem($callbackId, $adminId);
        if (!$result[0]) {
            return $result[1];
        }
        return ['ticket_id' => $result[2]];
    }

    // ==================== 统计数据 ====================

    /**
     * @notes 获取统计数据
     * @return array
     */
    public static function getStatistics(): array
    {
        return [
            'ticket' => AfterSaleTicket::getStatistics(),
            'complaint' => Complaint::getStatistics(),
            'reshoot' => Reshoot::getStatistics(),
            'callback' => ServiceCallback::getStatistics(),
        ];
    }

    /**
     * @notes 获取趋势数据
     * @param int $days
     * @return array
     */
    public static function getTrend(int $days = 7): array
    {
        $startTime = strtotime(date('Y-m-d', strtotime("-{$days} days")));
        $trend = [];

        for ($i = 0; $i < $days; $i++) {
            $date = date('Y-m-d', $startTime + $i * 86400);
            $dayStart = strtotime($date);
            $dayEnd = $dayStart + 86400;

            $trend[] = [
                'date' => $date,
                'ticket_new' => AfterSaleTicket::where('create_time', '>=', $dayStart)
                    ->where('create_time', '<', $dayEnd)
                    ->count(),
                'ticket_completed' => AfterSaleTicket::where('status', AfterSaleTicket::STATUS_COMPLETED)
                    ->where('update_time', '>=', $dayStart)
                    ->where('update_time', '<', $dayEnd)
                    ->count(),
                'complaint_new' => Complaint::where('create_time', '>=', $dayStart)
                    ->where('create_time', '<', $dayEnd)
                    ->count(),
                'reshoot_new' => Reshoot::where('create_time', '>=', $dayStart)
                    ->where('create_time', '<', $dayEnd)
                    ->count(),
                'callback_completed' => ServiceCallback::where('status', ServiceCallback::STATUS_COMPLETED)
                    ->where('actual_time', '>=', $dayStart)
                    ->where('actual_time', '<', $dayEnd)
                    ->count(),
            ];
        }

        return $trend;
    }

    /**
     * @notes 批量分配工单
     * @param array $ticketIds
     * @param int $adminId
     * @param int $operatorId
     * @return array [int $success, int $fail]
     */
    public static function batchAssignTickets(array $ticketIds, int $adminId, int $operatorId): array
    {
        $success = 0;
        $fail = 0;

        foreach ($ticketIds as $ticketId) {
            $result = AfterSaleTicket::assignTicket($ticketId, $adminId, $operatorId);
            if ($result[0]) {
                $success++;
            } else {
                $fail++;
            }
        }

        return [$success, $fail];
    }

    /**
     * @notes 批量关闭工单
     * @param array $ticketIds
     * @param int $adminId
     * @param string $reason
     * @return array [int $success, int $fail]
     */
    public static function batchCloseTickets(array $ticketIds, int $adminId, string $reason): array
    {
        $success = 0;
        $fail = 0;

        foreach ($ticketIds as $ticketId) {
            $result = AfterSaleTicket::closeTicket($ticketId, $adminId, $reason);
            if ($result[0]) {
                $success++;
            } else {
                $fail++;
            }
        }

        return [$success, $fail];
    }
}
