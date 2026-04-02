<template>
    <page-meta :page-style="$theme.pageStyle" />
    <PageShell scene="consumer">
        <BaseNavbar title="订单详情" />

        <view v-if="order" class="order-detail">
            <view class="page-body">
                <view class="status-card" :style="{ background: statusTheme.background }">
                    <view class="status-card__chip" :style="{ background: statusTheme.iconBg }">
                        <text class="status-card__chip-text">{{ order.order_status_desc }}</text>
                    </view>
                    <text class="status-card__title">{{ statusHeadline }}</text>
                    <text class="status-card__desc">{{ statusCardText }}</text>
                    <view
                        v-if="showPayCountdown || showVoucherPending || Number(needPayAmount) > 0"
                        class="status-card__meta"
                    >
                        <view v-if="showPayCountdown" class="status-card__meta-item">
                            <text class="status-card__meta-label">剩余支付时间</text>
                            <text class="status-card__meta-value">{{ payCountdownText }}</text>
                        </view>
                        <view v-else-if="showVoucherPending" class="status-card__meta-item">
                            <text class="status-card__meta-label">线下凭证</text>
                            <text class="status-card__meta-value">审核中</text>
                        </view>
                        <view
                            v-if="
                                Number(needPayAmount) > 0 && [1, 2, 3].includes(order.order_status)
                            "
                            class="status-card__meta-item"
                        >
                            <text class="status-card__meta-label">当前待支付</text>
                            <text class="status-card__meta-value"
                                >¥{{ formatAmount(needPayAmount) }}</text
                            >
                        </view>
                    </view>
                </view>

                <view class="card card--core">
                    <text class="card__title">服务信息</text>
                    <view class="service-summary">
                        <text class="service-summary__label">主套餐</text>
                        <view class="service-summary__headline">
                            <text class="service-summary__title">{{ serviceCardTitle }}</text>
                            <text class="service-summary__price"
                                >¥{{ formatAmount(primaryServiceAmount) }}</text
                            >
                        </view>
                        <text class="service-summary__meta">{{ serviceCardMeta }}</text>
                        <text v-if="primaryPackageDescription" class="service-summary__desc">
                            {{ primaryPackageDescription }}
                        </text>
                    </view>
                    <view v-if="serviceAddonRows.length" class="service-addon-section">
                        <view class="service-addon-section__header">
                            <text class="service-addon-section__title">附加服务明细</text>
                            <text class="service-addon-section__meta">{{
                                serviceAddonSummaryText
                            }}</text>
                        </view>
                        <view class="service-addon-list">
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
                                    <text v-if="item.description" class="service-addon-item__desc">
                                        {{ item.description }}
                                    </text>
                                </view>
                                <text class="service-addon-item__price">{{ item.priceText }}</text>
                            </view>
                        </view>
                    </view>
                </view>

                <view class="card card--core">
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

                <view class="card card--core">
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
                        <view v-if="order.pay_time" class="sub-panel__row">
                            <text class="sub-panel__label">支付时间</text>
                            <text class="sub-panel__value">{{ order.pay_time }}</text>
                        </view>
                    </view>
                </view>

                <view v-if="order.pay_type === 4 || order.pay_voucher" class="card card--secondary">
                    <text class="card__title">线下支付凭证</text>
                    <view class="sub-panel">
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
                        <view class="sub-panel__row sub-panel__row--stack">
                            <text class="sub-panel__label">退款原因</text>
                            <text class="sub-panel__value sub-panel__value--left">
                                {{ order.refund.refund_reason }}
                            </text>
                        </view>
                    </view>
                </view>
            </view>

            <ActionArea sticky safeBottom>
                <view class="action-bar">
                    <view class="action-bar__buttons">
                        <view
                            v-if="secondaryVisibleAction"
                            class="action-btn action-btn--secondary"
                            :style="secondaryVisibleAction.style"
                            @click="secondaryVisibleAction.onClick"
                        >
                            <text>{{ secondaryVisibleAction.label }}</text>
                        </view>
                        <view
                            v-if="primaryVisibleAction"
                            class="action-btn action-btn--primary"
                            :style="primaryVisibleAction.style"
                            @click="primaryVisibleAction.onClick"
                        >
                            <text>{{ primaryVisibleAction.label }}</text>
                        </view>
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

            <tn-popup
                v-model="showRefundPopup"
                open-direction="bottom"
                :radius="32"
                safe-area-inset-bottom
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
                            ><text class="form-item__label">退款金额</text
                            ><tn-input
                                v-model="refundForm.amount"
                                type="number"
                                :placeholder="`最多可退 ¥${formatAmount(order.pay_amount)}`"
                                border
                        /></view>
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
                        <view
                            class="popup__btn popup__btn--secondary"
                            :style="{
                                borderColor: $theme.primaryColor,
                                color: $theme.primaryColor
                            }"
                            @click="showRefundPopup = false"
                            ><text>取消</text></view
                        >
                        <view
                            class="popup__btn popup__btn--primary"
                            :style="{
                                background: `linear-gradient(135deg, ${$theme.primaryColor} 0%, ${$theme.primaryColor} 100%)`,
                                color: $theme.btnColor
                            }"
                            @click="submitRefund"
                            ><text>提交申请</text></view
                        >
                    </view>
                </view>
            </tn-popup>

            <tn-popup
                v-model="showVoucherPopup"
                open-direction="bottom"
                :radius="32"
                safe-area-inset-bottom
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
                        <view
                            class="popup__btn popup__btn--secondary"
                            :style="{
                                borderColor: $theme.primaryColor,
                                color: $theme.primaryColor
                            }"
                            @click="showVoucherPopup = false"
                            ><text>取消</text></view
                        >
                        <view
                            class="popup__btn popup__btn--primary"
                            :style="{
                                background: `linear-gradient(135deg, ${$theme.primaryColor} 0%, ${$theme.primaryColor} 100%)`,
                                color: $theme.btnColor
                            }"
                            @click="submitVoucher"
                            ><text>{{
                                voucherForm.uploading ? '上传中...' : '提交审核'
                            }}</text></view
                        >
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
import { useThemeStore } from '@/stores/theme'
import {
    applyRefund,
    cancelOrder,
    confirmOrder,
    deleteOrder,
    getOrderDetail,
    uploadPayVoucher
} from '@/api/order'
import { uploadImage } from '@/api/app'
const $theme = useThemeStore()
const orderId = ref(0)
const order = ref<any>(null)
const showRefundPopup = ref(false)
const showVoucherPopup = ref(false)
const payState = reactive({
    showPay: false,
    showCheck: false,
    from: 'order',
    redirect: '/pages/order_detail/order_detail',
    paymentSn: ''
})
const refundForm = reactive({ amount: '', reason: '' })
const voucherForm = reactive({ image: '', uploading: false })
const showFinancialDetails = ref(false)
const payCountdownSeconds = ref(0)
let payCountdownTimer: ReturnType<typeof setInterval> | null = null
let payCountdownRefreshing = false

const formatAmount = (value: any) => Number(value || 0).toFixed(2)
const formatCountdown = (seconds: number) => {
    const total = Math.max(seconds, 0)
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
            4: { color: '#DC2626', bg: 'rgba(220,38,38,0.1)' }
        } as Record<number, { color: string; bg: string }>
    )[status] || { color: '#64748B', bg: 'rgba(100,116,139,0.1)' })

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
const getExtraItemTitle = (item: any) => {
    if (Number(item?.item_type || 1) === 2) {
        return item?.item_meta?.label || item?.package_name || '预约附加项'
    }
    if (Number(item?.item_type || 1) === 3) {
        const roleLabel = item?.item_meta?.role_label || '关联服务'
        const staffName = item?.staff?.name || item?.staff_name || ''
        return staffName ? `${roleLabel} · ${staffName}` : roleLabel
    }
    return item?.package_name || '服务项目'
}
const getExtraItemTypeText = (item: any) => {
    const itemType = Number(item?.item_type || 1)
    if (itemType === 2) return '预约附加项'
    if (itemType === 3) return '关联服务人员'
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
const buildAddonRowKey = (kind: string, title: string, amount: number) =>
    `${kind}:${title.trim()}:${formatAmount(amount)}`
const serviceAddonRows = computed(() => {
    const items = Array.isArray(order.value?.items) ? order.value.items : []
    const rows: Array<{
        key: string
        title: string
        typeText: string
        description: string
        priceText: string
    }> = []
    const seen = new Set<string>()

    const pushRow = (
        kind: string,
        title: string,
        amount: number,
        typeText: string,
        description = ''
    ) => {
        const normalizedTitle = String(title || '').trim()
        if (!normalizedTitle) return

        const key = buildAddonRowKey(kind, normalizedTitle, amount)
        if (seen.has(key)) return
        seen.add(key)
        rows.push({
            key,
            title: normalizedTitle,
            typeText,
            description: String(description || '').trim(),
            priceText: `¥${formatAmount(amount)}`
        })
    }

    extraItems.value.forEach((item: any) => {
        pushRow(
            Number(item?.item_type || 1) === 3 ? 'related' : 'addon',
            getExtraItemTitle(item),
            getItemDisplayAmount(item),
            getExtraItemTypeText(item),
            getExtraItemDescription(item)
        )
    })

    items.forEach((item: any) => {
        ;(item?.addons || []).forEach((addon: any) => {
            pushRow(
                'addon',
                addon?.addon_name || addon?.name || '附加服务',
                getAddonDisplayAmount(addon),
                '附加服务'
            )
        })
    })

    return rows
})
const serviceAddonSummaryText = computed(() => `共 ${serviceAddonRows.value.length} 项`)
const serviceCardTitle = computed(() => {
    const packageName = String(primaryPackageName.value || '').trim()
    const staffName = String(primaryStaffName.value || '').trim()

    if (packageName && staffName && staffName !== '待分配服务人员') {
        return `${packageName}｜${staffName}`
    }

    return packageName || staffName || '婚礼服务订单'
})
const serviceCardMeta = computed(() => {
    const locationText = [
        order.value?.service_region_text,
        order.value?.wedding_venue,
        order.value?.service_address
    ]
        .map((item: any) => String(item || '').trim())
        .filter(Boolean)[0]

    return [primaryServiceDate.value, locationText].filter(Boolean).join(' · ') || '待确认服务信息'
})
const needPayAmount = computed(() => {
    if (!order.value) return 0
    return Number(order.value.need_pay_amount || 0)
})
const showVoucherPending = computed(
    () =>
        !!order.value &&
        Number(order.value.pay_type) === 4 &&
        Number(order.value.pay_voucher_status) === 0
)
const showPayCountdown = computed(() => !!order.value && payCountdownSeconds.value > 0)
const payCountdownText = computed(() => formatCountdown(payCountdownSeconds.value))
const canPayOnline = computed(
    () =>
        !!order.value &&
        Number(order.value.order_status) === 1 &&
        Number(order.value.need_pay_amount || 0) > 0 &&
        !(Number(order.value.pay_type) === 4 && Number(order.value.pay_voucher_status) === 0)
)
const canUploadVoucher = computed(
    () =>
        !!order.value &&
        Number(order.value.order_status) === 1 &&
        Number(order.value.pay_voucher_status) !== 0
)
const statusTheme = computed(() => getStatusTheme(Number(order.value?.order_status ?? 6)))
const statusDescription = computed(
    () =>
        ((
            {
                0: '工作人员确认后，订单会进入支付流程。',
                1: showVoucherPending.value
                    ? '线下支付凭证审核中，审核结果会及时同步到订单状态。'
                    : showPayCountdown.value
                    ? `请在 ${payCountdownText.value} 内完成支付，超时订单将自动取消。`
                    : '请尽快完成支付，系统会自动计算当前应付金额。',
                2: '订单已支付成功，服务准备中。',
                3: '服务进行中，完成后可在这里确认完成。',
                4: '本次服务已完成，感谢你的信任。',
                5: '订单已评价，服务流程已闭环。',
                6: '订单已取消，如需继续预约可重新下单。',
                7: '订单当前处于暂停状态，恢复后会继续履约。',
                8: '订单已进入退款完成状态。'
            } as Record<number, string>
        )[Number(order.value?.order_status ?? -1)] || '订单状态已更新，请留意后续进度。')
)
const statusHeadline = computed(() => {
    const orderStatus = Number(order.value?.order_status ?? -1)
    const serviceName = String(primaryPackageName.value || '服务').trim()

    const headlines: Record<number, string> = {
        0: `${serviceName}订单待确认`,
        1: `${serviceName}订单待支付`,
        2: `${serviceName}订单已锁定`,
        3: `${serviceName}服务进行中`,
        4: `${serviceName}订单已完成`,
        5: `${serviceName}订单已评价`,
        6: `${serviceName}订单已取消`,
        7: `${serviceName}订单已暂停`,
        8: `${serviceName}订单已退款`
    }

    return headlines[orderStatus] || `${serviceName}订单状态已更新`
})
const statusCardText = computed(
    () => `订单编号：${order.value?.order_sn || '-'}\n${statusDescription.value}`
)
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

    return [2, 3, 4, 5, 8].includes(Number(order.value.order_status || 0))
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
    if ([2, 3, 4, 5, 8].includes(Number(order.value.order_status || 0))) return '已完成'
    return '待开始'
})
const progressItems = computed(() => [
    {
        label: '1. 档期确认',
        value: Number(order.value?.order_status || 0) >= 1 ? '已完成' : '待确认'
    },
    {
        label: '2. 支付进度',
        value: paymentProgressText.value
    },
    {
        label: '3. 婚礼执行',
        value: primaryServiceDate.value || '待安排'
    },
    {
        label: '4. 尾款结清',
        value:
            Number(order.value?.balance_amount || 0) > 0
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

    if (Number(order.value.order_status) === 3) {
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

    if ([2, 3].includes(status) && !order.value.refund) {
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

const fetchDetail = async () => {
    try {
        order.value = await getOrderDetail({ id: orderId.value })
        syncPayCountdown(order.value?.pay_remain_seconds || 0)
    } catch (e: any) {
        clearPayCountdown()
        uni.showToast({ title: e?.message || '加载失败', icon: 'none' })
    }
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
        itemList: ['申请改期', '申请暂停', '我的申请'],
        success: ({ tapIndex }) => {
            const routes = [
                `/packages/pages/order_change/apply_date?order_id=${orderId.value}`,
                `/packages/pages/order_change/apply_pause?order_id=${orderId.value}`,
                '/packages/pages/order_change/list?type=change'
            ]
            const url = routes[tapIndex]
            if (url) uni.navigateTo({ url })
        }
    })

const handlePay = () => {
    if (Number(order.value?.pay_deadline_time || 0) > 0 && payCountdownSeconds.value <= 0) {
        uni.showToast({ title: '支付时间已到，正在刷新订单', icon: 'none' })
        fetchDetail()
        return
    }
    payState.paymentSn = ''
    payState.showPay = true
}

const handlePaySuccess = async () => {
    payState.showPay = false
    payState.showCheck = false
    const paymentSn = payState.paymentSn
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
        uni.showToast({ title: '订单已完成', icon: 'success' })
        await fetchDetail()
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

const submitRefund = async () => {
    if (!refundForm.amount || Number(refundForm.amount) <= 0)
        return uni.showToast({ title: '请输入退款金额', icon: 'none' })
    if (Number(refundForm.amount) > Number(order.value?.pay_amount || 0))
        return uni.showToast({ title: '退款金额不能超过实付金额', icon: 'none' })
    if (!refundForm.reason.trim()) return uni.showToast({ title: '请输入退款原因', icon: 'none' })
    try {
        await applyRefund({
            id: orderId.value,
            amount: Number(refundForm.amount),
            reason: refundForm.reason
        })
        uni.showToast({ title: '申请已提交', icon: 'success' })
        showRefundPopup.value = false
        refundForm.amount = ''
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

onLoad((options: any) => {
    $theme.setScene('consumer')
    orderId.value = Number(options?.id || 0)
    if (options?.payment_sn) payState.paymentSn = String(options.payment_sn)
    if (options?.checkPay) payState.showCheck = true
    fetchDetail()
})

onShow(() => {
    $theme.setScene('consumer')
    if (orderId.value > 0 && order.value) {
        fetchDetail()
    }
})

onHide(() => {
    clearPayCountdown()
})

onUnload(() => {
    clearPayCountdown()
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
    gap: 12rpx;
}

.service-summary__label {
    font-size: 22rpx;
    font-weight: 600;
    color: var(--wm-text-tertiary, #b4aca8);
}

.service-summary__headline {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 20rpx;
}

.service-summary__title {
    flex: 1;
    min-width: 0;
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

.service-summary__meta {
    font-size: 24rpx;
    line-height: 1.6;
    color: var(--wm-text-secondary, #7f7b78);
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

.service-addon-item__price {
    flex-shrink: 0;
    font-size: 28rpx;
    font-weight: 700;
    line-height: 1.5;
    color: var(--wm-color-primary, #e85a4f);
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

.action-btn {
    flex: 1;
    min-height: 90rpx;
    padding: 0 30rpx;
    border-radius: 37rpx;
    box-sizing: border-box;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 28rpx;
    font-weight: 700;
    line-height: 1;
}

.action-btn--primary {
    box-shadow: 0 12rpx 24rpx rgba(232, 90, 79, 0.16);
}

.action-btn--secondary {
    background: rgba(255, 255, 255, 0.88);
    border: 1rpx solid var(--wm-color-border, #efe6e1);
}

.popup {
    background: #fff;
    border-top-left-radius: 52rpx;
    border-top-right-radius: 52rpx;
    padding: 34rpx 37rpx calc(env(safe-area-inset-bottom) + 37rpx);
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

.popup__actions {
    display: flex;
    gap: 22rpx;
    margin-top: 28rpx;
}

.popup__btn {
    flex: 1;
    height: 90rpx;
    border-radius: 37rpx;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 28rpx;
    font-weight: 700;
}

.popup__btn--secondary {
    background: transparent;
    border: 2rpx solid;
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
    height: calc(env(safe-area-inset-bottom) + 37rpx);
}

.loading-container {
    min-height: 100vh;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 16rpx;
    background: var(--wm-color-page, #fcfbf9);
}

.loading-text {
    font-size: 26rpx;
    color: var(--wm-text-tertiary, #b4aca8);
}
</style>
