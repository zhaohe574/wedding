<template>
    <div class="quick-entry-preview">
        <!-- 网格布局 -->
        <div
            v-if="content.style == 1"
            class="grid-layout"
        >
            <div
                class="entries-grid"
                :style="{ 'grid-template-columns': `repeat(${content.per_line}, 1fr)` }"
            >
                <div
                    v-for="(item, index) in showList"
                    :key="index"
                    class="entry-item"
                >
                    <!-- 图标容器 -->
                    <div class="icon-wrapper">
                        <div class="icon-bg"></div>
                        <decoration-img
                            width="28px"
                            height="28px"
                            :src="item.icon"
                            class="entry-icon"
                        />
                        <!-- 光晕效果 -->
                        <div class="icon-glow"></div>
                    </div>
                    <!-- 标题 -->
                    <div class="entry-title">{{ item.title }}</div>
                </div>
            </div>
        </div>

        <!-- 横向滑动 -->
        <div v-if="content.style == 2" class="scroll-layout">
            <div class="scroll-container">
                <div class="entries-scroll">
                    <div
                        v-for="(item, index) in showList"
                        :key="index"
                        class="entry-item"
                    >
                        <!-- 图标容器 -->
                        <div class="icon-wrapper">
                            <div class="icon-bg"></div>
                            <decoration-img
                                width="28px"
                                height="28px"
                                :src="item.icon"
                                class="entry-icon"
                            />
                            <!-- 光晕效果 -->
                            <div class="icon-glow"></div>
                        </div>
                        <!-- 标题 -->
                        <div class="entry-title">{{ item.title }}</div>
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
</script>

<style lang="scss" scoped>
.quick-entry-preview {
    /* 网格布局样式 */
    .grid-layout {
        background: #FFFFFF;
        border-radius: 12px;
        padding: 16px 10px;
        box-shadow: 0 2px 10px rgba(15, 23, 42, 0.06);
        
        .entries-grid {
            display: grid;
            gap: 16px 8px;
            width: 100%;
        }
    }
    
    /* 横向滑动样式 */
    .scroll-layout {
        background: #FFFFFF;
        border-radius: 12px;
        padding: 16px 0;
        box-shadow: 0 2px 10px rgba(15, 23, 42, 0.06);
        
        .scroll-container {
            overflow-x: auto;
            
            &::-webkit-scrollbar {
                display: none;
            }
        }
        
        .entries-scroll {
            display: inline-flex;
            gap: 16px;
            padding: 0 16px;
        }
    }
    
    /* 入口项通用样式 */
    .entry-item {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 8px;
        cursor: pointer;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        
        /* 悬停效果 */
        &:hover {
            transform: scale(1.05);
            
            .icon-wrapper {
                .icon-bg {
                    transform: scale(1.1);
                    background: linear-gradient(135deg, #E0F2FE 0%, #BAE6FD 100%);
                }
                
                .icon-glow {
                    opacity: 1;
                }
            }
            
            .entry-title {
                color: #0369A1;
            }
        }
        
        /* 点击效果 */
        &:active {
            transform: scale(0.95);
        }
    }
    
    /* 图标容器 */
    .icon-wrapper {
        position: relative;
        width: 48px;
        height: 48px;
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
            border-radius: 12px;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            z-index: 0;
        }
        
        /* 图标 */
        .entry-icon {
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
            width: 60px;
            height: 60px;
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
        font-size: 13px;
        font-weight: 500;
        color: #334155;
        text-align: center;
        line-height: 1.4;
        max-width: 60px;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
        transition: color 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }
}
</style>
