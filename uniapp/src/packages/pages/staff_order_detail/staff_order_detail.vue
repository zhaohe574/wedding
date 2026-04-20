<template>
    <page-meta :page-style="$theme.pageStyle" />

    <PageShell scene="staff" hasSafeBottom>
        <BaseNavbar title="订单详情" />

        <view v-if="order" class="staff-order-detail">
            <view class="staff-order-detail__content">
                <view
                    :class="[
                        'staff-order-hero',

                        'wm-panel-card',

                        `staff-order-hero--${statusInfo.tone}`
                    ]"
                >
                    <view class="staff-order-hero__top">
                        <StatusBadge :tone="statusInfo.badgeModifier as BadgeTone" size="sm">
                            {{ statusInfo.badgeText }}
                        </StatusBadge>

                        <view class="hero-pill hero-pill--ghost">
                            <text class="hero-pill__text">订单号 {{ order.order_sn }}</text>
                        </view>
                    </view>

                    <view class="staff-order-hero__copy">
                        <text class="staff-order-hero__title">{{ statusInfo.title }}</text>

                        <text class="staff-order-hero__desc">{{ statusInfo.description }}</text>
                    </view>

                    <view class="hero-chip-list">
                        <view v-for="item in heroChips" :key="item.label" class="hero-chip">
                            <text class="hero-chip__label">{{ item.label }}</text>

                            <text class="hero-chip__value">{{ item.value }}</text>
                        </view>
                    </view>
                </view>

                <view class="staff-section-card wm-form-block">
                    <view class="section-head">
                        <view class="section-head__copy">
                            <text class="section-head__title">执行摘要</text>
                        </view>

                        <text class="section-head__meta">{{ serviceSummaryMeta }}</text>
                    </view>

                    <text class="section-main-text">{{ serviceSummaryTitle }}</text>

                    <text class="section-sub-text">{{ serviceSummaryDescription }}</text>

                    <view v-if="summaryTags.length" class="tag-list">
                        <view v-for="item in summaryTags" :key="item" class="tag-list__item">
                            <text class="tag-list__text">{{ item }}</text>
                        </view>
                    </view>

                    <view class="sub-panel">
                        <view
                            v-for="item in executionRows"
                            :key="item.label"
                            class="sub-panel__row"
                        >
                            <text class="sub-panel__label">{{ item.label }}</text>

                            <text class="sub-panel__value">{{ item.value }}</text>
                        </view>
                    </view>
                </view>

                <view class="staff-section-card wm-form-block">
                    <view class="section-head">
                        <view class="section-head__copy">
                            <text class="section-head__title">服务项明细</text>
                        </view>

                        <text class="section-head__meta">{{ serviceItemsMeta }}</text>
                    </view>

                    <text v-if="legacyServiceNotice" class="section-note">{{
                        legacyServiceNotice
                    }}</text>

                    <view v-if="serviceCards.length" class="service-list">
                        <view
                            v-for="item in serviceCards"
                            :key="item.id"
                            class="service-card wm-soft-card"
                        >
                            <view class="service-card__row service-card__row--start">
                                <view class="service-card__copy">
                                    <view class="service-card__title-row">
                                        <text class="service-card__title">{{ item.title }}</text>

                                        <StatusBadge v-if="item.typeText" tone="info" size="sm">
                                            {{ item.typeText }}
                                        </StatusBadge>
                                    </view>

                                    <text v-if="item.description" class="service-card__desc">
                                        {{ item.description }}
                                    </text>
                                </view>

                                <text class="service-card__price">{{ item.priceText }}</text>
                            </view>

                            <view class="service-card__row service-card__row--center">
                                <text class="service-card__meta">{{ item.dateText }}</text>

                                <StatusBadge :tone="item.statusModifier as BadgeTone" size="sm">
                                    {{ item.statusText }}
                                </StatusBadge>
                            </view>

                            <view class="service-card__row service-card__row--center">
                                <text class="service-card__meta">{{ item.quantityText }}</text>

                                <text v-if="item.staffName" class="service-card__meta">
                                    {{ item.staffName }}
                                </text>
                            </view>

                            <view v-if="item.addons.length" class="addon-box">
                                <view class="addon-box__header">
                                    <text class="addon-box__title">附加服务</text>

                                    <text class="addon-box__total">{{ item.addonTotalText }}</text>
                                </view>

                                <view
                                    v-for="addon in item.addons"
                                    :key="`${item.id}-${addon.id}`"
                                    class="addon-box__row"
                                >
                                    <text class="addon-box__name">{{ addon.name }}</text>

                                    <text class="addon-box__price">{{ addon.priceText }}</text>
                                </view>
                            </view>
                        </view>
                    </view>

                    <EmptyState
                        v-else
                        title="当前没有可展示的服务项"
                        description="服务项会显示在这里。"
                    />
                </view>

                <view class="staff-section-card wm-form-block">
                    <view class="section-head">
                        <view class="section-head__copy">
                            <text class="section-head__title">金额与结算</text>
                        </view>

                        <text class="section-head__meta">服务人员视角</text>
                    </view>

                    <text class="section-note">{{ paymentScopeNotice }}</text>

                    <view class="sub-panel">
                        <view
                            v-for="item in amountRows"
                            :key="item.label"
                            :class="['sub-panel__row', { 'sub-panel__row--total': item.total }]"
                        >
                            <text class="sub-panel__label">{{ item.label }}</text>

                            <text
                                :class="[
                                    'sub-panel__value',

                                    {
                                        'sub-panel__value--danger': item.danger,

                                        'sub-panel__value--total': item.total
                                    }
                                ]"
                            >
                                {{ item.value }}
                            </text>
                        </view>
                    </view>
                </view>

                <view class="staff-section-card wm-form-block">
                    <view class="section-head">
                        <view class="section-head__copy">
                            <text class="section-head__title">履约与联系</text>
                        </view>

                        <text class="section-head__meta">{{ contactMeta }}</text>
                    </view>

                    <view class="sub-panel">
                        <view
                            v-for="item in contactRows"
                            :key="item.label"
                            :class="['sub-panel__row', { 'sub-panel__row--stack': item.multiline }]"
                        >
                            <text class="sub-panel__label">{{ item.label }}</text>

                            <text
                                :class="[
                                    'sub-panel__value',

                                    { 'sub-panel__value--left': item.multiline }
                                ]"
                            >
                                {{ item.value }}
                            </text>
                        </view>
                    </view>
                </view>

                <view class="staff-section-card wm-form-block">
                    <view class="section-head">
                        <view class="section-head__copy">
                            <text class="section-head__title">订单信息</text>
                        </view>

                        <text class="section-link" @click="copyOrderSn">复制编号</text>
                    </view>

                    <view class="sub-panel">
                        <view
                            v-for="item in orderInfoRows"
                            :key="item.label"
                            class="sub-panel__row"
                        >
                            <text class="sub-panel__label">{{ item.label }}</text>

                            <text class="sub-panel__value">{{ item.value }}</text>
                        </view>
                    </view>
                </view>

                <view class="staff-section-card wm-form-block">
                    <view class="section-head">
                        <view class="section-head__copy">
                            <text class="section-head__title">订单确认函</text>
                        </view>

                        <text class="section-link" @click="openConfirmLetterActions">更多操作</text>
                    </view>

                    <view class="sub-panel">
                        <view class="sub-panel__row">
                            <text class="sub-panel__label">当前版本</text>

                            <text class="sub-panel__value">{{
                                confirmLetter?.version ? `v${confirmLetter.version}` : '未生成'
                            }}</text>
                        </view>

                        <view class="sub-panel__row">
                            <text class="sub-panel__label">版本记录</text>

                            <text class="sub-panel__value"
                                >{{ confirmLetterHistory.length || 0 }} 个版本</text
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
            </view>

            <ActionArea
                v-if="primaryActionVisible || secondaryActionVisible"
                sticky
                safeBottom
                layout="split"
            >
                <view class="action-bar">
                    <view class="action-bar__buttons">
                        <view
                            v-if="secondaryActionVisible"
                            class="action-btn action-btn--secondary"
                            @click="handleContactCustomer"
                        >
                            <text>联系客户</text>
                        </view>

                        <view
                            v-if="primaryActionVisible"
                            class="action-btn action-btn--primary"
                            @click="handleConfirm"
                        >
                            <text>{{ primaryActionText }}</text>
                        </view>
                    </view>
                </view>
            </ActionArea>
        </view>

        <view v-else class="loading-container">
            <tn-loading mode="circle" />

            <text class="loading-text">订单加载中...</text>
        </view>
    </PageShell>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue'

import { onHide, onLoad, onShow, onUnload } from '@dcloudio/uni-app'

import PageShell from '@/components/base/PageShell.vue'

import BaseNavbar from '@/components/base/BaseNavbar.vue'

import ActionArea from '@/components/base/ActionArea.vue'

import EmptyState from '@/components/base/EmptyState.vue'

import StatusBadge from '@/components/base/StatusBadge.vue'

import {
    staffCenterOrderConfirmLetterDetail,
    staffCenterOrderConfirmLetterGenerate,
    staffCenterOrderConfirmLetterHistory,
    staffCenterOrderConfirmLetterPush,
    staffCenterOrderComplete,
    staffCenterOrderDetail,
    staffCenterOrderConfirm,
    staffCenterOrderStartService
} from '@/api/staffCenter'

import { isOrderConfirmLetterBitmapAssetUrl } from '@/utils/orderConfirmLetterRenderer'

import { ensureStaffCenterAccess } from '@/packages/common/utils/staff-center'

import { saveImageToPhotosAlbum } from '@/packages/common/utils/file'

import { useThemeStore } from '@/stores/theme'

interface HeroChip {
    label: string

    value: string
}

type BadgeTone = 'neutral' | 'success' | 'warning' | 'danger' | 'info'

interface InfoRow {
    label: string

    value: string

    multiline?: boolean
}

interface AmountRow {
    label: string

    value: string

    danger?: boolean

    total?: boolean
}

interface AddonCard {
    id: number | string

    name: string

    priceText: string
}

interface ServiceCard {
    id: number

    title: string

    typeText: string

    description: string

    dateText: string

    quantityText: string

    priceText: string

    statusText: string

    statusModifier: 'primary' | 'warning' | 'success' | 'danger' | 'neutral'

    staffName: string

    addonTotalText: string

    addons: AddonCard[]
}

interface StatusDescriptor {
    badgeText: string

    badgeModifier: 'primary' | 'warning' | 'success' | 'danger' | 'neutral'

    tone: 'primary' | 'warning' | 'success' | 'danger' | 'neutral'

    title: string

    description: string
}

const $theme = useThemeStore()

const order = ref<any>(null)

const confirmLetter = ref<any>(null)

const confirmLetterHistory = ref<any[]>([])

const confirmCountdownSeconds = ref(0)

const payCountdownSeconds = ref(0)

let confirmCountdownTimer: ReturnType<typeof setInterval> | null = null

let payCountdownTimer: ReturnType<typeof setInterval> | null = null

let confirmCountdownRefreshing = false

let payCountdownRefreshing = false

let detailRequestPromise: Promise<void> | null = null

let currentOrderId = 0

let hasLoadedOnce = false

let hasBeenHidden = false

const statusConfig: Record<string, Omit<StatusDescriptor, 'badgeText'>> = {
    pending_confirm: {
        badgeModifier: 'primary',

        tone: 'primary',

        title: '等待你确认服务安排',

        description: '请先核对服务安排。'
    },

    pending_pay: {
        badgeModifier: 'warning',

        tone: 'warning',

        title: '订单已确认，等待客户支付',

        description: '服务安排已就绪。'
    },

    paid: {
        badgeModifier: 'success',

        tone: 'success',

        title: '订单待服务，可开始履约',

        description: '订单已进入待服务阶段。'
    },

    in_service: {
        badgeModifier: 'success',

        tone: 'success',

        title: '订单正在服务中',

        description: '订单已进入执行阶段。'
    },

    completed: {
        badgeModifier: 'success',

        tone: 'success',

        title: '订单服务已完成',

        description: '本单已完成履约。'
    },

    reviewed: {
        badgeModifier: 'success',

        tone: 'success',

        title: '订单已闭环并收到评价',

        description: '本单已完成并评价。'
    },

    cancelled: {
        badgeModifier: 'neutral',

        tone: 'neutral',

        title: '订单已取消',

        description: '当前订单无需继续安排服务。'
    },

    paused: {
        badgeModifier: 'warning',

        tone: 'warning',

        title: '订单已暂停',

        description: '请等待后续安排。'
    },

    refunding: {
        badgeModifier: 'warning',

        tone: 'warning',

        title: '订单退款处理中',

        description: '该订单正在退款处理中。'
    },

    refunded: {
        badgeModifier: 'danger',

        tone: 'danger',

        title: '订单已退款',

        description: '该订单已结束。'
    },

    user_deleted: {
        badgeModifier: 'danger',

        tone: 'danger',

        title: '订单已被用户删除',

        description: '该订单已隐藏。'
    }
}

const getStatusKey = (status: number) => {
    const statusMap: Record<number, string> = {
        0: 'pending_confirm',

        1: 'pending_pay',

        2: 'paid',

        3: 'in_service',

        4: 'completed',

        5: 'reviewed',

        6: 'cancelled',

        7: 'paused',

        10: 'refunding',

        8: 'refunded',

        9: 'user_deleted'
    }

    return statusMap[status] || 'pending_pay'
}

const formatAmount = (amount: number | string) => Number(amount || 0).toFixed(2)

const formatCountdown = (seconds: number | string | undefined) => {
    const total = Math.max(Number(seconds || 0), 0)

    if (total <= 0) return '已超时，等待系统处理'

    const hours = Math.floor(total / 3600)

    const minutes = Math.floor((total % 3600) / 60)

    const remainSeconds = total % 60

    return [hours, minutes, remainSeconds].map((item) => String(item).padStart(2, '0')).join(':')
}

const confirmRemainText = computed(() => {
    if (Number(order.value?.order_status ?? -1) !== 0) return ''

    if (Number(order.value?.confirm_deadline_time || 0) <= 0) return ''

    return formatCountdown(confirmCountdownSeconds.value)
})

const confirmTimeoutActionText = computed(() => {
    if (Number(order.value?.order_status ?? -1) !== 0) return ''

    return String(order.value?.confirm_timeout_action_desc || '').trim()
})

const payRemainText = computed(() => {
    if (Number(order.value?.order_status ?? -1) !== 1) return ''

    if (Number(order.value?.pay_deadline_time || 0) <= 0) return ''

    return formatCountdown(payCountdownSeconds.value)
})

const payTimeoutActionText = computed(() => {
    if (Number(order.value?.order_status ?? -1) !== 1) return ''

    return String(order.value?.pay_timeout_action_desc || '').trim()
})

const uniqueServiceDates = computed<string[]>(() => {
    const values = (order.value?.items || [])

        .map((item: any) => String(item?.service_date || '').trim())

        .filter(Boolean)

    return Array.from(new Set<string>(values)).sort()
})

const earliestServiceDateText = computed<string>(() => uniqueServiceDates.value[0] || '待安排')

const serviceDateSummary = computed<string>(() => {
    if (!uniqueServiceDates.value.length) {
        return '待安排服务日期'
    }

    if (uniqueServiceDates.value.length === 1) {
        return uniqueServiceDates.value[0]
    }

    return `${uniqueServiceDates.value[0]} 起，共 ${uniqueServiceDates.value.length} 天`
})

const pendingConfirmCount = computed(
    () =>
        (order.value?.items || []).filter((item: any) => Number(item?.confirm_status ?? 0) === 0)
            .length
)

const packageNames = computed<string[]>(() => {
    const values = (order.value?.items || [])

        .map((item: any) => String(item?.package_name || '').trim())

        .filter(Boolean)

    return Array.from(new Set<string>(values))
})

const serviceSummaryTitle = computed(() => {
    if (packageNames.value.length > 1) {
        return `${packageNames.value[0]} 等 ${packageNames.value.length} 项服务`
    }

    if (packageNames.value.length === 1) {
        return packageNames.value[0]
    }

    return `共 ${(order.value?.items || []).length || 0} 项服务内容`
})

const serviceSummaryMeta = computed(() => `${(order.value?.items || []).length || 0} 项服务`)

const paymentScopeNotice = '当前金额仅按你的履约内容展示。'

const serviceSummaryDescription = computed(() => {
    const region = String(order.value?.service_region_text || '').trim()

    const address = String(order.value?.service_address || '').trim()

    const location = address || region || '待确认服务地点'

    return `${serviceDateSummary.value} · ${location}`
})

const statusInfo = computed<StatusDescriptor>(() => {
    const badgeText = String(order.value?.order_status_desc || '处理中')

    const config =
        statusConfig[getStatusKey(Number(order.value?.order_status ?? 1))] ||
        statusConfig.pending_pay

    return {
        badgeText,

        ...config
    }
})

const heroChips = computed<HeroChip[]>(() => {
    const chips: HeroChip[] = [
        {
            label: '最早服务',

            value: earliestServiceDateText.value
        },

        {
            label: '待确认项',

            value: pendingConfirmCount.value > 0 ? `${pendingConfirmCount.value} 项` : '已确认'
        }
    ]

    if (confirmRemainText.value) {
        chips.push({
            label: '确认时限',

            value: confirmRemainText.value
        })
    }

    if (confirmTimeoutActionText.value) {
        chips.push({
            label: '超时处理',

            value: confirmTimeoutActionText.value
        })
    }

    if (payRemainText.value) {
        chips.push({
            label: '支付时限',

            value: payRemainText.value
        })
    }

    if (payTimeoutActionText.value) {
        chips.push({
            label: '支付超时',

            value: payTimeoutActionText.value
        })
    }

    const contactText = String(
        order.value?.contact_mobile || order.value?.contact_name || ''
    ).trim()

    if (contactText) {
        chips.push({
            label: '联系信息',

            value: contactText
        })
    }

    return chips
})

const summaryTags = computed<string[]>(() => packageNames.value.slice(0, 3))

const legacyServiceNotice = computed(() => {
    const hasLegacyItems = (order.value?.items || []).some(
        (item: any) => Number(item?.item_type || 1) === 2
    )

    if (!hasLegacyItems) {
        return ''
    }

    return '旧版服务项仅保留必要信息。'
})

const executionRows = computed<InfoRow[]>(() => {
    const rows: InfoRow[] = [
        {
            label: '服务时间',

            value: serviceDateSummary.value
        }
    ]

    if (confirmRemainText.value) {
        rows.push({
            label: '剩余确认时间',

            value: confirmRemainText.value
        })
    }

    if (confirmTimeoutActionText.value) {
        rows.push({
            label: '超时处理',

            value: confirmTimeoutActionText.value
        })
    }

    if (payRemainText.value) {
        rows.push({
            label: '剩余支付时间',

            value: payRemainText.value
        })
    }

    if (payTimeoutActionText.value) {
        rows.push({
            label: '支付超时处理',

            value: payTimeoutActionText.value
        })
    }

    const region = String(order.value?.service_region_text || '').trim()

    if (region) {
        rows.push({
            label: '服务地区',

            value: region
        })
    }

    const address = String(order.value?.service_address || '').trim()

    if (address) {
        rows.push({
            label: '详细地址',

            value: address
        })
    }

    rows.push({
        label: '下一步',

        value: statusInfo.value.description
    })

    return rows
})

const getPackageDescription = (item: any) => String(item?.package_description || '').trim()

const getAddonTotal = (item: any) =>
    (item?.addons || []).reduce((sum: number, addon: any) => {
        return sum + Number(addon?.subtotal || addon?.price || 0)
    }, 0)

const getServiceStatusModifier = (item: any) => {
    if (Number(item?.item_status ?? 0) === 2) {
        return 'danger'
    }

    if (Number(item?.confirm_status ?? 0) === 0) {
        return 'primary'
    }

    if ([2, 3, 4, 5].includes(Number(order.value?.order_status ?? -1))) {
        return 'success'
    }

    if (Number(order.value?.order_status ?? -1) === 10) {
        return 'warning'
    }

    if ([6, 8].includes(Number(order.value?.order_status ?? -1))) {
        return 'danger'
    }

    return 'neutral'
}

const getServiceStatusText = (item: any) => {
    if (item?.item_status_desc) {
        return String(item.item_status_desc)
    }

    return Number(item?.confirm_status ?? 0) === 0 ? '待确认' : '已确认'
}

const serviceCards = computed<ServiceCard[]>(() =>
    (order.value?.items || []).map((item: any) => {
        const quantity = Math.max(Number(item?.quantity || 1), 1)

        const amount = Number(item?.subtotal || item?.price || 0)

        const addonTotal = getAddonTotal(item)

        return {
            id: Number(item?.id || 0),

            title: String(item?.package_name || '服务套餐'),

            typeText: String(item?.item_type_desc || ''),

            description: getPackageDescription(item),

            dateText: String(item?.service_date || '未选择日期'),

            quantityText: `数量 x${quantity}`,

            priceText: `¥${formatAmount(amount)}`,

            statusText: getServiceStatusText(item),

            statusModifier: getServiceStatusModifier(item),

            staffName: String(item?.staff_name || '').trim(),

            addonTotalText: `+¥${formatAmount(addonTotal)}`,

            addons: (item?.addons || []).map((addon: any) => ({
                id: addon?.id || addon?.addon_id || '',

                name: String(addon?.addon_name || addon?.name || '附加服务'),

                priceText: `+¥${formatAmount(addon?.subtotal || addon?.price || 0)}`
            }))
        }
    })
)

const serviceItemsMeta = computed(() => `${serviceCards.value.length || 0} 个服务项`)

const orderServiceAmount = computed(() => {
    const serviceAmount = Number(order.value?.service_amount ?? -1)

    if (serviceAmount >= 0) {
        return serviceAmount
    }

    const total = Number(order.value?.total_amount || 0)

    const addonAmount = Number(order.value?.addon_amount || 0)

    return Math.max(0, total - addonAmount)
})

const amountRows = computed<AmountRow[]>(() => {
    const rows: AmountRow[] = [
        {
            label: '主服务金额',

            value: `¥${formatAmount(orderServiceAmount.value)}`
        }
    ]

    if (Number(order.value?.addon_amount || 0) > 0) {
        rows.push({
            label: '附加内容金额',

            value: `+¥${formatAmount(order.value?.addon_amount || 0)}`
        })
    }

    if (Number(order.value?.discount_amount || 0) > 0) {
        rows.push({
            label: '优惠金额',

            value: `-¥${formatAmount(order.value?.discount_amount || 0)}`,

            danger: true
        })
    }

    rows.push({
        label: '实付金额',

        value: `¥${formatAmount(order.value?.pay_amount || 0)}`,

        total: true
    })

    return rows
})

const contactRows = computed<InfoRow[]>(() => {
    const rows: InfoRow[] = [
        {
            label: '联系人',

            value: String(order.value?.contact_name || '未填写')
        },

        {
            label: '联系电话',

            value: String(order.value?.contact_mobile || '未填写')
        }
    ]

    const region = String(order.value?.service_region_text || '').trim()

    if (region) {
        rows.push({
            label: '服务地区',

            value: region
        })
    }

    const address = String(order.value?.service_address || '').trim()

    if (address) {
        rows.push({
            label: '详细地址',

            value: address,

            multiline: true
        })
    }

    return rows
})

const contactMeta = computed(() =>
    String(order.value?.contact_name || order.value?.contact_mobile || '待补充')
)

const orderInfoRows = computed<InfoRow[]>(() => {
    const rows: InfoRow[] = [
        {
            label: '订单编号',

            value: String(order.value?.order_sn || '-')
        },

        {
            label: '下单时间',

            value: String(order.value?.create_time || '-')
        },

        {
            label: '订单状态',

            value: String(order.value?.order_status_desc || '-')
        },

        {
            label: '支付状态',

            value: String(order.value?.pay_status_desc || '-')
        },

        {
            label: '支付方式',

            value: String(order.value?.pay_type_desc || '-')
        }
    ]

    if (confirmRemainText.value) {
        rows.splice(3, 0, {
            label: '剩余确认时间',

            value: confirmRemainText.value
        })
    }

    if (confirmTimeoutActionText.value) {
        rows.splice(confirmRemainText.value ? 4 : 3, 0, {
            label: '超时处理',

            value: confirmTimeoutActionText.value
        })
    }

    if (payRemainText.value) {
        rows.push({
            label: '剩余支付时间',

            value: payRemainText.value
        })
    }

    if (payTimeoutActionText.value) {
        rows.push({
            label: '支付超时处理',

            value: payTimeoutActionText.value
        })
    }

    if (order.value?.pay_time) {
        rows.splice(2, 0, {
            label: '支付时间',

            value: String(order.value?.pay_time || '-')
        })
    }

    return rows
})

const primaryActionVisible = computed(() => {
    const hasPending = (order.value?.items || []).some(
        (item: any) => Number(item?.confirm_status ?? 0) === 0
    )

    const status = Number(order.value?.order_status ?? -1)

    if (status === 0 && hasPending) {
        return true
    }

    if (status === 2) {
        return Number(order.value?.can_staff_start || 0) === 1
    }

    return status === 3 && Number(order.value?.can_staff_complete || 0) === 1
})

const secondaryActionVisible = computed(() => Boolean(order.value))

const primaryActionText = computed(() => {
    const status = Number(order.value?.order_status ?? -1)

    if (status === 3) return '完成服务'

    if (status === 2) return '开始履约'

    return '确认订单'
})

const clearConfirmCountdown = () => {
    if (confirmCountdownTimer) {
        clearInterval(confirmCountdownTimer)

        confirmCountdownTimer = null
    }
}

const clearPayCountdown = () => {
    if (payCountdownTimer) {
        clearInterval(payCountdownTimer)

        payCountdownTimer = null
    }
}

const syncConfirmCountdown = (seconds: number | string) => {
    clearConfirmCountdown()

    confirmCountdownSeconds.value = Math.max(Number(seconds || 0), 0)

    if (
        Number(order.value?.order_status ?? -1) !== 0 ||
        Number(order.value?.confirm_deadline_time || 0) <= 0 ||
        confirmCountdownSeconds.value <= 0
    ) {
        return
    }

    confirmCountdownTimer = setInterval(async () => {
        if (confirmCountdownSeconds.value > 0) {
            confirmCountdownSeconds.value -= 1
        }

        if (confirmCountdownSeconds.value <= 0) {
            clearConfirmCountdown()

            if (confirmCountdownRefreshing) return

            confirmCountdownRefreshing = true

            try {
                await fetchDetail(Number(order.value?.id || 0))
            } finally {
                confirmCountdownRefreshing = false
            }
        }
    }, 1000)
}

const syncPayCountdown = (seconds: number | string) => {
    clearPayCountdown()

    payCountdownSeconds.value = Math.max(Number(seconds || 0), 0)

    if (
        Number(order.value?.order_status ?? -1) !== 1 ||
        Number(order.value?.pay_deadline_time || 0) <= 0 ||
        payCountdownSeconds.value <= 0
    ) {
        return
    }

    payCountdownTimer = setInterval(async () => {
        if (payCountdownSeconds.value > 0) {
            payCountdownSeconds.value -= 1
        }

        if (payCountdownSeconds.value <= 0) {
            clearPayCountdown()

            if (payCountdownRefreshing) return

            payCountdownRefreshing = true

            try {
                await fetchDetail(Number(order.value?.id || 0))
            } finally {
                payCountdownRefreshing = false
            }
        }
    }, 1000)
}

const fetchDetail = async (id: number) => {
    if (id <= 0) return

    if (detailRequestPromise) return detailRequestPromise

    detailRequestPromise = (async () => {
        try {
            const res: any = await staffCenterOrderDetail({ id })

            order.value = res || null

            currentOrderId = Number(order.value?.id || id)

            await loadConfirmLetter()

            syncConfirmCountdown(order.value?.confirm_remain_seconds || 0)

            syncPayCountdown(order.value?.pay_remain_seconds || 0)

            hasLoadedOnce = true
        } catch (error: any) {
            clearConfirmCountdown()

            clearPayCountdown()

            const msg =
                typeof error === 'string' ? error : error?.msg || error?.message || '获取订单失败'

            uni.showToast({ title: msg, icon: 'none' })
        } finally {
            detailRequestPromise = null
        }
    })()

    return detailRequestPromise
}

const openConfirmLetterActions = () => {
    const itemList = ['生成确认函', '查看确认函']

    if (confirmLetterHistory.value.length > 1) {
        itemList.push('切换版本')
    }

    itemList.push('推送给客户', '保存图片')

    uni.showActionSheet({
        itemList,

        success: ({ tapIndex }) => {
            if (tapIndex === 0) handleGenerateLetter()

            if (tapIndex === 1) handlePreviewLetter()

            if (itemList[tapIndex] === '切换版本') handleSelectConfirmLetterVersion()

            if (itemList[tapIndex] === '推送给客户') handlePushLetter()

            if (itemList[tapIndex] === '保存图片') handleSaveLetter()
        }
    })
}

const copyOrderSn = () => {
    const orderSn = String(order.value?.order_sn || '').trim()

    if (!orderSn) {
        uni.showToast({ title: '订单编号为空', icon: 'none' })

        return
    }

    uni.setClipboardData({
        data: orderSn,

        success: () => {
            uni.showToast({ title: '已复制订单编号', icon: 'success' })
        }
    })
}

const handleContactCustomer = () => {
    const mobile = String(order.value?.contact_mobile || '').trim()

    if (!mobile) {
        uni.showToast({ title: '客户未留下联系电话', icon: 'none' })

        return
    }

    uni.makePhoneCall({
        phoneNumber: mobile
    })
}

const loadConfirmLetter = async (targetLetterId = 0) => {
    const currentOrderId = Number(order.value?.id || 0)

    if (!currentOrderId) {
        confirmLetter.value = null

        confirmLetterHistory.value = []

        return
    }

    try {
        const history: any = await staffCenterOrderConfirmLetterHistory({
            order_id: currentOrderId
        })

        confirmLetterHistory.value = Array.isArray(history) ? history : []

        const selectedLetterId =
            targetLetterId > 0
                ? targetLetterId
                : Number(confirmLetter.value?.letter_id || 0) ||
                  Number(confirmLetterHistory.value[0]?.letter_id || 0)

        if (!selectedLetterId) {
            confirmLetter.value = null

            return
        }

        confirmLetter.value = await staffCenterOrderConfirmLetterDetail({
            letter_id: selectedLetterId
        })
    } catch {
        confirmLetter.value = null

        confirmLetterHistory.value = []
    }
}

const handleSelectConfirmLetterVersion = () => {
    if (!confirmLetterHistory.value.length) {
        uni.showToast({ title: '暂无确认函版本记录', icon: 'none' })

        return
    }

    uni.showActionSheet({
        itemList: confirmLetterHistory.value.map((item) => {
            const tags = [
                item?.is_current ? '当前' : '',

                item?.is_pushed ? '已推送' : '未推送'
            ].filter(Boolean)

            return `v${item?.version || 0}${tags.length ? `（${tags.join('·')}）` : ''}`
        }),

        success: async ({ tapIndex }) => {
            const target = confirmLetterHistory.value[tapIndex]

            if (!target?.letter_id) {
                return
            }

            try {
                await loadConfirmLetter(Number(target.letter_id || 0))

                uni.showToast({ title: `已切换到 v${target.version || 0}`, icon: 'none' })
            } catch (error: any) {
                uni.showToast({ title: error?.message || '切换版本失败', icon: 'none' })
            }
        }
    })
}

const handleGenerateLetter = async () => {
    try {
        await staffCenterOrderConfirmLetterGenerate({ order_id: Number(order.value?.id || 0) })

        await loadConfirmLetter()

        uni.showToast({ title: '确认函已生成', icon: 'success' })
    } catch (error: any) {
        uni.showToast({ title: error?.msg || error?.message || '生成失败', icon: 'none' })
    }
}

const handlePushLetter = async () => {
    if (!confirmLetter.value?.letter_id) {
        uni.showToast({ title: '请先生成确认函', icon: 'none' })

        return
    }

    try {
        await staffCenterOrderConfirmLetterPush({ letter_id: confirmLetter.value.letter_id })

        uni.showToast({ title: '推送成功', icon: 'success' })

        await loadConfirmLetter()
    } catch (error: any) {
        uni.showToast({ title: error?.msg || error?.message || '推送失败', icon: 'none' })
    }
}

const getConfirmLetterBitmapSrc = (letter: any) => {
    const fullImageUrl = String(letter?.full_image_url || '').trim()
    return isOrderConfirmLetterBitmapAssetUrl(fullImageUrl) ? fullImageUrl : ''
}

const getConfirmLetterPreviewSrc = (letter: any) =>
    getConfirmLetterBitmapSrc(letter)

const handlePreviewLetter = async () => {
    const imageUrl = getConfirmLetterPreviewSrc(confirmLetter.value)

    if (!imageUrl) {
        uni.showToast({ title: '确认函图片暂未生成', icon: 'none' })

        return
    }

    uni.previewImage({ urls: [imageUrl], current: imageUrl })
}

const handleSaveLetter = async () => {
    const imageUrl = getConfirmLetterBitmapSrc(confirmLetter.value)

    if (!imageUrl) {
        uni.showToast({ title: '确认函图片暂未生成', icon: 'none' })

        return
    }

    saveImageToPhotosAlbum(imageUrl)
}

const handleConfirm = () => {
    if (!order.value?.id) return

    const status = Number(order.value?.order_status ?? -1)

    if (status === 3) {
        uni.showModal({
            title: '完成服务',

            content: '确认本单服务已完成吗？',

            success: async (res) => {
                if (!res.confirm) return

                try {
                    await staffCenterOrderComplete({ id: order.value.id })

                    await fetchDetail(order.value.id)

                    const successText =
                        Number(order.value?.order_status || 0) === 1
                            ? '服务已完成，待支付尾款'
                            : '订单已完成'

                    uni.showToast({ title: successText, icon: 'success' })
                } catch (error: any) {
                    const msg =
                        typeof error === 'string'
                            ? error
                            : error?.msg || error?.message || '操作失败'

                    uni.showToast({ title: msg, icon: 'none' })
                }
            }
        })

        return
    }

    if (status === 2) {
        uni.showModal({
            title: '开始履约',

            content: '确认本单已开始履约吗？',

            success: async (res) => {
                if (!res.confirm) return

                try {
                    await staffCenterOrderStartService({ id: order.value.id })

                    await fetchDetail(order.value.id)

                    uni.showToast({ title: '开始履约成功', icon: 'success' })
                } catch (error: any) {
                    const msg =
                        typeof error === 'string'
                            ? error
                            : error?.msg || error?.message || '操作失败'

                    uni.showToast({ title: msg, icon: 'none' })
                }
            }
        })

        return
    }

    uni.showModal({
        title: '确认订单',

        content: '确认后客户可进行支付，是否继续？',

        success: async (res) => {
            if (!res.confirm) return

            try {
                await staffCenterOrderConfirm({ id: order.value.id })

                uni.showToast({ title: '确认成功', icon: 'success' })

                await fetchDetail(order.value.id)
            } catch (error: any) {
                const msg =
                    typeof error === 'string' ? error : error?.msg || error?.message || '确认失败'

                uni.showToast({ title: msg, icon: 'none' })
            }
        }
    })
}

onLoad(async (options: any) => {
    $theme.setScene('staff')

    if (!(await ensureStaffCenterAccess())) return

    const id = Number(options?.id || 0)

    if (!id) {
        uni.showToast({ title: '订单不存在', icon: 'none' })

        setTimeout(() => {
            uni.navigateBack()
        }, 1500)

        return
    }

    currentOrderId = id

    hasLoadedOnce = false

    hasBeenHidden = false

    detailRequestPromise = null

    void fetchDetail(id)
})

onShow(() => {
    if (!hasLoadedOnce || !hasBeenHidden || currentOrderId <= 0) {
        return
    }

    hasBeenHidden = false

    void fetchDetail(currentOrderId)
})

onHide(() => {
    hasBeenHidden = hasLoadedOnce

    clearConfirmCountdown()

    clearPayCountdown()
})

onUnload(() => {
    detailRequestPromise = null

    currentOrderId = 0

    hasLoadedOnce = false

    hasBeenHidden = false

    clearConfirmCountdown()

    clearPayCountdown()
})
</script>

<style lang="scss" scoped>
.staff-order-detail {
    padding-bottom: var(--wm-safe-bottom-action, calc(env(safe-area-inset-bottom) + 150rpx));

    background: var(--wm-color-page, #fcfbf9);

    &__content {
        display: flex;

        flex-direction: column;

        gap: 15rpx;

        padding: 12rpx var(--wm-space-page-x, 37rpx) 37rpx;
    }
}

.staff-order-hero,
.staff-section-card {
    position: relative;

    overflow: hidden;

    box-sizing: border-box;
}

.staff-order-hero {
    display: flex;

    flex-direction: column;

    gap: 20rpx;

    padding: 30rpx 30rpx 34rpx;

    border-radius: 49rpx;

    border: 1rpx solid var(--wm-color-border-strong, #f4c7bf);

    background: linear-gradient(135deg, #fff6f2 0%, #fde8e1 100%);

    box-shadow: 0 20rpx 44rpx rgba(192, 130, 115, 0.16);

    &--warning {
        background: linear-gradient(135deg, #fff7eb 0%, #fff1d7 100%);

        border-color: rgba(201, 155, 115, 0.38);
    }

    &--success {
        background: linear-gradient(135deg, #f2fbf6 0%, #e4f6ec 100%);

        border-color: rgba(47, 125, 88, 0.22);
    }

    &--danger {
        background: linear-gradient(135deg, #fff3f1 0%, #fbe3df 100%);

        border-color: rgba(180, 74, 58, 0.24);
    }

    &--neutral {
        background: linear-gradient(135deg, #f7f4f2 0%, #f2ebe7 100%);

        border-color: rgba(127, 123, 120, 0.24);
    }

    &__top,
    &__copy {
        display: flex;

        flex-direction: column;
    }

    &__top {
        flex-direction: row;

        align-items: center;

        justify-content: space-between;

        gap: 16rpx;
    }

    &__copy {
        gap: 8rpx;
    }

    &__title {
        font-size: 40rpx;

        font-weight: 700;

        line-height: 1.32;

        color: var(--wm-text-primary, #1e2432);
    }

    &__desc {
        font-size: 24rpx;

        font-weight: 600;

        line-height: 1.6;

        color: var(--wm-text-secondary, #7f7b78);
    }
}

.hero-pill {
    display: inline-flex;

    align-items: center;

    justify-content: center;

    min-height: 42rpx;

    padding: 11rpx 18rpx;

    border-radius: 999rpx;

    box-sizing: border-box;

    &__text {
        font-size: 22rpx;

        font-weight: 700;

        line-height: 1;
    }

    &--primary {
        background: #fff1ee;

        .hero-pill__text {
            color: var(--wm-color-primary, #e85a4f);
        }
    }

    &--warning {
        background: #fff8ed;

        .hero-pill__text {
            color: #c98524;
        }
    }

    &--success {
        background: rgba(47, 125, 88, 0.12);

        .hero-pill__text {
            color: #2f7d58;
        }
    }

    &--danger {
        background: rgba(180, 74, 58, 0.12);

        .hero-pill__text {
            color: #b44a3a;
        }
    }

    &--neutral {
        background: rgba(96, 112, 134, 0.12);

        .hero-pill__text {
            color: #607086;
        }
    }

    &--ghost {
        background: rgba(255, 255, 255, 0.78);

        border: 1rpx solid rgba(255, 255, 255, 0.88);

        .hero-pill__text {
            color: var(--wm-text-secondary, #7f7b78);
        }
    }
}

.hero-chip-list {
    display: flex;

    flex-wrap: wrap;

    gap: 12rpx;
}

.hero-chip {
    min-width: 168rpx;

    flex: 1;

    display: flex;

    flex-direction: column;

    gap: 6rpx;

    padding: 15rpx 18rpx;

    border-radius: 30rpx;

    border: 1rpx solid rgba(255, 255, 255, 0.82);

    background: rgba(255, 255, 255, 0.66);

    box-sizing: border-box;

    &__label {
        font-size: 21rpx;

        font-weight: 700;

        line-height: 1.35;

        color: var(--wm-text-secondary, #7f7b78);
    }

    &__value {
        font-size: 28rpx;

        font-weight: 700;

        line-height: 1.35;

        color: var(--wm-text-primary, #1e2432);

        word-break: break-all;
    }
}

.staff-section-card {
    display: flex;

    flex-direction: column;

    gap: 15rpx;

    padding: 26rpx 30rpx;

    border-radius: 45rpx;

    border: 1rpx solid var(--wm-color-border, #efe6e1);

    background: rgba(255, 255, 255, 0.92);

    box-shadow: var(--wm-shadow-card, 0 18rpx 36rpx rgba(214, 185, 167, 0.2));

    backdrop-filter: blur(24rpx);

    -webkit-backdrop-filter: blur(24rpx);
}

.section-head {
    display: flex;

    align-items: flex-start;

    justify-content: space-between;

    gap: 18rpx;

    &__copy {
        flex: 1;

        min-width: 0;

        display: flex;

        flex-direction: column;

        gap: 4rpx;
    }

    &__title {
        font-size: 32rpx;

        font-weight: 700;

        line-height: 1.35;

        color: var(--wm-text-primary, #1e2432);
    }

    &__meta {
        flex-shrink: 0;

        padding-top: 4rpx;

        font-size: 22rpx;

        font-weight: 700;

        line-height: 1.35;

        color: var(--wm-text-secondary, #7f7b78);
    }
}

.section-main-text {
    font-size: 30rpx;

    font-weight: 700;

    line-height: 1.5;

    color: var(--wm-text-primary, #1e2432);
}

.section-sub-text {
    font-size: 24rpx;

    line-height: 1.65;

    color: var(--wm-text-secondary, #7f7b78);
}

.section-note {
    font-size: 22rpx;

    font-weight: 600;

    line-height: 1.65;

    color: var(--wm-text-secondary, #7f7b78);
}

.section-link {
    padding-top: 4rpx;

    font-size: 24rpx;

    font-weight: 700;

    line-height: 1.35;

    color: var(--wm-color-primary, #e85a4f);
}

.tag-list {
    display: flex;

    flex-wrap: wrap;

    gap: 12rpx;
}

.tag-list__item {
    min-height: 46rpx;

    padding: 0 18rpx;

    border-radius: 999rpx;

    display: inline-flex;

    align-items: center;

    background: rgba(255, 241, 238, 0.92);

    border: 1rpx solid var(--wm-color-border-strong, #f4c7bf);
}

.tag-list__text {
    font-size: 22rpx;

    font-weight: 600;

    color: var(--wm-color-primary, #e85a4f);
}

.sub-panel {
    display: flex;

    flex-direction: column;
}

.sub-panel__row {
    display: flex;

    align-items: flex-start;

    justify-content: space-between;

    gap: 24rpx;

    padding: 18rpx 0;

    & + & {
        border-top: 1rpx solid var(--wm-color-border, #efe6e1);
    }

    &--stack {
        flex-direction: column;

        gap: 12rpx;
    }

    &--total {
        padding-top: 24rpx;
    }
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

    word-break: break-all;

    &--left {
        text-align: left;
    }

    &--danger {
        color: #b44a3a;
    }

    &--total {
        font-size: 32rpx;

        font-weight: 700;

        color: var(--wm-color-primary, #e85a4f);
    }
}

.service-list {
    display: flex;

    flex-direction: column;

    gap: 12rpx;
}

.service-card {
    display: flex;

    flex-direction: column;

    gap: 12rpx;

    padding: 22rpx;

    border-radius: 30rpx;

    border: 1rpx solid var(--wm-color-border, #efe6e1);

    background: #fcfbf9;

    &__row {
        display: flex;

        justify-content: space-between;

        gap: 12rpx;

        &--start {
            align-items: flex-start;
        }

        &--center {
            align-items: center;
        }
    }

    &__copy {
        flex: 1;

        min-width: 0;

        display: flex;

        flex-direction: column;

        gap: 8rpx;
    }

    &__title-row {
        display: flex;

        align-items: center;

        gap: 10rpx;

        flex-wrap: wrap;
    }

    &__title {
        flex: 1;

        min-width: 0;

        font-size: 28rpx;

        font-weight: 700;

        line-height: 1.35;

        color: var(--wm-text-primary, #1e2432);
    }

    &__desc {
        font-size: 24rpx;

        line-height: 1.65;

        color: var(--wm-text-secondary, #7f7b78);

        white-space: pre-wrap;

        word-break: break-word;
    }

    &__price {
        flex-shrink: 0;

        font-size: 30rpx;

        font-weight: 700;

        line-height: 1.3;

        color: var(--wm-color-primary, #e85a4f);
    }

    &__meta {
        font-size: 22rpx;

        font-weight: 600;

        line-height: 1.45;

        color: var(--wm-text-secondary, #7f7b78);
    }
}

.status-pill {
    display: inline-flex;

    align-items: center;

    justify-content: center;

    min-height: 38rpx;

    padding: 8rpx 14rpx;

    border-radius: 999rpx;

    box-sizing: border-box;

    &__text {
        font-size: 20rpx;

        font-weight: 700;

        line-height: 1;

        white-space: nowrap;
    }

    &--primary {
        background: #fff1ee;

        .status-pill__text {
            color: var(--wm-color-primary, #e85a4f);
        }
    }

    &--warning {
        background: #fff8ed;

        .status-pill__text {
            color: #c99b73;
        }
    }

    &--success {
        background: rgba(47, 125, 88, 0.12);

        .status-pill__text {
            color: #2f7d58;
        }
    }

    &--danger {
        background: rgba(180, 74, 58, 0.12);

        .status-pill__text {
            color: #b44a3a;
        }
    }

    &--neutral {
        background: rgba(96, 112, 134, 0.12);

        .status-pill__text {
            color: #607086;
        }
    }
}

.addon-box {
    display: flex;

    flex-direction: column;

    gap: 10rpx;

    padding: 18rpx;

    border-radius: 24rpx;

    background: rgba(255, 241, 238, 0.56);

    &__header,
    &__row {
        display: flex;

        align-items: center;

        justify-content: space-between;

        gap: 12rpx;
    }

    &__title {
        font-size: 24rpx;

        font-weight: 700;

        color: var(--wm-text-primary, #1e2432);
    }

    &__total,
    &__price {
        font-size: 24rpx;

        font-weight: 700;

        color: var(--wm-color-primary, #e85a4f);
    }

    &__name {
        font-size: 22rpx;

        line-height: 1.45;

        color: var(--wm-text-secondary, #7f7b78);
    }
}

.empty-state {
    min-height: 160rpx;

    border-radius: 30rpx;

    display: flex;

    align-items: center;

    justify-content: center;

    background: #fcfbf9;

    border: 1rpx solid var(--wm-color-border, #efe6e1);
}

.empty-state__text {
    font-size: 24rpx;

    color: var(--wm-text-tertiary, #b4aca8);
}

.action-bar {
    width: 100%;
}

.action-bar__buttons {
    display: flex;

    gap: 20rpx;
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

    &--primary {
        color: #ffffff;

        background: linear-gradient(
            135deg,
            var(--wm-color-primary, #e85a4f) 0%,

            var(--wm-color-primary, #e85a4f) 100%
        );

        box-shadow: 0 12rpx 24rpx rgba(232, 90, 79, 0.16);
    }

    &--secondary {
        color: var(--wm-text-primary, #1e2432);

        background: rgba(255, 255, 255, 0.88);

        border: 1rpx solid var(--wm-color-border, #efe6e1);
    }
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
