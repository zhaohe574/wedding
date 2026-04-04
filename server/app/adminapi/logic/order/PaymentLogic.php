<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 支付业务逻辑
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\adminapi\logic\order;

use app\common\logic\BaseLogic;
use app\common\model\order\Payment;

/**
 * 支付业务逻辑
 * Class PaymentLogic
 * @package app\adminapi\logic\order
 */
class PaymentLogic extends BaseLogic
{
    /**
     * @notes 获取支付详情
     * @param int $id
     * @return array|null
     */
    public static function detail(int $id): ?array
    {
        $payment = Payment::with([
            'order' => function ($query) {
                $query->with(['user' => function ($q) {
                    $q->field('id, nickname, avatar, mobile');
                }]);
            }
        ])->find($id);

        if (!$payment) {
            return null;
        }

        $data = $payment->toArray();
        $data['pay_type_desc'] = $payment->pay_type_desc;
        $data['pay_way_desc'] = $payment->pay_way_desc;
        $data['pay_status_desc'] = $payment->pay_status_desc;

        return $data;
    }

    /**
     * @notes 支付统计
     * @param array $params
     * @return array
     */
    public static function statistics(array $params): array
    {
        $startDate = $params['start_date'] ?? date('Y-m-01');
        $endDate = $params['end_date'] ?? date('Y-m-d');
        
        $startTime = strtotime($startDate);
        $endTime = strtotime($endDate . ' 23:59:59');

        $query = Payment::whereBetween('create_time', [$startTime, $endTime]);

        // 总支付数
        $totalPayments = (clone $query)->count();

        // 成功支付数
        $paidPayments = (clone $query)->where('pay_status', Payment::STATUS_PAID)->count();

        // 各支付方式统计
        $wayCounts = [];
        foreach ([
            Payment::WAY_WECHAT => '微信支付',
            Payment::WAY_ALIPAY => '支付宝',
            Payment::WAY_BALANCE => '余额支付',
            Payment::WAY_OFFLINE => '线下支付',
        ] as $way => $label) {
            $wayCounts[] = [
                'way' => $way,
                'label' => $label,
                'count' => (clone $query)->where('pay_way', $way)->where('pay_status', Payment::STATUS_PAID)->count(),
                'amount' => round((clone $query)->where('pay_way', $way)->where('pay_status', Payment::STATUS_PAID)->sum('pay_amount'), 2),
            ];
        }

        // 各支付类型统计
        $typeCounts = [];
        foreach ([
            Payment::TYPE_DEPOSIT => '定金',
            Payment::TYPE_BALANCE => '尾款',
            Payment::TYPE_FULL => '全款',
        ] as $type => $label) {
            $typeCounts[] = [
                'type' => $type,
                'label' => $label,
                'count' => (clone $query)->where('pay_type', $type)->where('pay_status', Payment::STATUS_PAID)->count(),
                'amount' => round((clone $query)->where('pay_type', $type)->where('pay_status', Payment::STATUS_PAID)->sum('pay_amount'), 2),
            ];
        }

        // 总金额
        $totalAmount = (clone $query)->where('pay_status', Payment::STATUS_PAID)->sum('pay_amount');

        // 今日数据
        $todayStart = strtotime(date('Y-m-d'));
        $todayEnd = time();
        $todayPayments = Payment::whereBetween('pay_time', [$todayStart, $todayEnd])
            ->where('pay_status', Payment::STATUS_PAID)
            ->count();
        $todayAmount = Payment::whereBetween('pay_time', [$todayStart, $todayEnd])
            ->where('pay_status', Payment::STATUS_PAID)
            ->sum('pay_amount');

        return [
            'total_payments' => $totalPayments,
            'paid_payments' => $paidPayments,
            'way_counts' => $wayCounts,
            'type_counts' => $typeCounts,
            'total_amount' => round($totalAmount, 2),
            'today' => [
                'payments' => $todayPayments,
                'amount' => round($todayAmount, 2),
            ],
        ];
    }

    /**
     * @notes 获取支付类型选项
     * @return array
     */
    public static function getTypeOptions(): array
    {
        return [
            ['value' => Payment::TYPE_DEPOSIT, 'label' => '定金'],
            ['value' => Payment::TYPE_BALANCE, 'label' => '尾款'],
            ['value' => Payment::TYPE_FULL, 'label' => '全款'],
        ];
    }

    /**
     * @notes 获取支付方式选项
     * @return array
     */
    public static function getWayOptions(): array
    {
        return [
            ['value' => Payment::WAY_WECHAT, 'label' => '微信支付'],
            ['value' => Payment::WAY_ALIPAY, 'label' => '支付宝'],
            ['value' => Payment::WAY_BALANCE, 'label' => '余额支付'],
            ['value' => Payment::WAY_OFFLINE, 'label' => '线下支付'],
        ];
    }

    /**
     * @notes 获取支付状态选项
     * @return array
     */
    public static function getStatusOptions(): array
    {
        return [
            ['value' => Payment::STATUS_PENDING, 'label' => '待支付'],
            ['value' => Payment::STATUS_PAID, 'label' => '已支付'],
            ['value' => Payment::STATUS_REFUNDED, 'label' => '已退款'],
            ['value' => Payment::STATUS_FAILED, 'label' => '支付失败'],
        ];
    }
}
