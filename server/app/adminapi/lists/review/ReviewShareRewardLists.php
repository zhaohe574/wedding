<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 晒单奖励列表
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\adminapi\lists\review;

use app\adminapi\lists\BaseAdminDataLists;
use app\common\lists\ListsSearchInterface;
use app\common\model\review\ReviewShareReward;

class ReviewShareRewardLists extends BaseAdminDataLists implements ListsSearchInterface
{
    public function setSearch(): array
    {
        return [
            '=' => ['status', 'share_platform'],
        ];
    }

    public function queryWhere(): array
    {
        $where = [];

        if (!empty($this->params['review_id'])) {
            $where[] = ['review_id', '=', (int)$this->params['review_id']];
        }

        if (!empty($this->params['nickname'])) {
            $userIds = \app\common\model\user\User::where('nickname', 'like', '%' . $this->params['nickname'] . '%')
                ->column('id');
            $where[] = ['user_id', 'in', $userIds ?: [0]];
        }

        if (!empty($this->params['start_date'])) {
            $where[] = ['create_time', '>=', strtotime($this->params['start_date'])];
        }

        if (!empty($this->params['end_date'])) {
            $where[] = ['create_time', '<=', strtotime($this->params['end_date'] . ' 23:59:59')];
        }

        return $where;
    }

    public function lists(): array
    {
        $lists = ReviewShareReward::with(['user', 'review'])
            ->where($this->searchWhere)
            ->where($this->queryWhere())
            ->order('id', 'desc')
            ->limit($this->limitOffset, $this->limitLength)
            ->select()
            ->toArray();

        foreach ($lists as &$item) {
            $item['status_text'] = ReviewShareReward::getStatusDesc((int)$item['status']);
            $item['platform_text'] = ReviewShareReward::getPlatformDesc((string)$item['share_platform']);
            $item['create_time_text'] = !empty($item['create_time']) ? date('Y-m-d H:i:s', (int)$item['create_time']) : '';
            $item['audit_time_text'] = !empty($item['audit_time']) ? date('Y-m-d H:i:s', (int)$item['audit_time']) : '';
        }

        return $lists;
    }

    public function count(): int
    {
        return ReviewShareReward::where($this->searchWhere)
            ->where($this->queryWhere())
            ->count();
    }
}
