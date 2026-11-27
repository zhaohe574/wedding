<template>
    <view class="conds">
        <scroll-view class="conds__scroll" scroll-x :show-scrollbar="false">
            <view class="conds__row">
                <!-- 地区：硬必选。有 *、无 ×；未选时虚线边 -->
                <view
                    class="cond"
                    :class="{ 'cond--ph': !region }"
                    hover-class="is-pressed"
                    @tap="emit('region')"
                >
                    <text class="cond__req">*</text>
                    <text class="cond__text" :class="{ 'is-ph': !region }">
                        {{ regionLabel(region) }}
                    </text>
                </view>

                <view class="cond" hover-class="is-pressed" @tap="emit('category')">
                    <text class="cond__text" :class="{ 'is-ph': !categoryName }">
                        {{ categoryName || '全部类型' }}
                    </text>
                    <view
                        v-if="categoryName"
                        class="cond__x"
                        @tap.stop="emit('category-clear')"
                    >
                        <tn-icon name="close" :size="18" class="cond__x-ic" />
                    </view>
                </view>

                <view class="cond" hover-class="is-pressed" @tap="emit('style')">
                    <text class="cond__text" :class="{ 'is-ph': !styleSelected }">
                        {{ styleText || '风格不限' }}
                    </text>
                    <view v-if="styleSelected" class="cond__x" @tap.stop="emit('style-clear')">
                        <tn-icon name="close" :size="18" class="cond__x-ic" />
                    </view>
                </view>

                <view
                    class="cond cond--icon"
                    hover-class="is-pressed"
                    aria-role="button"
                    aria-label="搜索人员"
                    @tap="emit('toggle-search')"
                >
                    <tn-icon name="search" :size="26" class="cond__ic" />
                </view>
            </view>
        </scroll-view>

        <!-- 搜索行：点放大镜才展开，确认后走一次查询 -->
        <view v-if="searchOpen" class="search">
            <tn-icon name="search" :size="26" class="search__ic" />
            <input
                class="search__input"
                type="text"
                :value="keyword"
                confirm-type="search"
                placeholder="搜人员名字、简介或风格"
                placeholder-class="field__ph"
                :focus="searchOpen"
                @input="onInput"
                @confirm="emit('confirm-keyword')"
            />
            <view v-if="keyword" class="search__clear" @tap="emit('clear-keyword')">
                <tn-icon name="close-circle" :size="28" class="search__clear-ic" />
            </view>
            <text class="search__cancel" @tap="emit('toggle-search')">取消</text>
        </view>
    </view>
</template>

<script setup lang="ts">
/**
 * 查询条件 chips：地区 / 类型 / 风格 / 搜索。
 *
 * 地区是唯一必选项：有 `*`、无 `×`，未选时虚线边。类型与风格可选，带 `×` 可单独清空。
 * 搜索默认收起，避免首屏再出现一条输入框；展开后确认关键词才发起查询。
 */
import type { PickedRegion } from '@/composables/useCatalogCache'

withDefaults(
    defineProps<{
        region: PickedRegion | null
        categoryName: string
        /** 已格式化的风格文案：先选类型 / 风格不限 / 大气、中式 / 已选 3 项 */
        styleText: string
        styleSelected: boolean
        keyword: string
        searchOpen: boolean
    }>(),
    {
        categoryName: '',
        styleText: '',
        styleSelected: false,
        keyword: '',
        searchOpen: false
    }
)

const emit = defineEmits<{
    (e: 'region'): void
    (e: 'category'): void
    (e: 'category-clear'): void
    (e: 'style'): void
    (e: 'style-clear'): void
    (e: 'toggle-search'): void
    (e: 'update:keyword', value: string): void
    (e: 'confirm-keyword'): void
    (e: 'clear-keyword'): void
}>()

function onInput(e: any) {
    emit('update:keyword', String(e?.detail?.value ?? ''))
}

/** 「杭州市·西湖区」→「杭州·西湖区」：chip 只放最短可辨识文案，全称留在 sheet 里 */
function regionLabel(r: PickedRegion | null) {
    if (!r) return '选择地区'
    return `${r.cityName.replace(/市$/, '')}·${r.districtName}`
}
</script>

<style lang="scss" scoped>
.conds {
    width: 100%;

    &__scroll {
        width: 100%;
        height: 88rpx;
        white-space: nowrap;
    }

    &__row {
        display: inline-flex;
        align-items: center;
        gap: 12rpx;
        height: 88rpx;
        padding-right: 30rpx;
    }
}

.cond {
    position: relative;
    height: 56rpx;
    padding: 0 20rpx;
    border-radius: var(--r-pill);
    border: 1rpx solid var(--c-control-border);
    background: var(--c-surface);
    display: inline-flex;
    align-items: center;
    gap: 6rpx;
    flex: none;

    /* 视觉 56rpx，热区纵向扩到 88rpx；相邻 chip 横向 gap 12rpx 保留视觉呼吸 */
    &::before {
        content: '';
        position: absolute;
        top: -16rpx;
        bottom: -16rpx;
        left: 0;
        right: 0;
    }

    &--ph {
        border-style: dashed;
    }

    &--icon {
        width: 56rpx;
        padding: 0;
        justify-content: center;
    }

    &__req {
        font-size: var(--fs-xs);
        color: var(--c-danger);
        line-height: var(--lh-none);
    }

    &__text {
        font-size: var(--fs-xs);
        color: var(--c-ink);
        white-space: nowrap;
        max-width: 300rpx;
        overflow: hidden;
        text-overflow: ellipsis;

        &.is-ph {
            color: var(--c-ink-3);
        }
    }

    &__x {
        width: 40rpx;
        height: 40rpx;
        margin-right: -12rpx;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    &__x-ic {
        color: var(--c-ink-3);
    }

    &__ic {
        color: var(--c-ink-2);
    }
}

.search {
    display: flex;
    align-items: center;
    gap: 12rpx;
    height: 88rpx;
    margin-top: 12rpx;
    padding-top: 12rpx;
    border-top: 1rpx solid var(--c-line);

    &__ic {
        color: var(--c-ink-3);
        flex: none;
    }

    &__input {
        flex: 1;
        min-width: 0;
        font-size: var(--fs-26);
        color: var(--c-ink);
    }

    &__clear {
        width: 40rpx;
        height: 40rpx;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    &__clear-ic {
        color: var(--c-ink-3);
    }

    &__cancel {
        font-size: var(--fs-xs);
        color: var(--c-ink-3);
        padding-left: 8rpx;
    }
}
</style>
