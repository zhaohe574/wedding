<template>
    <page-meta :page-style="$theme.pageStyle" />
    <PageShell scene="staff">
        <BaseNavbar title="附加项管理" />

        <view class="staff-resource-page">
            <view class="page-section page-section--top">
                <BaseCard variant="hero" scene="staff" class="hero-card">
                    <view class="hero-card__head">
                        <view class="hero-card__copy">
                            <text class="hero-card__eyebrow">服务人员中心</text>
                            <text class="hero-card__title">附加项管理</text>
                            <text class="hero-card__meta">维护加价服务</text>
                        </view>

                        <BaseButton
                            variant="secondary"
                            size="sm"
                            class="hero-card__action"
                            @click="goCreate"
                        >
                            新增附加项
                        </BaseButton>
                    </view>

                    <view class="hero-metrics">
                        <view
                            v-for="item in heroMetrics"
                            :key="item.label"
                            :class="['hero-metric', { 'hero-metric--accent': item.accent }]"
                        >
                            <text class="hero-metric__label">{{ item.label }}</text>
                            <text class="hero-metric__value">{{ item.value }}</text>
                        </view>
                    </view>
                </BaseCard>
            </view>

            <view class="page-section page-section--list">
                <template v-if="addonList.length">
                    <BaseCard
                        v-for="item in addonList"
                        :key="item.id"
                        variant="glass"
                        scene="staff"
                        class="addon-card"
                    >
                        <view class="addon-card__head">
                            <view class="addon-card__thumb">
                                <image
                                    v-if="item.image"
                                    :src="item.image"
                                    class="addon-card__image"
                                    mode="aspectFill"
                                />
                                <view v-else class="addon-card__image addon-card__image--placeholder">
                                    <tn-icon name="image" size="42" color="#D8CEC8" />
                                </view>
                            </view>

                            <view class="addon-card__main">
                                <view class="addon-card__title-row">
                                    <view class="addon-card__copy">
                                        <text class="addon-card__title">
                                            {{ item.name || '未命名附加项' }}
                                        </text>
                                        <text class="addon-card__category">
                                            {{ item.category_name || '服务分类自动同步' }}
                                        </text>
                                    </view>

                                    <StatusBadge
                                        :tone="Number(item.is_show) === 1 ? 'success' : 'neutral'"
                                        size="sm"
                                    >
                                        {{ Number(item.is_show) === 1 ? '上架中' : '已下架' }}
                                    </StatusBadge>
                                </view>

                                <view class="price-row">
                                    <text class="price-row__prefix">+¥</text>
                                    <text class="price-row__value">{{ formatPrice(item.price) }}</text>
                                </view>

                                <view class="chip-row">
                                    <view class="info-chip">
                                        排序 {{ Number(item.sort || 0) }}
                                    </view>
                                    <view v-if="item.image" class="info-chip info-chip--accent">
                                        含图片
                                    </view>
                                </view>
                            </view>
                        </view>

                        <text v-if="item.description" class="addon-card__desc">
                            {{ item.description }}
                        </text>

                        <view class="action-row">
                            <view class="action-btn action-btn--ghost" @click="handleEdit(item)">
                                编辑
                            </view>
                            <view class="action-btn action-btn--danger" @click="handleRemove(item)">
                                删除
                            </view>
                        </view>
                    </BaseCard>
                </template>

                <EmptyState
                    v-else
                    title="还没有附加项"
                    action-text="新增附加项"
                    @action="goCreate"
                />
            </view>
        </view>
    </PageShell>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue'
import { onShow } from '@dcloudio/uni-app'
import BaseButton from '@/components/base/BaseButton.vue'
import BaseCard from '@/components/base/BaseCard.vue'
import BaseNavbar from '@/components/base/BaseNavbar.vue'
import EmptyState from '@/components/base/EmptyState.vue'
import PageShell from '@/components/base/PageShell.vue'
import StatusBadge from '@/components/base/StatusBadge.vue'
import { staffCenterAddonLists, staffCenterAddonRemove } from '@/api/staffCenter'
import { useThemeStore } from '@/stores/theme'
import { ensureStaffCenterAccess } from '@/utils/staff-center'

interface HeroMetric {
    label: string
    value: number
    accent: boolean
}

const $theme = useThemeStore()
const addonList = ref<any[]>([])

const visibleCount = computed(() =>
    addonList.value.filter((item) => Number(item.is_show) === 1).length
)
const imageCount = computed(() =>
    addonList.value.filter((item) => String(item.image || '').trim()).length
)
const heroMetrics = computed<HeroMetric[]>(() => [
    { label: '总数量', value: addonList.value.length, accent: false },
    { label: '上架中', value: visibleCount.value, accent: true },
    { label: '带图片', value: imageCount.value, accent: false }
])

const fetchAddonList = async () => {
    try {
        const data = await staffCenterAddonLists()
        addonList.value = Array.isArray(data) ? data : []
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

const handleRemove = (item: any) => {
    uni.showModal({
        title: '确认删除',
        content: `确定删除附加项“${item.name || ''}”吗？`,
        success: async (res) => {
            if (!res.confirm) return
            try {
                await staffCenterAddonRemove({ addon_id: item.id })
                uni.showToast({ title: '删除成功', icon: 'success' })
                fetchAddonList()
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
    fetchAddonList()
})
</script>

<style lang="scss" scoped>
.staff-resource-page {
    min-height: 100vh;
    padding-top: 20rpx;
    box-sizing: border-box;
    background:
        radial-gradient(circle at top left, rgba(232, 90, 79, 0.1) 0, rgba(252, 251, 249, 0) 36%),
        linear-gradient(180deg, var(--wm-color-bg-page, #fcfbf9) 0%, #f7f1ed 100%);
}

.page-section {
    display: flex;
    flex-direction: column;
    gap: 16rpx;
    padding: 0 var(--wm-space-page-x, 37rpx);
    box-sizing: border-box;

    &--list {
        padding-top: 18rpx;
        padding-bottom: calc(48rpx + env(safe-area-inset-bottom));
    }
}

.hero-card {
    overflow: hidden;
}

.hero-card__head {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 20rpx;
}

.hero-card__copy {
    flex: 1;
    min-width: 0;
}

.hero-card__eyebrow {
    font-size: 20rpx;
    font-weight: 700;
    line-height: 1.2;
    color: var(--wm-color-primary, #e85a4f);
}

.hero-card__title {
    display: block;
    margin-top: 10rpx;
    font-size: 40rpx;
    font-weight: 700;
    line-height: 1.28;
    color: var(--wm-text-primary, #1e2432);
}

.hero-card__meta {
    display: block;
    margin-top: 8rpx;
    font-size: 24rpx;
    font-weight: 600;
    line-height: 1.45;
    color: var(--wm-text-secondary, #7f7b78);
}

.hero-card__action {
    flex-shrink: 0;
}

.hero-card__action :deep(.tn-button) {
    background: rgba(255, 255, 255, 0.84);
    border-color: rgba(255, 255, 255, 0.72);
}

.hero-metrics {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 12rpx;
    margin-top: 22rpx;
}

.hero-metric {
    display: flex;
    flex-direction: column;
    gap: 8rpx;
    min-width: 0;
    padding: 18rpx 20rpx;
    border-radius: 30rpx;
    background: rgba(255, 255, 255, 0.76);
    border: 1rpx solid var(--wm-color-border, #efe6e1);

    &--accent {
        background: var(--wm-color-primary-soft, #fff1ee);
        border-color: var(--wm-color-border-strong, #f4c7bf);
    }
}

.hero-metric__label {
    font-size: 21rpx;
    font-weight: 700;
    line-height: 1.3;
    color: var(--wm-text-secondary, #7f7b78);
}

.hero-metric--accent .hero-metric__label {
    color: var(--wm-color-primary, #e85a4f);
}

.hero-metric__value {
    font-size: 40rpx;
    font-weight: 700;
    line-height: 1;
    color: var(--wm-text-primary, #1e2432);
}

.addon-card + .addon-card {
    margin-top: 18rpx;
}

.addon-card__head {
    display: flex;
    gap: 18rpx;
}

.addon-card__thumb {
    flex-shrink: 0;
}

.addon-card__image {
    width: 144rpx;
    height: 144rpx;
    display: block;
    border-radius: 30rpx;
    background: #f7f1ed;
}

.addon-card__image--placeholder {
    display: flex;
    align-items: center;
    justify-content: center;
    border: 1rpx dashed var(--wm-color-border-strong, #f4c7bf);
    background: rgba(255, 241, 238, 0.64);
}

.addon-card__main {
    flex: 1;
    min-width: 0;
}

.addon-card__title-row {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 16rpx;
}

.addon-card__copy {
    flex: 1;
    min-width: 0;
}

.addon-card__title {
    display: block;
    font-size: 32rpx;
    font-weight: 700;
    line-height: 1.35;
    color: var(--wm-text-primary, #1e2432);
    word-break: break-all;
}

.addon-card__category {
    display: block;
    margin-top: 10rpx;
    font-size: 22rpx;
    font-weight: 600;
    line-height: 1.45;
    color: var(--wm-text-secondary, #7f7b78);
}

.price-row {
    display: flex;
    align-items: baseline;
    gap: 6rpx;
    margin-top: 18rpx;
}

.price-row__prefix,
.price-row__value {
    color: var(--wm-color-primary, #e85a4f);
    line-height: 1;
}

.price-row__prefix {
    font-size: 28rpx;
    font-weight: 700;
}

.price-row__value {
    font-size: 42rpx;
    font-weight: 700;
}

.chip-row {
    display: flex;
    flex-wrap: wrap;
    gap: 12rpx;
    margin-top: 16rpx;
}

.info-chip {
    display: inline-flex;
    align-items: center;
    min-height: 48rpx;
    padding: 0 16rpx;
    border-radius: var(--wm-radius-pill, 999rpx);
    background: rgba(255, 255, 255, 0.74);
    border: 1rpx solid var(--wm-color-border, #efe6e1);
    font-size: 22rpx;
    font-weight: 600;
    color: var(--wm-text-secondary, #7f7b78);

    &--accent {
        background: var(--wm-color-primary-soft, #fff1ee);
        border-color: var(--wm-color-border-strong, #f4c7bf);
        color: var(--wm-color-primary, #e85a4f);
    }
}

.addon-card__desc {
    display: -webkit-box;
    margin-top: 18rpx;
    overflow: hidden;
    font-size: 24rpx;
    font-weight: 600;
    line-height: 1.6;
    color: var(--wm-text-secondary, #7f7b78);
    text-overflow: ellipsis;
    word-break: break-all;
    -webkit-box-orient: vertical;
    -webkit-line-clamp: 2;
}

.action-row {
    display: flex;
    gap: 14rpx;
    margin-top: 22rpx;
}

.action-btn {
    flex: 1;
    min-height: 72rpx;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: var(--wm-radius-pill, 999rpx);
    font-size: 26rpx;
    font-weight: 600;
    transition: all var(--wm-motion-base, 220ms) ease;

    &:active {
        transform: translateY(2rpx);
        opacity: 0.92;
    }

    &--ghost {
        color: var(--wm-color-primary, #e85a4f);
        background: rgba(255, 255, 255, 0.7);
        border: 1rpx solid rgba(232, 90, 79, 0.18);
    }

    &--danger {
        color: var(--wm-color-danger, #b44a3a);
        background: rgba(180, 74, 58, 0.08);
        border: 1rpx solid rgba(180, 74, 58, 0.12);
    }
}
</style>
