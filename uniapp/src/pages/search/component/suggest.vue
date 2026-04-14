<template>
    <view class="suggest-container">
        <!-- 热门搜索 -->
        <view class="suggest-section" v-if="hot_search.status == 1 && searchData.length">
            <view class="section-header">
                <tn-icon name="fire" size="32" :color="$theme.primaryColor" />
                <text class="section-title">热门搜索</text>
            </view>

            <view class="keyword-list">
                <view
                    v-for="(hotItem, index) in searchData"
                    :key="index"
                    class="keyword-tag"
                    :style="{
                        backgroundColor: alphaColor($theme.primaryColor, 0.08),
                        color: $theme.primaryColor
                    }"
                    @click="handleHistoreSearch(hotItem.name)"
                >
                    <text class="keyword-text">{{ hotItem.name }}</text>
                </view>
            </view>
        </view>

        <!-- 分割线 -->
        <view
            class="divider"
            v-if="hot_search.status == 1 && searchData.length && his_search.length"
        ></view>

        <!-- 历史搜索 -->
        <view class="suggest-section" v-if="his_search.length">
            <view class="section-header">
                <tn-icon name="clock" size="32" color="#999999" />
                <text class="section-title">历史搜索</text>
                <view class="flex-1"></view>
                <view class="clear-btn" @click="() => emit('clear')">
                    <tn-icon name="delete" size="28" color="#999999" />
                    <text class="clear-text">清空</text>
                </view>
            </view>

            <view class="keyword-list">
                <view
                    v-for="(hisItem, index) in his_search"
                    :key="index"
                    class="keyword-tag history-tag"
                    @click="handleHistoreSearch(hisItem)"
                >
                    <text class="keyword-text">{{ hisItem }}</text>
                </view>
            </view>
        </view>

        <!-- 空状态 -->
        <view class="empty-state" v-if="!searchData.length && !his_search.length">
            <tn-icon name="search" size="120" color="#E5E5E5" />
            <text class="empty-text">暂无搜索记录</text>
            <text class="empty-hint">试试搜索人员、服务或作品</text>
        </view>
    </view>
</template>

<script lang="ts" setup>
import { computed } from 'vue'
import { useThemeStore } from '@/stores/theme'
import { alphaColor } from '@/utils/color'

const $theme = useThemeStore()

const emit = defineEmits<{
    (event: 'search', value: string): void
    (event: 'clear', value: void): void
}>()

const props = withDefaults(
    defineProps<{
        hot_search?: {
            data: any[]
            status: number
        }
        his_search?: string[]
    }>(),
    {
        hot_search: () => ({
            data: [],
            status: 0
        }),
        his_search: () => []
    }
)

const searchData = computed(() => {
    return props.hot_search.data.filter((item) => item.name)
})

const handleHistoreSearch = (text: string) => {
    emit('search', text)
}
</script>

<style lang="scss" scoped>
.suggest-container {
    height: 100%;
    background: transparent;
    padding: 12rpx 0 24rpx;
}

.suggest-section {
    padding: 0 37rpx;
    margin-bottom: 32rpx;
}

.section-header {
    display: flex;
    align-items: center;
    gap: 12rpx;
    margin-bottom: 24rpx;
}

.section-title {
    font-size: 32rpx;
    font-weight: 700;
    color: var(--wm-text-primary, #1e2432);
}

.flex-1 {
    flex: 1;
}

.clear-btn {
    display: flex;
    align-items: center;
    gap: 6rpx;
    padding: 10rpx 18rpx;
    border-radius: 999rpx;
    background: rgba(255, 255, 255, 0.84);
    border: 1rpx solid var(--wm-color-border, #efe6e1);
    transition: all 0.2s ease;

    &:active {
        opacity: 0.82;
    }
}

.clear-text {
    font-size: 24rpx;
    color: var(--wm-text-secondary, #7f7b78);
}

.keyword-list {
    display: flex;
    flex-wrap: wrap;
    gap: 16rpx;
}

.keyword-tag {
    padding: 12rpx 24rpx;
    border-radius: 999rpx;
    font-size: 28rpx;
    background: rgba(255, 255, 255, 0.88);
    border: 1rpx solid var(--wm-color-border, #efe6e1);
    transition: all 0.2s ease;
    max-width: 100%;

    &:active {
        transform: scale(0.96);
        opacity: 0.8;
    }
}

.history-tag {
    background: rgba(255, 255, 255, 0.88);
    color: var(--wm-text-secondary, #7f7b78);

    &:active {
        opacity: 0.82;
    }
}

.keyword-text {
    display: block;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.divider {
    height: 1rpx;
    background: rgba(239, 230, 225, 0.92);
    margin: 32rpx 37rpx;
}

.empty-state {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 120rpx 48rpx;
    text-align: center;
}

.empty-text {
    margin-top: 32rpx;
    font-size: 32rpx;
    font-weight: 700;
    color: var(--wm-text-primary, #1e2432);
}

.empty-hint {
    margin-top: 16rpx;
    font-size: 26rpx;
    color: var(--wm-text-secondary, #7f7b78);
}
</style>
