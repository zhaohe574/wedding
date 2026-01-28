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
                    <text class="form-label">服务地址</text>
                    <tn-input
                        v-model="form.service_address"
                        placeholder="请输入服务地址（选填）"
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

            <view class="section" v-if="hasItems">
                <view class="section-title">服务项目</view>
                <view class="service-group" v-for="group in groupedItems" :key="group.key">
                    <view class="group-header">
                        <view class="staff-section">
                            <image
                                :src="group.staff?.avatar || '/static/images/default-avatar.png'"
                                class="staff-avatar"
                                mode="aspectFill"
                            />
                            <view class="staff-info">
                                <text class="staff-name">人员：{{ group.staff?.name || '服务人员' }}</text>
                                <text class="staff-subtitle">日期：{{ group.schedule_date || '未指定日期' }}</text>
                            </view>
                        </view>
                        <view class="group-total">
                            <text class="group-total-label">小计</text>
                            <text class="group-total-value" :style="{ color: $theme.ctaColor }">
                                ¥{{ formatPrice(group.total_price) }}
                            </text>
                        </view>
                    </view>

                    <view class="group-packages">
                        <view
                            class="package-group"
                            v-for="pkg in group.packages"
                            :key="pkg.key"
                        >
                            <view class="package-header">
                                <view class="package-title">
                                    <tn-icon name="gift" size="24" />
                                    <text>套餐：{{ pkg.package?.name || '服务套餐' }}</text>
                                </view>
                                <text class="package-total" :style="{ color: $theme.ctaColor }">
                                    ¥{{ formatPrice(pkg.total_price) }}
                                </text>
                            </view>

                            <view class="package-items">
                                <view class="package-item" v-for="item in pkg.items" :key="item.id">
                                    <view class="slot-info">
                                        <view class="slot-row">
                                            <text class="slot-label">场次：{{ getTimeSlotLabel(item) }}</text>
                                            <text class="slot-price" :style="{ color: $theme.ctaColor }">
                                                ¥{{ formatPrice(item.price) }}
                                            </text>
                                        </view>
                                    </view>
                                </view>
                            </view>
                        </view>
                    </view>
                </view>
            </view>

            <view class="section" v-if="hasItems">
                <view class="section-title">费用明细</view>
                <view class="price-row">
                    <text>商品金额</text>
                    <text>¥{{ formatPrice(preview.total_amount) }}</text>
                </view>
                <view class="price-row" v-if="preview.coupon_amount > 0">
                    <text>优惠金额</text>
                    <text class="text-discount">-¥{{ formatPrice(preview.coupon_amount) }}</text>
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
                :style="canSubmit ? {
                    background: `linear-gradient(135deg, ${$theme.primaryColor} 0%, ${$theme.primaryColor} 100%)`
                } : {}"
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
import { useThemeStore } from '@/stores/theme'
import { useUserStore } from '@/stores/user'

const $theme = useThemeStore()
const userStore = useUserStore()

const loading = ref(false)
const submitting = ref(false)
const initialized = ref(false)

const preview = ref<any>({
    items: [],
    total_amount: 0,
    coupon_amount: 0,
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

const hasItems = computed(() => Array.isArray(preview.value.items) && preview.value.items.length > 0)
const canSubmit = computed(() => hasItems.value && !loading.value && !submitting.value)

const formatPrice = (value: any) => Number(value || 0).toFixed(2)

const groupedItems = computed(() => {
    const groups: any[] = []
    const groupMap = new Map<string, any>()
    const items = preview.value.items || []

    items.forEach((item: any) => {
        const staffId = Number(item.staff_id || 0)
        const date = item.schedule_date || ''
        const key = `${staffId}-${date}`
        let group = groupMap.get(key)
        if (!group) {
            group = {
                key,
                staff: item.staff,
                schedule_date: date,
                packages: [],
                total_price: 0,
                packageMap: new Map<number, any>()
            }
            groupMap.set(key, group)
            groups.push(group)
        }

        group.total_price += Number(item.price || 0)

        const pkgKey = Number(item.package_id || 0)
        let pkgGroup = group.packageMap.get(pkgKey)
        if (!pkgGroup) {
            pkgGroup = {
                key: `${key}-${pkgKey}`,
                package: item.package,
                items: [],
                total_price: 0
            }
            group.packageMap.set(pkgKey, pkgGroup)
            group.packages.push(pkgGroup)
        }

        pkgGroup.items.push(item)
        pkgGroup.total_price += Number(item.price || 0)
    })

    groups.forEach((group) => {
        group.packages.forEach((pkg: any) => {
            pkg.items.sort((a: any, b: any) => Number(a.time_slot || 0) - Number(b.time_slot || 0))
        })
        delete group.packageMap
    })

    return groups
})

const getTimeSlotLabel = (item: any) => {
    if (item?.time_slot_desc) {
        return item.time_slot_desc
    }
    const map: Record<number, string> = {
        0: '全天',
        1: '早礼',
        2: '午宴',
        3: '晚宴'
    }
    const slot = Number(item?.time_slot)
    return Number.isFinite(slot) ? (map[slot] || '未知场次') : '未知场次'
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

const handlePreviewError = (message: string) => {
    uni.showToast({ title: message, icon: 'none' })
    uni.redirectTo({ url: '/packages/pages/cart/cart' })
}

const fetchPreview = async () => {
    loading.value = true
    try {
        const data = await previewOrder({})
        preview.value = {
            items: data?.items || [],
            total_amount: data?.total_amount || 0,
            coupon_amount: data?.coupon_amount || 0,
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

const handleSubmit = async () => {
    if (!canSubmit.value) return
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

    submitting.value = true
    try {
        const params: any = {
            contact_name: form.contact_name.trim(),
            contact_mobile: form.contact_mobile.trim()
        }
        if (form.service_address.trim()) params.service_address = form.service_address.trim()
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

onLoad(() => {
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

.form-item + .form-item {
    margin-top: 16rpx;
}

.form-label {
    display: block;
    color: #666666;
    font-size: 26rpx;
    margin-bottom: 12rpx;
}

.service-group + .service-group {
    margin-top: 24rpx;
}

.group-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 16rpx;
}

.staff-section {
    display: flex;
    align-items: center;
    gap: 16rpx;
}

.staff-avatar {
    width: 88rpx;
    height: 88rpx;
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

.group-total {
    text-align: right;
}

.group-total-label {
    display: block;
    font-size: 22rpx;
    color: #999999;
}

.group-total-value {
    font-size: 28rpx;
    font-weight: 600;
}

.group-packages {
    display: flex;
    flex-direction: column;
    gap: 16rpx;
}

.package-group {
    background: #f9fafb;
    border-radius: 16rpx;
    padding: 20rpx;
    border: 1rpx solid #f0f0f0;
}

.package-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 12rpx;
}

.package-title {
    display: flex;
    align-items: center;
    gap: 8rpx;
    font-size: 26rpx;
    font-weight: 600;
    color: #333333;
}

.package-total {
    font-size: 26rpx;
    font-weight: 600;
}

.package-items {
    display: flex;
    flex-direction: column;
    gap: 12rpx;
}

.package-item {
    display: flex;
    align-items: center;
    padding: 16rpx;
    background: #ffffff;
    border-radius: 12rpx;
    border: 1rpx solid #eeeeee;
}

.slot-info {
    flex: 1;
    display: flex;
    flex-direction: column;
    gap: 6rpx;
}

.slot-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12rpx;
}

.slot-label {
    font-size: 24rpx;
    color: #666666;
}

.slot-price {
    font-size: 26rpx;
    font-weight: 600;
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
