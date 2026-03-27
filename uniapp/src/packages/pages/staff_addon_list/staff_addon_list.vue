<template>
    <page-meta :page-style="$theme.pageStyle" />
    <BaseNavbar title="附加服务管理" />

    <view class="page-container">
        <view
            class="hero-card"
            :style="{
                background: `linear-gradient(145deg, ${$theme.primaryColor} 0%, ${$theme.secondaryColor || $theme.primaryColor} 78%)`
            }"
        >
            <view class="hero-top">
                <view>
                    <text class="hero-title">附加服务工作区</text>
                    <text class="hero-desc">维护加拍、加时、加项等增值内容，提升客单能力</text>
                </view>
                <view class="hero-add-btn" @click="goCreate">
                    <tn-icon name="add" size="28" color="#FFFFFF" />
                    <text>新增服务</text>
                </view>
            </view>

            <view class="hero-stats">
                <view class="hero-stat">
                    <text class="hero-stat-label">总服务</text>
                    <text class="hero-stat-value">{{ addons.length }}</text>
                </view>
                <view class="hero-stat">
                    <text class="hero-stat-label">上架中</text>
                    <text class="hero-stat-value">{{ activeCount }}</text>
                </view>
                <view class="hero-stat">
                    <text class="hero-stat-label">下架中</text>
                    <text class="hero-stat-value">{{ inactiveCount }}</text>
                </view>
            </view>
        </view>

        <view v-if="addons.length" class="addon-list">
            <view v-for="item in addons" :key="item.id" class="addon-card">
                <view class="addon-head">
                    <view class="addon-title-wrap">
                        <text class="addon-title">{{ item.name || '未命名附加服务' }}</text>
                        <text class="addon-category">{{ item.category_name || '分类自动同步' }}</text>
                    </view>
                    <view class="addon-status" :class="{ off: !item.is_show }">
                        {{ item.is_show ? '上架中' : '已下架' }}
                    </view>
                </view>

                <view class="price-row">
                    <text class="price-main">¥{{ formatPrice(item.price) }}</text>
                    <text v-if="Number(item.original_price || 0) > 0" class="price-origin">
                        ¥{{ formatPrice(item.original_price) }}
                    </text>
                </view>

                <view class="meta-row">
                    <view class="meta-chip">
                        排序 {{ item.sort || 0 }}
                    </view>
                    <view class="meta-chip">
                        {{ item.is_show ? '支持售卖' : '暂不售卖' }}
                    </view>
                </view>

                <text v-if="item.description" class="addon-desc">{{ item.description }}</text>

                <view class="action-row">
                    <view class="action-btn action-btn--ghost" @click="handleEdit(item)">
                        <tn-icon name="edit" size="26" :color="$theme.primaryColor" />
                        <text>编辑</text>
                    </view>
                    <view class="action-btn action-btn--toggle" @click="handleToggle(item)">
                        <tn-icon
                            :name="item.is_show ? 'close-circle' : 'check-circle'"
                            size="26"
                            :color="item.is_show ? '#F97316' : '#10B981'"
                        />
                        <text>{{ item.is_show ? '下架' : '上架' }}</text>
                    </view>
                    <view class="action-btn action-btn--danger" @click="handleRemove(item)">
                        <tn-icon name="delete" size="26" color="#FF2C3C" />
                        <text>删除</text>
                    </view>
                </view>
            </view>
        </view>

        <view v-else class="empty-state">
            <tn-icon name="gift" size="120" color="#D1D5DB" />
            <text class="empty-title">还没有附加服务</text>
            <text class="empty-desc">补充可选增值项目，方便客户在下单时灵活组合</text>
            <view class="empty-btn" :style="{ background: $theme.primaryColor }" @click="goCreate">
                立即新增
            </view>
        </view>
    </view>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue'
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

const activeCount = computed(() => addons.value.filter((item) => Number(item.is_show) === 1).length)
const inactiveCount = computed(() => addons.value.filter((item) => Number(item.is_show) !== 1).length)

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

const formatPrice = (value: number | string) => {
    const amount = Number(value || 0)
    return Number.isInteger(amount) ? String(amount) : amount.toFixed(2)
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
    background:
        radial-gradient(circle at top left, rgba(191, 219, 254, 0.72) 0, rgba(246, 248, 252, 0) 36%),
        linear-gradient(180deg, #F6F8FC 0%, #F4F6FB 100%);
}

.hero-card {
    padding: 28rpx;
    border-radius: 30rpx;
    box-shadow: 0 18rpx 36rpx rgba(37, 99, 235, 0.18);
}

.hero-top {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 20rpx;
}

.hero-title {
    display: block;
    font-size: 36rpx;
    font-weight: 700;
    color: #FFFFFF;
}

.hero-desc {
    display: block;
    margin-top: 10rpx;
    font-size: 22rpx;
    line-height: 1.55;
    color: rgba(255, 255, 255, 0.8);
}

.hero-add-btn {
    flex-shrink: 0;
    display: inline-flex;
    align-items: center;
    gap: 8rpx;
    padding: 16rpx 22rpx;
    border-radius: 999rpx;
    background: rgba(255, 255, 255, 0.16);
    font-size: 24rpx;
    font-weight: 600;
    color: #FFFFFF;
}

.hero-stats {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 14rpx;
    margin-top: 26rpx;
}

.hero-stat {
    padding: 20rpx;
    border-radius: 22rpx;
    background: rgba(255, 255, 255, 0.14);
}

.hero-stat-label {
    display: block;
    font-size: 22rpx;
    color: rgba(255, 255, 255, 0.75);
}

.hero-stat-value {
    display: block;
    margin-top: 12rpx;
    font-size: 38rpx;
    font-weight: 800;
    color: #FFFFFF;
}

.addon-list {
    margin-top: 22rpx;
}

.addon-card + .addon-card {
    margin-top: 18rpx;
}

.addon-card {
    padding: 28rpx;
    border-radius: 28rpx;
    background: rgba(255, 255, 255, 0.92);
    border: 1rpx solid rgba(255, 255, 255, 0.72);
    box-shadow: 0 18rpx 30rpx rgba(15, 23, 42, 0.05);
}

.addon-head {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 18rpx;
}

.addon-title-wrap {
    flex: 1;
    min-width: 0;
}

.addon-title {
    display: block;
    font-size: 32rpx;
    font-weight: 700;
    color: #0F172A;
}

.addon-category {
    display: block;
    margin-top: 10rpx;
    font-size: 22rpx;
    color: #94A3B8;
}

.addon-status {
    flex-shrink: 0;
    padding: 10rpx 18rpx;
    border-radius: 999rpx;
    font-size: 22rpx;
    font-weight: 600;
    color: #059669;
    background: rgba(16, 185, 129, 0.12);
}

.addon-status.off {
    color: #64748B;
    background: rgba(148, 163, 184, 0.16);
}

.price-row {
    display: flex;
    align-items: baseline;
    gap: 12rpx;
    margin-top: 22rpx;
}

.price-main {
    font-size: 42rpx;
    font-weight: 800;
    color: #0EA5E9;
}

.price-origin {
    font-size: 24rpx;
    color: #94A3B8;
    text-decoration: line-through;
}

.meta-row {
    display: flex;
    flex-wrap: wrap;
    gap: 12rpx;
    margin-top: 18rpx;
}

.meta-chip {
    padding: 10rpx 16rpx;
    border-radius: 999rpx;
    background: #F8FAFC;
    font-size: 22rpx;
    color: #64748B;
}

.addon-desc {
    display: block;
    margin-top: 18rpx;
    font-size: 24rpx;
    line-height: 1.7;
    color: #475569;
}

.action-row {
    display: flex;
    gap: 14rpx;
    margin-top: 24rpx;
}

.action-btn {
    flex: 1;
    height: 72rpx;
    border-radius: 999rpx;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8rpx;
    font-size: 24rpx;
    font-weight: 600;
}

.action-btn--ghost {
    color: var(--color-primary);
    border: 2rpx solid currentColor;
}

.action-btn--toggle {
    color: #F97316;
    border: 2rpx solid currentColor;
}

.action-btn--danger {
    color: #FF2C3C;
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
    color: #475569;
}

.empty-desc {
    margin-top: 10rpx;
    font-size: 22rpx;
    color: #94A3B8;
}

.empty-btn {
    margin-top: 24rpx;
    padding: 18rpx 42rpx;
    border-radius: 999rpx;
    font-size: 26rpx;
    font-weight: 600;
    color: #FFFFFF;
}
</style>
