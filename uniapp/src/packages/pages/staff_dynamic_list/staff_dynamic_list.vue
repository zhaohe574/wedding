<template>
    <page-meta :page-style="$theme.pageStyle" />
    <PageShell scene="staff">
        <BaseNavbar title="动态管理" />

        <view class="staff-resource-page">
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
                        <view class="page-head">
                            <view class="page-head__main">
                                <view class="page-head__copy">
                                    <text class="page-head__title">动态管理</text>
                                    <text class="page-head__meta">发布近期内容</text>
                                </view>

                                <BaseButton
                                    variant="secondary"
                                    size="sm"
                                    class="page-head__action"
                                    @click="handleAdd"
                                >
                                    发布动态
                                </BaseButton>
                            </view>

                            <view class="summary-row">
                                <view
                                    v-for="item in summaryPills"
                                    :key="item.label"
                                    :class="['summary-pill', { 'summary-pill--accent': item.accent }]"
                                >
                                    <text class="summary-pill__label">{{ item.label }}</text>
                                    <text class="summary-pill__value">{{ item.value }}</text>
                                </view>
                            </view>

                            <scroll-view scroll-x class="filter-scroll" show-scrollbar="false">
                                <view class="filter-row">
                                    <FilterChip
                                        v-for="item in filterOptions"
                                        :key="item.key"
                                        scene="staff"
                                        :selected="currentFilter === item.key"
                                        @click="switchFilter(item.key)"
                                    >
                                        {{ item.label }}
                                    </FilterChip>
                                </view>
                            </scroll-view>
                        </view>
                    </view>
                </template>

                <view class="page-section page-section--list">
                    <LoadingState v-if="loading && !hasLoaded" text="正在同步动态列表..." />

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
                            <view class="dynamic-card__head">
                                <view class="dynamic-card__meta">
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

                                    <StatusBadge
                                        :tone="getStatusTone(Number(item.status))"
                                        size="sm"
                                    >
                                        {{ statusText(Number(item.status)) }}
                                    </StatusBadge>
                                </view>

                                <text class="dynamic-card__time">
                                    {{ formatTime(item.create_time) || '刚刚' }}
                                </text>
                            </view>

                            <text class="dynamic-card__content">
                                {{ item.content || '未填写动态内容' }}
                            </text>

                            <view v-if="shouldShowMedia(item)" class="dynamic-card__media">
                                <image
                                    :src="appStore.getImageUrl(getCardCover(item))"
                                    class="dynamic-card__image"
                                    mode="aspectFill"
                                />
                                <view
                                    v-if="getDynamicType(item) === 2"
                                    class="dynamic-card__play"
                                >
                                    <text class="dynamic-card__play-icon">▶</text>
                                </view>
                                <view
                                    v-else-if="getImageList(item).length > 1"
                                    class="dynamic-card__media-count"
                                >
                                    {{ getImageList(item).length }} 图
                                </view>
                            </view>

                            <view v-if="getTagsList(item).length" class="tag-row">
                                <text
                                    v-for="(tag, index) in getTagsList(item).slice(0, 2)"
                                    :key="`${item.id}-${index}`"
                                    class="tag-chip"
                                >
                                    #{{ tag }}
                                </text>
                            </view>

                            <view class="card-footer">
                                <view class="stats-row">
                                    <view class="metric-chip">
                                        <tn-icon
                                            name="eye"
                                            size="18"
                                            color="var(--wm-color-secondary, #C99B73)"
                                        />
                                        <text>浏览 {{ Number(item.view_count || 0) }}</text>
                                    </view>
                                    <view class="metric-chip">
                                        <tn-icon
                                            name="like"
                                            size="18"
                                            color="var(--wm-color-secondary, #C99B73)"
                                        />
                                        <text>点赞 {{ Number(item.like_count || 0) }}</text>
                                    </view>
                                </view>

                                <view class="action-row">
                                    <view
                                        class="action-btn action-btn--ghost"
                                        @click.stop="handleEdit(item)"
                                    >
                                        编辑
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

                    <EmptyState
                        v-else-if="hasLoaded"
                        :title="currentFilter === 'all' ? '还没有动态' : '当前类型暂无动态'"
                        action-text="发布动态"
                        @action="handleAdd"
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
import BaseNavbar from '@/components/base/BaseNavbar.vue'
import EmptyState from '@/components/base/EmptyState.vue'
import FilterChip from '@/components/base/FilterChip.vue'
import LoadingState from '@/components/base/LoadingState.vue'
import PageShell from '@/components/base/PageShell.vue'
import StatusBadge from '@/components/base/StatusBadge.vue'
import { staffCenterDynamicDelete, staffCenterDynamicLists } from '@/api/staffCenter'
import { useFixedNavbarPagingStyle } from '@/hooks/useFixedNavbarPagingStyle'
import { useAppStore } from '@/stores/app'
import { useThemeStore } from '@/stores/theme'
import { ensureStaffCenterAccess } from '@/utils/staff-center'

type FilterKey = 'all' | 'image' | 'video' | 'activity'
type BadgeTone = 'neutral' | 'success' | 'warning' | 'danger' | 'info'

interface HeroMetric {
    label: string
    value: number
    accent: boolean
}

interface FilterOption {
    key: FilterKey
    label: string
}

interface DynamicListSummary {
    total: number
    published_count: number
    pending_count: number
}

const $theme = useThemeStore()
const appStore = useAppStore()
const pagingStyle = useFixedNavbarPagingStyle()
const pagingRef = ref<any>(null)
const dynamicList = ref<any[]>([])
const loading = ref(false)
const hasLoaded = ref(false)
const currentFilter = ref<FilterKey>('all')
const summary = ref<DynamicListSummary>({
    total: 0,
    published_count: 0,
    pending_count: 0
})

const summaryPills = computed<HeroMetric[]>(() => [
    { label: '总动态', value: summary.value.total, accent: false },
    { label: '已发布', value: summary.value.published_count, accent: true },
    { label: '待审核', value: summary.value.pending_count, accent: false }
])

const filterOptions = computed<FilterOption[]>(() => [
    { key: 'all', label: '全部' },
    { key: 'image', label: '图文' },
    { key: 'video', label: '视频' },
    { key: 'activity', label: '活动' }
])

const getDynamicType = (item: any) => Number(item?.dynamic_type || 0)

const typeText = (type: number) => {
    const map: Record<number, string> = { 1: '图文', 2: '视频', 4: '活动' }
    return map[type] || '动态'
}

const getTypeIcon = (type: number) => {
    const map: Record<number, string> = { 1: 'image', 2: 'video', 4: 'gift' }
    return map[type] || 'edit'
}

const getTypeModifier = (type: number) => {
    const map: Record<number, string> = { 1: 'image', 2: 'video', 4: 'activity' }
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
    const map: Record<number, string> = { 0: '待审核', 1: '已发布', 2: '已下架', 3: '已拒绝' }
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

const getImageList = (item: any): string[] => {
    if (Array.isArray(item?.images) && item.images.length > 0) {
        return item.images
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

const getTagsList = (item: any): string[] => {
    if (Array.isArray(item?.tags_arr) && item.tags_arr.length > 0) {
        return item.tags_arr
    }
    if (typeof item?.tags === 'string') {
        return item.tags.split(',').map((tag: string) => tag.trim()).filter(Boolean)
    }
    return []
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

const getQueryDynamicType = () => {
    const map: Record<Exclude<FilterKey, 'all'>, number> = {
        image: 1,
        video: 2,
        activity: 4
    }

    if (currentFilter.value === 'all') {
        return undefined
    }

    return map[currentFilter.value]
}

const queryList = async (pageNo: number, pageSize: number) => {
    if (pageNo === 1) {
        loading.value = true
    }
    try {
        const dynamicType = getQueryDynamicType()
        const res: any = await staffCenterDynamicLists({
            page: pageNo,
            page_size: pageSize,
            ...(dynamicType ? { dynamic_type: dynamicType } : {})
        })
        summary.value = {
            total: Number(res?.summary?.total || 0),
            published_count: Number(res?.summary?.published_count || 0),
            pending_count: Number(res?.summary?.pending_count || 0)
        }
        hasLoaded.value = true
        pagingRef.value.complete(Array.isArray(res?.data) ? res.data : [])
    } catch (e) {
        hasLoaded.value = true
        pagingRef.value.complete(false)
    } finally {
        if (pageNo === 1) {
            loading.value = false
        }
    }
}

const switchFilter = (filterKey: FilterKey) => {
    if (currentFilter.value === filterKey) return
    currentFilter.value = filterKey
    hasLoaded.value = false
    loading.value = true
    pagingRef.value?.reload()
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
    hasLoaded.value = false
    loading.value = true
    pagingRef.value?.reload()
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
    gap: 14rpx;
    padding: 0 var(--wm-space-page-x, 37rpx);
    box-sizing: border-box;

    &--list {
        padding-top: 12rpx;
        padding-bottom: calc(48rpx + env(safe-area-inset-bottom));
    }
}

.page-head {
    display: flex;
    flex-direction: column;
    gap: 16rpx;
}

.page-head__main {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 20rpx;
}

.page-head__copy {
    flex: 1;
    min-width: 0;
}

.page-head__title {
    display: block;
    font-size: 40rpx;
    font-weight: 700;
    line-height: 1.24;
    color: var(--wm-text-primary, #1e2432);
}

.page-head__meta {
    display: block;
    margin-top: 6rpx;
    font-size: 24rpx;
    font-weight: 600;
    line-height: 1.4;
    color: var(--wm-text-secondary, #7f7b78);
}

.page-head__action {
    flex-shrink: 0;
}

.page-head__action :deep(.tn-button) {
    background: rgba(255, 255, 255, 0.78);
    border-color: rgba(232, 90, 79, 0.12);
}

.summary-row {
    display: flex;
    flex-wrap: wrap;
    gap: 12rpx;
}

.summary-pill {
    display: inline-flex;
    align-items: center;
    gap: 12rpx;
    min-height: 54rpx;
    padding: 0 18rpx;
    border-radius: var(--wm-radius-pill, 999rpx);
    background: rgba(255, 255, 255, 0.76);
    border: 1rpx solid var(--wm-color-border, #efe6e1);

    &--accent {
        background: var(--wm-color-primary-soft, #fff1ee);
        border-color: var(--wm-color-border-strong, #f4c7bf);
    }
}

.summary-pill__label {
    font-size: 22rpx;
    font-weight: 600;
    line-height: 1;
    color: var(--wm-text-secondary, #7f7b78);
}

.summary-pill--accent .summary-pill__label {
    color: var(--wm-color-primary, #e85a4f);
}

.summary-pill__value {
    font-size: 28rpx;
    font-weight: 700;
    line-height: 1;
    color: var(--wm-text-primary, #1e2432);
}

.filter-scroll {
    white-space: nowrap;
}

.filter-row {
    display: inline-flex;
    gap: 12rpx;
    padding-bottom: 2rpx;
}

.dynamic-card + .dynamic-card {
    margin-top: 16rpx;
}

.dynamic-card__head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16rpx;
}

.dynamic-card__meta {
    display: inline-flex;
    align-items: center;
    flex-wrap: wrap;
    gap: 10rpx;
}

.dynamic-card__time {
    flex-shrink: 0;
    font-size: 22rpx;
    font-weight: 600;
    line-height: 1.2;
    color: var(--wm-text-tertiary, #948f8b);
}

.type-pill {
    display: inline-flex;
    align-items: center;
    gap: 8rpx;
    min-height: 44rpx;
    padding: 0 14rpx;
    border-radius: var(--wm-radius-pill, 999rpx);
    border: 1rpx solid transparent;
    font-size: 21rpx;
    font-weight: 700;

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

.dynamic-card__media {
    position: relative;
    margin-top: 16rpx;
    overflow: hidden;
    border-radius: 28rpx;
    background: #f7f1ed;
}

.dynamic-card__image {
    width: 100%;
    height: 228rpx;
    display: block;
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

.dynamic-card__content {
    display: -webkit-box;
    margin-top: 16rpx;
    overflow: hidden;
    font-size: 28rpx;
    font-weight: 600;
    line-height: 1.52;
    color: var(--wm-text-primary, #1e2432);
    text-overflow: ellipsis;
    word-break: break-all;
    -webkit-box-orient: vertical;
    -webkit-line-clamp: 3;
}

.tag-row {
    display: flex;
    flex-wrap: wrap;
    gap: 10rpx;
    margin-top: 14rpx;
}

.tag-chip {
    display: inline-flex;
    align-items: center;
    min-height: 40rpx;
    padding: 0 12rpx;
    border-radius: var(--wm-radius-pill, 999rpx);
    background: rgba(255, 241, 238, 0.72);
    border: 1rpx solid rgba(232, 90, 79, 0.12);
    font-size: 20rpx;
    font-weight: 600;
    color: var(--wm-color-primary, #e85a4f);
}

.card-footer {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16rpx;
    margin-top: 18rpx;
}

.stats-row {
    display: flex;
    flex-wrap: wrap;
    gap: 12rpx;
}

.metric-chip {
    display: inline-flex;
    align-items: center;
    gap: 8rpx;
    min-height: 46rpx;
    padding: 0 14rpx;
    border-radius: var(--wm-radius-pill, 999rpx);
    background: rgba(255, 255, 255, 0.74);
    border: 1rpx solid var(--wm-color-border, #efe6e1);
    font-size: 21rpx;
    font-weight: 600;
    color: var(--wm-text-secondary, #7f7b78);
}

.action-row {
    display: flex;
    flex-shrink: 0;
    gap: 12rpx;
}

.action-btn {
    min-width: 108rpx;
    min-height: 56rpx;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 0 24rpx;
    border-radius: var(--wm-radius-pill, 999rpx);
    font-size: 22rpx;
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
