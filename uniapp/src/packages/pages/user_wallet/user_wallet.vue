<template>
    <page-meta :page-style="$theme.pageStyle" />
    <PageShell scene="consumer">
        <BaseNavbar title="我的钱包" />
        <z-paging
            ref="paging"
            v-model="dataList"
            @query="queryList"
            :show-loading-more-when-reload="true"
            :hide-empty-view="true"
            :paging-style="pagingStyle"
        >
            <template #top>
                <view class="user-wallet-top wm-page-content">
                    <BaseCard variant="hero" scene="consumer" class="user-wallet-top__hero">
                        <view>
                            <text class="user-wallet-top__label">钱包余额</text>
                            <text class="user-wallet-top__amount"
                                >¥ {{ wallet.user_money || '0.00' }}</text
                            >
                        </view>
                        <navigator
                            v-if="wallet.status"
                            url="/packages/pages/recharge/recharge"
                            hover-class="none"
                        >
                            <view class="user-wallet-top__cta">去充值</view>
                        </navigator>
                    </BaseCard>

                    <view class="wm-pill-tabs user-wallet-top__tabs">
                        <view
                            v-for="(tab, index) in tabList"
                            :key="tab.type"
                            class="wm-pill-tab"
                            :class="{ 'wm-pill-tab--active': current === index }"
                            @click="changeType(index)"
                        >
                            {{ tab.name }}
                        </view>
                    </view>
                </view>
            </template>

            <view class="user-wallet-list wm-page-content">
                <view v-if="dataList.length" class="wm-page-stack">
                    <view
                        v-for="item in dataList"
                        :key="item.id"
                        class="wm-page-card user-wallet-list__item"
                    >
                        <view class="wm-toolbar-row">
                            <text class="user-wallet-list__title">{{ item.type_desc }}</text>
                            <text
                                class="user-wallet-list__amount"
                                :class="{ 'is-expense': item.action != 1 }"
                            >
                                {{ item.change_amount_desc }}
                            </text>
                        </view>
                        <text class="user-wallet-list__time">{{ item.create_time }}</text>
                    </view>
                </view>

                <view v-else class="wm-empty-shell">
                    <EmptyState title="暂无钱包流水" description="资金记录会显示在这里。" />
                </view>
            </view>
        </z-paging>
    </PageShell>
</template>

<script lang="ts" setup>
import BaseCard from '@/components/base/BaseCard.vue'
import EmptyState from '@/components/base/EmptyState.vue'
import PageShell from '@/components/base/PageShell.vue'
import { ref, shallowRef } from 'vue'
import { accountLog } from '@/api/user'
import { rechargeConfig } from '@/packages/common/api/recharge'
import { onShow } from '@dcloudio/uni-app'
import { useFixedNavbarPagingStyle } from '@/packages/common/hooks/useFixedNavbarPagingStyle'

const tabList = ref([
    {
        name: '全部',
        type: ''
    },
    {
        name: '收入',
        type: 1
    },
    {
        name: '支出',
        type: 2
    }
])
const paging = shallowRef()
const dataList = ref<any[]>([])
const current = ref(0)
const pagingStyle = useFixedNavbarPagingStyle()

const changeType = (index: number | string) => {
    const nextIndex = Number(index)
    current.value = Number.isNaN(nextIndex) ? 0 : nextIndex
    paging.value?.reload()
}

const queryList = async (pageNo: number, pageSize: number) => {
    try {
        const action = tabList.value[current.value]?.type ?? ''
        const data = await accountLog({
            action,
            type: 'um',
            page_no: pageNo,
            page_size: pageSize
        })
        paging.value.complete(data.lists)
    } catch (error) {
        paging.value.complete(false)
    }
}

const wallet = ref<any>({})
const getWallet = async () => {
    wallet.value = await rechargeConfig()
}
onShow(() => {
    getWallet()
})
</script>

<style lang="scss" scoped>
.user-wallet-top {
    padding-top: 20rpx;
}

.user-wallet-top__hero {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 24rpx;
    background: linear-gradient(145deg, #111111 0%, #000000 58%, #2f2924 100%) !important;
    border-color: rgba(255, 255, 255, 0.08) !important;
}

.user-wallet-top__label {
    display: block;
    font-size: 24rpx;
    color: rgba(255, 255, 255, 0.72);
}

.user-wallet-top__amount {
    display: block;
    margin-top: 12rpx;
    font-size: 54rpx;
    font-weight: 700;
    color: #ffffff;
}

.user-wallet-top__cta {
    min-height: 68rpx;
    padding: 0 26rpx;
    border-radius: 999rpx;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    background: #ffffff;
    color: #111111;
    font-size: 24rpx;
    font-weight: 700;
}

.user-wallet-top__tabs {
    margin-top: 20rpx;
}

.user-wallet-list {
    padding-bottom: 24rpx;
}

.user-wallet-list__item {
    gap: 10rpx;
}

.user-wallet-list__title {
    flex: 1;
    min-width: 0;
    font-size: 28rpx;
    color: var(--wm-text-primary, #111111);
}

.user-wallet-list__amount {
    font-size: 32rpx;
    font-weight: 700;
    color: var(--color-cta, #d0021b);

    &.is-expense {
        color: var(--wm-text-primary, #111111);
    }
}

.user-wallet-list__time {
    font-size: 22rpx;
    color: var(--wm-text-secondary, #5f5a50);
}
</style>
