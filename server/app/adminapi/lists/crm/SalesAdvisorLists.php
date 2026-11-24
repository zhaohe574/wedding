<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 销售顾问列表
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\adminapi\lists\crm;

use app\adminapi\lists\BaseAdminDataLists;
use app\common\lists\ListsSearchInterface;
use app\common\model\crm\Customer;
use app\common\model\crm\CustomerLossWarning;
use app\common\model\crm\SalesAdvisor;
use think\facade\Db;

/**
 * 销售顾问列表
 * Class SalesAdvisorLists
 * @package app\adminapi\lists\crm
 */
class SalesAdvisorLists extends BaseAdminDataLists implements ListsSearchInterface
{
    /**
     * @notes 设置搜索条件
     * @return array
     */
    public function setSearch(): array
    {
        return [
            '=' => ['status'],
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
        $query = SalesAdvisor::where($this->searchWhere)
            ->field('id,admin_id,advisor_name,avatar,mobile,email,areas,specialties,max_customer_count,current_customer_count,total_order_count,total_order_amount,conversion_rate,status,sort,create_time,update_time')
            ->append(['status_desc']);

        $this->applyKeyword($query);

        $list = $query
            ->order(['sort' => 'desc', 'id' => 'desc'])
            ->limit($this->limitOffset, $this->limitLength)
            ->select()
            ->toArray();

        $adminMap = $this->getAdminMap(array_column($list, 'admin_id'));
        $advisorIds = array_values(array_unique(array_filter(array_column($list, 'id'))));
        $customerStatsMap = $this->getCustomerStatsMap($advisorIds);
        $warningCountMap = $this->getPendingWarningMap($advisorIds);

        foreach ($list as &$item) {
            $advisorId = (int)$item['id'];
            $currentCount = (int)($item['current_customer_count'] ?? 0);
            $maxCount = (int)($item['max_customer_count'] ?? 0);
            $cStats = $customerStatsMap[$advisorId] ?? ['active' => 0, 'signed' => 0, 'total' => 0];

            $item['admin'] = $adminMap[(int)($item['admin_id'] ?? 0)] ?? null;
            $item['load_text'] = $currentCount . '/' . $maxCount;
            $item['can_assign'] = (int)($item['status'] ?? 0) === SalesAdvisor::STATUS_NORMAL
                && $currentCount < $maxCount;
            $item['active_customer_count'] = $cStats['active'];
            $item['signed_customer_count'] = $cStats['signed'];
            $item['total_customer_count'] = $cStats['total'];
            $item['pending_warning_count'] = $warningCountMap[$advisorId] ?? 0;
        }
        unset($item);

        return $list;
    }

    /**
     * @notes 获取数量
     * @return int
     */
    public function count(): int
    {
        $query = SalesAdvisor::where($this->searchWhere);
        $this->applyKeyword($query);
        return $query->count();
    }

    /**
     * @notes 关键词搜索
     * @param mixed $query
     * @return void
     */
    private function applyKeyword($query): void
    {
        $keyword = trim((string)($this->params['keyword'] ?? ''));
        if ($keyword === '') {
            return;
        }

        $query->where(function ($subQuery) use ($keyword) {
            $subQuery->whereLike('advisor_name', '%' . $keyword . '%')
                ->whereOr('mobile', 'like', '%' . $keyword . '%')
                ->whereOr('email', 'like', '%' . $keyword . '%');
        });
    }

    /**
     * @notes 批量聚合各顾问名下客户分布
     */
    private function getCustomerStatsMap(array $advisorIds): array
    {
        if (empty($advisorIds)) {
            return [];
        }

        $rows = Customer::whereIn('advisor_id', $advisorIds)
            ->field('advisor_id, COUNT(*) as total_cnt, SUM(CASE WHEN customer_status IN (1, 2) THEN 1 ELSE 0 END) as active_cnt, SUM(CASE WHEN customer_status IN (3, 5) THEN 1 ELSE 0 END) as signed_cnt')
            ->group('advisor_id')
            ->select()
            ->toArray();

        $map = [];
        foreach ($rows as $row) {
            $map[(int)$row['advisor_id']] = [
                'total' => (int)($row['total_cnt'] ?? 0),
                'active' => (int)($row['active_cnt'] ?? 0),
                'signed' => (int)($row['signed_cnt'] ?? 0),
            ];
        }
        return $map;
    }

    /**
     * @notes 批量聚合待处理流失预警数
     */
    private function getPendingWarningMap(array $advisorIds): array
    {
        if (empty($advisorIds)) {
            return [];
        }

        $rows = CustomerLossWarning::whereIn('advisor_id', $advisorIds)
            ->where('warning_status', CustomerLossWarning::STATUS_PENDING)
            ->field('advisor_id, COUNT(*) as cnt')
            ->group('advisor_id')
            ->select()
            ->toArray();

        $map = [];
        foreach ($rows as $row) {
            $map[(int)$row['advisor_id']] = (int)($row['cnt'] ?? 0);
        }
        return $map;
    }

    /**
     * @notes 获取轻量管理员信息
     * @param array $adminIds
     * @return array
     */
    private function getAdminMap(array $adminIds): array
    {
        $adminIds = array_values(array_unique(array_filter(array_map('intval', $adminIds))));
        if (empty($adminIds)) {
            return [];
        }

        $admins = Db::name('admin')
            ->whereIn('id', $adminIds)
            ->field('id,name,account,avatar')
            ->select()
            ->toArray();

        $map = [];
        foreach ($admins as $admin) {
            $map[(int)$admin['id']] = $admin;
        }
        return $map;
    }
}
