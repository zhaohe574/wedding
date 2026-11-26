<template>
    <!-- 四宫格入口：与真机 / home-preview 一致的 2 列图文卡片（大图占两行 / 小图 / 通栏占两列） -->
    <div v-if="showList.length" class="tile-grid">
        <div
            v-for="(item, index) in showList"
            :key="index"
            class="tile"
            :class="[`tile--${item.size}`, { 'tile--no-image': !item.image }]"
        >
            <decoration-img
                v-if="item.image"
                width="100%"
                height="100%"
                :src="item.image"
                fit="cover"
            />
            <div class="tile__scrim" />
            <div
                class="tile__copy"
                :class="[
                    `tile__copy--${item.text_position}`,
                    `tile__copy--align-${item.text_align}`
                ]"
            >
                <div class="tile__title">{{ item.title }}</div>
                <div v-if="item.subtitle" class="tile__subtitle">{{ item.subtitle }}</div>
                <div class="tile__line" />
            </div>
        </div>
    </div>
    <div v-else class="tile-grid__empty">没有可显示的入口，真机不显示本区块</div>
</template>

<script lang="ts" setup>
import type { PropType } from 'vue'

import {
    isDecorateLinkConfigured,
    normalizeHomeServiceCategoryItem
} from '@shared/contracts/decorate'

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
    const list = Array.isArray(props.content.data) ? props.content.data : []
    return list
        .map((item) => normalizeHomeServiceCategoryItem(item))
        .filter(
            (item) => item.is_show === '1' && item.title && isDecorateLinkConfigured(item.link)
        )
})
</script>

<style lang="scss" scoped>
/* 几何比例与 375px 手机壳一致：真机 rpx × 0.5 */
.tile-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    grid-auto-rows: 84px;
    gap: 9px;
    padding: 18px 21px 0;

    &__empty {
        padding: 16px;
        color: #999999;
        font-size: 12px;
        text-align: center;
    }
}

.tile {
    position: relative;
    min-width: 0;
    height: 100%;
    overflow: hidden;
    border-radius: 16px;
    background: #181614;
    border: 1px solid #e7e0d3;
    box-sizing: border-box;

    &--large {
        grid-row: span 2;
    }

    &--wide {
        grid-column: span 2;
    }

    &--no-image {
        background: linear-gradient(135deg, #211d19 0%, #14110f 100%);
    }

    &__scrim {
        position: absolute;
        inset: 0;
        background: linear-gradient(180deg, rgba(24, 22, 20, 0.2) 0%, rgba(24, 22, 20, 0.68) 100%);
        pointer-events: none;
    }

    &__copy {
        position: absolute;
        left: 12px;
        right: 12px;
        z-index: 2;
        display: flex;
        flex-direction: column;
        gap: 4px;

        &--top {
            top: 12px;
        }

        &--middle {
            top: 50%;
            transform: translateY(-50%);
        }

        &--bottom {
            bottom: 11px;
        }

        &--align-left {
            align-items: flex-start;
            text-align: left;
        }

        &--align-center {
            align-items: center;
            text-align: center;
        }

        &--align-right {
            align-items: flex-end;
            text-align: right;
        }
    }

    &__title {
        max-width: 100%;
        font-size: 15px;
        line-height: 1.25;
        font-weight: 600;
        color: #ffffff;
        overflow: hidden;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        word-break: break-word;
    }

    &__subtitle {
        max-width: 100%;
        font-size: 9px;
        line-height: 1.25;
        font-weight: 500;
        letter-spacing: 0.06em;
        color: #d9be82;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    &__line {
        width: 21px;
        height: 1px;
        border-radius: 999px;
        background: #c6a15b;
    }
}
</style>
