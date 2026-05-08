<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 服务人员结算列表
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\adminapi\lists\financial;

use app\adminapi\lists\BaseAdminDataLists;
use app\common\model\financial\StaffSettlement;
use app\common\model\financial\StaffSettlementRedPacket;
use app\common\lists\ListsSearchInterface;
use app\common\lists\ListsExcelInterface;

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
        $query = StaffSettlement::with(['staff', 'order', 'redPackets'])
            ->where($this->searchWhere);
        
        // 服务日期范围
        if (!empty($this->params['start_date']) && !empty($this->params['end_date'])) {
            $query->whereBetween('service_date', [$this->params['start_date'], $this->params['end_date']]);
        }
        
        // 订单编号搜索
        if (!empty($this->params['order_sn'])) {
            $query->whereExists(function ($q) {
                $q->table('la_order')
                    ->whereColumn('la_order.id', 'la_staff_settlement.order_id')
                    ->whereLike('order_sn', '%' . $this->params['order_sn'] . '%');
            });
        }
        
        // 人员姓名搜索
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
            $item['red_packet_summary'] = $this->buildRedPacketSummary($item['red_packets'] ?? []);
            $item['red_packet_status_text'] = $item['red_packet_summary']['status_text'];
            $item['red_packet_mch_billno'] = $item['red_packet_summary']['mch_billno'];
            $item['red_packet_wx_hb_id'] = $item['red_packet_summary']['wx_hb_id'];
            $item['red_packet_fail_reason'] = $item['red_packet_summary']['fail_reason'];
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
     * @notes 构造红包摘要
     */
    protected function buildRedPacketSummary(array $packets): array
    {
        $summary = [
            'count' => count($packets),
            'received_count' => 0,
            'status_text' => '',
            'mch_billno' => '',
            'wx_hb_id' => '',
            'fail_reason' => '',
        ];

        foreach ($packets as $packet) {
            if ($summary['mch_billno'] === '' && !empty($packet['mch_billno'])) {
                $summary['mch_billno'] = (string)$packet['mch_billno'];
            }
            if ($summary['wx_hb_id'] === '' && !empty($packet['wx_hb_id'])) {
                $summary['wx_hb_id'] = (string)$packet['wx_hb_id'];
            }
            if ($summary['fail_reason'] === '' && !empty($packet['fail_reason'])) {
                $summary['fail_reason'] = (string)$packet['fail_reason'];
            }
            if ((int)($packet['status'] ?? -1) === StaffSettlementRedPacket::STATUS_RECEIVED) {
                $summary['received_count']++;
            }
        }

        if ($summary['count'] > 0) {
            $firstStatus = (int)($packets[0]['status'] ?? StaffSettlementRedPacket::STATUS_PENDING);
            $summary['status_text'] = StaffSettlementRedPacket::getStatusDesc($firstStatus);
            if ($summary['received_count'] === $summary['count']) {
                $summary['status_text'] = '全部已领取';
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
