<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 评价列表
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\adminapi\lists\review;

use app\adminapi\lists\BaseAdminDataLists;
use app\common\model\review\Review;
use app\common\lists\ListsSearchInterface;
use app\common\lists\ListsExcelInterface;

/**
 * 评价列表
 * Class ReviewLists
 * @package app\adminapi\lists\review
 */
class ReviewLists extends BaseAdminDataLists implements ListsSearchInterface, ListsExcelInterface
{
    /**
     * @notes 搜索条件
     * @return array
     */
    public function setSearch(): array
    {
        return [
            '=' => ['status', 'staff_id', 'user_id', 'score', 'review_type', 'is_top', 'is_show'],
            '%like%' => ['content'],
        ];
    }

    /**
     * @notes 自定义搜索条件
     * @return array
     */
    public function queryWhere(): array
    {
        $where = [];

        // 订单号搜索
        if (!empty($this->params['order_sn'])) {
            $orderIds = \app\common\model\order\Order::where('order_sn', 'like', '%' . $this->params['order_sn'] . '%')
                ->column('id');
            $where[] = ['order_id', 'in', $orderIds];
        }

        // 用户昵称搜索
        if (!empty($this->params['nickname'])) {
            $userIds = \app\common\model\user\User::where('nickname', 'like', '%' . $this->params['nickname'] . '%')
                ->column('id');
            $where[] = ['user_id', 'in', $userIds];
        }

        // 人员姓名搜索
        if (!empty($this->params['staff_name'])) {
            $staffIds = \app\common\model\staff\Staff::where('name', 'like', '%' . $this->params['staff_name'] . '%')
                ->column('id');
            $where[] = ['staff_id', 'in', $staffIds];
        }

        // 评分范围搜索
        if (!empty($this->params['score_min'])) {
            $where[] = ['score', '>=', $this->params['score_min']];
        }
        if (!empty($this->params['score_max'])) {
            $where[] = ['score', '<=', $this->params['score_max']];
        }

        // 日期范围搜索
        if (!empty($this->params['start_date'])) {
            $where[] = ['create_time', '>=', strtotime($this->params['start_date'])];
        }
        if (!empty($this->params['end_date'])) {
            $where[] = ['create_time', '<=', strtotime($this->params['end_date'] . ' 23:59:59')];
        }

        // 评价类型筛选
        if (!empty($this->params['score_level'])) {
            switch ($this->params['score_level']) {
                case 'good':
                    $where[] = ['score', '>=', 4];
                    break;
                case 'medium':
                    $where[] = ['score', '=', 3];
                    break;
                case 'bad':
                    $where[] = ['score', '<=', 2];
                    break;
            }
        }

        // 有图评价
        if (!empty($this->params['has_image'])) {
            $where[] = ['images', '<>', ''];
            $where[] = ['images', '<>', '[]'];
        }

        // 有视频评价
        if (!empty($this->params['has_video'])) {
            $where[] = ['video', '<>', ''];
        }

        return $where;
    }

    /**
     * @notes 列表
     * @return array
     */
    public function lists(): array
    {
        $lists = Review::with(['user', 'staff', 'order'])
            ->where($this->searchWhere)
            ->where($this->queryWhere())
            ->order('is_top desc, create_time desc')
            ->limit($this->limitOffset, $this->limitLength)
            ->select()
            ->toArray();

        foreach ($lists as &$item) {
            // PHP 8 类型转换
            $item['create_time'] = (int)$item['create_time'];
            $item['status'] = (int)$item['status'];
            $item['score'] = (int)$item['score'];
            $item['review_type'] = (int)$item['review_type'];
            
            $item['status_text'] = Review::getStatusDesc($item['status']);
            $item['review_type_text'] = Review::getTypeDesc($item['review_type']);
            $item['score_level'] = Review::getScoreLevel($item['score']);
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
        return Review::where($this->searchWhere)
            ->where($this->queryWhere())
            ->count();
    }

    /**
     * @notes 导出字段
     * @return array
     */
    public function setExcelFields(): array
    {
        return [
            'id' => '评价ID',
            'order.order_sn' => '订单号',
            'user.nickname' => '用户昵称',
            'staff.name' => '服务人员',
            'score' => '综合评分',
            'score_service' => '服务态度',
            'score_professional' => '专业水平',
            'score_punctual' => '时间守约',
            'score_effect' => '整体效果',
            'content' => '评价内容',
            'status_text' => '状态',
            'review_type_text' => '评价类型',
            'create_time_text' => '评价时间',
        ];
    }

    /**
     * @notes 导出文件名
     * @return string
     */
    public function setFileName(): string
    {
        return '评价列表_' . date('YmdHis');
    }
}
