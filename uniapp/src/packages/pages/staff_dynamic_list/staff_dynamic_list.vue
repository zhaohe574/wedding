<template>
    <page-meta :page-style="$theme.pageStyle">
        <navigation-bar title="动态管理" :front-color="$theme.navColor" :background-color="$theme.navBgColor" />
    </page-meta>

    <view class="min-h-screen bg-[#f6f6f6]">
        <z-paging ref="pagingRef" v-model="dynamicList" @query="queryList" :auto="true">
            <template #top>
                <view class="bg-white px-[24rpx] py-[20rpx]">
                    <tn-button type="primary" shape="round" size="md" @click="handleAdd">
                        发布动态
                    </tn-button>
                </view>
            </template>

            <view class="px-[24rpx] pb-[24rpx]">
                <view
                    v-for="item in dynamicList"
                    :key="item.id"
                    class="bg-white rounded-lg p-[20rpx] mb-[20rpx]"
                >
                    <view class="text-sm text-gray-500">{{ typeText(item.dynamic_type) }} · {{ statusText(item.status) }}</view>
                    <view class="text-base text-[#333] mt-[8rpx] line-clamp-2">{{ item.content }}</view>
                    <view class="text-xs text-gray-400 mt-[8rpx]">
                        图片 {{ item.images?.length || 0 }} · 视频 {{ item.video_url ? '1' : '0' }}
                    </view>
                    <view class="flex justify-end gap-[16rpx] mt-[16rpx]">
                        <tn-button size="sm" type="primary" shape="round" :plain="true" @click="handleEdit(item)">
                            编辑
                        </tn-button>
                        <tn-button size="sm" type="danger" shape="round" :plain="true" @click="handleDelete(item)">
                            删除
                        </tn-button>
                    </view>
                </view>
            </view>
        </z-paging>
    </view>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import { onShow } from '@dcloudio/uni-app'
import { staffCenterDynamicLists, staffCenterDynamicDelete } from '@/api/staffCenter'
import { ensureStaffCenterAccess } from '@/utils/staff-center'

const pagingRef = ref<any>(null)
const dynamicList = ref<any[]>([])

const typeText = (type: number) => {
    const map: Record<number, string> = { 1: '图文', 2: '视频', 3: '案例', 4: '活动' }
    return map[type] || '动态'
}

const statusText = (status: number) => {
    const map: Record<number, string> = { 0: '待审核', 1: '已发布', 2: '已下架', 3: '审核拒绝' }
    return map[status] || '未知'
}

const queryList = async (pageNo: number, pageSize: number) => {
    try {
        const res: any = await staffCenterDynamicLists({ page: pageNo, page_size: pageSize })
        const list = res?.data || []
        pagingRef.value.complete(list)
    } catch (e) {
        pagingRef.value.complete(false)
    }
}

const handleAdd = () => {
    uni.navigateTo({ url: '/packages/pages/staff_dynamic_edit/staff_dynamic_edit' })
}

const handleEdit = (item: any) => {
    uni.navigateTo({
        url: `/packages/pages/staff_dynamic_edit/staff_dynamic_edit?id=${item.id}`,
        success: (res) => {
            res.eventChannel.emit('detail', item)
        }
    })
}

const handleDelete = (item: any) => {
    uni.showModal({
        title: '确认删除',
        content: '删除后不可恢复，是否继续？',
        success: async (res) => {
            if (!res.confirm) return
            try {
                await staffCenterDynamicDelete({ id: item.id })
                uni.showToast({ title: '删除成功', icon: 'success' })
                pagingRef.value.reload()
            } catch (e: any) {
                const msg = typeof e === 'string' ? e : e?.msg || e?.message || '删除失败'
                uni.showToast({ title: msg, icon: 'none' })
            }
        }
    })
}

onShow(async () => {
    if (!(await ensureStaffCenterAccess())) return
    pagingRef.value?.reload()
})
</script>

<style lang="scss" scoped></style>
