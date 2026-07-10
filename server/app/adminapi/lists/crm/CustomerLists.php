<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 客户列表
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\adminapi\lists\crm;

use app\adminapi\lists\BaseAdminDataLists;
use app\common\lists\ListsSearchInterface;
use app\common\model\crm\Customer;
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

        $list = $query
            ->order(['id' => 'desc'])
            ->limit($this->limitOffset, $this->limitLength)
            ->select()
            ->toArray();

        $advisorMap = $this->getAdvisorMap(array_column($list, 'advisor_id'));
        $userMap = $this->getUserMap(array_column($list, 'user_id'));

        foreach ($list as &$item) {
            $advisorId = (int)($item['advisor_id'] ?? 0);
            $userId = (int)($item['user_id'] ?? 0);
            $item['advisor'] = $advisorMap[$advisorId] ?? null;
            $item['user'] = $userMap[$userId] ?? null;
            $item['can_transfer'] = in_array((int)($item['customer_status'] ?? 0), [Customer::STATUS_NEW, Customer::STATUS_FOLLOWING], true);
            $item['assign_time_text'] = $this->formatTime($item['assign_time'] ?? 0);
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
            ->field('id,advisor_name,mobile,wecom_userid,status,current_customer_count,max_customer_count')
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
