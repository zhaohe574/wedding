<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 新人问卷答案模型
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\common\model\questionnaire;

use app\common\model\BaseModel;

/**
 * 新人问卷答案模型
 */
class CoupleQuestionnaireAnswer extends BaseModel
{
    protected $name = 'couple_questionnaire_answer';

    public function getQuestionsSnapshotAttr($value): array
    {
        if (is_array($value)) {
            return $value;
        }

        return $value ? (json_decode((string)$value, true) ?: []) : [];
    }

    public function setQuestionsSnapshotAttr($value): string
    {
        return is_array($value) ? json_encode($value, JSON_UNESCAPED_UNICODE) : (string)$value;
    }

    public function getAnswersAttr($value): array
    {
        if (is_array($value)) {
            return $value;
        }

        return $value ? (json_decode((string)$value, true) ?: []) : [];
    }

    public function setAnswersAttr($value): string
    {
        return is_array($value) ? json_encode($value, JSON_UNESCAPED_UNICODE) : (string)$value;
    }
}
