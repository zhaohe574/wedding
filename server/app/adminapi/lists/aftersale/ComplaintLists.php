<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 投诉列表
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\adminapi\lists\aftersale;

use app\adminapi\lists\BaseAdminDataLists;
use app\common\model\aftersale\Complaint;
use app\common\lists\ListsSearchInterface;

/**
 * 投诉列表
 * Class ComplaintLists
 * @package app\adminapi\lists\aftersale
 */
class ComplaintLists extends BaseAdminDataLists implements ListsSearchInterface
{
    /**
     * @notes 设置搜索条件
     * @return array
     */
    public function setSearch(): array
    {
        return [
            '=' => ['type', 'level', 'status', 'staff_id'],
            '%like%' => ['complaint_sn', 'title'],
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
        $lists = Complaint::with(['user', 'staff'])
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
            $complaint = Complaint::find($item['id']);
            $item['type_desc'] = $complaint->type_desc ?? '';
            $item['level_desc'] = $complaint->level_desc ?? '';
            $item['status_desc'] = $complaint->status_desc ?? '';
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
        return Complaint::where($this->searchWhere)
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
