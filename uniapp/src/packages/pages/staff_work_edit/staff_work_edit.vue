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
                        <text class="card-head__title">素材</text>
                    </view>

                    <view class="field-block">
                        <view class="field-label-row">
                            <view class="field-label-group">
                                <text class="field-label field-label--required">封面图</text>
                            </view>
                            <text class="field-side-text">{{
                                form.cover ? '已上传' : '必填'
                            }}</text>
                        </view>

                        <view v-if="form.cover" class="cover-preview">
                            <image
                                :src="form.cover"
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
                            <view class="field-label-group">
                                <text class="field-label">作品图片</text>
                            </view>
                            <text class="field-side-text">{{ form.images.length }}/9</text>
                        </view>

                        <view class="images-grid">
                            <view
                                v-for="(img, idx) in form.images"
                                :key="idx"
                                class="image-card"
                                @longpress="handleImageLongPress(idx)"
                            >
                                <image
                                    :src="img"
                                    class="image-card__image"
                                    mode="aspectFill"
                                    @click="previewImages(idx)"
                                />
                                <view class="image-card__delete" @click.stop="removeImage(idx)">
                                    <BaseIcon name="close" size="20" color="#ffffff" />
                                </view>
                                <view class="image-card__index">
                                    <text class="image-card__index-text">{{ idx + 1 }}</text>
                                </view>
                            </view>

                            <view
                                v-if="form.images.length < 9"
                                class="upload-tile"
                                @click="chooseImages"
                            >
                                <BaseIcon
                                    name="plus"
                                    size="42"
                                    color="var(--wm-color-primary, #0B0B0B)"
                                />
                                <text class="upload-tile__text">添加图片</text>
                            </view>
                        </view>
                    </view>

                    <view class="field-block field-block--compact">
                        <view class="field-label-row">
                            <view class="field-label-group">
                                <text class="field-label">作品视频</text>
                            </view>
                            <text class="field-side-text">{{
                                form.video ? '已上传' : '选填'
                            }}</text>
                        </view>

                        <view v-if="form.video" class="video-preview">
                            <video
                                :src="form.video"
                                class="video-preview__player"
                                object-fit="cover"
                                :show-center-play-btn="true"
                                :controls="true"
                            />
                            <view class="video-preview__action" @click="removeVideo">
                                <BaseIcon name="delete" size="24" color="#ffffff" />
                                <text class="video-preview__action-text">删除视频</text>
                            </view>
                        </view>

                        <view
                            v-else
                            class="upload-panel upload-panel--video wm-soft-card"
                            @click="chooseVideo"
                        >
                            <view class="upload-panel__icon-wrap">
                                <BaseIcon
                                    name="video"
                                    size="50"
                                    color="var(--wm-color-primary, #0B0B0B)"
                                />
                            </view>
                            <text class="upload-panel__title">上传视频</text>
                        </view>
                    </view>
                </BaseCard>

                <BaseCard variant="panel" scene="staff" class="form-card wm-form-block">
                    <view class="card-head">
                        <text class="card-head__title">内容</text>
                    </view>

                    <view class="field-block">
                        <view class="field-label-row">
                            <view class="field-label-group">
                                <text class="field-label field-label--required">作品标题</text>
                            </view>
                            <text class="field-side-text">{{ form.title.length }}/50</text>
                        </view>
                        <view class="field-input-shell">
                            <BaseInput
                                v-model="form.title"
                                placeholder="作品标题"
                                :maxlength="50"
                                clearable
                                variant="filled"
                                class="field-base-input"
                            />
                        </view>
                    </view>

                    <view class="field-block field-block--compact">
                        <view class="field-label-row">
                            <view class="field-label-group">
                                <text class="field-label">作品说明</text>
                            </view>
                            <text class="field-side-text">{{ form.description.length }}/500</text>
                        </view>
                        <view class="textarea-shell wm-soft-card">
                            <textarea
                                v-model="form.description"
                                class="field-textarea"
                                placeholder="作品说明"
                                :maxlength="500"
                                :auto-height="true"
                                :show-confirm-bar="false"
                            />
                        </view>
                    </view>
                </BaseCard>

                <BaseCard variant="panel" scene="staff" class="form-card wm-form-block">
                    <view class="card-head">
                        <text class="card-head__title">设置</text>
                    </view>

                    <view class="setting-list">
                        <view class="setting-item">
                            <text class="setting-item__label">拍摄日期</text>
                            <view class="setting-item__input setting-item__input--date">
                                <view
                                    class="setting-inline-control setting-inline-control--date"
                                    @click="openShootDatePicker"
                                >
                                    <text
                                        :class="[
                                            'setting-inline-text',
                                            { 'setting-inline-text--placeholder': !form.shoot_date }
                                        ]"
                                    >
                                        {{ form.shoot_date || '请选择' }}
                                    </text>
                                </view>
                            </view>
                        </view>

                        <view class="setting-item">
                            <text class="setting-item__label">拍摄地点</text>
                            <view class="setting-item__input">
                                <view class="setting-inline-control">
                                    <input
                                        class="setting-inline-input"
                                        placeholder-class="setting-inline-placeholder"
                                        type="text"
                                        placeholder="拍摄地点"
                                        :value="form.location"
                                        @input="handleLocationInput"
                                    />
                                </view>
                            </view>
                        </view>

                        <view class="setting-item">
                            <text class="setting-item__label">排序值</text>
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
                            <text class="setting-item__label">是否展示</text>
                            <switch
                                :checked="isShowSwitch"
                                :color="$theme.primaryColor"
                                style="transform: scale(0.9)"
                                @change="handleShowSwitchChange"
                            />
                        </view>
                    </view>
                </BaseCard>
            </view>

            <ActionArea sticky safeBottom layout="split" tone="solid">
                <view class="work-action-bar">
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
                :model-value="shootDatePickerValue"
                :open="showShootDatePicker"
                mode="date"
                format="YYYY-MM-DD"
                @update:model-value="shootDatePickerValue = $event"
                @update:open="showShootDatePicker = $event"
                @confirm="handleShootDateConfirm"
                @cancel="closeShootDatePicker"
                @close="closeShootDatePicker"
            />
        </view>
    </PageShell>
</template>

<script setup lang="ts">
import { computed, reactive, ref } from 'vue'
import { onLoad } from '@dcloudio/uni-app'
import { uploadImage, uploadVideo } from '@/api/app'
import ActionArea from '@/components/base/ActionArea.vue'
import BaseButton from '@/components/base/BaseButton.vue'
import BaseCard from '@/components/base/BaseCard.vue'
import BaseDateTimePicker from '@/components/base/BaseDateTimePicker.vue'
import BaseInput from '@/components/base/BaseInput.vue'
import BaseNavbar from '@/components/base/BaseNavbar.vue'
import PageShell from '@/components/base/PageShell.vue'
import { staffCenterWorkAdd, staffCenterWorkDetail, staffCenterWorkEdit } from '@/api/staffCenter'
import { ensureStaffCenterAccess } from '@/packages/common/utils/staff-center'
import { useThemeStore } from '@/stores/theme'
import { confirmModal, showError, showSuccess } from '@/utils/feedback'
const $theme = useThemeStore()
const submitting = ref(false)
const showShootDatePicker = ref(false)
const shootDatePickerValue = ref('')

const form = reactive({
    id: 0,
    title: '',
    cover: '',
    images: [] as string[],
    video: '',
    description: '',
    shoot_date: '',
    location: '',
    sort: '',
    is_show: 1
})

const isEdit = computed(() => form.id > 0)
const pageTitle = computed(() => (isEdit.value ? '编辑作品' : '新增作品'))
const submitButtonText = computed(() => {
    if (submitting.value) {
        return isEdit.value ? '保存中...' : '提交中...'
    }
    return isEdit.value ? '保存修改' : '提交审核'
})

const resolveErrorMessage = (error: unknown, fallback = '操作失败') => {
    if (typeof error === 'string' && error.trim()) {
        return error
    }
    if (error && typeof error === 'object') {
        const value = (error as { msg?: unknown; message?: unknown }).msg ??
            (error as { message?: unknown }).message
        if (typeof value === 'string' && value.trim()) {
            return value
        }
    }
    return fallback
}
const isShowSwitch = computed({
    get: () => form.is_show === 1,
    set: (val: boolean) => {
        form.is_show = val ? 1 : 0
    }
})

const getInputValue = (event: any) => String(event?.detail?.value ?? '')

const handleLocationInput = (event: any) => {
    form.location = getInputValue(event)
}

const handleSortInput = (event: any) => {
    form.sort = getInputValue(event)
}

const handleShowSwitchChange = (event: Event) => {
    const value = (event as Event & { detail?: { value?: boolean } }).detail?.value
    isShowSwitch.value = Boolean(value)
}

const openShootDatePicker = () => {
    shootDatePickerValue.value = form.shoot_date
    showShootDatePicker.value = true
}

const closeShootDatePicker = () => {
    showShootDatePicker.value = false
}

const handleShootDateConfirm = (value: string) => {
    form.shoot_date = value
    shootDatePickerValue.value = value
    closeShootDatePicker()
}

// 预览封面
const previewCover = () => {
    uni.previewImage({
        urls: [form.cover],
        current: form.cover
    })
}

// 删除封面
const removeCover = async () => {
    const confirmed = await confirmModal({
        title: '提示',
        content: '确定要删除封面图吗？'
    })
    if (confirmed) {
        form.cover = ''
    }
}

// 选择封面
const chooseCover = () => {
    uni.chooseImage({
        count: 1,
        sizeType: ['compressed'],
        sourceType: ['album', 'camera'],
        success: async (res) => {
            uni.showLoading({ title: '上传中...' })
            try {
                const uploadRes: any = await uploadImage(res.tempFilePaths[0])
                if (uploadRes?.uri) {
                    form.cover = uploadRes.uri
                }
            } catch (e: unknown) {
                showError(resolveErrorMessage(e, '上传失败'))
            } finally {
                uni.hideLoading()
            }
        }
    })
}

// 选择图片
const chooseImages = () => {
    const count = 9 - form.images.length
    uni.chooseImage({
        count,
        sizeType: ['compressed'],
        sourceType: ['album', 'camera'],
        success: async (res) => {
            uni.showLoading({ title: '上传中...' })
            try {
                for (const path of res.tempFilePaths) {
                    const uploadRes: any = await uploadImage(path)
                    if (uploadRes?.uri) {
                        form.images.push(uploadRes.uri)
                    }
                }
            } catch (e: unknown) {
                showError(resolveErrorMessage(e, '上传失败'))
            } finally {
                uni.hideLoading()
            }
        }
    })
}

// 预览图片
const previewImages = (index: number) => {
    uni.previewImage({
        urls: form.images,
        current: index
    })
}

// 长按图片
const handleImageLongPress = (index: number) => {
    uni.showActionSheet({
        itemList: ['删除图片', '设为封面'],
        success: (res) => {
            if (res.tapIndex === 0) {
                removeImage(index)
            } else if (res.tapIndex === 1) {
                form.cover = form.images[index]
                showSuccess('已设为封面')
            }
        }
    })
}

// 删除图片
const removeImage = (index: number) => {
    form.images.splice(index, 1)
}

// 选择视频
const chooseVideo = () => {
    uni.chooseVideo({
        sourceType: ['album', 'camera'],
        maxDuration: 60,
        compressed: true,
        success: async (res) => {
            uni.showLoading({ title: '上传中...' })
            try {
                const uploadRes: any = await uploadVideo(res.tempFilePath)
                if (uploadRes?.uri) {
                    form.video = uploadRes.uri
                }
            } catch (e: unknown) {
                showError(resolveErrorMessage(e, '上传失败'))
            } finally {
                uni.hideLoading()
            }
        }
    })
}

// 删除视频
const removeVideo = async () => {
    const confirmed = await confirmModal({
        title: '提示',
        content: '确定要删除视频吗？'
    })
    if (confirmed) {
        form.video = ''
    }
}

// 取消
const handleCancel = async () => {
    const confirmed = await confirmModal({
        title: '提示',
        content: '确定要放弃编辑吗？'
    })
    if (confirmed) {
        uni.navigateBack()
    }
}

// 加载详情
const loadDetail = async (id: number) => {
    const data = await staffCenterWorkDetail({ id })
    form.id = data.id
    form.title = data.title || ''
    form.cover = data.cover || ''
    form.images = Array.isArray(data.images) ? data.images : []
    form.video = data.video || ''
    form.description = data.description || ''
    form.shoot_date = data.shoot_date || ''
    form.location = data.location || ''
    form.sort = data.sort !== undefined && data.sort !== null ? String(data.sort) : ''
    form.is_show = Number(data.is_show ?? 1) === 1 ? 1 : 0
}

const normalizeOptionalDate = (value: string) => {
    const trimmedValue = value.trim()
    return trimmedValue === '' ? '' : trimmedValue
}

// 提交
const handleSubmit = async () => {
    if (!form.title.trim()) {
        showError('请输入作品标题')
        return
    }
    if (!form.cover) {
        showError('请上传封面图')
        return
    }

    const payload: any = {
        title: form.title.trim(),
        cover: form.cover,
        images: form.images,
        video: form.video,
        description: form.description,
        location: form.location,
        sort: form.sort === '' ? 0 : Number(form.sort),
        is_show: form.is_show
    }
    const shootDate = normalizeOptionalDate(form.shoot_date)
    payload.shoot_date = shootDate

    submitting.value = true
    try {
        if (isEdit.value) {
            await staffCenterWorkEdit({ ...payload, id: form.id })
            showSuccess('保存成功')
        } else {
            await staffCenterWorkAdd(payload)
            showSuccess('提交成功')
        }
        setTimeout(() => uni.navigateBack(), 1200)
    } catch (e: unknown) {
        showError(resolveErrorMessage(e, '提交失败'))
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

.field-label-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16rpx;
    margin-bottom: 16rpx;
}

.field-label-group {
    display: flex;
    align-items: center;
    gap: 10rpx;
    min-width: 0;
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

.field-input-shell {
    padding: 0;
    background: transparent;
    border: none;
    overflow: visible;
}

.textarea-shell {
    border-radius: 28rpx;
    background: #ffffff;
    border: 1rpx solid var(--wm-color-border, #e7e2d6);
    overflow: hidden;
    padding: 22rpx 24rpx;
}

.field-base-input {
    width: 100%;
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

.setting-inline-text,
.setting-inline-placeholder {
    height: 44rpx;
    font-size: 23rpx;
    font-weight: 700;
    line-height: 44rpx;
    text-align: center;
    color: var(--wm-text-primary, #111111);
}

.setting-inline-text--placeholder,
.setting-inline-placeholder {
    color: var(--wm-text-tertiary, #9a9388);
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

.cover-preview,
.video-preview {
    position: relative;
    width: 100%;
    overflow: hidden;
    border-radius: 32rpx;
    background: #f8f7f2;
}

.cover-preview__image,
.video-preview__player {
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

.upload-panel--video {
    height: 272rpx;
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

.images-grid {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 14rpx;
}

.image-card,
.upload-tile {
    position: relative;
    width: 100%;
    padding-bottom: 100%;
    border-radius: 26rpx;
    overflow: hidden;
}

.image-card {
    background: #f8f7f2;
}

.image-card__image {
    position: absolute;
    inset: 0;
    width: 100%;
    height: 100%;
}

.image-card__delete,
.image-card__index {
    position: absolute;
    z-index: 2;
    display: flex;
    align-items: center;
    justify-content: center;
}

.image-card__delete {
    top: 10rpx;
    right: 10rpx;
    width: 44rpx;
    height: 44rpx;
    border-radius: 999rpx;
    background: rgba(11, 11, 11, 0.54);
}

.image-card__index {
    left: 10rpx;
    bottom: 10rpx;
    min-width: 40rpx;
    height: 40rpx;
    padding: 0 10rpx;
    border-radius: 999rpx;
    background: rgba(11, 11, 11, 0.9);
}

.image-card__index-text {
    font-size: 20rpx;
    font-weight: 700;
    line-height: 1;
    color: #ffffff;
}

.upload-tile {
    border: 1rpx dashed rgba(216, 194, 138, 0.88);
    background: rgba(248, 247, 242, 0.82);
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 10rpx;
}

.upload-tile__text {
    font-size: 22rpx;
    font-weight: 700;
    line-height: 1.2;
    color: var(--wm-color-primary, #0b0b0b);
}

.video-preview__player {
    height: 272rpx;
}

.video-preview__action {
    position: absolute;
    right: 14rpx;
    bottom: 14rpx;
    min-height: 56rpx;
    padding: 0 20rpx;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8rpx;
    border-radius: 999rpx;
    background: rgba(11, 11, 11, 0.54);
    backdrop-filter: blur(12rpx);
    -webkit-backdrop-filter: blur(12rpx);
}

.video-preview__action-text {
    font-size: 22rpx;
    font-weight: 600;
    line-height: 1;
    color: #ffffff;
}

.setting-list {
    border-radius: 30rpx;
    background: #ffffff;
    border: 1rpx solid var(--wm-color-border, #e7e2d6);
    overflow: hidden;
}

.setting-item {
    min-height: 78rpx;
    padding: 0 20rpx;
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
    font-size: 26rpx;
    font-weight: 600;
    line-height: 1.3;
    color: var(--wm-text-primary, #111111);
}

.setting-item__value {
    display: flex;
    align-items: center;
    gap: 10rpx;
    min-width: 0;
}

.setting-item__value-text {
    font-size: 26rpx;
    font-weight: 600;
    line-height: 1.2;
    color: var(--wm-text-primary, #111111);
}

.setting-item__value-text--placeholder {
    color: var(--wm-text-tertiary, #9a9388);
}

.setting-item__input {
    flex: 1;
    min-width: 0;
    max-width: 320rpx;
    display: flex;
    align-items: center;
    justify-content: flex-end;
}

.setting-item__input--date {
    max-width: 244rpx;
}

.setting-item__input--sm {
    max-width: 144rpx;
}

.page-container :deep(.wm-action-area) {
    padding-left: var(--wm-space-page-x, 37rpx);
    padding-right: var(--wm-space-page-x, 37rpx);
}

.work-action-bar {
    width: 100%;
    display: flex;
    gap: 16rpx;
}

.work-action-bar :deep(.base-button) {
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
