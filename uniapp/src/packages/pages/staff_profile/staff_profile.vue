<template>
    <page-meta :page-style="$theme.pageStyle">
        <navigation-bar
            title="个人资料"
            :front-color="$theme.navColor"
            :background-color="$theme.navBgColor"
        />
    </page-meta>

    <view class="page-container">
        <!-- 头像区域 -->
        <view class="avatar-section">
            <view class="avatar-card">
                <avatar-upload v-model="form.avatar" :round="true" :size="160" />
                <text class="avatar-tip">点击更换头像</text>
            </view>
        </view>

        <!-- 基本信息 -->
        <view class="form-card">
            <view class="card-title-row">
                <tn-icon name="edit" size="30" :color="$theme.primaryColor" />
                <text class="card-title">基本信息</text>
            </view>

            <view class="form-item">
                <view class="form-label">
                    <text>姓名</text>
                    <text class="required">*</text>
                </view>
                <tn-input
                    v-model="form.name"
                    placeholder="请输入姓名"
                    class="form-input"
                    :border="false"
                />
            </view>

            <view class="form-item">
                <view class="form-label">
                    <text>服务分类</text>
                </view>
                <view class="form-readonly">
                    <text :style="{ color: form.category_id ? '#1F2937' : '#D1D5DB' }">
                        {{ currentCategoryName }}
                    </text>
                </view>
            </view>

            <view class="form-item">
                <view class="form-label">
                    <text>手机号</text>
                </view>
                <tn-input
                    v-model="form.mobile"
                    placeholder="请输入手机号"
                    type="number"
                    class="form-input"
                    :border="false"
                />
            </view>

            <view class="form-item no-border">
                <view class="form-label">
                    <text>从业年限</text>
                </view>
                <view class="year-input-row">
                    <tn-input
                        v-model="form.experience_years"
                        type="number"
                        placeholder="请输入年限"
                        class="form-input"
                        :border="false"
                    />
                    <text class="year-suffix">年</text>
                </view>
            </view>
        </view>

        <!-- 个人简介 -->
        <view class="form-card">
            <view class="card-title-row">
                <tn-icon name="file-text" size="30" :color="$theme.primaryColor" />
                <text class="card-title">个人简介</text>
                <text class="char-counter">{{ form.profile.length }}/500</text>
            </view>
            <textarea
                v-model="form.profile"
                class="form-textarea"
                placeholder="请简要介绍自己的风格与经验"
                :maxlength="500"
                :auto-height="true"
            />
        </view>

        <!-- 服务说明 -->
        <view class="form-card">
            <view class="card-title-row">
                <tn-icon name="list" size="30" :color="$theme.primaryColor" />
                <text class="card-title">服务说明</text>
                <text class="char-counter">{{ form.service_desc.length }}/1000</text>
            </view>
            <textarea
                v-model="form.service_desc"
                class="form-textarea"
                placeholder="填写服务内容、流程或注意事项"
                :maxlength="1000"
                :auto-height="true"
            />
        </view>

        <!-- 保存按钮 -->
        <view class="save-bar">
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
        category_id: form.category_id,
        profile: form.profile,
        service_desc: form.service_desc
    }

    if (form.mobile) payload.mobile = form.mobile
    if (form.experience_years !== '') payload.experience_years = Number(form.experience_years)

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
    background: #F4F5F7;
    padding-bottom: 140rpx;
}

.avatar-section {
    padding: 32rpx 24rpx 0;
}

.avatar-card {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 12rpx;
    padding: 40rpx;
    background: #FFFFFF;
    border-radius: 24rpx;
    box-shadow: 0 4rpx 24rpx rgba(0, 0, 0, 0.05);
}

.avatar-tip {
    font-size: 24rpx;
    color: #9CA3AF;
}

/* 表单卡片 */
.form-card {
    margin: 20rpx 24rpx 0;
    padding: 28rpx;
    background: #FFFFFF;
    border-radius: 24rpx;
    box-shadow: 0 4rpx 24rpx rgba(0, 0, 0, 0.05);
}

.card-title-row {
    display: flex;
    align-items: center;
    gap: 10rpx;
    margin-bottom: 20rpx;
}

.card-title {
    flex: 1;
    font-size: 30rpx;
    font-weight: 700;
    color: #1F2937;
}

.char-counter {
    font-size: 24rpx;
    color: #9CA3AF;
}

/* 表单项 */
.form-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 24rpx 0;
    border-bottom: 1rpx solid #F3F4F6;

    &.no-border {
        border-bottom: none;
    }
}

.form-label {
    display: flex;
    align-items: center;
    gap: 4rpx;
    min-width: 150rpx;
    font-size: 28rpx;
    color: #374151;
    font-weight: 500;

    .required {
        color: #FF2C3C;
        font-size: 28rpx;
    }
}

.form-input {
    flex: 1;
    text-align: left;
    font-size: 28rpx;
}

.form-readonly {
    flex: 1;
    font-size: 28rpx;
}

.year-input-row {
    flex: 1;
    display: flex;
    align-items: center;
    gap: 6rpx;
}

.year-suffix {
    font-size: 28rpx;
    color: #6B7280;
}

/* 文本域 */
.form-textarea {
    width: 100%;
    min-height: 180rpx;
    padding: 20rpx;
    background: #F9FAFB;
    border-radius: 16rpx;
    border: 2rpx solid #F3F4F6;
    font-size: 28rpx;
    line-height: 1.6;
    color: #1F2937;
}

/* 保存按钮 */
.save-bar {
    position: fixed;
    bottom: 0;
    left: 0;
    right: 0;
    padding: 20rpx 24rpx;
    padding-bottom: calc(20rpx + env(safe-area-inset-bottom));
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(20rpx);
    border-top: 1rpx solid #F3F4F6;
    z-index: 100;
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
    transition: all 0.2s ease;

    &:active {
        transform: translateY(2rpx);
        box-shadow: 0 4rpx 12rpx rgba(124, 58, 237, 0.25);
    }
}
</style>
