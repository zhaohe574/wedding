<template>
    <page-meta :page-style="$theme.pageStyle" />
    <PageShell scene="staff">
        <BaseNavbar title="动态管理" />

        <view class="page-container">
            <z-paging
                ref="pagingRef"
                v-model="dynamicList"
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
                                    <text class="hero-card__title">动态管理</text>
                                </view>

                                <view class="hero-card__action" @click="handleAdd">
                                    <text class="hero-card__action-text">发布动态</text>
                                </view>
                            </view>

                            <view class="hero-metrics">
                                <view
                                    v-for="item in heroMetrics"
                                    :key="item.key"
                                    :class="[
                                        'hero-metric',
                                        {
                                            'hero-metric--selected':
                                                currentStatusFilter === item.key
                                        }
                                    ]"
                                    @click="switchStatusFilter(item.key)"
                                >
                                    <text class="hero-metric__label">{{ item.label }}</text>
                                    <text class="hero-metric__value">{{ item.value }}</text>
                                </view>
                            </view>
                        </BaseCard>

                        <BaseCard variant="glass" scene="staff" class="filter-panel">
                            <view class="section-head">
                                <text class="section-head__title">内容类型</text>
                            </view>

                            <scroll-view scroll-x class="filter-scroll" show-scrollbar="false">
                                <view class="filter-row">
                                    <FilterChip
                                        v-for="item in typeFilterOptions"
                                        :key="item.key"
                                        scene="staff"
                                        :selected="currentTypeFilter === item.key"
                                        @click="switchTypeFilter(item.key)"
                                    >
                                        {{ item.label }}
                                    </FilterChip>
                                </view>
                            </scroll-view>
                        </BaseCard>
                    </view>
                </template>

                <view class="page-section page-section--list">
                    <LoadingState v-if="loading && !hasLoaded" text="加载动态中..." />

                    <template v-else-if="dynamicList.length">
                        <BaseCard
                            v-for="item in dynamicList"
                            :key="item.id"
                            variant="glass"
                            scene="staff"
                            class="dynamic-card"
                            interactive
                            @click="handleEdit(item)"
                        >
                            <view v-if="shouldShowMedia(item)" class="dynamic-card__media">
                                <image
                                    :src="getCardCover(item)"
                                    class="dynamic-card__image"
                                    mode="aspectFill"
                                />
                                <view
                                    :class="[
                                        'type-pill',
                                        'type-pill--overlay',
                                        `type-pill--${getTypeModifier(getDynamicType(item))}`
                                    ]"
                                >
                                    <tn-icon
                                        :name="getTypeIcon(getDynamicType(item))"
                                        size="18"
                                        :color="getTypeColor(getDynamicType(item))"
                                    />
                                    <text>{{ typeText(getDynamicType(item)) }}</text>
                                </view>
                                <StatusBadge
                                    class="dynamic-card__status"
                                    :tone="getStatusTone(Number(item.status))"
                                    size="sm"
                                >
                                    {{ statusText(Number(item.status)) }}
                                </StatusBadge>
                                <view v-if="getDynamicType(item) === 2" class="dynamic-card__play">
                                    <text class="dynamic-card__play-icon">▶</text>
                                </view>
                                <view
                                    v-else-if="getImageList(item).length > 1"
                                    class="dynamic-card__media-count"
                                >
                                    {{ getImageList(item).length }} 图
                                </view>
                            </view>

                            <view class="dynamic-card__body">
                                <view class="dynamic-card__head">
                                    <view class="dynamic-card__copy">
                                        <text class="dynamic-card__title">
                                            {{ getCardTitle(item) }}
                                        </text>
                                    </view>

                                    <StatusBadge
                                        v-if="!shouldShowMedia(item)"
                                        :tone="getStatusTone(Number(item.status))"
                                        size="sm"
                                    >
                                        {{ statusText(Number(item.status)) }}
                                    </StatusBadge>
                                </view>

                                <view v-if="!shouldShowMedia(item)" class="chip-row">
                                    <view
                                        :class="[
                                            'type-pill',
                                            `type-pill--${getTypeModifier(getDynamicType(item))}`
                                        ]"
                                    >
                                        <tn-icon
                                            :name="getTypeIcon(getDynamicType(item))"
                                            size="18"
                                            :color="getTypeColor(getDynamicType(item))"
                                        />
                                        <text>{{ typeText(getDynamicType(item)) }}</text>
                                    </view>
                                    <view class="info-chip">
                                        {{ formatTime(item.create_time) || '刚刚' }}
                                    </view>
                                </view>

                                <view v-else class="chip-row">
                                    <view class="info-chip">
                                        {{ formatTime(item.create_time) || '刚刚' }}
                                    </view>
                                </view>

                                <text v-if="getCardDescription(item)" class="dynamic-card__desc">
                                    {{ getCardDescription(item) }}
                                </text>

                                <view
                                    :class="[
                                        'status-panel',
                                        `status-panel--${getStatusPanelModifier(
                                            Number(item.status)
                                        )}`
                                    ]"
                                >
                                    <text class="status-panel__text">
                                        {{ getStatusReasonText(item) }}
                                    </text>
                                    <text v-if="getStatusHintText(item)" class="status-panel__meta">
                                        {{ getStatusHintText(item) }}
                                    </text>
                                    <text
                                        v-if="getHandledTimeText(item)"
                                        class="status-panel__meta"
                                    >
                                        最近处理：{{ getHandledTimeText(item) }}
                                    </text>
                                </view>

                                <view class="stats-row">
                                    <view class="metric-chip">
                                        <tn-icon
                                            name="eye"
                                            size="18"
                                            color="var(--wm-color-secondary, #C99B73)"
                                        />
                                        <text>浏览 {{ Number(item.view_count || 0) }}</text>
                                    </view>
                                    <view class="metric-chip metric-chip--accent">
                                        <tn-icon
                                            name="like"
                                            size="18"
                                            color="var(--wm-color-primary, #E85A4F)"
                                        />
                                        <text>点赞 {{ Number(item.like_count || 0) }}</text>
                                    </view>
                                </view>

                                <view class="action-row">
                                    <view
                                        class="action-btn action-btn--ghost"
                                        @click.stop="handleEdit(item)"
                                    >
                                        {{ getEditButtonText(item) }}
                                    </view>
                                    <view
                                        class="action-btn action-btn--danger"
                                        @click.stop="handleDelete(item)"
                                    >
                                        删除
                                    </view>
                                </view>
                            </view>
                        </BaseCard>
                    </template>

                    <view v-else-if="hasLoaded" class="dynamic-empty-state">
                        <view class="dynamic-empty-state__icon">
                            <tn-icon name="edit" size="82" color="#D8CEC8" />
                        </view>
                        <text class="dynamic-empty-state__title">{{ emptyStateTitle }}</text>
                        <view class="dynamic-empty-state__action" @click="handleAdd">
                            <text class="dynamic-empty-state__action-text">发布动态</text>
                        </view>
                    </view>
                </view>
            </z-paging>
        </view>
    </PageShell>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue'
import { onShow } from '@dcloudio/uni-app'
import BaseCard from '@/components/base/BaseCard.vue'
import BaseNavbar from '@/components/base/BaseNavbar.vue'
import FilterChip from '@/components/base/FilterChip.vue'
import LoadingState from '@/components/base/LoadingState.vue'
import PageShell from '@/components/base/PageShell.vue'
import StatusBadge from '@/components/base/StatusBadge.vue'
import { staffCenterDynamicDelete, staffCenterDynamicLists } from '@/api/staffCenter'
import { useFixedNavbarPagingStyle } from '@/hooks/useFixedNavbarPagingStyle'
import { useThemeStore } from '@/stores/theme'
import { ensureStaffCenterAccess } from '@/utils/staff-center'

type DynamicStatusFilter = 'published' | 'pending' | 'offline' | 'rejected'
type DynamicTypeFilter = 'all' | 'image' | 'video'
type BadgeTone = 'neutral' | 'success' | 'warning' | 'danger' | 'info'

interface HeroMetric {
    key: DynamicStatusFilter
    label: string
    value: number
}

interface TypeFilterOption {
    key: DynamicTypeFilter
    label: string
}

interface DynamicListSummary {
    total: number
    published_count: number
    pending_count: number
    offline_count: number
    rejected_count: number
}

const $theme = useThemeStore()
const pagingStyle = useFixedNavbarPagingStyle()
const pagingRef = ref<any>(null)
const dynamicList = ref<any[]>([])
const loading = ref(false)
const hasLoaded = ref(false)
const currentStatusFilter = ref<DynamicStatusFilter>('published')
const currentTypeFilter = ref<DynamicTypeFilter>('all')
const summary = ref<DynamicListSummary>({
    total: 0,
    published_count: 0,
    pending_count: 0,
    offline_count: 0,
    rejected_count: 0
})

const heroMetrics = computed<HeroMetric[]>(() => [
    { key: 'published', label: '已发布', value: summary.value.published_count },
    { key: 'pending', label: '待审核', value: summary.value.pending_count },
    { key: 'offline', label: '已下架', value: summary.value.offline_count },
    { key: 'rejected', label: '已拒绝', value: summary.value.rejected_count }
])

const typeFilterOptions = computed<TypeFilterOption[]>(() => [
    { key: 'all', label: '全部' },
    { key: 'image', label: '图文' },
    { key: 'video', label: '视频' }
])

const emptyStateTitle = computed(() => {
    if (currentStatusFilter.value === 'published') {
        return '暂无已发布动态'
    }
    if (currentStatusFilter.value === 'pending') {
        return '暂无待审核动态'
    }
    if (currentStatusFilter.value === 'offline') {
        return '暂无已下架动态'
    }
    if (currentStatusFilter.value === 'rejected') {
        return '暂无已拒绝动态'
    }
    if (currentTypeFilter.value !== 'all') {
        return '暂无该类型动态'
    }
    return '暂无动态'
})

const getDynamicType = (item: any) => Number(item?.dynamic_type || 0)

const typeText = (type: number) => {
    const map: Record<number, string> = {
        1: '图文',
        2: '视频',
        4: '活动'
    }
    return map[type] || '动态'
}

const getTypeIcon = (type: number) => {
    const map: Record<number, string> = {
        1: 'image',
        2: 'video',
        4: 'gift'
    }
    return map[type] || 'edit'
}

const getTypeModifier = (type: number) => {
    const map: Record<number, string> = {
        1: 'image',
        2: 'video',
        4: 'activity'
    }
    return map[type] || 'default'
}

const getTypeColor = (type: number) => {
    const map: Record<number, string> = {
        1: 'var(--wm-color-primary, #E85A4F)',
        2: 'var(--wm-color-info, #607086)',
        4: 'var(--wm-color-warning, #C98524)'
    }
    return map[type] || 'var(--wm-color-primary, #E85A4F)'
}

const statusText = (status: number) => {
    const map: Record<number, string> = {
        0: '待审核',
        1: '已发布',
        2: '已下架',
        3: '已拒绝'
    }
    return map[status] || '未知'
}

const getStatusTone = (status: number): BadgeTone => {
    const map: Record<number, BadgeTone> = {
        0: 'warning',
        1: 'success',
        2: 'neutral',
        3: 'danger'
    }
    return map[status] || 'neutral'
}

const getStatusPanelModifier = (status: number) => {
    const map: Record<number, string> = {
        0: 'warning',
        1: 'success',
        2: 'neutral',
        3: 'danger'
    }
    return map[status] || 'neutral'
}

const getImageList = (item: any): string[] => {
    if (Array.isArray(item?.images) && item.images.length > 0) {
        return item.images.filter(Boolean)
    }
    return []
}

const getVideoCover = (item: any): string => {
    if (item?.video_cover) return item.video_cover
    const images = getImageList(item)
    if (images.length > 0) return images[0]
    return ''
}

const getCardCover = (item: any) => {
    if (getDynamicType(item) === 2) {
        return getVideoCover(item)
    }
    const images = getImageList(item)
    if (images.length > 0) return images[0]
    return ''
}

const shouldShowMedia = (item: any) => {
    if (getDynamicType(item) === 2) return !!getVideoCover(item)
    return getImageList(item).length > 0
}

const normalizeTextValue = (value: unknown) => String(value ?? '').trim()

const getCardTitle = (item: any) => {
    const title = normalizeTextValue(item?.title)
    if (title) return title

    const content = normalizeTextValue(item?.content)
    if (content) {
        return content.length > 24 ? `${content.slice(0, 24)}...` : content
    }

    return '未命名动态'
}

const getCardDescription = (item: any) => {
    const title = normalizeTextValue(item?.title)
    const content = normalizeTextValue(item?.content)
    if (title && content) return content
    return ''
}

const formatTime = (time: any): string => {
    if (!time) return ''

    let date: Date
    if (typeof time === 'number') {
        date = new Date(time < 1e12 ? time * 1000 : time)
    } else {
        date = new Date(String(time).replace(/-/g, '/'))
    }

    if (isNaN(date.getTime())) return String(time)

    const now = Date.now()
    const diff = now - date.getTime()
    const minute = Math.floor(diff / 60000)

    if (minute < 1) return '刚刚'
    if (minute < 60) return `${minute}分钟前`

    const hour = Math.floor(minute / 60)
    if (hour < 24) return `${hour}小时前`

    const day = Math.floor(hour / 24)
    if (day < 7) return `${day}天前`

    const pad = (value: number) => String(value).padStart(2, '0')
    return `${pad(date.getMonth() + 1)}-${pad(date.getDate())}`
}

const formatHandledTime = (time: any): string => {
    if (!time) return ''

    let date: Date
    if (typeof time === 'number') {
        date = new Date(time < 1e12 ? time * 1000 : time)
    } else {
        date = new Date(String(time).replace(/-/g, '/'))
    }

    if (isNaN(date.getTime())) return String(time)

    const pad = (value: number) => String(value).padStart(2, '0')
    return `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())} ${pad(
        date.getHours()
    )}:${pad(date.getMinutes())}`
}

const getStatusReasonText = (item: any) =>
    normalizeTextValue(item?.status_reason) || '当前状态已更新。'

const getStatusHintText = (item: any) => normalizeTextValue(item?.status_hint)

const getHandledTimeText = (item: any) => {
    const handledTime = Number(item?.handled_time || 0)
    return handledTime > 0 ? formatHandledTime(handledTime) : ''
}

const getEditButtonText = (item: any) => {
    const status = Number(item?.status || 0)
    if (status === 2 || status === 3) {
        return '修改后重提'
    }
    return '编辑'
}

const getQueryDynamicType = () => {
    const map: Record<Exclude<DynamicTypeFilter, 'all'>, number> = {
        image: 1,
        video: 2
    }

    if (currentTypeFilter.value === 'all') {
        return undefined
    }

    return map[currentTypeFilter.value]
}

const getQueryStatus = () => {
    const map: Record<DynamicStatusFilter, number> = {
        published: 1,
        pending: 0,
        offline: 2,
        rejected: 3
    }

    return map[currentStatusFilter.value]
}

const queryList = async (pageNo: number, pageSize: number) => {
    if (pageNo === 1) {
        loading.value = true
    }
    try {
        const dynamicType = getQueryDynamicType()
        const status = getQueryStatus()
        const res: any = await staffCenterDynamicLists({
            ...(pageNo > 1 ? { page_no: pageNo } : {}),
            page_size: pageSize,
            ...(dynamicType ? { dynamic_type: dynamicType } : {}),
            ...(status !== undefined ? { status } : {})
        })

        summary.value = {
            total: Number(res?.summary?.total || 0),
            published_count: Number(res?.summary?.published_count || 0),
            pending_count: Number(res?.summary?.pending_count || 0),
            offline_count: Number(res?.summary?.offline_count || 0),
            rejected_count: Number(res?.summary?.rejected_count || 0)
        }
        hasLoaded.value = true
        pagingRef.value.complete(Array.isArray(res?.data) ? res.data : [])
    } catch (e: any) {
        const msg = typeof e === 'string' ? e : e?.msg || e?.message || '加载失败'
        uni.showToast({ title: msg, icon: 'none' })
        hasLoaded.value = true
        pagingRef.value.complete(false)
    } finally {
        if (pageNo === 1) {
            loading.value = false
        }
    }
}

const reloadList = () => {
    hasLoaded.value = false
    loading.value = true
    pagingRef.value?.reload()
}

const switchStatusFilter = (filterKey: DynamicStatusFilter) => {
    if (currentStatusFilter.value === filterKey) return
    currentStatusFilter.value = filterKey
    reloadList()
}

const switchTypeFilter = (filterKey: DynamicTypeFilter) => {
    if (currentTypeFilter.value === filterKey) return
    currentTypeFilter.value = filterKey
    reloadList()
}

const handleAdd = () => {
    uni.navigateTo({ url: '/packages/pages/staff_dynamic_edit/staff_dynamic_edit' })
}

const handleEdit = (item: any) => {
    uni.setStorageSync('_temp_dynamic_detail', JSON.stringify(item))
    uni.navigateTo({
        url: `/packages/pages/staff_dynamic_edit/staff_dynamic_edit?id=${item.id}`,
        success: (res) => {
            res.eventChannel.emit('detail', item)
        }
    })
}

const handleDelete = (item: any) => {
    uni.showModal({
        title: '确认删除',
        content: '删除后不可恢复，是否继续？',
        success: async (res) => {
            if (!res.confirm) return
            try {
                await staffCenterDynamicDelete({ id: item.id })
                uni.showToast({ title: '删除成功', icon: 'success' })
                pagingRef.value.reload()
            } catch (e: any) {
                uni.showToast({ title: e?.msg || e?.message || '删除失败', icon: 'none' })
            }
        }
    })
}

onShow(async () => {
    if (!(await ensureStaffCenterAccess())) return
    reloadList()
})
</script>

<style lang="scss" scoped>
.page-container {
    min-height: 100vh;
    padding-top: 20rpx;
    box-sizing: border-box;
    background: radial-gradient(
            circle at top left,
            rgba(232, 90, 79, 0.1) 0,
            rgba(252, 251, 249, 0) 36%
        ),
        linear-gradient(180deg, var(--wm-color-bg-page, #fcfbf9) 0%, #f7f1ed 100%);
}

.page-section {
    display: flex;
    flex-direction: column;
    gap: 16rpx;
    padding: 0 var(--wm-space-page-x, 37rpx);
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
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 10rpx;
    margin-top: 22rpx;
}

.hero-metric {
    display: flex;
    flex-direction: column;
    gap: 8rpx;
    min-width: 0;
    padding: 16rpx 14rpx;
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
    font-size: 20rpx;
    font-weight: 700;
    line-height: 1.3;
    color: var(--wm-text-secondary, #7f7b78);
    text-align: center;
}

.hero-metric--selected .hero-metric__label {
    color: var(--wm-color-primary, #e85a4f);
}

.hero-metric__value {
    font-size: 34rpx;
    font-weight: 700;
    line-height: 1;
    color: var(--wm-text-primary, #1e2432);
    text-align: center;
}

.hero-metric--selected .hero-metric__value {
    color: var(--wm-color-primary, #e85a4f);
}

.section-head {
    display: flex;
    align-items: center;
}

.section-head__title {
    font-size: 30rpx;
    font-weight: 700;
    line-height: 1.3;
    color: var(--wm-text-primary, #1e2432);
}

.filter-scroll {
    margin-top: 16rpx;
    white-space: nowrap;
}

.filter-row {
    display: inline-flex;
    gap: 12rpx;
    padding-bottom: 2rpx;
}

.dynamic-card + .dynamic-card {
    margin-top: 18rpx;
}

.dynamic-card__media {
    position: relative;
    overflow: hidden;
    border-radius: 32rpx;
    background: rgba(255, 255, 255, 0.72);
}

.dynamic-card__image {
    width: 100%;
    height: 248rpx;
    display: block;
    background: #f7f1ed;
}

.dynamic-card__status {
    position: absolute;
    top: 16rpx;
    right: 16rpx;
}

.dynamic-card__play {
    position: absolute;
    top: 50%;
    left: 50%;
    width: 88rpx;
    height: 88rpx;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    background: rgba(30, 36, 50, 0.32);
    transform: translate(-50%, -50%);
    backdrop-filter: blur(8rpx);
    -webkit-backdrop-filter: blur(8rpx);
}

.dynamic-card__play-icon {
    margin-left: 4rpx;
    font-size: 28rpx;
    color: #ffffff;
}

.dynamic-card__media-count {
    position: absolute;
    right: 16rpx;
    bottom: 16rpx;
    min-height: 40rpx;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 0 14rpx;
    border-radius: var(--wm-radius-pill, 999rpx);
    background: rgba(30, 36, 50, 0.52);
    font-size: 20rpx;
    font-weight: 700;
    color: #ffffff;
}

.dynamic-card__body {
    margin-top: 20rpx;
}

.dynamic-card__head {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 18rpx;
}

.dynamic-card__copy {
    flex: 1;
    min-width: 0;
}

.dynamic-card__title {
    display: block;
    font-size: 32rpx;
    font-weight: 700;
    line-height: 1.35;
    color: var(--wm-text-primary, #1e2432);
    word-break: break-all;
}

.dynamic-card__desc {
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
    -webkit-line-clamp: 2;
}

.status-panel {
    display: flex;
    flex-direction: column;
    gap: 8rpx;
    margin-top: 16rpx;
    padding: 18rpx 20rpx;
    border-radius: 24rpx;
    border: 1rpx solid rgba(239, 230, 225, 0.92);
    background: rgba(252, 251, 249, 0.88);

    &--warning {
        background: rgba(255, 248, 233, 0.9);
        border-color: rgba(245, 202, 118, 0.5);
    }

    &--success {
        background: rgba(240, 250, 243, 0.92);
        border-color: rgba(129, 199, 132, 0.45);
    }

    &--neutral {
        background: rgba(246, 244, 242, 0.95);
        border-color: rgba(180, 172, 168, 0.42);
    }

    &--danger {
        background: rgba(255, 241, 240, 0.94);
        border-color: rgba(232, 90, 79, 0.3);
    }
}

.status-panel__text {
    font-size: 24rpx;
    font-weight: 700;
    line-height: 1.55;
    color: var(--wm-text-primary, #1e2432);
}

.status-panel__meta {
    font-size: 22rpx;
    font-weight: 600;
    line-height: 1.55;
    color: var(--wm-text-secondary, #7f7b78);
}

.chip-row {
    display: flex;
    flex-wrap: wrap;
    gap: 12rpx;
    margin-top: 18rpx;
}

.type-pill,
.info-chip {
    display: inline-flex;
    align-items: center;
    gap: 8rpx;
    min-height: 46rpx;
    padding: 0 14rpx;
    border-radius: var(--wm-radius-pill, 999rpx);
    border: 1rpx solid transparent;
    font-size: 21rpx;
    font-weight: 700;
}

.type-pill {
    &--overlay {
        position: absolute;
        left: 16rpx;
        top: 16rpx;
        background: rgba(255, 255, 255, 0.92);
        backdrop-filter: blur(10rpx);
        -webkit-backdrop-filter: blur(10rpx);
    }

    &--image {
        color: var(--wm-color-primary, #e85a4f);
        background: var(--wm-color-primary-soft, #fff1ee);
        border-color: var(--wm-color-border-strong, #f4c7bf);
    }

    &--video {
        color: var(--wm-color-info, #607086);
        background: rgba(96, 112, 134, 0.1);
        border-color: rgba(96, 112, 134, 0.16);
    }

    &--activity {
        color: var(--wm-color-warning, #c98524);
        background: rgba(201, 133, 36, 0.1);
        border-color: rgba(201, 133, 36, 0.16);
    }

    &--default {
        color: var(--wm-text-secondary, #7f7b78);
        background: rgba(255, 255, 255, 0.72);
        border-color: var(--wm-color-border, #efe6e1);
    }
}

.info-chip {
    background: rgba(255, 255, 255, 0.74);
    border-color: var(--wm-color-border, #efe6e1);
    color: var(--wm-text-secondary, #7f7b78);
}

.stats-row {
    display: flex;
    flex-wrap: wrap;
    gap: 12rpx;
    margin-top: 18rpx;
}

.metric-chip {
    display: inline-flex;
    align-items: center;
    gap: 8rpx;
    min-height: 48rpx;
    padding: 0 16rpx;
    border-radius: var(--wm-radius-pill, 999rpx);
    background: rgba(255, 255, 255, 0.74);
    border: 1rpx solid var(--wm-color-border, #efe6e1);
    font-size: 21rpx;
    font-weight: 600;
    color: var(--wm-text-secondary, #7f7b78);

    &--accent {
        background: var(--wm-color-primary-soft, #fff1ee);
        border-color: var(--wm-color-border-strong, #f4c7bf);
        color: var(--wm-color-primary, #e85a4f);
    }
}

.action-row {
    display: flex;
    gap: 14rpx;
    margin-top: 22rpx;
}

.dynamic-empty-state {
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

.dynamic-empty-state__icon {
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

.dynamic-empty-state__title {
    font-size: 30rpx;
    font-weight: 700;
    line-height: 1.3;
    color: var(--wm-text-primary, #1e2432);
}

.dynamic-empty-state__action {
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

.dynamic-empty-state__action-text {
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
