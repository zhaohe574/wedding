<?php
// +----------------------------------------------------------------------
// | likeadmin快速开发前后端分离管理后台（PHP版）
// +----------------------------------------------------------------------

namespace app\common\command;

use app\common\model\order\Order;
use app\common\model\order\Payment;
use app\common\model\order\RefundItem;
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
            $approved = \app\common\model\order\Refund::where('refund_status',
                \app\common\model\order\Refund::STATUS_APPROVED)->limit(100)->select();
            foreach ($approved as $refund) {
                \think\facade\Db::transaction(static function () use ($refund) {
                    OrderRefundService::executeApprovedRefund($refund);
                });
            }
            \app\common\service\StaffSettlementRepayService::processCompensationRefunds();
            \app\common\service\ActivityRegistrationService::processRefunds();
            $this->handleOrderRefunds();
            return 0;
        } catch (\Throwable $e) {
            Log::write('订单退款状态查询失败,失败原因:' . $e->getMessage());
            $output->writeln('退款查询失败：' . $e->getMessage());
            return 1;
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

        $failed = 0;
        foreach ($refundItems as $item) {
            try {
                $result = (new WeChatPayService(\app\common\enum\user\UserTerminalEnum::WECHAT_MMP))
                    ->queryRefund((string)$item['out_refund_no']);
                $result['refund_status'] = $result['status'] ?? '';
                OrderRefundService::handleWechatRefundCallback($result);
            } catch (\Throwable $e) {
                $failed++;
                Log::write('预约订单退款查询失败:' . $e->getMessage());
            }
        }
        if ($failed > 0) {
            throw new \RuntimeException('有 ' . $failed . ' 笔退款查询失败，请检查任务日志');
        }
    }





}
