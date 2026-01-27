<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 小程序端工作人员列表
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\api\lists\staff;

use app\api\lists\BaseApiDataLists;
use app\common\lists\ListsSearchInterface;
use app\common\model\staff\Staff;
use app\common\model\staff\Favorite;

/**
 * 工作人员列表（小程序端）
 * Class StaffLists
 * @package app\api\lists\staff
 */
class StaffLists extends BaseApiDataLists implements ListsSearchInterface
{
    /**
     * @notes 搜索条件
     * @return array
     */
    public function setSearch(): array
    {
        return [
            '=' => ['category_id'],
        ];
    }

    /**
     * @notes 自定义查询条件
     * @return array
     */
    public function queryWhere(): array
    {
        $where = [];
        $where[] = ['status', '=', Staff::STATUS_ENABLE];
        $where[] = ['audit_status', '=', Staff::AUDIT_PASS];
        $where[] = ['delete_time', '=', null];

        // 关键词搜索（名称）
        if (!empty($this->params['keyword'])) {
            $where[] = ['name', 'like', '%' . $this->params['keyword'] . '%'];
        }

        // 价格区间筛选
        if (!empty($this->params['price_min'])) {
            $where[] = ['price', '>=', floatval($this->params['price_min'])];
        }
        if (!empty($this->params['price_max'])) {
            $where[] = ['price', '<=', floatval($this->params['price_max'])];
        }

        // 从业年限筛选
        if (!empty($this->params['experience_min'])) {
            $where[] = ['experience_years', '>=', intval($this->params['experience_min'])];
        }

        return $where;
    }

    /**
     * @notes 获取工作人员列表
     * @return array
     */
    public function lists(): array
    {
        // 排序方式
        $orderRaw = 'sort desc, id desc';
        $sortType = $this->params['sort'] ?? 'default';
        
        switch ($sortType) {
            case 'price_asc':
                $orderRaw = 'price asc, id desc';
                break;
            case 'price_desc':
                $orderRaw = 'price desc, id desc';
                break;
            case 'rating':
                $orderRaw = 'rating desc, id desc';
                break;
            case 'order_count':
                $orderRaw = 'order_count desc, id desc';
                break;
            case 'new':
                $orderRaw = 'id desc';
                break;
        }

        $field = 'id, sn, name, avatar, category_id, price, rating, order_count, experience_years, profile, is_recommend';
        
        $result = Staff::field($field)
            ->where($this->queryWhere())
            ->where($this->searchWhere)
            ->orderRaw($orderRaw)
            ->append(['category_name'])
            ->limit($this->limitOffset, $this->limitLength)
            ->select()
            ->toArray();

        // 标签筛选（如果有tag_ids参数）
        if (!empty($this->params['tag_ids'])) {
            $tagIds = is_array($this->params['tag_ids']) 
                ? $this->params['tag_ids'] 
                : explode(',', $this->params['tag_ids']);
            
            $staffIds = \app\common\model\staff\StaffTag::whereIn('tag_id', $tagIds)
                ->group('staff_id')
                ->column('staff_id');
            
            $result = array_filter($result, function($item) use ($staffIds) {
                return in_array($item['id'], $staffIds);
            });
            $result = array_values($result);
        }

        // 获取用户收藏状态
        if ($this->userId > 0) {
            $staffIds = array_column($result, 'id');
            $favoriteIds = Favorite::where('user_id', $this->userId)
                ->whereIn('staff_id', $staffIds)
                ->column('staff_id');

            foreach ($result as &$item) {
                $item['is_favorite'] = in_array($item['id'], $favoriteIds);
            }
        } else {
            foreach ($result as &$item) {
                $item['is_favorite'] = false;
            }
        }

        // 获取工作人员的标签信息
        if (!empty($result)) {
            $staffIds = array_column($result, 'id');
            
            // 查询标签关联
            $staffTags = \app\common\model\staff\StaffTag::alias('st')
                ->leftJoin('style_tag tag', 'st.tag_id = tag.id')
                ->whereIn('st.staff_id', $staffIds)
                ->where('tag.delete_time', null)
                ->where('tag.is_show', 1)
                ->field('st.staff_id, tag.name')
                ->select()
                ->toArray();
            
            // 按工作人员ID分组标签
            $tagsByStaff = [];
            foreach ($staffTags as $staffTag) {
                $tagsByStaff[$staffTag['staff_id']][] = $staffTag['name'];
            }
            
            // 将标签添加到结果中
            foreach ($result as &$item) {
                $item['tags'] = $tagsByStaff[$item['id']] ?? [];
            }
        }

        return $result;
    }

    /**
     * @notes 获取工作人员数量
     * @return int
     */
    public function count(): int
    {
        return Staff::where($this->searchWhere)
            ->where($this->queryWhere())
            ->count();
    }
}
