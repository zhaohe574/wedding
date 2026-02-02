<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 档期规则列表
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\adminapi\lists\schedule;

use app\adminapi\lists\BaseAdminDataLists;
use app\common\model\schedule\ScheduleRule;

/**
 * 档期规则列表
 * Class ScheduleRuleLists
 * @package app\adminapi\lists\schedule
 */
class ScheduleRuleLists extends BaseAdminDataLists
{
    /**
     * @notes 搜索条件
     * @return array
     */
    public function setSearch(): array
    {
        return [
            '=' => ['staff_id', 'is_enabled'],
        ];
    }

    /**
     * @notes 列表
     * @return array
     */
    public function lists(): array
    {
        $query = ScheduleRule::with(['staff' => function ($query) {
                $query->field('id, name, avatar');
            }])
            ->where($this->searchWhere);

        $staffScopeId = $this->getStaffScopeId();
        if ($staffScopeId > 0) {
            $query->whereIn('staff_id', [$staffScopeId, 0]);
        }

        $lists = $query->order('staff_id', 'asc')
            ->limit($this->limitOffset, $this->limitLength)
            ->select()
            ->toArray();

        foreach ($lists as &$item) {
            $item['rest_days_arr'] = $item['rest_days'] ? explode(',', $item['rest_days']) : [];
            $item['rest_days_desc'] = $this->getRestDaysDesc($item['rest_days_arr']);
            $item['type_desc'] = $item['staff_id'] == 0 ? '全局规则' : '个人规则';
        }

        return $lists;
    }

    /**
     * @notes 总数
     * @return int
     */
    public function count(): int
    {
        $query = ScheduleRule::where($this->searchWhere);
        $staffScopeId = $this->getStaffScopeId();
        if ($staffScopeId > 0) {
            $query->whereIn('staff_id', [$staffScopeId, 0]);
        }
        return $query->count();
    }

    /**
     * @notes 获取休息日描述
     * @param array $restDays
     * @return string
     */
    protected function getRestDaysDesc(array $restDays): string
    {
        if (empty($restDays)) {
            return '无';
        }
        $map = ['周日', '周一', '周二', '周三', '周四', '周五', '周六'];
        $desc = [];
        foreach ($restDays as $day) {
            if (isset($map[(int)$day])) {
                $desc[] = $map[(int)$day];
            }
        }
        return implode('、', $desc);
    }
}
