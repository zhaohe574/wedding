<template>
    <page-meta :page-style="$theme.pageStyle">
        <navigation-bar 
            title="我的收藏"
            :front-color="$theme.navColor" 
            :background-color="$theme.navBgColor" 
        />
    </page-meta>
    
    <view class="staff-favorite">
        <!-- 加载状态 -->
        <view v-if="loading" class="loading-container">
            <tn-loading mode="circle" />
        </view>

        <!-- 空状态 -->
        <view v-else-if="!favoriteList.length" class="empty-container">
            <view class="empty-icon">
                <tn-icon name="star" size="160" color="#D1D5DB" />
            </view>
            <text class="empty-text">还没有收藏任何服务人员</text>
            <text class="empty-hint">快去发现心仪的服务人员吧</text>
            <view class="empty-btn" @click="goToList">
                <text class="empty-btn-text">去看看</text>
            </view>
        </view>

        <!-- 收藏列表 -->
        <view v-else class="favorite-list">
            <!-- 统计信息 -->
            <view class="stats-bar">
                <text class="stats-text">共收藏 {{ favoriteList.length }} 位服务人员</text>
            </view>

            <!-- 人员卡片 -->
            <view
                v-for="item in favoriteList"
                :key="item.id"
                class="staff-card"
                @click="goToDetail(item.id)"
            >
                <!-- 卡片内容 -->
                <view class="card-content">
                    <!-- 左侧头像 -->
                    <view class="avatar-wrapper">
                        <image
                            class="avatar"
                            :src="item.avatar || '/static/images/default-avatar.png'"
                            mode="aspectFill"
                            lazy-load
                        />
                        <!-- VIP标识 -->
                        <view v-if="item.is_vip" class="vip-badge">
                            <tn-icon name="vip-fill" size="24" color="#FFD700" />
                        </view>
                    </view>

                    <!-- 右侧信息 -->
                    <view class="info-wrapper">
                        <!-- 顶部：姓名和收藏按钮 -->
                        <view class="info-header">
                            <view class="name-row">
                                <text class="staff-name">{{ item.name }}</text>
                                <!-- 认证标识 -->
                                <view v-if="item.is_verified" class="verified-badge">
                                    <tn-icon name="check-circle-fill" size="28" :color="$theme.primaryColor" />
                                </view>
                            </view>
                            <!-- 收藏按钮 -->
                            <view class="favorite-btn" @click.stop="handleCancelFavorite(item)">
                                <tn-icon name="star-fill" size="44" :color="$theme.secondaryColor" />
                            </view>
                        </view>

                        <!-- 分类标签 -->
                        <view class="category-tag">
                            <text class="category-text">{{ item.category_name }}</text>
                        </view>

                        <!-- 评分和服务次数 -->
                        <view class="rating-row">
                            <view class="rating-stars">
                                <tn-icon 
                                    v-for="star in 5" 
                                    :key="star"
                                    :name="star <= Math.floor(item.rating) ? 'star-fill' : 'star'"
                                    size="24"
                                    :color="star <= Math.floor(item.rating) ? $theme.accentColor : '#E5E7EB'"
                                />
                            </view>
                            <text class="rating-score">{{ item.rating }}</text>
                            <text class="rating-divider">|</text>
                            <text class="order-count">{{ item.order_count }}单</text>
                        </view>

                        <!-- 底部：价格和操作 -->
                        <view class="info-footer">
                            <view class="price-wrapper">
                                <text class="price-symbol">¥</text>
                                <text class="price-value">{{ item.price }}</text>
                                <text class="price-unit">/次</text>
                            </view>
                            <view 
                                class="action-btn" 
                                :style="{ background: `linear-gradient(135deg, ${$theme.primaryColor} 0%, ${$theme.primaryColor} 100%)` }"
                                @click.stop="handleBooking(item)"
                            >
                                <text class="action-text">立即预约</text>
                            </view>
                        </view>
                    </view>
                </view>
            </view>
        </view>
    </view>
</template>

<script lang="ts" setup>
import { ref } from 'vue'
import { onShow } from '@dcloudio/uni-app'
import { getMyFavoriteStaff, toggleStaffFavorite } from '@/api/staff'
import { useThemeStore } from '@/stores/theme'

const themeStore = useThemeStore()

const loading = ref(true)
const favoriteList = ref<any[]>([])

// 获取收藏列表
const getFavorites = async () => {
    loading.value = true
    try {
        const data = await getMyFavoriteStaff()
        favoriteList.value = data || []
    } catch (e) {
        console.error(e)
        uni.showToast({ 
            title: '加载失败，请重试', 
            icon: 'none' 
        })
    } finally {
        loading.value = false
    }
}

// 取消收藏
const handleCancelFavorite = async (item: any) => {
    uni.showModal({
        title: '提示',
        content: `确定取消收藏 ${item.name} 吗？`,
        success: async (res) => {
            if (res.confirm) {
                try {
                    await toggleStaffFavorite({ id: item.id })
                    favoriteList.value = favoriteList.value.filter((i) => i.id !== item.id)
                    uni.showToast({ 
                        title: '已取消收藏', 
                        icon: 'success' 
                    })
                } catch (e: any) {
                    uni.showToast({ 
                        title: e.msg || '操作失败', 
                        icon: 'none' 
                    })
                }
            }
        }
    })
}

// 立即预约
const handleBooking = (item: any) => {
    if (!item.id) {
        uni.showToast({ title: '服务人员信息错误', icon: 'none' })
        return
    }
    uni.navigateTo({
        url: `/packages/pages/schedule_calendar/schedule_calendar?staff_id=${item.id}`
    })
}

// 跳转列表
const goToList = () => {
    uni.switchTab({
        url: '/pages/index/index'
    })
}

// 跳转详情
const goToDetail = (id: number) => {
    uni.navigateTo({
        url: `/packages/pages/staff_detail/staff_detail?id=${id}`
    })
}

onShow(() => {
    getFavorites()
})
</script>

<style lang="scss" scoped>
.staff-favorite {
    min-height: 100vh;
    background: linear-gradient(180deg, var(--color-primary-light-9) 0%, #F5F5F5 100%);
}

/* 加载状态 */
.loading-container {
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 200rpx 0;
}

/* 空状态 */
.empty-container {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 200rpx 48rpx;
    
    .empty-icon {
        margin-bottom: 32rpx;
    }
    
    .empty-text {
        font-size: 32rpx;
        font-weight: 500;
        color: var(--color-main);
        margin-bottom: 16rpx;
    }
    
    .empty-hint {
        font-size: 26rpx;
        color: var(--color-muted);
        margin-bottom: 48rpx;
    }
    
    .empty-btn {
        background: linear-gradient(135deg, var(--color-primary) 0%, var(--color-primary-dark-2) 100%);
        padding: 24rpx 80rpx;
        border-radius: 48rpx;
        box-shadow: 0 8rpx 24rpx rgba(124, 58, 237, 0.3);
        transition: all 0.2s ease;
        
        &:active {
            transform: translateY(2rpx);
            box-shadow: 0 4rpx 12rpx rgba(124, 58, 237, 0.3);
        }
        
        .empty-btn-text {
            font-size: 28rpx;
            font-weight: 500;
            color: var(--color-btn-text);
        }
    }
}

/* 收藏列表 */
.favorite-list {
    padding: 24rpx;
}

/* 统计栏 */
.stats-bar {
    padding: 16rpx 24rpx;
    margin-bottom: 16rpx;
    
    .stats-text {
        font-size: 26rpx;
        color: var(--color-content);
    }
}

/* 人员卡片 */
.staff-card {
    background: #FFFFFF;
    border-radius: 16rpx;
    margin-bottom: 24rpx;
    box-shadow: 0 2rpx 12rpx rgba(0, 0, 0, 0.08);
    overflow: hidden;
    transition: all 0.2s ease;
    
    &:active {
        transform: translateY(-2rpx);
        box-shadow: 0 8rpx 24rpx rgba(0, 0, 0, 0.12);
    }
}

.card-content {
    display: flex;
    padding: 24rpx;
}

/* 头像区域 */
.avatar-wrapper {
    position: relative;
    margin-right: 20rpx;
    
    .avatar {
        width: 160rpx;
        height: 160rpx;
        border-radius: 16rpx;
        background: var(--color-primary-light-9);
    }
    
    .vip-badge {
        position: absolute;
        top: 8rpx;
        right: 8rpx;
        width: 40rpx;
        height: 40rpx;
        background: rgba(0, 0, 0, 0.6);
        border-radius: 20rpx;
        display: flex;
        align-items: center;
        justify-content: center;
    }
}

/* 信息区域 */
.info-wrapper {
    flex: 1;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
}

/* 顶部：姓名和收藏 */
.info-header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    margin-bottom: 12rpx;
}

.name-row {
    display: flex;
    align-items: center;
    flex: 1;
}

.staff-name {
    font-size: 32rpx;
    font-weight: 600;
    color: var(--color-main);
    margin-right: 8rpx;
}

.verified-badge {
    display: flex;
    align-items: center;
}

.favorite-btn {
    padding: 8rpx;
    transition: all 0.2s ease;
    
    &:active {
        transform: scale(0.9);
    }
}

/* 分类标签 */
.category-tag {
    display: inline-flex;
    padding: 6rpx 16rpx;
    background: var(--color-primary-light-9);
    border: 1rpx solid var(--color-primary-light-7);
    border-radius: 12rpx;
    margin-bottom: 12rpx;
    align-self: flex-start;
    
    .category-text {
        font-size: 24rpx;
        font-weight: 500;
        color: var(--color-primary);
    }
}

/* 评分行 */
.rating-row {
    display: flex;
    align-items: center;
    margin-bottom: 16rpx;
}

.rating-stars {
    display: flex;
    align-items: center;
    gap: 4rpx;
    margin-right: 12rpx;
}

.rating-score {
    font-size: 26rpx;
    font-weight: 600;
    color: var(--color-accent);
    margin-right: 12rpx;
}

.rating-divider {
    font-size: 24rpx;
    color: var(--color-light);
    margin: 0 8rpx;
}

.order-count {
    font-size: 24rpx;
    color: var(--color-muted);
}

/* 底部：价格和操作 */
.info-footer {
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.price-wrapper {
    display: flex;
    align-items: baseline;
}

.price-symbol {
    font-size: 24rpx;
    font-weight: 600;
    color: var(--color-primary);
    margin-right: 4rpx;
}

.price-value {
    font-size: 40rpx;
    font-weight: 700;
    color: var(--color-primary);
}

.price-unit {
    font-size: 24rpx;
    color: var(--color-muted);
    margin-left: 4rpx;
}

.action-btn {
    padding: 16rpx 32rpx;
    border-radius: 48rpx;
    transition: all 0.2s ease;
    
    &:active {
        transform: scale(0.95);
    }
    
    .action-text {
        font-size: 26rpx;
        font-weight: 500;
        color: #FFFFFF;
    }
}
</style>
