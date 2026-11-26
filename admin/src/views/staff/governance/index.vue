<template>
    <admin-hub-tabs
        class="staff-governance"
        title="资质标签与工作台"
        :tabs="tabs"
        default-tab="certificate"
    />
</template>

<script lang="ts" setup name="staffGovernance">
/**
 * 资质标签与工作台 Hub：证书、标签审核、标签库、话术模板、档期确认函与工作台开关。
 * Tab 按 perms 过滤；各 Tab 内部只局部提交自己业务域的字段。
 */
import TagPage from '@/views/service/tag/index.vue'
import CertificatePage from '@/views/staff/certificate/index.vue'
import ConfirmLetterPage from '@/views/staff/confirm_letter/index.vue'
import TagReviewPage from '@/views/staff/tag_review/index.vue'
import CopyTemplatePage from '@/views/staff/copy_template/index.vue'

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
        name: 'copy_templates',
        label: '工作台话术模板',
        perms: ['ops.staffCopyTemplate/lists'],
        component: CopyTemplatePage
    },
    {
        name: 'confirm_letter',
        label: '档期确认函',
        perms: ['ops.staff/lists'],
        component: ConfirmLetterPage
    },
    {
        name: 'workbench',
        label: '工作台与协同开关',
        perms: ['setting.feature_switch/getConfig'],
        component: WorkbenchSwitchTab
    }
]
</script>

<style lang="scss" scoped>
.staff-governance {
    :deep(.admin-hub-tabs__pane) {
        min-width: 0;
    }
}
</style>
