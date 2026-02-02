<template>
    <page-meta :page-style="$theme.pageStyle">
        <navigation-bar title="作品管理" :front-color="$theme.navColor" :background-color="$theme.navBgColor" />
    </page-meta>

    <view class="min-h-screen bg-[#f6f6f6]">
        <z-paging ref="pagingRef" v-model="workList" @query="queryList" :auto="true">
            <template #top>
                <view class="bg-white px-[24rpx] py-[20rpx]">
                    <tn-button type="primary" shape="round" size="md" @click="handleAdd">
                        新增作品
                    </tn-button>
                </view>
            </template>

            <view class="px-[24rpx] pb-[24rpx]">
                <view
                    v-for="item in workList"
                    :key="item.id"
                    class="bg-white rounded-lg p-[20rpx] mb-[20rpx]"
                >
                    <view class="flex items-center">
                        <image
                            class="w-[140rpx] h-[140rpx] rounded-lg bg-[#f2f2f2]"
                            :src="item.cover || item.images?.[0] || defaultCover"
                            mode="aspectFill"
                        />
                        <view class="flex-1 ml-[20rpx]">
                            <view class="text-base font-semibold text-[#333]">
                                {{ item.title || '未命名作品' }}
                            </view>
                            <view class="text-xs text-gray-400 mt-[6rpx]">
                                审核：{{ auditStatusText(item.audit_status) }} · {{ item.is_show ? '显示' : '隐藏' }}
                            </view>
                        </view>
                    </view>
                    <view class="flex justify-end gap-[16rpx] mt-[20rpx]">
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
import { staffCenterWorkLists, staffCenterWorkDelete } from '@/api/staffCenter'
import { ensureStaffCenterAccess } from '@/utils/staff-center'

const pagingRef = ref<any>(null)
const workList = ref<any[]>([])
const defaultCover = '/static/images/default-avatar.png'

const auditStatusText = (status: number) => {
    const map: Record<number, string> = {
        0: '待审核',
        1: '已通过',
        2: '已拒绝'
    }
    return map[status] || '未知'
}

const queryList = async (pageNo: number, pageSize: number) => {
    try {
        const res: any = await staffCenterWorkLists({ page: pageNo, page_size: pageSize })
        const list = res?.data || []
        pagingRef.value.complete(list)
    } catch (e) {
        pagingRef.value.complete(false)
    }
}

const handleAdd = () => {
    uni.navigateTo({ url: '/packages/pages/staff_work_edit/staff_work_edit' })
}

const handleEdit = (item: any) => {
    uni.navigateTo({ url: `/packages/pages/staff_work_edit/staff_work_edit?id=${item.id}` })
}

const handleDelete = (item: any) => {
    uni.showModal({
        title: '确认删除',
        content: '删除后不可恢复，是否继续？',
        success: async (res) => {
            if (!res.confirm) return
            try {
                await staffCenterWorkDelete({ id: item.id })
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
