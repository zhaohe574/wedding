<template>
    <div class="quick-tool-page">
        <el-card class="!border-none quick-tool-shell" shadow="never">
            <div class="quick-tool-head">
                <div class="quick-tool-head__title">{{ title }}</div>
                <el-button :icon="RefreshRight" @click="reloadTool">重新加载</el-button>
            </div>

            <div class="quick-tool-frame-wrap">
                <div v-if="frameLoading" class="quick-tool-state">
                    <el-icon class="is-loading" :size="28"><Loading /></el-icon>
                    <span>工具加载中</span>
                </div>
                <div v-if="frameError" class="quick-tool-state quick-tool-state--error">
                    <el-icon :size="28"><Warning /></el-icon>
                    <span>工具加载失败，请稍后重试</span>
                    <el-button type="primary" @click="reloadTool">重新加载</el-button>
                </div>
                <iframe
                    v-if="currentFrameUrl"
                    :key="frameKey"
                    class="quick-tool-frame"
                    :src="currentFrameUrl"
                    :title="title"
                    @load="handleFrameLoad"
                ></iframe>
            </div>
        </el-card>
    </div>
</template>

<script lang="ts" setup>
import { onMounted, ref, watch } from 'vue'
import { Loading, RefreshRight, Warning } from '@element-plus/icons-vue'

import { quickToolTicket } from '@/api/quick-tool'
import configs from '@/config'

export type QuickToolKey = 'human_split' | 'compress' | 'convert' | 'image_edit'

const props = defineProps<{
    tool: QuickToolKey
    title: string
}>()

const frameKey = ref(0)
const frameLoading = ref(true)
const frameError = ref(false)
const currentFrameUrl = ref('')

const buildFrameUrl = (ticket: string) => {
    const baseUrl = `${configs.baseUrl}${configs.urlPrefix}/ops.quickTool/page`
    return `${baseUrl}?tool=${encodeURIComponent(props.tool)}&ticket=${encodeURIComponent(ticket)}&_t=${frameKey.value}`
}

const reloadTool = async () => {
    frameError.value = false
    frameLoading.value = true
    frameKey.value += 1
    currentFrameUrl.value = ''

    try {
        const data = await quickToolTicket({ tool: props.tool })
        currentFrameUrl.value = buildFrameUrl(data.ticket)
    } catch (error) {
        frameLoading.value = false
        frameError.value = true
    }
}

const handleFrameLoad = () => {
    frameLoading.value = false
    frameError.value = false
}

watch(
    () => props.tool,
    () => {
        reloadTool()
    },
)

onMounted(() => {
    reloadTool()
})
</script>

<style lang="scss" scoped>
.quick-tool-page {
    min-width: 720px;
}

.quick-tool-shell {
    :deep(.el-card__body) {
        padding: 18px;
    }
}

.quick-tool-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
    padding-bottom: 16px;
    border-bottom: 1px solid var(--el-border-color-lighter);

    &__title {
        color: var(--el-text-color-primary);
        font-size: 20px;
        font-weight: 600;
        line-height: 1.4;
    }
}

.quick-tool-frame-wrap {
    position: relative;
    min-height: calc(100vh - 200px);
    margin-top: 16px;
    overflow: hidden;
    border: 1px solid var(--el-border-color-lighter);
    border-radius: 8px;
    background: #f5f7fa;
}

.quick-tool-frame {
    display: block;
    width: 100%;
    min-height: calc(100vh - 200px);
    border: 0;
    background: #f5f7fa;
}

.quick-tool-state {
    position: absolute;
    inset: 0;
    z-index: 2;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    color: var(--el-text-color-secondary);
    background: rgb(245 247 250 / 92%);
    font-size: 14px;

    &--error {
        flex-direction: column;
    }
}

@media (max-width: 768px) {
    .quick-tool-page {
        min-width: 0;
    }

    .quick-tool-head {
        align-items: flex-start;
        flex-direction: column;
    }
}
</style>
