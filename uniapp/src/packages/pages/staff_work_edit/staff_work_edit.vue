<template>
    <page-meta :page-style="$theme.pageStyle">
        <navigation-bar
            :title="pageTitle"
            :front-color="$theme.navColor"
            :background-color="$theme.navBgColor"
        />
    </page-meta>

    <view class="page-container">
        <!-- 顶部提示卡片 -->
        <view class="tip-card" :style="{ borderColor: $theme.primaryColor }">
            <view class="flex items-center">
                <tn-icon name="info-circle" size="32" :color="$theme.primaryColor" />
                <text class="tip-text">展示您的专业作品，吸引更多客户</text>
            </view>
        </view>

        <!-- 作品标题 -->
        <view class="form-section">
            <view class="section-header">
                <view class="section-title">
                    <text class="required-mark" :style="{ color: $theme.ctaColor }">*</text>
                    <text>作品标题</text>
                </view>
                <text class="section-desc">给作品起个吸引人的名字</text>
            </view>
            <view class="input-wrapper">
                <tn-input
                    v-model="form.title"
                    placeholder="例如：浪漫海边婚纱照"
                    :maxlength="50"
                    class="custom-input"
                />
                <text class="char-count">{{ form.title.length }}/50</text>
            </view>
        </view>

        <!-- 封面图 -->
        <view class="form-section">
            <view class="section-header">
                <view class="section-title">
                    <text class="required-mark" :style="{ color: $theme.ctaColor }">*</text>
                    <text>封面图</text>
                </view>
                <text class="section-desc">建议尺寸：750x750px</text>
            </view>
            <view class="cover-upload-area">
                <view v-if="form.cover" class="cover-preview" @click="previewCover">
                    <image :src="form.cover" class="cover-image" mode="aspectFill" />
                    <view class="cover-mask">
                        <view class="cover-actions">
                            <view class="action-btn" @click.stop="chooseCover">
                                <tn-icon name="refresh" size="32" color="#fff" />
                                <text class="action-text">更换</text>
                            </view>
                            <view class="action-btn" @click.stop="removeCover">
                                <tn-icon name="delete" size="32" color="#fff" />
                                <text class="action-text">删除</text>
                            </view>
                        </view>
                    </view>
                </view>
                <view v-else class="cover-upload-btn" @click="chooseCover">
                    <view class="upload-icon-wrapper" :style="{ borderColor: $theme.primaryColor }">
                        <tn-icon name="image" size="48" :color="$theme.primaryColor" />
                    </view>
                    <text class="upload-text">点击上传封面</text>
                    <text class="upload-hint">支持jpg、png格式</text>
                </view>
            </view>
        </view>

        <!-- 作品图片 -->
        <view class="form-section">
            <view class="section-header">
                <view class="section-title">
                    <text>作品图片</text>
                    <view class="count-badge" :style="{ backgroundColor: $theme.primaryColor }">
                        {{ form.images.length }}/9
                    </view>
                </view>
                <text class="section-desc">最多上传9张，展示作品细节</text>
            </view>
            <view class="images-grid">
                <view
                    v-for="(img, idx) in form.images"
                    :key="idx"
                    class="image-item"
                    @longpress="handleImageLongPress(idx)"
                >
                    <image
                        :src="img"
                        class="image-content"
                        mode="aspectFill"
                        @click="previewImages(idx)"
                    />
                    <view class="image-delete" @click.stop="removeImage(idx)">
                        <tn-icon name="close" size="24" color="#fff" />
                    </view>
                    <view class="image-index" :style="{ backgroundColor: $theme.primaryColor }">
                        {{ idx + 1 }}
                    </view>
                </view>
                <view v-if="form.images.length < 9" class="image-upload-btn" @click="chooseImages">
                    <tn-icon name="plus" size="48" :color="$theme.primaryColor" />
                    <text class="upload-btn-text">添加图片</text>
                </view>
            </view>
        </view>

        <!-- 作品视频 -->
        <view class="form-section">
            <view class="section-header">
                <view class="section-title">
                    <text>作品视频</text>
                    <view
                        v-if="form.video"
                        class="video-badge"
                        :style="{ backgroundColor: $theme.accentColor }"
                    >
                        <tn-icon name="video" size="24" color="#fff" />
                    </view>
                </view>
                <text class="section-desc">时长不超过60秒，大小不超过50MB</text>
            </view>
            <view v-if="form.video" class="video-preview">
                <video
                    :src="form.video"
                    class="video-player"
                    object-fit="cover"
                    :show-center-play-btn="true"
                    :controls="true"
                />
                <view class="video-delete" @click="removeVideo">
                    <tn-icon name="delete" size="32" color="#fff" />
                    <text class="delete-text">删除视频</text>
                </view>
            </view>
            <view v-else class="video-upload-btn" @click="chooseVideo">
                <view class="upload-icon-wrapper" :style="{ borderColor: $theme.secondaryColor }">
                    <tn-icon name="video" size="48" :color="$theme.secondaryColor" />
                </view>
                <text class="upload-text">点击上传视频</text>
                <text class="upload-hint">支持mp4格式</text>
            </view>
        </view>

        <!-- 作品说明 -->
        <view class="form-section">
            <view class="section-header">
                <view class="section-title">
                    <text>作品说明</text>
                </view>
                <text class="section-desc">介绍作品的创作理念、拍摄技巧等</text>
            </view>
            <view class="textarea-wrapper">
                <textarea
                    v-model="form.description"
                    class="custom-textarea"
                    placeholder="例如：这组作品采用自然光拍摄，捕捉了新人最真实的情感瞬间..."
                    :maxlength="500"
                    :auto-height="true"
                    :show-confirm-bar="false"
                />
                <text class="char-count">{{ form.description.length }}/500</text>
            </view>
        </view>

        <!-- 作品信息 -->
        <view class="form-section">
            <view class="section-header">
                <view class="section-title">
                    <text>作品信息</text>
                </view>
            </view>
            <view class="info-list">
                <picker mode="date" :value="form.shoot_date" @change="handleDateChange">
                    <view class="info-item">
                        <view class="info-label">
                            <tn-icon name="calendar" size="32" :color="$theme.primaryColor" />
                            <text class="label-text">拍摄日期</text>
                        </view>
                        <view class="info-value">
                            <text :class="form.shoot_date ? 'value-text' : 'placeholder-text'">
                                {{ form.shoot_date || '请选择日期' }}
                            </text>
                            <tn-icon name="arrow-right" size="28" color="#999" />
                        </view>
                    </view>
                </picker>

                <view class="info-item">
                    <view class="info-label">
                        <tn-icon name="map-pin" size="32" :color="$theme.primaryColor" />
                        <text class="label-text">拍摄地点</text>
                    </view>
                    <view class="info-value flex-1">
                        <tn-input
                            v-model="form.location"
                            placeholder="例如：三亚海棠湾"
                            class="location-input"
                        />
                    </view>
                </view>

                <view class="info-item">
                    <view class="info-label">
                        <tn-icon name="sort" size="32" :color="$theme.primaryColor" />
                        <text class="label-text">排序值</text>
                    </view>
                    <view class="info-value flex-1">
                        <tn-input
                            v-model="form.sort"
                            type="number"
                            placeholder="数值越大越靠前"
                            class="sort-input"
                        />
                    </view>
                </view>

                <view class="info-item">
                    <view class="info-label">
                        <tn-icon name="eye" size="32" :color="$theme.primaryColor" />
                        <text class="label-text">是否展示</text>
                    </view>
                    <view class="info-value">
                        <u-switch
                            v-model="isShowSwitch"
                            :active-color="$theme.primaryColor"
                            inactive-color="#e5e7eb"
                            size="24"
                        />
                    </view>
                </view>
            </view>
        </view>

        <!-- 底部操作栏 -->
        <view class="bottom-bar">
            <view class="bottom-content">
                <view class="btn-group">
                    <view class="cancel-btn" @click="handleCancel">
                        <text class="cancel-text">取消</text>
                    </view>
                    <view
                        class="submit-btn"
                        :style="{
                            background: `linear-gradient(135deg, ${$theme.primaryColor} 0%, ${$theme.primaryColor} 100%)`,
                            opacity: submitting ? 0.6 : 1
                        }"
                        @click="handleSubmit"
                    >
                        <tn-icon
                            v-if="submitting"
                            name="loading"
                            size="32"
                            color="#fff"
                            class="loading-icon"
                        />
                        <text class="submit-text">{{
                            submitting ? '提交中...' : isEdit ? '保存修改' : '提交审核'
                        }}</text>
                    </view>
                </view>
            </view>
        </view>
    </view>
</template>

<script setup lang="ts">
import { computed, reactive, ref } from 'vue'
import { onLoad } from '@dcloudio/uni-app'
import { uploadImage, uploadVideo } from '@/api/app'
import { staffCenterWorkAdd, staffCenterWorkDetail, staffCenterWorkEdit } from '@/api/staffCenter'
import { ensureStaffCenterAccess } from '@/utils/staff-center'
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
                if (uploadRes?.url) {
                    form.cover = uploadRes.url
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
                    if (uploadRes?.url) {
                        form.images.push(uploadRes.url)
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
                if (uploadRes?.url) {
                    form.video = uploadRes.url
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
    form.is_show = data.is_show ? 1 : 0
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
        shoot_date: form.shoot_date,
        location: form.location,
        sort: form.sort === '' ? 0 : Number(form.sort),
        is_show: form.is_show
    }

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
    background: linear-gradient(180deg, #f9fafb 0%, #ffffff 100%);
    padding-bottom: 180rpx;
}

/* 顶部提示卡片 */
.tip-card {
    margin: 24rpx;
    padding: 24rpx;
    background: rgba(255, 255, 255, 0.9);
    border-radius: 16rpx;
    border-left: 6rpx solid;
    box-shadow: 0 2rpx 12rpx rgba(0, 0, 0, 0.06);

    .tip-text {
        margin-left: 16rpx;
        font-size: 28rpx;
        color: #666;
        line-height: 1.5;
    }
}

/* 表单区块 */
.form-section {
    margin: 0 24rpx 24rpx;
    padding: 32rpx;
    background: #ffffff;
    border-radius: 20rpx;
    box-shadow: 0 2rpx 16rpx rgba(0, 0, 0, 0.04);
}

/* 区块标题 */
.section-header {
    margin-bottom: 24rpx;

    .section-title {
        display: flex;
        align-items: center;
        font-size: 32rpx;
        font-weight: 600;
        color: #333;
        margin-bottom: 8rpx;

        .required-mark {
            font-size: 32rpx;
            font-weight: 700;
            margin-right: 6rpx;
        }

        .count-badge {
            margin-left: 12rpx;
            padding: 4rpx 12rpx;
            border-radius: 20rpx;
            font-size: 24rpx;
            font-weight: 500;
            color: #ffffff;
        }

        .video-badge {
            margin-left: 12rpx;
            padding: 6rpx 12rpx;
            border-radius: 20rpx;
            display: flex;
            align-items: center;
        }
    }

    .section-desc {
        font-size: 26rpx;
        color: #999;
        line-height: 1.5;
    }
}

/* 输入框包装 */
.input-wrapper {
    position: relative;

    .custom-input {
        width: 100%;
        padding: 24rpx;
        background: #f9fafb;
        border-radius: 16rpx;
        border: 2rpx solid #e5e7eb;
        font-size: 28rpx;
        transition: all 0.2s ease;

        &:focus {
            background: #ffffff;
            border-color: var(--color-primary);
        }
    }

    .char-count {
        position: absolute;
        right: 24rpx;
        bottom: 24rpx;
        font-size: 24rpx;
        color: #999;
    }
}

/* 封面上传区域 */
.cover-upload-area {
    width: 100%;
}

.cover-preview {
    position: relative;
    width: 100%;
    height: 400rpx;
    border-radius: 16rpx;
    overflow: hidden;

    .cover-image {
        width: 100%;
        height: 100%;
    }

    .cover-mask {
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0, 0, 0, 0);
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.3s ease;

        &:active {
            background: rgba(0, 0, 0, 0.5);
        }
    }

    .cover-actions {
        display: flex;
        gap: 32rpx;
        opacity: 0;
        transform: translateY(20rpx);
        transition: all 0.3s ease;
    }

    &:active .cover-actions {
        opacity: 1;
        transform: translateY(0);
    }

    .action-btn {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 8rpx;

        .action-text {
            font-size: 24rpx;
            color: #ffffff;
        }
    }
}

.cover-upload-btn {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    width: 100%;
    height: 400rpx;
    background: #f9fafb;
    border-radius: 16rpx;
    border: 2rpx dashed #e5e7eb;
    transition: all 0.2s ease;

    &:active {
        background: #f3f4f6;
        border-color: var(--color-primary);
    }

    .upload-icon-wrapper {
        width: 120rpx;
        height: 120rpx;
        border-radius: 50%;
        border: 3rpx dashed;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-bottom: 16rpx;
    }

    .upload-text {
        font-size: 28rpx;
        color: #333;
        font-weight: 500;
        margin-bottom: 8rpx;
    }

    .upload-hint {
        font-size: 24rpx;
        color: #999;
    }
}

/* 图片网格 */
.images-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 16rpx;
}

.image-item {
    position: relative;
    width: 100%;
    padding-bottom: 100%;
    border-radius: 16rpx;
    overflow: hidden;

    .image-content {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
    }

    .image-delete {
        position: absolute;
        top: 8rpx;
        right: 8rpx;
        width: 48rpx;
        height: 48rpx;
        background: rgba(0, 0, 0, 0.6);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 2;
    }

    .image-index {
        position: absolute;
        bottom: 8rpx;
        left: 8rpx;
        width: 40rpx;
        height: 40rpx;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 24rpx;
        font-weight: 600;
        color: #ffffff;
        z-index: 1;
    }
}

.image-upload-btn {
    position: relative;
    width: 100%;
    padding-bottom: 100%;
    background: #f9fafb;
    border-radius: 16rpx;
    border: 2rpx dashed #e5e7eb;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    transition: all 0.2s ease;

    &:active {
        background: #f3f4f6;
        border-color: var(--color-primary);
    }

    .upload-btn-text {
        position: absolute;
        bottom: 20rpx;
        left: 0;
        right: 0;
        text-align: center;
        font-size: 24rpx;
        color: #666;
    }
}

/* 视频预览 */
.video-preview {
    position: relative;
    width: 100%;
    border-radius: 16rpx;
    overflow: hidden;

    .video-player {
        width: 100%;
        height: 400rpx;
    }

    .video-delete {
        position: absolute;
        bottom: 16rpx;
        right: 16rpx;
        padding: 12rpx 24rpx;
        background: rgba(0, 0, 0, 0.7);
        border-radius: 48rpx;
        display: flex;
        align-items: center;
        gap: 8rpx;

        .delete-text {
            font-size: 26rpx;
            color: #ffffff;
        }
    }
}

.video-upload-btn {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    width: 100%;
    height: 300rpx;
    background: #f9fafb;
    border-radius: 16rpx;
    border: 2rpx dashed #e5e7eb;
    transition: all 0.2s ease;

    &:active {
        background: #f3f4f6;
        border-color: var(--color-secondary);
    }

    .upload-icon-wrapper {
        width: 120rpx;
        height: 120rpx;
        border-radius: 50%;
        border: 3rpx dashed;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-bottom: 16rpx;
    }

    .upload-text {
        font-size: 28rpx;
        color: #333;
        font-weight: 500;
        margin-bottom: 8rpx;
    }

    .upload-hint {
        font-size: 24rpx;
        color: #999;
    }
}

/* 文本域包装 */
.textarea-wrapper {
    position: relative;

    .custom-textarea {
        width: 100%;
        min-height: 200rpx;
        padding: 24rpx;
        background: #f9fafb;
        border-radius: 16rpx;
        border: 2rpx solid #e5e7eb;
        font-size: 28rpx;
        line-height: 1.6;
        color: #333;
        transition: all 0.2s ease;

        &:focus {
            background: #ffffff;
            border-color: var(--color-primary);
        }
    }

    .char-count {
        position: absolute;
        right: 24rpx;
        bottom: 24rpx;
        font-size: 24rpx;
        color: #999;
    }
}

/* 信息列表 */
.info-list {
    .info-item {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 28rpx 0;
        border-bottom: 1rpx solid #f3f4f6;

        &:last-child {
            border-bottom: none;
        }

        .info-label {
            display: flex;
            align-items: center;
            gap: 12rpx;

            .label-text {
                font-size: 28rpx;
                color: #333;
                font-weight: 500;
            }
        }

        .info-value {
            display: flex;
            align-items: center;
            gap: 12rpx;

            .value-text {
                font-size: 28rpx;
                color: #333;
            }

            .placeholder-text {
                font-size: 28rpx;
                color: #999;
            }
        }

        .location-input,
        .sort-input {
            text-align: right;
            font-size: 28rpx;
        }
    }
}

/* 底部操作栏 */
.bottom-bar {
    position: fixed;
    bottom: 0;
    left: 0;
    right: 0;
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(20rpx);
    border-top: 1rpx solid #f3f4f6;
    padding-bottom: constant(safe-area-inset-bottom);
    padding-bottom: env(safe-area-inset-bottom);
    z-index: 100;
}

.bottom-content {
    padding: 24rpx;
}

.btn-group {
    display: flex;
    gap: 24rpx;
}

.cancel-btn {
    flex: 1;
    height: 72rpx;
    background: #f3f4f6;
    border-radius: 32rpx;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s ease;

    &:active {
        background: #e5e7eb;
        transform: scale(0.98);
    }

    .cancel-text {
        font-size: 32rpx;
        font-weight: 600;
        color: #666;
    }
}

.submit-btn {
    flex: 2;
    height: 72rpx;
    border-radius: 32rpx;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 12rpx;
    box-shadow: 0 8rpx 24rpx rgba(124, 58, 237, 0.3);
    transition: all 0.2s ease;

    &:active {
        transform: translateY(2rpx);
        box-shadow: 0 4rpx 12rpx rgba(124, 58, 237, 0.3);
    }

    .loading-icon {
        animation: rotate 1s linear infinite;
    }

    .submit-text {
        font-size: 32rpx;
        font-weight: 700;
        color: #ffffff;
    }
}

@keyframes rotate {
    from {
        transform: rotate(0deg);
    }
    to {
        transform: rotate(360deg);
    }
}

/* 无障碍动画 */
@media (prefers-reduced-motion: reduce) {
    * {
        animation-duration: 0.01ms !important;
        animation-iteration-count: 1 !important;
        transition-duration: 0.01ms !important;
    }
}
</style>
