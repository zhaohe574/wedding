<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 小程序端售后工单逻辑层
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\api\logic;

use app\common\logic\BaseLogic;
use app\common\model\aftersale\AfterSaleTicket;
use app\common\model\aftersale\AfterSaleTicketLog;
use app\common\model\aftersale\Complaint;
use app\common\model\aftersale\Reshoot;
use app\common\model\aftersale\ServiceCallback;
use think\facade\Db;

/**
 * 小程序端售后工单逻辑层
 * Class AfterSaleLogic
 * @package app\api\logic
 */
class AfterSaleLogic extends BaseLogic
{
    // ==================== 工单管理 ====================

    /**
     * @notes 获取工单列表
     * @param array $params
     * @return array
     */
    public static function getTicketLists(array $params): array
    {
        $page = $params['page'] ?? 1;
        $limit = $params['limit'] ?? 10;
        $status = $params['status'] ?? null;

        $query = AfterSaleTicket::where('user_id', $params['user_id']);

        if ($status !== null && $status !== '') {
            $query->where('status', $status);
        }

        $total = $query->count();
        $lists = $query->order('id', 'desc')
            ->page($page, $limit)
            ->select()
            ->toArray();

        foreach ($lists as &$item) {
            $ticket = AfterSaleTicket::find($item['id']);
            $item['type_desc'] = $ticket->type_desc ?? '';
            $item['priority_desc'] = $ticket->priority_desc ?? '';
            $item['status_desc'] = $ticket->status_desc ?? '';
            $item['create_time'] = date('Y-m-d H:i', $item['create_time']);
        }

        return [
            'lists' => $lists,
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
        ];
    }

    /**
     * @notes 获取工单详情
     * @param int $id
     * @param int $userId
     * @return array
     */
    public static function getTicketDetail(int $id, int $userId): array
    {
        $ticket = AfterSaleTicket::with(['order'])->where('id', $id)->where('user_id', $userId)->find();
        if (!$ticket) {
            return [];
        }

        $data = $ticket->toArray();
        $data['type_desc'] = $ticket->type_desc;
        $data['priority_desc'] = $ticket->priority_desc;
        $data['status_desc'] = $ticket->status_desc;
        $data['create_time'] = date('Y-m-d H:i:s', $data['create_time']);
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
     * @notes 取消工单
     * @param int $ticketId
     * @param int $userId
     * @return bool|string
     */
    public static function cancelTicket(int $ticketId, int $userId)
    {
        try {
            $ticket = AfterSaleTicket::where('id', $ticketId)->where('user_id', $userId)->find();
            if (!$ticket) {
                return '工单不存在';
            }

            if (!in_array($ticket->status, [AfterSaleTicket::STATUS_PENDING])) {
                return '当前状态不可取消';
            }

            $ticket->status = AfterSaleTicket::STATUS_CANCELLED;
            $ticket->update_time = time();
            $ticket->save();

            AfterSaleTicketLog::addLog($ticketId, 1, $userId, 'cancel', $ticket->status, AfterSaleTicket::STATUS_CANCELLED, '用户取消工单');

            return true;
        } catch (\Exception $e) {
            return '取消失败：' . $e->getMessage();
        }
    }

    /**
     * @notes 确认完成
     * @param int $ticketId
     * @param int $userId
     * @param int $satisfaction
     * @param string $remark
     * @return bool|string
     */
    public static function confirmComplete(int $ticketId, int $userId, int $satisfaction = 5, string $remark = '')
    {
        $result = AfterSaleTicket::confirmComplete($ticketId, $userId, $satisfaction, $remark);
        if (!$result[0]) {
            return $result[1];
        }
        return true;
    }

    // ==================== 投诉管理 ====================

    /**
     * @notes 获取投诉列表
     * @param array $params
     * @return array
     */
    public static function getComplaintLists(array $params): array
    {
        $page = $params['page'] ?? 1;
        $limit = $params['limit'] ?? 10;
        $status = $params['status'] ?? null;

        $query = Complaint::where('user_id', $params['user_id']);

        if ($status !== null && $status !== '') {
            $query->where('status', $status);
        }

        $total = $query->count();
        $lists = $query->with(['staff'])
            ->order('id', 'desc')
            ->page($page, $limit)
            ->select()
            ->toArray();

        foreach ($lists as &$item) {
            $complaint = Complaint::find($item['id']);
            $item['type_desc'] = $complaint->type_desc ?? '';
            $item['level_desc'] = $complaint->level_desc ?? '';
            $item['status_desc'] = $complaint->status_desc ?? '';
            $item['create_time'] = date('Y-m-d H:i', $item['create_time']);
        }

        return [
            'lists' => $lists,
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
        ];
    }

    /**
     * @notes 获取投诉详情
     * @param int $id
     * @param int $userId
     * @return array
     */
    public static function getComplaintDetail(int $id, int $userId): array
    {
        $complaint = Complaint::with(['staff', 'order'])->where('id', $id)->where('user_id', $userId)->find();
        if (!$complaint) {
            return [];
        }

        $data = $complaint->toArray();
        $data['type_desc'] = $complaint->type_desc;
        $data['level_desc'] = $complaint->level_desc;
        $data['status_desc'] = $complaint->status_desc;
        $data['create_time'] = date('Y-m-d H:i:s', $data['create_time']);

        return $data;
    }

    /**
     * @notes 提交投诉
     * @param array $params
     * @return bool|string
     */
    public static function submitComplaint(array $params)
    {
        $result = Complaint::submitComplaint($params);
        if (!$result[0]) {
            return $result[1];
        }
        return true;
    }

    /**
     * @notes 评价投诉处理满意度
     * @param int $complaintId
     * @param int $userId
     * @param int $satisfaction
     * @return bool|string
     */
    public static function rateComplaintSatisfaction(int $complaintId, int $userId, int $satisfaction)
    {
        $result = Complaint::rateSatisfaction($complaintId, $userId, $satisfaction);
        if (!$result[0]) {
            return $result[1];
        }
        return true;
    }

    // ==================== 补拍申请 ====================

    /**
     * @notes 获取补拍申请列表
     * @param array $params
     * @return array
     */
    public static function getReshootLists(array $params): array
    {
        $page = $params['page'] ?? 1;
        $limit = $params['limit'] ?? 10;
        $status = $params['status'] ?? null;

        $query = Reshoot::where('user_id', $params['user_id']);

        if ($status !== null && $status !== '') {
            $query->where('status', $status);
        }

        $total = $query->count();
        $lists = $query->with(['staff', 'order'])
            ->order('id', 'desc')
            ->page($page, $limit)
            ->select()
            ->toArray();

        foreach ($lists as &$item) {
            $reshoot = Reshoot::find($item['id']);
            $item['type_desc'] = $reshoot->type_desc ?? '';
            $item['reason_type_desc'] = $reshoot->reason_type_desc ?? '';
            $item['status_desc'] = $reshoot->status_desc ?? '';
            $item['create_time'] = date('Y-m-d H:i', $item['create_time']);
        }

        return [
            'lists' => $lists,
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
        ];
    }

    /**
     * @notes 获取补拍申请详情
     * @param int $id
     * @param int $userId
     * @return array
     */
    public static function getReshootDetail(int $id, int $userId): array
    {
        $reshoot = Reshoot::with(['staff', 'newStaff', 'order'])->where('id', $id)->where('user_id', $userId)->find();
        if (!$reshoot) {
            return [];
        }

        $data = $reshoot->toArray();
        $data['type_desc'] = $reshoot->type_desc;
        $data['reason_type_desc'] = $reshoot->reason_type_desc;
        $data['status_desc'] = $reshoot->status_desc;
        $data['create_time'] = date('Y-m-d H:i:s', $data['create_time']);

        return $data;
    }

    /**
     * @notes 提交补拍申请
     * @param array $params
     * @return bool|string
     */
    public static function applyReshoot(array $params)
    {
        $result = Reshoot::applyReshoot($params);
        if (!$result[0]) {
            return $result[1];
        }
        return true;
    }

    /**
     * @notes 取消补拍申请
     * @param int $reshootId
     * @param int $userId
     * @return bool|string
     */
    public static function cancelReshoot(int $reshootId, int $userId)
    {
        $result = Reshoot::cancelReshoot($reshootId, $userId);
        if (!$result[0]) {
            return $result[1];
        }
        return true;
    }

    // ==================== 回访问卷 ====================

    /**
     * @notes 获取回访列表
     * @param array $params
     * @return array
     */
    public static function getCallbackLists(array $params): array
    {
        $page = $params['page'] ?? 1;
        $limit = $params['limit'] ?? 10;
        $status = $params['status'] ?? null;

        $query = ServiceCallback::where('user_id', $params['user_id'])
            ->where('method', ServiceCallback::METHOD_QUESTIONNAIRE);  // 只显示问卷类型

        if ($status !== null && $status !== '') {
            $query->where('status', $status);
        }

        $total = $query->count();
        $lists = $query->with(['staff', 'order'])
            ->order('id', 'desc')
            ->page($page, $limit)
            ->select()
            ->toArray();

        foreach ($lists as &$item) {
            $callback = ServiceCallback::find($item['id']);
            $item['type_desc'] = $callback->type_desc ?? '';
            $item['status_desc'] = $callback->status_desc ?? '';
            $item['create_time'] = date('Y-m-d H:i', $item['create_time']);
            $item['plan_time'] = $item['plan_time'] ? date('Y-m-d H:i', $item['plan_time']) : '';
        }

        return [
            'lists' => $lists,
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
        ];
    }

    /**
     * @notes 获取回访问卷
     * @param int $callbackId
     * @param int $userId
     * @return array
     */
    public static function getQuestionnaire(int $callbackId, int $userId): array
    {
        $callback = ServiceCallback::with(['staff', 'order'])->where('id', $callbackId)->where('user_id', $userId)->find();
        if (!$callback) {
            return [];
        }

        // 获取问卷配置
        $questionnaire = Db::name('callback_questionnaire')
            ->where('type', $callback->type)
            ->where('status', 1)
            ->order('sort', 'asc')
            ->find();

        $data = $callback->toArray();
        $data['type_desc'] = $callback->type_desc;
        $data['status_desc'] = $callback->status_desc;
        $data['questionnaire'] = null;

        if ($questionnaire) {
            $data['questionnaire'] = [
                'id' => $questionnaire['id'],
                'title' => $questionnaire['title'],
                'description' => $questionnaire['description'],
                'questions' => json_decode($questionnaire['questions'], true) ?: [],
            ];
        }

        return $data;
    }

    /**
     * @notes 提交回访问卷
     * @param int $callbackId
     * @param int $userId
     * @param array $answers
     * @return bool|string
     */
    public static function submitQuestionnaire(int $callbackId, int $userId, array $answers)
    {
        $result = ServiceCallback::submitQuestionnaire($callbackId, $userId, $answers);
        if (!$result[0]) {
            return $result[1];
        }
        return true;
    }

    // ==================== 统计 ====================

    /**
     * @notes 获取用户售后统计
     * @param int $userId
     * @return array
     */
    public static function getUserStatistics(int $userId): array
    {
        return [
            'ticket' => [
                'total' => AfterSaleTicket::where('user_id', $userId)->count(),
                'pending' => AfterSaleTicket::where('user_id', $userId)
                    ->whereIn('status', [AfterSaleTicket::STATUS_PENDING, AfterSaleTicket::STATUS_PROCESSING, AfterSaleTicket::STATUS_CONFIRMING])
                    ->count(),
            ],
            'complaint' => [
                'total' => Complaint::where('user_id', $userId)->count(),
                'pending' => Complaint::where('user_id', $userId)
                    ->whereIn('status', [Complaint::STATUS_PENDING, Complaint::STATUS_PROCESSING])
                    ->count(),
            ],
            'reshoot' => [
                'total' => Reshoot::where('user_id', $userId)->count(),
                'pending' => Reshoot::where('user_id', $userId)
                    ->whereIn('status', [Reshoot::STATUS_PENDING, Reshoot::STATUS_APPROVED, Reshoot::STATUS_SCHEDULED])
                    ->count(),
            ],
            'callback' => [
                'total' => ServiceCallback::where('user_id', $userId)->where('method', ServiceCallback::METHOD_QUESTIONNAIRE)->count(),
                'pending' => ServiceCallback::where('user_id', $userId)
                    ->where('method', ServiceCallback::METHOD_QUESTIONNAIRE)
                    ->where('status', ServiceCallback::STATUS_PENDING)
                    ->count(),
            ],
        ];
    }
}
