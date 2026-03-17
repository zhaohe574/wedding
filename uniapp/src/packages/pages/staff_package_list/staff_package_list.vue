<template>
    <page-meta :page-style="$theme.pageStyle">
        <navigation-bar
            title="套餐管理"
            :front-color="$theme.navColor"
            :background-color="$theme.navBgColor"
        />
    </page-meta>

    <view class="page-container">
        <view class="header-card">
            <view>
                <text class="header-title">我的套餐</text>
                <text class="header-desc">直接维护本人套餐，价格按固定价展示。</text>
            </view>
            <view class="header-btn" :style="{ background: $theme.primaryColor }" @click="goCreate">
                <tn-icon name="add" size="28" color="#FFFFFF" />
                <text>新增</text>
            </view>
        </view>

        <view v-if="packages.length" class="package-list">
            <view v-for="item in packages" :key="item.id" class="package-card">
                <view class="package-header">
                    <view class="package-title-wrap">
                        <text class="package-title">{{ item.name || '未命名套餐' }}</text>
                    </view>
                    <view class="package-status" :class="{ off: !item.is_show }">
                        {{ item.is_show ? '上架' : '下架' }}
                    </view>
                </view>

                <view class="package-price-row">
                    <text class="price-main">¥{{ item.price || 0 }}</text>
                    <text v-if="Number(item.original_price || 0) > 0" class="price-origin">
                        ¥{{ item.original_price }}
                    </text>
                </view>

                <view class="package-meta">
                    <text>排序：{{ item.sort || 0 }}</text>
                    <text>{{ item.is_recommend ? '推荐套餐' : '普通套餐' }}</text>
                </view>

                <view v-if="item.description" class="package-desc">{{ item.description }}</view>

                <view class="package-actions">
                    <view class="action-btn edit" @click="handleEdit(item)">
                        <tn-icon name="edit" size="26" :color="$theme.primaryColor" />
                        <text>编辑</text>
                    </view>
                    <view class="action-btn remove" @click="handleRemove(item)">
                        <tn-icon name="delete" size="26" color="#FF2C3C" />
                        <text>删除</text>
                    </view>
                </view>
            </view>
        </view>

        <view v-else class="empty-state">
            <tn-icon name="gift" size="120" color="#E5E7EB" />
            <text class="empty-title">暂无套餐</text>
            <text class="empty-desc">创建后将在预约页供用户选择。</text>
        </view>
    </view>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import { onShow } from '@dcloudio/uni-app'
import {
    staffCenterPackageLists,
    staffCenterPackageRemove
} from '@/api/staffCenter'
import { ensureStaffCenterAccess } from '@/utils/staff-center'
import { useThemeStore } from '@/stores/theme'

const $theme = useThemeStore()
const packages = ref<any[]>([])

const fetchPackages = async () => {
    try {
        const data = await staffCenterPackageLists()
        packages.value = Array.isArray(data) ? data : []
    } catch (e: any) {
        const msg = typeof e === 'string' ? e : e?.msg || e?.message || '加载失败'
        uni.showToast({ title: msg, icon: 'none' })
    }
}

const goCreate = () => {
    uni.navigateTo({
        url: '/packages/pages/staff_package_edit/staff_package_edit'
    })
}

const handleEdit = (item: any) => {
    uni.navigateTo({
        url: `/packages/pages/staff_package_edit/staff_package_edit?package_id=${item.id}`,
        success: (res) => {
            res.eventChannel.emit('detail', item)
        }
    })
}

const handleRemove = (item: any) => {
    uni.showModal({
        title: '确认删除',
        content: `确定删除套餐“${item.name || ''}”吗？`,
        success: async (res) => {
            if (!res.confirm) return
            try {
                await staffCenterPackageRemove({ package_id: item.id })
                uni.showToast({ title: '删除成功', icon: 'success' })
                fetchPackages()
            } catch (e: any) {
                const msg = typeof e === 'string' ? e : e?.msg || e?.message || '删除失败'
                uni.showToast({ title: msg, icon: 'none' })
            }
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
    padding: 24rpx;
    background: #f4f5f7;
}

.header-card {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 24rpx;
    padding: 28rpx;
    background: #ffffff;
    border-radius: 24rpx;
    box-shadow: 0 4rpx 24rpx rgba(0, 0, 0, 0.05);
}

.header-title {
    display: block;
    font-size: 34rpx;
    font-weight: 700;
    color: #1f2937;
}

.header-desc {
    display: block;
    margin-top: 10rpx;
    font-size: 24rpx;
    color: #9ca3af;
}

.header-btn {
    display: flex;
    align-items: center;
    gap: 8rpx;
    padding: 18rpx 28rpx;
    border-radius: 999rpx;
    color: #ffffff;
    font-size: 26rpx;
    font-weight: 600;
}

.package-list {
    margin-top: 24rpx;
    display: flex;
    flex-direction: column;
    gap: 20rpx;
}

.package-card {
    padding: 24rpx;
    background: #ffffff;
    border-radius: 24rpx;
    box-shadow: 0 4rpx 24rpx rgba(0, 0, 0, 0.05);
}

.package-header {
    display: flex;
    justify-content: space-between;
    gap: 24rpx;
}

.package-title-wrap {
    flex: 1;
    min-width: 0;
}

.package-title {
    display: block;
    font-size: 30rpx;
    font-weight: 700;
    color: #111827;
}

.package-status {
    align-self: flex-start;
    padding: 8rpx 18rpx;
    border-radius: 999rpx;
    font-size: 22rpx;
    color: #059669;
    background: rgba(5, 150, 105, 0.12);
}

.package-status.off {
    color: #6b7280;
    background: rgba(107, 114, 128, 0.12);
}

.package-price-row {
    display: flex;
    align-items: baseline;
    gap: 12rpx;
    margin-top: 20rpx;
}

.price-main {
    font-size: 40rpx;
    font-weight: 700;
    color: #ef4444;
}

.price-origin {
    font-size: 24rpx;
    color: #9ca3af;
    text-decoration: line-through;
}

.package-meta {
    display: flex;
    gap: 20rpx;
    margin-top: 12rpx;
    font-size: 22rpx;
    color: #6b7280;
}

.package-desc {
    margin-top: 18rpx;
    font-size: 24rpx;
    line-height: 1.7;
    color: #4b5563;
}

.package-actions {
    display: flex;
    gap: 16rpx;
    margin-top: 24rpx;
}

.action-btn {
    flex: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8rpx;
    height: 72rpx;
    border-radius: 999rpx;
    font-size: 26rpx;
    font-weight: 600;
}

.action-btn.edit {
    color: var(--color-primary);
    border: 2rpx solid currentColor;
}

.action-btn.remove {
    color: #ff2c3c;
    border: 2rpx solid currentColor;
}

.empty-state {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 180rpx 0;
}

.empty-title {
    margin-top: 24rpx;
    font-size: 30rpx;
    font-weight: 600;
    color: #6b7280;
}

.empty-desc {
    margin-top: 12rpx;
    font-size: 24rpx;
    color: #9ca3af;
}
</style>
