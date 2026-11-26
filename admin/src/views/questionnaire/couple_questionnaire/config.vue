<template>
    <admin-page-shell title="配置新人问卷">
        <template #extra>
            <el-button @click="goBack">返回列表</el-button>
        </template>

        <StaffQuestionnaireEditor :staff-id="staffId" />
    </admin-page-shell>
</template>

<script setup lang="ts">
/** 旧路由独立页：仅承接 ?staff_id=，编辑能力由共享编辑器提供。 */
import { computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'

import { getMenuPath } from '@/router'

import StaffQuestionnaireEditor from '../components/StaffQuestionnaireEditor.vue'

const route = useRoute()
const router = useRouter()
const hubPath = getMenuPath('page.questionnaire.hub')
const listPath =
    getMenuPath('page.questionnaire.couple_questionnaire') || '/couple-questionnaire/lists'

const staffId = computed(() => Number(route.query.staff_id || 0))

const goBack = () => {
    if (hubPath) {
        router.push({ path: hubPath, query: { tab: 'lists' } })
        return
    }
    router.push(listPath)
}
</script>
