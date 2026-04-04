<template>
    <div class="faq-preview">
        <!-- 标题区域 -->
        <div v-if="content.title" class="faq-header">
            <div class="header-decoration"></div>
            <span class="header-title">{{ content.title }}</span>
        </div>

        <!-- 搜索框 -->
        <div v-if="content.show_search" class="search-container">
            <div class="search-box">
                <span class="search-icon">🔍</span>
                <input
                    class="search-input"
                    placeholder="搜索您的问题..."
                    readonly
                />
            </div>
        </div>

        <!-- 折叠面板样式 -->
        <div v-if="content.style == 1" class="accordion-style">
            <div
                v-for="(item, index) in showList"
                :key="index"
                class="accordion-item"
                :class="{ 'is-active': activeIndex === index }"
            >
                <div class="accordion-header" @click="toggleAccordion(index)">
                    <div class="question-wrapper">
                        <div class="question-icon">Q</div>
                        <span class="question-text">{{ item.question }}</span>
                    </div>
                    <div class="expand-icon" :class="{ 'is-expanded': activeIndex === index }">
                        <span class="icon-text">›</span>
                    </div>
                </div>
                <div class="accordion-content" :class="{ 'is-expanded': activeIndex === index }">
                    <div class="answer-wrapper">
                        <div class="answer-icon">A</div>
                        <div class="answer-text">{{ item.answer }}</div>
                    </div>
                    <div v-if="item.category" class="category-tag">
                        <span class="tag-text">{{ item.category }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- 列表式样式 -->
        <div v-if="content.style == 2" class="list-style">
            <div
                v-for="(item, index) in showList"
                :key="index"
                class="faq-card"
            >
                <!-- 问题部分 -->
                <div class="question-section">
                    <div class="q-badge">Q</div>
                    <div class="q-content">
                        <span class="q-text">{{ item.question }}</span>
                        <div v-if="item.category" class="q-category">
                            <span class="category-text">{{ item.category }}</span>
                        </div>
                    </div>
                </div>
                
                <!-- 答案部分 -->
                <div class="answer-section">
                    <div class="a-badge">A</div>
                    <div class="a-content">{{ item.answer }}</div>
                </div>
            </div>
        </div>
    </div>
</template>

<script lang="ts" setup>
import { ref } from 'vue'
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

const activeIndex = ref<number>(-1)

const showList = computed(() => {
    const data = props.content.data?.filter((item: any) => item.is_show == '1') || []
    return data
})

// 切换折叠面板
const toggleAccordion = (index: number) => {
    activeIndex.value = activeIndex.value === index ? -1 : index
}
</script>

<style lang="scss" scoped>
.faq-preview {
    margin: 10px;
    
    /* 标题区域 */
    .faq-header {
        display: flex;
        align-items: center;
        margin-bottom: 12px;
        gap: 6px;
        
        .header-decoration {
            width: 3px;
            height: 16px;
            background: linear-gradient(180deg, #2563EB 0%, #3B82F6 100%);
            border-radius: 1.5px;
        }
        
        .header-title {
            font-size: 16px;
            font-weight: 700;
            color: #1E293B;
            letter-spacing: -0.3px;
        }
    }
    
    /* 搜索容器 */
    .search-container {
        margin-bottom: 12px;
        
        .search-box {
            position: relative;
            display: flex;
            align-items: center;
            background: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(37, 99, 235, 0.1);
            border-radius: 12px;
            padding: 12px 16px;
            box-shadow: 0 4px 16px rgba(37, 99, 235, 0.08);
        }
        
        .search-icon {
            font-size: 16px;
            margin-right: 8px;
            opacity: 0.6;
        }
        
        .search-input {
            flex: 1;
            font-size: 14px;
            color: #1E293B;
            background: transparent;
            border: none;
            outline: none;
            
            &::placeholder {
                color: #94A3B8;
            }
        }
    }
    
    /* 折叠面板样式 */
    .accordion-style {
        display: flex;
        flex-direction: column;
        gap: 8px;
        
        .accordion-item {
            background: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(37, 99, 235, 0.1);
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(37, 99, 235, 0.06);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            
            &.is-active {
                border-color: rgba(37, 99, 235, 0.3);
                box-shadow: 0 4px 16px rgba(37, 99, 235, 0.12);
            }
        }
        
        .accordion-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 14px 12px;
            cursor: pointer;
            transition: background 0.2s;
            
            &:hover {
                background: rgba(37, 99, 235, 0.03);
            }
        }
        
        .question-wrapper {
            flex: 1;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .question-icon {
            width: 24px;
            height: 24px;
            background: linear-gradient(135deg, #2563EB 0%, #3B82F6 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            font-weight: 800;
            color: #FFFFFF;
            flex-shrink: 0;
            box-shadow: 0 2px 8px rgba(37, 99, 235, 0.3);
        }
        
        .question-text {
            font-size: 14px;
            font-weight: 600;
            color: #1E293B;
            line-height: 1.5;
        }
        
        .expand-icon {
            width: 24px;
            height: 24px;
            background: rgba(37, 99, 235, 0.08);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            transition: all 0.3s;
            
            .icon-text {
                font-size: 20px;
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
                max-height: 1000px;
            }
        }
        
        .answer-wrapper {
            display: flex;
            gap: 8px;
            padding: 0 12px 12px 12px;
        }
        
        .answer-icon {
            width: 24px;
            height: 24px;
            background: linear-gradient(135deg, #10B981 0%, #34D399 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            font-weight: 800;
            color: #FFFFFF;
            flex-shrink: 0;
            box-shadow: 0 2px 8px rgba(16, 185, 129, 0.3);
        }
        
        .answer-text {
            flex: 1;
            font-size: 13px;
            color: #475569;
            line-height: 1.7;
            padding-top: 2px;
        }
        
        .category-tag {
            margin: 8px 12px 12px 44px;
            display: inline-block;
            padding: 4px 10px;
            background: linear-gradient(135deg, rgba(37, 99, 235, 0.1) 0%, rgba(59, 130, 246, 0.1) 100%);
            border-radius: 8px;
            
            .tag-text {
                font-size: 11px;
                color: #2563EB;
                font-weight: 600;
            }
        }
    }
    
    /* 列表式样式 */
    .list-style {
        display: flex;
        flex-direction: column;
        gap: 10px;
        
        .faq-card {
            background: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(37, 99, 235, 0.1);
            border-radius: 10px;
            padding: 14px 12px;
            box-shadow: 0 2px 10px rgba(37, 99, 235, 0.06);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            
            &:hover {
                transform: translateY(2px);
                box-shadow: 0 1px 6px rgba(37, 99, 235, 0.08);
            }
        }
        
        .question-section {
            display: flex;
            gap: 8px;
            margin-bottom: 12px;
        }
        
        .q-badge {
            width: 24px;
            height: 24px;
            background: linear-gradient(135deg, #2563EB 0%, #3B82F6 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            font-weight: 800;
            color: #FFFFFF;
            flex-shrink: 0;
            box-shadow: 0 2px 8px rgba(37, 99, 235, 0.3);
        }
        
        .q-content {
            flex: 1;
            display: flex;
            flex-direction: column;
            gap: 6px;
        }
        
        .q-text {
            font-size: 14px;
            font-weight: 600;
            color: #1E293B;
            line-height: 1.5;
        }
        
        .q-category {
            display: inline-block;
            align-self: flex-start;
            padding: 3px 8px;
            background: linear-gradient(135deg, rgba(37, 99, 235, 0.1) 0%, rgba(59, 130, 246, 0.1) 100%);
            border-radius: 6px;
            
            .category-text {
                font-size: 11px;
                color: #2563EB;
                font-weight: 600;
            }
        }
        
        .answer-section {
            display: flex;
            gap: 8px;
        }
        
        .a-badge {
            width: 24px;
            height: 24px;
            background: linear-gradient(135deg, #10B981 0%, #34D399 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            font-weight: 800;
            color: #FFFFFF;
            flex-shrink: 0;
            box-shadow: 0 2px 8px rgba(16, 185, 129, 0.3);
        }
        
        .a-content {
            flex: 1;
            font-size: 13px;
            color: #475569;
            line-height: 1.7;
            padding-top: 2px;
        }
    }
}
</style>
