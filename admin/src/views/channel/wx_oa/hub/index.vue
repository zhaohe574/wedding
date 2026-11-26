<template>
    <admin-hub-tabs title="微信公众号" :tabs="tabs" default-tab="config" />
</template>

<script lang="ts" setup name="channelWxOaHub">
/**
 * 微信公众号 Hub（批次 3a 聚合公众号配置与回复规则页面）。
 * Tab 按 perms 过滤；子页面通过 tabs 数组挂载，原路由保持不变。
 */
import WxOaConfigPage from '@/views/channel/wx_oa/config.vue'
import WxOaMenuPage from '@/views/channel/wx_oa/menu.vue'
import DefaultReplyPage from '@/views/channel/wx_oa/reply/default_reply.vue'
import FollowReplyPage from '@/views/channel/wx_oa/reply/follow_reply.vue'
import KeywordReplyPage from '@/views/channel/wx_oa/reply/keyword_reply.vue'

const tabs = [
    {
        name: 'config',
        label: '基础配置',
        perms: ['channel.official_account_setting/getConfig'],
        component: WxOaConfigPage
    },
    {
        name: 'menu',
        label: '自定义菜单',
        perms: ['channel.official_account_menu/detail'],
        component: WxOaMenuPage
    },
    {
        name: 'follow',
        label: '关注回复',
        perms: ['channel.official_account_reply/lists'],
        component: FollowReplyPage
    },
    {
        name: 'keyword',
        label: '关键字回复',
        perms: ['channel.official_account_reply/lists'],
        component: KeywordReplyPage
    },
    {
        name: 'default',
        label: '默认回复',
        perms: ['channel.official_account_reply/lists'],
        component: DefaultReplyPage
    }
]
</script>
