<template>
    <admin-page-shell title="问卷任务">
        <template #extra>
            <el-button
                v-perms="['growth.coupleQuestionnaire/send']"
                type="primary"
                @click="sendDialogVisible = true"
            >
                按订单补发
            </el-button>
            <el-button @click="goList">问卷列表</el-button>
        </template>

        <el-card class="!border-none" shadow="never">
            <TaskListPanel
                ref="taskPanelRef"
                @open-send-by-order="sendDialogVisible = true"
            />
        </el-card>

        <SendByOrderDialog v-model="sendDialogVisible" @success="handleSendSuccess" />
    </admin-page-shell>
</template>

<script setup lang="ts">
/** 旧路由独立页：任务列表与补发弹窗复用问卷域核心组件。 */
import { ref } from 'vue'
import { useRouter } from 'vue-router'

import { getMenuPath } from '@/router'

import SendByOrderDialog from '../components/SendByOrderDialog.vue'
import TaskListPanel from '../components/TaskListPanel.vue'

const router = useRouter()
const hubPath = getMenuPath('page.questionnaire.hub')
const taskPanelRef = ref<any>()
const sendDialogVisible = ref(false)

const handleSendSuccess = () => {
    taskPanelRef.value?.reload()
}

const goList = () => {
    if (hubPath) {
        router.push({ path: hubPath, query: { tab: 'lists' } })
        return
    }
    router.push('/couple-questionnaire/lists')
}
</script>
