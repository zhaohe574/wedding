<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 客户业务逻辑
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\adminapi\logic\crm;

use app\common\logic\BaseLogic;
use app\common\model\crm\Customer;
use app\common\model\crm\SalesAdvisor;
use app\common\model\crm\CustomerAssignLog;
use app\common\model\crm\CustomerLossWarning;
use think\facade\Db;

/**
 * 客户业务逻辑
 * Class CustomerLogic
 * @package app\adminapi\logic\crm
 */
class CustomerLogic extends BaseLogic
{
    /**
     * @notes 获取客户详情
     * @param int $id
     * @return array|null
     */
    public static function detail(int $id): ?array
    {
        $customer = Customer::with([
            'advisor' => function ($query) {
                $query->field('id, advisor_name, mobile, avatar, wechat');
            },
            'user' => function ($query) {
                $query->field('id, nickname, avatar, mobile');
            }
        ])->find($id);

        if (!$customer) {
            return null;
        }

        $data = $customer->toArray();
        $data['intention_level_desc'] = $customer->intention_level_desc;
        $data['customer_status_desc'] = $customer->customer_status_desc;
        $data['source_channel_desc'] = $customer->source_channel_desc;
        $data['gender_desc'] = $customer->gender_desc;
        $data['days_no_follow'] = $customer->days_no_follow;
        $data['days_to_wedding'] = $customer->days_to_wedding;

        return $data;
    }

    /**
     * @notes 添加客户
     * @param array $params
     * @param int $adminId
     * @return bool
     */
    public static function add(array $params, int $adminId = 0): bool
    {
        Db::startTrans();
        try {
            // 检查手机号是否已存在
            if (!empty($params['customer_mobile'])) {
                $exists = Customer::where('customer_mobile', $params['customer_mobile'])->find();
                if ($exists) {
                    self::setError('该手机号已存在');
                    return false;
                }
            }

            // 自动分配顾问
            $advisorId = $params['advisor_id'] ?? 0;
            if ($advisorId <= 0 && !empty($params['city'])) {
                $advisorId = SalesAdvisor::autoAssign($params['city']) ?? 0;
            }

            $customer = Customer::create([
                'user_id' => $params['user_id'] ?? 0,
                'customer_name' => $params['customer_name'],
                'customer_mobile' => $params['customer_mobile'] ?? '',
                'customer_wechat' => $params['customer_wechat'] ?? '',
                'gender' => $params['gender'] ?? 0,
                'age' => $params['age'] ?? 0,
                'city' => $params['city'] ?? '',
                'district' => $params['district'] ?? '',
                'intention_level' => $params['intention_level'] ?? 'D',
                'intention_score' => $params['intention_score'] ?? 0,
                'wedding_date' => $params['wedding_date'] ?? null,
                'wedding_venue' => $params['wedding_venue'] ?? '',
                'wedding_budget' => $params['wedding_budget'] ?? 0,
                'budget_range' => $params['budget_range'] ?? '',
                'service_needs' => $params['service_needs'] ?? [],
                'source_channel' => $params['source_channel'] ?? 1,
                'source_detail' => $params['source_detail'] ?? '',
                'tags' => $params['tags'] ?? [],
                'customer_status' => Customer::STATUS_NEW,
                'advisor_id' => $advisorId,
                'assign_time' => $advisorId > 0 ? time() : 0,
                'next_follow_time' => $params['next_follow_time'] ?? 0,
                'remark' => $params['remark'] ?? '',
                'create_time' => time(),
                'update_time' => time(),
            ]);

            // 记录分配日志
            if ($advisorId > 0) {
                CustomerAssignLog::record(
                    $customer->id,
                    0,
                    $advisorId,
                    isset($params['advisor_id']) ? CustomerAssignLog::TYPE_MANUAL : CustomerAssignLog::TYPE_AUTO,
                    '新客户创建时分配',
                    $adminId
                );
                
                // 更新顾问客户数
                SalesAdvisor::incrementCustomerCount($advisorId);
            }

            Db::commit();
            return true;
        } catch (\Exception $e) {
            Db::rollback();
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * @notes 编辑客户
     * @param array $params
     * @return bool
     */
    public static function edit(array $params): bool
    {
        try {
            $customer = Customer::find($params['id']);
            if (!$customer) {
                self::setError('客户不存在');
                return false;
            }

            // 检查手机号唯一性
            if (!empty($params['customer_mobile']) && $params['customer_mobile'] != $customer->customer_mobile) {
                $exists = Customer::where('customer_mobile', $params['customer_mobile'])
                    ->where('id', '<>', $params['id'])
                    ->find();
                if ($exists) {
                    self::setError('该手机号已存在');
                    return false;
                }
            }

            $updateData = [];
            $allowFields = [
                'customer_name', 'customer_mobile', 'customer_wechat',
                'gender', 'age', 'city', 'district',
                'intention_level', 'intention_score',
                'wedding_date', 'wedding_venue', 'wedding_budget', 'budget_range',
                'service_needs', 'source_channel', 'source_detail',
                'tags', 'remark', 'next_follow_time'
            ];

            foreach ($allowFields as $field) {
                if (isset($params[$field])) {
                    $updateData[$field] = $params[$field];
                }
            }

            if (!empty($updateData)) {
                $updateData['update_time'] = time();
                Customer::where('id', $params['id'])->update($updateData);
            }

            return true;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * @notes 删除客户
     * @param int $id
     * @return bool
     */
    public static function delete(int $id): bool
    {
        try {
            $customer = Customer::find($id);
            if (!$customer) {
                self::setError('客户不存在');
                return false;
            }

            // 减少顾问客户数
            if ($customer->advisor_id > 0) {
                SalesAdvisor::decrementCustomerCount($customer->advisor_id);
            }

            $customer->delete();
            return true;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * @notes 分配顾问
     * @param int $customerId
     * @param int $advisorId
     * @param int $adminId
     * @param string $reason
     * @return bool
     */
    public static function assignAdvisor(int $customerId, int $advisorId, int $adminId, string $reason = ''): bool
    {
        try {
            $customer = Customer::find($customerId);
            if (!$customer) {
                self::setError('客户不存在');
                return false;
            }

            $advisor = SalesAdvisor::find($advisorId);
            if (!$advisor) {
                self::setError('顾问不存在');
                return false;
            }

            if (!$advisor->canAssignCustomer()) {
                self::setError('该顾问无法接收新客户');
                return false;
            }

            if ($customer->advisor_id == $advisorId) {
                self::setError('客户已分配给该顾问');
                return false;
            }

            $assignType = $customer->advisor_id > 0 ? CustomerAssignLog::TYPE_TRANSFER : CustomerAssignLog::TYPE_MANUAL;
            
            $result = Customer::assignAdvisor($customerId, $advisorId, $adminId, $assignType, $reason);
            
            if (!$result) {
                self::setError('分配失败');
                return false;
            }

            return true;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * @notes 批量分配顾问
     * @param array $customerIds
     * @param int $advisorId
     * @param int $adminId
     * @param string $reason
     * @return array
     */
    public static function batchAssign(array $customerIds, int $advisorId, int $adminId, string $reason = ''): array
    {
        $success = 0;
        $fail = 0;
        $errors = [];

        foreach ($customerIds as $customerId) {
            if (self::assignAdvisor((int)$customerId, $advisorId, $adminId, $reason)) {
                $success++;
            } else {
                $fail++;
                $errors[] = "客户ID {$customerId}: " . self::getError();
            }
        }

        return [
            'success' => $success,
            'fail' => $fail,
            'errors' => $errors,
        ];
    }

    /**
     * @notes 标记客户流失
     * @param int $customerId
     * @param string $reason
     * @return bool
     */
    public static function markAsLost(int $customerId, string $reason = ''): bool
    {
        try {
            $customer = Customer::find($customerId);
            if (!$customer) {
                self::setError('客户不存在');
                return false;
            }

            if ($customer->customer_status == Customer::STATUS_LOST) {
                self::setError('客户已标记为流失');
                return false;
            }

            $result = Customer::markAsLost($customerId, $reason);
            if (!$result) {
                self::setError('标记失败');
                return false;
            }

            // 减少顾问客户数
            if ($customer->advisor_id > 0) {
                SalesAdvisor::decrementCustomerCount($customer->advisor_id);
            }

            return true;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * @notes 更新意向等级
     * @param int $customerId
     * @param string $intentionLevel
     * @param int $intentionScore
     * @return bool
     */
    public static function updateIntention(int $customerId, string $intentionLevel, int $intentionScore = 0): bool
    {
        try {
            $customer = Customer::find($customerId);
            if (!$customer) {
                self::setError('客户不存在');
                return false;
            }

            $updateData = [
                'intention_level' => $intentionLevel,
                'update_time' => time(),
            ];

            if ($intentionScore > 0) {
                $updateData['intention_score'] = $intentionScore;
            }

            Customer::where('id', $customerId)->update($updateData);
            return true;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * @notes 获取客户统计概览
     * @param int $advisorId
     * @return array
     */
    public static function getOverview(int $advisorId = 0): array
    {
        return Customer::getCustomerOverview($advisorId);
    }

    /**
     * @notes 获取待跟进客户
     * @param int $advisorId
     * @return array
     */
    public static function getPendingFollow(int $advisorId): array
    {
        return Customer::getPendingFollowCustomers($advisorId);
    }

    /**
     * @notes 获取客户分配历史
     * @param int $customerId
     * @return array
     */
    public static function getAssignHistory(int $customerId): array
    {
        return CustomerAssignLog::getCustomerHistory($customerId);
    }

    /**
     * @notes 获取可用顾问列表(用于分配)
     * @return array
     */
    public static function getAvailableAdvisors(): array
    {
        return SalesAdvisor::getAvailableAdvisors();
    }
}
