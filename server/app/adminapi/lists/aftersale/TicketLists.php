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
            '=' => ['type', 'priority', 'status', 'assign_admin_id'],
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
        $lists = AfterSaleTicket::with(['user', 'assignAdmin'])
            ->where($this->searchWhere)
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
            $item['create_time'] = date('Y-m-d H:i:s', $item['create_time']);
            $item['deadline'] = $item['deadline'] ? date('Y-m-d H:i:s', $item['deadline']) : '';
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
}
