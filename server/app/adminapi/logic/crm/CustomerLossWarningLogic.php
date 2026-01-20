<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 客户流失预警业务逻辑
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\adminapi\logic\crm;

use app\common\logic\BaseLogic;
use app\common\model\crm\CustomerLossWarning;
use app\common\model\crm\Customer;

/**
 * 客户流失预警业务逻辑
 * Class CustomerLossWarningLogic
 * @package app\adminapi\logic\crm
 */
class CustomerLossWarningLogic extends BaseLogic
{
    /**
     * @notes 获取预警详情
     * @param int $id
     * @return array|null
     */
    public static function detail(int $id): ?array
    {
        $warning = CustomerLossWarning::with([
            'customer' => function ($query) {
                $query->field('id, customer_name, customer_mobile, intention_level, customer_status, last_follow_time, advisor_id');
            },
            'advisor' => function ($query) {
                $query->field('id, advisor_name, mobile, avatar');
            }
        ])->find($id);

        if (!$warning) {
            return null;
        }

        $data = $warning->toArray();
        $data['warning_type_desc'] = $warning->warning_type_desc;
        $data['warning_level_desc'] = $warning->warning_level_desc;
        $data['warning_status_desc'] = $warning->warning_status_desc;

        return $data;
    }

    /**
     * @notes 处理预警
     * @param int $warningId
     * @param string $remark
     * @return bool
     */
    public static function handle(int $warningId, string $remark = ''): bool
    {
        try {
            $warning = CustomerLossWarning::find($warningId);
            if (!$warning) {
                self::setError('预警记录不存在');
                return false;
            }

            if ($warning->warning_status != CustomerLossWarning::STATUS_PENDING) {
                self::setError('该预警已处理');
                return false;
            }

            $result = CustomerLossWarning::handleWarning($warningId, $remark);
            if (!$result) {
                self::setError('处理失败');
                return false;
            }

            return true;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * @notes 忽略预警
     * @param int $warningId
     * @param string $remark
     * @return bool
     */
    public static function ignore(int $warningId, string $remark = ''): bool
    {
        try {
            $warning = CustomerLossWarning::find($warningId);
            if (!$warning) {
                self::setError('预警记录不存在');
                return false;
            }

            if ($warning->warning_status != CustomerLossWarning::STATUS_PENDING) {
                self::setError('该预警已处理');
                return false;
            }

            $result = CustomerLossWarning::ignoreWarning($warningId, $remark);
            if (!$result) {
                self::setError('忽略失败');
                return false;
            }

            return true;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * @notes 批量处理预警
     * @param array $warningIds
     * @param string $action handle/ignore
     * @param string $remark
     * @return array
     */
    public static function batchProcess(array $warningIds, string $action, string $remark = ''): array
    {
        $success = 0;
        $fail = 0;

        foreach ($warningIds as $warningId) {
            if ($action == 'handle') {
                $result = self::handle((int)$warningId, $remark);
            } else {
                $result = self::ignore((int)$warningId, $remark);
            }

            if ($result) {
                $success++;
            } else {
                $fail++;
            }
        }

        return [
            'success' => $success,
            'fail' => $fail,
        ];
    }

    /**
     * @notes 生成流失预警
     * @return int 生成的预警数量
     */
    public static function generateWarnings(): int
    {
        return CustomerLossWarning::generateNoFollowWarnings(7, 14, 30);
    }

    /**
     * @notes 获取顾问待处理预警
     * @param int $advisorId
     * @return array
     */
    public static function getAdvisorPendingWarnings(int $advisorId): array
    {
        return CustomerLossWarning::getAdvisorPendingWarnings($advisorId);
    }

    /**
     * @notes 获取所有待处理预警
     * @param int $level
     * @return array
     */
    public static function getAllPendingWarnings(int $level = 0): array
    {
        return CustomerLossWarning::getAllPendingWarnings($level);
    }

    /**
     * @notes 获取预警统计
     * @param int $advisorId
     * @return array
     */
    public static function getWarningStats(int $advisorId = 0): array
    {
        return CustomerLossWarning::getWarningStats($advisorId);
    }

    /**
     * @notes 获取预警类型选项
     * @return array
     */
    public static function getTypeOptions(): array
    {
        return CustomerLossWarning::getTypeOptions();
    }

    /**
     * @notes 获取预警等级选项
     * @return array
     */
    public static function getLevelOptions(): array
    {
        return CustomerLossWarning::getLevelOptions();
    }

    /**
     * @notes 获取处理状态选项
     * @return array
     */
    public static function getStatusOptions(): array
    {
        return CustomerLossWarning::getStatusOptions();
    }

    /**
     * @notes 手动创建预警
     * @param int $customerId
     * @param int $warningType
     * @param int $warningLevel
     * @param string $reason
     * @return bool
     */
    public static function createWarning(int $customerId, int $warningType, int $warningLevel, string $reason = ''): bool
    {
        try {
            $customer = Customer::find($customerId);
            if (!$customer) {
                self::setError('客户不存在');
                return false;
            }

            $result = CustomerLossWarning::createWarning(
                $customerId,
                $customer->advisor_id,
                $warningType,
                $warningLevel,
                $reason
            );

            if (!$result) {
                self::setError('创建预警失败');
                return false;
            }

            return true;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }
}
