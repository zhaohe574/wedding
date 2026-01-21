<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 补拍申请列表
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\adminapi\lists\aftersale;

use app\adminapi\lists\BaseAdminDataLists;
use app\common\model\aftersale\Reshoot;
use app\common\lists\ListsSearchInterface;

/**
 * 补拍申请列表
 * Class ReshootLists
 * @package app\adminapi\lists\aftersale
 */
class ReshootLists extends BaseAdminDataLists implements ListsSearchInterface
{
    /**
     * @notes 设置搜索条件
     * @return array
     */
    public function setSearch(): array
    {
        return [
            '=' => ['type', 'reason_type', 'status', 'staff_id', 'is_free'],
            '%like%' => ['reshoot_sn'],
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
        $lists = Reshoot::with(['user', 'staff', 'newStaff', 'order'])
            ->where($this->searchWhere)
            ->when(!empty($this->params['order_id']), function ($query) {
                $query->where('order_id', $this->params['order_id']);
            })
            ->when(!empty($this->params['user_id']), function ($query) {
                $query->where('user_id', $this->params['user_id']);
            })
            ->when(!empty($this->params['start_time']) && !empty($this->params['end_time']), function ($query) {
                $query->whereBetweenTime('create_time', $this->params['start_time'], $this->params['end_time']);
            })
            ->order('id', 'desc')
            ->limit($this->limitOffset, $this->limitLength)
            ->select()
            ->toArray();

        foreach ($lists as &$item) {
            $reshoot = Reshoot::find($item['id']);
            $item['type_desc'] = $reshoot->type_desc ?? '';
            $item['reason_type_desc'] = $reshoot->reason_type_desc ?? '';
            $item['status_desc'] = $reshoot->status_desc ?? '';
            $item['create_time'] = date('Y-m-d H:i:s', $item['create_time']);
            $item['expect_date'] = $item['expect_date'] ?? '';
            $item['schedule_date'] = $item['schedule_date'] ?? '';
        }

        return $lists;
    }

    /**
     * @notes 获取数量
     * @return int
     */
    public function count(): int
    {
        return Reshoot::where($this->searchWhere)
            ->when(!empty($this->params['order_id']), function ($query) {
                $query->where('order_id', $this->params['order_id']);
            })
            ->when(!empty($this->params['user_id']), function ($query) {
                $query->where('user_id', $this->params['user_id']);
            })
            ->when(!empty($this->params['start_time']) && !empty($this->params['end_time']), function ($query) {
                $query->whereBetweenTime('create_time', $this->params['start_time'], $this->params['end_time']);
            })
            ->count();
    }
}
