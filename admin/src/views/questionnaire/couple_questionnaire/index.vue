<template>
    <admin-page-shell title="新人问卷">
        <template #extra>
            <el-button @click="goTasks()">问卷任务</el-button>
            <el-button type="primary" @click="goBank">基础题库</el-button>
        </template>

        <el-card class="!border-none" shadow="never">
            <StaffConfigPanel ref="panelRef" @open-config="openConfig" @view-tasks="goTasks" />
        </el-card>

        <StaffConfigDrawer
            v-model="drawer.visible"
            :staff-id="drawer.staffId"
            :staff-name="drawer.staffName"
            @saved="reloadPanel"
            @published="reloadPanel"
        />
    </admin-page-shell>
</template>

<script setup lang="ts">
/** 旧路由独立页：仅保留页头与快捷跳转，列表与编辑体验复用问卷域核心组件。 */
import { reactive, ref } from 'vue'
import { useRouter } from 'vue-router'

import { getMenuPath } from '@/router'

import StaffConfigDrawer from '../components/StaffConfigDrawer.vue'
import StaffConfigPanel from '../components/StaffConfigPanel.vue'

const router = useRouter()
const hubPath = getMenuPath('page.questionnaire.hub')
const panelRef = ref<any>()
const drawer = reactive({
    visible: false,
    staffId: 0,
    staffName: ''
})

const reloadPanel = () => {
    panelRef.value?.reload()
}

const openConfig = (row: any) => {
    drawer.staffId = Number(row?.staff_id || row?.staff?.id || 0)
    drawer.staffName = String(row?.staff_name || row?.staff?.name || '')
    drawer.visible = true
}

const goTasks = (row?: any) => {
    const staffId = Number(row?.staff_id || row?.staff?.id || 0)
    if (hubPath) {
        router.push({
            path: hubPath,
            query: staffId
                ? { tab: 'tasks', staff_id: String(staffId) }
                : { tab: 'tasks' }
        })
        return
    }
    router.push({
        path: '/couple-questionnaire/tasks',
        query: staffId ? { staff_id: String(staffId) } : undefined
    })
}

const goBank = () => {
    if (hubPath) {
        router.push({ path: hubPath, query: { tab: 'bank' } })
        return
    }
    router.push('/couple-questionnaire/bank')
}
</script>
