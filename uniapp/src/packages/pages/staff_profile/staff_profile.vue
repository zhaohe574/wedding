<template>
    <page-meta :page-style="$theme.pageStyle" />
    <PageShell scene="staff" hasSafeBottom>
        <BaseNavbar title="个人资料" />

        <view class="staff-profile-page staff-profile-page--static">
            <view class="staff-profile-page__content wm-page-content">
                <BaseCard
                    variant="glass"
                    scene="staff"
                    class="form-card form-card--hero wm-form-block"
                >
                    <view class="profile-hero-card__top">
                        <view class="hero-pill hero-pill--primary">
                            <text class="hero-pill__text">资料编辑</text>
                        </view>

                        <view v-if="heroBadges.length" class="profile-hero-card__badge-group">
                            <view
                                v-for="item in heroBadges"
                                :key="item.key"
                                :class="['hero-pill', `hero-pill--${item.tone}`]"
                            >
                                <text class="hero-pill__text">{{ item.text }}</text>
                            </view>
                        </view>
                    </view>

                    <view class="profile-hero-card__main">
                        <view class="profile-hero-card__avatar-panel">
                            <avatar-upload v-model="form.avatar" :round="true" :size="136" />
                            <text class="profile-hero-card__avatar-tip">更换头像</text>
                        </view>

                        <view class="profile-hero-card__info">
                            <text class="profile-hero-card__name">{{ displayName }}</text>
                            <text class="profile-hero-card__category">{{
                                currentCategoryName
                            }}</text>

                            <view class="profile-chip-list">
                                <view class="profile-chip">
                                    <tn-icon
                                        name="phone"
                                        size="18"
                                        color="var(--wm-text-secondary, #5f5a50)"
                                    />
                                    <text class="profile-chip__text">{{ mobileText }}</text>
                                </view>
                                <view class="profile-chip">
                                    <tn-icon
                                        name="calendar"
                                        size="18"
                                        color="var(--wm-text-secondary, #5f5a50)"
                                    />
                                    <text class="profile-chip__text">{{ experienceText }}</text>
                                </view>
                            </view>
                        </view>
                    </view>
                </BaseCard>

                <BaseCard variant="glass" scene="staff" class="form-card wm-form-block">
                    <view class="card-head">
                        <view class="card-head__copy">
                            <text class="card-head__title">基础信息</text>
                            <text class="card-head__desc">完善基础资料，提升资料页的完整度</text>
                        </view>
                    </view>

                    <view class="field-stack">
                        <view class="field-card wm-soft-card">
                            <view class="field-card__label">
                                <text>姓名</text>
                                <text class="field-card__required">*</text>
                            </view>
                            <view class="field-card__control">
                                <tn-input
                                    v-model="form.name"
                                    placeholder="请输入姓名"
                                    class="wm-input"
                                    :border="false"
                                />
                            </view>
                        </view>

                        <view class="field-card wm-soft-card">
                            <view class="field-card__label">
                                <text>服务分类</text>
                            </view>
                            <view class="field-card__readonly">
                                <text
                                    :class="[
                                        'field-card__value',
                                        {
                                            'is-placeholder':
                                                !form.category_id && !profileMeta.category_name
                                        }
                                    ]"
                                >
                                    {{ currentCategoryName }}
                                </text>
                            </view>
                        </view>

                        <view class="field-card wm-soft-card">
                            <view class="field-card__label">
                                <text>手机号</text>
                            </view>
                            <view class="field-card__control">
                                <tn-input
                                    v-model="form.mobile"
                                    placeholder="请输入手机号"
                                    type="number"
                                    class="wm-input"
                                    :border="false"
                                />
                            </view>
                        </view>

                        <view class="field-card wm-soft-card">
                            <view class="field-card__label">
                                <text>从业年限</text>
                            </view>
                            <view class="field-card__control field-card__control--inline">
                                <tn-input
                                    v-model="form.experience_years"
                                    type="number"
                                    placeholder="请输入年限"
                                    class="wm-input wm-input--inline"
                                    :border="false"
                                />
                                <text class="field-card__suffix">年</text>
                            </view>
                        </view>
                    </view>
                </BaseCard>

                <BaseCard variant="glass" scene="staff" class="form-card wm-form-block">
                    <view class="card-head">
                        <view class="card-head__copy">
                            <text class="card-head__title">个人简介</text>
                            <text class="card-head__desc"
                                >用一段简洁介绍突出你的服务风格与经验</text
                            >
                        </view>
                        <text class="card-head__meta">{{ form.profile.length }}/500</text>
                    </view>

                    <view class="textarea-card wm-soft-card">
                        <textarea
                            v-model="form.profile"
                            class="wm-textarea"
                            placeholder="介绍服务特点"
                            :maxlength="500"
                            :auto-height="true"
                            :show-confirm-bar="false"
                        />
                    </view>
                </BaseCard>

                <BaseCard variant="glass" scene="staff" class="form-card wm-form-block">
                    <view class="card-head">
                        <view class="card-head__copy">
                            <text class="card-head__title">服务风格标签</text>
                            <text class="card-head__desc">{{ tagNotice }}</text>
                        </view>
                    </view>

                    <view
                        v-if="tagStatusTip"
                        :class="['status-tip', `status-tip--${tagStatusTip.tone}`]"
                    >
                        <text class="status-tip__text">{{ tagStatusTip.text }}</text>
                    </view>

                    <view v-if="Object.keys(groupedTags).length" class="tag-group-list">
                        <view
                            v-for="(tags, groupName) in groupedTags"
                            :key="groupName"
                            class="tag-group-card wm-soft-card"
                        >
                            <text class="tag-group-card__title">{{ groupName }}</text>
                            <view class="tag-chip-list">
                                <view
                                    v-for="tag in tags"
                                    :key="tag.id"
                                    :class="[
                                        'tag-chip',
                                        { 'tag-chip--active': isTagSelected(tag.id) }
                                    ]"
                                    @click="toggleTag(tag.id)"
                                >
                                    <text class="tag-chip__text">{{ tag.name }}</text>
                                </view>
                            </view>
                        </view>
                    </view>
                    <view v-else class="empty-tip">
                        <text>当前分类下暂无可选标签</text>
                    </view>
                </BaseCard>

                <BaseCard variant="glass" scene="staff" class="form-card wm-form-block">
                    <view class="card-head">
                        <view class="card-head__copy">
                            <text class="card-head__title">服务说明</text>
                            <text class="card-head__desc">补充服务亮点、流程安排或注意事项</text>
                        </view>
                        <text class="card-head__meta">{{ form.service_desc.length }}/1000</text>
                    </view>

                    <view class="textarea-card wm-soft-card">
                        <textarea
                            v-model="form.service_desc"
                            class="wm-textarea"
                            placeholder="补充服务说明"
                            :maxlength="1000"
                            :auto-height="true"
                            :show-confirm-bar="false"
                        />
                    </view>
                </BaseCard>

                <BaseCard variant="glass" scene="staff" class="form-card wm-form-block">
                    <view class="card-head">
                        <view class="card-head__copy">
                            <text class="card-head__title">长图详情</text>
                            <text class="card-head__desc"
                                >支持图片和文本模块，适合展示完整服务内容</text
                            >
                        </view>
                        <text class="card-head__meta">{{ longDetailCount }} 个模块</text>
                    </view>

                    <staff-long-detail-editor
                        v-model="form.long_detail"
                        @uploading-change="handleLongDetailUploadingChange"
                    />
                </BaseCard>
            </view>

            <StaffActionBar
                :primary-text="saving ? '保存中...' : '保存资料'"
                :loading="saving"
                disable-feedback
                @primary="handleSave"
            />
        </view>
    </PageShell>
</template>

<script setup lang="ts">
import { computed, reactive, ref } from 'vue'
import { onShow } from '@dcloudio/uni-app'
import BaseCard from '@/components/base/BaseCard.vue'
import PageShell from '@/components/base/PageShell.vue'
import BaseNavbar from '@/components/base/BaseNavbar.vue'
import StaffActionBar from '@/packages/components/staff-workspace/staff-action-bar.vue'
import StaffLongDetailEditor from '@/packages/components/staff-long-detail/staff-long-detail-editor.vue'
import { staffCenterProfile, staffCenterUpdateProfile } from '@/api/staffCenter'
import { getServiceCategories, getStyleTags } from '@/api/service'
import {
    parseLongDetailContent,
    parseLongDetailDraftContent,
    stringifyLongDetailContent
} from '@/packages/components/staff-long-detail/utils'
import { ensureStaffCenterAccess } from '@/packages/common/utils/staff-center'
import { useThemeStore } from '@/stores/theme'

type HeroBadgeTone = 'primary' | 'success' | 'warning' | 'danger' | 'neutral'

interface HeroBadgeItem {
    key: string
    text: string
    tone: HeroBadgeTone
}

const $theme = useThemeStore()
const saving = ref(false)
const longDetailUploading = ref(false)
const profileLoaded = ref(false)
const categories = ref<Array<{ id: number; name: string }>>([])
const groupedTags = ref<Record<string, Array<{ id: number; name: string }>>>({})

const form = reactive({
    name: '',
    avatar: '',
    mobile: '',
    category_id: 0,
    experience_years: '',
    profile: '',
    service_desc: '',
    long_detail: '',
    tag_ids: [] as number[]
})

const profileMeta = reactive({
    category_name: '',
    status: 0,
    status_desc: '',
    audit_status: 0,
    audit_status_desc: '',
    current_tag_names: [] as string[],
    pending_tag_ids: [] as number[],
    pending_tag_names: [] as string[],
    tag_apply_status: null as number | null,
    tag_apply_status_desc: '',
    tag_apply_reject_reason: '',
    staff_tag_review_enabled: 0
})

const currentCategoryName = computed(() => {
    const match = categories.value.find((item) => item.id === Number(form.category_id))
    return match?.name || profileMeta.category_name || '待设置服务分类'
})

const displayName = computed(() => form.name.trim() || '未填写姓名')

const mobileText = computed(() => form.mobile || '未绑定手机号')

const experienceText = computed(() => {
    if (!form.experience_years) return '待补充从业年限'
    return `${form.experience_years} 年经验`
})

const tagNotice = computed(() => {
    return profileMeta.staff_tag_review_enabled === 1
        ? '保存后需管理员审核通过才会生效'
        : '保存后立即生效'
})

const tagStatusTip = computed(() => {
    if (profileMeta.staff_tag_review_enabled !== 1) {
        return null
    }
    if (profileMeta.tag_apply_status === 0) {
        return {
            tone: 'warning',
            text: '当前存在待审核标签申请，新的保存会覆盖原待审内容。'
        }
    }
    if (profileMeta.tag_apply_status === 2) {
        const reason = profileMeta.tag_apply_reject_reason
            ? `拒绝原因：${profileMeta.tag_apply_reject_reason}`
            : '请调整后重新提交。'
        return {
            tone: 'danger',
            text: `上次标签申请未通过审核。${reason}`
        }
    }
    return {
        tone: 'info',
        text: '当前标签保存后需管理员审核通过才会生效。'
    }
})

const longDetailCount = computed(() => parseLongDetailContent(form.long_detail).length)

const getAuditTone = (status: number): HeroBadgeTone => {
    if (status === 1) return 'success'
    if (status === 2) return 'danger'
    if (status === 0) return 'warning'
    return 'neutral'
}

const getStatusTone = (status: number): HeroBadgeTone => {
    if (status === 1) return 'success'
    if (status === 0) return 'neutral'
    return 'warning'
}

const heroBadges = computed<HeroBadgeItem[]>(() => {
    const badges: HeroBadgeItem[] = []

    if (profileMeta.audit_status_desc) {
        badges.push({
            key: 'audit',
            text: profileMeta.audit_status_desc,
            tone: getAuditTone(Number(profileMeta.audit_status))
        })
    }

    if (profileMeta.status_desc) {
        badges.push({
            key: 'status',
            text: profileMeta.status_desc,
            tone: getStatusTone(Number(profileMeta.status))
        })
    }

    return badges
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

const loadTags = async () => {
    if (!form.category_id) {
        groupedTags.value = {}
        form.tag_ids = []
        return
    }
    const data = await getStyleTags({
        grouped: 1,
        category_id: Number(form.category_id)
    })
    groupedTags.value = (data || {}) as Record<string, Array<{ id: number; name: string }>>

    const availableIds = new Set<number>()
    Object.values(groupedTags.value).forEach((group) => {
        group.forEach((tag) => availableIds.add(Number(tag.id)))
    })
    form.tag_ids = form.tag_ids.filter((id) => availableIds.has(Number(id)))
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
    form.long_detail = data?.long_detail || ''
    profileMeta.current_tag_names = Array.isArray(data?.tag_names) ? data.tag_names : []
    profileMeta.pending_tag_ids = Array.isArray(data?.pending_tag_ids)
        ? data.pending_tag_ids.map((item: any) => Number(item))
        : []
    profileMeta.pending_tag_names = Array.isArray(data?.pending_tag_names)
        ? data.pending_tag_names
        : []
    profileMeta.tag_apply_status =
        data?.tag_apply_status === null || data?.tag_apply_status === undefined
            ? null
            : Number(data.tag_apply_status)
    profileMeta.tag_apply_status_desc = data?.tag_apply_status_desc || ''
    profileMeta.tag_apply_reject_reason = data?.tag_apply_reject_reason || ''
    profileMeta.staff_tag_review_enabled = Number(data?.staff_tag_review_enabled ?? 0)
    const effectiveTagIds = Array.isArray(data?.tag_ids)
        ? data.tag_ids.map((item: any) => Number(item))
        : []
    form.tag_ids = profileMeta.pending_tag_ids.length
        ? [...profileMeta.pending_tag_ids]
        : effectiveTagIds

    profileMeta.category_name = data?.category_name || ''
    profileMeta.status = Number(data?.status || 0)
    profileMeta.status_desc = data?.status_desc || ''
    profileMeta.audit_status = Number(data?.audit_status || 0)
    profileMeta.audit_status_desc = data?.audit_status_desc || ''
}

const isTagSelected = (tagId: number) => form.tag_ids.includes(Number(tagId))

const toggleTag = (tagId: number) => {
    const currentId = Number(tagId)
    if (!currentId) return
    if (isTagSelected(currentId)) {
        form.tag_ids = form.tag_ids.filter((id) => id !== currentId)
        return
    }
    form.tag_ids = [...form.tag_ids, currentId]
}

const handleLongDetailUploadingChange = (value: boolean) => {
    longDetailUploading.value = value
}

const handleSave = async () => {
    if (!form.name.trim()) {
        uni.showToast({ title: '请输入姓名', icon: 'none' })
        return
    }

    if (longDetailUploading.value) {
        uni.showToast({ title: '请等待图片上传完成后再保存', icon: 'none' })
        return
    }

    const payload: any = {
        name: form.name.trim(),
        avatar: form.avatar,
        category_id: form.category_id,
        profile: form.profile,
        service_desc: form.service_desc,
        long_detail: stringifyLongDetailContent(parseLongDetailDraftContent(form.long_detail)),
        tag_ids: form.tag_ids
    }

    if (form.mobile) payload.mobile = form.mobile
    if (form.experience_years !== '') payload.experience_years = Number(form.experience_years)

    saving.value = true
    try {
        const res = await staffCenterUpdateProfile(payload)
        uni.showToast({
            title: res?.tag_action === 'pending' ? '标签已提交审核' : '保存成功',
            icon: 'success'
        })
        await loadProfile()
        await loadTags()
    } catch (e: any) {
        const msg = typeof e === 'string' ? e : e?.msg || e?.message || '保存失败'
        uni.showToast({ title: msg, icon: 'none' })
    } finally {
        saving.value = false
    }
}

onShow(async () => {
    if (!(await ensureStaffCenterAccess())) return

    if (!profileLoaded.value) {
        await Promise.all([loadCategories(), loadProfile()])
        await loadTags()
        profileLoaded.value = true
    }
})
</script>

<style lang="scss" scoped>
.staff-profile-page {
    min-height: 100vh;
    padding: 12rpx 0 calc(176rpx + env(safe-area-inset-bottom));
    box-sizing: border-box;
    background: var(--wm-color-page-bg, #ffffff);

    &__content {
        display: flex;
        flex-direction: column;
        gap: 16rpx;
        padding: 0 var(--wm-space-page-x, 37rpx);
    }
}

.form-card {
    position: relative;
    overflow: hidden;
    box-sizing: border-box;
}

.staff-profile-page--static,
.staff-profile-page--static :deep(*) {
    -webkit-tap-highlight-color: transparent;
    animation: none !important;
    transition: none !important;
}

.staff-profile-page--static :deep(*:active) {
    transform: none !important;
}

.tag-group-list {
    display: flex;
    flex-direction: column;
    gap: 18rpx;
}

.tag-group-card {
    display: flex;
    flex-direction: column;
    gap: 14rpx;
}

.tag-group-card__title {
    font-size: 24rpx;
    font-weight: 700;
    line-height: 1.4;
    color: var(--wm-text-primary, #111111);
}

.tag-chip-list {
    display: flex;
    flex-wrap: wrap;
    gap: 14rpx;
}

.tag-chip {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-height: 64rpx;
    padding: 0 26rpx;
    border-radius: 999rpx;
    border: 2rpx solid rgba(216, 194, 138, 0.34);
    background: rgba(255, 255, 255, 0.78);
}

.tag-chip--active {
    border-color: rgba(200, 164, 93, 0.9);
    background: linear-gradient(
        135deg,
        rgba(247, 240, 223, 0.96) 0%,
        rgba(216, 194, 138, 0.36) 100%
    );
    box-shadow: 0 12rpx 24rpx rgba(200, 164, 93, 0.16);
}

.tag-chip__text {
    font-size: 24rpx;
    font-weight: 600;
    line-height: 1.4;
    color: var(--wm-text-primary, #111111);
}

.status-tip {
    margin-bottom: 18rpx;
    padding: 20rpx 22rpx;
    border-radius: 24rpx;
}

.status-tip--info {
    background: rgba(154, 147, 136, 0.12);
    color: #5f5a50;
}

.status-tip--warning {
    background: rgba(159, 122, 46, 0.12);
    color: #9f7a2e;
}

.status-tip--danger {
    background: rgba(90, 68, 51, 0.12);
    color: #5a4433;
}

.status-tip__text {
    font-size: 24rpx;
    font-weight: 600;
    line-height: 1.6;
}

.empty-tip {
    padding: 16rpx 0 6rpx;
    font-size: 24rpx;
    line-height: 1.6;
    color: var(--wm-text-secondary, #5f5a50);
}

.profile-hero-card {
    display: flex;
    flex-direction: column;
    gap: 20rpx;
    padding: 28rpx 30rpx 32rpx;
    border-radius: 46rpx;
    border: 1rpx solid var(--wm-color-border-strong, #d8c28a);
    background: var(--wm-hero-gradient, linear-gradient(135deg, #ffffff 0%, #f7f0df 100%));
    box-shadow: 0 20rpx 42rpx rgba(17, 17, 17, 0.16);

    &__top,
    &__main {
        display: flex;
    }

    &__top {
        align-items: center;
        justify-content: space-between;
        gap: 16rpx;
    }

    &__badge-group {
        flex: 1;
        min-width: 0;
        display: flex;
        flex-wrap: wrap;
        justify-content: flex-end;
        gap: 10rpx;
    }

    &__main {
        align-items: center;
        gap: 22rpx;
    }

    &__avatar-panel {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 10rpx;
        flex-shrink: 0;
    }

    &__avatar-tip {
        font-size: 22rpx;
        font-weight: 600;
        line-height: 1.35;
        color: var(--wm-text-secondary, #5f5a50);
    }

    &__info {
        flex: 1;
        min-width: 0;
        display: flex;
        flex-direction: column;
        gap: 8rpx;
    }

    &__name {
        font-size: 40rpx;
        font-weight: 700;
        line-height: 1.3;
        color: var(--wm-text-primary, #111111);
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    &__category {
        font-size: 24rpx;
        font-weight: 600;
        line-height: 1.45;
        color: var(--wm-text-secondary, #5f5a50);
    }
}

.hero-pill {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-height: 42rpx;
    padding: 11rpx 18rpx;
    border-radius: 999rpx;
    box-sizing: border-box;

    &__text {
        font-size: 22rpx;
        font-weight: 700;
        line-height: 1;
    }

    &--primary {
        background: #f3f2ee;

        .hero-pill__text {
            color: var(--wm-color-primary, #0b0b0b);
        }
    }

    &--success,
    &--warning,
    &--danger,
    &--neutral {
        border: 1rpx solid var(--wm-color-border, #e7e2d6);
        background: rgba(255, 255, 255, 0.8);
    }

    &--success .hero-pill__text {
        color: #4d4a42;
    }

    &--warning .hero-pill__text {
        color: #9f7a2e;
    }

    &--danger .hero-pill__text {
        color: #5a4433;
    }

    &--neutral .hero-pill__text {
        color: #6c665c;
    }
}

.profile-chip-list {
    display: flex;
    flex-wrap: wrap;
    gap: 12rpx;
    margin-top: 4rpx;
}

.profile-chip {
    display: inline-flex;
    align-items: center;
    gap: 8rpx;
    min-height: 50rpx;
    padding: 10rpx 16rpx;
    border-radius: 999rpx;
    border: 1rpx solid rgba(231, 226, 214, 0.94);
    background: rgba(255, 255, 255, 0.8);
    box-sizing: border-box;

    &__text {
        font-size: 22rpx;
        font-weight: 600;
        line-height: 1.35;
        color: var(--wm-text-secondary, #5f5a50);
    }
}

.form-card:not(.form-card--hero) {
    display: flex;
    flex-direction: column;
    gap: 16rpx;
    padding: 26rpx 30rpx;
    border-radius: 44rpx;
    border: 1rpx solid var(--wm-color-border, #e7e2d6);
    background: rgba(255, 255, 255, 0.92);
    box-shadow: var(--wm-shadow-card, 0 18rpx 36rpx rgba(17, 17, 17, 0.2));
    backdrop-filter: blur(24rpx);
    -webkit-backdrop-filter: blur(24rpx);
}

.card-head {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 18rpx;

    &__copy {
        flex: 1;
        min-width: 0;
    }

    &__title {
        font-size: 32rpx;
        font-weight: 700;
        line-height: 1.35;
        color: var(--wm-text-primary, #111111);
    }

    &__desc {
        margin-top: 6rpx;
        font-size: 22rpx;
        line-height: 1.5;
        color: var(--wm-text-secondary, #5f5a50);
    }

    &__meta {
        flex-shrink: 0;
        padding-top: 4rpx;
        font-size: 22rpx;
        font-weight: 700;
        line-height: 1.35;
        color: var(--wm-text-secondary, #5f5a50);
    }
}

.field-stack {
    display: flex;
    flex-direction: column;
    gap: 12rpx;
}

.field-card,
.textarea-card {
    border-radius: 30rpx;
    border: 1rpx solid var(--wm-color-border, #e7e2d6);
    background: #ffffff;
    box-sizing: border-box;
}

.field-card {
    display: flex;
    flex-direction: column;
    gap: 10rpx;
    padding: 20rpx 22rpx;

    &__label {
        display: flex;
        align-items: center;
        gap: 6rpx;
        font-size: 22rpx;
        font-weight: 600;
        line-height: 1.35;
        color: var(--wm-text-secondary, #5f5a50);
    }

    &__required {
        color: #0b0b0b;
    }

    &__control,
    &__readonly {
        min-height: 44rpx;
        display: flex;
        align-items: center;
    }

    &__control--inline {
        justify-content: space-between;
        gap: 12rpx;
    }

    &__value,
    &__suffix {
        font-size: 28rpx;
        font-weight: 600;
        line-height: 1.35;
        color: var(--wm-text-primary, #111111);
    }

    &__value.is-placeholder {
        color: var(--wm-text-tertiary, #9a9388);
    }

    &__suffix {
        flex-shrink: 0;
        color: var(--wm-text-secondary, #5f5a50);
    }
}

.wm-input {
    width: 100%;
    font-size: 28rpx;
    color: var(--wm-text-primary, #111111);

    &--inline {
        flex: 1;
        min-width: 0;
    }
}

.textarea-card {
    padding: 18rpx 22rpx;
}

.wm-textarea {
    width: 100%;
    min-height: 180rpx;
    font-size: 28rpx;
    line-height: 1.7;
    color: var(--wm-text-primary, #111111);
}

.bottom-bar {
    position: fixed;
    left: 0;
    right: 0;
    bottom: 0;
    z-index: 40;
    padding: 12rpx var(--wm-space-page-x, 37rpx) calc(20rpx + env(safe-area-inset-bottom));
    background: rgba(248, 247, 242, 0.88);
    border-top: 1rpx solid rgba(231, 226, 214, 0.9);
    backdrop-filter: blur(24rpx);
    -webkit-backdrop-filter: blur(24rpx);
    box-sizing: border-box;

    &__inner {
        display: flex;
        gap: 12rpx;
    }
}

.bottom-bar__action {
    flex: 1;
    min-height: 88rpx;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10rpx;
    border-radius: 36rpx;
    font-size: 30rpx;
    font-weight: 700;
    box-shadow: 0 14rpx 28rpx rgba(11, 11, 11, 0.18);
}

:deep(.wm-input .input-text),
:deep(.wm-input input) {
    font-size: 28rpx !important;
    font-weight: 600;
    color: var(--wm-text-primary, #111111) !important;
}

:deep(.wm-input .input-placeholder),
:deep(.wm-input .tn-input__placeholder) {
    color: var(--wm-text-tertiary, #9a9388) !important;
}
</style>
