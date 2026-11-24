<template>
    <admin-hub-tabs
        title="缓存环境与看板开关"
        description="配置移动端管理员看板入口，并查看系统缓存与运行环境"
        :tabs="tabs"
        default-tab="environment"
    />
</template>

<script lang="ts" setup name="settingSystemOpsHub">
/**
 * 缓存环境与看板开关 Hub（批次 2b 拆分自 setting/feature_switch）。
 * Tab 按 perms 过滤；各 Tab 内部只局部提交自己业务域的字段。
 */
import CachePage from '@/views/setting/system/cache.vue'
import EnvironmentPage from '@/views/setting/system/environment.vue'

import DashboardAccessTab from './components/DashboardAccessTab.vue'

const tabs = [
    {
        name: 'environment',
        label: '系统环境',
        perms: ['setting.system.system/info'],
        component: EnvironmentPage
    },
    {
        name: 'cache',
        label: '系统缓存',
        perms: [],
        component: CachePage
    },
    {
        name: 'dashboard',
        label: '移动端看板开关',
        perms: ['setting.feature_switch/getConfig'],
        component: DashboardAccessTab
    }
]
</script>
