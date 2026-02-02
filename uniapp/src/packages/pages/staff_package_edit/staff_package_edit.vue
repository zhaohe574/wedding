<template>
    <page-meta :page-style="$theme.pageStyle">
        <navigation-bar title="编辑套餐" :front-color="$theme.navColor" :background-color="$theme.navBgColor" />
    </page-meta>

    <view class="min-h-screen bg-[#f6f6f6] pb-[40rpx]">
        <view class="bg-white mx-[24rpx] mt-[24rpx] rounded-lg p-[24rpx]">
            <view class="text-base font-semibold text-[#333]">{{ form.name || '套餐信息' }}</view>
            <view class="text-xs text-gray-400 mt-[8rpx]">
                基础价格：¥{{ basePrice }}
            </view>
        </view>

        <view class="bg-white mx-[24rpx] mt-[20rpx] rounded-lg">
            <view class="flex items-center justify-between px-[24rpx] py-[26rpx] border-b border-solid border-0 border-light">
                <view class="text-sm text-[#333]">套餐价格</view>
                <tn-input v-model="form.price" type="number" placeholder="默认价格" class="text-right" />
            </view>
            <view class="flex items-center justify-between px-[24rpx] py-[26rpx] border-b border-solid border-0 border-light">
                <view class="text-sm text-[#333]">原价</view>
                <tn-input v-model="form.original_price" type="number" placeholder="可选" class="text-right" />
            </view>
            <view class="flex items-center justify-between px-[24rpx] py-[26rpx] border-b border-solid border-0 border-light">
                <view class="text-sm text-[#333]">个人统一价</view>
                <tn-input v-model="form.custom_price" type="number" placeholder="可选" class="text-right" />
            </view>
            <view class="flex items-center justify-between px-[24rpx] py-[26rpx] border-b border-solid border-0 border-light">
                <view class="text-sm text-[#333]">启用状态</view>
                <u-switch v-model="statusSwitch" active-color="#16a34a" inactive-color="#e5e7eb" />
            </view>
        </view>

        <view class="bg-white mx-[24rpx] mt-[20rpx] rounded-lg p-[24rpx]">
            <view class="text-sm font-medium mb-[16rpx]">预约类型</view>
            <view class="flex gap-[16rpx]">
                <view
                    class="px-[24rpx] py-[12rpx] rounded-full text-sm"
                    :class="form.booking_type === 0 ? 'bg-primary text-white' : 'bg-gray-100 text-gray-500'"
                    @click="form.booking_type = 0"
                >
                    全天
                </view>
                <view
                    class="px-[24rpx] py-[12rpx] rounded-full text-sm"
                    :class="form.booking_type === 1 ? 'bg-primary text-white' : 'bg-gray-100 text-gray-500'"
                    @click="form.booking_type = 1"
                >
                    分场次
                </view>
            </view>

            <view v-if="form.booking_type === 1" class="mt-[20rpx]">
                <view class="text-sm font-medium mb-[12rpx]">允许场次</view>
                <view class="flex flex-wrap gap-[12rpx]">
                    <view
                        v-for="slot in timeSlots"
                        :key="slot.value"
                        class="px-[20rpx] py-[10rpx] rounded-full text-xs"
                        :class="form.allowed_time_slots.includes(slot.value) ? 'bg-primary text-white' : 'bg-gray-100 text-gray-500'"
                        @click="toggleSlot(slot.value)"
                    >
                        {{ slot.label }}
                    </view>
                </view>
            </view>
        </view>

        <view class="mx-[24rpx] mt-[30rpx]">
            <tn-button type="primary" shape="round" size="lg" :loading="saving" @click="handleSave">
                保存
            </tn-button>
        </view>
    </view>
</template>

<script setup lang="ts">
import { computed, getCurrentInstance, reactive, ref } from 'vue'
import { onLoad } from '@dcloudio/uni-app'
import { staffCenterPackageLists, staffCenterPackageUpdate } from '@/api/staffCenter'
import { ensureStaffCenterAccess } from '@/utils/staff-center'

const saving = ref(false)
const form = reactive({
    package_id: 0,
    name: '',
    price: '',
    original_price: '',
    custom_price: '',
    booking_type: 0,
    allowed_time_slots: [] as number[],
    status: 1,
    package: null as any
})

const timeSlots = [
    { value: 1, label: '早礼' },
    { value: 2, label: '午宴' },
    { value: 3, label: '晚宴' }
]

const basePrice = computed(() => form.package?.price ?? form.price ?? 0)

const statusSwitch = computed({
    get: () => form.status === 1,
    set: (val: boolean) => {
        form.status = val ? 1 : 0
    }
})

const fillForm = (data: any) => {
    form.package_id = Number(data.package_id || data.id || 0)
    form.name = data.package?.name || data.name || ''
    form.price = data.price !== undefined && data.price !== null ? String(data.price) : ''
    form.original_price =
        data.original_price !== undefined && data.original_price !== null ? String(data.original_price) : ''
    form.custom_price =
        data.custom_price !== undefined && data.custom_price !== null ? String(data.custom_price) : ''
    form.booking_type = Number(data.booking_type ?? data.package?.booking_type ?? 0)
    form.allowed_time_slots = Array.isArray(data.allowed_time_slots) ? data.allowed_time_slots : []
    form.status = data.status ? 1 : 0
    form.package = data.package || null
}

const toggleSlot = (value: number) => {
    const index = form.allowed_time_slots.indexOf(value)
    if (index >= 0) {
        form.allowed_time_slots.splice(index, 1)
    } else {
        form.allowed_time_slots.push(value)
    }
}

const loadFallback = async (packageId: number) => {
    const data = await staffCenterPackageLists()
    const item = (data?.configured || []).find((pkg: any) => Number(pkg.package_id) === packageId)
    if (item) {
        fillForm(item)
    }
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
    if (form.custom_price !== '') payload.custom_price = Number(form.custom_price)
    if (form.booking_type === 1) {
        payload.allowed_time_slots = form.allowed_time_slots
    } else {
        payload.allowed_time_slots = []
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
    channel?.on('detail', (data: any) => {
        fillForm(data)
    })
    if (packageId && !form.package_id) {
        await loadFallback(packageId)
    }
})
</script>

<style lang="scss" scoped></style>
