<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 活动报名模型
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\common\model\dynamic;

use app\common\model\BaseModel;
use app\common\model\user\User;

/**
 * 活动报名模型
 */
class ActivityRegistration extends BaseModel
{
    protected $name = 'activity_registration';

    const STATUS_PENDING_PAY = 0;      // 待支付
    const STATUS_REGISTERED = 1;       // 已报名
    const STATUS_CANCEL_APPLY = 2;     // 取消审核中
    const STATUS_CANCELLED = 3;        // 已取消
    const STATUS_REFUNDING = 4;        // 退款处理中
    const STATUS_REFUND_FAILED = 5;    // 退款失败

    const PAY_STATUS_UNPAID = 0;
    const PAY_STATUS_PAID = 1;
    const PAY_STATUS_REFUNDED = 2;
    const PAY_STATUS_FAILED = 3;

    const CANCEL_STATUS_NONE = 0;
    const CANCEL_STATUS_PENDING = 1;
    const CANCEL_STATUS_APPROVED = 2;
    const CANCEL_STATUS_REJECTED = 3;

    public static function getActiveStatuses(): array
    {
        return [
            self::STATUS_PENDING_PAY,
            self::STATUS_REGISTERED,
            self::STATUS_CANCEL_APPLY,
            self::STATUS_REFUNDING,
            self::STATUS_REFUND_FAILED,
        ];
    }

    public static function getStatusText(int $status): string
    {
        return [
            self::STATUS_PENDING_PAY => '待支付',
            self::STATUS_REGISTERED => '已报名',
            self::STATUS_CANCEL_APPLY => '取消审核中',
            self::STATUS_CANCELLED => '已取消',
            self::STATUS_REFUNDING => '退款处理中',
            self::STATUS_REFUND_FAILED => '退款失败',
        ][$status] ?? '未知';
    }

    public static function getPayStatusText(int $status): string
    {
        return [
            self::PAY_STATUS_UNPAID => '待支付',
            self::PAY_STATUS_PAID => '已支付',
            self::PAY_STATUS_REFUNDED => '已退款',
            self::PAY_STATUS_FAILED => '支付失败',
        ][$status] ?? '未知';
    }

    public static function getCancelStatusText(int $status): string
    {
        return [
            self::CANCEL_STATUS_NONE => '未申请',
            self::CANCEL_STATUS_PENDING => '待审核',
            self::CANCEL_STATUS_APPROVED => '已通过',
            self::CANCEL_STATUS_REJECTED => '已拒绝',
        ][$status] ?? '未知';
    }

    public function dynamic()
    {
        return $this->belongsTo(Dynamic::class, 'dynamic_id', 'id');
    }

    public function ticket()
    {
        return $this->belongsTo(ActivityTicket::class, 'ticket_id', 'id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id')->field('id,nickname,avatar,mobile');
    }
}
