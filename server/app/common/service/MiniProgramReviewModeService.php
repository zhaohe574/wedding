<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 小程序送审模式
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\common\service;

/**
 * 小程序送审模式统一判断。
 */
class MiniProgramReviewModeService
{
    public const CONFIG_GROUP = 'feature_switch';
    public const CONFIG_NAME = 'mini_program_review_mode';

    /**
     * @notes 是否开启小程序送审模式
     */
    public static function enabled(): bool
    {
        return (int) ConfigService::get(self::CONFIG_GROUP, self::CONFIG_NAME, 0) === 1;
    }

    /**
     * @notes 送审模式下统一提示文案
     */
    public static function message(string $action = '发布内容'): string
    {
        return '小程序送审模式已开启，暂不支持' . $action;
    }
}
