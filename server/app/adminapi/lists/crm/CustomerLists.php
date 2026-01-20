<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 客户列表
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\adminapi\lists\crm;

use app\adminapi\lists\BaseAdminDataLists;
use app\common\model\crm\Customer;
use app\common\lists\ListsSearchInterface;

/**
 * 客户列表
 * Class CustomerLists
 * @package app\adminapi\lists\crm
 */
class CustomerLists extends BaseAdminDataLists implements ListsSearchInterface
{
    /**
     * @notes 搜索条件
     * @return array
     */
    public function setSearch(): array
    {
        return [
            '=' => ['intention_level', 'customer_status', 'source_channel', 'advisor_id', 'gender'],
            '%like%' => ['customer_name', 'customer_mobile', 'customer_wechat', 'city'],
            'between_date' => ['wedding_date', 'create_time'],
            'between' => ['wedding_budget'],
        ];
    }

    /**
     * @notes 自定义查询条件
     * @return array
     */
    public function queryWhere(): array
    {
        $where = [];

        // 标签筛选
        if (!empty($this->params['tag'])) {
            $where[] = ['tags', 'like', '%"' . $this->params['tag'] . '"%'];
        }

        // 未跟进天数筛选
        if (!empty($this->params['days_no_follow'])) {
            $days = (int)$this->params['days_no_follow'];
            $threshold = time() - ($days * 86400);
            $where[] = ['last_follow_time', '<', $threshold];
        }

        // 待跟进筛选（下次跟进时间已过）
        if (!empty($this->params['need_follow'])) {
            $where[] = ['next_follow_time', '<=', time()];
            $where[] = ['next_follow_time', '>', 0];
            $where[] = ['customer_status', 'in', [Customer::STATUS_NEW, Customer::STATUS_FOLLOWING]];
        }

        // 婚期临近筛选（N天内）
        if (!empty($this->params['wedding_soon'])) {
            $days = (int)$this->params['wedding_soon'];
            $futureDate = date('Y-m-d', strtotime("+{$days} days"));
            $today = date('Y-m-d');
            $where[] = ['wedding_date', '>=', $today];
            $where[] = ['wedding_date', '<=', $futureDate];
        }

        return $where;
    }

    /**
     * @notes 列表数据
     * @return array
     */
    public function lists(): array
    {
        $lists = Customer::with([
            'advisor' => function ($query) {
                $query->field('id, advisor_name, mobile, avatar');
            },
            'user' => function ($query) {
                $query->field('id, nickname, avatar, mobile');
            }
        ])
            ->where($this->searchWhere)
            ->where($this->queryWhere())
            ->order($this->sortOrder ?: ['create_time' => 'desc', 'id' => 'desc'])
            ->limit($this->limitOffset, $this->limitLength)
            ->select()
            ->toArray();

        foreach ($lists as &$item) {
            $item['intention_level_desc'] = $this->getIntentionDesc($item['intention_level']);
            $item['customer_status_desc'] = $this->getStatusDesc($item['customer_status']);
            $item['source_channel_desc'] = $this->getSourceDesc($item['source_channel']);
            $item['gender_desc'] = $this->getGenderDesc($item['gender']);
            $item['days_no_follow'] = $this->getDaysNoFollow($item);
            $item['days_to_wedding'] = $this->getDaysToWedding($item['wedding_date']);
            
            // 解析JSON字段
            $item['tags'] = !empty($item['tags']) ? (is_array($item['tags']) ? $item['tags'] : json_decode($item['tags'], true)) : [];
            $item['service_needs'] = !empty($item['service_needs']) ? (is_array($item['service_needs']) ? $item['service_needs'] : json_decode($item['service_needs'], true)) : [];
        }

        return $lists;
    }

    /**
     * @notes 统计数量
     * @return int
     */
    public function count(): int
    {
        return Customer::where($this->searchWhere)
            ->where($this->queryWhere())
            ->count();
    }

    /**
     * @notes 获取意向等级描述
     * @param string $level
     * @return string
     */
    private function getIntentionDesc(string $level): string
    {
        $map = [
            'A' => '高意向',
            'B' => '中意向',
            'C' => '低意向',
            'D' => '待跟进',
        ];
        return $map[$level] ?? '未知';
    }

    /**
     * @notes 获取客户状态描述
     * @param int $status
     * @return string
     */
    private function getStatusDesc(int $status): string
    {
        $map = [
            1 => '新客户',
            2 => '跟进中',
            3 => '已签单',
            4 => '已流失',
            5 => '已完成',
        ];
        return $map[$status] ?? '未知';
    }

    /**
     * @notes 获取来源渠道描述
     * @param int $source
     * @return string
     */
    private function getSourceDesc(int $source): string
    {
        $map = [
            1 => '小程序',
            2 => 'H5',
            3 => '线下',
            4 => '转介绍',
            5 => '广告',
            6 => '其他',
        ];
        return $map[$source] ?? '未知';
    }

    /**
     * @notes 获取性别描述
     * @param int $gender
     * @return string
     */
    private function getGenderDesc(int $gender): string
    {
        $map = [
            0 => '未知',
            1 => '男',
            2 => '女',
        ];
        return $map[$gender] ?? '未知';
    }

    /**
     * @notes 计算未跟进天数
     * @param array $item
     * @return int
     */
    private function getDaysNoFollow(array $item): int
    {
        $lastTime = $item['last_follow_time'] ?: $item['create_time'];
        return (int)ceil((time() - $lastTime) / 86400);
    }

    /**
     * @notes 计算距婚期天数
     * @param string|null $weddingDate
     * @return int|null
     */
    private function getDaysToWedding(?string $weddingDate): ?int
    {
        if (empty($weddingDate)) {
            return null;
        }
        $weddingTime = strtotime($weddingDate);
        $now = strtotime(date('Y-m-d'));
        return (int)ceil(($weddingTime - $now) / 86400);
    }
}
