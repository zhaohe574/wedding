<template>
    <page-meta :page-style="$theme.pageStyle" />
    <PageShell scene="staff">
        <BaseNavbar :title="pageTitle" />

        <view class="page-container">
            <view class="page-section page-section--content">
                <BaseCard
                    v-if="isEdit"
                    variant="glass"
                    scene="staff"
                    class="form-card wm-form-block"
                >
                    <view class="status-card">
                        <view class="status-card__row">
                            <text class="status-card__label">当前状态</text>
                            <StatusBadge :tone="statusTone" size="sm">
                                {{ statusText }}
                            </StatusBadge>
                        </view>
                        <text class="status-card__desc">
                            {{ isEdit ? '修改后会重新进入待审核状态' : '提交后将进入待审核状态' }}
                        </text>
                        <view v-if="form.reject_reason" class="status-card__reason">
                            <text class="status-card__reason-label">驳回原因</text>
                            <text class="status-card__reason-text">{{ form.reject_reason }}</text>
                        </view>
                    </view>
                </BaseCard>

                <BaseCard variant="glass" scene="staff" class="form-card wm-form-block">
                    <view class="card-head">
                        <text class="card-head__title">证书图片</text>
                    </view>

                    <view v-if="form.image" class="cover-preview">
                        <image
                            :src="form.image"
                            class="cover-preview__image"
                            mode="aspectFill"
                            @click="previewImage"
                        />
                        <view class="cover-preview__toolbar">
                            <view class="cover-preview__action" @click="chooseImage">
                                <tn-icon name="refresh" size="26" color="#ffffff" />
                                <text class="cover-preview__action-text">更换</text>
                            </view>
                            <view class="cover-preview__divider" />
                            <view class="cover-preview__action" @click="removeImage">
                                <tn-icon name="delete" size="26" color="#ffffff" />
                                <text class="cover-preview__action-text">删除</text>
                            </view>
                        </view>
                    </view>

                    <view v-else class="upload-panel wm-soft-card" @click="chooseImage">
                        <view class="upload-panel__icon-wrap">
                            <tn-icon
                                name="image"
                                size="50"
                                color="var(--wm-color-primary, #E85A4F)"
                            />
                        </view>
                        <text class="upload-panel__title">上传证书图片</text>
                        <text class="upload-panel__meta">建议上传清晰完整的证书照片</text>
                    </view>
                </BaseCard>

                <BaseCard variant="glass" scene="staff" class="form-card wm-form-block">
                    <view class="card-head">
                        <text class="card-head__title">证书信息</text>
                    </view>

                    <view class="field-block">
                        <view class="field-label-row">
                            <text class="field-label field-label--required">证书名称</text>
                            <text class="field-side-text">{{ form.name.length }}/100</text>
                        </view>
                        <view class="field-input-shell wm-soft-card">
                            <tn-input
                                v-model="form.name"
                                placeholder="例如：婚礼主持人资格证"
                                :maxlength="100"
                                :border="false"
                                class="field-input"
                            />
                        </view>
                    </view>

                    <view class="field-block">
                        <view class="field-label-row">
                            <text class="field-label">证书类型</text>
                            <text class="field-side-text">{{ form.type.length }}/50</text>
                        </view>
                        <view class="field-input-shell wm-soft-card">
                            <tn-input
                                v-model="form.type"
                                placeholder="例如：职业资格 / 荣誉资质"
                                :maxlength="50"
                                :border="false"
                                class="field-input"
                            />
                        </view>
                    </view>

                    <view class="field-block">
                        <view class="field-label-row">
                            <text class="field-label">证书编号</text>
                            <text class="field-side-text">{{ form.sn.length }}/100</text>
                        </view>
                        <view class="field-input-shell wm-soft-card">
                            <tn-input
                                v-model="form.sn"
                                placeholder="输入证书编号"
                                :maxlength="100"
                                :border="false"
                                class="field-input"
                            />
                        </view>
                    </view>

                    <view class="field-block">
                        <view class="field-label-row">
                            <text class="field-label">发证机构</text>
                            <text class="field-side-text">{{ form.issue_org.length }}/100</text>
                        </view>
                        <view class="field-input-shell wm-soft-card">
                            <tn-input
                                v-model="form.issue_org"
                                placeholder="输入发证机构"
                                :maxlength="100"
                                :border="false"
                                class="field-input"
                            />
                        </view>
                    </view>

                    <view class="setting-list">
                        <picker
                            mode="date"
                            :value="form.issue_date"
                            @change="handleDateChange('issue_date', $event)"
                        >
                            <view class="setting-item">
                                <text class="setting-item__label">发证日期</text>
                                <view class="setting-item__value">
                                    <text
                                        :class="[
                                            'setting-item__value-text',
                                            {
                                                'setting-item__value-text--placeholder':
                                                    !form.issue_date
                                            }
                                        ]"
                                    >
                                        {{ form.issue_date || '请选择' }}
                                    </text>
                                    <tn-icon name="arrow-right" size="24" color="#B4ACA8" />
                                </view>
                            </view>
                        </picker>

                        <view
                            v-if="form.issue_date"
                            class="clear-row"
                            @click="clearDate('issue_date')"
                            >清空发证日期</view
                        >

                        <picker
                            mode="date"
                            :value="form.expire_date"
                            @change="handleDateChange('expire_date', $event)"
                        >
                            <view class="setting-item">
                                <text class="setting-item__label">有效期至</text>
                                <view class="setting-item__value">
                                    <text
                                        :class="[
                                            'setting-item__value-text',
                                            {
                                                'setting-item__value-text--placeholder':
                                                    !form.expire_date
                                            }
                                        ]"
                                    >
                                        {{ form.expire_date || '长期有效' }}
                                    </text>
                                    <tn-icon name="arrow-right" size="24" color="#B4ACA8" />
                                </view>
                            </view>
                        </picker>

                        <view
                            v-if="form.expire_date"
                            class="clear-row"
                            @click="clearDate('expire_date')"
                            >设为长期有效</view
                        >
                    </view>
                </BaseCard>
            </view>

            <view class="bottom-bar">
                <view class="bottom-bar__inner">
                    <view
                        class="bottom-bar__action bottom-bar__action--ghost"
                        @click="handleCancel"
                    >
                        <text class="bottom-bar__action-text bottom-bar__action-text--ghost"
                            >取消</text
                        >
                    </view>
                    <view
                        class="bottom-bar__action bottom-bar__action--primary"
                        :style="{ opacity: submitting ? 0.66 : 1 }"
                        @click="handleSubmit"
                    >
                        <tn-icon
                            v-if="submitting"
                            name="loading"
                            size="26"
                            color="#ffffff"
                            class="bottom-bar__loading"
                        />
                        <text class="bottom-bar__action-text">{{ submitButtonText }}</text>
                    </view>
                </view>
            </view>
        </view>
    </PageShell>
</template>

<script setup lang="ts">
import { computed, reactive, ref } from 'vue'
import { onLoad } from '@dcloudio/uni-app'
import { uploadImage } from '@/api/app'
import BaseCard from '@/components/base/BaseCard.vue'
import BaseNavbar from '@/components/base/BaseNavbar.vue'
import PageShell from '@/components/base/PageShell.vue'
import StatusBadge from '@/components/base/StatusBadge.vue'
import {
    staffCenterCertificateAdd,
    staffCenterCertificateDetail,
    staffCenterCertificateEdit
} from '@/api/staffCenter'
import { ensureStaffCenterAccess } from '@/packages/common/utils/staff-center'
import { useThemeStore } from '@/stores/theme'

type BadgeTone = 'neutral' | 'success' | 'warning' | 'danger' | 'info'

const $theme = useThemeStore()
const submitting = ref(false)

const form = reactive({
    id: 0,
    name: '',
    type: '',
    sn: '',
    image: '',
    issue_org: '',
    issue_date: '',
    expire_date: '',
    verify_status: 0,
    verify_status_desc: '',
    reject_reason: ''
})

const isEdit = computed(() => form.id > 0)
const pageTitle = computed(() => (isEdit.value ? '编辑证书' : '新增证书'))
const submitButtonText = computed(() => {
    if (submitting.value) {
        return isEdit.value ? '提交中...' : '提交中...'
    }
    return isEdit.value ? '修改后重提' : '提交审核'
})
const statusText = computed(() => form.verify_status_desc || '待审核')
const statusTone = computed<BadgeTone>(() => {
    const map: Record<number, BadgeTone> = { 0: 'warning', 1: 'success', 2: 'danger' }
    return map[Number(form.verify_status)] || 'neutral'
})

const previewImage = () => {
    if (!form.image) return
    uni.previewImage({ urls: [form.image], current: form.image })
}

const removeImage = () => {
    uni.showModal({
        title: '提示',
        content: '确定要删除证书图片吗？',
        success: (res) => {
            if (res.confirm) {
                form.image = ''
            }
        }
    })
}

const chooseImage = () => {
    uni.chooseImage({
        count: 1,
        sizeType: ['compressed'],
        sourceType: ['album', 'camera'],
        success: async (res) => {
            uni.showLoading({ title: '上传中...' })
            try {
                const uploadRes: any = await uploadImage(res.tempFilePaths[0])
                if (uploadRes?.uri) {
                    form.image = uploadRes.uri
                }
            } catch (error: any) {
                uni.showToast({ title: error?.message || '上传失败', icon: 'none' })
            } finally {
                uni.hideLoading()
            }
        }
    })
}

const handleDateChange = (field: 'issue_date' | 'expire_date', event: any) => {
    form[field] = event.detail.value || ''
}

const clearDate = (field: 'issue_date' | 'expire_date') => {
    form[field] = ''
}

const handleCancel = () => {
    uni.showModal({
        title: '提示',
        content: '确定要放弃编辑吗？',
        success: (res) => {
            if (res.confirm) {
                uni.navigateBack()
            }
        }
    })
}

const loadDetail = async (id: number) => {
    const data: any = await staffCenterCertificateDetail({ id })
    form.id = Number(data?.id || 0)
    form.name = data?.name || ''
    form.type = data?.type || ''
    form.sn = data?.sn || ''
    form.image = data?.image || ''
    form.issue_org = data?.issue_org || ''
    form.issue_date = data?.issue_date || ''
    form.expire_date = data?.expire_date || ''
    form.verify_status = Number(data?.verify_status || 0)
    form.verify_status_desc = data?.verify_status_desc || ''
    form.reject_reason = data?.reject_reason || ''
}

const normalizeOptionalDate = (value: string) => {
    const trimmedValue = value.trim()
    return trimmedValue === '' ? '' : trimmedValue
}

const handleSubmit = async () => {
    if (!form.name.trim()) {
        uni.showToast({ title: '请输入证书名称', icon: 'none' })
        return
    }

    const payload: any = {
        name: form.name.trim(),
        type: form.type.trim(),
        sn: form.sn.trim(),
        image: form.image,
        issue_org: form.issue_org.trim(),
        issue_date: normalizeOptionalDate(form.issue_date),
        expire_date: normalizeOptionalDate(form.expire_date)
    }

    submitting.value = true
    try {
        if (isEdit.value) {
            await staffCenterCertificateEdit({ ...payload, id: form.id })
            uni.showToast({ title: '已重新提交审核', icon: 'success' })
        } else {
            await staffCenterCertificateAdd(payload)
            uni.showToast({ title: '提交成功', icon: 'success' })
        }
        setTimeout(() => uni.navigateBack(), 1200)
    } catch (error: any) {
        const msg = typeof error === 'string' ? error : error?.msg || error?.message || '提交失败'
        uni.showToast({ title: msg, icon: 'none' })
    } finally {
        submitting.value = false
    }
}

onLoad(async (options: any) => {
    if (!(await ensureStaffCenterAccess())) return
    const id = Number(options?.id || 0)
    if (id > 0) {
        await loadDetail(id)
    }
})
</script>

<style lang="scss" scoped>
.page-container {
    min-height: 100vh;
    padding-top: 20rpx;
    padding-bottom: calc(180rpx + env(safe-area-inset-bottom));
    box-sizing: border-box;
    background: radial-gradient(
            circle at top left,
            rgba(232, 90, 79, 0.1) 0,
            rgba(252, 251, 249, 0) 36%
        ),
        linear-gradient(180deg, var(--wm-color-bg-page, #fcfbf9) 0%, #f7f1ed 100%);
}

.page-section {
    display: flex;
    flex-direction: column;
    gap: 18rpx;
    padding: 0 var(--wm-space-page-x, 37rpx);
    box-sizing: border-box;
}

.page-section--content {
    padding-top: 20rpx;
}

.status-card__row,
.field-label-row,
.setting-item,
.setting-item__value,
.bottom-bar__inner,
.bottom-bar__action,
.cover-preview__toolbar,
.cover-preview__action,
.upload-panel {
    display: flex;
}

.status-card__row,
.field-label-row,
.setting-item,
.setting-item__value,
.bottom-bar__inner {
    align-items: center;
    justify-content: space-between;
}

.status-card__label,
.card-head__title,
.field-label,
.setting-item__label {
    font-weight: 700;
    color: var(--wm-text-primary, #1e2432);
}

.status-card__label,
.field-label,
.setting-item__label,
.setting-item__value-text,
.field-input :deep(.input-text) {
    font-size: 28rpx !important;
}

.status-card__desc,
.field-side-text,
.clear-row,
.setting-item__value-text--placeholder,
.field-input :deep(.input-placeholder) {
    color: #b4aca8 !important;
}

.status-card__desc,
.field-side-text,
.clear-row {
    font-size: 22rpx;
}

.status-card__desc {
    display: block;
    margin-top: 14rpx;
}

.status-card__reason {
    margin-top: 18rpx;
    padding: 18rpx 20rpx;
    border-radius: 24rpx;
    background: rgba(232, 90, 79, 0.08);
}

.status-card__reason-label {
    display: block;
    font-size: 22rpx;
    font-weight: 700;
    color: #d4473c;
}

.status-card__reason-text {
    display: block;
    margin-top: 8rpx;
    font-size: 24rpx;
    line-height: 1.6;
    color: #7a4c46;
}

.card-head {
    margin-bottom: 22rpx;
}

.card-head__title {
    font-size: 30rpx;
}

.field-block + .field-block {
    margin-top: 22rpx;
}

.field-label-row {
    gap: 16rpx;
    margin-bottom: 16rpx;
}

.field-label--required::before {
    content: '*';
    margin-right: 6rpx;
    color: var(--wm-color-primary, #e85a4f);
}

.field-input-shell {
    min-height: 94rpx;
    padding: 0 24rpx;
    display: flex;
    align-items: center;
    border-radius: 28rpx;
    background: #fcfbf9;
    border: 1rpx solid var(--wm-color-border, #efe6e1);
}

.field-input {
    width: 100%;
}

.field-input :deep(.tn-input) {
    background: transparent !important;
}

.setting-list {
    margin-top: 20rpx;
    border-radius: 28rpx;
    overflow: hidden;
    background: #fcfbf9;
    border: 1rpx solid var(--wm-color-border, #efe6e1);
}

.setting-item {
    min-height: 94rpx;
    padding: 0 24rpx;
}

.setting-item + .setting-item {
    border-top: 1rpx solid #efe6e1;
}

.clear-row {
    padding: 0 24rpx 18rpx;
}

.cover-preview,
.upload-panel {
    height: 392rpx;
}

.cover-preview {
    position: relative;
    width: 100%;
    overflow: hidden;
    border-radius: 32rpx;
    background: #f7f1ed;
}

.cover-preview__image {
    width: 100%;
    height: 100%;
    display: block;
}

.cover-preview__toolbar {
    position: absolute;
    left: 16rpx;
    right: 16rpx;
    bottom: 16rpx;
    min-height: 68rpx;
    border-radius: 999rpx;
    background: rgba(19, 24, 35, 0.48);
    backdrop-filter: blur(14rpx);
    -webkit-backdrop-filter: blur(14rpx);
}

.cover-preview__action {
    flex: 1;
    height: 100%;
    justify-content: center;
    gap: 8rpx;
}

.cover-preview__divider {
    width: 1rpx;
    background: rgba(255, 255, 255, 0.18);
}

.cover-preview__action-text {
    font-size: 24rpx;
    color: #fff;
}

.upload-panel {
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 14rpx;
    border-radius: 32rpx;
    background: linear-gradient(
        180deg,
        rgba(255, 245, 241, 0.98) 0%,
        rgba(255, 250, 246, 0.98) 100%
    );
    border: 2rpx dashed rgba(232, 90, 79, 0.24);
}

.upload-panel__icon-wrap {
    width: 100rpx;
    height: 100rpx;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 999rpx;
    background: rgba(232, 90, 79, 0.12);
}

.upload-panel__title {
    font-size: 28rpx;
    font-weight: 700;
    color: #1e2432;
}

.upload-panel__meta {
    font-size: 22rpx;
    color: #948f8b;
}

.bottom-bar {
    position: fixed;
    left: 0;
    right: 0;
    bottom: 0;
    z-index: 40;
    padding: 12rpx var(--wm-space-page-x, 37rpx) calc(20rpx + env(safe-area-inset-bottom));
    background: rgba(252, 251, 249, 0.88);
    border-top: 1rpx solid rgba(239, 230, 225, 0.9);
    backdrop-filter: blur(24rpx);
    -webkit-backdrop-filter: blur(24rpx);
    box-sizing: border-box;
}

.bottom-bar__inner {
    display: flex;
    gap: 12rpx;
}

.bottom-bar__action {
    flex: 1;
    min-height: 88rpx;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10rpx;
    border-radius: 36rpx;
    transition: all var(--wm-motion-base, 220ms) ease;

    &:active {
        transform: translateY(2rpx);
        opacity: 0.92;
    }
}

.bottom-bar__action--ghost {
    background: rgba(255, 255, 255, 0.82);
    border: 1rpx solid var(--wm-color-border, #efe6e1);
}

.bottom-bar__action--primary {
    background: linear-gradient(135deg, var(--wm-color-primary, #e85a4f) 0%, #d96a60 100%);
    box-shadow: 0 14rpx 28rpx rgba(232, 90, 79, 0.18);
}

.bottom-bar__action-text {
    font-size: 30rpx;
    font-weight: 700;
    line-height: 1;
    color: #fff;
}

.bottom-bar__action-text--ghost {
    color: var(--wm-text-primary, #1e2432);
}

.bottom-bar__loading {
    animation: rotate 1s linear infinite;
}

@keyframes rotate {
    from {
        transform: rotate(0deg);
    }

    to {
        transform: rotate(360deg);
    }
}

@media (prefers-reduced-motion: reduce) {
    * {
        animation-duration: 0.01ms !important;
        animation-iteration-count: 1 !important;
        transition-duration: 0.01ms !important;
    }
}
</style>
