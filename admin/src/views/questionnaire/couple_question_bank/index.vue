<template>
    <admin-page-shell title="基础题库样例">
        <el-card class="!border-none" shadow="never">
            <QuestionBankPanel />
        </el-card>
    </admin-page-shell>
</template>

<script setup lang="ts">
/** 旧路由独立页：题库维护复用问卷域核心组件。 */
import QuestionBankPanel from '../components/QuestionBankPanel.vue'
</script>
