<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 订单转让列表
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\adminapi\lists\order;

use app\adminapi\lists\BaseAdminDataLists;
use app\common\lists\ListsExcelInterface;
use app\common\model\order\OrderTransfer;

/**
 * 订单转让列表
 * Class OrderTransferLists
 * @package app\adminapi\lists\order
 */
class OrderTransferLists extends BaseAdminDataLists implements ListsExcelInterface
{
    /**
     * @notes 搜索条件
     * @return array
     */
    public function setSearch(): array
    {
        return [
            '=' => ['transfer_status', 'order_id', 'from_user_id', 'to_user_id'],
            '%like%' => ['transfer_sn', 'order_sn', 'from_user_mobile', 'to_user_mobile', 'from_user_name', 'to_user_name'],
            'between_time' => ['create_time', 'audit_time', 'complete_time'],
        ];
    }

    /**
     * @notes 额外搜索条件
     * @return array
     */
    public function queryWhere(): array
    {
        $where = [];

        // 用户关键词搜索（转让方或接收方）
        if (!empty($this->params['user_keyword'])) {
            $keyword = $this->params['user_keyword'];
            $where[] = [function ($query) use ($keyword) {
                $query->where('from_user_name|from_user_mobile|to_user_name|to_user_mobile', 'like', '%' . $keyword . '%');
            }];
        }

        // 接收方是否已验证
        if (isset($this->params['to_user_verified'])) {
            $where[] = ['to_user_verified', '=', $this->params['to_user_verified']];
        }

        return $where;
    }

    /**
     * @notes 列表
     * @return array
     */
    public function lists(): array
    {
        $lists = OrderTransfer::with([
            'order' => function ($query) {
                $query->field('id, order_sn, user_id, order_status, pay_amount, service_date');
            },
            'fromUser' => function ($query) {
                $query->field('id, nickname, avatar, mobile');
            },
            'toUser' => function ($query) {
                $query->field('id, nickname, avatar, mobile');
            },
        ])
            ->where($this->searchWhere)
            ->where($this->queryWhere())
            ->order($this->sortOrder ?: ['id' => 'desc'])
            ->limit($this->limitOffset, $this->limitLength)
            ->select()
            ->toArray();

        foreach ($lists as &$item) {
            $item['transfer_status_desc'] = $this->getStatusDesc($item['transfer_status']);
            // 隐藏验证码
            unset($item['accept_code']);
        }

        return $lists;
    }

    /**
     * @notes 总数
     * @return int
     */
    public function count(): int
    {
        return OrderTransfer::where($this->searchWhere)
            ->where($this->queryWhere())
            ->count();
    }

    /**
     * @notes 导出字段
     * @return array
     */
    public function setExcelFields(): array
    {
        return [
            'transfer_sn' => '转让单号',
            'order_sn' => '订单编号',
            'from_user_name' => '转让方',
            'from_user_mobile' => '转让方手机',
            'to_user_name' => '接收方',
            'to_user_mobile' => '接收方手机',
            'transfer_status_desc' => '转让状态',
            'transfer_reason' => '转让原因',
            'transfer_fee' => '手续费',
            'create_time' => '申请时间',
            'audit_time' => '审核时间',
            'accept_time' => '接收时间',
            'complete_time' => '完成时间',
        ];
    }

    /**
     * @notes 导出文件名
     * @return string
     */
    public function setFileName(): string
    {
        return '订单转让列表';
    }

    /**
     * @notes 获取转让状态描述
     * @param int $status
     * @return string
     */
    protected function getStatusDesc(int $status): string
    {
        $map = [
            OrderTransfer::STATUS_PENDING => '待审核',
            OrderTransfer::STATUS_WAITING => '待接收',
            OrderTransfer::STATUS_ACCEPTED => '接收确认',
            OrderTransfer::STATUS_COMPLETED => '转让完成',
            OrderTransfer::STATUS_REJECTED => '已拒绝',
            OrderTransfer::STATUS_CANCELLED => '已取消',
        ];
        return $map[$status] ?? '未知';
    }
}
