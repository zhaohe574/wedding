<template>
    <page-meta :page-style="$theme.pageStyle">
        <navigation-bar
            title="编辑套餐"
            :front-color="$theme.navColor"
            :background-color="$theme.navBgColor"
        />
    </page-meta>

    <view class="page-container">
        <!-- 套餐名称卡片 -->
        <view class="info-card">
            <view class="card-title-row">
                <tn-icon name="gift" size="30" :color="$theme.primaryColor" />
                <text class="card-title">{{ form.name || '套餐信息' }}</text>
                <view
                    v-if="form.is_staff_only"
                    class="badge-exclusive"
                    :style="{ background: `${$theme.primaryColor}15`, color: $theme.primaryColor }"
                >
                    专属
                </view>
            </view>
        </view>

        <!-- 价格设置 -->
        <view class="form-card">
            <view class="card-title-row">
                <tn-icon name="money" size="30" :color="$theme.primaryColor" />
                <text class="card-title">价格设置</text>
            </view>

            <view class="form-item">
                <view class="form-label-wrap">
                    <text class="form-label">个人统一价</text>
                    <text class="form-hint">覆盖套餐基础价和场次价</text>
                </view>
                <tn-input
                    v-model="form.custom_price"
                    type="number"
                    :placeholder="`参考: ¥${packageBasePrice}`"
                    class="form-input-right"
                    :border="false"
                />
            </view>
            <view class="form-item">
                <view class="form-label-wrap">
                    <text class="form-label">展示原价</text>
                    <text class="form-hint">划线价，仅展示用</text>
                </view>
                <tn-input
                    v-model="form.original_price"
                    type="number"
                    placeholder="可选"
                    class="form-input-right"
                    :border="false"
                />
            </view>
            <view class="form-item no-border">
                <text class="form-label">启用状态</text>
                <u-switch
                    v-model="statusSwitch"
                    :active-color="$theme.primaryColor"
                    inactive-color="#E5E7EB"
                    size="24"
                />
            </view>
        </view>

        <!-- 预约类型 & 场次设置 -->
        <view class="form-card">
            <view class="card-title-row">
                <tn-icon name="calendar" size="30" :color="$theme.primaryColor" />
                <text class="card-title">场次设置</text>
            </view>

            <view class="type-selector">
                <view
                    class="type-option"
                    :style="
                        form.booking_type === 0
                            ? {
                                  background: `linear-gradient(135deg, ${$theme.primaryColor} 0%, ${$theme.primaryColor} 100%)`,
                                  color: '#FFFFFF'
                              }
                            : {}
                    "
                    @click="form.booking_type = 0"
                >
                    全天
                </view>
                <view
                    class="type-option"
                    :style="
                        form.booking_type === 1
                            ? {
                                  background: `linear-gradient(135deg, ${$theme.primaryColor} 0%, ${$theme.primaryColor} 100%)`,
                                  color: '#FFFFFF'
                              }
                            : {}
                    "
                    @click="form.booking_type = 1"
                >
                    分场次
                </view>
            </view>

            <!-- 分场次详细设置 -->
            <view v-if="form.booking_type === 1" class="slot-section">
                <text class="slot-title">允许场次</text>
                <view class="slot-options">
                    <view
                        v-for="slot in timeSlots"
                        :key="slot.value"
                        class="slot-option"
                        :style="
                            form.allowed_time_slots.includes(slot.value)
                                ? {
                                      background: `${$theme.primaryColor}15`,
                                      color: $theme.primaryColor,
                                      borderColor: $theme.primaryColor
                                  }
                                : {}
                        "
                        @click="toggleSlot(slot.value)"
                    >
                        {{ slot.label }}
                    </view>
                </view>

                <!-- 分场次价格设置 -->
                <view v-if="form.allowed_time_slots.length > 0" class="slot-prices">
                    <view class="slot-prices-header">
                        <text class="slot-prices-title">场次价格</text>
                        <text class="slot-prices-hint">不填则按优先级取价</text>
                    </view>
                    <view
                        v-for="slotValue in sortedAllowedSlots"
                        :key="slotValue"
                        class="slot-price-row"
                    >
                        <view class="slot-price-label">
                            <text class="slot-name">{{ getSlotLabel(slotValue) }}</text>
                            <text class="slot-ref-price">
                                基础: ¥{{ getPackageSlotPrice(slotValue) }}
                            </text>
                        </view>
                        <tn-input
                            v-model="slotPriceMap[slotValue]"
                            type="number"
                            :placeholder="`¥${getPackageSlotPrice(slotValue)}`"
                            class="slot-price-input"
                            :border="false"
                        />
                    </view>
                </view>
            </view>
        </view>

        <!-- 保存按钮 -->
        <view class="save-wrapper">
            <view
                class="save-btn"
                :style="{
                    background: `linear-gradient(135deg, ${$theme.primaryColor} 0%, ${$theme.primaryColor} 100%)`,
                    color: $theme.btnColor,
                    opacity: saving ? 0.6 : 1
                }"
                @click="handleSave"
            >
                <tn-icon v-if="saving" name="loading" size="32" :color="$theme.btnColor" />
                <text>{{ saving ? '保存中...' : '保存' }}</text>
            </view>
        </view>
    </view>
</template>

<script setup lang="ts">
import { computed, getCurrentInstance, reactive, ref } from 'vue'
import { onLoad } from '@dcloudio/uni-app'
import { staffCenterPackageLists, staffCenterPackageUpdate } from '@/api/staffCenter'
import { ensureStaffCenterAccess } from '@/utils/staff-center'
import { useThemeStore } from '@/stores/theme'

const $theme = useThemeStore()
const saving = ref(false)

// 场次价格用 reactive map 存储，方便 v-model 绑定
const slotPriceMap = reactive<Record<number, string>>({})

const form = reactive({
    package_id: 0,
    name: '',
    price: '',
    original_price: '',
    custom_price: '',
    booking_type: 0,
    allowed_time_slots: [] as number[],
    status: 1,
    is_staff_only: false,
    package: null as any
})

const timeSlots = [
    { value: 1, label: '早礼' },
    { value: 2, label: '午宴' },
    { value: 3, label: '晚宴' }
]

// 套餐基础价格
const packageBasePrice = computed(() => {
    return form.package?.price ?? form.price ?? '0'
})

// 排序后的已选场次
const sortedAllowedSlots = computed(() => {
    return [...form.allowed_time_slots].sort((a, b) => a - b)
})

const statusSwitch = computed({
    get: () => form.status === 1,
    set: (val: boolean) => {
        form.status = val ? 1 : 0
    }
})

// 获取场次标签
const getSlotLabel = (value: number): string => {
    return timeSlots.find((s) => s.value === value)?.label || `场次${value}`
}

// 获取套餐基础场次价格
const getPackageSlotPrice = (slotValue: number): string => {
    const slotPrices = form.package?.slot_prices || []
    const found = slotPrices.find((s: any) => Number(s.time_slot) === slotValue)
    if (found && found.price !== undefined && found.price !== null) {
        return String(found.price)
    }
    return String(form.package?.price ?? form.price ?? '0')
}

const fillForm = (data: any) => {
    form.package_id = Number(data.package_id || data.id || 0)
    form.name = data.package?.name || data.name || ''
    form.price = data.price !== undefined && data.price !== null ? String(data.price) : ''
    form.original_price =
        data.original_price !== undefined && data.original_price !== null
            ? String(data.original_price)
            : ''
    form.custom_price =
        data.custom_price !== undefined && data.custom_price !== null
            ? String(data.custom_price)
            : ''
    // 个人场次价格 → 写入 slotPriceMap
    const rawSlotPrices = data.custom_slot_prices || []
    // 先清空
    Object.keys(slotPriceMap).forEach((k) => delete slotPriceMap[Number(k)])
    if (Array.isArray(rawSlotPrices)) {
        rawSlotPrices.forEach((s: any) => {
            const ts = Number(s.time_slot)
            slotPriceMap[ts] = s.price !== undefined && s.price !== null ? String(s.price) : ''
        })
    }
    form.booking_type = Number(data.booking_type ?? data.package?.booking_type ?? 0)
    form.allowed_time_slots = Array.isArray(data.allowed_time_slots)
        ? data.allowed_time_slots.map(Number)
        : []
    form.status = data.status ? 1 : 0
    form.is_staff_only = !!data.is_staff_only
    form.package = data.package || null
}

const toggleSlot = (value: number) => {
    const index = form.allowed_time_slots.indexOf(value)
    if (index >= 0) {
        form.allowed_time_slots.splice(index, 1)
        // 移除对应的场次价格
        delete slotPriceMap[value]
    } else {
        form.allowed_time_slots.push(value)
    }
}

const loadFallback = async (packageId: number) => {
    const data = await staffCenterPackageLists()
    const item = (data?.configured || []).find((pkg: any) => Number(pkg.package_id) === packageId)
    if (item) fillForm(item)
}

const handleSave = async () => {
    if (!form.package_id) {
        uni.showToast({ title: '套餐信息缺失', icon: 'none' })
        return
    }
    const payload: any = {
        package_id: form.package_id,
        status: form.status,
        booking_type: form.booking_type
    }
    if (form.price !== '') payload.price = Number(form.price)
    if (form.original_price !== '') payload.original_price = Number(form.original_price)
    if (form.custom_price !== '') {
        payload.custom_price = Number(form.custom_price)
    } else {
        payload.custom_price = null
    }
    payload.allowed_time_slots = form.booking_type === 1 ? form.allowed_time_slots : []

    // 分场次价格：只提交有值的
    if (form.booking_type === 1) {
        const validSlotPrices = form.allowed_time_slots
            .filter((ts: number) => slotPriceMap[ts] && slotPriceMap[ts] !== '')
            .map((ts: number) => ({ time_slot: ts, price: Number(slotPriceMap[ts]) }))
        payload.custom_slot_prices = validSlotPrices.length > 0 ? validSlotPrices : []
    } else {
        payload.custom_slot_prices = []
    }

    saving.value = true
    try {
        await staffCenterPackageUpdate(payload)
        uni.showToast({ title: '保存成功', icon: 'success' })
        setTimeout(() => uni.navigateBack(), 1200)
    } catch (e: any) {
        const msg = typeof e === 'string' ? e : e?.msg || e?.message || '保存失败'
        uni.showToast({ title: msg, icon: 'none' })
    } finally {
        saving.value = false
    }
}

onLoad(async (options: any) => {
    if (!(await ensureStaffCenterAccess())) return
    const packageId = Number(options?.package_id || 0)
    const instance = getCurrentInstance()
    const channel = instance?.proxy?.getOpenerEventChannel?.()
    channel?.on('detail', (data: any) => fillForm(data))
    if (packageId && !form.package_id) await loadFallback(packageId)
})
</script>

<style lang="scss" scoped>
.page-container {
    min-height: 100vh;
    background: #F4F5F7;
    padding-bottom: 60rpx;
}

.info-card,
.form-card {
    margin: 20rpx 24rpx 0;
    padding: 28rpx;
    background: #FFFFFF;
    border-radius: 24rpx;
    box-shadow: 0 4rpx 24rpx rgba(0, 0, 0, 0.05);
}

.card-title-row {
    display: flex;
    align-items: center;
    gap: 10rpx;
    margin-bottom: 20rpx;
}

.card-title {
    flex: 1;
    font-size: 30rpx;
    font-weight: 700;
    color: #1F2937;
}

.badge-exclusive {
    font-size: 22rpx;
    font-weight: 600;
    padding: 4rpx 16rpx;
    border-radius: 20rpx;
}

/* 表单项 */
.form-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 24rpx 0;
    border-bottom: 1rpx solid #F3F4F6;

    &.no-border {
        border-bottom: none;
    }
}

.form-label-wrap {
    display: flex;
    flex-direction: column;
    min-width: 200rpx;
}

.form-label {
    font-size: 28rpx;
    color: #374151;
    font-weight: 500;
    min-width: 180rpx;
}

.form-hint {
    font-size: 22rpx;
    color: #9CA3AF;
    margin-top: 4rpx;
}

.form-input-right {
    flex: 1;
    text-align: right;
    font-size: 28rpx;
}

/* 预约类型选择 */
.type-selector {
    display: flex;
    gap: 16rpx;
}

.type-option {
    flex: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    height: 72rpx;
    border-radius: 36rpx;
    font-size: 28rpx;
    font-weight: 600;
    background: #F3F4F6;
    color: #6B7280;
    transition: all 0.2s ease;

    &:active {
        opacity: 0.8;
    }
}

.slot-section {
    margin-top: 24rpx;
    padding-top: 24rpx;
    border-top: 1rpx solid #F3F4F6;
}

.slot-title {
    font-size: 28rpx;
    font-weight: 600;
    color: #374151;
    margin-bottom: 16rpx;
}

.slot-options {
    display: flex;
    flex-wrap: wrap;
    gap: 12rpx;
}

.slot-option {
    padding: 12rpx 28rpx;
    border-radius: 32rpx;
    font-size: 26rpx;
    font-weight: 500;
    background: #F3F4F6;
    color: #6B7280;
    border: 2rpx solid transparent;
    transition: all 0.2s ease;

    &:active {
        opacity: 0.8;
    }
}

/* 分场次价格 */
.slot-prices {
    margin-top: 24rpx;
    padding-top: 24rpx;
    border-top: 1rpx solid #F3F4F6;
}

.slot-prices-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 16rpx;
}

.slot-prices-title {
    font-size: 28rpx;
    font-weight: 600;
    color: #374151;
}

.slot-prices-hint {
    font-size: 22rpx;
    color: #9CA3AF;
}

.slot-price-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 16rpx 0;
    border-bottom: 1rpx solid #F3F4F6;

    &:last-child {
        border-bottom: none;
    }
}

.slot-price-label {
    display: flex;
    flex-direction: column;
    min-width: 180rpx;
}

.slot-name {
    font-size: 26rpx;
    font-weight: 500;
    color: #374151;
}

.slot-ref-price {
    font-size: 22rpx;
    color: #9CA3AF;
    margin-top: 4rpx;
}

.slot-price-input {
    flex: 1;
    max-width: 280rpx;
    text-align: right;
    font-size: 28rpx;
}

/* 保存按钮 */
.save-wrapper {
    margin: 40rpx 24rpx 0;
}

.save-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10rpx;
    height: 88rpx;
    border-radius: 44rpx;
    font-size: 32rpx;
    font-weight: 700;
    box-shadow: 0 8rpx 24rpx rgba(124, 58, 237, 0.25);
    transition: all 0.2s ease;

    &:active {
        transform: translateY(2rpx);
        box-shadow: 0 4rpx 12rpx rgba(124, 58, 237, 0.25);
    }
}
</style>
