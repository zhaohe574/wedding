<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 订单新人问卷任务模型
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\common\model\questionnaire;

use app\common\model\BaseModel;
use app\common\model\order\Order;
use app\common\model\staff\Staff;
use app\common\model\user\User;

/**
 * 订单新人问卷任务模型
 */
class CoupleQuestionnaireTask extends BaseModel
{
    protected $name = 'couple_questionnaire_task';

    public const STATUS_PENDING = 0;
    public const STATUS_SUBMITTED = 1;
    public const STATUS_CANCELLED = 2;

    public const SEND_STATUS_PENDING = 0;
    public const SEND_STATUS_SENT = 1;

    public static function generateTaskSn(): string
    {
        return 'NQ' . date('YmdHis') . str_pad((string)mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
    }

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

    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id', 'id');
    }

    public function staff()
    {
        return $this->belongsTo(Staff::class, 'staff_id', 'id')->field('id,name,avatar,mobile');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id')->field('id,nickname,avatar,mobile');
    }
}
