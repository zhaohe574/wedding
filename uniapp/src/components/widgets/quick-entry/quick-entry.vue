<template>
    <view v-if="content.enabled && showList.length" class="quick-entry-widget">
        <!-- 网格布局 -->
        <view v-if="content.style == 1" class="grid-layout">
            <view
                class="entries-grid"
                :style="{ 'grid-template-columns': `repeat(${content.per_line}, 1fr)` }"
            >
                <view
                    v-for="(item, index) in showList"
                    :key="index"
                    class="entry-item"
                    @click="handleClick(item.link)"
                >
                    <!-- 图标容器 -->
                    <view class="icon-wrapper">
                        <view class="icon-bg"></view>
                        <image
                            lazy-load
                            class="entry-icon"
                            :src="getImageUrl(item.icon)"
                            :alt="item.title"
                            mode="aspectFit"
                        />
                        <!-- 悬浮光晕效果 -->
                        <view class="icon-glow"></view>
                    </view>
                    <!-- 标题 -->
                    <text class="entry-title">{{ item.title }}</text>
                </view>
            </view>
        </view>

        <!-- 横向滑动 -->
        <view v-if="content.style == 2" class="scroll-layout">
            <scroll-view scroll-x class="scroll-container" show-scrollbar="false">
                <view class="entries-scroll">
                    <view
                        v-for="(item, index) in showList"
                        :key="index"
                        class="entry-item"
                        @click="handleClick(item.link)"
                    >
                        <!-- 图标容器 -->
                        <view class="icon-wrapper">
                            <view class="icon-bg"></view>
                            <image
                                lazy-load
                                class="entry-icon"
                                :src="getImageUrl(item.icon)"
                                :alt="item.title"
                                mode="aspectFit"
                            />
                            <!-- 悬浮光晕效果 -->
                            <view class="icon-glow"></view>
                        </view>
                        <!-- 标题 -->
                        <text class="entry-title">{{ item.title }}</text>
                    </view>
                </view>
            </scroll-view>
        </view>
    </view>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { useAppStore } from '@/stores/app'
import { navigateTo } from '@/utils/util'

const props = defineProps({
    content: {
        type: Object,
        default: () => ({})
    },
    styles: {
        type: Object,
        default: () => ({})
    }
})

const { getImageUrl } = useAppStore()

// 过滤显示的入口
const showList = computed(() => {
    return props.content.data?.filter((item: any) => item.is_show == '1') || []
})

// 处理点击事件
const handleClick = (link: any) => {
    navigateTo(link)
}
</script>

<style scoped lang="scss">
.quick-entry-widget {
    margin: 20rpx;
    
    /* 网格布局样式 */
    .grid-layout {
        background: #FFFFFF;
        border-radius: 24rpx;
        padding: 32rpx 20rpx;
        box-shadow: 0 4rpx 20rpx rgba(15, 23, 42, 0.06);
        
        .entries-grid {
            display: grid;
            gap: 32rpx 16rpx;
            width: 100%;
        }
    }
    
    /* 横向滑动样式 */
    .scroll-layout {
        background: #FFFFFF;
        border-radius: 24rpx;
        padding: 32rpx 0;
        box-shadow: 0 4rpx 20rpx rgba(15, 23, 42, 0.06);
        
        .scroll-container {
            white-space: nowrap;
            
            &::-webkit-scrollbar {
                display: none;
            }
        }
        
        .entries-scroll {
            display: inline-flex;
            gap: 32rpx;
            padding: 0 32rpx;
        }
    }
    
    /* 入口项通用样式 */
    .entry-item {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 16rpx;
        cursor: pointer;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        
        /* 点击反馈 */
        &:active {
            transform: scale(0.92);
            
            .icon-wrapper {
                .icon-bg {
                    transform: scale(1.1);
                    opacity: 0.8;
                }
                
                .entry-icon {
                    transform: scale(0.9);
                }
                
                .icon-glow {
                    opacity: 1;
                }
            }
            
            .entry-title {
                color: #0369A1;
            }
        }
    }
    
    /* 图标容器 */
    .icon-wrapper {
        position: relative;
        width: 96rpx;
        height: 96rpx;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        
        /* 背景装饰 */
        .icon-bg {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(135deg, #F8FAFC 0%, #E0F2FE 100%);
            border-radius: 24rpx;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            z-index: 0;
        }
        
        /* 图标 */
        .entry-icon {
            width: 56rpx;
            height: 56rpx;
            position: relative;
            z-index: 2;
            transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        /* 光晕效果 */
        .icon-glow {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 120rpx;
            height: 120rpx;
            background: radial-gradient(circle, rgba(3, 105, 161, 0.2) 0%, transparent 70%);
            border-radius: 50%;
            opacity: 0;
            transition: opacity 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            z-index: 1;
            pointer-events: none;
        }
    }
    
    /* 标题 */
    .entry-title {
        font-size: 26rpx;
        font-weight: 500;
        color: #334155;
        text-align: center;
        line-height: 1.4;
        max-width: 120rpx;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
        transition: color 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }
    
    /* 响应式适配 - 尊重用户的动画偏好 */
    @media (prefers-reduced-motion: reduce) {
        .quick-entry-widget * {
            transition: none !important;
            animation: none !important;
        }
    }
}
</style>
