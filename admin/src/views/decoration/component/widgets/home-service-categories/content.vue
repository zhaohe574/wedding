<template>
    <!-- 四宫格入口：与真机一致，一行四个「圆角图标 + 名称」，不再有大小图/文字位置 -->
    <div v-if="showList.length" class="home-categories-preview">
        <div v-for="(item, index) in showList" :key="index" class="home-categories-preview__item">
            <div class="home-categories-preview__ic">
                <decoration-img
                    v-if="item.image"
                    width="100%"
                    height="100%"
                    :src="item.image"
                    fit="contain"
                />
                <icon v-else name="el-icon-Calendar" size="18" />
            </div>
            <div class="home-categories-preview__label">{{ item.title }}</div>
        </div>
    </div>
    <div v-else class="home-categories-preview__empty">没有可显示的入口，真机不显示这一排</div>
</template>

<script lang="ts" setup>
import type { PropType } from 'vue'

import DecorationImg from '../../decoration-img.vue'
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
    return (props.content.data || []).filter(
        (item) => String(item.is_show ?? '1') === '1' && String(item.title || '').trim()
    )
})
</script>

<style lang="scss" scoped>
.home-categories-preview {
    display: flex;
    gap: 8px;
    padding: 16px 14px;

    &__item {
        flex: 1;
        min-width: 0;
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 6px;
    }

    &__ic {
        width: 44px;
        height: 44px;
        display: flex;
        align-items: center;
        justify-content: center;
        border: 1px solid rgba(11, 11, 11, 0.08);
        border-radius: 14px;
        background: #f5eddd;
        color: #8a6b2f;
        overflow: hidden;
    }

    &__label {
        max-width: 100%;
        overflow: hidden;
        white-space: nowrap;
        text-overflow: ellipsis;
        color: #4a4a4a;
        font-size: 12px;
        text-align: center;
    }

    &__empty {
        padding: 16px;
        color: #999999;
        font-size: 12px;
        text-align: center;
    }
}
</style>
