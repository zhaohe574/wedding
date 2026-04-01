<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 订单日志列表
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\adminapi\lists\order;

use app\adminapi\lists\BaseAdminDataLists;
use app\common\model\order\Order;
use app\common\model\order\OrderLog;

/**
 * 订单日志列表
 * Class OrderLogLists
 * @package app\adminapi\lists\order
 */
class OrderLogLists extends BaseAdminDataLists
{
    /**
     * @notes 搜索条件
     * @return array
     */
    public function setSearch(): array
    {
        return [
            '=' => ['order_id', 'operator_type', 'action'],
        ];
    }

    /**
     * @notes 列表
     * @return array
     */
    public function lists(): array
    {
        $lists = OrderLog::where($this->searchWhere)
            ->order('create_time', 'desc')
            ->limit($this->limitOffset, $this->limitLength)
            ->select()
            ->toArray();

        foreach ($lists as &$item) {
            $item['operator_type_desc'] = $this->getOperatorTypeDesc($item['operator_type']);
            $item['action_desc'] = OrderLog::getActionDesc($item['action']);
            $item['before_status_desc'] = $this->getStatusDesc($item['before_status']);
            $item['after_status_desc'] = $this->getStatusDesc($item['after_status']);
        }

        return $lists;
    }

    /**
     * @notes 总数
     * @return int
     */
    public function count(): int
    {
        return OrderLog::where($this->searchWhere)->count();
    }

    /**
     * @notes 获取操作者类型描述
     * @param int $type
     * @return string
     */
    protected function getOperatorTypeDesc(int $type): string
    {
        $map = [
            OrderLog::OPERATOR_USER => '用户',
            OrderLog::OPERATOR_ADMIN => '管理员',
            OrderLog::OPERATOR_SYSTEM => '系统',
        ];
        return $map[$type] ?? '未知';
    }

    /**
     * @notes 获取状态描述
     * @param int $status
     * @return string
     */
    protected function getStatusDesc(int $status): string
    {
        $map = [
            Order::STATUS_PENDING_CONFIRM => '待确认',
            Order::STATUS_PENDING_PAY => '待支付',
            Order::STATUS_PAID => '已支付',
            Order::STATUS_IN_SERVICE => '服务中',
            Order::STATUS_COMPLETED => '已完成',
            Order::STATUS_REVIEWED => '已评价',
            Order::STATUS_CANCELLED => '已取消',
            Order::STATUS_PAUSED => '已暂停',
            Order::STATUS_REFUNDED => '已退款',
            Order::STATUS_USER_DELETED => '用户已删除',
        ];
        return $map[$status] ?? '-';
    }
}
