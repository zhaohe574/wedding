<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 流失预警列表
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\adminapi\lists\crm;

use app\adminapi\lists\BaseAdminDataLists;
use app\common\lists\ListsSearchInterface;
use app\common\model\crm\Customer;
use app\common\model\crm\CustomerLossWarning;
use app\common\model\crm\SalesAdvisor;
use app\common\model\notification\Notification;
use app\common\model\wechat\OaNotificationLog;

/**
 * 流失预警列表
 */
class LossWarningLists extends BaseAdminDataLists implements ListsSearchInterface
{
    /**
     * @notes 设置搜索条件
     */
    public function setSearch(): array
    {
        return [
            '=' => ['customer_id', 'advisor_id', 'warning_type', 'warning_level', 'warning_status'],
        ];
    }

    /**
     * @notes 获取列表
     */
    public function lists(): array
    {
        $advisorScopeId = $this->getCrmAdvisorScopeId();
        if ($advisorScopeId < 0) {
            return [];
        }

        $query = CustomerLossWarning::where($this->searchWhere)
            ->append(['warning_type_desc', 'warning_level_desc', 'warning_status_desc']);
        if ($advisorScopeId > 0) {
            $query->where('advisor_id', $advisorScopeId);
        }
        $this->applyKeyword($query);

        $list = $query->order(['warning_status' => 'asc', 'warning_level' => 'desc', 'id' => 'desc'])
            ->limit($this->limitOffset, $this->limitLength)
            ->select()
            ->toArray();

        $customerMap = $this->getCustomerMap(array_column($list, 'customer_id'));
        $advisorMap = $this->getAdvisorMap(array_column($list, 'advisor_id'));
        $pushStatusMap = $this->getPushStatusMap(array_column($list, 'id'));
        foreach ($list as &$item) {
            $warningId = (int)($item['id'] ?? 0);
            $customerId = (int)($item['customer_id'] ?? 0);
            $advisorId = (int)($item['advisor_id'] ?? 0);
            $pushInfo = $pushStatusMap[$warningId] ?? [
                'push_status' => 'none',
                'push_status_desc' => '未推送',
                'push_time_text' => '',
                'push_error_msg' => '',
            ];
            $item['customer'] = $customerMap[$customerId] ?? null;
            $item['advisor'] = $advisorMap[$advisorId] ?? null;
            $item['push_status'] = $pushInfo['push_status'];
            $item['push_status_desc'] = $pushInfo['push_status_desc'];
            $item['push_time_text'] = $pushInfo['push_time_text'];
            $item['push_error_msg'] = $pushInfo['push_error_msg'];
            $item['handle_time_text'] = $this->formatTime($item['handle_time'] ?? 0);
            $item['create_time_text'] = $this->formatTime($item['create_time'] ?? 0);
            $item['update_time_text'] = $this->formatTime($item['update_time'] ?? 0);
        }
        unset($item);

        return $list;
    }

    /**
     * @notes 获取数量
     */
    public function count(): int
    {
        $advisorScopeId = $this->getCrmAdvisorScopeId();
        if ($advisorScopeId < 0) {
            return 0;
        }

        $query = CustomerLossWarning::where($this->searchWhere);
        if ($advisorScopeId > 0) {
            $query->where('advisor_id', $advisorScopeId);
        }
        $this->applyKeyword($query);
        return $query->count();
    }

    /**
     * @notes 关键词搜索
     */
    private function applyKeyword($query): void
    {
        $keyword = trim((string)($this->params['keyword'] ?? ''));
        if ($keyword === '') {
            return;
        }

        $customerIds = Customer::whereLike('customer_name', '%' . $keyword . '%')
            ->whereOr('customer_mobile', 'like', '%' . $keyword . '%')
            ->column('id');
        $query->where(function ($subQuery) use ($keyword, $customerIds) {
            $subQuery->whereLike('warning_reason', '%' . $keyword . '%')
                ->whereOr('handle_remark', 'like', '%' . $keyword . '%');
            if (!empty($customerIds)) {
                $subQuery->whereOr('customer_id', 'in', $customerIds);
            }
        });
    }

    /**
     * @notes 客户映射
     */
    private function getCustomerMap(array $customerIds): array
    {
        $customerIds = array_values(array_unique(array_filter(array_map('intval', $customerIds))));
        if (empty($customerIds)) {
            return [];
        }

        $customers = Customer::whereIn('id', $customerIds)
            ->append(['customer_status_desc', 'intention_level_desc'])
            ->field('id,customer_name,customer_mobile,customer_wechat,intention_level,customer_status,advisor_id,last_follow_time,next_follow_time')
            ->select()
            ->toArray();

        $map = [];
        foreach ($customers as $customer) {
            $customer['last_follow_time_text'] = $this->formatTime($customer['last_follow_time'] ?? 0);
            $customer['next_follow_time_text'] = $this->formatTime($customer['next_follow_time'] ?? 0);
            $map[(int)$customer['id']] = $customer;
        }
        return $map;
    }

    /**
     * @notes 顾问映射
     */
    private function getAdvisorMap(array $advisorIds): array
    {
        $advisorIds = array_values(array_unique(array_filter(array_map('intval', $advisorIds))));
        if (empty($advisorIds)) {
            return [];
        }

        $advisors = SalesAdvisor::whereIn('id', $advisorIds)
            ->field('id,advisor_name,mobile')
            ->select()
            ->toArray();

        $map = [];
        foreach ($advisors as $advisor) {
            $map[(int)$advisor['id']] = $advisor;
        }
        return $map;
    }

    /**
     * @notes 批量获取各预警最近一次站内/服务号推送状态
     */
    private function getPushStatusMap(array $warningIds): array
    {
        $warningIds = array_values(array_unique(array_filter(array_map('intval', $warningIds))));
        if (empty($warningIds)) {
            return [];
        }

        $stationMap = [];
        try {
            $stationRows = Notification::where('business_type', 'crm_warning')
                ->whereIn('business_id', $warningIds)
                ->field('business_id, create_time')
                ->order('id desc')
                ->select()
                ->toArray();
            foreach ($stationRows as $row) {
                $bid = (int)($row['business_id'] ?? 0);
                if ($bid > 0 && !isset($stationMap[$bid])) {
                    $stationMap[$bid] = (int)($row['create_time'] ?? 0);
                }
            }
        } catch (\Throwable $e) {
        }

        $oaMap = [];
        try {
            $oaRows = OaNotificationLog::where('business_type', 'crm_warning')
                ->whereIn('business_id', $warningIds)
                ->field('business_id, send_status, send_time, create_time, error_msg')
                ->order('id desc')
                ->select()
                ->toArray();
            foreach ($oaRows as $row) {
                $bid = (int)($row['business_id'] ?? 0);
                if ($bid > 0 && !isset($oaMap[$bid])) {
                    $oaMap[$bid] = $row;
                }
            }
        } catch (\Throwable $e) {
        }

        $result = [];
        foreach ($warningIds as $wid) {
            $stationTime = $stationMap[$wid] ?? 0;
            $oa = $oaMap[$wid] ?? null;
            if (!$stationTime && !$oa) {
                continue;
            }

            $oaStatus = $oa ? (int)($oa['send_status'] ?? -1) : -1;
            $oaTime = $oa ? (int)(($oa['send_time'] ?? 0) ?: ($oa['create_time'] ?? 0)) : 0;
            $oaError = trim((string)($oa['error_msg'] ?? ''));

            if ($oaStatus === OaNotificationLog::STATUS_SUCCESS) {
                $status = 'oa_sent';
                $desc = '服务号+站内已达';
            } elseif ($oaStatus === OaNotificationLog::STATUS_PENDING || $oaStatus === OaNotificationLog::STATUS_SENDING) {
                $status = 'queued';
                $desc = $stationTime > 0 ? '站内已达 · 服务号待派发' : '队列待派发';
            } elseif ($stationTime > 0) {
                $status = 'station_only';
                $desc = '仅站内已达';
            } else {
                $status = 'failed';
                $desc = '推送未达';
            }

            $result[$wid] = [
                'push_status' => $status,
                'push_status_desc' => $desc,
                'push_time_text' => $this->formatTime(max($stationTime, $oaTime)),
                'push_error_msg' => $oaStatus === OaNotificationLog::STATUS_SUCCESS ? '' : $oaError,
            ];
        }

        return $result;
    }

    /**
     * @notes 格式化时间
     */
    private function formatTime($value): string
    {
        $timestamp = Customer::parseTimestampValue($value);
        return $timestamp > 0 ? date('Y-m-d H:i:s', $timestamp) : '';
    }
}
