<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 流失预警逻辑
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\adminapi\logic\crm;

use app\common\logic\BaseLogic;
use app\common\model\crm\Customer;
use app\common\model\crm\CustomerLossWarning;
use app\common\model\crm\SalesAdvisor;
use app\common\service\CrmAdvisorScopeService;
use app\common\service\InternalNotificationService;
use think\facade\Db;
use think\facade\Log;

/**
 * 流失预警逻辑
 */
class LossWarningLogic extends BaseLogic
{
    /**
     * @notes 预警详情
     */
    public static function detail(int $id, int $advisorScopeId = 0, array $adminInfo = []): array|false
    {
        $warning = CustomerLossWarning::where('id', $id)
            ->append(['warning_type_desc', 'warning_level_desc', 'warning_status_desc'])
            ->find();
        if (!$warning) {
            return [];
        }
        if (!FollowRecordLogic::canAccessAdvisor((int)$warning->advisor_id, $advisorScopeId, $adminInfo)) {
            self::setError(CrmAdvisorScopeService::getAdvisorScopeAccessDeniedMessage($adminInfo));
            return false;
        }

        return self::formatWarning($warning->toArray());
    }

    /**
     * @notes 处理预警
     */
    public static function handle(array $params, int $advisorScopeId = 0, array $adminInfo = []): bool
    {
        return self::changeStatus((int)$params['id'], CustomerLossWarning::STATUS_HANDLED, (string)($params['remark'] ?? ''), $advisorScopeId, $adminInfo);
    }

    /**
     * @notes 忽略预警
     */
    public static function ignore(array $params, int $advisorScopeId = 0, array $adminInfo = []): bool
    {
        return self::changeStatus((int)$params['id'], CustomerLossWarning::STATUS_IGNORED, (string)($params['remark'] ?? ''), $advisorScopeId, $adminInfo);
    }

    /**
     * @notes 手动生成预警
     */
    public static function generate(int $advisorScopeId = 0): array|false
    {
        if ($advisorScopeId < 0) {
            self::setError('当前后台账号未绑定顾问资料，请联系管理员在顾问管理中绑定');
            return false;
        }

        try {
            if ($advisorScopeId === 0) {
                $count = CustomerLossWarning::generateNoFollowWarnings(7, 14, 30);
            } else {
                $count = self::generateNoFollowWarningsForAdvisor($advisorScopeId, 7, 14, 30);
            }
            return ['count' => $count];
        } catch (\Throwable $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * @notes 推送预警
     */
    public static function push(array $params = [], int $advisorScopeId = 0, array $adminInfo = [], bool $fromCrontab = false): array|false
    {
        if ($advisorScopeId < 0) {
            self::setError(CrmAdvisorScopeService::getAdvisorScopeAccessDeniedMessage($adminInfo));
            return false;
        }

        $warningId = (int)($params['id'] ?? 0);
        $query = CustomerLossWarning::where('warning_status', CustomerLossWarning::STATUS_PENDING);
        if ($warningId > 0) {
            $query->where('id', $warningId);
        }
        if ($advisorScopeId > 0) {
            $query->where('advisor_id', $advisorScopeId);
        }

        $warnings = $query->append(['warning_type_desc', 'warning_level_desc'])
            ->order(['warning_level' => 'desc', 'id' => 'desc'])
            ->limit($warningId > 0 ? 1 : 50)
            ->select()
            ->toArray();

        $result = [
            'success' => 0,
            'oa_sent' => 0,
            'station_only' => 0,
            'skipped' => 0,
            'failed' => 0,
            'errors' => [],
        ];

        $customerIds = array_values(array_unique(array_filter(array_column($warnings, 'customer_id'))));
        $advisorIds = array_values(array_unique(array_filter(array_column($warnings, 'advisor_id'))));
        $customerNames = $customerIds ? Customer::whereIn('id', $customerIds)->column('customer_name', 'id') : [];
        $advisorMap = $advisorIds ? SalesAdvisor::whereIn('id', $advisorIds)->column('status,admin_id', 'id') : [];

        $adminIds = array_values(array_unique(array_filter(array_map(
            static fn ($a) => (int)($a['admin_id'] ?? 0),
            $advisorMap
        ))));
        $adminMap = $adminIds
            ? Db::name('admin')->whereIn('id', $adminIds)->column('disable,user_id', 'id')
            : [];

        foreach ($warnings as $warning) {
            $customerName = (string)($customerNames[(int)($warning['customer_id'] ?? 0)] ?? '未命名客户');
            $warning['customer_name'] = $customerName;
            $advId = (int)($warning['advisor_id'] ?? 0);

            if ($advId <= 0) {
                $result['failed']++;
                $result['errors'][] = ['id' => (int)$warning['id'], 'message' => "客户「{$customerName}」未分配顾问"];
                continue;
            }
            $advInfo = $advisorMap[$advId] ?? null;
            if (!$advInfo) {
                $result['failed']++;
                $result['errors'][] = ['id' => (int)$warning['id'], 'message' => "客户「{$customerName}」关联顾问不存在"];
                continue;
            }
            if ((int)($advInfo['status'] ?? 0) === SalesAdvisor::STATUS_LEAVE) {
                $result['failed']++;
                $result['errors'][] = ['id' => (int)$warning['id'], 'message' => "客户「{$customerName}」顾问已离职，请先交接"];
                continue;
            }
            $adminId = (int)($advInfo['admin_id'] ?? 0);
            if ($adminId <= 0) {
                $result['failed']++;
                $result['errors'][] = ['id' => (int)$warning['id'], 'message' => "客户「{$customerName}」顾问未绑定后台账号"];
                continue;
            }
            $adminRow = $adminMap[$adminId] ?? null;
            if (!$adminRow || (int)($adminRow['disable'] ?? 0) === 1) {
                $result['failed']++;
                $result['errors'][] = ['id' => (int)$warning['id'], 'message' => "客户「{$customerName}」顾问后台账号已停用"];
                continue;
            }
            if ((int)($adminRow['user_id'] ?? 0) <= 0) {
                $result['failed']++;
                $result['errors'][] = ['id' => (int)$warning['id'], 'message' => "客户「{$customerName}」顾问账号未绑定微信/前台账号"];
                continue;
            }

            $dispatchRes = self::pushOne($warning, $fromCrontab);
            if (!empty($dispatchRes['duplicate'])) {
                $result['skipped']++;
                $result['errors'][] = ['id' => (int)$warning['id'], 'message' => "客户「{$customerName}」" . ($dispatchRes['oa_msg'] ?: '刚刚已推送，请勿重复')];
                continue;
            }
            if (empty($dispatchRes['ok'])) {
                $result['failed']++;
                $result['errors'][] = [
                    'id' => (int)$warning['id'],
                    'message' => "客户「{$customerName}」" . ($dispatchRes['error'] ?: '推送失败'),
                ];
                continue;
            }

            $result['success']++;
            if (!empty($dispatchRes['oa_sent'])) {
                $result['oa_sent']++;
            } elseif (!$fromCrontab) {
                $result['station_only']++;
                if (!empty($dispatchRes['oa_msg']) && $dispatchRes['oa_status'] !== 'queued') {
                    $result['errors'][] = [
                        'id' => (int)$warning['id'],
                        'message' => "客户「{$customerName}」站内信已发，服务号未送达：{$dispatchRes['oa_msg']}",
                    ];
                }
            }
        }

        if (empty($warnings)) {
            $result['errors'][] = ['id' => 0, 'message' => '没有待推送的预警'];
        }
        return $result;
    }

    /**
     * @notes 预警统计
     */
    public static function stats(int $advisorScopeId = 0): array
    {
        if ($advisorScopeId < 0) {
            return [
                'total_pending' => 0,
                'high_level' => 0,
                'medium_level' => 0,
                'low_level' => 0,
                'today_handled' => 0,
            ];
        }
        return CustomerLossWarning::getWarningStats($advisorScopeId);
    }

    /**
     * @notes 预警选项
     */
    public static function options(): array
    {
        return [
            'warning_type_options' => self::formatOptions(CustomerLossWarning::getTypeOptions()),
            'warning_level_options' => self::formatOptions(CustomerLossWarning::getLevelOptions()),
            'warning_status_options' => self::formatOptions(CustomerLossWarning::getStatusOptions()),
        ];
    }

    /**
     * @notes 定时任务生成并推送全部长期未跟进预警
     */
    public static function generateAndPushForCrontab(): array
    {
        $generated = CustomerLossWarning::generateNoFollowWarnings(7, 14, 30);
        $pushResult = self::push([], 0, [], true);
        return [
            'generated' => $generated,
            'pushed' => $pushResult ?: ['success' => 0, 'failed' => 0, 'errors' => [['id' => 0, 'message' => self::getError()]]],
        ];
    }

    /**
     * @notes 变更预警状态
     */
    private static function changeStatus(int $id, int $status, string $remark, int $advisorScopeId, array $adminInfo): bool
    {
        Db::startTrans();
        try {
            $warning = CustomerLossWarning::find($id);
            if (!$warning) {
                throw new \Exception('预警不存在');
            }
            if (!FollowRecordLogic::canAccessAdvisor((int)$warning->advisor_id, $advisorScopeId, $adminInfo)) {
                throw new \Exception(CrmAdvisorScopeService::getAdvisorScopeAccessDeniedMessage($adminInfo));
            }

            $data = [
                'warning_status' => $status,
                'handle_time' => time(),
                'handle_remark' => trim($remark),
                'update_time' => time(),
            ];
            if (CustomerLossWarning::where('id', $id)->update($data) === false) {
                throw new \Exception('预警状态更新失败');
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
     * @notes 生成指定顾问的长期未跟进预警
     */
    private static function generateNoFollowWarningsForAdvisor(int $advisorId, int $threshold7, int $threshold14, int $threshold30): int
    {
        $count = 0;
        $threshold = time() - ($threshold7 * 86400);
        $customers = Customer::whereIn('customer_status', [Customer::STATUS_NEW, Customer::STATUS_FOLLOWING])
            ->where('advisor_id', $advisorId)
            ->where(function ($query) use ($threshold) {
                $query->where('last_follow_time', '<', $threshold)
                    ->whereOr(function ($q) use ($threshold) {
                        $q->where('last_follow_time', 0)
                            ->where('create_time', '<', $threshold);
                    });
            })
            ->order('last_follow_time asc')
            ->select()
            ->toArray();

        foreach ($customers as $customer) {
            $lastFollowTime = Customer::parseTimestampValue($customer['last_follow_time'] ?? null);
            $createTime = Customer::parseTimestampValue($customer['create_time'] ?? null);
            $baseTime = $lastFollowTime > 0 ? $lastFollowTime : $createTime;
            if ($baseTime <= 0) {
                continue;
            }

            $daysNoFollow = (int)floor(max(0, time() - $baseTime) / 86400);
            if ($daysNoFollow < $threshold7) {
                continue;
            }
            if ($daysNoFollow >= $threshold30) {
                $level = CustomerLossWarning::LEVEL_HIGH;
            } elseif ($daysNoFollow >= $threshold14) {
                $level = CustomerLossWarning::LEVEL_MEDIUM;
            } else {
                $level = CustomerLossWarning::LEVEL_LOW;
            }

            $result = CustomerLossWarning::createWarning(
                (int)$customer['id'],
                (int)$customer['advisor_id'],
                CustomerLossWarning::TYPE_NO_FOLLOW,
                $level,
                "客户已{$daysNoFollow}天未跟进",
                $daysNoFollow
            );
            if ($result) {
                $count++;
            }
        }
        return $count;
    }

    /**
     * @notes 推送单条预警
     */

    /**
     * @notes 格式化预警详情
     */
    private static function pushOne(array $warning, bool $fromCrontab = false): array
    {
        $customerName = trim((string)($warning['customer_name'] ?? ''));
        if ($customerName === '') {
            $customerName = (string)Customer::where('id', (int)($warning['customer_id'] ?? 0))->value('customer_name');
        }
        $levelDesc = trim((string)($warning['warning_level_desc'] ?? ''));
        if ($levelDesc === '') {
            $levelDesc = CustomerLossWarning::getLevelOptions()[(int)($warning['warning_level'] ?? 1)] ?? '提示';
        }
        $reason = trim((string)($warning['warning_reason'] ?? '长时间未跟进'));
        $cleanReason = preg_replace('/^客户/', '', $reason) ?: $reason;
        $content = sprintf('【%s风险】%s%s，请及时跟进或转派。', $levelDesc, $customerName ?: '未命名客户', $cleanReason);

        $instanceKey = $fromCrontab
            ? sprintf('crm_warning:%d:%s', (int)$warning['id'], date('Ymd'))
            : sprintf('crm_warning_manual:%d:%s', (int)$warning['id'], date('YmdHi'));

        return InternalNotificationService::advisorDispatch(
            (int)($warning['advisor_id'] ?? 0),
            '客户流失预警',
            $content,
            'crm_warning',
            (int)$warning['id'],
            ['instance' => $instanceKey],
            !$fromCrontab
        );
    }

    private static function formatWarning(array $warning): array
    {
        $customer = Customer::where('id', (int)$warning['customer_id'])
            ->append(['customer_status_desc', 'intention_level_desc'])
            ->field('id,customer_name,customer_mobile,customer_wechat,intention_level,customer_status,advisor_id,last_follow_time,next_follow_time')
            ->find();
        $advisor = SalesAdvisor::where('id', (int)$warning['advisor_id'])
            ->field('id,advisor_name,mobile')
            ->find();

        $warning['customer'] = $customer ? $customer->toArray() : null;
        $warning['advisor'] = $advisor ? $advisor->toArray() : null;
        $warning['handle_time_text'] = self::formatTime($warning['handle_time'] ?? 0);
        $warning['create_time_text'] = self::formatTime($warning['create_time'] ?? 0);
        $warning['update_time_text'] = self::formatTime($warning['update_time'] ?? 0);
        return $warning;
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
     * @notes 格式化时间
     */
    private static function formatTime($value): string
    {
        $timestamp = Customer::parseTimestampValue($value);
        return $timestamp > 0 ? date('Y-m-d H:i:s', $timestamp) : '';
    }
}
