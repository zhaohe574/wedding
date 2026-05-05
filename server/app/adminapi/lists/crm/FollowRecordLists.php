<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 跟进记录列表
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\adminapi\lists\crm;

use app\adminapi\lists\BaseAdminDataLists;
use app\common\lists\ListsSearchInterface;
use app\common\model\crm\Customer;
use app\common\model\crm\FollowRecord;
use app\common\model\crm\SalesAdvisor;
use app\common\model\auth\Admin;

/**
 * 跟进记录列表
 */
class FollowRecordLists extends BaseAdminDataLists implements ListsSearchInterface
{
    /**
     * @notes 设置搜索条件
     */
    public function setSearch(): array
    {
        return [
            '=' => ['customer_id', 'advisor_id', 'follow_type', 'follow_result', 'is_important'],
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

        $query = FollowRecord::where($this->searchWhere)
            ->append(['follow_type_desc', 'follow_result_desc']);
        if ($advisorScopeId > 0) {
            $query->where('advisor_id', $advisorScopeId);
        }
        $this->applyKeyword($query);
        $this->applyTimeRange($query);

        $list = $query->order(['id' => 'desc'])
            ->limit($this->limitOffset, $this->limitLength)
            ->select()
            ->toArray();

        $customerMap = $this->getCustomerMap(array_column($list, 'customer_id'));
        $advisorMap = $this->getAdvisorMap(array_column($list, 'advisor_id'));
        $adminMap = $this->getAdminMap(array_column($list, 'admin_id'));

        foreach ($list as &$item) {
            $customerId = (int)($item['customer_id'] ?? 0);
            $advisorId = (int)($item['advisor_id'] ?? 0);
            $adminId = (int)($item['admin_id'] ?? 0);
            $item['customer'] = $customerMap[$customerId] ?? null;
            $item['advisor'] = $advisorMap[$advisorId] ?? null;
            $item['admin'] = $adminMap[$adminId] ?? null;
            $item['next_follow_time_text'] = $this->formatTime($item['next_follow_time'] ?? 0);
            $item['create_time_text'] = $this->formatTime($item['create_time'] ?? 0);
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

        $query = FollowRecord::where($this->searchWhere);
        if ($advisorScopeId > 0) {
            $query->where('advisor_id', $advisorScopeId);
        }
        $this->applyKeyword($query);
        $this->applyTimeRange($query);
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
            $subQuery->whereLike('follow_content', '%' . $keyword . '%')
                ->whereOr('next_follow_content', 'like', '%' . $keyword . '%');
            if (!empty($customerIds)) {
                $subQuery->whereOr('customer_id', 'in', $customerIds);
            }
        });
    }

    /**
     * @notes 跟进时间筛选
     */
    private function applyTimeRange($query): void
    {
        $start = Customer::parseTimestampValue($this->params['start_time'] ?? 0);
        $end = Customer::parseTimestampValue($this->params['end_time'] ?? 0);
        if ($start > 0) {
            $query->where('create_time', '>=', $start);
        }
        if ($end > 0) {
            $query->where('create_time', '<=', $end);
        }
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
            ->field('id,customer_name,customer_mobile,customer_wechat,intention_level,customer_status,advisor_id')
            ->select()
            ->toArray();

        $map = [];
        foreach ($customers as $customer) {
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
     * @notes 管理员映射
     */
    private function getAdminMap(array $adminIds): array
    {
        $adminIds = array_values(array_unique(array_filter(array_map('intval', $adminIds))));
        if (empty($adminIds)) {
            return [];
        }

        $admins = Admin::whereIn('id', $adminIds)
            ->field('id,name,account')
            ->select()
            ->toArray();

        $map = [];
        foreach ($admins as $admin) {
            $map[(int)$admin['id']] = $admin;
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
