<template>
    <page-meta :page-style="$theme.pageStyle">
        <navigation-bar
            :title="form.addon_id ? '编辑附加服务' : '新增附加服务'"
            :front-color="$theme.navColor"
            :background-color="$theme.navBgColor"
        />
    </page-meta>

    <view class="page-container">
        <view class="form-card">
            <view class="card-title">基础信息</view>

            <view class="form-item">
                <text class="form-label">服务名称</text>
                <tn-input v-model="form.name" placeholder="请输入附加服务名称" :border="false" />
            </view>

            <view class="form-item">
                <text class="form-label">售价</text>
                <tn-input v-model="form.price" type="number" placeholder="请输入价格" :border="false" />
            </view>

            <view class="form-item">
                <text class="form-label">原价</text>
                <tn-input v-model="form.original_price" type="number" placeholder="选填" :border="false" />
            </view>

            <view class="form-item no-border">
                <text class="form-label">图片</text>
                <tn-input v-model="form.image" placeholder="请输入图片地址" :border="false" />
            </view>
        </view>

        <view class="form-card">
            <view class="card-title">展示信息</view>

            <view class="form-item">
                <text class="form-label">描述</text>
                <tn-input
                    v-model="form.description"
                    type="textarea"
                    placeholder="请输入附加服务说明"
                    :border="false"
                    height="140"
                />
            </view>

            <view class="form-item">
                <text class="form-label">排序</text>
                <tn-input v-model="form.sort" type="number" placeholder="默认 0" :border="false" />
            </view>

            <view class="form-item no-border">
                <text class="form-label">上架状态</text>
                <u-switch
                    v-model="statusSwitch"
                    :active-color="$theme.primaryColor"
                    inactive-color="#E5E7EB"
                    size="24"
                />
            </view>
        </view>

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
import {
    staffCenterAddonAdd,
    staffCenterAddonLists,
    staffCenterAddonUpdate
} from '@/api/staffCenter'
import { ensureStaffCenterAccess } from '@/utils/staff-center'
import { useThemeStore } from '@/stores/theme'

const $theme = useThemeStore()
const saving = ref(false)

const form = reactive({
    addon_id: 0,
    name: '',
    price: '',
    original_price: '',
    image: '',
    description: '',
    sort: '0',
    is_show: 1
})

const statusSwitch = computed({
    get: () => form.is_show === 1,
    set: (value: boolean) => {
        form.is_show = value ? 1 : 0
    }
})

const fillForm = (data: any) => {
    form.addon_id = Number(data.addon_id || data.id || 0)
    form.name = data.name || ''
    form.price = data.price !== undefined && data.price !== null ? String(data.price) : ''
    form.original_price =
        data.original_price !== undefined && data.original_price !== null ? String(data.original_price) : ''
    form.image = data.image || ''
    form.description = data.description || ''
    form.sort = data.sort !== undefined && data.sort !== null ? String(data.sort) : '0'
    form.is_show = Number(data.is_show ?? 1)
}

const loadFallback = async (addonId: number) => {
    const data = await staffCenterAddonLists()
    const list = Array.isArray(data) ? data : []
    const item = list.find((addon: any) => Number(addon.id) === addonId)
    if (item) {
        fillForm(item)
    }
}

const handleSave = async () => {
    if (!form.name.trim()) {
        uni.showToast({ title: '请输入附加服务名称', icon: 'none' })
        return
    }
    if (!form.price.trim()) {
        uni.showToast({ title: '请输入附加服务价格', icon: 'none' })
        return
    }

    const payload: any = {
        name: form.name.trim(),
        price: Number(form.price || 0),
        original_price: form.original_price === '' ? 0 : Number(form.original_price),
        image: form.image.trim(),
        description: form.description.trim(),
        sort: Number(form.sort || 0),
        is_show: form.is_show
    }

    saving.value = true
    try {
        if (form.addon_id) {
            await staffCenterAddonUpdate({
                ...payload,
                addon_id: form.addon_id
            })
        } else {
            await staffCenterAddonAdd(payload)
        }
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
    const addonId = Number(options?.addon_id || 0)
    const instance = getCurrentInstance()
    const channel = instance?.proxy?.getOpenerEventChannel?.()
    channel?.on('detail', (data: any) => fillForm(data))
    if (addonId && !form.addon_id) {
        await loadFallback(addonId)
    }
})
</script>

<style lang="scss" scoped>
.page-container {
    min-height: 100vh;
    background: #f4f5f7;
    padding: 24rpx 24rpx 60rpx;
}

.form-card {
    margin-bottom: 24rpx;
    padding: 28rpx;
    background: #ffffff;
    border-radius: 24rpx;
    box-shadow: 0 4rpx 24rpx rgba(0, 0, 0, 0.05);
}

.card-title {
    margin-bottom: 20rpx;
    font-size: 30rpx;
    font-weight: 700;
    color: #1f2937;
}

.form-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 20rpx;
    padding: 24rpx 0;
    border-bottom: 1rpx solid #f3f4f6;
}

.form-item.no-border {
    border-bottom: none;
}

.form-label {
    min-width: 180rpx;
    font-size: 28rpx;
    color: #374151;
    font-weight: 500;
}

.save-wrapper {
    margin-top: 40rpx;
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
}
</style>
