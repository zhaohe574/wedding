<template>
    <page-meta :page-style="$theme.pageStyle">
        <navigation-bar title="套餐管理" :front-color="$theme.navColor" :background-color="$theme.navBgColor" />
    </page-meta>

    <view class="min-h-screen bg-[#f6f6f6] pb-[40rpx]">
        <view class="bg-white">
            <tn-tabs v-model="tabIndex" :scroll="false" height="80rpx" :active-color="$theme.primaryColor">
                <tn-tabs-item v-for="tab in tabs" :key="tab.key" :title="tab.label" />
            </tn-tabs>
        </view>

        <view class="px-[24rpx] mt-[20rpx]">
            <view v-if="tabIndex === 0">
                <view v-if="configured.length === 0" class="text-center text-gray-400 py-[80rpx]">暂无已关联套餐</view>
                <view v-for="item in configured" :key="item.package_id" class="bg-white rounded-lg p-[20rpx] mb-[20rpx]">
                    <view class="text-base font-semibold text-[#333]">{{ getPackageName(item) }}</view>
                    <view class="text-xs text-gray-400 mt-[8rpx]">
                        价格：¥{{ getPackagePrice(item) }} · {{ getBookingTypeText(item) }} · {{ item.status ? '启用' : '禁用' }}
                    </view>
                    <view class="flex justify-end gap-[16rpx] mt-[16rpx]">
                        <tn-button size="sm" type="primary" shape="round" :plain="true" @click="handleEdit(item)">
                            编辑
                        </tn-button>
                        <tn-button size="sm" type="danger" shape="round" :plain="true" @click="handleRemove(item)">
                            移除
                        </tn-button>
                    </view>
                </view>
            </view>

            <view v-else>
                <view v-if="available.length === 0" class="text-center text-gray-400 py-[80rpx]">暂无可关联套餐</view>
                <view v-for="item in available" :key="item.id" class="bg-white rounded-lg p-[20rpx] mb-[20rpx]">
                    <view class="text-base font-semibold text-[#333]">{{ item.name }}</view>
                    <view class="text-xs text-gray-400 mt-[8rpx]">价格：¥{{ item.price || 0 }}</view>
                    <view class="flex justify-end mt-[16rpx]">
                        <tn-button size="sm" type="primary" shape="round" :plain="true" @click="handleAdd(item)">
                            关联
                        </tn-button>
                    </view>
                </view>
            </view>
        </view>
    </view>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import { onShow } from '@dcloudio/uni-app'
import { staffCenterPackageLists, staffCenterPackageAdd, staffCenterPackageRemove } from '@/api/staffCenter'
import { ensureStaffCenterAccess } from '@/utils/staff-center'

const tabs = [
    { key: 'configured', label: '已关联' },
    { key: 'available', label: '可选套餐' }
]
const tabIndex = ref(0)
const configured = ref<any[]>([])
const available = ref<any[]>([])

const getPackageName = (item: any) => item.package?.name || item.name || '套餐'
const getPackagePrice = (item: any) =>
    item.custom_price ?? item.price ?? item.package?.price ?? 0

const getBookingTypeText = (item: any) => {
    const type = item.booking_type ?? item.package?.booking_type ?? 0
    return Number(type) === 1 ? '分场次' : '全天'
}

const fetchPackages = async () => {
    try {
        const data = await staffCenterPackageLists()
        configured.value = data?.configured || []
        available.value = data?.available || []
    } catch (e: any) {
        const msg = typeof e === 'string' ? e : e?.msg || e?.message || '加载失败'
        uni.showToast({ title: msg, icon: 'none' })
    }
}

const handleAdd = async (item: any) => {
    try {
        await staffCenterPackageAdd({ package_id: item.id })
        uni.showToast({ title: '关联成功', icon: 'success' })
        fetchPackages()
    } catch (e: any) {
        const msg = typeof e === 'string' ? e : e?.msg || e?.message || '关联失败'
        uni.showToast({ title: msg, icon: 'none' })
    }
}

const handleRemove = (item: any) => {
    uni.showModal({
        title: '确认移除',
        content: '移除后将不再显示该套餐，是否继续？',
        success: async (res) => {
            if (!res.confirm) return
            try {
                await staffCenterPackageRemove({ package_id: item.package_id })
                uni.showToast({ title: '移除成功', icon: 'success' })
                fetchPackages()
            } catch (e: any) {
                const msg = typeof e === 'string' ? e : e?.msg || e?.message || '移除失败'
                uni.showToast({ title: msg, icon: 'none' })
            }
        }
    })
}

const handleEdit = (item: any) => {
    uni.navigateTo({
        url: `/packages/pages/staff_package_edit/staff_package_edit?package_id=${item.package_id}`,
        success: (res) => {
            res.eventChannel.emit('detail', item)
        }
    })
}

onShow(async () => {
    if (!(await ensureStaffCenterAccess())) return
    fetchPackages()
})
</script>

<style lang="scss" scoped></style>
