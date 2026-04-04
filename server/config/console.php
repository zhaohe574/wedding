<?php
// +----------------------------------------------------------------------
// | 控制台配置
// +----------------------------------------------------------------------
return [
    // 指令定义
    'commands' => [
        // 定时任务
        'crontab' => 'app\common\command\Crontab',
        // 超时未支付订单自动取消
        'cancel_unpaid_orders' => 'app\common\command\CancelUnpaidOrders',
        // 服务人员确认超时处理
        'handle_pending_confirm_orders' => 'app\common\command\HandlePendingConfirmOrders',
        // 超过预约日期的候补自动失效
        'expire_waitlists' => 'app\common\command\ExpireWaitlists',
        // 站内提醒
        'send_station_reminders' => 'app\common\command\SendStationReminders',
        // 退款查询
        'query_refund' => 'app\common\command\QueryRefund',
    ],
];
