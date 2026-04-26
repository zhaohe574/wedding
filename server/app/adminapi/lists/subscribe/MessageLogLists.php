<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 订阅消息发送日志列表
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\adminapi\lists\subscribe;

use app\adminapi\lists\BaseAdminDataLists;
use app\common\lists\ListsSearchInterface;
use app\common\model\subscribe\SubscribeMessageLog;
use app\common\model\subscribe\SubscribeMessageTemplate;
use app\common\model\user\User;

/**
 * 订阅消息发送日志列表
 * Class MessageLogLists
 * @package app\adminapi\lists\subscribe
 */
class MessageLogLists extends BaseAdminDataLists implements ListsSearchInterface
{
    /**
     * @notes 搜索条件
     * @return array
     */
    public function setSearch(): array
    {
        return [
            '=' => ['user_id', 'template_id', 'scene', 'send_status', 'business_type'],
        ];
    }

    /**
     * @notes 获取列表
     * @return array
     */
    public function lists(): array
    {
        $where = $this->searchWhere;

        // 日期范围筛选
        if (!empty($this->params['start_date'])) {
            $where[] = ['create_time', '>=', strtotime($this->params['start_date'])];
        }
        if (!empty($this->params['end_date'])) {
            $where[] = ['create_time', '<', strtotime($this->params['end_date']) + 86400];
        }

        $list = SubscribeMessageLog::where($where)
            ->field('id, user_id, openid, template_id, scene, business_type, business_id, send_status, error_code, error_msg, planned_send_time, next_retry_time, retry_count, send_time, create_time')
            ->order('id', 'desc')
            ->limit($this->limitOffset, $this->limitLength)
            ->select()
            ->toArray();

        // 批量获取用户信息
        $userIds = array_filter(array_unique(array_column($list, 'user_id')));
        $users = [];
        if (!empty($userIds)) {
            $users = User::whereIn('id', $userIds)->column('nickname,avatar', 'id');
        }

        foreach ($list as &$item) {
            // PHP 8 类型转换
            $item['create_time'] = (int)$item['create_time'];
            $item['planned_send_time'] = (int)($item['planned_send_time'] ?? 0);
            $item['next_retry_time'] = (int)($item['next_retry_time'] ?? 0);
            $item['retry_count'] = (int)($item['retry_count'] ?? 0);
            $item['send_time'] = (int)$item['send_time'];
            $item['send_status'] = (int)$item['send_status'];

            $item['user_info'] = $users[$item['user_id']] ?? ['nickname' => '未知用户', 'avatar' => ''];
            $item['scene_desc'] = SubscribeMessageTemplate::getSceneDesc($item['scene']);
            $item['send_status_desc'] = $this->getSendStatusDesc($item['send_status']);
            $item['create_time'] = $item['create_time'] ? date('Y-m-d H:i:s', $item['create_time']) : '';
            $item['planned_send_time'] = $item['planned_send_time'] ? date('Y-m-d H:i:s', $item['planned_send_time']) : '';
            $item['next_retry_time'] = $item['next_retry_time'] ? date('Y-m-d H:i:s', $item['next_retry_time']) : '';
            $item['send_time'] = $item['send_time'] ? date('Y-m-d H:i:s', $item['send_time']) : '';
        }

        return $list;
    }

    /**
     * @notes 获取数量
     * @return int
     */
    public function count(): int
    {
        $where = $this->searchWhere;

        if (!empty($this->params['start_date'])) {
            $where[] = ['create_time', '>=', strtotime($this->params['start_date'])];
        }
        if (!empty($this->params['end_date'])) {
            $where[] = ['create_time', '<', strtotime($this->params['end_date']) + 86400];
        }

        return SubscribeMessageLog::where($where)->count();
    }

    /**
     * @notes 获取发送状态描述
     * @param int $status
     * @return string
     */
    protected function getSendStatusDesc(int $status): string
    {
        $map = [
            0 => '待发送',
            1 => '发送成功',
            2 => '发送失败',
            3 => '发送中',
        ];
        return $map[$status] ?? '未知';
    }
}
