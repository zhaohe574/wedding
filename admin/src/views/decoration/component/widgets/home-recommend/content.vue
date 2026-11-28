<template>
    <!-- 本期推荐：接口内容，装修只控制显隐 -->
    <div class="home-recommend-preview">
        <div class="hr-head">
            <span class="hr-title">本期推荐</span>
            <span v-if="hidden" class="hr-badge">已隐藏</span>
        </div>
        <div v-if="hidden" class="hr-empty">已关闭，真机不显示本区块</div>
        <div v-else class="hr-cards">
            <div v-for="n in 2" :key="n" class="hr-card">
                <div class="hr-card__ph" />
                <div class="hr-card__line" />
                <div class="hr-card__line hr-card__line--short" />
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
.home-recommend-preview {
    padding: 12px;
}

.hr-head {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 8px;
}

.hr-title {
    font-size: 13px;
    font-weight: 600;
    color: #101010;
}

.hr-badge {
    padding: 0 6px;
    font-size: 11px;
    line-height: 18px;
    color: #999999;
    background: #f3f3f3;
    border-radius: 4px;
}

.hr-empty {
    padding: 14px 0;
    font-size: 12px;
    color: #999999;
    text-align: center;
    border: 1px dashed #e0e0e0;
    border-radius: 8px;
}

.hr-cards {
    display: flex;
    gap: 8px;
}

.hr-card {
    flex: 1;
    min-width: 0;
    padding: 6px;
    border-radius: 10px;
    background: #fffdf8;
    box-shadow: 0 2px 8px -4px rgba(64, 48, 28, 0.22);
    box-sizing: border-box;

    &__ph {
        height: 74px;
        border-radius: 6px;
        background: #f0ebe2;
    }

    &__line {
        height: 7px;
        margin-top: 6px;
        border-radius: 3px;
        background: #f0ebe2;

        &--short {
            width: 60%;
        }
    }
}
</style>
