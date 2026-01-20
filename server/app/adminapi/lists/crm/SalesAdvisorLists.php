<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 销售顾问列表
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\adminapi\lists\crm;

use app\adminapi\lists\BaseAdminDataLists;
use app\common\model\crm\SalesAdvisor;
use app\common\lists\ListsSearchInterface;

/**
 * 销售顾问列表
 * Class SalesAdvisorLists
 * @package app\adminapi\lists\crm
 */
class SalesAdvisorLists extends BaseAdminDataLists implements ListsSearchInterface
{
    /**
     * @notes 搜索条件
     * @return array
     */
    public function setSearch(): array
    {
        return [
            '=' => ['status', 'admin_id'],
            '%like%' => ['advisor_name', 'mobile', 'wechat', 'email'],
        ];
    }

    /**
     * @notes 自定义查询条件
     * @return array
     */
    public function queryWhere(): array
    {
        $where = [];

        // 区域筛选
        if (!empty($this->params['area'])) {
            $where[] = ['areas', 'like', '%"' . $this->params['area'] . '"%'];
        }

        // 专长筛选
        if (!empty($this->params['specialty'])) {
            $where[] = ['specialties', 'like', '%"' . $this->params['specialty'] . '"%'];
        }

        // 可分配筛选（状态正常且客户数未满）
        if (!empty($this->params['available'])) {
            $where[] = ['status', '=', SalesAdvisor::STATUS_NORMAL];
            $where[] = ['current_customer_count', 'exp', \think\facade\Db::raw('< max_customer_count')];
        }

        return $where;
    }

    /**
     * @notes 列表数据
     * @return array
     */
    public function lists(): array
    {
        $lists = SalesAdvisor::with([
            'admin' => function ($query) {
                $query->field('id, name, avatar');
            }
        ])
            ->where($this->searchWhere)
            ->where($this->queryWhere())
            ->order($this->sortOrder ?: ['sort' => 'desc', 'create_time' => 'desc'])
            ->limit($this->limitOffset, $this->limitLength)
            ->select()
            ->toArray();

        foreach ($lists as &$item) {
            $item['status_desc'] = $this->getStatusDesc($item['status']);
            $item['customer_usage'] = $item['current_customer_count'] . '/' . $item['max_customer_count'];
            $item['can_assign'] = ($item['status'] == SalesAdvisor::STATUS_NORMAL && 
                $item['current_customer_count'] < $item['max_customer_count']);
            
            // 解析JSON字段
            $item['areas'] = !empty($item['areas']) ? 
                (is_array($item['areas']) ? $item['areas'] : json_decode($item['areas'], true)) : [];
            $item['specialties'] = !empty($item['specialties']) ? 
                (is_array($item['specialties']) ? $item['specialties'] : json_decode($item['specialties'], true)) : [];
        }

        return $lists;
    }

    /**
     * @notes 统计数量
     * @return int
     */
    public function count(): int
    {
        return SalesAdvisor::where($this->searchWhere)
            ->where($this->queryWhere())
            ->count();
    }

    /**
     * @notes 获取状态描述
     * @param int $status
     * @return string
     */
    private function getStatusDesc(int $status): string
    {
        $map = [
            0 => '离职',
            1 => '正常',
            2 => '休假',
        ];
        return $map[$status] ?? '未知';
    }
}
