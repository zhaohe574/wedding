<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 档期规则业务逻辑
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\adminapi\logic\schedule;

use app\common\logic\BaseLogic;
use app\common\model\schedule\ScheduleRule;

/**
 * 档期规则业务逻辑
 * Class ScheduleRuleLogic
 * @package app\adminapi\logic\schedule
 */
class ScheduleRuleLogic extends BaseLogic
{
    /**
     * @notes 规则详情
     * @param int $id
     * @return array
     */
    public static function detail(int $id): array
    {
        $rule = ScheduleRule::with(['staff'])->find($id);
        if (!$rule) {
            return [];
        }
        $data = $rule->toArray();
        $data['rest_days_arr'] = $rule->rest_days ? explode(',', $rule->rest_days) : [];
        return $data;
    }

    /**
     * @notes 添加规则
     * @param array $params
     * @return bool
     */
    public static function add(array $params): bool
    {
        try {
            // 检查是否已存在该工作人员的规则
            if ($params['staff_id'] > 0) {
                $exists = ScheduleRule::where('staff_id', $params['staff_id'])->find();
                if ($exists) {
                    self::setError('该工作人员已有规则配置');
                    return false;
                }
            } else {
                // 全局规则只能有一个
                $exists = ScheduleRule::where('staff_id', 0)->find();
                if ($exists) {
                    self::setError('全局规则已存在，请编辑');
                    return false;
                }
            }

            ScheduleRule::create([
                'staff_id' => $params['staff_id'] ?? 0,
                'advance_days' => $params['advance_days'] ?? 1,
                'max_orders_per_day' => $params['max_orders_per_day'] ?? 1,
                'interval_hours' => $params['interval_hours'] ?? 0,
                'work_start_time' => $params['work_start_time'] ?? '09:00',
                'work_end_time' => $params['work_end_time'] ?? '18:00',
                'rest_days' => is_array($params['rest_days'] ?? '') ? implode(',', $params['rest_days']) : ($params['rest_days'] ?? ''),
                'is_enabled' => $params['is_enabled'] ?? 1,
                'create_time' => time(),
                'update_time' => time(),
            ]);
            return true;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * @notes 编辑规则
     * @param array $params
     * @return bool
     */
    public static function edit(array $params): bool
    {
        try {
            $rule = ScheduleRule::find($params['id']);
            if (!$rule) {
                self::setError('规则不存在');
                return false;
            }

            $rule->advance_days = $params['advance_days'] ?? $rule->advance_days;
            $rule->max_orders_per_day = $params['max_orders_per_day'] ?? $rule->max_orders_per_day;
            $rule->interval_hours = $params['interval_hours'] ?? $rule->interval_hours;
            $rule->work_start_time = $params['work_start_time'] ?? $rule->work_start_time;
            $rule->work_end_time = $params['work_end_time'] ?? $rule->work_end_time;
            $rule->rest_days = is_array($params['rest_days'] ?? '') ? implode(',', $params['rest_days']) : ($params['rest_days'] ?? $rule->rest_days);
            $rule->is_enabled = $params['is_enabled'] ?? $rule->is_enabled;
            $rule->update_time = time();
            $rule->save();

            return true;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * @notes 删除规则
     * @param array $params
     * @return bool
     */
    public static function delete(array $params): bool
    {
        try {
            $rule = ScheduleRule::find($params['id']);
            if (!$rule) {
                self::setError('规则不存在');
                return false;
            }

            // 全局规则不能删除
            if ($rule->staff_id == 0) {
                self::setError('全局规则不能删除，只能编辑');
                return false;
            }

            $rule->delete();
            return true;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * @notes 切换启用状态
     * @param array $params
     * @return bool
     */
    public static function changeStatus(array $params): bool
    {
        try {
            $rule = ScheduleRule::find($params['id']);
            if (!$rule) {
                self::setError('规则不存在');
                return false;
            }

            $rule->is_enabled = $rule->is_enabled ? 0 : 1;
            $rule->update_time = time();
            $rule->save();

            return true;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * @notes 获取全局规则
     * @return array
     */
    public static function getGlobalRule(): array
    {
        $rule = ScheduleRule::where('staff_id', 0)->find();
        if (!$rule) {
            return [];
        }
        $data = $rule->toArray();
        $data['rest_days_arr'] = $rule->rest_days ? explode(',', $rule->rest_days) : [];
        return $data;
    }

    /**
     * @notes 获取工作人员规则（含全局回退）
     * @param int $staffId
     * @return array
     */
    public static function getStaffRule(int $staffId): array
    {
        // 优先获取个人规则
        $rule = ScheduleRule::where('staff_id', $staffId)
            ->where('is_enabled', 1)
            ->find();

        // 回退到全局规则
        if (!$rule) {
            $rule = ScheduleRule::where('staff_id', 0)
                ->where('is_enabled', 1)
                ->find();
        }

        if (!$rule) {
            return [];
        }

        $data = $rule->toArray();
        $data['rest_days_arr'] = $rule->rest_days ? explode(',', $rule->rest_days) : [];
        $data['is_global'] = $rule->staff_id == 0;
        return $data;
    }
}
