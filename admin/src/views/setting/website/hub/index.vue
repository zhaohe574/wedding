<template>
    <admin-hub-tabs
        title="品牌与站点"
        description="维护前后台站点名称与图标、海报资源、底部备案、政策协议及站点统计"
        :tabs="tabs"
        default-tab="information"
    />
</template>

<script lang="ts" setup name="settingWebsiteHub">
/**
 * 品牌与站点 Hub（批次 3a 聚合站点信息、海报资源、备案、协议与统计）。
 * Tab 按 perms 过滤；子页面通过 tabs 数组挂载，原路由保持不变。
 */
import PosterAssetsTab from '@/views/setting/website/components/PosterAssetsTab.vue'
import WebsiteFilingPage from '@/views/setting/website/filing.vue'
import WebsiteInfoPage from '@/views/setting/website/information.vue'
import WebsiteProtocolPage from '@/views/setting/website/protocol.vue'
import WebsiteStatsPage from '@/views/setting/website/statistics.vue'

const tabs = [
    {
        name: 'information',
        label: '站点信息',
        perms: ['setting.web.web_setting/getWebsite'],
        component: WebsiteInfoPage
    },
    {
        name: 'poster',
        label: '海报资源',
        perms: ['setting.order_confirm_letter/fontLists', 'setting.web.web_setting/getWebsite'],
        component: PosterAssetsTab
    },
    {
        name: 'filing',
        label: '版权备案',
        perms: ['setting.web.web_setting/getCopyright'],
        component: WebsiteFilingPage
    },
    {
        name: 'protocol',
        label: '政策协议',
        perms: ['setting.web.web_setting/getAgreement'],
        component: WebsiteProtocolPage
    },
    {
        name: 'statistics',
        label: '站点统计',
        perms: ['setting.web.web_setting/getSiteStatistics'],
        component: WebsiteStatsPage
    }
]
</script>
