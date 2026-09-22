<template>
    <page-meta :page-style="$theme.pageStyle" />
    <PageShell scene="consumer" tone="workspace" hasSafeBottom>
        <BaseNavbar
            title="售后服务"
            variant="solid"
            bg-color="#191713"
            text-color="#FFFDF8"
        />

        <view class="aftersale-home">
            <view class="aftersale-home__wrapper wm-page-content">
                <!-- 1. 黑曜石售后进度 Hero 看板 -->
                <view class="aftersale-status-panel">
                    <view class="aftersale-status-panel__top">
                        <view class="aftersale-status-panel__kicker">
                            <BaseIcon name="shield-check" size="20" color="#D9BE82" />
                            <text>售后进度概览</text>
                        </view>
                        <text class="aftersale-status-panel__summary">
                            {{ unfinishedTotalText }}
                        </text>
                    </view>

                    <view class="aftersale-status-panel__metrics">
                        <view class="aftersale-status-panel__metric" @click="goTicketList('unfinished')">
                            <text class="aftersale-status-panel__metric-value">
                                {{ stats.ticket.unfinished }}
                            </text>
                            <text class="aftersale-status-panel__metric-label">待办工单</text>
                        </view>

                        <view class="aftersale-status-panel__metric" @click="goComplaintList('unfinished')">
                            <text class="aftersale-status-panel__metric-value">
                                {{ stats.complaint.unfinished }}
                            </text>
                            <text class="aftersale-status-panel__metric-label">处理中投诉</text>
                        </view>

                        <view class="aftersale-status-panel__metric" @click="goCallback">
                            <text class="aftersale-status-panel__metric-value">
                                {{ stats.callback.unfinished }}
                            </text>
                            <text class="aftersale-status-panel__metric-label">待填回访</text>
                        </view>
                    </view>
                </view>

                <!-- 2. 快速发起双核心通道 -->
                <view class="aftersale-home__section">
                    <view class="aftersale-home__section-head">
                        <text class="aftersale-home__section-title">快速发起</text>
                        <text class="aftersale-home__section-desc">选择契合的服务通道，平台专席极速响应</text>
                    </view>

                    <view class="aftersale-home__primary-grid">
                        <!-- 事项协助工单 -->
                        <BaseCard
                            variant="list"
                            scene="consumer"
                            interactive
                            padding="28rpx 26rpx"
                            class="aftersale-primary-card aftersale-primary-card--ticket"
                            border="1rpx solid rgba(216, 201, 173, 0.78)"
                            box-shadow="0 14rpx 32rpx rgba(74, 43, 24, 0.06)"
                            @click="goCreateTicket"
                        >
                            <view class="aftersale-primary-card__head">
                                <view class="aftersale-primary-card__icon aftersale-primary-card__icon--ticket">
                                    <BaseIcon name="file-text" size="30" color="#D9BE82" />
                                </view>
                                <text class="aftersale-primary-card__badge">常规协助</text>
                            </view>

                            <text class="aftersale-primary-card__title">提交事项工单</text>
                            <text class="aftersale-primary-card__desc">排期微调 · 流程加项 · 资料素材 · 咨询答疑</text>

                            <view class="aftersale-primary-card__footer">
                                <text class="aftersale-primary-card__footer-text">去提交</text>
                                <BaseIcon name="right" size="20" color="#B8954A" />
                            </view>
                        </BaseCard>

                        <!-- 服务监督投诉 -->
                        <BaseCard
                            variant="list"
                            scene="consumer"
                            interactive
                            padding="28rpx 26rpx"
                            class="aftersale-primary-card aftersale-primary-card--complaint"
                            border="1rpx solid rgba(216, 201, 173, 0.78)"
                            box-shadow="0 14rpx 32rpx rgba(74, 43, 24, 0.06)"
                            @click="goCreateComplaint"
                        >
                            <view class="aftersale-primary-card__head">
                                <view class="aftersale-primary-card__icon aftersale-primary-card__icon--complaint">
                                    <BaseIcon name="warning-circle" size="30" color="#C27D50" />
                                </view>
                                <text class="aftersale-primary-card__badge aftersale-primary-card__badge--danger">严肃维权</text>
                            </view>

                            <text class="aftersale-primary-card__title">发起服务投诉</text>
                            <text class="aftersale-primary-card__desc">履约落差 · 服务态度 · 沟通偏差 · 争议核查</text>

                            <view class="aftersale-primary-card__footer">
                                <text class="aftersale-primary-card__footer-text aftersale-primary-card__footer-text--danger">去投诉</text>
                                <BaseIcon name="right" size="20" color="#9A6B35" />
                            </view>
                        </BaseCard>
                    </view>
                </view>

                <!-- 3. 我的服务记录三大专区 -->
                <view class="aftersale-home__section">
                    <view class="aftersale-home__section-head">
                        <text class="aftersale-home__section-title">服务记录</text>
                        <text class="aftersale-home__section-desc">随时查看历史处理进展与闭环结果</text>
                    </view>

                    <view class="aftersale-home__record-cards">
                        <!-- 工单记录 -->
                        <BaseCard
                            variant="list"
                            scene="consumer"
                            interactive
                            padding="24rpx 26rpx"
                            border="1rpx solid rgba(216, 201, 173, 0.7)"
                            box-shadow="0 8rpx 22rpx rgba(74, 43, 24, 0.04)"
                            @click="goTicketList()"
                        >
                            <view class="aftersale-record-entry">
                                <view class="aftersale-record-entry__left">
                                    <view class="aftersale-record-entry__icon">
                                        <BaseIcon name="order" size="26" color="#B8954A" />
                                    </view>
                                    <view class="aftersale-record-entry__copy">
                                        <view class="aftersale-record-entry__title-row">
                                            <text class="aftersale-record-entry__title">工单记录</text>
                                            <StatusBadge
                                                v-if="stats.ticket.unfinished > 0"
                                                tone="warning"
                                                size="xs"
                                            >
                                                {{ stats.ticket.unfinished }} 条待办
                                            </StatusBadge>
                                        </view>
                                        <text class="aftersale-record-entry__desc">
                                            {{ stats.ticket.total > 0 ? `累计提交 ${stats.ticket.total} 项需求工单` : '暂无提交的历史工单' }}
                                        </text>
                                    </view>
                                </view>
                                <view class="aftersale-record-entry__arrow">
                                    <BaseIcon name="right" size="22" color="#9A9388" />
                                </view>
                            </view>
                        </BaseCard>

                        <!-- 投诉记录 -->
                        <BaseCard
                            variant="list"
                            scene="consumer"
                            interactive
                            padding="24rpx 26rpx"
                            border="1rpx solid rgba(216, 201, 173, 0.7)"
                            box-shadow="0 8rpx 22rpx rgba(74, 43, 24, 0.04)"
                            @click="goComplaintList()"
                        >
                            <view class="aftersale-record-entry">
                                <view class="aftersale-record-entry__left">
                                    <view class="aftersale-record-entry__icon aftersale-record-entry__icon--complaint">
                                        <BaseIcon name="warning-circle" size="26" color="#9A6B35" />
                                    </view>
                                    <view class="aftersale-record-entry__copy">
                                        <view class="aftersale-record-entry__title-row">
                                            <text class="aftersale-record-entry__title">投诉记录</text>
                                            <StatusBadge
                                                v-if="stats.complaint.unfinished > 0"
                                                tone="danger"
                                                size="xs"
                                            >
                                                {{ stats.complaint.unfinished }} 条跟进中
                                            </StatusBadge>
                                        </view>
                                        <text class="aftersale-record-entry__desc">
                                            {{ stats.complaint.total > 0 ? `累计发起 ${stats.complaint.total} 项监督投诉` : '暂无发起的历史投诉' }}
                                        </text>
                                    </view>
                                </view>
                                <view class="aftersale-record-entry__arrow">
                                    <BaseIcon name="right" size="22" color="#9A9388" />
                                </view>
                            </view>
                        </BaseCard>

                        <!-- 满意度回访 -->
                        <BaseCard
                            variant="list"
                            scene="consumer"
                            interactive
                            padding="24rpx 26rpx"
                            border="1rpx solid rgba(216, 201, 173, 0.7)"
                            box-shadow="0 8rpx 22rpx rgba(74, 43, 24, 0.04)"
                            @click="goCallback"
                        >
                            <view class="aftersale-record-entry">
                                <view class="aftersale-record-entry__left">
                                    <view class="aftersale-record-entry__icon aftersale-record-entry__icon--callback">
                                        <BaseIcon name="edit" size="26" color="#4F6F5A" />
                                    </view>
                                    <view class="aftersale-record-entry__copy">
                                        <view class="aftersale-record-entry__title-row">
                                            <text class="aftersale-record-entry__title">满意度回访问卷</text>
                                            <StatusBadge
                                                v-if="stats.callback.pending > 0"
                                                tone="warning"
                                                size="xs"
                                            >
                                                {{ stats.callback.pending }} 份待填
                                            </StatusBadge>
                                        </view>
                                        <text class="aftersale-record-entry__desc">
                                            {{ stats.callback.pending > 0 ? '有未完成的服务体验问卷待您填写' : '查看婚礼体验反馈记录' }}
                                        </text>
                                    </view>
                                </view>
                                <view class="aftersale-record-entry__arrow">
                                    <BaseIcon name="right" size="22" color="#9A9388" />
                                </view>
                            </view>
                        </BaseCard>
                    </view>
                </view>

                <!-- 4. 底部专属人工客服 -->
                <BaseCard
                    variant="list"
                    scene="consumer"
                    interactive
                    padding="26rpx 28rpx"
                    border="1rpx solid rgba(217, 190, 130, 0.65)"
                    box-shadow="0 10rpx 26rpx rgba(74, 43, 24, 0.06)"
                    background="linear-gradient(180deg, #fffdf8 0%, #faf6ec 100%)"
                    @click="contactService"
                >
                    <view class="aftersale-contact-card">
                        <view class="aftersale-contact-card__left">
                            <view class="aftersale-contact-card__icon-box">
                                <BaseIcon name="service" size="30" color="#D9BE82" />
                            </view>
                            <view class="aftersale-contact-card__copy">
                                <text class="aftersale-contact-card__title">联系专属客服管家</text>
                                <text class="aftersale-contact-card__desc">遇到紧急事项或沟通疑问，可随时联系专席婚礼管家</text>
                            </view>
                        </view>
                        <view class="aftersale-contact-card__action">
                            <text>立即咨询</text>
                            <BaseIcon name="right" size="20" color="#191713" />
                        </view>
                    </view>
                </BaseCard>
            </view>
        </view>
    </PageShell>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue'
import { onLoad, onShow } from '@dcloudio/uni-app'
import BaseCard from '@/components/base/BaseCard.vue'
import BaseIcon from '@/components/base/BaseIcon.vue'
import BaseNavbar from '@/components/base/BaseNavbar.vue'
import PageShell from '@/components/base/PageShell.vue'
import StatusBadge from '@/components/base/StatusBadge.vue'
import { getMyStatistics } from '@/packages/common/api/aftersale'
import { useThemeStore } from '@/stores/theme'

interface StatisticsItem {
    total: number
    pending: number
    unfinished: number
}

interface StatisticsState {
    ticket: StatisticsItem
    complaint: StatisticsItem
    callback: StatisticsItem
}

const $theme = useThemeStore()
const statistics = ref<StatisticsState>({
    ticket: { total: 0, pending: 0, unfinished: 0 },
    complaint: { total: 0, pending: 0, unfinished: 0 },
    callback: { total: 0, pending: 0, unfinished: 0 }
})

const stats = computed(() => statistics.value)
const unfinishedTotalText = computed(() => {
    const total =
        stats.value.ticket.unfinished +
        stats.value.complaint.unfinished +
        stats.value.callback.unfinished
    return total > 0 ? `${total} 项服务推进中` : '所有服务事项皆已办结'
})

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
    try {
        const response = await getMyStatistics()
        const data = response?.data || response || {}
        statistics.value = {
            ticket: {
                total: Number(data?.ticket?.total || 0),
                pending: Number(data?.ticket?.pending || 0),
                unfinished: Number(data?.ticket?.unfinished ?? data?.ticket?.pending ?? 0)
            },
            complaint: {
                total: Number(data?.complaint?.total || 0),
                pending: Number(data?.complaint?.pending || 0),
                unfinished: Number(data?.complaint?.unfinished ?? data?.complaint?.pending ?? 0)
            },
            callback: {
                total: Number(data?.callback?.total || 0),
                pending: Number(data?.callback?.pending || 0),
                unfinished: Number(data?.callback?.unfinished ?? data?.callback?.pending ?? 0)
            }
        }
    } catch (error) {
        console.error('加载售后统计失败', error)
    }
}

const goCreateTicket = () => {
    uni.navigateTo({ url: '/packages/pages/aftersale/create_ticket' })
}

const goCreateComplaint = () => {
    uni.navigateTo({ url: '/packages/pages/aftersale/create_complaint' })
}

const goTicketList = (status?: string) => {
    const query = status ? `?status=${status}` : ''
    uni.navigateTo({ url: `/packages/pages/aftersale/ticket${query}` })
}

const goComplaintList = (status?: string) => {
    const query = status ? `?status=${status}` : ''
    uni.navigateTo({ url: `/packages/pages/aftersale/complaint${query}` })
}

const goCallback = () => {
    const query = stats.value.callback.unfinished > 0 ? '?status=0' : ''
    uni.navigateTo({ url: `/packages/pages/aftersale/callback${query}` })
}

const contactService = () => {
    uni.navigateTo({
        url: '/packages/pages/customer_service/customer_service?scene=aftersale'
    })
}

const loadPageData = async () => {
    await loadStatistics()
}

onLoad((options: any) => {
    applyRouteAction(options?.action)
})

onShow(() => {
    $theme.setScene('consumer')
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
    gap: 24rpx;
}

/* 1. 黑曜石售后进度 Hero 看板 */
.aftersale-status-panel {
    overflow: hidden;
    padding: 28rpx;
    border-radius: 34rpx;
    border: 1rpx solid rgba(217, 190, 130, 0.65);
    background: radial-gradient(
            circle at 88% -40rpx,
            rgba(217, 190, 130, 0.22) 0,
            rgba(217, 190, 130, 0) 180rpx
        ),
        linear-gradient(145deg, #26221B 0%, #171512 60%, #30261A 100%);
    box-shadow: 0 16rpx 36rpx rgba(74, 43, 24, 0.12);
    display: flex;
    flex-direction: column;
    gap: 22rpx;
    box-sizing: border-box;
}

.aftersale-status-panel__top {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16rpx;
}

.aftersale-status-panel__kicker {
    display: inline-flex;
    align-items: center;
    gap: 8rpx;
    padding: 4rpx 14rpx;
    border-radius: var(--wm-radius-pill, 999rpx);
    background: rgba(217, 190, 130, 0.16);
    border: 1rpx solid rgba(217, 190, 130, 0.45);
    font-size: 20rpx;
    font-weight: 800;
    color: var(--wm-color-champagne, #d9be82);
}

.aftersale-status-panel__summary {
    font-size: 23rpx;
    font-weight: 700;
    color: rgba(255, 253, 248, 0.85);
}

.aftersale-status-panel__metrics {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 14rpx;
    padding-top: 20rpx;
    border-top: 1rpx solid rgba(217, 190, 130, 0.22);
}

.aftersale-status-panel__metric {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 16rpx 8rpx;
    border-radius: 20rpx;
    background: rgba(255, 253, 248, 0.06);
    border: 1rpx solid rgba(217, 190, 130, 0.2);
    box-sizing: border-box;
    transition: all 0.2s ease;
}

.aftersale-status-panel__metric:active {
    background: rgba(255, 253, 248, 0.12);
}

.aftersale-status-panel__metric-value {
    font-size: 38rpx;
    font-weight: 900;
    line-height: 1.1;
    color: var(--wm-color-champagne, #d9be82);
}

.aftersale-status-panel__metric-label {
    margin-top: 6rpx;
    font-size: 20rpx;
    color: rgba(255, 253, 248, 0.72);
}

/* 2. 结构化区块 */
.aftersale-home__section {
    display: flex;
    flex-direction: column;
    gap: 16rpx;
}

.aftersale-home__section-head {
    display: flex;
    flex-direction: column;
    gap: 4rpx;
}

.aftersale-home__section-title {
    font-size: 30rpx;
    font-weight: 900;
    line-height: 1.3;
    color: var(--wm-text-primary, #191713);
}

.aftersale-home__section-desc {
    font-size: 21rpx;
    color: var(--wm-text-tertiary, #9a9388);
}

/* 快速发起双卡片 */
.aftersale-home__primary-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 16rpx;
}

.aftersale-primary-card {
    display: flex;
    flex-direction: column;
    gap: 10rpx;
    min-height: 236rpx;
}

.aftersale-primary-card__head {
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.aftersale-primary-card__icon {
    width: 60rpx;
    height: 60rpx;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 20rpx;
    background: #191713;
    border: 1rpx solid rgba(217, 190, 130, 0.6);
}

.aftersale-primary-card__badge {
    padding: 2rpx 12rpx;
    border-radius: var(--wm-radius-pill, 999rpx);
    background: rgba(217, 190, 130, 0.16);
    border: 1rpx solid rgba(217, 190, 130, 0.45);
    font-size: 19rpx;
    font-weight: 800;
    color: var(--wm-color-gold, #b8954a);
}

.aftersale-primary-card__badge--danger {
    background: rgba(184, 92, 56, 0.12);
    border-color: rgba(184, 92, 56, 0.35);
    color: var(--wm-color-clay, #9a6b35);
}

.aftersale-primary-card__title {
    margin-top: 6rpx;
    font-size: 28rpx;
    font-weight: 900;
    line-height: 1.35;
    color: var(--wm-text-primary, #191713);
}

.aftersale-primary-card__desc {
    font-size: 21rpx;
    line-height: 1.45;
    color: var(--wm-text-tertiary, #9a9388);
}

.aftersale-primary-card__footer {
    display: inline-flex;
    align-items: center;
    gap: 6rpx;
    margin-top: auto;
    padding-top: 10rpx;
}

.aftersale-primary-card__footer-text {
    font-size: 23rpx;
    font-weight: 800;
    color: var(--wm-color-gold, #b8954a);
}

.aftersale-primary-card__footer-text--danger {
    color: var(--wm-color-clay, #9a6b35);
}

/* 3. 服务记录列表 */
.aftersale-home__record-cards {
    display: flex;
    flex-direction: column;
    gap: 16rpx;
}

.aftersale-record-entry {
    display: flex;
    flex-direction: row;
    align-items: center;
    justify-content: space-between;
    width: 100%;
    gap: 16rpx;
}

.aftersale-record-entry__left {
    min-width: 0;
    flex: 1;
    display: flex;
    align-items: center;
    gap: 18rpx;
}

.aftersale-record-entry__icon {
    width: 64rpx;
    height: 64rpx;
    flex-shrink: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 20rpx;
    background: rgba(248, 242, 228, 0.85);
    border: 1rpx solid rgba(216, 201, 173, 0.6);
}

.aftersale-record-entry__icon--complaint {
    background: rgba(245, 235, 226, 0.85);
    border-color: rgba(210, 185, 170, 0.6);
}

.aftersale-record-entry__icon--callback {
    background: rgba(235, 242, 236, 0.85);
    border-color: rgba(185, 205, 190, 0.6);
}

.aftersale-record-entry__copy {
    min-width: 0;
    flex: 1;
    display: flex;
    flex-direction: column;
    gap: 4rpx;
}

.aftersale-record-entry__title-row {
    display: flex;
    align-items: center;
    gap: 12rpx;
}

.aftersale-record-entry__title {
    font-size: 28rpx;
    font-weight: 800;
    color: var(--wm-text-primary, #191713);
}

.aftersale-record-entry__desc {
    font-size: 21rpx;
    color: var(--wm-text-tertiary, #9a9388);
}

.aftersale-record-entry__arrow {
    flex-shrink: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    width: 32rpx;
    height: 32rpx;
}

/* 4. 底部专属客服卡片 */
.aftersale-contact-card {
    display: flex;
    flex-direction: row;
    align-items: center;
    justify-content: space-between;
    width: 100%;
    gap: 18rpx;
}

.aftersale-contact-card__left {
    min-width: 0;
    flex: 1;
    display: flex;
    align-items: center;
    gap: 18rpx;
}

.aftersale-contact-card__icon-box {
    width: 64rpx;
    height: 64rpx;
    flex-shrink: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 20rpx;
    background: #191713;
    border: 1rpx solid rgba(217, 190, 130, 0.6);
}

.aftersale-contact-card__copy {
    min-width: 0;
    flex: 1;
    display: flex;
    flex-direction: column;
    gap: 4rpx;
}

.aftersale-contact-card__title {
    font-size: 28rpx;
    font-weight: 900;
    color: var(--wm-text-primary, #191713);
}

.aftersale-contact-card__desc {
    font-size: 20rpx;
    color: var(--wm-text-secondary, #5f5a50);
}

.aftersale-contact-card__action {
    flex-shrink: 0;
    display: inline-flex;
    align-items: center;
    gap: 4rpx;
    padding: 10rpx 20rpx;
    border-radius: var(--wm-radius-pill, 999rpx);
    background: var(--wm-color-champagne, #d9be82);
    font-size: 22rpx;
    font-weight: 800;
    color: #191713;
    white-space: nowrap;
}

@media screen and (max-width: 360px) {
    .aftersale-home__primary-grid {
        grid-template-columns: 1fr;
    }
}
</style>
