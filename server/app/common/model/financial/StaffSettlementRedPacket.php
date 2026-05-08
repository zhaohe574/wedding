<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 服务人员结算红包明细
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\common\model\financial;

use app\common\model\BaseModel;
use app\common\model\order\Order;
use app\common\model\order\OrderItem;
use app\common\model\staff\Staff;

/**
 * 服务人员结算红包明细模型
 * Class StaffSettlementRedPacket
 * @package app\common\model\financial
 */
class StaffSettlementRedPacket extends BaseModel
{
    protected $name = 'staff_settlement_red_packet';

    const STATUS_PENDING = 0;    // 待发放
    const STATUS_SENDING = 1;    // 发放中
    const STATUS_SENT = 2;       // 已发放待领取
    const STATUS_RECEIVED = 3;   // 已领取
    const STATUS_REFUNDED = 4;   // 已退款
    const STATUS_FAILED = 5;     // 发放失败

    /**
     * @notes 状态描述
     */
    public static function getStatusDesc($value = true)
    {
        $data = [
            self::STATUS_PENDING => '待发放',
            self::STATUS_SENDING => '发放中',
            self::STATUS_SENT => '已发放待领取',
            self::STATUS_RECEIVED => '已领取',
            self::STATUS_REFUNDED => '已退款',
            self::STATUS_FAILED => '发放失败',
        ];
        if ($value === true) {
            return $data;
        }
        return $data[$value] ?? '';
    }

    /**
     * @notes 关联结算记录
     */
    public function settlement()
    {
        return $this->belongsTo(StaffSettlement::class, 'settlement_id', 'id');
    }

    /**
     * @notes 关联服务人员
     */
    public function staff()
    {
        return $this->belongsTo(Staff::class, 'staff_id', 'id')
            ->field('id, name, avatar, mobile, user_id');
    }

    /**
     * @notes 关联订单
     */
    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id', 'id')
            ->field('id, order_sn, total_amount, pay_amount');
    }

    /**
     * @notes 关联订单项
     */
    public function orderItem()
    {
        return $this->belongsTo(OrderItem::class, 'order_item_id', 'id')
            ->field('id, staff_name, package_name, subtotal');
    }

    /**
     * @notes 创建红包明细
     */
    public static function createPacket(StaffSettlement $settlement, array $data): self
    {
        $packet = new self();
        $packet->settlement_id = (int)$settlement->id;
        $packet->settlement_sn = (string)$settlement->settlement_sn;
        $packet->staff_id = (int)$settlement->staff_id;
        $packet->order_id = (int)$settlement->order_id;
        $packet->order_item_id = (int)$settlement->order_item_id;
        $packet->mch_billno = (string)$data['mch_billno'];
        $packet->mch_id = (string)($data['mch_id'] ?? '');
        $packet->wxappid = (string)($data['wxappid'] ?? '');
        $packet->openid = (string)$data['openid'];
        $packet->amount = round((float)$data['amount'], 2);
        $packet->amount_fen = (int)$data['amount_fen'];
        $packet->total_num = (int)($data['total_num'] ?? 1);
        $packet->send_name = (string)($data['send_name'] ?? '');
        $packet->wishing = (string)($data['wishing'] ?? '');
        $packet->act_name = (string)($data['act_name'] ?? '');
        $packet->remark = (string)($data['remark'] ?? '');
        $packet->scene_id = (string)($data['scene_id'] ?? '');
        $packet->status = self::STATUS_PENDING;
        $packet->save();
        return $packet;
    }

    /**
     * @notes 是否可发起重试
     */
    public function canRetry(): bool
    {
        return in_array((int)$this->status, [self::STATUS_PENDING, self::STATUS_FAILED], true);
    }

    /**
     * @notes 标记发放中
     */
    public function markSending(array $requestData = []): bool
    {
        $this->status = self::STATUS_SENDING;
        $this->request_data = self::encodePayload($requestData);
        $this->send_time = time();
        $this->update_time = time();
        return $this->save();
    }

    /**
     * @notes 标记已发放待领取
     */
    public function markSent(array $responseData = []): bool
    {
        $this->status = self::STATUS_SENT;
        $this->wx_status = (string)($responseData['status'] ?? 'SENT');
        $this->wx_hb_id = (string)($responseData['send_listid'] ?? ($responseData['wx_hb_id'] ?? ''));
        $this->receive_package = (string)($responseData['package'] ?? ($responseData['receive_package'] ?? ''));
        $this->response_data = self::encodePayload($responseData);
        $this->fail_reason = '';
        $this->send_time = (int)$this->send_time > 0 ? (int)$this->send_time : time();
        $this->update_time = time();
        return $this->save();
    }

    /**
     * @notes 标记已领取
     */
    public function markReceived(array $queryData = []): bool
    {
        $this->status = self::STATUS_RECEIVED;
        $this->wx_status = 'RECEIVED';
        $this->query_response = self::encodePayload($queryData);
        $this->last_query_time = time();
        $this->receive_time = (int)$this->receive_time > 0 ? (int)$this->receive_time : time();
        $this->fail_reason = '';
        $this->update_time = time();
        return $this->save();
    }

    /**
     * @notes 标记已退款
     */
    public function markRefunded(array $queryData = []): bool
    {
        $this->status = self::STATUS_REFUNDED;
        $this->wx_status = (string)($queryData['status'] ?? 'REFUND');
        $this->query_response = self::encodePayload($queryData);
        $this->last_query_time = time();
        $this->refund_time = (int)$this->refund_time > 0 ? (int)$this->refund_time : time();
        $this->fail_reason = (string)($queryData['reason'] ?? '红包已退款');
        $this->update_time = time();
        return $this->save();
    }

    /**
     * @notes 标记失败
     */
    public function markFailed(string $reason, array $responseData = []): bool
    {
        $this->status = self::STATUS_FAILED;
        $this->wx_status = (string)($responseData['status'] ?? '');
        $this->response_data = $responseData ? self::encodePayload($responseData) : (string)$this->response_data;
        $this->fail_reason = mb_substr($reason, 0, 255);
        $this->retry_count = (int)$this->retry_count + 1;
        $this->update_time = time();
        return $this->save();
    }

    /**
     * @notes 更新查询结果
     */
    public function saveQueryResult(array $queryData): bool
    {
        $this->wx_status = (string)($queryData['status'] ?? $this->wx_status);
        $this->query_response = self::encodePayload($queryData);
        $this->last_query_time = time();
        $this->update_time = time();
        return $this->save();
    }

    /**
     * @notes JSON编码
     */
    public static function encodePayload(array $payload): string
    {
        if (!$payload) {
            return '';
        }
        return json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '';
    }
}
