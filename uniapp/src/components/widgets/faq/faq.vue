<template>
    <view v-if="content.enabled && showList.length" class="faq-widget">
        <!-- 标题区域 -->
        <view v-if="content.title" class="faq-header">
            <view class="header-decoration"></view>
            <text class="header-title">{{ content.title }}</text>
        </view>

        <!-- 搜索框 -->
        <view v-if="content.show_search" class="search-container">
            <view class="search-box">
                <view class="search-icon">🔍</view>
                <input
                    v-model="searchKeyword"
                    class="search-input"
                    placeholder="搜索您的问题..."
                    @input="handleSearch"
                />
                <view v-if="searchKeyword" class="clear-icon" @click="clearSearch">✕</view>
            </view>
        </view>

        <!-- 折叠面板样式 -->
        <view v-if="content.style == 1" class="accordion-style">
            <view
                v-for="(item, index) in filteredList"
                :key="index"
                class="accordion-item"
                :class="{ 'is-active': activeIndex === index }"
            >
                <view class="accordion-header" @click="toggleAccordion(index)">
                    <view class="question-wrapper">
                        <view class="question-icon">Q</view>
                        <text class="question-text">{{ item.question }}</text>
                    </view>
                    <view class="expand-icon" :class="{ 'is-expanded': activeIndex === index }">
                        <text class="icon-text">›</text>
                    </view>
                </view>
                <view class="accordion-content" :class="{ 'is-expanded': activeIndex === index }">
                    <view class="answer-wrapper">
                        <view class="answer-icon">A</view>
                        <view class="answer-text">
                            <rich-text :nodes="item.answer"></rich-text>
                        </view>
                    </view>
                    <view v-if="item.category" class="category-tag">
                        <text class="tag-text">{{ item.category }}</text>
                    </view>
                </view>
            </view>
        </view>

        <!-- 列表式样式 -->
        <view v-if="content.style == 2" class="list-style">
            <view
                v-for="(item, index) in filteredList"
                :key="index"
                class="faq-card"
            >
                <!-- 问题部分 -->
                <view class="question-section">
                    <view class="q-badge">Q</view>
                    <view class="q-content">
                        <text class="q-text">{{ item.question }}</text>
                        <view v-if="item.category" class="q-category">
                            <text class="category-text">{{ item.category }}</text>
                        </view>
                    </view>
                </view>
                
                <!-- 答案部分 -->
                <view class="answer-section">
                    <view class="a-badge">A</view>
                    <view class="a-content">
                        <rich-text :nodes="item.answer"></rich-text>
                    </view>
                </view>
            </view>
        </view>

        <!-- 无搜索结果 -->
        <view v-if="content.show_search && filteredList.length === 0" class="no-result">
            <view class="no-result-icon">🔍</view>
            <text class="no-result-text">未找到相关问题</text>
            <text class="no-result-hint">试试其他关键词</text>
        </view>
    </view>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'

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

const activeIndex = ref<number>(-1)
const searchKeyword = ref('')

// 过滤显示的问题
const showList = computed(() => {
    return props.content.data?.filter((item: any) => item.is_show == '1') || []
})

// 搜索过滤后的列表
const filteredList = computed(() => {
    if (!searchKeyword.value) {
        return showList.value
    }
    
    const keyword = searchKeyword.value.toLowerCase()
    return showList.value.filter((item: any) => {
        return (
            item.question.toLowerCase().includes(keyword) ||
            item.answer.toLowerCase().includes(keyword) ||
            (item.category && item.category.toLowerCase().includes(keyword))
        )
    })
})

// 切换折叠面板
const toggleAccordion = (index: number) => {
    activeIndex.value = activeIndex.value === index ? -1 : index
}

// 处理搜索
const handleSearch = () => {
    // 搜索时自动展开第一个结果
    if (filteredList.value.length > 0 && props.content.style == 1) {
        activeIndex.value = 0
    }
}

// 清除搜索
const clearSearch = () => {
    searchKeyword.value = ''
    activeIndex.value = -1
}
</script>

<style scoped lang="scss">
.faq-widget {
    margin: 20rpx;
    
    /* 标题区域 */
    .faq-header {
        display: flex;
        align-items: center;
        margin-bottom: 24rpx;
        gap: 12rpx;
        
        .header-decoration {
            width: 6rpx;
            height: 32rpx;
            background: linear-gradient(180deg, #2563EB 0%, #3B82F6 100%);
            border-radius: 3rpx;
        }
        
        .header-title {
            font-size: 32rpx;
            font-weight: 700;
            color: #1E293B;
            letter-spacing: -0.5rpx;
        }
    }
    
    /* 搜索容器 */
    .search-container {
        margin-bottom: 24rpx;
        
        .search-box {
            position: relative;
            display: flex;
            align-items: center;
            background: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(20rpx);
            border: 2rpx solid rgba(37, 99, 235, 0.1);
            border-radius: 24rpx;
            padding: 24rpx 32rpx;
            box-shadow: 0 8rpx 32rpx rgba(37, 99, 235, 0.08);
            transition: all 0.3s;
            
            &:focus-within {
                border-color: rgba(37, 99, 235, 0.3);
                box-shadow: 0 12rpx 40rpx rgba(37, 99, 235, 0.15);
            }
        }
        
        .search-icon {
            font-size: 32rpx;
            margin-right: 16rpx;
            opacity: 0.6;
        }
        
        .search-input {
            flex: 1;
            font-size: 28rpx;
            color: #1E293B;
            background: transparent;
            border: none;
            outline: none;
            
            &::placeholder {
                color: #94A3B8;
            }
        }
        
        .clear-icon {
            width: 40rpx;
            height: 40rpx;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(148, 163, 184, 0.1);
            border-radius: 50%;
            font-size: 24rpx;
            color: #64748B;
            cursor: pointer;
            transition: all 0.2s;
            
            &:active {
                background: rgba(148, 163, 184, 0.2);
                transform: scale(0.9);
            }
        }
    }
    
    /* 折叠面板样式 */
    .accordion-style {
        display: flex;
        flex-direction: column;
        gap: 16rpx;
        
        .accordion-item {
            background: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(20rpx);
            border: 2rpx solid rgba(37, 99, 235, 0.1);
            border-radius: 20rpx;
            overflow: hidden;
            box-shadow: 0 4rpx 20rpx rgba(37, 99, 235, 0.06);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            
            &.is-active {
                border-color: rgba(37, 99, 235, 0.3);
                box-shadow: 0 8rpx 32rpx rgba(37, 99, 235, 0.12);
            }
        }
        
        .accordion-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 28rpx 24rpx;
            cursor: pointer;
            transition: background 0.2s;
            
            &:active {
                background: rgba(37, 99, 235, 0.03);
            }
        }
        
        .question-wrapper {
            flex: 1;
            display: flex;
            align-items: center;
            gap: 16rpx;
        }
        
        .question-icon {
            width: 48rpx;
            height: 48rpx;
            background: linear-gradient(135deg, #2563EB 0%, #3B82F6 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24rpx;
            font-weight: 800;
            color: #FFFFFF;
            flex-shrink: 0;
            box-shadow: 0 4rpx 16rpx rgba(37, 99, 235, 0.3);
        }
        
        .question-text {
            font-size: 28rpx;
            font-weight: 600;
            color: #1E293B;
            line-height: 1.5;
        }
        
        .expand-icon {
            width: 48rpx;
            height: 48rpx;
            background: rgba(37, 99, 235, 0.08);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            transition: all 0.3s;
            
            .icon-text {
                font-size: 40rpx;
                color: #2563EB;
                font-weight: 300;
                transform: rotate(90deg);
                transition: transform 0.3s;
            }
            
            &.is-expanded .icon-text {
                transform: rotate(270deg);
            }
        }
        
        .accordion-content {
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            
            &.is-expanded {
                max-height: 2000rpx;
            }
        }
        
        .answer-wrapper {
            display: flex;
            gap: 16rpx;
            padding: 0 24rpx 24rpx 24rpx;
        }
        
        .answer-icon {
            width: 48rpx;
            height: 48rpx;
            background: linear-gradient(135deg, #10B981 0%, #34D399 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24rpx;
            font-weight: 800;
            color: #FFFFFF;
            flex-shrink: 0;
            box-shadow: 0 4rpx 16rpx rgba(16, 185, 129, 0.3);
        }
        
        .answer-text {
            flex: 1;
            font-size: 26rpx;
            color: #475569;
            line-height: 1.7;
            padding-top: 4rpx;
        }
        
        .category-tag {
            margin: 16rpx 24rpx 24rpx 88rpx;
            display: inline-block;
            padding: 8rpx 20rpx;
            background: linear-gradient(135deg, rgba(37, 99, 235, 0.1) 0%, rgba(59, 130, 246, 0.1) 100%);
            border-radius: 16rpx;
            
            .tag-text {
                font-size: 22rpx;
                color: #2563EB;
                font-weight: 600;
            }
        }
    }
    
    /* 列表式样式 */
    .list-style {
        display: flex;
        flex-direction: column;
        gap: 20rpx;
        
        .faq-card {
            background: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(20rpx);
            border: 2rpx solid rgba(37, 99, 235, 0.1);
            border-radius: 20rpx;
            padding: 28rpx 24rpx;
            box-shadow: 0 4rpx 20rpx rgba(37, 99, 235, 0.06);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            
            &:active {
                transform: translateY(4rpx);
                box-shadow: 0 2rpx 12rpx rgba(37, 99, 235, 0.08);
            }
        }
        
        .question-section {
            display: flex;
            gap: 16rpx;
            margin-bottom: 24rpx;
        }
        
        .q-badge {
            width: 48rpx;
            height: 48rpx;
            background: linear-gradient(135deg, #2563EB 0%, #3B82F6 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24rpx;
            font-weight: 800;
            color: #FFFFFF;
            flex-shrink: 0;
            box-shadow: 0 4rpx 16rpx rgba(37, 99, 235, 0.3);
        }
        
        .q-content {
            flex: 1;
            display: flex;
            flex-direction: column;
            gap: 12rpx;
        }
        
        .q-text {
            font-size: 28rpx;
            font-weight: 600;
            color: #1E293B;
            line-height: 1.5;
        }
        
        .q-category {
            display: inline-block;
            align-self: flex-start;
            padding: 6rpx 16rpx;
            background: linear-gradient(135deg, rgba(37, 99, 235, 0.1) 0%, rgba(59, 130, 246, 0.1) 100%);
            border-radius: 12rpx;
            
            .category-text {
                font-size: 22rpx;
                color: #2563EB;
                font-weight: 600;
            }
        }
        
        .answer-section {
            display: flex;
            gap: 16rpx;
        }
        
        .a-badge {
            width: 48rpx;
            height: 48rpx;
            background: linear-gradient(135deg, #10B981 0%, #34D399 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24rpx;
            font-weight: 800;
            color: #FFFFFF;
            flex-shrink: 0;
            box-shadow: 0 4rpx 16rpx rgba(16, 185, 129, 0.3);
        }
        
        .a-content {
            flex: 1;
            font-size: 26rpx;
            color: #475569;
            line-height: 1.7;
            padding-top: 4rpx;
        }
    }
    
    /* 无结果状态 */
    .no-result {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        padding: 120rpx 40rpx;
        background: rgba(255, 255, 255, 0.6);
        backdrop-filter: blur(20rpx);
        border: 2rpx dashed rgba(148, 163, 184, 0.3);
        border-radius: 20rpx;
        
        .no-result-icon {
            font-size: 120rpx;
            opacity: 0.3;
            margin-bottom: 24rpx;
        }
        
        .no-result-text {
            font-size: 28rpx;
            color: #64748B;
            font-weight: 600;
            margin-bottom: 8rpx;
        }
        
        .no-result-hint {
            font-size: 24rpx;
            color: #94A3B8;
        }
    }
    
    /* 响应式适配 */
    @media (prefers-reduced-motion: reduce) {
        .faq-widget * {
            transition: none !important;
            animation: none !important;
        }
    }
}
</style>
