<template>
    <view v-if="content.enabled && showList.length" class="coupon-receive mx-[24rpx] mt-[24rpx]">
        <!-- 标题 -->
        <view v-if="content.title" class="flex items-center justify-between mb-[20rpx] px-[8rpx]">
            <view class="flex items-center">
                <view 
                    class="w-[8rpx] h-[36rpx] rounded-full mr-[16rpx]" 
                    :style="{ background: `linear-gradient(180deg, ${$theme.primaryColor} 0%, ${$theme.primaryColor} 100%)` }"
                ></view>
                <text class="text-[32rpx] font-semibold text-[#1E293B]">{{ content.title }}</text>
            </view>
            <view
                v-if="content.show_more"
                class="flex items-center cursor-pointer"
                @click="handleMore"
            >
                <text class="text-[26rpx] text-[#64748B]">查看更多</text>
                <tn-icon name="right" size="28" color="#94A3B8" class="ml-[4rpx]" />
            </view>
        </view>

        <!-- 横向滑动 -->
        <scroll-view v-if="content.style == 1" scroll-x class="coupon-scroll">
            <view class="flex gap-[24rpx]">
                <view
                    v-for="(item, index) in showList"
                    :key="index"
                    class="coupon-card-horizontal flex-shrink-0"
                    @click="goDetail(item)"
                >
                    <!-- 玻璃态背景 -->
                    <view 
                        class="coupon-glass-bg"
                        :style="{ background: `linear-gradient(135deg, ${$theme.primaryColor}F2 0%, ${$theme.secondaryColor}E6 100%)` }"
                    ></view>
                    
                    <!-- 内容区 -->
                    <view class="coupon-content-wrapper">
                        <!-- 左侧金额区 -->
                        <view class="coupon-amount-section">
                            <view class="coupon-value-wrapper">
                                <text class="coupon-symbol">¥</text>
                                <text class="coupon-value">{{ parseFloat(item.discount_amount || 0).toFixed(0) }}</text>
                            </view>
                            <view class="coupon-condition">满{{ parseFloat(item.threshold_amount || 0).toFixed(0) }}元</view>
                        </view>
                        
                        <!-- 分隔线 -->
                        <view class="coupon-divider"></view>
                        
                        <!-- 右侧信息区 -->
                        <view class="coupon-info-section">
                            <view class="coupon-name">{{ item.name || '优惠券' }}</view>
                            <view class="coupon-time">{{ formatValidPeriod(item) }}</view>
                            <view 
                                class="coupon-btn coupon-btn-active"
                                :style="{ color: $theme.primaryColor }"
                            >
                                <text>查看详情</text>
                            </view>
                        </view>
                    </view>
                    
                    <!-- 装饰圆点 -->
                    <view class="coupon-dot coupon-dot-left"></view>
                    <view class="coupon-dot coupon-dot-right"></view>
                </view>
            </view>
        </scroll-view>

        <!-- 纵向列表 -->
        <view v-if="content.style == 2" class="coupon-list">
            <view
                v-for="(item, index) in showList"
                :key="index"
                class="coupon-card-vertical mb-[24rpx]"
                @click="goDetail(item)"
            >
                <!-- 玻璃态背景 -->
                <view 
                    class="coupon-glass-bg"
                    :style="{ background: `linear-gradient(135deg, ${$theme.primaryColor}F2 0%, ${$theme.secondaryColor}E6 100%)` }"
                ></view>
                
                <!-- 内容区 -->
                <view class="coupon-content-wrapper">
                    <!-- 左侧金额区 -->
                    <view class="coupon-amount-section">
                        <view class="coupon-symbol">¥</view>
                        <view class="coupon-value">{{ parseFloat(item.discount_amount || 0).toFixed(0) }}</view>
                        <view class="coupon-condition">满{{ parseFloat(item.threshold_amount || 0).toFixed(0) }}元</view>
                    </view>
                    
                    <!-- 分隔线 -->
                    <view class="coupon-divider"></view>
                    
                    <!-- 右侧信息区 -->
                    <view class="coupon-info-section">
                        <view class="coupon-name">{{ item.name || '优惠券' }}</view>
                        <view class="coupon-time">{{ formatValidPeriod(item) }}</view>
                        <view 
                            class="coupon-btn coupon-btn-active"
                            :style="{ color: $theme.primaryColor }"
                        >
                            <text>查看详情</text>
                        </view>
                    </view>
                </view>
                
                <!-- 装饰圆点 -->
                <view class="coupon-dot coupon-dot-left"></view>
                <view class="coupon-dot coupon-dot-right"></view>
            </view>
        </view>
    </view>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { useThemeStore } from '@/stores/theme'
import { navigateTo } from '@/utils/util'

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

const $theme = useThemeStore()

// 过滤显示的列表
const showList = computed(() => {
    const data = props.content.data?.filter((item: any) => item.is_show !== '0') || []
    const limit = props.content.show_count || data.length
    return data.slice(0, limit)
})

// 格式化有效期显示
const formatValidPeriod = (item: any) => {
    const validType = Number(item.valid_type || 0)
    const validDays = Number(item.valid_days || 0)
    
    // 领取后N天有效
    if (validType === 2 && validDays) {
        return `领取后${validDays}天有效`
    }
    
    // 固定时间段
    const start = Number(item.valid_start_time || 0)
    const end = Number(item.valid_end_time || 0)
    
    if (!start && !end) return '长期有效'
    
    const formatDate = (timestamp: number) => {
        if (!timestamp) return ''
        const date = new Date(timestamp * 1000)
        const year = date.getFullYear()
        const month = String(date.getMonth() + 1).padStart(2, '0')
        const day = String(date.getDate()).padStart(2, '0')
        return `${year}.${month}.${day}`
    }
    
    if (start && end) {
        return `${formatDate(start)} - ${formatDate(end)}`
    }
    
    if (start) return `${formatDate(start)}起`
    return `${formatDate(end)}止`
}

const goDetail = (item: any) => {
    const id = item.coupon_id || item.id
    if (!id) return
    uni.navigateTo({
        url: `/pages/coupon/detail?id=${id}`
    })
}

// 查看更多
const handleMore = () => {
    if (props.content.more_link && Object.keys(props.content.more_link).length > 0) {
        navigateTo(props.content.more_link)
    }
}

</script>

<style scoped lang="scss">
.coupon-receive {
    .coupon-scroll {
        white-space: nowrap;
        
        &::-webkit-scrollbar {
            display: none;
        }
    }
    
    // 横向卡片样式
    .coupon-card-horizontal {
        position: relative;
        width: 580rpx;
        height: 200rpx;
        border-radius: 20rpx;
        overflow: hidden;
        transition: all 0.2s ease;

        &:active {
            transform: translateY(-4rpx);
        }
    }
    
    // 纵向卡片样式
    .coupon-card-vertical {
        position: relative;
        width: 100%;
        height: 200rpx;
        border-radius: 20rpx;
        overflow: hidden;
        transition: all 0.2s ease;

        &:active {
            transform: translateY(-4rpx);
        }
    }
    
    // 玻璃态背景
    .coupon-glass-bg {
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        backdrop-filter: blur(20rpx);
        border-radius: 20rpx;
        border: 2rpx solid rgba(255, 255, 255, 0.3);
        box-shadow: 0 16rpx 48rpx rgba(124, 58, 237, 0.25),
                    0 4rpx 12rpx rgba(124, 58, 237, 0.15),
                    inset 0 2rpx 4rpx rgba(255, 255, 255, 0.2);
    }
    
    // 内容包裹层
    .coupon-content-wrapper {
        position: relative;
        display: flex;
        align-items: center;
        height: 100%;
        padding: 32rpx;
        z-index: 1;
    }
    
    // 左侧金额区域
    .coupon-amount-section {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        width: 200rpx;
        
        .coupon-value-wrapper {
            display: flex;
            align-items: baseline;
            line-height: 1;
            margin-bottom: 12rpx;
        }
        
        .coupon-symbol {
            font-size: 32rpx;
            font-weight: 600;
            color: rgba(255, 255, 255, 0.95);
            margin-right: 4rpx;
        }
        
        .coupon-value {
            font-size: 64rpx;
            font-weight: 700;
            color: #FFFFFF;
            text-shadow: 0 4rpx 12rpx rgba(0, 0, 0, 0.15);
        }
        
        .coupon-condition {
            font-size: 22rpx;
            color: rgba(255, 255, 255, 0.85);
            font-weight: 500;
        }
    }
    
    // 分隔线
    .coupon-divider {
        width: 2rpx;
        height: 120rpx;
        background: linear-gradient(180deg, 
            rgba(255, 255, 255, 0) 0%, 
            rgba(255, 255, 255, 0.4) 50%, 
            rgba(255, 255, 255, 0) 100%
        );
        margin: 0 28rpx;
    }
    
    // 右侧信息区域
    .coupon-info-section {
        flex: 1;
        display: flex;
        flex-direction: column;
        justify-content: center;
        
        .coupon-name {
            font-size: 30rpx;
            font-weight: 600;
            color: #FFFFFF;
            margin-bottom: 12rpx;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        
        .coupon-time {
            font-size: 22rpx;
            color: rgba(255, 255, 255, 0.75);
            margin-bottom: 20rpx;
        }
        
        .coupon-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 160rpx;
            height: 56rpx;
            border-radius: 28rpx;
            font-size: 26rpx;
            font-weight: 600;
            transition: all 0.2s ease;
        }
        
        .coupon-btn-active {
            background: #FFFFFF;
            box-shadow: 0 4rpx 12rpx rgba(255, 255, 255, 0.3);
            
            &:active {
                transform: scale(0.95);
            }
        }
        
    }
    
    // 装饰圆点（模拟撕边效果）
    .coupon-dot {
        position: absolute;
        width: 24rpx;
        height: 24rpx;
        border-radius: 50%;
        background: #F8FAFC;
        top: 50%;
        transform: translateY(-50%);
        z-index: 2;
    }
    
    .coupon-dot-left {
        left: -12rpx;
    }
    
    .coupon-dot-right {
        right: -12rpx;
    }
}
</style>
