<template>
    <admin-hub-tabs title="内容风控与审核" :tabs="tabs" default-tab="policy" />
</template>

<script lang="ts" setup name="reviewContentSecurity">
/**
 * 内容风控与审核 Hub（批次 2b 拆分自 setting/feature_switch）。
 * Tab 按 perms 过滤；各 Tab 内部只局部提交自己业务域的字段。
 */
import SensitiveWordPage from '@/views/review/sensitive/index.vue'

import ModerationPolicyTab from './components/ModerationPolicyTab.vue'
import RiskPermissionsTab from './components/RiskPermissionsTab.vue'

const tabs = [
    {
        name: 'policy',
        label: '审核与送审策略',
        perms: ['setting.feature_switch/getConfig'],
        component: ModerationPolicyTab
    },
    {
        name: 'risk',
        label: '风险等级权限',
        perms: ['setting.feature_switch/getConfig'],
        component: RiskPermissionsTab
    },
    {
        name: 'sensitive',
        label: '敏感词库',
        perms: ['growth.sensitiveWord/lists'],
        component: SensitiveWordPage
    }
]
</script>
