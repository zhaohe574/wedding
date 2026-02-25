<template>
    <page-meta :page-style="$theme.pageStyle">
        <navigation-bar
            title="个人资料"
            :front-color="$theme.navColor"
            :background-color="$theme.navBgColor"
        />
    </page-meta>

    <view class="page-container">
        <!-- 头像上传卡片 -->
        <view class="avatar-card">
            <view class="card-header">
                <tn-icon name="user" size="32" :color="$theme.primaryColor" />
                <text class="card-title">头像设置</text>
            </view>
            <view class="avatar-wrapper">
                <avatar-upload v-model="form.avatar" :round="true" :size="140" />
                <view class="avatar-tip">点击上传头像，建议尺寸400x400</view>
            </view>
        </view>

        <!-- 基本信息卡片 -->
        <view class="info-card">
            <view class="card-header">
                <tn-icon name="edit" size="32" :color="$theme.primaryColor" />
                <text class="card-title">基本信息</text>
            </view>

            <view class="form-item">
                <view class="form-label">
                    <text class="label-text">姓名</text>
                    <text class="label-required">*</text>
                </view>
                <tn-input
                    v-model="form.name"
                    placeholder="请输入姓名"
                    class="form-input-left"
                    :border="false"
                />
            </view>

            <view class="form-item">
                <view class="form-label">
                    <text class="label-text">服务分类</text>
                </view>
                <view class="form-value-readonly">
                    <text :style="{ color: form.category_id ? '#333333' : '#C8C9CC' }">
                        {{ currentCategoryName }}
                    </text>
                </view>
            </view>

            <view class="form-item">
                <view class="form-label">
                    <text class="label-text">手机号</text>
                </view>
                <tn-input
                    v-model="form.mobile"
                    placeholder="请输入手机号"
                    type="number"
                    class="form-input-left"
                    :border="false"
                />
            </view>

            <view class="form-item">
                <view class="form-label">
                    <text class="label-text">服务价格</text>
                </view>
                <view class="price-input-wrapper-left">
                    <text class="price-symbol" :style="{ color: $theme.ctaColor }">¥</text>
                    <tn-input
                        v-model="form.price"
                        type="number"
                        placeholder="请输入价格"
                        class="form-input-left"
                        :border="false"
                    />
                </view>
            </view>

            <view class="form-item no-border">
                <view class="form-label">
                    <text class="label-text">从业年限</text>
                </view>
                <view class="year-input-wrapper-left">
                    <tn-input
                        v-model="form.experience_years"
                        type="number"
                        placeholder="请输入年限"
                        class="form-input-left"
                        :border="false"
                    />
                    <text class="year-unit">年</text>
                </view>
            </view>
        </view>

        <!-- 个人简介卡片 -->
        <view class="desc-card">
            <view class="card-header">
                <tn-icon name="file-text" size="32" :color="$theme.primaryColor" />
                <text class="card-title">个人简介</text>
                <text class="char-count">{{ form.profile.length }}/500</text>
            </view>
            <textarea
                v-model="form.profile"
                class="desc-textarea"
                placeholder="请简要介绍自己的风格与经验，让客户更了解您"
                :maxlength="500"
                :auto-height="true"
            />
        </view>

        <!-- 服务说明卡片 -->
        <view class="desc-card">
            <view class="card-header">
                <tn-icon name="list" size="32" :color="$theme.primaryColor" />
                <text class="card-title">服务说明</text>
                <text class="char-count">{{ form.service_desc.length }}/1000</text>
            </view>
            <textarea
                v-model="form.service_desc"
                class="desc-textarea"
                placeholder="填写服务内容、流程或注意事项，帮助客户了解服务详情"
                :maxlength="1000"
                :auto-height="true"
            />
        </view>

        <!-- 保存按钮 -->
        <view class="save-btn-wrapper">
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
                <text>{{ saving ? '保存中...' : '保存资料' }}</text>
            </view>
        </view>
    </view>
</template>

<script setup lang="ts">
import { computed, reactive, ref } from 'vue'
import { onShow } from '@dcloudio/uni-app'
import { staffCenterProfile, staffCenterUpdateProfile } from '@/api/staffCenter'
import { getServiceCategories } from '@/api/service'
import { ensureStaffCenterAccess } from '@/utils/staff-center'
import { useThemeStore } from '@/stores/theme'

const $theme = useThemeStore()
const saving = ref(false)
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

const handleSave = async () => {
    if (!form.name.trim()) {
        uni.showToast({ title: '请输入姓名', icon: 'none' })
        return
    }

    const payload: any = {
        name: form.name.trim(),
        avatar: form.avatar,
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

<style lang="scss" scoped>
.page-container {
    min-height: 100vh;
    background: linear-gradient(180deg, rgba(124, 58, 237, 0.05) 0%, #f6f6f6 100%);
    padding-bottom: 120rpx;
}

/* 卡片通用样式 */
.avatar-card,
.info-card,
.desc-card {
    margin: 24rpx;
    padding: 32rpx 24rpx;
    background: #ffffff;
    border-radius: 24rpx;
    box-shadow: 0 2rpx 12rpx rgba(0, 0, 0, 0.06);
}

.card-header {
    display: flex;
    align-items: center;
    gap: 12rpx;
    margin-bottom: 24rpx;
}

.card-title {
    flex: 1;
    font-size: 32rpx;
    font-weight: 600;
    color: #333333;
}

.char-count {
    font-size: 24rpx;
    color: #999999;
}

/* 头像卡片 */
.avatar-wrapper {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 16rpx;
}

.avatar-tip {
    font-size: 24rpx;
    color: #999999;
}

/* 表单项 */
.form-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 24rpx 0;
    border-bottom: 1rpx solid #f5f5f5;

    &.no-border {
        border-bottom: none;
    }
}

.form-label {
    display: flex;
    align-items: center;
    gap: 4rpx;
    min-width: 160rpx;
}

.label-text {
    font-size: 30rpx;
    color: #333333;
}

.label-required {
    font-size: 30rpx;
    color: #ff2c3c;
}

.form-input-left {
    flex: 1;
    text-align: left;
    font-size: 28rpx;
}

.form-value-readonly {
    flex: 1;
    display: flex;
    align-items: center;
    font-size: 28rpx;
    color: #666666;
}

/* 价格输入 */
.price-input-wrapper-left {
    flex: 1;
    display: flex;
    align-items: center;
    gap: 8rpx;
}

.price-symbol {
    font-size: 28rpx;
    font-weight: 600;
}

/* 年限输入 */
.year-input-wrapper-left {
    flex: 1;
    display: flex;
    align-items: center;
    gap: 8rpx;
}

.year-unit {
    font-size: 28rpx;
    color: #666666;
}

/* 文本域 */
.desc-textarea {
    width: 100%;
    min-height: 200rpx;
    padding: 20rpx;
    background: rgba(124, 58, 237, 0.03);
    border-radius: 16rpx;
    font-size: 28rpx;
    line-height: 1.6;
    color: #333333;
    border: 1rpx solid rgba(124, 58, 237, 0.1);
}

/* 保存按钮 */
.save-btn-wrapper {
    position: fixed;
    bottom: 0;
    left: 0;
    right: 0;
    padding: 24rpx;
    background: rgba(255, 255, 255, 0.9);
    backdrop-filter: blur(20rpx);
    border-top: 1rpx solid #f5f5f5;
    z-index: 100;
}

.save-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 12rpx;
    height: 72rpx;
    border-radius: 32rpx;
    font-size: 30rpx;
    font-weight: 600;
    box-shadow: 0 8rpx 24rpx rgba(124, 58, 237, 0.3);
    transition: all 0.2s ease;

    &:active {
        transform: translateY(2rpx);
        box-shadow: 0 4rpx 12rpx rgba(124, 58, 237, 0.3);
    }
}
</style>
