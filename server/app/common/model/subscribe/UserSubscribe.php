<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 用户订阅记录模型
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\common\model\subscribe;

use app\common\model\BaseModel;

/**
 * 用户订阅记录模型
 * Class UserSubscribe
 * @package app\common\model\subscribe
 */
class UserSubscribe extends BaseModel
{
    protected $name = 'user_subscribe';

    // 订阅状态
    const STATUS_REJECTED = 0;    // 已拒绝
    const STATUS_ACCEPTED = 1;    // 已订阅(一次性)
    const STATUS_PERMANENT = 2;   // 永久订阅

    /**
     * @notes 状态描述获取器
     * @param $value
     * @param $data
     * @return string
     */
    public function getStatusDescAttr($value, $data): string
    {
        $map = [
            self::STATUS_REJECTED => '已拒绝',
            self::STATUS_ACCEPTED => '已订阅',
            self::STATUS_PERMANENT => '永久订阅',
        ];
        return $map[$data['status']] ?? '未知';
    }

    /**
     * @notes 记录用户订阅授权
     * @param int $userId
     * @param string $templateId
     * @param string $scene
     * @param bool $accepted
     * @return UserSubscribe
     */
    public static function recordSubscribe(int $userId, string $templateId, string $scene, bool $accepted = true): UserSubscribe
    {
        $record = self::where('user_id', $userId)
            ->where('template_id', $templateId)
            ->find();

        $time = time();

        if ($record) {
            // 更新现有记录
            if ($accepted) {
                $record->accept_count += 1;
                $record->last_accept_time = $time;
                $record->status = self::STATUS_ACCEPTED;
            } else {
                $record->reject_count += 1;
                $record->last_reject_time = $time;
                $record->status = self::STATUS_REJECTED;
            }
            $record->update_time = $time;
            $record->save();
            return $record;
        }

        // 创建新记录
        return self::create([
            'user_id' => $userId,
            'template_id' => $templateId,
            'scene' => $scene,
            'accept_count' => $accepted ? 1 : 0,
            'reject_count' => $accepted ? 0 : 1,
            'last_accept_time' => $accepted ? $time : 0,
            'last_reject_time' => $accepted ? 0 : $time,
            'status' => $accepted ? self::STATUS_ACCEPTED : self::STATUS_REJECTED,
            'create_time' => $time,
            'update_time' => $time,
        ]);
    }

    /**
     * @notes 批量记录订阅结果
     * @param int $userId
     * @param array $subscribeResults 格式: [templateId => 'accept'/'reject']
     * @param string $scene
     * @return int 处理数量
     */
    public static function batchRecordSubscribe(int $userId, array $subscribeResults, string $scene = ''): int
    {
        $count = 0;
        foreach ($subscribeResults as $templateId => $result) {
            self::recordSubscribe($userId, $templateId, $scene, $result === 'accept');
            $count++;
        }
        return $count;
    }

    /**
     * @notes 检查用户是否有订阅授权
     * @param int $userId
     * @param string $templateId
     * @return bool
     */
    public static function hasSubscription(int $userId, string $templateId): bool
    {
        $record = self::where('user_id', $userId)
            ->where('template_id', $templateId)
            ->find();

        if (!$record) {
            return false;
        }

        // 永久订阅或有可用的一次性订阅次数
        return $record->status == self::STATUS_PERMANENT || $record->accept_count > 0;
    }

    /**
     * @notes 消费一次订阅授权
     * @param int $userId
     * @param string $templateId
     * @return bool
     */
    public static function consumeSubscription(int $userId, string $templateId): bool
    {
        $record = self::where('user_id', $userId)
            ->where('template_id', $templateId)
            ->find();

        if (!$record) {
            return false;
        }

        // 永久订阅不消耗次数
        if ($record->status == self::STATUS_PERMANENT) {
            return true;
        }

        // 一次性订阅消耗次数
        if ($record->accept_count > 0) {
            $record->accept_count -= 1;
            $record->update_time = time();
            $record->save();
            return true;
        }

        return false;
    }

    /**
     * @notes 获取用户订阅状态
     * @param int $userId
     * @param array $templateIds
     * @return array
     */
    public static function getUserSubscribeStatus(int $userId, array $templateIds): array
    {
        $records = self::where('user_id', $userId)
            ->whereIn('template_id', $templateIds)
            ->select()
            ->toArray();

        $result = [];
        foreach ($records as $record) {
            $result[$record['template_id']] = [
                'status' => $record['status'],
                'accept_count' => $record['accept_count'],
                'can_send' => $record['status'] == self::STATUS_PERMANENT || $record['accept_count'] > 0,
            ];
        }

        // 未记录的模板默认为无订阅
        foreach ($templateIds as $templateId) {
            if (!isset($result[$templateId])) {
                $result[$templateId] = [
                    'status' => -1,
                    'accept_count' => 0,
                    'can_send' => false,
                ];
            }
        }

        return $result;
    }

    /**
     * @notes 获取用户所有订阅
     * @param int $userId
     * @return array
     */
    public static function getUserAllSubscriptions(int $userId): array
    {
        return self::where('user_id', $userId)
            ->order('update_time', 'desc')
            ->select()
            ->toArray();
    }

    /**
     * @notes 获取模板的订阅统计
     * @param string $templateId
     * @return array
     */
    public static function getTemplateStats(string $templateId): array
    {
        $total = self::where('template_id', $templateId)->count();
        $accepted = self::where('template_id', $templateId)
            ->whereIn('status', [self::STATUS_ACCEPTED, self::STATUS_PERMANENT])
            ->count();
        $rejected = self::where('template_id', $templateId)
            ->where('status', self::STATUS_REJECTED)
            ->count();

        return [
            'total' => $total,
            'accepted' => $accepted,
            'rejected' => $rejected,
            'accept_rate' => $total > 0 ? round($accepted / $total * 100, 2) : 0,
        ];
    }
}
