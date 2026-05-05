<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 跟进记录逻辑
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\adminapi\logic\crm;

use app\common\logic\BaseLogic;
use app\common\model\crm\Customer;
use app\common\model\crm\FollowRecord;
use app\common\model\crm\SalesAdvisor;
use app\common\service\CrmAdvisorScopeService;
use think\facade\Db;

/**
 * 跟进记录逻辑
 */
class FollowRecordLogic extends BaseLogic
{
    /**
     * @notes 跟进记录详情
     */
    public static function detail(int $id, int $advisorScopeId = 0, array $adminInfo = []): array|false
    {
        $record = FollowRecord::where('id', $id)
            ->append(['follow_type_desc', 'follow_result_desc'])
            ->find();
        if (!$record) {
            return [];
        }
        if (!self::canAccessAdvisor((int)$record->advisor_id, $advisorScopeId, $adminInfo)) {
            self::setError(CrmAdvisorScopeService::getAdvisorScopeAccessDeniedMessage($adminInfo));
            return false;
        }

        $data = $record->toArray();
        $data['customer'] = self::getCustomerInfo((int)$data['customer_id']);
        $data['advisor'] = self::getAdvisorInfo((int)$data['advisor_id']);
        $data['next_follow_time_text'] = self::formatTime($data['next_follow_time'] ?? 0);
        $data['create_time_text'] = self::formatTime($data['create_time'] ?? 0);
        return $data;
    }

    /**
     * @notes 新增跟进记录
     */
    public static function add(array $params, int $adminId, int $advisorScopeId = 0, array $adminInfo = []): bool
    {
        Db::startTrans();
        try {
            $customerId = (int)$params['customer_id'];
            $customer = Customer::find($customerId);
            if (!$customer) {
                throw new \Exception('客户不存在');
            }
            if (!CustomerLogic::canAccessCustomer($customer, $advisorScopeId, $adminInfo)) {
                throw new \Exception(CrmAdvisorScopeService::getAdvisorScopeAccessDeniedMessage($adminInfo));
            }

            $advisorId = (int)$customer->advisor_id;
            if ($advisorScopeId > 0) {
                $advisorId = $advisorScopeId;
            }
            if ($advisorId <= 0) {
                throw new \Exception('客户未分配顾问，不能新增跟进记录');
            }

            $data = [
                'customer_id' => $customerId,
                'advisor_id' => $advisorId,
                'admin_id' => $adminId,
                'follow_type' => (int)$params['follow_type'],
                'follow_content' => trim((string)$params['follow_content']),
                'follow_result' => (int)$params['follow_result'],
                'intention_before' => (string)$customer->intention_level,
                'intention_after' => (string)($params['intention_after'] ?? ''),
                'duration' => max(0, (int)($params['duration'] ?? 0)),
                'next_follow_time' => self::normalizeTimestamp($params['next_follow_time'] ?? 0),
                'next_follow_content' => trim((string)($params['next_follow_content'] ?? '')),
                'attachments' => self::normalizeAttachments($params['attachments'] ?? []),
                'is_important' => (int)($params['is_important'] ?? 0),
                'create_time' => time(),
            ];

            if ($data['intention_after'] === '') {
                $data['intention_after'] = (string)$customer->intention_level;
            }

            if (!FollowRecord::createRecord($data)) {
                throw new \Exception('跟进记录新增失败');
            }

            Db::commit();
            return true;
        } catch (\Throwable $e) {
            Db::rollback();
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * @notes 跟进选项
     */
    public static function options(): array
    {
        return [
            'follow_type_options' => self::formatOptions(FollowRecord::getTypeOptions()),
            'follow_result_options' => self::formatOptions(FollowRecord::getResultOptions()),
            'intention_options' => self::formatOptions(Customer::getIntentionOptions()),
        ];
    }

    /**
     * @notes 可跟进客户选项
     */
    public static function customerOptions(int $advisorScopeId = 0): array
    {
        if ($advisorScopeId < 0) {
            return [];
        }

        $customers = Customer::whereIn('customer_status', [Customer::STATUS_NEW, Customer::STATUS_FOLLOWING])
            ->when($advisorScopeId > 0, function ($query) use ($advisorScopeId) {
                $query->where('advisor_id', $advisorScopeId);
            })
            ->append(['customer_status_desc', 'intention_level_desc'])
            ->field('id,customer_name,customer_mobile,intention_level,customer_status,advisor_id')
            ->order(['id' => 'desc'])
            ->limit(200)
            ->select()
            ->toArray();

        return array_map(static function ($customer) {
            return [
                'id' => (int)$customer['id'],
                'customer_name' => (string)$customer['customer_name'],
                'customer_mobile' => (string)($customer['customer_mobile'] ?? ''),
                'intention_level' => (string)($customer['intention_level'] ?? ''),
                'intention_level_desc' => (string)($customer['intention_level_desc'] ?? ''),
                'customer_status' => (int)($customer['customer_status'] ?? 0),
                'customer_status_desc' => (string)($customer['customer_status_desc'] ?? ''),
                'advisor_id' => (int)($customer['advisor_id'] ?? 0),
            ];
        }, $customers);
    }

    /**
     * @notes 当前账号是否能访问顾问数据
     */
    public static function canAccessAdvisor(int $advisorId, int $advisorScopeId = 0, array $adminInfo = []): bool
    {
        if ($advisorScopeId === 0) {
            return true;
        }
        if ($advisorScopeId < 0) {
            return false;
        }
        return $advisorId === $advisorScopeId;
    }

    /**
     * @notes 选项格式
     */
    private static function formatOptions(array $options): array
    {
        $result = [];
        foreach ($options as $value => $label) {
            $result[] = [
                'value' => is_numeric($value) ? (int)$value : (string)$value,
                'label' => $label,
            ];
        }
        return $result;
    }

    /**
     * @notes 客户信息
     */
    private static function getCustomerInfo(int $customerId): ?array
    {
        if ($customerId <= 0) {
            return null;
        }
        $customer = Customer::where('id', $customerId)
            ->append(['customer_status_desc', 'intention_level_desc'])
            ->field('id,customer_name,customer_mobile,customer_wechat,intention_level,customer_status,advisor_id')
            ->find();
        return $customer ? $customer->toArray() : null;
    }

    /**
     * @notes 顾问信息
     */
    private static function getAdvisorInfo(int $advisorId): ?array
    {
        if ($advisorId <= 0) {
            return null;
        }
        $advisor = SalesAdvisor::where('id', $advisorId)
            ->field('id,advisor_name,mobile,wecom_userid')
            ->find();
        return $advisor ? $advisor->toArray() : null;
    }

    /**
     * @notes 规范化时间
     */
    private static function normalizeTimestamp($value): int
    {
        if ($value === null || $value === '' || $value === false) {
            return 0;
        }
        if (is_numeric($value)) {
            return max(0, (int)$value);
        }
        $timestamp = strtotime((string)$value);
        return $timestamp === false ? 0 : $timestamp;
    }

    /**
     * @notes 规范化附件
     */
    private static function normalizeAttachments($value): array
    {
        if (is_string($value)) {
            $decoded = json_decode($value, true);
            $value = is_array($decoded) ? $decoded : [];
        }
        if (!is_array($value)) {
            return [];
        }
        return array_values(array_filter($value, static fn ($item) => trim((string)$item) !== ''));
    }

    /**
     * @notes 格式化时间
     */
    private static function formatTime($value): string
    {
        $timestamp = Customer::parseTimestampValue($value);
        return $timestamp > 0 ? date('Y-m-d H:i:s', $timestamp) : '';
    }
}
