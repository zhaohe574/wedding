<template>
    <div class="info-card">
        <div class="info-card__head">
            <span class="info-card__title">{{ header }}</span>
            <NuxtLink v-if="link" class="info-card__more" :to="link">
                更多
                <ElIcon><ArrowRight /></ElIcon>
            </NuxtLink>
        </div>
        <slot name="content" :data="data" v-if="data.length">
            <div class="info-card__body">
                <template v-for="(item, index) in data" :key="item.id">
                    <slot name="item" :item="item" :index="index">
                        <InformationItems
                            :index="index"
                            :show-sort="showSort"
                            :id="item.id"
                            :title="item.title"
                            :desc="item.desc"
                            :click="item.click"
                            :author="item.author"
                            :create-time="item.create_time"
                            :image="item.image"
                            :only-title="onlyTitle"
                            :image-size="imageSize"
                            :show-author="showAuthor"
                            :show-desc="showDesc"
                            :show-click="showClick"
                            :border="border"
                            :title-line="titleLine"
                            :show-time="showTime"
                            :source="source"
                        />
                    </slot>
                </template>
            </div>
        </slot>
        <div class="info-card__empty" v-else>暂无资讯</div>
    </div>
</template>
<script lang="ts" setup>
import { ElIcon } from 'element-plus'
import { ArrowRight } from '@element-plus/icons-vue'
import type { PropType } from 'vue'
defineProps({
    header: {
        type: String,
        default: ''
    },
    link: {
        type: String,
        default: ''
    },
    data: {
        type: Array as PropType<any[]>,
        default: () => []
    },
    source: {
        type: String,
        default: 'default'
    },
    onlyTitle: {
        type: Boolean,
        default: true
    },
    titleLine: {
        type: Number,
        default: 1
    },
    border: {
        type: Boolean,
        default: true
    },
    imageSize: {
        type: String,
        default: 'default'
    },
    showAuthor: {
        type: Boolean,
        default: true
    },
    showDesc: {
        type: Boolean,
        default: true
    },
    showClick: {
        type: Boolean,
        default: true
    },
    showTime: {
        type: Boolean,
        default: true
    },
    showSort: {
        type: Boolean,
        default: true
    }
})
</script>
<style lang="scss" scoped>
.info-card {
    overflow: hidden;
    border: 1px solid var(--pc-line);
    border-radius: 14px;
    background: var(--pc-surface);

    &__head {
        display: flex;
        align-items: center;
        gap: 16px;
        height: 60px;
        margin: 0 20px;
        border-bottom: 1px solid var(--pc-line);
    }

    &__title {
        position: relative;
        display: flex;
        flex: 1;
        align-items: center;
        min-width: 0;
        height: 100%;
        overflow: hidden;
        color: var(--pc-ink);
        font-family: var(--font-display);
        font-size: 20px;
        font-weight: 400;
        line-height: 1;
        white-space: nowrap;
        text-overflow: ellipsis;

        &::after {
            content: '';
            position: absolute;
            left: 0;
            bottom: 0;
            width: 28px;
            height: 2px;
            background: var(--pc-gold);
        }
    }

    &__more {
        display: inline-flex;
        flex: none;
        align-items: center;
        gap: 2px;
        color: var(--pc-ink-2);
        font-size: 13px;
        text-decoration: none;
        transition: color var(--dur-fast) var(--ease-out);

        &:hover {
            color: var(--pc-gold);
        }
    }

    &__body {
        padding: 12px 20px 20px;
    }

    &__empty {
        display: flex;
        align-items: center;
        justify-content: center;
        min-height: 48px;
        padding: 12px 20px;
        color: var(--pc-ink-3);
        font-size: 13px;
    }
}
</style>
