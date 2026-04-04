<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 动态评论列表
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\adminapi\lists\dynamic;

use app\adminapi\lists\BaseAdminDataLists;
use app\common\model\dynamic\DynamicComment;

/**
 * 动态评论列表
 * Class DynamicCommentLists
 * @package app\adminapi\lists\dynamic
 */
class DynamicCommentLists extends BaseAdminDataLists
{
    /**
     * @notes 搜索条件
     * @return array
     */
    public function setSearch(): array
    {
        return [
            '=' => ['dynamic_id', 'user_id', 'status', 'is_top'],
            '%like%' => ['content'],
            'between_time' => ['create_time'],
        ];
    }

    /**
     * @notes 列表
     * @return array
     */
    public function lists(): array
    {
        $lists = DynamicComment::where($this->searchWhere)
            ->order($this->sortOrder ?: ['is_top' => 'desc', 'id' => 'desc'])
            ->limit($this->limitOffset, $this->limitLength)
            ->select()
            ->toArray();

        foreach ($lists as &$item) {
            $item['status_desc'] = $this->getStatusDesc($item['status']);
            
            // 获取用户信息
            $user = \app\common\model\user\User::field('id, nickname, avatar')->find($item['user_id']);
            $item['user'] = $user ? $user->toArray() : null;
            
            // 图片处理
            $item['images'] = $item['images'] ? json_decode($item['images'], true) : [];
        }

        return $lists;
    }

    /**
     * @notes 总数
     * @return int
     */
    public function count(): int
    {
        return DynamicComment::where($this->searchWhere)->count();
    }

    /**
     * @notes 获取状态描述
     * @param int $status
     * @return string
     */
    protected function getStatusDesc(int $status): string
    {
        $map = [
            DynamicComment::STATUS_PENDING => '待审核',
            DynamicComment::STATUS_NORMAL => '正常',
            DynamicComment::STATUS_DELETED => '已删除',
            DynamicComment::STATUS_REJECTED => '已拒绝',
        ];
        return $map[$status] ?? '未知';
    }
}
