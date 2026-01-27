<template>
    <div class="service-process-preview">
        <!-- 标题区域 -->
        <div v-if="content.title" class="process-header">
            <div class="header-decoration"></div>
            <span class="header-title">{{ content.title }}</span>
        </div>

        <!-- 时间轴样式 -->
        <div v-if="content.style == 1" class="timeline-style">
            <div
                v-for="(item, index) in showList"
                :key="index"
                class="timeline-item"
                :class="{ 'is-last': index === showList.length - 1 }"
            >
                <!-- 左侧：图标和连接线 -->
                <div class="timeline-left">
                    <div 
                        class="icon-container" 
                        :class="[
                            getIconAnimation(index),
                            content.icon_style == 2 ? 'square' : 'circle'
                        ]"
                    >
                        <decoration-img
                            width="20px"
                            height="20px"
                            :src="item.icon"
                        />
                    </div>
                    <div 
                        v-if="index < showList.length - 1" 
                        class="timeline-connector"
                        :style="{ 
                            background: content.line_color || 'linear-gradient(180deg, #0369A1 0%, #BAE6FD 100%)' 
                        }"
                    ></div>
                </div>
                
                <!-- 右侧：内容 -->
                <div class="timeline-right">
                    <div class="step-badge">步骤 {{ index + 1 }}</div>
                    <div class="step-title">{{ item.title }}</div>
                    <div class="step-description">{{ item.description }}</div>
                </div>
            </div>
        </div>

        <!-- 步骤卡片样式 -->
        <div v-if="content.style == 2" class="card-style">
            <div
                v-for="(item, index) in showList"
                :key="index"
                class="step-card"
            >
                <!-- 顶部进度条 -->
                <div class="card-progress-bar">
                    <div 
                        class="progress-fill" 
                        :style="{ width: ((index + 1) / showList.length * 100) + '%' }"
                    ></div>
                </div>
                
                <div class="card-content">
                    <!-- 左侧：序号和图标 -->
                    <div class="card-left">
                        <div class="step-number-circle">
                            <span class="number-text">{{ index + 1 }}</span>
                        </div>
                        <decoration-img
                            width="30px"
                            height="30px"
                            :src="item.icon"
                            class="card-icon"
                        />
                    </div>
                    
                    <!-- 右侧：内容 -->
                    <div class="card-right">
                        <div class="card-title">{{ item.title }}</div>
                        <div class="card-description">{{ item.description }}</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 横向流程样式 -->
        <div v-if="content.style == 3" class="horizontal-style">
            <div class="scroll-container">
                <div class="steps-wrapper">
                    <div
                        v-for="(item, index) in showList"
                        :key="index"
                        class="horizontal-step"
                    >
                        <!-- 步骤内容 -->
                        <div class="step-box">
                            <div class="step-number-badge">{{ index + 1 }}</div>
                            <div 
                                class="step-icon-wrapper"
                                :class="content.icon_style == 2 ? 'square' : 'circle'"
                            >
                                <decoration-img
                                    width="20px"
                                    height="20px"
                                    :src="item.icon"
                                />
                            </div>
                            <div class="step-info">
                                <span class="step-name">{{ item.title }}</span>
                                <span class="step-desc">{{ item.description }}</span>
                            </div>
                        </div>
                        
                        <!-- 箭头连接 -->
                        <div v-if="index < showList.length - 1" class="step-arrow">
                            <div 
                                class="arrow-line"
                                :style="{ 
                                    background: content.line_color || 'linear-gradient(90deg, #0369A1 0%, #BAE6FD 100%)' 
                                }"
                            ></div>
                            <div 
                                class="arrow-head"
                                :style="{ color: content.line_color || '#0369A1' }"
                            >›</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script lang="ts" setup>
import type { PropType } from 'vue'
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
    const data = props.content.data?.filter((item: any) => item.is_show == '1') || []
    return data
})

// 获取图标动画类
const getIconAnimation = (index: number): string => {
    const animations = ['anim-1', 'anim-2', 'anim-3']
    return animations[index % animations.length]
}
</script>

<style lang="scss" scoped>
.service-process-preview {
    margin: 10px;
    
    /* 标题区域 */
    .process-header {
        display: flex;
        align-items: center;
        margin-bottom: 12px;
        gap: 6px;
        
        .header-decoration {
            width: 3px;
            height: 16px;
            background: linear-gradient(180deg, #0369A1 0%, #0284C7 100%);
            border-radius: 1.5px;
        }
        
        .header-title {
            font-size: 16px;
            font-weight: 700;
            color: #0F172A;
            letter-spacing: -0.3px;
        }
    }
    
    /* 时间轴样式 */
    .timeline-style {
        background: #FFFFFF;
        border-radius: 10px;
        padding: 12px 10px;
        box-shadow: 0 2px 8px rgba(15, 23, 42, 0.06);
        
        .timeline-item {
            display: flex;
            gap: 10px;
            position: relative;
            
            &:not(.is-last) {
                padding-bottom: 20px;
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
            width: 36px;
            height: 36px;
            background: linear-gradient(135deg, #E0F2FE 0%, #BAE6FD 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            border: 2px solid #FFFFFF;
            box-shadow: 0 3px 10px rgba(3, 105, 161, 0.15);
            position: relative;
            z-index: 2;
            
            /* 根据icon_style设置形状 */
            &.circle {
                border-radius: 50%;
            }
            
            &.square {
                border-radius: 8px;
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
        }
        
        .timeline-connector {
            position: absolute;
            top: 36px;
            left: 50%;
            transform: translateX(-50%);
            width: 1.5px;
            height: calc(100% - 20px);
            /* 使用配置的line_color，默认为渐变色 */
            z-index: 1;
        }
        
        .timeline-right {
            flex: 1;
            padding-top: 2px;
        }
        
        .step-badge {
            display: inline-block;
            padding: 2px 6px;
            background: linear-gradient(135deg, #0369A1 0%, #0284C7 100%);
            color: #FFFFFF;
            font-size: 10px;
            font-weight: 700;
            border-radius: 8px;
            margin-bottom: 4px;
            letter-spacing: 0.3px;
        }
        
        .step-title {
            font-size: 14px;
            font-weight: 700;
            color: #0F172A;
            margin-bottom: 4px;
            line-height: 1.4;
        }
        
        .step-description {
            font-size: 12px;
            color: #64748B;
            line-height: 1.5;
        }
    }
    
    /* 脉冲动画 */
    @keyframes pulse-1 {
        0%, 100% {
            transform: scale(1);
            box-shadow: 0 4px 12px rgba(3, 105, 161, 0.15);
        }
        50% {
            transform: scale(1.05);
            box-shadow: 0 6px 16px rgba(3, 105, 161, 0.25);
        }
    }
    
    @keyframes pulse-2 {
        0%, 100% {
            transform: scale(1) rotate(0deg);
            box-shadow: 0 4px 12px rgba(3, 105, 161, 0.15);
        }
        50% {
            transform: scale(1.08) rotate(5deg);
            box-shadow: 0 6px 16px rgba(3, 105, 161, 0.25);
        }
    }
    
    @keyframes pulse-3 {
        0%, 100% {
            transform: scale(1);
            box-shadow: 0 4px 12px rgba(3, 105, 161, 0.15);
        }
        33% {
            transform: scale(1.06);
            box-shadow: 0 5px 14px rgba(3, 105, 161, 0.2);
        }
        66% {
            transform: scale(0.98);
            box-shadow: 0 3px 10px rgba(3, 105, 161, 0.12);
        }
    }
    
    /* 步骤卡片样式 */
    .card-style {
        display: flex;
        flex-direction: column;
        gap: 10px;
        
        .step-card {
            background: #FFFFFF;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(15, 23, 42, 0.06);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            
            &:hover {
                transform: translateY(2px);
                box-shadow: 0 1px 6px rgba(15, 23, 42, 0.08);
            }
        }
        
        .card-progress-bar {
            height: 3px;
            background: #F1F5F9;
            position: relative;
            overflow: hidden;
            
            .progress-fill {
                height: 100%;
                background: linear-gradient(90deg, #0369A1 0%, #0284C7 100%);
                transition: width 0.6s cubic-bezier(0.4, 0, 0.2, 1);
            }
        }
        
        .card-content {
            display: flex;
            gap: 10px;
            padding: 12px 10px;
        }
        
        .card-left {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 6px;
            flex-shrink: 0;
        }
        
        .step-number-circle {
            width: 26px;
            height: 26px;
            background: linear-gradient(135deg, #0369A1 0%, #0284C7 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 2px 8px rgba(3, 105, 161, 0.3);
            
            .number-text {
                font-size: 14px;
                font-weight: 800;
                color: #FFFFFF;
            }
        }
        
        .card-icon {
            border-radius: 6px;
            background: #F8FAFC;
            padding: 5px;
        }
        
        .card-right {
            flex: 1;
            display: flex;
            flex-direction: column;
            gap: 4px;
        }
        
        .card-title {
            font-size: 14px;
            font-weight: 700;
            color: #0F172A;
            line-height: 1.4;
        }
        
        .card-description {
            font-size: 12px;
            color: #64748B;
            line-height: 1.5;
        }
    }
    
    /* 横向流程样式 */
    .horizontal-style {
        .scroll-container {
            overflow-x: auto;
            
            &::-webkit-scrollbar {
                display: none;
            }
        }
        
        .steps-wrapper {
            display: flex;
            align-items: center;
            gap: 0;
            padding: 8px;
        }
        
        .horizontal-step {
            display: flex;
            align-items: center;
        }
        
        .step-box {
            position: relative;
            width: 120px;
            background: #FFFFFF;
            border-radius: 10px;
            padding: 12px 10px;
            box-shadow: 0 2px 8px rgba(15, 23, 42, 0.06);
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 6px;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            
            &:hover {
                transform: scale(0.98);
                box-shadow: 0 1px 6px rgba(15, 23, 42, 0.08);
            }
        }
        
        .step-number-badge {
            position: absolute;
            top: -5px;
            left: 50%;
            transform: translateX(-50%);
            width: 20px;
            height: 20px;
            background: linear-gradient(135deg, #0369A1 0%, #0284C7 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 10px;
            font-weight: 800;
            color: #FFFFFF;
            box-shadow: 0 2px 8px rgba(3, 105, 161, 0.3);
            border: 1.5px solid #FFFFFF;
        }
        
        .step-icon-wrapper {
            width: 36px;
            height: 36px;
            background: linear-gradient(135deg, #E0F2FE 0%, #BAE6FD 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            margin-top: 6px;
            
            /* 根据icon_style设置形状 */
            &.circle {
                border-radius: 50%;
            }
            
            &.square {
                border-radius: 8px;
            }
        }
        
        .step-info {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 3px;
            width: 100%;
        }
        
        .step-name {
            font-size: 13px;
            font-weight: 700;
            color: #0F172A;
            text-align: center;
            line-height: 1.4;
        }
        
        .step-desc {
            font-size: 11px;
            color: #64748B;
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
            padding: 0 10px;
            position: relative;
            
            .arrow-line {
                width: 20px;
                height: 1.5px;
                /* 使用配置的line_color，默认为渐变色 */
            }
            
            .arrow-head {
                font-size: 20px;
                /* 颜色通过内联样式设置 */
                font-weight: 300;
                line-height: 1;
                margin-left: -3px;
            }
        }
    }
}
</style>
