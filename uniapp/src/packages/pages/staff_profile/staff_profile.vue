<template>
    <page-meta :page-style="$theme.pageStyle">
        <navigation-bar
            title="个人资料"
            :front-color="$theme.navColor"
            :background-color="$theme.navBgColor"
        />
    </page-meta>

    <view class="page-container">
        <view class="hero-section">
            <view
                class="hero-card"
                :style="{
                    background: `linear-gradient(145deg, ${$theme.primaryColor} 0%, ${$theme.secondaryColor || $theme.primaryColor} 76%)`
                }"
            >
                <view class="hero-main">
                    <view class="avatar-panel">
                        <avatar-upload v-model="form.avatar" :round="true" :size="160" />
                        <text class="avatar-tip">点击更换头像</text>
                    </view>

                    <view class="hero-info">
                        <text class="hero-name">{{ form.name || '未填写姓名' }}</text>
                        <text class="hero-category">{{ currentCategoryName }}</text>

                        <view class="hero-chips">
                            <view class="hero-chip">
                                <tn-icon name="phone" size="20" color="rgba(255,255,255,0.8)" />
                                <text>{{ form.mobile || '未绑定手机号' }}</text>
                            </view>
                            <view class="hero-chip">
                                <tn-icon name="calendar" size="20" color="rgba(255,255,255,0.8)" />
                                <text>{{ experienceText }}</text>
                            </view>
                        </view>
                    </view>
                </view>

                <view class="hero-tips">
                    <text>资料越完整，越容易提升客户信任和转化效率。</text>
                </view>
            </view>
        </view>

        <view class="section-card">
            <view class="section-head">
                <view>
                    <text class="section-title">基础信息</text>
                    <text class="section-subtitle">维护对外展示的核心身份信息</text>
                </view>
            </view>

            <view class="form-list">
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
                        <text :style="{ color: form.category_id ? '#0F172A' : '#94A3B8' }">
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
        </view>

        <view class="section-card">
            <view class="section-head">
                <view>
                    <text class="section-title">个人简介</text>
                    <text class="section-subtitle">告诉客户你的风格、经验与擅长方向</text>
                </view>
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

        <view class="section-card">
            <view class="section-head">
                <view>
                    <text class="section-title">服务说明</text>
                    <text class="section-subtitle">让客户提前了解服务流程与注意事项</text>
                </view>
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

        <view class="save-bar">
            <view
                class="save-btn"
                :style="{
                    background: `linear-gradient(135deg, ${$theme.primaryColor} 0%, ${$theme.secondaryColor || $theme.primaryColor} 100%)`,
                    color: $theme.btnColor,
                    opacity: saving ? 0.7 : 1
                }"
                @click="handleSave"
            >
                <tn-icon v-if="saving" name="loading" size="30" :color="$theme.btnColor" />
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
    return match?.name || '待设置服务分类'
})

const experienceText = computed(() => {
    if (!form.experience_years) return '待补充从业年限'
    return `${form.experience_years} 年经验`
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
    padding: 24rpx 24rpx 150rpx;
    background:
        radial-gradient(circle at top left, rgba(191, 219, 254, 0.68) 0, rgba(246, 248, 252, 0) 36%),
        linear-gradient(180deg, #F6F8FC 0%, #F4F6FB 100%);
}

.hero-card {
    padding: 28rpx;
    border-radius: 30rpx;
    box-shadow: 0 18rpx 36rpx rgba(37, 99, 235, 0.18);
}

.hero-main {
    display: flex;
    align-items: center;
    gap: 24rpx;
}

.avatar-panel {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 10rpx;
}

.avatar-tip {
    font-size: 22rpx;
    color: rgba(255, 255, 255, 0.74);
}

.hero-info {
    flex: 1;
    min-width: 0;
}

.hero-name {
    display: block;
    font-size: 38rpx;
    font-weight: 700;
    color: #FFFFFF;
}

.hero-category {
    display: block;
    margin-top: 10rpx;
    font-size: 24rpx;
    color: rgba(255, 255, 255, 0.8);
}

.hero-chips {
    display: flex;
    flex-wrap: wrap;
    gap: 12rpx;
    margin-top: 20rpx;
}

.hero-chip {
    display: inline-flex;
    align-items: center;
    gap: 8rpx;
    padding: 10rpx 16rpx;
    border-radius: 999rpx;
    background: rgba(255, 255, 255, 0.16);
    font-size: 22rpx;
    color: #FFFFFF;
}

.hero-tips {
    margin-top: 22rpx;
    padding-top: 18rpx;
    border-top: 1rpx solid rgba(255, 255, 255, 0.14);
    font-size: 22rpx;
    line-height: 1.6;
    color: rgba(255, 255, 255, 0.78);
}

.section-card {
    margin-top: 22rpx;
    padding: 28rpx;
    border-radius: 28rpx;
    background: rgba(255, 255, 255, 0.92);
    border: 1rpx solid rgba(255, 255, 255, 0.72);
    box-shadow: 0 18rpx 30rpx rgba(15, 23, 42, 0.05);
}

.section-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 18rpx;
}

.section-title {
    display: block;
    font-size: 32rpx;
    font-weight: 700;
    color: #0F172A;
}

.section-subtitle {
    display: block;
    margin-top: 8rpx;
    font-size: 22rpx;
    color: #94A3B8;
}

.char-counter {
    font-size: 22rpx;
    color: #94A3B8;
}

.form-list {
    margin-top: 14rpx;
}

.form-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 18rpx;
    padding: 28rpx 0;
    border-bottom: 1rpx solid #EDF2F7;

    &.no-border {
        border-bottom: none;
        padding-bottom: 8rpx;
    }
}

.form-label {
    min-width: 160rpx;
    display: flex;
    align-items: center;
    gap: 4rpx;
    font-size: 28rpx;
    font-weight: 600;
    color: #0F172A;
}

.required {
    color: #EF4444;
}

.form-input,
.form-readonly {
    flex: 1;
    min-width: 0;
    font-size: 28rpx;
    color: #0F172A;
}

.year-input-row {
    flex: 1;
    display: flex;
    align-items: center;
    gap: 8rpx;
}

.year-suffix {
    font-size: 28rpx;
    color: #64748B;
}

.form-textarea {
    width: 100%;
    min-height: 210rpx;
    margin-top: 20rpx;
    padding: 24rpx;
    border-radius: 24rpx;
    background: linear-gradient(180deg, #F8FAFC 0%, #FFFFFF 100%);
    border: 2rpx solid #E2E8F0;
    font-size: 28rpx;
    line-height: 1.7;
    color: #0F172A;
}

.save-bar {
    position: fixed;
    left: 0;
    right: 0;
    bottom: 0;
    padding: 20rpx 24rpx;
    padding-bottom: calc(20rpx + env(safe-area-inset-bottom));
    background: rgba(255, 255, 255, 0.92);
    backdrop-filter: blur(18rpx);
    border-top: 1rpx solid rgba(226, 232, 240, 0.9);
}

.save-btn {
    height: 88rpx;
    border-radius: 999rpx;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10rpx;
    font-size: 30rpx;
    font-weight: 700;
    box-shadow: 0 16rpx 28rpx rgba(37, 99, 235, 0.16);
}
</style>
