<template>
    <page-meta :page-style="$theme.pageStyle">
        <navigation-bar
            title="消息中心"
            :front-color="$theme.navColor"
            :background-color="$theme.navBgColor"
        />
    </page-meta>
    <view class="notification-page">
        <!-- 消息分类入口 -->
        <view class="category-list">
            <view
                class="category-item"
                :class="{ active: currentType === 0 }"
                @click="switchType(0)"
            >
                <view class="icon-wrap all">
                    <uni-icons type="chat" size="24" color="#fff"></uni-icons>
                </view>
                <view class="category-info">
                    <view class="category-name">全部消息</view>
                    <view class="category-desc">{{ unreadCount.total || 0 }} 条未读</view>
                </view>
                <view class="badge" v-if="unreadCount.total > 0">{{ unreadCount.total }}</view>
            </view>
            <view
                class="category-item"
                :class="{ active: currentType === 1 }"
                @click="switchType(1)"
            >
                <view class="icon-wrap system">
                    <uni-icons type="info" size="24" color="#fff"></uni-icons>
                </view>
                <view class="category-info">
                    <view class="category-name">系统通知</view>
                    <view class="category-desc">公告、活动通知</view>
                </view>
                <view class="badge" v-if="unreadCount.system > 0">{{ unreadCount.system }}</view>
            </view>
            <view
                class="category-item"
                :class="{ active: currentType === 2 }"
                @click="switchType(2)"
            >
                <view class="icon-wrap order">
                    <uni-icons type="shop" size="24" color="#fff"></uni-icons>
                </view>
                <view class="category-info">
                    <view class="category-name">订单通知</view>
                    <view class="category-desc">订单状态、服务提醒</view>
                </view>
                <view class="badge" v-if="unreadCount.order > 0">{{ unreadCount.order }}</view>
            </view>
            <view
                class="category-item"
                :class="{ active: currentType === 3 }"
                @click="switchType(3)"
            >
                <view class="icon-wrap interact">
                    <uni-icons type="heart" size="24" color="#fff"></uni-icons>
                </view>
                <view class="category-info">
                    <view class="category-name">互动通知</view>
                    <view class="category-desc">点赞、评论、关注</view>
                </view>
                <view class="badge" v-if="unreadCount.interact > 0">{{
                    unreadCount.interact
                }}</view>
            </view>
        </view>

        <!-- 操作按钮 -->
        <view class="action-bar" v-if="currentType > 0">
            <view class="action-btn" @click="handleMarkAllRead">
                <uni-icons type="checkbox" size="16" color="#666"></uni-icons>
                <text>全部已读</text>
            </view>
            <view class="action-btn" @click="handleClear">
                <uni-icons type="trash" size="16" color="#666"></uni-icons>
                <text>清空消息</text>
            </view>
        </view>

        <!-- 消息列表 -->
        <view v-if="currentType > 0" class="list-wrap">
            <view v-if="notificationList.length">
                <view
                    v-for="item in notificationList"
                    :key="item.id"
                    class="notification-card"
                    :class="{ unread: !item.is_read }"
                    @click="handleItemClick(item)"
                >
                    <view class="notification-header">
                        <view class="notification-title">{{ item.title }}</view>
                        <view class="notification-time">{{ item.create_time_text }}</view>
                    </view>
                    <view class="notification-content">{{ item.content }}</view>
                    <view class="notification-footer" v-if="item.target_type">
                        <text class="link-text">查看详情</text>
                        <uni-icons type="right" size="12" color="#999"></uni-icons>
                    </view>
                </view>
            </view>
            <view v-else class="empty-tip">
                <image src="/static/images/empty.png" class="empty-icon" mode="aspectFit" />
                <text>暂无消息</text>
            </view>
        </view>

        <!-- 加载状态 -->
        <view v-if="loading" class="loading-tip">
            <uni-icons type="spinner-cycle" size="20" color="#999"></uni-icons>
            <text>加载中...</text>
        </view>

        <!-- 加载更多提示 -->
        <view
            v-if="currentType > 0 && !loading && notificationList.length > 0"
            class="load-more-tip"
        >
            <text v-if="hasMore">上拉加载更多</text>
            <text v-else>没有更多了</text>
        </view>

        <!-- 底部安全区 -->
        <view class="safe-bottom"></view>
    </view>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { onReachBottom, onPullDownRefresh } from '@dcloudio/uni-app'
import {
    getNotificationList,
    getNotificationDetail,
    getUnreadCount,
    markAllNotificationRead,
    clearNotification
} from '@/api/notification'

const loading = ref(false)
const currentType = ref(0)
const notificationList = ref<any[]>([])
const unreadCount = ref<any>({
    total: 0,
    system: 0,
    order: 0,
    interact: 0,
    activity: 0
})
const page = ref(1)
const hasMore = ref(true)

const switchType = (type: number) => {
    currentType.value = type
    if (type > 0) {
        loadList(true)
    }
}

const loadUnreadCount = async () => {
    try {
        const res = await getUnreadCount()
        unreadCount.value = res || {}
    } catch (error) {
        console.error(error)
    }
}

const loadList = async (refresh = false) => {
    if (loading.value || (!refresh && !hasMore.value)) return

    if (refresh) {
        page.value = 1
        hasMore.value = true
    }

    loading.value = true
    try {
        const res = await getNotificationList({
            page: page.value,
            limit: 10,
            notify_type: currentType.value
        })

        const list = res.lists || []
        if (refresh) {
            notificationList.value = list
        } else {
            notificationList.value = [...notificationList.value, ...list]
        }

        hasMore.value = res.has_more
        if (hasMore.value) {
            page.value++
        }
    } catch (error) {
        console.error(error)
    } finally {
        loading.value = false
        uni.stopPullDownRefresh()
    }
}

const handleItemClick = async (item: any) => {
    // 标记已读
    if (!item.is_read) {
        item.is_read = 1
        item.is_read_text = '已读'
        // 更新未读数
        loadUnreadCount()
    }

    // 跳转到目标页面
    if (item.target_type && item.target_id) {
        switch (item.target_type) {
            case 'order':
                uni.navigateTo({
                    url: `/pages/order/detail?id=${item.target_id}`
                })
                break
            case 'dynamic':
                uni.navigateTo({
                    url: `/pages/dynamic/detail?id=${item.target_id}`
                })
                break
            case 'review':
                uni.navigateTo({
                    url: `/pages/review/detail?id=${item.target_id}`
                })
                break
            default:
                // 打开详情弹窗
                try {
                    await getNotificationDetail({ id: item.id })
                } catch (error) {
                    console.error(error)
                }
        }
    }
}

const handleMarkAllRead = async () => {
    uni.showModal({
        title: '提示',
        content: '确定将所有消息标记为已读吗？',
        success: async (res) => {
            if (res.confirm) {
                try {
                    await markAllNotificationRead({ notify_type: currentType.value })
                    uni.showToast({
                        title: '标记成功',
                        icon: 'success'
                    })
                    // 刷新列表和未读数
                    loadList(true)
                    loadUnreadCount()
                } catch (error) {
                    console.error(error)
                }
            }
        }
    })
}

const handleClear = async () => {
    uni.showModal({
        title: '提示',
        content: '确定清空所有消息吗？此操作不可恢复',
        success: async (res) => {
            if (res.confirm) {
                try {
                    await clearNotification({ notify_type: currentType.value })
                    uni.showToast({
                        title: '清空成功',
                        icon: 'success'
                    })
                    // 刷新列表和未读数
                    loadList(true)
                    loadUnreadCount()
                } catch (error) {
                    console.error(error)
                }
            }
        }
    })
}

onReachBottom(() => {
    if (currentType.value > 0) {
        loadList()
    }
})

onPullDownRefresh(() => {
    loadUnreadCount()
    if (currentType.value > 0) {
        loadList(true)
    } else {
        uni.stopPullDownRefresh()
    }
})

onMounted(() => {
    loadUnreadCount()
})
</script>

<style scoped lang="scss">
.notification-page {
    min-height: 100vh;
    background: #f5f5f5;
}

.category-list {
    padding: 24rpx;
}

.category-item {
    display: flex;
    align-items: center;
    background: #fff;
    padding: 28rpx 24rpx;
    border-radius: 16rpx;
    margin-bottom: 20rpx;
    position: relative;

    &.active {
        border: 2rpx solid #ff6b35;
    }

    .icon-wrap {
        width: 80rpx;
        height: 80rpx;
        border-radius: 40rpx;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-right: 24rpx;

        &.all {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        &.system {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
        }
        &.order {
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
        }
        &.interact {
            background: linear-gradient(135deg, #ff6b35 0%, #ff9a5a 100%);
        }
    }

    .category-info {
        flex: 1;

        .category-name {
            font-size: 30rpx;
            font-weight: bold;
            color: #333;
            margin-bottom: 6rpx;
        }

        .category-desc {
            font-size: 24rpx;
            color: #999;
        }
    }

    .badge {
        position: absolute;
        right: 24rpx;
        top: 50%;
        transform: translateY(-50%);
        min-width: 36rpx;
        height: 36rpx;
        line-height: 36rpx;
        text-align: center;
        font-size: 22rpx;
        color: #fff;
        background: #ff4d4f;
        border-radius: 18rpx;
        padding: 0 10rpx;
    }
}

.action-bar {
    display: flex;
    justify-content: flex-end;
    padding: 0 24rpx 20rpx;

    .action-btn {
        display: flex;
        align-items: center;
        padding: 12rpx 24rpx;
        margin-left: 20rpx;
        background: #fff;
        border-radius: 30rpx;

        text {
            font-size: 24rpx;
            color: #666;
            margin-left: 8rpx;
        }
    }
}

.list-wrap {
    padding: 0 24rpx;
}

.notification-card {
    background: #fff;
    border-radius: 16rpx;
    padding: 24rpx;
    margin-bottom: 20rpx;

    &.unread {
        position: relative;

        &::before {
            content: '';
            position: absolute;
            left: 12rpx;
            top: 50%;
            transform: translateY(-50%);
            width: 12rpx;
            height: 12rpx;
            background: #ff4d4f;
            border-radius: 50%;
        }
    }

    .notification-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 16rpx;

        .notification-title {
            font-size: 30rpx;
            font-weight: bold;
            color: #333;
            flex: 1;
            margin-right: 20rpx;
        }

        .notification-time {
            font-size: 24rpx;
            color: #999;
        }
    }

    .notification-content {
        font-size: 28rpx;
        color: #666;
        line-height: 1.6;
    }

    .notification-footer {
        display: flex;
        align-items: center;
        justify-content: flex-end;
        margin-top: 16rpx;
        padding-top: 16rpx;
        border-top: 1rpx solid #f0f0f0;

        .link-text {
            font-size: 24rpx;
            color: #ff6b35;
            margin-right: 4rpx;
        }
    }
}

.empty-tip {
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: 80rpx 0;

    .empty-icon {
        width: 200rpx;
        height: 200rpx;
        margin-bottom: 24rpx;
    }

    text {
        font-size: 28rpx;
        color: #999;
    }
}

.loading-tip {
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 32rpx;
    color: #999;
    font-size: 26rpx;

    text {
        margin-left: 12rpx;
    }
}

.load-more-tip {
    text-align: center;
    padding: 24rpx;
    font-size: 24rpx;
    color: #999;
}

.safe-bottom {
    height: constant(safe-area-inset-bottom);
    height: env(safe-area-inset-bottom);
}
</style>
