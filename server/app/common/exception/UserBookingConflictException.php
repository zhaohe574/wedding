<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 用户重复预约冲突异常
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\common\exception;

/**
 * 同一用户在同一自然日对同一服务人员的重复/重叠预约冲突。
 */
class UserBookingConflictException extends \RuntimeException
{
    /**
     * @notes 已有预约冲突
     * @param string $date
     * @param string $orderSn
     * @param string $statusLabel
     * @param bool $sameSlot 是否为同一具体时段冲突
     * @return self
     */
    public static function duplicate(
        string $date,
        string $orderSn,
        string $statusLabel,
        bool $sameSlot = false
    ): self {
        $scope = $sameSlot ? '的同一时段' : '当天';
        $orderLabel = $orderSn !== '' ? '（订单号 ' . $orderSn . '）' : '';

        return new self(sprintf(
            '您在 %s %s已预约过该服务人员%s，状态：%s，不能重复预约；请先取消原订单或更换日期/人员',
            $date,
            $scope,
            $orderLabel,
            $statusLabel
        ));
    }

    /**
     * @notes 同一请求内重复选择
     * @param string $staffName
     * @param string $date
     * @return self
     */
    public static function duplicatedInPayload(string $staffName, string $date): self
    {
        $staffLabel = $staffName !== '' ? '服务人员「' . $staffName . '」' : '同一服务人员';

        return new self(sprintf(
            '同一订单不能重复选择%s在 %s 的档期，请调整服务人员或日期',
            $staffLabel,
            $date
        ));
    }

    /**
     * @notes 并发提交未拿到互斥锁
     * @return self
     */
    public static function submitting(): self
    {
        return new self('预约正在提交中，请勿重复操作');
    }
}
