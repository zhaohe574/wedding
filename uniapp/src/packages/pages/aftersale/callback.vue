<template>
    <page-meta :page-style="$theme.pageStyle" />
    <PageShell scene="consumer" hasSafeBottom>
        <BaseNavbar title="回访问卷" />

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
                            variant="surface"
                            scene="consumer"
                            :interactive="true"
                            class="aftersale-record-card"
                            @click="goDetail(item.id)"
                        >
                            <text class="aftersale-record-card__title">
                                {{ item.type_desc || '服务回访问卷' }}
                            </text>
                            <text class="aftersale-record-card__meta">
                                关联订单：{{ getOrderText(item) }}
                            </text>
                            <text class="aftersale-record-card__meta">
                                服务人员：{{ getStaffText(item) }}
                            </text>
                            <text class="aftersale-record-card__meta">
                                计划时间：{{ getPlanTimeText(item) }}
                            </text>

                            <view class="aftersale-record-card__footer">
                                <text
                                    class="aftersale-record-card__status"
                                    :class="
                                        getBadgeToneClass(
                                            getCallbackStatusMeta(Number(item.status || 0)).tone
                                        )
                                    "
                                >
                                    {{ getCallbackStatusMeta(Number(item.status || 0)).label }}
                                </text>
                            </view>
                        </BaseCard>
                    </view>

                    <template #empty>
                        <AfterSaleEmptyState
                            icon="edit"
                            title="暂无回访问卷"
                            description="服务后会显示在这里。"
                        />
                    </template>
                </z-paging>
            </view>
        </view>
    </PageShell>
</template>

<script setup lang="ts">
import { getCallbackLists } from '@/packages/common/api/aftersale'
import BaseCard from '@/components/base/BaseCard.vue'
import BaseNavbar from '@/components/base/BaseNavbar.vue'
import PageShell from '@/components/base/PageShell.vue'
import { useThemeStore } from '@/stores/theme'
import { onLoad } from '@dcloudio/uni-app'
import AfterSaleEmptyState from './components/AfterSaleEmptyState.vue'
import AfterSaleFilterTabs from './components/AfterSaleFilterTabs.vue'
import { useAftersaleListPage } from './composables/useAftersaleListPage'
import { getBadgeToneClass, getCallbackStatusMeta, getValueText } from './shared'

const $theme = useThemeStore()
const { paging, dataList, currentStatus, changeStatus, applyQueryStatus, initStatus } =
    useAftersaleListPage()

const statusTabs = [
    { label: '全部', value: '' },
    { label: '待填写', value: 0 },
    { label: '已完成', value: 1 },
    { label: '无法联系', value: 2 },
    { label: '已取消', value: 3 }
]

const queryList = async (pageNo: number, pageSize: number) => {
    try {
        const res = await getCallbackLists(
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

const getOrderText = (item: any) => getValueText(item?.order?.order_sn, '待补充')

const getStaffText = (item: any) => getValueText(item?.staff?.name || item?.staff_name, '待补充')

const getPlanTimeText = (item: any) => getValueText(item?.plan_time || item?.create_time, '待补充')

const goDetail = (id: number) => {
    uni.navigateTo({
        url: `/packages/pages/aftersale/callback_detail?id=${id}`
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
    @include aftersale-page-wrapper;
    gap: 16rpx;
    padding-top: 12rpx;
    padding-bottom: var(--wm-space-section-gap-lg, 30rpx);
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
    display: flex;
    flex-direction: column;
    gap: 8rpx;
}

.aftersale-record-card__title,
.aftersale-record-card__meta {
    display: block;
    color: var(--wm-text-primary, #111111);
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
