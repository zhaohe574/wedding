<template>
    <view class="waitlist-page">
        <!-- 筛选标签 -->
        <view class="filter-tabs">
            <view 
                class="tab-item"
                v-for="tab in statusTabs"
                :key="tab.value"
                :class="{ active: currentStatus === tab.value }"
                @click="currentStatus = tab.value; fetchList()"
            >
                {{ tab.label }}
            </view>
        </view>

        <!-- 空状态 -->
        <view class="empty-state" v-if="!loading && waitlist.length === 0">
            <text class="empty-text">暂无候补记录</text>
        </view>

        <!-- 候补列表 -->
        <view class="waitlist">
            <view 
                class="waitlist-item"
                v-for="item in waitlist"
                :key="item.id"
            >
                <view class="item-header">
                    <image :src="item.staff?.avatar" class="staff-avatar" mode="aspectFill" />
                    <view class="staff-info">
                        <text class="name">{{ item.staff?.name }}</text>
                        <text class="category">{{ item.staff?.category_name || '' }}</text>
                    </view>
                    <view class="status-tag" :class="getStatusClass(item.notify_status)">
                        {{ item.notify_status_desc }}
                    </view>
                </view>
                
                <view class="item-content">
                    <view class="info-row">
                        <text class="label">预约日期:</text>
                        <text class="value">{{ item.schedule_date }}</text>
                    </view>
                    <view class="info-row">
                        <text class="label">时间段:</text>
                        <text class="value">{{ item.time_slot_desc }}</text>
                    </view>
                    <view class="info-row" v-if="item.remark">
                        <text class="label">备注:</text>
                        <text class="value">{{ item.remark }}</text>
                    </view>
                </view>
                
                <view class="item-footer">
                    <text class="create-time">加入时间: {{ formatTime(item.create_time) }}</text>
                    <view class="actions">
                        <button 
                            class="btn-book" 
                            v-if="item.notify_status === 1"
                            @click="handleBook(item)"
                        >
                            立即预约
                        </button>
                        <button 
                            class="btn-cancel" 
                            v-if="item.notify_status === 0 || item.notify_status === 1"
                            @click="handleCancel(item)"
                        >
                            取消候补
                        </button>
                    </view>
                </view>
            </view>
        </view>
    </view>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { onShow } from '@dcloudio/uni-app'
import { getMyWaitlist, cancelWaitlist } from '@/api/schedule'

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

// 获取状态样式
const getStatusClass = (status: number) => {
    const map: Record<number, string> = {
        0: 'waiting',
        1: 'notified',
        2: 'ordered',
        3: 'expired'
    }
    return map[status] || ''
}

// 格式化时间
const formatTime = (timestamp: number) => {
    if (!timestamp) return ''
    const date = new Date(timestamp * 1000)
    return `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}-${String(date.getDate()).padStart(2, '0')}`
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
        title: '提示',
        content: '确定要取消该候补吗？',
        success: async (res) => {
            if (res.confirm) {
                try {
                    await cancelWaitlist({ id: item.id })
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
    background: #f5f5f5;
}

.filter-tabs {
    display: flex;
    background: #fff;
    padding: 20rpx 0;
    
    .tab-item {
        flex: 1;
        text-align: center;
        font-size: 28rpx;
        color: #666;
        padding: 10rpx 0;
        position: relative;
        
        &.active {
            color: #ff6b6b;
            font-weight: bold;
            
            &::after {
                content: '';
                position: absolute;
                bottom: 0;
                left: 50%;
                transform: translateX(-50%);
                width: 40rpx;
                height: 4rpx;
                background: #ff6b6b;
                border-radius: 2rpx;
            }
        }
    }
}

.empty-state {
    display: flex;
    justify-content: center;
    padding-top: 200rpx;
    
    .empty-text {
        font-size: 28rpx;
        color: #999;
    }
}

.waitlist-item {
    background: #fff;
    margin: 20rpx;
    border-radius: 16rpx;
    padding: 30rpx;
    
    .item-header {
        display: flex;
        align-items: center;
        
        .staff-avatar {
            width: 80rpx;
            height: 80rpx;
            border-radius: 50%;
            margin-right: 20rpx;
        }
        
        .staff-info {
            flex: 1;
            
            .name {
                font-size: 30rpx;
                font-weight: bold;
                display: block;
            }
            
            .category {
                font-size: 24rpx;
                color: #999;
                margin-top: 6rpx;
            }
        }
        
        .status-tag {
            font-size: 24rpx;
            padding: 8rpx 16rpx;
            border-radius: 4rpx;
            
            &.waiting {
                background: #e6f7ff;
                color: #1890ff;
            }
            
            &.notified {
                background: #fff7e6;
                color: #fa8c16;
            }
            
            &.ordered {
                background: #f6ffed;
                color: #52c41a;
            }
            
            &.expired {
                background: #f5f5f5;
                color: #999;
            }
        }
    }
    
    .item-content {
        margin-top: 20rpx;
        padding: 20rpx;
        background: #f9f9f9;
        border-radius: 8rpx;
        
        .info-row {
            display: flex;
            font-size: 26rpx;
            margin-bottom: 10rpx;
            
            &:last-child {
                margin-bottom: 0;
            }
            
            .label {
                color: #999;
                width: 140rpx;
            }
            
            .value {
                flex: 1;
            }
        }
    }
    
    .item-footer {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-top: 20rpx;
        
        .create-time {
            font-size: 24rpx;
            color: #999;
        }
        
        .actions {
            display: flex;
            gap: 20rpx;
            
            button {
                font-size: 26rpx;
                padding: 12rpx 30rpx;
                border-radius: 30rpx;
                
                &.btn-book {
                    background: #ff6b6b;
                    color: #fff;
                }
                
                &.btn-cancel {
                    background: #f5f5f5;
                    color: #666;
                }
            }
        }
    }
}
</style>
