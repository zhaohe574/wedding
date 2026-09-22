<template>
    <page-meta :page-style="$theme.pageStyle" />
    <PageShell scene="consumer" hasSafeBottom>
        <BaseNavbar title="报名详情" variant="solid" bg-color="#181614" text-color="#FFFDF8" />

        <view v-if="detail" class="activity-detail-page">
            <view class="activity-detail-page__body wm-page-content">
                <!-- 1. VIP 电子入场凭证卡片 (Electronic Voucher Ticket) -->
                <view class="voucher-ticket">
                    <!-- Ticket Top Section -->
                    <view class="voucher-ticket__top">
                        <view class="voucher-ticket__badge-row">
                            <view class="voucher-ticket__kicker">
                                <BaseIcon name="vip" size="20" color="#D9BE82" />
                                <text>电子报名凭证</text>
                            </view>
                            <StatusBadge
                                :tone="getStatusTone(detail)"
                                size="sm"
                                dot
                            >
                                {{ detail.registration_status_desc || '未知状态' }}
                            </StatusBadge>
                        </view>

                        <view class="voucher-ticket__hero-content">
                            <view class="voucher-ticket__title-box">
                                <text class="voucher-ticket__title">{{ detail.dynamic?.title || '精彩主题活动' }}</text>
                                <view class="voucher-ticket__ticket-pill">
                                    <BaseIcon name="order" size="18" color="#D9BE82" />
                                    <text class="voucher-ticket__ticket-text">
                                        {{ detail.ticket_name }} · {{ detail.ticket_price_label }}
                                    </text>
                                </view>
                            </view>

                            <image
                                v-if="activityCoverImage"
                                class="voucher-ticket__cover"
                                :src="activityCoverImage"
                                mode="aspectFill"
                            />
                        </view>
                    </view>

                    <!-- Perforated Cutout Separator -->
                    <view class="voucher-ticket__perforation">
                        <view class="voucher-ticket__notch voucher-ticket__notch--left"></view>
                        <view class="voucher-ticket__dash"></view>
                        <view class="voucher-ticket__notch voucher-ticket__notch--right"></view>
                    </view>

                    <!-- Ticket Bottom Section -->
                    <view class="voucher-ticket__bottom">

                        <view class="voucher-ticket__meta-grid">
                            <view class="voucher-ticket__meta-item">
                                <view class="voucher-ticket__meta-icon-box">
                                    <BaseIcon name="calendar" size="20" color="#D9BE82" />
                                </view>
                                <view class="voucher-ticket__meta-copy">
                                    <text class="voucher-ticket__meta-label">活动开始时间</text>
                                    <text class="voucher-ticket__meta-value">{{ formatTime(detail.dynamic?.activity?.activity_start_time) }}</text>
                                </view>
                            </view>

                            <view class="voucher-ticket__meta-item">
                                <view class="voucher-ticket__meta-icon-box">
                                    <BaseIcon name="clock" size="20" color="#D9BE82" />
                                </view>
                                <view class="voucher-ticket__meta-copy">
                                    <text class="voucher-ticket__meta-label">报名提交时间</text>
                                    <text class="voucher-ticket__meta-value">{{ formatTime(detail.create_time) }}</text>
                                </view>
                            </view>
                        </view>

                        <!-- Checked-in Tip -->
                        <view v-if="Number(detail.registration_status) === 1" class="voucher-ticket__checkin-tip">
                            <BaseIcon name="check-circle" size="22" color="#D9BE82" />
                            <text>活动当天请凭此凭证向现场工作人员出示核销入场</text>
                        </view>
                    </view>
                </view>

                <!-- 2. 活动详情与入口卡片 -->
                <view class="detail-card">
                    <view class="detail-card__header">
                        <view class="detail-card__title-box">
                            <BaseIcon name="dynamic" size="24" color="#B8954A" />
                            <text class="detail-card__title">活动信息</text>
                        </view>
                        <view
                            v-if="canGoActivity"
                            class="detail-card__link"
                            @click="goActivityDetail"
                        >
                            <text>查看原活动</text>
                            <BaseIcon name="right" size="20" color="#B8954A" />
                        </view>
                    </view>
                    <view class="detail-info-list">
                        <BaseInfoRow
                            label="活动主题"
                            :value="detail.dynamic?.title || '-'"
                            multiline
                        />
                        <BaseInfoRow
                            label="活动时间"
                            :value="formatTime(detail.dynamic?.activity?.activity_start_time)"
                        />
                        <BaseInfoRow
                            v-if="detail.dynamic?.activity?.activity_signup_deadline"
                            label="报名截止"
                            :value="formatTime(detail.dynamic?.activity?.activity_signup_deadline)"
                        />
                    </view>
                </view>

                <!-- 3. 报名人与支付卡片 -->
                <view class="detail-card">
                    <view class="detail-card__header">
                        <view class="detail-card__title-box">
                            <BaseIcon name="order" size="24" color="#B8954A" />
                            <text class="detail-card__title">报名人与支付信息</text>
                        </view>
                        <StatusBadge
                            :tone="detail.pay_status === 1 ? 'success' : 'neutral'"
                            size="xs"
                        >
                            {{ detail.pay_status_desc || '-' }}
                        </StatusBadge>
                    </view>
                    <view class="detail-info-list">
                        <BaseInfoRow label="联系人姓名" :value="detail.contact_name || '-'" />
                        <BaseInfoRow label="联系人电话" :value="detail.contact_mobile || '-'" />
                        <BaseInfoRow label="门票规格" :value="detail.ticket_name || '-'" />
                        <BaseInfoRow
                            label="支付金额"
                            :value="formatAmount(detail.pay_amount || detail.ticket_price)"
                            tone="price"
                        />
                        <BaseInfoRow
                            v-if="detail.pay_time"
                            label="支付时间"
                            :value="formatTime(detail.pay_time)"
                        />
                        <BaseInfoRow
                            v-if="detail.remark"
                            label="报名备注"
                            :value="detail.remark"
                            multiline
                        />
                    </view>
                </view>

                <!-- 4. 取消申请状态卡片 (有取消状态时显示) -->
                <view v-if="Number(detail.cancel_status || 0) > 0" class="detail-card">
                    <view class="detail-card__header">
                        <view class="detail-card__title-box">
                            <BaseIcon name="tip" size="24" color="#9A6B35" />
                            <text class="detail-card__title">取消申请状态</text>
                        </view>
                        <StatusBadge tone="warning" size="sm">
                            {{ detail.cancel_status_desc }}
                        </StatusBadge>
                    </view>
                    <view class="detail-info-list">
                        <BaseInfoRow label="取消原因" :value="detail.cancel_reason || '-'" multiline />
                        <BaseInfoRow
                            v-if="detail.cancel_reject_reason"
                            label="驳回说明"
                            :value="detail.cancel_reject_reason"
                            tone="danger"
                            multiline
                        />
                    </view>
                </view>

                <!-- 5. 退款进度卡片 (有退款信息时显示) -->
                <view v-if="detail.latest_refund" class="detail-card">
                    <view class="detail-card__header">
                        <view class="detail-card__title-box">
                            <BaseIcon name="funds" size="24" color="#B8954A" />
                            <text class="detail-card__title">退款进度</text>
                        </view>
                        <StatusBadge tone="warning" size="sm">
                            {{ detail.latest_refund.refund_status_desc }}
                        </StatusBadge>
                    </view>
                    <view class="detail-info-list">
                        <BaseInfoRow
                            label="申请退款金额"
                            :value="formatAmount(detail.latest_refund.refund_amount)"
                            tone="price"
                        />
                        <BaseInfoRow
                            label="实际退款金额"
                            :value="formatAmount(detail.latest_refund.actual_refund_amount)"
                            tone="price"
                        />
                        <BaseInfoRow
                            v-if="detail.latest_refund.audit_remark"
                            label="审核备注"
                            :value="detail.latest_refund.audit_remark"
                            multiline
                        />
                        <BaseInfoRow
                            v-if="detail.latest_refund.refund_msg"
                            label="退款说明"
                            :value="detail.latest_refund.refund_msg"
                            multiline
                        />
                    </view>
                </view>
            </view>

            <!-- 底部悬浮操作栏 -->
            <ActionArea v-if="showActions" sticky safeBottom class="activity-detail-page__action-area">
                <view class="action-bar">
                    <BaseButton
                        v-if="canGoActivity"
                        :block="!primaryActionVisible"
                        size="md"
                        height="84rpx"
                        font-size="26rpx"
                        variant="light"
                        @click="goActivityDetail"
                    >
                        查看原活动
                    </BaseButton>
                    <BaseButton
                        v-if="canPay"
                        :block="!canGoActivity"
                        size="md"
                        height="84rpx"
                        font-size="26rpx"
                        variant="primary"
                        @click="showPay = true"
                    >
                        继续支付
                    </BaseButton>
                    <BaseButton
                        v-else-if="canCancel"
                        :block="!canGoActivity"
                        size="md"
                        height="84rpx"
                        font-size="26rpx"
                        variant="secondary"
                        @click="openCancel"
                    >
                        申请取消报名
                    </BaseButton>
                </view>
            </ActionArea>
        </view>

        <!-- 异常空状态 -->
        <view v-else class="activity-detail-page__empty-wrap wm-page-content">
            <BaseCard variant="panel" padding="48rpx 32rpx">
                <LoadingState v-if="loading" text="正在加载报名详情..." tone="wedding" />
                <EmptyState v-else :title="errorText || '报名详情不存在'" compact />
                <view v-if="errorText" class="activity-detail-page__retry-btn">
                    <BaseButton size="sm" variant="dark" @click="fetchDetail">
                        重新加载
                    </BaseButton>
                </view>
            </BaseCard>
        </view>

        <!-- 取消申请抽屉 -->
        <BaseOverlayMask :show="showCancel" :z-index="20128" @close="showCancel = false" />
        <TnPopup v-model="showCancel" open-direction="bottom" :overlay="false" :radius="36" :z-index="20130">
            <view class="cancel-popup">
                <view class="cancel-popup__bar-wrap" @click="showCancel = false">
                    <view class="cancel-popup__bar"></view>
                </view>
                <view class="popup-head">
                    <text class="popup-head__title">申请取消报名</text>
                    <view class="popup-head__close" @click="showCancel = false">
                        <BaseIcon name="close" size="30" color="#191713" />
                    </view>
                </view>
                <view class="cancel-popup__content">
                    <view class="cancel-popup__tip-box">
                        <BaseIcon name="tip" size="24" color="#B8954A" />
                        <text class="cancel-popup__tip">提交取消申请后，活动主办方将审核您的申请并按规则执行退款流程。</text>
                    </view>
                    <view class="cancel-popup__input-wrap">
                        <textarea
                            v-model="cancelReason"
                            class="cancel-popup__textarea"
                            maxlength="200"
                            placeholder="请详细填写取消报名的原因（必填）"
                        />
                        <text class="cancel-popup__counter">{{ cancelReason.length }}/200</text>
                    </view>
                </view>
                <view class="popup-actions">
                    <BaseButton
                        variant="light"
                        size="md"
                        height="84rpx"
                        class="popup-actions__btn"
                        @click="showCancel = false"
                    >
                        暂不取消
                    </BaseButton>
                    <BaseButton
                        variant="primary"
                        size="md"
                        height="84rpx"
                        class="popup-actions__btn"
                        :loading="submittingCancel"
                        @click="submitCancel"
                    >
                        提交申请
                    </BaseButton>
                </view>
            </view>
        </TnPopup>

        <payment
            v-if="registrationId > 0"
            v-model:show="showPay"
            v-model:show-check="showPayCheck"
            :order-id="registrationId"
            from="activity_registration"
            redirect="/packages/pages/activity_registration/detail"
            @success="handlePaySuccess"
        />
    </PageShell>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue'
import { onLoad, onShow } from '@dcloudio/uni-app'
import TnPopup from '@tuniao/tnui-vue3-uniapp/components/popup/src/popup.vue'
import PageShell from '@/components/base/PageShell.vue'
import BaseNavbar from '@/components/base/BaseNavbar.vue'
import BaseCard from '@/components/base/BaseCard.vue'
import BaseButton from '@/components/base/BaseButton.vue'
import BaseIcon from '@/components/base/BaseIcon.vue'
import BaseOverlayMask from '@/components/base/BaseOverlayMask.vue'
import BaseInfoRow from '@/components/base/BaseInfoRow.vue'
import ActionArea from '@/components/base/ActionArea.vue'
import StatusBadge from '@/components/base/StatusBadge.vue'
import LoadingState from '@/components/base/LoadingState.vue'
import EmptyState from '@/components/base/EmptyState.vue'
import Payment from '@/components/payment/payment.vue'
import { useThemeStore } from '@/stores/theme'
import { applyActivityRegistrationCancel, getActivityRegistrationDetail } from '@/api/dynamic'
import { showError, showSuccess } from '@/utils/feedback'
import type { ActivityRegistration } from '@/types/dynamic'

const $theme = useThemeStore()
const registrationId = ref(0)
const detail = ref<ActivityRegistration | null>(null)
const showCancel = ref(false)
const cancelReason = ref('')
const submittingCancel = ref(false)
const showPay = ref(false)
const showPayCheck = ref(false)
const loading = ref(false)
const errorText = ref('')

const canPay = computed(() => !!detail.value && Number(detail.value.registration_status) === 0)
const canCancel = computed(() => [0, 1, 5].includes(Number(detail.value?.registration_status || -1)))
const activityId = computed(() => Number(detail.value?.dynamic_id || detail.value?.dynamic?.id || 0))
const canGoActivity = computed(() => activityId.value > 0)
const primaryActionVisible = computed(() => canPay.value || canCancel.value)
const showActions = computed(() => canGoActivity.value || primaryActionVisible.value)

const activityCoverImage = computed(() => {
    const images = detail.value?.dynamic?.images
    if (Array.isArray(images) && images.length > 0) {
        return images[0]
    }
    if (typeof images === 'string' && images.trim()) {
        try {
            const parsed = JSON.parse(images)
            if (Array.isArray(parsed) && parsed.length > 0) return parsed[0]
        } catch {
            return images.split(',')[0]
        }
        return images
    }
    return ''
})

const getStatusTone = (item: any) => {
    const status = Number(item?.registration_status ?? -1)
    if (status === 0) return 'warning'
    if (status === 1) return 'success'
    if (status === 2) return 'primary'
    if (status === 3) return 'neutral'
    if (status === 4) return 'info'
    if (status === 5) return 'danger'
    return 'neutral'
}


const fetchDetail = async () => {
    if (!registrationId.value) return
    loading.value = true
    errorText.value = ''
    try {
        detail.value = await getActivityRegistrationDetail({ id: registrationId.value })
    } catch (error: unknown) {
        detail.value = null
        errorText.value = error instanceof Error ? error.message : '报名详情加载失败'
    } finally {
        loading.value = false
    }
}

const formatTime = (timestamp: number | string | undefined) => {
    if (timestamp === undefined || timestamp === null || timestamp === '') return '-'
    const rawValue = String(timestamp).trim()
    if (!rawValue || rawValue === '0') return '-'
    const numericValue = Number(rawValue)
    const date = Number.isFinite(numericValue)
        ? new Date(numericValue > 1000000000000 ? numericValue : numericValue * 1000)
        : new Date(rawValue.replace(/-/g, '/'))
    if (Number.isNaN(date.getTime())) return '-'
    const pad = (num: number) => String(num).padStart(2, '0')
    return `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())} ${pad(
        date.getHours()
    )}:${pad(date.getMinutes())}`
}

const formatAmount = (amount: number | string | undefined) => {
    const value = Number(amount || 0)
    return value <= 0 ? '¥0.00' : `¥${value.toFixed(2)}`
}

const goActivityDetail = () => {
    if (!activityId.value) {
        showError('活动不存在')
        return
    }
    uni.navigateTo({ url: `/packages/pages/dynamic_detail/dynamic_detail?id=${activityId.value}` })
}

const openCancel = () => {
    cancelReason.value = ''
    showCancel.value = true
}

const submitCancel = async () => {
    if (!cancelReason.value.trim()) {
        showError('请填写取消原因')
        return
    }
    submittingCancel.value = true
    try {
        await applyActivityRegistrationCancel({
            registration_id: registrationId.value,
            reason: cancelReason.value
        })
        showCancel.value = false
        showSuccess('已提交申请')
        fetchDetail()
    } finally {
        submittingCancel.value = false
    }
}

const handlePaySuccess = () => {
    showPay.value = false
    showPayCheck.value = false
    fetchDetail()
}

onLoad((options?: { id?: string | number }) => {
    registrationId.value = Number(options?.id || 0)
    fetchDetail()
})
onShow(() => fetchDetail())
</script>

<style lang="scss" scoped>
.activity-detail-page {
    min-height: 100vh;
    background:
        radial-gradient(ellipse at 50% 0%, rgba(217, 190, 130, 0.12) 0%, rgba(248, 246, 240, 0) 70%),
        var(--wm-color-bg-page, #f8f6f0);
    box-sizing: border-box;
    padding-bottom: calc(140rpx + env(safe-area-inset-bottom));

    &__body {
        padding: 24rpx var(--wm-space-page-x, 28rpx) calc(48rpx + env(safe-area-inset-bottom));
        display: flex;
        flex-direction: column;
        gap: 24rpx;
        box-sizing: border-box;
    }

    &__empty-wrap {
        padding: 40rpx var(--wm-space-page-x, 28rpx);
    }

    &__retry-btn {
        display: flex;
        justify-content: center;
        margin-top: 24rpx;
    }
}

/* VIP 电子入场凭证卡片 (Electronic Voucher Ticket) */
.voucher-ticket {
    position: relative;
    border-radius: 36rpx;
    background: linear-gradient(150deg, #1c1915 0%, #151310 50%, #2b2216 100%);
    border: 1rpx solid rgba(217, 190, 130, 0.55);
    box-shadow: 0 18rpx 44rpx rgba(24, 22, 20, 0.25);
    overflow: hidden;
    box-sizing: border-box;

    &__top {
        padding: 32rpx 30rpx 20rpx;
        display: flex;
        flex-direction: column;
        gap: 20rpx;
    }

    &__badge-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
    }

    &__kicker {
        display: inline-flex;
        align-items: center;
        gap: 8rpx;
        padding: 4rpx 16rpx;
        border-radius: var(--wm-radius-pill, 999rpx);
        background: rgba(217, 190, 130, 0.15);
        border: 1rpx solid rgba(217, 190, 130, 0.45);
        font-size: 20rpx;
        font-weight: 800;
        color: var(--wm-color-champagne, #d9be82);
    }

    &__hero-content {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 20rpx;
    }

    &__title-box {
        min-width: 0;
        flex: 1;
        display: flex;
        flex-direction: column;
        gap: 12rpx;
    }

    &__title {
        font-size: 34rpx;
        font-weight: 900;
        line-height: 1.35;
        color: #fffdf8;
    }

    &__ticket-pill {
        display: inline-flex;
        align-items: center;
        gap: 8rpx;
        padding: 6rpx 18rpx;
        border-radius: var(--wm-radius-pill, 999rpx);
        background: rgba(217, 190, 130, 0.2);
        border: 1rpx solid rgba(217, 190, 130, 0.4);
        align-self: flex-start;
    }

    &__ticket-text {
        font-size: 22rpx;
        color: var(--wm-color-champagne, #d9be82);
        font-weight: 700;
    }

    &__cover {
        width: 140rpx;
        height: 140rpx;
        flex-shrink: 0;
        border-radius: 20rpx;
        border: 1rpx solid rgba(217, 190, 130, 0.4);
    }

    /* Perforated Separator */
    &__perforation {
        position: relative;
        height: 32rpx;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    &__notch {
        position: absolute;
        top: 50%;
        transform: translateY(-50%);
        width: 32rpx;
        height: 32rpx;
        border-radius: 50%;
        background: var(--wm-color-bg-page, #f8f6f0);
        z-index: 2;

        &--left {
            left: -16rpx;
            border-right: 1rpx solid rgba(217, 190, 130, 0.55);
        }

        &--right {
            right: -16rpx;
            border-left: 1rpx solid rgba(217, 190, 130, 0.55);
        }
    }

    &__dash {
        width: calc(100% - 64rpx);
        height: 0;
        border-top: 2rpx dashed rgba(217, 190, 130, 0.4);
    }

    &__bottom {
        padding: 20rpx 30rpx 32rpx;
        display: flex;
        flex-direction: column;
        gap: 18rpx;
    }


    &__meta-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 14rpx;
    }

    &__meta-item {
        display: flex;
        align-items: center;
        gap: 12rpx;
        padding: 14rpx 16rpx;
        border-radius: 20rpx;
        background: rgba(255, 253, 248, 0.05);
        border: 1rpx solid rgba(217, 190, 130, 0.2);
    }

    &__meta-icon-box {
        width: 44rpx;
        height: 44rpx;
        flex-shrink: 0;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 12rpx;
        background: rgba(217, 190, 130, 0.15);
    }

    &__meta-copy {
        min-width: 0;
        flex: 1;
        display: flex;
        flex-direction: column;
        gap: 2rpx;
    }

    &__meta-label {
        font-size: 19rpx;
        color: rgba(255, 253, 248, 0.6);
    }

    &__meta-value {
        font-size: 21rpx;
        font-weight: 700;
        color: #fffdf8;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    &__checkin-tip {
        display: flex;
        align-items: center;
        gap: 10rpx;
        padding: 14rpx 20rpx;
        border-radius: 18rpx;
        background: rgba(217, 190, 130, 0.12);
        border: 1rpx solid rgba(217, 190, 130, 0.35);
        font-size: 21rpx;
        color: var(--wm-color-champagne, #d9be82);
        line-height: 1.4;
    }
}

/* 通用详情卡片 */
.detail-card {
    padding: 28rpx;
    border-radius: 32rpx;
    background: #fffdf8;
    border: 1rpx solid rgba(216, 201, 173, 0.65);
    box-shadow: 0 12rpx 32rpx rgba(74, 43, 24, 0.05);
    display: flex;
    flex-direction: column;
    gap: 16rpx;
    box-sizing: border-box;

    &__header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding-bottom: 12rpx;
        border-bottom: 1rpx solid rgba(216, 201, 173, 0.45);
    }

    &__title-box {
        display: flex;
        align-items: center;
        gap: 10rpx;
    }

    &__title {
        font-size: 28rpx;
        font-weight: 800;
        color: var(--wm-text-primary, #191713);
    }

    &__link {
        display: inline-flex;
        align-items: center;
        gap: 4rpx;
        font-size: 22rpx;
        font-weight: 800;
        color: var(--wm-color-gold, #b8954a);
    }
}

.detail-info-list {
    display: flex;
    flex-direction: column;
    gap: 0;

    :deep(.base-info-row + .base-info-row) {
        border-top: 1rpx solid rgba(231, 224, 211, 0.6);
    }
}

/* 底部操作栏 */
.action-bar {
    display: flex;
    align-items: center;
    gap: 16rpx;
    width: 100%;

    :deep(.base-button) {
        flex: 1;
    }
}

/* 弹窗抽屉 */
.cancel-popup {
    padding: 20rpx 32rpx calc(48rpx + env(safe-area-inset-bottom));
    background: #faf7f2;
    box-sizing: border-box;

    &__bar-wrap {
        display: flex;
        justify-content: center;
        padding: 8rpx 0 16rpx;
    }

    &__bar {
        width: 72rpx;
        height: 8rpx;
        border-radius: 4rpx;
        background: rgba(25, 23, 19, 0.18);
    }
}

.popup-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding-bottom: 20rpx;
    border-bottom: 1rpx solid rgba(216, 201, 173, 0.6);

    &__title {
        font-size: 32rpx;
        font-weight: 900;
        color: var(--wm-text-primary, #191713);
    }

    &__close {
        width: 58rpx;
        height: 58rpx;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: var(--wm-radius-pill, 999rpx);
        background: rgba(25, 23, 19, 0.06);
    }
}

.cancel-popup__content {
    display: flex;
    flex-direction: column;
    gap: 20rpx;
    margin-top: 24rpx;
}

.cancel-popup__tip-box {
    display: flex;
    align-items: flex-start;
    gap: 12rpx;
    padding: 18rpx 20rpx;
    border-radius: 20rpx;
    background: rgba(217, 190, 130, 0.14);
    border: 1rpx solid rgba(217, 190, 130, 0.45);
}

.cancel-popup__tip {
    font-size: 22rpx;
    color: var(--wm-color-clay, #8f6027);
    line-height: 1.5;
}

.cancel-popup__input-wrap {
    position: relative;
}

.cancel-popup__textarea {
    width: 100%;
    min-height: 220rpx;
    padding: 22rpx 22rpx 50rpx;
    border-radius: 24rpx;
    background: #fffdf8;
    border: 1rpx solid rgba(216, 201, 173, 0.7);
    box-sizing: border-box;
    font-size: 26rpx;
    color: var(--wm-text-primary, #191713);
}

.cancel-popup__counter {
    position: absolute;
    right: 20rpx;
    bottom: 16rpx;
    font-size: 20rpx;
    color: var(--wm-text-tertiary, #9a9388);
}

.popup-actions {
    display: flex;
    gap: 18rpx;
    margin-top: 28rpx;

    &__btn {
        flex: 1;
    }
}

@media screen and (max-width: 360px) {
    .voucher-ticket__meta-grid {
        grid-template-columns: 1fr;
    }
}
</style>
