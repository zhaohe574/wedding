<template>
    <page-meta :page-style="$theme.pageStyle" />
    <PageShell scene="staff" tone="workspace" hasSafeBottom>
        <BaseNavbar
            title="个人资料"
            title-align="center"
            variant="solid"
            bg-color="#191713"
            text-color="#FFFDF8"
        />

        <view class="staff-profile-page staff-profile-page--static">
            <view class="staff-profile-page__content wm-page-content">
                <BaseCard
                    variant="hero"
                    scene="staff"
                    class="profile-summary wm-form-block"
                    background="linear-gradient(145deg, #2B261D 0%, #191713 62%, #3A2A16 100%)"
                    border="1rpx solid #D9BE82"
                    box-shadow="0 28rpx 68rpx rgba(74, 43, 24, 0.18)"
                >
                    <view class="profile-summary__badges">
                        <StatusBadge tone="primary" size="sm" class="profile-summary__badge">
                            资料编辑
                        </StatusBadge>

                        <view v-if="heroBadges.length" class="profile-summary__badge-group">
                            <StatusBadge
                                v-for="item in heroBadges"
                                :key="item.key"
                                :tone="item.tone"
                                size="sm"
                                class="profile-summary__badge"
                            >
                                {{ item.text }}
                            </StatusBadge>
                        </view>
                    </view>

                    <view class="profile-summary__body">
                        <view class="profile-summary__avatar-panel">
                            <avatar-upload v-model="form.avatar" :round="true" :size="136" />
                            <text class="profile-summary__avatar-tip">更换头像</text>
                        </view>

                        <view class="profile-summary__copy">
                            <text class="profile-summary__name">{{ displayName }}</text>
                            <text class="profile-summary__category">{{ currentCategoryName }}</text>

                            <view class="profile-summary__meta-grid">
                                <view class="profile-meta">
                                    <text class="profile-meta__label">手机号</text>
                                    <text class="profile-meta__value">{{ mobileText }}</text>
                                </view>
                                <view class="profile-meta">
                                    <text class="profile-meta__label">从业年限</text>
                                    <text class="profile-meta__value">{{ experienceText }}</text>
                                </view>
                            </view>
                        </view>
                    </view>
                </BaseCard>

                <BaseCard variant="panel" scene="staff" class="profile-section wm-form-block">
                    <view class="section-head">
                        <text class="section-head__title">基础资料</text>
                    </view>

                    <view class="profile-form-grid">
                        <BaseInput
                            v-model="form.name"
                            label="姓名"
                            placeholder="请输入姓名"
                            clearable
                        >
                            <template #suffix>
                                <text class="required-mark">*</text>
                            </template>
                        </BaseInput>

                        <BaseInput
                            v-model="form.mobile"
                            label="手机号"
                            placeholder="请输入手机号"
                            type="tel"
                            clearable
                        />

                        <BaseInput
                            v-model="form.experience_years"
                            label="从业年限"
                            placeholder="请输入年限"
                            type="number"
                            clearable
                        >
                            <template #suffix>
                                <text class="input-suffix">年</text>
                            </template>
                        </BaseInput>

                        <view class="readonly-field">
                            <text class="readonly-field__label">服务分类</text>
                            <text
                                :class="[
                                    'readonly-field__value',
                                    {
                                        'readonly-field__value--placeholder':
                                            !form.category_id && !profileMeta.category_name
                                    }
                                ]"
                            >
                                {{ currentCategoryName }}
                            </text>
                        </view>
                    </view>
                </BaseCard>

                <BaseCard variant="panel" scene="staff" class="profile-section wm-form-block">
                    <view class="section-head">
                        <text class="section-head__title">服务内容</text>
                    </view>

                    <view class="textarea-stack">
                        <view class="textarea-field">
                            <view class="textarea-field__head">
                                <text class="textarea-field__label">个人简介</text>
                                <text class="textarea-field__count">{{ form.profile.length }}/500</text>
                            </view>
                            <textarea
                                v-model="form.profile"
                                class="profile-textarea"
                                placeholder="介绍服务特点"
                                :maxlength="500"
                                :auto-height="true"
                                :show-confirm-bar="false"
                            />
                        </view>

                        <view class="textarea-field">
                            <view class="textarea-field__head">
                                <text class="textarea-field__label">服务说明</text>
                                <text class="textarea-field__count">
                                    {{ form.service_desc.length }}/1000
                                </text>
                            </view>
                            <textarea
                                v-model="form.service_desc"
                                class="profile-textarea profile-textarea--large"
                                placeholder="补充服务说明"
                                :maxlength="1000"
                                :auto-height="true"
                                :show-confirm-bar="false"
                            />
                        </view>
                    </view>
                </BaseCard>

                <BaseCard variant="panel" scene="staff" class="profile-section wm-form-block">
                    <view class="section-head">
                        <text class="section-head__title">服务风格</text>
                        <StatusBadge tone="warning" size="sm" class="section-head__badge">
                            {{ tagNotice }}
                        </StatusBadge>
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

                <BaseCard variant="panel" scene="staff" class="profile-section wm-form-block">
                    <view class="section-head">
                        <text class="section-head__title">长图详情</text>
                        <text class="section-head__meta">{{ longDetailCount }} 个模块</text>
                    </view>

                    <staff-long-detail-editor
                        v-model="form.long_detail"
                        @uploading-change="handleLongDetailUploadingChange"
                    />
                </BaseCard>
            </view>

            <ActionArea sticky safeBottom tone="solid">
                <view class="profile-action-bar">
                    <BaseButton
                        block
                        variant="dark"
                        height="88rpx"
                        :loading="saving"
                        :label="saving ? '保存中...' : '保存资料'"
                        @click="handleSave"
                    />
                </view>
            </ActionArea>
        </view>
    </PageShell>
</template>

<script setup lang="ts">
import { computed, reactive, ref } from 'vue'
import { onShow } from '@dcloudio/uni-app'
import ActionArea from '@/components/base/ActionArea.vue'
import BaseButton from '@/components/base/BaseButton.vue'
import BaseCard from '@/components/base/BaseCard.vue'
import PageShell from '@/components/base/PageShell.vue'
import BaseNavbar from '@/components/base/BaseNavbar.vue'
import BaseInput from '@/components/base/BaseInput.vue'
import StatusBadge from '@/components/base/StatusBadge.vue'
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

.profile-summary,
.profile-section {
    position: relative;
    box-sizing: border-box;
}

.profile-summary {
    display: flex;
    flex-direction: column;
    gap: 28rpx;

    &__badges {
        display: flex;
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

    &__body {
        display: flex;
        align-items: center;
        gap: 26rpx;
    }

    &__avatar-panel {
        flex-shrink: 0;
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 10rpx;
    }

    &__avatar-tip {
        font-size: 22rpx;
        font-weight: 800;
        line-height: 1.35;
        color: rgba(255, 253, 248, 0.68);
    }

    &__copy {
        flex: 1;
        min-width: 0;
        display: flex;
        flex-direction: column;
        gap: 10rpx;
    }

    &__name {
        font-size: 40rpx;
        font-weight: 900;
        line-height: 1.25;
        color: var(--wm-text-inverse, #fffdf8);
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    &__category {
        font-size: 24rpx;
        font-weight: 800;
        line-height: 1.45;
        color: rgba(255, 253, 248, 0.72);
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    &__meta-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 12rpx;
        margin-top: 6rpx;
    }
}

.profile-meta {
    min-width: 0;
    display: flex;
    flex-direction: column;
    gap: 6rpx;
    padding: 16rpx 18rpx;
    border-radius: 24rpx;
    border: 1rpx solid rgba(217, 190, 130, 0.22);
    background: rgba(255, 253, 248, 0.08);
    box-sizing: border-box;

    &__label {
        font-size: 20rpx;
        font-weight: 800;
        line-height: 1.3;
        color: rgba(255, 253, 248, 0.56);
    }

    &__value {
        font-size: 23rpx;
        font-weight: 900;
        line-height: 1.35;
        color: var(--wm-text-inverse, #fffdf8);
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
}

.profile-section {
    display: flex;
    flex-direction: column;
    gap: 22rpx;
}

.section-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 18rpx;
    min-width: 0;

    &__title {
        flex: 1;
        min-width: 0;
        font-size: 32rpx;
        font-weight: 900;
        line-height: 1.35;
        color: var(--wm-text-primary, #191713);
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    &__badge,
    &__meta {
        flex-shrink: 0;
    }

    &__meta {
        font-size: 23rpx;
        font-weight: 900;
        line-height: 1.35;
        color: var(--wm-color-gold, #b8954a);
    }
}

.profile-form-grid,
.textarea-stack {
    display: flex;
    flex-direction: column;
    gap: 18rpx;
}

.readonly-field {
    display: flex;
    flex-direction: column;
    justify-content: center;
    gap: 10rpx;
    min-height: 96rpx;
    padding: 20rpx 28rpx;
    border-radius: var(--wm-radius-input, 44rpx);
    border: 1rpx solid var(--wm-color-border, #d8c9ad);
    background: var(--wm-color-bg-soft, #faf6ee);
    box-shadow: var(--wm-shadow-soft, 0 16rpx 36rpx rgba(74, 43, 24, 0.07));
    box-sizing: border-box;

    &__label {
        font-size: 24rpx;
        font-weight: 900;
        line-height: 1.35;
        color: var(--wm-text-secondary, #665e52);
    }

    &__value {
        font-size: 28rpx;
        font-weight: 900;
        line-height: 1.35;
        color: var(--wm-text-primary, #191713);
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    &__value--placeholder {
        color: var(--wm-text-tertiary, #8a806f);
    }
}

.required-mark,
.input-suffix {
    flex-shrink: 0;
    font-size: 24rpx;
    font-weight: 900;
    line-height: 1;
}

.required-mark {
    color: var(--wm-color-clay, #9a6b35);
}

.input-suffix {
    color: var(--wm-text-secondary, #665e52);
}

.textarea-field {
    display: flex;
    flex-direction: column;
    gap: 14rpx;
    padding: 22rpx 24rpx;
    border-radius: 32rpx;
    border: 1rpx solid var(--wm-color-border, #d8c9ad);
    background: var(--wm-color-bg-card, #fffdf8);
    box-shadow: var(--wm-shadow-soft, 0 16rpx 36rpx rgba(74, 43, 24, 0.07));
    box-sizing: border-box;

    &__head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 16rpx;
    }

    &__label {
        font-size: 24rpx;
        font-weight: 900;
        line-height: 1.35;
        color: var(--wm-text-secondary, #665e52);
    }

    &__count {
        flex-shrink: 0;
        font-size: 21rpx;
        font-weight: 800;
        line-height: 1.35;
        color: var(--wm-text-tertiary, #8a806f);
    }
}

.profile-textarea {
    width: 100%;
    min-height: 176rpx;
    font-size: 28rpx;
    font-weight: 700;
    line-height: 1.68;
    color: var(--wm-text-primary, #191713);
    box-sizing: border-box;

    &--large {
        min-height: 220rpx;
    }
}

.profile-action-bar {
    width: 100%;
}

.profile-section .tag-group-card {
    padding: 22rpx 24rpx;
    border-radius: 30rpx;
    border: 1rpx solid var(--wm-color-border, #d8c9ad);
    background: var(--wm-color-bg-card, #fffdf8);
    box-shadow: var(--wm-shadow-soft, 0 16rpx 36rpx rgba(74, 43, 24, 0.07));
    box-sizing: border-box;
}

.profile-section .tag-group-card__title {
    font-size: 25rpx;
    font-weight: 900;
    color: var(--wm-text-primary, #191713);
}

.profile-section .tag-chip {
    min-height: 60rpx;
    padding: 0 24rpx;
    border-color: rgba(216, 201, 173, 0.9);
    background: var(--wm-color-bg-soft, #faf6ee);
}

.profile-section .tag-chip--active {
    border-color: var(--wm-color-champagne, #d9be82);
    background: linear-gradient(135deg, #191713 0%, #3a2a16 100%);
    box-shadow: 0 14rpx 28rpx rgba(74, 43, 24, 0.16);
}

.profile-section .tag-chip--active .tag-chip__text {
    color: var(--wm-text-inverse, #fffdf8);
}

.profile-section .status-tip {
    margin-bottom: 0;
    border: 1rpx solid rgba(216, 201, 173, 0.8);
    box-sizing: border-box;
}

.staff-profile-page :deep(.wm-action-area) {
    padding-left: var(--wm-space-page-x, 37rpx);
    padding-right: var(--wm-space-page-x, 37rpx);
}

.staff-profile-page :deep(.base-input__control) {
    min-height: 96rpx;
}

.staff-profile-page :deep(.base-input__native) {
    font-size: 28rpx;
}

@media screen and (max-width: 360px) {
    .profile-summary__body {
        align-items: flex-start;
    }

    .profile-summary__meta-grid {
        grid-template-columns: 1fr;
    }
}
</style>
