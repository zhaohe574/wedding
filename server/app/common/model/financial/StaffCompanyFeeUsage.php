<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 服务人员包月公司费用累计模型
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\common\model\financial;

use app\common\model\BaseModel;

/**
 * 服务人员包月公司费用累计
 */
class StaffCompanyFeeUsage extends BaseModel
{
    protected $name = 'staff_company_fee_usage';

    /**
     * @notes 按人员自然月扣减包月费用
     */
    public static function consumeMonthlyFee(
        int $staffId,
        int $ruleConfigId,
        string $serviceDate,
        float $monthlyFee,
        float $orderAmount
    ): float {
        $monthlyFee = round(max($monthlyFee, 0), 2);
        $orderAmount = round(max($orderAmount, 0), 2);
        if ($staffId <= 0 || $monthlyFee <= 0 || $orderAmount <= 0) {
            return 0.0;
        }

        $timestamp = strtotime($serviceDate) ?: time();
        $periodMonth = date('Y-m', $timestamp);

        $usage = self::where('staff_id', $staffId)
            ->where('period_month', $periodMonth)
            ->lock(true)
            ->find();

        if (!$usage) {
            $usage = new self();
            $usage->staff_id = $staffId;
            $usage->rule_config_id = $ruleConfigId;
            $usage->period_month = $periodMonth;
            $usage->monthly_fee_amount = $monthlyFee;
            $usage->used_amount = 0;
            $usage->create_time = time();
        }

        $usedAmount = round((float)$usage->used_amount, 2);
        $remaining = round(max($monthlyFee - $usedAmount, 0), 2);
        if ($remaining <= 0) {
            if ((int)$usage->rule_config_id !== $ruleConfigId || round((float)$usage->monthly_fee_amount, 2) !== $monthlyFee) {
                $usage->rule_config_id = $ruleConfigId;
                $usage->monthly_fee_amount = $monthlyFee;
                $usage->update_time = time();
                $usage->save();
            }
            return 0.0;
        }

        $deductAmount = round(min($remaining, $orderAmount), 2);
        $usage->rule_config_id = $ruleConfigId;
        $usage->monthly_fee_amount = $monthlyFee;
        $usage->used_amount = round($usedAmount + $deductAmount, 2);
        $usage->update_time = time();
        $usage->save();

        return $deductAmount;
    }
}
