<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 服务人员中心我的回访列表
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\adminapi\lists\aftersale;

use app\adminapi\lists\BaseAdminDataLists;
use app\common\lists\ListsSearchInterface;
use app\common\model\aftersale\ServiceCallback;

/**
 * 服务人员中心我的回访列表
 * Class MyCallbackLists
 * @package app\adminapi\lists\aftersale
 */
class MyCallbackLists extends BaseAdminDataLists implements ListsSearchInterface
{
    private int $staffId = 0;

    public function __construct()
    {
        parent::__construct();
        $this->staffId = $this->getStaffScopeId();
    }

    /**
     * @notes 设置搜索条件
     * @return array
     */
    public function setSearch(): array
    {
        return [
            '=' => ['type', 'method', 'status', 'has_problem'],
            '%like%' => ['callback_sn'],
        ];
    }

    /**
     * @notes 获取列表
     * @return array
     */
    public function lists(): array
    {
        if ($this->staffId <= 0) {
            return [];
        }

        $lists = $this->baseQuery()
            ->order('id', 'desc')
            ->limit($this->limitOffset, $this->limitLength)
            ->select()
            ->toArray();

        foreach ($lists as &$item) {
            $callback = ServiceCallback::find($item['id']);
            $item['type_desc'] = $callback->type_desc ?? '';
            $item['method_desc'] = $callback->method_desc ?? '';
            $item['status_desc'] = $callback->status_desc ?? '';
            $item['create_time'] = $this->formatDateTime($item['create_time'] ?? null);
            $item['plan_time'] = $this->formatDateTime($item['plan_time'] ?? null);
            $item['actual_time'] = $this->formatDateTime($item['actual_time'] ?? null);
        }

        return $lists;
    }

    /**
     * @notes 获取数量
     * @return int
     */
    public function count(): int
    {
        if ($this->staffId <= 0) {
            return 0;
        }
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
            ? ServiceCallback::with(['user', 'staff', 'admin', 'order'])
            : ServiceCallback::where([]);

        return $query->where($this->searchWhere)
            ->where('staff_id', $this->staffId)
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
                $planStart = strtotime((string)$this->params['plan_date']);
                $planEnd = $planStart + 86400;
                $query->where('plan_time', '>=', $planStart)->where('plan_time', '<', $planEnd);
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
