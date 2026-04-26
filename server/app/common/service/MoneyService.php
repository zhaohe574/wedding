<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 金额处理服务
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\common\service;

/**
 * 金额处理服务
 */
class MoneyService
{
    /**
     * @notes 元转分，使用十进制字符串处理，避免浮点截断少一分
     * @param int|float|string $amount
     * @return int
     */
    public static function yuanToFen($amount): int
    {
        $value = self::normalizeDecimalString($amount);
        if ($value === '') {
            throw new \InvalidArgumentException('金额格式错误');
        }

        if (str_starts_with($value, '-')) {
            throw new \InvalidArgumentException('金额不能为负数');
        }

        [$yuan, $decimal] = array_pad(explode('.', $value, 2), 2, '');
        $yuan = ltrim($yuan, '+');
        $yuan = ltrim($yuan, '0');
        $yuan = $yuan === '' ? '0' : $yuan;

        $decimal = preg_replace('/\D/', '', $decimal) ?? '';
        $decimal = str_pad($decimal, 3, '0');
        $fen = ((int)$yuan * 100) + (int)substr($decimal, 0, 2);
        if ((int)$decimal[2] >= 5) {
            $fen++;
        }

        return $fen;
    }

    /**
     * @notes 规范金额字符串
     * @param mixed $amount
     * @return string
     */
    protected static function normalizeDecimalString($amount): string
    {
        if (is_int($amount)) {
            return (string)$amount;
        }

        if (is_float($amount)) {
            $amount = rtrim(rtrim(sprintf('%.6F', $amount), '0'), '.');
        }

        $value = trim((string)$amount);
        if ($value === '' || !preg_match('/^[+-]?\d+(?:\.\d+)?$/', $value)) {
            return '';
        }

        return $value;
    }
}
