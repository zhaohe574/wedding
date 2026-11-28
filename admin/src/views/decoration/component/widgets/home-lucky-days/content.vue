<template>
    <!-- 近期吉日：接口内容，装修只控制显隐 -->
    <div class="home-lucky-days-preview">
        <div class="hl-head">
            <span class="hl-title">近期吉日 · 可约情况</span>
            <span v-if="hidden" class="hl-badge">已隐藏</span>
        </div>
        <div v-if="hidden" class="hl-empty">已关闭，真机不显示本区块</div>
        <div v-else class="hl-darkcard">
            <div class="hl-darkcard__bar" />
            <div class="hl-darkcard__slots">
                <div v-for="n in 3" :key="n" class="hl-slot" />
            </div>
        </div>
    </div>
</template>

<script lang="ts" setup>
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

const hidden = computed(() => Number(props.content.enabled) === 0)
</script>

<style lang="scss" scoped>
.home-lucky-days-preview {
    padding: 12px;
}

.hl-head {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 8px;
}

.hl-title {
    font-size: 13px;
    font-weight: 600;
    color: #101010;
}

.hl-badge {
    padding: 0 6px;
    font-size: 11px;
    line-height: 18px;
    color: #999999;
    background: #f3f3f3;
    border-radius: 4px;
}

.hl-empty {
    padding: 14px 0;
    font-size: 12px;
    color: #999999;
    text-align: center;
    border: 1px dashed #e0e0e0;
    border-radius: 8px;
}

.hl-darkcard {
    padding: 12px;
    border-radius: 12px;
    background: linear-gradient(180deg, #211d19 0%, #14110f 100%);

    &__bar {
        width: 100%;
        height: 2px;
        border-radius: 999px;
        background: rgba(255, 255, 255, 0.16);
    }

    &__slots {
        display: flex;
        gap: 8px;
        margin-top: 12px;
    }
}

.hl-slot {
    flex: 1;
    height: 42px;
    border-radius: 6px;
    background: rgba(255, 255, 255, 0.08);
    border: 1px solid rgba(255, 255, 255, 0.12);
    box-sizing: border-box;
}
</style>
