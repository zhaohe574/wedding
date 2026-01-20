<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 动态列表
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\adminapi\lists\dynamic;

use app\adminapi\lists\BaseAdminDataLists;
use app\common\lists\ListsExcelInterface;
use app\common\model\dynamic\Dynamic;

/**
 * 动态列表
 * Class DynamicLists
 * @package app\adminapi\lists\dynamic
 */
class DynamicLists extends BaseAdminDataLists implements ListsExcelInterface
{
    /**
     * @notes 搜索条件
     * @return array
     */
    public function setSearch(): array
    {
        return [
            '=' => ['status', 'dynamic_type', 'user_type', 'user_id', 'is_top', 'is_hot'],
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
        $lists = Dynamic::where($this->searchWhere)
            ->order($this->sortOrder ?: ['is_top' => 'desc', 'id' => 'desc'])
            ->limit($this->limitOffset, $this->limitLength)
            ->select()
            ->toArray();

        foreach ($lists as &$item) {
            $item['type_desc'] = $this->getTypeDesc($item['dynamic_type']);
            $item['status_desc'] = $this->getStatusDesc($item['status']);
            $item['user_type_desc'] = $this->getUserTypeDesc($item['user_type']);
            
            // 获取发布者信息
            $item['publisher'] = $this->getPublisher($item['user_type'], $item['user_id']);
            
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
        return Dynamic::where($this->searchWhere)->count();
    }

    /**
     * @notes 获取发布者信息
     * @param int $userType
     * @param int $userId
     * @return array|null
     */
    protected function getPublisher(int $userType, int $userId): ?array
    {
        if ($userType == Dynamic::USER_TYPE_USER) {
            $user = \app\common\model\user\User::field('id, nickname, avatar')->find($userId);
            return $user ? $user->toArray() : null;
        } elseif ($userType == Dynamic::USER_TYPE_STAFF) {
            $staff = \app\common\model\staff\Staff::field('id, name as nickname, avatar')->find($userId);
            return $staff ? $staff->toArray() : null;
        } else {
            return ['id' => 0, 'nickname' => '官方', 'avatar' => ''];
        }
    }

    /**
     * @notes 导出字段
     * @return array
     */
    public function setExcelFields(): array
    {
        return [
            'id' => 'ID',
            'user_type_desc' => '发布者类型',
            'type_desc' => '动态类型',
            'content' => '内容',
            'view_count' => '浏览量',
            'like_count' => '点赞数',
            'comment_count' => '评论数',
            'collect_count' => '收藏数',
            'status_desc' => '状态',
            'create_time' => '发布时间',
        ];
    }

    /**
     * @notes 导出文件名
     * @return string
     */
    public function setFileName(): string
    {
        return '动态列表';
    }

    /**
     * @notes 获取类型描述
     * @param int $type
     * @return string
     */
    protected function getTypeDesc(int $type): string
    {
        $map = [
            Dynamic::TYPE_IMAGE_TEXT => '图文',
            Dynamic::TYPE_VIDEO => '视频',
            Dynamic::TYPE_CASE => '案例',
            Dynamic::TYPE_ACTIVITY => '活动',
        ];
        return $map[$type] ?? '未知';
    }

    /**
     * @notes 获取状态描述
     * @param int $status
     * @return string
     */
    protected function getStatusDesc(int $status): string
    {
        $map = [
            Dynamic::STATUS_PENDING => '待审核',
            Dynamic::STATUS_PUBLISHED => '已发布',
            Dynamic::STATUS_OFFLINE => '已下架',
            Dynamic::STATUS_REJECTED => '已拒绝',
        ];
        return $map[$status] ?? '未知';
    }

    /**
     * @notes 获取用户类型描述
     * @param int $userType
     * @return string
     */
    protected function getUserTypeDesc(int $userType): string
    {
        $map = [
            Dynamic::USER_TYPE_USER => '用户',
            Dynamic::USER_TYPE_STAFF => '工作人员',
            Dynamic::USER_TYPE_OFFICIAL => '官方',
        ];
        return $map[$userType] ?? '未知';
    }
}
