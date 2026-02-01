<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 候补列表
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\adminapi\lists\schedule;

use app\adminapi\lists\BaseAdminDataLists;
use app\common\lists\ListsExtendInterface;
use app\common\model\schedule\Waitlist;

/**
 * 候补列表
 * Class WaitlistLists
 * @package app\adminapi\lists\schedule
 */
class WaitlistLists extends BaseAdminDataLists implements ListsExtendInterface
{
    /**
     * @notes 设置搜索条件（不使用框架自动搜索，因为需要JOIN查询）
     * @return array
     */
    public function setSearch(): array
    {
        // 返回空数组，不使用框架的自动搜索
        return [];
    }
    
    /**
     * @notes 创建搜索条件
     * @return array
     */
    private function createSearchWhere(): array
    {
        $where = [];
        
        // ID搜索
        if (!empty($this->params['id'])) {
            $where[] = ['w.id', '=', $this->params['id']];
        }
        
        // 工作人员搜索
        if (!empty($this->params['staff_id'])) {
            $where[] = ['w.staff_id', '=', $this->params['staff_id']];
        }
        
        // 状态搜索（前端传的是status，需要映射到notify_status）
        if (isset($this->params['status']) && $this->params['status'] !== '') {
            $where[] = ['w.notify_status', '=', $this->params['status']];
        }
        
        // 客户姓名搜索
        if (!empty($this->params['customer_name'])) {
            $where[] = ['u.nickname', 'like', '%' . $this->params['customer_name'] . '%'];
        }
        
        // 日期范围搜索
        if (!empty($this->params['start_date']) && !empty($this->params['end_date'])) {
            $where[] = ['w.schedule_date', 'between', [$this->params['start_date'], $this->params['end_date']]];
        }
        
        return $where;
    }

    /**
     * @notes 列表
     * @return array
     */
    public function lists(): array
    {
        // 创建搜索条件
        $where = $this->createSearchWhere();
        
        $lists = Waitlist::alias('w')
            ->leftJoin('la_user u', 'w.user_id = u.id')
            ->leftJoin('la_staff s', 'w.staff_id = s.id')
            ->leftJoin('la_service_package sp', 'w.package_id = sp.id')
            ->field('w.*, u.nickname as customer_name, u.mobile as customer_phone, s.name as staff_name, sp.name as service_name')
            ->where($where)
            ->order('w.create_time', 'desc')
            ->limit($this->limitOffset, $this->limitLength)
            ->select()
            ->toArray();

        foreach ($lists as &$item) {
            $item = $this->formatWaitlist($item);
        }
        
        return $lists;
    }

    /**
     * @notes 总数
     * @return int
     */
    public function count(): int
    {
        // 创建搜索条件
        $where = $this->createSearchWhere();
        
        $query = Waitlist::alias('w');
        
        // 如果有客户姓名搜索，需要关联用户表
        if (!empty($this->params['customer_name'])) {
            $query->leftJoin('la_user u', 'w.user_id = u.id');
        }
        
        return $query->where($where)->count();
    }

    /**
     * @notes 格式化候补数据
     * @param array $item
     * @return array
     */
    private function formatWaitlist(array $item): array
    {
        // 基本格式化 - 转换为整数
        $item['create_time'] = $this->formatTimeValue($item['create_time'] ?? null);
        $item['notify_time'] = $this->formatTimeValue($item['notify_time'] ?? null);
        $item['expire_time'] = $this->formatTimeValue($item['expire_time'] ?? null);

        // 状态描述
        $statusMap = [
            0 => '等待中',
            1 => '已通知',
            2 => '已下单',
            3 => '已过期'
        ];
        $item['notify_status_desc'] = $statusMap[$item['notify_status']] ?? '未知';

        // 时间段描述
        $timeSlotMap = [
            0 => '全天',
            1 => '早礼',
            2 => '午宴',
            3 => '晚宴'
        ];
        $item['time_slot_desc'] = $timeSlotMap[$item['time_slot']] ?? '未知';

        return $item;
    }

    /**
     * @notes 格式化时间值（兼容时间戳与字符串时间）
     * @param mixed $value
     * @return string
     */
    private function formatTimeValue($value): string
    {
        if ($value === null || $value === '' || $value === '0000-00-00 00:00:00') {
            return '';
        }

        if (is_numeric($value)) {
            $timestamp = (int) $value;
            return $timestamp > 0 ? date('Y-m-d H:i:s', $timestamp) : '';
        }

        return (string) $value;
    }

    /**
     * @notes 获取统计
     * @return array
     */
    public function countExtend(): array
    {
        $total = Waitlist::count();
        $waiting = Waitlist::where('notify_status', 0)->count();
        $notified = Waitlist::where('notify_status', 1)->count();
        $ordered = Waitlist::where('notify_status', 2)->count();
        $expired = Waitlist::where('notify_status', 3)->count();

        return [
            'total' => $total,
            'waiting' => $waiting,
            'notified' => $notified,
            'converted' => $ordered,
            'expired' => $expired,
        ];
    }

    /**
     * @notes 扩展字段
     * @return array
     */
    public function extend(): array
    {
        return $this->countExtend();
    }
}
