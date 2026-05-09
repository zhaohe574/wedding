<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 服务人员结算转账明细
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\common\model\financial;

use app\common\model\BaseModel;
use app\common\model\order\Order;
use app\common\model\order\OrderItem;
use app\common\model\staff\Staff;

/**
 * 服务人员结算转账明细模型
 * Class StaffSettlementTransfer
 * @package app\common\model\financial
 */
class StaffSettlementTransfer extends BaseModel
{
    protected $name = 'staff_settlement_transfer';

    const STATUS_PENDING = 0;             // 待发起
    const STATUS_PROCESSING = 1;          // 转账中
    const STATUS_WAIT_USER_CONFIRM = 2;   // 待用户确认
    const STATUS_SUCCESS = 3;             // 已到账
    const STATUS_FAILED = 4;              // 转账失败
    const STATUS_CLOSED = 5;              // 已关闭

    /**
     * @notes 状态描述
     */
    public static function getStatusDesc($value = true)
    {
        $data = [
            self::STATUS_PENDING => '待发起',
            self::STATUS_PROCESSING => '转账中',
            self::STATUS_WAIT_USER_CONFIRM => '待确认收款',
            self::STATUS_SUCCESS => '已到账',
            self::STATUS_FAILED => '转账失败',
            self::STATUS_CLOSED => '已关闭',
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
     * @notes 创建转账明细
     */
    public static function createTransfer(StaffSettlement $settlement, array $data): self
    {
        $transfer = new self();
        $transfer->settlement_id = (int)$settlement->id;
        $transfer->settlement_sn = (string)$settlement->settlement_sn;
        $transfer->staff_id = (int)$settlement->staff_id;
        $transfer->order_id = (int)$settlement->order_id;
        $transfer->order_item_id = (int)$settlement->order_item_id;
        $transfer->out_bill_no = (string)$data['out_bill_no'];
        $transfer->transfer_bill_no = (string)($data['transfer_bill_no'] ?? '');
        $transfer->mch_id = (string)($data['mch_id'] ?? '');
        $transfer->appid = (string)($data['appid'] ?? '');
        $transfer->openid = (string)$data['openid'];
        $transfer->user_name = (string)($data['user_name'] ?? '');
        $transfer->amount = round((float)$data['amount'], 2);
        $transfer->amount_fen = (int)$data['amount_fen'];
        $transfer->transfer_scene_id = (string)($data['transfer_scene_id'] ?? '');
        $transfer->transfer_remark = (string)($data['transfer_remark'] ?? '');
        $transfer->user_recv_perception = (string)($data['user_recv_perception'] ?? '');
        $transfer->status = self::STATUS_PENDING;
        $transfer->save();
        return $transfer;
    }

    /**
     * @notes 是否可按原单重试
     */
    public function canRetry(): bool
    {
        return in_array((int)$this->status, [self::STATUS_PENDING, self::STATUS_FAILED], true);
    }

    /**
     * @notes 标记转账中
     */
    public function markProcessing(array $requestData = []): bool
    {
        $this->status = self::STATUS_PROCESSING;
        if ($requestData) {
            $this->request_data = self::encodePayload($requestData);
        }
        $this->wx_state = 'PROCESSING';
        $this->fail_reason = '';
        $this->send_time = (int)$this->send_time > 0 ? (int)$this->send_time : time();
        $this->update_time = time();
        return $this->save();
    }

    /**
     * @notes 按微信返回结果更新状态
     */
    public function applyWechatResult(array $responseData, bool $fromQuery = false): bool
    {
        $state = self::normalizeWechatState($responseData);
        if ($state === '') {
            $state = 'PROCESSING';
        }

        $transferBillNo = (string)($responseData['transfer_bill_no'] ?? $responseData['transferBillNo'] ?? '');
        if ($transferBillNo !== '') {
            $this->transfer_bill_no = $transferBillNo;
        }

        $packageInfo = (string)($responseData['package_info'] ?? $responseData['packageInfo'] ?? $responseData['package'] ?? '');
        if ($packageInfo !== '') {
            $this->package_info = $packageInfo;
        }

        if ($fromQuery) {
            $this->query_response = self::encodePayload($responseData);
            $this->last_query_time = time();
        } else {
            $this->response_data = self::encodePayload($responseData);
        }

        return match ($state) {
            'SUCCESS' => $this->markSuccess($responseData),
            'WAIT_USER_CONFIRM' => $this->markWaitUserConfirm($responseData),
            'FAIL', 'FAILED' => $this->markFailed(self::resolveFailReason($responseData), $responseData),
            'CANCELLED', 'CLOSED' => $this->markClosed(self::resolveFailReason($responseData) ?: '转账单已关闭', $responseData),
            default => $this->markAccepted($responseData, $state),
        };
    }

    /**
     * @notes 记录查询结果但不改变本地终态
     */
    public function saveQueryResult(array $queryData): bool
    {
        $state = self::normalizeWechatState($queryData);
        if ($state !== '') {
            $this->wx_state = $state;
        }
        $this->query_response = self::encodePayload($queryData);
        $this->last_query_time = time();
        $this->update_time = time();
        return $this->save();
    }

    /**
     * @notes 标记已受理或处理中
     */
    protected function markAccepted(array $responseData, string $state): bool
    {
        $this->status = self::STATUS_PROCESSING;
        $this->wx_state = $state;
        $this->fail_reason = '';
        $this->send_time = (int)$this->send_time > 0 ? (int)$this->send_time : time();
        $this->update_time = time();
        return $this->save();
    }

    /**
     * @notes 标记待用户确认
     */
    protected function markWaitUserConfirm(array $responseData): bool
    {
        $this->status = self::STATUS_WAIT_USER_CONFIRM;
        $this->wx_state = 'WAIT_USER_CONFIRM';
        $this->fail_reason = '';
        $this->send_time = (int)$this->send_time > 0 ? (int)$this->send_time : time();
        $this->update_time = time();
        return $this->save();
    }

    /**
     * @notes 标记已到账
     */
    protected function markSuccess(array $responseData): bool
    {
        $this->status = self::STATUS_SUCCESS;
        $this->wx_state = 'SUCCESS';
        $this->fail_reason = '';
        $this->success_time = (int)$this->success_time > 0 ? (int)$this->success_time : time();
        $this->update_time = time();
        return $this->save();
    }

    /**
     * @notes 标记失败
     */
    public function markFailed(string $reason, array $responseData = []): bool
    {
        $this->status = self::STATUS_FAILED;
        $state = self::normalizeWechatState($responseData);
        if ($state !== '') {
            $this->wx_state = $state;
        }
        if ($responseData) {
            $this->response_data = self::encodePayload($responseData);
        }
        $this->fail_reason = mb_substr($reason ?: '微信商家转账失败', 0, 255);
        $this->retry_count = (int)$this->retry_count + 1;
        $this->update_time = time();
        return $this->save();
    }

    /**
     * @notes 标记已关闭
     */
    protected function markClosed(string $reason, array $responseData = []): bool
    {
        $this->status = self::STATUS_CLOSED;
        $this->wx_state = self::normalizeWechatState($responseData) ?: 'CLOSED';
        if ($responseData) {
            $this->response_data = self::encodePayload($responseData);
        }
        $this->fail_reason = mb_substr($reason ?: '转账单已关闭', 0, 255);
        $this->close_time = (int)$this->close_time > 0 ? (int)$this->close_time : time();
        $this->update_time = time();
        return $this->save();
    }

    /**
     * @notes 解析微信转账状态
     */
    public static function normalizeWechatState(array $payload): string
    {
        return strtoupper((string)(
            $payload['state']
            ?? $payload['transfer_state']
            ?? $payload['transfer_status']
            ?? $payload['status']
            ?? $payload['wx_state']
            ?? ''
        ));
    }

    /**
     * @notes 解析失败原因
     */
    public static function resolveFailReason(array $payload): string
    {
        return (string)(
            $payload['fail_reason']
            ?? $payload['fail_message']
            ?? $payload['fail_msg']
            ?? $payload['reason']
            ?? $payload['message']
            ?? '微信商家转账失败'
        );
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
