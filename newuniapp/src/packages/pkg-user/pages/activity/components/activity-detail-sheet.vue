<template>
    <tn-popup v-model="visible" open-direction="bottom" :radius="40">
        <view v-if="item" class="sheet">
            <view class="sheet__head">
                <text class="sheet__title">入场凭证与报名详情</text>
                <view
                    class="sheet__close"
                    hover-class="is-pressed"
                    aria-role="button"
                    aria-label="关闭详情"
                    @tap="visible = false"
                >
                    <tn-icon name="close" :size="30" class="sheet__close-ic" />
                </view>
            </view>

            <scroll-view scroll-y class="sheet__body">
                <!-- 电子凭证主卡 -->
                <view class="voucher">
                    <view class="voucher__kicker-row">
                        <text class="voucher__kicker">{{ voucherKicker }}</text>
                        <status-tag :label="badge.label" :tone="badge.tone" size="sm" />
                    </view>
                    <text class="voucher__title">{{ titleText }}</text>
                    <text class="voucher__sub">{{ item.ticket_name || '活动门票' }} · {{ item.ticket_price_label || '免费' }}</text>

                    <template v-if="showQr">
                        <view class="voucher__qr-wrap">
                            <activity-qr-canvas :value="`ACT_CHECKIN:${item.checkin_code}`" />
                        </view>
                        <view class="voucher__code-row" @tap="copyCode">
                            <text class="voucher__code">{{ item.checkin_code }}</text>
                            <view class="voucher__copy">
                                <text class="voucher__copy-text">复制</text>
                            </view>
                        </view>
                        <text class="voucher__tip">
                            {{
                                isCheckedIn
                                    ? `已于 ${formatFullTime(item.checkin_time)} 核销入场`
                                    : '现场请向工作人员出示二维码或 8 位核销码（离线可用）'
                            }}
                        </text>
                    </template>

                    <view v-else-if="isPendingPay" class="voucher__notice">
                        <text class="voucher__notice-text">
                            {{
                                payRemain > 0
                                    ? `请在 ${formatCountdown(payRemain)} 内完成支付，支付后自动生成入场核销码`
                                    : '支付已超时，名额正在释放'
                            }}
                        </text>
                    </view>

                    <view v-else class="voucher__notice">
                        <text class="voucher__notice-text">{{ noticeFallback }}</text>
                    </view>
                </view>

                <!-- 活动信息 -->
                <view class="block">
                    <view class="block__head">
                        <text class="block__title">活动信息</text>
                        <view
                            v-if="item.dynamic_id"
                            class="block__link"
                            hover-class="is-pressed"
                            @tap="emit('openActivity', item)"
                        >
                            <text class="block__link-text">查看原活动</text>
                            <tn-icon name="right" :size="20" class="block__link-ic" />
                        </view>
                    </view>
                    <view class="row">
                        <text class="row__k">活动主题</text>
                        <text class="row__v">{{ titleText }}</text>
                    </view>
                    <view class="row">
                        <text class="row__k">开始时间</text>
                        <text class="row__v">{{ formatFullTime(startTs) }}</text>
                    </view>
                    <view v-if="rawEndTs > 0" class="row">
                        <text class="row__k">结束时间</text>
                        <text class="row__v">{{ formatFullTime(rawEndTs) }}</text>
                    </view>
                    <view v-if="locationText" class="row">
                        <text class="row__k">活动地点</text>
                        <view class="row__v-action" @tap="copyLocation">
                            <text class="row__v">{{ locationText }}</text>
                            <text class="row__copy">复制</text>
                        </view>
                    </view>
                </view>

                <!-- 报名与支付信息 -->
                <view class="block">
                    <view class="block__head">
                        <text class="block__title">报名与支付</text>
                        <text class="block__sn">#{{ item.id }}</text>
                    </view>
                    <view class="row">
                        <text class="row__k">联系人</text>
                        <text class="row__v">{{ item.contact_name || '—' }}</text>
                    </view>
                    <view class="row">
                        <text class="row__k">手机号</text>
                        <text class="row__v">{{ item.contact_mobile || '—' }}</text>
                    </view>
                    <view class="row">
                        <text class="row__k">票种费用</text>
                        <text class="row__v">{{ item.ticket_name || '—' }} · {{ item.ticket_price_label || '免费' }}</text>
                    </view>
                    <view class="row">
                        <text class="row__k">支付状态</text>
                        <text class="row__v">{{ item.pay_status_desc || '—' }}</text>
                    </view>
                    <view v-if="Number(item.pay_time) > 0" class="row">
                        <text class="row__k">支付时间</text>
                        <text class="row__v">{{ formatFullTime(item.pay_time) }}</text>
                    </view>
                    <view class="row">
                        <text class="row__k">报名时间</text>
                        <text class="row__v">{{ formatFullTime(item.create_time) }}</text>
                    </view>
                    <view v-if="item.remark" class="row">
                        <text class="row__k">报名备注</text>
                        <text class="row__v">{{ item.remark }}</text>
                    </view>
                </view>

                <!-- 取消与退款进度 -->
                <view v-if="showCancelBlock" class="block">
                    <view class="block__head">
                        <text class="block__title">取消与退款轨迹</text>
                        <text v-if="item.latest_refund" class="block__sn">{{
                            item.latest_refund.refund_sn
                        }}</text>
                    </view>
                    <view v-if="Number(item.cancel_status) > 0" class="row">
                        <text class="row__k">取消状态</text>
                        <text class="row__v">{{ item.cancel_status_desc || '—' }}</text>
                    </view>
                    <view v-if="item.cancel_reason" class="row">
                        <text class="row__k">取消原因</text>
                        <text class="row__v">{{ item.cancel_reason }}</text>
                    </view>
                    <view v-if="item.cancel_reject_reason" class="row">
                        <text class="row__k">驳回说明</text>
                        <text class="row__v row__v--danger">{{ item.cancel_reject_reason }}</text>
                    </view>
                    <template v-if="item.latest_refund">
                        <view class="row">
                            <text class="row__k">退款状态</text>
                            <text class="row__v">{{ item.latest_refund.refund_status_desc }}</text>
                        </view>
                        <view class="row">
                            <text class="row__k">申请金额</text>
                            <text class="row__v">¥{{ Number(item.latest_refund.refund_amount || 0).toFixed(2) }}</text>
                        </view>
                        <view v-if="Number(item.latest_refund.actual_refund_amount) > 0" class="row">
                            <text class="row__k">实退金额</text>
                            <text class="row__v">¥{{ Number(item.latest_refund.actual_refund_amount).toFixed(2) }}</text>
                        </view>
                        <view v-if="item.latest_refund.audit_remark" class="row">
                            <text class="row__k">审核备注</text>
                            <text class="row__v">{{ item.latest_refund.audit_remark }}</text>
                        </view>
                        <view v-if="item.latest_refund.refund_msg" class="row">
                            <text class="row__k">退款说明</text>
                            <text class="row__v">{{ item.latest_refund.refund_msg }}</text>
                        </view>
                    </template>
                </view>
            </scroll-view>

            <view class="sheet__foot">
                <view v-if="canCancel" class="foot-btn">
                    <base-button
                        :text="cancelBtnText"
                        variant="ghost"
                        size="md"
                        @click="emit('cancel', item)"
                    />
                </view>
                <view v-if="isPendingPay" class="foot-btn">
                    <base-button
                        :text="`继续支付 ${item.ticket_price_label || ''}`"
                        variant="ink"
                        size="md"
                        :loading="payingId === item.id"
                        @click="emit('pay', item)"
                    />
                </view>
                <view v-else-if="item.dynamic_id" class="foot-btn">
                    <base-button
                        text="查看原活动"
                        variant="ink"
                        size="md"
                        @click="emit('openActivity', item)"
                    />
                </view>
                <view v-else class="foot-btn">
                    <base-button text="关闭" variant="ink" size="md" @click="visible = false" />
                </view>
            </view>
        </view>
    </tn-popup>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import type { ActivityRegistrationVO } from '@/api/types/activity'
import { toast } from '@/composables/useToast'
import ActivityQrCanvas from './activity-qr-canvas.vue'
import {
    activityLocation,
    activityStartTs,
    activityTitle,
    formatCountdown,
    formatFullTime,
    getPayRemainSeconds,
    resolveStatusBadge
} from '@/utils/activityFormat'

const props = defineProps<{
    show: boolean
    item: ActivityRegistrationVO | null
    nowSec: number
    payingId: number
}>()

const emit = defineEmits<{
    (e: 'update:show', value: boolean): void
    (e: 'pay', item: ActivityRegistrationVO): void
    (e: 'cancel', item: ActivityRegistrationVO): void
    (e: 'openActivity', item: ActivityRegistrationVO): void
}>()

const visible = computed({
    get: () => props.show,
    set: (val: boolean) => emit('update:show', val)
})

const titleText = computed(() => (props.item ? activityTitle(props.item) : ''))
const startTs = computed(() => (props.item ? activityStartTs(props.item) : 0))
const rawEndTs = computed(() =>
    props.item ? Number(props.item.activity_end_time || props.item.dynamic?.activity_end_time || 0) : 0
)
const locationText = computed(() => (props.item ? activityLocation(props.item) : ''))
const badge = computed(() =>
    props.item ? resolveStatusBadge(props.item, props.nowSec * 1000) : { label: '', tone: 'neutral' as const }
)

const isPendingPay = computed(() => Number(props.item?.registration_status) === 0)
const isCheckedIn = computed(() => Number(props.item?.checkin_status) === 1)
const payRemain = computed(() => (props.item ? getPayRemainSeconds(props.item, props.nowSec) : 0))

const showQr = computed(() => {
    if (!props.item) return false
    const code = String(props.item.checkin_code || '').trim()
    const status = Number(props.item.registration_status)
    return !!code && (status === 1 || Number(props.item.checkin_status) === 1)
})

const voucherKicker = computed(() => {
    if (isCheckedIn.value) return 'CHECKED IN · 已签到核销'
    if (showQr.value) return 'E-VOUCHER · 电子入场凭证'
    if (isPendingPay.value) return 'PENDING PAYMENT · 待支付'
    return 'REGISTRATION · 报名凭证'
})

const noticeFallback = computed(() => {
    const status = Number(props.item?.registration_status ?? -1)
    if (status === 2) return '取消申请审核中，入场凭证已暂冻'
    if (status === 3) return '该报名已取消，原占用名额已释放'
    if (status === 4) return '退款处理中，资金将原路退回微信零钱或银行卡'
    if (status === 5) return '退款未能完成，您可重新提交取消退款申请'
    return '当前暂无可用核销码'
})

const showCancelBlock = computed(() => {
    if (!props.item) return false
    return (
        Number(props.item.cancel_status || 0) > 0 ||
        !!props.item.cancel_reason ||
        !!props.item.latest_refund
    )
})

const canCancel = computed(() => Number(props.item?.can_cancel) === 1)
const cancelBtnText = computed(() => {
    if (props.item?.cancel_mode === 'direct') return '取消报名'
    if (Number(props.item?.registration_status) === 5) return '重新申请退款'
    return '申请取消退款'
})

function copyCode() {
    const code = String(props.item?.checkin_code || '').trim()
    if (!code) return
    uni.setClipboardData({
        data: code,
        success: () => toast.success('核销码已复制')
    })
}

function copyLocation() {
    const loc = locationText.value
    if (!loc) return
    uni.setClipboardData({
        data: loc,
        success: () => toast.success('活动地点已复制')
    })
}
</script>

<style lang="scss" scoped>
.sheet {
    max-height: 85vh;
    display: flex;
    flex-direction: column;
    background: var(--c-surface);

    &__head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: var(--sp-4) var(--page-gutter) var(--sp-3);
        border-bottom: 1rpx solid var(--c-line);
    }

    &__title {
        font-size: var(--fs-lg);
        font-weight: var(--fw-semibold);
        color: var(--c-ink);
    }

    &__close {
        width: 56rpx;
        height: 56rpx;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: var(--r-pill);
    }

    &__close-ic {
        color: var(--c-ink-3);
    }

    &__body {
        max-height: 62vh;
        padding: var(--sp-3) var(--page-gutter) var(--sp-4);
        box-sizing: border-box;
    }

    &__foot {
        display: flex;
        gap: var(--sp-2);
        padding: var(--sp-3) var(--page-gutter) calc(var(--sp-4) + var(--safe-bottom));
        border-top: 1rpx solid var(--c-line);
        background: var(--c-surface);
    }
}

.foot-btn {
    flex: 1;
    min-width: 0;
}

.voucher {
    padding: var(--sp-4);
    border-radius: var(--r-md);
    background: linear-gradient(152deg, var(--c-dark-1) 0%, var(--c-dark-2) 58%, var(--c-dark-3) 100%);
    border: 1rpx solid var(--c-primary-border);
    margin-bottom: var(--sp-3);

    &__kicker-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: var(--sp-2);
    }

    &__kicker {
        font-family: var(--font-serif);
        font-size: var(--fs-18);
        letter-spacing: 0.14em;
        color: var(--c-brand-soft);
    }

    &__title {
        display: block;
        margin-top: var(--sp-2);
        font-size: var(--fs-lg);
        font-weight: var(--fw-medium);
        line-height: var(--lh-tight);
        color: var(--c-on-dark);
    }

    &__sub {
        display: block;
        margin-top: var(--sp-1);
        font-size: var(--fs-22);
        color: var(--c-on-dark-2);
    }

    &__qr-wrap {
        margin-top: var(--sp-4);
        display: flex;
        justify-content: center;
    }

    &__code-row {
        margin-top: var(--sp-3);
        display: flex;
        align-items: center;
        justify-content: center;
        gap: var(--sp-2);
    }

    &__code {
        font-family: var(--font-serif);
        font-size: var(--fs-46);
        letter-spacing: 0.16em;
        color: var(--c-brand-soft);
    }

    &__copy {
        padding: 4rpx 14rpx;
        border-radius: var(--r-pill);
        border: 1rpx solid var(--c-primary-border);
        background: rgba(217, 190, 130, 0.14);
    }

    &__copy-text {
        font-size: var(--fs-20);
        color: var(--c-brand-soft);
    }

    &__tip {
        display: block;
        margin-top: var(--sp-2);
        text-align: center;
        font-size: var(--fs-20);
        line-height: var(--lh-body);
        color: var(--c-on-dark-2);
    }

    &__notice {
        margin-top: var(--sp-3);
        padding: var(--sp-2) var(--sp-3);
        border-radius: var(--r-sm);
        background: rgba(255, 253, 248, 0.08);
        border: 1rpx solid rgba(217, 190, 130, 0.25);
    }

    &__notice-text {
        font-size: var(--fs-22);
        line-height: var(--lh-body);
        color: var(--c-brand-soft);
    }
}

.block {
    padding: var(--sp-3);
    border-radius: var(--r-md);
    background: var(--c-bg);
    border: 1rpx solid var(--c-line);
    margin-bottom: var(--sp-3);

    &__head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: var(--sp-2);
    }

    &__title {
        font-size: var(--fs-xs);
        font-weight: var(--fw-semibold);
        color: var(--c-ink);
    }

    &__sn {
        font-size: var(--fs-20);
        color: var(--c-ink-3);
    }

    &__link {
        display: flex;
        align-items: center;
        gap: 4rpx;
    }

    &__link-text {
        font-size: var(--fs-22);
        color: var(--c-brand-text);
        font-weight: var(--fw-medium);
    }

    &__link-ic {
        color: var(--c-brand-text);
    }
}

.row {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: var(--sp-3);
    padding: var(--sp-1) 0;

    &__k {
        flex: none;
        font-size: var(--fs-23);
        color: var(--c-ink-3);
        line-height: var(--lh-body);
    }

    &__v {
        flex: 1;
        min-width: 0;
        text-align: right;
        font-size: var(--fs-23);
        color: var(--c-ink);
        line-height: var(--lh-body);
        word-break: break-all;

        &--danger {
            color: var(--c-danger);
        }
    }

    &__v-action {
        flex: 1;
        min-width: 0;
        display: flex;
        align-items: center;
        justify-content: flex-end;
        gap: var(--sp-1);
    }

    &__copy {
        flex: none;
        font-size: var(--fs-20);
        color: var(--c-brand-text);
        padding: 2rpx 10rpx;
        border-radius: var(--r-pill);
        background: var(--c-brand-bg);
    }
}
</style>
