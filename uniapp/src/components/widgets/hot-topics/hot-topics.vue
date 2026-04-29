<template>
    <view v-if="content.enabled && topicList.length" class="hot-topics-widget">
        <!-- 标题区域 -->
        <view v-if="content.title" class="topic-header">
            <view class="header-decoration" :style="headerBarStyle"></view>
            <text class="header-title">{{ content.title }}</text>
        </view>

        <!-- 标签云样式 -->
        <view v-if="content.style == 1" class="tag-cloud-style" :style="tagCloudStyle">
            <!-- 装饰性背景元素 -->
            <view class="cloud-decoration">
                <view class="bubble bubble-1"></view>
                <view class="bubble bubble-2"></view>
                <view class="bubble bubble-3"></view>
            </view>

            <view class="tags-container">
                <view
                    v-for="(item, index) in displayList"
                    :key="index"
                    class="topic-tag"
                    :style="getTagVisualStyle(index)"
                    @click="handleClick(item)"
                >
                    <view class="tag-glow"></view>
                    <view class="tag-content">
                        <text class="tag-hash" :style="getTagTextStyle(index)">#</text>
                        <text class="tag-name" :style="getTagTextStyle(index)">{{
                            item.name
                        }}</text>
                        <view
                            v-if="item.count"
                            class="tag-count-badge"
                            :style="getTagBadgeStyle(index)"
                        >
                            <text class="count-text">{{ formatCount(item.count) }}</text>
                        </view>
                    </view>
                </view>
            </view>
        </view>

        <!-- 横向滑动样式 -->
        <view v-if="content.style == 2" class="horizontal-scroll-style">
            <scroll-view scroll-x class="scroll-container" show-scrollbar="false">
                <view class="cards-wrapper">
                    <view
                        v-for="(item, index) in displayList"
                        :key="index"
                        class="topic-card"
                        @click="handleClick(item)"
                    >
                        <!-- 背景装饰 -->
                        <view class="card-bg-pattern" :class="getCardPattern(index)"></view>

                        <!-- 卡片内容 -->
                        <view class="card-content">
                            <!-- 顶部：话题名称 -->
                            <view class="card-header">
                                <text class="topic-name">{{ item.name }}</text>
                            </view>

                            <!-- 底部：统计信息 -->
                            <view class="card-footer">
                                <view class="stat-group">
                                    <view class="stat-item">
                                        <text class="stat-label">参与</text>
                                        <text
                                            class="stat-value"
                                            :style="getStatValueStyle(index)"
                                            >{{ formatCount(item.count || 0) }}</text
                                        >
                                    </view>
                                </view>
                                <view class="arrow-icon" :style="getArrowStyle(index)">→</view>
                            </view>
                        </view>

                        <!-- 装饰性光晕 -->
                        <view class="card-shine"></view>
                    </view>
                </view>
            </scroll-view>
        </view>

        <!-- 列表式样式 -->
        <view v-if="content.style == 3" class="list-style">
            <view
                v-for="(item, index) in displayList"
                :key="index"
                class="topic-list-item"
                @click="handleClick(item)"
            >
                <!-- 左侧：排名 -->
                <view class="item-left">
                    <view
                        class="rank-badge"
                        :class="getRankClass(index)"
                        :style="getRankBadgeStyle(index)"
                    >
                        <text class="rank-number">{{ index + 1 }}</text>
                    </view>
                </view>

                <!-- 中间：话题信息 -->
                <view class="item-center">
                    <view class="topic-name-row">
                        <text class="topic-name">{{ item.name }}</text>
                        <view v-if="index < 3" class="hot-badge">
                            <text class="hot-text">HOT</text>
                        </view>
                    </view>
                    <view class="topic-stats">
                        <view class="stat-chip">
                            <text class="stat-text">{{ formatCount(item.count || 0) }} 人参与</text>
                        </view>
                    </view>
                </view>

                <!-- 右侧：箭头 -->
                <view class="item-right">
                    <view class="arrow-circle" :style="getArrowCircleStyle(index)">
                        <text class="arrow-text">›</text>
                    </view>
                </view>

                <!-- 装饰性渐变条 -->
                <view
                    class="item-gradient-bar"
                    :class="getGradientBarClass(index)"
                    :style="getGradientBarStyle(index)"
                ></view>
            </view>
        </view>
    </view>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useThemeStore } from '@/stores/theme'
import { alphaColor, tintColor, shadeColor } from '@/utils/color'

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

const topicList = ref<any[]>([])
const themeStore = useThemeStore()
const palette = computed(() => [
    themeStore.primaryColor || '#0B0B0B',
    themeStore.ctaColor || '#9F7A2E',
    themeStore.secondaryColor || '#C8A45D',
    themeStore.accentColor || '#C8A45D'
])
const pickColor = (index: number) => palette.value[index % palette.value.length]
const headerBarStyle = computed(() => ({
    background: `linear-gradient(180deg, ${palette.value[0]} 0%, ${tintColor(
        palette.value[0],
        0.35
    )} 100%)`,
    boxShadow: `0 4rpx 12rpx ${alphaColor(palette.value[0], 0.12)}`
}))
const tagCloudStyle = computed(() => ({
    background: `linear-gradient(135deg, #FFFFFF 0%, ${tintColor(palette.value[0], 0.9)} 100%)`
}))

// 显示的话题列表（限制数量）
const displayList = computed(() => {
    const count = props.content.show_count || 10
    return topicList.value.slice(0, count)
})

const getTagVisualStyle = (index: number) => {
    const base = pickColor(index)
    const sizes = ['small', 'medium', 'large']
    const rotations = [-2, -1, 0, 1, 2]
    const size = sizes[index % sizes.length]
    const rotation = rotations[index % rotations.length]
    let scale = 1
    if (size === 'small') scale = 0.9
    if (size === 'large') scale = 1.1

    return {
        background: `linear-gradient(135deg, ${tintColor(base, 0.85)} 0%, ${tintColor(
            base,
            0.65
        )} 100%)`,
        borderColor: tintColor(base, 0.55),
        boxShadow: `0 4rpx 16rpx ${alphaColor(base, 0.14)}`,
        transform: `scale(${scale}) rotate(${rotation}deg)`
    }
}

const getTagTextStyle = (index: number) => ({
    color: shadeColor(pickColor(index), 0.25)
})

const getTagBadgeStyle = (index: number) => {
    const base = pickColor(index)
    return {
        background: `linear-gradient(135deg, ${base} 0%, ${shadeColor(base, 0.2)} 100%)`,
        boxShadow: `0 3rpx 10rpx ${alphaColor(base, 0.18)}`
    }
}

const getArrowStyle = (index: number) => {
    const base = pickColor(index)
    return {
        background: `linear-gradient(135deg, ${base} 0%, ${shadeColor(base, 0.2)} 100%)`,
        boxShadow: `0 4rpx 12rpx ${alphaColor(base, 0.18)}`
    }
}

const getStatValueStyle = (index: number) => ({
    color: pickColor(index)
})

const getGradientBarStyle = (index: number) => {
    const base = pickColor(index)
    return { background: `linear-gradient(180deg, ${base} 0%, ${tintColor(base, 0.35)} 100%)` }
}

const getRankBadgeStyle = (index: number) => {
    const base = index === 0 ? palette.value[3] : pickColor(index)
    return {
        background: `linear-gradient(135deg, ${tintColor(base, 0.25)} 0%, ${shadeColor(
            base,
            0.15
        )} 100%)`,
        color: shadeColor(base, 0.6),
        boxShadow: `0 4rpx 12rpx ${alphaColor(base, 0.1)}`
    }
}

const getArrowCircleStyle = (index: number) => {
    const base = pickColor(index)
    return {
        background: `linear-gradient(135deg, ${tintColor(base, 0.9)} 0%, ${tintColor(
            base,
            0.7
        )} 100%)`,
        boxShadow: `0 4rpx 10rpx ${alphaColor(base, 0.12)}`
    }
}

// 格式化数量显示
const formatCount = (count: number): string => {
    if (count >= 10000) {
        return (count / 10000).toFixed(1) + 'w'
    } else if (count >= 1000) {
        return (count / 1000).toFixed(1) + 'k'
    }
    return count.toString()
}

// 获取卡片背景图案类
const getCardPattern = (index: number): string => {
    const patterns = ['pattern-dots', 'pattern-waves', 'pattern-grid', 'pattern-circles']
    return patterns[index % patterns.length]
}

// 获取渐变条类
const getGradientBarClass = (index: number): string => {
    const classes = ['bar-purple', 'bar-orange', 'bar-blue', 'bar-pink']
    return classes[index % classes.length]
}

// 获取排名样式类
const getRankClass = (index: number): string => {
    if (index === 0) return 'rank-gold'
    if (index === 1) return 'rank-silver'
    if (index === 2) return 'rank-bronze'
    return 'rank-normal'
}

// 处理点击事件
const handleClick = (item: any) => {
    // 跳转到动态页面，并传递话题标签参数
    const topicName = item.name || item.id
    uni.navigateTo({
        url: `/pages/dynamic/dynamic?tag=${encodeURIComponent(topicName)}`
    })
}

// 组件挂载时，从content.data中获取话题数据
onMounted(() => {
    if (props.content.data && Array.isArray(props.content.data)) {
        topicList.value = props.content.data
    }
})
</script>

<style scoped lang="scss">
.hot-topics-widget {
    margin: 20rpx;

    /* 标题区域 */
    .topic-header {
        display: flex;
        align-items: center;
        margin-bottom: 20rpx;
        gap: 16rpx;

        .header-decoration {
            width: 8rpx;
            height: 40rpx;
            background: linear-gradient(180deg, #c8a45d 0%, #d8c28a 100%);
            border-radius: 4rpx;
        }

        .header-title {
            font-size: 36rpx;
            font-weight: 700;
            color: #111111;
            letter-spacing: 0;
        }
    }

    /* 标签云样式 */
    .tag-cloud-style {
        position: relative;
        background: linear-gradient(135deg, #ffffff 0%, #F8F7F2 100%);
        border-radius: 24rpx;
        padding: 28rpx 24rpx;
        overflow: hidden;
        box-shadow: 0 8rpx 32rpx rgba(11, 11, 11, 0.08);

        /* 装饰性背景气泡 */
        .cloud-decoration {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            pointer-events: none;
            z-index: 0;

            .bubble {
                position: absolute;
                border-radius: 50%;
                opacity: 0.08;

                &.bubble-1 {
                    width: 200rpx;
                    height: 200rpx;
                    background: linear-gradient(135deg, #c8a45d, #d8c28a);
                    top: -60rpx;
                    right: -40rpx;
                    animation: float-1 8s ease-in-out infinite;
                }

                &.bubble-2 {
                    width: 150rpx;
                    height: 150rpx;
                    background: linear-gradient(135deg, #9f7a2e, #c8a45d);
                    bottom: -40rpx;
                    left: -30rpx;
                    animation: float-2 10s ease-in-out infinite;
                }

                &.bubble-3 {
                    width: 120rpx;
                    height: 120rpx;
                    background: linear-gradient(135deg, #c8a45d, #d8c28a);
                    top: 50%;
                    right: 10%;
                    animation: float-3 12s ease-in-out infinite;
                }
            }
        }

        .tags-container {
            position: relative;
            z-index: 1;
            display: flex;
            flex-wrap: wrap;
            gap: 12rpx;
            justify-content: center;
        }

        .topic-tag {
            position: relative;
            cursor: pointer;
            border-radius: 32rpx;
            padding: 16rpx 24rpx;
            background: #ffffff;
            border: 3rpx solid transparent;
            transition: all 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
            box-shadow: 0 4rpx 16rpx rgba(0, 0, 0, 0.06);

            /* 发光效果层 */
            .tag-glow {
                position: absolute;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                border-radius: 32rpx;
                opacity: 0;
                transition: opacity 0.4s;
                pointer-events: none;
            }

            &:nth-child(4n + 1) {
                background: linear-gradient(135deg, #F7F0DF 0%, #F7F0DF 100%);
                border-color: #D8C28A;

                .tag-hash {
                    color: #c8a45d;
                }

                .tag-name {
                    color: #9f7a2e;
                }

                .tag-count-badge {
                    background: linear-gradient(135deg, #c8a45d 0%, #c8a45d 100%);
                }

                .tag-glow {
                    background: radial-gradient(
                        circle,
                        rgba(11, 11, 11, 0.3) 0%,
                        transparent 70%
                    );
                }

                &:active {
                    background: linear-gradient(135deg, #c8a45d 0%, #9f7a2e 100%);
                    border-color: #c8a45d;
                    transform: scale(0.95) translateY(4rpx);
                    box-shadow: 0 8rpx 24rpx rgba(11, 11, 11, 0.4);

                    .tag-hash,
                    .tag-name {
                        color: #ffffff;
                    }

                    .tag-count-badge {
                        background: rgba(255, 255, 255, 0.3);

                        .count-text {
                            color: #ffffff;
                        }
                    }

                    .tag-glow {
                        opacity: 1;
                    }
                }
            }

            &:nth-child(4n + 2) {
                background: linear-gradient(135deg, #f8f7f2 0%, #F7F0DF 100%);
                border-color: #D8C28A;

                .tag-hash {
                    color: #9f7a2e;
                }

                .tag-name {
                    color: #9F7A2E;
                }

                .tag-count-badge {
                    background: linear-gradient(135deg, #9f7a2e 0%, #c8a45d 100%);
                }

                .tag-glow {
                    background: radial-gradient(
                        circle,
                        rgba(159, 122, 46, 0.24) 0%,
                        transparent 70%
                    );
                }

                &:active {
                    background: linear-gradient(135deg, #9f7a2e 0%, #9f7a2e 100%);
                    border-color: #9f7a2e;
                    transform: scale(0.95) translateY(4rpx);
                    box-shadow: 0 8rpx 24rpx rgba(159, 122, 46, 0.28);

                    .tag-hash,
                    .tag-name {
                        color: #ffffff;
                    }

                    .tag-count-badge {
                        background: rgba(255, 255, 255, 0.3);

                        .count-text {
                            color: #ffffff;
                        }
                    }

                    .tag-glow {
                        opacity: 1;
                    }
                }
            }

            &:nth-child(4n + 3) {
                background: linear-gradient(135deg, #F8F7F2 0%, #F3F2EE 100%);
                border-color: #D8D3C7;

                .tag-hash {
                    color: #6c665c;
                }

                .tag-name {
                    color: #6C665C;
                }

                .tag-count-badge {
                    background: linear-gradient(135deg, #6c665c 0%, #6C665C 100%);
                }

                .tag-glow {
                    background: radial-gradient(
                        circle,
                        rgba(108, 102, 92, 0.22) 0%,
                        transparent 70%
                    );
                }

                &:active {
                    background: linear-gradient(135deg, #6c665c 0%, #6C665C 100%);
                    border-color: #6c665c;
                    transform: scale(0.95) translateY(4rpx);
                    box-shadow: 0 8rpx 24rpx rgba(108, 102, 92, 0.28);

                    .tag-hash,
                    .tag-name {
                        color: #ffffff;
                    }

                    .tag-count-badge {
                        background: rgba(255, 255, 255, 0.3);

                        .count-text {
                            color: #ffffff;
                        }
                    }

                    .tag-glow {
                        opacity: 1;
                    }
                }
            }

            &:nth-child(4n + 4) {
                background: linear-gradient(135deg, #F8F7F2 0%, #F7F0DF 100%);
                border-color: #D8C28A;

                .tag-hash {
                    color: #c8a45d;
                }

                .tag-name {
                    color: #5A4433;
                }

                .tag-count-badge {
                    background: linear-gradient(135deg, #c8a45d 0%, #d8c28a 100%);
                }

                .tag-glow {
                    background: radial-gradient(
                        circle,
                        rgba(200, 164, 93, 0.24) 0%,
                        transparent 70%
                    );
                }

                &:active {
                    background: linear-gradient(135deg, #0B0B0B 0%, #111111 100%);
                    border-color: #c8a45d;
                    transform: scale(0.95) translateY(4rpx);
                    box-shadow: 0 8rpx 24rpx rgba(200, 164, 93, 0.3);

                    .tag-hash,
                    .tag-name {
                        color: #ffffff;
                    }

                    .tag-count-badge {
                        background: rgba(255, 255, 255, 0.3);

                        .count-text {
                            color: #ffffff;
                        }
                    }

                    .tag-glow {
                        opacity: 1;
                    }
                }
            }

            .tag-content {
                display: flex;
                align-items: center;
                gap: 8rpx;
                position: relative;
                z-index: 1;
            }

            .tag-hash {
                font-size: 32rpx;
                font-weight: 800;
                line-height: 1;
                transition: color 0.4s;
            }

            .tag-name {
                font-size: 28rpx;
                font-weight: 600;
                transition: color 0.4s;
                white-space: nowrap;
            }

            .tag-count-badge {
                padding: 4rpx 12rpx;
                border-radius: 20rpx;
                margin-left: 4rpx;
                transition: background 0.4s;

                .count-text {
                    font-size: 20rpx;
                    font-weight: 700;
                    color: #ffffff;
                    transition: color 0.4s;
                }
            }
        }
    }

    /* 气泡浮动动画 */
    @keyframes float-1 {
        0%,
        100% {
            transform: translate(0, 0) scale(1);
        }
        25% {
            transform: translate(20rpx, -20rpx) scale(1.1);
        }
        50% {
            transform: translate(-10rpx, 10rpx) scale(0.9);
        }
        75% {
            transform: translate(15rpx, 15rpx) scale(1.05);
        }
    }

    @keyframes float-2 {
        0%,
        100% {
            transform: translate(0, 0) scale(1);
        }
        33% {
            transform: translate(-15rpx, 20rpx) scale(1.08);
        }
        66% {
            transform: translate(10rpx, -15rpx) scale(0.92);
        }
    }

    @keyframes float-3 {
        0%,
        100% {
            transform: translate(0, 0) scale(1) rotate(0deg);
        }
        25% {
            transform: translate(10rpx, 15rpx) scale(1.1) rotate(90deg);
        }
        50% {
            transform: translate(-15rpx, -10rpx) scale(0.9) rotate(180deg);
        }
        75% {
            transform: translate(20rpx, -5rpx) scale(1.05) rotate(270deg);
        }
    }

    /* 横向滑动样式 */
    .horizontal-scroll-style {
        .scroll-container {
            white-space: nowrap;

            ::-webkit-scrollbar {
                display: none;
            }
        }

        .cards-wrapper {
            display: inline-flex;
            gap: 20rpx;
            padding: 0 20rpx 16rpx 20rpx;
        }

        .topic-card {
            position: relative;
            width: 300rpx;
            height: 180rpx;
            border-radius: 20rpx;
            overflow: hidden;
            cursor: pointer;
            background: #ffffff;
            box-shadow: 0 8rpx 32rpx rgba(0, 0, 0, 0.08);
            transition: all 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);

            /* 背景装饰图案 */
            .card-bg-pattern {
                position: absolute;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                opacity: 0.05;
                z-index: 0;

                &.pattern-dots {
                    background-image: radial-gradient(circle, #c8a45d 2rpx, transparent 2rpx);
                    background-size: 32rpx 32rpx;
                }

                &.pattern-waves {
                    background-image: repeating-linear-gradient(
                        45deg,
                        #9f7a2e 0rpx,
                        #9f7a2e 4rpx,
                        transparent 4rpx,
                        transparent 16rpx
                    );
                }

                &.pattern-grid {
                    background-image: linear-gradient(#6c665c 2rpx, transparent 2rpx),
                        linear-gradient(90deg, #6c665c 2rpx, transparent 2rpx);
                    background-size: 40rpx 40rpx;
                }

                &.pattern-circles {
                    background-image: radial-gradient(
                        circle,
                        transparent 20%,
                        #c8a45d 20%,
                        #c8a45d 22%,
                        transparent 22%
                    );
                    background-size: 48rpx 48rpx;
                }
            }

            /* 装饰性光晕 */
            .card-shine {
                position: absolute;
                top: -50%;
                left: -50%;
                width: 200%;
                height: 200%;
                background: linear-gradient(
                    45deg,
                    transparent 30%,
                    rgba(255, 255, 255, 0.3) 50%,
                    transparent 70%
                );
                opacity: 0;
                transition: opacity 0.6s;
                pointer-events: none;
                z-index: 2;
            }

            &:active {
                transform: scale(0.96) translateY(8rpx);
                box-shadow: 0 4rpx 16rpx rgba(0, 0, 0, 0.12);

                .card-shine {
                    opacity: 1;
                }
            }

            .card-content {
                position: relative;
                z-index: 1;
                height: 100%;
                padding: 24rpx;
                display: flex;
                flex-direction: column;
                justify-content: space-between;
            }

            .card-header {
                flex: 1;
                display: flex;
                align-items: center;
            }

            .topic-name {
                font-size: 36rpx;
                font-weight: 700;
                color: #111111;
                line-height: 1.3;
                overflow: hidden;
                text-overflow: ellipsis;
                display: -webkit-box;
                -webkit-line-clamp: 2;
                -webkit-box-orient: vertical;
            }

            .card-footer {
                display: flex;
                align-items: center;
                justify-content: space-between;
            }

            .stat-group {
                display: flex;
                gap: 16rpx;
            }

            .stat-item {
                display: flex;
                flex-direction: column;
                gap: 4rpx;

                .stat-label {
                    font-size: 20rpx;
                    color: #9a9388;
                    font-weight: 500;
                }

                .stat-value {
                    font-size: 28rpx;
                    font-weight: 700;
                    color: #c8a45d;
                }
            }

            .arrow-icon {
                width: 48rpx;
                height: 48rpx;
                background: linear-gradient(135deg, #c8a45d 0%, #d8c28a 100%);
                border-radius: 12rpx;
                display: flex;
                align-items: center;
                justify-content: center;
                font-size: 32rpx;
                font-weight: 700;
                color: #ffffff;
                box-shadow: 0 4rpx 12rpx rgba(11, 11, 11, 0.3);
            }
        }
    }

    /* 列表式样式 */
    .list-style {
        display: flex;
        flex-direction: column;
        gap: 16rpx;

        .topic-list-item {
            position: relative;
            display: flex;
            align-items: center;
            gap: 16rpx;
            padding: 20rpx;
            background: #ffffff;
            border-radius: 14rpx;
            cursor: pointer;
            box-shadow: 0 4rpx 20rpx rgba(0, 0, 0, 0.06);
            transition: all 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
            overflow: hidden;

            /* 装饰性渐变条 */
            .item-gradient-bar {
                position: absolute;
                left: 0;
                top: 0;
                bottom: 0;
                width: 8rpx;
                transition: width 0.4s;

                &.bar-purple {
                    background: linear-gradient(180deg, #c8a45d 0%, #d8c28a 100%);
                }

                &.bar-orange {
                    background: linear-gradient(180deg, #9f7a2e 0%, #c8a45d 100%);
                }

                &.bar-blue {
                    background: linear-gradient(180deg, #6c665c 0%, #6C665C 100%);
                }

                &.bar-pink {
                    background: linear-gradient(180deg, #c8a45d 0%, #d8c28a 100%);
                }
            }

            &:active {
                transform: translateX(12rpx) scale(0.98);
                box-shadow: 0 8rpx 32rpx rgba(11, 11, 11, 0.15);

                .item-gradient-bar {
                    width: 16rpx;
                }
            }

            /* 左侧：排名 */
            .item-left {
                display: flex;
                align-items: center;
                flex-shrink: 0;
            }

            .rank-badge {
                width: 56rpx;
                height: 56rpx;
                border-radius: 14rpx;
                display: flex;
                align-items: center;
                justify-content: center;
                font-weight: 800;
                font-size: 28rpx;
                flex-shrink: 0;
                box-shadow: 0 4rpx 12rpx rgba(0, 0, 0, 0.1);

                &.rank-gold {
                    background: linear-gradient(135deg, #D8C28A 0%, #c8a45d 100%);
                    color: #5A4433;
                }

                &.rank-silver {
                    background: linear-gradient(135deg, #e7e2d6 0%, #9a9388 100%);
                    color: #5F5A50;
                }

                &.rank-bronze {
                    background: linear-gradient(135deg, #D8C28A 0%, #9f7a2e 100%);
                    color: #5A4433;
                }

                &.rank-normal {
                    background: linear-gradient(135deg, #f8f7f2 0%, #e7e2d6 100%);
                    color: #6c665c;
                }
            }

            /* 中间：话题信息 */
            .item-center {
                flex: 1;
                min-width: 0;
            }

            .topic-name-row {
                display: flex;
                align-items: center;
                gap: 12rpx;
                margin-bottom: 8rpx;
            }

            .topic-name {
                font-size: 32rpx;
                font-weight: 700;
                color: #111111;
                overflow: hidden;
                text-overflow: ellipsis;
                white-space: nowrap;
            }

            .hot-badge {
                padding: 4rpx 12rpx;
                background: linear-gradient(135deg, #F7F0DF 0%, #D8C28A 100%);
                border-radius: 8rpx;
                flex-shrink: 0;

                .hot-text {
                    font-size: 20rpx;
                    font-weight: 800;
                    color: #9F7A2E;
                    letter-spacing: 0;
                }
            }

            .topic-stats {
                display: flex;
                gap: 16rpx;
            }

            .stat-chip {
                display: flex;
                align-items: center;
                gap: 8rpx;
                padding: 6rpx 16rpx;
                background: linear-gradient(135deg, #F8F7F2 0%, #f8f7f2 100%);
                border-radius: 12rpx;

                .stat-text {
                    font-size: 24rpx;
                    color: #6c665c;
                    font-weight: 500;
                }
            }

            /* 右侧：箭头 */
            .item-right {
                flex-shrink: 0;
            }

            .arrow-circle {
                width: 56rpx;
                height: 56rpx;
                background: linear-gradient(135deg, #f8f7f2 0%, #e7e2d6 100%);
                border-radius: 50%;
                display: flex;
                align-items: center;
                justify-content: center;
                transition: all 0.4s;

                .arrow-text {
                    font-size: 40rpx;
                    color: #9a9388;
                    font-weight: 300;
                    transition: transform 0.4s;
                }
            }

            &:active .arrow-circle {
                background: linear-gradient(135deg, #c8a45d 0%, #d8c28a 100%);

                .arrow-text {
                    color: #ffffff;
                    transform: translateX(4rpx);
                }
            }
        }
    }

    /* 响应式适配 */
    @media (prefers-reduced-motion: reduce) {
        .hot-topics-widget * {
            transition: none !important;
            animation: none !important;
        }
    }
}
</style>
