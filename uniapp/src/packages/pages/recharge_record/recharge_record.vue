<template>
    <page-meta :page-style="$theme.pageStyle" />
    <PageShell scene="consumer">
        <BaseNavbar title="充值记录" />
        <z-paging
            ref="paging"
            v-model="dataList"
            @query="queryList"
            :show-loading-more-when-reload="true"
            :hide-empty-view="true"
            :paging-style="pagingStyle"
        >
            <view class="recharge-record wm-page-content">
                <view v-if="dataList.length" class="wm-page-stack">
                    <view
                        v-for="item in dataList"
                        :key="item.id"
                        class="wm-page-card recharge-record__item"
                    >
                        <view class="wm-toolbar-row">
                            <text class="recharge-record__title">{{ item.tips }}</text>
                            <text class="recharge-record__amount">+{{ item.order_amount }}</text>
                        </view>
                        <text class="recharge-record__time">{{ item.create_time }}</text>
                    </view>
                </view>

                <view v-else class="wm-empty-shell">
                    <EmptyState title="暂无充值记录" description="完成充值后，记录会显示在这里。" />
                </view>
            </view>
        </z-paging>
    </PageShell>
</template>

<script lang="ts" setup>
import EmptyState from '@/components/base/EmptyState.vue'
import PageShell from '@/components/base/PageShell.vue'
import { ref, shallowRef } from 'vue'
import { rechargeRecord } from '@/packages/common/api/recharge'
import { useFixedNavbarPagingStyle } from '@/packages/common/hooks/useFixedNavbarPagingStyle'

const paging = shallowRef()
const dataList = ref<any[]>([])
const pagingStyle = useFixedNavbarPagingStyle()

const queryList = async (pageNo: number, pageSize: number) => {
    try {
        const data = await rechargeRecord({
            page_no: pageNo,
            page_size: pageSize
        })
        paging.value.complete(data.lists)
    } catch (error) {
        paging.value.complete(false)
    }
}
</script>

<style lang="scss" scoped>
.recharge-record {
    padding-top: 20rpx;
    padding-bottom: 24rpx;
}

.recharge-record__item {
    gap: 10rpx;
}

.recharge-record__title {
    flex: 1;
    min-width: 0;
    font-size: 28rpx;
    color: var(--wm-text-primary, #1e2432);
}

.recharge-record__amount {
    font-size: 34rpx;
    font-weight: 700;
    color: var(--wm-color-primary, #e85a4f);
}

.recharge-record__time {
    font-size: 22rpx;
    color: var(--wm-text-secondary, #7f7b78);
}
</style>
