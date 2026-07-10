<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 服务人员补交平台款记录
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\common\model\financial;

use app\common\enum\PayEnum;
use app\common\model\BaseModel;
use app\common\model\order\Order;
use app\common\model\staff\Staff;

/**
 * 服务人员补交平台款记录
 */
class StaffSettlementRepay extends BaseModel
{
    protected $name = 'staff_settlement_repay';

    const PAY_STATUS_PENDING = 0;   // 待支付
    const PAY_STATUS_PAID = 1;      // 已支付
    const PAY_STATUS_FAILED = 2;    // 支付失败
    const PAY_STATUS_CANCELLED = 3; // 已取消

    const COLLECT_WAY_WECHAT = 1;  // 微信支付
    const COLLECT_WAY_ALIPAY = 2;  // 支付宝支付
    const COLLECT_WAY_OFFLINE = 3; // 后台线下补入

    public static function getPayStatusDesc($value = true)
    {
        $data = [
            self::PAY_STATUS_PENDING => '待支付',
            self::PAY_STATUS_PAID => '已补交',
            self::PAY_STATUS_FAILED => '补交失败',
            self::PAY_STATUS_CANCELLED => '已取消',
        ];
        if ($value === true) {
            return $data;
        }
        return $data[$value] ?? '';
    }

    public static function getCollectWayDesc($value = true)
    {
        $data = [
            self::COLLECT_WAY_WECHAT => '微信支付',
            self::COLLECT_WAY_ALIPAY => '支付宝支付',
            self::COLLECT_WAY_OFFLINE => '线下补入',
        ];
        if ($value === true) {
            return $data;
        }
        return $data[$value] ?? '';
    }

    public function settlement()
    {
        return $this->belongsTo(StaffSettlement::class, 'settlement_id', 'id');
    }

    public function staff()
    {
        return $this->belongsTo(Staff::class, 'staff_id', 'id')
            ->field('id, name, avatar, mobile, user_id');
    }

    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id', 'id')
            ->field('id, order_sn, total_amount, pay_amount');
    }

    public static function generateRepaySn(): string
    {
        return 'SRP' . date('YmdHis') . mt_rand(1000, 9999);
    }

    public static function createRepay(array $data): self
    {
        $repay = new self();
        $repay->repay_sn = self::generateRepaySn();
        $repay->settlement_id = (int)$data['settlement_id'];
        $repay->settlement_sn = (string)($data['settlement_sn'] ?? '');
        $repay->staff_id = (int)$data['staff_id'];
        $repay->order_id = (int)($data['order_id'] ?? 0);
        $repay->order_item_id = (int)($data['order_item_id'] ?? 0);
        $repay->amount = round((float)$data['amount'], 2);
        $repay->collect_way = (int)($data['collect_way'] ?? self::COLLECT_WAY_WECHAT);
        $repay->pay_way = (int)($data['pay_way'] ?? 0);
        $repay->pay_status = (int)($data['pay_status'] ?? self::PAY_STATUS_PENDING);
        $repay->pay_sn = (string)($data['pay_sn'] ?? '');
        $repay->transaction_id = (string)($data['transaction_id'] ?? '');
        $repay->admin_id = (int)($data['admin_id'] ?? 0);
        $repay->remark = (string)($data['remark'] ?? '');
        $repay->create_time = time();
        $repay->update_time = time();
        $repay->save();
        return $repay;
    }

    public function markPaid(string $transactionId = '', array $callbackData = []): bool
    {
        if ((int)$this->pay_status === self::PAY_STATUS_PAID) {
            return true;
        }

        $this->pay_status = self::PAY_STATUS_PAID;
        $this->transaction_id = $transactionId;
        $this->pay_time = time();
        $this->callback_time = time();
        if ($callbackData) {
            $this->callback_data = json_encode($callbackData, JSON_UNESCAPED_UNICODE);
        }
        $this->fail_reason = '';
        $this->update_time = time();
        return $this->save();
    }

    public function markFailed(string $reason): bool
    {
        if ((int)$this->pay_status === self::PAY_STATUS_PAID) {
            return true;
        }
        $this->pay_status = self::PAY_STATUS_FAILED;
        $this->fail_reason = mb_substr($reason, 0, 255);
        $this->update_time = time();
        return $this->save();
    }

    public static function collectWayFromPayWay(int $payWay): int
    {
        return match ($payWay) {
            PayEnum::WECHAT_PAY => self::COLLECT_WAY_WECHAT,
            PayEnum::ALI_PAY => self::COLLECT_WAY_ALIPAY,
            default => 0,
        };
    }
}
