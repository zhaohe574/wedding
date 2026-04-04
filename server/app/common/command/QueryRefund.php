<?php
// +----------------------------------------------------------------------
// | likeadmin快速开发前后端分离管理后台（PHP版）
// +----------------------------------------------------------------------

namespace app\common\command;

use app\common\enum\PayEnum;
use app\common\enum\RefundEnum;
use app\common\model\order\Order;
use app\common\model\order\Payment;
use app\common\model\order\RefundItem;
use app\common\model\recharge\RechargeOrder;
use app\common\model\refund\RefundLog;
use app\common\model\refund\RefundRecord;
use app\common\service\OrderRefundService;
use app\common\service\pay\WeChatPayService;
use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\facade\Log;

class QueryRefund extends Command
{
    protected function configure()
    {
        $this->setName('query_refund')
            ->setDescription('订单退款状态处理');
    }

    protected function execute(Input $input, Output $output)
    {
        try {
            $this->handleLegacyRechargeRefunds();
            $this->handleOrderRefunds();
            return true;
        } catch (\Throwable $e) {
            Log::write('订单退款状态查询失败,失败原因:' . $e->getMessage());
            return false;
        }
    }

    /**
     * @notes 兼容原充值退款查询
     * @return void
     */
    protected function handleLegacyRechargeRefunds(): void
    {
        $refundRecords = (new RefundLog())->alias('l')
            ->join('refund_record r', 'r.id = l.record_id')
            ->field([
                'l.id' => 'log_id',
                'l.sn' => 'log_sn',
                'r.id' => 'record_id',
                'r.order_id',
                'r.sn' => 'record_sn',
                'r.order_type'
            ])
            ->where(['l.refund_status' => RefundEnum::REFUND_ING])
            ->select()
            ->toArray();

        if (empty($refundRecords)) {
            return;
        }

        $rechargeRecords = array_filter($refundRecords, static function ($item) {
            return $item['order_type'] === RefundEnum::ORDER_TYPE_RECHARGE;
        });

        if (!empty($rechargeRecords)) {
            $this->handleRechargeOrder($rechargeRecords);
        }
    }

    /**
     * @notes 查询预约订单微信退款结果
     * @return void
     */
    protected function handleOrderRefunds(): void
    {
        if (!RefundItem::isTableReady()) {
            return;
        }

        $refundItems = RefundItem::alias('ri')
            ->join('refund r', 'r.id = ri.refund_id')
            ->join('payment p', 'p.id = ri.payment_id')
            ->join('order o', 'o.id = ri.order_id')
            ->field([
                'ri.id' => 'refund_item_id',
                'ri.out_refund_no',
                'ri.pay_terminal',
                'p.pay_way',
                'o.source',
            ])
            ->where('ri.refund_status', RefundItem::STATUS_PROCESSING)
            ->where('ri.pay_way', Payment::WAY_WECHAT)
            ->select()
            ->toArray();

        foreach ($refundItems as $item) {
            try {
                $terminal = (int)($item['pay_terminal'] ?? 0);
                if ($terminal <= 0) {
                    $terminal = (int)match ((int)($item['source'] ?? Order::SOURCE_MINIAPP)) {
                        Order::SOURCE_H5 => \app\common\enum\user\UserTerminalEnum::H5,
                        Order::SOURCE_ADMIN => \app\common\enum\user\UserTerminalEnum::PC,
                        default => \app\common\enum\user\UserTerminalEnum::WECHAT_MMP,
                    };
                }

                $result = (new WeChatPayService($terminal))->queryRefund((string)$item['out_refund_no']);
                $status = strtoupper((string)($result['status'] ?? ''));
                if ($status === 'SUCCESS') {
                    OrderRefundService::completeRefundItemByOutRefundNo(
                        (string)$item['out_refund_no'],
                        (string)($result['refund_id'] ?? $item['out_refund_no']),
                        json_encode($result, JSON_UNESCAPED_UNICODE)
                    );
                    continue;
                }

                if (in_array($status, ['ABNORMAL', 'CLOSED'], true)) {
                    OrderRefundService::failRefundItem((int)$item['refund_item_id'], '微信退款失败：' . $status);
                }
            } catch (\Throwable $e) {
                Log::write('预约订单退款查询失败:' . $e->getMessage());
            }
        }
    }

    /**
     * @notes 处理充值订单
     * @param array $refundRecords
     * @return void
     */
    protected function handleRechargeOrder(array $refundRecords): void
    {
        $orderIds = array_unique(array_column($refundRecords, 'order_id'));
        $orders = RechargeOrder::whereIn('id', $orderIds)->column('*', 'id');

        foreach ($refundRecords as $record) {
            if (!isset($orders[$record['order_id']])) {
                continue;
            }

            $order = $orders[$record['order_id']];
            if (!in_array($order['pay_way'], [PayEnum::WECHAT_PAY, PayEnum::ALI_PAY], true)) {
                continue;
            }

            $this->checkRechargeRefundStatus([
                'record_id' => $record['record_id'],
                'log_id' => $record['log_id'],
                'log_sn' => $record['log_sn'],
                'pay_way' => $order['pay_way'],
                'order_terminal' => $order['order_terminal'],
            ]);
        }
    }

    /**
     * @notes 校验充值退款状态
     * @param array $refundData
     * @return void
     */
    protected function checkRechargeRefundStatus(array $refundData): void
    {
        $result = null;
        if ((int)$refundData['pay_way'] === PayEnum::WECHAT_PAY) {
            $result = $this->checkWechatRefund((int)$refundData['order_terminal'], (string)$refundData['log_sn']);
        }

        if ($result === null) {
            return;
        }

        if ($result === true) {
            $this->updateRefundSuccess((int)$refundData['log_id'], (int)$refundData['record_id']);
            return;
        }

        $this->updateRefundMsg((int)$refundData['log_id'], (string)$result);
    }

    /**
     * @notes 查询微信支付退款状态
     * @param int $orderTerminal
     * @param string $refundLogSn
     * @return bool|string|null
     */
    protected function checkWechatRefund(int $orderTerminal, string $refundLogSn)
    {
        $result = (new WeChatPayService($orderTerminal))->queryRefund($refundLogSn);

        if (!empty($result['status']) && strtoupper((string)$result['status']) === 'SUCCESS') {
            return true;
        }

        if (!empty($result['code']) || !empty($result['message'])) {
            return '微信:' . ($result['code'] ?? '') . '-' . ($result['message'] ?? '');
        }

        return null;
    }

    /**
     * @notes 更新原充值退款记录为成功
     * @param int $logId
     * @param int $recordId
     * @return void
     */
    protected function updateRefundSuccess(int $logId, int $recordId): void
    {
        RefundLog::update([
            'id' => $logId,
            'refund_status' => RefundEnum::REFUND_SUCCESS,
        ]);

        RefundRecord::update([
            'id' => $recordId,
            'refund_status' => RefundEnum::REFUND_SUCCESS,
        ]);
    }

    /**
     * @notes 更新原充值退款日志信息
     * @param int $logId
     * @param string $msg
     * @return void
     */
    protected function updateRefundMsg(int $logId, string $msg): void
    {
        RefundLog::update([
            'id' => $logId,
            'refund_msg' => $msg,
        ]);
    }
}
