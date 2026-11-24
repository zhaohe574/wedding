<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 客户列表
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\adminapi\lists\crm;

use app\adminapi\lists\BaseAdminDataLists;
use app\common\lists\ListsSearchInterface;
use app\common\model\crm\Customer;
use app\common\model\crm\CustomerLossWarning;
use app\common\model\crm\SalesAdvisor;
use app\common\model\user\User;

/**
 * 客户列表
 * Class CustomerLists
 * @package app\adminapi\lists\crm
 */
class CustomerLists extends BaseAdminDataLists implements ListsSearchInterface
{
    /**
     * @notes 设置搜索条件
     * @return array
     */
    public function setSearch(): array
    {
        return [
            '=' => ['customer_status', 'intention_level', 'source_channel', 'advisor_id'],
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
        $advisorScopeId = $this->getCrmAdvisorScopeId();
        if ($advisorScopeId < 0) {
            return [];
        }

        $query = Customer::where($this->searchWhere)
            ->append([
                'customer_status_desc',
                'intention_level_desc',
                'source_channel_desc',
                'gender_desc',
                'days_to_wedding',
                'days_no_follow',
            ]);

        if ($advisorScopeId > 0) {
            $query->where('advisor_id', $advisorScopeId);
        }
        $this->applyKeyword($query);
        $this->applyFilters($query);

        $list = $query
            ->order($this->resolveOrderBy())
            ->limit($this->limitOffset, $this->limitLength)
            ->select()
            ->toArray();

        $advisorMap = $this->getAdvisorMap(array_column($list, 'advisor_id'));
        $userMap = $this->getUserMap(array_column($list, 'user_id'));
        $warningCountMap = $this->getPendingWarningCountMap(array_column($list, 'id'));
        $realOrderMap = Customer::getRealOrderSummaryMap(array_column($list, 'user_id'));

        $now = time();
        $todayStart = strtotime(date('Y-m-d'));
        $todayEnd = $todayStart + 86399;

        foreach ($list as &$item) {
            $customerId = (int)($item['id'] ?? 0);
            $advisorId = (int)($item['advisor_id'] ?? 0);
            $userId = (int)($item['user_id'] ?? 0);
            $status = (int)($item['customer_status'] ?? 0);
            $isActive = in_array($status, [Customer::STATUS_NEW, Customer::STATUS_FOLLOWING], true);
            $nextFollowTime = Customer::parseTimestampValue($item['next_follow_time'] ?? 0);

            $item['advisor'] = $advisorMap[$advisorId] ?? null;
            $item['user'] = $userMap[$userId] ?? null;
            $item['source_detail_text'] = Customer::formatSourceDetailText((string)($item['source_detail'] ?? ''));
            $item['can_transfer'] = $isActive;
            $item['pending_warning_count'] = $warningCountMap[$customerId] ?? 0;
            $item['real_order_count'] = $userId > 0 && isset($realOrderMap[$userId])
                ? (int)$realOrderMap[$userId]['order_count']
                : (int)($item['order_count'] ?? 0);
            $item['real_total_amount'] = $userId > 0 && isset($realOrderMap[$userId])
                ? (float)$realOrderMap[$userId]['total_amount']
                : (float)($item['total_amount'] ?? 0);
            $item['is_follow_overdue'] = $isActive && $nextFollowTime > 0 && $nextFollowTime < $now;
            $item['is_follow_today'] = $isActive && $nextFollowTime >= $todayStart && $nextFollowTime <= $todayEnd;
            $item['assign_time_text'] = $this->formatTime($item['assign_time'] ?? 0);
            $item['last_follow_time_text'] = $this->formatTime($item['last_follow_time'] ?? 0);
            $item['next_follow_time_text'] = $this->formatTime($item['next_follow_time'] ?? 0);
            $item['create_time_text'] = $this->formatTime($item['create_time'] ?? 0);
            $item['update_time_text'] = $this->formatTime($item['update_time'] ?? 0);
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
        $advisorScopeId = $this->getCrmAdvisorScopeId();
        if ($advisorScopeId < 0) {
            return 0;
        }

        $query = Customer::where($this->searchWhere);
        if ($advisorScopeId > 0) {
            $query->where('advisor_id', $advisorScopeId);
        }
        $this->applyKeyword($query);
        $this->applyFilters($query);
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
            $subQuery->whereLike('customer_name', '%' . $keyword . '%')
                ->whereOr('customer_mobile', 'like', '%' . $keyword . '%')
                ->whereOr('customer_wechat', 'like', '%' . $keyword . '%')
                ->whereOr('city', 'like', '%' . $keyword . '%')
                ->whereOr('wedding_venue', 'like', '%' . $keyword . '%')
                ->whereOr('source_detail', 'like', '%' . $keyword . '%');
        });
    }

    /**
     * @notes 跟进阶段、婚期范围与快捷筛选
     */
    private function applyFilters($query): void
    {
        $followStage = trim((string)($this->params['follow_stage'] ?? ''));
        $now = time();
        $todayStart = strtotime(date('Y-m-d'));
        $todayEnd = $todayStart + 86399;
        $threshold7 = $now - 7 * 86400;

        if ($followStage !== '') {
            $query->whereIn('customer_status', [Customer::STATUS_NEW, Customer::STATUS_FOLLOWING]);
            if ($followStage === 'today') {
                $query->whereBetween('next_follow_time', [$todayStart, $todayEnd]);
            } elseif ($followStage === 'overdue') {
                $query->where('next_follow_time', '>', 0)->where('next_follow_time', '<', $now);
            } elseif ($followStage === 'no_follow_7d') {
                $query->where(function ($sub) use ($threshold7) {
                    $sub->where('last_follow_time', '<', $threshold7)
                        ->whereOr(function ($q) use ($threshold7) {
                            $q->where('last_follow_time', 0)->where('create_time', '<', $threshold7);
                        });
                });
            } elseif ($followStage === 'never') {
                $query->where('follow_count', 0);
            } elseif ($followStage === 'unassigned') {
                $query->where('advisor_id', 0);
            }
        }

        $weddingStart = trim((string)($this->params['wedding_date_start'] ?? ''));
        $weddingEnd = trim((string)($this->params['wedding_date_end'] ?? ''));
        if ($weddingStart !== '') {
            $query->where('wedding_date', '>=', $weddingStart);
        }
        if ($weddingEnd !== '') {
            $query->where('wedding_date', '<=', $weddingEnd);
        }
    }

    /**
     * @notes 解析排序方式
     */
    private function resolveOrderBy(): array
    {
        $sortBy = trim((string)($this->params['sort_by'] ?? ''));
        return match ($sortBy) {
            'next_follow' => ['next_follow_time' => 'asc', 'id' => 'desc'],
            'wedding_date' => ['wedding_date' => 'asc', 'id' => 'desc'],
            'days_no_follow' => ['last_follow_time' => 'asc', 'create_time' => 'asc'],
            default => ['id' => 'desc'],
        };
    }

    /**
     * @notes 批量获取待处理流失预警数
     */
    private function getPendingWarningCountMap(array $customerIds): array
    {
        $customerIds = array_values(array_unique(array_filter(array_map('intval', $customerIds))));
        if (empty($customerIds)) {
            return [];
        }

        $rows = CustomerLossWarning::whereIn('customer_id', $customerIds)
            ->where('warning_status', CustomerLossWarning::STATUS_PENDING)
            ->field('customer_id, COUNT(*) as cnt')
            ->group('customer_id')
            ->select()
            ->toArray();

        $map = [];
        foreach ($rows as $row) {
            $map[(int)$row['customer_id']] = (int)$row['cnt'];
        }
        return $map;
    }

    /**
     * @notes 获取顾问信息映射
     * @param array $advisorIds
     * @return array
     */
    private function getAdvisorMap(array $advisorIds): array
    {
        $advisorIds = array_values(array_unique(array_filter(array_map('intval', $advisorIds))));
        if (empty($advisorIds)) {
            return [];
        }

        $advisors = SalesAdvisor::whereIn('id', $advisorIds)
            ->append(['status_desc'])
            ->field('id,advisor_name,mobile,status,current_customer_count,max_customer_count')
            ->select()
            ->toArray();

        $map = [];
        foreach ($advisors as $advisor) {
            $map[(int)$advisor['id']] = $advisor;
        }
        return $map;
    }

    /**
     * @notes 获取用户信息映射
     * @param array $userIds
     * @return array
     */
    private function getUserMap(array $userIds): array
    {
        $userIds = array_values(array_unique(array_filter(array_map('intval', $userIds))));
        if (empty($userIds)) {
            return [];
        }

        $users = User::whereIn('id', $userIds)
            ->field('id,nickname,avatar,mobile')
            ->select()
            ->toArray();

        $map = [];
        foreach ($users as $user) {
            $map[(int)$user['id']] = $user;
        }
        return $map;
    }

    /**
     * @notes 格式化时间
     * @param mixed $value
     * @return string
     */
    private function formatTime($value): string
    {
        $timestamp = Customer::parseTimestampValue($value);
        return $timestamp > 0 ? date('Y-m-d H:i:s', $timestamp) : '';
    }
}
