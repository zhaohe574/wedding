<template>
    <page-meta :page-style="$theme.pageStyle" />
    <PageShell scene="staff" tone="workspace">
        <BaseNavbar
            title="套餐管理"
            variant="solid"
            title-align="center"
            bg-color="#191713"
            text-color="#FFFDF8"
        />

        <view class="page-container wm-page-content">
            <z-paging
                ref="pagingRef"
                v-model="packageList"
                :auto="false"
                :hide-empty-view="true"
                :paging-style="resolvedPagingStyle"
                @query="queryList"
            >
                <template #top>
                    <view class="page-section page-section--top">
                        <StaffWorkspaceHero
                            title="套餐库"
                            action-text="新增"
                            @action="goCreate"
                        >
                            <StaffFilterBar
                                :items="packageFilterItems"
                                :model-value="currentMetricFilter"
                                @select="handleMetricFilterSelect"
                            />
                        </StaffWorkspaceHero>
                    </view>
                </template>

                <view class="page-section page-section--list">
                    <StaffSectionHeader
                        :title="listSectionTitle"
                        :meta="listSectionMeta"
                    />

                    <LoadingState v-if="loading && !hasLoaded" text="套餐加载中" />

                    <template v-else-if="packageList.length">
                        <BaseCard
                            v-for="item in packageList"
                            :key="item.id"
                            variant="panel"
                            scene="staff"
                            class="package-card"
                            padding="22rpx"
                            border-radius="34rpx"
                        >
                            <view class="package-card__media">
                                <image
                                    v-if="item.image"
                                    class="package-card__image"
                                    :src="item.image"
                                    mode="aspectFill"
                                />
                                <view
                                    v-else
                                    class="package-card__image package-card__image--placeholder"
                                >
                                    <view class="package-card__placeholder-mark">
                                        <BaseIcon name="image" size="42" color="#D8D3C7" />
                                    </view>
                                </view>

                                <StatusBadge
                                    class="package-card__status"
                                    :tone="Number(item.is_show) === 1 ? 'success' : 'neutral'"
                                    size="sm"
                                >
                                    {{ Number(item.is_show) === 1 ? '上架中' : '已下架' }}
                                </StatusBadge>
                            </view>

                            <view class="package-card__body">
                                <view class="package-card__head">
                                    <view class="package-card__copy">
                                        <text class="package-card__title">
                                            {{ item.name || '未命名套餐' }}
                                        </text>
                                    </view>
                                </view>

                                <view class="price-row">
                                    <text class="price-row__prefix">¥</text>
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
                                    <view v-if="Number(item.duration || 0) > 0" class="info-chip">
                                        {{ Number(item.duration) }}小时
                                    </view>
                                    <view class="info-chip">
                                        附加项
                                        {{
                                            Array.isArray(item.addon_ids)
                                                ? item.addon_ids.length
                                                : 0
                                        }}
                                    </view>
                                    <view class="info-chip">排序 {{ Number(item.sort || 0) }}</view>
                                    <view
                                        v-if="Number(item.is_recommend) === 1"
                                        class="info-chip info-chip--accent"
                                    >
                                        推荐款
                                    </view>
                                </view>

                                <text v-if="item.description" class="package-card__desc">
                                    {{ item.description }}
                                </text>

                                <view class="action-row">
                                    <BaseButton
                                        label="编辑"
                                        variant="light"
                                        size="sm"
                                        height="68rpx"
                                        block
                                        @click="handleEdit(item)"
                                    />
                                    <BaseButton
                                        label="删除"
                                        variant="danger"
                                        size="sm"
                                        height="68rpx"
                                        block
                                        @click="handleRemove(item)"
                                    />
                                </view>
                            </view>
                        </BaseCard>
                    </template>

                    <EmptyState
                        v-else-if="hasLoaded"
                        :title="emptyStateTitle"
                        description="暂无套餐"
                        action-text="新增套餐"
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
import BaseButton from '@/components/base/BaseButton.vue'
import BaseCard from '@/components/base/BaseCard.vue'
import EmptyState from '@/components/base/EmptyState.vue'
import BaseNavbar from '@/components/base/BaseNavbar.vue'
import LoadingState from '@/components/base/LoadingState.vue'
import PageShell from '@/components/base/PageShell.vue'
import StatusBadge from '@/components/base/StatusBadge.vue'
import StaffFilterBar from '@/packages/components/staff-workspace/staff-filter-bar.vue'
import StaffSectionHeader from '@/packages/components/staff-workspace/staff-section-header.vue'
import StaffWorkspaceHero from '@/packages/components/staff-workspace/staff-workspace-hero.vue'
import { staffCenterPackageLists, staffCenterPackageRemove } from '@/api/staffCenter'
import { useFixedNavbarPagingStyle } from '@/packages/common/hooks/useFixedNavbarPagingStyle'
import { useThemeStore } from '@/stores/theme'
import { ensureStaffCenterAccess } from '@/packages/common/utils/staff-center'
import { confirmModal, showError, showSuccess } from '@/utils/feedback'

type PackageFilter = 'all' | 'active' | 'recommend' | 'inactive'

interface HeroMetric {
    key: PackageFilter
    label: string
    value: number
}

const $theme = useThemeStore()
const pagingStyle = useFixedNavbarPagingStyle()
const resolvedPagingStyle = computed(() => ({
    ...pagingStyle.value,
    paddingLeft: 'var(--wm-space-page-x, 37rpx)',
    paddingRight: 'var(--wm-space-page-x, 37rpx)',
    boxSizing: 'border-box'
}))
const pagingRef = ref<any>(null)
const packageList = ref<any[]>([])
const loading = ref(false)
const hasLoaded = ref(false)
const currentMetricFilter = ref<PackageFilter>('all')
const metricCounts = ref<Record<PackageFilter, number>>({
    all: 0,
    active: 0,
    recommend: 0,
    inactive: 0
})

const heroMetrics = computed<HeroMetric[]>(() => [
    { key: 'all', label: '全部', value: metricCounts.value.all },
    { key: 'active', label: '上架中', value: metricCounts.value.active },
    { key: 'recommend', label: '推荐款', value: metricCounts.value.recommend },
    { key: 'inactive', label: '已下架', value: metricCounts.value.inactive }
])

const packageFilterItems = computed(() =>
    heroMetrics.value.map((item) => ({
        label: item.label,
        value: item.key,
        count: item.value
    }))
)

const emptyStateTitle = computed(() => {
    const titleMap: Record<PackageFilter, string> = {
        all: '还没有套餐',
        active: '当前筛选下暂无上架套餐',
        recommend: '当前筛选下暂无推荐套餐',
        inactive: '当前筛选下暂无已下架套餐'
    }
    return titleMap[currentMetricFilter.value]
})
const listSectionTitle = computed(() => {
    const map: Record<PackageFilter, string> = {
        all: '全部套餐',
        active: '上架中的套餐',
        recommend: '推荐套餐',
        inactive: '已下架套餐'
    }
    return map[currentMetricFilter.value]
})
const listSectionMeta = computed(() => `共 ${metricCounts.value[currentMetricFilter.value]} 项`)

const resolvePackageListError = (error: unknown, fallback = '操作失败') => {
    if (typeof error === 'string' && error.trim()) {
        return error
    }

    if (error && typeof error === 'object') {
        const value =
            (error as { msg?: unknown; message?: unknown }).msg ??
            (error as { message?: unknown }).message

        if (typeof value === 'string' && value.trim()) {
            return value
        }
    }

    return fallback
}

const getListParams = (pageNo: number, pageSize: number) => {
    const params: Record<string, number> = {
        page_size: pageSize
    }
    if (pageNo > 1) {
        params.page_no = pageNo
    }
    if (currentMetricFilter.value === 'active') {
        params.is_show = 1
    } else if (currentMetricFilter.value === 'recommend') {
        params.is_recommend = 1
    } else if (currentMetricFilter.value === 'inactive') {
        params.is_show = 0
    }
    return params
}

const refreshMetricCounts = async () => {
    const [allRes, activeRes, recommendRes, inactiveRes] = await Promise.all([
        staffCenterPackageLists({ page_size: 1 }),
        staffCenterPackageLists({ is_show: 1, page_size: 1 }),
        staffCenterPackageLists({ is_recommend: 1, page_size: 1 }),
        staffCenterPackageLists({ is_show: 0, page_size: 1 })
    ])
    metricCounts.value = {
        all: Number(allRes?.total || 0),
        active: Number(activeRes?.total || 0),
        recommend: Number(recommendRes?.total || 0),
        inactive: Number(inactiveRes?.total || 0)
    }
}

const queryList = async (pageNo: number, pageSize: number) => {
    if (pageNo === 1) {
        loading.value = true
    }

    try {
        const data = await staffCenterPackageLists(getListParams(pageNo, pageSize))
        if (pageNo === 1) {
            await refreshMetricCounts()
        }
        pagingRef.value?.complete(Array.isArray(data?.data) ? data.data : [])
    } catch (e: any) {
        showError(resolvePackageListError(e, '加载失败'))
        pagingRef.value?.complete(false)
    } finally {
        if (pageNo === 1) {
            loading.value = false
        }
        hasLoaded.value = true
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

const switchMetricFilter = (filter: PackageFilter) => {
    if (currentMetricFilter.value === filter) {
        return
    }
    currentMetricFilter.value = filter
    hasLoaded.value = false
    pagingRef.value?.reload()
}

const handleMetricFilterSelect = (value: string | number) => {
    switchMetricFilter(String(value) as PackageFilter)
}

const handleRemove = async (item: any) => {
    const confirmed = await confirmModal({
        title: '确认删除',
        content: `确定删除套餐“${item.name || ''}”吗？`
    })

    if (!confirmed) {
        return
    }

    try {
        await staffCenterPackageRemove({ package_id: item.id })
        showSuccess('删除成功')
        hasLoaded.value = false
        pagingRef.value?.reload()
    } catch (e: any) {
        showError(resolvePackageListError(e, '删除失败'))
    }
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
    background: radial-gradient(
            circle at top left,
            rgba(11, 11, 11, 0.1) 0,
            rgba(248, 247, 242, 0) 36%
        ),
        linear-gradient(180deg, var(--wm-color-bg-page, #ffffff) 0%, #f8f7f2 100%);
}

.page-section {
    display: flex;
    flex-direction: column;
    gap: 18rpx;
    box-sizing: border-box;

    &--top {
        padding-top: 20rpx;
    }

    &--list {
        padding-top: 20rpx;
        padding-bottom: calc(48rpx + env(safe-area-inset-bottom));
    }
}

.package-card + .package-card {
    margin-top: 22rpx;
}

.package-card__media {
    position: relative;
    overflow: hidden;
    border-radius: 32rpx;
    background: rgba(255, 255, 255, 0.72);
}

.package-card__image {
    width: 100%;
    height: 248rpx;
    display: block;
    background: #f8f7f2;

    &--placeholder {
        display: flex;
        align-items: center;
        justify-content: center;
        background: linear-gradient(
                135deg,
                rgba(247, 240, 223, 0.92) 0%,
                rgba(247, 240, 223, 0.88) 100%
            ),
            #f8f7f2;
    }
}

.package-card__placeholder-mark {
    width: 112rpx;
    height: 112rpx;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 36rpx;
    background: rgba(255, 255, 255, 0.56);
    border: 1rpx solid rgba(216, 194, 138, 0.72);
}

.package-card__status {
    position: absolute;
    top: 16rpx;
    right: 16rpx;
}

.package-card__body {
    margin-top: 20rpx;
}

.package-card__head {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 18rpx;
}

.package-card__copy {
    flex: 1;
    min-width: 0;
}

.package-card__title {
    display: block;
    font-size: 32rpx;
    font-weight: 700;
    line-height: 1.35;
    color: var(--wm-text-primary, #111111);
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
    color: var(--wm-color-primary, #0b0b0b);
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
    color: var(--wm-text-tertiary, #9a9388);
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
    border: 1rpx solid var(--wm-color-border, #e7e2d6);
    font-size: 22rpx;
    font-weight: 600;
    color: var(--wm-text-secondary, #5f5a50);

    &--accent {
        background: var(--wm-color-primary-soft, #f3f2ee);
        border-color: var(--wm-color-border-strong, #d8c28a);
        color: var(--wm-color-primary, #0b0b0b);
    }
}

.package-card__desc {
    display: -webkit-box;
    margin-top: 18rpx;
    overflow: hidden;
    font-size: 24rpx;
    font-weight: 600;
    line-height: 1.5;
    color: var(--wm-text-secondary, #5f5a50);
    text-overflow: ellipsis;
    word-break: break-all;
    -webkit-box-orient: vertical;
    -webkit-line-clamp: 1;
}

.action-row {
    display: flex;
    gap: 14rpx;
    margin-top: 24rpx;
}

.action-row :deep(.base-button) {
    flex: 1;
    min-width: 0;
}
</style>
