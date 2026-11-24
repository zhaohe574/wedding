<template>
    <admin-hub-tabs
        title="新人问卷"
        description="配置新人备婚问卷模板、跟进下发任务及基础题库"
        :tabs="tabs"
        default-tab="lists"
    />
</template>

<script lang="ts" setup name="questionnaireHub">
/**
 * 新人问卷 Hub（批次 3a 聚合问卷域页面）。
 * Tab 按 perms 过滤；子页面通过 tabs 数组挂载，原路由保持不变。
 */
import QuestionBankPage from '@/views/questionnaire/couple_question_bank/index.vue'
import QuestionnaireListsPage from '@/views/questionnaire/couple_questionnaire/index.vue'
import QuestionnaireTaskPage from '@/views/questionnaire/couple_questionnaire_task/index.vue'

const tabs = [
    {
        name: 'lists',
        label: '问卷列表',
        perms: ['growth.coupleQuestionnaire/lists'],
        component: QuestionnaireListsPage
    },
    {
        name: 'tasks',
        label: '问卷任务',
        perms: ['growth.coupleQuestionnaire/tasks'],
        component: QuestionnaireTaskPage
    },
    {
        name: 'bank',
        label: '基础题库',
        perms: ['growth.coupleQuestionBank/lists'],
        component: QuestionBankPage
    }
]
</script>
