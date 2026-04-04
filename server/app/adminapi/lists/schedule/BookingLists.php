<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 预约列表
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\adminapi\lists\schedule;

use app\adminapi\lists\BaseAdminDataLists;
use app\common\lists\ListsExtendInterface;
use app\common\model\order\Order;
use app\common\model\order\OrderItem;

/**
 * 预约列表（基于待确认订单项）
 * Class BookingLists
 * @package app\adminapi\lists\schedule
 */
class BookingLists extends BaseAdminDataLists implements ListsExtendInterface
{
    /**
     * @notes 自定义搜索，不使用框架自动搜索
     * @return array
     */
    public function setSearch(): array
    {
        return [];
    }

    /**
     * @notes 构建搜索条件
     * @return array
     */
    private function createSearchWhere(): array
    {
        $where = [];
        $staffScopeId = $this->getStaffScopeId();

        if ($staffScopeId <= 0) {
            // 避免管理员误用 my* 接口时返回数据
            $where[] = ['oi.staff_id', '=', -1];
            return $where;
        }

        $where[] = ['oi.staff_id', '=', $staffScopeId];

        if (!empty($this->params['order_sn'])) {
            $where[] = ['o.order_sn', 'like', '%' . trim($this->params['order_sn']) . '%'];
        }

        if (!empty($this->params['customer_name'])) {
            $keyword = trim($this->params['customer_name']);
            $where[] = ['o.contact_name|u.nickname', 'like', '%' . $keyword . '%'];
        }

        if (!empty($this->params['contact_mobile'])) {
            $where[] = ['o.contact_mobile|u.mobile', 'like', '%' . trim($this->params['contact_mobile']) . '%'];
        }

        if (!empty($this->params['start_date']) && !empty($this->params['end_date'])) {
            $where[] = ['oi.service_date', 'between', [$this->params['start_date'], $this->params['end_date']]];
        }

        $status = $this->params['status'] ?? '';
        if ($status !== '' && $status !== null) {
            $status = (int)$status;
            switch ($status) {
                case 0: // 待确认
                    $where[] = ['oi.confirm_status', '=', 0];
                    $where[] = ['oi.item_status', '<>', OrderItem::STATUS_CANCELLED];
                    break;
                case 1: // 已确认
                    $where[] = ['oi.confirm_status', '=', 1];
                    $where[] = ['oi.item_status', 'in', [OrderItem::STATUS_PENDING, OrderItem::STATUS_IN_SERVICE]];
                    break;
                case 2: // 已完成
                    $where[] = ['oi.item_status', '=', OrderItem::STATUS_COMPLETED];
                    break;
                case 3: // 已取消
                    $where[] = ['oi.item_status', '=', OrderItem::STATUS_CANCELLED];
                    break;
            }
        } else {
            // 默认“待确认订单项”
            $where[] = ['oi.confirm_status', '=', 0];
            $where[] = ['oi.item_status', '<>', OrderItem::STATUS_CANCELLED];
        }

        return $where;
    }

    /**
     * @notes 预约列表
     * @return array
     */
    public function lists(): array
    {
        $where = $this->createSearchWhere();

        $lists = OrderItem::alias('oi')
            ->leftJoin('la_order o', 'o.id = oi.order_id')
            ->leftJoin('la_user u', 'u.id = o.user_id')
            ->field([
                'oi.id',
                'oi.order_id',
                'oi.staff_id',
                'oi.staff_name',
                'oi.package_name',
                'oi.service_date',
                'oi.item_status',
                'oi.confirm_status',
                'oi.schedule_id',
                'oi.price',
                'oi.quantity',
                'oi.subtotal',
                'oi.remark',
                'o.order_sn',
                'o.order_status',
                'o.pay_status',
                'o.pay_type',
                'o.confirm_deadline_time',
                'o.total_amount',
                'o.discount_amount',
                'o.pay_amount',
                'o.contact_name',
                'o.contact_mobile',
                'o.create_time',
                'u.nickname as user_nickname',
                'u.mobile as user_mobile',
            ])
            ->where($where)
            ->order('oi.id', 'desc')
            ->limit($this->limitOffset, $this->limitLength)
            ->select()
            ->toArray();

        foreach ($lists as &$item) {
            $item['customer_name'] = $item['contact_name'] ?: ($item['user_nickname'] ?? '');
            $item['customer_phone'] = $item['contact_mobile'] ?: ($item['user_mobile'] ?? '');
            $item['item_status_desc'] = $this->getItemStatusDesc((int)$item['item_status']);
            $item['confirm_status_desc'] = (int)$item['confirm_status'] === 1 ? '已确认' : '待确认';
            $item['order_status_desc'] = $this->getOrderStatusDesc((int)$item['order_status']);
            $item['create_time'] = $this->formatTimeValue($item['create_time'] ?? null);
            $item = array_merge(
                $item,
                Order::buildConfirmTimeoutSummaryFromState(
                    (int)($item['order_status'] ?? Order::STATUS_PENDING_CONFIRM),
                    (int)($item['confirm_deadline_time'] ?? 0)
                )
            );
        }

        return $lists;
    }

    /**
     * @notes 列表数量
     * @return int
     */
    public function count(): int
    {
        $where = $this->createSearchWhere();

        return OrderItem::alias('oi')
            ->leftJoin('la_order o', 'o.id = oi.order_id')
            ->leftJoin('la_user u', 'u.id = o.user_id')
            ->where($where)
            ->count();
    }

    /**
     * @notes 扩展统计
     * @return array
     */
    public function extend(): array
    {
        $staffScopeId = $this->getStaffScopeId();
        if ($staffScopeId <= 0) {
            return [
                'total' => 0,
                'pending' => 0,
                'confirmed' => 0,
                'completed' => 0,
                'cancelled' => 0,
            ];
        }

        $baseQuery = OrderItem::alias('oi')
            ->leftJoin('la_order o', 'o.id = oi.order_id')
            ->where('oi.staff_id', $staffScopeId)
            ->where('o.delete_time', null);

        return [
            'total' => (clone $baseQuery)->count(),
            'pending' => (clone $baseQuery)
                ->where('oi.confirm_status', 0)
                ->where('oi.item_status', '<>', OrderItem::STATUS_CANCELLED)
                ->count(),
            'confirmed' => (clone $baseQuery)
                ->where('oi.confirm_status', 1)
                ->where('oi.item_status', 'in', [OrderItem::STATUS_PENDING, OrderItem::STATUS_IN_SERVICE])
                ->count(),
            'completed' => (clone $baseQuery)->where('oi.item_status', OrderItem::STATUS_COMPLETED)->count(),
            'cancelled' => (clone $baseQuery)->where('oi.item_status', OrderItem::STATUS_CANCELLED)->count(),
        ];
    }

    /**
     * @notes 订单项状态文案
     * @param int $status
     * @return string
     */
    private function getItemStatusDesc(int $status): string
    {
        $map = [
            OrderItem::STATUS_PENDING => '待服务',
            OrderItem::STATUS_IN_SERVICE => '服务中',
            OrderItem::STATUS_COMPLETED => '已完成',
            OrderItem::STATUS_CANCELLED => '已取消',
        ];
        return $map[$status] ?? '未知';
    }

    /**
     * @notes 订单状态文案
     * @param int $status
     * @return string
     */
    private function getOrderStatusDesc(int $status): string
    {
        return Order::getStatusText($status);
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
            $timestamp = (int)$value;
            return $timestamp > 0 ? date('Y-m-d H:i:s', $timestamp) : '';
        }

        return (string)$value;
    }
}
