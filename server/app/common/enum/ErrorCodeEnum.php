<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 结构化错误码枚举
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\common\enum;

/**
 * 结构化错误码常量。
 *
 * 与 shared/contracts/error-codes.ts 中 ERROR_CODES 的 26 个键保持一一对应。
 */
class ErrorCodeEnum
{
    // ---------- 鉴权 ----------
    public const TOKEN_EXPIRED = 'TOKEN_EXPIRED';
    public const RISK_BLOCKED = 'RISK_BLOCKED';

    // ---------- 档期 ----------
    public const SCHEDULE_LOCKED = 'SCHEDULE_LOCKED';
    public const SCHEDULE_UNAVAILABLE = 'SCHEDULE_UNAVAILABLE';
    public const SCHEDULE_LEAD_TIME = 'SCHEDULE_LEAD_TIME';

    // ---------- 订单 ----------
    public const ORDER_STATUS_INVALID = 'ORDER_STATUS_INVALID';
    public const ORDER_NOT_PAYABLE = 'ORDER_NOT_PAYABLE';
    public const ORDER_NOT_FOUND = 'ORDER_NOT_FOUND';
    public const PAY_TIMEOUT = 'PAY_TIMEOUT';
    public const PAY_AMOUNT_MISMATCH = 'PAY_AMOUNT_MISMATCH';
    public const QUOTE_EXPIRED = 'QUOTE_EXPIRED';
    public const VOUCHER_PENDING = 'VOUCHER_PENDING';

    // ---------- 售后 ----------
    public const TICKET_STATUS_INVALID = 'TICKET_STATUS_INVALID';

    // ---------- 问卷 ----------
    public const QUESTIONNAIRE_NOT_FOUND = 'QUESTIONNAIRE_NOT_FOUND';
    public const QUESTIONNAIRE_ALREADY_SUBMITTED = 'QUESTIONNAIRE_ALREADY_SUBMITTED';
    public const QUESTIONNAIRE_CANCELLED = 'QUESTIONNAIRE_CANCELLED';
    public const QUESTIONNAIRE_EXPIRED = 'QUESTIONNAIRE_EXPIRED';
    public const QUESTIONNAIRE_REQUIRED_MISSING = 'QUESTIONNAIRE_REQUIRED_MISSING';

    // ---------- 通用 ----------
    public const IDEMPOTENT_REPLAY = 'IDEMPOTENT_REPLAY';
    public const QUOTA_EXCEEDED = 'QUOTA_EXCEEDED';
    public const FEATURE_OFF = 'FEATURE_OFF';
    public const REVIEW_MODE_ON = 'REVIEW_MODE_ON';
    public const VALIDATE_FAILED = 'VALIDATE_FAILED';
    public const V2_UNAVAILABLE = 'V2_UNAVAILABLE';
    public const NETWORK_ERROR = 'NETWORK_ERROR';
    public const UNKNOWN = 'UNKNOWN';

    /**
     * @notes 返回全部合法错误码列表
     * @return string[]
     */
    public static function all(): array
    {
        return [
            self::TOKEN_EXPIRED,
            self::RISK_BLOCKED,
            self::SCHEDULE_LOCKED,
            self::SCHEDULE_UNAVAILABLE,
            self::SCHEDULE_LEAD_TIME,
            self::ORDER_STATUS_INVALID,
            self::ORDER_NOT_PAYABLE,
            self::ORDER_NOT_FOUND,
            self::PAY_TIMEOUT,
            self::PAY_AMOUNT_MISMATCH,
            self::QUOTE_EXPIRED,
            self::VOUCHER_PENDING,
            self::TICKET_STATUS_INVALID,
            self::QUESTIONNAIRE_NOT_FOUND,
            self::QUESTIONNAIRE_ALREADY_SUBMITTED,
            self::QUESTIONNAIRE_CANCELLED,
            self::QUESTIONNAIRE_EXPIRED,
            self::QUESTIONNAIRE_REQUIRED_MISSING,
            self::IDEMPOTENT_REPLAY,
            self::QUOTA_EXCEEDED,
            self::FEATURE_OFF,
            self::REVIEW_MODE_ON,
            self::VALIDATE_FAILED,
            self::V2_UNAVAILABLE,
            self::NETWORK_ERROR,
            self::UNKNOWN,
        ];
    }

    /**
     * @notes 判断错误码是否在契约范围内
     */
    public static function isValid(?string $code): bool
    {
        if ($code === null || $code === '') {
            return false;
        }
        return in_array($code, self::all(), true);
    }
}
