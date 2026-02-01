<template>
    <page-meta :page-style="$theme.pageStyle">
        <!-- #ifndef H5 -->
        <navigation-bar 
            title="我的候补"
            :front-color="$theme.navColor" 
            :background-color="$theme.navBgColor" 
        />
        <!-- #endif -->
    </page-meta>
    
    <view class="waitlist-page">
        <!-- 筛选标签 -->
        <view class="filter-tabs">
            <view
                class="tab-item"
                v-for="tab in statusTabs"
                :key="tab.value"
                :class="{ active: currentStatus === tab.value }"
                @click="() => { currentStatus = tab.value; fetchList() }"
            >
                <text 
                    class="tab-label"
                    :style="currentStatus === tab.value ? { color: $theme.primaryColor } : {}"
                >
                    {{ tab.label }}
                </text>
                <view 
                    v-if="currentStatus === tab.value"
                    class="tab-indicator"
                    :style="{ background: $theme.primaryColor }"
                />
            </view>
        </view>

        <!-- 空状态 -->
        <view class="empty-state" v-if="!loading && waitlist.length === 0">
            <tn-icon name="inbox" size="120" color="#CCCCCC" />
            <text class="empty-text">暂无候补记录</text>
            <text class="empty-hint">档期满时可加入候补，有空位时会通知您</text>
        </view>

        <!-- 候补列表 -->
        <view class="waitlist-list">
            <view 
                class="waitlist-card" 
                v-for="item in waitlist" 
                :key="item.id"
            >
                <!-- 状态标签（顶部） -->
                <view 
                    class="status-ribbon"
                    :class="getStatusClass(item.notify_status)"
                >
                    <tn-icon 
                        :name="getStatusIcon(item.notify_status)" 
                        size="28" 
                        :color="getStatusColor(item.notify_status)"
                    />
                    <text>{{ item.notify_status_desc }}</text>
                </view>

                <!-- 卡片头部：人员信息 -->
                <view class="card-header">
                    <view class="staff-section">
                        <view class="avatar-wrapper">
                            <image 
                                :src="item.staff?.avatar" 
                                class="staff-avatar" 
                                mode="aspectFill" 
                            />
                            <view 
                                class="avatar-border"
                                :style="{ borderColor: $theme.primaryColor }"
                            />
                        </view>
                        <view class="staff-info">
                            <text class="staff-name">{{ item.staff?.name || '未知人员' }}</text>
                            <view 
                                class="staff-tag"
                                :style="{ 
                                    background: getColorWithOpacity($theme.primaryColor, 0.1),
                                    borderColor: getColorWithOpacity($theme.primaryColor, 0.2)
                                }"
                            >
                                <tn-icon 
                                    name="shield-check" 
                                    size="24" 
                                    :color="$theme.primaryColor" 
                                />
                                <text 
                                    class="staff-category"
                                    :style="{ color: $theme.primaryColor }"
                                >
                                    {{ item.staff?.category_name || '服务人员' }}
                                </text>
                            </view>
                        </view>
                    </view>
                </view>

                <!-- 预约信息区域 -->
                <view class="info-section">
                    <view class="info-item">
                        <view 
                            class="icon-wrapper"
                            :style="{ background: getColorWithOpacity($theme.primaryColor, 0.1) }"
                        >
                            <tn-icon 
                                name="calendar" 
                                size="36" 
                                :color="$theme.primaryColor" 
                            />
                        </view>
                        <view class="info-content">
                            <text class="info-label">预约日期</text>
                            <text class="info-value">{{ item.schedule_date }}</text>
                        </view>
                    </view>
                    
                    <view class="info-item" v-if="item.package || item.package_id">
                        <view 
                            class="icon-wrapper"
                            :style="{ background: getColorWithOpacity($theme.secondaryColor, 0.1) }"
                        >
                            <tn-icon 
                                name="gift" 
                                size="36" 
                                :color="$theme.secondaryColor" 
                            />
                        </view>
                        <view class="info-content">
                            <text class="info-label">套餐</text>
                            <text class="info-value">{{ item.package?.name || '套餐已删除' }}</text>
                        </view>
                    </view>
                    
                    <view class="info-item">
                        <view 
                            class="icon-wrapper"
                            :style="{ background: getColorWithOpacity($theme.accentColor, 0.1) }"
                        >
                            <tn-icon 
                                name="time" 
                                size="36" 
                                :color="$theme.accentColor" 
                            />
                        </view>
                        <view class="info-content">
                            <text class="info-label">时间段</text>
                            <text class="info-value">{{ getTimeSlotLabel(item) }}</text>
                        </view>
                    </view>
                </view>

                <!-- 底部：时间 + 操作按钮 -->
                <view class="card-footer">
                    <view class="time-info">
                        <tn-icon name="clock" size="28" color="#999999" />
                        <text class="time-text">{{ formatTime(item.create_time) }}</text>
                    </view>
                    <view class="action-buttons">
                        <view
                            v-if="item.notify_status === 1"
                            class="btn btn-book"
                            :style="{ 
                                background: `linear-gradient(135deg, ${$theme.ctaColor} 0%, ${$theme.ctaColor} 100%)`,
                                color: $theme.btnColor
                            }"
                            @click.stop="handleBook(item)"
                        >
                            <tn-icon name="check-circle" size="28" :color="$theme.btnColor" />
                            <text>立即预约</text>
                        </view>
                        <view
                            v-if="item.notify_status === 0 || item.notify_status === 1"
                            class="btn btn-cancel"
                            @click.stop="handleCancel(item)"
                        >
                            <tn-icon name="close-circle" size="28" color="#666666" />
                            <text>取消候补</text>
                        </view>
                    </view>
                </view>
            </view>
        </view>
    </view>
</template>

<script setup lang="ts">
import { ref ,computed } from 'vue'
import { onShow } from '@dcloudio/uni-app'
import { getMyWaitlist, cancelWaitlist } from '@/api/schedule'
import { useThemeStore } from '@/stores/theme'

const $theme = useThemeStore()

const statusTabs = [
    { value: -1, label: '全部' },
    { value: 0, label: '等待中' },
    { value: 1, label: '已通知' },
    { value: 2, label: '已下单' },
    { value: 3, label: '已过期' }
]

const loading = ref(false)
const currentStatus = ref(-1)
const waitlist = ref<any[]>([])

// 获取列表
const fetchList = async () => {
    loading.value = true
    try {
        const params: any = {}
        if (currentStatus.value >= 0) {
            params.status = currentStatus.value
        }
        const res = await getMyWaitlist(params)
        waitlist.value = res || []
    } finally {
        loading.value = false
    }
}

// 获取状态样式类
const getStatusClass = (status: number) => {
    const map: Record<number, string> = {
        0: 'status-waiting',
        1: 'status-notified',
        2: 'status-ordered',
        3: 'status-expired'
    }
    return map[status] || ''
}

// 获取状态图标
const getStatusIcon = (status: number) => {
    const map: Record<number, string> = {
        0: 'clock',
        1: 'notification-fill',
        2: 'check-circle-fill',
        3: 'close-circle-fill'
    }
    return map[status] || 'clock'
}

// 获取状态颜色
const getStatusColor = (status: number) => {
    const map: Record<number, string> = {
        0: '#1890FF',
        1: '#FA8C16',
        2: '#52C41A',
        3: '#999999'
    }
    return map[status] || '#999999'
}

// 获取带透明度的颜色
const getColorWithOpacity = (color: string, opacity: number) => {
    // 如果是十六进制颜色
    if (color.startsWith('#')) {
        const hex = color.replace('#', '')
        const r = parseInt(hex.substring(0, 2), 16)
        const g = parseInt(hex.substring(2, 4), 16)
        const b = parseInt(hex.substring(4, 6), 16)
        return `rgba(${r}, ${g}, ${b}, ${opacity})`
    }
    // 如果已经是 rgba 格式，直接返回
    return color
}

// 格式化时间
const formatTime = (timestamp: any) => {
    if (!timestamp) return '未知时间'
    
    let date: Date
    
    // 处理不同的时间格式
    if (typeof timestamp === 'string') {
        // 如果是字符串，直接解析
        date = new Date(timestamp)
    } else if (typeof timestamp === 'number') {
        // 如果是数字，判断是秒还是毫秒
        // 如果小于 10000000000，认为是秒级时间戳
        if (timestamp < 10000000000) {
            date = new Date(timestamp * 1000)
        } else {
            date = new Date(timestamp)
        }
    } else {
        return '未知时间'
    }
    
    // 检查日期是否有效
    if (isNaN(date.getTime())) {
        return '未知时间'
    }
    
    const year = date.getFullYear()
    const month = String(date.getMonth() + 1).padStart(2, '0')
    const day = String(date.getDate()).padStart(2, '0')
    const hour = String(date.getHours()).padStart(2, '0')
    const minute = String(date.getMinutes()).padStart(2, '0')
    
    return `${year}-${month}-${day} ${hour}:${minute}`
}

// 获取时间段文案
const getTimeSlotLabel = (item: any) => {
    if (item?.time_slot_desc) {
        return item.time_slot_desc
    }
    const map: Record<number, string> = {
        0: '全天',
        1: '早礼',
        2: '午宴',
        3: '晚宴'
    }
    const slot = Number(item?.time_slot)
    return Number.isFinite(slot) ? (map[slot] || '未知场次') : '未知场次'
}

// 点击卡片
const handleItemClick = (item: any) => {
    // 可以跳转到详情页或其他操作
}

// 立即预约
const handleBook = (item: any) => {
    const params = [
        `staff_id=${item.staff_id}`,
        `date=${item.schedule_date}`
    ]
    if (item.package_id) {
        params.push(`package_id=${item.package_id}`)
    }
    if (item.time_slot !== null && item.time_slot !== undefined) {
        params.push(`time_slot=${item.time_slot}`)
    }
    uni.navigateTo({
        url: `/packages/pages/schedule_calendar/schedule_calendar?${params.join('&')}`
    })
}

// 取消候补
const handleCancel = (item: any) => {
    uni.showModal({
        title: '取消候补',
        content: '确定要取消该候补吗？取消后需要重新加入候补队列。',
        confirmColor: '#FF2C3C',
        success: async (res) => {
            if (res.confirm) {
                try {
                    // 确保 id 是数字类型
                    await cancelWaitlist({ id: Number(item.id) })
                    uni.showToast({ title: '取消成功', icon: 'success' })
                    fetchList()
                } catch (e: any) {
                    uni.showToast({ title: e.message || '操作失败', icon: 'none' })
                }
            }
        }
    })
}

onShow(() => {
    fetchList()
})
</script>

<style lang="scss" scoped>
.waitlist-page {
    min-height: 100vh;
    background: linear-gradient(180deg, #F9FAFB 0%, #F5F5F5 100%);
    padding-bottom: 24rpx;
}

/* 筛选标签 */
.filter-tabs {
    display: flex;
    background: #FFFFFF;
    padding: 0;
    position: sticky;
    top: 0;
    z-index: 10;
    box-shadow: 0 2rpx 12rpx rgba(0, 0, 0, 0.06);

    .tab-item {
        flex: 1;
        text-align: center;
        padding: 28rpx 0;
        position: relative;
        transition: all 0.2s ease;

        .tab-label {
            font-size: 28rpx;
            color: #666666;
            font-weight: 400;
            transition: all 0.2s ease;
        }

        .tab-indicator {
            position: absolute;
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 48rpx;
            height: 6rpx;
            border-radius: 3rpx;
            transition: all 0.3s ease;
        }

        &.active {
            .tab-label {
                font-weight: 700;
            }
        }
    }
}

/* 空状态 */
.empty-state {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding-top: 200rpx;

    .empty-text {
        font-size: 30rpx;
        color: #333333;
        font-weight: 600;
        margin-top: 32rpx;
    }

    .empty-hint {
        font-size: 26rpx;
        color: #999999;
        margin-top: 16rpx;
        text-align: center;
        padding: 0 48rpx;
        line-height: 1.6;
    }
}

/* 候补列表 */
.waitlist-list {
    padding: 24rpx;
}

/* 候补卡片 */
.waitlist-card {
    position: relative;
    background: #FFFFFF;
    border-radius: 24rpx;
    padding: 0;
    margin-bottom: 24rpx;
    box-shadow: 0 4rpx 20rpx rgba(0, 0, 0, 0.08);
    overflow: hidden;
    transition: all 0.3s ease;

    &:active {
        transform: translateY(-2rpx);
        box-shadow: 0 8rpx 32rpx rgba(0, 0, 0, 0.12);
    }

    /* 状态标签（顶部彩带） */
    .status-ribbon {
        display: flex;
        align-items: center;
        gap: 8rpx;
        padding: 16rpx 32rpx;
        font-size: 24rpx;
        font-weight: 600;
        
        &.status-waiting {
            background: linear-gradient(135deg, #E6F7FF 0%, #BAE7FF 100%);
            color: #1890FF;
        }

        &.status-notified {
            background: linear-gradient(135deg, #FFF7E6 0%, #FFE7BA 100%);
            color: #FA8C16;
        }

        &.status-ordered {
            background: linear-gradient(135deg, #F6FFED 0%, #D9F7BE 100%);
            color: #52C41A;
        }

        &.status-expired {
            background: linear-gradient(135deg, #F5F5F5 0%, #E5E5E5 100%);
            color: #999999;
        }
    }

    /* 卡片头部 */
    .card-header {
        padding: 32rpx 32rpx 24rpx;

        .staff-section {
            display: flex;
            align-items: center;

            .avatar-wrapper {
                position: relative;
                margin-right: 24rpx;

                .staff-avatar {
                    width: 96rpx;
                    height: 96rpx;
                    border-radius: 50%;
                    display: block;
                }

                .avatar-border {
                    position: absolute;
                    top: -4rpx;
                    left: -4rpx;
                    right: -4rpx;
                    bottom: -4rpx;
                    border: 3rpx solid;
                    border-radius: 50%;
                    opacity: 0.3;
                }
            }

            .staff-info {
                flex: 1;

                .staff-name {
                    font-size: 34rpx;
                    font-weight: 700;
                    color: #333333;
                    display: block;
                    margin-bottom: 12rpx;
                    line-height: 1.3;
                }

                .staff-tag {
                    display: inline-flex;
                    align-items: center;
                    gap: 8rpx;
                    padding: 8rpx 16rpx;
                    border: 2rpx solid;
                    border-radius: 24rpx;

                    .staff-category {
                        font-size: 24rpx;
                        font-weight: 500;
                    }
                }
            }
        }
    }

    /* 预约信息区域 */
    .info-section {
        padding: 0 32rpx 24rpx;

        .info-item {
            display: flex;
            align-items: center;
            gap: 20rpx;
            padding: 20rpx 0;
            border-bottom: 1rpx solid #F0F0F0;

            &:last-child {
                border-bottom: none;
            }

            .icon-wrapper {
                width: 72rpx;
                height: 72rpx;
                border-radius: 16rpx;
                display: flex;
                align-items: center;
                justify-content: center;
                flex-shrink: 0;
            }

            .info-content {
                flex: 1;
                display: flex;
                flex-direction: column;
                gap: 8rpx;

                .info-label {
                    font-size: 24rpx;
                    color: #999999;
                    line-height: 1.4;
                }

                .info-value {
                    font-size: 30rpx;
                    color: #333333;
                    font-weight: 600;
                    line-height: 1.4;
                }
            }
        }
    }

    /* 底部操作 */
    .card-footer {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 24rpx 32rpx 32rpx;
        border-top: 1rpx solid #F0F0F0;

        .time-info {
            display: flex;
            align-items: center;
            gap: 8rpx;

            .time-text {
                font-size: 24rpx;
                color: #999999;
            }
        }

        .action-buttons {
            display: flex;
            gap: 16rpx;

            .btn {
                display: flex;
                align-items: center;
                gap: 8rpx;
                padding: 18rpx 32rpx;
                border-radius: 56rpx;
                font-size: 26rpx;
                font-weight: 600;
                transition: all 0.2s ease;
                white-space: nowrap;

                &.btn-book {
                    box-shadow: 0 8rpx 24rpx rgba(249, 115, 22, 0.3);

                    &:active {
                        transform: scale(0.96);
                        box-shadow: 0 4rpx 12rpx rgba(249, 115, 22, 0.3);
                    }
                }

                &.btn-cancel {
                    background: #FFFFFF;
                    border: 2rpx solid #E5E5E5;
                    color: #666666;

                    &:active {
                        background: #F5F5F5;
                        border-color: #D9D9D9;
                    }
                }
            }
        }
    }
}
</style>
