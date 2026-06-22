<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 活动报名支付模型
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\common\model\dynamic;

use app\common\model\BaseModel;

/**
 * 活动报名支付模型
 */
class ActivityPayment extends BaseModel
{
    protected $name = 'activity_payment';

    const WAY_BALANCE = 1;
    const WAY_WECHAT = 2;
    const WAY_ALIPAY = 3;
    const WAY_OFFLINE = 4;

    const STATUS_PENDING = 0;
    const STATUS_PAID = 1;
    const STATUS_REFUNDED = 2;
    const STATUS_FAILED = 3;
    const STATUS_EXCEPTION = 4;

    public static function generatePaymentSn(): string
    {
        return 'AP' . date('YmdHis') . str_pad((string)mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
    }

    public static function getPayWayText(int $payWay): string
    {
        return [
            self::WAY_WECHAT => '微信支付',
            self::WAY_ALIPAY => '支付宝',
            self::WAY_BALANCE => '余额支付',
            self::WAY_OFFLINE => '线下支付',
        ][$payWay] ?? '未知';
    }

    public static function getStatusText(int $status): string
    {
        return [
            self::STATUS_PENDING => '待支付',
            self::STATUS_PAID => '已支付',
            self::STATUS_REFUNDED => '已退款',
            self::STATUS_FAILED => '支付失败',
            self::STATUS_EXCEPTION => '异常支付',
        ][$status] ?? '未知';
    }

    public function registration()
    {
        return $this->belongsTo(ActivityRegistration::class, 'registration_id', 'id');
    }
}
