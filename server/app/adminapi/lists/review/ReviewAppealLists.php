<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 评价申诉列表
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\adminapi\lists\review;

use app\adminapi\lists\BaseAdminDataLists;
use app\common\model\review\ReviewAppeal;
use app\common\lists\ListsSearchInterface;

/**
 * 评价申诉列表
 * Class ReviewAppealLists
 * @package app\adminapi\lists\review
 */
class ReviewAppealLists extends BaseAdminDataLists implements ListsSearchInterface
{
    /**
     * @notes 搜索条件
     * @return array
     */
    public function setSearch(): array
    {
        return [
            '=' => ['status', 'appeal_type'],
        ];
    }

    /**
     * @notes 列表
     * @return array
     */
    public function lists(): array
    {
        $lists = ReviewAppeal::with(['review', 'review.user', 'review.staff', 'appealUser', 'appealStaff'])
            ->where($this->searchWhere)
            ->order('create_time desc')
            ->limit($this->limitOffset, $this->limitLength)
            ->select()
            ->toArray();

        foreach ($lists as &$item) {
            // PHP 8 类型转换
            $item['create_time'] = (int)$item['create_time'];
            $item['appeal_type'] = (int)$item['appeal_type'];
            $item['status'] = (int)$item['status'];
            
            $item['appeal_type_text'] = ReviewAppeal::getTypeDesc($item['appeal_type']);
            $item['status_text'] = ReviewAppeal::getStatusDesc($item['status']);
            $item['create_time_text'] = date('Y-m-d H:i:s', $item['create_time']);
        }

        return $lists;
    }

    /**
     * @notes 总数
     * @return int
     */
    public function count(): int
    {
        return ReviewAppeal::where($this->searchWhere)->count();
    }
}
