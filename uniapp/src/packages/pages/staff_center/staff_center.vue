<template>
    <page-meta :page-style="$theme.pageStyle">
        <navigation-bar
            title="服务人员中心"
            :front-color="$theme.navColor"
            :background-color="$theme.navBgColor"
        />
    </page-meta>

    <view class="page-container">
        <!-- 顶部渐变背景区域 -->
        <view
            class="header-bg"
            :style="{
                background: `linear-gradient(135deg, ${$theme.primaryColor} 0%, ${$theme.secondaryColor || $theme.primaryColor} 100%)`
            }"
        >
            <!-- 装饰圆 -->
            <view class="header-decor-1" />
            <view class="header-decor-2" />
        </view>

        <!-- 用户信息卡片 - 浮层设计 -->
        <view class="profile-card" @click="goPage('/packages/pages/staff_profile/staff_profile')">
            <view class="profile-main">
                <!-- 头像区域 -->
                <view class="avatar-section">
                    <image
                        class="profile-avatar"
                        :src="profile.avatar || defaultAvatar"
                        mode="aspectFill"
                    />
                    <!-- 认证徽章 -->
                    <view
                        v-if="profile.status === 1"
                        class="verify-badge"
                        :style="{ background: $theme.primaryColor }"
                    >
                        <tn-icon name="check" size="20" color="#FFFFFF" />
                    </view>
                </view>

                <!-- 信息区域 -->
                <view class="profile-info">
                    <view class="profile-name-row">
                        <text class="profile-name">{{ profile.name || '未填写姓名' }}</text>
                        <view
                            v-if="profile.status !== undefined"
                            class="status-tag"
                            :style="getStatusStyle(profile.status)"
                        >
                            {{ getStatusText(profile.status) }}
                        </view>
                    </view>
                    <view class="profile-mobile">
                        <tn-icon name="phone" size="24" color="#999999" />
                        <text>{{ profile.mobile || '未绑定手机号' }}</text>
                    </view>
                    <view
                        v-if="profile.price"
                        class="profile-price"
                        :style="{ color: $theme.ctaColor }"
                    >
                        <text class="price-symbol">¥</text>
                        <text class="price-value">{{ profile.price }}</text>
                        <text class="price-unit">/次</text>
                    </view>
                </view>

                <!-- 箭头 -->
                <view class="profile-arrow">
                    <tn-icon name="right" size="28" color="#C8C9CC" />
                </view>
            </view>
        </view>

        <!-- 数据统计卡片 -->
        <view class="stats-card">
            <view
                v-for="(stat, index) in stats"
                :key="index"
                class="stat-item"
                @click="goPage(stat.path)"
            >
                <view
                    class="stat-icon-bg"
                    :style="{
                        background: `linear-gradient(135deg, ${stat.color}20 0%, ${stat.color}40 100%)`
                    }"
                >
                    <tn-icon :name="stat.icon" size="36" :color="stat.color" />
                </view>
                <text class="stat-value" :style="{ color: stat.color }">{{ stat.value }}</text>
                <text class="stat-label">{{ stat.label }}</text>
            </view>
        </view>

        <!-- 功能菜单 -->
        <view class="menu-card">
            <view
                v-for="(item, index) in menus"
                :key="item.path"
                class="menu-item"
                @click="goPage(item.path)"
            >
                <view class="menu-left">
                    <view
                        class="menu-icon-bg"
                        :style="{ background: item.iconBg }"
                    >
                        <tn-icon :name="item.icon" size="36" :color="item.iconColor" />
                    </view>
                    <view class="menu-text">
                        <text class="menu-name">{{ item.name }}</text>
                        <text class="menu-desc">{{ item.desc }}</text>
                    </view>
                </view>
                <tn-icon name="right" size="26" color="#D1D5DB" />
            </view>
        </view>

        <!-- 底部提示 -->
        <view class="tip-bar">
            <tn-icon name="info" size="26" color="#9CA3AF" />
            <text class="tip-text">仅可维护本人资料、订单、作品、套餐、档期与动态</text>
        </view>
    </view>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import { onShow } from '@dcloudio/uni-app'
import { staffCenterProfile } from '@/api/staffCenter'
import { ensureStaffCenterAccess } from '@/utils/staff-center'
import { useThemeStore } from '@/stores/theme'

const $theme = useThemeStore()
const profile = ref<any>({})
const defaultAvatar = '/static/images/user/default_avatar.png'

// 数据统计 - 每个带独立颜色
const stats = computed(() => [
    {
        icon: 'order',
        value: profile.value.orderCount || 0,
        label: '订单',
        color: '#7C3AED',
        path: '/packages/pages/staff_order_list/staff_order_list'
    },
    {
        icon: 'image',
        value: profile.value.workCount || 0,
        label: '作品',
        color: '#EC4899',
        path: '/packages/pages/staff_work_list/staff_work_list'
    },
    {
        icon: 'gift',
        value: profile.value.packageCount || 0,
        label: '套餐',
        color: '#F97316',
        path: '/packages/pages/staff_package_list/staff_package_list'
    },
    {
        icon: 'calendar',
        value: profile.value.scheduleCount || 0,
        label: '档期',
        color: '#19BE6B',
        path: '/packages/pages/staff_schedule/staff_schedule'
    }
])

// 功能菜单 - 带独立图标颜色
const menus = [
    {
        name: '个人资料',
        desc: '完善基本信息与服务说明',
        icon: 'my',
        iconBg: 'linear-gradient(135deg, #EDE9FE 0%, #DDD6FE 100%)',
        iconColor: '#7C3AED',
        path: '/packages/pages/staff_profile/staff_profile'
    },
    {
        name: '订单管理',
        desc: '查看并管理服务订单',
        icon: 'order',
        iconBg: 'linear-gradient(135deg, #DBEAFE 0%, #BFDBFE 100%)',
        iconColor: '#3B82F6',
        path: '/packages/pages/staff_order_list/staff_order_list'
    },
    {
        name: '作品管理',
        desc: '上传作品并等待审核',
        icon: 'image',
        iconBg: 'linear-gradient(135deg, #FCE7F3 0%, #FBCFE8 100%)',
        iconColor: '#EC4899',
        path: '/packages/pages/staff_work_list/staff_work_list'
    },
    {
        name: '套餐管理',
        desc: '关联与调整服务套餐',
        icon: 'gift',
        iconBg: 'linear-gradient(135deg, #FFEDD5 0%, #FED7AA 100%)',
        iconColor: '#F97316',
        path: '/packages/pages/staff_package_list/staff_package_list'
    },
    {
        name: '档期管理',
        desc: '设置可预约日期与时段',
        icon: 'calendar',
        iconBg: 'linear-gradient(135deg, #D1FAE5 0%, #A7F3D0 100%)',
        iconColor: '#19BE6B',
        path: '/packages/pages/staff_schedule/staff_schedule'
    },
    {
        name: '动态管理',
        desc: '发布动态，展示服务案例',
        icon: 'edit',
        iconBg: 'linear-gradient(135deg, #FEF3C7 0%, #FDE68A 100%)',
        iconColor: '#D97706',
        path: '/packages/pages/staff_dynamic_list/staff_dynamic_list'
    }
]

// 获取状态样式
const getStatusStyle = (status: number) => {
    const styles: Record<number, any> = {
        0: { background: 'rgba(255, 153, 0, 0.12)', color: '#FF9900' },
        1: { background: 'rgba(25, 190, 107, 0.12)', color: '#19BE6B' },
        2: { background: 'rgba(255, 44, 60, 0.12)', color: '#FF2C3C' }
    }
    return styles[status] || styles[0]
}

// 获取状态文本
const getStatusText = (status: number) => {
    const texts: Record<number, string> = {
        0: '待审核',
        1: '已认证',
        2: '已拒绝'
    }
    return texts[status] || '未知'
}

// 获取资料
const fetchProfile = async () => {
    try {
        const data = await staffCenterProfile()
        profile.value = data || {}
    } catch (e: any) {
        const msg = typeof e === 'string' ? e : e?.msg || e?.message || '获取资料失败'
        uni.showToast({ title: msg, icon: 'none' })
    }
}

// 页面跳转
const goPage = (path: string) => {
    uni.navigateTo({ url: path })
}

onShow(async () => {
    if (!(await ensureStaffCenterAccess())) return
    fetchProfile()
})
</script>

<style lang="scss" scoped>
.page-container {
    min-height: 100vh;
    background: #F4F5F7;
    padding-bottom: 60rpx;
}

/* 顶部渐变背景 */
.header-bg {
    position: absolute;
    top: -200rpx;
    left: 0;
    right: 0;
    height: 580rpx;
    border-radius: 0 0 48rpx 48rpx;
    overflow: hidden;
}

.header-decor-1 {
    position: absolute;
    top: -60rpx;
    right: -40rpx;
    width: 240rpx;
    height: 240rpx;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.08);
}

.header-decor-2 {
    position: absolute;
    bottom: -30rpx;
    left: -50rpx;
    width: 180rpx;
    height: 180rpx;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.06);
}

/* 用户信息卡片 */
.profile-card {
    position: relative;
    margin: 24rpx 24rpx 0;
    padding: 32rpx;
    background: #FFFFFF;
    border-radius: 24rpx;
    box-shadow: 0 8rpx 40rpx rgba(0, 0, 0, 0.08);
    z-index: 2;
}

.profile-main {
    display: flex;
    align-items: center;
}

.avatar-section {
    position: relative;
    flex-shrink: 0;
}

.profile-avatar {
    width: 140rpx;
    height: 140rpx;
    border-radius: 70rpx;
    background: #F3F4F6;
    border: 6rpx solid #FFFFFF;
    box-shadow: 0 4rpx 20rpx rgba(0, 0, 0, 0.1);
}

.verify-badge {
    position: absolute;
    bottom: 4rpx;
    right: 4rpx;
    width: 40rpx;
    height: 40rpx;
    border-radius: 20rpx;
    display: flex;
    align-items: center;
    justify-content: center;
    border: 4rpx solid #FFFFFF;
    box-shadow: 0 2rpx 8rpx rgba(0, 0, 0, 0.15);
}

.profile-info {
    flex: 1;
    margin-left: 28rpx;
    overflow: hidden;
}

.profile-name-row {
    display: flex;
    align-items: center;
    gap: 12rpx;
}

.profile-name {
    font-size: 36rpx;
    font-weight: 700;
    color: #1F2937;
    line-height: 1.3;
}

.status-tag {
    flex-shrink: 0;
    padding: 4rpx 14rpx;
    border-radius: 20rpx;
    font-size: 22rpx;
    font-weight: 600;
}

.profile-mobile {
    display: flex;
    align-items: center;
    gap: 8rpx;
    margin-top: 10rpx;
    font-size: 26rpx;
    color: #9CA3AF;
}

.profile-price {
    display: flex;
    align-items: baseline;
    margin-top: 12rpx;

    .price-symbol {
        font-size: 24rpx;
        font-weight: 700;
    }

    .price-value {
        font-size: 44rpx;
        font-weight: 800;
        margin: 0 2rpx;
        line-height: 1;
    }

    .price-unit {
        font-size: 24rpx;
        font-weight: 500;
        color: #9CA3AF;
    }
}

.profile-arrow {
    flex-shrink: 0;
    margin-left: 12rpx;
}

/* 数据统计卡片 */
.stats-card {
    position: relative;
    display: flex;
    margin: 20rpx 24rpx 0;
    padding: 28rpx 16rpx;
    background: #FFFFFF;
    border-radius: 24rpx;
    box-shadow: 0 4rpx 24rpx rgba(0, 0, 0, 0.05);
    z-index: 2;
}

.stat-item {
    flex: 1;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 8rpx;
    position: relative;

    &:not(:last-child)::after {
        content: '';
        position: absolute;
        right: 0;
        top: 50%;
        transform: translateY(-50%);
        width: 1rpx;
        height: 60rpx;
        background: #F3F4F6;
    }

    &:active {
        opacity: 0.7;
    }
}

.stat-icon-bg {
    width: 72rpx;
    height: 72rpx;
    border-radius: 36rpx;
    display: flex;
    align-items: center;
    justify-content: center;
}

.stat-value {
    font-size: 40rpx;
    font-weight: 800;
    line-height: 1.1;
}

.stat-label {
    font-size: 24rpx;
    color: #9CA3AF;
    font-weight: 500;
}

/* 功能菜单 */
.menu-card {
    margin: 20rpx 24rpx 0;
    background: #FFFFFF;
    border-radius: 24rpx;
    box-shadow: 0 4rpx 24rpx rgba(0, 0, 0, 0.05);
    overflow: hidden;
}

.menu-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 28rpx 28rpx;
    transition: all 0.15s ease;

    &:not(:last-child) {
        border-bottom: 1rpx solid #F9FAFB;
    }

    &:active {
        background: #FAFAFA;
    }
}

.menu-left {
    display: flex;
    align-items: center;
    flex: 1;
    min-width: 0;
}

.menu-icon-bg {
    width: 72rpx;
    height: 72rpx;
    border-radius: 20rpx;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.menu-text {
    margin-left: 20rpx;
    flex: 1;
    min-width: 0;
}

.menu-name {
    font-size: 30rpx;
    font-weight: 600;
    color: #1F2937;
    line-height: 1.4;
}

.menu-desc {
    font-size: 24rpx;
    color: #9CA3AF;
    margin-top: 4rpx;
    line-height: 1.4;
}

/* 底部提示 */
.tip-bar {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8rpx;
    margin-top: 32rpx;
    padding: 0 48rpx;
}

.tip-text {
    font-size: 24rpx;
    color: #9CA3AF;
    line-height: 1.5;
}
</style>
