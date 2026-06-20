<template>
    <page-meta :page-style="$theme.pageStyle" />
    <PageShell scene="consumer" tone="workspace" hasSafeBottom>
        <BaseNavbar
            title="我的工单"
            variant="solid"
            bg-color="#191713"
            text-color="#FFFDF8"
        />

        <view class="aftersale-list-page">
            <view class="aftersale-list-page__wrapper wm-page-content">
                <view class="aftersale-list-page__filters">
                    <AfterSaleFilterTabs
                        v-model="currentStatus"
                        :tabs="statusTabs"
                        @change="changeStatus"
                    />
                </view>

                <z-paging ref="paging" v-model="dataList" use-page-scroll @query="queryList">
                    <view class="aftersale-record-list">
                        <BaseCard
                            v-for="item in dataList"
                            :key="item.id"
                            variant="list"
                            scene="consumer"
                            padding="24rpx"
                            border-radius="32rpx"
                            border="1rpx solid rgba(216, 201, 173, 0.9)"
                            box-shadow="0 16rpx 36rpx rgba(74, 43, 24, 0.07)"
                            class="aftersale-record-card"
                        >
                            <view class="aftersale-record-card__top">
                                <view class="aftersale-record-card__title-group">
                                    <view class="aftersale-record-card__eyebrow">
                                        <BaseIcon name="file-text" size="22" color="#B8954A" />
                                        <text>{{ getTicketEyebrowText(item) }}</text>
                                    </view>
                                    <text class="aftersale-record-card__title">
                                        {{ item.title || '未命名工单' }}
                                    </text>
                                </view>

                                <StatusBadge :tone="getTicketMeta(item).tone" size="sm" dot>
                                    {{ getTicketMeta(item).label }}
                                </StatusBadge>
                            </view>

                            <view class="aftersale-record-card__meta-grid">
                                <view class="aftersale-record-card__meta-item">
                                    <view class="aftersale-record-card__meta-icon">
                                        <BaseIcon name="order" size="24" color="#9A9388" />
                                    </view>
                                    <view class="aftersale-record-card__meta-copy">
                                        <text class="aftersale-record-card__meta-label">
                                            关联订单
                                        </text>
                                        <text class="aftersale-record-card__meta-value">
                                            {{ getOrderText(item) }}
                                        </text>
                                    </view>
                                </view>

                                <view class="aftersale-record-card__meta-item">
                                    <view class="aftersale-record-card__meta-icon">
                                        <BaseIcon name="calendar" size="24" color="#9A9388" />
                                    </view>
                                    <view class="aftersale-record-card__meta-copy">
                                        <text class="aftersale-record-card__meta-label">
                                            提交时间
                                        </text>
                                        <text class="aftersale-record-card__meta-value">
                                            {{ formatSubmitTimeLabel(item.create_time) }}
                                        </text>
                                    </view>
                                </view>
                            </view>

                            <view class="aftersale-record-card__footer">
                                <text class="aftersale-record-card__sn">
                                    {{ getTicketSnText(item) }}
                                </text>
                                <BaseButton
                                    class="aftersale-record-card__button"
                                    :label="getTicketActionText(item)"
                                    :variant="getTicketActionVariant(item)"
                                    size="sm"
                                    height="62rpx"
                                    font-size="23rpx"
                                    icon="right"
                                    icon-position="right"
                                    @click.stop="goDetail(item.id)"
                                />
                            </view>
                        </BaseCard>
                    </view>

                    <template #empty>
                        <AfterSaleEmptyState icon="file-text" title="暂无工单" />
                    </template>
                </z-paging>
            </view>
        </view>
    </PageShell>
</template>

<script setup lang="ts">
import { getTicketLists } from '@/packages/common/api/aftersale'
import BaseButton from '@/components/base/BaseButton.vue'
import BaseCard from '@/components/base/BaseCard.vue'
import BaseIcon from '@/components/base/BaseIcon.vue'
import BaseNavbar from '@/components/base/BaseNavbar.vue'
import PageShell from '@/components/base/PageShell.vue'
import StatusBadge from '@/components/base/StatusBadge.vue'
import { useThemeStore } from '@/stores/theme'
import { onLoad } from '@dcloudio/uni-app'
import AfterSaleEmptyState from './components/AfterSaleEmptyState.vue'
import AfterSaleFilterTabs from './components/AfterSaleFilterTabs.vue'
import { useAftersaleListPage } from './composables/useAftersaleListPage'
import { formatSubmitTimeLabel, getTicketStatusMeta, getValueText } from './shared'

type ActionButtonVariant = 'dark' | 'light'

const $theme = useThemeStore()
const { paging, dataList, currentStatus, changeStatus, applyQueryStatus, initStatus } =
    useAftersaleListPage()

const statusTabs = [
    { label: '全部', value: '' },
    { label: '未完成', value: 'unfinished' },
    { label: '待处理', value: 0 },
    { label: '处理中', value: 1 },
    { label: '待确认', value: 2 },
    { label: '已解决', value: 3 }
]

const queryList = async (pageNo: number, pageSize: number) => {
    try {
        const res = await getTicketLists(
            applyQueryStatus({
                page: pageNo,
                limit: pageSize
            })
        )
        const lists = res?.data?.lists || res?.lists || []
        paging.value?.complete(lists)
    } catch (error) {
        paging.value?.complete(false)
    }
}

const getTicketMeta = (item: any) => getTicketStatusMeta(Number(item?.status || 0))

const getTicketEyebrowText = (item: any) => {
    const typeText = getValueText(item?.type_desc, '')
    const priorityText = getValueText(item?.priority_desc, '')
    return [typeText, priorityText].filter(Boolean).join(' · ') || '售后工单'
}

const getOrderText = (item: any) =>
    getValueText(item?.order_info?.order_sn || item?.order?.order_sn, '未关联订单')

const getTicketSnText = (item: any) =>
    getValueText(item?.ticket_sn || (item?.id ? `#${item.id}` : ''), '编号待补充')

const getTicketActionText = (item: any) => {
    const status = Number(item?.status || 0)
    if (status === 2) {
        return '去确认'
    }
    if (status === 0 || status === 1) {
        return '查看进度'
    }
    return '查看详情'
}

const getTicketActionVariant = (item: any): ActionButtonVariant =>
    Number(item?.status || 0) === 2 ? 'dark' : 'light'

const goDetail = (id: number) => {
    uni.navigateTo({ url: `/packages/pages/aftersale/ticket_detail?id=${id}` })
}

onLoad((options: any) => {
    initStatus(options?.status)
})
</script>

<style lang="scss" scoped>
@import '../../../styles/aftersale.scss';

.aftersale-list-page {
    @include aftersale-page-base;
    min-height: 100vh;
}

.aftersale-list-page__wrapper {
    @include aftersale-page-wrapper;
    gap: 18rpx;
    padding-top: 16rpx;
    padding-bottom: var(--wm-space-section-gap-lg, 30rpx);
}

.aftersale-list-page__filters {
    width: 100%;
}

.aftersale-record-list {
    display: flex;
    flex-direction: column;
    gap: 18rpx;
}

.aftersale-record-card {
    display: flex;
    flex-direction: column;
    gap: 20rpx;
}

.aftersale-record-card__top,
.aftersale-record-card__footer,
.aftersale-record-card__eyebrow,
.aftersale-record-card__meta-item {
    position: relative;
    z-index: 1;
    display: flex;
    align-items: center;
}

.aftersale-record-card__top {
    align-items: flex-start;
    justify-content: space-between;
    gap: 18rpx;
}

.aftersale-record-card__title-group {
    min-width: 0;
    flex: 1;
    display: flex;
    flex-direction: column;
    gap: 8rpx;
}

.aftersale-record-card__eyebrow {
    gap: 8rpx;
    font-size: 22rpx;
    line-height: 1.2;
    font-weight: 800;
    color: var(--wm-color-gold, #b8954a);
}

.aftersale-record-card__title {
    display: block;
    min-width: 0;
    font-size: 30rpx;
    line-height: 1.35;
    font-weight: 900;
    color: var(--wm-text-primary, #191713);
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.aftersale-record-card__meta-grid {
    position: relative;
    z-index: 1;
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 12rpx;
}

.aftersale-record-card__meta-item {
    min-width: 0;
    align-items: flex-start;
    gap: 12rpx;
    padding: 16rpx;
    border-radius: 24rpx;
    background: rgba(248, 242, 228, 0.58);
    border: 1rpx solid rgba(216, 201, 173, 0.62);
    box-sizing: border-box;
}

.aftersale-record-card__meta-icon {
    width: 42rpx;
    height: 42rpx;
    flex-shrink: 0;
    border-radius: 16rpx;
    background: rgba(255, 253, 248, 0.9);
    display: flex;
    align-items: center;
    justify-content: center;
}

.aftersale-record-card__meta-copy {
    min-width: 0;
    flex: 1;
    display: flex;
    flex-direction: column;
    gap: 4rpx;
}

.aftersale-record-card__meta-label {
    display: block;
    font-size: 21rpx;
    line-height: 1.35;
    color: var(--wm-text-tertiary, #9a9388);
}

.aftersale-record-card__meta-value {
    display: block;
    min-width: 0;
    font-size: 24rpx;
    line-height: 1.35;
    font-weight: 800;
    color: var(--wm-text-primary, #191713);
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.aftersale-record-card__footer {
    justify-content: space-between;
    gap: 18rpx;
    padding-top: 18rpx;
    border-top: 1rpx solid rgba(216, 201, 173, 0.62);
}

.aftersale-record-card__sn {
    min-width: 0;
    flex: 1;
    font-size: 22rpx;
    line-height: 1.4;
    color: var(--wm-text-tertiary, #9a9388);
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.aftersale-record-card__button {
    flex-shrink: 0;
    min-width: 168rpx;
}

@media screen and (max-width: 360px) {
    .aftersale-record-card__top,
    .aftersale-record-card__footer {
        align-items: stretch;
        flex-direction: column;
    }

    .aftersale-record-card__meta-grid {
        grid-template-columns: minmax(0, 1fr);
    }

    .aftersale-record-card__button {
        width: 100%;
    }
}
</style>
