<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 服务人员中心我的工单列表
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\adminapi\lists\aftersale;

use app\adminapi\lists\BaseAdminDataLists;
use app\common\lists\ListsSearchInterface;
use app\common\model\aftersale\AfterSaleTicket;

/**
 * 服务人员中心我的工单列表
 * Class MyTicketLists
 * @package app\adminapi\lists\aftersale
 */
class MyTicketLists extends BaseAdminDataLists implements ListsSearchInterface
{
    private int $scopeAdminId = 0;

    public function __construct()
    {
        parent::__construct();
        $this->scopeAdminId = (int)($this->adminInfo['admin_id'] ?? $this->adminId);
    }

    /**
     * @notes 设置搜索条件
     * @return array
     */
    public function setSearch(): array
    {
        return [
            '=' => ['type', 'priority', 'status'],
            '%like%' => ['ticket_sn', 'title'],
        ];
    }

    /**
     * @notes 获取列表
     * @return array
     */
    public function lists(): array
    {
        $lists = $this->baseQuery()
            ->order('id', 'desc')
            ->limit($this->limitOffset, $this->limitLength)
            ->select()
            ->toArray();

        foreach ($lists as &$item) {
            $ticket = AfterSaleTicket::find($item['id']);
            $item['type_desc'] = $ticket->type_desc ?? '';
            $item['priority_desc'] = $ticket->priority_desc ?? '';
            $item['status_desc'] = $ticket->status_desc ?? '';
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
        return $this->baseQuery(false)->count();
    }

    /**
     * @notes 基础查询
     * @param bool $withRelation
     * @return mixed
     */
    private function baseQuery(bool $withRelation = true)
    {
        $query = $withRelation
            ? AfterSaleTicket::with(['user', 'assignAdmin', 'order'])
            : AfterSaleTicket::where([]);

        return $query->where($this->searchWhere)
            ->where('assign_admin_id', $this->scopeAdminId)
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
            });
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
