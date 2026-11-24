<template>
    <admin-hub-tabs
        title="微信小程序"
        description="配置微信小程序开发者凭据、合法域名与小程序热门搜索词"
        :tabs="tabs"
        default-tab="config"
    />
</template>

<script lang="ts" setup name="channelWeappHub">
/**
 * 微信小程序 Hub（批次 3a 聚合小程序渠道与热门搜索配置）。
 * Tab 按 perms 过滤；子页面通过 tabs 数组挂载，原路由保持不变。
 */
import WeappPage from '@/views/channel/weapp.vue'
import HotSearchPage from '@/views/setting/search/index.vue'

const tabs = [
    {
        name: 'config',
        label: '开发与域名配置',
        perms: ['channel.mnp_settings/getConfig'],
        component: WeappPage
    },
    {
        name: 'search',
        label: '热门搜索词',
        perms: ['setting.hot_search/getConfig'],
        component: HotSearchPage
    }
]
</script>
