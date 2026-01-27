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
                <text class="tab-label">{{ tab.label }}</text>
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
                <!-- 卡片头部：人员信息 + 状态 -->
                <view class="card-header">
                    <view class="staff-section">
                        <image :src="item.staff?.avatar" class="staff-avatar" mode="aspectFill" />
                        <view class="staff-info">
                            <text class="staff-name">{{ item.staff?.name || '未知人员' }}</text>
                            <view class="staff-tag">
                                <tn-icon name="shield-check" size="24" color="#999999" />
                                <text class="staff-category">{{ item.staff?.category_name || '服务人员' }}</text>
                            </view>
                        </view>
                    </view>
                    <view class="status-badge" :class="getStatusClass(item.notify_status)">
                        <text>{{ item.notify_status_desc }}</text>
                    </view>
                </view>

                <!-- 预约信息区域 -->
                <view class="info-section">
                    <view class="info-item">
                        <tn-icon name="calendar" size="36" color="#52C41A" />
                        <view class="info-content">
                            <text class="info-label">预约日期</text>
                            <text class="info-value">{{ item.schedule_date }}</text>
                        </view>
                    </view>
                    <view class="info-item">
                        <tn-icon name="clock" size="36" color="#52C41A" />
                        <view class="info-content">
                            <text class="info-label">时间段</text>
                            <text class="info-value">{{ item.time_slot_desc || '全天' }}</text>
                        </view>
                    </view>
                </view>

                <!-- 底部：时间 + 操作按钮 -->
                <view class="card-footer">
                    <view class="time-info">
                        <tn-icon name="time" size="24" color="#CCCCCC" />
                        <text class="time-text">{{ formatTime(item.create_time) }}</text>
                    </view>
                    <view class="action-buttons">
                        <view
                            class="btn btn-book"
                            v-if="item.notify_status === 1"
                            @click.stop="handleBook(item)"
                        >
                            立即预约
                        </view>
                        <view
                            class="btn btn-cancel"
                            v-if="item.notify_status === 0 || item.notify_status === 1"
                            @click.stop="handleCancel(item)"
                        >
                            取消候补
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
        1: 'notification',
        2: 'check-circle',
        3: 'close-circle'
    }
    return map[status] || 'clock'
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

// 点击卡片
const handleItemClick = (item: any) => {
    // 可以跳转到详情页或其他操作
}

// 立即预约
const handleBook = (item: any) => {
    uni.navigateTo({
        url: `/packages/pages/schedule_calendar/schedule_calendar?staff_id=${item.staff_id}&date=${item.schedule_date}`
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
    background: #F5F5F5;
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
    box-shadow: 0 2rpx 8rpx rgba(0, 0, 0, 0.04);

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
        }

        &.active {
            .tab-label {
                color: var(--color-primary);
                font-weight: 600;
            }

            &::after {
                content: '';
                position: absolute;
                bottom: 0;
                left: 50%;
                transform: translateX(-50%);
                width: 48rpx;
                height: 6rpx;
                background: var(--color-primary);
                border-radius: 3rpx;
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
        font-weight: 500;
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
    background: #FFFFFF;
    border-radius: 24rpx;
    padding: 32rpx;
    margin-bottom: 24rpx;
    box-shadow: 0 4rpx 16rpx rgba(0, 0, 0, 0.06);

    /* 卡片头部 */
    .card-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        margin-bottom: 28rpx;

        .staff-section {
            display: flex;
            align-items: center;
            flex: 1;

            .staff-avatar {
                width: 96rpx;
                height: 96rpx;
                border-radius: 50%;
                margin-right: 24rpx;
                border: 3rpx solid #F5F5F5;
            }

            .staff-info {
                flex: 1;

                .staff-name {
                    font-size: 32rpx;
                    font-weight: 600;
                    color: #333333;
                    display: block;
                    margin-bottom: 12rpx;
                }

                .staff-tag {
                    display: inline-flex;
                    align-items: center;
                    gap: 6rpx;
                    padding: 6rpx 12rpx;
                    background: #F5F5F5;
                    border-radius: 8rpx;

                    .staff-category {
                        font-size: 24rpx;
                        color: #666666;
                    }
                }
            }
        }

        .status-badge {
            padding: 8rpx 20rpx;
            border-radius: 20rpx;
            font-size: 24rpx;
            font-weight: 500;
            white-space: nowrap;

            &.status-waiting {
                background: #E6F7FF;
                color: #1890FF;
            }

            &.status-notified {
                background: #FFF7E6;
                color: #FA8C16;
            }

            &.status-ordered {
                background: #F6FFED;
                color: #52C41A;
            }

            &.status-expired {
                background: #F5F5F5;
                color: #999999;
            }
        }
    }

    /* 预约信息区域 */
    .info-section {
        background: #F9FAFB;
        border-radius: 16rpx;
        padding: 24rpx;
        margin-bottom: 24rpx;

        .info-item {
            display: flex;
            align-items: center;
            gap: 20rpx;
            margin-bottom: 24rpx;

            &:last-child {
                margin-bottom: 0;
            }

            .info-content {
                flex: 1;
                display: flex;
                flex-direction: column;
                gap: 6rpx;

                .info-label {
                    font-size: 24rpx;
                    color: #999999;
                }

                .info-value {
                    font-size: 30rpx;
                    color: #333333;
                    font-weight: 500;
                }
            }
        }
    }

    /* 底部操作 */
    .card-footer {
        display: flex;
        justify-content: space-between;
        align-items: center;

        .time-info {
            display: flex;
            align-items: center;
            gap: 8rpx;

            .time-text {
                font-size: 24rpx;
                color: #CCCCCC;
            }
        }

        .action-buttons {
            display: flex;
            gap: 16rpx;

            .btn {
                padding: 16rpx 32rpx;
                border-radius: 48rpx;
                font-size: 26rpx;
                font-weight: 500;
                transition: all 0.2s ease;

                &.btn-book {
                    background: linear-gradient(135deg, var(--color-cta) 0%, #FF8C42 100%);
                    color: #FFFFFF;
                    box-shadow: 0 6rpx 20rpx rgba(249, 115, 22, 0.25);

                    &:active {
                        transform: scale(0.96);
                        box-shadow: 0 3rpx 10rpx rgba(249, 115, 22, 0.25);
                    }
                }

                &.btn-cancel {
                    background: #FFFFFF;
                    border: 2rpx solid #E5E5E5;
                    color: #666666;

                    &:active {
                        background: #F5F5F5;
                    }
                }
            }
        }
    }
}
</style>
