<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 退款列表
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\adminapi\lists\order;

use app\adminapi\lists\BaseAdminDataLists;
use app\common\lists\ListsExcelInterface;
use app\common\model\order\Refund;

/**
 * 退款列表
 * Class RefundLists
 * @package app\adminapi\lists\order
 */
class RefundLists extends BaseAdminDataLists implements ListsExcelInterface
{
    /**
     * @notes 搜索条件
     * @return array
     */
    public function setSearch(): array
    {
        return [
            '=' => ['refund_status', 'refund_type', 'order_id', 'user_id'],
            '%like%' => ['refund_sn'],
            'between_time' => ['create_time'],
        ];
    }

    /**
     * @notes 列表
     * @return array
     */
    public function lists(): array
    {
        $lists = Refund::with([
            'order' => function ($query) {
                $query->field('id, order_sn, user_id, total_amount, pay_amount')
                    ->with(['user' => function ($q) {
                        $q->field('id, nickname, avatar, mobile');
                    }]);
            }
        ])
            ->where($this->searchWhere)
            ->order($this->sortOrder ?: ['id' => 'desc'])
            ->limit($this->limitOffset, $this->limitLength)
            ->select()
            ->toArray();

        foreach ($lists as &$item) {
            $item['refund_status_desc'] = $this->getStatusDesc($item['refund_status']);
            $item['refund_type_desc'] = $this->getTypeDesc($item['refund_type']);
        }

        return $lists;
    }

    /**
     * @notes 总数
     * @return int
     */
    public function count(): int
    {
        return Refund::where($this->searchWhere)->count();
    }

    /**
     * @notes 导出字段
     * @return array
     */
    public function setExcelFields(): array
    {
        return [
            'refund_sn' => '退款编号',
            'order.order_sn' => '订单编号',
            'order.user.nickname' => '用户昵称',
            'refund_status_desc' => '退款状态',
            'refund_type_desc' => '退款类型',
            'refund_amount' => '退款金额',
            'refund_reason' => '退款原因',
            'create_time' => '申请时间',
            'audit_time' => '审核时间',
            'refund_time' => '退款时间',
        ];
    }

    /**
     * @notes 导出文件名
     * @return string
     */
    public function setFileName(): string
    {
        return '退款列表';
    }

    /**
     * @notes 获取退款状态描述
     * @param int $status
     * @return string
     */
    protected function getStatusDesc(int $status): string
    {
        $map = [
            Refund::STATUS_PENDING => '待审核',
            Refund::STATUS_APPROVED => '审核通过',
            Refund::STATUS_PROCESSING => '退款中',
            Refund::STATUS_COMPLETED => '已退款',
            Refund::STATUS_REJECTED => '已拒绝',
        ];
        return $map[$status] ?? '未知';
    }

    /**
     * @notes 获取退款类型描述
     * @param int $type
     * @return string
     */
    protected function getTypeDesc(int $type): string
    {
        $map = [
            Refund::TYPE_USER => '用户申请',
            Refund::TYPE_ADMIN => '管理员操作',
            Refund::TYPE_SYSTEM => '系统自动',
        ];
        return $map[$type] ?? '未知';
    }
}
