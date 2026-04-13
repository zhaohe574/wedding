<template>
    <page-meta :page-style="$theme.pageStyle" />
    <PageShell scene="consumer" hasSafeBottom>
        <BaseNavbar title="我的投诉" />

        <view class="aftersale-list-page">
            <view class="aftersale-list-page__wrapper">
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
                            variant="surface"
                            scene="consumer"
                            :interactive="true"
                            class="aftersale-record-card"
                            @click="goDetail(item.id)"
                        >
                            <text class="aftersale-record-card__title">{{
                                item.title || '未命名投诉'
                            }}</text>
                            <text class="aftersale-record-card__meta">
                                关联订单：{{ getOrderText(item) }}
                            </text>
                            <text class="aftersale-record-card__meta">
                                提交时间：{{ formatSubmitTimeLabel(item.create_time) }}
                            </text>

                            <view class="aftersale-record-card__footer">
                                <text
                                    class="aftersale-record-card__status"
                                    :class="
                                        getBadgeToneClass(
                                            getComplaintStatusMeta(Number(item.status || 0)).tone
                                        )
                                    "
                                >
                                    {{ getComplaintStatusMeta(Number(item.status || 0)).label }}
                                </text>
                            </view>
                        </BaseCard>
                    </view>

                    <template #empty>
                        <AfterSaleEmptyState
                            icon="warning-circle"
                            title="还没有投诉"
                            description="遇到异常时可以快速提交。"
                        />
                    </template>
                </z-paging>
            </view>
        </view>
    </PageShell>
</template>

<script setup lang="ts">
import { getComplaintLists } from '@/packages/common/api/aftersale'
import BaseCard from '@/components/base/BaseCard.vue'
import BaseNavbar from '@/components/base/BaseNavbar.vue'
import PageShell from '@/components/base/PageShell.vue'
import { useThemeStore } from '@/stores/theme'
import { onLoad } from '@dcloudio/uni-app'
import AfterSaleEmptyState from './components/AfterSaleEmptyState.vue'
import AfterSaleFilterTabs from './components/AfterSaleFilterTabs.vue'
import { useAftersaleListPage } from './composables/useAftersaleListPage'
import {
    formatSubmitTimeLabel,
    getBadgeToneClass,
    getComplaintStatusMeta,
    getValueText
} from './shared'

const $theme = useThemeStore()
const { paging, dataList, currentStatus, changeStatus, applyQueryStatus, initStatus } =
    useAftersaleListPage()

const statusTabs = [
    { label: '全部', value: '' },
    { label: '待受理', value: 0 },
    { label: '处理中', value: 1 },
    { label: '已处理', value: 2 }
]

const queryList = async (pageNo: number, pageSize: number) => {
    try {
        const res = await getComplaintLists(
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

const getOrderText = (item: any) => {
    return getValueText(item?.order?.order_sn, '待补充')
}

const goDetail = (id: number) => {
    uni.navigateTo({
        url: `/packages/pages/aftersale/complaint_detail?id=${id}`
    })
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
    display: flex;
    flex-direction: column;
    gap: 16rpx;
    padding: 12rpx var(--wm-space-page-x, 37rpx) calc(var(--wm-safe-bottom-action, 160rpx) + 18rpx);
}

.aftersale-list-page__filters {
    width: 100%;
}

.aftersale-record-list {
    display: flex;
    flex-direction: column;
    gap: 16rpx;
}

.aftersale-record-card {
    @include aftersale-list-card;
    border-radius: 44rpx !important;
    background: rgba(255, 255, 255, 0.92) !important;
    border: 1rpx solid rgba(239, 230, 225, 0.96) !important;
    box-shadow: 0 18rpx 38rpx rgba(214, 185, 167, 0.12) !important;
}

.aftersale-record-card__title,
.aftersale-record-card__meta {
    display: block;
    color: var(--wm-text-primary, #1e2432);
}

.aftersale-record-card__title {
    @include aftersale-list-card-title;
}

.aftersale-record-card__meta {
    @include aftersale-list-card-meta;
}

.aftersale-record-card__footer {
    @include aftersale-list-card-footer;
}

.aftersale-record-card__status {
    @include aftersale-badge;
    font-size: 20rpx;
    font-weight: 700;
}
</style>
