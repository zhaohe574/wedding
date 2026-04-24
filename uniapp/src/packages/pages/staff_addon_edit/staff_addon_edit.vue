<template>
    <page-meta :page-style="$theme.pageStyle" />
    <PageShell scene="staff">
        <BaseNavbar :title="pageTitle" />

        <view class="page-container">
            <view class="page-section page-section--content">
                <BaseCard variant="glass" scene="staff" class="form-card wm-form-block">
                    <view class="card-head">
                        <text class="card-head__title">封面与价格</text>
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
                                    <tn-icon name="refresh" size="26" color="#ffffff" />
                                    <text class="cover-preview__action-text">更换</text>
                                </view>
                                <view class="cover-preview__divider" />
                                <view class="cover-preview__action" @click="removeCover">
                                    <tn-icon name="delete" size="26" color="#ffffff" />
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
                                <tn-icon
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
                        <view class="field-input-shell wm-soft-card">
                            <tn-input
                                v-model="form.name"
                                placeholder="例如：晨袍跟拍"
                                :maxlength="50"
                                :border="false"
                                class="field-input"
                            />
                        </view>
                    </view>

                    <view class="field-grid">
                        <view class="field-block field-block--compact">
                            <view class="field-label-row">
                                <text class="field-label field-label--required">售价</text>
                            </view>
                            <view class="field-input-shell wm-soft-card">
                                <tn-input
                                    v-model="form.price"
                                    type="digit"
                                    placeholder="输入价格"
                                    :border="false"
                                    class="field-input"
                                />
                            </view>
                        </view>

                        <view class="field-block field-block--compact">
                            <view class="field-label-row">
                                <text class="field-label">原价</text>
                            </view>
                            <view class="field-input-shell wm-soft-card">
                                <tn-input
                                    v-model="form.original_price"
                                    type="digit"
                                    placeholder="选填"
                                    :border="false"
                                    class="field-input"
                                />
                            </view>
                        </view>
                    </view>
                </BaseCard>

                <BaseCard variant="glass" scene="staff" class="form-card wm-form-block">
                    <view class="card-head">
                        <text class="card-head__title">展示设置</text>
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
                                <tn-input
                                    v-model="form.sort"
                                    type="number"
                                    placeholder="默认 0"
                                    :border="false"
                                    class="setting-input setting-input--right"
                                />
                            </view>
                        </view>

                        <view class="setting-item setting-item--switch">
                            <text class="setting-item__label">上架状态</text>
                            <switch
                                :checked="statusSwitch"
                                :color="$theme.primaryColor"
                                style="transform: scale(0.9)"
                                @change="statusSwitch = $event.detail.value"
                            />
                        </view>
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
                        :style="{ opacity: saving ? 0.66 : 1 }"
                        @click="handleSave"
                    >
                        <tn-icon
                            v-if="saving"
                            name="loading"
                            size="26"
                            color="#ffffff"
                            class="bottom-bar__loading"
                        />
                        <text class="bottom-bar__action-text">{{ saveButtonText }}</text>
                    </view>
                </view>
            </view>
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
import BaseCard from '@/components/base/BaseCard.vue'
import BaseNavbar from '@/components/base/BaseNavbar.vue'
import PageShell from '@/components/base/PageShell.vue'
import { useThemeStore } from '@/stores/theme'
import { ensureStaffCenterAccess } from '@/packages/common/utils/staff-center'

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
                const msg =
                    typeof error === 'string' ? error : error?.msg || error?.message || '上传失败'
                uni.showToast({ title: msg, icon: 'none' })
            } finally {
                uni.hideLoading()
            }
        }
    })
}

const removeCover = () => {
    uni.showModal({
        title: '提示',
        content: '确定删除封面图吗？',
        success: (res) => {
            if (res.confirm) {
                form.image = ''
            }
        }
    })
}

const handleCancel = () => {
    uni.showModal({
        title: '提示',
        content: '确定放弃当前编辑吗？',
        success: (res) => {
            if (res.confirm) {
                uni.navigateBack()
            }
        }
    })
}

const handleSave = async () => {
    const nameText = normalizeTextValue(form.name)
    const priceText = normalizeTextValue(form.price)
    const imageText = normalizeTextValue(form.image)
    const descriptionText = normalizeTextValue(form.description)
    const originalPriceText = normalizeTextValue(form.original_price)

    if (!nameText) {
        uni.showToast({ title: '请输入附加项名称', icon: 'none' })
        return
    }
    if (!priceText) {
        uni.showToast({ title: '请输入附加项价格', icon: 'none' })
        return
    }
    if (!imageText) {
        uni.showToast({ title: '请上传封面图', icon: 'none' })
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

    const addonId = Number(options?.addon_id || options?.id || 0)
    const instance = getCurrentInstance()
    const channel = instance?.proxy?.getOpenerEventChannel?.()
    channel?.on('detail', (data: any) => fillForm(data))

    if (addonId > 0 && !form.addon_id) {
        try {
            await loadDetail(addonId)
        } catch (error: any) {
            const msg =
                typeof error === 'string'
                    ? error
                    : error?.msg || error?.message || '加载附加项详情失败'
            uni.showToast({ title: msg, icon: 'none' })
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
    border-radius: 28rpx;
    background: #ffffff;
    border: 1rpx solid var(--wm-color-border, #e7e2d6);
    overflow: hidden;
}

.field-input-shell {
    min-height: 94rpx;
    padding: 0 24rpx;
    display: flex;
    align-items: center;
}

.field-input,
.setting-input {
    width: 100%;
}

.textarea-shell {
    padding: 22rpx 24rpx;
}

.field-input :deep(.tn-input),
.setting-input :deep(.tn-input) {
    background: transparent !important;
}

.field-input :deep(.input-placeholder),
.setting-input :deep(.input-placeholder) {
    color: #9a9388 !important;
}

.field-input :deep(.input-text),
.setting-input :deep(.input-text) {
    font-size: 28rpx !important;
    color: #111111 !important;
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
    justify-content: flex-end;
}

.setting-item__input--sm {
    max-width: 200rpx;
}

.setting-input--right :deep(.tn-input) {
    justify-content: flex-end !important;
}

.setting-input--right :deep(.input-text),
.setting-input--right :deep(.input-placeholder) {
    text-align: right !important;
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
}

.bottom-bar__inner {
    display: flex;
    gap: 12rpx;
}

.bottom-bar__action {
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
    flex: 1;
    background: rgba(255, 255, 255, 0.82);
    border: 1rpx solid var(--wm-color-border, #e7e2d6);
}

.bottom-bar__action--primary {
    flex: 1.35;
    background: linear-gradient(135deg, var(--wm-color-primary, #0b0b0b) 0%, #9f7a2e 100%);
    box-shadow: 0 14rpx 28rpx rgba(11, 11, 11, 0.18);
}

.bottom-bar__action-text {
    font-size: 30rpx;
    font-weight: 700;
    line-height: 1;
    color: #ffffff;
}

.bottom-bar__action-text--ghost {
    color: var(--wm-text-primary, #111111);
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
