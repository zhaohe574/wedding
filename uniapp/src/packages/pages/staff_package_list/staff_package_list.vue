<template>
    <page-meta :page-style="$theme.pageStyle" />
    <BaseNavbar title="套餐管理" />

    <view class="page-container">
        <view
            class="hero-card"
            :style="{
                background: `linear-gradient(145deg, ${$theme.primaryColor} 0%, ${$theme.secondaryColor || $theme.primaryColor} 78%)`
            }"
        >
            <view class="hero-top">
                <view>
                    <text class="hero-title">套餐工作区</text>
                    <text class="hero-desc">集中维护服务组合、价格展示和推荐策略</text>
                </view>
                <view class="hero-add-btn" @click="goCreate">
                    <tn-icon name="add" size="28" color="#FFFFFF" />
                    <text>新增套餐</text>
                </view>
            </view>

            <view class="hero-stats">
                <view class="hero-stat">
                    <text class="hero-stat-label">总套餐</text>
                    <text class="hero-stat-value">{{ packages.length }}</text>
                </view>
                <view class="hero-stat">
                    <text class="hero-stat-label">上架中</text>
                    <text class="hero-stat-value">{{ activeCount }}</text>
                </view>
                <view class="hero-stat">
                    <text class="hero-stat-label">推荐款</text>
                    <text class="hero-stat-value">{{ recommendCount }}</text>
                </view>
            </view>
        </view>

        <view v-if="packages.length" class="package-list">
            <view v-for="item in packages" :key="item.id" class="package-card">
                <view class="package-head">
                    <view class="package-title-wrap">
                        <text class="package-title">{{ item.name || '未命名套餐' }}</text>
                        <text class="package-category">{{ item.category_name || '服务分类自动同步' }}</text>
                    </view>
                    <view class="package-status" :class="{ off: !item.is_show }">
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
                    <view class="meta-chip" :class="{ recommend: !!item.is_recommend }">
                        {{ item.is_recommend ? '推荐套餐' : '普通套餐' }}
                    </view>
                </view>

                <text v-if="item.description" class="package-desc">{{ item.description }}</text>

                <view class="action-row">
                    <view class="action-btn action-btn--ghost" @click="handleEdit(item)">
                        <tn-icon name="edit" size="26" :color="$theme.primaryColor" />
                        <text>编辑</text>
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
            <text class="empty-title">还没有套餐内容</text>
            <text class="empty-desc">先创建一个可售套餐，让客户能快速下单</text>
            <view class="empty-btn" :style="{ background: $theme.primaryColor }" @click="goCreate">
                立即新增
            </view>
        </view>
    </view>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue'
import { onShow } from '@dcloudio/uni-app'
import { staffCenterPackageLists, staffCenterPackageRemove } from '@/api/staffCenter'
import { ensureStaffCenterAccess } from '@/utils/staff-center'
import { useThemeStore } from '@/stores/theme'

const $theme = useThemeStore()
const packages = ref<any[]>([])

const activeCount = computed(() => packages.value.filter((item) => Number(item.is_show) === 1).length)
const recommendCount = computed(() =>
    packages.value.filter((item) => Number(item.is_recommend) === 1).length
)

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

const formatPrice = (value: number | string) => {
    const amount = Number(value || 0)
    return Number.isInteger(amount) ? String(amount) : amount.toFixed(2)
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

.package-list {
    margin-top: 22rpx;
}

.package-card + .package-card {
    margin-top: 18rpx;
}

.package-card {
    padding: 28rpx;
    border-radius: 28rpx;
    background: rgba(255, 255, 255, 0.92);
    border: 1rpx solid rgba(255, 255, 255, 0.72);
    box-shadow: 0 18rpx 30rpx rgba(15, 23, 42, 0.05);
}

.package-head {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 18rpx;
}

.package-title-wrap {
    flex: 1;
    min-width: 0;
}

.package-title {
    display: block;
    font-size: 32rpx;
    font-weight: 700;
    color: #0F172A;
}

.package-category {
    display: block;
    margin-top: 10rpx;
    font-size: 22rpx;
    color: #94A3B8;
}

.package-status {
    flex-shrink: 0;
    padding: 10rpx 18rpx;
    border-radius: 999rpx;
    font-size: 22rpx;
    font-weight: 600;
    color: #059669;
    background: rgba(16, 185, 129, 0.12);
}

.package-status.off {
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
    color: #F97316;
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

.meta-chip.recommend {
    color: #D97706;
    background: rgba(245, 158, 11, 0.12);
}

.package-desc {
    display: block;
    margin-top: 18rpx;
    font-size: 24rpx;
    line-height: 1.7;
    color: #475569;
}

.action-row {
    display: flex;
    gap: 16rpx;
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
    font-size: 26rpx;
    font-weight: 600;
}

.action-btn--ghost {
    color: var(--color-primary);
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
