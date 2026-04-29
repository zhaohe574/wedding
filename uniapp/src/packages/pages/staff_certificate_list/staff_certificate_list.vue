<template>
    <page-meta :page-style="$theme.pageStyle" />
    <PageShell scene="staff">
        <BaseNavbar title="证书管理" />

        <view class="page-container wm-page-content">
            <z-paging
                ref="pagingRef"
                v-model="certificateList"
                :auto="false"
                :hide-empty-view="true"
                :paging-style="resolvedPagingStyle"
                @query="queryList"
            >
                <template #top>
                    <view class="page-section page-section--top">
                        <StaffWorkspaceHero
                            eyebrow="服务人员中心"
                            title="证书管理"
                            description="维护资质资料与审核反馈"
                            action-text="新增证书"
                            @action="handleAdd"
                        >
                            <StaffFilterBar
                                :items="certificateFilterItems"
                                :model-value="currentFilter"
                                @select="handleFilterSelect"
                            />
                        </StaffWorkspaceHero>
                    </view>
                </template>

                <view class="page-section page-section--list">
                    <StaffSectionHeader
                        :title="listSectionTitle"
                        :description="listSectionDesc"
                    />

                    <LoadingState v-if="loading && !hasLoaded" text="正在同步证书资料..." />

                    <template v-else-if="certificateList.length">
                        <BaseCard
                            v-for="item in certificateList"
                            :key="item.id"
                            variant="glass"
                            scene="staff"
                            class="certificate-card"
                            interactive
                            @click="handleEdit(item)"
                        >
                            <view class="certificate-card__head">
                                <image
                                    class="certificate-card__image"
                                    :src="item.image || defaultImage"
                                    mode="aspectFill"
                                />
                                <view class="certificate-card__copy">
                                    <view class="certificate-card__title-row">
                                        <text class="certificate-card__title">{{
                                            item.name || '未命名证书'
                                        }}</text>
                                        <StatusBadge
                                            :tone="getStatusTone(Number(item.verify_status))"
                                            size="sm"
                                        >
                                            {{ item.verify_status_desc || '待审核' }}
                                        </StatusBadge>
                                    </view>
                                    <text v-if="item.type" class="certificate-card__meta"
                                        >类型：{{ item.type }}</text
                                    >
                                    <text v-if="item.sn" class="certificate-card__meta"
                                        >编号：{{ item.sn }}</text
                                    >
                                </view>
                            </view>

                            <view class="chip-row">
                                <view v-if="item.issue_org" class="info-chip">{{
                                    item.issue_org
                                }}</view>
                                <view class="info-chip">{{ formatDateRange(item) }}</view>
                                <view
                                    :class="[
                                        'info-chip',
                                        item.is_expired ? 'info-chip--danger' : 'info-chip--success'
                                    ]"
                                >
                                    {{ item.is_expired ? '已过期' : '有效中' }}
                                </view>
                            </view>

                            <view v-if="item.reject_reason" class="certificate-card__reason">
                                <text class="certificate-card__reason-label">驳回原因</text>
                                <text class="certificate-card__reason-text">{{
                                    item.reject_reason
                                }}</text>
                            </view>

                            <view class="action-row">
                                <view
                                    class="action-btn action-btn--ghost"
                                    @click.stop="handleEdit(item)"
                                >
                                    {{ Number(item.verify_status) === 2 ? '修改后重提' : '编辑' }}
                                </view>
                                <view
                                    class="action-btn action-btn--danger"
                                    @click.stop="handleDelete(item)"
                                >
                                    删除
                                </view>
                            </view>
                        </BaseCard>
                    </template>

                    <EmptyState
                        v-else-if="hasLoaded"
                        :title="emptyStateTitle"
                        description="补齐资质后会出现在这里。"
                        action-text="新增证书"
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
import BaseCard from '@/components/base/BaseCard.vue'
import BaseNavbar from '@/components/base/BaseNavbar.vue'
import EmptyState from '@/components/base/EmptyState.vue'
import LoadingState from '@/components/base/LoadingState.vue'
import PageShell from '@/components/base/PageShell.vue'
import StatusBadge from '@/components/base/StatusBadge.vue'
import StaffFilterBar from '@/packages/components/staff-workspace/staff-filter-bar.vue'
import StaffSectionHeader from '@/packages/components/staff-workspace/staff-section-header.vue'
import StaffWorkspaceHero from '@/packages/components/staff-workspace/staff-workspace-hero.vue'
import { staffCenterCertificateDelete, staffCenterCertificateLists } from '@/api/staffCenter'
import { useFixedNavbarPagingStyle } from '@/packages/common/hooks/useFixedNavbarPagingStyle'
import { ensureStaffCenterAccess } from '@/packages/common/utils/staff-center'
import { useThemeStore } from '@/stores/theme'

type FilterKey = 'all' | 'pending' | 'approved' | 'rejected'
type BadgeTone = 'neutral' | 'success' | 'warning' | 'danger' | 'info'

interface SummaryState {
    total: number
    pending_count: number
    approved_count: number
    rejected_count: number
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
const certificateList = ref<any[]>([])
const loading = ref(false)
const hasLoaded = ref(false)
const currentFilter = ref<FilterKey>('all')
const summary = ref<SummaryState>({
    total: 0,
    pending_count: 0,
    approved_count: 0,
    rejected_count: 0
})
const defaultImage = '/static/images/user/default_avatar.png'

const heroMetrics = computed(() => [
    { key: 'all' as FilterKey, label: '全部', value: summary.value.total },
    { key: 'pending' as FilterKey, label: '待审核', value: summary.value.pending_count },
    { key: 'approved' as FilterKey, label: '已通过', value: summary.value.approved_count },
    { key: 'rejected' as FilterKey, label: '已拒绝', value: summary.value.rejected_count }
])

const certificateFilterItems = computed(() =>
    heroMetrics.value.map((item) => ({
        label: item.label,
        value: item.key,
        count: item.value
    }))
)

const emptyStateTitle = computed(() => {
    const titleMap: Record<FilterKey, string> = {
        all: '暂时还没有提交证书',
        pending: '当前没有待审核证书',
        approved: '当前没有已通过证书',
        rejected: '当前没有已拒绝证书'
    }
    return titleMap[currentFilter.value]
})
const listSectionTitle = computed(() => {
    const map: Record<FilterKey, string> = {
        all: '全部证书',
        pending: '待审核证书',
        approved: '已通过证书',
        rejected: '已拒绝证书'
    }
    return map[currentFilter.value]
})
const listSectionDesc = computed(() => {
    const map: Record<FilterKey, string> = {
        all: '集中查看全部资质材料，保持证书信息完整可追踪。',
        pending: '优先关注审核中的资质，及时补充缺失信息。',
        approved: '已通过的证书会作为对外展示的重要信任信息。',
        rejected: '根据驳回原因修正内容后再提交审核。'
    }
    return map[currentFilter.value]
})
const listSectionMeta = computed(() => {
    const map: Record<FilterKey, number> = {
        all: summary.value.total,
        pending: summary.value.pending_count,
        approved: summary.value.approved_count,
        rejected: summary.value.rejected_count
    }
    return `共 ${map[currentFilter.value]} 项`
})

const getStatusTone = (status: number): BadgeTone => {
    const map: Record<number, BadgeTone> = {
        0: 'warning',
        1: 'success',
        2: 'danger'
    }
    return map[status] || 'neutral'
}

const formatDateRange = (item: any) => {
    const issueDate = item.issue_date || '未知发证时间'
    const expireDate = item.expire_date || '长期有效'
    return `${issueDate} 至 ${expireDate}`
}

const queryList = async (pageNo: number, pageSize: number) => {
    if (pageNo === 1) {
        loading.value = true
    }

    try {
        const params: Record<string, any> = {
            page_size: pageSize
        }
        if (pageNo > 1) {
            params.page_no = pageNo
        }
        if (currentFilter.value === 'pending') {
            params.verify_status = 0
        } else if (currentFilter.value === 'approved') {
            params.verify_status = 1
        } else if (currentFilter.value === 'rejected') {
            params.verify_status = 2
        }

        const res: any = await staffCenterCertificateLists(params)
        const list = Array.isArray(res?.data) ? res.data : []
        summary.value = {
            total: Number(res?.summary?.total || 0),
            pending_count: Number(res?.summary?.pending_count || 0),
            approved_count: Number(res?.summary?.approved_count || 0),
            rejected_count: Number(res?.summary?.rejected_count || 0)
        }
        hasLoaded.value = true
        pagingRef.value.complete(list)
    } catch (error: any) {
        const msg = typeof error === 'string' ? error : error?.msg || error?.message || '加载失败'
        uni.showToast({ title: msg, icon: 'none' })
        hasLoaded.value = true
        pagingRef.value.complete(false)
    } finally {
        if (pageNo === 1) {
            loading.value = false
        }
    }
}

const switchFilter = (filter: FilterKey) => {
    if (currentFilter.value === filter) return
    currentFilter.value = filter
    certificateList.value = []
    hasLoaded.value = false
    loading.value = true
    pagingRef.value?.reload()
}

const handleFilterSelect = (value: string | number) => {
    switchFilter(String(value) as FilterKey)
}

const handleAdd = () => {
    uni.navigateTo({ url: '/packages/pages/staff_certificate_edit/staff_certificate_edit' })
}

const handleEdit = (item: any) => {
    uni.navigateTo({
        url: `/packages/pages/staff_certificate_edit/staff_certificate_edit?id=${item.id}`
    })
}

const handleDelete = (item: any) => {
    uni.showModal({
        title: '确认删除',
        content: '删除后不可恢复，是否继续？',
        success: async (res) => {
            if (!res.confirm) return
            try {
                await staffCenterCertificateDelete({ id: item.id })
                uni.showToast({ title: '删除成功', icon: 'success' })
                pagingRef.value?.reload()
            } catch (error: any) {
                const msg =
                    typeof error === 'string' ? error : error?.msg || error?.message || '删除失败'
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
            rgba(11, 11, 11, 0.1) 0,
            rgba(248, 247, 242, 0) 36%
        ),
        linear-gradient(180deg, var(--wm-color-bg-page, #ffffff) 0%, #f8f7f2 100%);
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

.section-head {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 20rpx;
    padding: 0 6rpx;
}

.section-head__copy {
    flex: 1;
    min-width: 0;
    display: flex;
    flex-direction: column;
    gap: 8rpx;
}

.section-head__title {
    font-size: 28rpx;
    font-weight: 700;
    line-height: 1.3;
    color: var(--wm-text-primary, #111111);
}

.section-head__desc {
    font-size: 22rpx;
    font-weight: 600;
    line-height: 1.5;
    color: var(--wm-text-secondary, #5f5a50);
}

.section-head__meta {
    flex-shrink: 0;
    font-size: 22rpx;
    font-weight: 700;
    line-height: 1.4;
    color: var(--wm-color-primary, #0b0b0b);
}

.hero-card__head,
.certificate-card__head,
.certificate-card__title-row,
.chip-row,
.action-row,
.empty-state {
    display: flex;
}

.hero-card__head,
.certificate-card__head,
.certificate-card__title-row,
.action-row {
    align-items: center;
}

.hero-card__head,
.certificate-card__title-row {
    justify-content: space-between;
}

.hero-card__copy,
.certificate-card__copy {
    flex: 1;
    min-width: 0;
}

.hero-card__eyebrow {
    font-size: 20rpx;
    font-weight: 700;
    color: var(--wm-color-primary, #0b0b0b);
}

.hero-card__title {
    display: block;
    margin-top: 10rpx;
    font-size: 40rpx;
    font-weight: 700;
    color: var(--wm-text-primary, #111111);
}

.hero-card__meta {
    display: block;
    margin-top: 8rpx;
    font-size: 24rpx;
    color: var(--wm-text-secondary, #5f5a50);
}

.hero-card__action,
.empty-state__action,
.action-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: 999rpx;
}

.hero-card__action,
.empty-state__action {
    min-height: 56rpx;
    padding: 0 20rpx;
    background: rgba(255, 255, 255, 0.92);
    border: 1rpx solid rgba(216, 194, 138, 0.88);
}

.hero-card__action-text,
.empty-state__action-text {
    font-size: 24rpx;
    font-weight: 600;
    color: var(--wm-color-primary, #0b0b0b);
}

.hero-metrics {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 14rpx;
    margin-top: 24rpx;
}

.hero-metric {
    padding: 18rpx 12rpx;
    border-radius: 24rpx;
    background: rgba(255, 255, 255, 0.72);
    border: 1rpx solid rgba(216, 194, 138, 0.72);
    text-align: center;
}

.hero-metric--selected {
    background: rgba(11, 11, 11, 0.12);
    border-color: rgba(11, 11, 11, 0.28);
}

.hero-metric__label {
    display: block;
    font-size: 22rpx;
    color: #5f5a50;
}

.hero-metric__value {
    display: block;
    margin-top: 8rpx;
    font-size: 30rpx;
    font-weight: 700;
    color: #111111;
}

.certificate-card__head {
    gap: 18rpx;
}

.certificate-card__image {
    width: 136rpx;
    height: 104rpx;
    border-radius: 24rpx;
    background: #f8f7f2;
    flex-shrink: 0;
}

.certificate-card__title {
    flex: 1;
    min-width: 0;
    font-size: 30rpx;
    font-weight: 700;
    color: #111111;
}

.certificate-card__meta {
    display: block;
    margin-top: 10rpx;
    font-size: 24rpx;
    color: #5f5a50;
}

.chip-row {
    flex-wrap: wrap;
    gap: 12rpx;
    margin-top: 18rpx;
}

.info-chip {
    padding: 10rpx 18rpx;
    border-radius: 999rpx;
    background: #F7F0DF;
    font-size: 22rpx;
    color: #5f5a50;
}

.info-chip--success {
    background: rgba(77, 74, 66, 0.12);
    color: #4D4A42;
}

.info-chip--danger {
    background: rgba(11, 11, 11, 0.12);
    color: #5a4433;
}

.certificate-card__reason {
    margin-top: 18rpx;
    padding: 18rpx 20rpx;
    border-radius: 24rpx;
    background: rgba(11, 11, 11, 0.08);
}

.certificate-card__reason-label {
    display: block;
    font-size: 22rpx;
    font-weight: 700;
    color: #5a4433;
}

.certificate-card__reason-text {
    display: block;
    margin-top: 8rpx;
    font-size: 24rpx;
    line-height: 1.6;
    color: #5A4433;
}

.action-row {
    gap: 16rpx;
    margin-top: 22rpx;
}

.action-btn {
    flex: 1;
    min-height: 72rpx;
    font-size: 26rpx;
    font-weight: 600;
}

.action-btn--ghost {
    background: #fff;
    border: 1rpx solid #e7e2d6;
    color: #5f5a50;
}

.action-btn--danger {
    background: rgba(11, 11, 11, 0.12);
    color: #5a4433;
}

.empty-state {
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 100rpx 0 60rpx;
}

.empty-state__title {
    margin-top: 20rpx;
    font-size: 26rpx;
    color: #5f5a50;
}

.empty-state__action {
    margin-top: 28rpx;
}
</style>
