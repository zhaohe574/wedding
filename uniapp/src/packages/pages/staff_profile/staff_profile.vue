<template>
    <page-meta :page-style="$theme.pageStyle">
        <navigation-bar title="个人资料" :front-color="$theme.navColor" :background-color="$theme.navBgColor" />
    </page-meta>

    <view class="min-h-screen bg-[#f6f6f6] pb-[40rpx]">
        <view class="bg-white mx-[24rpx] mt-[24rpx] rounded-lg p-[24rpx]">
            <view class="text-sm font-medium mb-[20rpx]">头像</view>
            <avatar-upload v-model="form.avatar" :round="true" :size="120" />
        </view>

        <view class="bg-white mx-[24rpx] mt-[20rpx] rounded-lg">
            <view class="flex items-center justify-between px-[24rpx] py-[26rpx] border-b border-solid border-0 border-light">
                <view class="text-sm text-[#333]">姓名</view>
                <tn-input v-model="form.name" placeholder="请输入姓名" class="text-right" />
            </view>

            <view class="flex items-center justify-between px-[24rpx] py-[26rpx] border-b border-solid border-0 border-light" @click="showCategoryPicker = true">
                <view class="text-sm text-[#333]">服务分类</view>
                <view class="flex items-center text-sm text-gray-500">
                    <text>{{ currentCategoryName }}</text>
                    <tn-icon name="right" size="24" color="#999999" class="ml-[6rpx]" />
                </view>
            </view>

            <view class="flex items-center justify-between px-[24rpx] py-[26rpx] border-b border-solid border-0 border-light">
                <view class="text-sm text-[#333]">手机号</view>
                <tn-input v-model="form.mobile" placeholder="请输入手机号" class="text-right" />
            </view>

            <view class="flex items-center justify-between px-[24rpx] py-[26rpx] border-b border-solid border-0 border-light">
                <view class="text-sm text-[#333]">服务价格</view>
                <tn-input v-model="form.price" type="number" placeholder="请输入价格" class="text-right" />
            </view>

            <view class="flex items-center justify-between px-[24rpx] py-[26rpx]">
                <view class="text-sm text-[#333]">从业年限</view>
                <tn-input v-model="form.experience_years" type="number" placeholder="请输入年限" class="text-right" />
            </view>
        </view>

        <view class="bg-white mx-[24rpx] mt-[20rpx] rounded-lg p-[24rpx]">
            <view class="text-sm font-medium mb-[16rpx]">个人简介</view>
            <textarea
                v-model="form.profile"
                class="w-full text-sm leading-6 text-[#333] bg-[#fafafa] rounded-lg p-[20rpx]"
                placeholder="请简要介绍自己的风格与经验"
                :maxlength="500"
            />
        </view>

        <view class="bg-white mx-[24rpx] mt-[20rpx] rounded-lg p-[24rpx]">
            <view class="text-sm font-medium mb-[16rpx]">服务说明</view>
            <textarea
                v-model="form.service_desc"
                class="w-full text-sm leading-6 text-[#333] bg-[#fafafa] rounded-lg p-[20rpx]"
                placeholder="填写服务内容、流程或注意事项"
                :maxlength="1000"
            />
        </view>

        <view class="mx-[24rpx] mt-[30rpx]">
            <tn-button type="primary" shape="round" size="lg" :loading="saving" @click="handleSave">
                保存
            </tn-button>
        </view>
    </view>

    <tn-action-sheet v-model="showCategoryPicker" :data="categoryOptions" @click="handleCategorySelect" />
</template>

<script setup lang="ts">
import { computed, reactive, ref } from 'vue'
import { onShow } from '@dcloudio/uni-app'
import { staffCenterProfile, staffCenterUpdateProfile } from '@/api/staffCenter'
import { getServiceCategories } from '@/api/service'
import { ensureStaffCenterAccess } from '@/utils/staff-center'
const saving = ref(false)
const showCategoryPicker = ref(false)
const categories = ref<Array<{ id: number; name: string }>>([])

const form = reactive({
    name: '',
    avatar: '',
    mobile: '',
    category_id: 0,
    price: '',
    experience_years: '',
    profile: '',
    service_desc: ''
})

const categoryOptions = computed(() => categories.value.map((item) => ({ text: item.name, value: item.id })))

const currentCategoryName = computed(() => {
    const match = categories.value.find((item) => item.id === Number(form.category_id))
    return match?.name || '请选择'
})

const flattenCategories = (list: any[], bucket: Array<{ id: number; name: string }>) => {
    list.forEach((item) => {
        bucket.push({ id: Number(item.id), name: item.name })
        if (Array.isArray(item.children) && item.children.length > 0) {
            flattenCategories(item.children, bucket)
        }
    })
}

const loadCategories = async () => {
    const data = await getServiceCategories()
    const flat: Array<{ id: number; name: string }> = []
    if (Array.isArray(data)) {
        flattenCategories(data, flat)
    }
    categories.value = flat
}

const loadProfile = async () => {
    const data = await staffCenterProfile()
    form.name = data?.name || ''
    form.avatar = data?.avatar || ''
    form.mobile = data?.mobile_full || data?.mobile || ''
    form.category_id = Number(data?.category_id || 0)
    form.price = data?.price !== undefined && data?.price !== null ? String(data?.price) : ''
    form.experience_years =
        data?.experience_years !== undefined && data?.experience_years !== null
            ? String(data?.experience_years)
            : ''
    form.profile = data?.profile || ''
    form.service_desc = data?.service_desc || ''
}

const handleCategorySelect = (index: number) => {
    const selected = categoryOptions.value[index]
    if (selected) {
        form.category_id = Number(selected.value)
    }
}

const handleSave = async () => {
    if (!form.name.trim()) {
        uni.showToast({ title: '请输入姓名', icon: 'none' })
        return
    }
    if (!form.category_id) {
        uni.showToast({ title: '请选择服务分类', icon: 'none' })
        return
    }

    const payload: any = {
        name: form.name.trim(),
        avatar: form.avatar,
        category_id: form.category_id,
        profile: form.profile,
        service_desc: form.service_desc
    }

    if (form.mobile) {
        payload.mobile = form.mobile
    }
    if (form.price !== '') {
        payload.price = Number(form.price)
    }
    if (form.experience_years !== '') {
        payload.experience_years = Number(form.experience_years)
    }

    saving.value = true
    try {
        await staffCenterUpdateProfile(payload)
        uni.showToast({ title: '保存成功', icon: 'success' })
    } catch (e: any) {
        const msg = typeof e === 'string' ? e : e?.msg || e?.message || '保存失败'
        uni.showToast({ title: msg, icon: 'none' })
    } finally {
        saving.value = false
    }
}

onShow(async () => {
    if (!(await ensureStaffCenterAccess())) return
    await Promise.all([loadCategories(), loadProfile()])
})
</script>

<style lang="scss" scoped></style>
