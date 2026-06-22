<template>
    <page-meta :page-style="$theme.pageStyle" />
    <PageShell scene="consumer" hasSafeBottom>
        <BaseNavbar title="报名详情" variant="solid" bg-color="#191713" text-color="#FFFDF8" />
        <view v-if="detail" class="activity-registration-detail wm-page-content">
            <BaseCard variant="panel" class="activity-registration-detail__card">
                <view class="activity-registration-detail__head">
                    <view>
                        <text class="activity-registration-detail__title">
                            {{ detail.dynamic?.title || '活动' }}
                        </text>
                        <text class="activity-registration-detail__subtitle">
                            {{ detail.ticket_name }} · {{ detail.ticket_price_label }}
                        </text>
                    </view>
                    <StatusBadge tone="primary" size="sm">
                        {{ detail.registration_status_desc }}
                    </StatusBadge>
                </view>
                <view class="activity-registration-detail__rows">
                    <view v-for="item in infoRows" :key="item.label" class="activity-registration-detail__row">
                        <text>{{ item.label }}</text>
                        <text>{{ item.value }}</text>
                    </view>
                </view>
            </BaseCard>

            <BaseCard v-if="Number(detail.cancel_status || 0) > 0" variant="panel" class="activity-registration-detail__card">
                <text class="activity-registration-detail__section-title">取消申请</text>
                <view class="activity-registration-detail__rows">
                    <view class="activity-registration-detail__row">
                        <text>状态</text>
                        <text>{{ detail.cancel_status_desc }}</text>
                    </view>
                    <view class="activity-registration-detail__row">
                        <text>原因</text>
                        <text>{{ detail.cancel_reason || '-' }}</text>
                    </view>
                    <view v-if="detail.cancel_reject_reason" class="activity-registration-detail__row">
                        <text>拒绝原因</text>
                        <text>{{ detail.cancel_reject_reason }}</text>
                    </view>
                </view>
            </BaseCard>

            <BaseCard v-if="detail.latest_refund" variant="panel" class="activity-registration-detail__card">
                <text class="activity-registration-detail__section-title">退款进度</text>
                <view class="activity-registration-detail__rows">
                    <view class="activity-registration-detail__row">
                        <text>状态</text>
                        <text>{{ detail.latest_refund.refund_status_desc }}</text>
                    </view>
                    <view class="activity-registration-detail__row">
                        <text>申请金额</text>
                        <text>{{ formatAmount(detail.latest_refund.refund_amount) }}</text>
                    </view>
                    <view class="activity-registration-detail__row">
                        <text>实退金额</text>
                        <text>{{ formatAmount(detail.latest_refund.actual_refund_amount) }}</text>
                    </view>
                    <view v-if="detail.latest_refund.audit_remark" class="activity-registration-detail__row">
                        <text>审核备注</text>
                        <text>{{ detail.latest_refund.audit_remark }}</text>
                    </view>
                    <view v-if="detail.latest_refund.refund_msg" class="activity-registration-detail__row">
                        <text>退款说明</text>
                        <text>{{ detail.latest_refund.refund_msg }}</text>
                    </view>
                </view>
            </BaseCard>
        </view>
        <view v-else class="activity-registration-detail wm-page-content">
            <BaseCard variant="panel">
                <LoadingState v-if="loading" text="正在加载报名详情..." />
                <EmptyState v-else :title="errorText || '报名详情不存在'" compact />
                <BaseButton
                    v-if="errorText"
                    class="activity-registration-detail__retry"
                    size="sm"
                    @click="fetchDetail"
                >
                    重试
                </BaseButton>
            </BaseCard>
        </view>

        <ActionArea v-if="detail && showActions" sticky safeBottom>
            <view class="activity-registration-detail__action-row">
                <BaseButton
                    v-if="canGoActivity"
                    :block="!primaryActionVisible"
                    size="lg"
                    variant="secondary"
                    @click="goActivityDetail"
                >
                    查看原活动
                </BaseButton>
            <BaseButton
                v-if="canPay"
                :block="!canGoActivity"
                size="lg"
                @click="showPay = true"
            >
                继续支付
            </BaseButton>
            <BaseButton
                v-else-if="canCancel"
                :block="!canGoActivity"
                size="lg"
                variant="secondary"
                @click="openCancel"
            >
                申请取消
            </BaseButton>
            </view>
        </ActionArea>

        <BaseOverlayMask :show="showCancel" :z-index="20128" @close="showCancel = false" />
        <TnPopup v-model="showCancel" open-direction="bottom" :overlay="false" :radius="28" :z-index="20130">
            <view class="activity-registration-detail__cancel-popup">
                <view class="activity-registration-detail__popup-head">
                    <text>申请取消</text>
                    <BaseIcon name="close" size="30" color="#9A9388" @click="showCancel = false" />
                </view>
                <textarea
                    v-model="cancelReason"
                    class="activity-registration-detail__textarea"
                    maxlength="200"
                    placeholder="请填写取消原因"
                />
                <ActionArea>
                    <BaseButton block size="lg" :loading="submittingCancel" @click="submitCancel">
                        提交申请
                    </BaseButton>
                </ActionArea>
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
const infoRows = computed(() => [
    { label: '活动时间', value: formatTime(detail.value?.dynamic?.activity?.activity_start_time) },
    { label: '报名时间', value: formatTime(detail.value?.create_time) },
    { label: '联系人', value: detail.value?.contact_name || '-' },
    { label: '手机号', value: detail.value?.contact_mobile || '-' },
    { label: '支付状态', value: detail.value?.pay_status_desc || '-' },
    { label: '支付时间', value: formatTime(detail.value?.pay_time) },
    { label: '报名备注', value: detail.value?.remark || '-' }
])

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
.activity-registration-detail {
    padding-top: 24rpx;
    padding-bottom: 220rpx;

    &__card {
        padding: 28rpx;
        margin-bottom: 18rpx;
    }

    &__head {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 18rpx;
    }

    &__title {
        display: block;
        font-size: 32rpx;
        font-weight: 900;
        color: var(--wm-text-primary, #191713);
    }

    &__subtitle {
        display: block;
        margin-top: 8rpx;
        font-size: 24rpx;
        color: var(--wm-text-secondary, #665E52);
    }

    &__section-title {
        display: block;
        margin-bottom: 16rpx;
        font-size: 28rpx;
        font-weight: 900;
    }

    &__rows {
        margin-top: 22rpx;
    }

    &__row {
        display: flex;
        justify-content: space-between;
        gap: 20rpx;
        padding: 16rpx 0;
        border-top: 1rpx solid rgba(216, 201, 173, 0.72);
        font-size: 25rpx;
        color: var(--wm-text-primary, #191713);
    }

    &__action-row {
        display: flex;
        align-items: center;
        gap: 18rpx;
        width: 100%;
    }

    &__action-row :deep(.base-button) {
        flex: 1;
        min-width: 0;
    }

    &__cancel-popup {
        padding: 28rpx;
        background: var(--wm-color-bg-card, #FFFDF8);
        border-radius: 28rpx 28rpx 0 0;
    }

    &__popup-head {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20rpx;
        font-size: 30rpx;
        font-weight: 900;
    }

    &__textarea {
        width: 100%;
        min-height: 180rpx;
        padding: 22rpx;
        border-radius: 22rpx;
        border: 1rpx solid rgba(216, 201, 173, 0.86);
        box-sizing: border-box;
        font-size: 27rpx;
    }

    &__retry {
        margin-top: 18rpx;
    }
}
</style>
