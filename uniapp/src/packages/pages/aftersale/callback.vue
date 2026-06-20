<template>
    <page-meta :page-style="$theme.pageStyle" />
    <PageShell scene="consumer" tone="workspace" hasSafeBottom>
        <BaseNavbar
            title="回访问卷"
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

                <z-paging
                    ref="paging"
                    v-model="dataList"
                    class="aftersale-list-page__paging"
                    use-page-scroll
                    @query="queryList"
                >
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
                                        <BaseIcon name="edit" size="22" color="#B8954A" />
                                        <text>{{ getCallbackTitle(item) }}</text>
                                    </view>
                                    <text class="aftersale-record-card__title">
                                        {{ getOrderText(item) }}
                                    </text>
                                </view>

                                <StatusBadge :tone="getCallbackMeta(item).tone" size="sm" dot>
                                    {{ getCallbackMeta(item).label }}
                                </StatusBadge>
                            </view>

                            <view class="aftersale-record-card__meta-grid">
                                <view class="aftersale-record-card__meta-item">
                                    <view class="aftersale-record-card__meta-icon">
                                        <BaseIcon name="calendar" size="24" color="#9A9388" />
                                    </view>
                                    <view class="aftersale-record-card__meta-copy">
                                        <text class="aftersale-record-card__meta-label">
                                            计划时间
                                        </text>
                                        <text class="aftersale-record-card__meta-value">
                                            {{ getPlanTimeText(item) }}
                                        </text>
                                    </view>
                                </view>

                                <view class="aftersale-record-card__meta-item">
                                    <view class="aftersale-record-card__meta-icon">
                                        <BaseIcon name="user" size="24" color="#9A9388" />
                                    </view>
                                    <view class="aftersale-record-card__meta-copy">
                                        <text class="aftersale-record-card__meta-label">
                                            服务人员
                                        </text>
                                        <text class="aftersale-record-card__meta-value">
                                            {{ getStaffText(item) }}
                                        </text>
                                    </view>
                                </view>
                            </view>

                            <view class="aftersale-record-card__footer">
                                <text class="aftersale-record-card__sn">
                                    {{ getCallbackSnText(item) }}
                                </text>
                                <BaseButton
                                    class="aftersale-record-card__button"
                                    :label="getActionText(item)"
                                    :variant="Number(item.status || 0) === 0 ? 'dark' : 'light'"
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
                        <AfterSaleEmptyState icon="edit" title="暂无回访问卷" />
                    </template>
                </z-paging>
            </view>
        </view>
    </PageShell>
</template>

<script setup lang="ts">
import { getCallbackLists } from '@/packages/common/api/aftersale'
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
import { getCallbackStatusMeta, getValueText } from './shared'

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

const getCallbackMeta = (item: any) => getCallbackStatusMeta(Number(item?.status || 0))

const getCallbackTitle = (item: any) => {
    const typeText = getValueText(item?.type_desc, '')
    return typeText ? `${typeText}回访` : '服务回访'
}

const getOrderText = (item: any) => getValueText(item?.order?.order_sn, '未关联订单')

const getStaffText = (item: any) => getValueText(item?.staff?.name || item?.staff_name, '待补充')

const getPlanTimeText = (item: any) => getValueText(item?.plan_time || item?.create_time, '待补充')

const getCallbackSnText = (item: any) =>
    getValueText(item?.callback_sn || (item?.id ? `#${item.id}` : ''), '编号待补充')

const getActionText = (item: any) => {
    const status = Number(item?.status || 0)
    if (status === 0) {
        return '填写问卷'
    }
    if (status === 1) {
        return '查看结果'
    }
    return '查看详情'
}

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
    gap: 18rpx;
    padding-top: 16rpx;
    padding-bottom: var(--wm-space-section-gap-lg, 30rpx);
}

.aftersale-list-page__filters {
    width: 100%;
}

.aftersale-list-page__paging {
    display: block;
    width: 100%;
    box-sizing: border-box;
}

.aftersale-record-list {
    display: flex;
    flex-direction: column;
    align-items: stretch;
    width: 100%;
    gap: 18rpx;
    box-sizing: border-box;
}

.aftersale-record-card {
    display: flex;
    flex-direction: column;
    align-self: stretch;
    width: 100%;
    gap: 20rpx;
    box-sizing: border-box;
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
