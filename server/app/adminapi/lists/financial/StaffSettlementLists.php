<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 服务人员结算列表
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\adminapi\lists\financial;

use app\adminapi\lists\BaseAdminDataLists;
use app\common\lists\ListsExcelInterface;
use app\common\lists\ListsSearchInterface;
use app\common\model\financial\StaffSettlement;
use app\common\model\financial\StaffSettlementTransfer;

/**
 * 服务人员结算列表
 * Class StaffSettlementLists
 * @package app\adminapi\lists\financial
 */
class StaffSettlementLists extends BaseAdminDataLists implements ListsSearchInterface, ListsExcelInterface
{
    /**
     * @notes 搜索条件
     */
    public function setSearch(): array
    {
        return [
            '=' => ['staff_id', 'status', 'settlement_type', 'batch_id'],
        ];
    }

    /**
     * @notes 列表
     */
    public function lists(): array
    {
        $query = StaffSettlement::with(['staff', 'order', 'transfers'])
            ->where($this->searchWhere);

        if (!empty($this->params['start_date']) && !empty($this->params['end_date'])) {
            $query->whereBetween('service_date', [$this->params['start_date'], $this->params['end_date']]);
        }

        if (!empty($this->params['order_sn'])) {
            $query->whereExists(function ($q) {
                $q->table('la_order')
                    ->whereColumn('la_order.id', 'la_staff_settlement.order_id')
                    ->whereLike('order_sn', '%' . $this->params['order_sn'] . '%');
            });
        }

        if (!empty($this->params['staff_name'])) {
            $query->whereExists(function ($q) {
                $q->table('la_staff')
                    ->whereColumn('la_staff.id', 'la_staff_settlement.staff_id')
                    ->whereLike('name', '%' . $this->params['staff_name'] . '%');
            });
        }

        $list = $query->order('id', 'desc')
            ->limit($this->limitOffset, $this->limitLength)
            ->select()
            ->toArray();

        foreach ($list as &$item) {
            $item['status_text'] = StaffSettlement::getStatusDesc($item['status']);
            $item['type_text'] = StaffSettlement::getTypeDesc($item['settlement_type']);
            $item['settle_way_text'] = StaffSettlement::getSettleWayDesc($item['settle_way']);
            $item['transfer_summary'] = $this->buildTransferSummary($item['transfers'] ?? []);
            $item['transfer_status_text'] = $item['transfer_summary']['status_text'];
            $item['transfer_out_bill_no'] = $item['transfer_summary']['out_bill_no'];
            $item['transfer_bill_no'] = $item['transfer_summary']['transfer_bill_no'];
            $item['transfer_fail_reason'] = $item['transfer_summary']['fail_reason'];
        }

        return $list;
    }

    /**
     * @notes 总数
     */
    public function count(): int
    {
        $query = StaffSettlement::where($this->searchWhere);

        if (!empty($this->params['start_date']) && !empty($this->params['end_date'])) {
            $query->whereBetween('service_date', [$this->params['start_date'], $this->params['end_date']]);
        }

        if (!empty($this->params['order_sn'])) {
            $query->whereExists(function ($q) {
                $q->table('la_order')
                    ->whereColumn('la_order.id', 'la_staff_settlement.order_id')
                    ->whereLike('order_sn', '%' . $this->params['order_sn'] . '%');
            });
        }

        if (!empty($this->params['staff_name'])) {
            $query->whereExists(function ($q) {
                $q->table('la_staff')
                    ->whereColumn('la_staff.id', 'la_staff_settlement.staff_id')
                    ->whereLike('name', '%' . $this->params['staff_name'] . '%');
            });
        }

        return $query->count();
    }

    /**
     * @notes 导出字段
     */
    public function setExcelFields(): array
    {
        return [
            'settlement_sn' => '结算编号',
            'staff.name' => '服务人员',
            'order.order_sn' => '订单编号',
            'service_date' => '服务日期',
            'order_amount' => '订单金额',
            'settlement_rate' => '结算比例(%)',
            'settlement_amount' => '结算金额',
            'platform_amount' => '平台抽成',
            'cost_amount' => '扣除成本',
            'actual_amount' => '实际结算',
            'status_text' => '状态',
            'settle_time' => '结算时间',
        ];
    }

    /**
     * @notes 构造转账摘要
     */
    protected function buildTransferSummary(array $transfers): array
    {
        $summary = [
            'count' => count($transfers),
            'success_count' => 0,
            'wait_confirm_count' => 0,
            'status_text' => '',
            'out_bill_no' => '',
            'transfer_bill_no' => '',
            'fail_reason' => '',
        ];

        foreach ($transfers as $transfer) {
            if ($summary['out_bill_no'] === '' && !empty($transfer['out_bill_no'])) {
                $summary['out_bill_no'] = (string)$transfer['out_bill_no'];
            }
            if ($summary['transfer_bill_no'] === '' && !empty($transfer['transfer_bill_no'])) {
                $summary['transfer_bill_no'] = (string)$transfer['transfer_bill_no'];
            }
            if ($summary['fail_reason'] === '' && !empty($transfer['fail_reason'])) {
                $summary['fail_reason'] = (string)$transfer['fail_reason'];
            }
            if ((int)($transfer['status'] ?? -1) === StaffSettlementTransfer::STATUS_SUCCESS) {
                $summary['success_count']++;
            }
            if ((int)($transfer['status'] ?? -1) === StaffSettlementTransfer::STATUS_WAIT_USER_CONFIRM) {
                $summary['wait_confirm_count']++;
            }
        }

        if ($summary['count'] > 0) {
            $firstStatus = (int)($transfers[0]['status'] ?? StaffSettlementTransfer::STATUS_PENDING);
            $summary['status_text'] = StaffSettlementTransfer::getStatusDesc($firstStatus);
            if ($summary['success_count'] === $summary['count']) {
                $summary['status_text'] = '全部已到账';
            }
        }

        return $summary;
    }

    /**
     * @notes 导出文件名
     */
    public function setFileName(): string
    {
        return '服务人员结算记录';
    }
}
