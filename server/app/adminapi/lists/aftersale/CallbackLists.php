<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 回访列表
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\adminapi\lists\aftersale;

use app\adminapi\lists\BaseAdminDataLists;
use app\common\model\aftersale\ServiceCallback;
use app\common\lists\ListsSearchInterface;

/**
 * 回访列表
 * Class CallbackLists
 * @package app\adminapi\lists\aftersale
 */
class CallbackLists extends BaseAdminDataLists implements ListsSearchInterface
{
    /**
     * @notes 设置搜索条件
     * @return array
     */
    public function setSearch(): array
    {
        return [
            '=' => ['type', 'method', 'status', 'admin_id', 'has_problem'],
            '%like%' => ['callback_sn'],
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
        $lists = ServiceCallback::with(['user', 'staff', 'admin', 'order'])
            ->where($this->searchWhere)
            ->when(!empty($this->params['order_id']), function ($query) {
                $query->where('order_id', $this->params['order_id']);
            })
            ->when(!empty($this->params['user_id']), function ($query) {
                $query->where('user_id', $this->params['user_id']);
            })
            ->when(!empty($this->params['problem_status']), function ($query) {
                $query->where('problem_status', $this->params['problem_status']);
            })
            ->when(!empty($this->params['plan_date']), function ($query) {
                $planStart = strtotime($this->params['plan_date']);
                $planEnd = $planStart + 86400;
                $query->where('plan_time', '>=', $planStart)->where('plan_time', '<', $planEnd);
            })
            ->when(!empty($this->params['start_time']) && !empty($this->params['end_time']), function ($query) {
                $query->whereBetweenTime('create_time', $this->params['start_time'], $this->params['end_time']);
            })
            ->order('id', 'desc')
            ->limit($this->limitOffset, $this->limitLength)
            ->select()
            ->toArray();

        foreach ($lists as &$item) {
            $callback = ServiceCallback::find($item['id']);
            $item['type_desc'] = $callback->type_desc ?? '';
            $item['method_desc'] = $callback->method_desc ?? '';
            $item['status_desc'] = $callback->status_desc ?? '';
            $item['create_time'] = date('Y-m-d H:i:s', $item['create_time']);
            $item['plan_time'] = $item['plan_time'] ? date('Y-m-d H:i:s', $item['plan_time']) : '';
            $item['actual_time'] = $item['actual_time'] ? date('Y-m-d H:i:s', $item['actual_time']) : '';
        }

        return $lists;
    }

    /**
     * @notes 获取数量
     * @return int
     */
    public function count(): int
    {
        return ServiceCallback::where($this->searchWhere)
            ->when(!empty($this->params['order_id']), function ($query) {
                $query->where('order_id', $this->params['order_id']);
            })
            ->when(!empty($this->params['user_id']), function ($query) {
                $query->where('user_id', $this->params['user_id']);
            })
            ->when(!empty($this->params['problem_status']), function ($query) {
                $query->where('problem_status', $this->params['problem_status']);
            })
            ->when(!empty($this->params['plan_date']), function ($query) {
                $planStart = strtotime($this->params['plan_date']);
                $planEnd = $planStart + 86400;
                $query->where('plan_time', '>=', $planStart)->where('plan_time', '<', $planEnd);
            })
            ->when(!empty($this->params['start_time']) && !empty($this->params['end_time']), function ($query) {
                $query->whereBetweenTime('create_time', $this->params['start_time'], $this->params['end_time']);
            })
            ->count();
    }
}
