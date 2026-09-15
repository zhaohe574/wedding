<?php
declare(strict_types=1);
namespace app\common\service;

/** 邀请只作为凭据传递，日志与错误上下文不保留明文。 */
class BindingPrivacyService
{
    public static function redact($value)
    {
        if (is_array($value)) {
            foreach ($value as $key => &$item) {
                $item = in_array(strtolower((string)$key), ['invitation', 'binding_code', 'candidate_openid', 'selection_token'], true)
                    ? '[已脱敏]' : self::redact($item);
            }
            return $value;
        }
        if (!is_string($value)) return $value;
        $value = preg_replace('/((?:invitation|binding_code|candidate_openid|selection_token)(?:=|%3D))[^&\s"<>]+/i', '$1[已脱敏]', $value);
        return preg_replace('/("(?:invitation|binding_code|candidate_openid|selection_token)"\s*:\s*")[^"]*/i', '$1[已脱敏]', $value);
    }
}
