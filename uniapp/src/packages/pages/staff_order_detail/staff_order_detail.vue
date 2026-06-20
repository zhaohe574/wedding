<template>
    <page-meta :page-style="$theme.pageStyle" />

    <PageShell scene="staff" tone="workspace" hasSafeBottom>
        <BaseNavbar
            title="订单详情"
            title-align="center"
            variant="solid"
            bg-color="#191713"
            text-color="#FFFDF8"
        />

        <view v-if="order" class="staff-order-detail">
            <view class="staff-order-detail__content">
                <BaseCard
                    variant="hero"
                    scene="staff"
                    class="detail-hero"
                    background="linear-gradient(145deg, #2B261D 0%, #191713 62%, #3A2A16 100%)"
                    border="1rpx solid #D9BE82"
                    box-shadow="0 28rpx 68rpx rgba(74, 43, 24, 0.18)"
                >
                    <view class="detail-hero__top">
                        <StatusBadge :tone="statusInfo.badgeModifier as BadgeTone" size="sm">
                            {{ statusInfo.badgeText }}
                        </StatusBadge>

                        <text v-if="heroAlertText" class="detail-hero__alert-mini">
                            {{ heroAlertText }}
                        </text>
                    </view>

                    <view class="detail-hero__body">
                        <text class="detail-hero__title">{{ statusInfo.title }}</text>
                    </view>

                    <view class="detail-hero__facts">
                        <view class="detail-fact">
                            <text class="detail-fact__label">日期</text>
                            <text class="detail-fact__value">{{ serviceDateSummary }}</text>
                        </view>
                        <view class="detail-fact">
                            <text class="detail-fact__label">服务</text>
                            <text class="detail-fact__value">{{ serviceItemsMeta }}</text>
                        </view>
                    </view>
                </BaseCard>

                <BaseCard variant="panel" scene="staff" class="detail-section">
                    <view class="detail-section__head">
                        <text class="detail-section__title">服务明细</text>
                        <text class="detail-section__meta">{{ serviceItemsMeta }}</text>
                    </view>

                    <view v-if="serviceCards.length" class="service-list">
                        <view
                            v-for="item in serviceCards"
                            :key="item.id"
                            class="service-card wm-soft-card"
                        >
                            <view class="service-card__head">
                                <view class="service-card__copy">
                                    <view class="service-card__title-row">
                                        <text class="service-card__title">{{ item.title }}</text>

                                        <StatusBadge v-if="item.typeText" tone="info" size="sm">
                                            {{ item.typeText }}
                                        </StatusBadge>
                                    </view>
                                </view>

                                <text class="service-card__price">{{ item.priceText }}</text>
                            </view>

                            <view class="service-card__meta-row">
                                <text class="service-card__meta">{{ item.dateText }}</text>

                                <StatusBadge :tone="item.statusModifier as BadgeTone" size="sm">
                                    {{ item.statusText }}
                                </StatusBadge>
                            </view>

                            <view class="service-card__meta-row">
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
                    />
                </BaseCard>

                <BaseCard variant="panel" scene="staff" class="detail-section">
                    <view class="detail-section__head">
                        <text class="detail-section__title">金额</text>
                    </view>

                    <view class="detail-info-list">
                        <BaseInfoRow
                            v-for="item in amountRows"
                            :key="item.label"
                            :label="item.label"
                            :value="item.value"
                            :tone="item.total ? 'price' : item.danger ? 'danger' : 'default'"
                        />
                    </view>
                </BaseCard>

                <BaseCard variant="panel" scene="staff" class="detail-section">
                    <view class="detail-section__head">
                        <text class="detail-section__title">履约联系</text>
                    </view>

                    <view class="detail-info-list">
                        <BaseInfoRow
                            v-for="item in contactRows"
                            :key="item.label"
                            :label="item.label"
                            :value="item.value"
                        />

                        <view v-if="detailAddress" class="detail-address-row">
                            <text class="detail-address-row__label">详细地址</text>
                            <text class="detail-address-row__value">{{ detailAddress }}</text>
                        </view>
                    </view>
                </BaseCard>

                <BaseCard variant="panel" scene="staff" class="detail-section">
                    <view class="detail-section__head">
                        <text class="detail-section__title">档期确认函</text>
                        <text class="detail-section__action" @click="openConfirmLetterActions">
                            更多
                        </text>
                    </view>

                    <view class="detail-info-list">
                        <BaseInfoRow
                            label="当前版本"
                            :value="confirmLetter?.version ? `v${confirmLetter.version}` : '未生成'"
                        />
                        <BaseInfoRow
                            label="海报模板"
                            :value="confirmLetter?.config_name || '-'"
                        />
                        <BaseInfoRow
                            label="历史海报"
                            :value="`${confirmLetterHistory.length || 0} 个版本`"
                        />
                        <BaseInfoRow
                            label="确认日期"
                            :value="confirmLetter?.rendered_snapshot?.confirm_date || '-'"
                        />
                    </view>
                </BaseCard>

                <BaseCard variant="panel" scene="staff" class="detail-section">
                    <view class="detail-section__head">
                        <text class="detail-section__title">订单信息</text>
                        <text class="detail-section__action" @click="copyOrderSn">复制编号</text>
                    </view>

                    <view class="detail-info-list">
                        <BaseInfoRow
                            v-for="item in orderInfoRows"
                            :key="item.label"
                            :label="item.label"
                            :value="item.value"
                        />
                    </view>
                </BaseCard>
            </view>

            <ActionArea
                v-if="primaryActionVisible || secondaryActionVisible"
                sticky
                safeBottom
                layout="split"
            >
                <view class="action-bar">
                    <view class="action-bar__buttons">
                        <BaseButton
                            v-if="secondaryActionVisible"
                            variant="light"
                            size="md"
                            height="84rpx"
                            label="联系客户"
                            @click="handleContactCustomer"
                        />

                        <BaseButton
                            v-if="primaryActionVisible"
                            variant="dark"
                            size="md"
                            height="84rpx"
                            :label="primaryActionText"
                            @click="handleConfirm"
                        />
                    </view>
                </view>
            </ActionArea>
        </view>

        <view v-else class="loading-container">
            <LoadingState text="订单加载中" />
        </view>
    </PageShell>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue'

import { onHide, onLoad, onShow, onUnload } from '@dcloudio/uni-app'

import PageShell from '@/components/base/PageShell.vue'

import BaseButton from '@/components/base/BaseButton.vue'

import BaseCard from '@/components/base/BaseCard.vue'

import BaseInfoRow from '@/components/base/BaseInfoRow.vue'

import BaseNavbar from '@/components/base/BaseNavbar.vue'

import ActionArea from '@/components/base/ActionArea.vue'

import EmptyState from '@/components/base/EmptyState.vue'

import LoadingState from '@/components/base/LoadingState.vue'

import StatusBadge from '@/components/base/StatusBadge.vue'

import {
    staffCenterOrderComplete,
    staffCenterOrderDetail,
    staffCenterOrderConfirm,
    staffCenterOrderStartService,
    staffCenterScheduleConfirmLetterConfig,
    staffCenterScheduleConfirmLetterDetail,
    staffCenterScheduleConfirmLetterGenerate,
    staffCenterScheduleConfirmLetterHistory
} from '@/api/staffCenter'

import { isOrderConfirmLetterBitmapAssetUrl } from '@/utils/orderConfirmLetterRenderer'

import { ensureStaffCenterAccess } from '@/packages/common/utils/staff-center'

import { saveImageToPhotosAlbum } from '@/packages/common/utils/file'

import { useThemeStore } from '@/stores/theme'
import { confirmModal, showError, showSuccess } from '@/utils/feedback'

type BadgeTone = 'neutral' | 'success' | 'warning' | 'danger' | 'info' | 'primary'

interface InfoRow {
    label: string

    value: string
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
}

const $theme = useThemeStore()

const order = ref<any>(null)

const confirmLetter = ref<any>(null)

const confirmLetterHistory = ref<any[]>([])

const confirmLetterTemplates = ref<any[]>([])

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

        title: '等待你确认服务安排'
    },

    pending_pay: {
        badgeModifier: 'warning',

        tone: 'warning',

        title: '订单已确认，等待客户支付'
    },

    paid: {
        badgeModifier: 'success',

        tone: 'success',

        title: '订单待服务，可开始履约'
    },

    in_service: {
        badgeModifier: 'success',

        tone: 'success',

        title: '订单正在服务中'
    },

    completed: {
        badgeModifier: 'success',

        tone: 'success',

        title: '订单服务已完成'
    },

    reviewed: {
        badgeModifier: 'success',

        tone: 'success',

        title: '订单已闭环并收到评价'
    },

    cancelled: {
        badgeModifier: 'neutral',

        tone: 'neutral',

        title: '订单已取消'
    },

    paused: {
        badgeModifier: 'warning',

        tone: 'warning',

        title: '订单已暂停'
    },

    refunding: {
        badgeModifier: 'warning',

        tone: 'warning',

        title: '订单退款处理中'
    },

    refunded: {
        badgeModifier: 'danger',

        tone: 'danger',

        title: '订单已退款'
    },

    user_deleted: {
        badgeModifier: 'danger',

        tone: 'danger',

        title: '订单已被用户删除'
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

const heroAlertText = computed(() => {
    if (confirmRemainText.value) return confirmRemainText.value
    if (confirmTimeoutActionText.value) return confirmTimeoutActionText.value
    if (payRemainText.value) return payRemainText.value
    if (payTimeoutActionText.value) return payTimeoutActionText.value
    return ''
})

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
        label: '应付金额',
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

    return rows
})

const detailAddress = computed(() => String(order.value?.service_address || '').trim())

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

            showError(msg)
        } finally {
            detailRequestPromise = null
        }
    })()

    return detailRequestPromise
}

const openConfirmLetterActions = () => {
    const itemList = ['生成海报', '预览海报']

    if (confirmLetterHistory.value.length > 1) {
        itemList.push('切换历史海报')
    }

    itemList.push('保存图片', '去配置')

    uni.showActionSheet({
        itemList,

        success: ({ tapIndex }) => {
            if (tapIndex === 0) handleGenerateLetter()

            if (tapIndex === 1) handlePreviewLetter()

            if (itemList[tapIndex] === '切换历史海报') handleSelectConfirmLetterVersion()

            if (itemList[tapIndex] === '保存图片') handleSaveLetter()

            if (itemList[tapIndex] === '去配置') goConfirmLetterConfig()
        }
    })
}

const copyOrderSn = () => {
    const orderSn = String(order.value?.order_sn || '').trim()

    if (!orderSn) {
        showError('订单编号为空')

        return
    }

    uni.setClipboardData({
        data: orderSn,

        success: () => {
            showSuccess('已复制订单编号')
        }
    })
}

const handleContactCustomer = () => {
    const mobile = String(order.value?.contact_mobile || '').trim()

    if (!mobile) {
        showError('客户未留下联系电话')

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
        const history: any = await staffCenterScheduleConfirmLetterHistory({
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

        confirmLetter.value = await staffCenterScheduleConfirmLetterDetail({
            letter_id: selectedLetterId
        })
    } catch {
        confirmLetter.value = null

        confirmLetterHistory.value = []
    }
}

const formatTemplateActionLabel = (item: any) => {
    const name = String(item?.template_name || '未命名模板').trim()
    const tags = [
        `模板 v${item?.template_version || 1}`,
        Number(item?.is_default) === 1 ? '默认' : ''
    ].filter(Boolean)
    return `${name}（${tags.join('·')}）`
}

const loadConfirmLetterTemplates = async () => {
    const data: any = await staffCenterScheduleConfirmLetterConfig()
    const list = Array.isArray(data?.versions) ? data.versions : []
    confirmLetterTemplates.value = list
        .filter((item: any) => Number(item?.status ?? 1) === 1 && Number(item?.config_id || 0) > 0)
        .sort((a: any, b: any) => {
            const defaultDiff = Number(b?.is_default || 0) - Number(a?.is_default || 0)
            if (defaultDiff !== 0) return defaultDiff
            const sortDiff = Number(b?.sort || 0) - Number(a?.sort || 0)
            if (sortDiff !== 0) return sortDiff
            return Number(a?.config_id || 0) - Number(b?.config_id || 0)
        })
    return confirmLetterTemplates.value
}

const selectConfirmLetterTemplate = async () => {
    const templates = await loadConfirmLetterTemplates()
    if (!templates.length) {
        showError('暂无可用海报模板')
        return null
    }

    return await new Promise<any>((resolve, reject) => {
        uni.showActionSheet({
            itemList: templates.map(formatTemplateActionLabel),
            success: ({ tapIndex }) => {
                resolve(templates[tapIndex] || null)
            },
            fail: reject
        })
    })
}

const handleSelectConfirmLetterVersion = () => {
    if (!confirmLetterHistory.value.length) {
        showError('暂无历史海报记录')

        return
    }

    uni.showActionSheet({
        itemList: confirmLetterHistory.value.map((item) => {
            const tags = [
                item?.is_current ? '当前' : '',

                item?.is_current ? '有效' : '历史'
            ].filter(Boolean)

            const templateName = item?.config_name ? ` · ${item.config_name}` : ''
            return `生成 v${item?.version || 0}${templateName}${tags.length ? `（${tags.join('·')}）` : ''}`
        }),

        success: async ({ tapIndex }) => {
            const target = confirmLetterHistory.value[tapIndex]

            if (!target?.letter_id) {
                return
            }

            try {
                await loadConfirmLetter(Number(target.letter_id || 0))

                showSuccess(`已切换到历史海报 v${target.version || 0}`)
            } catch (error: any) {
                showError(error, '切换历史海报失败')
            }
        }
    })
}

const handleGenerateLetter = async () => {
    try {
        const template = await selectConfirmLetterTemplate()
        if (!template) {
            return
        }

        await staffCenterScheduleConfirmLetterGenerate({
            order_id: Number(order.value?.id || 0),
            config_id: Number(template.config_id || 0)
        })

        await loadConfirmLetter()

        showSuccess(`海报已生成：${template.template_name || '默认海报'}`)
    } catch (error: any) {
        if (String(error?.errMsg || '').includes('cancel')) {
            return
        }
        showError(error, '生成失败')
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
        showError('档期确认函海报暂未生成')

        return
    }

    uni.previewImage({ urls: [imageUrl], current: imageUrl })
}

const handleSaveLetter = async () => {
    const imageUrl = getConfirmLetterBitmapSrc(confirmLetter.value)

    if (!imageUrl) {
        showError('档期确认函海报暂未生成')

        return
    }

    saveImageToPhotosAlbum(imageUrl)
}

const goConfirmLetterConfig = () => {
    uni.navigateTo({
        url: '/packages/pages/staff_schedule_confirm_letter/staff_schedule_confirm_letter'
    })
}

const handleConfirm = async () => {
    if (!order.value?.id) return

    const status = Number(order.value?.order_status ?? -1)

    if (status === 3) {
        const confirmed = await confirmModal({
            title: '完成服务',

            content: '确认本单服务已完成吗？'
        })

        if (!confirmed) return

        try {
            await staffCenterOrderComplete({ id: order.value.id })

            await fetchDetail(order.value.id)

            const successText =
                Number(order.value?.order_status || 0) === 1
                    ? '服务已完成，待支付尾款'
                    : '订单已完成'

            showSuccess(successText)
        } catch (error: any) {
            showError(error, '操作失败')
        }

        return
    }

    if (status === 2) {
        const confirmed = await confirmModal({
            title: '开始履约',

            content: '确认本单已开始履约吗？'
        })

        if (!confirmed) return

        try {
            await staffCenterOrderStartService({ id: order.value.id })

            await fetchDetail(order.value.id)

            showSuccess('开始履约成功')
        } catch (error: any) {
            showError(error, '操作失败')
        }

        return
    }

    const confirmed = await confirmModal({
        title: '确认订单',

        content: '确认后客户可进行支付，是否继续？'
    })

    if (!confirmed) return

    try {
        await staffCenterOrderConfirm({ id: order.value.id })

        showSuccess('确认成功')

        await fetchDetail(order.value.id)
    } catch (error: any) {
        showError(error, '确认失败')
    }
}

onLoad(async (options: any) => {
    $theme.setScene('staff')

    if (!(await ensureStaffCenterAccess())) return

    const id = Number(options?.id || 0)

    if (!id) {
        showError('订单不存在')

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
    background: linear-gradient(180deg, #fffdf8 0%, #f8f3e7 100%);

    &__content {
        display: flex;
        flex-direction: column;
        gap: 22rpx;
        padding: 20rpx var(--wm-space-page-x, 37rpx) 40rpx;
    }
}

.detail-hero {
    display: flex;
    flex-direction: column;
    gap: 20rpx;
    border-radius: 40rpx;
}

.detail-hero::before {
    opacity: 0.34;
}

.detail-hero__top {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 18rpx;
}

.detail-hero__alert-mini {
    max-width: 390rpx;
    min-width: 0;
    min-height: 46rpx;
    padding: 0 18rpx;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: 999rpx;
    background: rgba(255, 253, 248, 0.12);
    border: 1rpx solid rgba(217, 190, 130, 0.34);
    box-sizing: border-box;
    text-align: right;
    font-size: 22rpx;
    font-weight: 900;
    line-height: 1.35;
    color: #fffdf8;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.detail-hero__body {
    display: flex;
    flex-direction: column;
    gap: 8rpx;
}

.detail-hero__title {
    font-size: 40rpx;
    font-weight: 900;
    line-height: 1.28;
    color: #fffdf8;
}

.detail-hero__facts {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 14rpx;
}

.detail-fact {
    display: flex;
    flex-direction: column;
    gap: 10rpx;
    min-width: 0;
    padding: 20rpx;
    border-radius: 28rpx;
    background: rgba(255, 253, 248, 0.12);
    border: 1rpx solid rgba(255, 253, 248, 0.18);
    box-sizing: border-box;
}

.detail-fact__label {
    font-size: 22rpx;
    font-weight: 800;
    color: rgba(255, 253, 248, 0.64);
}

.detail-fact__value {
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    font-size: 28rpx;
    font-weight: 900;
    color: #fffdf8;
}

.detail-section {
    display: flex;
    flex-direction: column;
    gap: 22rpx;
    padding: 30rpx 28rpx;
    border-radius: 36rpx;
    background: rgba(255, 253, 248, 0.96);
    border-color: #d8c9ad;
    box-shadow: 0 18rpx 40rpx rgba(74, 43, 24, 0.08);
}

.detail-section__head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 18rpx;
}

.detail-section__title {
    flex: 1;
    min-width: 0;
    font-size: 31rpx;
    font-weight: 900;
    line-height: 1.3;
    color: #191713;
}

.detail-section__meta,
.detail-section__action {
    flex-shrink: 0;
    font-size: 22rpx;
    font-weight: 800;
    line-height: 1.3;
    color: #b8954a;
}

.detail-info-list {
    display: flex;
    flex-direction: column;
    padding: 4rpx 20rpx;
    border-radius: 28rpx;
    background: rgba(250, 246, 238, 0.78);
    border: 1rpx solid rgba(216, 201, 173, 0.78);
}

.detail-info-list :deep(.base-info-row) {
    min-height: 76rpx;
    gap: 20rpx;
}

.detail-info-list :deep(.base-info-row + .base-info-row) {
    border-top: 1rpx solid rgba(216, 201, 173, 0.58);
}

.detail-info-list :deep(.base-info-row__label) {
    font-size: 23rpx;
    font-weight: 800;
    color: #756b5c;
}

.detail-info-list :deep(.base-info-row__value) {
    font-size: 25rpx;
    font-weight: 900;
    color: #191713;
}

.detail-info-list :deep(.base-info-row__value--price) {
    font-size: 29rpx;
    color: #b8954a;
}

.detail-address-row {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 24rpx;
    min-height: 76rpx;
    padding: 18rpx 0;
    border-top: 1rpx solid rgba(216, 201, 173, 0.58);
    box-sizing: border-box;
}

.detail-address-row__label {
    flex-shrink: 0;
    padding-top: 2rpx;
    font-size: 23rpx;
    font-weight: 800;
    line-height: 1.45;
    color: #756b5c;
}

.detail-address-row__value {
    flex: 1;
    min-width: 0;
    text-align: right;
    font-size: 25rpx;
    font-weight: 900;
    line-height: 1.55;
    color: #191713;
    word-break: break-word;
}

.service-list {
    display: flex;
    flex-direction: column;
    gap: 18rpx;
}

.service-card {
    display: flex;
    flex-direction: column;
    gap: 16rpx;
    padding: 24rpx;
    border-radius: 30rpx;
    border: 1rpx solid rgba(216, 201, 173, 0.78);
    background: rgba(250, 246, 238, 0.74);
    box-sizing: border-box;
}

.service-card__head,
.service-card__meta-row {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 14rpx;
}

.service-card__meta-row {
    align-items: center;
}

.service-card__copy {
    flex: 1;
    min-width: 0;
}

.service-card__title-row {
    display: flex;
    align-items: center;
    gap: 10rpx;
    flex-wrap: wrap;
}

.service-card__title {
    flex: 1;
    min-width: 0;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    font-size: 28rpx;
    font-weight: 900;
    line-height: 1.35;
    color: #191713;
}

.service-card__price {
    flex-shrink: 0;
    font-size: 29rpx;
    font-weight: 900;
    line-height: 1.3;
    color: #b8954a;
}

.service-card__meta {
    min-width: 0;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    font-size: 22rpx;
    font-weight: 800;
    line-height: 1.45;
    color: #756b5c;
}

.addon-box {
    display: flex;
    flex-direction: column;
    gap: 10rpx;
    padding: 18rpx;
    border-radius: 24rpx;
    background: rgba(255, 253, 248, 0.78);
    border: 1rpx solid rgba(216, 201, 173, 0.56);

    &__header,
    &__row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12rpx;
    }
    &__title {
        font-size: 24rpx;
        font-weight: 900;
        color: #191713;
    }

    &__total,
    &__price {
        font-size: 24rpx;
        font-weight: 900;
        color: #b8954a;
    }

    &__name {
        flex: 1;
        min-width: 0;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
        font-size: 22rpx;
        line-height: 1.45;
        color: #756b5c;
    }
}

.action-bar {
    width: 100%;
}
.action-bar__buttons {
    display: flex;
    align-items: center;
    gap: 18rpx;
}

.action-bar__buttons :deep(.base-button) {
    flex: 1;
    min-width: 0;
}

.loading-container {
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 40rpx;
    background: #fffdf8;
}

@media screen and (max-width: 375px) {
    .staff-order-detail__content {
        gap: 20rpx;
        padding-left: 28rpx;
        padding-right: 28rpx;
    }

    .detail-hero__title {
        font-size: 36rpx;
    }

    .detail-hero__facts {
        grid-template-columns: 1fr;
    }

    .detail-section {
        padding: 26rpx 24rpx;
    }

    .detail-hero__alert-mini {
        max-width: 330rpx;
        min-height: 54rpx;
        padding-top: 8rpx;
        padding-bottom: 8rpx;
        border-radius: 28rpx;
        white-space: normal;
        word-break: break-word;
    }
}
</style>
