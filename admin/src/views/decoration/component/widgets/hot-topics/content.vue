<template>
    <div class="hot-topics-preview">
        <!-- 标题区域 -->
        <div v-if="content.title" class="topic-header">
            <div class="header-decoration"></div>
            <span class="header-title">{{ content.title }}</span>
        </div>

        <!-- 标签云样式 -->
        <div v-if="content.style == 1" class="tag-cloud-style">
            <!-- 装饰性背景元素 -->
            <div class="cloud-decoration">
                <div class="bubble bubble-1"></div>
                <div class="bubble bubble-2"></div>
                <div class="bubble bubble-3"></div>
            </div>
            
            <div class="tags-container">
                <div
                    v-for="(item, index) in showList"
                    :key="index"
                    class="topic-tag"
                    :style="getTagStyle(index)"
                >
                    <div class="tag-glow"></div>
                    <div class="tag-content">
                        <span class="tag-hash">#</span>
                        <span class="tag-name">{{ item.name }}</span>
                        <div v-if="item.count" class="tag-count-badge">
                            <span class="count-text">{{ formatCount(item.count) }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 横向滑动样式 -->
        <div v-if="content.style == 2" class="horizontal-scroll-style">
            <div class="scroll-container">
                <div class="cards-wrapper">
                    <div
                        v-for="(item, index) in showList"
                        :key="index"
                        class="topic-card"
                    >
                        <!-- 背景装饰 -->
                        <div class="card-bg-pattern" :class="getCardPattern(index)"></div>
                        
                        <!-- 卡片内容 -->
                        <div class="card-content">
                            <!-- 顶部：话题名称 -->
                            <div class="card-header">
                                <span class="topic-name">{{ item.name }}</span>
                            </div>
                            
                            <!-- 底部：统计信息 -->
                            <div class="card-footer">
                                <div class="stat-group">
                                    <div class="stat-item">
                                        <span class="stat-label">参与</span>
                                        <span class="stat-value">{{ formatCount(item.count || 0) }}</span>
                                    </div>
                                </div>
                                <div class="arrow-icon">→</div>
                            </div>
                        </div>
                        
                        <!-- 装饰性光晕 -->
                        <div class="card-shine"></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 列表式样式 -->
        <div v-if="content.style == 3" class="list-style">
            <div
                v-for="(item, index) in showList"
                :key="index"
                class="topic-list-item"
            >
                <!-- 左侧：排名 -->
                <div class="item-left">
                    <div class="rank-badge" :class="getRankClass(index)">
                        <span class="rank-number">{{ index + 1 }}</span>
                    </div>
                </div>
                
                <!-- 中间：话题信息 -->
                <div class="item-center">
                    <div class="topic-name-row">
                        <span class="topic-name">{{ item.name }}</span>
                        <div v-if="index < 3" class="hot-badge">
                            <span class="hot-text">HOT</span>
                        </div>
                    </div>
                    <div class="topic-stats">
                        <div class="stat-chip">
                            <span class="stat-text">{{ formatCount(item.count || 0) }} 人参与</span>
                        </div>
                    </div>
                </div>
                
                <!-- 右侧：箭头 -->
                <div class="item-right">
                    <div class="arrow-circle">
                        <span class="arrow-text">›</span>
                    </div>
                </div>
                
                <!-- 装饰性渐变条 -->
                <div class="item-gradient-bar" :class="getGradientBarClass(index)"></div>
            </div>
        </div>
    </div>
</template>

<script lang="ts" setup>
import type { PropType } from 'vue'
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

// 获取排名样式类
const getRankClass = (index: number): string => {
    if (index === 0) return 'rank-gold'
    if (index === 1) return 'rank-silver'
    if (index === 2) return 'rank-bronze'
    return 'rank-normal'
}

const showList = computed(() => {
    const data = props.content.data?.filter((item: any) => item.is_show == '1') || []
    const limit = props.content.show_count || data.length
    
    // 如果有真实数据，使用真实数据
    if (data.length > 0 && data[0].name) {
        return data.slice(0, limit)
    }
    
    // 否则使用模拟数据用于预览
    const mockTopics = [
        { name: '婚礼策划', count: 8520 },
        { name: '婚纱摄影', count: 6340 },
        { name: '婚礼现场', count: 5280 },
        { name: '新娘造型', count: 4150 },
        { name: '婚礼布置', count: 3890 },
        { name: '婚礼跟拍', count: 2760 },
        { name: '婚礼花艺', count: 2340 },
        { name: '婚礼主持', count: 1980 }
    ]
    
    return mockTopics.slice(0, Math.min(limit, mockTopics.length))
})
</script>

<style lang="scss" scoped>
.hot-topics-preview {
    margin: 10px;
    
    /* 标题区域 */
    .topic-header {
        display: flex;
        align-items: center;
        margin-bottom: 12px;
        gap: 8px;
        
        .header-decoration {
            width: 4px;
            height: 20px;
            background: linear-gradient(180deg, #7C3AED 0%, #A78BFA 100%);
            border-radius: 2px;
        }
        
        .header-title {
            font-size: 18px;
            font-weight: 700;
            color: #1F2937;
            letter-spacing: -0.3px;
        }
    }
    
    /* 标签云样式 */
    .tag-cloud-style {
        position: relative;
        background: linear-gradient(135deg, #FFFFFF 0%, #FAF5FF 100%);
        border-radius: 16px;
        padding: 20px 16px;
        overflow: hidden;
        box-shadow: 0 4px 16px rgba(124, 58, 237, 0.08);
        
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
                    width: 100px;
                    height: 100px;
                    background: linear-gradient(135deg, #7C3AED, #A78BFA);
                    top: -30px;
                    right: -20px;
                    animation: float-1 8s ease-in-out infinite;
                }
                
                &.bubble-2 {
                    width: 75px;
                    height: 75px;
                    background: linear-gradient(135deg, #F97316, #FB923C);
                    bottom: -20px;
                    left: -15px;
                    animation: float-2 10s ease-in-out infinite;
                }
                
                &.bubble-3 {
                    width: 60px;
                    height: 60px;
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
            gap: 8px;
            justify-content: center;
        }
        
        .topic-tag {
            position: relative;
            cursor: pointer;
            border-radius: 25px;
            padding: 10px 16px;
            background: #FFFFFF;
            border: 1.5px solid transparent;
            transition: all 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
            
            /* 发光效果层 */
            .tag-glow {
                position: absolute;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                border-radius: 25px;
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
                
                &:hover {
                    background: linear-gradient(135deg, #7C3AED 0%, #6D28D9 100%);
                    border-color: #7C3AED;
                    transform: scale(1.05) translateY(-2px);
                    box-shadow: 0 4px 12px rgba(124, 58, 237, 0.4);
                    
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
                
                &:hover {
                    background: linear-gradient(135deg, #F97316 0%, #EA580C 100%);
                    border-color: #F97316;
                    transform: scale(1.05) translateY(-2px);
                    box-shadow: 0 4px 12px rgba(249, 115, 22, 0.4);
                    
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
                
                &:hover {
                    background: linear-gradient(135deg, #3B82F6 0%, #2563EB 100%);
                    border-color: #3B82F6;
                    transform: scale(1.05) translateY(-2px);
                    box-shadow: 0 4px 12px rgba(59, 130, 246, 0.4);
                    
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
                
                &:hover {
                    background: linear-gradient(135deg, #EC4899 0%, #DB2777 100%);
                    border-color: #EC4899;
                    transform: scale(1.05) translateY(-2px);
                    box-shadow: 0 4px 12px rgba(236, 72, 153, 0.4);
                    
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
                gap: 4px;
                position: relative;
                z-index: 1;
            }
            
            .tag-hash {
                font-size: 16px;
                font-weight: 800;
                line-height: 1;
                transition: color 0.4s;
            }
            
            .tag-name {
                font-size: 14px;
                font-weight: 600;
                transition: color 0.4s;
                white-space: nowrap;
            }
            
            .tag-count-badge {
                padding: 2px 6px;
                border-radius: 10px;
                margin-left: 2px;
                transition: background 0.4s;
                
                .count-text {
                    font-size: 10px;
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
            transform: translate(10px, -10px) scale(1.1);
        }
        50% {
            transform: translate(-5px, 5px) scale(0.9);
        }
        75% {
            transform: translate(7px, 7px) scale(1.05);
        }
    }
    
    @keyframes float-2 {
        0%, 100% {
            transform: translate(0, 0) scale(1);
        }
        33% {
            transform: translate(-7px, 10px) scale(1.08);
        }
        66% {
            transform: translate(5px, -7px) scale(0.92);
        }
    }
    
    @keyframes float-3 {
        0%, 100% {
            transform: translate(0, 0) scale(1) rotate(0deg);
        }
        25% {
            transform: translate(5px, 7px) scale(1.1) rotate(90deg);
        }
        50% {
            transform: translate(-7px, -5px) scale(0.9) rotate(180deg);
        }
        75% {
            transform: translate(10px, -2px) scale(1.05) rotate(270deg);
        }
    }
    
    /* 横向滑动样式 */
    .horizontal-scroll-style {
        .scroll-container {
            overflow-x: auto;
            
            &::-webkit-scrollbar {
                display: none;
            }
        }
        
        .cards-wrapper {
            display: flex;
            gap: 12px;
            padding-bottom: 10px;
        }
        
        .topic-card {
            position: relative;
            width: 150px;
            height: 90px;
            border-radius: 14px;
            overflow: hidden;
            cursor: pointer;
            background: #FFFFFF;
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.08);
            transition: all 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
            flex-shrink: 0;
            
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
                    background-image: radial-gradient(circle, #7C3AED 1px, transparent 1px);
                    background-size: 16px 16px;
                }
                
                &.pattern-waves {
                    background-image: repeating-linear-gradient(
                        45deg,
                        #F97316 0px,
                        #F97316 2px,
                        transparent 2px,
                        transparent 8px
                    );
                }
                
                &.pattern-grid {
                    background-image: 
                        linear-gradient(#3B82F6 1px, transparent 1px),
                        linear-gradient(90deg, #3B82F6 1px, transparent 1px);
                    background-size: 20px 20px;
                }
                
                &.pattern-circles {
                    background-image: radial-gradient(circle, transparent 20%, #EC4899 20%, #EC4899 22%, transparent 22%);
                    background-size: 24px 24px;
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
            
            &:hover {
                transform: scale(1.05) translateY(-4px);
                box-shadow: 0 8px 24px rgba(0, 0, 0, 0.12);
                
                .card-shine {
                    opacity: 1;
                }
            }
            
            .card-content {
                position: relative;
                z-index: 1;
                height: 100%;
                padding: 16px;
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
                font-size: 18px;
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
                gap: 8px;
            }
            
            .stat-item {
                display: flex;
                flex-direction: column;
                gap: 2px;
                
                .stat-label {
                    font-size: 10px;
                    color: #9CA3AF;
                    font-weight: 500;
                }
                
                .stat-value {
                    font-size: 14px;
                    font-weight: 700;
                    color: #7C3AED;
                }
            }
            
            .arrow-icon {
                width: 28px;
                height: 28px;
                background: linear-gradient(135deg, #7C3AED 0%, #A78BFA 100%);
                border-radius: 8px;
                display: flex;
                align-items: center;
                justify-content: center;
                font-size: 18px;
                font-weight: 700;
                color: #FFFFFF;
                box-shadow: 0 2px 6px rgba(124, 58, 237, 0.3);
            }
        }
    }
    
    /* 列表式样式 */
    .list-style {
        display: flex;
        flex-direction: column;
        gap: 10px;
        
        .topic-list-item {
            position: relative;
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 14px 12px;
            background: #FFFFFF;
            border-radius: 12px;
            cursor: pointer;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.06);
            transition: all 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
            overflow: hidden;
            
            /* 装饰性渐变条 */
            .item-gradient-bar {
                position: absolute;
                left: 0;
                top: 0;
                bottom: 0;
                width: 4px;
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
            
            &:hover {
                transform: translateX(6px) scale(1.02);
                box-shadow: 0 4px 16px rgba(124, 58, 237, 0.15);
                
                .item-gradient-bar {
                    width: 8px;
                }
            }
            
            /* 左侧：排名 */
            .item-left {
                display: flex;
                align-items: center;
                flex-shrink: 0;
            }
            
            .rank-badge {
                width: 28px;
                height: 28px;
                border-radius: 7px;
                display: flex;
                align-items: center;
                justify-content: center;
                font-weight: 800;
                font-size: 14px;
                flex-shrink: 0;
                box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);
                
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
                gap: 6px;
                margin-bottom: 4px;
            }
            
            .topic-name {
                font-size: 16px;
                font-weight: 700;
                color: #1F2937;
                overflow: hidden;
                text-overflow: ellipsis;
                white-space: nowrap;
            }
            
            .hot-badge {
                padding: 2px 6px;
                background: linear-gradient(135deg, #FEF3C7 0%, #FDE68A 100%);
                border-radius: 4px;
                flex-shrink: 0;
                
                .hot-text {
                    font-size: 10px;
                    font-weight: 800;
                    color: #92400E;
                    letter-spacing: 0.5px;
                }
            }
            
            .topic-stats {
                display: flex;
                gap: 8px;
            }
            
            .stat-chip {
                display: flex;
                align-items: center;
                gap: 4px;
                padding: 3px 8px;
                background: linear-gradient(135deg, #F9FAFB 0%, #F3F4F6 100%);
                border-radius: 6px;
                
                .stat-icon {
                    font-size: 12px;
                }
                
                .stat-text {
                    font-size: 12px;
                    color: #6B7280;
                    font-weight: 500;
                }
            }
            
            /* 右侧：箭头 */
            .item-right {
                flex-shrink: 0;
            }
            
            .arrow-circle {
                width: 28px;
                height: 28px;
                background: linear-gradient(135deg, #F3F4F6 0%, #E5E7EB 100%);
                border-radius: 50%;
                display: flex;
                align-items: center;
                justify-content: center;
                transition: all 0.4s;
                
                .arrow-text {
                    font-size: 20px;
                    color: #9CA3AF;
                    font-weight: 300;
                    transition: transform 0.4s;
                }
            }
            
            &:hover .arrow-circle {
                background: linear-gradient(135deg, #7C3AED 0%, #A78BFA 100%);
                
                .arrow-text {
                    color: #FFFFFF;
                    transform: translateX(2px);
                }
            }
        }
    }
}
</style>
