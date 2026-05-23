<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 服务人员新人问卷配置模型
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\common\model\questionnaire;

use app\common\model\BaseModel;

/**
 * 服务人员新人问卷配置模型
 */
class CoupleQuestionnaire extends BaseModel
{
    protected $name = 'couple_questionnaire';

    public const STATUS_DISABLED = 0;
    public const STATUS_ENABLED = 1;

    public const PUSH_MODE_AUTO = 1;
    public const PUSH_MODE_MANUAL = 2;

    public function getDraftQuestionsAttr($value): array
    {
        if (is_array($value)) {
            return $value;
        }

        return $value ? (json_decode((string)$value, true) ?: []) : [];
    }

    public function setDraftQuestionsAttr($value): string
    {
        return is_array($value) ? json_encode($value, JSON_UNESCAPED_UNICODE) : (string)$value;
    }
}
