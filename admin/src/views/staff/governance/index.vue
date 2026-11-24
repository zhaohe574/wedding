<template>
    <admin-hub-tabs
        title="资质标签与工作台"
        description="统一管理服务人员证书审核、自助标签审核、风格标签库及移动端工作台入口灰度"
        :tabs="tabs"
        default-tab="certificate"
    />
</template>

<script lang="ts" setup name="staffGovernance">
/**
 * 资质标签与工作台 Hub（批次 2b 拆分自 setting/feature_switch）。
 * Tab 按 perms 过滤；各 Tab 内部只局部提交自己业务域的字段。
 */
import TagPage from '@/views/service/tag/index.vue'
import CertificatePage from '@/views/staff/certificate/index.vue'
import TagReviewPage from '@/views/staff/tag_review/index.vue'

import WorkbenchSwitchTab from './components/WorkbenchSwitchTab.vue'

const tabs = [
    {
        name: 'certificate',
        label: '人员证书',
        perms: ['ops.staffCertificate/lists'],
        component: CertificatePage
    },
    {
        name: 'tag_review',
        label: '标签审核',
        perms: ['ops.staffTagReview/lists'],
        component: TagReviewPage
    },
    {
        name: 'tags',
        label: '服务人员标签',
        perms: ['ops.styleTag/lists'],
        component: TagPage
    },
    {
        name: 'workbench',
        label: '工作台与协同开关',
        perms: ['setting.feature_switch/getConfig'],
        component: WorkbenchSwitchTab
    }
]
</script>
