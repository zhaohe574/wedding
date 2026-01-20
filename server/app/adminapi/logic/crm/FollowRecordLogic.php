<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 跟进记录业务逻辑
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\adminapi\logic\crm;

use app\common\logic\BaseLogic;
use app\common\model\crm\FollowRecord;
use app\common\model\crm\Customer;
use app\common\model\crm\SalesAdvisor;

/**
 * 跟进记录业务逻辑
 * Class FollowRecordLogic
 * @package app\adminapi\logic\crm
 */
class FollowRecordLogic extends BaseLogic
{
    /**
     * @notes 获取跟进记录详情
     * @param int $id
     * @return array|null
     */
    public static function detail(int $id): ?array
    {
        $record = FollowRecord::with([
            'customer' => function ($query) {
                $query->field('id, customer_name, customer_mobile, intention_level, customer_status');
            },
            'advisor' => function ($query) {
                $query->field('id, advisor_name, mobile, avatar');
            },
            'admin' => function ($query) {
                $query->field('id, name, avatar');
            }
        ])->find($id);

        if (!$record) {
            return null;
        }

        $data = $record->toArray();
        $data['follow_type_desc'] = $record->follow_type_desc;
        $data['follow_result_desc'] = $record->follow_result_desc;

        return $data;
    }

    /**
     * @notes 添加跟进记录
     * @param array $params
     * @param int $adminId
     * @return bool
     */
    public static function add(array $params, int $adminId): bool
    {
        try {
            // 检查客户是否存在
            $customer = Customer::find($params['customer_id']);
            if (!$customer) {
                self::setError('客户不存在');
                return false;
            }

            // 获取客户当前意向等级
            $intentionBefore = $customer->intention_level;

            $record = FollowRecord::createRecord([
                'customer_id' => $params['customer_id'],
                'advisor_id' => $customer->advisor_id,
                'admin_id' => $adminId,
                'follow_type' => $params['follow_type'],
                'follow_content' => $params['follow_content'],
                'follow_result' => $params['follow_result'],
                'intention_before' => $intentionBefore,
                'intention_after' => $params['intention_after'] ?? $intentionBefore,
                'duration' => $params['duration'] ?? 0,
                'next_follow_time' => $params['next_follow_time'] ?? 0,
                'next_follow_content' => $params['next_follow_content'] ?? '',
                'attachments' => $params['attachments'] ?? [],
                'is_important' => $params['is_important'] ?? 0,
            ]);

            if (!$record) {
                self::setError('添加跟进记录失败');
                return false;
            }

            return true;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * @notes 编辑跟进记录
     * @param array $params
     * @return bool
     */
    public static function edit(array $params): bool
    {
        try {
            $record = FollowRecord::find($params['id']);
            if (!$record) {
                self::setError('跟进记录不存在');
                return false;
            }

            $updateData = [];
            $allowFields = [
                'follow_type', 'follow_content', 'follow_result',
                'intention_after', 'duration',
                'next_follow_time', 'next_follow_content',
                'attachments', 'is_important'
            ];

            foreach ($allowFields as $field) {
                if (isset($params[$field])) {
                    $updateData[$field] = $params[$field];
                }
            }

            if (!empty($updateData)) {
                FollowRecord::where('id', $params['id'])->update($updateData);

                // 如果更新了意向等级，同步更新客户信息
                if (!empty($params['intention_after'])) {
                    Customer::where('id', $record->customer_id)->update([
                        'intention_level' => $params['intention_after'],
                        'update_time' => time(),
                    ]);
                }

                // 如果更新了下次跟进时间，同步更新客户信息
                if (isset($params['next_follow_time'])) {
                    Customer::where('id', $record->customer_id)->update([
                        'next_follow_time' => $params['next_follow_time'],
                        'update_time' => time(),
                    ]);
                }
            }

            return true;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * @notes 删除跟进记录
     * @param int $id
     * @return bool
     */
    public static function delete(int $id): bool
    {
        try {
            $record = FollowRecord::find($id);
            if (!$record) {
                self::setError('跟进记录不存在');
                return false;
            }

            FollowRecord::destroy($id);
            return true;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * @notes 获取客户跟进记录列表
     * @param int $customerId
     * @param int $limit
     * @return array
     */
    public static function getCustomerRecords(int $customerId, int $limit = 20): array
    {
        return FollowRecord::getCustomerRecords($customerId, $limit);
    }

    /**
     * @notes 获取顾问今日跟进统计
     * @param int $advisorId
     * @return array
     */
    public static function getAdvisorTodayStats(int $advisorId): array
    {
        return FollowRecord::getAdvisorTodayStats($advisorId);
    }

    /**
     * @notes 获取重要跟进记录
     * @param int $customerId
     * @return array
     */
    public static function getImportantRecords(int $customerId): array
    {
        return FollowRecord::getImportantRecords($customerId);
    }

    /**
     * @notes 获取跟进方式选项
     * @return array
     */
    public static function getTypeOptions(): array
    {
        return FollowRecord::getTypeOptions();
    }

    /**
     * @notes 获取跟进结果选项
     * @return array
     */
    public static function getResultOptions(): array
    {
        return FollowRecord::getResultOptions();
    }

    /**
     * @notes 获取时间段跟进统计
     * @param int $advisorId
     * @param string $startDate
     * @param string $endDate
     * @return array
     */
    public static function getPeriodStats(int $advisorId, string $startDate, string $endDate): array
    {
        return FollowRecord::getPeriodStats($advisorId, $startDate, $endDate);
    }
}
