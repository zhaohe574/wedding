<template>
    <page-meta :page-style="$theme.pageStyle" />
    <PageShell scene="staff">
        <BaseNavbar title="作品管理" />

        <view class="staff-resource-page">
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
                        <view class="page-head">
                            <view class="page-head__main">
                                <view class="page-head__copy">
                                    <text class="page-head__title">作品管理</text>
                                    <text class="page-head__meta">维护展示内容</text>
                                </view>

                                <BaseButton
                                    variant="secondary"
                                    size="sm"
                                    class="page-head__action"
                                    @click="handleAdd"
                                >
                                    新增作品
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
                        </view>
                    </view>
                </template>

                <view class="page-section page-section--list">
                    <LoadingState v-if="loading && !hasLoaded" text="正在同步作品列表..." />

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
                                <view class="resource-head">
                                    <view class="resource-head__copy">
                                        <text class="resource-head__title">
                                            {{ item.title || '未命名作品' }}
                                        </text>
                                        <text class="resource-head__meta">
                                            {{ auditStatusText(Number(item.audit_status)) }}
                                        </text>
                                    </view>

                                    <StatusBadge
                                        :tone="Number(item.is_show) === 1 ? 'info' : 'neutral'"
                                        size="sm"
                                    >
                                        {{ Number(item.is_show) === 1 ? '显示中' : '已隐藏' }}
                                    </StatusBadge>
                                </view>

                                <view class="stats-row">
                                    <view class="metric-chip">
                                        <tn-icon
                                            name="eye"
                                            size="20"
                                            color="var(--wm-color-secondary, #C99B73)"
                                        />
                                        <text>浏览 {{ Number(item.view_count || 0) }}</text>
                                    </view>
                                    <view class="metric-chip">
                                        <tn-icon
                                            name="like"
                                            size="20"
                                            color="var(--wm-color-secondary, #C99B73)"
                                        />
                                        <text>点赞 {{ Number(item.like_count || 0) }}</text>
                                    </view>
                                </view>

                                <view class="card-footer">
                                    <view class="card-footer__hint">
                                        <text>{{ Number(item.is_show) === 1 ? '前台可见' : '暂不展示' }}</text>
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
                            </view>
                        </BaseCard>
                    </template>

                    <EmptyState
                        v-else-if="hasLoaded"
                        title="还没有作品"
                        action-text="新增作品"
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
import LoadingState from '@/components/base/LoadingState.vue'
import PageShell from '@/components/base/PageShell.vue'
import StatusBadge from '@/components/base/StatusBadge.vue'
import { staffCenterWorkDelete, staffCenterWorkLists } from '@/api/staffCenter'
import { useFixedNavbarPagingStyle } from '@/hooks/useFixedNavbarPagingStyle'
import { useThemeStore } from '@/stores/theme'
import { ensureStaffCenterAccess } from '@/utils/staff-center'

type BadgeTone = 'neutral' | 'success' | 'warning' | 'danger' | 'info'

interface HeroMetric {
    label: string
    value: number
    accent: boolean
}

interface WorkListSummary {
    total: number
    visible_count: number
    pending_audit_count: number
}

const $theme = useThemeStore()
const pagingStyle = useFixedNavbarPagingStyle()
const pagingRef = ref<any>(null)
const workList = ref<any[]>([])
const loading = ref(false)
const hasLoaded = ref(false)
const summary = ref<WorkListSummary>({
    total: 0,
    visible_count: 0,
    pending_audit_count: 0
})
const defaultCover = '/static/images/user/default_avatar.png'

const summaryPills = computed<HeroMetric[]>(() => [
    { label: '总作品', value: summary.value.total, accent: false },
    { label: '展示中', value: summary.value.visible_count, accent: true },
    { label: '待审核', value: summary.value.pending_audit_count, accent: false }
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
        const res: any = await staffCenterWorkLists({ page: pageNo, page_size: pageSize })
        const list = Array.isArray(res?.data) ? res.data : []
        summary.value = {
            total: Number(res?.summary?.total || 0),
            visible_count: Number(res?.summary?.visible_count || 0),
            pending_audit_count: Number(res?.summary?.pending_audit_count || 0)
        }
        hasLoaded.value = true
        pagingRef.value.complete(list)
    } catch (e) {
        hasLoaded.value = true
        pagingRef.value.complete(false)
    } finally {
        if (pageNo === 1) {
            loading.value = false
        }
    }
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
    background: rgba(255, 255, 255, 0.72);
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

.work-card + .work-card {
    margin-top: 16rpx;
}

.work-card__media {
    position: relative;
    overflow: hidden;
    border-radius: 30rpx;
    background: rgba(255, 255, 255, 0.72);
}

.work-card__cover {
    width: 100%;
    height: 276rpx;
    display: block;
    background: #f7f1ed;
}

.work-card__audit {
    position: absolute;
    top: 16rpx;
    right: 16rpx;
}

.work-card__body {
    margin-top: 18rpx;
}

.resource-head {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 16rpx;
}

.resource-head__copy {
    flex: 1;
    min-width: 0;
}

.resource-head__title {
    display: block;
    font-size: 30rpx;
    font-weight: 700;
    line-height: 1.4;
    color: var(--wm-text-primary, #1e2432);
    word-break: break-all;
}

.resource-head__meta {
    display: block;
    margin-top: 8rpx;
    font-size: 22rpx;
    font-weight: 600;
    line-height: 1.3;
    color: var(--wm-text-tertiary, #948f8b);
}

.stats-row {
    display: flex;
    flex-wrap: wrap;
    gap: 12rpx;
    margin-top: 14rpx;
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
}

.card-footer {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16rpx;
    margin-top: 18rpx;
}

.card-footer__hint {
    flex: 1;
    min-width: 0;
    font-size: 22rpx;
    font-weight: 600;
    line-height: 1.3;
    color: var(--wm-text-tertiary, #948f8b);
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
