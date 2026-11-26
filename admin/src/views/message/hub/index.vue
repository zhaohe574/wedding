<template>
    <admin-hub-tabs title="消息通知中心" :tabs="tabs" default-tab="lists" />
</template>

<script lang="ts" setup name="messageHub">
/**
 * 消息通知中心 Hub（批次 3a 聚合消息域页面）。
 * Tab 按 perms 过滤；子页面通过 tabs 数组挂载，原路由保持不变。
 */
import MnpNotificationPage from '@/views/message/mnp_notification/index.vue'
import NoticePage from '@/views/message/notice/index.vue'
import OaNotificationPage from '@/views/message/oa_notification/index.vue'
import ShortLetterPage from '@/views/message/short_letter/index.vue'
import NotificationListsPage from '@/views/notification/lists/index.vue'

const tabs = [
    {
        name: 'lists',
        label: '站内通知',
        perms: ['growth.notification/lists'],
        component: NotificationListsPage
    },
    {
        name: 'oa',
        label: '服务号推送',
        perms: ['notification.oaNotification/templateList'],
        component: OaNotificationPage
    },
    {
        // 此前漏在菜单外的小程序订阅消息，本批次恢复接入 Hub。
        name: 'mnp',
        label: '小程序订阅消息',
        perms: ['notification.mnpNotification/templateList'],
        component: MnpNotificationPage
    },
    {
        name: 'notice',
        label: '通知场景',
        perms: ['notice.notice/settingLists'],
        component: NoticePage
    },
    {
        name: 'sms',
        label: '短信通道',
        perms: ['notice.sms_config/getConfig'],
        component: ShortLetterPage
    }
]
</script>
