<template>
    <div class="staff-showcase mx-[10px] mt-[10px]">
        <!-- 标题 -->
        <div v-if="content.title" class="flex items-center mb-[12px]">
            <div class="w-[4px] h-[17px] bg-primary rounded-full mr-[8px]"></div>
            <span class="text-base font-medium text-gray-900">{{ content.title }}</span>
            <div class="flex-1"></div>
            <div v-if="content.show_more" class="flex items-center text-xs text-gray-500">
                <span>查看更多</span>
                <icon name="el-icon-ArrowRight" :size="12" />
            </div>
        </div>

        <!-- 横向滑动卡片样式 -->
        <div v-if="content.style == 1" class="staff-grid">
            <div class="scroll-container">
                <div class="scroll-content">
                    <div
                        v-for="(item, index) in showList"
                        :key="index"
                        class="staff-card bg-white rounded-xl overflow-hidden shadow-sm"
                    >
                        <!-- 头像容器 -->
                        <div class="image-container">
                            <decoration-img
                                width="100%"
                                height="160px"
                                :src="item.avatar"
                                fit="cover"
                            />
                            <!-- 渐变遮罩 -->
                            <div class="image-overlay"></div>
                            <!-- 角色标签 - 底部左侧 -->
                            <div class="role-tag">
                                <span class="role-text">{{ item.role || '服务人员' }}</span>
                            </div>
                        </div>
                        
                        <!-- 信息区域 -->
                        <div class="info-section">
                            <!-- 姓名 -->
                            <div class="staff-name">{{ item.name }}</div>
                            
                            <!-- 评分和订单数 -->
                            <div class="rating-row">
                                <div class="rating-container">
                                    <icon name="el-icon-StarFilled" :size="14" color="#f59e0b" />
                                    <span class="rating-text">{{ item.rating || '5.00' }}</span>
                                </div>
                                <div class="divider"></div>
                                <span class="order-count">{{ item.order_count || 0 }}单</span>
                            </div>
                            
                            <!-- 标签 -->
                            <div v-if="item.tags && item.tags.length" class="tags-container">
                                <span
                                    v-for="(tag, tagIndex) in item.tags.slice(0, 3)"
                                    :key="tagIndex"
                                    class="tag-item"
                                >
                                    {{ tag }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 列表样式 -->
        <div v-if="content.style == 2" class="staff-list bg-white rounded-xl overflow-hidden shadow-sm">
            <div
                v-for="(item, index) in showList"
                :key="index"
                class="staff-item"
                :class="{ 'border-b border-gray-100': index < showList.length - 1 }"
            >
                <!-- 头像容器 -->
                <div class="avatar-container">
                    <decoration-img
                        width="60px"
                        height="60px"
                        :src="item.avatar"
                        class="avatar-image"
                        fit="cover"
                    />
                </div>
                
                <!-- 信息区域 -->
                <div class="list-info-section">
                    <!-- 姓名和角色 -->
                    <div class="name-role-row">
                        <span class="list-staff-name">{{ item.name }}</span>
                        <span class="list-role-tag">{{ item.role || '服务人员' }}</span>
                    </div>
                    
                    <!-- 评分和订单数 -->
                    <div class="list-rating-row">
                        <div class="list-rating-container">
                            <icon name="el-icon-StarFilled" :size="14" color="#f59e0b" />
                            <span class="list-rating-text">{{ item.rating || '5.00' }}</span>
                        </div>
                        <span class="list-order-count">已服务{{ item.order_count || 0 }}单</span>
                    </div>
                    
                    <!-- 标签 -->
                    <div v-if="item.tags && item.tags.length" class="list-tags-row">
                        <span class="list-tag-text">
                            {{ item.tags.slice(0, 3).join(' · ') }}
                        </span>
                    </div>
                </div>
                
                <!-- 箭头 -->
                <div class="arrow-container">
                    <icon name="el-icon-ArrowRight" :size="16" color="#9ca3af" />
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
    const limit = props.content.show_count || data.length
    return data.slice(0, limit)
})
</script>

<style lang="scss" scoped>
.staff-showcase {
    // 横向滚动容器
    .scroll-container {
        width: 100%;
        overflow-x: auto;
        overflow-y: hidden;
        white-space: nowrap;
        -webkit-overflow-scrolling: touch;
        
        &::-webkit-scrollbar {
            height: 4px;
        }
        
        &::-webkit-scrollbar-thumb {
            background: #d1d5db;
            border-radius: 2px;
        }
        
        .scroll-content {
            display: inline-flex;
            gap: 12px;
            padding-bottom: 8px;
        }
    }
    
    // 卡片样式
    .staff-card {
        display: inline-block;
        width: 140px;
        border: 1px solid #f3f4f6;
        flex-shrink: 0;
        transition: all 0.3s ease;
        
        &:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }
    }

    // 图片容器
    .image-container {
        position: relative;
        width: 100%;
        height: 160px;
        overflow: hidden;
        
        // 渐变遮罩
        .image-overlay {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 60px;
            background: linear-gradient(to top, rgba(0, 0, 0, 0.5), transparent);
            pointer-events: none;
        }
        
        // 角色标签 - 底部左侧
        .role-tag {
            position: absolute;
            bottom: 8px;
            left: 8px;
            padding: 4px 8px;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(4px);
            border-radius: 10px;
            box-shadow: 0 1px 4px rgba(0, 0, 0, 0.1);
            
            .role-text {
                font-size: 12px;
                font-weight: 500;
                color: #0f172a;
            }
        }
    }

    // 信息区域
    .info-section {
        padding: 10px 8px;
        
        // 姓名
        .staff-name {
            font-size: 15px;
            font-weight: 600;
            color: #0f172a;
            margin-bottom: 6px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        
        // 评分行
        .rating-row {
            display: flex;
            align-items: center;
            margin-bottom: 6px;
            
            .rating-container {
                display: flex;
                align-items: center;
                gap: 3px;
                
                .rating-text {
                    font-size: 13px;
                    font-weight: 600;
                    color: #f59e0b;
                }
            }
            
            .divider {
                width: 1px;
                height: 10px;
                background: #e5e7eb;
                margin: 0 6px;
            }
            
            .order-count {
                font-size: 11px;
                color: #6b7280;
            }
        }
        
        // 标签容器
        .tags-container {
            display: flex;
            flex-wrap: wrap;
            gap: 4px;
            
            .tag-item {
                padding: 2px 6px;
                background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);
                border-radius: 6px;
                border: 1px solid #bae6fd;
                font-size: 10px;
                color: #0369a1;
                font-weight: 500;
                line-height: 1.4;
            }
        }
    }

    // 列表样式
    .staff-list {
        border: 1px solid #f3f4f6;
    }
    
    .staff-item {
        display: flex;
        align-items: center;
        padding: 12px;
        transition: background-color 0.2s ease;
        
        &:hover {
            background-color: #f9fafb;
        }
        
        // 头像容器
        .avatar-container {
            flex-shrink: 0;
            width: 60px;
            height: 60px;
            border-radius: 30px;
            overflow: hidden;
            box-shadow: 0 1px 4px rgba(0, 0, 0, 0.08);
            
            .avatar-image {
                width: 100%;
                height: 100%;
                display: block;
            }
        }
        
        // 信息区域
        .list-info-section {
            flex: 1;
            margin-left: 12px;
            overflow: hidden;
            
            // 姓名和角色行
            .name-role-row {
                display: flex;
                align-items: center;
                margin-bottom: 6px;
                
                .list-staff-name {
                    font-size: 14px;
                    font-weight: 600;
                    color: #0f172a;
                    max-width: 60%;
                    overflow: hidden;
                    text-overflow: ellipsis;
                    white-space: nowrap;
                }
                
                .list-role-tag {
                    margin-left: 6px;
                    padding: 3px 6px;
                    background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);
                    border-radius: 6px;
                    border: 1px solid #bae6fd;
                    font-size: 11px;
                    color: #0369a1;
                    font-weight: 500;
                }
            }
            
            // 评分行
            .list-rating-row {
                display: flex;
                align-items: center;
                margin-bottom: 6px;
                
                .list-rating-container {
                    display: flex;
                    align-items: center;
                    gap: 3px;
                    
                    .list-rating-text {
                        font-size: 12px;
                        font-weight: 600;
                        color: #f59e0b;
                    }
                }
                
                .list-order-count {
                    margin-left: 10px;
                    font-size: 11px;
                    color: #6b7280;
                }
            }
            
            // 标签行
            .list-tags-row {
                display: flex;
                flex-wrap: wrap;
                
                .list-tag-text {
                    font-size: 11px;
                    color: #6b7280;
                }
            }
        }
        
        // 箭头容器
        .arrow-container {
            flex-shrink: 0;
            margin-left: 8px;
        }
    }
}
</style>
