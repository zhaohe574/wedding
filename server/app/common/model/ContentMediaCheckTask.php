<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 微信图片内容安全异步检测任务
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\common\model;

/**
 * 图片内容安全异步检测任务
 *
 * 一行对应一张图片：微信 media_check_async 一次只接受一个 media_url，
 * 每次提交返回一个 trace_id，回调里用 trace_id 反查这张表，
 * 再把结论落到 status / suggest / label 上。
 *
 * Class ContentMediaCheckTask
 * @package app\common\model
 */
class ContentMediaCheckTask extends BaseModel
{
    protected $name = 'content_media_check_task';

    // 任务状态
    public const STATUS_WAITING = 0;   // 已提交，等待微信回调
    public const STATUS_PASS = 1;      // 检测通过
    public const STATUS_RISKY = 2;     // 检测命中违规
    public const STATUS_FAILED = 3;    // 提交失败（未拿到 trace_id）
    public const STATUS_TIMEOUT = 4;   // 超时未回调

    // 业务类型
    public const BIZ_REVIEW = 'review';
    public const BIZ_REVIEW_REPLY = 'review_reply';
    public const BIZ_TICKET = 'ticket';
    public const BIZ_COMPLAINT = 'complaint';

    /**
     * @notes 状态描述
     * @param mixed $value
     * @return array|string
     */
    public static function getStatusDesc($value = true)
    {
        $data = [
            self::STATUS_WAITING => '待回调',
            self::STATUS_PASS => '通过',
            self::STATUS_RISKY => '命中违规',
            self::STATUS_FAILED => '提交失败',
            self::STATUS_TIMEOUT => '超时未回调',
        ];
        if ($value === true) {
            return $data;
        }
        return $data[$value] ?? '';
    }

    /**
     * @notes 业务类型描述
     * @param mixed $value
     * @return array|string
     */
    public static function getBizTypeDesc($value = true)
    {
        $data = [
            self::BIZ_REVIEW => '评价',
            self::BIZ_REVIEW_REPLY => '追评',
            self::BIZ_TICKET => '售后工单',
            self::BIZ_COMPLAINT => '投诉',
        ];
        if ($value === true) {
            return $data;
        }
        return $data[$value] ?? '';
    }

    /**
     * @notes 图片地址获取器
     * @param mixed $value
     * @return array
     */
    public function getMediaUrlsAttr($value): array
    {
        if (is_array($value)) {
            return $value;
        }
        if (empty($value)) {
            return [];
        }
        $decoded = json_decode((string)$value, true);
        return is_array($decoded) ? $decoded : [];
    }

    /**
     * @notes 图片地址设置器
     * @param mixed $value
     * @return string
     */
    public function setMediaUrlsAttr($value): string
    {
        if (!is_array($value)) {
            return '[]';
        }
        return json_encode(array_values($value), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    /**
     * @notes 微信回调原文获取器
     * @param mixed $value
     * @return array
     */
    public function getCallbackDetailAttr($value): array
    {
        if (is_array($value)) {
            return $value;
        }
        if (empty($value)) {
            return [];
        }
        $decoded = json_decode((string)$value, true);
        return is_array($decoded) ? $decoded : [];
    }

    /**
     * @notes 微信回调原文设置器
     * @param mixed $value
     * @return string
     */
    public function setCallbackDetailAttr($value): string
    {
        if (!is_array($value)) {
            return '';
        }
        return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
}
