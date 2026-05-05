<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 销售顾问列表
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\adminapi\lists\crm;

use app\adminapi\lists\BaseAdminDataLists;
use app\common\lists\ListsSearchInterface;
use app\common\model\crm\Customer;
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
            ->append(['status_desc']);

        $this->applyKeyword($query);

        $list = $query
            ->order(['sort' => 'desc', 'id' => 'desc'])
            ->limit($this->limitOffset, $this->limitLength)
            ->select()
            ->toArray();

        $adminMap = $this->getAdminMap(array_column($list, 'admin_id'));
        foreach ($list as &$item) {
            $currentCount = (int)($item['current_customer_count'] ?? 0);
            $maxCount = (int)($item['max_customer_count'] ?? 0);
            $item['admin'] = $adminMap[(int)($item['admin_id'] ?? 0)] ?? null;
            $item['load_text'] = $currentCount . '/' . $maxCount;
            $item['can_assign'] = (int)($item['status'] ?? 0) === SalesAdvisor::STATUS_NORMAL
                && $currentCount < $maxCount;
            $item['active_customer_count'] = Customer::where('advisor_id', (int)$item['id'])
                ->whereIn('customer_status', [Customer::STATUS_NEW, Customer::STATUS_FOLLOWING])
                ->count();
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
                ->whereOr('wechat', 'like', '%' . $keyword . '%')
                ->whereOr('wecom_userid', 'like', '%' . $keyword . '%');
        });
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
