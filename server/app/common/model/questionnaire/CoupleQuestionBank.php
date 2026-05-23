<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 新人问卷基础题库模型
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\common\model\questionnaire;

use app\common\model\BaseModel;

/**
 * 新人问卷基础题库模型
 */
class CoupleQuestionBank extends BaseModel
{
    protected $name = 'couple_question_bank';

    public const STATUS_DISABLED = 0;
    public const STATUS_ENABLED = 1;

    public function getOptionsAttr($value): array
    {
        if (is_array($value)) {
            return $value;
        }

        return $value ? (json_decode((string)$value, true) ?: []) : [];
    }

    public function setOptionsAttr($value): string
    {
        return is_array($value) ? json_encode($value, JSON_UNESCAPED_UNICODE) : (string)$value;
    }
}
