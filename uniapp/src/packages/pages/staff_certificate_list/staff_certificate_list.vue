<template>
    <page-meta :page-style="$theme.pageStyle" />
    <PageShell scene="staff" tone="workspace">
        <BaseNavbar
            title="证书管理"
            variant="solid"
            title-align="center"
            bg-color="#191713"
            text-color="#FFFDF8"
        />

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
                        <StaffWorkspaceHero title="资质证书" action-text="新增" @action="handleAdd">
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
                        :meta="listSectionMeta"
                    />

                    <LoadingState v-if="loading && !hasLoaded" text="证书加载中" />

                    <template v-else-if="certificateList.length">
                        <BaseCard
                            v-for="item in certificateList"
                            :key="item.id"
                            variant="panel"
                            scene="staff"
                            class="certificate-card"
                            padding="22rpx"
                            border-radius="34rpx"
                            interactive
                            @click="handleEdit(item)"
                        >
                            <view class="certificate-card__main">
                                <image
                                    class="certificate-card__image"
                                    :src="item.image || defaultImage"
                                    mode="aspectFill"
                                />
                                <view class="certificate-card__body">
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
                                <BaseButton
                                    :label="Number(item.verify_status) === 2 ? '修改后重提' : '编辑'"
                                    variant="light"
                                    size="sm"
                                    height="68rpx"
                                    block
                                    @click.stop="handleEdit(item)"
                                />
                                <BaseButton
                                    label="删除"
                                    variant="danger"
                                    size="sm"
                                    height="68rpx"
                                    block
                                    @click.stop="handleDelete(item)"
                                />
                            </view>
                        </BaseCard>
                    </template>
                    <EmptyState
                        v-else-if="hasLoaded"
                        :title="emptyStateTitle"
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
import BaseButton from '@/components/base/BaseButton.vue'
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
    gap: 18rpx;
    box-sizing: border-box;

    &--top {
        padding-top: 20rpx;
    }

    &--list {
        padding-top: 18rpx;
        padding-bottom: calc(48rpx + env(safe-area-inset-bottom));
    }
}

.certificate-card + .certificate-card {
    margin-top: 22rpx;
}

.certificate-card__main {
    display: flex;
    align-items: flex-start;
    gap: 18rpx;
}

.certificate-card__image {
    width: 148rpx;
    height: 116rpx;
    border-radius: 28rpx;
    background: linear-gradient(135deg, #f7f0df 0%, #fffdf8 100%);
    border: 1rpx solid rgba(216, 194, 138, 0.58);
    flex-shrink: 0;
}

.certificate-card__body {
    flex: 1;
    min-width: 0;
}

.certificate-card__title-row {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 14rpx;
}

.certificate-card__title {
    flex: 1;
    min-width: 0;
    display: -webkit-box;
    overflow: hidden;
    font-size: 31rpx;
    font-weight: 700;
    line-height: 1.35;
    color: #111111;
    word-break: break-all;
    -webkit-box-orient: vertical;
    -webkit-line-clamp: 2;
}

.certificate-card__meta {
    display: block;
    margin-top: 8rpx;
    overflow: hidden;
    font-size: 24rpx;
    line-height: 1.45;
    color: #5f5a50;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.chip-row {
    display: flex;
    flex-wrap: wrap;
    gap: 12rpx;
    margin-top: 18rpx;
}
.info-chip {
    min-height: 48rpx;
    padding: 0 16rpx;
    display: inline-flex;
    align-items: center;
    border-radius: var(--wm-radius-pill, 999rpx);
    background: rgba(255, 255, 255, 0.74);
    border: 1rpx solid var(--wm-color-border, #e7e2d6);
    font-size: 22rpx;
    font-weight: 600;
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
    display: flex;
    gap: 16rpx;
    margin-top: 22rpx;
}

.action-row :deep(.base-button) {
    flex: 1;
    min-width: 0;
}
</style>
