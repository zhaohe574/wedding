<template>
    <view class="staff-showcase mx-md mt-md" v-if="content.enabled && showList.length">
        <!-- 标题 -->
        <view v-if="content.title" class="flex items-center mb-md">
            <view class="title-bar w-[8rpx] h-[34rpx] bg-primary rounded-full mr-sm"></view>
            <text class="text-[32rpx] font-semibold text-main">{{ content.title }}</text>
            <view class="flex-1"></view>
            <view
                v-if="content.show_more"
                class="flex items-center text-sm text-muted transition-colors duration-200"
                @click="handleMore"
            >
                <text>查看更多</text>
                <tn-icon name="right" size="24" color="#999999" class="ml-1"></tn-icon>
            </view>
        </view>

        <!-- 卡片样式 - 横向滑动 -->
        <view v-if="content.style == 1" class="staff-grid">
            <scroll-view
                class="scroll-container"
                scroll-x
                :show-scrollbar="false"
                :enable-flex="true"
            >
                <view class="scroll-content">
                    <view
                        v-for="(item, index) in showList"
                        :key="index"
                        class="staff-card bg-white rounded-[24rpx] overflow-hidden shadow-sm"
                        @click="handleClick(item.link)"
                    >
                        <!-- 头像容器 -->
                        <view class="image-container">
                            <image
                                class="staff-image"
                                :src="getImageUrl(item.avatar)"
                                mode="aspectFill"
                            />
                            <!-- 渐变遮罩 -->
                            <view class="image-overlay"></view>
                            <!-- 角色标签 - 底部左侧 -->
                            <view class="role-tag">
                                <text class="role-text">{{ item.role || '服务人员' }}</text>
                            </view>
                        </view>
                        
                        <!-- 信息区域 -->
                        <view class="info-section">
                            <!-- 姓名 -->
                            <view class="staff-name">{{ item.name }}</view>
                            
                            <!-- 评分和订单数 -->
                            <view class="rating-row">
                                <view class="rating-container">
                                    <tn-icon name="star-fill" size="14" color="#f59e0b"></tn-icon>
                                    <text class="rating-text">{{ item.rating || '5.00' }}</text>
                                </view>
                                <view class="divider"></view>
                                <text class="order-count">{{ item.order_count || 0 }}单</text>
                            </view>
                            
                            <!-- 标签 -->
                            <view v-if="item.tags && item.tags.length" class="tags-container">
                                <text
                                    v-for="(tag, tagIndex) in item.tags.slice(0, 3)"
                                    :key="tagIndex"
                                    class="tag-item"
                                >
                                    {{ tag }}
                                </text>
                            </view>
                        </view>
                    </view>
                </view>
            </scroll-view>
        </view>

        <!-- 列表样式 -->
        <view v-if="content.style == 2" class="staff-list bg-white rounded-[24rpx] overflow-hidden">
            <view
                v-for="(item, index) in showList"
                :key="index"
                class="staff-item"
                :class="{ 'border-b border-gray-100': index < showList.length - 1 }"
                @click="handleClick(item.link)"
            >
                <!-- 头像 -->
                <view class="avatar-container">
                    <image
                        class="avatar-image"
                        :src="getImageUrl(item.avatar)"
                        mode="aspectFill"
                    />
                </view>
                
                <!-- 信息区域 -->
                <view class="list-info-section">
                    <!-- 姓名和角色 -->
                    <view class="name-role-row">
                        <text class="list-staff-name">{{ item.name }}</text>
                        <view class="list-role-tag">
                            <text class="list-role-text">{{ item.role || '服务人员' }}</text>
                        </view>
                    </view>
                    
                    <!-- 评分和订单数 -->
                    <view class="list-rating-row">
                        <view class="list-rating-container">
                            <tn-icon name="star-fill" size="14" color="#f59e0b"></tn-icon>
                            <text class="list-rating-text">{{ item.rating || '5.00' }}</text>
                        </view>
                        <text class="list-order-count">已服务{{ item.order_count || 0 }}单</text>
                    </view>
                    
                    <!-- 标签 -->
                    <view v-if="item.tags && item.tags.length" class="list-tags-row">
                        <text
                            v-for="(tag, tagIndex) in item.tags.slice(0, 3)"
                            :key="tagIndex"
                            class="list-tag-text"
                        >
                            {{ tag }}{{ tagIndex < Math.min(item.tags.length, 3) - 1 ? ' · ' : '' }}
                        </text>
                    </view>
                </view>
                
                <!-- 箭头 -->
                <view class="arrow-container">
                    <tn-icon name="right" size="16" color="#9ca3af"></tn-icon>
                </view>
            </view>
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
        default: () => ({
            data: [],
            enabled: true,
            title: '推荐人员',
            show_more: true,
            show_count: 10,
            style: 1, // 1: 卡片样式（横向滚动）, 2: 列表样式
            more_link: {}
        })
    },
    styles: {
        type: Object,
        default: () => ({})
    }
})

const { getImageUrl } = useAppStore()

// 过滤显示的列表
const showList = computed(() => {
    const data = props.content.data?.filter((item: any) => item.is_show !== '0') || []
    const limit = props.content.show_count || data.length
    return data.slice(0, limit)
})

// 点击人员卡片
const handleClick = (link: any) => {
    if (link && Object.keys(link).length > 0) {
        navigateTo(link)
    }
}

// 查看更多
const handleMore = () => {
    if (props.content.more_link && Object.keys(props.content.more_link).length > 0) {
        navigateTo(props.content.more_link)
    }
}
</script>

<style lang="scss" scoped>
.staff-showcase {
    // 横向滚动容器
    .scroll-container {
        width: 100%;
        white-space: nowrap;
        
        .scroll-content {
            display: inline-flex;
            gap: 24rpx; // 卡片间距
            padding: 0 24rpx 0 0;
        }
    }
    
    // 卡片样式
    .staff-card {
        display: inline-block;
        width: 280rpx; // 卡片宽度
        border: 1rpx solid #f3f4f6;
        flex-shrink: 0;
        transition: all 0.3s ease;
        
        &:active {
            transform: translateY(-4rpx);
            box-shadow: 0 8rpx 24rpx rgba(0, 0, 0, 0.12);
        }
    }

    // 图片容器
    .image-container {
        position: relative;
        width: 100%;
        height: 320rpx; // 头像容器高度（对应后台的160px）
        overflow: hidden;
        
        .staff-image {
            width: 100%;
            height: 100%;
            display: block;
        }
        
        // 渐变遮罩
        .image-overlay {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 120rpx;
            background: linear-gradient(to top, rgba(0, 0, 0, 0.5), transparent);
            pointer-events: none;
        }
        
        // 角色标签 - 底部左侧
        .role-tag {
            position: absolute;
            bottom: 16rpx;
            left: 16rpx;
            padding: 8rpx 16rpx;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(8rpx);
            border-radius: 20rpx;
            box-shadow: 0 2rpx 8rpx rgba(0, 0, 0, 0.1);
            
            .role-text {
                font-size: 24rpx;
                font-weight: 500;
                color: #0f172a;
            }
        }
    }

    // 信息区域
    .info-section {
        padding: 20rpx 16rpx;
        
        // 姓名
        .staff-name {
            font-size: 30rpx;
            font-weight: 600;
            color: #0f172a;
            margin-bottom: 12rpx;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        
        // 评分行
        .rating-row {
            display: flex;
            align-items: center;
            margin-bottom: 12rpx;
            
            .rating-container {
                display: flex;
                align-items: center;
                gap: 6rpx;
                
                .rating-text {
                    font-size: 26rpx;
                    font-weight: 600;
                    color: #f59e0b;
                }
            }
            
            .divider {
                width: 2rpx;
                height: 20rpx;
                background: #e5e7eb;
                margin: 0 12rpx;
            }
            
            .order-count {
                font-size: 22rpx;
                color: #6b7280;
            }
        }
        
        // 标签容器
        .tags-container {
            display: flex;
            flex-wrap: wrap;
            gap: 8rpx;
            
            .tag-item {
                padding: 4rpx 12rpx;
                background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);
                border-radius: 12rpx;
                border: 1rpx solid #bae6fd;
                font-size: 20rpx;
                color: #0369a1;
                font-weight: 500;
                line-height: 1.4;
            }
        }
    }

    // 列表样式
    .staff-list {
        background: #ffffff;
        border-radius: 16rpx;
        overflow: hidden;
        box-shadow: 0 2rpx 12rpx rgba(0, 0, 0, 0.08);
    }
    
    .staff-item {
        display: flex;
        align-items: center;
        padding: 24rpx;
        transition: background-color 0.2s ease;
        
        &:active {
            background-color: #f9fafb;
        }
        
        // 头像容器
        .avatar-container {
            flex-shrink: 0;
            width: 120rpx;
            height: 120rpx;
            border-radius: 60rpx;
            overflow: hidden;
            box-shadow: 0 2rpx 8rpx rgba(0, 0, 0, 0.08);
            
            .avatar-image {
                width: 100%;
                height: 100%;
                display: block;
            }
        }
        
        // 信息区域
        .list-info-section {
            flex: 1;
            margin-left: 24rpx;
            overflow: hidden;
            
            // 姓名和角色行
            .name-role-row {
                display: flex;
                align-items: center;
                margin-bottom: 12rpx;
                
                .list-staff-name {
                    font-size: 32rpx;
                    font-weight: 600;
                    color: var(--color-main);
                    max-width: 60%;
                    overflow: hidden;
                    text-overflow: ellipsis;
                    white-space: nowrap;
                }
                
                .list-role-tag {
                    margin-left: 12rpx;
                    padding: 6rpx 12rpx;
                    background: var(--color-primary-light-9);
                    border-radius: 12rpx;
                    border: 1rpx solid var(--color-primary-light-7);
                    
                    .list-role-text {
                        font-size: 22rpx;
                        color: var(--color-primary);
                        font-weight: 500;
                    }
                }
            }
            
            // 评分行
            .list-rating-row {
                display: flex;
                align-items: center;
                margin-bottom: 12rpx;
                
                .list-rating-container {
                    display: flex;
                    align-items: center;
                    gap: 6rpx;
                    
                    .list-rating-text {
                        font-size: 26rpx;
                        font-weight: 600;
                        color: #f59e0b;
                    }
                }
                
                .list-order-count {
                    margin-left: 20rpx;
                    font-size: 24rpx;
                    color: var(--color-muted);
                }
            }
            
            // 标签行
            .list-tags-row {
                display: flex;
                flex-wrap: wrap;
                
                .list-tag-text {
                    font-size: 24rpx;
                    color: var(--color-muted);
                }
            }
        }
        
        // 箭头容器
        .arrow-container {
            flex-shrink: 0;
            margin-left: 16rpx;
        }
    }
}
</style>
