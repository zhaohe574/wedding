<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 退款子项模型
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\common\model\order;

use app\common\model\BaseModel;
use think\facade\Db;

/**
 * 退款子项模型
 * Class RefundItem
 * @package app\common\model\order
 */
class RefundItem extends BaseModel
{
    protected $name = 'refund_item';

    // 子项退款状态
    const STATUS_PENDING = 0;      // 待执行
    const STATUS_PROCESSING = 1;   // 处理中
    const STATUS_COMPLETED = 2;    // 已完成
    const STATUS_FAILED = 3;       // 失败

    /**
     * @notes 关联退款单
     * @return \think\model\relation\BelongsTo
     */
    public function refund()
    {
        return $this->belongsTo(Refund::class, 'refund_id', 'id');
    }

    /**
     * @notes 关联支付流水
     * @return \think\model\relation\BelongsTo
     */
    public function payment()
    {
        return $this->belongsTo(Payment::class, 'payment_id', 'id');
    }

    /**
     * @notes 退款子项表是否已就绪
     * @return bool
     */
    public static function isTableReady(): bool
    {
        static $ready = null;

        if ($ready !== null) {
            return $ready;
        }

        try {
            $table = addslashes((string)(new static())->getTable());
            $ready = !empty(Db::query("SHOW TABLES LIKE '{$table}'"));
        } catch (\Throwable $e) {
            $ready = false;
        }

        return $ready;
    }

    /**
     * @notes 子项退款状态描述
     * @param mixed $value
     * @param array $data
     * @return string
     */
    public function getRefundStatusDescAttr($value, $data): string
    {
        $map = [
            self::STATUS_PENDING => '待执行',
            self::STATUS_PROCESSING => '处理中',
            self::STATUS_COMPLETED => '已完成',
            self::STATUS_FAILED => '失败',
        ];

        return $map[(int)($data['refund_status'] ?? self::STATUS_PENDING)] ?? '未知';
    }

    /**
     * @notes 支付方式描述
     * @param mixed $value
     * @param array $data
     * @return string
     */
    public function getPayWayDescAttr($value, $data): string
    {
        $map = [
            Payment::WAY_WECHAT => '微信支付',
            Payment::WAY_ALIPAY => '支付宝',
            Payment::WAY_BALANCE => '余额支付',
            Payment::WAY_OFFLINE => '线下支付',
        ];

        return $map[(int)($data['pay_way'] ?? 0)] ?? '未知';
    }

    /**
     * @notes 生成子项退款单号
     * @param string $refundSn
     * @param int $index
     * @return string
     */
    public static function buildOutRefundNo(string $refundSn, int $index): string
    {
        return sprintf('%s-%02d', $refundSn, max($index, 1));
    }
}
