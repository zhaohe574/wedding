<template>
    <view v-if="content.enabled && showList.length" class="store-map mx-[20rpx] mt-[20rpx]">
        <!-- 标题 -->
        <view v-if="content.title" class="flex items-center mb-[20rpx]">
            <view class="w-[6rpx] h-[32rpx] bg-primary rounded-full mr-[12rpx]"></view>
            <text class="text-lg font-medium text-gray-900">{{ content.title }}</text>
        </view>

        <!-- 地图+列表样式 -->
        <view v-if="content.style == 1" class="map-with-list">
            <!-- 地图（仅在非 H5 环境显示） -->
            <!-- #ifndef H5 -->
            <view class="map-container rounded-lg overflow-hidden mb-[20rpx]">
                <map
                    :latitude="centerLatitude"
                    :longitude="centerLongitude"
                    :markers="markers"
                    :show-location="false"
                    style="width: 100%; height: 400rpx"
                    @markertap="handleMarkerTap"
                ></map>
            </view>
            <!-- #endif -->
            
            <!-- H5 环境提示 -->
            <!-- #ifdef H5 -->
            <view class="map-placeholder rounded-lg overflow-hidden mb-[20rpx] bg-gray-100 flex items-center justify-center" style="height: 400rpx">
                <view class="text-center text-gray-400">
                    <tn-icon name="map" size="80" color="#ccc"></tn-icon>
                    <text class="block mt-2 text-sm">地图功能仅在小程序中可用</text>
                </view>
            </view>
            <!-- #endif -->
            
            <!-- 门店列表 -->
            <view class="store-list">
                <view
                    v-for="(item, index) in showList"
                    :key="index"
                    class="store-card bg-white rounded-lg p-[24rpx] mb-[20rpx]"
                >
                    <view class="flex items-start">
                        <view class="flex-1">
                            <text class="text-base font-medium text-gray-900 mb-[12rpx]">{{ item.name }}</text>
                            <view class="text-sm text-gray-600 mb-[8rpx]">
                                <tn-icon name="map-pin" size="28" class="mr-[8rpx]"></tn-icon>
                                <text>{{ item.address }}</text>
                            </view>
                            <view class="text-sm text-gray-600 mb-[8rpx]">
                                <tn-icon name="phone" size="28" class="mr-[8rpx]"></tn-icon>
                                <text>{{ item.phone }}</text>
                            </view>
                            <view class="text-sm text-gray-600">
                                <tn-icon name="clock" size="28" class="mr-[8rpx]"></tn-icon>
                                <text>{{ item.business_hours }}</text>
                            </view>
                        </view>
                        <view class="flex flex-col ml-[20rpx]" style="gap: 12rpx;">
                            <view 
                                class="action-btn primary-btn" 
                                @click="handleNavigation(item)"
                                hover-class="btn-hover"
                            >
                                <text class="btn-text">导航</text>
                            </view>
                            <view 
                                class="action-btn plain-btn" 
                                @click="handleCall(item)"
                                hover-class="btn-hover"
                            >
                                <text class="btn-text plain-text">电话</text>
                            </view>
                        </view>
                    </view>
                </view>
            </view>
        </view>

        <!-- 纯地图样式 -->
        <view v-if="content.style == 2">
            <!-- #ifndef H5 -->
            <view class="map-only rounded-lg overflow-hidden">
                <map
                    :latitude="centerLatitude"
                    :longitude="centerLongitude"
                    :markers="markers"
                    :show-location="false"
                    style="width: 100%; height: 600rpx"
                    @markertap="handleMarkerTap"
                ></map>
            </view>
            <!-- #endif -->
            
            <!-- #ifdef H5 -->
            <view class="map-placeholder rounded-lg overflow-hidden bg-gray-100 flex items-center justify-center" style="height: 600rpx">
                <view class="text-center text-gray-400">
                    <tn-icon name="map" size="80" color="#ccc"></tn-icon>
                    <text class="block mt-2 text-sm">地图功能仅在小程序中可用</text>
                </view>
            </view>
            <!-- #endif -->
        </view>

        <!-- 纯列表样式 -->
        <view v-if="content.style == 3" class="list-only">
            <view
                v-for="(item, index) in showList"
                :key="index"
                class="store-card bg-white rounded-lg p-[24rpx] mb-[20rpx]"
            >
                <view class="flex items-start">
                    <view class="store-icon mr-[20rpx]">
                        <view class="w-[80rpx] h-[80rpx] rounded-full bg-primary/10 flex items-center justify-center">
                            <tn-icon name="location" size="48" color="#4a5dff"></tn-icon>
                        </view>
                    </view>
                    <view class="flex-1">
                        <text class="text-base font-medium text-gray-900 mb-[12rpx]">{{ item.name }}</text>
                        <view class="text-sm text-gray-600 mb-[8rpx]">
                            <tn-icon name="map-pin" size="28" class="mr-[8rpx]"></tn-icon>
                            <text>{{ item.address }}</text>
                        </view>
                        <view class="flex items-center gap-4 text-sm text-gray-600 mb-[12rpx]">
                            <view>
                                <tn-icon name="phone" size="28" class="mr-[8rpx]"></tn-icon>
                                <text>{{ item.phone }}</text>
                            </view>
                            <view>
                                <tn-icon name="clock" size="28" class="mr-[8rpx]"></tn-icon>
                                <text>{{ item.business_hours }}</text>
                            </view>
                        </view>
                        <view class="flex" style="gap: 12rpx;">
                            <view 
                                class="action-btn-inline primary-btn" 
                                @click="handleNavigation(item)"
                                hover-class="btn-hover"
                            >
                                <text class="btn-text">导航</text>
                            </view>
                            <view 
                                class="action-btn-inline plain-btn" 
                                @click="handleCall(item)"
                                hover-class="btn-hover"
                            >
                                <text class="btn-text plain-text">拨打电话</text>
                            </view>
                        </view>
                    </view>
                </view>
            </view>
        </view>
    </view>
</template>

<script setup lang="ts">
import { computed } from 'vue'

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

// 过滤显示的门店
const showList = computed(() => {
    return props.content.data?.filter((item: any) => item.is_show == '1') || []
})

// 地图中心点（第一个门店的位置）
const centerLatitude = computed(() => {
    return showList.value[0]?.latitude || 39.9042
})

const centerLongitude = computed(() => {
    return showList.value[0]?.longitude || 116.4074
})

// 地图标记点（使用默认标记，避免图片加载失败）
const markers = computed(() => {
    return showList.value.map((item: any, index: number) => ({
        id: index,
        latitude: item.latitude,
        longitude: item.longitude,
        title: item.name,
        // 不设置 iconPath，使用微信小程序默认的红色标记点
        width: 30,
        height: 30
    }))
})

// 处理标记点击
const handleMarkerTap = (e: any) => {
    const index = e.detail.markerId
    const store = showList.value[index]
    if (store) {
        uni.showModal({
            title: store.name,
            content: `地址：${store.address}\n电话：${store.phone}\n营业时间：${store.business_hours}`,
            confirmText: '导航',
            cancelText: '取消',
            success: (res) => {
                if (res.confirm) {
                    handleNavigation(store)
                }
            }
        })
    }
}

// 导航
const handleNavigation = (item: any) => {
    uni.openLocation({
        latitude: item.latitude,
        longitude: item.longitude,
        name: item.name,
        address: item.address,
        success: () => {
            console.log('打开地图成功')
        }
    })
}

// 拨打电话
const handleCall = (item: any) => {
    uni.makePhoneCall({
        phoneNumber: item.phone,
        success: () => {
            console.log('拨打电话成功')
        }
    })
}
</script>

<style scoped lang="scss">
.store-map {
    .map-container,
    .map-only,
    .store-card {
        box-shadow: 0 2rpx 8rpx rgba(0, 0, 0, 0.05);
    }

    .store-card {
        transition: all 0.3s ease;

        &:active {
            transform: scale(0.98);
        }
    }

    // 统一按钮样式（与后台 Element Plus 风格一致）
    .action-btn,
    .action-btn-inline {
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 8rpx;
        cursor: pointer;
        transition: all 0.2s ease;
        user-select: none;
        border: 2rpx solid transparent;
    }

    .action-btn {
        min-width: 120rpx;
        height: 56rpx;
        padding: 0 24rpx;
    }

    .action-btn-inline {
        height: 56rpx;
        padding: 0 28rpx;
    }

    // 主要按钮（实心）
    .primary-btn {
        background-color: #409eff;
        border-color: #409eff;
    }

    // 朴素按钮（空心）
    .plain-btn {
        background-color: #fff;
        border-color: #dcdfe6;
    }

    // 按钮文字
    .btn-text {
        font-size: 26rpx;
        font-weight: 400;
        color: #fff;
        line-height: 1;
    }

    .plain-text {
        color: #606266;
    }

    // Hover 效果
    .btn-hover {
        opacity: 0.8;
    }
}
</style>
