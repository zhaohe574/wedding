<template>
    <div class="home-flow-preview">
        <div class="hf-head">
            <span class="hf-title">{{ content.title || HOME_FLOW_DEFAULTS.title }}</span>
            <span v-if="Number(content.enabled) === 0" class="hf-badge">已隐藏</span>
        </div>
        <div v-if="showList.length" class="hf-list">
            <div v-for="(item, index) in showList" :key="index" class="hf-row">
                <span class="hf-tag">{{ item.tag }}</span>
                <div class="hf-body">
                    <span class="hf-row-title">{{ item.title }}</span>
                    <span v-if="item.desc" class="hf-desc">{{ item.desc }}</span>
                </div>
            </div>
        </div>
        <div v-else class="hf-empty">未配置步骤，真机整块不渲染</div>
    </div>
</template>

<script lang="ts" setup>
import { HOME_FLOW_DEFAULTS } from '@shared/contracts/decorate'
import type { PropType } from 'vue'

import type options from './options'

type OptionsType = ReturnType<typeof options>

const props = defineProps({
    content: {
        type: Object as PropType<OptionsType['content']>,
        default: () => ({})
    },
    styles: {
        type: Object as PropType<OptionsType['styles']>,
        default: () => ({})
    }
})

const showList = computed(() => {
    const list = Array.isArray(props.content.data) ? props.content.data : []
    return list.filter((item) => item && String(item.is_show) === '1' && String(item.title || '').trim())
})
</script>

<style lang="scss" scoped>
.home-flow-preview {
    padding: 12px;
}

.hf-head {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 8px;
}

.hf-title {
    font-size: 13px;
    font-weight: 600;
    color: #101010;
}

.hf-badge {
    padding: 0 6px;
    font-size: 11px;
    line-height: 18px;
    color: #909399;
    background: #f4f4f5;
    border-radius: 4px;
}

.hf-row {
    display: flex;
    gap: 8px;
    padding: 6px 0;
    border-bottom: 1px solid #f0f0f0;

    &:last-child {
        border-bottom: 0;
    }
}

.hf-tag {
    flex: none;
    width: 44px;
    padding-top: 1px;
    font-size: 11px;
    color: #b08d57;
}

.hf-body {
    display: flex;
    flex-direction: column;
    min-width: 0;
}

.hf-row-title {
    font-size: 12px;
    color: #303133;
}

.hf-desc {
    margin-top: 2px;
    font-size: 11px;
    line-height: 1.5;
    color: #909399;
}

.hf-empty {
    font-size: 12px;
    color: #909399;
}
</style>
