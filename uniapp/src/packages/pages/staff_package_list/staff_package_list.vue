<template>
    <page-meta :page-style="$theme.pageStyle">
        <navigation-bar title="套餐管理" :front-color="$theme.navColor" :background-color="$theme.navBgColor" />
    </page-meta>

    <view class="page-container">
        <!-- 标签页 -->
        <view class="tabs-wrapper">
            <view class="tabs-container">
                <view
                    v-for="(tab, index) in tabs"
                    :key="tab.key"
                    class="tab-item"
                    :class="{ active: tabIndex === index }"
                    :style="tabIndex === index ? {
                        color: $theme.primaryColor,
                        borderBottomColor: $theme.primaryColor
                    } : {}"
                    @click="tabIndex = index"
                >
                    <text class="tab-text">{{ tab.label }}</text>
                    <view 
                        v-if="tabIndex === index" 
                        class="tab-indicator"
                        :style="{ background: $theme.primaryColor }"
                    />
                </view>
            </view>
        </view>

        <!-- 已关联套餐 -->
        <view v-if="tabIndex === 0" class="package-list">
            <view
                v-for="item in configured"
                :key="item.package_id"
                class="package-card"
            >
                <!-- 套餐头部 -->
                <view class="package-header">
                    <view class="package-title">
                        <tn-icon name="gift" size="32" :color="$theme.primaryColor" />
                        <text>{{ getPackageName(item) }}</text>
                    </view>
                    <view 
                        class="package-status"
                        :style="{ 
                            background: item.status ? `${$theme.primaryColor}15` : 'rgba(153, 153, 153, 0.1)',
                            color: item.status ? $theme.primaryColor : '#999999'
                        }"
                    >
                        {{ item.status ? '启用中' : '已禁用' }}
                    </view>
                </view>

                <!-- 套餐信息 -->
                <view class="package-info">
                    <view class="info-item">
                        <tn-icon name="money" size="28" :color="$theme.ctaColor" />
                        <text class="info-label">价格</text>
                        <text class="info-value" :style="{ color: $theme.ctaColor }">
                            ¥{{ getPackagePrice(item) }}
                        </text>
                    </view>
                    <view class="info-item">
                        <tn-icon name="calendar" size="28" color="#999999" />
                        <text class="info-label">预约方式</text>
                        <text class="info-value">{{ getBookingTypeText(item) }}</text>
                    </view>
                </view>

                <!-- 操作按钮 -->
                <view class="package-actions">
                    <view
                        class="action-btn edit-btn"
                        :style="{ 
                            color: $theme.primaryColor,
                            borderColor: $theme.primaryColor
                        }"
                        @click="handleEdit(item)"
                    >
                        <tn-icon name="edit" size="28" :color="$theme.primaryColor" />
                        <text>编辑</text>
                    </view>
                    <view
                        class="action-btn remove-btn"
                        @click="handleRemove(item)"
                    >
                        <tn-icon name="delete" size="28" color="#FF2C3C" />
                        <text>移除</text>
                    </view>
                </view>
            </view>

            <!-- 空状态 -->
            <view v-if="configured.length === 0" class="empty-state">
                <tn-icon name="gift" size="120" color="#E5E5E5" />
                <text class="empty-text">暂无已关联套餐</text>
                <text class="empty-tip">切换到"可选套餐"进行关联</text>
            </view>
        </view>

        <!-- 可选套餐 -->
        <view v-else class="package-list">
            <view
                v-for="item in available"
                :key="item.id"
                class="package-card"
            >
                <!-- 套餐头部 -->
                <view class="package-header">
                    <view class="package-title">
                        <tn-icon name="gift" size="32" :color="$theme.primaryColor" />
                        <text>{{ item.name }}</text>
                    </view>
                </view>

                <!-- 套餐信息 -->
                <view class="package-info">
                    <view class="info-item">
                        <tn-icon name="money" size="28" :color="$theme.ctaColor" />
                        <text class="info-label">价格</text>
                        <text class="info-value" :style="{ color: $theme.ctaColor }">
                            ¥{{ item.price || 0 }}
                        </text>
                    </view>
                </view>

                <!-- 操作按钮 -->
                <view class="package-actions">
                    <view
                        class="action-btn add-btn"
                        :style="{ 
                            background: `linear-gradient(135deg, ${$theme.primaryColor} 0%, ${$theme.primaryColor} 100%)`,
                            color: $theme.btnColor
                        }"
                        @click="handleAdd(item)"
                    >
                        <tn-icon name="add" size="28" :color="$theme.btnColor" />
                        <text>关联套餐</text>
                    </view>
                </view>
            </view>

            <!-- 空状态 -->
            <view v-if="available.length === 0" class="empty-state">
                <tn-icon name="gift" size="120" color="#E5E5E5" />
                <text class="empty-text">暂无可关联套餐</text>
            </view>
        </view>
    </view>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import { onShow } from '@dcloudio/uni-app'
import { staffCenterPackageLists, staffCenterPackageAdd, staffCenterPackageRemove } from '@/api/staffCenter'
import { ensureStaffCenterAccess } from '@/utils/staff-center'
import { useThemeStore } from '@/stores/theme'

const $theme = useThemeStore()

const tabs = [
    { key: 'configured', label: '已关联' },
    { key: 'available', label: '可选套餐' }
]
const tabIndex = ref(0)
const configured = ref<any[]>([])
const available = ref<any[]>([])

// 获取套餐名称
const getPackageName = (item: any) => item.package?.name || item.name || '套餐'

// 获取套餐价格
const getPackagePrice = (item: any) =>
    item.custom_price ?? item.price ?? item.package?.price ?? 0

// 获取预约方式文本
const getBookingTypeText = (item: any) => {
    const type = item.booking_type ?? item.package?.booking_type ?? 0
    return Number(type) === 1 ? '分场次预约' : '全天预约'
}

// 获取套餐列表
const fetchPackages = async () => {
    try {
        const data = await staffCenterPackageLists()
        configured.value = data?.configured || []
        available.value = data?.available || []
    } catch (e: any) {
        const msg = typeof e === 'string' ? e : e?.msg || e?.message || '加载失败'
        uni.showToast({ title: msg, icon: 'none' })
    }
}

// 关联套餐
const handleAdd = async (item: any) => {
    try {
        await staffCenterPackageAdd({ package_id: item.id })
        uni.showToast({ title: '关联成功', icon: 'success' })
        fetchPackages()
    } catch (e: any) {
        const msg = typeof e === 'string' ? e : e?.msg || e?.message || '关联失败'
        uni.showToast({ title: msg, icon: 'none' })
    }
}

// 移除套餐
const handleRemove = (item: any) => {
    uni.showModal({
        title: '确认移除',
        content: '移除后将不再显示该套餐，是否继续？',
        success: async (res) => {
            if (!res.confirm) return
            try {
                await staffCenterPackageRemove({ package_id: item.package_id })
                uni.showToast({ title: '移除成功', icon: 'success' })
                fetchPackages()
            } catch (e: any) {
                const msg = typeof e === 'string' ? e : e?.msg || e?.message || '移除失败'
                uni.showToast({ title: msg, icon: 'none' })
            }
        }
    })
}

// 编辑套餐
const handleEdit = (item: any) => {
    uni.navigateTo({
        url: `/packages/pages/staff_package_edit/staff_package_edit?package_id=${item.package_id}`,
        success: (res) => {
            res.eventChannel.emit('detail', item)
        }
    })
}

onShow(async () => {
    if (!(await ensureStaffCenterAccess())) return
    fetchPackages()
})
</script>

<style lang="scss" scoped>
.page-container {
    min-height: 100vh;
    background: linear-gradient(180deg, rgba(124, 58, 237, 0.05) 0%, #F6F6F6 100%);
}

/* 标签页 */
.tabs-wrapper {
    background: #FFFFFF;
    box-shadow: 0 2rpx 8rpx rgba(0, 0, 0, 0.04);
}

.tabs-container {
    display: flex;
    padding: 0 24rpx;
}

.tab-item {
    position: relative;
    flex: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 28rpx 0;
    color: #666666;
    font-size: 30rpx;
    transition: all 0.2s ease;
    
    &.active {
        font-weight: 600;
    }
}

.tab-text {
    white-space: nowrap;
}

.tab-indicator {
    position: absolute;
    bottom: 0;
    left: 50%;
    transform: translateX(-50%);
    width: 48rpx;
    height: 6rpx;
    border-radius: 3rpx;
}

/* 套餐列表 */
.package-list {
    padding: 24rpx;
}

.package-card {
    margin-bottom: 24rpx;
    padding: 24rpx;
    background: #FFFFFF;
    border-radius: 24rpx;
    box-shadow: 0 2rpx 12rpx rgba(0, 0, 0, 0.06);
    
    &:last-child {
        margin-bottom: 0;
    }
}

/* 套餐头部 */
.package-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding-bottom: 20rpx;
    border-bottom: 1rpx solid #F5F5F5;
    margin-bottom: 20rpx;
}

.package-title {
    display: flex;
    align-items: center;
    gap: 12rpx;
    font-size: 32rpx;
    font-weight: 600;
    color: #333333;
}

.package-status {
    padding: 6rpx 16rpx;
    border-radius: 24rpx;
    font-size: 24rpx;
    font-weight: 500;
}

/* 套餐信息 */
.package-info {
    display: flex;
    flex-direction: column;
    gap: 16rpx;
    margin-bottom: 20rpx;
}

.info-item {
    display: flex;
    align-items: center;
    gap: 12rpx;
}

.info-label {
    font-size: 28rpx;
    color: #666666;
}

.info-value {
    font-size: 28rpx;
    font-weight: 600;
    color: #333333;
    margin-left: auto;
}

/* 操作按钮 */
.package-actions {
    display: flex;
    gap: 16rpx;
}

.action-btn {
    flex: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8rpx;
    height: 72rpx;
    border-radius: 48rpx;
    font-size: 28rpx;
    font-weight: 500;
    transition: all 0.2s ease;
    
    &:active {
        opacity: 0.8;
    }
}

.edit-btn {
    background: transparent;
    border: 2rpx solid;
}

.remove-btn {
    background: transparent;
    color: #FF2C3C;
    border: 2rpx solid #FF2C3C;
}

.add-btn {
    border: none;
    box-shadow: 0 8rpx 24rpx rgba(124, 58, 237, 0.3);
}

/* 空状态 */
.empty-state {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 120rpx 0;
    gap: 16rpx;
}

.empty-text {
    font-size: 28rpx;
    color: #999999;
}

.empty-tip {
    font-size: 24rpx;
    color: #C8C9CC;
}
</style>
