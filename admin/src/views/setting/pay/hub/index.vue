<template>
    <admin-hub-tabs title="支付设置" :tabs="tabs" default-tab="method" />
</template>

<script lang="ts" setup name="settingPayHub">
/**
 * 支付设置 Hub（批次 3a 聚合支付域页面）。
 * Tab 按 perms 过滤；子页面通过 tabs 数组挂载，原路由保持不变。
 */
import PayConfigPage from '@/views/setting/pay/config/index.vue'
import PayMethodPage from '@/views/setting/pay/method/index.vue'

const tabs = [
    {
        name: 'method',
        label: '支付方式',
        perms: ['setting.pay.pay_way/getPayWay'],
        component: PayMethodPage
    },
    {
        name: 'config',
        label: '支付配置',
        perms: ['setting.pay.pay_config/lists'],
        component: PayConfigPage
    }
]
</script>
