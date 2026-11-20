<?php
// +----------------------------------------------------------------------
// | 控制台配置
// +----------------------------------------------------------------------
return [
    // 指令定义
    'commands' => [
        'sync_admin_permissions' => 'app\common\command\SyncAdminPermissions',
        // 按 database/menu/menu_manifest.php 同步后台菜单（幂等 / 可回滚 / 不删数据）
        'admin_menu:sync' => 'app\common\command\AdminMenuSync',
        // 菜单清单一致性校验（CI 用）
        'admin_menu:check' => 'app\common\command\AdminMenuCheck',
        // 脱敏日志中的会话 token 并强制重新登录
        'purge_session_credentials' => 'app\common\command\PurgeSessionCredentials',
        // 定时任务
        'crontab' => 'app\common\command\Crontab',
        // 超时未支付订单自动取消
        'cancel_unpaid_orders' => 'app\common\command\CancelUnpaidOrders',
        // 活动报名待支付超时自动释放
        'expire_activity_registrations' => 'app\common\command\ExpireActivityRegistrations',
        // 服务人员确认超时处理
        'handle_pending_confirm_orders' => 'app\common\command\HandlePendingConfirmOrders',
        // 超过预约日期的候补自动失效
        'expire_waitlists' => 'app\common\command\ExpireWaitlists',
        // 站内提醒
        'send_station_reminders' => 'app\common\command\SendStationReminders',
        // 公众号通知派发
        'send_oa_notifications' => 'app\common\command\SendOaNotifications',
        // CRM流失预警生成与服务号推送
        'generate_loss_warnings' => 'app\common\command\GenerateLossWarnings',
        // 支付查单与超时关单
        'query_payments' => 'app\common\command\QueryPayments',
        // 退款查询
        'query_refund' => 'app\common\command\QueryRefund',
        // 重算服务人员已服务场次与评分
        'refresh_staff_service_stats' => 'app\common\command\RefreshStaffServiceStats',
        // 自动生成服务人员结算并处理微信商家转账状态
        'auto_staff_settlement' => 'app\common\command\AutoStaffSettlement',
        // 图片内容安全异步检测回调超时兜底
        'media_check_recover' => 'app\common\command\MediaCheckRecover',
    ],
];
