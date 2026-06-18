<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 活动报名退款模型
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\common\model\dynamic;

use app\common\model\BaseModel;

/**
 * 活动报名退款模型
 */
class ActivityRefund extends BaseModel
{
    protected $name = 'activity_refund';

    const STATUS_PENDING = 0;
    const STATUS_APPROVED = 1;
    const STATUS_PROCESSING = 2;
    const STATUS_COMPLETED = 3;
    const STATUS_REJECTED = 4;
    const STATUS_FAILED = 5;

    public static function generateRefundSn(): string
    {
        return 'AR' . date('YmdHis') . str_pad((string)mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
    }

    public static function getStatusText(int $status): string
    {
        return [
            self::STATUS_PENDING => '待审核',
            self::STATUS_APPROVED => '审核通过',
            self::STATUS_PROCESSING => '退款处理中',
            self::STATUS_COMPLETED => '已退款',
            self::STATUS_REJECTED => '已拒绝',
            self::STATUS_FAILED => '退款失败',
        ][$status] ?? '未知';
    }

    public function registration()
    {
        return $this->belongsTo(ActivityRegistration::class, 'registration_id', 'id');
    }

    public function payment()
    {
        return $this->belongsTo(ActivityPayment::class, 'payment_id', 'id');
    }
}
