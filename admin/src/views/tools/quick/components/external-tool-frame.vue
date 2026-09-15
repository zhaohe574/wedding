<template>
    <section class="external-tool">
        <h2>{{ title }}</h2>
        <el-tag type="info">第三方工具</el-tag>
        <p>{{ provider }}</p>
        <el-link :href="url" target="_blank" rel="noopener noreferrer" type="primary" :icon="TopRight">打开第三方网站</el-link>
    </section>
</template>

<script setup lang="ts">
import { TopRight } from '@element-plus/icons-vue'
defineProps<{ title: string; url: string; provider: string }>()
</script>

<style scoped>
.external-tool { padding: 24px; background: var(--el-bg-color); }
h2 { margin: 0 0 12px; font-size: 18px; }
p { color: var(--el-text-color-secondary); }
</style>
