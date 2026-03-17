<template>
    <page-meta :page-style="$theme.pageStyle">
        <navigation-bar
            title="附加服务管理"
            :front-color="$theme.navColor"
            :background-color="$theme.navBgColor"
        />
    </page-meta>

    <view class="page-container">
        <view class="header-card">
            <view>
                <text class="header-title">我的附加服务</text>
                <text class="header-desc">仅维护本人附加服务，分类会随本人服务分类自动同步。</text>
            </view>
            <view class="header-btn" :style="{ background: $theme.primaryColor }" @click="goCreate">
                <tn-icon name="add" size="28" color="#FFFFFF" />
                <text>新增</text>
            </view>
        </view>

        <view v-if="addons.length" class="addon-list">
            <view v-for="item in addons" :key="item.id" class="addon-card">
                <view class="addon-header">
                    <view class="addon-title-wrap">
                        <text class="addon-title">{{ item.name || '未命名附加服务' }}</text>
                    </view>
                    <view class="addon-status" :class="{ off: !item.is_show }">
                        {{ item.is_show ? '上架' : '下架' }}
                    </view>
                </view>

                <view class="addon-price-row">
                    <text class="price-main">¥{{ item.price || 0 }}</text>
                    <text v-if="Number(item.original_price || 0) > 0" class="price-origin">
                        ¥{{ item.original_price }}
                    </text>
                </view>

                <view class="addon-meta">
                    <text>排序：{{ item.sort || 0 }}</text>
                    <text>{{ item.category_name || '分类自动同步' }}</text>
                </view>

                <view v-if="item.description" class="addon-desc">{{ item.description }}</view>

                <view class="addon-actions">
                    <view class="action-btn edit" @click="handleEdit(item)">
                        <tn-icon name="edit" size="26" :color="$theme.primaryColor" />
                        <text>编辑</text>
                    </view>
                    <view class="action-btn toggle" @click="handleToggle(item)">
                        <tn-icon
                            :name="item.is_show ? 'close-circle' : 'check-circle'"
                            size="26"
                            :color="item.is_show ? '#F97316' : '#19BE6B'"
                        />
                        <text>{{ item.is_show ? '下架' : '上架' }}</text>
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
            <text class="empty-title">暂无附加服务</text>
            <text class="empty-desc">创建后可用于订单确认与后续附加服务变更。</text>
        </view>
    </view>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import { onShow } from '@dcloudio/uni-app'
import {
    staffCenterAddonLists,
    staffCenterAddonRemove,
    staffCenterAddonUpdate
} from '@/api/staffCenter'
import { ensureStaffCenterAccess } from '@/utils/staff-center'
import { useThemeStore } from '@/stores/theme'

const $theme = useThemeStore()
const addons = ref<any[]>([])

const fetchAddons = async () => {
    try {
        const data = await staffCenterAddonLists()
        addons.value = Array.isArray(data) ? data : []
    } catch (e: any) {
        const msg = typeof e === 'string' ? e : e?.msg || e?.message || '加载失败'
        uni.showToast({ title: msg, icon: 'none' })
    }
}

const goCreate = () => {
    uni.navigateTo({
        url: '/packages/pages/staff_addon_edit/staff_addon_edit'
    })
}

const handleEdit = (item: any) => {
    uni.navigateTo({
        url: `/packages/pages/staff_addon_edit/staff_addon_edit?addon_id=${item.id}`,
        success: (res) => {
            res.eventChannel.emit('detail', item)
        }
    })
}

const handleToggle = async (item: any) => {
    try {
        await staffCenterAddonUpdate({
            addon_id: item.id,
            name: item.name,
            price: Number(item.price || 0),
            original_price: Number(item.original_price || 0),
            image: item.image || '',
            description: item.description || '',
            sort: Number(item.sort || 0),
            is_show: Number(item.is_show ? 0 : 1)
        })
        uni.showToast({ title: item.is_show ? '已下架' : '已上架', icon: 'success' })
        fetchAddons()
    } catch (e: any) {
        const msg = typeof e === 'string' ? e : e?.msg || e?.message || '操作失败'
        uni.showToast({ title: msg, icon: 'none' })
    }
}

const handleRemove = (item: any) => {
    uni.showModal({
        title: '确认删除',
        content: `确定删除附加服务“${item.name || ''}”吗？`,
        success: async (res) => {
            if (!res.confirm) return
            try {
                await staffCenterAddonRemove({ addon_id: item.id })
                uni.showToast({ title: '删除成功', icon: 'success' })
                fetchAddons()
            } catch (e: any) {
                const msg = typeof e === 'string' ? e : e?.msg || e?.message || '删除失败'
                uni.showToast({ title: msg, icon: 'none' })
            }
        }
    })
}

onShow(async () => {
    if (!(await ensureStaffCenterAccess())) return
    fetchAddons()
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

.addon-list {
    margin-top: 24rpx;
    display: flex;
    flex-direction: column;
    gap: 20rpx;
}

.addon-card {
    padding: 24rpx;
    background: #ffffff;
    border-radius: 24rpx;
    box-shadow: 0 4rpx 24rpx rgba(0, 0, 0, 0.05);
}

.addon-header {
    display: flex;
    justify-content: space-between;
    gap: 24rpx;
}

.addon-title-wrap {
    flex: 1;
    min-width: 0;
}

.addon-title {
    display: block;
    font-size: 30rpx;
    font-weight: 700;
    color: #111827;
}

.addon-status {
    align-self: flex-start;
    padding: 8rpx 18rpx;
    border-radius: 999rpx;
    font-size: 22rpx;
    color: #059669;
    background: rgba(5, 150, 105, 0.12);
}

.addon-status.off {
    color: #6b7280;
    background: rgba(107, 114, 128, 0.12);
}

.addon-price-row {
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

.addon-meta {
    display: flex;
    gap: 20rpx;
    margin-top: 12rpx;
    font-size: 22rpx;
    color: #6b7280;
}

.addon-desc {
    margin-top: 18rpx;
    font-size: 24rpx;
    line-height: 1.7;
    color: #4b5563;
}

.addon-actions {
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

.action-btn.toggle {
    color: #f97316;
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
