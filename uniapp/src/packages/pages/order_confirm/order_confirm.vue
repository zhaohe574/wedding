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

    <view class="order-confirm-page">
        <view v-if="loading" class="loading-state">
            <tn-loading size="60" mode="flower" />
            <text class="loading-text">加载中...</text>
        </view>

        <view v-else>
            <view class="section">
                <view class="section-title">预约信息</view>
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

            <view class="section">
                <view class="section-title">联系人信息</view>
                <view class="form-item">
                    <text class="form-label">联系人</text>
                    <tn-input
                        v-model="form.contact_name"
                        placeholder="请输入联系人姓名"
                        :border="true"
                        height="80"
                    />
                </view>
                <view class="form-item">
                    <text class="form-label">手机号码</text>
                    <tn-input
                        v-model="form.contact_mobile"
                        placeholder="请输入手机号码"
                        type="number"
                        :border="true"
                        height="80"
                    />
                </view>
                <view class="form-item">
                    <text class="form-label">详细地址</text>
                    <tn-input
                        v-model="form.service_address"
                        placeholder="请输入详细地址"
                        :border="true"
                        height="80"
                    />
                </view>
                <view class="form-item">
                    <text class="form-label">备注</text>
                    <tn-input
                        v-model="form.remark"
                        type="textarea"
                        placeholder="请输入备注（选填）"
                        :border="true"
                        height="120"
                    />
                </view>
            </view>

            <view class="section" v-if="selectedItem">
                <view class="section-header">
                    <view class="section-title">服务项目</view>
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
                                <text class="staff-name"
                                    >人员：{{ selectedItem.staff?.name || '服务人员' }}</text
                                >
                                <text class="package-name"
                                    >套餐：{{ selectedItem.package?.name || '服务套餐' }}</text
                                >
                            </view>
                        </view>
                        <text class="package-price" :style="{ color: $theme.ctaColor }">
                            ¥{{ formatPrice(selectedItem.price) }}
                        </text>
                    </view>
                    <text v-if="selectedItem.package?.description" class="package-desc">
                        {{ selectedItem.package?.description }}
                    </text>
                    <view v-if="selectedAddons.length" class="selected-addon-section">
                        <view class="selected-addon-section__header">
                            <text class="selected-addon-section__title">已选附加服务</text>
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

            <view class="section" v-if="hasItems">
                <view class="section-title">费用明细</view>
                <view class="price-row">
                    <text>主服务金额</text>
                    <text>¥{{ formatPrice(serviceAmount) }}</text>
                </view>
                <view class="price-row" v-if="preview.addon_amount > 0">
                    <text>附加服务金额</text>
                    <text>+¥{{ formatPrice(preview.addon_amount) }}</text>
                </view>
                <view class="price-row total">
                    <text>应付金额</text>
                    <text class="text-total">¥{{ formatPrice(preview.pay_amount) }}</text>
                </view>
            </view>
        </view>

        <view class="submit-bar">
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
    background: #f5f5f5;
    padding-bottom: 180rpx;
}

.loading-state {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 200rpx 48rpx;
}

.loading-text {
    margin-top: 24rpx;
    color: #999999;
    font-size: 28rpx;
}

.section {
    background: #ffffff;
    margin: 24rpx;
    padding: 24rpx;
    border-radius: 20rpx;
    box-shadow: 0 12rpx 32rpx rgba(0, 0, 0, 0.04);
}

.section-title {
    font-size: 30rpx;
    font-weight: 600;
    color: #333333;
    margin-bottom: 20rpx;
}

.section-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16rpx;
    margin-bottom: 20rpx;
}

.section-header .section-title {
    margin-bottom: 0;
}

.section-action {
    font-size: 24rpx;
    font-weight: 600;
    color: #d85c61;
}

.booking-info-card {
    display: flex;
    flex-direction: column;
    gap: 16rpx;
}

.booking-info-item {
    padding: 20rpx 24rpx;
    border-radius: 18rpx;
    background: #f9fafb;
    border: 1rpx solid #edf0f3;
}

.booking-info-item__label {
    display: block;
    font-size: 24rpx;
    color: #999999;
}

.booking-info-item__value {
    display: block;
    margin-top: 10rpx;
    font-size: 28rpx;
    font-weight: 600;
    line-height: 1.5;
    color: #333333;
}

.form-item + .form-item {
    margin-top: 16rpx;
}

.form-label {
    display: block;
    color: #666666;
    font-size: 26rpx;
    margin-bottom: 12rpx;
}

.service-card {
    background: #f9fafb;
    border-radius: 20rpx;
    padding: 24rpx;
    border: 1rpx solid #f0f0f0;
}

.selected-addon-section {
    margin-top: 20rpx;
    padding-top: 20rpx;
    border-top: 1rpx solid #ebeef2;
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
    font-weight: 600;
    color: #333333;
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
    background: #ffffff;
    border: 1rpx solid #edf0f3;
}

.selected-addon-card__main {
    flex: 1;
    min-width: 0;
}

.selected-addon-card__title {
    display: block;
    font-size: 26rpx;
    font-weight: 600;
    color: #333333;
}

.selected-addon-card__desc {
    display: block;
    margin-top: 8rpx;
    font-size: 24rpx;
    line-height: 1.6;
    color: #7a7a7a;
}

.selected-addon-card__price {
    font-size: 26rpx;
    font-weight: 700;
    color: #d85c61;
}

.addon-tip {
    margin-bottom: 18rpx;
    font-size: 24rpx;
    color: #8c8c8c;
}

.addon-list {
    display: flex;
    flex-direction: column;
    gap: 16rpx;
}

.addon-card {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 20rpx;
    padding: 22rpx;
    border-radius: 20rpx;
    border: 2rpx solid #edf0f3;
    background: linear-gradient(180deg, #ffffff 0%, #fafbfd 100%);
    transition: all 0.2s ease;
}

.addon-card--active {
    background: #fffaf5;
}

.addon-card__main {
    flex: 1;
    min-width: 0;
}

.addon-card__title-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16rpx;
}

.addon-card__title {
    flex: 1;
    min-width: 0;
    font-size: 28rpx;
    font-weight: 600;
    color: #333333;
}

.addon-card__desc {
    display: block;
    margin-top: 10rpx;
    font-size: 24rpx;
    line-height: 1.6;
    color: #7a7a7a;
}

.addon-card__check {
    width: 36rpx;
    height: 36rpx;
    border-radius: 50%;
    border: 2rpx solid #d7dce2;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    background: #ffffff;
}

.addon-card__price {
    min-width: 140rpx;
    text-align: right;
    display: flex;
    flex-direction: column;
    align-items: flex-end;
    gap: 8rpx;
}

.addon-card__price-current {
    font-size: 28rpx;
    font-weight: 700;
    color: #d85c61;
}

.addon-card__price-original {
    font-size: 22rpx;
    color: #a0a0a0;
    text-decoration: line-through;
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
    gap: 16rpx;
}

.staff-avatar {
    width: 80rpx;
    height: 80rpx;
    border-radius: 16rpx;
}

.staff-info {
    display: flex;
    flex-direction: column;
    gap: 6rpx;
}

.staff-name {
    font-size: 28rpx;
    font-weight: 600;
    color: #333333;
}

.staff-subtitle {
    font-size: 24rpx;
    color: #999999;
}

.package-name {
    font-size: 26rpx;
    color: #333333;
    font-weight: 600;
}

.package-price {
    font-size: 28rpx;
    font-weight: 600;
}

.package-desc {
    display: block;
    margin-top: 20rpx;
    font-size: 24rpx;
    color: #666666;
}

.price-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-size: 26rpx;
    color: #666666;
    padding: 10rpx 0;
}

.price-row.total {
    font-size: 28rpx;
    color: #333333;
    font-weight: 600;
}

.text-discount {
    color: #ff4d4f;
}

.text-total {
    color: #ff4d4f;
    font-weight: 600;
}

.submit-bar {
    position: fixed;
    left: 0;
    right: 0;
    bottom: 0;
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 20rpx 24rpx;
    background: #ffffff;
    box-shadow: 0 -8rpx 24rpx rgba(0, 0, 0, 0.06);
    padding-bottom: calc(20rpx + env(safe-area-inset-bottom));
}

.submit-price {
    display: flex;
    align-items: baseline;
    gap: 8rpx;
}

.submit-price .label {
    color: #666666;
    font-size: 26rpx;
}

.submit-price .symbol,
.submit-price .value {
    font-size: 32rpx;
    font-weight: 600;
}

.submit-btn {
    min-width: 200rpx;
    text-align: center;
    padding: 22rpx 32rpx;
    border-radius: 48rpx;
    color: #ffffff;
    font-size: 28rpx;
    font-weight: 600;
    background: #cccccc;
}

.submit-btn.disabled {
    opacity: 0.6;
}
</style>
