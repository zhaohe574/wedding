<template>
    <div class="search-preview">
        <div class="search-preview__navbar">
            <span class="search-preview__title">首页</span>
            <div class="search-preview__mini-search">
                <icon name="el-icon-Search" :size="14" />
            </div>
        </div>
        <div v-if="Number(content.showSearchPanel ?? 1) !== 0" class="search-preview__panel">
            <div class="search-preview__input flex items-center px-[15px]">
                <icon name="el-icon-Search" :size="17" />
                <span class="ml-[5px]">{{ placeholderText }}</span>
            </div>
        </div>
    </div>
</template>
<script lang="ts" setup>
import { computed } from 'vue'
import type { PropType } from 'vue'

import type options from './options'

type OptionsType = ReturnType<typeof options>
const props = defineProps({
    content: {
        type: Object as PropType<OptionsType['content']>,
        default: () => ({})
    }
})

const placeholderText = computed(() => {
    const placeholder = props.content?.placeholder
    return typeof placeholder === 'string' && placeholder.trim() ? placeholder : '请输入关键词搜索'
})
</script>

<style lang="scss" scoped>
.search-preview {
    background-color: #fff;
}

.search-preview__navbar {
    position: relative;
    display: flex;
    align-items: center;
    justify-content: center;
    height: 52px;
    padding: 0 12px;
}

.search-preview__title {
    font-size: 16px;
    color: #333333;
}

.search-preview__mini-search {
    position: absolute;
    right: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    width: 28px;
    height: 28px;
    border-radius: 50%;
    background: #ffffff;
    color: #666666;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
}

.search-preview__panel {
    padding: 7px 12px 12px;
}

.search-preview__input {
    height: 36px;
    border-radius: 36px;
    background: #f4f4f4;
    color: #999999;
}
</style>
