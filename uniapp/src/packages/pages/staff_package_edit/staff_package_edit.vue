<template>
    <page-meta :page-style="$theme.pageStyle">
        <navigation-bar
            :title="form.package_id ? '编辑套餐' : '新增套餐'"
            :front-color="$theme.navColor"
            :background-color="$theme.navBgColor"
        />
    </page-meta>

    <view class="page-container">
        <view class="form-card">
            <view class="card-title">基础信息</view>

            <view class="form-item">
                <text class="form-label">套餐名称</text>
                <tn-input v-model="form.name" placeholder="请输入套餐名称" :border="false" />
            </view>

            <view class="form-item">
                <text class="form-label">服务分类</text>
                <view class="picker-input" @click="showCategorySheet = true">
                    <text :class="categoryLabel ? 'picker-value' : 'picker-placeholder'">
                        {{ categoryLabel || '请选择服务分类' }}
                    </text>
                    <tn-icon name="arrow-right" size="26" color="#9CA3AF" />
                </view>
            </view>

            <view class="form-item">
                <text class="form-label">套餐价格</text>
                <tn-input v-model="form.price" type="number" placeholder="请输入价格" :border="false" />
            </view>

            <view class="form-item">
                <text class="form-label">原价</text>
                <tn-input v-model="form.original_price" type="number" placeholder="选填" :border="false" />
            </view>

            <view class="form-item no-border">
                <text class="form-label">封面图</text>
                <tn-input v-model="form.image" placeholder="请输入图片地址" :border="false" />
            </view>
        </view>

        <view class="form-card">
            <view class="card-title">展示信息</view>

            <view class="form-item">
                <text class="form-label">套餐说明</text>
                <tn-input
                    v-model="form.description"
                    type="textarea"
                    placeholder="请输入套餐说明"
                    :border="false"
                    height="140"
                />
            </view>

            <view class="form-item">
                <text class="form-label">排序</text>
                <tn-input v-model="form.sort" type="number" placeholder="默认 0" :border="false" />
            </view>

            <view class="form-item">
                <text class="form-label">是否推荐</text>
                <u-switch
                    v-model="recommendSwitch"
                    :active-color="$theme.primaryColor"
                    inactive-color="#E5E7EB"
                    size="24"
                />
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

        <u-action-sheet
            v-model="showCategorySheet"
            :list="categoryOptions"
            label-name="text"
            @click="handleCategorySelect"
        />

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
    staffCenterPackageAdd,
    staffCenterPackageLists,
    staffCenterPackageUpdate
} from '@/api/staffCenter'
import { getServiceCategories } from '@/api/service'
import { ensureStaffCenterAccess } from '@/utils/staff-center'
import { useThemeStore } from '@/stores/theme'

const $theme = useThemeStore()
const saving = ref(false)
const showCategorySheet = ref(false)
const categoryOptions = ref<any[]>([])

const form = reactive({
    package_id: 0,
    name: '',
    category_id: '',
    price: '',
    original_price: '',
    image: '',
    description: '',
    sort: '0',
    is_show: 1,
    is_recommend: 0
})

const statusSwitch = computed({
    get: () => form.is_show === 1,
    set: (value: boolean) => {
        form.is_show = value ? 1 : 0
    }
})

const recommendSwitch = computed({
    get: () => form.is_recommend === 1,
    set: (value: boolean) => {
        form.is_recommend = value ? 1 : 0
    }
})

const categoryLabel = computed(() => {
    const categoryId = Number(form.category_id || 0)
    if (!categoryId) {
        return ''
    }
    const current = categoryOptions.value.find((item: any) => Number(item.value) === categoryId)
    return current?.text || ''
})

const flattenCategories = (list: any[], prefix = ''): any[] => {
    const result: any[] = []
    list.forEach((item: any) => {
        const label = prefix ? `${prefix} / ${item.name}` : item.name
        result.push({
            text: label,
            value: Number(item.id || 0)
        })
        if (Array.isArray(item.children) && item.children.length) {
            result.push(...flattenCategories(item.children, label))
        }
    })
    return result.filter((item) => item.value > 0)
}

const fillForm = (data: any) => {
    form.package_id = Number(data.package_id || data.id || 0)
    form.name = data.name || ''
    form.category_id = data.category_id !== undefined && data.category_id !== null ? String(data.category_id) : ''
    form.price = data.price !== undefined && data.price !== null ? String(data.price) : ''
    form.original_price =
        data.original_price !== undefined && data.original_price !== null ? String(data.original_price) : ''
    form.image = data.image || ''
    form.description = data.description || ''
    form.sort = data.sort !== undefined && data.sort !== null ? String(data.sort) : '0'
    form.is_show = Number(data.is_show ?? 1)
    form.is_recommend = Number(data.is_recommend ?? 0)
}

const loadCategoryOptions = async () => {
    try {
        const data = await getServiceCategories()
        categoryOptions.value = flattenCategories(Array.isArray(data) ? data : [])
    } catch (e) {
        categoryOptions.value = []
    }
}

const loadFallback = async (packageId: number) => {
    const data = await staffCenterPackageLists()
    const list = Array.isArray(data) ? data : []
    const item = list.find((pkg: any) => Number(pkg.id) === packageId)
    if (item) {
        fillForm(item)
    }
}

const handleCategorySelect = (index: number) => {
    const current = categoryOptions.value[index]
    if (!current) {
        return
    }
    form.category_id = String(current.value)
}

const handleSave = async () => {
    if (!form.name.trim()) {
        uni.showToast({ title: '请输入套餐名称', icon: 'none' })
        return
    }
    if (!form.category_id.trim()) {
        uni.showToast({ title: '请选择服务分类', icon: 'none' })
        return
    }
    if (!form.price.trim()) {
        uni.showToast({ title: '请输入套餐价格', icon: 'none' })
        return
    }

    const payload: any = {
        name: form.name.trim(),
        category_id: Number(form.category_id || 0),
        price: Number(form.price || 0),
        original_price: form.original_price === '' ? 0 : Number(form.original_price),
        image: form.image.trim(),
        description: form.description.trim(),
        sort: Number(form.sort || 0),
        is_show: form.is_show,
        is_recommend: form.is_recommend
    }

    saving.value = true
    try {
        if (form.package_id) {
            await staffCenterPackageUpdate({
                ...payload,
                package_id: form.package_id
            })
        } else {
            await staffCenterPackageAdd(payload)
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
    await loadCategoryOptions()
    const packageId = Number(options?.package_id || 0)
    const instance = getCurrentInstance()
    const channel = instance?.proxy?.getOpenerEventChannel?.()
    channel?.on('detail', (data: any) => fillForm(data))
    if (packageId && !form.package_id) {
        await loadFallback(packageId)
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

.picker-input {
    flex: 1;
    display: flex;
    align-items: center;
    justify-content: space-between;
    min-height: 44rpx;
}

.picker-value {
    font-size: 28rpx;
    color: #111827;
}

.picker-placeholder {
    font-size: 28rpx;
    color: #9ca3af;
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
