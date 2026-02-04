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
                        backgroundColor: getLightColor($theme.primaryColor, 0.08),
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
        <view 
            class="empty-state" 
            v-if="!searchData.length && !his_search.length"
        >
            <tn-icon name="search" size="120" color="#E5E5E5" />
            <text class="empty-text">暂无搜索记录</text>
            <text class="empty-hint">试试搜索人员、服务或作品</text>
        </view>
    </view>
</template>

<script lang="ts" setup>
import { computed } from 'vue'
import { useThemeStore } from '@/stores/theme'

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

// 获取浅色变体
const getLightColor = (color: string, opacity: number) => {
    const hex = color.replace('#', '')
    const r = parseInt(hex.substring(0, 2), 16)
    const g = parseInt(hex.substring(2, 4), 16)
    const b = parseInt(hex.substring(4, 6), 16)
    return `rgba(${r}, ${g}, ${b}, ${opacity})`
}
</script>

<style lang="scss" scoped>
.suggest-container {
    height: 100%;
    background: #ffffff;
    padding: 24rpx 0;
}

.suggest-section {
    padding: 0 24rpx;
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
    font-weight: 600;
    color: #333333;
}

.flex-1 {
    flex: 1;
}

.clear-btn {
    display: flex;
    align-items: center;
    gap: 6rpx;
    padding: 8rpx 16rpx;
    border-radius: 999rpx;
    background: #f5f5f5;
    transition: all 0.2s ease;

    &:active {
        background: #e5e5e5;
    }
}

.clear-text {
    font-size: 24rpx;
    color: #999999;
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
    transition: all 0.2s ease;
    max-width: 100%;

    &:active {
        transform: scale(0.96);
        opacity: 0.8;
    }
}

.history-tag {
    background: #f5f5f5;
    color: #666666;

    &:active {
        background: #e5e5e5;
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
    background: #f0f0f0;
    margin: 32rpx 24rpx;
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
    font-weight: 500;
    color: #999999;
}

.empty-hint {
    margin-top: 16rpx;
    font-size: 26rpx;
    color: #cccccc;
}
</style>
