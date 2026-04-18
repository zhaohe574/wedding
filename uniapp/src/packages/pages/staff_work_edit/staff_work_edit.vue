<template>
    <page-meta :page-style="$theme.pageStyle" />
    <PageShell scene="staff">
        <BaseNavbar :title="pageTitle" />

        <view class="page-container">
            <view class="page-section page-section--content">
                <BaseCard variant="glass" scene="staff" class="form-card wm-form-block">
                    <view class="card-head">
                        <text class="card-head__title">作品封面与素材</text>
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
                                    color="var(--wm-color-primary, #E85A4F)"
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
                                    <tn-icon name="close" size="20" color="#ffffff" />
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
                                <tn-icon
                                    name="plus"
                                    size="42"
                                    color="var(--wm-color-primary, #E85A4F)"
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
                                <tn-icon name="delete" size="24" color="#ffffff" />
                                <text class="video-preview__action-text">删除视频</text>
                            </view>
                        </view>

                        <view
                            v-else
                            class="upload-panel upload-panel--video wm-soft-card"
                            @click="chooseVideo"
                        >
                            <view class="upload-panel__icon-wrap">
                                <tn-icon
                                    name="video"
                                    size="50"
                                    color="var(--wm-color-primary, #E85A4F)"
                                />
                            </view>
                            <text class="upload-panel__title">上传视频</text>
                        </view>
                    </view>
                </BaseCard>

                <BaseCard variant="glass" scene="staff" class="form-card wm-form-block">
                    <view class="card-head">
                        <text class="card-head__title">作品内容</text>
                    </view>

                    <view class="field-block">
                        <view class="field-label-row">
                            <view class="field-label-group">
                                <text class="field-label field-label--required">作品标题</text>
                            </view>
                            <text class="field-side-text">{{ form.title.length }}/50</text>
                        </view>
                        <view class="field-input-shell wm-soft-card">
                            <tn-input
                                v-model="form.title"
                                placeholder="例如：浪漫海边婚礼纪实"
                                :maxlength="50"
                                :border="false"
                                class="field-input"
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
                                placeholder="写一句作品亮点"
                                :maxlength="500"
                                :auto-height="true"
                                :show-confirm-bar="false"
                            />
                        </view>
                    </view>
                </BaseCard>

                <BaseCard variant="glass" scene="staff" class="form-card wm-form-block">
                    <view class="card-head">
                        <text class="card-head__title">展示设置</text>
                    </view>

                    <view class="setting-list">
                        <picker mode="date" :value="form.shoot_date" @change="handleDateChange">
                            <view class="setting-item">
                                <text class="setting-item__label">拍摄日期</text>
                                <view class="setting-item__value">
                                    <text
                                        :class="[
                                            'setting-item__value-text',
                                            {
                                                'setting-item__value-text--placeholder':
                                                    !form.shoot_date
                                            }
                                        ]"
                                    >
                                        {{ form.shoot_date || '请选择' }}
                                    </text>
                                    <tn-icon name="arrow-right" size="24" color="#B4ACA8" />
                                </view>
                            </view>
                        </picker>

                        <view class="setting-item">
                            <text class="setting-item__label">拍摄地点</text>
                            <view class="setting-item__input">
                                <tn-input
                                    v-model="form.location"
                                    placeholder="例如：三亚海棠湾"
                                    :border="false"
                                    class="setting-input setting-input--right"
                                />
                            </view>
                        </view>

                        <view class="setting-item">
                            <text class="setting-item__label">排序值</text>
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
                            <text class="setting-item__label">是否展示</text>
                            <switch
                                :checked="isShowSwitch"
                                :color="$theme.primaryColor"
                                style="transform: scale(0.9)"
                                @change="isShowSwitch = $event.detail.value"
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
                        <text class="bottom-bar__action-text bottom-bar__action-text--ghost">
                            取消
                        </text>
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
                        <text class="bottom-bar__action-text">
                            {{ submitButtonText }}
                        </text>
                    </view>
                </view>
            </view>
        </view>
    </PageShell>
</template>

<script setup lang="ts">
import { computed, reactive, ref } from 'vue'
import { onLoad } from '@dcloudio/uni-app'
import { uploadImage, uploadVideo } from '@/api/app'
import BaseCard from '@/components/base/BaseCard.vue'
import BaseNavbar from '@/components/base/BaseNavbar.vue'
import PageShell from '@/components/base/PageShell.vue'
import { staffCenterWorkAdd, staffCenterWorkDetail, staffCenterWorkEdit } from '@/api/staffCenter'
import { ensureStaffCenterAccess } from '@/packages/common/utils/staff-center'
import { useThemeStore } from '@/stores/theme'
const $theme = useThemeStore()
const submitting = ref(false)

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
const isShowSwitch = computed({
    get: () => form.is_show === 1,
    set: (val: boolean) => {
        form.is_show = val ? 1 : 0
    }
})

// 预览封面
const previewCover = () => {
    uni.previewImage({
        urls: [form.cover],
        current: form.cover
    })
}

// 删除封面
const removeCover = () => {
    uni.showModal({
        title: '提示',
        content: '确定要删除封面图吗？',
        success: (res) => {
            if (res.confirm) {
                form.cover = ''
            }
        }
    })
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
            } catch (e: any) {
                uni.showToast({ title: e?.message || '上传失败', icon: 'none' })
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
            } catch (e: any) {
                uni.showToast({ title: e?.message || '上传失败', icon: 'none' })
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
                uni.showToast({ title: '已设为封面', icon: 'success' })
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
            } catch (e: any) {
                uni.showToast({ title: e?.message || '上传失败', icon: 'none' })
            } finally {
                uni.hideLoading()
            }
        }
    })
}

// 删除视频
const removeVideo = () => {
    uni.showModal({
        title: '提示',
        content: '确定要删除视频吗？',
        success: (res) => {
            if (res.confirm) {
                form.video = ''
            }
        }
    })
}

// 日期变化
const handleDateChange = (e: any) => {
    form.shoot_date = e.detail.value
}

// 取消
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
        uni.showToast({ title: '请输入作品标题', icon: 'none' })
        return
    }
    if (!form.cover) {
        uni.showToast({ title: '请上传封面图', icon: 'none' })
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
            uni.showToast({ title: '保存成功', icon: 'success' })
        } else {
            await staffCenterWorkAdd(payload)
            uni.showToast({ title: '提交成功', icon: 'success' })
        }
        setTimeout(() => uni.navigateBack(), 1200)
    } catch (e: any) {
        const msg = typeof e === 'string' ? e : e?.msg || e?.message || '提交失败'
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
    color: var(--wm-text-primary, #1e2432);
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
    color: var(--wm-text-primary, #1e2432);
}

.field-label--required::before {
    content: '*';
    margin-right: 6rpx;
    color: var(--wm-color-primary, #e85a4f);
}

.field-side-text {
    flex-shrink: 0;
    font-size: 22rpx;
    font-weight: 600;
    line-height: 1;
    color: var(--wm-text-tertiary, #948f8b);
}

.field-input-shell,
.textarea-shell {
    border-radius: 28rpx;
    background: #fcfbf9;
    border: 1rpx solid var(--wm-color-border, #efe6e1);
    overflow: hidden;
}

.field-input-shell {
    min-height: 94rpx;
    padding: 0 24rpx;
    display: flex;
    align-items: center;
}

.textarea-shell {
    padding: 22rpx 24rpx;
}

.field-input,
.setting-input {
    width: 100%;
}

.field-input :deep(.tn-input),
.setting-input :deep(.tn-input) {
    background: transparent !important;
}

.field-input :deep(.input-placeholder),
.setting-input :deep(.input-placeholder) {
    color: #b4aca8 !important;
}

.field-input :deep(.input-text),
.setting-input :deep(.input-text) {
    font-size: 28rpx !important;
    color: #1e2432 !important;
}

.field-textarea {
    width: 100%;
    min-height: 220rpx;
    font-size: 28rpx;
    line-height: 1.65;
    color: var(--wm-text-primary, #1e2432);
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
    background: #f7f1ed;
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
    background: rgba(19, 24, 35, 0.48);
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
    border: 1rpx dashed rgba(244, 199, 191, 0.88);
    background: linear-gradient(
        180deg,
        rgba(255, 245, 241, 0.9) 0%,
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
    border: 1rpx solid rgba(244, 199, 191, 0.72);
}

.upload-panel__title {
    font-size: 28rpx;
    font-weight: 700;
    line-height: 1.2;
    color: var(--wm-text-primary, #1e2432);
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
    background: #f7f1ed;
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
    background: rgba(19, 24, 35, 0.54);
}

.image-card__index {
    left: 10rpx;
    bottom: 10rpx;
    min-width: 40rpx;
    height: 40rpx;
    padding: 0 10rpx;
    border-radius: 999rpx;
    background: rgba(232, 90, 79, 0.9);
}

.image-card__index-text {
    font-size: 20rpx;
    font-weight: 700;
    line-height: 1;
    color: #ffffff;
}

.upload-tile {
    border: 1rpx dashed rgba(244, 199, 191, 0.88);
    background: rgba(255, 245, 241, 0.82);
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
    color: var(--wm-color-primary, #e85a4f);
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
    background: rgba(19, 24, 35, 0.54);
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
    background: #fcfbf9;
    border: 1rpx solid var(--wm-color-border, #efe6e1);
    overflow: hidden;
}

.setting-item {
    min-height: 98rpx;
    padding: 0 22rpx;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16rpx;
    border-bottom: 1rpx solid rgba(239, 230, 225, 0.9);
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
    color: var(--wm-text-primary, #1e2432);
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
    color: var(--wm-text-primary, #1e2432);
}

.setting-item__value-text--placeholder {
    color: var(--wm-text-tertiary, #b4aca8);
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
    border: 1rpx solid var(--wm-color-border, #efe6e1);
}

.bottom-bar__action--primary {
    flex: 1.35;
    background: linear-gradient(135deg, var(--wm-color-primary, #e85a4f) 0%, #d96a60 100%);
    box-shadow: 0 14rpx 28rpx rgba(232, 90, 79, 0.18);
}

.bottom-bar__action-text {
    font-size: 30rpx;
    font-weight: 700;
    line-height: 1;
    color: #ffffff;
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
