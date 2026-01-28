<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 档期列表
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\adminapi\lists\schedule;

use app\adminapi\lists\BaseAdminDataLists;
use app\common\lists\ListsExcelInterface;
use app\common\model\schedule\Schedule;

/**
 * 档期列表
 * Class ScheduleLists
 * @package app\adminapi\lists\schedule
 */
class ScheduleLists extends BaseAdminDataLists implements ListsExcelInterface
{
    /**
     * @notes 搜索条件
     * @return array
     */
    public function setSearch(): array
    {
        return [
            '=' => ['staff_id', 'status', 'time_slot', 'lock_type'],
            'between_date' => ['schedule_date'],
        ];
    }

    /**
     * @notes 列表
     * @return array
     */
    public function lists(): array
    {
        $lists = Schedule::with(['staff' => function ($query) {
                $query->field('id, name, avatar, category_id');
            }])
            ->where($this->searchWhere)
            ->order($this->sortOrder ?: ['schedule_date' => 'asc', 'time_slot' => 'asc'])
            ->limit($this->limitOffset, $this->limitLength)
            ->select()
            ->toArray();

        foreach ($lists as &$item) {
            $item['time_slot_desc'] = $this->getTimeSlotDesc($item['time_slot']);
            $item['status_desc'] = $this->getStatusDesc($item['status']);
            $item['lock_type_desc'] = $this->getLockTypeDesc($item['lock_type']);
        }

        return $lists;
    }

    /**
     * @notes 总数
     * @return int
     */
    public function count(): int
    {
        return Schedule::where($this->searchWhere)->count();
    }

    /**
     * @notes 导出字段
     * @return array
     */
    public function setExcelFields(): array
    {
        return [
            'id' => 'ID',
            'staff.name' => '工作人员',
            'schedule_date' => '日期',
            'time_slot_desc' => '时间段',
            'status_desc' => '状态',
            'price' => '价格',
            'remark' => '备注',
            'create_time' => '创建时间',
        ];
    }

    /**
     * @notes 导出文件名
     * @return string
     */
    public function setFileName(): string
    {
        return '档期列表';
    }

    /**
     * @notes 获取时间段描述
     * @param int $timeSlot
     * @return string
     */
    protected function getTimeSlotDesc(int $timeSlot): string
    {
        $map = [
            0 => '全天',
            1 => '早礼',
            2 => '午宴',
            3 => '晚宴',
        ];
        return $map[$timeSlot] ?? '未知';
    }

    /**
     * @notes 获取状态描述
     * @param int $status
     * @return string
     */
    protected function getStatusDesc(int $status): string
    {
        $map = [
            0 => '不可用',
            1 => '可预约',
            2 => '已预约',
            3 => '已锁定',
            4 => '内部预留',
        ];
        return $map[$status] ?? '未知';
    }

    /**
     * @notes 获取锁定类型描述
     * @param int $lockType
     * @return string
     */
    protected function getLockTypeDesc(int $lockType): string
    {
        $map = [
            0 => '正常',
            1 => 'VIP锁定',
            2 => '内部预留',
        ];
        return $map[$lockType] ?? '未知';
    }
}
