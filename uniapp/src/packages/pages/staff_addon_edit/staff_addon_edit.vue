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
                <BaseCard variant="panel" scene="staff" class="form-card wm-form-block">
                    <view class="card-head">
                        <text class="card-head__title">基础信息</text>
                    </view>

                    <view class="field-block">
                        <view class="field-label-row">
                            <text class="field-label field-label--required">封面图</text>
                            <text class="field-side-text">{{
                                form.image ? '已上传' : '必填'
                            }}</text>
                        </view>

                        <view v-if="form.image" class="cover-preview">
                            <image
                                :src="form.image"
                                class="cover-preview__image"
                                mode="aspectFill"
                                @click="previewCover"
                            />
                            <view class="cover-preview__toolbar">
                                <view class="cover-preview__action" @click="chooseCover">
                                    <BaseIcon name="refresh" size="26" color="#ffffff" />
                                    <text class="cover-preview__action-text">更换</text>
                                </view>
                                <view class="cover-preview__divider" />
                                <view class="cover-preview__action" @click="removeCover">
                                    <BaseIcon name="delete" size="26" color="#ffffff" />
                                    <text class="cover-preview__action-text">删除</text>
                                </view>
                            </view>
                        </view>

                        <view
                            v-else
                            class="upload-panel upload-panel--cover wm-soft-card"
                            @click="chooseCover"
                        >
                            <view class="upload-panel__icon-wrap">
                                <BaseIcon
                                    name="image"
                                    size="50"
                                    color="var(--wm-color-primary, #0B0B0B)"
                                />
                            </view>
                            <text class="upload-panel__title">上传封面</text>
                        </view>
                    </view>

                    <view class="field-block">
                        <view class="field-label-row">
                            <text class="field-label field-label--required">附加项名称</text>
                            <text class="field-side-text">{{ form.name.length }}/50</text>
                        </view>
                        <view class="field-input-shell">
                            <BaseInput
                                v-model="form.name"
                                placeholder="附加项名称"
                                :maxlength="50"
                                clearable
                                variant="filled"
                                class="field-base-input"
                            />
                        </view>
                    </view>

                    <view class="field-grid">
                        <view class="field-block field-block--compact">
                            <view class="field-label-row">
                                <text class="field-label field-label--required">售价</text>
                            </view>
                            <view class="field-input-shell">
                                <BaseInput
                                    v-model="form.price"
                                    type="digit"
                                    placeholder="价格"
                                    variant="filled"
                                    class="field-base-input"
                                />
                            </view>
                        </view>

                        <view class="field-block field-block--compact">
                            <view class="field-label-row">
                                <text class="field-label">原价</text>
                            </view>
                            <view class="field-input-shell">
                                <BaseInput
                                    v-model="form.original_price"
                                    type="digit"
                                    placeholder="选填"
                                    variant="filled"
                                    class="field-base-input"
                                />
                            </view>
                        </view>
                    </view>
                </BaseCard>

                <BaseCard variant="panel" scene="staff" class="form-card wm-form-block">
                    <view class="card-head">
                        <text class="card-head__title">设置</text>
                    </view>

                    <view class="field-block">
                        <view class="field-label-row">
                            <text class="field-label">附加项说明</text>
                            <text class="field-side-text">{{ form.description.length }}/500</text>
                        </view>
                        <view class="textarea-shell wm-soft-card">
                            <textarea
                                v-model="form.description"
                                class="field-textarea"
                                placeholder="输入附加项说明"
                                :maxlength="500"
                                :auto-height="true"
                                :show-confirm-bar="false"
                            />
                        </view>
                    </view>

                    <view class="setting-list">
                        <view class="setting-item">
                            <text class="setting-item__label">排序</text>
                            <view class="setting-item__input setting-item__input--sm">
                                <view class="setting-inline-control">
                                    <input
                                        class="setting-inline-input"
                                        placeholder-class="setting-inline-placeholder"
                                        type="number"
                                        placeholder="0"
                                        :value="form.sort"
                                        @input="handleSortInput"
                                    />
                                </view>
                            </view>
                        </view>

                        <view class="setting-item setting-item--switch">
                            <text class="setting-item__label">上架状态</text>
                            <switch
                                :checked="statusSwitch"
                                :color="$theme.primaryColor"
                                style="transform: scale(0.9)"
                                @change="handleStatusSwitchChange"
                            />
                        </view>
                    </view>
                </BaseCard>
            </view>

            <ActionArea sticky safeBottom layout="split" tone="solid">
                <view class="addon-action-bar">
                    <BaseButton
                        label="取消"
                        variant="light"
                        size="sm"
                        height="78rpx"
                        block
                        @click="handleCancel"
                    />
                    <BaseButton
                        :label="saveButtonText"
                        variant="dark"
                        size="sm"
                        height="78rpx"
                        block
                        :loading="saving"
                        @click="handleSave"
                    />
                </view>
            </ActionArea>
        </view>
    </PageShell>
</template>

<script setup lang="ts">
import { computed, getCurrentInstance, reactive, ref } from 'vue'
import { onLoad } from '@dcloudio/uni-app'
import { uploadImage } from '@/api/app'
import {
    staffCenterAddonAdd,
    staffCenterAddonDetail,
    staffCenterAddonUpdate
} from '@/api/staffCenter'
import ActionArea from '@/components/base/ActionArea.vue'
import BaseButton from '@/components/base/BaseButton.vue'
import BaseCard from '@/components/base/BaseCard.vue'
import BaseIcon from '@/components/base/BaseIcon.vue'
import BaseInput from '@/components/base/BaseInput.vue'
import BaseNavbar from '@/components/base/BaseNavbar.vue'
import PageShell from '@/components/base/PageShell.vue'
import { useThemeStore } from '@/stores/theme'
import { ensureStaffCenterAccess } from '@/packages/common/utils/staff-center'
import { confirmModal, showError, showSuccess } from '@/utils/feedback'

type OpenerEventChannelProxy = {
    getOpenerEventChannel?: () => {
        on?: (eventName: string, callback: (data: any) => void) => void
    }
}

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

const pageTitle = computed(() => (form.addon_id ? '编辑附加项' : '新增附加项'))
const saveButtonText = computed(() => {
    if (saving.value) {
        return form.addon_id ? '保存中...' : '提交中...'
    }
    return form.addon_id ? '保存修改' : '提交附加项'
})

const statusSwitch = computed({
    get: () => form.is_show === 1,
    set: (value: boolean) => {
        form.is_show = value ? 1 : 0
    }
})

const normalizeTextValue = (value: unknown) => String(value ?? '').trim()

const getInputValue = (event: any) => String(event?.detail?.value ?? '')

const handleSortInput = (event: any) => {
    form.sort = getInputValue(event)
}

const handleStatusSwitchChange = (event: any) => {
    statusSwitch.value = Boolean(event?.detail?.value)
}

const fillForm = (data: any) => {
    form.addon_id = Number(data.addon_id || data.id || 0)
    form.name = data.name || ''
    form.price = data.price !== undefined && data.price !== null ? String(data.price) : ''
    form.original_price =
        data.original_price !== undefined && data.original_price !== null
            ? String(data.original_price)
            : ''
    form.image = data.image || ''
    form.description = data.description || ''
    form.sort = data.sort !== undefined && data.sort !== null ? String(data.sort) : '0'
    form.is_show = Number(data.is_show ?? 1)
}

const loadDetail = async (addonId: number) => {
    const data = await staffCenterAddonDetail({ addon_id: addonId })
    fillForm(data || {})
}

const previewCover = () => {
    if (!form.image) {
        return
    }

    uni.previewImage({
        urls: [form.image],
        current: form.image
    })
}

const chooseCover = () => {
    uni.chooseImage({
        count: 1,
        sizeType: ['compressed'],
        sourceType: ['album', 'camera'],
        success: async (res) => {
            uni.showLoading({ title: '上传中...' })
            try {
                const uploadRes = await uploadImage(res.tempFilePaths[0])
                form.image = uploadRes?.uri || uploadRes?.url || ''
            } catch (error: any) {
                showError(error, '上传失败')
            } finally {
                uni.hideLoading()
            }
        }
    })
}

const removeCover = async () => {
    const confirmed = await confirmModal({
        title: '提示',
        content: '确定删除封面图吗？'
    })
    if (confirmed) {
        form.image = ''
    }
}

const handleCancel = async () => {
    const confirmed = await confirmModal({
        title: '提示',
        content: '确定放弃当前编辑吗？'
    })
    if (confirmed) {
        uni.navigateBack()
    }
}

const handleSave = async () => {
    const nameText = normalizeTextValue(form.name)
    const priceText = normalizeTextValue(form.price)
    const imageText = normalizeTextValue(form.image)
    const descriptionText = normalizeTextValue(form.description)
    const originalPriceText = normalizeTextValue(form.original_price)

    if (!nameText) {
        showError('请输入附加项名称')
        return
    }
    if (!priceText) {
        showError('请输入附加项价格')
        return
    }
    if (!imageText) {
        showError('请上传封面图')
        return
    }

    const payload = {
        name: nameText,
        price: Number(priceText || 0),
        original_price: originalPriceText === '' ? 0 : Number(originalPriceText),
        image: imageText,
        description: descriptionText,
        sort: Number(normalizeTextValue(form.sort) || 0),
        is_show: form.is_show
    }

    saving.value = true
    try {
        if (form.addon_id) {
            await staffCenterAddonUpdate({
                addon_id: form.addon_id,
                ...payload
            })
        } else {
            await staffCenterAddonAdd(payload)
        }
        showSuccess('保存成功')
        setTimeout(() => uni.navigateBack(), 1200)
    } catch (e: any) {
        showError(e, '保存失败')
    } finally {
        saving.value = false
    }
}

onLoad(async (options: any) => {
    if (!(await ensureStaffCenterAccess())) return

    const addonId = Number(options?.addon_id || options?.id || 0)
    const instance = getCurrentInstance()
    const channel = (
        instance?.proxy as OpenerEventChannelProxy | undefined
    )?.getOpenerEventChannel?.()
    channel?.on?.('detail', (data: any) => fillForm(data))

    if (addonId > 0 && !form.addon_id) {
        try {
            await loadDetail(addonId)
        } catch (error: any) {
            showError(error, '加载附加项详情失败')
        }
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

.form-card {
    overflow: hidden;
}

.page-section {
    display: flex;
    flex-direction: column;
    gap: 16rpx;
    padding: 0 var(--wm-space-page-x, 37rpx);
    box-sizing: border-box;
}

.page-section--content {
    padding-top: 20rpx;
}

.form-card + .form-card {
    margin-top: 18rpx;
}

.card-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16rpx;
    margin-bottom: 22rpx;
}

.card-head__title {
    font-size: 30rpx;
    font-weight: 700;
    line-height: 1.3;
    color: var(--wm-text-primary, #111111);
}

.field-block + .field-block {
    margin-top: 22rpx;
}

.field-block--compact {
    margin-top: 20rpx;
}

.field-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 16rpx;
}

.field-label-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16rpx;
    margin-bottom: 16rpx;
}

.field-label {
    font-size: 28rpx;
    font-weight: 700;
    line-height: 1.3;
    color: var(--wm-text-primary, #111111);
}

.field-label--required::before {
    content: '*';
    margin-right: 6rpx;
    color: var(--wm-color-primary, #0b0b0b);
}

.field-side-text {
    flex-shrink: 0;
    font-size: 22rpx;
    font-weight: 600;
    line-height: 1;
    color: var(--wm-text-tertiary, #9a9388);
}

.field-input-shell,
.textarea-shell {
    overflow: hidden;
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

.textarea-shell {
    border-radius: 28rpx;
    background: #ffffff;
    border: 1rpx solid var(--wm-color-border, #e7e2d6);
    overflow: hidden;
    padding: 22rpx 24rpx;
}

.field-textarea {
    width: 100%;
    min-height: 220rpx;
    font-size: 28rpx;
    line-height: 1.65;
    color: var(--wm-text-primary, #111111);
}

.cover-preview,
.upload-panel--cover {
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
    display: flex;
    align-items: center;
    border-radius: 999rpx;
    background: rgba(11, 11, 11, 0.48);
    backdrop-filter: blur(14rpx);
    -webkit-backdrop-filter: blur(14rpx);
}

.cover-preview__action {
    flex: 1;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8rpx;
}

.cover-preview__action-text {
    font-size: 24rpx;
    font-weight: 600;
    color: #ffffff;
}

.cover-preview__divider {
    width: 1rpx;
    height: 30rpx;
    background: rgba(255, 255, 255, 0.24);
}

.upload-panel {
    width: 100%;
    border-radius: 32rpx;
    border: 1rpx dashed rgba(216, 194, 138, 0.88);
    background: linear-gradient(
        180deg,
        rgba(248, 247, 242, 0.9) 0%,
        rgba(255, 255, 255, 0.72) 100%
    );
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 14rpx;
}

.upload-panel__icon-wrap {
    width: 108rpx;
    height: 108rpx;
    border-radius: 999rpx;
    display: flex;
    align-items: center;
    justify-content: center;
    background: rgba(255, 255, 255, 0.82);
    border: 1rpx solid rgba(216, 194, 138, 0.72);
}

.upload-panel__title {
    font-size: 28rpx;
    font-weight: 700;
    line-height: 1.2;
    color: var(--wm-text-primary, #111111);
}

.setting-list {
    border-radius: 30rpx;
    background: #ffffff;
    border: 1rpx solid var(--wm-color-border, #e7e2d6);
    overflow: hidden;
}

.setting-item {
    min-height: 98rpx;
    padding: 0 22rpx;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16rpx;
    border-bottom: 1rpx solid rgba(231, 226, 214, 0.9);
    box-sizing: border-box;

    &:last-child {
        border-bottom: none;
    }
}

.setting-item__label {
    flex-shrink: 0;
    font-size: 28rpx;
    font-weight: 600;
    line-height: 1.3;
    color: var(--wm-text-primary, #111111);
}

.setting-item__input {
    flex: 1;
    min-width: 0;
    display: flex;
    align-items: center;
    justify-content: flex-end;
}

.setting-item__input--sm {
    max-width: 154rpx;
}

.setting-inline-control {
    width: 100%;
    height: 46rpx;
    min-height: 46rpx;
    padding: 0 16rpx;
    display: flex;
    align-items: center;
    justify-content: center;
    background: rgba(255, 253, 248, 0.82);
    border: 1rpx solid var(--wm-color-border, #e7e2d6);
    border-radius: 999rpx;
    box-sizing: border-box;
}

.setting-inline-input {
    width: 100%;
    height: 44rpx;
    min-height: 44rpx;
    font-size: 23rpx;
    font-weight: 700;
    line-height: 44rpx;
    text-align: center;
    color: var(--wm-text-primary, #111111);
}

.setting-inline-placeholder {
    color: var(--wm-text-tertiary, #9a9388);
    text-align: center;
}

.page-container :deep(.wm-action-area) {
    padding-left: var(--wm-space-page-x, 37rpx);
    padding-right: var(--wm-space-page-x, 37rpx);
}

.addon-action-bar {
    width: 100%;
    display: flex;
    gap: 16rpx;
}

.addon-action-bar :deep(.base-button) {
    flex: 1;
    min-width: 0;
}

@media (max-width: 720rpx) {
    .field-grid {
        grid-template-columns: 1fr;
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
