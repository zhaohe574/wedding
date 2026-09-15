<?php

declare(strict_types=1);

namespace app\common\command;

use app\common\enum\PayEnum;
use app\common\model\order\Payment;
use app\common\model\dynamic\ActivityPayment;
use app\common\model\financial\StaffSettlementRepay;
use app\common\service\pay\WeChatPayService;
use think\console\Command;
use think\console\Input;
use think\console\Output;

class QueryPayments extends Command
{
    protected function configure()
    {
        $this->setName('query_payments')->setDescription('恢复微信支付结果并关闭超时付款流水');
    }

    protected function execute(Input $input, Output $output)
    {
        foreach ([
            [Payment::class, Payment::WAY_WECHAT, [0, 3], 'payment_sn'],
            [ActivityPayment::class, ActivityPayment::WAY_WECHAT, [0, 3], 'payment_sn'],
            [StaffSettlementRepay::class, PayEnum::WECHAT_PAY, [0, 2, 3], 'pay_sn'],
        ] as [$model, $way, $statuses, $field]) {
            $payments = $model::where('pay_way', $way)->whereIn('pay_status', $statuses)
                ->where('closed_time', 0)->where('query_time', '<=', time() - 60)
                ->order('query_time asc,id asc')->limit(100)->select();
            foreach ($payments as $payment) {
                WeChatPayService::reconcilePayment($payment, $field);
            }
        }
        $output->writeln('支付结果查询完成');
        return 0;
    }
}
