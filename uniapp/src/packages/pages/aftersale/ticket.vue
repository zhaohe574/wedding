<template>
    <page-meta :page-style="$theme.pageStyle" />
    <PageShell scene="consumer" hasSafeBottom>
        <BaseNavbar title="我的工单" />

        <view class="ticket-page">
            <view class="ticket-page__head">
                <text class="ticket-page__title">售后工单</text>
                <text class="ticket-page__desc">查看当前进度，处理咨询、投诉与售后问题。</text>
            </view>

            <z-paging ref="paging" v-model="dataList" use-page-scroll @query="queryList">
                <template #top>
                    <scroll-view
                        scroll-x
                        class="ticket-page__filter-scroll"
                        :show-scrollbar="false"
                    >
                        <view class="ticket-page__filters">
                            <view
                                v-for="item in statusTabs"
                                :key="String(item.value)"
                                class="ticket-page__filter"
                                :class="{ 'is-active': currentStatus === item.value }"
                                @click="changeStatus(item.value)"
                            >
                                {{ item.label }}
                            </view>
                        </view>
                    </scroll-view>
                </template>

                <view class="ticket-list">
                    <view
                        v-for="item in dataList"
                        :key="item.id"
                        class="ticket-card"
                        @click="goDetail(item.id)"
                    >
                        <view class="ticket-card__head">
                            <view>
                                <text class="ticket-card__sn">{{ item.ticket_sn }}</text>
                                <text class="ticket-card__title">{{ item.title }}</text>
                            </view>
                            <view class="ticket-card__status" :class="getStatusClass(item.status)">
                                {{ item.status_desc || getStatusText(item.status) }}
                            </view>
                        </view>

                        <text v-if="item.content" class="ticket-card__content">{{
                            item.content
                        }}</text>

                        <view class="ticket-card__meta">
                            <text class="ticket-card__chip">
                                {{ item.type_desc || getTypeText(item.type) }}
                            </text>
                            <text class="ticket-card__time">{{ item.create_time }}</text>
                        </view>
                    </view>
                </view>

                <template #empty>
                    <view class="ticket-empty">
                        <tn-icon name="file-text" size="112" color="#D9CDC7" />
                        <text class="ticket-empty__title">还没有工单记录</text>
                        <text class="ticket-empty__desc">
                            预约问题、服务咨询和售后处理都会在这里集中展示。
                        </text>
                    </view>
                </template>
            </z-paging>

            <view class="ticket-page__fab" @click="goCreate">
                <tn-icon name="plus" size="34" color="#FFFFFF" />
            </view>
        </view>
    </PageShell>
</template>

<script setup lang="ts">
import { ref, shallowRef } from 'vue'
import { getTicketLists } from '@/api/aftersale'
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
    { label: '待处理', value: 0 },
    { label: '处理中', value: 1 },
    { label: '待确认', value: 2 },
    { label: '已完成', value: 3 },
    { label: '已关闭', value: 4 }
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
        const res = await getTicketLists(params)
        const lists = res?.data?.lists || res?.lists || []
        paging.value?.complete(lists)
    } catch (error) {
        paging.value?.complete(false)
    }
}

const getStatusClass = (status: number) => {
    const map: Record<number, string> = {
        0: 'status-pending',
        1: 'status-processing',
        2: 'status-confirming',
        3: 'status-completed',
        4: 'status-closed',
        5: 'status-cancelled'
    }
    return map[status] || ''
}

const getStatusText = (status: number) => {
    const map: Record<number, string> = {
        0: '待处理',
        1: '处理中',
        2: '待确认',
        3: '已完成',
        4: '已关闭',
        5: '已取消'
    }
    return map[status] || '未知'
}

const getTypeText = (type: number) => {
    const map: Record<number, string> = {
        1: '投诉',
        2: '咨询',
        3: '售后',
        4: '建议',
        5: '其他'
    }
    return map[type] || '其他'
}

const goDetail = (id: number) => {
    uni.navigateTo({ url: `/packages/pages/aftersale/ticket_detail?id=${id}` })
}

const goCreate = () => {
    uni.navigateTo({ url: '/packages/pages/aftersale/index?action=create_ticket' })
}

onLoad((options: any) => {
    if (options?.status !== undefined) {
        currentStatus.value = Number(options.status)
    }
})
</script>

<style lang="scss" scoped>
@import '../../../styles/aftersale.scss';

.ticket-page {
    @include aftersale-page-base;
    padding: 0 0 150rpx;
}

.ticket-page__head {
    padding: 14rpx 20rpx 18rpx;
}

.ticket-page__title {
    display: block;
    font-size: 40rpx;
    font-weight: 700;
    color: var(--wm-text-primary, #1e2432);
}

.ticket-page__desc {
    display: block;
    margin-top: 10rpx;
    font-size: 24rpx;
    line-height: 1.6;
    color: var(--wm-text-secondary, #7f7b78);
}

.ticket-page__filter-scroll {
    margin-bottom: 18rpx;
}

.ticket-page__filters {
    @include aftersale-filter-tabs;
}

.ticket-page__filter {
    @include aftersale-filter-item;

    &.is-active {
        background: var(--wm-color-primary, #e85a4f);
        border-color: var(--wm-color-primary, #e85a4f);
        color: #ffffff;
        box-shadow: 0 12rpx 24rpx rgba(232, 90, 79, 0.18);
    }
}

.ticket-list {
    display: flex;
    flex-direction: column;
    gap: 16rpx;
    padding: 0 20rpx;
}

.ticket-card {
    @include aftersale-section-card;
}

.ticket-card__head {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 16rpx;
}

.ticket-card__sn {
    display: block;
    font-size: 22rpx;
    color: var(--wm-text-tertiary, #b4aca8);
}

.ticket-card__title {
    display: block;
    margin-top: 10rpx;
    font-size: 30rpx;
    font-weight: 700;
    line-height: 1.35;
    color: var(--wm-text-primary, #1e2432);
}

.ticket-card__status {
    flex-shrink: 0;
    padding: 8rpx 16rpx;
    border-radius: 999rpx;
    font-size: 22rpx;
    font-weight: 600;
}

.ticket-card__content {
    display: block;
    margin-top: 16rpx;
    font-size: 25rpx;
    line-height: 1.65;
    color: var(--wm-text-secondary, #7f7b78);
}

.ticket-card__meta {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16rpx;
    margin-top: 18rpx;
}

.ticket-card__chip {
    padding: 8rpx 14rpx;
    border-radius: 999rpx;
    background: var(--wm-color-bg-soft, #fff7f4);
    font-size: 22rpx;
    color: var(--wm-color-primary, #e85a4f);
}

.ticket-card__time {
    font-size: 22rpx;
    color: var(--wm-text-tertiary, #b4aca8);
}

.ticket-empty {
    @include aftersale-empty-state;
}

.ticket-empty__title {
    margin-top: 24rpx;
    font-size: 32rpx;
    font-weight: 700;
    color: var(--wm-text-primary, #1e2432);
}

.ticket-empty__desc {
    margin-top: 12rpx;
    font-size: 26rpx;
    line-height: 1.7;
    color: var(--wm-text-secondary, #7f7b78);
}

.ticket-page__fab {
    @include aftersale-fab;
    background: linear-gradient(
        135deg,
        var(--wm-color-primary, #e85a4f) 0%,
        var(--wm-color-secondary, #c99b73) 100%
    );
}

.status-pending {
    color: #c98524;
    background: rgba(201, 133, 36, 0.12);
}

.status-processing {
    color: #607086;
    background: rgba(96, 112, 134, 0.12);
}

.status-confirming {
    color: #8f6ab5;
    background: rgba(143, 106, 181, 0.12);
}

.status-completed {
    color: #2f7d58;
    background: rgba(47, 125, 88, 0.12);
}

.status-closed,
.status-cancelled {
    color: #978b83;
    background: rgba(151, 139, 131, 0.12);
}
</style>
