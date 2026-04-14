<template>
    <page-meta :page-style="$theme.pageStyle" />
    <PageShell scene="staff">
        <BaseNavbar title="附加项管理" />

        <view class="page-container wm-page-content">
            <z-paging
                ref="pagingRef"
                v-model="addonList"
                :auto="false"
                :hide-empty-view="true"
                :paging-style="pagingStyle"
                @query="queryList"
            >
                <template #top>
                    <view class="page-section page-section--top">
                        <BaseCard variant="hero" scene="staff" class="hero-card">
                            <view class="hero-card__head">
                                <view class="hero-card__copy">
                                    <text class="hero-card__eyebrow">服务人员中心</text>
                                    <text class="hero-card__title">附加项管理</text>
                                </view>

                                <view class="hero-card__action" @click="goCreate">
                                    <text class="hero-card__action-text">新增附加项</text>
                                </view>
                            </view>

                            <view class="hero-metrics">
                                <view
                                    v-for="item in heroMetrics"
                                    :key="item.key"
                                    :class="[
                                        'hero-metric',
                                        'wm-soft-card',
                                        {
                                            'hero-metric--selected':
                                                currentMetricFilter === item.key
                                        }
                                    ]"
                                    @click="switchMetricFilter(item.key)"
                                >
                                    <text class="hero-metric__label">{{ item.label }}</text>
                                    <text class="hero-metric__value">{{ item.value }}</text>
                                </view>
                            </view>
                        </BaseCard>
                    </view>
                </template>

                <view class="page-section page-section--list">
                    <LoadingState v-if="loading && !hasLoaded" text="加载附加项中..." />

                    <template v-else-if="addonList.length">
                        <BaseCard
                            v-for="item in addonList"
                            :key="item.id"
                            variant="glass"
                            scene="staff"
                            class="addon-card"
                            interactive
                            @click="handleEdit(item)"
                        >
                            <view class="addon-card__media">
                                <image
                                    v-if="item.image"
                                    class="addon-card__image"
                                    :src="item.image"
                                    mode="aspectFill"
                                />
                                <view
                                    v-else
                                    class="addon-card__image addon-card__image--placeholder"
                                >
                                    <view class="addon-card__placeholder-mark">
                                        <tn-icon name="image" size="42" color="#D8CEC8" />
                                    </view>
                                </view>

                                <StatusBadge
                                    class="addon-card__status"
                                    :tone="Number(item.is_show) === 1 ? 'success' : 'neutral'"
                                    size="sm"
                                >
                                    {{ Number(item.is_show) === 1 ? '上架中' : '已下架' }}
                                </StatusBadge>
                            </view>

                            <view class="addon-card__body">
                                <view class="addon-card__head">
                                    <view class="addon-card__copy">
                                        <text class="addon-card__title">
                                            {{ item.name || '未命名附加项' }}
                                        </text>
                                    </view>
                                </view>

                                <view class="price-row">
                                    <text class="price-row__prefix">+¥</text>
                                    <text class="price-row__value">{{
                                        formatPrice(item.price)
                                    }}</text>
                                    <text
                                        v-if="Number(item.original_price || 0) > 0"
                                        class="price-row__origin"
                                    >
                                        ¥{{ formatPrice(item.original_price) }}
                                    </text>
                                </view>

                                <view class="chip-row">
                                    <view class="info-chip">
                                        {{ item.category_name || '未分类' }}
                                    </view>
                                    <view class="info-chip">排序 {{ Number(item.sort || 0) }}</view>
                                </view>

                                <text v-if="item.description" class="addon-card__desc">
                                    {{ item.description }}
                                </text>

                                <view class="action-row">
                                    <view
                                        class="action-btn action-btn--ghost"
                                        @click.stop="handleEdit(item)"
                                    >
                                        编辑
                                    </view>
                                    <view
                                        class="action-btn action-btn--danger"
                                        @click.stop="handleRemove(item)"
                                    >
                                        删除
                                    </view>
                                </view>
                            </view>
                        </BaseCard>
                    </template>

                    <EmptyState
                        v-else-if="hasLoaded"
                        :title="emptyStateTitle"
                        description="附加项的图片、价格和上下架状态，会在这里统一维护。"
                        action-text="新增附加项"
                        @action="goCreate"
                    />
                </view>
            </z-paging>
        </view>
    </PageShell>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue'
import { onShow } from '@dcloudio/uni-app'
import BaseCard from '@/components/base/BaseCard.vue'
import EmptyState from '@/components/base/EmptyState.vue'
import BaseNavbar from '@/components/base/BaseNavbar.vue'
import LoadingState from '@/components/base/LoadingState.vue'
import PageShell from '@/components/base/PageShell.vue'
import StatusBadge from '@/components/base/StatusBadge.vue'
import { staffCenterAddonLists, staffCenterAddonRemove } from '@/api/staffCenter'
import { useFixedNavbarPagingStyle } from '@/packages/common/hooks/useFixedNavbarPagingStyle'
import { useThemeStore } from '@/stores/theme'
import { ensureStaffCenterAccess } from '@/packages/common/utils/staff-center'

type AddonMetricFilter = 'all' | 'visible' | 'hidden'

interface HeroMetric {
    key: AddonMetricFilter
    label: string
    value: number
}

const $theme = useThemeStore()
const pagingStyle = useFixedNavbarPagingStyle()
const pagingRef = ref<any>(null)
const addonList = ref<any[]>([])
const loading = ref(false)
const hasLoaded = ref(false)
const currentMetricFilter = ref<AddonMetricFilter>('all')
const metricCounts = ref<Record<AddonMetricFilter, number>>({
    all: 0,
    visible: 0,
    hidden: 0
})

const heroMetrics = computed<HeroMetric[]>(() => [
    { key: 'all', label: '全部', value: metricCounts.value.all },
    { key: 'visible', label: '上架中', value: metricCounts.value.visible },
    { key: 'hidden', label: '已下架', value: metricCounts.value.hidden }
])

const emptyStateTitle = computed(() => {
    const titleMap: Record<AddonMetricFilter, string> = {
        all: '还没有附加项',
        visible: '当前筛选下暂无上架附加项',
        hidden: '当前筛选下暂无已下架附加项'
    }
    return titleMap[currentMetricFilter.value]
})

const getListParams = (pageNo: number, pageSize: number) => {
    const params: Record<string, number> = {
        page_size: pageSize
    }
    if (pageNo > 1) {
        params.page_no = pageNo
    }
    if (currentMetricFilter.value === 'visible') {
        params.is_show = 1
    } else if (currentMetricFilter.value === 'hidden') {
        params.is_show = 0
    }
    return params
}

const refreshMetricCounts = async () => {
    const [allRes, visibleRes, hiddenRes] = await Promise.all([
        staffCenterAddonLists({ page_size: 1 }),
        staffCenterAddonLists({ is_show: 1, page_size: 1 }),
        staffCenterAddonLists({ is_show: 0, page_size: 1 })
    ])
    metricCounts.value = {
        all: Number(allRes?.total || 0),
        visible: Number(visibleRes?.total || 0),
        hidden: Number(hiddenRes?.total || 0)
    }
}

const queryList = async (pageNo: number, pageSize: number) => {
    if (pageNo === 1) {
        loading.value = true
    }

    try {
        const data = await staffCenterAddonLists(getListParams(pageNo, pageSize))
        if (pageNo === 1) {
            await refreshMetricCounts()
        }
        pagingRef.value?.complete(Array.isArray(data?.data) ? data.data : [])
    } catch (e: any) {
        const msg = typeof e === 'string' ? e : e?.msg || e?.message || '加载失败'
        uni.showToast({ title: msg, icon: 'none' })
        pagingRef.value?.complete(false)
    } finally {
        if (pageNo === 1) {
            loading.value = false
        }
        hasLoaded.value = true
    }
}

const switchMetricFilter = (filter: AddonMetricFilter) => {
    if (currentMetricFilter.value === filter) {
        return
    }
    currentMetricFilter.value = filter
    hasLoaded.value = false
    pagingRef.value?.reload()
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
                hasLoaded.value = false
                pagingRef.value?.reload()
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
    hasLoaded.value = false
    pagingRef.value?.reload()
})
</script>

<style lang="scss" scoped>
.page-container {
    min-height: 100vh;
    padding-top: 20rpx;
    box-sizing: border-box;
    background: transparent;
}

.page-section {
    display: flex;
    flex-direction: column;
    gap: 16rpx;
    box-sizing: border-box;

    &--top {
        padding-top: 20rpx;
    }

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

.hero-card__action {
    flex-shrink: 0;
    min-height: 56rpx;
    padding: 0 20rpx;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: var(--wm-radius-pill, 999rpx);
    background: rgba(255, 255, 255, 0.92);
    border: 1rpx solid rgba(244, 199, 191, 0.88);
    box-shadow: inset 0 1rpx 0 rgba(255, 255, 255, 0.7), 0 8rpx 18rpx rgba(177, 108, 95, 0.08);
    backdrop-filter: blur(14rpx);
    -webkit-backdrop-filter: blur(14rpx);
    transition: all var(--wm-motion-base, 220ms) ease;

    &:active {
        transform: translateY(2rpx);
        opacity: 0.92;
    }
}

.hero-card__action-text {
    font-size: 22rpx;
    line-height: 1;
    font-weight: 700;
    letter-spacing: 0.2rpx;
    color: var(--wm-color-primary, #e85a4f);
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
    transition: all var(--wm-motion-base, 220ms) ease;
    cursor: pointer;

    &:active {
        transform: translateY(2rpx);
        opacity: 0.92;
    }

    &--selected {
        background: var(--wm-color-primary-soft, #fff1ee);
        border-color: var(--wm-color-border-strong, #f4c7bf);
        box-shadow: 0 12rpx 24rpx rgba(232, 90, 79, 0.12);
    }
}

.hero-metric__label {
    font-size: 21rpx;
    font-weight: 700;
    line-height: 1.3;
    color: var(--wm-text-secondary, #7f7b78);
}

.hero-metric--selected .hero-metric__label {
    color: var(--wm-color-primary, #e85a4f);
}

.hero-metric__value {
    font-size: 40rpx;
    font-weight: 700;
    line-height: 1;
    color: var(--wm-text-primary, #1e2432);
}

.hero-metric--selected .hero-metric__value {
    color: var(--wm-color-primary, #e85a4f);
}

.addon-card + .addon-card {
    margin-top: 18rpx;
}

.addon-card__media {
    position: relative;
    overflow: hidden;
    border-radius: 32rpx;
    background: rgba(255, 255, 255, 0.72);
}

.addon-card__image {
    width: 100%;
    height: 248rpx;
    display: block;
    background: #f7f1ed;

    &--placeholder {
        display: flex;
        align-items: center;
        justify-content: center;
        background: linear-gradient(
                135deg,
                rgba(255, 241, 238, 0.92) 0%,
                rgba(248, 239, 231, 0.88) 100%
            ),
            #f7f1ed;
    }
}

.addon-card__placeholder-mark {
    width: 112rpx;
    height: 112rpx;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 36rpx;
    background: rgba(255, 255, 255, 0.56);
    border: 1rpx solid rgba(244, 199, 191, 0.72);
}

.addon-card__status {
    position: absolute;
    top: 16rpx;
    right: 16rpx;
}

.addon-card__body {
    margin-top: 20rpx;
}

.addon-card__head {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 18rpx;
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
    font-size: 48rpx;
    font-weight: 700;
}

.price-row__origin {
    margin-left: 8rpx;
    font-size: 24rpx;
    font-weight: 600;
    color: var(--wm-text-tertiary, #b4aca8);
    text-decoration: line-through;
}

.chip-row {
    display: flex;
    flex-wrap: wrap;
    gap: 12rpx;
    margin-top: 18rpx;
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
}

.addon-card__desc {
    display: -webkit-box;
    margin-top: 18rpx;
    overflow: hidden;
    font-size: 24rpx;
    font-weight: 600;
    line-height: 1.5;
    color: var(--wm-text-secondary, #7f7b78);
    text-overflow: ellipsis;
    word-break: break-all;
    -webkit-box-orient: vertical;
    -webkit-line-clamp: 1;
}

.action-row {
    display: flex;
    gap: 14rpx;
    margin-top: 22rpx;
}

.addon-empty-state {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 18rpx;
    padding: 56rpx 40rpx 72rpx;
    border-radius: var(--wm-radius-card-glass, 49rpx);
    background: rgba(255, 255, 255, 0.88);
    border: 1rpx solid var(--wm-color-border, #efe6e1);
    box-shadow: var(--wm-shadow-card, 0 18rpx 36rpx rgba(214, 185, 167, 0.2));
    backdrop-filter: blur(24rpx);
    -webkit-backdrop-filter: blur(24rpx);
}

.addon-empty-state__icon {
    width: 132rpx;
    height: 132rpx;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 40rpx;
    background: linear-gradient(
        180deg,
        rgba(255, 245, 241, 0.96) 0%,
        rgba(255, 241, 238, 0.82) 100%
    );
    border: 1rpx solid rgba(244, 199, 191, 0.72);
}

.addon-empty-state__title {
    font-size: 30rpx;
    font-weight: 700;
    line-height: 1.3;
    color: var(--wm-text-primary, #1e2432);
}

.addon-empty-state__action {
    min-width: 220rpx;
    min-height: 72rpx;
    padding: 0 32rpx;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: var(--wm-radius-pill, 999rpx);
    background: linear-gradient(135deg, var(--wm-color-primary, #e85a4f) 0%, #d86a5f 100%);
    box-shadow: 0 16rpx 30rpx rgba(232, 90, 79, 0.2);
    transition: all var(--wm-motion-base, 220ms) ease;

    &:active {
        transform: translateY(2rpx);
        opacity: 0.92;
    }
}

.addon-empty-state__action-text {
    font-size: 26rpx;
    line-height: 1;
    font-weight: 700;
    letter-spacing: 0.4rpx;
    color: #ffffff;
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
