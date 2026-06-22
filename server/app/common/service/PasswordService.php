<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 密码安全服务
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\common\service;

/**
 * 后台密码统一处理。
 */
class PasswordService
{
    public const LEGACY_MD5_LENGTH = 32;

    public static function hash(string $plaintext): string
    {
        return password_hash($plaintext, PASSWORD_DEFAULT);
    }

    public static function verify(string $plaintext, string $hash, string $salt = ''): bool
    {
        if ($hash === '') {
            return false;
        }

        if (password_get_info($hash)['algo'] !== 0) {
            return password_verify($plaintext, $hash);
        }

        if (strlen($hash) === self::LEGACY_MD5_LENGTH && $salt !== '') {
            return hash_equals($hash, create_password($plaintext, $salt));
        }

        return false;
    }

    public static function needsRehash(string $hash): bool
    {
        if ($hash === '' || password_get_info($hash)['algo'] === 0) {
            return true;
        }

        return password_needs_rehash($hash, PASSWORD_DEFAULT);
    }
}
