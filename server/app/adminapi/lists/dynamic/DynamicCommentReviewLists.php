<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 评论审核列表
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\adminapi\lists\dynamic;

use app\adminapi\lists\BaseAdminDataLists;
use app\common\model\dynamic\DynamicComment;

/**
 * 评论审核列表
 * Class DynamicCommentReviewLists
 * @package app\adminapi\lists\dynamic
 */
class DynamicCommentReviewLists extends BaseAdminDataLists
{
    /**
     * @notes 搜索条件
     * @return array
     */
    public function setSearch(): array
    {
        $allowSearch = [
            '=' => ['dynamic_id', 'user_id'],
            '%like%' => ['content'],
            'between_time' => ['create_time'],
        ];
        
        // 单独处理 review_status，因为 0 是有效值
        if (isset($this->params['review_status']) && $this->params['review_status'] !== '') {
            $allowSearch['='][] = 'review_status';
        }
        
        return $allowSearch;
    }

    /**
     * @notes 列表
     * @return array
     */
    public function lists(): array
    {
        $query = DynamicComment::with(['user' => function($q) {
                $q->field('id, nickname, avatar');
            }, 'dynamic' => function($q) {
                $q->field('id, title, content');
            }])
            ->where($this->searchWhere);
        
        // 单独处理 review_status，因为 0 是有效值
        if (isset($this->params['review_status']) && $this->params['review_status'] !== '') {
            $query->where('review_status', $this->params['review_status']);
        }
        
        $lists = $query->order($this->sortOrder ?: ['create_time' => 'desc'])
            ->limit($this->limitOffset, $this->limitLength)
            ->select()
            ->toArray();

        foreach ($lists as &$item) {
            $item['review_status_desc'] = $this->getReviewStatusDesc($item['review_status']);
        }

        return $lists;
    }

    /**
     * @notes 总数
     * @return int
     */
    public function count(): int
    {
        $query = DynamicComment::where($this->searchWhere);
        
        // 单独处理 review_status，因为 0 是有效值
        if (isset($this->params['review_status']) && $this->params['review_status'] !== '') {
            $query->where('review_status', $this->params['review_status']);
        }
        
        return $query->count();
    }

    /**
     * @notes 获取审核状态描述
     * @param int $status
     * @return string
     */
    protected function getReviewStatusDesc(int $status): string
    {
        $map = [
            DynamicComment::REVIEW_STATUS_PENDING => '待审核',
            DynamicComment::REVIEW_STATUS_APPROVED => '已通过',
            DynamicComment::REVIEW_STATUS_REJECTED => '已拒绝',
        ];
        return $map[$status] ?? '未知';
    }
}
