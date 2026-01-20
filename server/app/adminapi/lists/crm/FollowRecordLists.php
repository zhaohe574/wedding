<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 跟进记录列表
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\adminapi\lists\crm;

use app\adminapi\lists\BaseAdminDataLists;
use app\common\model\crm\FollowRecord;
use app\common\lists\ListsSearchInterface;

/**
 * 跟进记录列表
 * Class FollowRecordLists
 * @package app\adminapi\lists\crm
 */
class FollowRecordLists extends BaseAdminDataLists implements ListsSearchInterface
{
    /**
     * @notes 搜索条件
     * @return array
     */
    public function setSearch(): array
    {
        return [
            '=' => ['customer_id', 'advisor_id', 'follow_type', 'follow_result', 'is_important'],
            '%like%' => ['follow_content'],
            'between_time' => ['create_time'],
        ];
    }

    /**
     * @notes 自定义查询条件
     * @return array
     */
    public function queryWhere(): array
    {
        $where = [];

        // 意向变化筛选
        if (!empty($this->params['intention_changed'])) {
            $where[] = ['intention_before', '<>', null];
            $where[] = ['intention_after', '<>', null];
            $where[] = ['intention_before', 'exp', \think\facade\Db::raw('<> intention_after')];
        }

        return $where;
    }

    /**
     * @notes 列表数据
     * @return array
     */
    public function lists(): array
    {
        $lists = FollowRecord::with([
            'customer' => function ($query) {
                $query->field('id, customer_name, customer_mobile, intention_level, customer_status');
            },
            'advisor' => function ($query) {
                $query->field('id, advisor_name, mobile, avatar');
            },
            'admin' => function ($query) {
                $query->field('id, name, avatar');
            }
        ])
            ->where($this->searchWhere)
            ->where($this->queryWhere())
            ->order($this->sortOrder ?: ['create_time' => 'desc', 'id' => 'desc'])
            ->limit($this->limitOffset, $this->limitLength)
            ->select()
            ->toArray();

        foreach ($lists as &$item) {
            $item['follow_type_desc'] = $this->getFollowTypeDesc($item['follow_type']);
            $item['follow_result_desc'] = $this->getResultDesc($item['follow_result']);
            $item['intention_before_desc'] = $this->getIntentionDesc($item['intention_before']);
            $item['intention_after_desc'] = $this->getIntentionDesc($item['intention_after']);
            
            // 解析附件
            $item['attachments'] = !empty($item['attachments']) ? 
                (is_array($item['attachments']) ? $item['attachments'] : json_decode($item['attachments'], true)) : [];
        }

        return $lists;
    }

    /**
     * @notes 统计数量
     * @return int
     */
    public function count(): int
    {
        return FollowRecord::where($this->searchWhere)
            ->where($this->queryWhere())
            ->count();
    }

    /**
     * @notes 获取跟进方式描述
     * @param int $type
     * @return string
     */
    private function getFollowTypeDesc(int $type): string
    {
        $map = [
            1 => '电话',
            2 => '微信',
            3 => '到店',
            4 => '试妆',
            5 => '看样片',
            6 => '上门',
            7 => '其他',
        ];
        return $map[$type] ?? '未知';
    }

    /**
     * @notes 获取跟进结果描述
     * @param int $result
     * @return string
     */
    private function getResultDesc(int $result): string
    {
        $map = [
            1 => '继续跟进',
            2 => '意向提升',
            3 => '意向下降',
            4 => '已成交',
            5 => '已流失',
        ];
        return $map[$result] ?? '未知';
    }

    /**
     * @notes 获取意向等级描述
     * @param string|null $level
     * @return string
     */
    private function getIntentionDesc(?string $level): string
    {
        if (empty($level)) {
            return '-';
        }
        $map = [
            'A' => '高意向',
            'B' => '中意向',
            'C' => '低意向',
            'D' => '待跟进',
        ];
        return $map[$level] ?? '未知';
    }
}
