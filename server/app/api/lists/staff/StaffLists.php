<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 小程序端工作人员列表
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\api\lists\staff;

use app\api\lists\BaseApiDataLists;
use app\common\lists\ListsSearchInterface;
use app\common\model\schedule\Schedule;
use app\common\model\staff\Staff;
use app\common\model\staff\Favorite;
use app\common\model\staff\StaffTag;
use app\common\service\PackageRegionPriceService;
use app\common\service\StaffPriceService;

/**
 * 工作人员列表（小程序端）
 * Class StaffLists
 * @package app\api\lists\staff
 */
class StaffLists extends BaseApiDataLists implements ListsSearchInterface
{
    /**
     * @var int|null
     */
    protected ?int $manualCount = null;

    /**
     * @var array|null
     */
    protected ?array $resolvedRegionContext = null;

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
        $this->manualCount = null;
        $sortType = (string)($this->params['sort'] ?? 'default');
        $field = 'id, sn, name, avatar, category_id, rating, order_count, experience_years, profile, is_recommend';
        $regionContext = $this->getResolvedRegionContext();
        $selectedDate = $this->getSelectedDate();

        $tagStaffIds = $this->getTagStaffIds();
        if ($tagStaffIds !== null && empty($tagStaffIds)) {
            $this->manualCount = 0;
            return [];
        }

        $keywordStaffIds = $this->getKeywordStaffIds();
        if ($keywordStaffIds !== null && empty($keywordStaffIds)) {
            $this->manualCount = 0;
            return [];
        }

        $baseQuery = Staff::where($this->queryWhere())
            ->where($this->searchWhere);

        if ($tagStaffIds !== null) {
            $baseQuery->whereIn('id', $tagStaffIds);
        }
        if ($keywordStaffIds !== null) {
            $baseQuery->whereIn('id', $keywordStaffIds);
        }

        $result = [];
        if ($this->needPriceMemoryMode()) {
            $baseOrderRaw = in_array($sortType, ['price_asc', 'price_desc'], true)
                ? 'id desc'
                : $this->getOrderRaw($sortType);

            $allIds = (clone $baseQuery)
                ->orderRaw($baseOrderRaw)
                ->column('id');

            if (empty($allIds)) {
                $this->manualCount = 0;
                return [];
            }

            $allIds = array_map('intval', $allIds);
            $priceMap = StaffPriceService::getDisplayPriceMap($allIds, $regionContext);
            $filteredIds = $this->filterIdsByContext($allIds, $priceMap, $regionContext, $selectedDate);

            if (in_array($sortType, ['price_asc', 'price_desc'], true)) {
                usort($filteredIds, function (int $a, int $b) use ($priceMap, $sortType) {
                    $aPrice = $priceMap[$a] ?? ['price' => null, 'has_price' => false];
                    $bPrice = $priceMap[$b] ?? ['price' => null, 'has_price' => false];

                    $aHasPrice = (bool)($aPrice['has_price'] ?? false);
                    $bHasPrice = (bool)($bPrice['has_price'] ?? false);

                    if (!$aHasPrice && !$bHasPrice) {
                        return $b <=> $a;
                    }
                    if (!$aHasPrice) {
                        return 1;
                    }
                    if (!$bHasPrice) {
                        return -1;
                    }

                    $aValue = (float)($aPrice['price'] ?? 0);
                    $bValue = (float)($bPrice['price'] ?? 0);
                    if ($aValue === $bValue) {
                        return $b <=> $a;
                    }

                    return $sortType === 'price_desc'
                        ? ($bValue <=> $aValue)
                        : ($aValue <=> $bValue);
                });
            }

            $this->manualCount = count($filteredIds);
            $pageIds = array_slice($filteredIds, $this->limitOffset, $this->limitLength);
            if (empty($pageIds)) {
                return [];
            }

            $rows = Staff::field($field)
                ->whereIn('id', $pageIds)
                ->append(['category_name'])
                ->select()
                ->toArray();

            $rowMap = [];
            foreach ($rows as $row) {
                $rowMap[(int)$row['id']] = $row;
            }

            foreach ($pageIds as $staffId) {
                if (!isset($rowMap[$staffId])) {
                    continue;
                }
                $row = $rowMap[$staffId];
                $display = $priceMap[$staffId] ?? ['price' => null, 'has_price' => false, 'price_text' => '面议'];
                $row['price'] = $display['price'];
                $row['has_price'] = $display['has_price'];
                $row['price_text'] = $display['price_text'];
                $result[] = $row;
            }
        } else {
            $result = (clone $baseQuery)
                ->field($field)
                ->orderRaw($this->getOrderRaw($sortType))
                ->append(['category_name'])
                ->limit($this->limitOffset, $this->limitLength)
                ->select()
                ->toArray();

            StaffPriceService::injectDisplayPrice($result, 'id', $regionContext);
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
        if ($this->manualCount !== null) {
            return $this->manualCount;
        }

        $tagStaffIds = $this->getTagStaffIds();
        if ($tagStaffIds !== null && empty($tagStaffIds)) {
            return 0;
        }

        $keywordStaffIds = $this->getKeywordStaffIds();
        if ($keywordStaffIds !== null && empty($keywordStaffIds)) {
            return 0;
        }

        $query = Staff::where($this->searchWhere)
            ->where($this->queryWhere());

        if ($tagStaffIds !== null) {
            $query->whereIn('id', $tagStaffIds);
        }
        if ($keywordStaffIds !== null) {
            $query->whereIn('id', $keywordStaffIds);
        }

        if ($this->needPriceMemoryMode()) {
            $allIds = $query->column('id');
            $allIds = array_map('intval', $allIds);
            $priceMap = StaffPriceService::getDisplayPriceMap($allIds, $this->getResolvedRegionContext());
            return count($this->filterIdsByContext(
                $allIds,
                $priceMap,
                $this->getResolvedRegionContext(),
                $this->getSelectedDate()
            ));
        }

        return $query->count();
    }

    /**
     * @notes 获取排序规则
     * @param string $sortType
     * @return string
     */
    protected function getOrderRaw(string $sortType): string
    {
        switch ($sortType) {
            case 'rating':
                return 'rating desc, id desc';
            case 'order_count':
                return 'order_count desc, id desc';
            case 'new':
                return 'id desc';
            default:
                return 'is_recommend desc, sort desc, id desc';
        }
    }

    /**
     * @notes 是否需要进入价格内存过滤/排序模式
     * @return bool
     */
    protected function needPriceMemoryMode(): bool
    {
        $sortType = (string)($this->params['sort'] ?? 'default');
        if (in_array($sortType, ['price_asc', 'price_desc'], true)) {
            return true;
        }

        return ($this->params['price_min'] ?? '') !== ''
            || ($this->params['price_max'] ?? '') !== ''
            || $this->getSelectedDate() !== ''
            || PackageRegionPriceService::hasRegionContext($this->getResolvedRegionContext());
    }

    /**
     * @notes 标签筛选对应的 staff_id 列表
     * @return array|null
     */
    protected function getTagStaffIds(): ?array
    {
        if (empty($this->params['tag_ids'])) {
            return null;
        }

        $tagIds = is_array($this->params['tag_ids'])
            ? $this->params['tag_ids']
            : explode(',', (string)$this->params['tag_ids']);
        $tagIds = array_values(array_unique(array_filter(array_map('intval', $tagIds))));

        if (empty($tagIds)) {
            return [];
        }

        return StaffTag::whereIn('tag_id', $tagIds)
            ->group('staff_id')
            ->column('staff_id');
    }

    /**
     * @notes 关键词匹配对应的 staff_id 列表
     * @return array|null
     */
    protected function getKeywordStaffIds(): ?array
    {
        $keyword = trim((string)($this->params['keyword'] ?? ''));
        if ($keyword === '') {
            return null;
        }

        $keywordLike = '%' . $keyword . '%';

        $staffIds = Staff::where(function ($query) use ($keywordLike) {
            $query->whereLike('name', $keywordLike)
                ->whereOrLike('profile', $keywordLike);
        })->column('id');

        $tagStaffIds = StaffTag::alias('st')
            ->leftJoin('style_tag tag', 'st.tag_id = tag.id')
            ->where('tag.delete_time', null)
            ->where('tag.is_show', 1)
            ->whereLike('tag.name', $keywordLike)
            ->column('st.staff_id');

        $staffIds = array_merge($staffIds, $tagStaffIds);
        $staffIds = array_values(array_unique(array_filter(array_map('intval', $staffIds))));

        return $staffIds;
    }

    /**
     * @notes 按自动价口径筛选 staff_id
     * @param array $staffIds
     * @param array $priceMap
     * @return array
     */
    protected function filterIdsByContext(
        array $staffIds,
        array $priceMap,
        array $regionContext,
        string $selectedDate
    ): array
    {
        $priceMin = ($this->params['price_min'] ?? '') !== '' ? (float)$this->params['price_min'] : null;
        $priceMax = ($this->params['price_max'] ?? '') !== '' ? (float)$this->params['price_max'] : null;
        $requireRegionPrice = PackageRegionPriceService::hasRegionContext($regionContext);

        $filtered = [];
        foreach ($staffIds as $staffId) {
            $display = $priceMap[$staffId] ?? ['price' => null, 'has_price' => false];

            if ($requireRegionPrice && !($display['has_price'] ?? false)) {
                continue;
            }

            if ($selectedDate !== '' && !Schedule::isAvailable((int)$staffId, $selectedDate, 0)) {
                continue;
            }

            if ($priceMin === null && $priceMax === null) {
                $filtered[] = (int)$staffId;
                continue;
            }

            if (!($display['has_price'] ?? false)) {
                continue;
            }

            $price = (float)($display['price'] ?? 0);
            if ($priceMin !== null && $price < $priceMin) {
                continue;
            }
            if ($priceMax !== null && $price > $priceMax) {
                continue;
            }
            $filtered[] = (int)$staffId;
        }

        return $filtered;
    }

    /**
     * @notes 获取地区上下文
     * @return array
     */
    protected function getResolvedRegionContext(): array
    {
        if ($this->resolvedRegionContext !== null) {
            return $this->resolvedRegionContext;
        }

        $context = PackageRegionPriceService::normalizeRegionContext([
            'province_code' => (string)($this->params['province_code'] ?? ''),
            'province_name' => (string)($this->params['province_name'] ?? ''),
            'city_code' => (string)($this->params['city_code'] ?? ''),
            'city_name' => (string)($this->params['city_name'] ?? ''),
            'district_code' => (string)($this->params['district_code'] ?? ''),
            'district_name' => (string)($this->params['district_name'] ?? ''),
        ]);

        $hasRegionInput = $context['province_code'] !== ''
            || $context['city_code'] !== ''
            || $context['district_code'] !== '';

        if ($hasRegionInput) {
            try {
                $context = PackageRegionPriceService::validateEnabledRegion($context);
            } catch (\Throwable $e) {
                $context = [];
            }
        }

        $this->resolvedRegionContext = $context;
        return $this->resolvedRegionContext;
    }

    /**
     * @notes 获取预约日期
     * @return string
     */
    protected function getSelectedDate(): string
    {
        $date = trim((string)($this->params['date'] ?? ''));
        if ($date === '') {
            return '';
        }

        $timestamp = strtotime($date);
        if ($timestamp === false) {
            return '';
        }

        return date('Y-m-d', $timestamp);
    }
}
