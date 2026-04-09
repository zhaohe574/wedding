<template>
    <page-meta :page-style="$theme.pageStyle" />
    <PageShell scene="consumer" hasSafeBottom>
        <BaseNavbar title="售后服务" />

        <view class="aftersale-home">
            <view class="aftersale-home__wrapper">
                <BaseCard variant="surface" scene="consumer" class="aftersale-home__intro">
                    <text class="aftersale-home__intro-title">售后问题统一处理</text>
                    <text class="aftersale-home__intro-summary">
                        工单、投诉、人工协助与回访问卷统一在这里处理，只保留正式入口与标准服务说明。
                    </text>

                    <view class="aftersale-home__intro-metrics">
                        <view class="aftersale-home__intro-metric" @click="goTicketList">
                            <text class="aftersale-home__intro-metric-value">
                                {{ stats.ticket.pending }}
                            </text>
                            <text class="aftersale-home__intro-metric-label">处理中工单</text>
                        </view>
                        <view class="aftersale-home__intro-metric" @click="goComplaintList">
                            <text class="aftersale-home__intro-metric-value">
                                {{ stats.complaint.pending }}
                            </text>
                            <text class="aftersale-home__intro-metric-label">待反馈投诉</text>
                        </view>
                    </view>
                </BaseCard>

                <view class="aftersale-home__section">
                    <text class="aftersale-home__section-title">售后入口</text>

                    <BaseCard
                        variant="surface"
                        scene="consumer"
                        :interactive="true"
                        class="aftersale-home__entry-card aftersale-home__entry-card--ticket"
                        @click="goCreateTicket"
                    >
                        <view class="aftersale-home__entry-icon">
                            <tn-icon name="file-text" :size="28" color="#E85A4F" />
                        </view>
                        <view class="aftersale-home__entry-copy">
                            <text class="aftersale-home__entry-title">提交工单</text>
                            <text class="aftersale-home__entry-desc">
                                档期协调、流程确认、素材交付、费用问题
                            </text>
                            <text class="aftersale-home__entry-tip">
                                适合需要平台跟进处理的服务问题
                            </text>
                        </view>
                        <tn-icon name="right" size="24" color="#B4ACA8" />
                    </BaseCard>

                    <BaseCard
                        variant="surface"
                        scene="consumer"
                        :interactive="true"
                        class="aftersale-home__entry-card"
                        @click="goCreateComplaint"
                    >
                        <view class="aftersale-home__entry-icon">
                            <tn-icon name="warning-circle" :size="28" color="#B44A3A" />
                        </view>
                        <view class="aftersale-home__entry-copy">
                            <text class="aftersale-home__entry-title">发起投诉</text>
                            <text class="aftersale-home__entry-desc">
                                服务态度、履约偏差、沟通问题、执行落差
                            </text>
                            <text class="aftersale-home__entry-tip">
                                适合提交正式反馈与处理诉求
                            </text>
                        </view>
                        <tn-icon name="right" size="24" color="#B4ACA8" />
                    </BaseCard>

                    <BaseCard
                        variant="surface"
                        scene="consumer"
                        :interactive="true"
                        class="aftersale-home__entry-card"
                        @click="contactService"
                    >
                        <view class="aftersale-home__entry-icon">
                            <tn-icon name="service" :size="28" color="#607086" />
                        </view>
                        <view class="aftersale-home__entry-copy">
                            <text class="aftersale-home__entry-title">联系人工</text>
                            <text class="aftersale-home__entry-desc">紧急问题优先人工协助</text>
                            <text class="aftersale-home__entry-tip">
                                工作时段优先响应，建议先准备订单与问题描述
                            </text>
                        </view>
                        <tn-icon name="right" size="24" color="#B4ACA8" />
                    </BaseCard>

                    <BaseCard
                        variant="surface"
                        scene="consumer"
                        :interactive="true"
                        class="aftersale-home__entry-card"
                        @click="goCallback"
                    >
                        <view class="aftersale-home__entry-icon">
                            <tn-icon name="edit" :size="28" color="#2F7D58" />
                        </view>
                        <view class="aftersale-home__entry-copy">
                            <view class="aftersale-home__entry-head">
                                <text class="aftersale-home__entry-title">回访问卷</text>
                                <StatusBadge
                                    v-if="stats.callback.pending > 0"
                                    tone="warning"
                                    size="sm"
                                >
                                    待填写 {{ stats.callback.pending }}
                                </StatusBadge>
                            </view>
                            <text class="aftersale-home__entry-desc">服务结束后回收体验反馈</text>
                            <text class="aftersale-home__entry-tip">
                                {{
                                    stats.callback.pending > 0
                                        ? '有待填写问卷，请及时完成反馈'
                                        : '服务反馈、体验评分与问题追踪统一回收'
                                }}
                            </text>
                        </view>
                        <tn-icon name="right" size="24" color="#B4ACA8" />
                    </BaseCard>
                </view>

                <BaseCard variant="surface" scene="consumer" class="aftersale-home__note">
                    <text class="aftersale-home__note-title">服务说明</text>
                    <text class="aftersale-home__note-desc">
                        工作时段优先响应，紧急问题建议直接联系人工。回访中发现的新问题会继续升级为工单跟进。
                    </text>
                </BaseCard>
            </view>
        </view>
    </PageShell>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue'
import { getMyStatistics } from '@/api/aftersale'
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
}

.aftersale-home__intro {
    display: flex;
    flex-direction: column;
    gap: 16rpx;
}

.aftersale-home__intro-title,
.aftersale-home__note-title,
.aftersale-home__section-title {
    display: block;
    font-size: 30rpx;
    line-height: 1.3;
    font-weight: 700;
    color: var(--wm-text-primary, #1e2432);
}

.aftersale-home__intro-summary,
.aftersale-home__note-desc {
    display: block;
    font-size: 24rpx;
    line-height: 1.6;
    color: var(--wm-text-secondary, #7f7b78);
}

.aftersale-home__intro-metrics {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 16rpx;
}

.aftersale-home__intro-metric {
    padding: 20rpx 22rpx;
    border-radius: 30rpx;
    background: rgba(255, 247, 244, 0.82);
    border: 1rpx solid rgba(239, 230, 225, 0.92);

    &:active {
        transform: scale(0.98);
    }
}

.aftersale-home__intro-metric-value {
    display: block;
    font-size: 38rpx;
    line-height: 1;
    font-weight: 700;
    color: var(--wm-color-primary, #e85a4f);
}

.aftersale-home__intro-metric-label {
    display: block;
    margin-top: 10rpx;
    font-size: 22rpx;
    color: var(--wm-text-secondary, #7f7b78);
}

.aftersale-home__section {
    display: flex;
    flex-direction: column;
    gap: 16rpx;
}

.aftersale-home__entry-card {
    display: flex;
    align-items: flex-start;
    gap: 20rpx;
    padding: 28rpx 30rpx !important;
    border-radius: 44rpx !important;
}

.aftersale-home__entry-card--ticket {
    background: rgba(255, 241, 238, 0.96) !important;
    border-color: rgba(244, 199, 191, 0.96) !important;
}

.aftersale-home__entry-icon {
    width: 64rpx;
    height: 64rpx;
    flex-shrink: 0;
    border-radius: 24rpx;
    display: flex;
    align-items: center;
    justify-content: center;
    background: rgba(255, 247, 244, 0.94);
}

.aftersale-home__entry-copy {
    min-width: 0;
    flex: 1;
}

.aftersale-home__entry-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 14rpx;
}

.aftersale-home__entry-title {
    display: block;
    font-size: 30rpx;
    line-height: 1.3;
    font-weight: 700;
    color: var(--wm-text-primary, #1e2432);
}

.aftersale-home__entry-desc {
    display: block;
    margin-top: 10rpx;
    font-size: 24rpx;
    line-height: 1.58;
    color: var(--wm-text-secondary, #7f7b78);
}

.aftersale-home__entry-tip {
    display: block;
    margin-top: 12rpx;
    font-size: 22rpx;
    line-height: 1.5;
    color: var(--wm-text-tertiary, #b4aca8);
}
</style>
