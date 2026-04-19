<template>
    <page-meta :page-style="$theme.pageStyle" />

    <PageShell scene="consumer">
        <BaseNavbar title="订单详情" />

        <view v-if="order" class="order-detail">
            <view class="page-body wm-page-content">
                <view
                    class="status-card wm-panel-card"
                    :style="{ background: statusTheme.background }"
                >
                    <view class="status-card__chip" :style="{ background: statusTheme.iconBg }">
                        <text class="status-card__chip-text">{{ order.order_status_desc }}</text>
                    </view>

                    <text class="status-card__title">{{ statusHeadline }}</text>

                    <text class="status-card__desc">{{ statusCardText }}</text>

                    <view
                        v-if="
                            showConfirmCountdown ||
                            showConfirmTimeoutAction ||
                            showPayCountdown ||
                            showPayTimeoutAction ||
                            showVoucherPending ||
                            Number(needPayAmount) > 0
                        "
                        class="status-card__meta"
                    >
                        <view v-if="showConfirmCountdown" class="status-card__meta-item">
                            <text class="status-card__meta-label">剩余确认时间</text>

                            <text class="status-card__meta-value">{{ confirmCountdownText }}</text>
                        </view>

                        <view v-if="showConfirmTimeoutAction" class="status-card__meta-item">
                            <text class="status-card__meta-label">超时处理</text>

                            <text class="status-card__meta-value">
                                {{ confirmTimeoutActionText }}
                            </text>
                        </view>

                        <view v-if="showPayCountdown" class="status-card__meta-item">
                            <text class="status-card__meta-label">剩余支付时间</text>

                            <text class="status-card__meta-value">{{ payCountdownText }}</text>
                        </view>

                        <view v-if="showPayTimeoutAction" class="status-card__meta-item">
                            <text class="status-card__meta-label">支付超时处理</text>

                            <text class="status-card__meta-value">
                                {{ payTimeoutActionText }}
                            </text>
                        </view>

                        <view v-if="showVoucherPending" class="status-card__meta-item">
                            <text class="status-card__meta-label">线下凭证</text>

                            <text class="status-card__meta-value">审核中</text>
                        </view>

                        <view v-if="showNeedPayMeta" class="status-card__meta-item">
                            <text class="status-card__meta-label">{{ needPayMetaLabel }}</text>

                            <text class="status-card__meta-value"
                                >¥{{ formatAmount(needPayAmount) }}</text
                            >
                        </view>
                    </view>
                </view>

                <view class="card card--secondary wm-form-block">
                    <text class="card__title">当前说明</text>

                    <view class="sub-panel">
                        <view
                            v-for="item in statusGuideRows"
                            :key="item.label"
                            class="sub-panel__row"
                            :class="{ 'sub-panel__row--stack': item.multiline }"
                        >
                            <text class="sub-panel__label">{{ item.label }}</text>

                            <text
                                class="sub-panel__value"
                                :class="{ 'sub-panel__value--left': item.multiline }"
                            >
                                {{ item.value }}
                            </text>
                        </view>
                    </view>
                </view>

                <view v-if="confirmLetterAvailable" class="card card--secondary wm-form-block">
                    <view class="card__title-row">
                        <text class="card__title">订单确认函</text>

                        <view class="card__title-actions">
                            <view
                                v-if="confirmLetterHistoryAvailable"
                                class="inline-copy"
                                @click="handleOpenConfirmLetterHistory"
                            >
                                <text class="inline-copy__text">版本记录</text>
                            </view>

                            <view class="inline-copy" @click="handleOpenConfirmLetter">
                                <text class="inline-copy__text">查看订单确认函</text>
                            </view>
                        </view>
                    </view>

                    <view class="sub-panel">
                        <view class="sub-panel__row">
                            <text class="sub-panel__label">版本</text>

                            <text class="sub-panel__value">v{{ confirmLetter?.version }}</text>
                        </view>

                        <view v-if="confirmLetterHistoryAvailable" class="sub-panel__row">
                            <text class="sub-panel__label">版本记录</text>

                            <text class="sub-panel__value"
                                >共 {{ confirmLetterHistory.length }} 个版本</text
                            >
                        </view>

                        <view class="sub-panel__row">
                            <text class="sub-panel__label">确认日期</text>

                            <text class="sub-panel__value">{{
                                confirmLetter?.rendered_snapshot?.confirm_date || '-'
                            }}</text>
                        </view>
                    </view>
                </view>

                <view class="card card--core wm-form-block">
                    <text class="card__title">服务信息</text>

                    <view class="service-summary">
                        <view class="service-summary__topbar">
                            <text class="service-summary__label">主套餐</text>

                            <text class="service-summary__price"
                                >¥{{ formatAmount(primaryServiceAmount) }}</text
                            >
                        </view>

                        <text class="service-summary__title">{{ serviceCardTitle }}</text>

                        <view class="service-summary__meta-grid">
                            <view
                                v-for="meta in primaryServiceMetaRows"
                                :key="meta.label"
                                class="service-summary__meta-card"
                            >
                                <text class="service-summary__meta-label">{{ meta.label }}</text>

                                <text class="service-summary__meta-value">{{ meta.value }}</text>
                            </view>
                        </view>

                        <text v-if="primaryPackageDescription" class="service-summary__desc">
                            {{ primaryPackageDescription }}
                        </text>
                    </view>

                    <view class="service-addon-section">
                        <view class="service-addon-section__header">
                            <text class="service-addon-section__title">附加套餐</text>

                            <text class="service-addon-section__meta">{{
                                serviceAddonSummaryText
                            }}</text>
                        </view>

                        <view v-if="serviceAddonRows.length" class="service-addon-list">
                            <view
                                v-for="item in serviceAddonRows"
                                :key="item.key"
                                class="service-addon-item"
                            >
                                <view class="service-addon-item__copy">
                                    <view class="service-addon-item__title-row">
                                        <text class="service-addon-item__title">{{
                                            item.title
                                        }}</text>

                                        <text class="service-addon-item__type">{{
                                            item.typeText
                                        }}</text>
                                    </view>

                                    <text v-if="item.metaText" class="service-addon-item__meta">
                                        {{ item.metaText }}
                                    </text>

                                    <text v-if="item.description" class="service-addon-item__desc">
                                        {{ item.description }}
                                    </text>
                                </view>

                                <text class="service-addon-item__price">{{ item.priceText }}</text>
                            </view>
                        </view>

                        <view v-else class="service-addon-empty">
                            <text>当前订单未配置附加套餐</text>
                        </view>
                    </view>

                    <view v-if="serviceRelatedRows.length" class="service-addon-section">
                        <view class="service-addon-section__header">
                            <text class="service-addon-section__title">协作服务</text>

                            <text class="service-addon-section__meta"
                                >共 {{ serviceRelatedRows.length }} 项</text
                            >
                        </view>

                        <view class="service-addon-list">
                            <view
                                v-for="item in serviceRelatedRows"
                                :key="item.key"
                                class="service-addon-item service-addon-item--related"
                            >
                                <view class="service-addon-item__copy">
                                    <view class="service-addon-item__title-row">
                                        <text class="service-addon-item__title">{{
                                            item.title
                                        }}</text>

                                        <text
                                            class="service-addon-item__type service-addon-item__type--related"
                                            >{{ item.typeText }}</text
                                        >
                                    </view>

                                    <text v-if="item.metaText" class="service-addon-item__meta">
                                        {{ item.metaText }}
                                    </text>

                                    <text v-if="item.description" class="service-addon-item__desc">
                                        {{ item.description }}
                                    </text>
                                </view>

                                <text class="service-addon-item__price">{{ item.priceText }}</text>
                            </view>
                        </view>
                    </view>
                </view>

                <view class="card card--core wm-form-block">
                    <text class="card__title">费用明细</text>

                    <text class="card__main-text">总价 ¥{{ formatAmount(totalOrderAmount) }}</text>

                    <text class="card__sub-text">已付 {{ paidAmountText }}</text>

                    <text class="card__sub-text">待付 {{ pendingAmountText }}</text>

                    <view
                        v-if="hasFinancialDetails"
                        class="inline-link"
                        @click="showFinancialDetails = !showFinancialDetails"
                    >
                        <text class="inline-link__text">
                            {{ showFinancialDetails ? '收起详细金额' : '查看详细金额' }}
                        </text>
                    </view>

                    <view v-if="showFinancialDetails" class="sub-panel">
                        <view class="sub-panel__row">
                            <text class="sub-panel__label">主服务金额</text>

                            <text class="sub-panel__value"
                                >¥{{ formatAmount(orderServiceAmount) }}</text
                            >
                        </view>

                        <view v-if="Number(order.addon_amount || 0) > 0" class="sub-panel__row">
                            <text class="sub-panel__label">附加内容金额</text>

                            <text class="sub-panel__value"
                                >¥{{ formatAmount(order.addon_amount) }}</text
                            >
                        </view>

                        <view v-if="Number(order.discount_amount || 0) > 0" class="sub-panel__row">
                            <text class="sub-panel__label">优惠金额</text>

                            <text class="sub-panel__value sub-panel__value--danger">
                                -¥{{ formatAmount(order.discount_amount) }}
                            </text>
                        </view>

                        <view v-if="Number(order.deposit_amount || 0) > 0" class="sub-panel__row">
                            <text class="sub-panel__label">定金</text>

                            <text class="sub-panel__value"
                                >¥{{ formatAmount(order.deposit_amount) }}</text
                            >
                        </view>

                        <view v-if="Number(order.balance_amount || 0) > 0" class="sub-panel__row">
                            <text class="sub-panel__label">尾款</text>

                            <text class="sub-panel__value"
                                >¥{{ formatAmount(order.balance_amount) }}</text
                            >
                        </view>
                    </view>
                </view>

                <view class="card card--core wm-form-block">
                    <text class="card__title">流程进度</text>

                    <view class="progress-list">
                        <view
                            v-for="item in progressItems"
                            :key="item.label"
                            class="progress-list__row"
                        >
                            <text class="progress-list__label">{{ item.label }}</text>

                            <text class="progress-list__value">{{ item.value }}</text>
                        </view>
                    </view>
                </view>

                <view class="card card--core">
                    <text class="card__title">联系与履约信息</text>

                    <view class="info-list">
                        <view class="info-list__row">
                            <text class="info-list__main">{{ contactPrimaryText }}</text>
                        </view>

                        <view class="info-list__row">
                            <text class="info-list__sub">{{ contactSecondaryText }}</text>
                        </view>

                        <view v-if="contactTertiaryText" class="info-list__row">
                            <text class="info-list__sub">{{ contactTertiaryText }}</text>
                        </view>
                    </view>
                </view>

                <view class="card card--secondary">
                    <view class="card__title-row">
                        <text class="card__title">订单信息</text>

                        <view class="inline-copy" @click="copyOrderSn">
                            <text class="inline-copy__text">复制编号</text>
                        </view>
                    </view>

                    <view class="sub-panel">
                        <view class="sub-panel__row">
                            <text class="sub-panel__label">订单编号</text>

                            <text class="sub-panel__value">{{ order.order_sn }}</text>
                        </view>

                        <view class="sub-panel__row">
                            <text class="sub-panel__label">下单时间</text>

                            <text class="sub-panel__value">{{ order.create_time || '-' }}</text>
                        </view>

                        <view class="sub-panel__row">
                            <text class="sub-panel__label">付款渠道</text>

                            <text class="sub-panel__value">{{ paymentChannelDesc }}</text>
                        </view>

                        <view v-if="order.pay_time" class="sub-panel__row">
                            <text class="sub-panel__label">支付时间</text>

                            <text class="sub-panel__value">{{ order.pay_time }}</text>
                        </view>
                    </view>
                </view>

                <view v-if="showOfflineVoucherCard" class="card card--secondary">
                    <text class="card__title">线下支付凭证</text>

                    <view class="sub-panel">
                        <view class="sub-panel__row">
                            <text class="sub-panel__label">付款渠道</text>

                            <text class="sub-panel__value">{{ paymentChannelDesc }}</text>
                        </view>

                        <view class="sub-panel__row">
                            <text class="sub-panel__label">凭证状态</text>

                            <text class="sub-panel__value">{{
                                order.pay_voucher_status_desc || '未上传'
                            }}</text>
                        </view>

                        <view
                            v-if="order.pay_voucher_audit_remark"
                            class="sub-panel__row sub-panel__row--stack"
                        >
                            <text class="sub-panel__label">审核备注</text>

                            <text class="sub-panel__value sub-panel__value--left">
                                {{ order.pay_voucher_audit_remark }}
                            </text>
                        </view>
                    </view>

                    <view v-if="order.pay_voucher" class="voucher-image">
                        <image :src="order.pay_voucher" mode="aspectFill" />
                    </view>

                    <view v-else class="voucher-empty"><text>暂无凭证</text></view>
                </view>

                <view v-if="order.refund" class="card card--secondary">
                    <text class="card__title">退款信息</text>

                    <view class="sub-panel">
                        <view class="sub-panel__row">
                            <text class="sub-panel__label">退款状态</text>

                            <text
                                class="refund-status"
                                :style="{
                                    color: getRefundStatusStyle(order.refund.refund_status).color,

                                    backgroundColor: getRefundStatusStyle(
                                        order.refund.refund_status
                                    ).bg
                                }"
                            >
                                {{ order.refund.refund_status_desc }}
                            </text>
                        </view>

                        <view class="sub-panel__row">
                            <text class="sub-panel__label">退款金额</text>

                            <text class="sub-panel__value">
                                ¥{{ formatAmount(order.refund.refund_amount) }}
                            </text>
                        </view>

                        <view class="sub-panel__row">
                            <text class="sub-panel__label">实际退款金额</text>

                            <text class="sub-panel__value">
                                ¥{{ formatAmount(order.refund.actual_refund_amount || 0) }}
                            </text>
                        </view>

                        <view class="sub-panel__row">
                            <text class="sub-panel__label">退款类型</text>

                            <text class="sub-panel__value">
                                {{ order.refund.refund_type_desc || '退款申请' }}
                            </text>
                        </view>

                        <view class="sub-panel__row sub-panel__row--stack">
                            <text class="sub-panel__label">退款原因</text>

                            <text class="sub-panel__value sub-panel__value--left">
                                {{ order.refund.refund_reason }}
                            </text>
                        </view>

                        <view
                            v-if="order.refund.refund_items && order.refund.refund_items.length"
                            class="sub-panel__row sub-panel__row--stack"
                        >
                            <text class="sub-panel__label">退款明细</text>

                            <view class="refund-item-list">
                                <view
                                    v-for="item in order.refund.refund_items"
                                    :key="item.id || item.out_refund_no"
                                    class="refund-item"
                                >
                                    <view class="refund-item__head">
                                        <text class="refund-item__title">
                                            {{ getPayWayText(Number(item.pay_way || 0)) }}
                                        </text>

                                        <text class="refund-item__amount">
                                            ¥{{ formatAmount(item.refund_amount || 0) }}
                                        </text>
                                    </view>

                                    <view class="refund-item__meta">
                                        <text>{{
                                            getRefundItemStatusText(Number(item.refund_status || 0))
                                        }}</text>

                                        <text v-if="item.out_refund_no"
                                            >单号：{{ item.out_refund_no }}</text
                                        >
                                    </view>

                                    <text v-if="item.refund_msg" class="refund-item__desc">
                                        {{ item.refund_msg }}
                                    </text>
                                </view>
                            </view>
                        </view>
                    </view>
                </view>
            </view>

            <ActionArea sticky safeBottom>
                <view class="action-bar">
                    <view class="action-bar__buttons">
                        <BaseButton
                            v-if="secondaryVisibleAction"
                            block
                            variant="secondary"
                            size="lg"
                            :style="secondaryVisibleAction.style"
                            @click="secondaryVisibleAction.onClick"
                        >
                            {{ secondaryVisibleAction.label }}
                        </BaseButton>

                        <BaseButton
                            v-if="primaryVisibleAction"
                            block
                            variant="primary"
                            size="lg"
                            :style="primaryVisibleAction.style"
                            @click="primaryVisibleAction.onClick"
                        >
                            {{ primaryVisibleAction.label }}
                        </BaseButton>
                    </view>

                    <view
                        v-if="moreActionItems.length"
                        class="action-bar__more"
                        @click="openMoreActions"
                    >
                        <text class="action-bar__more-text">更多操作</text>
                    </view>
                </view>
            </ActionArea>

            <BaseOverlayMask :show="showRefundPopup" @close="showRefundPopup = false" />

            <tn-popup
                v-model="showRefundPopup"
                open-direction="bottom"
                :radius="32"
                safe-area-inset-bottom
                :overlay="false"
                :overlay-closeable="true"
            >
                <view class="popup">
                    <view class="popup__header"
                        ><text class="popup__title">申请退款</text
                        ><tn-icon
                            name="close"
                            size="40"
                            color="#999999"
                            @click="showRefundPopup = false"
                    /></view>

                    <view class="popup__content">
                        <view class="form-item"
                            ><text class="form-item__label">退款金额</text>

                            <view class="refund-amount-card">
                                <text class="refund-amount-card__value"
                                    >¥{{ formatAmount(refundApplyAmount) }}</text
                                >

                                <text class="refund-amount-card__tip"
                                    >按当前剩余可退金额提交，提交后不可修改</text
                                >
                            </view>
                        </view>

                        <view class="form-item"
                            ><text class="form-item__label">退款原因</text
                            ><tn-input
                                v-model="refundForm.reason"
                                type="textarea"
                                placeholder="请输入退款原因"
                                :maxlength="200"
                                border
                                height="200"
                        /></view>
                    </view>

                    <view class="popup__actions">
                        <view class="popup__action">
                            <BaseButton
                                block
                                variant="secondary"
                                size="lg"
                                @click="showRefundPopup = false"
                            >
                                取消
                            </BaseButton>
                        </view>

                        <view class="popup__action">
                            <BaseButton block variant="primary" size="lg" @click="submitRefund">
                                提交申请
                            </BaseButton>
                        </view>
                    </view>
                </view>
            </tn-popup>

            <BaseOverlayMask :show="showVoucherPopup" @close="showVoucherPopup = false" />

            <tn-popup
                v-model="showVoucherPopup"
                open-direction="bottom"
                :radius="32"
                safe-area-inset-bottom
                :overlay="false"
                :overlay-closeable="true"
            >
                <view class="popup">
                    <view class="popup__header"
                        ><text class="popup__title">上传支付凭证</text
                        ><tn-icon
                            name="close"
                            size="40"
                            color="#999999"
                            @click="showVoucherPopup = false"
                    /></view>

                    <view class="popup__content">
                        <view class="form-item"
                            ><text class="form-item__label">凭证图片</text
                            ><text class="form-item__tip">请上传转账截图或付款凭证</text></view
                        >

                        <view class="voucher-upload">
                            <view v-if="voucherForm.image" class="voucher-upload__preview">
                                <image :src="voucherForm.image" mode="aspectFill" />

                                <view class="voucher-upload__remove" @click="voucherForm.image = ''"
                                    ><tn-icon name="close" size="32" color="#FFFFFF"
                                /></view>
                            </view>

                            <view v-else class="voucher-upload__add" @click="chooseVoucherImage">
                                <tn-icon name="add" size="64" color="#CBD5E1" />

                                <text class="voucher-upload__text">选择图片</text>

                                <text class="voucher-upload__tip">支持 jpg、png 格式</text>
                            </view>
                        </view>
                    </view>

                    <view class="popup__actions">
                        <view class="popup__action">
                            <BaseButton
                                block
                                variant="secondary"
                                size="lg"
                                @click="showVoucherPopup = false"
                            >
                                取消
                            </BaseButton>
                        </view>

                        <view class="popup__action">
                            <BaseButton block variant="primary" size="lg" @click="submitVoucher">
                                {{ voucherForm.uploading ? '上传中...' : '提交审核' }}
                            </BaseButton>
                        </view>
                    </view>
                </view>
            </tn-popup>

            <payment
                v-model:show="payState.showPay"
                v-model:show-check="payState.showCheck"
                :order-id="orderId"
                :from="payState.from"
                :redirect="payState.redirect"
                :payment-sn="payState.paymentSn"
                @success="handlePaySuccess"
                @fail="handlePayFail"
            />

            <view class="safe-bottom"></view>
        </view>

        <view v-else class="loading-container">
            <tn-loading mode="circle" />

            <text class="loading-text">加载中...</text>
        </view>
    </PageShell>
</template>

<script setup lang="ts">
import { computed, reactive, ref } from 'vue'

import { onHide, onLoad, onShow, onUnload } from '@dcloudio/uni-app'

import PageShell from '@/components/base/PageShell.vue'

import BaseNavbar from '@/components/base/BaseNavbar.vue'

import ActionArea from '@/components/base/ActionArea.vue'

import BaseButton from '@/components/base/BaseButton.vue'

import { ClientEnum } from '@/enums/appEnums'

import { useThemeStore } from '@/stores/theme'

import {
    applyRefund,
    cancelOrder,
    confirmOrder,
    deleteOrder,
    getOrderConfirmLetterById,
    getOrderConfirmLetterCurrent,
    getOrderConfirmLetterHistory,
    getOrderDetail,
    uploadPayVoucher
} from '@/api/order'

import { uploadImage } from '@/api/app'

import { buildOrderConfirmLetterDataUrl } from '@/utils/orderConfirmLetterRenderer'

import { client } from '@/utils/client'

import { subscribeAfterSaleScenes } from '@/utils/subscribe'

const $theme = useThemeStore()

const orderId = ref(0)

const order = ref<any>(null)

const confirmLetterId = ref(0)

const confirmLetter = ref<any>(null)

const confirmLetterHistory = ref<any[]>([])

const confirmLetterEntry = ref('')

const confirmLetterFallbackHintShown = ref(false)

const showRefundPopup = ref(false)

const showVoucherPopup = ref(false)

const payState = reactive({
    showPay: false,

    showCheck: false,

    from: 'order',

    redirect: '/pages/order_detail/order_detail',

    paymentSn: ''
})

const refundForm = reactive({ reason: '' })

const voucherForm = reactive({ image: '', uploading: false })

const showFinancialDetails = ref(false)

const payCountdownSeconds = ref(0)

const confirmCountdownSeconds = ref(0)

let payCountdownTimer: ReturnType<typeof setInterval> | null = null

let confirmCountdownTimer: ReturnType<typeof setInterval> | null = null

let payCountdownRefreshing = false

let confirmCountdownRefreshing = false

let detailRequestPromise: Promise<void> | null = null

let hasLoadedOnce = false

let hasBeenHidden = false

const CONFIRM_LETTER_NOTIFICATION_ENTRY = 'confirm_letter_notification'

const formatAmount = (value: any) => Number(value || 0).toFixed(2)

const confirmLetterAvailable = computed(() => !!confirmLetter.value?.letter_id)

const confirmLetterHistoryAvailable = computed(() => confirmLetterHistory.value.length > 0)

const refundApplyAmount = computed(() =>
    Number(order.value?.refund_apply_amount ?? order.value?.refundable_amount ?? 0)
)

const resolvePaymentChannel = (target: any) => {
    const paymentChannel = Number(target?.payment_channel || 0)

    if ([1, 2].includes(paymentChannel)) {
        return paymentChannel
    }

    return Number(target?.pay_type) === 4 || !!target?.pay_voucher ? 2 : 1
}

const formatCountdown = (seconds: number | string | undefined) => {
    const total = Math.max(Number(seconds || 0), 0)

    if (total <= 0) return '已超时，等待系统处理'

    const hours = Math.floor(total / 3600)

    const minutes = Math.floor((total % 3600) / 60)

    const remainSeconds = total % 60

    return [hours, minutes, remainSeconds].map((item) => String(item).padStart(2, '0')).join(':')
}

const getStatusTheme = (status: number) =>
    ((
        {
            0: {
                background: 'linear-gradient(180deg, #FFF5E8 0%, #FFFFFF 100%)',

                iconBg: '#C98524'
            },

            1: {
                background: 'linear-gradient(180deg, #FFF1EE 0%, #FFFFFF 100%)',

                iconBg: '#E85A4F'
            },

            2: {
                background: 'linear-gradient(180deg, #FFF5F1 0%, #FFFFFF 100%)',

                iconBg: '#E85A4F'
            },

            3: {
                background: 'linear-gradient(180deg, #EEF9F5 0%, #FFFFFF 100%)',

                iconBg: '#2F7D58'
            },

            4: {
                background: 'linear-gradient(180deg, #EEF9F5 0%, #FFFFFF 100%)',

                iconBg: '#2F7D58'
            },

            5: {
                background: 'linear-gradient(180deg, #EEF9F5 0%, #FFFFFF 100%)',

                iconBg: '#2F7D58'
            },

            6: {
                background: 'linear-gradient(180deg, #F7F3F1 0%, #FFFFFF 100%)',

                iconBg: '#7F7B78'
            },

            7: {
                background: 'linear-gradient(180deg, #FFF5E8 0%, #FFFFFF 100%)',

                iconBg: '#C98524'
            },

            10: {
                background: 'linear-gradient(180deg, #EEF9F5 0%, #FFFFFF 100%)',

                iconBg: '#0F766E'
            },

            8: {
                background: 'linear-gradient(180deg, #FDEEEE 0%, #FFFFFF 100%)',

                iconBg: '#B44A3A'
            }
        } as Record<number, { background: string; iconBg: string }>
    )[status] || {
        background: 'linear-gradient(180deg, #F7F3F1 0%, #FFFFFF 100%)',

        iconBg: '#7F7B78'
    })

const getRefundStatusStyle = (status: number) =>
    ((
        {
            0: { color: '#D97706', bg: 'rgba(217,119,6,0.1)' },

            1: { color: '#C99B73', bg: 'rgba(201,155,115,0.12)' },

            2: { color: '#0F766E', bg: 'rgba(15,118,110,0.1)' },

            3: { color: '#16A34A', bg: 'rgba(22,163,74,0.1)' },

            4: { color: '#DC2626', bg: 'rgba(220,38,38,0.1)' },

            5: { color: '#DC2626', bg: 'rgba(220,38,38,0.1)' }
        } as Record<number, { color: string; bg: string }>
    )[status] || { color: '#64748B', bg: 'rgba(100,116,139,0.1)' })

const getPayWayText = (payWay: number) => {
    const texts: Record<number, string> = {
        1: '微信支付',

        2: '支付宝',

        3: '余额支付',

        4: '线下支付'
    }

    return texts[payWay] || '未知方式'
}

const getRefundItemStatusText = (status: number) => {
    const texts: Record<number, string> = {
        0: '待执行',

        1: '处理中',

        2: '已完成',

        3: '失败'
    }

    return texts[status] || '未知状态'
}

const primaryItem = computed(() => {
    const items = Array.isArray(order.value?.items) ? order.value.items : []

    return items.find((item: any) => Number(item?.item_type || 1) === 1) || items[0] || null
})

const extraItems = computed(() => {
    const items = Array.isArray(order.value?.items) ? order.value.items : []

    return items.filter((item: any) => Number(item?.item_type || 1) !== 1)
})

const primaryStaffName = computed(
    () => primaryItem.value?.staff?.name || primaryItem.value?.staff_name || '待分配服务人员'
)

const primaryPackageName = computed(
    () => primaryItem.value?.package?.name || primaryItem.value?.package_name || '待确认主套餐'
)

const primaryPackageDescription = computed(() =>
    String(primaryItem.value?.package_description || '').trim()
)

const primaryServiceDate = computed(
    () =>
        primaryItem.value?.service_date ||
        primaryItem.value?.schedule_date ||
        order.value?.service_date ||
        '待确认服务日期'
)

const primaryServiceMetaRows = computed(() =>
    [
        { label: '服务人员', value: primaryStaffName.value || '待分配服务人员' },

        { label: '服务日期', value: primaryServiceDate.value || '待确认服务日期' },

        {
            label: '服务地点',

            value:
                [order.value?.service_region_text, order.value?.service_address]

                    .map((item: any) => String(item || '').trim())

                    .filter(Boolean)

                    .join(' · ') || '待确认服务地点'
        }
    ].filter((item) => String(item.value || '').trim() !== '')
)

const getItemQuantity = (item: any) => Math.max(Number(item?.quantity || 1), 1)

const getItemDisplayAmount = (item: any) => {
    const subtotal = Number(item?.subtotal)

    if (Number.isFinite(subtotal) && subtotal >= 0) {
        return subtotal
    }

    return Math.max(Number(item?.price || 0) * getItemQuantity(item), 0)
}

const getAddonDisplayAmount = (addon: any) => {
    const subtotal = Number(addon?.subtotal)

    if (Number.isFinite(subtotal) && subtotal >= 0) {
        return subtotal
    }

    return Math.max(Number(addon?.price || 0) * Math.max(Number(addon?.quantity || 1), 1), 0)
}

const orderServiceAmount = computed(() => {
    const serviceAmount = Number(order.value?.service_amount ?? -1)

    return serviceAmount >= 0 ? serviceAmount : Math.max(0, Number(order.value?.total_amount || 0))
})

const primaryServiceAmount = computed(() =>
    primaryItem.value ? getItemDisplayAmount(primaryItem.value) : orderServiceAmount.value
)

const buildAddonRowKey = (kind: string, title: string, amount: number, quantity = 1, extra = '') =>
    `${kind}:${title.trim()}:${formatAmount(amount)}:${quantity}:${extra}`

const getExtraItemTitle = (item: any) => {
    if (Number(item?.item_type || 1) === 2) {
        return item?.item_meta?.label || item?.package_name || '附加套餐'
    }

    if (Number(item?.item_type || 1) === 3) {
        const roleLabel = item?.item_meta?.role_label || '协作服务'

        const staffName = item?.staff?.name || item?.staff_name || ''

        return staffName ? `${roleLabel} · ${staffName}` : roleLabel
    }

    return item?.package_name || '服务项目'
}

const getExtraItemTypeText = (item: any) => {
    const itemType = Number(item?.item_type || 1)

    if (itemType === 2) return '附加套餐'

    if (itemType === 3) return '协作服务'

    return item?.item_type_desc || '服务项目'
}

const getExtraItemDescription = (item: any) => {
    const parts: string[] = []

    const description = String(item?.package_description || item?.package?.description || '').trim()

    if (description) {
        parts.push(description)
    }

    const quantity = getItemQuantity(item)

    if (quantity > 1) {
        parts.push(`数量 x${quantity}`)
    }

    return parts.join(' · ')
}

const serviceAddonRows = computed(() => {
    const rows: Array<{
        key: string

        title: string

        typeText: string

        description: string

        metaText: string

        priceText: string
    }> = []

    const seen = new Set<string>()

    const pushRow = (
        kind: string,

        title: string,

        amount: number,

        quantity: number,

        typeText: string,

        description = '',

        metaText = '',

        extra = ''
    ) => {
        const normalizedTitle = String(title || '').trim()

        if (!normalizedTitle) return

        const key = buildAddonRowKey(kind, normalizedTitle, amount, quantity, extra)

        if (seen.has(key)) return

        seen.add(key)

        rows.push({
            key,

            title: normalizedTitle,

            typeText,

            description: String(description || '').trim(),

            metaText: String(metaText || '').trim(),

            priceText: `¥${formatAmount(amount)}`
        })
    }

    ;(Array.isArray(order.value?.items) ? order.value.items : []).forEach((item: any) => {
        ;(item?.addons || []).forEach((addon: any) => {
            const quantity = Math.max(Number(addon?.quantity || 1), 1)

            pushRow(
                'addon',

                addon?.addon_name || addon?.name || '附加套餐',

                getAddonDisplayAmount(addon),

                quantity,

                '附加套餐',

                '',

                `数量 x${quantity}`
            )
        })
    })

    extraItems.value

        .filter((item: any) => Number(item?.item_type || 1) === 2)

        .forEach((item: any) => {
            const quantity = getItemQuantity(item)

            pushRow(
                'addon',

                getExtraItemTitle(item),

                getItemDisplayAmount(item),

                quantity,

                getExtraItemTypeText(item),

                getExtraItemDescription(item),

                [item?.service_date, `数量 x${quantity}`].filter(Boolean).join(' · '),

                item?.service_date || ''
            )
        })

    return rows
})

const serviceAddonSummaryText = computed(() => `共 ${serviceAddonRows.value.length} 项`)

const serviceRelatedRows = computed(() =>
    extraItems.value

        .filter((item: any) => Number(item?.item_type || 1) === 3)

        .map((item: any) => {
            const quantity = getItemQuantity(item)

            return {
                key: buildAddonRowKey(
                    'related',

                    getExtraItemTitle(item),

                    getItemDisplayAmount(item),

                    quantity,

                    item?.service_date || ''
                ),

                title: getExtraItemTitle(item),

                typeText: getExtraItemTypeText(item),

                description: getExtraItemDescription(item),

                metaText: [item?.service_date, `数量 x${quantity}`].filter(Boolean).join(' · '),

                priceText: `¥${formatAmount(getItemDisplayAmount(item))}`
            }
        })
)

const serviceCardTitle = computed(() => {
    return String(primaryPackageName.value || '').trim() || '婚礼服务订单'
})

const paymentChannel = computed(() => resolvePaymentChannel(order.value))

const paymentChannelDesc = computed(
    () =>
        String(order.value?.payment_channel_desc || '').trim() ||
        (paymentChannel.value === 2 ? '线下支付' : '线上支付')
)

const needPayAmount = computed(() => {
    if (!order.value) return 0

    return Number(order.value.need_pay_amount || 0)
})

const needPayMetaLabel = computed(() => {
    if (!order.value) return '当前待支付'

    const status = Number(order.value.order_status || 0)

    if (
        [2, 3].includes(status) &&
        String(order.value.current_pay_stage || '').trim() === 'balance_after_service'
    ) {
        return '预计尾款'
    }

    return '当前待支付'
})

const showNeedPayMeta = computed(() => {
    if (!order.value || Number(needPayAmount.value) <= 0) return false

    const status = Number(order.value.order_status || 0)

    if (status === 1) return true

    return (
        [2, 3].includes(status) &&
        String(order.value.current_pay_stage || '').trim() === 'balance_after_service'
    )
})

const showOfflineVoucherCard = computed(
    () => !!order.value && (paymentChannel.value === 2 || !!order.value?.pay_voucher)
)

const showVoucherPending = computed(
    () =>
        !!order.value && paymentChannel.value === 2 && Number(order.value.pay_voucher_status) === 0
)

const showConfirmCountdown = computed(
    () =>
        !!order.value &&
        Number(order.value.order_status) === 0 &&
        Number(order.value.confirm_deadline_time || 0) > 0
)

const confirmCountdownText = computed(() =>
    showConfirmCountdown.value ? formatCountdown(confirmCountdownSeconds.value) : '-'
)

const confirmTimeoutActionText = computed(() =>
    String(order.value?.confirm_timeout_action_desc || '').trim()
)

const showConfirmTimeoutAction = computed(
    () =>
        !!order.value && Number(order.value.order_status) === 0 && !!confirmTimeoutActionText.value
)

const showPayCountdown = computed(() => !!order.value && payCountdownSeconds.value > 0)

const payCountdownText = computed(() => formatCountdown(payCountdownSeconds.value))

const payTimeoutActionText = computed(() =>
    String(order.value?.pay_timeout_action_desc || '').trim()
)

const showPayTimeoutAction = computed(
    () => !!order.value && Number(order.value.order_status) === 1 && !!payTimeoutActionText.value
)

const canPayOnline = computed(
    () =>
        !!order.value &&
        Number(order.value.order_status) === 1 &&
        Number(order.value.need_pay_amount || 0) > 0 &&
        paymentChannel.value === 1
)

const canUploadVoucher = computed(
    () =>
        !!order.value &&
        Number(order.value.order_status) === 1 &&
        paymentChannel.value === 2 &&
        Number(order.value.pay_voucher_status) !== 0
)

const statusTheme = computed(() => getStatusTheme(Number(order.value?.order_status ?? 6)))

const statusDescription = computed(
    () =>
        ((
            {
                0: showConfirmCountdown.value
                    ? `请在 ${confirmCountdownText.value} 内等待服务人员确认。${
                          confirmTimeoutActionText.value
                              ? `超时后将${confirmTimeoutActionText.value}。`
                              : ''
                      }`
                    : '确认后进入支付流程。',

                1: showVoucherPending.value
                    ? showPayCountdown.value
                        ? `线下支付凭证审核中。若在 ${payCountdownText.value} 内仍未处理，系统将${
                              payTimeoutActionText.value || '自动关闭当前支付阶段'
                          }。`
                        : '线下支付凭证审核中。'
                    : paymentChannel.value === 2
                    ? showPayCountdown.value
                        ? `请在 ${payCountdownText.value} 内完成线下付款并上传凭证。${
                              payTimeoutActionText.value
                                  ? `超时后将${payTimeoutActionText.value}。`
                                  : ''
                          }`
                        : '该订单需线下付款，请上传凭证后等待审核。'
                    : showPayCountdown.value
                    ? `请在 ${payCountdownText.value} 内完成支付。${
                          payTimeoutActionText.value
                              ? `超时后将${payTimeoutActionText.value}。`
                              : ''
                      }`
                    : '请尽快完成支付。',

                2: '订单已进入待服务。',

                3:
                    Number(order.value?.can_user_complete || 0) === 1
                        ? '服务进行中，完成后可确认。'
                        : '服务进行中，请留意后续状态。',

                4: '本次服务已完成。',

                5: '订单已评价。',

                6: '订单已取消。',

                7: '订单当前处于暂停状态。',

                10: '退款申请处理中。',

                8: '订单已退款。'
            } as Record<number, string>
        )[Number(order.value?.order_status ?? -1)] || '订单状态已更新。')
)

const statusHeadline = computed(() => {
    const orderStatus = Number(order.value?.order_status ?? -1)

    const serviceName = String(primaryPackageName.value || '服务').trim()

    const headlines: Record<number, string> = {
        0: `${serviceName}订单待确认`,

        1: `${serviceName}订单待支付`,

        2: `${serviceName}订单待服务`,

        3: `${serviceName}服务进行中`,

        4: `${serviceName}订单已完成`,

        5: `${serviceName}订单已评价`,

        6: `${serviceName}订单已取消`,

        7: `${serviceName}订单已暂停`,

        10: `${serviceName}退款处理中`,

        8: `${serviceName}订单已退款`
    }

    return headlines[orderStatus] || `${serviceName}订单状态已更新`
})

const statusCardText = computed(
    () => `订单编号：${order.value?.order_sn || '-'}\n${statusDescription.value}`
)

const statusGuideRows = computed(() => [
    {
        label: '当前阶段',

        value: String(order.value?.status_summary || statusDescription.value || '订单状态已更新'),

        multiline: true
    },

    {
        label: '等待对象',

        value: String(order.value?.waiting_for || '等待平台同步'),

        multiline: false
    },

    {
        label: '下一步',

        value: String(order.value?.next_action_text || '进入订单详情查看最新安排'),

        multiline: true
    }
])

const totalOrderAmount = computed(() =>
    Math.max(Number(order.value?.total_amount || 0), Number(order.value?.pay_amount || 0))
)

const paidAmount = computed(() => {
    if (!order.value) return 0

    const depositAmount = Number(order.value.deposit_amount || 0)

    const balanceAmount = Number(order.value.balance_amount || 0)

    if (depositAmount > 0 || balanceAmount > 0) {
        return (
            (order.value.deposit_paid ? depositAmount : 0) +
            (order.value.balance_paid ? balanceAmount : 0)
        )
    }

    return [2, 3, 4, 5, 8, 10].includes(Number(order.value.order_status || 0))
        ? Number(order.value.pay_amount || 0)
        : 0
})

const pendingAmount = computed(() => {
    if (Number(order.value?.order_status || 0) === 1 && Number(needPayAmount.value) > 0) {
        return Number(needPayAmount.value)
    }

    return Math.max(totalOrderAmount.value - paidAmount.value, 0)
})

const paidAmountText = computed(() => `¥${formatAmount(paidAmount.value)}`)

const pendingAmountText = computed(() => `¥${formatAmount(pendingAmount.value)}`)

const hasFinancialDetails = computed(
    () =>
        Number(order.value?.addon_amount || 0) > 0 ||
        Number(order.value?.discount_amount || 0) > 0 ||
        Number(order.value?.deposit_amount || 0) > 0 ||
        Number(order.value?.balance_amount || 0) > 0
)

const paymentProgressText = computed(() => {
    if (!order.value) return '待开始'

    if (showVoucherPending.value) return '凭证审核中'

    if (paymentChannel.value === 2 && Number(order.value.order_status || 0) === 1) {
        return order.value.need_pay === 'balance'
            ? '待上传尾款凭证'
            : order.value.need_pay === 'deposit'
            ? '待上传首笔凭证'
            : '待上传线下凭证'
    }

    if (canPayOnline.value)
        return order.value.need_pay === 'balance'
            ? '待支付尾款'
            : order.value.need_pay === 'deposit'
            ? '待支付定金'
            : '待支付'

    if (
        Number(order.value.deposit_amount || 0) > 0 ||
        Number(order.value.balance_amount || 0) > 0
    ) {
        if (order.value.deposit_paid && order.value.balance_paid) return '已完成'

        if (order.value.deposit_paid) return '定金已付'
    }

    if (Number(order.value.order_status || 0) === 10) return '退款处理中'

    if ([2, 3, 4, 5, 8].includes(Number(order.value.order_status || 0))) return '已完成支付'

    return '待开始'
})

const progressItems = computed(() => [
    {
        label: '1. 档期确认',

        value:
            Number(order.value?.order_status || 0) >= 1
                ? '已完成'
                : showConfirmCountdown.value
                ? `${confirmCountdownText.value}${
                      confirmTimeoutActionText.value
                          ? `，超时后${confirmTimeoutActionText.value}`
                          : ''
                  }`
                : '待确认'
    },

    {
        label: '2. 支付进度',

        value:
            showPayCountdown.value && payTimeoutActionText.value
                ? `${paymentProgressText.value}，剩余 ${payCountdownText.value}，超时后${payTimeoutActionText.value}`
                : showPayCountdown.value
                ? `${paymentProgressText.value}，剩余 ${payCountdownText.value}`
                : paymentProgressText.value
    },

    {
        label: '3. 婚礼执行',

        value:
            Number(order.value?.order_status || 0) === 10
                ? '退款处理中'
                : Number(order.value?.order_status || 0) === 8
                ? '已结束'
                : primaryServiceDate.value || '待安排'
    },

    {
        label: '4. 尾款结清',

        value:
            Number(order.value?.order_status || 0) === 10
                ? '退款处理中'
                : Number(order.value?.order_status || 0) === 8
                ? '已退款'
                : Number(order.value?.balance_amount || 0) > 0
                ? order.value?.balance_paid
                    ? '已完成'
                    : '婚礼结束后结清'
                : '无尾款'
    }
])

const contactPrimaryText = computed(() => {
    const contactName = String(order.value?.contact_name || '').trim() || primaryStaffName.value

    return `联系人：${contactName || '待确认'}`
})

const contactSecondaryText = computed(
    () => `手机号码：${String(order.value?.contact_mobile || '-').trim() || '-'}`
)

const contactTertiaryText = computed(() =>
    [order.value?.service_region_text, order.value?.service_address, order.value?.user_remark]

        .map((item: any) => String(item || '').trim())

        .filter(Boolean)

        .join(' · ')
)

const primaryVisibleAction = computed(() => {
    if (!order.value) return null

    const baseStyle = {
        background: `linear-gradient(135deg, ${$theme.primaryColor} 0%, ${$theme.primaryColor} 100%)`,

        color: $theme.btnColor
    }

    if (canPayOnline.value) {
        return {
            key: 'pay',

            label: order.value?.need_pay_label || '立即支付',

            style: baseStyle,

            onClick: handlePay
        }
    }

    if (
        Number(order.value.order_status) === 3 &&
        Number(order.value?.can_user_complete || 0) === 1
    ) {
        return {
            key: 'confirm',

            label: '确认完成',

            style: baseStyle,

            onClick: handleConfirm
        }
    }

    if (canUploadVoucher.value) {
        return {
            key: 'voucher',

            label: '上传凭证',

            style: baseStyle,

            onClick: () => {
                showVoucherPopup.value = true
            }
        }
    }

    return null
})

const canApplyRefund = computed(() => {
    return !!Number(order.value?.can_user_refund || 0)
})

const secondaryVisibleAction = computed(() => {
    if (!order.value) return null

    return {
        key: 'contact',

        label: '联系顾问',

        style: {
            borderColor: 'var(--wm-color-border, #EFE6E1)',

            color: 'var(--wm-text-primary, #1E2432)'
        },

        onClick: handleContactAdvisor
    }
})

const moreActionItems = computed(() => {
    if (!order.value) return []

    const status = Number(order.value.order_status || -1)

    const items: Array<{ label: string; onClick: () => void }> = []

    if ([2, 3].includes(status)) {
        items.push({ label: '申请变更', onClick: openChangeActions })
    }

    if ([0, 1].includes(status)) {
        items.push({ label: '取消订单', onClick: handleCancel })
    }

    if (canUploadVoucher.value && primaryVisibleAction.value?.key !== 'voucher') {
        items.push({
            label: '上传凭证',

            onClick: () => {
                showVoucherPopup.value = true
            }
        })
    }

    if (canApplyRefund.value) {
        items.push({
            label: '申请退款',

            onClick: () => {
                showRefundPopup.value = true
            }
        })
    }

    if ([4, 5, 6, 8].includes(status)) {
        items.push({ label: '删除订单', onClick: handleDelete })
    }

    return items
})

const openMoreActions = () => {
    if (!moreActionItems.value.length) return

    uni.showActionSheet({
        itemList: moreActionItems.value.map((item) => item.label),

        success: ({ tapIndex }) => {
            moreActionItems.value[tapIndex]?.onClick()
        }
    })
}

const clearPayCountdown = () => {
    if (payCountdownTimer) {
        clearInterval(payCountdownTimer)

        payCountdownTimer = null
    }
}

const clearConfirmCountdown = () => {
    if (confirmCountdownTimer) {
        clearInterval(confirmCountdownTimer)

        confirmCountdownTimer = null
    }
}

const syncPayCountdown = (seconds: number | string) => {
    clearPayCountdown()

    payCountdownSeconds.value = Math.max(Number(seconds || 0), 0)

    if (payCountdownSeconds.value <= 0) return

    payCountdownTimer = setInterval(async () => {
        if (payCountdownSeconds.value > 0) {
            payCountdownSeconds.value -= 1
        }

        if (payCountdownSeconds.value <= 0) {
            clearPayCountdown()

            if (payCountdownRefreshing) return

            payCountdownRefreshing = true

            try {
                await fetchDetail()
            } finally {
                payCountdownRefreshing = false
            }
        }
    }, 1000)
}

const syncConfirmCountdown = (seconds: number | string) => {
    clearConfirmCountdown()

    confirmCountdownSeconds.value = Math.max(Number(seconds || 0), 0)

    if (!showConfirmCountdown.value || confirmCountdownSeconds.value <= 0) return

    confirmCountdownTimer = setInterval(async () => {
        if (confirmCountdownSeconds.value > 0) {
            confirmCountdownSeconds.value -= 1
        }

        if (confirmCountdownSeconds.value <= 0) {
            clearConfirmCountdown()

            if (confirmCountdownRefreshing) return

            confirmCountdownRefreshing = true

            try {
                await fetchDetail()
            } finally {
                confirmCountdownRefreshing = false
            }
        }
    }, 1000)
}

const isConfirmLetterNotificationEntry = () =>
    confirmLetterEntry.value === CONFIRM_LETTER_NOTIFICATION_ENTRY

const showConfirmLetterFallbackHint = (message: string) => {
    if (confirmLetterFallbackHintShown.value) {
        return
    }

    confirmLetterFallbackHintShown.value = true
    uni.showToast({ title: message, icon: 'none' })
}

const handleMissingNotificationConfirmLetter = async (message?: string) => {
    if (!isConfirmLetterNotificationEntry()) {
        uni.showToast({ title: message || '加载确认函失败', icon: 'none' })
        return
    }

    await uni.showModal({
        title: '确认函已更新',
        content:
            message ||
            '这条通知对应的确认函版本已更新，当前通知未携带订单信息，暂时无法自动跳转。请前往“我的订单”查看当前有效确认函。',
        showCancel: false,
        confirmText: '查看订单'
    })

    uni.reLaunch({ url: '/pages/order/order' })
}

const fetchConfirmLetter = async () => {
    if (confirmLetterId.value > 0) {
        try {
            confirmLetter.value = await getOrderConfirmLetterById({
                letter_id: confirmLetterId.value
            })
        } catch {
            confirmLetter.value = null

            if (orderId.value > 0) {
                try {
                    confirmLetter.value = await getOrderConfirmLetterCurrent({ id: orderId.value })
                    if (confirmLetter.value?.letter_id && isConfirmLetterNotificationEntry()) {
                        showConfirmLetterFallbackHint('确认函版本已更新，已切换到当前有效版本')
                    }
                } catch {
                    confirmLetter.value = null
                }
            }
        }

        return
    }

    if (orderId.value <= 0) {
        confirmLetter.value = null

        return
    }

    try {
        confirmLetter.value = await getOrderConfirmLetterCurrent({ id: orderId.value })
    } catch {
        confirmLetter.value = null
    }
}

const fetchConfirmLetterHistory = async () => {
    if (orderId.value <= 0) {
        confirmLetterHistory.value = []

        return
    }

    try {
        const history = await getOrderConfirmLetterHistory({ id: orderId.value })

        confirmLetterHistory.value = Array.isArray(history) ? history : []
    } catch {
        confirmLetterHistory.value = []
    }
}

const fetchDetail = async () => {
    if (orderId.value <= 0) return

    if (detailRequestPromise) return detailRequestPromise

    detailRequestPromise = (async () => {
        try {
            order.value = await getOrderDetail({ id: orderId.value })

            await fetchConfirmLetter()

            if (
                isConfirmLetterNotificationEntry() &&
                confirmLetterId.value > 0 &&
                !confirmLetter.value?.letter_id
            ) {
                showConfirmLetterFallbackHint('当前暂无可查看确认函，请在订单详情查看最新状态')
            }

            await fetchConfirmLetterHistory()

            syncPayCountdown(order.value?.pay_remain_seconds || 0)

            syncConfirmCountdown(order.value?.confirm_remain_seconds || 0)

            hasLoadedOnce = true
        } catch (e: any) {
            confirmLetter.value = null

            confirmLetterHistory.value = []

            clearPayCountdown()

            clearConfirmCountdown()

            uni.showToast({ title: e?.message || '加载失败', icon: 'none' })
        } finally {
            detailRequestPromise = null
        }
    })()

    return detailRequestPromise
}

const handleOpenConfirmLetter = () => {
    if (!confirmLetter.value?.letter_id) {
        uni.showToast({ title: '订单确认函暂未生成', icon: 'none' })

        return
    }

    const imageUrl =
        String(confirmLetter.value?.full_image_url || '').trim() ||
        buildOrderConfirmLetterDataUrl(confirmLetter.value?.rendered_snapshot || ({} as any), {
            renderSpecVersion: confirmLetter.value?.render_spec_version,
        })

    if (!imageUrl) {
        uni.showToast({ title: '订单确认函暂不可查看', icon: 'none' })

        return
    }

    uni.previewImage({
        urls: [imageUrl],

        current: imageUrl
    })
}

const handleOpenConfirmLetterHistory = () => {
    if (!confirmLetterHistory.value.length) {
        uni.showToast({ title: '暂无确认函版本记录', icon: 'none' })

        return
    }

    uni.showActionSheet({
        itemList: confirmLetterHistory.value.map((item) => {
            const tags = [
                item?.is_current ? '当前' : '',

                item?.is_pushed ? '已推送' : '未推送',

                item?.can_view ? '' : '仅保留记录'
            ].filter(Boolean)

            return `v${item?.version || 0}${tags.length ? `（${tags.join('·')}）` : ''}`
        }),

        success: async ({ tapIndex }) => {
            const target = confirmLetterHistory.value[tapIndex]

            if (!target) {
                return
            }

            if (!Number(target?.can_view || 0)) {
                uni.showToast({
                    title: '历史版本仅保留记录，当前仅支持查看有效版本',

                    icon: 'none'
                })

                return
            }

            try {
                confirmLetter.value = await getOrderConfirmLetterById({
                    letter_id: Number(target.letter_id || 0)
                })

                handleOpenConfirmLetter()
            } catch (error: any) {
                uni.showToast({ title: error?.message || '加载确认函失败', icon: 'none' })
            }
        }
    })
}

const copyOrderSn = () => {
    if (!order.value?.order_sn) return

    uni.setClipboardData({
        data: order.value.order_sn,

        success: () => uni.showToast({ title: '已复制订单编号', icon: 'success' })
    })
}

const handleContactAdvisor = () =>
    uni.navigateTo({
        url: `/packages/pages/customer_service/customer_service?scene=order_detail&order_id=${orderId.value}`
    })

const openChangeActions = () =>
    uni.showActionSheet({
        itemList: ['申请改期', '申请暂停', '申请加项', '我的申请'],

        success: ({ tapIndex }) => {
            const routes = [
                `/packages/pages/order_change/apply_date?order_id=${orderId.value}`,

                `/packages/pages/order_change/apply_pause?order_id=${orderId.value}`,

                `/packages/pages/order_change/apply_add_item?order_id=${orderId.value}`,

                '/packages/pages/order_change/list?type=change'
            ]

            const url = routes[tapIndex]

            if (url) uni.navigateTo({ url })
        }
    })

const handlePay = () => {
    if (paymentChannel.value !== 1) {
        uni.showToast({ title: '该订单需线下付款，请上传支付凭证', icon: 'none' })

        return
    }

    if (Number(order.value?.pay_deadline_time || 0) > 0 && payCountdownSeconds.value <= 0) {
        uni.showToast({ title: '支付时间已到，正在刷新订单', icon: 'none' })

        fetchDetail()

        return
    }

    payState.paymentSn = ''

    payState.showPay = true
}

const handlePaySuccess = async (payload?: { paymentSn?: string }) => {
    payState.showPay = false

    payState.showCheck = false

    const paymentSn = String(payload?.paymentSn || payState.paymentSn || '')

    payState.paymentSn = ''

    uni.navigateTo({
        url: `/pages/payment_result/payment_result?id=${orderId.value}&from=${payState.from}${
            paymentSn ? `&payment_sn=${paymentSn}` : ''
        }`
    })
}

const handlePayFail = async (payload?: { reason?: string; message?: string }) => {
    if (payload?.reason === 'timeout') {
        await fetchDetail()

        return
    }

    uni.showToast({ title: payload?.message || '支付失败，请重试', icon: 'none' })
}

const handleCancel = async () => {
    const res = await uni.showModal({ title: '提示', content: '确定要取消该订单吗？' })

    if (!res.confirm) return

    try {
        await cancelOrder({ id: orderId.value, reason: '用户取消' })

        uni.showToast({ title: '订单已取消', icon: 'success' })

        await fetchDetail()
    } catch (e: any) {
        uni.showToast({ title: e?.message || '操作失败', icon: 'none' })
    }
}

const handleConfirm = async () => {
    const res = await uni.showModal({ title: '提示', content: '确定服务已完成吗？' })

    if (!res.confirm) return

    try {
        await confirmOrder({ id: orderId.value })

        await fetchDetail()

        const successText =
            Number(order.value?.order_status || 0) === 1 ? '服务已完成，待支付尾款' : '订单已完成'

        uni.showToast({ title: successText, icon: 'success' })
    } catch (e: any) {
        uni.showToast({ title: e?.message || '操作失败', icon: 'none' })
    }
}

const handleDelete = async () => {
    const res = await uni.showModal({ title: '提示', content: '确定要删除该订单吗？' })

    if (!res.confirm) return

    try {
        await deleteOrder({ id: orderId.value })

        uni.showToast({ title: '删除成功', icon: 'success' })

        setTimeout(() => uni.navigateBack(), 1500)
    } catch (e: any) {
        uni.showToast({ title: e?.message || '操作失败', icon: 'none' })
    }
}

const promptAfterSaleSubscribe = async () => {
    if (client !== ClientEnum.MP_WEIXIN) {
        return true
    }

    const result = await uni.showModal({
        title: '接收售后处理提醒',

        content: '订阅后可接收处理提醒。',

        confirmText: '去订阅',

        cancelText: '暂不订阅'
    })

    if (!result.confirm) {
        return false
    }

    try {
        await subscribeAfterSaleScenes()
    } catch (error) {
        console.error('请求售后订阅失败', error)
    }

    return true
}

const submitRefund = async () => {
    if (!canApplyRefund.value || refundApplyAmount.value <= 0) {
        return uni.showToast({ title: '当前订单暂不支持申请退款', icon: 'none' })
    }

    if (!refundForm.reason.trim()) return uni.showToast({ title: '请输入退款原因', icon: 'none' })

    try {
        await promptAfterSaleSubscribe()

        await applyRefund({
            id: orderId.value,

            reason: refundForm.reason
        })

        uni.showToast({ title: '申请已提交', icon: 'success' })

        showRefundPopup.value = false

        refundForm.reason = ''

        await fetchDetail()
    } catch (e: any) {
        uni.showToast({ title: e?.message || '申请失败', icon: 'none' })
    }
}

const chooseVoucherImage = () => {
    if (voucherForm.uploading) return

    uni.chooseImage({
        count: 1,

        sizeType: ['compressed'],

        sourceType: ['album', 'camera'],

        success: async (res) => {
            const path = res.tempFilePaths?.[0]

            if (!path) return

            try {
                voucherForm.uploading = true

                const uploadRes: any = await uploadImage(path)

                if (uploadRes?.uri) voucherForm.image = uploadRes.uri
                else uni.showToast({ title: '上传失败，请重试', icon: 'none' })
            } catch (e: any) {
                uni.showToast({ title: e?.message || '上传失败', icon: 'none' })
            } finally {
                voucherForm.uploading = false
            }
        }
    })
}

const submitVoucher = async () => {
    if (voucherForm.uploading) return

    if (paymentChannel.value !== 2 || !canUploadVoucher.value) {
        uni.showToast({ title: '当前订单暂不支持上传凭证', icon: 'none' })

        return
    }

    if (!voucherForm.image) return uni.showToast({ title: '请先选择凭证图片', icon: 'none' })

    try {
        await uploadPayVoucher({ id: orderId.value, voucher: voucherForm.image })

        uni.showToast({ title: '凭证已提交', icon: 'success' })

        showVoucherPopup.value = false

        voucherForm.image = ''

        await fetchDetail()
    } catch (e: any) {
        uni.showToast({ title: e?.message || '提交失败', icon: 'none' })
    }
}

onLoad(async (options: any) => {
    $theme.setScene('consumer')

    hasLoadedOnce = false

    hasBeenHidden = false

    detailRequestPromise = null

    confirmLetterFallbackHintShown.value = false

    confirmLetterEntry.value = String(options?.entry || '').trim()

    orderId.value = Number(options?.id || 0)

    confirmLetterId.value = Number(options?.letter_id || 0)

    if (options?.payment_sn) payState.paymentSn = String(options.payment_sn)

    if (options?.checkPay) payState.showCheck = true

    if (orderId.value <= 0 && confirmLetterId.value > 0) {
        try {
            const letter = await getOrderConfirmLetterById({ letter_id: confirmLetterId.value })

            confirmLetter.value = letter || null

            orderId.value = Number(letter?.order_id || 0)
        } catch (e: any) {
            confirmLetter.value = null

            await handleMissingNotificationConfirmLetter(
                e?.message || '这条通知对应的确认函版本已更新，请前往“我的订单”查看当前有效确认函。'
            )

            return
        }
    }

    if (orderId.value > 0) {
        void fetchDetail()
    }
})

onShow(() => {
    $theme.setScene('consumer')

    if (!hasLoadedOnce || !hasBeenHidden || orderId.value <= 0) {
        return
    }

    hasBeenHidden = false

    void fetchDetail()
})

onHide(() => {
    hasBeenHidden = hasLoadedOnce

    clearPayCountdown()

    clearConfirmCountdown()
})

onUnload(() => {
    detailRequestPromise = null

    hasLoadedOnce = false

    hasBeenHidden = false

    clearPayCountdown()

    clearConfirmCountdown()
})
</script>

<style lang="scss" scoped>
.order-detail {
    padding-bottom: var(--wm-safe-bottom-action, calc(env(safe-area-inset-bottom) + 150rpx));

    background: var(--wm-color-page, #fcfbf9);
}

.page-body {
    padding: 22rpx 37rpx 37rpx;

    display: flex;

    flex-direction: column;

    gap: 30rpx;
}

.status-card {
    display: flex;

    flex-direction: column;

    gap: 16rpx;

    padding: 34rpx 34rpx 37rpx;

    border-radius: 45rpx;

    border: 1rpx solid var(--wm-color-border-strong, #f4c7bf);

    box-shadow: 0 14rpx 32rpx rgba(214, 185, 167, 0.12);
}

.status-card__chip {
    align-self: flex-start;

    min-height: 48rpx;

    padding: 0 24rpx;

    border-radius: 999rpx;

    display: inline-flex;

    align-items: center;

    justify-content: center;
}

.status-card__chip-text {
    font-size: 24rpx;

    font-weight: 700;

    line-height: 1;

    color: #ffffff;
}

.status-card__title {
    font-size: 44rpx;

    font-weight: 700;

    line-height: 1.35;

    color: var(--wm-text-primary, #1e2432);
}

.status-card__desc {
    font-size: 24rpx;

    font-weight: 500;

    line-height: 1.65;

    color: var(--wm-text-secondary, #7f7b78);

    white-space: pre-line;
}

.status-card__meta {
    display: flex;

    flex-wrap: wrap;

    gap: 16rpx;
}

.status-card__meta-item {
    min-height: 52rpx;

    padding: 0 24rpx;

    border-radius: 999rpx;

    display: inline-flex;

    align-items: center;

    gap: 10rpx;

    background: rgba(255, 255, 255, 0.76);

    border: 1rpx solid rgba(244, 199, 191, 0.88);

    box-sizing: border-box;
}

.status-card__meta-label {
    font-size: 22rpx;

    color: var(--wm-text-secondary, #7f7b78);
}

.status-card__meta-value {
    font-size: 24rpx;

    font-weight: 700;

    color: var(--wm-color-primary, #e85a4f);
}

.card {
    display: flex;

    flex-direction: column;

    gap: 22rpx;

    padding: 34rpx 34rpx;

    border-radius: 45rpx;

    border: 1rpx solid var(--wm-color-border, #efe6e1);

    background: rgba(255, 255, 255, 0.86);

    box-shadow: 0 10rpx 24rpx rgba(214, 185, 167, 0.08);
}

.card--secondary {
    gap: 16rpx;
}

.card__title {
    font-size: 28rpx;

    font-weight: 700;

    line-height: 1.4;

    color: var(--wm-text-primary, #1e2432);
}

.card__title-row {
    display: flex;

    align-items: center;

    justify-content: space-between;

    gap: 16rpx;
}

.card__title-actions {
    display: inline-flex;

    align-items: center;

    gap: 20rpx;

    flex-wrap: wrap;

    justify-content: flex-end;
}

.card__main-text {
    font-size: 28rpx;

    font-weight: 600;

    line-height: 1.65;

    color: var(--wm-text-primary, #1e2432);
}

.card__sub-text {
    font-size: 26rpx;

    line-height: 1.7;

    color: var(--wm-text-primary, #1e2432);
}

.card__sub-text--muted {
    font-weight: 500;

    color: var(--wm-text-secondary, #7f7b78);
}

.service-summary {
    display: flex;

    flex-direction: column;

    gap: 18rpx;

    padding: 28rpx;

    border-radius: 34rpx;

    background: linear-gradient(180deg, rgba(255, 247, 244, 0.96) 0%, #ffffff 100%);

    border: 1rpx solid var(--wm-color-border, #efe6e1);
}

.service-summary__topbar {
    display: flex;

    align-items: center;

    justify-content: space-between;

    gap: 20rpx;
}

.service-summary__label {
    font-size: 22rpx;

    font-weight: 600;

    color: var(--wm-text-tertiary, #b4aca8);
}

.service-summary__title {
    font-size: 30rpx;

    font-weight: 700;

    line-height: 1.5;

    color: var(--wm-text-primary, #1e2432);
}

.service-summary__price {
    flex-shrink: 0;

    font-size: 30rpx;

    font-weight: 700;

    line-height: 1.4;

    color: var(--wm-color-primary, #e85a4f);
}

.service-summary__meta-grid {
    display: flex;

    flex-direction: column;

    gap: 14rpx;
}

.service-summary__meta-card {
    padding: 20rpx 22rpx;

    border-radius: 26rpx;

    border: 1rpx solid rgba(239, 230, 225, 0.92);

    background: rgba(255, 255, 255, 0.94);

    display: flex;

    flex-direction: column;

    gap: 6rpx;
}

.service-summary__meta-label {
    font-size: 22rpx;

    color: var(--wm-text-tertiary, #b4aca8);
}

.service-summary__meta-value {
    font-size: 24rpx;

    line-height: 1.6;

    color: var(--wm-text-primary, #1e2432);
}

.service-summary__desc {
    font-size: 24rpx;

    line-height: 1.7;

    color: var(--wm-text-secondary, #7f7b78);
}

.service-addon-section {
    display: flex;

    flex-direction: column;

    gap: 18rpx;

    padding-top: 4rpx;
}

.service-addon-section__header {
    display: flex;

    align-items: center;

    justify-content: space-between;

    gap: 16rpx;
}

.service-addon-section__title {
    font-size: 26rpx;

    font-weight: 700;

    line-height: 1.5;

    color: var(--wm-text-primary, #1e2432);
}

.service-addon-section__meta {
    flex-shrink: 0;

    font-size: 22rpx;

    color: var(--wm-text-tertiary, #b4aca8);
}

.service-addon-list {
    display: flex;

    flex-direction: column;

    gap: 14rpx;
}

.service-addon-item {
    display: flex;

    align-items: flex-start;

    justify-content: space-between;

    gap: 20rpx;

    padding: 22rpx 24rpx;

    border-radius: 30rpx;

    border: 1rpx solid var(--wm-color-border, #efe6e1);

    background: #fcfbf9;
}

.service-addon-item__copy {
    flex: 1;

    min-width: 0;

    display: flex;

    flex-direction: column;

    gap: 8rpx;
}

.service-addon-item__title-row {
    display: flex;

    align-items: center;

    flex-wrap: wrap;

    gap: 10rpx;
}

.service-addon-item__title {
    font-size: 26rpx;

    font-weight: 600;

    line-height: 1.5;

    color: var(--wm-text-primary, #1e2432);
}

.service-addon-item__type {
    min-height: 38rpx;

    padding: 0 14rpx;

    border-radius: 999rpx;

    display: inline-flex;

    align-items: center;

    justify-content: center;

    font-size: 20rpx;

    font-weight: 600;

    color: var(--wm-color-primary, #e85a4f);

    background: rgba(255, 241, 238, 0.92);

    border: 1rpx solid var(--wm-color-border-strong, #f4c7bf);

    box-sizing: border-box;
}

.service-addon-item__desc {
    font-size: 22rpx;

    line-height: 1.6;

    color: var(--wm-text-secondary, #7f7b78);
}

.service-addon-item__meta {
    font-size: 22rpx;

    line-height: 1.6;

    color: var(--wm-text-tertiary, #b4aca8);
}

.service-addon-item__price {
    flex-shrink: 0;

    font-size: 28rpx;

    font-weight: 700;

    line-height: 1.5;

    color: var(--wm-color-primary, #e85a4f);
}

.service-addon-item--related {
    background: linear-gradient(180deg, #f5fbf8 0%, #ffffff 100%);

    border-color: rgba(198, 234, 215, 0.96);
}

.service-addon-item__type--related {
    color: #14804a;

    background: rgba(230, 248, 238, 0.96);

    border-color: rgba(198, 234, 215, 0.96);
}

.service-addon-empty {
    padding: 28rpx 30rpx;

    border-radius: 28rpx;

    border: 1rpx dashed var(--wm-color-border, #efe6e1);

    background: #fcfbf9;
}

.service-addon-empty text {
    font-size: 24rpx;

    line-height: 1.6;

    color: var(--wm-text-tertiary, #b4aca8);
}

.inline-link {
    padding-top: 4rpx;
}

.inline-link__text,
.inline-copy__text {
    font-size: 24rpx;

    font-weight: 600;

    color: var(--wm-color-primary, #e85a4f);
}

.sub-panel {
    display: flex;

    flex-direction: column;

    gap: 0;
}

.sub-panel__row {
    display: flex;

    align-items: flex-start;

    justify-content: space-between;

    gap: 24rpx;

    padding: 18rpx 0;
}

.sub-panel__row + .sub-panel__row {
    border-top: 1rpx solid var(--wm-color-border, #efe6e1);
}

.sub-panel__row--stack {
    flex-direction: column;

    gap: 12rpx;
}

.sub-panel__label {
    flex-shrink: 0;

    font-size: 24rpx;

    line-height: 1.5;

    color: var(--wm-text-secondary, #7f7b78);
}

.sub-panel__value {
    flex: 1;

    font-size: 26rpx;

    line-height: 1.6;

    text-align: right;

    color: var(--wm-text-primary, #1e2432);
}

.sub-panel__value--left {
    text-align: left;
}

.sub-panel__value--danger {
    color: #dc2626;
}

.progress-list {
    display: flex;

    flex-direction: column;

    gap: 16rpx;
}

.progress-list__row {
    display: flex;

    align-items: flex-start;

    justify-content: space-between;

    gap: 20rpx;
}

.progress-list__label {
    flex-shrink: 0;

    font-size: 24rpx;

    line-height: 1.6;

    color: var(--wm-text-primary, #1e2432);
}

.progress-list__value {
    flex: 1;

    font-size: 24rpx;

    line-height: 1.6;

    text-align: right;

    color: var(--wm-text-secondary, #7f7b78);
}

.info-list {
    display: flex;

    flex-direction: column;

    gap: 10rpx;
}

.info-list__row {
    display: flex;
}

.info-list__main {
    font-size: 26rpx;

    font-weight: 600;

    line-height: 1.65;

    color: var(--wm-text-primary, #1e2432);
}

.info-list__sub {
    font-size: 24rpx;

    line-height: 1.65;

    color: var(--wm-text-secondary, #7f7b78);
}

.voucher-image {
    margin-top: 4rpx;

    border-radius: 45rpx;

    overflow: hidden;

    background: var(--wm-color-bg-soft, #fff7f4);
}

.voucher-image image {
    width: 100%;

    height: 420rpx;

    display: block;
}

.voucher-empty {
    margin-top: 4rpx;

    min-height: 160rpx;

    border-radius: 45rpx;

    display: flex;

    align-items: center;

    justify-content: center;

    background: var(--wm-color-bg-soft, #fff7f4);
}

.voucher-empty text {
    font-size: 24rpx;

    color: var(--wm-text-tertiary, #b4aca8);
}

.refund-status {
    padding: 8rpx 18rpx;

    border-radius: 999rpx;

    font-size: 22rpx;

    font-weight: 600;
}

.refund-item-list {
    display: flex;

    flex-direction: column;

    gap: 16rpx;

    width: 100%;
}

.refund-item {
    display: flex;

    flex-direction: column;

    gap: 8rpx;

    padding: 20rpx 22rpx;

    border-radius: 24rpx;

    background: rgba(248, 250, 252, 0.88);

    border: 1rpx solid rgba(226, 232, 240, 0.9);
}

.refund-item__head {
    display: flex;

    align-items: center;

    justify-content: space-between;

    gap: 16rpx;
}

.refund-item__title {
    font-size: 24rpx;

    font-weight: 600;

    color: var(--wm-text-primary, #1e2432);
}

.refund-item__amount {
    font-size: 24rpx;

    font-weight: 700;

    color: var(--wm-color-primary, #e85a4f);
}

.refund-item__meta {
    display: flex;

    flex-wrap: wrap;

    gap: 12rpx 18rpx;

    font-size: 22rpx;

    color: var(--wm-text-secondary, #7f7b78);
}

.refund-item__desc {
    font-size: 22rpx;

    line-height: 1.6;

    color: var(--wm-text-secondary, #7f7b78);
}

.action-bar {
    display: flex;

    align-items: center;

    gap: 20rpx;

    width: 100%;
}

.action-bar__buttons {
    display: flex;

    flex: 1;

    gap: 20rpx;

    min-width: 0;
}

.action-bar__more {
    flex-shrink: 0;

    display: inline-flex;

    align-items: center;

    justify-content: center;

    min-width: 120rpx;
}

.action-bar__more-text {
    font-size: 24rpx;

    font-weight: 600;

    color: var(--wm-text-secondary, #7f7b78);

    white-space: nowrap;
}

.popup {
    background: rgba(255, 255, 255, 0.98);

    border-top-left-radius: 52rpx;

    border-top-right-radius: 52rpx;

    padding: 34rpx 37rpx 37rpx;
}

.popup__header {
    display: flex;

    align-items: center;

    justify-content: space-between;

    gap: 20rpx;
}

.popup__title {
    font-size: 32rpx;

    font-weight: 700;

    color: var(--wm-text-primary, #1e2432);
}

.popup__content {
    margin-top: 28rpx;
}

.form-item + .form-item {
    margin-top: 20rpx;
}

.form-item__label {
    display: block;

    margin-bottom: 12rpx;

    font-size: 26rpx;

    font-weight: 600;

    color: var(--wm-text-primary, #1e2432);
}

.form-item__tip {
    display: block;

    margin-top: 8rpx;

    font-size: 22rpx;

    color: var(--wm-text-tertiary, #b4aca8);
}

.refund-amount-card {
    display: flex;

    flex-direction: column;

    gap: 10rpx;

    padding: 24rpx 28rpx;

    border-radius: 28rpx;

    background: var(--wm-color-bg-soft, #fff7f4);

    border: 2rpx solid var(--wm-color-border, #efe6e1);
}

.refund-amount-card__value {
    font-size: 34rpx;

    font-weight: 700;

    color: var(--wm-color-danger, #b44a3a);
}

.refund-amount-card__tip {
    font-size: 22rpx;

    line-height: 1.6;

    color: var(--wm-text-tertiary, #b4aca8);
}

.popup__actions {
    display: flex;

    gap: 22rpx;

    margin-top: 28rpx;
}

.popup__action {
    flex: 1;
}

.voucher-upload {
    margin-top: 16rpx;
}

.voucher-upload__preview,
.voucher-upload__add {
    width: 100%;

    height: 360rpx;

    border-radius: 45rpx;

    overflow: hidden;
}

.voucher-upload__preview {
    position: relative;

    background: var(--wm-color-bg-soft, #fff7f4);
}

.voucher-upload__preview image {
    width: 100%;

    height: 100%;

    display: block;
}

.voucher-upload__remove {
    position: absolute;

    top: 16rpx;

    right: 16rpx;

    width: 48rpx;

    height: 48rpx;

    border-radius: 50%;

    background: rgba(15, 23, 42, 0.58);

    display: flex;

    align-items: center;

    justify-content: center;
}

.voucher-upload__add {
    border: 2rpx dashed var(--wm-color-border, #efe6e1);

    background: var(--wm-color-bg-soft, #fff7f4);

    display: flex;

    flex-direction: column;

    align-items: center;

    justify-content: center;
}

.voucher-upload__text {
    margin-top: 16rpx;

    font-size: 28rpx;

    color: var(--wm-text-secondary, #7f7b78);

    font-weight: 600;
}

.voucher-upload__tip {
    margin-top: 8rpx;

    font-size: 22rpx;

    color: var(--wm-text-tertiary, #b4aca8);
}

.safe-bottom {
    height: var(--wm-safe-bottom-action, calc(150rpx + env(safe-area-inset-bottom)));
}

.loading-container {
    min-height: 100vh;

    display: flex;

    flex-direction: column;

    align-items: center;

    justify-content: center;

    gap: 16rpx;

    background: var(--wm-color-bg-page, #fff7f4);
}

.loading-text {
    font-size: 26rpx;

    color: var(--wm-text-tertiary, #b4aca8);
}
</style>
