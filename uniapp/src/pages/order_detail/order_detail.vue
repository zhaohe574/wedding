<template>
    <page-meta :page-style="$theme.pageStyle" />
    <PageShell scene="consumer" hasSafeBottom>
        <BaseNavbar title="订单详情" />

        <view v-if="order" class="order-detail">
        <view class="status-hero" :style="{ background: statusTheme.background }">
            <view class="status-hero__glow status-hero__glow--left"></view>
            <view class="status-hero__glow status-hero__glow--right"></view>
            <view class="status-hero__main">
                <view class="status-hero__icon" :style="{ background: statusTheme.iconBg }">
                    <tn-icon :name="statusTheme.icon" size="48" color="#FFFFFF" />
                </view>
                <view class="status-hero__content">
                    <text class="status-hero__title">{{ order.order_status_desc }}</text>
                    <text class="status-hero__desc">{{ statusDescription }}</text>
                    <view v-if="showPayCountdown" class="status-hero__countdown">
                        <text class="status-hero__countdown-label">剩余支付时间</text>
                        <text class="status-hero__countdown-value">{{ payCountdownText }}</text>
                    </view>
                    <view v-else-if="showVoucherPending" class="status-hero__countdown">
                        <text class="status-hero__countdown-label">线下凭证</text>
                        <text class="status-hero__countdown-value">审核中</text>
                    </view>
                </view>
                <view
                    v-if="order.order_status === 1 && Number(needPayAmount) > 0"
                    class="status-hero__pay"
                >
                    <text class="status-hero__pay-label">当前待支付</text>
                    <text class="status-hero__pay-value">¥{{ formatAmount(needPayAmount) }}</text>
                </view>
            </view>
            <view class="status-hero__meta" @click="copyOrderSn">
                <text class="status-hero__meta-label">订单编号</text>
                <view class="status-hero__meta-value">
                    <text>{{ order.order_sn }}</text>
                    <tn-icon name="copy" size="24" color="rgba(255,255,255,0.88)" />
                </view>
            </view>
        </view>

        <view class="page-body">
            <view class="card summary-card">
                <view class="card__header">
                    <tn-icon name="calendar" size="32" :color="$theme.primaryColor" />
                    <text class="card__title">服务摘要</text>
                </view>
                <template v-if="primaryItem">
                    <view class="summary-card__top">
                        <view class="summary-card__staff">
                            <image class="summary-card__avatar" :src="primaryStaffAvatar" mode="aspectFill" />
                            <view class="summary-card__staff-copy">
                                <view class="summary-card__staff-row">
                                    <text class="summary-card__staff-name">{{ primaryStaffName }}</text>
                                    <text class="summary-card__badge">服务人员</text>
                                </view>
                                <text class="summary-card__staff-note">本次订单已确认服务人员</text>
                            </view>
                        </view>
                        <view class="summary-card__price">
                            <text class="summary-card__price-label">套餐金额</text>
                            <text class="summary-card__price-value" :style="{ color: $theme.ctaColor }">
                                ¥{{ formatAmount(primaryPackagePrice) }}
                            </text>
                        </view>
                    </view>
                    <view class="summary-grid">
                        <view class="summary-grid__item summary-grid__item--wide">
                            <text class="summary-grid__label">服务日期</text>
                            <text class="summary-grid__value">{{ primaryServiceDate }}</text>
                        </view>
                        <view class="summary-grid__item">
                            <text class="summary-grid__label">套餐</text>
                            <text class="summary-grid__value summary-grid__value--multi">
                                {{ primaryPackageName }}
                            </text>
                        </view>
                        <view class="summary-grid__item">
                            <text class="summary-grid__label">附加服务</text>
                            <text class="summary-grid__value">
                                {{ primaryAddons.length ? `已选 ${primaryAddons.length} 项` : '暂无' }}
                            </text>
                        </view>
                    </view>
                    <view v-if="primaryPackageDescription" class="summary-card__description">
                        <text class="summary-card__description-label">套餐内容</text>
                        <text class="summary-card__description-text">
                            {{ primaryPackageDescription }}
                        </text>
                    </view>
                </template>
                <view v-else class="empty-state">
                    <tn-icon name="document" size="56" color="#CBD5E1" />
                    <text class="empty-state__title">服务信息缺失</text>
                    <text class="empty-state__desc">当前订单未返回主服务项，但金额和支付信息仍可查看。</text>
                </view>
            </view>

            <view v-if="primaryAddons.length" class="card">
                <view class="card__header">
                    <tn-icon name="gift" size="32" :color="$theme.primaryColor" />
                    <text class="card__title">附加服务</text>
                    <text class="card__extra" :style="{ color: $theme.ctaColor }">
                        +¥{{ formatAmount(primaryAddonAmount) }}
                    </text>
                </view>
                <view class="addon-list">
                    <view
                        v-for="(addon, index) in primaryAddons"
                        :key="`${getAddonKey(addon)}-${index}`"
                        class="addon-item"
                    >
                        <view class="addon-item__main">
                            <view class="addon-item__name-row">
                                <text class="addon-item__name">{{ getAddonName(addon) }}</text>
                                <text class="addon-item__tag">附加套餐</text>
                            </view>
                            <text v-if="addon.description" class="addon-item__desc">{{ addon.description }}</text>
                        </view>
                        <text class="addon-item__price">+¥{{ formatAmount(getAddonPrice(addon)) }}</text>
                    </view>
                </view>
            </view>

            <view class="card">
                <view class="card__header">
                    <tn-icon name="location-fill" size="32" :color="$theme.primaryColor" />
                    <text class="card__title">联系与履约信息</text>
                </view>
                <view class="detail-list">
                    <view class="detail-row"><text class="detail-row__label">联系人</text><text class="detail-row__value">{{ order.contact_name || '-' }}</text></view>
                    <view class="detail-row"><text class="detail-row__label">手机号码</text><text class="detail-row__value">{{ order.contact_mobile || '-' }}</text></view>
                    <view class="detail-row"><text class="detail-row__label">服务地区</text><text class="detail-row__value">{{ order.service_region_text || '-' }}</text></view>
                    <view class="detail-row"><text class="detail-row__label">详细地址</text><text class="detail-row__value detail-row__value--left">{{ order.service_address || '-' }}</text></view>
                    <view v-if="order.wedding_date" class="detail-row"><text class="detail-row__label">婚礼日期</text><text class="detail-row__value">{{ order.wedding_date }}</text></view>
                    <view v-if="order.wedding_venue" class="detail-row"><text class="detail-row__label">婚礼地点</text><text class="detail-row__value detail-row__value--left">{{ order.wedding_venue }}</text></view>
                    <view v-if="order.user_remark" class="detail-row detail-row--stack"><text class="detail-row__label">备注</text><text class="detail-row__value detail-row__value--left">{{ order.user_remark }}</text></view>
                </view>
            </view>

            <view class="card">
                <view class="card__header">
                    <tn-icon name="document" size="32" :color="$theme.primaryColor" />
                    <text class="card__title">订单与金额</text>
                </view>
                <view class="section">
                    <text class="section__title">订单信息</text>
                    <view class="detail-list detail-list--compact">
                        <view class="detail-row">
                            <text class="detail-row__label">订单编号</text>
                            <view class="detail-row__copy" @click="copyOrderSn">
                                <text class="detail-row__value">{{ order.order_sn }}</text>
                                <tn-icon name="copy" size="24" color="#94A3B8" />
                            </view>
                        </view>
                        <view class="detail-row"><text class="detail-row__label">下单时间</text><text class="detail-row__value">{{ order.create_time || '-' }}</text></view>
                        <view v-if="order.pay_time" class="detail-row"><text class="detail-row__label">支付时间</text><text class="detail-row__value">{{ order.pay_time }}</text></view>
                    </view>
                </view>
                <view class="divider-block"></view>
                <view class="section">
                    <text class="section__title">金额明细</text>
                    <view class="amount-list">
                        <view class="amount-row"><text class="amount-row__label">主服务金额</text><text class="amount-row__value">¥{{ formatAmount(orderServiceAmount) }}</text></view>
                        <view v-if="Number(primaryAddonAmount) > 0" class="amount-row"><text class="amount-row__label">附加服务金额</text><text class="amount-row__value">+¥{{ formatAmount(primaryAddonAmount) }}</text></view>
                        <view v-if="Number(order.discount_amount || 0) > 0" class="amount-row"><text class="amount-row__label">优惠金额</text><text class="amount-row__value amount-row__value--discount">-¥{{ formatAmount(order.discount_amount) }}</text></view>
                        <view class="amount-divider"></view>
                        <view class="amount-row amount-row--total"><text class="amount-row__label">实付金额</text><text class="amount-row__value amount-row__value--total" :style="{ color: $theme.ctaColor }">¥{{ formatAmount(order.pay_amount) }}</text></view>
                        <view v-if="Number(order.deposit_amount || 0) > 0" class="amount-row">
                            <text class="amount-row__label">定金</text>
                            <view class="amount-row__wrap">
                                <text class="amount-row__value">¥{{ formatAmount(order.deposit_amount) }}</text>
                                <text class="amount-status" :style="{ color: order.deposit_paid ? '#16A34A' : '#D97706', backgroundColor: order.deposit_paid ? 'rgba(22,163,74,0.1)' : 'rgba(217,119,6,0.1)' }">{{ order.deposit_paid ? '已付' : '待付' }}</text>
                            </view>
                        </view>
                        <view v-if="Number(order.balance_amount || 0) > 0" class="amount-row">
                            <text class="amount-row__label">尾款</text>
                            <view class="amount-row__wrap">
                                <text class="amount-row__value">¥{{ formatAmount(order.balance_amount) }}</text>
                                <text class="amount-status" :style="{ color: order.balance_paid ? '#16A34A' : '#D97706', backgroundColor: order.balance_paid ? 'rgba(22,163,74,0.1)' : 'rgba(217,119,6,0.1)' }">{{ order.balance_paid ? '已付' : '待付' }}</text>
                            </view>
                        </view>
                    </view>
                </view>
            </view>

            <view v-if="order.pay_type === 4 || order.pay_voucher" class="card">
                <view class="card__header">
                    <tn-icon name="image" size="32" :color="$theme.primaryColor" />
                    <text class="card__title">线下支付凭证</text>
                </view>
                <view class="detail-list">
                    <view class="detail-row"><text class="detail-row__label">凭证状态</text><text class="detail-row__value">{{ order.pay_voucher_status_desc || '未上传' }}</text></view>
                    <view v-if="order.pay_voucher_audit_remark" class="detail-row detail-row--stack"><text class="detail-row__label">审核备注</text><text class="detail-row__value detail-row__value--left">{{ order.pay_voucher_audit_remark }}</text></view>
                    <view v-if="order.pay_voucher" class="voucher-image"><image :src="order.pay_voucher" mode="aspectFill" /></view>
                    <view v-else class="voucher-empty"><text>暂无凭证</text></view>
                </view>
            </view>

            <view v-if="order.refund" class="card">
                <view class="card__header">
                    <tn-icon name="refund" size="32" color="#FF2C3C" />
                    <text class="card__title">退款信息</text>
                </view>
                <view class="detail-list">
                    <view class="detail-row"><text class="detail-row__label">退款状态</text><text class="refund-status" :style="{ color: getRefundStatusStyle(order.refund.refund_status).color, backgroundColor: getRefundStatusStyle(order.refund.refund_status).bg }">{{ order.refund.refund_status_desc }}</text></view>
                    <view class="detail-row"><text class="detail-row__label">退款金额</text><text class="detail-row__value">¥{{ formatAmount(order.refund.refund_amount) }}</text></view>
                    <view class="detail-row detail-row--stack"><text class="detail-row__label">退款原因</text><text class="detail-row__value detail-row__value--left">{{ order.refund.refund_reason }}</text></view>
                </view>
            </view>
        </view>

        <ActionArea sticky safeBottom>
            <view class="action-bar__buttons">
                <view v-if="[2, 3].includes(order.order_status)" class="btn-secondary" :style="{ borderColor: $theme.primaryColor, color: $theme.primaryColor }" @click="openChangeActions"><text>申请变更</text></view>
                <view class="btn-secondary" :style="{ borderColor: '#D85C61', color: '#D85C61' }" @click="handleContactAdvisor"><text>联系顾问</text></view>
                <view v-if="[0, 1].includes(order.order_status)" class="btn-secondary" :style="{ borderColor: $theme.primaryColor, color: $theme.primaryColor }" @click="handleCancel"><text>取消订单</text></view>
                <view v-if="canPayOnline" class="btn-primary" :style="{ background: `linear-gradient(135deg, ${$theme.ctaColor} 0%, ${$theme.ctaColor} 100%)`, color: $theme.btnColor }" @click="handlePay">
                    <tn-icon name="wallet-fill" size="28" :color="$theme.btnColor" />
                    <text>立即支付 ¥{{ formatAmount(needPayAmount) }}</text>
                </view>
                <view v-if="canUploadVoucher" class="btn-secondary" :style="{ borderColor: $theme.primaryColor, color: $theme.primaryColor }" @click="showVoucherPopup = true"><text>上传凭证</text></view>
                <view v-if="order.order_status === 3" class="btn-primary" :style="{ background: `linear-gradient(135deg, ${$theme.primaryColor} 0%, ${$theme.primaryColor} 100%)`, color: $theme.btnColor }" @click="handleConfirm">
                    <tn-icon name="check-circle-fill" size="28" :color="$theme.btnColor" />
                    <text>确认完成</text>
                </view>
                <view v-if="[2, 3].includes(order.order_status) && !order.refund" class="btn-secondary" :style="{ borderColor: '#FF2C3C', color: '#FF2C3C' }" @click="showRefundPopup = true"><text>申请退款</text></view>
                <view v-if="[4, 5, 6, 8].includes(order.order_status)" class="btn-secondary" :style="{ borderColor: '#999999', color: '#999999' }" @click="handleDelete"><text>删除订单</text></view>
            </view>
        </ActionArea>

        <tn-popup v-model="showRefundPopup" open-direction="bottom" :radius="32" safe-area-inset-bottom>
            <view class="popup">
                <view class="popup__header"><text class="popup__title">申请退款</text><tn-icon name="close" size="40" color="#999999" @click="showRefundPopup = false" /></view>
                <view class="popup__content">
                    <view class="form-item"><text class="form-item__label">退款金额</text><tn-input v-model="refundForm.amount" type="number" :placeholder="`最多可退 ¥${formatAmount(order.pay_amount)}`" border /></view>
                    <view class="form-item"><text class="form-item__label">退款原因</text><tn-input v-model="refundForm.reason" type="textarea" placeholder="请输入退款原因" :maxlength="200" border height="200" /></view>
                </view>
                <view class="popup__actions">
                    <view class="popup__btn popup__btn--secondary" :style="{ borderColor: $theme.primaryColor, color: $theme.primaryColor }" @click="showRefundPopup = false"><text>取消</text></view>
                    <view class="popup__btn popup__btn--primary" :style="{ background: `linear-gradient(135deg, ${$theme.primaryColor} 0%, ${$theme.primaryColor} 100%)`, color: $theme.btnColor }" @click="submitRefund"><text>提交申请</text></view>
                </view>
            </view>
        </tn-popup>

        <tn-popup v-model="showVoucherPopup" open-direction="bottom" :radius="32" safe-area-inset-bottom>
            <view class="popup">
                <view class="popup__header"><text class="popup__title">上传支付凭证</text><tn-icon name="close" size="40" color="#999999" @click="showVoucherPopup = false" /></view>
                <view class="popup__content">
                    <view class="form-item"><text class="form-item__label">凭证图片</text><text class="form-item__tip">请上传转账截图或付款凭证</text></view>
                    <view class="voucher-upload">
                        <view v-if="voucherForm.image" class="voucher-upload__preview">
                            <image :src="voucherForm.image" mode="aspectFill" />
                            <view class="voucher-upload__remove" @click="voucherForm.image = ''"><tn-icon name="close" size="32" color="#FFFFFF" /></view>
                        </view>
                        <view v-else class="voucher-upload__add" @click="chooseVoucherImage">
                            <tn-icon name="add" size="64" color="#CBD5E1" />
                            <text class="voucher-upload__text">选择图片</text>
                            <text class="voucher-upload__tip">支持 jpg、png 格式</text>
                        </view>
                    </view>
                </view>
                <view class="popup__actions">
                    <view class="popup__btn popup__btn--secondary" :style="{ borderColor: $theme.primaryColor, color: $theme.primaryColor }" @click="showVoucherPopup = false"><text>取消</text></view>
                    <view class="popup__btn popup__btn--primary" :style="{ background: `linear-gradient(135deg, ${$theme.primaryColor} 0%, ${$theme.primaryColor} 100%)`, color: $theme.btnColor }" @click="submitVoucher"><text>{{ voucherForm.uploading ? '上传中...' : '提交审核' }}</text></view>
                </view>
            </view>
        </tn-popup>

        <payment v-model:show="payState.showPay" v-model:show-check="payState.showCheck" :order-id="orderId" :from="payState.from" :redirect="payState.redirect" :payment-sn="payState.paymentSn" @success="handlePaySuccess" @fail="handlePayFail" />
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
import { applyRefund, cancelOrder, confirmOrder, deleteOrder, getOrderDetail, uploadPayVoucher } from '@/api/order'
import { uploadImage } from '@/api/app'

const DEFAULT_AVATAR = '/static/images/user/default_avatar.png'
const $theme = useThemeStore()
const orderId = ref(0)
const order = ref<any>(null)
const showRefundPopup = ref(false)
const showVoucherPopup = ref(false)
const payState = reactive({ showPay: false, showCheck: false, from: 'order', redirect: '/pages/order_detail/order_detail', paymentSn: '' })
const refundForm = reactive({ amount: '', reason: '' })
const voucherForm = reactive({ image: '', uploading: false })
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
const getItemAddonTotal = (item: any) => (item?.addons || []).reduce((sum: number, addon: any) => sum + Number(addon?.subtotal || addon?.price || 0), 0)
const getAddonKey = (addon: any) => String(addon?.addon_id || addon?.id || addon?.name || 'addon')
const getAddonName = (addon: any) => addon?.addon_name || addon?.name || '附加服务'
const getAddonPrice = (addon: any) => Number(addon?.subtotal || addon?.price || 0)

const getStatusTheme = (status: number) =>
    ({
        0: { background: 'linear-gradient(180deg, #FFF5E8 0%, #FFFFFF 100%)', iconBg: '#C98524', icon: 'time-fill' },
        1: { background: 'linear-gradient(180deg, #FFF1EE 0%, #FFFFFF 100%)', iconBg: '#E85A4F', icon: 'wallet-fill' },
        2: { background: 'linear-gradient(180deg, #FFF5F1 0%, #FFFFFF 100%)', iconBg: '#E85A4F', icon: 'check-circle-fill' },
        3: { background: 'linear-gradient(180deg, #EEF9F5 0%, #FFFFFF 100%)', iconBg: '#2F7D58', icon: 'loading' },
        4: { background: 'linear-gradient(180deg, #EEF9F5 0%, #FFFFFF 100%)', iconBg: '#2F7D58', icon: 'check-circle-fill' },
        5: { background: 'linear-gradient(180deg, #EEF9F5 0%, #FFFFFF 100%)', iconBg: '#2F7D58', icon: 'check-circle-fill' },
        6: { background: 'linear-gradient(180deg, #F7F3F1 0%, #FFFFFF 100%)', iconBg: '#7F7B78', icon: 'close-circle-fill' },
        7: { background: 'linear-gradient(180deg, #FFF5E8 0%, #FFFFFF 100%)', iconBg: '#C98524', icon: 'time-fill' },
        8: { background: 'linear-gradient(180deg, #FDEEEE 0%, #FFFFFF 100%)', iconBg: '#B44A3A', icon: 'refund' }
    } as Record<number, { background: string; iconBg: string; icon: string }>)[status] || { background: 'linear-gradient(180deg, #F7F3F1 0%, #FFFFFF 100%)', iconBg: '#7F7B78', icon: 'document' }

const getRefundStatusStyle = (status: number) =>
    ({
        0: { color: '#D97706', bg: 'rgba(217,119,6,0.1)' },
        1: { color: '#C99B73', bg: 'rgba(201,155,115,0.12)' },
        2: { color: '#0F766E', bg: 'rgba(15,118,110,0.1)' },
        3: { color: '#16A34A', bg: 'rgba(22,163,74,0.1)' },
        4: { color: '#DC2626', bg: 'rgba(220,38,38,0.1)' }
    } as Record<number, { color: string; bg: string }>)[status] || { color: '#64748B', bg: 'rgba(100,116,139,0.1)' }

const primaryItem = computed(() => (Array.isArray(order.value?.items) ? order.value.items[0] || null : null))
const primaryAddons = computed(() => (Array.isArray(primaryItem.value?.addons) ? primaryItem.value.addons : []))
const primaryStaffAvatar = computed(() => primaryItem.value?.staff?.avatar || primaryItem.value?.staff_avatar || DEFAULT_AVATAR)
const primaryStaffName = computed(() => primaryItem.value?.staff?.name || primaryItem.value?.staff_name || '待分配服务人员')
const primaryPackageName = computed(() => primaryItem.value?.package?.name || primaryItem.value?.package_name || '待确认主套餐')
const primaryPackageDescription = computed(() =>
    String(primaryItem.value?.package_description || '').trim()
)
const primaryServiceDate = computed(() => primaryItem.value?.service_date || primaryItem.value?.schedule_date || order.value?.service_date || '待确认服务日期')
const primaryPackagePrice = computed(() => Number(primaryItem.value?.price || primaryItem.value?.package?.price || 0))
const primaryAddonAmount = computed(() => {
    const addonAmount = Number(order.value?.addon_amount ?? -1)
    return addonAmount >= 0 ? addonAmount : getItemAddonTotal(primaryItem.value)
})
const orderServiceAmount = computed(() => {
    const serviceAmount = Number(order.value?.service_amount ?? -1)
    return serviceAmount >= 0 ? serviceAmount : Math.max(0, Number(order.value?.total_amount || 0) - Number(order.value?.addon_amount || 0))
})
const needPayAmount = computed(() => {
    if (!order.value) return 0
    if (Number(order.value.deposit_amount || 0) > 0) {
        if (!order.value.deposit_paid) return Number(order.value.deposit_amount || 0)
        if (!order.value.balance_paid) return Number(order.value.balance_amount || 0)
    }
    return Number(order.value.pay_amount || 0)
})
const showVoucherPending = computed(() => !!order.value && Number(order.value.pay_type) === 4 && Number(order.value.pay_voucher_status) === 0)
const showPayCountdown = computed(() => !!order.value && payCountdownSeconds.value > 0)
const payCountdownText = computed(() => formatCountdown(payCountdownSeconds.value))
const canPayOnline = computed(() => !!order.value && Number(order.value.order_status) === 1 && !(Number(order.value.pay_type) === 4 && Number(order.value.pay_voucher_status) === 0))
const canUploadVoucher = computed(() => !!order.value && Number(order.value.order_status) === 1 && Number(order.value.pay_voucher_status) !== 0)
const statusTheme = computed(() => getStatusTheme(Number(order.value?.order_status ?? 6)))
const statusDescription = computed(() => ({
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
} as Record<number, string>)[Number(order.value?.order_status ?? -1)] || '订单状态已更新，请留意后续进度。')

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
    uni.setClipboardData({ data: order.value.order_sn, success: () => uni.showToast({ title: '已复制订单编号', icon: 'success' }) })
}

const handleContactAdvisor = () => uni.navigateTo({ url: `/packages/pages/customer_service/customer_service?scene=order_detail&order_id=${orderId.value}` })
const openChangeActions = () =>
    uni.showActionSheet({
        itemList: ['申请改期', '附加服务变更', '申请暂停', '我的申请'],
        success: ({ tapIndex }) => {
            const routes = [
                `/packages/pages/order_change/apply_date?order_id=${orderId.value}`,
                `/packages/pages/order_change/apply_addon?order_id=${orderId.value}`,
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
    payState.paymentSn = ''
    uni.showToast({ title: '支付成功', icon: 'success' })
    await fetchDetail()
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
    if (!refundForm.amount || Number(refundForm.amount) <= 0) return uni.showToast({ title: '请输入退款金额', icon: 'none' })
    if (Number(refundForm.amount) > Number(order.value?.pay_amount || 0)) return uni.showToast({ title: '退款金额不能超过实付金额', icon: 'none' })
    if (!refundForm.reason.trim()) return uni.showToast({ title: '请输入退款原因', icon: 'none' })
    try {
        await applyRefund({ id: orderId.value, amount: Number(refundForm.amount), reason: refundForm.reason })
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
                if (uploadRes?.url) voucherForm.image = uploadRes.url
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
.order-detail{min-height:100vh;background:linear-gradient(180deg,var(--wm-color-bg-page,#fcfbf9) 0%,var(--wm-color-bg-soft,#fff7f4) 100%);padding-bottom:calc(env(safe-area-inset-bottom) + 132rpx)}
.page-body{position:relative;z-index:2;margin-top:-88rpx}
.card{margin:0 20rpx 20rpx;background:#fff;border-radius:24rpx;border:1rpx solid var(--wm-color-border,#efe6e1);box-shadow:var(--wm-shadow-soft,0 14rpx 32rpx rgba(214,185,167,.16));overflow:hidden}
.card__header{display:flex;align-items:center;gap:12rpx;padding:22rpx 24rpx;border-bottom:1rpx solid var(--wm-color-border,#efe6e1)}
.card__title{flex:1;font-size:30rpx;font-weight:700;color:var(--wm-text-primary,#1e2432)}
.card__extra{font-size:26rpx;font-weight:700}

.status-hero{position:relative;overflow:hidden;margin:24rpx 20rpx 0;padding:28rpx 24rpx 144rpx;border-radius:32rpx;border:1rpx solid var(--wm-color-border-strong,#f4c7bf);box-shadow:var(--wm-shadow-hero,0 24rpx 56rpx rgba(177,108,95,.18))}
.status-hero__glow{position:absolute;border-radius:50%;background:rgba(255,255,255,.28)}
.status-hero__glow--left{width:220rpx;height:220rpx;top:-60rpx;left:-70rpx}
.status-hero__glow--right{width:260rpx;height:260rpx;right:-100rpx;bottom:-80rpx}
.status-hero__main,.status-hero__meta{position:relative;z-index:1}
.status-hero__main{display:flex;align-items:flex-start;gap:20rpx}
.status-hero__icon{width:88rpx;height:88rpx;border-radius:28rpx;display:flex;align-items:center;justify-content:center;flex-shrink:0;backdrop-filter:blur(12rpx)}
.status-hero__content{flex:1;min-width:0}
.status-hero__title{display:block;font-size:42rpx;font-weight:700;line-height:1.3;color:var(--wm-text-primary,#1e2432)}
.status-hero__desc{display:block;margin-top:8rpx;font-size:24rpx;line-height:1.6;color:var(--wm-text-secondary,#7f7b78)}
.status-hero__countdown{margin-top:14rpx;display:inline-flex;align-items:center;gap:12rpx;padding:10rpx 18rpx;border-radius:999rpx;background:var(--wm-color-primary-soft,#fff1ee);border:1rpx solid var(--wm-color-border-strong,#f4c7bf)}
.status-hero__countdown-label{font-size:22rpx;color:var(--wm-text-secondary,#7f7b78)}
.status-hero__countdown-value{font-size:28rpx;font-weight:700;color:var(--wm-color-primary,#e85a4f)}
.status-hero__pay{flex-shrink:0;padding:14rpx 18rpx;border-radius:22rpx;background:rgba(255,255,255,.72);border:1rpx solid var(--wm-color-border,#efe6e1);text-align:right}
.status-hero__pay-label{display:block;font-size:22rpx;color:var(--wm-text-secondary,#7f7b78)}
.status-hero__pay-value{display:block;margin-top:6rpx;font-size:28rpx;font-weight:700;color:var(--wm-color-primary,#e85a4f)}
.status-hero__meta{margin-top:28rpx;padding:20rpx 22rpx;border-radius:24rpx;background:rgba(255,255,255,.78);border:1rpx solid var(--wm-color-border,#efe6e1)}
.status-hero__meta-label{display:block;font-size:22rpx;color:var(--wm-text-secondary,#7f7b78)}
.status-hero__meta-value{margin-top:12rpx;display:flex;align-items:center;justify-content:space-between;gap:16rpx;font-size:28rpx;font-weight:600;color:var(--wm-text-primary,#1e2432)}

.summary-card__top{display:flex;align-items:center;justify-content:space-between;gap:20rpx;padding:24rpx}
.summary-card__staff{flex:1;min-width:0;display:flex;align-items:center;gap:16rpx}
.summary-card__avatar{width:92rpx;height:92rpx;border-radius:24rpx;flex-shrink:0;background:var(--wm-color-bg-soft,#fff7f4)}
.summary-card__staff-copy{min-width:0}
.summary-card__staff-row{display:flex;align-items:center;gap:12rpx}
.summary-card__staff-name{max-width:320rpx;font-size:32rpx;font-weight:700;color:var(--wm-text-primary,#1e2432);white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.summary-card__badge{padding:6rpx 14rpx;border-radius:999rpx;background:var(--wm-color-primary-soft,#fff1ee);color:var(--wm-color-primary,#e85a4f);font-size:20rpx;font-weight:600;flex-shrink:0;border:1rpx solid var(--wm-color-border-strong,#f4c7bf)}
.summary-card__staff-note{display:block;margin-top:8rpx;font-size:24rpx;color:var(--wm-text-secondary,#7f7b78)}
.summary-card__price{text-align:right;flex-shrink:0}
.summary-card__price-label{display:block;font-size:22rpx;color:var(--wm-text-tertiary,#b4aca8)}
.summary-card__price-value{display:block;margin-top:8rpx;font-size:34rpx;font-weight:700}
.summary-grid{padding:0 24rpx 24rpx;display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:14rpx}
.summary-grid__item{min-height:132rpx;padding:18rpx;border-radius:22rpx;border:1rpx solid var(--wm-color-border,#efe6e1);background:var(--wm-color-bg-soft,#fff7f4)}
.summary-grid__item--wide{grid-column:1/-1;background:linear-gradient(135deg,#fff7f4 0%,#fff1ee 100%);border-color:var(--wm-color-border-strong,#f4c7bf)}
.summary-grid__label{display:block;font-size:22rpx;color:var(--wm-text-secondary,#7f7b78)}
.summary-grid__value{display:block;margin-top:16rpx;font-size:28rpx;line-height:1.45;font-weight:600;color:var(--wm-text-primary,#1e2432);word-break:break-all}
.summary-grid__value--multi{display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden}
.summary-card__description{margin:0 24rpx 24rpx;padding:22rpx 24rpx;border-radius:22rpx;background:linear-gradient(135deg,#fff7f4 0%,#ffffff 100%);border:1rpx solid var(--wm-color-border-strong,#f4c7bf)}
.summary-card__description-label{display:block;font-size:22rpx;color:var(--wm-color-secondary,#c99b73)}
.summary-card__description-text{display:block;margin-top:12rpx;font-size:26rpx;line-height:1.7;color:var(--wm-text-secondary,#7f7b78);white-space:pre-wrap;word-break:break-word}
.empty-state{padding:44rpx 32rpx 48rpx;display:flex;flex-direction:column;align-items:center;text-align:center}
.empty-state__title{margin-top:20rpx;font-size:30rpx;font-weight:600;color:var(--wm-text-primary,#1e2432)}
.empty-state__desc{margin-top:12rpx;font-size:24rpx;line-height:1.7;color:var(--wm-text-tertiary,#b4aca8)}

.addon-list{padding:20rpx;display:flex;flex-direction:column;gap:14rpx}
.addon-item{display:flex;align-items:flex-start;justify-content:space-between;gap:16rpx;padding:20rpx;border-radius:22rpx;border:1rpx solid var(--wm-color-border,#efe6e1);background:linear-gradient(135deg,#fff 0%,#fff7f4 100%)}
.addon-item__main{flex:1;min-width:0}
.addon-item__name-row{display:flex;align-items:center;gap:10rpx}
.addon-item__name{flex:1;min-width:0;font-size:28rpx;font-weight:600;color:var(--wm-text-primary,#1e2432)}
.addon-item__tag{padding:4rpx 12rpx;border-radius:999rpx;background:rgba(201,155,115,.14);font-size:20rpx;color:var(--wm-color-secondary,#c99b73);flex-shrink:0}
.addon-item__desc{display:block;margin-top:8rpx;font-size:24rpx;line-height:1.6;color:var(--wm-text-secondary,#7f7b78)}
.addon-item__price{flex-shrink:0;font-size:28rpx;font-weight:700;color:var(--wm-color-primary,#e85a4f)}
.detail-list{padding:10rpx 24rpx 18rpx}
.detail-list--compact{padding:0}
.detail-row{display:flex;align-items:flex-start;justify-content:space-between;gap:24rpx;padding:16rpx 0}
.detail-row + .detail-row{border-top:1rpx solid var(--wm-color-border,#efe6e1)}
.detail-row--stack{flex-direction:column;gap:12rpx}
.detail-row__label{flex-shrink:0;font-size:26rpx;color:var(--wm-text-secondary,#7f7b78)}
.detail-row__value{flex:1;font-size:27rpx;line-height:1.6;color:var(--wm-text-primary,#1e2432);text-align:right}
.detail-row__value--left{text-align:left}
.detail-row__copy{display:flex;align-items:center;justify-content:flex-end;gap:10rpx;flex:1;min-width:0}

.section{padding:20rpx 24rpx 24rpx}
.section__title{display:block;margin-bottom:8rpx;font-size:24rpx;font-weight:600;color:var(--wm-text-tertiary,#b4aca8)}
.divider-block{height:12rpx;background:var(--wm-color-bg-soft,#fff7f4)}
.amount-list{padding-top:6rpx}
.amount-row{display:flex;align-items:center;justify-content:space-between;gap:24rpx;padding:16rpx 0}
.amount-row__label{font-size:27rpx;color:var(--wm-text-secondary,#7f7b78)}
.amount-row__value{font-size:28rpx;font-weight:600;color:var(--wm-text-primary,#1e2432)}
.amount-row__value--discount{color:#dc2626}
.amount-row--total{padding-top:24rpx}
.amount-row__value--total{font-size:42rpx;line-height:1.2;font-weight:700}
.amount-row__wrap{display:flex;align-items:center;gap:12rpx}
.amount-divider{height:1rpx;background:var(--wm-color-border,#efe6e1);margin:8rpx 0}
.amount-status{padding:6rpx 14rpx;border-radius:999rpx;font-size:22rpx;font-weight:600}

.voucher-image{margin-top:16rpx;border-radius:22rpx;overflow:hidden;background:var(--wm-color-bg-soft,#fff7f4)}
.voucher-image image{width:100%;height:360rpx;display:block}
.voucher-empty{margin-top:16rpx;padding:40rpx 0;text-align:center;border-radius:22rpx;background:var(--wm-color-bg-soft,#fff7f4);font-size:24rpx;color:var(--wm-text-tertiary,#b4aca8)}
.refund-status{padding:8rpx 16rpx;border-radius:999rpx;font-size:24rpx;font-weight:600}

.action-bar__buttons{display:flex;flex-wrap:wrap;justify-content:flex-end;gap:12rpx}
.btn-primary,.btn-secondary{height:72rpx;padding:0 28rpx;border-radius:999rpx;display:flex;align-items:center;justify-content:center;gap:8rpx;font-size:26rpx;font-weight:600}
.btn-primary{box-shadow:0 10rpx 20rpx rgba(232,90,79,.18)}
.btn-secondary{background:transparent;border:2rpx solid}

.popup{background:#fff;border-top-left-radius:32rpx;border-top-right-radius:32rpx;padding:24rpx 24rpx calc(env(safe-area-inset-bottom) + 24rpx)}
.popup__header{display:flex;align-items:center;justify-content:space-between;gap:20rpx}
.popup__title{font-size:32rpx;font-weight:700;color:var(--wm-text-primary,#1e2432)}
.popup__content{margin-top:24rpx}
.form-item + .form-item{margin-top:20rpx}
.form-item__label{display:block;margin-bottom:12rpx;font-size:26rpx;font-weight:600;color:var(--wm-text-primary,#1e2432)}
.form-item__tip{display:block;margin-top:8rpx;font-size:22rpx;color:var(--wm-text-tertiary,#b4aca8)}
.popup__actions{display:flex;gap:16rpx;margin-top:28rpx}
.popup__btn{flex:1;height:80rpx;border-radius:999rpx;display:flex;align-items:center;justify-content:center;font-size:28rpx;font-weight:600}
.popup__btn--secondary{background:transparent;border:2rpx solid}

.voucher-upload{margin-top:16rpx}
.voucher-upload__preview,.voucher-upload__add{width:100%;height:320rpx;border-radius:24rpx;overflow:hidden}
.voucher-upload__preview{position:relative;background:var(--wm-color-bg-soft,#fff7f4)}
.voucher-upload__preview image{width:100%;height:100%;display:block}
.voucher-upload__remove{position:absolute;top:16rpx;right:16rpx;width:44rpx;height:44rpx;border-radius:50%;background:rgba(15,23,42,.58);display:flex;align-items:center;justify-content:center}
.voucher-upload__add{border:2rpx dashed var(--wm-color-border,#efe6e1);background:var(--wm-color-bg-soft,#fff7f4);display:flex;flex-direction:column;align-items:center;justify-content:center}
.voucher-upload__text{margin-top:16rpx;font-size:28rpx;color:var(--wm-text-secondary,#7f7b78);font-weight:600}
.voucher-upload__tip{margin-top:8rpx;font-size:22rpx;color:var(--wm-text-tertiary,#b4aca8)}

.safe-bottom{height:calc(env(safe-area-inset-bottom) + 24rpx)}
.loading-container{min-height:100vh;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:16rpx;background:var(--wm-color-bg-page,#fcfbf9)}
.loading-text{font-size:26rpx;color:var(--wm-text-tertiary,#b4aca8)}
</style>
