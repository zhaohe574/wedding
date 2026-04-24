<template>
    <view v-if="content.enabled && showList.length" class="service-process-widget">
        <!-- 标题区域 -->
        <view v-if="content.title" class="process-header">
            <view class="header-decoration" :style="$theme.titleBar.value"></view>
            <text class="header-title">{{ content.title }}</text>
        </view>

        <!-- 时间轴样式 -->
        <view v-if="content.style == 1" class="timeline-style">
            <view
                v-for="(item, index) in showList"
                :key="index"
                class="timeline-item"
                :class="{ 'is-last': index === showList.length - 1 }"
            >
                <!-- 左侧：图标和连接线 -->
                <view class="timeline-left">
                    <view
                        class="icon-container"
                        :style="$theme.iconBg.value"
                        :class="[
                            getIconAnimation(index),
                            content.icon_style == 2 ? 'square' : 'circle'
                        ]"
                    >
                        <image
                            v-if="item.icon"
                            :src="item.icon"
                            class="step-icon"
                            mode="aspectFit"
                        />
                    </view>
                    <view
                        v-if="index < showList.length - 1"
                        class="timeline-connector"
                        :style="{
                            background: content.line_color || $theme.lineGradientV.value
                        }"
                    ></view>
                </view>

                <!-- 右侧：内容 -->
                <view class="timeline-right">
                    <view class="step-badge" :style="$theme.badge.value">步骤 {{ index + 1 }}</view>
                    <view class="step-title">{{ item.title }}</view>
                    <view class="step-description">{{ item.description }}</view>
                </view>
            </view>
        </view>

        <!-- 步骤卡片样式 -->
        <view v-if="content.style == 2" class="card-style">
            <view v-for="(item, index) in showList" :key="index" class="step-card">
                <!-- 顶部进度条 -->
                <view class="card-progress-bar">
                    <view
                        class="progress-fill"
                        :style="{
                            width: ((index + 1) / showList.length) * 100 + '%',
                            background: $theme.progressGradient.value
                        }"
                    ></view>
                </view>

                <view class="card-content">
                    <!-- 左侧：序号和图标 -->
                    <view class="card-left">
                        <view class="step-number-circle" :style="$theme.badge.value">
                            <text class="number-text">{{ index + 1 }}</text>
                        </view>
                        <image
                            v-if="item.icon"
                            :src="item.icon"
                            class="card-icon"
                            mode="aspectFit"
                        />
                    </view>

                    <!-- 右侧：内容 -->
                    <view class="card-right">
                        <view class="card-title">{{ item.title }}</view>
                        <view class="card-description">{{ item.description }}</view>
                    </view>
                </view>
            </view>
        </view>

        <!-- 横向流程样式 -->
        <view v-if="content.style == 3" class="horizontal-style">
            <scroll-view scroll-x class="scroll-container" show-scrollbar="false">
                <view class="steps-wrapper">
                    <view v-for="(item, index) in showList" :key="index" class="horizontal-step">
                        <!-- 步骤内容 -->
                        <view class="step-box">
                            <view class="step-number-badge" :style="$theme.badge.value">{{
                                index + 1
                            }}</view>
                            <view
                                class="step-icon-wrapper"
                                :style="$theme.iconBg.value"
                                :class="content.icon_style == 2 ? 'square' : 'circle'"
                            >
                                <image
                                    v-if="item.icon"
                                    :src="item.icon"
                                    class="horizontal-icon"
                                    mode="aspectFit"
                                />
                            </view>
                            <view class="step-info">
                                <text class="step-name">{{ item.title }}</text>
                                <text class="step-desc">{{ item.description }}</text>
                            </view>
                        </view>

                        <!-- 箭头连接 -->
                        <view v-if="index < showList.length - 1" class="step-arrow">
                            <view
                                class="arrow-line"
                                :style="{
                                    background: content.line_color || $theme.progressGradient.value
                                }"
                            ></view>
                            <view
                                class="arrow-head"
                                :style="{ color: content.line_color || primaryColor }"
                                >›</view
                            >
                        </view>
                    </view>
                </view>
            </scroll-view>
        </view>
    </view>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { useThemeStore } from '@/stores/theme'
import { tintColor, alphaColor } from '@/utils/color'

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

const themeStore = useThemeStore()
const primaryColor = computed(() => themeStore.primaryColor || '#0B0B0B')

// 主题内联样式（兼容小程序，不使用CSS变量）
const $theme = {
    titleBar: computed(() => ({
        background: `linear-gradient(180deg, ${primaryColor.value} 0%, ${tintColor(
            primaryColor.value,
            0.3
        )} 100%)`
    })),
    iconBg: computed(() => ({
        background: `linear-gradient(135deg, ${tintColor(primaryColor.value, 0.85)} 0%, ${tintColor(
            primaryColor.value,
            0.7
        )} 100%)`,
        boxShadow: `0 6rpx 20rpx ${alphaColor(primaryColor.value, 0.15)}`
    })),
    badge: computed(() => ({
        background: `linear-gradient(135deg, ${primaryColor.value} 0%, ${tintColor(
            primaryColor.value,
            0.3
        )} 100%)`,
        boxShadow: `0 4rpx 16rpx ${alphaColor(primaryColor.value, 0.3)}`
    })),
    lineGradientV: computed(
        () =>
            `linear-gradient(180deg, ${primaryColor.value} 0%, ${tintColor(
                primaryColor.value,
                0.7
            )} 100%)`
    ),
    progressGradient: computed(
        () =>
            `linear-gradient(90deg, ${primaryColor.value} 0%, ${tintColor(
                primaryColor.value,
                0.3
            )} 100%)`
    )
}

// 过滤显示的步骤
const showList = computed(() => {
    return props.content.data?.filter((item: any) => item.is_show == '1') || []
})

// 获取图标动画类
const getIconAnimation = (index: number): string => {
    const animations = ['anim-1', 'anim-2', 'anim-3']
    return animations[index % animations.length]
}
</script>

<style scoped lang="scss">
.service-process-widget {
    margin: 20rpx;

    /* 标题区域 */
    .process-header {
        display: flex;
        align-items: center;
        margin-bottom: 20rpx;
        gap: 12rpx;

        .header-decoration {
            width: 6rpx;
            height: 32rpx;
            border-radius: 3rpx;
        }

        .header-title {
            font-size: 32rpx;
            font-weight: 700;
            color: #111111;
            letter-spacing: 0;
        }
    }

    /* 时间轴样式 */
    .timeline-style {
        background: #ffffff;
        border-radius: 14rpx;
        padding: 20rpx 16rpx;
        box-shadow: 0 4rpx 16rpx rgba(17, 17, 17, 0.06);

        .timeline-item {
            display: flex;
            gap: 16rpx;
            position: relative;

            &:not(.is-last) {
                padding-bottom: 32rpx;
            }
        }

        .timeline-left {
            position: relative;
            display: flex;
            flex-direction: column;
            align-items: center;
            flex-shrink: 0;
        }

        .icon-container {
            width: 64rpx;
            height: 64rpx;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 4rpx solid #ffffff;
            position: relative;
            z-index: 2;

            &.circle {
                border-radius: 50%;
            }

            &.square {
                border-radius: 12rpx;
            }

            &.anim-1 {
                animation: pulse-1 2s ease-in-out infinite;
            }

            &.anim-2 {
                animation: pulse-2 2.5s ease-in-out infinite;
            }

            &.anim-3 {
                animation: pulse-3 3s ease-in-out infinite;
            }

            .step-icon {
                width: 36rpx;
                height: 36rpx;
            }
        }

        .timeline-connector {
            position: absolute;
            top: 64rpx;
            left: 50%;
            transform: translateX(-50%);
            width: 3rpx;
            height: calc(100% - 40rpx);
            z-index: 1;
        }

        .timeline-right {
            flex: 1;
            padding-top: 4rpx;
        }

        .step-badge {
            display: inline-block;
            padding: 4rpx 10rpx;
            color: #ffffff;
            font-size: 20rpx;
            font-weight: 700;
            border-radius: 12rpx;
            margin-bottom: 8rpx;
            letter-spacing: 0;
        }

        .step-title {
            font-size: 28rpx;
            font-weight: 700;
            color: #111111;
            margin-bottom: 6rpx;
            line-height: 1.4;
        }

        .step-description {
            font-size: 24rpx;
            color: #5f5a50;
            line-height: 1.5;
        }
    }

    /* 脉冲动画 */
    @keyframes pulse-1 {
        0%,
        100% {
            transform: scale(1);
        }
        50% {
            transform: scale(1.05);
        }
    }

    @keyframes pulse-2 {
        0%,
        100% {
            transform: scale(1) rotate(0deg);
        }
        50% {
            transform: scale(1.08) rotate(5deg);
        }
    }

    @keyframes pulse-3 {
        0%,
        100% {
            transform: scale(1);
        }
        33% {
            transform: scale(1.06);
        }
        66% {
            transform: scale(0.98);
        }
    }

    /* 步骤卡片样式 */
    .card-style {
        display: flex;
        flex-direction: column;
        gap: 16rpx;

        .step-card {
            background: #ffffff;
            border-radius: 14rpx;
            overflow: hidden;
            box-shadow: 0 4rpx 16rpx rgba(17, 17, 17, 0.06);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);

            &:active {
                transform: translateY(4rpx);
                box-shadow: 0 2rpx 12rpx rgba(17, 17, 17, 0.08);
            }
        }

        .card-progress-bar {
            height: 6rpx;
            background: #f8f7f2;
            position: relative;
            overflow: hidden;

            .progress-fill {
                height: 100%;
                transition: width 0.6s cubic-bezier(0.4, 0, 0.2, 1);
            }
        }

        .card-content {
            display: flex;
            gap: 16rpx;
            padding: 20rpx 16rpx;
        }

        .card-left {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 10rpx;
            flex-shrink: 0;
        }

        .step-number-circle {
            width: 48rpx;
            height: 48rpx;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;

            .number-text {
                font-size: 26rpx;
                font-weight: 800;
                color: #ffffff;
            }
        }

        .card-icon {
            width: 52rpx;
            height: 52rpx;
            border-radius: 10rpx;
            background: #ffffff;
            padding: 8rpx;
        }

        .card-right {
            flex: 1;
            display: flex;
            flex-direction: column;
            gap: 8rpx;
        }

        .card-title {
            font-size: 28rpx;
            font-weight: 700;
            color: #111111;
            line-height: 1.4;
        }

        .card-description {
            font-size: 24rpx;
            color: #5f5a50;
            line-height: 1.5;
        }
    }

    /* 横向流程样式 */
    .horizontal-style {
        .scroll-container {
            white-space: nowrap;

            &::-webkit-scrollbar {
                display: none;
            }
        }

        .steps-wrapper {
            display: inline-flex;
            align-items: center;
            gap: 0;
            padding: 12rpx;
        }

        .horizontal-step {
            display: inline-flex;
            align-items: center;
        }

        .step-box {
            position: relative;
            width: 220rpx;
            background: #ffffff;
            border-radius: 14rpx;
            padding: 20rpx 16rpx;
            box-shadow: 0 4rpx 16rpx rgba(17, 17, 17, 0.06);
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 10rpx;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);

            &:active {
                transform: scale(0.96);
                box-shadow: 0 2rpx 12rpx rgba(17, 17, 17, 0.08);
            }
        }

        .step-number-badge {
            position: absolute;
            top: -8rpx;
            left: 50%;
            transform: translateX(-50%);
            width: 36rpx;
            height: 36rpx;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18rpx;
            font-weight: 800;
            color: #ffffff;
            border: 3rpx solid #ffffff;
        }

        .step-icon-wrapper {
            width: 64rpx;
            height: 64rpx;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-top: 10rpx;

            &.circle {
                border-radius: 50%;
            }

            &.square {
                border-radius: 12rpx;
            }

            .horizontal-icon {
                width: 36rpx;
                height: 36rpx;
            }
        }

        .step-info {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 6rpx;
            width: 100%;
        }

        .step-name {
            font-size: 26rpx;
            font-weight: 700;
            color: #111111;
            text-align: center;
            line-height: 1.4;
        }

        .step-desc {
            font-size: 22rpx;
            color: #5f5a50;
            text-align: center;
            line-height: 1.4;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .step-arrow {
            display: flex;
            align-items: center;
            padding: 0 16rpx;
            position: relative;

            .arrow-line {
                width: 32rpx;
                height: 3rpx;
            }

            .arrow-head {
                font-size: 32rpx;
                font-weight: 300;
                line-height: 1;
                margin-left: -6rpx;
            }
        }
    }

    /* 响应式适配 */
    @media (prefers-reduced-motion: reduce) {
        .service-process-widget * {
            transition: none !important;
            animation: none !important;
        }
    }
}
</style>
