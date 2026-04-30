<template>
    <page-meta :page-style="$theme.pageStyle" />
    <PageShell scene="consumer" hasSafeBottom>
        <BaseNavbar title="售后服务" />

        <view class="aftersale-home">
            <view class="aftersale-home__wrapper wm-page-content">
                <view class="aftersale-status-panel">
                    <view class="aftersale-status-panel__head">
                        <view class="aftersale-status-panel__copy">
                            <text class="aftersale-status-panel__eyebrow">售后处理</text>
                            <text class="aftersale-status-panel__title"
                                >选择需要处理的售后事项</text
                            >
                            <text class="aftersale-status-panel__summary">
                                工单、投诉和回访集中处理。
                            </text>
                        </view>

                        <view class="aftersale-status-panel__service" @click="contactService">
                            <tn-icon name="service" :size="28" color="#FFFFFF" />
                            <text class="aftersale-status-panel__service-text">人工</text>
                        </view>
                    </view>

                    <view class="aftersale-status-panel__metrics">
                        <view class="aftersale-status-panel__metric" @click="goTicketList">
                            <text class="aftersale-status-panel__metric-value">
                                {{ stats.ticket.pending }}
                            </text>
                            <text class="aftersale-status-panel__metric-label">处理中工单</text>
                        </view>

                        <view class="aftersale-status-panel__metric" @click="goComplaintList">
                            <text class="aftersale-status-panel__metric-value">
                                {{ stats.complaint.pending }}
                            </text>
                            <text class="aftersale-status-panel__metric-label">待反馈投诉</text>
                        </view>

                        <view class="aftersale-status-panel__metric" @click="goCallback">
                            <text class="aftersale-status-panel__metric-value">
                                {{ stats.callback.pending }}
                            </text>
                            <text class="aftersale-status-panel__metric-label">待填写回访</text>
                        </view>
                    </view>
                </view>

                <view class="aftersale-home__section">
                    <view class="aftersale-home__section-head">
                        <text class="aftersale-home__section-title">主要入口</text>
                        <text class="aftersale-home__section-meta">选择最匹配的问题类型</text>
                    </view>

                    <view class="aftersale-home__primary-grid">
                        <BaseCard
                            variant="surface"
                            scene="consumer"
                            interactive
                            padding="0"
                            class="aftersale-primary-card aftersale-primary-card--ticket"
                            @click="goCreateTicket"
                        >
                            <view class="aftersale-primary-card__icon">
                                <tn-icon
                                    name="file-text"
                                    :size="34"
                                    color="var(--wm-color-primary, #0B0B0B)"
                                />
                            </view>

                            <text class="aftersale-primary-card__title">提交工单</text>
                            <text class="aftersale-primary-card__desc">
                                档期、流程、交付、费用问题
                            </text>

                            <view class="aftersale-primary-card__footer">
                                <text class="aftersale-primary-card__footer-text">平台跟进</text>
                                <tn-icon
                                    name="right"
                                    :size="22"
                                    color="var(--wm-text-tertiary, #9A9388)"
                                />
                            </view>
                        </BaseCard>

                        <BaseCard
                            variant="surface"
                            scene="consumer"
                            interactive
                            padding="0"
                            class="aftersale-primary-card aftersale-primary-card--complaint"
                            @click="goCreateComplaint"
                        >
                            <view class="aftersale-primary-card__icon">
                                <tn-icon
                                    name="warning-circle"
                                    :size="34"
                                    color="var(--wm-color-danger, #5A4433)"
                                />
                            </view>

                            <text class="aftersale-primary-card__title">发起投诉</text>
                            <text class="aftersale-primary-card__desc"> 态度、履约、沟通问题 </text>

                            <view class="aftersale-primary-card__footer">
                                <text class="aftersale-primary-card__footer-text">正式投诉</text>
                                <tn-icon
                                    name="right"
                                    :size="22"
                                    color="var(--wm-text-tertiary, #9A9388)"
                                />
                            </view>
                        </BaseCard>
                    </view>
                </view>

                <view class="aftersale-home__section">
                    <view class="aftersale-home__section-head">
                        <text class="aftersale-home__section-title">服务记录</text>
                        <text class="aftersale-home__section-meta">查看进度与补充反馈</text>
                    </view>

                    <view class="aftersale-home__secondary-list">
                        <view class="aftersale-secondary-row" @click="goCallback">
                            <view class="aftersale-secondary-row__icon">
                                <tn-icon
                                    name="edit"
                                    :size="28"
                                    color="var(--wm-color-success, #4D4A42)"
                                />
                            </view>

                            <view class="aftersale-secondary-row__copy">
                                <view class="aftersale-secondary-row__head">
                                    <text class="aftersale-secondary-row__title">回访问卷</text>
                                    <StatusBadge
                                        v-if="stats.callback.pending > 0"
                                        tone="warning"
                                        size="sm"
                                    >
                                        待填写 {{ stats.callback.pending }}
                                    </StatusBadge>
                                </view>
                                <text class="aftersale-secondary-row__desc">
                                    {{
                                        stats.callback.pending > 0
                                            ? '有待填写问卷'
                                            : '查看体验反馈记录'
                                    }}
                                </text>
                            </view>

                            <tn-icon
                                name="right"
                                :size="24"
                                color="var(--wm-text-tertiary, #9A9388)"
                            />
                        </view>

                        <view class="aftersale-secondary-row" @click="contactService">
                            <view class="aftersale-secondary-row__icon">
                                <tn-icon
                                    name="service"
                                    :size="28"
                                    color="var(--wm-color-info, #6C665C)"
                                />
                            </view>

                            <view class="aftersale-secondary-row__copy">
                                <text class="aftersale-secondary-row__title">联系人工</text>
                                <text class="aftersale-secondary-row__desc">
                                    紧急问题可直接联系人工
                                </text>
                            </view>

                            <tn-icon
                                name="right"
                                :size="24"
                                color="var(--wm-text-tertiary, #9A9388)"
                            />
                        </view>
                    </view>
                </view>

                <view class="aftersale-home__note">
                    <tn-icon
                        name="info-circle"
                        :size="24"
                        color="var(--wm-color-secondary, #C8A45D)"
                    />
                    <text class="aftersale-home__note-text">紧急问题可优先联系人工。</text>
                </view>
            </view>
        </view>
    </PageShell>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue'
import { getMyStatistics } from '@/packages/common/api/aftersale'
import BaseCard from '@/components/base/BaseCard.vue'
import BaseNavbar from '@/components/base/BaseNavbar.vue'
import PageShell from '@/components/base/PageShell.vue'
import StatusBadge from '@/components/base/StatusBadge.vue'
import { useThemeStore } from '@/stores/theme'
import { onLoad, onShow } from '@dcloudio/uni-app'

interface StatisticsItem {
    total: number
    pending: number
}

interface StatisticsState {
    ticket: StatisticsItem
    complaint: StatisticsItem
    callback: StatisticsItem
}

const $theme = useThemeStore()
const statistics = ref<StatisticsState>({
    ticket: { total: 0, pending: 0 },
    complaint: { total: 0, pending: 0 },
    callback: { total: 0, pending: 0 }
})

const stats = computed(() => statistics.value)

const applyRouteAction = (action?: string) => {
    if (action === 'create_ticket') {
        uni.redirectTo({ url: '/packages/pages/aftersale/create_ticket' })
        return
    }

    if (action === 'submit_complaint') {
        uni.redirectTo({ url: '/packages/pages/aftersale/create_complaint' })
    }
}

const loadStatistics = async () => {
    const response = await getMyStatistics()
    const data = response?.data || response || {}
    statistics.value = {
        ticket: {
            total: Number(data?.ticket?.total || 0),
            pending: Number(data?.ticket?.pending || 0)
        },
        complaint: {
            total: Number(data?.complaint?.total || 0),
            pending: Number(data?.complaint?.pending || 0)
        },
        callback: {
            total: Number(data?.callback?.total || 0),
            pending: Number(data?.callback?.pending || 0)
        }
    }
}

const goCreateTicket = () => {
    uni.navigateTo({ url: '/packages/pages/aftersale/create_ticket' })
}

const goCreateComplaint = () => {
    uni.navigateTo({ url: '/packages/pages/aftersale/create_complaint' })
}

const goTicketList = () => {
    uni.navigateTo({ url: '/packages/pages/aftersale/ticket?status=1' })
}

const goComplaintList = () => {
    uni.navigateTo({ url: '/packages/pages/aftersale/complaint?status=0' })
}

const goCallback = () => {
    const query = stats.value.callback.pending > 0 ? '?status=0' : ''
    uni.navigateTo({ url: `/packages/pages/aftersale/callback${query}` })
}

const contactService = () => {
    uni.navigateTo({
        url: '/packages/pages/customer_service/customer_service?scene=aftersale'
    })
}

const loadPageData = async () => {
    try {
        await loadStatistics()
    } catch (error) {
        console.error('加载售后数据失败', error)
    }
}

onLoad((options: any) => {
    applyRouteAction(options?.action)
})

onShow(() => {
    void loadPageData()
})
</script>

<style lang="scss" scoped>
@import '../../../styles/aftersale.scss';

.aftersale-home {
    @include aftersale-page-base;
    min-height: 100vh;
}

.aftersale-home__wrapper {
    @include aftersale-page-wrapper;
    gap: 22rpx;
    padding: 20rpx var(--wm-space-page-x, 37rpx) calc(var(--wm-safe-bottom-action, 160rpx) + 18rpx);
}

.aftersale-status-panel {
    overflow: hidden;
    padding: 32rpx 30rpx 28rpx;
    border-radius: var(--wm-radius-card-lg, 20rpx);
    background: linear-gradient(135deg, #0b0b0b 0%, #2f2a25 58%, #5a4433 100%);
    box-shadow: 0 16rpx 34rpx rgba(11, 11, 11, 0.14);
    animation: aftersale-home-enter 240ms ease both;
}

.aftersale-status-panel__head {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 24rpx;
}

.aftersale-status-panel__copy {
    min-width: 0;
    flex: 1;
    display: flex;
    flex-direction: column;
    gap: 10rpx;
}

.aftersale-status-panel__eyebrow {
    font-size: 22rpx;
    line-height: 1.2;
    font-weight: 700;
    letter-spacing: 0;
    color: var(--wm-color-secondary, #c8a45d);
}

.aftersale-status-panel__title {
    font-size: 38rpx;
    line-height: 1.25;
    font-weight: 800;
    color: #ffffff;
}

.aftersale-status-panel__summary {
    font-size: 24rpx;
    line-height: 1.55;
    color: rgba(255, 255, 255, 0.72);
}

.aftersale-status-panel__service {
    flex-shrink: 0;
    min-height: 60rpx;
    padding: 0 18rpx;
    border-radius: var(--wm-radius-pill, 999rpx);
    background: rgba(255, 255, 255, 0.14);
    border: 1rpx solid rgba(255, 255, 255, 0.22);
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8rpx;

    &:active {
        transform: scale(0.98);
    }
}

.aftersale-status-panel__service-text {
    font-size: 23rpx;
    line-height: 1;
    font-weight: 700;
    color: #ffffff;
}

.aftersale-status-panel__metrics {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 18rpx;
    margin-top: 30rpx;
    padding-top: 24rpx;
    border-top: 1rpx solid rgba(255, 255, 255, 0.16);
}

.aftersale-status-panel__metric {
    min-width: 0;
    display: flex;
    flex-direction: column;
    gap: 8rpx;

    &:active {
        transform: translateY(1rpx);
    }
}

.aftersale-status-panel__metric-value {
    display: block;
    font-size: 42rpx;
    line-height: 1;
    font-weight: 800;
    color: #ffffff;
}

.aftersale-status-panel__metric-label {
    display: block;
    min-width: 0;
    font-size: 21rpx;
    line-height: 1.35;
    color: rgba(255, 255, 255, 0.66);
}

.aftersale-home__section {
    display: flex;
    flex-direction: column;
    gap: 16rpx;
    animation: aftersale-home-enter 260ms ease 40ms both;
}

.aftersale-home__section-head {
    display: flex;
    align-items: flex-end;
    justify-content: space-between;
    gap: 20rpx;
    padding: 0 2rpx;
}

.aftersale-home__section-title {
    font-size: 31rpx;
    line-height: 1.25;
    font-weight: 800;
    color: var(--wm-text-primary, #111111);
}

.aftersale-home__section-meta {
    flex: 1;
    min-width: 0;
    text-align: right;
    font-size: 22rpx;
    line-height: 1.4;
    color: var(--wm-text-tertiary, #8a8a8a);
}

.aftersale-home__primary-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 16rpx;
}

.aftersale-primary-card {
    min-width: 0;
    min-height: 264rpx;
    padding: 24rpx;
    display: flex;
    flex-direction: column;
    gap: 12rpx;
    border-radius: var(--wm-radius-card-lg, 20rpx) !important;

    &--ticket {
        background: linear-gradient(
            180deg,
            #ffffff 0%,
            var(--wm-color-secondary-soft, #f8f3e7) 100%
        ) !important;
        border-color: rgba(200, 164, 93, 0.42) !important;
    }
}

.aftersale-primary-card__icon {
    width: 64rpx;
    height: 64rpx;
    border-radius: var(--wm-radius-card-soft, 14rpx);
    background: var(--wm-color-bg-soft, #f7f7f7);
    display: flex;
    align-items: center;
    justify-content: center;
}

.aftersale-primary-card__title {
    display: block;
    font-size: 31rpx;
    line-height: 1.25;
    font-weight: 800;
    color: var(--wm-text-primary, #111111);
}

.aftersale-primary-card__desc {
    display: block;
    flex: 1;
    min-height: 64rpx;
    font-size: 23rpx;
    line-height: 1.48;
    color: var(--wm-text-secondary, #4a4a4a);
}

.aftersale-primary-card__footer {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12rpx;
    padding-top: 4rpx;
}

.aftersale-primary-card__footer-text {
    min-width: 0;
    font-size: 22rpx;
    line-height: 1.2;
    font-weight: 700;
    color: var(--wm-color-primary, #0b0b0b);
}

.aftersale-home__secondary-list {
    overflow: hidden;
    border-radius: var(--wm-radius-card-lg, 20rpx);
    border: 1rpx solid var(--wm-color-border, #e5e5e5);
    background: #ffffff;
}

.aftersale-secondary-row {
    min-height: 112rpx;
    padding: 22rpx 24rpx;
    display: flex;
    align-items: center;
    gap: 18rpx;

    &:active {
        background: var(--wm-color-bg-soft, #f7f7f7);
    }
}

.aftersale-secondary-row + .aftersale-secondary-row {
    border-top: 1rpx solid var(--wm-color-border, #e5e5e5);
}

.aftersale-secondary-row__icon {
    width: 60rpx;
    height: 60rpx;
    flex-shrink: 0;
    border-radius: var(--wm-radius-card-soft, 14rpx);
    background: var(--wm-color-bg-soft, #f7f7f7);
    display: flex;
    align-items: center;
    justify-content: center;
}

.aftersale-secondary-row__copy {
    flex: 1;
    min-width: 0;
}

.aftersale-secondary-row__head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12rpx;
}

.aftersale-secondary-row__title {
    display: block;
    min-width: 0;
    font-size: 28rpx;
    line-height: 1.3;
    font-weight: 800;
    color: var(--wm-text-primary, #111111);
}

.aftersale-secondary-row__desc {
    display: block;
    margin-top: 8rpx;
    min-width: 0;
    font-size: 23rpx;
    line-height: 1.42;
    color: var(--wm-text-secondary, #4a4a4a);
}

.aftersale-home__note {
    display: flex;
    align-items: center;
    gap: 10rpx;
    padding: 4rpx 2rpx 0;
    animation: aftersale-home-enter 260ms ease 80ms both;
}

.aftersale-home__note-text {
    font-size: 23rpx;
    line-height: 1.45;
    color: var(--wm-text-secondary, #4a4a4a);
}

@keyframes aftersale-home-enter {
    from {
        opacity: 0;
        transform: translateY(14rpx);
    }

    to {
        opacity: 1;
        transform: translateY(0);
    }
}
</style>
