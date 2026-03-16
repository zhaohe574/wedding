<template>
    <page-meta :page-style="$theme.pageStyle">
        <navigation-bar
            title="消息中心"
            :front-color="$theme.navColor"
            :background-color="$theme.navBgColor"
        />
    </page-meta>
    <view class="notification-page">
        <!-- 顶部统计区域 -->
        <view class="stats-header">
            <view
                class="stats-card"
                :style="{
                    background: `linear-gradient(135deg, ${$theme.primaryColor} 0%, ${$theme.secondaryColor} 100%)`
                }"
            >
                <view class="stats-content">
                    <view class="stats-number" :style="{ color: $theme.btnColor }">
                        {{ unreadCount.total || 0 }}
                    </view>
                    <view class="stats-label" :style="{ color: $theme.btnColor }">
                        条未读消息
                    </view>
                </view>
                <view
                    v-if="unreadCount.total > 0"
                    class="stats-action"
                    :style="{
                        background: 'rgba(255, 255, 255, 0.2)',
                        color: $theme.btnColor
                    }"
                    @click="handleMarkAllRead(0)"
                >
                    <tn-icon name="check-circle" :size="28" :color="$theme.btnColor" />
                    <text :style="{ color: $theme.btnColor }">全部已读</text>
                </view>
            </view>
        </view>

        <!-- 消息分类入口 -->
        <view class="section-card">
            <view class="section-title">消息分类</view>
            <view class="category-grid">
                <view
                    v-for="item in categoryList"
                    :key="item.type"
                    class="category-item"
                    hover-class="category-item-hover"
                    @click="switchType(item.type)"
                >
                    <view class="category-icon-wrap" :style="{ background: getIconBg(item.color) }">
                        <tn-icon :name="item.icon" :size="40" color="#FFFFFF" />
                        <view
                            v-if="getUnreadByType(item.type) > 0"
                            class="category-badge"
                        >
                            {{ getUnreadByType(item.type) > 99 ? '99+' : getUnreadByType(item.type) }}
                        </view>
                    </view>
                    <view class="category-name">{{ item.name }}</view>
                    <view class="category-desc">{{ item.desc }}</view>
                </view>
            </view>
        </view>

        <!-- 最近消息列表 -->
        <view class="section-card" v-if="currentType === 0">
            <view class="section-header">
                <view class="section-title">最近消息</view>
                <view
                    v-if="notificationList.length > 0"
                    class="section-more"
                    :style="{ color: $theme.primaryColor }"
                    @click="switchType(1)"
                >
                    <text>查看全部</text>
                    <tn-icon name="right" :size="24" :color="$theme.primaryColor" />
                </view>
            </view>

            <view v-if="notificationList.length > 0" class="message-list">
                <view
                    v-for="item in recentList"
                    :key="item.id"
                    class="message-item"
                    hover-class="message-item-hover"
                    @click="handleItemClick(item)"
                >
                    <view class="message-dot" v-if="!item.is_read">
                        <view
                            class="dot"
                            :style="{ background: $theme.ctaColor }"
                        ></view>
                    </view>
                    <view
                        class="message-icon"
                        :style="{ background: getTypeBg(item.notify_type) }"
                    >
                        <tn-icon
                            :name="getTypeIcon(item.notify_type)"
                            :size="32"
                            color="#FFFFFF"
                        />
                    </view>
                    <view class="message-body">
                        <view class="message-top">
                            <text class="message-title">{{ item.title }}</text>
                            <text class="message-time">{{ item.create_time_text }}</text>
                        </view>
                        <text class="message-content">{{ item.content }}</text>
                    </view>
                    <view class="message-arrow" v-if="item.target_type">
                        <tn-icon name="right" :size="28" color="#C8C9CC" />
                    </view>
                </view>
            </view>

            <!-- 空状态 -->
            <view v-else class="empty-state">
                <tn-icon name="email" :size="120" color="#E5E5E5" />
                <text class="empty-text">暂无消息</text>
                <text class="empty-hint">新消息会在这里显示</text>
            </view>
        </view>

        <!-- 分类消息列表 -->
        <view class="section-card" v-if="currentType > 0">
            <view class="section-header">
                <view class="section-title-row">
                    <view
                        class="back-type"
                        @click="switchType(0)"
                    >
                        <tn-icon name="left" :size="32" color="#666666" />
                    </view>
                    <view class="section-title">{{ currentTypeName }}</view>
                </view>
                <view class="action-group">
                    <view
                        class="action-btn"
                        hover-class="action-btn-hover"
                        @click="handleMarkAllRead(currentType)"
                    >
                        <tn-icon name="check-circle" :size="28" color="#666666" />
                        <text>已读</text>
                    </view>
                    <view
                        class="action-btn action-btn-danger"
                        hover-class="action-btn-hover"
                        @click="handleClear"
                    >
                        <tn-icon name="delete" :size="28" color="#FF2C3C" />
                        <text style="color: #FF2C3C">清空</text>
                    </view>
                </view>
            </view>

            <view v-if="notificationList.length > 0" class="message-list">
                <view
                    v-for="item in notificationList"
                    :key="item.id"
                    class="message-item"
                    hover-class="message-item-hover"
                    @click="handleItemClick(item)"
                >
                    <view class="message-dot" v-if="!item.is_read">
                        <view
                            class="dot"
                            :style="{ background: $theme.ctaColor }"
                        ></view>
                    </view>
                    <view
                        class="message-icon"
                        :style="{ background: getTypeBg(item.notify_type) }"
                    >
                        <tn-icon
                            :name="getTypeIcon(item.notify_type)"
                            :size="32"
                            color="#FFFFFF"
                        />
                    </view>
                    <view class="message-body">
                        <view class="message-top">
                            <text class="message-title">{{ item.title }}</text>
                            <text class="message-time">{{ item.create_time_text }}</text>
                        </view>
                        <text class="message-content">{{ item.content }}</text>
                        <view
                            v-if="item.target_type"
                            class="message-link"
                            :style="{ color: $theme.primaryColor }"
                        >
                            <text>查看详情</text>
                            <tn-icon name="right" :size="22" :color="$theme.primaryColor" />
                        </view>
                    </view>
                </view>
            </view>

            <!-- 空状态 -->
            <view v-else-if="!loading" class="empty-state">
                <tn-icon name="email" :size="120" color="#E5E5E5" />
                <text class="empty-text">暂无{{ currentTypeName }}</text>
            </view>
        </view>

        <!-- 加载状态 -->
        <view v-if="loading" class="loading-tip">
            <tn-icon name="loading" :size="32" color="#999999" />
            <text>加载中...</text>
        </view>

        <!-- 加载更多 -->
        <view
            v-if="currentType > 0 && !loading && notificationList.length > 0"
            class="load-more-tip"
        >
            <text v-if="hasMore">上拉加载更多</text>
            <text v-else>— 没有更多了 —</text>
        </view>

        <!-- 底部安全区 -->
        <view class="safe-bottom"></view>
    </view>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { onReachBottom, onPullDownRefresh } from '@dcloudio/uni-app'
import { useThemeStore } from '@/stores/theme'
import {
    getNotificationList,
    getNotificationDetail,
    getUnreadCount,
    markNotificationRead,
    markAllNotificationRead,
    clearNotification
} from '@/api/notification'

const $theme = useThemeStore()

const loading = ref(false)
const currentType = ref(0)
const notificationList = ref<any[]>([])
const unreadCount = ref<any>({
    total: 0,
    system: 0,
    order: 0,
    interact: 0
})
const page = ref(1)
const hasMore = ref(true)

// 消息分类配置
const categoryList = [
    { type: 1, name: '系统通知', desc: '公告、活动通知', icon: 'notice', color: 'primary' },
    { type: 2, name: '订单通知', desc: '订单状态、服务提醒', icon: 'shop', color: 'cta' },
    { type: 3, name: '互动通知', desc: '点赞、评论等互动', icon: 'my-love', color: 'secondary' }
]

// 最近消息（首页展示最多5条）
const recentList = computed(() => notificationList.value.slice(0, 5))

// 当前分类名称
const currentTypeName = computed(() => {
    const item = categoryList.find((c) => c.type === currentType.value)
    return item?.name || '全部消息'
})

// 获取图标渐变背景
const getIconBg = (type: string) => {
    const colors: Record<string, string> = {
        primary: $theme.primaryColor,
        secondary: $theme.secondaryColor,
        cta: $theme.ctaColor,
        accent: $theme.accentColor
    }
    const color = colors[type] || $theme.primaryColor
    return `linear-gradient(135deg, ${color} 0%, ${color} 100%)`
}

// 根据消息类型获取图标背景色
const getTypeBg = (type: number) => {
    const map: Record<number, string> = {
        1: $theme.primaryColor,
        2: $theme.ctaColor,
        3: $theme.secondaryColor
    }
    const color = map[type] || $theme.primaryColor
    return `linear-gradient(135deg, ${color} 0%, ${color} 100%)`
}

// 根据消息类型获取图标名称
const getTypeIcon = (type: number) => {
    const map: Record<number, string> = {
        1: 'notice',
        2: 'shop',
        3: 'my-love'
    }
    return map[type] || 'email'
}

// 根据类型获取未读数
const getUnreadByType = (type: number) => {
    const map: Record<number, string> = {
        1: 'system',
        2: 'order',
        3: 'interact'
    }
    return unreadCount.value[map[type]] || 0
}

// 切换消息类型
const switchType = (type: number) => {
    currentType.value = type
    if (type > 0) {
        loadList(true)
    } else {
        // 回到首页时加载最近消息
        loadRecentList()
    }
}

// 加载未读数量
const loadUnreadCount = async () => {
    try {
        const res = await getUnreadCount()
        unreadCount.value = res || {}
    } catch (error) {
        console.error(error)
    }
}

// 加载最近消息（首页用）
const loadRecentList = async () => {
    loading.value = true
    try {
        const res = await getNotificationList({
            page: 1,
            limit: 5
        })
        notificationList.value = res.lists || []
    } catch (error) {
        console.error(error)
    } finally {
        loading.value = false
    }
}

// 加载分类消息列表
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

// 点击消息项
const handleItemClick = async (item: any) => {
    // 标记已读
    if (!item.is_read) {
        try {
            await markNotificationRead({ id: item.id })
            item.is_read = 1
            loadUnreadCount()
        } catch (error) {
            console.error(error)
        }
    }

    // 跳转到目标页面
    if (item.target_type && item.target_id) {
        const routeMap: Record<string, string> = {
            order: '/pages/order_detail/order_detail',
            order_detail: '/pages/order_detail/order_detail',
            staff_order: '/packages/pages/staff_order_detail/staff_order_detail',
            dynamic: '/pages/dynamic_detail/dynamic_detail',
            dynamic_detail: '/pages/dynamic_detail/dynamic_detail',
            review: '/packages/pages/review/detail'
        }
        const route = routeMap[item.target_type]
        if (route) {
            uni.navigateTo({ url: `${route}?id=${item.target_id}` })
        } else {
            try {
                await getNotificationDetail({ id: item.id })
            } catch (error) {
                console.error(error)
            }
        }
    }
}

// 全部标记已读
const handleMarkAllRead = (type: number) => {
    uni.showModal({
        title: '提示',
        content: '确定将所有消息标记为已读吗？',
        success: async (res) => {
            if (res.confirm) {
                try {
                    await markAllNotificationRead({
                        notify_type: type || undefined
                    })
                    uni.showToast({ title: '标记成功', icon: 'success' })
                    loadUnreadCount()
                    if (currentType.value > 0) {
                        loadList(true)
                    } else {
                        loadRecentList()
                    }
                } catch (error) {
                    console.error(error)
                }
            }
        }
    })
}

// 清空消息
const handleClear = () => {
    uni.showModal({
        title: '提示',
        content: '确定清空所有消息吗？此操作不可恢复',
        success: async (res) => {
            if (res.confirm) {
                try {
                    await clearNotification({ notify_type: currentType.value })
                    uni.showToast({ title: '清空成功', icon: 'success' })
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
        loadRecentList()
        uni.stopPullDownRefresh()
    }
})

onMounted(() => {
    loadUnreadCount()
    loadRecentList()
})
</script>

<style scoped lang="scss">
.notification-page {
    min-height: 100vh;
    background: linear-gradient(180deg, #f9fafb 0%, #ffffff 100%);
    padding-bottom: 48rpx;
}

/* 顶部统计卡片 */
.stats-header {
    padding: 24rpx 24rpx 0;
}

.stats-card {
    border-radius: 24rpx;
    padding: 40rpx 32rpx;
    display: flex;
    align-items: center;
    justify-content: space-between;
    box-shadow: 0 8rpx 32rpx rgba(124, 58, 237, 0.2);
}

.stats-content {
    display: flex;
    align-items: baseline;
    gap: 12rpx;
}

.stats-number {
    font-size: 64rpx;
    font-weight: 700;
    line-height: 1;
}

.stats-label {
    font-size: 28rpx;
    opacity: 0.9;
}

.stats-action {
    display: flex;
    align-items: center;
    gap: 8rpx;
    padding: 14rpx 28rpx;
    border-radius: 32rpx;
    font-size: 24rpx;
    transition: all 0.2s ease;

    &:active {
        opacity: 0.7;
    }
}

/* 区域卡片 */
.section-card {
    background: #ffffff;
    border-radius: 24rpx;
    margin: 24rpx;
    padding: 28rpx 0;
    box-shadow: 0 4rpx 16rpx rgba(0, 0, 0, 0.06);
    overflow: hidden;
}

.section-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0 28rpx 20rpx;
}

.section-title {
    font-size: 30rpx;
    font-weight: 600;
    color: #333333;
    padding: 0 28rpx 0;
}

.section-title-row {
    display: flex;
    align-items: center;
    gap: 8rpx;

    .section-title {
        padding: 0;
    }
}

.back-type {
    width: 56rpx;
    height: 56rpx;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 28rpx;
    background: #f5f5f5;
    transition: all 0.2s ease;

    &:active {
        background: #e5e5e5;
    }
}

.section-more {
    display: flex;
    align-items: center;
    gap: 4rpx;
    font-size: 24rpx;
}

/* 分类网格 */
.category-grid {
    display: flex;
    justify-content: space-around;
    padding: 20rpx 16rpx 8rpx;
}

.category-item {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 12rpx;
    padding: 16rpx;
    border-radius: 16rpx;
    transition: all 0.2s ease;
}

.category-item-hover {
    background: #f9fafb;
}

.category-icon-wrap {
    width: 96rpx;
    height: 96rpx;
    border-radius: 24rpx;
    display: flex;
    align-items: center;
    justify-content: center;
    position: relative;
    box-shadow: 0 6rpx 16rpx rgba(0, 0, 0, 0.12);
}

.category-badge {
    position: absolute;
    top: -10rpx;
    right: -10rpx;
    min-width: 36rpx;
    height: 36rpx;
    line-height: 36rpx;
    text-align: center;
    font-size: 20rpx;
    color: #ffffff;
    background: #FF2C3C;
    border-radius: 18rpx;
    padding: 0 8rpx;
    border: 3rpx solid #ffffff;
}

.category-name {
    font-size: 26rpx;
    font-weight: 500;
    color: #333333;
}

.category-desc {
    font-size: 22rpx;
    color: #999999;
}

/* 操作按钮组 */
.action-group {
    display: flex;
    gap: 16rpx;
}

.action-btn {
    display: flex;
    align-items: center;
    gap: 6rpx;
    padding: 10rpx 20rpx;
    border-radius: 24rpx;
    background: #f5f5f5;
    font-size: 24rpx;
    color: #666666;
    transition: all 0.2s ease;
}

.action-btn-hover {
    background: #e5e5e5;
    transform: scale(0.98);
}

/* 消息列表 */
.message-list {
    padding: 0 8rpx;
}

.message-item {
    display: flex;
    align-items: flex-start;
    padding: 24rpx 20rpx;
    margin: 0 8rpx;
    border-bottom: 1rpx solid #f5f5f5;
    position: relative;
    transition: all 0.2s ease;

    &:last-child {
        border-bottom: none;
    }
}

.message-item-hover {
    background: #f9fafb;
    border-radius: 12rpx;
}

.message-dot {
    position: absolute;
    left: 8rpx;
    top: 36rpx;

    .dot {
        width: 14rpx;
        height: 14rpx;
        border-radius: 50%;
    }
}

.message-icon {
    width: 72rpx;
    height: 72rpx;
    min-width: 72rpx;
    border-radius: 18rpx;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-right: 20rpx;
    margin-left: 16rpx;
    box-shadow: 0 4rpx 12rpx rgba(0, 0, 0, 0.1);
}

.message-body {
    flex: 1;
    min-width: 0;
}

.message-top {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 8rpx;
}

.message-title {
    font-size: 28rpx;
    font-weight: 600;
    color: #333333;
    flex: 1;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    margin-right: 16rpx;
}

.message-time {
    font-size: 22rpx;
    color: #C8C9CC;
    white-space: nowrap;
}

.message-content {
    font-size: 26rpx;
    color: #999999;
    line-height: 1.5;
    display: -webkit-box;
    -webkit-box-orient: vertical;
    -webkit-line-clamp: 2;
    overflow: hidden;
}

.message-link {
    display: flex;
    align-items: center;
    gap: 4rpx;
    margin-top: 12rpx;
    font-size: 24rpx;
}

.message-arrow {
    display: flex;
    align-items: center;
    margin-left: 8rpx;
    padding-top: 20rpx;
}

/* 空状态 */
.empty-state {
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: 60rpx 0 40rpx;
}

.empty-text {
    font-size: 28rpx;
    color: #999999;
    margin-top: 20rpx;
}

.empty-hint {
    font-size: 24rpx;
    color: #C8C9CC;
    margin-top: 8rpx;
}

/* 加载状态 */
.loading-tip {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 12rpx;
    padding: 32rpx;
    font-size: 26rpx;
    color: #999999;
}

.load-more-tip {
    text-align: center;
    padding: 24rpx;
    font-size: 24rpx;
    color: #C8C9CC;
}

/* 底部安全区 */
.safe-bottom {
    height: constant(safe-area-inset-bottom);
    height: env(safe-area-inset-bottom);
}

/* 无障碍：减少动画 */
@media (prefers-reduced-motion: reduce) {
    * {
        animation-duration: 0.01ms !important;
        animation-iteration-count: 1 !important;
        transition-duration: 0.01ms !important;
    }
}
</style>
