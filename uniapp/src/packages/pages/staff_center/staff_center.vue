<template>
    <page-meta :page-style="$theme.pageStyle">
        <navigation-bar title="服务人员中心" :front-color="$theme.navColor" :background-color="$theme.navBgColor" />
    </page-meta>

    <view class="page-container">
        <!-- 用户信息卡片 - 玻璃态设计 -->
        <view class="profile-card" @click="goPage('/packages/pages/staff_profile/staff_profile')">
            <view class="profile-header">
                <image
                    class="profile-avatar"
                    :src="profile.avatar || defaultAvatar"
                    mode="aspectFill"
                />
                <view class="profile-info">
                    <view class="profile-name">
                        {{ profile.name || '未填写姓名' }}
                    </view>
                    <view class="profile-mobile">
                        {{ profile.mobile || '未绑定手机号' }}
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
                <view class="profile-arrow">
                    <tn-icon name="right" size="32" :color="$theme.primaryColor" />
                </view>
            </view>
            
            <!-- 认证状态标签 -->
            <view 
                v-if="profile.status" 
                class="status-badge"
                :style="getStatusStyle(profile.status)"
            >
                <tn-icon :name="getStatusIcon(profile.status)" size="24" color="#FFFFFF" />
                <text class="status-text">{{ getStatusText(profile.status) }}</text>
            </view>
        </view>

        <!-- 数据统计卡片 -->
        <view class="stats-container">
            <view 
                v-for="(stat, index) in stats" 
                :key="index"
                class="stat-item"
                @click="goPage(stat.path)"
            >
                <view 
                    class="stat-icon-wrapper"
                    :style="{ background: `linear-gradient(135deg, ${$theme.primaryColor}15 0%, ${$theme.primaryColor}30 100%)` }"
                >
                    <tn-icon :name="stat.icon" size="40" :color="$theme.primaryColor" />
                </view>
                <view class="stat-value" :style="{ color: $theme.primaryColor }">
                    {{ stat.value }}
                </view>
                <view class="stat-label">{{ stat.label }}</view>
            </view>
        </view>

        <!-- 功能菜单 -->
        <view class="menu-container">
            <view
                v-for="item in menus"
                :key="item.path"
                class="menu-item"
                @click="goPage(item.path)"
            >
                <view class="menu-left">
                    <view 
                        class="menu-icon-wrapper"
                        :style="{ background: `linear-gradient(135deg, ${$theme.primaryColor}10 0%, ${$theme.primaryColor}20 100%)` }"
                    >
                        <tn-icon :name="item.icon" size="36" :color="$theme.primaryColor" />
                    </view>
                    <view class="menu-content">
                        <view class="menu-name">{{ item.name }}</view>
                        <view class="menu-desc">{{ item.desc }}</view>
                    </view>
                </view>
                <tn-icon name="right" size="28" color="#C8C9CC" />
            </view>
        </view>

        <!-- 提示信息 -->
        <view class="tip-container">
            <tn-icon name="info" size="28" color="#999999" />
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

// 数据统计
const stats = computed(() => [
    {
        icon: 'order',
        value: profile.value.orderCount || 0,
        label: '订单数',
        path: '/packages/pages/staff_order_list/staff_order_list'
    },
    {
        icon: 'image',
        value: profile.value.workCount || 0,
        label: '作品数',
        path: '/packages/pages/staff_work_list/staff_work_list'
    },
    {
        icon: 'gift',
        value: profile.value.packageCount || 0,
        label: '套餐数',
        path: '/packages/pages/staff_package_list/staff_package_list'
    },
    {
        icon: 'calendar',
        value: profile.value.scheduleCount || 0,
        label: '档期数',
        path: '/packages/pages/staff_schedule/staff_schedule'
    }
])

// 功能菜单
const menus = [
    {
        name: '个人资料',
        desc: '完善基本信息与服务说明',
        icon: 'user',
        path: '/packages/pages/staff_profile/staff_profile'
    },
    {
        name: '订单管理',
        desc: '查看并管理服务订单',
        icon: 'order',
        path: '/packages/pages/staff_order_list/staff_order_list'
    },
    {
        name: '作品管理',
        desc: '上传作品并等待审核',
        icon: 'image',
        path: '/packages/pages/staff_work_list/staff_work_list'
    },
    {
        name: '套餐管理',
        desc: '关联与调整服务套餐',
        icon: 'gift',
        path: '/packages/pages/staff_package_list/staff_package_list'
    },
    {
        name: '档期管理',
        desc: '设置可预约日期与时段',
        icon: 'calendar',
        path: '/packages/pages/staff_schedule/staff_schedule'
    },
    {
        name: '动态管理',
        desc: '发布动态，展示服务案例',
        icon: 'edit',
        path: '/packages/pages/staff_dynamic_list/staff_dynamic_list'
    }
]

// 获取状态样式
const getStatusStyle = (status: number) => {
    const styles: Record<number, any> = {
        0: { background: 'rgba(255, 153, 0, 0.1)', color: '#FF9900' }, // 待审核
        1: { background: `${$theme.primaryColor}15`, color: $theme.primaryColor }, // 已认证
        2: { background: 'rgba(255, 44, 60, 0.1)', color: '#FF2C3C' } // 已拒绝
    }
    return styles[status] || styles[0]
}

// 获取状态图标
const getStatusIcon = (status: number) => {
    const icons: Record<number, string> = {
        0: 'clock',
        1: 'check-circle',
        2: 'close-circle'
    }
    return icons[status] || 'clock'
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
    background: linear-gradient(180deg, rgba(124, 58, 237, 0.05) 0%, #F6F6F6 100%);
    padding-bottom: 40rpx;
}

/* 用户信息卡片 - 玻璃态设计 */
.profile-card {
    position: relative;
    margin: 24rpx;
    padding: 32rpx 24rpx;
    background: rgba(255, 255, 255, 0.9);
    backdrop-filter: blur(20rpx);
    border-radius: 24rpx;
    box-shadow: 0 8rpx 32rpx rgba(124, 58, 237, 0.08),
                0 2rpx 8rpx rgba(0, 0, 0, 0.04);
    transition: all 0.3s ease;
    overflow: hidden;
    
    &::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 4rpx;
        background: linear-gradient(90deg, #7C3AED 0%, #EC4899 100%);
    }
}

.profile-header {
    display: flex;
    align-items: center;
}

.profile-avatar {
    width: 128rpx;
    height: 128rpx;
    border-radius: 64rpx;
    background: #F2F2F2;
    border: 4rpx solid #FFFFFF;
    box-shadow: 0 4rpx 16rpx rgba(0, 0, 0, 0.08);
}

.profile-info {
    flex: 1;
    margin-left: 24rpx;
}

.profile-name {
    font-size: 36rpx;
    font-weight: 600;
    color: #333333;
    line-height: 1.4;
}

.profile-mobile {
    font-size: 26rpx;
    color: #999999;
    margin-top: 8rpx;
}

.profile-price {
    display: flex;
    align-items: baseline;
    margin-top: 12rpx;
    
    .price-symbol {
        font-size: 24rpx;
        font-weight: 600;
    }
    
    .price-value {
        font-size: 40rpx;
        font-weight: 700;
        margin: 0 4rpx;
    }
    
    .price-unit {
        font-size: 24rpx;
        font-weight: 500;
    }
}

.profile-arrow {
    margin-left: 16rpx;
}

/* 认证状态标签 */
.status-badge {
    display: inline-flex;
    align-items: center;
    gap: 8rpx;
    margin-top: 24rpx;
    padding: 8rpx 16rpx;
    border-radius: 24rpx;
    font-size: 24rpx;
    font-weight: 500;
    
    .status-text {
        color: inherit;
    }
}

/* 数据统计卡片 */
.stats-container {
    display: flex;
    gap: 16rpx;
    margin: 0 24rpx 24rpx;
}

.stat-item {
    flex: 1;
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: 24rpx 16rpx;
    background: #FFFFFF;
    border-radius: 16rpx;
    box-shadow: 0 2rpx 12rpx rgba(0, 0, 0, 0.06);
    transition: all 0.2s ease;
    
    &:active {
        transform: translateY(-2rpx);
        box-shadow: 0 4rpx 16rpx rgba(0, 0, 0, 0.1);
    }
}

.stat-icon-wrapper {
    width: 80rpx;
    height: 80rpx;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 40rpx;
    margin-bottom: 12rpx;
}

.stat-value {
    font-size: 40rpx;
    font-weight: 700;
    line-height: 1.2;
    margin-bottom: 4rpx;
}

.stat-label {
    font-size: 24rpx;
    color: #999999;
}

/* 功能菜单 */
.menu-container {
    margin: 0 24rpx;
    background: #FFFFFF;
    border-radius: 16rpx;
    overflow: hidden;
    box-shadow: 0 2rpx 12rpx rgba(0, 0, 0, 0.06);
}

.menu-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 24rpx;
    border-bottom: 1rpx solid #F5F5F5;
    transition: all 0.2s ease;
    
    &:last-child {
        border-bottom: none;
    }
    
    &:active {
        background: rgba(124, 58, 237, 0.03);
    }
}

.menu-left {
    display: flex;
    align-items: center;
    flex: 1;
}

.menu-icon-wrapper {
    width: 72rpx;
    height: 72rpx;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 16rpx;
    margin-right: 20rpx;
}

.menu-content {
    flex: 1;
}

.menu-name {
    font-size: 30rpx;
    font-weight: 500;
    color: #333333;
    line-height: 1.4;
}

.menu-desc {
    font-size: 24rpx;
    color: #999999;
    margin-top: 6rpx;
    line-height: 1.4;
}

/* 提示信息 */
.tip-container {
    display: flex;
    align-items: center;
    gap: 12rpx;
    margin: 24rpx 24rpx 0;
    padding: 20rpx 24rpx;
    background: rgba(255, 255, 255, 0.6);
    border-radius: 12rpx;
}

.tip-text {
    flex: 1;
    font-size: 24rpx;
    color: #999999;
    line-height: 1.5;
}
</style>
