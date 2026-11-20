<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 档期占用冲突异常
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\common\exception;

/**
 * 档期占用冲突。
 *
 * 只用于「已确认且已付款」订单之间的互斥：未付款订单不占用档期，
 * 因此本异常不会因为“别人下了未付款单”而抛出。
 */
class ScheduleOccupancyException extends \RuntimeException
{
    /**
     * @notes 构造带人员、日期、冲突订单号的稳定提示
     * @param string $staffName
     * @param string $date
     * @param string $orderSn
     * @param string $action
     * @return self
     */
    public static function conflict(string $staffName, string $date, string $orderSn, string $action): self
    {
        $staffLabel = $staffName !== '' ? '服务人员「' . $staffName . '」' : '该服务人员';
        $orderLabel = $orderSn !== '' ? '（订单号 ' . $orderSn . '）' : '';

        return new self(sprintf(
            '%s在 %s 已有已付款订单%s，无法%s；请先取消或退款原订单后再操作',
            $staffLabel,
            $date,
            $orderLabel,
            $action
        ));
    }
}
