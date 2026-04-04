<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 订阅消息发送日志模型
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\common\model\subscribe;

use app\common\model\BaseModel;

/**
 * 订阅消息发送日志模型
 * Class SubscribeMessageLog
 * @package app\common\model\subscribe
 */
class SubscribeMessageLog extends BaseModel
{
    protected $name = 'subscribe_message_log';

    // 发送状态
    const SEND_STATUS_PENDING = 0;   // 待发送
    const SEND_STATUS_SUCCESS = 1;   // 发送成功
    const SEND_STATUS_FAILED = 2;    // 发送失败

    // 业务类型
    const BIZ_TYPE_ORDER = 'order';
    const BIZ_TYPE_SCHEDULE = 'schedule';
    const BIZ_TYPE_REFUND = 'refund';
    const BIZ_TYPE_CALLBACK = 'callback';
    const BIZ_TYPE_TICKET = 'ticket';
    const BIZ_TYPE_CHANGE = 'change';

    /**
     * @notes 内容JSON自动转换
     * @param $value
     * @return array
     */
    public function getContentAttr($value): array
    {
        return $value ? json_decode($value, true) : [];
    }

    /**
     * @notes 内容存储自动转JSON
     * @param $value
     * @return string
     */
    public function setContentAttr($value): string
    {
        return is_array($value) ? json_encode($value, JSON_UNESCAPED_UNICODE) : $value;
    }

    /**
     * @notes 发送状态描述获取器
     * @param $value
     * @param $data
     * @return string
     */
    public function getSendStatusDescAttr($value, $data): string
    {
        $map = [
            self::SEND_STATUS_PENDING => '待发送',
            self::SEND_STATUS_SUCCESS => '发送成功',
            self::SEND_STATUS_FAILED => '发送失败',
        ];
        return $map[$data['send_status']] ?? '未知';
    }

    /**
     * @notes 创建发送日志
     * @param int $userId
     * @param string $openid
     * @param string $templateId
     * @param string $scene
     * @param string $businessType
     * @param int $businessId
     * @param array $content
     * @param string $page
     * @return SubscribeMessageLog
     */
    public static function createLog(
        int $userId,
        string $openid,
        string $templateId,
        string $scene,
        string $businessType,
        int $businessId,
        array $content,
        string $page = ''
    ): SubscribeMessageLog {
        return self::create([
            'user_id' => $userId,
            'openid' => $openid,
            'template_id' => $templateId,
            'scene' => $scene,
            'business_type' => $businessType,
            'business_id' => $businessId,
            'content' => $content,
            'page' => $page,
            'miniprogram_state' => 'formal',
            'send_status' => self::SEND_STATUS_PENDING,
            'create_time' => time(),
        ]);
    }

    /**
     * @notes 更新发送结果
     * @param int $logId
     * @param bool $success
     * @param string $errorCode
     * @param string $errorMsg
     * @param string $requestId
     * @return bool
     */
    public static function updateSendResult(
        int $logId,
        bool $success,
        string $errorCode = '',
        string $errorMsg = '',
        string $requestId = ''
    ): bool {
        return self::where('id', $logId)->update([
            'send_status' => $success ? self::SEND_STATUS_SUCCESS : self::SEND_STATUS_FAILED,
            'error_code' => $errorCode,
            'error_msg' => $errorMsg,
            'request_id' => $requestId,
            'send_time' => time(),
        ]) > 0;
    }

    /**
     * @notes 获取发送统计
     * @param string $startDate
     * @param string $endDate
     * @return array
     */
    public static function getSendStatistics(string $startDate = '', string $endDate = ''): array
    {
        $query = self::field('send_status, COUNT(*) as count');

        if ($startDate) {
            $query->where('create_time', '>=', strtotime($startDate));
        }
        if ($endDate) {
            $query->where('create_time', '<', strtotime($endDate) + 86400);
        }

        $result = $query->group('send_status')->select()->toArray();

        $stats = [
            'total' => 0,
            'pending' => 0,
            'success' => 0,
            'failed' => 0,
            'success_rate' => 0,
        ];

        foreach ($result as $item) {
            $stats['total'] += $item['count'];
            switch ($item['send_status']) {
                case self::SEND_STATUS_PENDING:
                    $stats['pending'] = $item['count'];
                    break;
                case self::SEND_STATUS_SUCCESS:
                    $stats['success'] = $item['count'];
                    break;
                case self::SEND_STATUS_FAILED:
                    $stats['failed'] = $item['count'];
                    break;
            }
        }

        // 计算成功率(排除待发送)
        $processed = $stats['success'] + $stats['failed'];
        $stats['success_rate'] = $processed > 0 ? round($stats['success'] / $processed * 100, 2) : 0;

        return $stats;
    }

    /**
     * @notes 获取场景统计
     * @param string $startDate
     * @param string $endDate
     * @return array
     */
    public static function getSceneStatistics(string $startDate = '', string $endDate = ''): array
    {
        $query = self::field('scene, send_status, COUNT(*) as count');

        if ($startDate) {
            $query->where('create_time', '>=', strtotime($startDate));
        }
        if ($endDate) {
            $query->where('create_time', '<', strtotime($endDate) + 86400);
        }

        $result = $query->group('scene, send_status')->select()->toArray();

        $stats = [];
        foreach ($result as $item) {
            $scene = $item['scene'];
            if (!isset($stats[$scene])) {
                $stats[$scene] = [
                    'scene' => $scene,
                    'total' => 0,
                    'success' => 0,
                    'failed' => 0,
                ];
            }
            $stats[$scene]['total'] += $item['count'];
            if ($item['send_status'] == self::SEND_STATUS_SUCCESS) {
                $stats[$scene]['success'] = $item['count'];
            } elseif ($item['send_status'] == self::SEND_STATUS_FAILED) {
                $stats[$scene]['failed'] = $item['count'];
            }
        }

        return array_values($stats);
    }

    /**
     * @notes 获取发送趋势
     * @param int $days
     * @return array
     */
    public static function getSendTrend(int $days = 7): array
    {
        $startTime = strtotime("-{$days} days", strtotime(date('Y-m-d')));

        $result = self::field('FROM_UNIXTIME(create_time, "%Y-%m-%d") as date, send_status, COUNT(*) as count')
            ->where('create_time', '>=', $startTime)
            ->group('date, send_status')
            ->order('date', 'asc')
            ->select()
            ->toArray();

        $trend = [];
        for ($i = $days - 1; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime("-{$i} days"));
            $trend[$date] = [
                'date' => $date,
                'total' => 0,
                'success' => 0,
                'failed' => 0,
            ];
        }

        foreach ($result as $item) {
            if (isset($trend[$item['date']])) {
                $trend[$item['date']]['total'] += $item['count'];
                if ($item['send_status'] == self::SEND_STATUS_SUCCESS) {
                    $trend[$item['date']]['success'] = $item['count'];
                } elseif ($item['send_status'] == self::SEND_STATUS_FAILED) {
                    $trend[$item['date']]['failed'] = $item['count'];
                }
            }
        }

        return array_values($trend);
    }

    /**
     * @notes 获取用户发送记录
     * @param int $userId
     * @param int $pageSize
     * @return array
     */
    public static function getUserLogs(int $userId, int $pageSize = 20): array
    {
        return self::where('user_id', $userId)
            ->order('create_time', 'desc')
            ->paginate($pageSize)
            ->toArray();
    }

    /**
     * @notes 获取失败记录列表
     * @param int $pageSize
     * @return array
     */
    public static function getFailedLogs(int $pageSize = 50): array
    {
        return self::where('send_status', self::SEND_STATUS_FAILED)
            ->order('create_time', 'desc')
            ->paginate($pageSize)
            ->toArray();
    }

    /**
     * @notes 重试发送
     * @param int $logId
     * @return bool
     */
    public static function markForRetry(int $logId): bool
    {
        return self::where('id', $logId)
            ->where('send_status', self::SEND_STATUS_FAILED)
            ->update([
                'send_status' => self::SEND_STATUS_PENDING,
                'error_code' => '',
                'error_msg' => '',
            ]) > 0;
    }
}
