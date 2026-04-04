<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\adminapi\lists\service;

use app\adminapi\lists\BaseAdminDataLists;
use app\common\lists\ListsSearchInterface;
use app\common\model\service\ServiceCityPool;

/**
 * 服务地区列表
 */
class RegionLists extends BaseAdminDataLists implements ListsSearchInterface
{
    /**
     * @notes 搜索条件
     * @return array
     */
    public function setSearch(): array
    {
        return [
            '=' => ['status'],
        ];
    }

    /**
     * @notes 列表
     * @return array
     */
    public function lists(): array
    {
        $query = ServiceCityPool::where($this->searchWhere);

        if (!empty($this->params['keyword'])) {
            $keyword = trim((string)$this->params['keyword']);
            $query->where(function ($subQuery) use ($keyword) {
                $subQuery->whereLike('province_name', '%' . $keyword . '%')
                    ->whereOr('city_name', 'like', '%' . $keyword . '%');
            });
        }

        return $query
            ->order($this->sortOrder ?: ['sort' => 'desc', 'id' => 'desc'])
            ->limit($this->limitOffset, $this->limitLength)
            ->select()
            ->toArray();
    }

    /**
     * @notes 总数
     * @return int
     */
    public function count(): int
    {
        $query = ServiceCityPool::where($this->searchWhere);

        if (!empty($this->params['keyword'])) {
            $keyword = trim((string)$this->params['keyword']);
            $query->where(function ($subQuery) use ($keyword) {
                $subQuery->whereLike('province_name', '%' . $keyword . '%')
                    ->whereOr('city_name', 'like', '%' . $keyword . '%');
            });
        }

        return $query->count();
    }
}
