<template>
    <page-meta :page-style="$theme.pageStyle">
        <!-- #ifndef H5 -->
        <navigation-bar
            title="订单确认"
            :front-color="$theme.navColor"
            :background-color="$theme.navBgColor"
        />
        <!-- #endif -->
    </page-meta>

    <view class="order-confirm-page cinema-page">
        <view class="order-confirm-page__hero">
            <text class="order-confirm-page__hero-label">Booking Checkout</text>
            <text class="order-confirm-page__hero-title">确认本次婚礼服务档期与联系信息</text>
            <text class="order-confirm-page__hero-desc">
                保持仪式感的沉浸头部，同时把联系人、地址和费用明细集中在浅色信息层里，降低确认成本。
            </text>
            <view class="order-confirm-page__hero-summary glass-card">
                <view class="order-confirm-page__hero-item">
                    <text class="order-confirm-page__hero-item-label">预约日期</text>
                    <text class="order-confirm-page__hero-item-value">{{ bookingDateText || '-' }}</text>
                </view>
                <view class="order-confirm-page__hero-divider" />
                <view class="order-confirm-page__hero-item">
                    <text class="order-confirm-page__hero-item-label">服务地区</text>
                    <text class="order-confirm-page__hero-item-value order-confirm-page__hero-item-value--small">
                        {{ serviceRegionText || '未选择区县' }}
                    </text>
                </view>
                <view class="order-confirm-page__hero-divider" />
                <view class="order-confirm-page__hero-item">
                    <text class="order-confirm-page__hero-item-label">预计支付</text>
                    <text class="order-confirm-page__hero-item-value">¥{{ formatPrice(preview.pay_amount) }}</text>
                </view>
            </view>
        </view>

        <view class="order-confirm-page__surface cinema-surface">
            <view v-if="loading" class="loading-state cinema-panel">
                <tn-loading size="60" mode="flower" />
                <text class="loading-text">加载中...</text>
            </view>

            <view v-else class="order-confirm-page__content">
                <view class="section cinema-panel">
                    <view class="section-header section-header--stack">
                        <view>
                            <text class="section-title">预约信息</text>
                            <text class="section-desc">当前档期和服务地区会直接写入订单，提交前请再次确认。</text>
                        </view>
                    </view>
                    <view class="booking-info-card">
                        <view class="booking-info-item">
                            <text class="booking-info-item__label">预约日期</text>
                            <text class="booking-info-item__value">{{ bookingDateText || '-' }}</text>
                        </view>
                        <view class="booking-info-item">
                            <text class="booking-info-item__label">服务地区</text>
                            <text class="booking-info-item__value">
                                {{ serviceRegionText || '未选择区县' }}
                            </text>
                        </view>
                    </view>
                </view>

                <view class="section cinema-panel">
                    <view class="section-header section-header--stack">
                        <view>
                            <text class="section-title">联系人信息</text>
                            <text class="section-desc">用于订单沟通、档期确认和上门服务，请尽量填写准确。</text>
                        </view>
                    </view>
                    <view class="form-item">
                        <text class="form-label">联系人</text>
                        <view class="form-shell">
                            <tn-input
                                v-model="form.contact_name"
                                placeholder="请输入联系人姓名"
                                :border="true"
                                height="80"
                            />
                        </view>
                    </view>
                    <view class="form-item">
                        <text class="form-label">手机号码</text>
                        <view class="form-shell">
                            <tn-input
                                v-model="form.contact_mobile"
                                placeholder="请输入手机号码"
                                type="number"
                                :border="true"
                                height="80"
                            />
                        </view>
                    </view>
                    <view class="form-item">
                        <text class="form-label">详细地址</text>
                        <view class="form-shell">
                            <tn-input
                                v-model="form.service_address"
                                placeholder="请输入详细地址"
                                :border="true"
                                height="80"
                            />
                        </view>
                    </view>
                    <view class="form-item">
                        <text class="form-label">备注</text>
                        <view class="form-shell">
                            <tn-input
                                v-model="form.remark"
                                type="textarea"
                                placeholder="请输入备注（选填）"
                                :border="true"
                                height="120"
                            />
                        </view>
                    </view>
                </view>

                <view class="section cinema-panel" v-if="selectedItem">
                    <view class="section-header">
                        <view>
                            <text class="section-title">服务项目</text>
                            <text class="section-desc">继续保持现有套餐和附加服务结构，无需重新录入内容。</text>
                        </view>
                        <text class="section-action" @click="handleReselect">重新选择</text>
                    </view>
                    <view class="service-card">
                        <view class="service-header">
                            <view class="staff-section">
                                <image
                                    :src="
                                        selectedItem.staff?.avatar || '/static/images/user/default_avatar.png'
                                    "
                                    class="staff-avatar"
                                    mode="aspectFill"
                                />
                                <view class="staff-info">
                                    <text class="staff-title">
                                        {{ selectedItem.staff?.name || '服务人员' }}
                                    </text>
                                    <text class="staff-subtitle">已为当前档期锁定服务人员</text>
                                    <text class="package-name">
                                        {{ selectedItem.package?.name || '服务套餐' }}
                                    </text>
                                </view>
                            </view>
                            <view class="package-price-wrap">
                                <text class="package-price-label">套餐金额</text>
                                <text class="package-price" :style="{ color: $theme.ctaColor }">
                                    ¥{{ formatPrice(selectedItem.price) }}
                                </text>
                            </view>
                        </view>
                        <text v-if="selectedItem.package?.description" class="package-desc">
                            {{ selectedItem.package?.description }}
                        </text>
                        <view v-if="selectedAddons.length" class="selected-addon-section">
                            <view class="selected-addon-section__header">
                                <text class="selected-addon-section__title">已选附加服务</text>
                                <text class="selected-addon-section__meta">
                                    共 {{ selectedAddons.length }} 项
                                </text>
                            </view>
                            <view class="selected-addon-list">
                                <view
                                    v-for="addon in selectedAddons"
                                    :key="addon.id || addon.addon_id"
                                    class="selected-addon-card"
                                >
                                    <view class="selected-addon-card__main">
                                        <text class="selected-addon-card__title">{{ addon.name }}</text>
                                        <text
                                            v-if="addon.description"
                                            class="selected-addon-card__desc"
                                        >
                                            {{ addon.description }}
                                        </text>
                                    </view>
                                    <view class="selected-addon-card__price">
                                        +¥{{ formatPrice(addon.price) }}
                                    </view>
                                </view>
                            </view>
                        </view>
                    </view>
                </view>

                <view class="section cinema-panel" v-if="hasItems">
                    <view class="section-header section-header--stack">
                        <view>
                            <text class="section-title">费用明细</text>
                            <text class="section-desc">展示主服务、附加服务与最终应付金额，便于下单前确认。</text>
                        </view>
                    </view>
                    <view class="price-list">
                        <view class="price-row">
                            <text>主服务金额</text>
                            <text>¥{{ formatPrice(serviceAmount) }}</text>
                        </view>
                        <view class="price-row" v-if="preview.addon_amount > 0">
                            <text>附加服务金额</text>
                            <text>+¥{{ formatPrice(preview.addon_amount) }}</text>
                        </view>
                        <view class="price-row" v-if="preview.deposit_amount > 0">
                            <text>定金</text>
                            <text>¥{{ formatPrice(preview.deposit_amount) }}</text>
                        </view>
                        <view class="price-row" v-if="preview.balance_amount > 0">
                            <text>尾款</text>
                            <text>¥{{ formatPrice(preview.balance_amount) }}</text>
                        </view>
                        <view class="price-row total">
                            <text>应付金额</text>
                            <text class="text-total">¥{{ formatPrice(preview.pay_amount) }}</text>
                        </view>
                    </view>
                </view>
            </view>
        </view>

        <view class="submit-bar glass-card">
            <view class="submit-price">
                <text class="label">合计</text>
                <text class="symbol" :style="{ color: $theme.ctaColor }">¥</text>
                <text class="value" :style="{ color: $theme.ctaColor }">
                    {{ formatPrice(preview.pay_amount) }}
                </text>
            </view>
            <view
                class="submit-btn"
                :class="{ disabled: !canSubmit }"
                :style="
                    canSubmit
                        ? {
                              background: `linear-gradient(135deg, ${$theme.primaryColor} 0%, ${$theme.primaryColor} 100%)`
                          }
                        : {}
                "
                @click="handleSubmit"
            >
                <text>{{ submitting ? '提交中...' : '提交订单' }}</text>
            </view>
        </view>
    </view>
</template>

<script setup lang="ts">
import { computed, reactive, ref } from 'vue'
import { onLoad, onShow } from '@dcloudio/uni-app'
import { previewOrder, createOrder } from '@/api/order'
import { BACK_URL } from '@/enums/constantEnums'
import { useThemeStore } from '@/stores/theme'
import { useUserStore } from '@/stores/user'
import cache from '@/utils/cache'
import { requestSubscribeByScene } from '@/utils/subscribe'
import {
    buildServiceRegionQuery,
    formatServiceRegionText,
    hasServiceRegion,
    loadServiceRegionSelection,
    normalizeServiceRegion,
    saveServiceRegionSelection,
    toServiceRegionParams
} from '@/utils/service-region'

const $theme = useThemeStore()
const userStore = useUserStore()

const loading = ref(false)
const submitting = ref(false)
const initialized = ref(false)
const selectedAddonIds = ref<number[]>([])
const selection = reactive({
    staff_id: 0,
    package_id: 0,
    date: '',
    province_code: '',
    province_name: '',
    city_code: '',
    city_name: '',
    district_code: '',
    district_name: ''
})

const preview = ref<any>({
    items: [],
    service_amount: 0,
    addon_amount: 0,
    total_amount: 0,
    pay_amount: 0,
    deposit_amount: 0,
    balance_amount: 0
})

const form = reactive({
    contact_name: '',
    contact_mobile: '',
    service_address: '',
    remark: ''
})

const hasItems = computed(
    () => Array.isArray(preview.value.items) && preview.value.items.length > 0
)
const selectedItem = computed(() => (hasItems.value ? preview.value.items[0] || null : null))
const selectedAddons = computed(() =>
    Array.isArray(selectedItem.value?.addons) ? selectedItem.value.addons : []
)
const bookingDateText = computed(() => selectedItem.value?.schedule_date || selection.date || '')
const serviceRegionText = computed(() => formatServiceRegionText(selection, ' / '))
const canSubmit = computed(() => hasItems.value && !loading.value && !submitting.value)
const serviceAmount = computed(() => {
    const amount = Number(preview.value.service_amount ?? -1)
    if (amount >= 0) {
        return amount
    }
    return Math.max(0, Number(preview.value.total_amount || 0) - Number(preview.value.addon_amount || 0))
})

const formatPrice = (value: any) => Number(value || 0).toFixed(2)
const parseAddonIds = (value: unknown): number[] => {
    if (Array.isArray(value)) {
        return value
            .map((item) => Number(item))
            .filter((item, index, list) => item > 0 && list.indexOf(item) === index)
    }

    if (typeof value !== 'string' || !value.trim()) {
        return []
    }

    return value
        .split(',')
        .map((item) => Number(item.trim()))
        .filter((item, index, list) => item > 0 && list.indexOf(item) === index)
}
const serializeAddonIds = (addonIds: number[]) => {
    return addonIds
        .map((item) => Number(item))
        .filter((item, index, list) => item > 0 && list.indexOf(item) === index)
        .join(',')
}

const getConfirmPageUrl = () => {
    const params = [
        `staff_id=${selection.staff_id}`,
        `package_id=${selection.package_id}`,
        `date=${encodeURIComponent(selection.date)}`
    ]
    const regionQuery = buildServiceRegionQuery(selection)
    if (regionQuery) {
        params.push(regionQuery)
    }
    const addonQuery = serializeAddonIds(selectedAddonIds.value)
    if (addonQuery) {
        params.push(`addon_ids=${encodeURIComponent(addonQuery)}`)
    }
    return `/packages/pages/order_confirm/order_confirm?${params.join('&')}`
}

const ensureSubmitLogin = () => {
    if (userStore.isLogin) {
        return true
    }

    cache.set(BACK_URL, getConfirmPageUrl())
    uni.showToast({ title: '请先登录后提交订单', icon: 'none' })
    setTimeout(() => {
        uni.navigateTo({ url: '/pages/login/login' })
    }, 300)
    return false
}

const initContact = async () => {
    await userStore.getUser()
    const info = userStore.userInfo || {}
    if (!form.contact_name) {
        form.contact_name = info.real_name || info.nickname || ''
    }
    if (!form.contact_mobile) {
        form.contact_mobile = info.mobile || ''
    }
}

const buildSelectionParams = (extra: Record<string, any> = {}) => {
    const params: Record<string, any> = {
        staff_id: selection.staff_id,
        package_id: selection.package_id,
        date: selection.date,
        ...toServiceRegionParams(selection),
        ...extra
    }

    if (selectedAddonIds.value.length) {
        params.addon_ids = [...selectedAddonIds.value]
    }

    return params
}

const getStaffDetailUrl = () => {
    if (!selection.staff_id) {
        return ''
    }

    const params = [`id=${selection.staff_id}`]
    const regionQuery = buildServiceRegionQuery(selection)
    if (regionQuery) {
        params.push(regionQuery)
    }
    if (selection.date) {
        params.push(`date=${encodeURIComponent(selection.date)}`)
    }
    if (selection.package_id) {
        params.push(`package_id=${selection.package_id}`)
    }
    const addonQuery = serializeAddonIds(selectedAddonIds.value)
    if (addonQuery) {
        params.push(`addon_ids=${encodeURIComponent(addonQuery)}`)
    }
    params.push('open_booking_popup=1')
    return `/packages/pages/staff_detail/staff_detail?${params.join('&')}`
}

const handlePreviewError = (message: string) => {
    uni.showToast({ title: message, icon: 'none' })
    const url = getStaffDetailUrl()
    setTimeout(() => {
        if (url) {
            uni.redirectTo({ url })
            return
        }
        uni.navigateBack()
    }, 1200)
}

const fetchPreview = async () => {
    loading.value = true
    try {
        const data = await previewOrder(buildSelectionParams())
        preview.value = {
            items: data?.items || [],
            service_amount: data?.service_amount || 0,
            addon_amount: data?.addon_amount || 0,
            total_amount: data?.total_amount || 0,
            pay_amount: data?.pay_amount || 0,
            deposit_amount: data?.deposit_amount || 0,
            balance_amount: data?.balance_amount || 0
        }
        if (!preview.value.items.length) {
            handlePreviewError('暂无可结算的服务')
        }
    } catch (e: any) {
        const errorMsg = typeof e === 'string' ? e : e.msg || e.message || '加载失败'
        handlePreviewError(errorMsg)
    } finally {
        loading.value = false
    }
}

const isValidMobile = (mobile: string) => /^1[3-9]\d{9}$/.test(mobile)

const handleReselect = () => {
    const url = getStaffDetailUrl()
    if (!url) {
        uni.navigateBack()
        return
    }
    uni.redirectTo({ url })
}

const handleSubmit = async () => {
    if (!canSubmit.value) return
    if (!ensureSubmitLogin()) {
        return
    }
    if (!form.contact_name.trim()) {
        uni.showToast({ title: '请输入联系人姓名', icon: 'none' })
        return
    }
    if (!form.contact_mobile.trim()) {
        uni.showToast({ title: '请输入手机号码', icon: 'none' })
        return
    }
    if (!isValidMobile(form.contact_mobile.trim())) {
        uni.showToast({ title: '手机号格式不正确', icon: 'none' })
        return
    }
    if (!form.service_address.trim()) {
        uni.showToast({ title: '请输入详细地址', icon: 'none' })
        return
    }

    submitting.value = true
    try {
        try {
            await requestSubscribeByScene('order_confirm')
        } catch (error) {
            // 订阅授权失败不影响下单
        }

        const params: any = {
            ...buildSelectionParams(),
            contact_name: form.contact_name.trim(),
            contact_mobile: form.contact_mobile.trim(),
            service_address: form.service_address.trim()
        }
        if (form.remark.trim()) params.remark = form.remark.trim()

        const res = await createOrder(params)
        const orderId = Number(res?.order_id || res?.id || 0)
        uni.showToast({ title: '订单已提交', icon: 'success' })
        if (orderId) {
            uni.redirectTo({ url: `/pages/order_detail/order_detail?id=${orderId}` })
        } else {
            uni.redirectTo({ url: '/pages/order/order' })
        }
    } catch (e: any) {
        const errorMsg = typeof e === 'string' ? e : e.msg || e.message || '提交失败'
        uni.showToast({ title: errorMsg, icon: 'none' })
    } finally {
        submitting.value = false
    }
}

const initPage = async () => {
    await initContact()
    await fetchPreview()
    initialized.value = true
}

onLoad((options: any) => {
    selection.staff_id = Number(options?.staff_id || 0)
    selection.package_id = Number(options?.package_id || 0)
    selection.date = options?.date || ''
    const region = normalizeServiceRegion({
        ...loadServiceRegionSelection(),
        ...options
    })
    selection.province_code = region.province_code
    selection.province_name = region.province_name
    selection.city_code = region.city_code
    selection.city_name = region.city_name
    selection.district_code = region.district_code
    selection.district_name = region.district_name

    if (hasServiceRegion(region)) {
        saveServiceRegionSelection(region)
    }
    if (options?.addon_ids) {
        selectedAddonIds.value = parseAddonIds(options.addon_ids)
    }

    if (!selection.staff_id || !selection.package_id || !selection.date || !hasServiceRegion(selection)) {
        handlePreviewError('预约信息不完整，请重新选择服务地区和日期')
        return
    }

    initPage()
})

onShow(() => {
    if (initialized.value) {
        fetchPreview()
    }
})
</script>

<style lang="scss" scoped>
.order-confirm-page {
    min-height: 100vh;
    padding-bottom: 196rpx;
    background: transparent;

    &__hero {
        position: relative;
        padding: 24rpx 24rpx 200rpx;
        background:
            radial-gradient(circle at top right, rgba(255, 255, 255, 0.12) 0, transparent 34%),
            linear-gradient(145deg, rgba(10, 13, 18, 0.98) 0%, rgba(21, 28, 40, 0.96) 54%, rgba(77, 59, 31, 0.92) 100%);
        overflow: hidden;
    }

    &__hero::after {
        content: '';
        position: absolute;
        right: -48rpx;
        top: 40rpx;
        width: 280rpx;
        height: 280rpx;
        border-radius: 50%;
        background: radial-gradient(circle, rgba(232, 201, 142, 0.18) 0, transparent 72%);
        pointer-events: none;
    }

    &__hero-label {
        display: block;
        font-size: 22rpx;
        font-weight: 600;
        letter-spacing: 0.16em;
        text-transform: uppercase;
        color: rgba(255, 248, 236, 0.72);
    }

    &__hero-title {
        display: block;
        margin-top: 22rpx;
        font-size: 48rpx;
        font-weight: 700;
        line-height: 1.22;
        color: var(--cinema-text-inverse, #fff8ea);
    }

    &__hero-desc {
        display: block;
        margin-top: 18rpx;
        max-width: 640rpx;
        font-size: 25rpx;
        line-height: 1.7;
        color: rgba(255, 248, 236, 0.72);
    }

    &__hero-summary {
        position: relative;
        z-index: 1;
        display: flex;
        align-items: stretch;
        gap: 16rpx;
        margin-top: 30rpx;
        padding: 22rpx 24rpx;
        background: rgba(255, 248, 236, 0.1);
    }

    &__hero-item {
        flex: 1;
        min-width: 0;
    }

    &__hero-item-label {
        display: block;
        font-size: 20rpx;
        letter-spacing: 0.1em;
        text-transform: uppercase;
        color: rgba(255, 248, 236, 0.6);
    }

    &__hero-item-value {
        display: block;
        margin-top: 10rpx;
        font-size: 30rpx;
        font-weight: 700;
        line-height: 1.36;
        color: var(--cinema-text-inverse, #fff8ea);
    }

    &__hero-item-value--small {
        font-size: 25rpx;
    }

    &__hero-divider {
        width: 1rpx;
        background: rgba(255, 248, 236, 0.16);
    }

    &__surface {
        position: relative;
        margin-top: -148rpx;
        border-radius: 36rpx 36rpx 0 0;
        padding: 0 24rpx 24rpx;
        box-shadow: 0 -24rpx 48rpx rgba(8, 10, 16, 0.18);
    }

    &__content {
        display: flex;
        flex-direction: column;
        gap: 24rpx;
    }
}

.loading-state {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 24rpx;
    min-height: 56vh;
    margin-bottom: 24rpx;
}

.loading-text {
    color: var(--cinema-text-secondary, #5d6472);
    font-size: 28rpx;
}

.section {
    padding: 26rpx 24rpx;
}

.section-header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 16rpx;
    margin-bottom: 20rpx;
}

.section-header--stack {
    justify-content: flex-start;
}

.section-title {
    display: block;
    font-size: 30rpx;
    font-weight: 700;
    color: var(--cinema-text-primary, #151a23);
}

.section-desc {
    display: block;
    margin-top: 8rpx;
    font-size: 22rpx;
    line-height: 1.6;
    color: var(--cinema-text-secondary, #5d6472);
}

.section-action {
    flex-shrink: 0;
    padding: 12rpx 20rpx;
    border-radius: 999rpx;
    background: rgba(255, 255, 255, 0.78);
    border: 1rpx solid var(--cinema-border, rgba(198, 168, 106, 0.24));
    font-size: 22rpx;
    font-weight: 600;
    color: var(--cinema-primary, #c6a86a);
}

.booking-info-card {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 16rpx;
}

.booking-info-item {
    padding: 24rpx;
    border-radius: 20rpx;
    background: linear-gradient(180deg, rgba(255, 255, 255, 0.94), rgba(247, 243, 234, 0.96));
    border: 1rpx solid rgba(255, 255, 255, 0.72);
}

.booking-info-item__label {
    display: block;
    font-size: 22rpx;
    letter-spacing: 0.08em;
    text-transform: uppercase;
    color: var(--cinema-text-secondary, #5d6472);
}

.booking-info-item__value {
    display: block;
    margin-top: 10rpx;
    font-size: 30rpx;
    font-weight: 700;
    line-height: 1.45;
    color: var(--cinema-text-primary, #151a23);
}

.form-item + .form-item {
    margin-top: 18rpx;
}

.form-label {
    display: block;
    color: var(--cinema-text-primary, #151a23);
    font-size: 26rpx;
    font-weight: 600;
    margin-bottom: 12rpx;
}

.form-shell {
    padding: 4rpx;
    border-radius: 20rpx;
    background: rgba(255, 255, 255, 0.76);
    border: 1rpx solid var(--cinema-border, rgba(198, 168, 106, 0.24));
}

.form-shell :deep(.tn-input) {
    border-radius: 18rpx !important;
}

.service-card {
    padding: 24rpx;
    border-radius: 24rpx;
    background: linear-gradient(180deg, rgba(255, 255, 255, 0.94), rgba(247, 243, 234, 0.96));
    border: 1rpx solid rgba(255, 255, 255, 0.72);
}

.service-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 20rpx;
}

.staff-section {
    display: flex;
    align-items: center;
    gap: 18rpx;
    min-width: 0;
    flex: 1;
}

.staff-avatar {
    width: 92rpx;
    height: 92rpx;
    border-radius: 24rpx;
    border: 2rpx solid rgba(255, 255, 255, 0.92);
    box-shadow: 0 10rpx 24rpx rgba(8, 10, 16, 0.12);
}

.staff-info {
    display: flex;
    flex-direction: column;
    gap: 8rpx;
    min-width: 0;
}

.staff-title {
    font-size: 30rpx;
    font-weight: 700;
    color: var(--cinema-text-primary, #151a23);
}

.staff-subtitle {
    font-size: 22rpx;
    color: var(--cinema-text-secondary, #5d6472);
}

.package-name {
    font-size: 26rpx;
    color: var(--cinema-primary, #c6a86a);
    font-weight: 600;
}

.package-price-wrap {
    display: flex;
    flex-direction: column;
    align-items: flex-end;
    gap: 8rpx;
    flex-shrink: 0;
}

.package-price-label {
    font-size: 22rpx;
    color: var(--cinema-text-secondary, #5d6472);
}

.package-price {
    font-size: 34rpx;
    font-weight: 700;
}

.package-desc {
    display: block;
    margin-top: 22rpx;
    font-size: 24rpx;
    line-height: 1.7;
    color: var(--cinema-text-secondary, #5d6472);
}

.selected-addon-section {
    margin-top: 22rpx;
    padding-top: 20rpx;
    border-top: 1rpx solid var(--cinema-border, rgba(198, 168, 106, 0.24));
}

.selected-addon-section__header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16rpx;
    margin-bottom: 16rpx;
}

.selected-addon-section__title {
    font-size: 26rpx;
    font-weight: 700;
    color: var(--cinema-text-primary, #151a23);
}

.selected-addon-section__meta {
    font-size: 22rpx;
    color: var(--cinema-text-secondary, #5d6472);
}

.selected-addon-list {
    display: flex;
    flex-direction: column;
    gap: 14rpx;
}

.selected-addon-card {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 16rpx;
    padding: 20rpx;
    border-radius: 18rpx;
    background: rgba(255, 255, 255, 0.78);
    border: 1rpx solid rgba(255, 255, 255, 0.72);
}

.selected-addon-card__main {
    flex: 1;
    min-width: 0;
}

.selected-addon-card__title {
    display: block;
    font-size: 26rpx;
    font-weight: 600;
    color: var(--cinema-text-primary, #151a23);
}

.selected-addon-card__desc {
    display: block;
    margin-top: 8rpx;
    font-size: 24rpx;
    line-height: 1.6;
    color: var(--cinema-text-secondary, #5d6472);
}

.selected-addon-card__price {
    font-size: 26rpx;
    font-weight: 700;
    color: var(--cinema-primary, #c6a86a);
}

.price-list {
    display: flex;
    flex-direction: column;
    gap: 8rpx;
}

.price-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 18rpx;
    font-size: 26rpx;
    color: var(--cinema-text-secondary, #5d6472);
    padding: 10rpx 0;
}

.price-row.total {
    margin-top: 8rpx;
    padding-top: 20rpx;
    border-top: 1rpx solid var(--cinema-border, rgba(198, 168, 106, 0.24));
    font-size: 30rpx;
    color: var(--cinema-text-primary, #151a23);
    font-weight: 700;
}

.text-total {
    color: var(--color-cta, #d97706);
    font-weight: 700;
}

.submit-bar {
    position: fixed;
    left: 20rpx;
    right: 20rpx;
    bottom: 20rpx;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 18rpx;
    padding: 18rpx 20rpx;
    border-radius: 28rpx;
    background: rgba(255, 248, 236, 0.86);
    box-shadow: var(--cinema-shadow-medium, 0 20rpx 52rpx rgba(8, 10, 16, 0.12));
    padding-bottom: calc(18rpx + env(safe-area-inset-bottom));
}

.submit-price {
    display: flex;
    align-items: baseline;
    gap: 8rpx;
    min-width: 0;
}

.submit-price .label {
    color: var(--cinema-text-secondary, #5d6472);
    font-size: 24rpx;
}

.submit-price .symbol,
.submit-price .value {
    font-size: 34rpx;
    font-weight: 700;
}

.submit-btn {
    min-width: 232rpx;
    text-align: center;
    padding: 24rpx 34rpx;
    border-radius: 999rpx;
    color: #ffffff;
    font-size: 28rpx;
    font-weight: 700;
    background: #cccccc;
    box-shadow: var(--cinema-shadow-soft, 0 18rpx 44rpx rgba(8, 10, 16, 0.08));
}

.submit-btn.disabled {
    opacity: 0.6;
    box-shadow: none;
}
</style>
