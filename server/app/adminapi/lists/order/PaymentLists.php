<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 支付记录列表
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\adminapi\lists\order;

use app\adminapi\lists\BaseAdminDataLists;
use app\common\lists\ListsExcelInterface;
use app\common\model\order\Payment;

/**
 * 支付记录列表
 * Class PaymentLists
 * @package app\adminapi\lists\order
 */
class PaymentLists extends BaseAdminDataLists implements ListsExcelInterface
{
    /**
     * @notes 搜索条件
     * @return array
     */
    public function setSearch(): array
    {
        return [
            '=' => ['pay_status', 'pay_way', 'pay_type', 'order_id', 'user_id'],
            '%like%' => ['payment_sn', 'order_sn', 'transaction_id'],
            'between_time' => ['create_time', 'pay_time'],
        ];
    }

    /**
     * @notes 列表
     * @return array
     */
    public function lists(): array
    {
        $lists = Payment::with([
            'order' => function ($query) {
                $query->field('id, order_sn, user_id, order_status')
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
            $item['pay_status_desc'] = $this->getStatusDesc($item['pay_status']);
            $item['pay_way_desc'] = $this->getWayDesc($item['pay_way']);
            $item['pay_type_desc'] = $this->getTypeDesc($item['pay_type']);
        }

        return $lists;
    }

    /**
     * @notes 总数
     * @return int
     */
    public function count(): int
    {
        return Payment::where($this->searchWhere)->count();
    }

    /**
     * @notes 导出字段
     * @return array
     */
    public function setExcelFields(): array
    {
        return [
            'payment_sn' => '支付流水号',
            'order_sn' => '订单编号',
            'order.user.nickname' => '用户昵称',
            'pay_status_desc' => '支付状态',
            'pay_way_desc' => '支付方式',
            'pay_type_desc' => '支付类型',
            'pay_amount' => '支付金额',
            'transaction_id' => '交易号',
            'create_time' => '创建时间',
            'pay_time' => '支付时间',
        ];
    }

    /**
     * @notes 导出文件名
     * @return string
     */
    public function setFileName(): string
    {
        return '支付记录';
    }

    /**
     * @notes 获取支付状态描述
     * @param int $status
     * @return string
     */
    protected function getStatusDesc(int $status): string
    {
        $map = [
            Payment::STATUS_PENDING => '待支付',
            Payment::STATUS_PAID => '已支付',
            Payment::STATUS_REFUNDED => '已退款',
            Payment::STATUS_FAILED => '支付失败',
        ];
        return $map[$status] ?? '未知';
    }

    /**
     * @notes 获取支付方式描述
     * @param int $way
     * @return string
     */
    protected function getWayDesc(int $way): string
    {
        $map = [
            Payment::WAY_WECHAT => '微信支付',
            Payment::WAY_ALIPAY => '支付宝',
            Payment::WAY_BALANCE => '余额支付',
            Payment::WAY_OFFLINE => '线下支付',
        ];
        return $map[$way] ?? '未知';
    }

    /**
     * @notes 获取支付类型描述
     * @param int $type
     * @return string
     */
    protected function getTypeDesc(int $type): string
    {
        $map = [
            Payment::TYPE_DEPOSIT => '定金',
            Payment::TYPE_BALANCE => '尾款',
            Payment::TYPE_FULL => '全款',
        ];
        return $map[$type] ?? '未知';
    }
}
