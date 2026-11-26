<template>
    <admin-hub-tabs title="售后管理" :tabs="tabs" default-tab="ticket" />
</template>

<script lang="ts" setup name="aftersaleHub">
/**
 * 售后管理 Hub（批次 3a 聚合售后域页面）。
 * Tab 按 perms 过滤；子页面通过 tabs 数组挂载，原路由保持不变。
 */
import CallbackPage from '@/views/aftersale/callback/index.vue'
import ComplaintPage from '@/views/aftersale/complaint/index.vue'
import TicketPage from '@/views/aftersale/ticket/index.vue'

const tabs = [
    {
        name: 'ticket',
        label: '工单管理',
        perms: ['ops.aftersaleTicket/ticketLists'],
        component: TicketPage
    },
    {
        name: 'complaint',
        label: '投诉管理',
        perms: ['ops.aftersaleTicket/complaintLists'],
        component: ComplaintPage
    },
    {
        name: 'callback',
        label: '回访管理',
        perms: ['ops.aftersaleTicket/callbackLists'],
        component: CallbackPage
    }
]
</script>
