<template>
    <view class="staff-showcase mx-md mt-md" v-if="content.enabled && showList.length">
        <!-- 标题 -->
        <view v-if="content.title" class="flex items-center mb-md">
            <view
                class="title-bar w-[8rpx] h-[34rpx] rounded-full mr-sm"
                :style="titleBarStyle"
            ></view>
            <text class="text-[32rpx] font-semibold text-main">{{ content.title }}</text>
            <view class="flex-1"></view>
            <view
                v-if="content.show_more"
                class="flex items-center text-sm text-muted transition-colors duration-200"
                :style="{ color: primaryColor }"
                @click="handleMore"
            >
                <text>查看更多</text>
                <tn-icon name="right" size="24" :color="primaryColor" class="ml-1"></tn-icon>
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
                        class="staff-card bg-white rounded-[14rpx] overflow-hidden shadow-sm"
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
                                    <tn-icon name="star-fill" size="14" :color="ctaColor"></tn-icon>
                                    <text class="rating-text" :style="{ color: ctaColor }">{{
                                        item.rating || '5.00'
                                    }}</text>
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
                                    :style="tagStyle"
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
        <view v-if="content.style == 2" class="staff-list bg-white rounded-[14rpx] overflow-hidden">
            <view
                v-for="(item, index) in showList"
                :key="index"
                class="staff-item"
                :class="{ 'border-b border-gray-100': index < showList.length - 1 }"
                @click="handleClick(item.link)"
            >
                <!-- 头像 -->
                <view class="avatar-container">
                    <image class="avatar-image" :src="getImageUrl(item.avatar)" mode="aspectFill" />
                </view>

                <!-- 信息区域 -->
                <view class="list-info-section">
                    <!-- 姓名和角色 -->
                    <view class="name-role-row">
                        <text class="list-staff-name">{{ item.name }}</text>
                        <view class="list-role-tag" :style="listRoleStyle">
                            <text class="list-role-text" :style="{ color: primaryColor }">{{
                                item.role || '服务人员'
                            }}</text>
                        </view>
                    </view>

                    <!-- 评分和订单数 -->
                    <view class="list-rating-row">
                        <view class="list-rating-container">
                            <tn-icon name="star-fill" size="14" :color="ctaColor"></tn-icon>
                            <text class="list-rating-text" :style="{ color: ctaColor }">{{
                                item.rating || '5.00'
                            }}</text>
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
                    <tn-icon name="right" size="16" color="#9a9388"></tn-icon>
                </view>
            </view>
        </view>
    </view>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { useAppStore } from '@/stores/app'
import { useThemeStore } from '@/stores/theme'
import { navigateTo } from '@/utils/util'
import { alphaColor, tintColor } from '@/utils/color'

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
const themeStore = useThemeStore()
const primaryColor = computed(() => themeStore.primaryColor || '#0B0B0B')
const ctaColor = computed(() => themeStore.ctaColor || '#9F7A2E')
const tagStyle = computed(() => ({
    background: `linear-gradient(135deg, ${tintColor(primaryColor.value, 0.85)} 0%, ${tintColor(
        primaryColor.value,
        0.7
    )} 100%)`,
    border: `1rpx solid ${tintColor(primaryColor.value, 0.55)}`,
    color: primaryColor.value
}))
const titleBarStyle = computed(() => ({
    background: `linear-gradient(180deg, ${primaryColor.value} 0%, ${tintColor(
        primaryColor.value,
        0.35
    )} 100%)`,
    boxShadow: `0 2rpx 8rpx ${alphaColor(primaryColor.value, 0.18)}`
}))
const listRoleStyle = computed(() => ({
    background: tintColor(primaryColor.value, 0.9),
    border: `1rpx solid ${tintColor(primaryColor.value, 0.65)}`
}))

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
            gap: 16rpx; // 卡片间距
            padding: 0 16rpx 0 0;
        }
    }

    // 卡片样式
    .staff-card {
        display: inline-block;
        width: 280rpx; // 卡片宽度
        border: 1rpx solid #f8f7f2;
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
            bottom: 12rpx;
            left: 12rpx;
            padding: 6rpx 12rpx;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(8rpx);
            border-radius: 12rpx;
            box-shadow: 0 2rpx 8rpx rgba(0, 0, 0, 0.1);

            .role-text {
                font-size: 24rpx;
                font-weight: 500;
                color: #111111;
            }
        }
    }

    // 信息区域
    .info-section {
        padding: 16rpx 14rpx;

        // 姓名
        .staff-name {
            font-size: 28rpx;
            font-weight: 600;
            color: #111111;
            margin-bottom: 8rpx;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        // 评分行
        .rating-row {
            display: flex;
            align-items: center;
            margin-bottom: 8rpx;

            .rating-container {
                display: flex;
                align-items: center;
                gap: 6rpx;

                .rating-text {
                    font-size: 26rpx;
                    font-weight: 600;
                    color: #c8a45d;
                }
            }

            .divider {
                width: 2rpx;
                height: 20rpx;
                background: #e7e2d6;
                margin: 0 12rpx;
            }

            .order-count {
                font-size: 22rpx;
                color: #6c665c;
            }
        }

        // 标签容器
        .tags-container {
            display: flex;
            flex-wrap: wrap;
            gap: 8rpx;

            .tag-item {
                padding: 4rpx 10rpx;
                background: linear-gradient(135deg, #F8F7F2 0%, #F3F2EE 100%);
                border-radius: 12rpx;
                border: 1rpx solid #F3F2EE;
                font-size: 20rpx;
                color: #6C665C;
                font-weight: 500;
                line-height: 1.4;
            }
        }
    }

    // 列表样式
    .staff-list {
        background: #ffffff;
        border-radius: 14rpx;
        overflow: hidden;
        box-shadow: 0 2rpx 10rpx rgba(0, 0, 0, 0.08);
    }

    .staff-item {
        display: flex;
        align-items: center;
        padding: 20rpx;
        transition: background-color 0.2s ease;

        &:active {
            background-color: #F8F7F2;
        }

        // 头像容器
        .avatar-container {
            flex-shrink: 0;
            width: 104rpx;
            height: 104rpx;
            border-radius: 52rpx;
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
            margin-left: 16rpx;
            overflow: hidden;

            // 姓名和角色行
            .name-role-row {
                display: flex;
                align-items: center;
                margin-bottom: 8rpx;

                .list-staff-name {
                    font-size: 32rpx;
                    font-weight: 600;
                    color: #111111;
                    max-width: 60%;
                    overflow: hidden;
                    text-overflow: ellipsis;
                    white-space: nowrap;
                }

                .list-role-tag {
                    margin-left: 8rpx;
                    padding: 4rpx 10rpx;
                    border-radius: 12rpx;

                    .list-role-text {
                        font-size: 22rpx;
                        font-weight: 500;
                    }
                }
            }

            // 评分行
            .list-rating-row {
                display: flex;
                align-items: center;
                margin-bottom: 8rpx;

                .list-rating-container {
                    display: flex;
                    align-items: center;
                    gap: 6rpx;

                    .list-rating-text {
                        font-size: 26rpx;
                        font-weight: 600;
                        color: #c8a45d;
                    }
                }

                .list-order-count {
                    margin-left: 12rpx;
                    font-size: 24rpx;
                    color: #6c665c;
                }
            }

            // 标签行
            .list-tags-row {
                display: flex;
                flex-wrap: wrap;

                .list-tag-text {
                    font-size: 24rpx;
                    color: #6c665c;
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
