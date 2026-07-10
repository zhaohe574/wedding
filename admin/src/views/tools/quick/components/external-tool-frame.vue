<template>
    <div class="external-tool-page">
        <el-card class="!border-none external-tool-shell" shadow="never">
            <div class="external-tool-head">
                <div class="external-tool-head__main">
                    <div class="external-tool-head__title">{{ title }}</div>
                    <div class="external-tool-head__desc">{{ description }}</div>
                </div>
                <div class="external-tool-head__actions">
                    <el-button :icon="RefreshRight" @click="reloadFrame">重新加载</el-button>
                </div>
            </div>

            <div class="external-tool-frame-wrap">
                <div v-if="frameLoading" class="external-tool-state">
                    <el-icon class="is-loading" :size="28"><Loading /></el-icon>
                    <span>工具加载中</span>
                </div>
                <div v-if="frameError" class="external-tool-state external-tool-state--error">
                    <el-icon :size="28"><Warning /></el-icon>
                    <span>工具加载失败，请稍后重试</span>
                    <el-button type="primary" :icon="RefreshRight" @click="reloadFrame">重新加载</el-button>
                </div>
                <iframe
                    :key="frameKey"
                    class="external-tool-frame"
                    :src="frameUrl"
                    :title="title"
                    allow="clipboard-read; clipboard-write"
                    referrerpolicy="no-referrer"
                    @load="handleFrameLoad"
                    @error="handleFrameError"
                ></iframe>
            </div>
        </el-card>
    </div>
</template>

<script lang="ts" setup>
import { computed, ref } from 'vue'
import { Loading, RefreshRight, Warning } from '@element-plus/icons-vue'

const props = defineProps<{
    title: string
    url: string
    description?: string
}>()

const frameKey = ref(0)
const frameLoading = ref(true)
const frameError = ref(false)

const description = computed(() => props.description || '第三方工具页面，本系统不保存、不上传、不转存处理文件。')
const frameUrl = computed(() => `${props.url}${props.url.includes('?') ? '&' : '?'}_t=${frameKey.value}`)

const reloadFrame = () => {
    frameError.value = false
    frameLoading.value = true
    frameKey.value += 1
}

const handleFrameLoad = () => {
    frameLoading.value = false
    frameError.value = false
}

const handleFrameError = () => {
    frameLoading.value = false
    frameError.value = true
}
</script>

<style lang="scss" scoped>
.external-tool-page {
    min-width: 720px;
}

.external-tool-shell {
    :deep(.el-card__body) {
        padding: 18px;
    }
}

.external-tool-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 18px;
    padding-bottom: 16px;
    border-bottom: 1px solid var(--el-border-color-lighter);

    &__main {
        min-width: 0;
    }

    &__title {
        color: var(--el-text-color-primary);
        font-size: 20px;
        font-weight: 600;
        line-height: 1.4;
    }

    &__desc {
        margin-top: 4px;
        color: var(--el-text-color-secondary);
        font-size: 13px;
        line-height: 1.5;
    }

    &__actions {
        display: flex;
        flex-shrink: 0;
        align-items: center;
        gap: 10px;
    }
}

.external-tool-frame-wrap {
    position: relative;
    min-height: calc(100vh - 220px);
    margin-top: 16px;
    overflow: hidden;
    border: 1px solid var(--el-border-color-lighter);
    border-radius: 8px;
    background: #f5f7fa;
}

.external-tool-frame {
    display: block;
    width: 100%;
    min-height: calc(100vh - 220px);
    border: 0;
    background: #fff;
}

.external-tool-state {
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
    .external-tool-page {
        min-width: 0;
    }

    .external-tool-head {
        align-items: flex-start;
        flex-direction: column;

        &__actions {
            width: 100%;
            flex-wrap: wrap;
        }
    }
}
</style>
