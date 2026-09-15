<?php

declare(strict_types=1);

namespace app\common\service;

/** 页面辅助接口只继承明确指定的权限，不根据请求方法或接口命名自动授权。 */
class AdminPermissionService
{
    public static function dependencies(): array
    {
        return [
            'auth.admin/lists' => ['auth.admin/all'],
            'auth.admin/bindingSave' => ['auth.admin/bindingDetail', 'consumer.user/lists', 'auth.admin/all'],
            'auth.admin/add' => ['auth.role/all', 'dept.dept/all', 'dept.jobs/all'],
            'auth.admin/edit' => ['auth.role/all', 'dept.dept/all', 'dept.jobs/all'],
            'auth.role/lists' => ['auth.role/detail'],
            'auth.role/add' => ['auth.menu/all'],
            'auth.role/edit' => ['auth.menu/all'],
            'auth.menu/lists' => ['auth.menu/all'],
            'dept.dept/lists' => ['dept.dept/all'],
            'dept.jobs/lists' => ['dept.jobs/all'],
            'setting.dict.dict_type/lists' => ['setting.dict.dict_type/all', 'setting.dict.dict_type/detail'],
            'setting.dict.dict_data/lists' => ['setting.dict.dict_data/detail', 'setting.dict.dict_type/all'],
            'setting.pay.pay_config/setConfig' => ['setting.pay.pay_config/getConfig'],
            'setting.order_confirm_letter/setConfig' => ['setting.order_confirm_letter/getConfig'],
            'setting.web.web_setting/saveSiteStatistics' => ['setting.web.web_setting/setSiteStatistics'],
            'channel.official_account_menu/save' => ['channel.official_account_menu/saveAndPublish'],
            'notification.oaNotification/templateList' => ['notification.oaNotification/templateDetail'],
            'notification.oaNotification/logList' => ['notification.oaNotification/eventList'],
            'notification.oaNotification/retry' => ['notification.oaNotification/eventRetry'],
            'crm.salesAdvisor/lists' => ['crm.salesAdvisor/detail', 'crm.salesAdvisor/statusOptions'],
            'crontab.crontab/lists' => ['crontab.crontab/detail', 'crontab.crontab/expression'],
            'crontab.crontab/edit' => ['crontab.crontab/operate'],
            'tools.generator/selectTable' => ['tools.generator/dataTable'],
            'tools.generator/edit' => ['tools.generator/detail', 'tools.generator/getModels'],
            'article.article/lists' => ['article/all', 'article.articleCate/all'],
            'article.articleCate/lists' => ['article.articleCate/all'],
            'decorate.page/detail' => ['decorate.data/article', 'decorate.data/pc', 'decorate.data/activityList', 'decorate.data/noticeList', 'decorate.data/topicList'],
            'ops.category/lists' => ['ops.category/all', 'ops.category/tree', 'ops.category/detail'],
            'ops.package/lists' => ['ops.package/all', 'ops.package/detail', 'ops.category/all'],
            'ops.staff/lists' => ['ops.staff/all', 'ops.staff/detail', 'ops.staff/statistics', 'ops.staff/getAddonConfig', 'ops.staff/getPackageConfig', 'ops.staff/getBannerList', 'ops.category/all'],
            'ops.styleTag/lists' => ['ops.styleTag/all', 'ops.styleTag/detail', 'ops.styleTag/typeOptions'],
            'ops.order/lists' => ['ops.order/detail', 'ops.order/logs', 'ops.order/statistics', 'ops.order/statusOptions', 'ops.order/payWayOptions'],
            'ops.order/add' => ['ops.order/estimatePayment', 'ops.package/all', 'ops.staff/all'],
            'ops.order/addOffline' => ['ops.staff/all', 'ops.region/enabledCityOptions', 'ops.region/districtOptions'],
            'ops.order/myOrders' => ['ops.order/myOrderDetail', 'ops.order/myOrderStatistics'],
            'ops.orderChange/lists' => ['ops.orderChange/detail', 'ops.orderChange/logs', 'ops.orderChange/statistics', 'ops.orderChange/statusOptions', 'ops.orderChange/typeOptions'],
            'ops.refund/lists' => ['ops.refund/detail', 'ops.refund/statistics', 'ops.refund/statusOptions'],
            'ops.payment/lists' => ['ops.payment/detail', 'ops.payment/statistics', 'ops.payment/statusOptions', 'ops.payment/typeOptions', 'ops.payment/wayOptions'],
            'ops.schedule/lists' => ['ops.schedule/detail', 'ops.schedule/monthCalendar', 'ops.schedule/statistics', 'ops.schedule/statusOptions', 'ops.schedule/timeSlotOptions', 'ops.schedule/lockRecords', 'ops.staff/all'],
            'ops.scheduleRule/lists' => ['ops.scheduleRule/detail', 'ops.scheduleRule/globalRule', 'ops.scheduleRule/staffRule'],
            'ops.schedule/myCalendar' => ['ops.schedule/myCalendarStatistics', 'ops.schedule/statusOptions', 'ops.schedule/timeSlotOptions'],
            'ops.scheduleRule/myRules' => ['ops.scheduleRule/myRuleDetail', 'ops.scheduleRule/myRuleTemplate'],
            'ops.waitlist/lists' => ['ops.waitlist/detail', 'ops.waitlist/statistics'],
            'ops.waitlist/myWaitlist' => ['ops.waitlist/myWaitlistDetail', 'ops.waitlist/myWaitlistStatistics'],
            'ops.booking/myBookings' => ['ops.booking/myBookingDetail', 'ops.booking/myBookingStatistics'],
            'growth.dynamic/lists' => ['growth.dynamic/statistics', 'growth.dynamic/statusOptions', 'growth.dynamic/typeOptions', 'growth.dynamic/commentLists'],
            'growth.dynamic/myDynamics' => ['growth.dynamic/myDynamicDetail', 'growth.dynamic/myDynamicTypeOptions', 'growth.dynamic/myDynamicStatusOptions'],
            'growth.dynamicComment/reviewList' => ['growth.dynamicComment/detail'],
            'growth.review/lists' => ['growth.review/detail', 'growth.review/hotTags', 'growth.review/scoreDistribution', 'growth.review/staffRanking', 'growth.review/statistics'],
            'growth.reviewTag/lists' => ['growth.reviewTag/detail', 'growth.reviewTag/grouped', 'growth.reviewTag/typeOptions'],
            'growth.sensitiveWord/lists' => ['growth.sensitiveWord/detail', 'growth.sensitiveWord/levelOptions', 'growth.sensitiveWord/typeOptions', 'growth.sensitiveWord/check'],
            'growth.notification/lists' => ['growth.notification/detail', 'growth.notification/templates', 'growth.notification/typeOptions', 'growth.notification/sendTrend', 'growth.notification/statistics'],
            'finance.flow/lists' => ['finance.flow/detail', 'finance.flow/statistics', 'finance.flow/bizTypeOptions', 'finance.flow/flowTypeOptions', 'finance.financialReport/overview', 'finance.financialReport/incomeStats', 'finance.financialReport/incomeTrend', 'finance.financialReport/payWayAnalysis', 'finance.financialReport/refundStats'],
            'finance.settlement/lists' => ['finance.settlement/detail', 'finance.settlement/statistics', 'finance.settlement/staffSummary', 'finance.settlement/batchLists'],
        ];
    }
}
