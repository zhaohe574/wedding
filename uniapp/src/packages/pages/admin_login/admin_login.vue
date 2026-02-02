<template>
    <page-meta :page-style="$theme.pageStyle">
        <navigation-bar title="管理员登录" :front-color="$theme.navColor" :background-color="$theme.navBgColor" />
    </page-meta>

    <view class="min-h-screen bg-[#f6f6f6]">
        <view class="bg-white mx-[24rpx] mt-[40rpx] rounded-lg p-[32rpx]">
            <view class="text-lg font-semibold text-center">管理员看板登录</view>
            <view class="mt-[30rpx]">
                <view class="text-sm text-gray-500 mb-[10rpx]">账号</view>
                <tn-input v-model="form.account" placeholder="请输入账号" />
            </view>
            <view class="mt-[20rpx]">
                <view class="text-sm text-gray-500 mb-[10rpx]">密码</view>
                <tn-input v-model="form.password" type="password" placeholder="请输入密码" />
            </view>
            <view class="mt-[30rpx]">
                <tn-button type="primary" shape="round" size="lg" :loading="loading" @click="handleLogin">
                    登录
                </tn-button>
            </view>
        </view>
    </view>
</template>

<script setup lang="ts">
import { reactive, ref } from 'vue'
import { onShow } from '@dcloudio/uni-app'
import { useAdminStore } from '@/stores/admin'
import { useAppStore } from '@/stores/app'

const adminStore = useAdminStore()
const appStore = useAppStore()
const loading = ref(false)

const form = reactive({
    account: '',
    password: ''
})

const ensureFeature = async () => {
    if (!appStore.config?.feature_switch) {
        await appStore.getConfig()
    }
    if (appStore.config?.feature_switch?.admin_dashboard !== 1) {
        uni.showToast({ title: '管理员看板已关闭', icon: 'none' })
        setTimeout(() => uni.navigateBack(), 1200)
        return false
    }
    return true
}

const handleLogin = async () => {
    if (!form.account.trim()) {
        uni.showToast({ title: '请输入账号', icon: 'none' })
        return
    }
    if (!form.password.trim()) {
        uni.showToast({ title: '请输入密码', icon: 'none' })
        return
    }
    loading.value = true
    try {
        await adminStore.login({ account: form.account.trim(), password: form.password })
        uni.showToast({ title: '登录成功', icon: 'success' })
        setTimeout(() => {
            uni.navigateTo({ url: '/packages/pages/admin_dashboard/admin_dashboard' })
        }, 300)
    } catch (e: any) {
        const msg = typeof e === 'string' ? e : e?.msg || e?.message || '登录失败'
        uni.showToast({ title: msg, icon: 'none' })
    } finally {
        loading.value = false
    }
}

onShow(async () => {
    const ok = await ensureFeature()
    if (!ok) return
    if (adminStore.isLogin) {
        uni.navigateTo({ url: '/packages/pages/admin_dashboard/admin_dashboard' })
    }
})
</script>

<style lang="scss" scoped></style>
