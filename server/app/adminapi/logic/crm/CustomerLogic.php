<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 客户管理逻辑
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\adminapi\logic\crm;

use app\common\logic\BaseLogic;
use app\common\model\crm\Customer;
use app\common\model\crm\CustomerAssignLog;
use app\common\model\crm\SalesAdvisor;
use app\common\service\CrmAdvisorScopeService;
use think\facade\Db;

/**
 * 客户管理逻辑
 * Class CustomerLogic
 * @package app\adminapi\logic\crm
 */
class CustomerLogic extends BaseLogic
{
    /**
     * @notes 客户详情
     * @param int $id
     * @return array
     */
    public static function detail(int $id, int $advisorScopeId = 0, array $adminInfo = []): array|false
    {
        $customer = Customer::find($id);
        if (!$customer) {
            return [];
        }
        if (!self::canAccessCustomer($customer, $advisorScopeId, $adminInfo)) {
            self::setError(CrmAdvisorScopeService::getAdvisorScopeAccessDeniedMessage($adminInfo));
            return false;
        }

        $data = $customer->append([
            'customer_status_desc',
            'intention_level_desc',
            'source_channel_desc',
            'gender_desc',
            'days_to_wedding',
            'days_no_follow',
        ])->toArray();
        $data['advisor'] = self::getAdvisorInfo((int)($data['advisor_id'] ?? 0));
        $data['last_assign_log'] = self::getLastAssignLog($id);
        $data['assign_time_text'] = self::formatTime($data['assign_time'] ?? 0);
        $data['first_contact_time_text'] = self::formatTime($data['first_contact_time'] ?? 0);
        $data['last_follow_time_text'] = self::formatTime($data['last_follow_time'] ?? 0);
        $data['next_follow_time_text'] = self::formatTime($data['next_follow_time'] ?? 0);
        $data['create_time_text'] = self::formatTime($data['create_time'] ?? 0);
        $data['update_time_text'] = self::formatTime($data['update_time'] ?? 0);
        return $data;
    }

    /**
     * @notes 编辑客户
     * @param array $params
     * @return bool
     */
    public static function edit(array $params, int $advisorScopeId = 0, array $adminInfo = []): bool
    {
        try {
            $customer = Customer::find((int)$params['id']);
            if (!$customer) {
                throw new \Exception('客户不存在');
            }
            if (!self::canAccessCustomer($customer, $advisorScopeId, $adminInfo)) {
                throw new \Exception(CrmAdvisorScopeService::getAdvisorScopeAccessDeniedMessage($adminInfo));
            }

            $customer->save(self::buildSaveData($params) + [
                'update_time' => time(),
            ]);

            return true;
        } catch (\Throwable $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * @notes 手动转移顾问
     * @param array $params
     * @param int $adminId
     * @return bool
     */
    public static function transferAdvisor(array $params, int $adminId, int $advisorScopeId = 0, array $adminInfo = []): bool
    {
        Db::startTrans();
        try {
            $customerId = (int)$params['customer_id'];
            $advisorId = (int)$params['advisor_id'];
            $reason = trim((string)($params['reason'] ?? ''));

            $customer = Customer::find($customerId);
            if (!$customer) {
                throw new \Exception('客户不存在');
            }
            if (!self::canAccessCustomer($customer, $advisorScopeId, $adminInfo)) {
                throw new \Exception(CrmAdvisorScopeService::getAdvisorScopeAccessDeniedMessage($adminInfo));
            }
            if ($advisorScopeId > 0) {
                throw new \Exception('顾问角色不能转移客户');
            }

            $customerStatus = (int)$customer->customer_status;
            if (!in_array($customerStatus, [Customer::STATUS_NEW, Customer::STATUS_FOLLOWING], true)) {
                throw new \Exception('仅新客户或跟进中客户允许转移顾问');
            }

            if ((int)$customer->advisor_id === $advisorId) {
                throw new \Exception('目标顾问不能与当前顾问相同');
            }

            $advisor = SalesAdvisor::find($advisorId);
            if (!$advisor) {
                throw new \Exception('目标顾问不存在');
            }
            if ((int)$advisor->status !== SalesAdvisor::STATUS_NORMAL) {
                throw new \Exception('目标顾问不是正常状态，不能承接客户');
            }
            if ((int)$advisor->current_customer_count >= (int)$advisor->max_customer_count) {
                throw new \Exception('目标顾问客户数已达上限');
            }

            if ($reason === '') {
                $reason = '后台手动转移顾问';
            }

            if (!Customer::assignAdvisor($customerId, $advisorId, $adminId, CustomerAssignLog::TYPE_TRANSFER, $reason)) {
                throw new \Exception('顾问转移失败');
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
     * @notes 可承接顾问选项
     * @return array
     */
    public static function advisorOptions(int $advisorScopeId = 0): array
    {
        $advisors = SalesAdvisor::where('status', SalesAdvisor::STATUS_NORMAL)
            ->whereRaw('current_customer_count < max_customer_count')
            ->append(['status_desc'])
            ->field('id,advisor_name,mobile,current_customer_count,max_customer_count,status,sort')
            ->order(['sort' => 'desc', 'current_customer_count' => 'asc', 'id' => 'desc'])
            ->when($advisorScopeId > 0, function ($query) use ($advisorScopeId) {
                $query->where('id', $advisorScopeId);
            })
            ->select()
            ->toArray();

        return array_map(static function ($advisor) {
            $current = (int)($advisor['current_customer_count'] ?? 0);
            $max = (int)($advisor['max_customer_count'] ?? 0);
            return [
                'id' => (int)$advisor['id'],
                'advisor_name' => (string)$advisor['advisor_name'],
                'mobile' => (string)($advisor['mobile'] ?? ''),
                'status' => (int)($advisor['status'] ?? 0),
                'status_desc' => (string)($advisor['status_desc'] ?? ''),
                'current_customer_count' => $current,
                'max_customer_count' => $max,
                'load_text' => $current . '/' . $max,
            ];
        }, $advisors);
    }

    /**
     * @notes 客户选项
     * @return array
     */
    public static function options(): array
    {
        return [
            'status_options' => self::formatOptions(Customer::getStatusOptions()),
            'intention_options' => self::formatOptions(Customer::getIntentionOptions()),
            'source_options' => self::formatOptions(Customer::getSourceOptions()),
            'gender_options' => self::formatOptions(Customer::getGenderOptions()),
        ];
    }

    /**
     * @notes 组装保存数据
     * @param array $params
     * @return array
     */
    private static function buildSaveData(array $params): array
    {
        $customerStatus = (int)($params['customer_status'] ?? Customer::STATUS_NEW);
        $lossReason = trim((string)($params['loss_reason'] ?? ''));
        $lossTime = isset($params['loss_time']) ? Customer::parseTimestampValue($params['loss_time']) : 0;
        if ($customerStatus === Customer::STATUS_LOST) {
            $lossTime = $lossTime > 0 ? $lossTime : time();
        } else {
            $lossReason = '';
        }

        return [
            'customer_name' => trim((string)$params['customer_name']),
            'customer_mobile' => trim((string)($params['customer_mobile'] ?? '')),
            'customer_wechat' => trim((string)($params['customer_wechat'] ?? '')),
            'gender' => (int)($params['gender'] ?? Customer::GENDER_UNKNOWN),
            'age' => max(0, min(120, (int)($params['age'] ?? 0))),
            'city' => trim((string)($params['city'] ?? '')),
            'district' => trim((string)($params['district'] ?? '')),
            'intention_level' => (string)($params['intention_level'] ?? Customer::INTENTION_D),
            'intention_score' => max(0, min(100, (int)($params['intention_score'] ?? 0))),
            'wedding_date' => self::normalizeDate($params['wedding_date'] ?? ''),
            'wedding_venue' => trim((string)($params['wedding_venue'] ?? '')),
            'wedding_budget' => max(0, (float)($params['wedding_budget'] ?? 0)),
            'budget_range' => trim((string)($params['budget_range'] ?? '')),
            'service_needs' => self::normalizeJsonList($params['service_needs'] ?? [], 30, 500),
            'source_channel' => (int)($params['source_channel'] ?? Customer::SOURCE_MINIAPP),
            'source_detail' => trim((string)($params['source_detail'] ?? '')),
            'tags' => self::normalizeJsonList($params['tags'] ?? [], 30, 500),
            'customer_status' => $customerStatus,
            'loss_reason' => $lossReason,
            'loss_time' => $lossTime,
            'next_follow_time' => self::normalizeTimestamp($params['next_follow_time'] ?? 0),
            'remark' => trim((string)($params['remark'] ?? '')),
        ];
    }

    /**
     * @notes 格式化选项
     * @param array $options
     * @return array
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
     * @notes 规范化日期
     * @param mixed $value
     * @return string|null
     */
    private static function normalizeDate($value): ?string
    {
        $value = trim((string)$value);
        if ($value === '') {
            return null;
        }

        $timestamp = strtotime($value);
        return $timestamp === false ? null : date('Y-m-d', $timestamp);
    }

    /**
     * @notes 规范化时间戳
     * @param mixed $value
     * @return int
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
     * @notes 规范化多选标签
     * @param mixed $value
     * @param int $itemMaxLength
     * @param int $totalMaxLength
     * @return array
     */
    private static function normalizeJsonList($value, int $itemMaxLength = 30, int $totalMaxLength = 500): array
    {
        if (is_string($value)) {
            $decoded = json_decode($value, true);
            $value = is_array($decoded) ? $decoded : array_filter(array_map('trim', explode(',', $value)));
        }

        if (!is_array($value)) {
            return [];
        }

        $result = [];
        foreach ($value as $item) {
            $text = trim((string)$item);
            if ($text === '' || in_array($text, $result, true)) {
                continue;
            }
            $result[] = function_exists('mb_substr') ? mb_substr($text, 0, $itemMaxLength) : substr($text, 0, $itemMaxLength);
            if (strlen(json_encode($result, JSON_UNESCAPED_UNICODE)) >= $totalMaxLength) {
                array_pop($result);
                break;
            }
        }

        return $result;
    }

    /**
     * @notes 获取顾问信息
     * @param int $advisorId
     * @return array|null
     */
    private static function getAdvisorInfo(int $advisorId): ?array
    {
        if ($advisorId <= 0) {
            return null;
        }

        $advisor = SalesAdvisor::where('id', $advisorId)
            ->append(['status_desc'])
            ->field('id,advisor_name,mobile,wecom_userid,status,current_customer_count,max_customer_count')
            ->find();

        return $advisor ? $advisor->toArray() : null;
    }

    /**
     * @notes 最近分配记录
     * @param int $customerId
     * @return array|null
     */
    private static function getLastAssignLog(int $customerId): ?array
    {
        $log = CustomerAssignLog::where('customer_id', $customerId)
            ->append(['assign_type_desc'])
            ->order('create_time desc')
            ->find();

        if (!$log) {
            return null;
        }

        $data = $log->toArray();
        $data['create_time_text'] = self::formatTime($data['create_time'] ?? 0);
        return $data;
    }

    /**
     * @notes 格式化时间
     * @param mixed $value
     * @return string
     */
    private static function formatTime($value): string
    {
        $timestamp = Customer::parseTimestampValue($value);
        return $timestamp > 0 ? date('Y-m-d H:i:s', $timestamp) : '';
    }

    /**
     * @notes 判断当前账号是否可访问客户
     * @param Customer $customer
     * @param int $advisorScopeId
     * @param array $adminInfo
     * @return bool
     */
    public static function canAccessCustomer(Customer $customer, int $advisorScopeId = 0, array $adminInfo = []): bool
    {
        if ($advisorScopeId === 0) {
            return true;
        }
        if ($advisorScopeId < 0) {
            return false;
        }
        return (int)$customer->advisor_id === $advisorScopeId;
    }
}
