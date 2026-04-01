<template>
    <page-meta :page-style="$theme.pageStyle" />
    <PageShell scene="consumer" hasSafeBottom>
        <BaseNavbar title="补拍申请" />

        <view class="reshoot-page">
            <view class="reshoot-page__head">
                <text class="reshoot-page__title">补拍 / 重拍申请</text>
                <text class="reshoot-page__desc">跟进审核状态、安排进度和补拍结果。</text>
            </view>

            <z-paging ref="paging" v-model="dataList" use-page-scroll @query="queryList">
                <template #top>
                    <scroll-view
                        scroll-x
                        class="reshoot-page__filter-scroll"
                        :show-scrollbar="false"
                    >
                        <view class="reshoot-page__filters">
                            <view
                                v-for="item in statusTabs"
                                :key="String(item.value)"
                                class="reshoot-page__filter"
                                :class="{ 'is-active': currentStatus === item.value }"
                                @click="changeStatus(item.value)"
                            >
                                {{ item.label }}
                            </view>
                        </view>
                    </scroll-view>
                </template>

                <view class="reshoot-list">
                    <view
                        v-for="item in dataList"
                        :key="item.id"
                        class="reshoot-card"
                        @click="goDetail(item.id)"
                    >
                        <view class="reshoot-card__head">
                            <view>
                                <text class="reshoot-card__sn">
                                    {{ item.reshoot_sn || `补拍申请 #${item.id}` }}
                                </text>
                                <text class="reshoot-card__title">
                                    {{ item.type_desc || getTypeText(item.type) }}
                                </text>
                            </view>
                            <view class="reshoot-card__status" :class="getStatusClass(item.status)">
                                {{ item.status_desc || getStatusText(item.status) }}
                            </view>
                        </view>

                        <view class="reshoot-card__meta-list">
                            <view class="reshoot-card__meta-item">
                                <text class="reshoot-card__meta-label">原因</text>
                                <text class="reshoot-card__meta-value">
                                    {{ item.reason_type_desc || getReasonText(item.reason_type) }}
                                </text>
                            </view>
                            <view v-if="item.expect_date" class="reshoot-card__meta-item">
                                <text class="reshoot-card__meta-label">期望日期</text>
                                <text class="reshoot-card__meta-value">{{ item.expect_date }}</text>
                            </view>
                        </view>

                        <view class="reshoot-card__footer">
                            <text class="reshoot-card__time">{{ item.create_time }}</text>
                            <text
                                v-if="item.status === 0 || item.status === 1"
                                class="reshoot-card__action"
                                @click.stop="handleCancel(item.id)"
                            >
                                取消申请
                            </text>
                        </view>
                    </view>
                </view>

                <template #empty>
                    <view class="reshoot-empty">
                        <tn-icon name="camera" size="112" color="#D9CDC7" />
                        <text class="reshoot-empty__title">暂无补拍申请</text>
                        <text class="reshoot-empty__desc">
                            如遇成片效果异常、天气影响或需要安排补拍，可发起申请。
                        </text>
                    </view>
                </template>
            </z-paging>

            <view class="reshoot-page__fab" @click="goCreate">
                <tn-icon name="plus" size="34" color="#FFFFFF" />
            </view>
        </view>
    </PageShell>
</template>

<script setup lang="ts">
import { ref, shallowRef } from 'vue'
import { getReshootLists, cancelReshoot } from '@/api/aftersale'
import BaseNavbar from '@/components/base/BaseNavbar.vue'
import PageShell from '@/components/base/PageShell.vue'
import { useThemeStore } from '@/stores/theme'
import { onLoad } from '@dcloudio/uni-app'

const $theme = useThemeStore()
const paging = shallowRef()
const dataList = ref<any[]>([])
const currentStatus = ref<number | string>('')

const statusTabs = [
    { label: '全部', value: '' },
    { label: '待审核', value: 0 },
    { label: '已通过', value: 1 },
    { label: '已拒绝', value: 2 },
    { label: '已安排', value: 3 },
    { label: '已完成', value: 4 }
]

const changeStatus = (status: number | string) => {
    currentStatus.value = status
    paging.value?.reload()
}

const queryList = async (pageNo: number, pageSize: number) => {
    try {
        const params: any = {
            page: pageNo,
            limit: pageSize
        }
        if (currentStatus.value !== '') {
            params.status = currentStatus.value
        }
        const res = await getReshootLists(params)
        const lists = res?.data?.lists || res?.lists || []
        paging.value?.complete(lists)
    } catch (error) {
        paging.value?.complete(false)
    }
}

const getStatusClass = (status: number) => {
    const map: Record<number, string> = {
        0: 'status-pending',
        1: 'status-approved',
        2: 'status-rejected',
        3: 'status-scheduled',
        4: 'status-completed',
        5: 'status-cancelled'
    }
    return map[status] || ''
}

const getStatusText = (status: number) => {
    const map: Record<number, string> = {
        0: '待审核',
        1: '审核通过',
        2: '审核拒绝',
        3: '已安排',
        4: '已完成',
        5: '已取消'
    }
    return map[status] || '未知'
}

const getTypeText = (type: number) => {
    const map: Record<number, string> = {
        1: '补拍',
        2: '重拍'
    }
    return map[type] || '补拍'
}

const getReasonText = (reasonType: number) => {
    const map: Record<number, string> = {
        1: '效果不满意',
        2: '天气原因',
        3: '设备故障',
        4: '人员原因',
        5: '其他'
    }
    return map[reasonType] || '其他'
}

const goDetail = (id: number) => {
    uni.navigateTo({
        url: `/packages/pages/aftersale/reshoot_detail?id=${id}`
    })
}

const goCreate = () => {
    uni.navigateTo({ url: '/packages/pages/aftersale/apply_reshoot' })
}

const handleCancel = async (id: number) => {
    uni.showModal({
        title: '确认取消',
        content: '确定要取消这条补拍申请吗？',
        success: async (res) => {
            if (!res.confirm) {
                return
            }

            try {
                await cancelReshoot(id)
                uni.showToast({ title: '取消成功', icon: 'none' })
                paging.value?.reload()
            } catch (error: any) {
                uni.showToast({ title: error?.message || '取消失败', icon: 'none' })
            }
        }
    })
}

onLoad((options: any) => {
    if (options?.status !== undefined) {
        currentStatus.value = Number(options.status)
    }
})
</script>

<style lang="scss" scoped>
@import '../../../styles/aftersale.scss';

.reshoot-page {
    @include aftersale-page-base;
    padding: 0 0 150rpx;
}

.reshoot-page__head {
    padding: 14rpx 20rpx 18rpx;
}

.reshoot-page__title {
    display: block;
    font-size: 40rpx;
    font-weight: 700;
    color: var(--wm-text-primary, #1e2432);
}

.reshoot-page__desc {
    display: block;
    margin-top: 10rpx;
    font-size: 24rpx;
    line-height: 1.6;
    color: var(--wm-text-secondary, #7f7b78);
}

.reshoot-page__filter-scroll {
    margin-bottom: 18rpx;
}

.reshoot-page__filters {
    @include aftersale-filter-tabs;
}

.reshoot-page__filter {
    @include aftersale-filter-item;

    &.is-active {
        background: linear-gradient(135deg, #e85a4f 0%, #c99b73 100%);
        border-color: transparent;
        color: #ffffff;
        box-shadow: 0 12rpx 24rpx rgba(201, 155, 115, 0.2);
    }
}

.reshoot-list {
    display: flex;
    flex-direction: column;
    gap: 16rpx;
    padding: 0 20rpx;
}

.reshoot-card {
    @include aftersale-section-card;
}

.reshoot-card__head {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 16rpx;
}

.reshoot-card__sn {
    display: block;
    font-size: 22rpx;
    color: var(--wm-text-tertiary, #b4aca8);
}

.reshoot-card__title {
    display: block;
    margin-top: 10rpx;
    font-size: 30rpx;
    font-weight: 700;
    color: var(--wm-text-primary, #1e2432);
}

.reshoot-card__status {
    flex-shrink: 0;
    padding: 8rpx 16rpx;
    border-radius: 999rpx;
    font-size: 22rpx;
    font-weight: 600;
}

.reshoot-card__meta-list {
    display: flex;
    flex-direction: column;
    gap: 12rpx;
    margin-top: 18rpx;
}

.reshoot-card__meta-item {
    display: flex;
    gap: 14rpx;
}

.reshoot-card__meta-label {
    width: 110rpx;
    flex-shrink: 0;
    font-size: 24rpx;
    color: var(--wm-text-tertiary, #b4aca8);
}

.reshoot-card__meta-value {
    flex: 1;
    min-width: 0;
    font-size: 25rpx;
    line-height: 1.6;
    color: var(--wm-text-secondary, #7f7b78);
}

.reshoot-card__footer {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16rpx;
    margin-top: 18rpx;
}

.reshoot-card__time {
    font-size: 22rpx;
    color: var(--wm-text-tertiary, #b4aca8);
}

.reshoot-card__action {
    font-size: 24rpx;
    font-weight: 600;
    color: var(--wm-color-danger, #b44a3a);
}

.reshoot-empty {
    @include aftersale-empty-state;
}

.reshoot-empty__title {
    margin-top: 24rpx;
    font-size: 32rpx;
    font-weight: 700;
    color: var(--wm-text-primary, #1e2432);
}

.reshoot-empty__desc {
    margin-top: 12rpx;
    font-size: 26rpx;
    line-height: 1.7;
    color: var(--wm-text-secondary, #7f7b78);
}

.reshoot-page__fab {
    @include aftersale-fab;
    background: linear-gradient(135deg, #e85a4f 0%, #c99b73 100%);
}

.status-pending {
    color: #c98524;
    background: rgba(201, 133, 36, 0.12);
}

.status-approved {
    color: #2f7d58;
    background: rgba(47, 125, 88, 0.12);
}

.status-rejected {
    color: #b44a3a;
    background: rgba(180, 74, 58, 0.12);
}

.status-scheduled {
    color: #8f6ab5;
    background: rgba(143, 106, 181, 0.12);
}

.status-completed {
    color: #607086;
    background: rgba(96, 112, 134, 0.12);
}

.status-cancelled {
    color: #978b83;
    background: rgba(151, 139, 131, 0.12);
}
</style>
