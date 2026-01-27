<template>
    <view v-if="content.enabled && showList.length" class="coupon-receive mx-[20rpx] mt-[20rpx]">
        <!-- 标题 -->
        <view v-if="content.title" class="flex items-center justify-between mb-[24rpx]">
            <view class="flex items-center">
                <view class="w-[8rpx] h-[36rpx] rounded-full mr-[16rpx]" style="background: linear-gradient(180deg, #2563EB 0%, #3B82F6 100%);"></view>
                <text class="text-[32rpx] font-semibold" style="color: #1E293B;">{{ content.title }}</text>
            </view>
            <view
                v-if="content.show_more"
                class="flex items-center text-[26rpx] cursor-pointer"
                style="color: #64748B;"
                @click="handleMore"
            >
                <text>查看更多</text>
                <tn-icon name="right" size="24"></tn-icon>
            </view>
        </view>

        <!-- 横向滑动 -->
        <scroll-view v-if="content.style == 1" scroll-x class="coupon-scroll">
            <view class="flex gap-[24rpx]">
                <view
                    v-for="(item, index) in showList"
                    :key="index"
                    class="coupon-card-horizontal flex-shrink-0 cursor-pointer"
                    @click="handleReceive(item)"
                >
                    <!-- 玻璃态背景 -->
                    <view class="coupon-glass-bg"></view>
                    
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
                                class="coupon-btn"
                                :class="item.is_received ? 'coupon-btn-received' : 'coupon-btn-active'"
                            >
                                {{ item.is_received ? '已领取' : '立即领取' }}
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
                class="coupon-card-vertical mb-[24rpx] cursor-pointer"
                @click="handleReceive(item)"
            >
                <!-- 玻璃态背景 -->
                <view class="coupon-glass-bg"></view>
                
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
                            class="coupon-btn"
                            :class="item.is_received ? 'coupon-btn-received' : 'coupon-btn-active'"
                        >
                            {{ item.is_received ? '已领取' : '立即领取' }}
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
import { receiveCoupon } from '@/api/coupon'
import { navigateTo } from '@/utils/util'
import { useUserStore } from '@/stores/user'

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

const userStore = useUserStore()

// 过滤显示的列表
const showList = computed(() => {
    const data = props.content.data?.filter((item: any) => item.is_show !== '0') || []
    const limit = props.content.show_count || data.length
    return data.slice(0, limit)
})

// 格式化日期
const formatDate = (timestamp: number) => {
    if (!timestamp) return '长期有效'
    const date = new Date(timestamp * 1000)
    return `${date.getFullYear()}.${String(date.getMonth() + 1).padStart(2, '0')}.${String(date.getDate()).padStart(2, '0')}`
}

// 格式化有效期显示
const formatValidPeriod = (item: any) => {
    // valid_type: 1=固定日期, 2=领取后N天
    if (item.valid_type == 2 && item.valid_days) {
        return `领取后${item.valid_days}天内有效`
    }
    if (item.valid_end_time) {
        return `${formatDate(item.valid_end_time)} 到期`
    }
    return '长期有效'
}

// 领取优惠券
const handleReceive = async (item: any) => {
    if (item.is_received) {
        uni.showToast({
            title: '已领取过该优惠券',
            icon: 'none'
        })
        return
    }

    // 检查登录状态
    if (!userStore.isLogin) {
        uni.showToast({
            title: '请先登录',
            icon: 'none'
        })
        setTimeout(() => {
            uni.navigateTo({
                url: '/pages/login/login'
            })
        }, 1500)
        return
    }

    try {
        await receiveCoupon({ coupon_id: item.coupon_id })
        uni.showToast({
            title: '领取成功',
            icon: 'success'
        })
        // 更新领取状态
        item.is_received = true
    } catch (error: any) {
        uni.showToast({
            title: error.msg || '领取失败',
            icon: 'none'
        })
    }
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
        background: linear-gradient(135deg, rgba(37, 99, 235, 0.95) 0%, rgba(59, 130, 246, 0.9) 100%);
        backdrop-filter: blur(20rpx);
        border-radius: 20rpx;
        border: 2rpx solid rgba(255, 255, 255, 0.3);
        box-shadow: 0 16rpx 48rpx rgba(37, 99, 235, 0.25),
                    0 4rpx 12rpx rgba(37, 99, 235, 0.15),
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
        
        .coupon-symbol {
            font-size: 32rpx;
            font-weight: 600;
            color: rgba(255, 255, 255, 0.95);
            line-height: 1;
            margin-bottom: 8rpx;
        }
        
        .coupon-value {
            font-size: 64rpx;
            font-weight: 700;
            color: #FFFFFF;
            line-height: 1;
            margin-bottom: 12rpx;
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
            color: #2563EB;
            box-shadow: 0 4rpx 12rpx rgba(255, 255, 255, 0.3);
            
            &:active {
                transform: scale(0.95);
            }
        }
        
        .coupon-btn-received {
            background: rgba(255, 255, 255, 0.25);
            color: rgba(255, 255, 255, 0.9);
            border: 2rpx solid rgba(255, 255, 255, 0.4);
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
