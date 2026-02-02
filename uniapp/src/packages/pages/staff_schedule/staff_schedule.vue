<template>
    <page-meta :page-style="$theme.pageStyle">
        <navigation-bar title="档期管理" :front-color="$theme.navColor" :background-color="$theme.navBgColor" />
    </page-meta>

    <view class="min-h-screen bg-[#f6f6f6] pb-[40rpx]">
        <view class="bg-white mx-[24rpx] mt-[24rpx] rounded-lg p-[24rpx]">
            <view class="flex items-center justify-between">
                <view class="text-sm font-medium">选择日期</view>
                <picker mode="date" :value="selectedDate" @change="handleDateChange">
                    <view class="text-sm text-gray-500">{{ selectedDate }}</view>
                </picker>
            </view>
            <view class="text-xs text-gray-400 mt-[12rpx]">当前月份：{{ year }}-{{ monthText }}</view>
        </view>

        <view class="mx-[24rpx] mt-[20rpx]">
            <view
                v-for="slot in timeSlots"
                :key="slot.value"
                class="bg-white rounded-lg p-[20rpx] mb-[16rpx]"
            >
                <view class="flex items-center justify-between">
                    <view class="text-sm font-medium">{{ slot.label }}</view>
                    <view class="text-xs" :class="statusClass(getSlotStatus(slot.value))">
                        {{ getSlotStatusLabel(slot.value) }}
                    </view>
                </view>
                <view class="flex gap-[16rpx] mt-[16rpx]">
                    <tn-button
                        size="sm"
                        type="primary"
                        shape="round"
                        :plain="true"
                        :disabled="!canEdit(slot.value)"
                        @click="setStatus(slot.value, 1)"
                    >
                        设为可预约
                    </tn-button>
                    <tn-button
                        size="sm"
                        type="danger"
                        shape="round"
                        :plain="true"
                        :disabled="!canEdit(slot.value)"
                        @click="setStatus(slot.value, 0)"
                    >
                        设为不可用
                    </tn-button>
                </view>
            </view>
        </view>

        <view class="mx-[24rpx] mt-[10rpx] text-xs text-gray-400">
            已预约/锁定/预留的档期不可调整。
        </view>
    </view>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue'
import { onShow } from '@dcloudio/uni-app'
import { staffCenterScheduleMonth, staffCenterScheduleSetStatus } from '@/api/staffCenter'
import { ensureStaffCenterAccess } from '@/utils/staff-center'

const formatDate = (date: Date) => {
    const year = date.getFullYear()
    const month = String(date.getMonth() + 1).padStart(2, '0')
    const day = String(date.getDate()).padStart(2, '0')
    return `${year}-${month}-${day}`
}

const selectedDate = ref(formatDate(new Date()))
const year = ref(Number(selectedDate.value.split('-')[0]))
const month = ref(Number(selectedDate.value.split('-')[1]))
const schedules = ref<Record<string, any>>({})

const monthText = computed(() => String(month.value).padStart(2, '0'))

const timeSlots = [
    { value: 0, label: '全天' },
    { value: 1, label: '早礼' },
    { value: 2, label: '午宴' },
    { value: 3, label: '晚宴' }
]

const statusMap: Record<number, string> = {
    0: '不可用',
    1: '可预约',
    2: '已预约',
    3: '已锁定',
    4: '内部预留'
}

const getSlotStatus = (slotValue: number) => {
    const day = schedules.value[selectedDate.value] || {}
    return day[slotValue]?.status ?? null
}

const getSlotStatusLabel = (slotValue: number) => {
    const status = getSlotStatus(slotValue)
    if (status === null || status === undefined) return '按规则'
    return statusMap[status] || '未知'
}

const statusClass = (status: number | null) => {
    if (status === 1) return 'text-green-600'
    if (status === 0) return 'text-red-500'
    if (status === 2) return 'text-orange-500'
    if (status === 3) return 'text-purple-500'
    if (status === 4) return 'text-blue-500'
    return 'text-gray-400'
}

const canEdit = (slotValue: number) => {
    const status = getSlotStatus(slotValue)
    return status === null || status === 0 || status === 1
}

const fetchMonth = async () => {
    const res = await staffCenterScheduleMonth({ year: year.value, month: month.value })
    schedules.value = res?.schedules || {}
}

const setStatus = async (slotValue: number, status: number) => {
    try {
        await staffCenterScheduleSetStatus({
            date: selectedDate.value,
            time_slot: slotValue,
            status
        })
        if (!schedules.value[selectedDate.value]) {
            schedules.value[selectedDate.value] = {}
        }
        schedules.value[selectedDate.value][slotValue] = {
            status
        }
        uni.showToast({ title: '设置成功', icon: 'success' })
    } catch (e: any) {
        const msg = typeof e === 'string' ? e : e?.msg || e?.message || '设置失败'
        uni.showToast({ title: msg, icon: 'none' })
    }
}

const handleDateChange = (e: any) => {
    selectedDate.value = e.detail.value
    const [y, m] = selectedDate.value.split('-')
    const newYear = Number(y)
    const newMonth = Number(m)
    if (newYear !== year.value || newMonth !== month.value) {
        year.value = newYear
        month.value = newMonth
        fetchMonth()
    }
}

onShow(async () => {
    if (!(await ensureStaffCenterAccess())) return
    await fetchMonth()
})
</script>

<style lang="scss" scoped></style>
