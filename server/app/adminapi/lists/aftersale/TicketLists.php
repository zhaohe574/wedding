<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 工单列表
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\adminapi\lists\aftersale;

use app\adminapi\lists\BaseAdminDataLists;
use app\common\model\aftersale\AfterSaleTicket;
use app\common\lists\ListsSearchInterface;

/**
 * 工单列表
 * Class TicketLists
 * @package app\adminapi\lists\aftersale
 */
class TicketLists extends BaseAdminDataLists implements ListsSearchInterface
{
    /**
     * @notes 设置搜索条件
     * @return array
     */
    public function setSearch(): array
    {
        return [
            '=' => ['type', 'priority', 'assign_admin_id'],
            '%like%' => ['ticket_sn', 'title'],
        ];
    }

    /**
     * @notes 获取列表
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function lists(): array
    {
        $lists = AfterSaleTicket::with(['user', 'assignAdmin', 'order'])
            ->where($this->searchWhere)
            ->when(isset($this->params['status']) && $this->params['status'] !== '', function ($query) {
                $this->applyStatusFilter($query, $this->params['status']);
            })
            ->when(!empty($this->params['order_id']), function ($query) {
                $query->where('order_id', $this->params['order_id']);
            })
            ->when(!empty($this->params['user_id']), function ($query) {
                $query->where('user_id', $this->params['user_id']);
            })
            ->when(!empty($this->params['is_overtime']), function ($query) {
                $query->where('is_overtime', 1);
            })
            ->when(!empty($this->params['start_time']) && !empty($this->params['end_time']), function ($query) {
                $query->whereBetweenTime('create_time', $this->params['start_time'], $this->params['end_time']);
            })
            ->order('id', 'desc')
            ->limit($this->limitOffset, $this->limitLength)
            ->select()
            ->toArray();

        foreach ($lists as &$item) {
            $item['type_desc'] = AfterSaleTicket::find($item['id'])->type_desc ?? '';
            $item['priority_desc'] = AfterSaleTicket::find($item['id'])->priority_desc ?? '';
            $item['status_desc'] = AfterSaleTicket::find($item['id'])->status_desc ?? '';
            $item['order_info'] = AfterSaleTicket::buildOrderInfo((int)($item['order_id'] ?? 0));
            $item['create_time'] = $this->formatDateTime($item['create_time'] ?? null);
            $item['deadline'] = $this->formatDateTime($item['deadline'] ?? null);
        }

        return $lists;
    }

    /**
     * @notes 获取数量
     * @return int
     */
    public function count(): int
    {
        return AfterSaleTicket::where($this->searchWhere)
            ->when(isset($this->params['status']) && $this->params['status'] !== '', function ($query) {
                $this->applyStatusFilter($query, $this->params['status']);
            })
            ->when(!empty($this->params['order_id']), function ($query) {
                $query->where('order_id', $this->params['order_id']);
            })
            ->when(!empty($this->params['user_id']), function ($query) {
                $query->where('user_id', $this->params['user_id']);
            })
            ->when(!empty($this->params['is_overtime']), function ($query) {
                $query->where('is_overtime', 1);
            })
            ->when(!empty($this->params['start_time']) && !empty($this->params['end_time']), function ($query) {
                $query->whereBetweenTime('create_time', $this->params['start_time'], $this->params['end_time']);
            })
            ->count();
    }

    /**
     * @notes 应用状态筛选，支持后台“未完成”口径
     * @param mixed $query
     * @param mixed $status
     * @return void
     */
    private function applyStatusFilter($query, $status): void
    {
        if ($status === 'unfinished') {
            $query->whereIn('status', [
                AfterSaleTicket::STATUS_PENDING,
                AfterSaleTicket::STATUS_PROCESSING,
                AfterSaleTicket::STATUS_CONFIRMING,
            ]);
            return;
        }

        $query->where('status', $status);
    }

    /**
     * @notes 安全格式化时间
     * @param mixed $value
     * @return string
     */
    private function formatDateTime($value): string
    {
        if ($value === null || $value === '' || $value === false) {
            return '';
        }

        $timestamp = is_numeric($value) ? (int)$value : strtotime((string)$value);
        return $timestamp > 0 ? date('Y-m-d H:i:s', $timestamp) : '';
    }
}
