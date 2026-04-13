<template>
    <page-meta :page-style="$theme.pageStyle" />
    <PageShell scene="staff">
        <BaseNavbar title="作品管理" />

        <view class="page-container">
            <z-paging
                ref="pagingRef"
                v-model="workList"
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
                                    <text class="hero-card__title">作品管理</text>
                                    <text class="hero-card__meta">展示案例</text>
                                </view>

                                <view class="hero-card__action" @click="handleAdd">
                                    <text class="hero-card__action-text">新增作品</text>
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
                    <LoadingState v-if="loading && !hasLoaded" text="加载作品中..." />

                    <template v-else-if="workList.length">
                        <BaseCard
                            v-for="item in workList"
                            :key="item.id"
                            variant="glass"
                            scene="staff"
                            class="work-card"
                            interactive
                            @click="handleEdit(item)"
                        >
                            <view class="work-card__media">
                                <image
                                    class="work-card__cover"
                                    :src="item.cover || item.images?.[0] || defaultCover"
                                    mode="aspectFill"
                                />
                                <StatusBadge
                                    class="work-card__audit"
                                    :tone="getAuditTone(Number(item.audit_status))"
                                    size="sm"
                                >
                                    {{ auditStatusText(Number(item.audit_status)) }}
                                </StatusBadge>
                            </view>

                            <view class="work-card__body">
                                <view class="work-card__head">
                                    <view class="work-card__copy">
                                        <text class="work-card__title">
                                            {{ item.title || '未命名作品' }}
                                        </text>
                                    </view>

                                    <StatusBadge
                                        :tone="Number(item.is_show) === 1 ? 'info' : 'neutral'"
                                        size="sm"
                                    >
                                        {{ Number(item.is_show) === 1 ? '展示中' : '已隐藏' }}
                                    </StatusBadge>
                                </view>

                                <view class="chip-row">
                                    <view v-if="item.shoot_date" class="info-chip">
                                        {{ item.shoot_date }}
                                    </view>
                                    <view v-if="item.location" class="info-chip">
                                        {{ item.location }}
                                    </view>
                                    <view class="info-chip">排序 {{ Number(item.sort || 0) }}</view>
                                </view>

                                <text v-if="item.description" class="work-card__desc">
                                    {{ item.description }}
                                </text>

                                <view class="stats-row">
                                    <view class="metric-chip">
                                        <tn-icon
                                            name="eye"
                                            size="20"
                                            color="var(--wm-color-secondary, #C99B73)"
                                        />
                                        <text>浏览 {{ Number(item.view_count || 0) }}</text>
                                    </view>
                                    <view class="metric-chip metric-chip--accent">
                                        <tn-icon
                                            name="like"
                                            size="20"
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

                    <view v-else-if="hasLoaded" class="work-empty-state">
                        <view class="work-empty-state__icon">
                            <tn-icon name="image" size="92" color="#D8CEC8" />
                        </view>
                        <text class="work-empty-state__title">{{ emptyStateTitle }}</text>
                        <view class="work-empty-state__action" @click="handleAdd">
                            <text class="work-empty-state__action-text">新增作品</text>
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
import LoadingState from '@/components/base/LoadingState.vue'
import PageShell from '@/components/base/PageShell.vue'
import StatusBadge from '@/components/base/StatusBadge.vue'
import { staffCenterWorkDelete, staffCenterWorkLists } from '@/api/staffCenter'
import { useFixedNavbarPagingStyle } from '@/packages/common/hooks/useFixedNavbarPagingStyle'
import { useThemeStore } from '@/stores/theme'
import { ensureStaffCenterAccess } from '@/packages/common/utils/staff-center'

type BadgeTone = 'neutral' | 'success' | 'warning' | 'danger' | 'info'
type WorkMetricFilter = 'visible' | 'pending' | 'hidden' | 'rejected'

interface HeroMetric {
    key: WorkMetricFilter
    label: string
    value: number
}

interface WorkListSummary {
    total: number
    visible_count: number
    hidden_count: number
    pending_audit_count: number
    rejected_count: number
}

const $theme = useThemeStore()
const pagingStyle = useFixedNavbarPagingStyle()
const pagingRef = ref<any>(null)
const workList = ref<any[]>([])
const loading = ref(false)
const hasLoaded = ref(false)
const currentMetricFilter = ref<WorkMetricFilter>('visible')
const summary = ref<WorkListSummary>({
    total: 0,
    visible_count: 0,
    hidden_count: 0,
    pending_audit_count: 0,
    rejected_count: 0
})
const defaultCover = '/static/images/user/default_avatar.png'
const emptyStateTitle = computed(() => {
    const titleMap: Record<WorkMetricFilter, string> = {
        visible: '当前筛选下暂无展示中的作品',
        pending: '当前筛选下暂无待审核作品',
        hidden: '当前筛选下暂无未展示作品',
        rejected: '当前筛选下暂无已拒绝作品'
    }
    return titleMap[currentMetricFilter.value]
})

const heroMetrics = computed<HeroMetric[]>(() => [
    { key: 'visible', label: '展示中', value: summary.value.visible_count },
    { key: 'pending', label: '待审核', value: summary.value.pending_audit_count },
    { key: 'hidden', label: '未展示', value: summary.value.hidden_count },
    { key: 'rejected', label: '已拒绝', value: summary.value.rejected_count }
])

const auditStatusText = (status: number) => {
    const map: Record<number, string> = {
        0: '待审核',
        1: '已通过',
        2: '已拒绝'
    }
    return map[status] || '未知'
}

const getAuditTone = (status: number): BadgeTone => {
    const map: Record<number, BadgeTone> = {
        0: 'warning',
        1: 'success',
        2: 'danger'
    }
    return map[status] || 'neutral'
}

const queryList = async (pageNo: number, pageSize: number) => {
    if (pageNo === 1) {
        loading.value = true
    }
    try {
        const params: Record<string, number> = {
            page_size: pageSize
        }
        if (pageNo > 1) {
            params.page_no = pageNo
        }
        if (currentMetricFilter.value === 'visible') {
            params.is_show = 1
            params.audit_status = 1
        } else if (currentMetricFilter.value === 'pending') {
            params.audit_status = 0
        } else if (currentMetricFilter.value === 'hidden') {
            params.is_show = 0
            params.audit_status = 1
        } else if (currentMetricFilter.value === 'rejected') {
            params.audit_status = 2
        }
        const res: any = await staffCenterWorkLists(params)
        const list = Array.isArray(res?.data) ? res.data : []
        summary.value = {
            total: Number(res?.summary?.total || 0),
            visible_count: Number(res?.summary?.visible_count || 0),
            hidden_count: Number(res?.summary?.hidden_count || 0),
            pending_audit_count: Number(res?.summary?.pending_audit_count || 0),
            rejected_count: Number(res?.summary?.rejected_count || 0)
        }
        hasLoaded.value = true
        pagingRef.value.complete(list)
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

const switchMetricFilter = (filter: WorkMetricFilter) => {
    if (currentMetricFilter.value === filter) {
        return
    }
    currentMetricFilter.value = filter
    workList.value = []
    hasLoaded.value = false
    loading.value = true
    pagingRef.value?.reload()
}

const handleAdd = () => {
    uni.navigateTo({ url: '/packages/pages/staff_work_edit/staff_work_edit' })
}

const handleEdit = (item: any) => {
    uni.navigateTo({ url: `/packages/pages/staff_work_edit/staff_work_edit?id=${item.id}` })
}

const handleDelete = (item: any) => {
    uni.showModal({
        title: '确认删除',
        content: '删除后不可恢复，是否继续？',
        success: async (res) => {
            if (!res.confirm) return
            try {
                await staffCenterWorkDelete({ id: item.id })
                uni.showToast({ title: '删除成功', icon: 'success' })
                pagingRef.value.reload()
            } catch (e: any) {
                const msg = typeof e === 'string' ? e : e?.msg || e?.message || '删除失败'
                uni.showToast({ title: msg, icon: 'none' })
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

.work-card + .work-card {
    margin-top: 18rpx;
}

.work-card__media {
    position: relative;
    overflow: hidden;
    border-radius: 32rpx;
    background: rgba(255, 255, 255, 0.72);
}

.work-card__cover {
    width: 100%;
    height: 248rpx;
    display: block;
    background: #f7f1ed;
}

.work-card__audit {
    position: absolute;
    top: 16rpx;
    right: 16rpx;
}

.work-card__body {
    margin-top: 20rpx;
}

.work-card__head {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 18rpx;
}

.work-card__copy {
    flex: 1;
    min-width: 0;
}

.work-card__title {
    display: block;
    font-size: 32rpx;
    font-weight: 700;
    line-height: 1.35;
    color: var(--wm-text-primary, #1e2432);
    word-break: break-all;
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

.work-card__desc {
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

.work-empty-state {
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

.work-empty-state__icon {
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

.work-empty-state__title {
    font-size: 30rpx;
    font-weight: 700;
    line-height: 1.3;
    color: var(--wm-text-primary, #1e2432);
}

.work-empty-state__action {
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

.work-empty-state__action-text {
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
