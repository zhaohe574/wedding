<template>
    <page-meta :page-style="$theme.pageStyle" />
    <PageShell scene="consumer">
        <BaseNavbar
            title="我的钱包"
            variant="solid"
            bg-color="#191713"
            text-color="#FFFDF8"
        />
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
                    <BaseCard
                        variant="hero"
                        scene="consumer"
                        padding="34rpx"
                        class="user-wallet-top__hero"
                    >
                        <view class="user-wallet-top__main">
                            <view class="user-wallet-top__heading">
                                <text class="user-wallet-top__label">钱包余额</text>
                                <text
                                    class="user-wallet-top__status"
                                    :class="{ 'is-disabled': !wallet.status }"
                                >
                                    {{ wallet.status ? '可充值' : '仅查看' }}
                                </text>
                            </view>
                            <text class="user-wallet-top__amount">
                                ¥ {{ wallet.user_money || '0.00' }}
                            </text>
                        </view>
                        <navigator
                            v-if="wallet.status"
                            class="user-wallet-top__cta-wrap"
                            url="/packages/pages/recharge/recharge"
                            hover-class="none"
                        >
                            <BaseButton
                                label="去充值"
                                variant="light"
                                size="sm"
                                icon="wallet"
                                height="68rpx"
                                font-size="24rpx"
                                text-color="#191713"
                            />
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
                        class="user-wallet-list__item"
                        :class="{ 'user-wallet-list__item--link': item.target_url }"
                        @click="goTransactionTarget(item)"
                    >
                        <view class="user-wallet-list__mark" :class="{ 'is-expense': item.direction != 1 }">
                            <text>{{ item.direction == 1 ? '+' : '-' }}</text>
                        </view>
                        <view class="user-wallet-list__body">
                            <view class="user-wallet-list__row">
                                <text class="user-wallet-list__title">{{ item.title || '交易记录' }}</text>
                                <text
                                    class="user-wallet-list__amount"
                                    :class="{ 'is-expense': item.direction != 1 }"
                                >
                                    {{ item.amount_desc || '--' }}
                                </text>
                            </view>
                            <view class="user-wallet-list__meta-row">
                                <text class="user-wallet-list__meta">{{ getTransactionMeta(item) }}</text>
                                <text class="user-wallet-list__time">{{ item.create_time || '--' }}</text>
                            </view>
                        </view>
                        <view v-if="item.target_url" class="user-wallet-list__arrow">
                            <BaseIcon name="right" size="26" color="rgba(25, 23, 19, 0.42)" />
                        </view>
                    </view>
                </view>

                <view v-else class="wm-empty-shell">
                    <EmptyState title="暂无交易记录" icon="wallet" compact />
                </view>
            </view>
        </z-paging>
    </PageShell>
</template>

<script lang="ts" setup>
import BaseCard from '@/components/base/BaseCard.vue'
import BaseButton from '@/components/base/BaseButton.vue'
import BaseIcon from '@/components/base/BaseIcon.vue'
import EmptyState from '@/components/base/EmptyState.vue'
import PageShell from '@/components/base/PageShell.vue'
import { ref, shallowRef } from 'vue'
import { getWalletTransactions } from '@/api/user'
import { rechargeConfig } from '@/packages/common/api/recharge'
import { onShow } from '@dcloudio/uni-app'
import { useFixedNavbarPagingStyle } from '@/packages/common/hooks/useFixedNavbarPagingStyle'
import type { RechargeConfigResponse } from '@/packages/common/api/recharge'

interface WalletLogItem {
    id: string
    title: string
    amount: string
    amount_desc: string
    direction: number
    pay_way_desc: string
    biz_sn: string
    create_time: string
    target_type: 'order' | 'activity_registration' | 'none' | string
    target_id: number
    target_url: string
}

interface PagingRef {
    reload: () => void
    complete: (data: WalletLogItem[] | false) => void
}

interface WalletLogResponse {
    lists: WalletLogItem[]
}

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
const paging = shallowRef<PagingRef>()
const dataList = ref<WalletLogItem[]>([])
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
        const data = await getWalletTransactions({
            action,
            page_no: pageNo,
            page_size: pageSize
        }) as WalletLogResponse
        paging.value?.complete(data.lists)
    } catch (error) {
        paging.value?.complete(false)
    }
}

const wallet = ref<Partial<RechargeConfigResponse>>({})
const getWallet = async () => {
    wallet.value = await rechargeConfig()
}

const getTransactionMeta = (item: WalletLogItem) => {
    const parts = [item.pay_way_desc, item.biz_sn ? `单号 ${item.biz_sn}` : '']
    return parts.filter(Boolean).join(' · ') || '交易记录'
}

const goTransactionTarget = (item: WalletLogItem) => {
    const targetUrl = String(item.target_url || '')
    if (!targetUrl) return
    uni.navigateTo({ url: targetUrl })
}

onShow(() => {
    getWallet()
})
</script>

<style lang="scss" scoped>
.user-wallet-top {
    padding-top: 24rpx;
    padding-bottom: 18rpx;
}

.user-wallet-top__hero {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 22rpx;
    min-height: 258rpx;
    background: radial-gradient(circle at 12% 0%, rgba(217, 190, 130, 0.26) 0, transparent 42%),
        linear-gradient(145deg, #2b261d 0%, #191713 58%, #0f0e0c 100%) !important;
    border: 1rpx solid rgba(217, 190, 130, 0.72) !important;
    box-shadow: 0 28rpx 68rpx rgba(74, 43, 24, 0.2) !important;
}

.user-wallet-top__main {
    position: relative;
    z-index: 1;
    flex: 1;
    min-width: 0;
}

.user-wallet-top__heading {
    display: flex;
    align-items: center;
    gap: 14rpx;
    min-width: 0;
}

.user-wallet-top__label {
    flex-shrink: 0;
    font-size: 24rpx;
    line-height: 1.2;
    color: rgba(255, 255, 255, 0.72);
}

.user-wallet-top__status {
    min-height: 42rpx;
    padding: 0 16rpx;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: 999rpx;
    border: 1rpx solid rgba(217, 190, 130, 0.45);
    background: rgba(217, 190, 130, 0.14);
    color: #f4deb2;
    font-size: 20rpx;
    font-weight: 800;
    line-height: 1;

    &.is-disabled {
        border-color: rgba(255, 255, 255, 0.18);
        background: rgba(255, 255, 255, 0.08);
        color: rgba(255, 253, 248, 0.64);
    }
}

.user-wallet-top__amount {
    display: block;
    max-width: 100%;
    margin-top: 20rpx;
    font-size: 58rpx;
    line-height: 1.08;
    font-weight: 900;
    color: var(--wm-text-inverse, #fffdf8);
    word-break: break-all;
}

.user-wallet-top__cta-wrap {
    position: relative;
    z-index: 1;
    flex-shrink: 0;
}

.user-wallet-top__tabs {
    margin-top: 22rpx;
    padding: 10rpx;
    flex-wrap: nowrap;
    border-radius: 999rpx;
    background: rgba(255, 253, 248, 0.88);
    border: 1rpx solid rgba(216, 201, 173, 0.82);
    box-shadow: 0 14rpx 34rpx rgba(74, 43, 24, 0.08);
}

.user-wallet-top__tabs .wm-pill-tab {
    flex: 1;
    min-width: 0;
    min-height: 64rpx;
    padding: 0 12rpx;
    border-color: transparent;
    background: transparent;
    white-space: nowrap;
}

.user-wallet-top__tabs .wm-pill-tab--active {
    background: var(--wm-color-primary, #191713);
    border-color: var(--wm-color-primary, #191713);
    color: var(--wm-text-inverse, #fffdf8);
    box-shadow: 0 10rpx 24rpx rgba(25, 23, 19, 0.18);
}

.user-wallet-list {
    padding-top: 0;
    padding-bottom: 34rpx;
}

.user-wallet-list__item {
    display: flex;
    align-items: center;
    gap: 20rpx;
    min-height: 128rpx;
    padding: 26rpx 28rpx;
    border-radius: var(--wm-radius-card-soft, 32rpx);
    background: linear-gradient(180deg, rgba(255, 253, 248, 0.98) 0%, var(--wm-color-bg-card, #fffdf8) 100%);
    border: 1rpx solid rgba(216, 201, 173, 0.86);
    box-shadow: 0 14rpx 32rpx rgba(74, 43, 24, 0.07);
}

.user-wallet-list__item--link:active {
    transform: translateY(2rpx);
    box-shadow: 0 10rpx 24rpx rgba(74, 43, 24, 0.06);
}

.user-wallet-list__mark {
    width: 56rpx;
    height: 56rpx;
    flex-shrink: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 999rpx;
    background: var(--wm-color-success-soft, #e8efe6);
    color: #4d6049;
    font-size: 30rpx;
    font-weight: 900;

    &.is-expense {
        background: var(--wm-color-gold-soft, #f1e5c8);
        color: var(--wm-color-secondary-strong, #7d4c35);
    }
}

.user-wallet-list__body {
    flex: 1;
    min-width: 0;
}

.user-wallet-list__row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 18rpx;
    min-width: 0;
}

.user-wallet-list__title {
    flex: 1;
    min-width: 0;
    font-size: 28rpx;
    font-weight: 800;
    line-height: 1.35;
    color: var(--wm-text-primary, #111111);
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.user-wallet-list__amount {
    flex-shrink: 0;
    max-width: 260rpx;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    text-align: right;
    font-size: 32rpx;
    line-height: 1.2;
    font-weight: 900;
    color: #4d6049;

    &.is-expense {
        color: var(--wm-text-primary, #191713);
    }
}

.user-wallet-list__time {
    flex-shrink: 0;
    max-width: 220rpx;
    text-align: right;
    font-size: 22rpx;
    line-height: 1.3;
    color: var(--wm-text-secondary, #5f5a50);
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.user-wallet-list__meta-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 18rpx;
    min-width: 0;
    margin-top: 12rpx;
}

.user-wallet-list__meta {
    flex: 1;
    min-width: 0;
    font-size: 22rpx;
    line-height: 1.3;
    color: var(--wm-text-secondary, #5f5a50);
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.user-wallet-list__arrow {
    width: 32rpx;
    height: 32rpx;
    flex-shrink: 0;
    display: flex;
    align-items: center;
    justify-content: center;
}

@media screen and (max-width: 360px) {
    .user-wallet-top__hero {
        align-items: flex-start;
        flex-direction: column;
    }

    .user-wallet-top__cta-wrap {
        align-self: flex-start;
    }

    .user-wallet-list__amount {
        max-width: 220rpx;
        font-size: 30rpx;
    }

    .user-wallet-list__meta-row {
        align-items: flex-start;
        flex-direction: column;
        gap: 8rpx;
    }

    .user-wallet-list__time {
        max-width: 100%;
        text-align: left;
    }
}
</style>
