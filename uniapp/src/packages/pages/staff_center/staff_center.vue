<template>
    <page-meta :page-style="$theme.pageStyle">
        <navigation-bar title="服务人员中心" :front-color="$theme.navColor" :background-color="$theme.navBgColor" />
    </page-meta>

    <view class="min-h-screen bg-[#f6f6f6] pb-[40rpx]">
        <view class="mx-[24rpx] mt-[24rpx] rounded-lg bg-white p-[24rpx] flex items-center">
            <image
                class="w-[120rpx] h-[120rpx] rounded-full bg-[#f2f2f2]"
                :src="profile.avatar || defaultAvatar"
                mode="aspectFill"
            />
            <view class="flex-1 ml-[24rpx]">
                <view class="text-lg font-semibold">
                    {{ profile.name || '未填写姓名' }}
                </view>
                <view class="text-xs text-gray-400 mt-[6rpx]">
                    {{ profile.mobile || '未绑定手机号' }}
                </view>
                <view class="text-xs text-gray-400 mt-[4rpx]">
                    {{ profile.price ? `服务价 ¥${profile.price}` : '未设置价格' }}
                </view>
            </view>
            <tn-icon name="right" size="30" color="#999999" />
        </view>

        <view class="mx-[24rpx] mt-[20rpx] rounded-lg bg-white">
            <view
                v-for="item in menus"
                :key="item.path"
                class="flex items-center justify-between px-[24rpx] py-[28rpx] border-b border-solid border-0 border-light"
                @click="goPage(item.path)"
            >
                <view>
                    <view class="text-sm font-medium text-[#333]">{{ item.name }}</view>
                    <view class="text-xs text-gray-400 mt-[6rpx]">{{ item.desc }}</view>
                </view>
                <tn-icon name="right" size="28" color="#c2c2c2" />
            </view>
        </view>

        <view class="mx-[24rpx] mt-[20rpx] text-xs text-gray-400">
            仅可维护本人资料、作品、套餐、档期与动态。
        </view>
    </view>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import { onShow } from '@dcloudio/uni-app'
import { staffCenterProfile } from '@/api/staffCenter'
import { ensureStaffCenterAccess } from '@/utils/staff-center'
const profile = ref<any>({})
const defaultAvatar = '/static/images/default-avatar.png'

const menus = [
    {
        name: '个人资料',
        desc: '完善基本信息与服务说明',
        path: '/packages/pages/staff_profile/staff_profile'
    },
    {
        name: '作品管理',
        desc: '上传作品并等待审核',
        path: '/packages/pages/staff_work_list/staff_work_list'
    },
    {
        name: '套餐管理',
        desc: '关联与调整服务套餐',
        path: '/packages/pages/staff_package_list/staff_package_list'
    },
    {
        name: '档期管理',
        desc: '设置可预约日期与时段',
        path: '/packages/pages/staff_schedule/staff_schedule'
    },
    {
        name: '动态管理',
        desc: '发布动态，展示服务案例',
        path: '/packages/pages/staff_dynamic_list/staff_dynamic_list'
    }
]

const fetchProfile = async () => {
    try {
        const data = await staffCenterProfile()
        profile.value = data || {}
    } catch (e: any) {
        const msg = typeof e === 'string' ? e : e?.msg || e?.message || '获取资料失败'
        uni.showToast({ title: msg, icon: 'none' })
    }
}

const goPage = (path: string) => {
    uni.navigateTo({ url: path })
}

onShow(async () => {
    if (!(await ensureStaffCenterAccess())) return
    fetchProfile()
})
</script>

<style lang="scss" scoped></style>
