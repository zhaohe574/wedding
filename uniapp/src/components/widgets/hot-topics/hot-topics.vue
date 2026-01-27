<template>
    <view v-if="content.enabled && topicList.length" class="hot-topics-widget">
        <!-- 标题区域 -->
        <view v-if="content.title" class="topic-header">
            <view class="header-decoration"></view>
            <text class="header-title">{{ content.title }}</text>
        </view>

        <!-- 标签云样式 -->
        <view v-if="content.style == 1" class="tag-cloud-style">
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
                    :style="getTagStyle(index)"
                    @click="handleClick(item)"
                >
                    <view class="tag-glow"></view>
                    <view class="tag-content">
                        <text class="tag-hash">#</text>
                        <text class="tag-name">{{ item.name }}</text>
                        <view v-if="item.count" class="tag-count-badge">
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
                                        <text class="stat-value">{{ formatCount(item.count || 0) }}</text>
                                    </view>
                                </view>
                                <view class="arrow-icon">→</view>
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
                    <view class="rank-badge" :class="getRankClass(index)">
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
                    <view class="arrow-circle">
                        <text class="arrow-text">›</text>
                    </view>
                </view>
                
                <!-- 装饰性渐变条 -->
                <view class="item-gradient-bar" :class="getGradientBarClass(index)"></view>
            </view>
        </view>
    </view>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'

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

// 显示的话题列表（限制数量）
const displayList = computed(() => {
    const count = props.content.show_count || 10
    return topicList.value.slice(0, count)
})

// 格式化数量显示
const formatCount = (count: number): string => {
    if (count >= 10000) {
        return (count / 10000).toFixed(1) + 'w'
    } else if (count >= 1000) {
        return (count / 1000).toFixed(1) + 'k'
    }
    return count.toString()
}

// 获取标签动态样式（随机大小和旋转）
const getTagStyle = (index: number): string => {
    const sizes = ['small', 'medium', 'large']
    const rotations = [-2, -1, 0, 1, 2]
    const size = sizes[index % sizes.length]
    const rotation = rotations[index % rotations.length]
    
    let scale = 1
    if (size === 'small') scale = 0.9
    if (size === 'large') scale = 1.1
    
    return `transform: scale(${scale}) rotate(${rotation}deg);`
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

// 获取话题表情
const getTopicEmoji = (index: number): string => {
    const emojis = ['🔥', '⭐', '💬', '🎯', '💡', '🎨', '🚀', '💎']
    return emojis[index % emojis.length]
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
        margin-bottom: 24rpx;
        gap: 16rpx;
        
        .header-decoration {
            width: 8rpx;
            height: 40rpx;
            background: linear-gradient(180deg, #7C3AED 0%, #A78BFA 100%);
            border-radius: 4rpx;
        }
        
        .header-title {
            font-size: 36rpx;
            font-weight: 700;
            color: #1F2937;
            letter-spacing: -0.5rpx;
        }
    }
    
    /* 标签云样式 */
    .tag-cloud-style {
        position: relative;
        background: linear-gradient(135deg, #FFFFFF 0%, #FAF5FF 100%);
        border-radius: 32rpx;
        padding: 40rpx 32rpx;
        overflow: hidden;
        box-shadow: 0 8rpx 32rpx rgba(124, 58, 237, 0.08);
        
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
                    background: linear-gradient(135deg, #7C3AED, #A78BFA);
                    top: -60rpx;
                    right: -40rpx;
                    animation: float-1 8s ease-in-out infinite;
                }
                
                &.bubble-2 {
                    width: 150rpx;
                    height: 150rpx;
                    background: linear-gradient(135deg, #F97316, #FB923C);
                    bottom: -40rpx;
                    left: -30rpx;
                    animation: float-2 10s ease-in-out infinite;
                }
                
                &.bubble-3 {
                    width: 120rpx;
                    height: 120rpx;
                    background: linear-gradient(135deg, #EC4899, #F472B6);
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
            gap: 16rpx;
            justify-content: center;
        }
        
        .topic-tag {
            position: relative;
            cursor: pointer;
            border-radius: 50rpx;
            padding: 20rpx 32rpx;
            background: #FFFFFF;
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
                border-radius: 50rpx;
                opacity: 0;
                transition: opacity 0.4s;
                pointer-events: none;
            }
            
            &:nth-child(4n+1) {
                background: linear-gradient(135deg, #F3E8FF 0%, #E9D5FF 100%);
                border-color: #C4B5FD;
                
                .tag-hash {
                    color: #7C3AED;
                }
                
                .tag-name {
                    color: #6D28D9;
                }
                
                .tag-count-badge {
                    background: linear-gradient(135deg, #7C3AED 0%, #8B5CF6 100%);
                }
                
                .tag-glow {
                    background: radial-gradient(circle, rgba(124, 58, 237, 0.3) 0%, transparent 70%);
                }
                
                &:active {
                    background: linear-gradient(135deg, #7C3AED 0%, #6D28D9 100%);
                    border-color: #7C3AED;
                    transform: scale(0.95) translateY(4rpx);
                    box-shadow: 0 8rpx 24rpx rgba(124, 58, 237, 0.4);
                    
                    .tag-hash,
                    .tag-name {
                        color: #FFFFFF;
                    }
                    
                    .tag-count-badge {
                        background: rgba(255, 255, 255, 0.3);
                        
                        .count-text {
                            color: #FFFFFF;
                        }
                    }
                    
                    .tag-glow {
                        opacity: 1;
                    }
                }
            }
            
            &:nth-child(4n+2) {
                background: linear-gradient(135deg, #FFF7ED 0%, #FFEDD5 100%);
                border-color: #FDBA74;
                
                .tag-hash {
                    color: #F97316;
                }
                
                .tag-name {
                    color: #C2410C;
                }
                
                .tag-count-badge {
                    background: linear-gradient(135deg, #F97316 0%, #FB923C 100%);
                }
                
                .tag-glow {
                    background: radial-gradient(circle, rgba(249, 115, 22, 0.3) 0%, transparent 70%);
                }
                
                &:active {
                    background: linear-gradient(135deg, #F97316 0%, #EA580C 100%);
                    border-color: #F97316;
                    transform: scale(0.95) translateY(4rpx);
                    box-shadow: 0 8rpx 24rpx rgba(249, 115, 22, 0.4);
                    
                    .tag-hash,
                    .tag-name {
                        color: #FFFFFF;
                    }
                    
                    .tag-count-badge {
                        background: rgba(255, 255, 255, 0.3);
                        
                        .count-text {
                            color: #FFFFFF;
                        }
                    }
                    
                    .tag-glow {
                        opacity: 1;
                    }
                }
            }
            
            &:nth-child(4n+3) {
                background: linear-gradient(135deg, #EFF6FF 0%, #DBEAFE 100%);
                border-color: #93C5FD;
                
                .tag-hash {
                    color: #3B82F6;
                }
                
                .tag-name {
                    color: #1E40AF;
                }
                
                .tag-count-badge {
                    background: linear-gradient(135deg, #3B82F6 0%, #60A5FA 100%);
                }
                
                .tag-glow {
                    background: radial-gradient(circle, rgba(59, 130, 246, 0.3) 0%, transparent 70%);
                }
                
                &:active {
                    background: linear-gradient(135deg, #3B82F6 0%, #2563EB 100%);
                    border-color: #3B82F6;
                    transform: scale(0.95) translateY(4rpx);
                    box-shadow: 0 8rpx 24rpx rgba(59, 130, 246, 0.4);
                    
                    .tag-hash,
                    .tag-name {
                        color: #FFFFFF;
                    }
                    
                    .tag-count-badge {
                        background: rgba(255, 255, 255, 0.3);
                        
                        .count-text {
                            color: #FFFFFF;
                        }
                    }
                    
                    .tag-glow {
                        opacity: 1;
                    }
                }
            }
            
            &:nth-child(4n+4) {
                background: linear-gradient(135deg, #FDF2F8 0%, #FCE7F3 100%);
                border-color: #F9A8D4;
                
                .tag-hash {
                    color: #EC4899;
                }
                
                .tag-name {
                    color: #BE185D;
                }
                
                .tag-count-badge {
                    background: linear-gradient(135deg, #EC4899 0%, #F472B6 100%);
                }
                
                .tag-glow {
                    background: radial-gradient(circle, rgba(236, 72, 153, 0.3) 0%, transparent 70%);
                }
                
                &:active {
                    background: linear-gradient(135deg, #EC4899 0%, #DB2777 100%);
                    border-color: #EC4899;
                    transform: scale(0.95) translateY(4rpx);
                    box-shadow: 0 8rpx 24rpx rgba(236, 72, 153, 0.4);
                    
                    .tag-hash,
                    .tag-name {
                        color: #FFFFFF;
                    }
                    
                    .tag-count-badge {
                        background: rgba(255, 255, 255, 0.3);
                        
                        .count-text {
                            color: #FFFFFF;
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
                    color: #FFFFFF;
                    transition: color 0.4s;
                }
            }
        }
    }
    
    /* 气泡浮动动画 */
    @keyframes float-1 {
        0%, 100% {
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
        0%, 100% {
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
        0%, 100% {
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
            gap: 24rpx;
            padding: 0 20rpx 20rpx 20rpx;
        }
        
        .topic-card {
            position: relative;
            width: 300rpx;
            height: 180rpx;
            border-radius: 28rpx;
            overflow: hidden;
            cursor: pointer;
            background: #FFFFFF;
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
                    background-image: radial-gradient(circle, #7C3AED 2rpx, transparent 2rpx);
                    background-size: 32rpx 32rpx;
                }
                
                &.pattern-waves {
                    background-image: repeating-linear-gradient(
                        45deg,
                        #F97316 0rpx,
                        #F97316 4rpx,
                        transparent 4rpx,
                        transparent 16rpx
                    );
                }
                
                &.pattern-grid {
                    background-image: 
                        linear-gradient(#3B82F6 2rpx, transparent 2rpx),
                        linear-gradient(90deg, #3B82F6 2rpx, transparent 2rpx);
                    background-size: 40rpx 40rpx;
                }
                
                &.pattern-circles {
                    background-image: radial-gradient(circle, transparent 20%, #EC4899 20%, #EC4899 22%, transparent 22%);
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
                padding: 32rpx;
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
                color: #1F2937;
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
                    color: #9CA3AF;
                    font-weight: 500;
                }
                
                .stat-value {
                    font-size: 28rpx;
                    font-weight: 700;
                    color: #7C3AED;
                }
            }
            
            .arrow-icon {
                width: 56rpx;
                height: 56rpx;
                background: linear-gradient(135deg, #7C3AED 0%, #A78BFA 100%);
                border-radius: 16rpx;
                display: flex;
                align-items: center;
                justify-content: center;
                font-size: 36rpx;
                font-weight: 700;
                color: #FFFFFF;
                box-shadow: 0 4rpx 12rpx rgba(124, 58, 237, 0.3);
            }
        }
    }
    
    /* 列表式样式 */
    .list-style {
        display: flex;
        flex-direction: column;
        gap: 20rpx;
        
        .topic-list-item {
            position: relative;
            display: flex;
            align-items: center;
            gap: 24rpx;
            padding: 28rpx 24rpx;
            background: #FFFFFF;
            border-radius: 24rpx;
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
                    background: linear-gradient(180deg, #7C3AED 0%, #A78BFA 100%);
                }
                
                &.bar-orange {
                    background: linear-gradient(180deg, #F97316 0%, #FB923C 100%);
                }
                
                &.bar-blue {
                    background: linear-gradient(180deg, #3B82F6 0%, #60A5FA 100%);
                }
                
                &.bar-pink {
                    background: linear-gradient(180deg, #EC4899 0%, #F472B6 100%);
                }
            }
            
            &:active {
                transform: translateX(12rpx) scale(0.98);
                box-shadow: 0 8rpx 32rpx rgba(124, 58, 237, 0.15);
                
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
                    background: linear-gradient(135deg, #FCD34D 0%, #F59E0B 100%);
                    color: #78350F;
                }
                
                &.rank-silver {
                    background: linear-gradient(135deg, #E5E7EB 0%, #9CA3AF 100%);
                    color: #374151;
                }
                
                &.rank-bronze {
                    background: linear-gradient(135deg, #FDBA74 0%, #F97316 100%);
                    color: #7C2D12;
                }
                
                &.rank-normal {
                    background: linear-gradient(135deg, #F3F4F6 0%, #E5E7EB 100%);
                    color: #6B7280;
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
                color: #1F2937;
                overflow: hidden;
                text-overflow: ellipsis;
                white-space: nowrap;
            }
            
            .hot-badge {
                padding: 4rpx 12rpx;
                background: linear-gradient(135deg, #FEF3C7 0%, #FDE68A 100%);
                border-radius: 8rpx;
                flex-shrink: 0;
                
                .hot-text {
                    font-size: 20rpx;
                    font-weight: 800;
                    color: #92400E;
                    letter-spacing: 1rpx;
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
                background: linear-gradient(135deg, #F9FAFB 0%, #F3F4F6 100%);
                border-radius: 12rpx;
                
                .stat-text {
                    font-size: 24rpx;
                    color: #6B7280;
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
                background: linear-gradient(135deg, #F3F4F6 0%, #E5E7EB 100%);
                border-radius: 50%;
                display: flex;
                align-items: center;
                justify-content: center;
                transition: all 0.4s;
                
                .arrow-text {
                    font-size: 40rpx;
                    color: #9CA3AF;
                    font-weight: 300;
                    transition: transform 0.4s;
                }
            }
            
            &:active .arrow-circle {
                background: linear-gradient(135deg, #7C3AED 0%, #A78BFA 100%);
                
                .arrow-text {
                    color: #FFFFFF;
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
