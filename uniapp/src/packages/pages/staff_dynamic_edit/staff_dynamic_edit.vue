<template>
    <page-meta :page-style="$theme.pageStyle">
        <navigation-bar
            :title="pageTitle"
            :front-color="$theme.navColor"
            :background-color="$theme.navBgColor"
        />
    </page-meta>

    <view class="page-container">
        <!-- 类型切换 Tab -->
        <view class="type-tab-card" :class="{ disabled: isEdit }">
            <view
                v-for="type in dynamicTypes"
                :key="type.value"
                class="type-tab"
                :class="{ active: form.dynamic_type === type.value }"
                :style="
                    form.dynamic_type === type.value
                        ? { color: $theme.primaryColor, borderColor: $theme.primaryColor }
                        : {}
                "
                @click="switchType(type.value)"
            >
                <tn-icon :name="type.icon" size="32" :color="form.dynamic_type === type.value ? $theme.primaryColor : '#9CA3AF'" />
                <text>{{ type.label }}</text>
            </view>
        </view>

        <!-- 内容输入 -->
        <view class="content-card">
            <textarea
                v-model="form.content"
                class="content-textarea"
                :placeholder="form.dynamic_type === 1 ? '分享你的服务动态、心得体会...' : '为视频添加描述...'"
                :maxlength="2000"
                :auto-height="true"
                :style="{ minHeight: form.dynamic_type === 2 ? '160rpx' : '260rpx' }"
            />
            <view class="content-footer">
                <text class="content-counter" :style="form.content.length > 1800 ? { color: '#EF4444' } : {}">
                    {{ form.content.length }}/2000
                </text>
            </view>
        </view>

        <!-- 图文模式：图片上传 -->
        <view v-if="form.dynamic_type === 1" class="media-card">
            <view class="card-title-row">
                <text class="card-title">添加图片</text>
                <text class="card-hint">{{ form.images.length }}/9</text>
            </view>
            <view class="image-grid">
                <view
                    v-for="(img, idx) in form.images"
                    :key="idx"
                    class="image-item"
                >
                    <image :src="img" class="image-content" mode="aspectFill" @click="previewImage(idx)" />
                    <view class="image-delete" @click.stop="removeImage(idx)">
                        <tn-icon name="close" size="22" color="#fff" />
                    </view>
                </view>
                <view
                    v-if="form.images.length < 9"
                    class="upload-btn"
                    :style="{ borderColor: `${$theme.primaryColor}40` }"
                    @click="chooseImage"
                >
                    <tn-icon name="plus" size="40" :color="`${$theme.primaryColor}80`" />
                    <text class="upload-text" :style="{ color: `${$theme.primaryColor}80` }">添加图片</text>
                </view>
            </view>
        </view>

        <!-- 视频模式：视频上传 -->
        <view v-if="form.dynamic_type === 2" class="media-card">
            <view class="card-title-row">
                <text class="card-title">添加视频</text>
                <text class="card-hint">最长60秒</text>
            </view>
            <view v-if="form.video_url" class="video-preview">
                <video :src="form.video_url" class="video-player" object-fit="cover" />
                <view class="video-delete" @click="removeVideo">
                    <tn-icon name="close" size="26" color="#fff" />
                </view>
            </view>
            <view
                v-else
                class="video-upload-area"
                :style="{ borderColor: `${$theme.primaryColor}40` }"
                @click="chooseVideo"
            >
                <tn-icon name="video" size="56" :color="`${$theme.primaryColor}80`" />
                <text class="upload-text" :style="{ color: `${$theme.primaryColor}80` }">点击上传视频</text>
                <text class="upload-sub">支持 mp4 格式，最长60秒</text>
            </view>

            <!-- 视频封面（可选） -->
            <view v-if="form.video_url" class="cover-section">
                <view class="card-title-row">
                    <text class="card-title">视频封面</text>
                    <text class="card-hint">可选</text>
                </view>
                <view v-if="form.video_cover" class="cover-preview">
                    <image :src="form.video_cover" class="cover-image" mode="aspectFill" />
                    <view class="cover-delete" @click="removeCover">
                        <tn-icon name="close" size="22" color="#fff" />
                    </view>
                </view>
                <view
                    v-else
                    class="cover-upload-btn"
                    :style="{ borderColor: `${$theme.primaryColor}40` }"
                    @click="chooseCover"
                >
                    <tn-icon name="image" size="40" :color="`${$theme.primaryColor}80`" />
                    <text class="upload-text" :style="{ color: `${$theme.primaryColor}80` }">上传封面</text>
                </view>
            </view>
        </view>

        <!-- 标签区域 -->
        <view class="tags-card">
            <view class="card-title-row">
                <text class="card-title">标签</text>
                <text class="card-hint">{{ form.tags.length }}/5</text>
            </view>
            <view class="tags-wrap">
                <view
                    v-for="(tag, idx) in form.tags"
                    :key="idx"
                    class="tag-item"
                    :style="{ background: `${$theme.primaryColor}12`, color: $theme.primaryColor }"
                >
                    <text class="tag-text">#{{ tag }}</text>
                    <view class="tag-remove" @click="removeTag(idx)">
                        <tn-icon name="close" size="20" :color="$theme.primaryColor" />
                    </view>
                </view>
                <view
                    v-if="form.tags.length < 5 && !tagInputVisible"
                    class="tag-add"
                    :style="{ borderColor: `${$theme.primaryColor}40`, color: `${$theme.primaryColor}80` }"
                    @click="showTagInput"
                >
                    <tn-icon name="plus" size="24" :color="`${$theme.primaryColor}80`" />
                    <text>添加标签</text>
                </view>
                <view v-if="tagInputVisible" class="tag-input-wrap">
                    <input
                        class="tag-input"
                        v-model="tagInputValue"
                        :focus="tagInputVisible"
                        placeholder="输入标签后回车"
                        maxlength="20"
                        confirm-type="done"
                        @confirm="confirmTag"
                        @blur="confirmTag"
                        :style="{ borderColor: $theme.primaryColor }"
                    />
                </view>
            </view>
        </view>

        <!-- 定位选择 -->
        <view class="settings-card">
            <view class="setting-row" @click="chooseLocation">
                <view class="setting-left">
                    <tn-icon name="map-pin" size="32" color="#6B7280" />
                    <text class="setting-label">{{ form.location || '添加位置' }}</text>
                </view>
                <view v-if="form.location" class="setting-right" @click.stop="clearLocation">
                    <tn-icon name="close" size="28" color="#9CA3AF" />
                </view>
                <view v-else class="setting-right">
                    <tn-icon name="right" size="28" color="#D1D5DB" />
                </view>
            </view>
        </view>

        <!-- 设置区域 -->
        <view class="settings-card">
            <view class="setting-row">
                <view class="setting-left">
                    <tn-icon name="edit" size="32" color="#6B7280" />
                    <text class="setting-label">允许评论</text>
                </view>
                <u-switch
                    v-model="allowCommentSwitch"
                    :active-color="$theme.primaryColor"
                    inactive-color="#E5E7EB"
                />
            </view>
        </view>

        <!-- 底部操作栏 -->
        <view class="bottom-bar">
            <view
                class="submit-btn"
                :style="{
                    background: canSubmit && !submitting
                        ? $theme.primaryColor
                        : '#D1D5DB',
                    color: canSubmit && !submitting ? '#FFFFFF' : '#9CA3AF'
                }"
                @click="handleSubmit"
            >
                <tn-icon v-if="submitting" name="loading" size="32" color="#9CA3AF" />
                <text>{{ submitting ? '提交中...' : isEdit ? '保存修改' : '发布动态' }}</text>
            </view>
        </view>
    </view>
</template>

<script setup lang="ts">
import { computed, getCurrentInstance, reactive, ref } from 'vue'
import { onLoad } from '@dcloudio/uni-app'
import { uploadImage, uploadVideo } from '@/api/app'
import { staffCenterDynamicAdd, staffCenterDynamicEdit } from '@/api/staffCenter'
import { ensureStaffCenterAccess } from '@/utils/staff-center'
import { useThemeStore } from '@/stores/theme'
import { useAppStore } from '@/stores/app'

const $theme = useThemeStore()
const appStore = useAppStore()

const dynamicTypes = [
    { label: '图文', value: 1, icon: 'image' },
    { label: '视频', value: 2, icon: 'video' }
]

const submitting = ref(false)
const form = reactive({
    id: 0,
    dynamic_type: 1,
    content: '',
    images: [] as string[],
    video_url: '',
    video_cover: '',
    tags: [] as string[],
    allow_comment: 1,
    location: '',
    latitude: 0,
    longitude: 0
})

const isEdit = computed(() => form.id > 0)
const pageTitle = computed(() => (isEdit.value ? '编辑动态' : '发布动态'))
const canSubmit = computed(() => form.content.trim().length > 0)

const allowCommentSwitch = computed({
    get: () => form.allow_comment === 1,
    set: (val: boolean) => {
        form.allow_comment = val ? 1 : 0
    }
})

// 切换类型
const switchType = (value: number) => {
    // 编辑模式下禁止切换类型
    if (isEdit.value) return
    if (value === form.dynamic_type) return
    if (value === 2 && form.images.length > 0) {
        uni.showModal({
            title: '切换提示',
            content: '切换到视频模式将清除已选图片，确定吗？',
            success: (res) => {
                if (res.confirm) {
                    form.images = []
                    form.dynamic_type = value
                }
            }
        })
        return
    }
    if (value === 1 && form.video_url) {
        uni.showModal({
            title: '切换提示',
            content: '切换到图文模式将清除已选视频，确定吗？',
            success: (res) => {
                if (res.confirm) {
                    form.video_url = ''
                    form.video_cover = ''
                    form.dynamic_type = value
                }
            }
        })
        return
    }
    form.dynamic_type = value
}

// 预览图片
const previewImage = (idx: number) => {
    uni.previewImage({
        current: idx,
        urls: form.images
    })
}

const chooseImage = () => {
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
                    if (uploadRes?.uri) form.images.push(uploadRes.uri)
                }
            } catch (e: any) {
                uni.showToast({ title: e?.message || '上传失败', icon: 'none' })
            } finally {
                uni.hideLoading()
            }
        }
    })
}

const removeImage = (idx: number) => {
    form.images.splice(idx, 1)
}

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
                    form.video_url = uploadRes.uri
                }
            } catch (e: any) {
                uni.showToast({ title: e?.message || '上传失败', icon: 'none' })
            } finally {
                uni.hideLoading()
            }
        }
    })
}

const removeVideo = () => {
    form.video_url = ''
    form.video_cover = ''
}

const chooseCover = () => {
    uni.chooseImage({
        count: 1,
        sizeType: ['compressed'],
        sourceType: ['album', 'camera'],
        success: async (res) => {
            uni.showLoading({ title: '上传中...' })
            try {
                const uploadRes: any = await uploadImage(res.tempFilePaths[0])
                if (uploadRes?.uri) form.video_cover = uploadRes.uri
            } catch (e: any) {
                uni.showToast({ title: e?.message || '上传失败', icon: 'none' })
            } finally {
                uni.hideLoading()
            }
        }
    })
}

const removeCover = () => {
    form.video_cover = ''
}

// 标签相关
const tagInputVisible = ref(false)
const tagInputValue = ref('')

const showTagInput = () => {
    tagInputValue.value = ''
    tagInputVisible.value = true
}

const confirmTag = () => {
    const val = tagInputValue.value.trim()
    if (val && form.tags.length < 5 && !form.tags.includes(val)) {
        form.tags.push(val)
    }
    tagInputValue.value = ''
    tagInputVisible.value = false
}

const removeTag = (idx: number) => {
    form.tags.splice(idx, 1)
}

// 定位选择
const chooseLocation = () => {
    uni.chooseLocation({
        success: (res) => {
            form.location = res.name || res.address || ''
            form.latitude = res.latitude || 0
            form.longitude = res.longitude || 0
        },
        fail: () => {
            // 用户取消或定位失败，不做处理
        }
    })
}

const clearLocation = () => {
    form.location = ''
    form.latitude = 0
    form.longitude = 0
}

const handleSubmit = async () => {
    if (!canSubmit.value || submitting.value) return

    const payload: any = {
        content: form.content.trim(),
        dynamic_type: form.dynamic_type,
        allow_comment: form.allow_comment
    }
    if (form.images.length > 0) payload.images = form.images
    if (form.video_url) payload.video_url = form.video_url
    if (form.video_cover) payload.video_cover = form.video_cover
    if (form.tags.length > 0) payload.tags = form.tags.join(',')
    if (form.location) {
        payload.location = form.location
        payload.latitude = form.latitude
        payload.longitude = form.longitude
    }

    submitting.value = true
    try {
        if (isEdit.value) {
            await staffCenterDynamicEdit({ ...payload, id: form.id })
            uni.showToast({ title: '保存成功', icon: 'success' })
        } else {
            await staffCenterDynamicAdd(payload)
            uni.showToast({ title: '发布成功', icon: 'success' })
        }
        setTimeout(() => uni.navigateBack(), 1200)
    } catch (e: any) {
        const msg = typeof e === 'string' ? e : e?.msg || e?.message || '提交失败'
        uni.showToast({ title: msg, icon: 'none' })
    } finally {
        submitting.value = false
    }
}

const fillForm = (data: any) => {
    form.id = Number(data.id || 0)
    form.dynamic_type = Number(data.dynamic_type || 1)
    form.content = data.content || ''
    // 图片URL兼容：相对路径自动拼接域名
    const rawImages = Array.isArray(data.images) ? data.images : []
    form.images = rawImages.map((img: string) => appStore.getImageUrl(img))
    // 视频URL兼容
    const rawVideo = data.video_url || data.video || ''
    form.video_url = rawVideo ? appStore.getImageUrl(rawVideo) : ''
    // 视频封面兼容
    const rawCover = data.video_cover || ''
    form.video_cover = rawCover ? appStore.getImageUrl(rawCover) : ''
    // allow_comment 兼容字符串 "0"
    form.allow_comment = Number(data.allow_comment) === 0 ? 0 : 1
    // 标签回显：兼容数组和逗号分隔字符串
    const rawTags = data.tags_arr || data.tags || []
    if (Array.isArray(rawTags)) {
        form.tags = rawTags.filter((t: string) => t)
    } else if (typeof rawTags === 'string' && rawTags) {
        form.tags = rawTags.split(',').filter((t: string) => t.trim())
    } else {
        form.tags = []
    }
    // 定位回显
    form.location = data.location || ''
    form.latitude = Number(data.latitude || 0)
    form.longitude = Number(data.longitude || 0)
}

onLoad(async (options: any) => {
    if (!(await ensureStaffCenterAccess())) return

    const id = Number(options?.id || 0)
    let loaded = false

    // 优先通过 eventChannel 接收数据
    const instance = getCurrentInstance()
    const channel = instance?.proxy?.getOpenerEventChannel?.()
    channel?.on('detail', (data: any) => {
        fillForm(data)
        loaded = true
    })

    // 兜底：H5 端 eventChannel 可能不可靠，从临时存储读取
    setTimeout(() => {
        if (!loaded && id > 0) {
            try {
                const cached = uni.getStorageSync('_temp_dynamic_detail')
                if (cached) {
                    const data = JSON.parse(cached)
                    if (Number(data.id) === id) {
                        fillForm(data)
                    }
                }
            } catch { /* ignore */ }
            uni.removeStorageSync('_temp_dynamic_detail')
        }
    }, 200)

    if (id > 0 && !form.id) form.id = id
})
</script>

<style lang="scss" scoped>
.page-container {
    min-height: 100vh;
    background: #F4F5F7;
    padding-bottom: 140rpx;
}

/* 类型切换 Tab */
.type-tab-card {
    display: flex;
    margin: 20rpx 24rpx 0;
    background: #FFFFFF;
    border-radius: 24rpx;
    box-shadow: 0 4rpx 24rpx rgba(0, 0, 0, 0.05);
    overflow: hidden;

    &.disabled {
        opacity: 0.6;
        pointer-events: none;
    }
}

.type-tab {
    flex: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8rpx;
    height: 88rpx;
    font-size: 28rpx;
    font-weight: 600;
    color: #9CA3AF;
    border-bottom: 4rpx solid transparent;
    transition: all 0.2s ease;
    cursor: pointer;

    &.active {
        font-weight: 700;
    }

    &:active {
        opacity: 0.7;
    }
}

/* 内容输入卡片 */
.content-card {
    margin: 16rpx 24rpx 0;
    padding: 24rpx 28rpx;
    background: #FFFFFF;
    border-radius: 24rpx;
    box-shadow: 0 4rpx 24rpx rgba(0, 0, 0, 0.05);
}

.content-textarea {
    width: 100%;
    font-size: 30rpx;
    line-height: 1.7;
    color: #1F2937;
}

.content-footer {
    display: flex;
    justify-content: flex-end;
    margin-top: 8rpx;
}

.content-counter {
    font-size: 24rpx;
    color: #D1D5DB;
}

/* 媒体上传卡片 */
.media-card {
    margin: 16rpx 24rpx 0;
    padding: 24rpx 28rpx;
    background: #FFFFFF;
    border-radius: 24rpx;
    box-shadow: 0 4rpx 24rpx rgba(0, 0, 0, 0.05);
}

.card-title-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 20rpx;
}

.card-title {
    font-size: 28rpx;
    font-weight: 700;
    color: #1F2937;
}

.card-hint {
    font-size: 24rpx;
    color: #9CA3AF;
}

/* 图片网格 */
.image-grid {
    display: flex;
    flex-wrap: wrap;
    gap: 12rpx;
}

.image-item {
    position: relative;
    width: calc((100% - 24rpx) / 3);
    aspect-ratio: 1;
    border-radius: 16rpx;
    overflow: hidden;
}

.image-content {
    width: 100%;
    height: 100%;
}

.image-delete {
    position: absolute;
    top: 0;
    right: 0;
    width: 40rpx;
    height: 40rpx;
    background: rgba(0, 0, 0, 0.5);
    border-radius: 0 16rpx 0 12rpx;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
}

.upload-btn {
    width: calc((100% - 24rpx) / 3);
    aspect-ratio: 1;
    border-radius: 16rpx;
    border: 2rpx dashed;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 6rpx;
    cursor: pointer;
    transition: all 0.15s ease;

    &:active {
        opacity: 0.7;
    }
}

.upload-text {
    font-size: 22rpx;
    font-weight: 500;
}

.upload-sub {
    font-size: 20rpx;
    color: #D1D5DB;
    margin-top: 4rpx;
}

/* 视频上传区域 */
.video-upload-area {
    width: 100%;
    height: 320rpx;
    border-radius: 16rpx;
    border: 2rpx dashed;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 10rpx;
    cursor: pointer;
    transition: all 0.15s ease;

    &:active {
        opacity: 0.7;
    }
}

.video-preview {
    position: relative;
    width: 100%;
    border-radius: 16rpx;
    overflow: hidden;
}

.video-player {
    width: 100%;
    height: 400rpx;
}

.video-delete {
    position: absolute;
    top: 12rpx;
    right: 12rpx;
    width: 52rpx;
    height: 52rpx;
    background: rgba(0, 0, 0, 0.5);
    border-radius: 26rpx;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
}

/* 视频封面 */
.cover-section {
    margin-top: 24rpx;
    padding-top: 24rpx;
    border-top: 1rpx solid #F3F4F6;
}

.cover-preview {
    position: relative;
    width: 240rpx;
    height: 320rpx;
    border-radius: 16rpx;
    overflow: hidden;
}

.cover-image {
    width: 100%;
    height: 100%;
}

.cover-delete {
    position: absolute;
    top: 0;
    right: 0;
    width: 40rpx;
    height: 40rpx;
    background: rgba(0, 0, 0, 0.5);
    border-radius: 0 16rpx 0 12rpx;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
}

.cover-upload-btn {
    width: 240rpx;
    height: 160rpx;
    border-radius: 16rpx;
    border: 2rpx dashed;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 8rpx;
    cursor: pointer;
    transition: all 0.15s ease;

    &:active {
        opacity: 0.7;
    }
}

/* 标签区域 */
.tags-card {
    margin: 16rpx 24rpx 0;
    padding: 24rpx 28rpx;
    background: #FFFFFF;
    border-radius: 24rpx;
    box-shadow: 0 4rpx 24rpx rgba(0, 0, 0, 0.05);
}

.tags-wrap {
    display: flex;
    flex-wrap: wrap;
    gap: 12rpx;
    align-items: center;
}

.tag-item {
    display: flex;
    align-items: center;
    gap: 6rpx;
    padding: 8rpx 16rpx;
    border-radius: 24rpx;
    font-size: 24rpx;
    font-weight: 500;
}

.tag-text {
    max-width: 240rpx;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.tag-remove {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 28rpx;
    height: 28rpx;
    cursor: pointer;
}

.tag-add {
    display: flex;
    align-items: center;
    gap: 4rpx;
    padding: 8rpx 16rpx;
    border-radius: 24rpx;
    border: 2rpx dashed;
    font-size: 24rpx;
    cursor: pointer;
    transition: all 0.15s ease;

    &:active {
        opacity: 0.7;
    }
}

.tag-input-wrap {
    flex: 1;
    min-width: 200rpx;
}

.tag-input {
    height: 56rpx;
    padding: 0 16rpx;
    border: 2rpx solid;
    border-radius: 24rpx;
    font-size: 24rpx;
    color: #1F2937;
}

/* 设置区域 */
.settings-card {
    margin: 16rpx 24rpx 0;
    background: #FFFFFF;
    border-radius: 24rpx;
    box-shadow: 0 4rpx 24rpx rgba(0, 0, 0, 0.05);
    overflow: hidden;
}

.setting-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 24rpx 28rpx;
}

.setting-left {
    display: flex;
    align-items: center;
    gap: 12rpx;
}

.setting-label {
    font-size: 28rpx;
    font-weight: 500;
    color: #374151;
}

/* 底部操作栏 */
.bottom-bar {
    position: fixed;
    bottom: 0;
    left: 0;
    right: 0;
    padding: 16rpx 24rpx;
    padding-bottom: calc(16rpx + env(safe-area-inset-bottom));
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(20rpx);
    border-top: 1rpx solid #F3F4F6;
    z-index: 100;
}

.submit-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10rpx;
    height: 88rpx;
    border-radius: 44rpx;
    font-size: 32rpx;
    font-weight: 700;
    transition: all 0.2s ease;
    cursor: pointer;

    &:active {
        opacity: 0.85;
        transform: translateY(2rpx);
    }
}
</style>
