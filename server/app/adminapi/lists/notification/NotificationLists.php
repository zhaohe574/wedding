<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 消息通知列表
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\adminapi\lists\notification;

use app\adminapi\lists\BaseAdminDataLists;
use app\common\model\notification\Notification;
use app\common\lists\ListsSearchInterface;
use app\common\lists\ListsExcelInterface;

/**
 * 消息通知列表
 * Class NotificationLists
 * @package app\adminapi\lists\notification
 */
class NotificationLists extends BaseAdminDataLists implements ListsSearchInterface, ListsExcelInterface
{
    /**
     * @notes 搜索条件
     * @return array
     */
    public function setSearch(): array
    {
        return [
            '=' => ['notify_type', 'user_id', 'is_read'],
            '%like%' => ['title', 'content'],
        ];
    }

    /**
     * @notes 自定义搜索条件
     * @return array
     */
    public function queryWhere(): array
    {
        $where = [];

        // 用户昵称/手机号搜索
        if (!empty($this->params['keyword'])) {
            $userIds = \app\common\model\user\User::where(function ($query) {
                $query->where('nickname', 'like', '%' . $this->params['keyword'] . '%')
                    ->whereOr('mobile', 'like', '%' . $this->params['keyword'] . '%');
            })->column('id');
            $where[] = ['user_id', 'in', $userIds ?: [0]];
        }

        // 日期范围搜索
        if (!empty($this->params['start_date'])) {
            $where[] = ['create_time', '>=', strtotime($this->params['start_date'])];
        }
        if (!empty($this->params['end_date'])) {
            $where[] = ['create_time', '<=', strtotime($this->params['end_date'] . ' 23:59:59')];
        }

        // 目标类型搜索
        if (!empty($this->params['target_type'])) {
            $where[] = ['target_type', '=', $this->params['target_type']];
        }

        return $where;
    }

    /**
     * @notes 列表
     * @return array
     */
    public function lists(): array
    {
        $lists = Notification::where($this->searchWhere)
            ->where($this->queryWhere())
            ->order('create_time desc')
            ->limit($this->limitOffset, $this->limitLength)
            ->select()
            ->toArray();

        // 获取用户信息
        $userIds = array_unique(array_column($lists, 'user_id'));
        $users = [];
        if (!empty($userIds)) {
            $userList = \app\common\model\user\User::whereIn('id', $userIds)
                ->field('id,nickname,avatar,mobile')
                ->select()
                ->toArray();
            $users = array_column($userList, null, 'id');
        }

        foreach ($lists as &$item) {
            $item['notify_type_text'] = $this->getTypeText($item['notify_type']);
            $item['is_read_text'] = $item['is_read'] ? '已读' : '未读';
            $item['create_time_text'] = date('Y-m-d H:i:s', $item['create_time']);
            $item['read_time_text'] = $item['read_time'] ? date('Y-m-d H:i:s', $item['read_time']) : '-';
            $item['user'] = $users[$item['user_id']] ?? null;
        }

        return $lists;
    }

    /**
     * @notes 总数
     * @return int
     */
    public function count(): int
    {
        return Notification::where($this->searchWhere)
            ->where($this->queryWhere())
            ->count();
    }

    /**
     * @notes 获取类型文本
     * @param int $type
     * @return string
     */
    private function getTypeText(int $type): string
    {
        $map = [
            Notification::TYPE_SYSTEM => '系统通知',
            Notification::TYPE_ORDER => '订单通知',
            Notification::TYPE_INTERACT => '互动通知',
            Notification::TYPE_ACTIVITY => '活动通知',
        ];
        return $map[$type] ?? '未知';
    }

    /**
     * @notes 导出字段
     * @return array
     */
    public function setExcelFields(): array
    {
        return [
            'id' => '通知ID',
            'user.nickname' => '接收用户',
            'user.mobile' => '用户手机',
            'notify_type_text' => '通知类型',
            'title' => '标题',
            'content' => '内容',
            'is_read_text' => '阅读状态',
            'create_time_text' => '发送时间',
            'read_time_text' => '阅读时间',
        ];
    }

    /**
     * @notes 导出文件名
     * @return string
     */
    public function setFileName(): string
    {
        return '消息通知列表_' . date('YmdHis');
    }
}
