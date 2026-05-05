<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 流失预警列表
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\adminapi\lists\crm;

use app\adminapi\lists\BaseAdminDataLists;
use app\common\lists\ListsSearchInterface;
use app\common\model\crm\Customer;
use app\common\model\crm\CustomerLossWarning;
use app\common\model\crm\SalesAdvisor;

/**
 * 流失预警列表
 */
class LossWarningLists extends BaseAdminDataLists implements ListsSearchInterface
{
    /**
     * @notes 设置搜索条件
     */
    public function setSearch(): array
    {
        return [
            '=' => ['customer_id', 'advisor_id', 'warning_type', 'warning_level', 'warning_status'],
        ];
    }

    /**
     * @notes 获取列表
     */
    public function lists(): array
    {
        $advisorScopeId = $this->getCrmAdvisorScopeId();
        if ($advisorScopeId < 0) {
            return [];
        }

        $query = CustomerLossWarning::where($this->searchWhere)
            ->append(['warning_type_desc', 'warning_level_desc', 'warning_status_desc']);
        if ($advisorScopeId > 0) {
            $query->where('advisor_id', $advisorScopeId);
        }
        $this->applyKeyword($query);

        $list = $query->order(['warning_status' => 'asc', 'warning_level' => 'desc', 'id' => 'desc'])
            ->limit($this->limitOffset, $this->limitLength)
            ->select()
            ->toArray();

        $customerMap = $this->getCustomerMap(array_column($list, 'customer_id'));
        $advisorMap = $this->getAdvisorMap(array_column($list, 'advisor_id'));
        foreach ($list as &$item) {
            $customerId = (int)($item['customer_id'] ?? 0);
            $advisorId = (int)($item['advisor_id'] ?? 0);
            $item['customer'] = $customerMap[$customerId] ?? null;
            $item['advisor'] = $advisorMap[$advisorId] ?? null;
            $item['handle_time_text'] = $this->formatTime($item['handle_time'] ?? 0);
            $item['create_time_text'] = $this->formatTime($item['create_time'] ?? 0);
            $item['update_time_text'] = $this->formatTime($item['update_time'] ?? 0);
        }
        unset($item);

        return $list;
    }

    /**
     * @notes 获取数量
     */
    public function count(): int
    {
        $advisorScopeId = $this->getCrmAdvisorScopeId();
        if ($advisorScopeId < 0) {
            return 0;
        }

        $query = CustomerLossWarning::where($this->searchWhere);
        if ($advisorScopeId > 0) {
            $query->where('advisor_id', $advisorScopeId);
        }
        $this->applyKeyword($query);
        return $query->count();
    }

    /**
     * @notes 关键词搜索
     */
    private function applyKeyword($query): void
    {
        $keyword = trim((string)($this->params['keyword'] ?? ''));
        if ($keyword === '') {
            return;
        }

        $customerIds = Customer::whereLike('customer_name', '%' . $keyword . '%')
            ->whereOr('customer_mobile', 'like', '%' . $keyword . '%')
            ->column('id');
        $query->where(function ($subQuery) use ($keyword, $customerIds) {
            $subQuery->whereLike('warning_reason', '%' . $keyword . '%')
                ->whereOr('handle_remark', 'like', '%' . $keyword . '%');
            if (!empty($customerIds)) {
                $subQuery->whereOr('customer_id', 'in', $customerIds);
            }
        });
    }

    /**
     * @notes 客户映射
     */
    private function getCustomerMap(array $customerIds): array
    {
        $customerIds = array_values(array_unique(array_filter(array_map('intval', $customerIds))));
        if (empty($customerIds)) {
            return [];
        }

        $customers = Customer::whereIn('id', $customerIds)
            ->append(['customer_status_desc', 'intention_level_desc'])
            ->field('id,customer_name,customer_mobile,customer_wechat,intention_level,customer_status,advisor_id,last_follow_time,next_follow_time')
            ->select()
            ->toArray();

        $map = [];
        foreach ($customers as $customer) {
            $customer['last_follow_time_text'] = $this->formatTime($customer['last_follow_time'] ?? 0);
            $customer['next_follow_time_text'] = $this->formatTime($customer['next_follow_time'] ?? 0);
            $map[(int)$customer['id']] = $customer;
        }
        return $map;
    }

    /**
     * @notes 顾问映射
     */
    private function getAdvisorMap(array $advisorIds): array
    {
        $advisorIds = array_values(array_unique(array_filter(array_map('intval', $advisorIds))));
        if (empty($advisorIds)) {
            return [];
        }

        $advisors = SalesAdvisor::whereIn('id', $advisorIds)
            ->field('id,advisor_name,mobile,wecom_userid')
            ->select()
            ->toArray();

        $map = [];
        foreach ($advisors as $advisor) {
            $map[(int)$advisor['id']] = $advisor;
        }
        return $map;
    }

    /**
     * @notes 格式化时间
     */
    private function formatTime($value): string
    {
        $timestamp = Customer::parseTimestampValue($value);
        return $timestamp > 0 ? date('Y-m-d H:i:s', $timestamp) : '';
    }
}
