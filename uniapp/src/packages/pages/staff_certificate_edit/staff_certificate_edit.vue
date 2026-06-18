<template>
    <page-meta :page-style="$theme.pageStyle" />
    <PageShell scene="staff" tone="workspace" hasSafeBottom>
        <BaseNavbar
            :title="pageTitle"
            variant="solid"
            title-align="center"
            bg-color="#191713"
            text-color="#FFFDF8"
        />

        <view class="page-container">
            <view class="page-section page-section--content wm-page-content">
                <BaseCard
                    v-if="isEdit"
                    variant="panel"
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

                <BaseCard variant="panel" scene="staff" class="form-card wm-form-block">
                    <view class="card-head">
                        <text class="card-head__title">证书图片</text>
                        <text class="field-side-text">{{ form.image ? '已上传' : '选填' }}</text>
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
                                <BaseIcon name="refresh" size="26" color="#ffffff" />
                                <text class="cover-preview__action-text">更换</text>
                            </view>
                            <view class="cover-preview__divider" />
                            <view class="cover-preview__action" @click="removeImage">
                                <BaseIcon name="delete" size="26" color="#ffffff" />
                                <text class="cover-preview__action-text">删除</text>
                            </view>
                        </view>
                    </view>

                    <view v-else class="upload-panel wm-soft-card" @click="chooseImage">
                        <view class="upload-panel__icon-wrap">
                            <BaseIcon
                                name="image"
                                size="50"
                                color="var(--wm-color-primary, #0B0B0B)"
                            />
                        </view>
                        <text class="upload-panel__title">上传证书图片</text>
                    </view>
                </BaseCard>

                <BaseCard variant="panel" scene="staff" class="form-card wm-form-block">
                    <view class="card-head">
                        <text class="card-head__title">证书信息</text>
                    </view>

                    <view class="field-block">
                        <view class="field-label-row">
                            <text class="field-label field-label--required">证书名称</text>
                            <text class="field-side-text">{{ form.name.length }}/100</text>
                        </view>
                        <view class="field-input-shell">
                            <BaseInput
                                v-model="form.name"
                                placeholder="例如：婚礼主持人资格证"
                                :maxlength="100"
                                clearable
                                variant="filled"
                                class="field-base-input"
                            />
                        </view>
                    </view>

                    <view class="field-block">
                        <view class="field-label-row">
                            <text class="field-label">证书类型</text>
                            <text class="field-side-text">{{ form.type.length }}/50</text>
                        </view>
                        <view class="field-input-shell">
                            <BaseInput
                                v-model="form.type"
                                placeholder="例如：职业资格 / 荣誉资质"
                                :maxlength="50"
                                clearable
                                variant="filled"
                                class="field-base-input"
                            />
                        </view>
                    </view>

                    <view class="field-block">
                        <view class="field-label-row">
                            <text class="field-label">证书编号</text>
                            <text class="field-side-text">{{ form.sn.length }}/100</text>
                        </view>
                        <view class="field-input-shell">
                            <BaseInput
                                v-model="form.sn"
                                placeholder="输入证书编号"
                                :maxlength="100"
                                clearable
                                variant="filled"
                                class="field-base-input"
                            />
                        </view>
                    </view>

                    <view class="field-block">
                        <view class="field-label-row">
                            <text class="field-label">发证机构</text>
                            <text class="field-side-text">{{ form.issue_org.length }}/100</text>
                        </view>
                        <view class="field-input-shell">
                            <BaseInput
                                v-model="form.issue_org"
                                placeholder="输入发证机构"
                                :maxlength="100"
                                clearable
                                variant="filled"
                                class="field-base-input"
                            />
                        </view>
                    </view>

                    <view class="date-picker-list">
                        <view
                            :class="[
                                'date-picker-field',
                                { 'date-picker-field--with-clear': form.issue_date }
                            ]"
                        >
                            <view class="date-picker-row" @click="openDatePicker('issue_date')">
                                <text class="date-picker-row__label">发证日期</text>
                                <view class="date-picker-row__value">
                                    <text
                                        :class="[
                                            'date-picker-row__text',
                                            { 'date-picker-row__text--placeholder': !form.issue_date }
                                        ]"
                                    >
                                        {{ form.issue_date || '请选择' }}
                                    </text>
                                    <BaseIcon
                                        name="calendar"
                                        size="28"
                                        color="var(--wm-color-champagne, #D9BE82)"
                                    />
                                </view>
                            </view>
                            <view
                                v-if="form.issue_date"
                                class="date-clear-action"
                                @click="clearDate('issue_date')"
                                >清空发证日期</view
                            >
                        </view>

                        <view
                            :class="[
                                'date-picker-field',
                                { 'date-picker-field--with-clear': form.expire_date }
                            ]"
                        >
                            <view class="date-picker-row" @click="openDatePicker('expire_date')">
                                <text class="date-picker-row__label">有效期至</text>
                                <view class="date-picker-row__value">
                                    <text
                                        :class="[
                                            'date-picker-row__text',
                                            { 'date-picker-row__text--placeholder': !form.expire_date }
                                        ]"
                                    >
                                        {{ form.expire_date || '长期有效' }}
                                    </text>
                                    <BaseIcon
                                        name="calendar"
                                        size="28"
                                        color="var(--wm-color-champagne, #D9BE82)"
                                    />
                                </view>
                            </view>
                            <view
                                v-if="form.expire_date"
                                class="date-clear-action"
                                @click="clearDate('expire_date')"
                                >设为长期有效</view
                            >
                        </view>
                    </view>
                </BaseCard>
            </view>
            <ActionArea sticky safeBottom layout="split" tone="solid">
                <view class="certificate-action-bar">
                    <BaseButton
                        label="取消"
                        variant="light"
                        size="sm"
                        height="78rpx"
                        block
                        @click="handleCancel"
                    />
                    <BaseButton
                        :label="submitButtonText"
                        variant="dark"
                        size="sm"
                        height="78rpx"
                        block
                        :loading="submitting"
                        @click="handleSubmit"
                    />
                </view>
            </ActionArea>

            <BaseDateTimePicker
                v-model="datePickerModel"
                v-model:open="showDatePicker"
                mode="date"
                format="YYYY-MM-DD"
                @confirm="handleDatePickerConfirm"
                @cancel="closeDatePicker"
                @close="closeDatePicker"
            />
        </view>
    </PageShell>
</template>

<script setup lang="ts">
import { computed, reactive, ref } from 'vue'
import { onLoad } from '@dcloudio/uni-app'
import { uploadImage } from '@/api/app'
import ActionArea from '@/components/base/ActionArea.vue'
import BaseButton from '@/components/base/BaseButton.vue'
import BaseCard from '@/components/base/BaseCard.vue'
import BaseDateTimePicker from '@/components/base/BaseDateTimePicker.vue'
import BaseIcon from '@/components/base/BaseIcon.vue'
import BaseInput from '@/components/base/BaseInput.vue'
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
import { confirmModal, showError, showSuccess } from '@/utils/feedback'

type BadgeTone = 'neutral' | 'success' | 'warning' | 'danger' | 'info'

const $theme = useThemeStore()
const submitting = ref(false)
const showDatePicker = ref(false)
const datePickerModel = ref('')
const activeDateField = ref<'issue_date' | 'expire_date'>('issue_date')

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

const openDatePicker = (field: 'issue_date' | 'expire_date') => {
    activeDateField.value = field
    datePickerModel.value = form[field]
    showDatePicker.value = true
}

const closeDatePicker = () => {
    showDatePicker.value = false
}

const handleDatePickerConfirm = (value: string) => {
    form[activeDateField.value] = value || ''
    closeDatePicker()
}

const removeImage = async () => {
    const confirmed = await confirmModal({
        title: '提示',
        content: '确定要删除证书图片吗？'
    })
    if (confirmed) {
        form.image = ''
    }
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
                showError(error, '上传失败')
            } finally {
                uni.hideLoading()
            }
        }
    })
}

const clearDate = (field: 'issue_date' | 'expire_date') => {
    form[field] = ''
}

const handleCancel = async () => {
    const confirmed = await confirmModal({
        title: '提示',
        content: '确定要放弃编辑吗？'
    })
    if (confirmed) {
        uni.navigateBack()
    }
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
        showError('请输入证书名称')
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
            showSuccess('已重新提交审核')
        } else {
            await staffCenterCertificateAdd(payload)
            showSuccess('提交成功')
        }
        setTimeout(() => uni.navigateBack(), 1200)
    } catch (error: any) {
        showError(error, '提交失败')
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
            rgba(11, 11, 11, 0.1) 0,
            rgba(248, 247, 242, 0) 36%
        ),
        linear-gradient(180deg, var(--wm-color-bg-page, #ffffff) 0%, #f8f7f2 100%);
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

.form-card {
    overflow: hidden;
}

.form-card + .form-card {
    margin-top: 18rpx;
}

.status-card__row,
.field-label-row,
.cover-preview__toolbar,
.cover-preview__action,
.upload-panel {
    display: flex;
}

.status-card__row,
.field-label-row {
    align-items: center;
    justify-content: space-between;
}

.status-card__label,
.card-head__title,
.field-label {
    font-weight: 700;
    color: var(--wm-text-primary, #111111);
}

.status-card__label,
.field-label {
    font-size: 28rpx;
}

.status-card__desc,
.field-side-text,
.date-clear-action {
    color: #9a9388;
}

.status-card__desc,
.field-side-text,
.date-clear-action {
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
    background: rgba(11, 11, 11, 0.08);
}

.status-card__reason-label {
    display: block;
    font-size: 22rpx;
    font-weight: 700;
    color: #5a4433;
}

.status-card__reason-text {
    display: block;
    margin-top: 8rpx;
    font-size: 24rpx;
    line-height: 1.6;
    color: #5A4433;
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
    color: var(--wm-color-primary, #0b0b0b);
}

.field-input-shell {
    padding: 0;
    background: transparent;
    border: none;
    overflow: visible;
}

.field-base-input {
    width: 100%;
}

.date-picker-list {
    margin-top: 20rpx;
    display: flex;
    flex-direction: column;
    overflow: hidden;
    border-radius: 28rpx;
    background: #ffffff;
    border: 1rpx solid var(--wm-color-border, #e7e2d6);
}

.date-picker-field {
    position: relative;
}

.date-picker-field + .date-picker-field {
    border-top: 1rpx solid rgba(231, 226, 214, 0.92);
}

.date-picker-field--with-clear {
    padding-bottom: 42rpx;
}

.date-clear-action {
    position: absolute;
    right: 28rpx;
    bottom: 16rpx;
    z-index: 1;
    font-weight: 700;
    color: var(--wm-color-primary, #0b0b0b);
}

.date-picker-row {
    min-height: 94rpx;
    padding: 0 22rpx 0 24rpx;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 18rpx;
    box-sizing: border-box;
}

.date-picker-row__label {
    flex-shrink: 0;
    font-size: 28rpx;
    font-weight: 700;
    line-height: 1.35;
    color: var(--wm-text-primary, #111111);
}

.date-picker-row__value {
    flex: 1;
    min-width: 0;
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: 10rpx;
}

.date-picker-row__text {
    min-width: 0;
    overflow: hidden;
    font-size: 28rpx;
    font-weight: 600;
    line-height: 1.35;
    color: var(--wm-text-primary, #111111);
    text-align: right;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.date-picker-row__text--placeholder {
    color: var(--wm-text-tertiary, #9a9388);
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
    background: #f8f7f2;
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
    background: rgba(11, 11, 11, 0.48);
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
        rgba(248, 247, 242, 0.98) 0%,
        rgba(255, 255, 255, 0.98) 100%
    );
    border: 2rpx dashed rgba(11, 11, 11, 0.24);
}

.upload-panel__icon-wrap {
    width: 100rpx;
    height: 100rpx;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 999rpx;
    background: rgba(11, 11, 11, 0.12);
}

.upload-panel__title {
    font-size: 28rpx;
    font-weight: 700;
    color: #111111;
}

.page-container :deep(.wm-action-area) {
    padding-left: var(--wm-space-page-x, 37rpx);
    padding-right: var(--wm-space-page-x, 37rpx);
}

.certificate-action-bar {
    width: 100%;
    display: flex;
    gap: 16rpx;
}

.certificate-action-bar :deep(.base-button) {
    flex: 1;
    min-width: 0;
}

@media (prefers-reduced-motion: reduce) {
    * {
        animation-duration: 0.01ms !important;
        animation-iteration-count: 1 !important;
        transition-duration: 0.01ms !important;
    }
}
</style>
