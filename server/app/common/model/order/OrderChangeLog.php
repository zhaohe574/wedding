<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 订单变更日志模型
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\common\model\order;

use app\common\model\BaseModel;

/**
 * 订单变更日志模型
 * 记录所有变更、转让、暂停操作的详细日志
 * Class OrderChangeLog
 * @package app\common\model\order
 */
class OrderChangeLog extends BaseModel
{
    protected $name = 'order_change_log';

    // 关联类型
    const RELATED_TYPE_CHANGE = 1;      // 变更
    const RELATED_TYPE_TRANSFER = 2;    // 转让
    const RELATED_TYPE_PAUSE = 3;       // 暂停

    // 操作者类型
    const OPERATOR_USER = 1;            // 用户
    const OPERATOR_ADMIN = 2;           // 管理员
    const OPERATOR_SYSTEM = 3;          // 系统

    /**
     * @notes 关联订单
     * @return \think\model\relation\BelongsTo
     */
    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id', 'id');
    }

    /**
     * @notes 关联类型描述获取器
     * @param $value
     * @param $data
     * @return string
     */
    public function getRelatedTypeDescAttr($value, $data): string
    {
        $map = [
            self::RELATED_TYPE_CHANGE => '变更',
            self::RELATED_TYPE_TRANSFER => '转让',
            self::RELATED_TYPE_PAUSE => '暂停',
        ];
        return $map[$data['related_type']] ?? '未知';
    }

    /**
     * @notes 操作者类型描述获取器
     * @param $value
     * @param $data
     * @return string
     */
    public function getOperatorTypeDescAttr($value, $data): string
    {
        $map = [
            self::OPERATOR_USER => '用户',
            self::OPERATOR_ADMIN => '管理员',
            self::OPERATOR_SYSTEM => '系统',
        ];
        return $map[$data['operator_type']] ?? '未知';
    }

    /**
     * @notes 操作动作描述获取器
     * @param $value
     * @param $data
     * @return string
     */
    public function getActionDescAttr($value, $data): string
    {
        $map = [
            'apply' => '申请',
            'audit' => '审核',
            'execute' => '执行',
            'cancel' => '取消',
            'accept' => '接收',
            'complete' => '完成',
            'resume' => '恢复',
            'reject' => '拒绝',
        ];
        return $map[$data['action']] ?? $data['action'];
    }

    /**
     * @notes 变更前数据获取器
     * @param $value
     * @return array
     */
    public function getBeforeDataAttr($value): array
    {
        return $value ? json_decode($value, true) : [];
    }

    /**
     * @notes 变更前数据设置器
     * @param $value
     * @return string
     */
    public function setBeforeDataAttr($value): string
    {
        return is_array($value) ? json_encode($value, JSON_UNESCAPED_UNICODE) : $value;
    }

    /**
     * @notes 变更后数据获取器
     * @param $value
     * @return array
     */
    public function getAfterDataAttr($value): array
    {
        return $value ? json_decode($value, true) : [];
    }

    /**
     * @notes 变更后数据设置器
     * @param $value
     * @return string
     */
    public function setAfterDataAttr($value): string
    {
        return is_array($value) ? json_encode($value, JSON_UNESCAPED_UNICODE) : $value;
    }

    /**
     * @notes 添加日志
     * @param int $orderId 订单ID
     * @param int $relatedType 关联类型
     * @param int $relatedId 关联记录ID
     * @param int $operatorType 操作者类型
     * @param int $operatorId 操作者ID
     * @param string $action 操作动作
     * @param int $beforeStatus 操作前状态
     * @param int $afterStatus 操作后状态
     * @param string $content 日志内容
     * @param array $beforeData 变更前数据
     * @param array $afterData 变更后数据
     * @return OrderChangeLog|null
     */
    public static function addLog(
        int $orderId,
        int $relatedType,
        int $relatedId,
        int $operatorType,
        int $operatorId,
        string $action,
        int $beforeStatus = 0,
        int $afterStatus = 0,
        string $content = '',
        array $beforeData = [],
        array $afterData = []
    ): ?OrderChangeLog {
        try {
            // 获取操作者名称
            $operatorName = '';
            if ($operatorType == self::OPERATOR_USER && $operatorId > 0) {
                $user = \app\common\model\user\User::find($operatorId);
                $operatorName = $user->nickname ?? $user->mobile ?? '';
            } elseif ($operatorType == self::OPERATOR_ADMIN && $operatorId > 0) {
                $admin = \app\common\model\auth\Admin::find($operatorId);
                $operatorName = $admin->name ?? '';
            } elseif ($operatorType == self::OPERATOR_SYSTEM) {
                $operatorName = '系统';
            }

            // 获取IP和UserAgent
            $ip = request()->ip();
            $userAgent = request()->header('user-agent', '');

            return self::create([
                'order_id' => $orderId,
                'related_type' => $relatedType,
                'related_id' => $relatedId,
                'operator_type' => $operatorType,
                'operator_id' => $operatorId,
                'operator_name' => $operatorName,
                'action' => $action,
                'before_status' => $beforeStatus,
                'after_status' => $afterStatus,
                'before_data' => $beforeData,
                'after_data' => $afterData,
                'content' => $content,
                'ip' => $ip,
                'user_agent' => substr($userAgent, 0, 500),
                'create_time' => time(),
            ]);
        } catch (\Exception $e) {
            // 日志记录失败不影响业务
            return null;
        }
    }

    /**
     * @notes 获取订单的所有变更日志
     * @param int $orderId
     * @param int $relatedType 关联类型，0表示全部
     * @return array
     */
    public static function getOrderLogs(int $orderId, int $relatedType = 0): array
    {
        $query = self::where('order_id', $orderId);

        if ($relatedType > 0) {
            $query->where('related_type', $relatedType);
        }

        return $query->order('create_time', 'desc')
            ->select()
            ->toArray();
    }

    /**
     * @notes 获取关联记录的日志
     * @param int $relatedType
     * @param int $relatedId
     * @return array
     */
    public static function getRelatedLogs(int $relatedType, int $relatedId): array
    {
        return self::where('related_type', $relatedType)
            ->where('related_id', $relatedId)
            ->order('create_time', 'asc')
            ->select()
            ->toArray();
    }

    /**
     * @notes 获取关联类型选项
     * @return array
     */
    public static function getRelatedTypeOptions(): array
    {
        return [
            ['value' => self::RELATED_TYPE_CHANGE, 'label' => '变更'],
            ['value' => self::RELATED_TYPE_TRANSFER, 'label' => '转让'],
            ['value' => self::RELATED_TYPE_PAUSE, 'label' => '暂停'],
        ];
    }
}
