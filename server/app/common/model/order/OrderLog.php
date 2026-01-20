<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 订单日志模型
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\common\model\order;

use app\common\model\BaseModel;

/**
 * 订单日志模型
 * Class OrderLog
 * @package app\common\model\order
 */
class OrderLog extends BaseModel
{
    protected $name = 'order_log';

    // 操作者类型
    const OPERATOR_USER = 1;    // 用户
    const OPERATOR_ADMIN = 2;   // 管理员
    const OPERATOR_SYSTEM = 3;  // 系统

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
     * @notes 添加订单日志
     * @param int $orderId
     * @param int $operatorType
     * @param int $operatorId
     * @param string $action
     * @param int $beforeStatus
     * @param int $afterStatus
     * @param string $content
     * @return OrderLog
     */
    public static function addLog(int $orderId, int $operatorType, int $operatorId, string $action, int $beforeStatus, int $afterStatus, string $content = ''): OrderLog
    {
        return self::create([
            'order_id' => $orderId,
            'operator_type' => $operatorType,
            'operator_id' => $operatorId,
            'action' => $action,
            'before_status' => $beforeStatus,
            'after_status' => $afterStatus,
            'content' => $content,
            'ip' => request()->ip(),
            'create_time' => time(),
        ]);
    }

    /**
     * @notes 获取订单日志
     * @param int $orderId
     * @return array
     */
    public static function getOrderLogs(int $orderId): array
    {
        return self::where('order_id', $orderId)
            ->order('create_time', 'desc')
            ->select()
            ->toArray();
    }

    /**
     * @notes 获取操作动作描述
     * @param string $action
     * @return string
     */
    public static function getActionDesc(string $action): string
    {
        $map = [
            'create' => '创建订单',
            'edit' => '编辑订单',
            'pay' => '支付订单',
            'pay_deposit' => '支付定金',
            'pay_balance' => '支付尾款',
            'cancel' => '取消订单',
            'start_service' => '开始服务',
            'complete' => '完成订单',
            'refund_apply' => '申请退款',
            'refund_audit' => '审核退款',
            'refund_success' => '退款成功',
            'remark' => '添加备注',
        ];
        return $map[$action] ?? $action;
    }
}
