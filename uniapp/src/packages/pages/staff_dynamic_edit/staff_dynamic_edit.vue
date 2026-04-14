<template>
    <page-meta :page-style="$theme.pageStyle" />
    <PageShell scene="staff">
        <BaseNavbar :title="pageTitle" />

        <view class="page-container">
            <view class="page-section page-section--content">
                <BaseCard variant="glass" scene="staff" class="form-card wm-form-block">
                    <view class="card-head">
                        <text class="card-head__title">类型与内容</text>
                    </view>

                    <view class="field-block">
                        <view class="field-label-row">
                            <text class="field-label field-label--required">动态类型</text>
                            <text class="field-side-text">{{ isEdit ? '已锁定' : '二选一' }}</text>
                        </view>

                        <view :class="['type-chip-row', { 'type-chip-row--locked': isEdit }]">
                            <view
                                v-for="item in dynamicTypes"
                                :key="item.value"
                                :class="[
                                    'type-chip',
                                    { 'type-chip--active': form.dynamic_type === item.value }
                                ]"
                                @click="switchType(item.value)"
                            >
                                <tn-icon
                                    :name="item.icon"
                                    size="28"
                                    :color="
                                        form.dynamic_type === item.value
                                            ? 'var(--wm-color-primary, #E85A4F)'
                                            : '#948F8B'
                                    "
                                />
                                <text class="type-chip__text">{{ item.label }}</text>
                            </view>
                        </view>
                    </view>

                    <view class="field-block">
                        <view class="field-label-row">
                            <text class="field-label field-label--required">动态标题</text>
                            <text class="field-side-text">{{ form.title.length }}/100</text>
                        </view>
                        <view class="field-input-shell wm-soft-card">
                            <tn-input
                                v-model="form.title"
                                placeholder="输入动态标题"
                                :maxlength="100"
                                :border="false"
                                class="field-input"
                            />
                        </view>
                    </view>

                    <view class="field-block">
                        <view class="field-label-row">
                            <text class="field-label field-label--required">正文内容</text>
                            <text class="field-side-text">{{ form.content.length }}/2000</text>
                        </view>
                        <view class="textarea-shell wm-soft-card">
                            <textarea
                                v-model="form.content"
                                class="field-textarea"
                                :placeholder="
                                    form.dynamic_type === 1 ? '输入图文内容' : '输入视频文案'
                                "
                                :maxlength="2000"
                                :auto-height="true"
                                :show-confirm-bar="false"
                            />
                        </view>
                    </view>
                </BaseCard>

                <BaseCard variant="glass" scene="staff" class="form-card wm-form-block">
                    <view class="card-head">
                        <text class="card-head__title">媒体内容</text>
                    </view>

                    <template v-if="form.dynamic_type === 1">
                        <view class="field-block">
                            <view class="field-label-row">
                                <text class="field-label field-label--required">图片</text>
                                <text class="field-side-text">{{ form.images.length }}/9</text>
                            </view>

                            <view class="image-grid">
                                <view
                                    v-for="(image, index) in form.images"
                                    :key="`${image}-${index}`"
                                    class="image-item"
                                >
                                    <image
                                        :src="image"
                                        class="image-item__image"
                                        mode="aspectFill"
                                        @click="previewImage(index)"
                                    />
                                    <view
                                        class="image-item__delete"
                                        @click.stop="removeImage(index)"
                                    >
                                        <tn-icon name="close" size="22" color="#ffffff" />
                                    </view>
                                </view>

                                <view
                                    v-if="form.images.length < 9"
                                    class="upload-panel upload-panel--grid wm-soft-card"
                                    @click="chooseImage"
                                >
                                    <view
                                        class="upload-panel__icon-wrap upload-panel__icon-wrap--sm"
                                    >
                                        <tn-icon
                                            name="image"
                                            size="38"
                                            color="var(--wm-color-primary, #E85A4F)"
                                        />
                                    </view>
                                    <text class="upload-panel__title upload-panel__title--sm">
                                        添加图片
                                    </text>
                                </view>
                            </view>
                        </view>
                    </template>

                    <template v-else>
                        <view class="field-block">
                            <view class="field-label-row">
                                <text class="field-label field-label--required">视频</text>
                                <text class="field-side-text">{{
                                    form.video_url ? '已上传' : '必填'
                                }}</text>
                            </view>

                            <view v-if="form.video_url" class="cover-preview">
                                <video
                                    :src="form.video_url"
                                    class="cover-preview__image"
                                    object-fit="cover"
                                    controls
                                    show-center-play-btn
                                />
                                <view class="cover-preview__toolbar">
                                    <view class="cover-preview__action" @click="chooseVideo">
                                        <tn-icon name="refresh" size="26" color="#ffffff" />
                                        <text class="cover-preview__action-text">更换</text>
                                    </view>
                                    <view class="cover-preview__divider" />
                                    <view class="cover-preview__action" @click="removeVideo">
                                        <tn-icon name="delete" size="26" color="#ffffff" />
                                        <text class="cover-preview__action-text">删除</text>
                                    </view>
                                </view>
                            </view>

                            <view
                                v-else
                                class="upload-panel upload-panel--cover wm-soft-card"
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

                        <view class="field-block">
                            <view class="field-label-row">
                                <text class="field-label">视频封面</text>
                                <text class="field-side-text">{{
                                    form.video_cover ? '已上传' : '选填'
                                }}</text>
                            </view>

                            <view v-if="form.video_cover" class="cover-preview cover-preview--sub">
                                <image
                                    :src="form.video_cover"
                                    class="cover-preview__image"
                                    mode="aspectFill"
                                    @click="previewVideoCover"
                                />
                                <view class="cover-preview__toolbar">
                                    <view class="cover-preview__action" @click="chooseVideoCover">
                                        <tn-icon name="refresh" size="26" color="#ffffff" />
                                        <text class="cover-preview__action-text">更换</text>
                                    </view>
                                    <view class="cover-preview__divider" />
                                    <view class="cover-preview__action" @click="removeVideoCover">
                                        <tn-icon name="delete" size="26" color="#ffffff" />
                                        <text class="cover-preview__action-text">删除</text>
                                    </view>
                                </view>
                            </view>

                            <view
                                v-else
                                class="upload-panel upload-panel--sub wm-soft-card"
                                @click="chooseVideoCover"
                            >
                                <view class="upload-panel__icon-wrap upload-panel__icon-wrap--sm">
                                    <tn-icon
                                        name="image"
                                        size="40"
                                        color="var(--wm-color-primary, #E85A4F)"
                                    />
                                </view>
                                <text class="upload-panel__title upload-panel__title--sm">
                                    上传封面
                                </text>
                            </view>
                        </view>
                    </template>
                </BaseCard>

                <BaseCard variant="glass" scene="staff" class="form-card wm-form-block">
                    <view class="card-head">
                        <text class="card-head__title">更多设置</text>
                    </view>

                    <view class="field-block">
                        <view class="field-label-row">
                            <text class="field-label">标签</text>
                            <text class="field-side-text">{{ form.tags.length }}/5</text>
                        </view>

                        <view class="tags-wrap">
                            <view
                                v-for="(tag, index) in form.tags"
                                :key="`${tag}-${index}`"
                                class="tag-chip"
                            >
                                <text class="tag-chip__text">#{{ tag }}</text>
                                <view class="tag-chip__remove" @click="removeTag(index)">
                                    <tn-icon
                                        name="close"
                                        size="18"
                                        color="var(--wm-color-primary, #E85A4F)"
                                    />
                                </view>
                            </view>

                            <view
                                v-if="form.tags.length < 5 && !tagInputVisible"
                                class="tag-add"
                                @click="showTagInput"
                            >
                                <tn-icon
                                    name="plus"
                                    size="18"
                                    color="var(--wm-color-primary, #E85A4F)"
                                />
                                <text class="tag-add__text">添加标签</text>
                            </view>
                        </view>

                        <view v-if="tagInputVisible" class="tag-input-shell wm-soft-card">
                            <input
                                v-model="tagInputValue"
                                class="tag-input"
                                :focus="tagInputVisible"
                                placeholder="输入标签后确认"
                                maxlength="20"
                                confirm-type="done"
                                @confirm="confirmTag"
                                @blur="confirmTag"
                            />
                        </view>
                    </view>

                    <view class="setting-list">
                        <view class="setting-item">
                            <text class="setting-item__label">位置</text>
                            <view class="setting-item__input">
                                <tn-input
                                    v-model="form.location"
                                    placeholder="例如：三亚海棠湾"
                                    :border="false"
                                    class="setting-input setting-input--right"
                                />
                            </view>
                        </view>

                        <view class="setting-item setting-item--switch">
                            <text class="setting-item__label">允许评论</text>
                            <switch
                                :checked="allowCommentSwitch"
                                :color="$theme.primaryColor"
                                style="transform: scale(0.9)"
                                @change="allowCommentSwitch = $event.detail.value"
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
                        :style="{ opacity: canSave && !saving ? 1 : 0.66 }"
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
import { uploadImage, uploadVideo } from '@/api/app'
import {
    staffCenterDynamicAdd,
    staffCenterDynamicDetail,
    staffCenterDynamicEdit
} from '@/api/staffCenter'
import BaseCard from '@/components/base/BaseCard.vue'
import BaseNavbar from '@/components/base/BaseNavbar.vue'
import PageShell from '@/components/base/PageShell.vue'
import { useAppStore } from '@/stores/app'
import { useThemeStore } from '@/stores/theme'
import { ensureStaffCenterAccess } from '@/packages/common/utils/staff-center'

const $theme = useThemeStore()
const appStore = useAppStore()

const dynamicTypes = [
    { label: '图文', value: 1, icon: 'image' },
    { label: '视频', value: 2, icon: 'video' }
]

const saving = ref(false)
const tagInputVisible = ref(false)
const tagInputValue = ref('')

const form = reactive({
    id: 0,
    dynamic_type: 1,
    title: '',
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
const saveButtonText = computed(() => {
    if (saving.value) {
        return isEdit.value ? '保存中...' : '发布中...'
    }
    return isEdit.value ? '保存修改' : '发布动态'
})
const canSave = computed(() => {
    const hasBaseContent =
        normalizeTextValue(form.title) !== '' && normalizeTextValue(form.content) !== ''
    if (!hasBaseContent) {
        return false
    }
    if (form.dynamic_type === 1) {
        return form.images.length > 0
    }
    return normalizeTextValue(form.video_url) !== ''
})

const allowCommentSwitch = computed({
    get: () => form.allow_comment === 1,
    set: (value: boolean) => {
        form.allow_comment = value ? 1 : 0
    }
})

const normalizeTextValue = (value: unknown) => String(value ?? '').trim()

const normalizeMediaUrl = (value: unknown) => {
    const text = normalizeTextValue(value)
    if (!text) {
        return ''
    }
    if (/^(https?:)?\/\//.test(text)) {
        return text
    }
    return appStore.getImageUrl(text)
}

const fillForm = (data: any) => {
    form.id = Number(data.id || 0)
    form.dynamic_type = Number(data.dynamic_type || 1) === 2 ? 2 : 1
    form.title = data.title || ''
    form.content = data.content || ''
    form.images = Array.isArray(data.images)
        ? data.images.map((item: string) => normalizeMediaUrl(item)).filter(Boolean)
        : []
    form.video_url = normalizeMediaUrl(data.video_url || data.video || '')
    form.video_cover = normalizeMediaUrl(data.video_cover || '')
    form.allow_comment = Number(data.allow_comment) === 0 ? 0 : 1

    const rawTags = data.tags_arr || data.tags || []
    if (Array.isArray(rawTags)) {
        form.tags = rawTags.map((item: string) => normalizeTextValue(item)).filter(Boolean)
    } else if (typeof rawTags === 'string' && rawTags) {
        form.tags = rawTags
            .split(',')
            .map((item: string) => normalizeTextValue(item))
            .filter(Boolean)
    } else {
        form.tags = []
    }

    form.location = data.location || ''
    form.latitude = Number(data.latitude || 0)
    form.longitude = Number(data.longitude || 0)
}

const loadCachedDetail = (id: number) => {
    try {
        const cached = uni.getStorageSync('_temp_dynamic_detail')
        if (!cached) {
            return false
        }
        const data = JSON.parse(cached)
        if (Number(data?.id || 0) !== id) {
            return false
        }
        fillForm(data)
        return true
    } catch {
        return false
    } finally {
        uni.removeStorageSync('_temp_dynamic_detail')
    }
}

const loadDetail = async (id: number) => {
    try {
        const data = await staffCenterDynamicDetail({ id })
        fillForm(data || {})
        uni.removeStorageSync('_temp_dynamic_detail')
    } catch (error: any) {
        const loaded = loadCachedDetail(id)
        if (!loaded && !form.title && !form.content) {
            const msg =
                typeof error === 'string'
                    ? error
                    : error?.msg || error?.message || '加载动态详情失败'
            uni.showToast({ title: msg, icon: 'none' })
        }
    }
}

const switchType = (value: number) => {
    if (isEdit.value || value === form.dynamic_type) {
        return
    }

    if (value === 2 && form.images.length > 0) {
        uni.showModal({
            title: '切换提示',
            content: '切换到视频模式会清空当前图片，是否继续？',
            success: (res) => {
                if (!res.confirm) {
                    return
                }
                form.images = []
                form.dynamic_type = 2
            }
        })
        return
    }

    if (value === 1 && form.video_url) {
        uni.showModal({
            title: '切换提示',
            content: '切换到图文模式会清空当前视频，是否继续？',
            success: (res) => {
                if (!res.confirm) {
                    return
                }
                form.video_url = ''
                form.video_cover = ''
                form.dynamic_type = 1
            }
        })
        return
    }

    form.dynamic_type = value === 2 ? 2 : 1
}

const previewImage = (index: number) => {
    uni.previewImage({
        current: index,
        urls: form.images
    })
}

const previewVideoCover = () => {
    if (!form.video_cover) {
        return
    }
    uni.previewImage({
        urls: [form.video_cover],
        current: form.video_cover
    })
}

const chooseImage = () => {
    const count = Math.max(9 - form.images.length, 1)
    uni.chooseImage({
        count,
        sizeType: ['compressed'],
        sourceType: ['album', 'camera'],
        success: async (res) => {
            uni.showLoading({ title: '上传中...' })
            try {
                for (const path of res.tempFilePaths) {
                    const uploadRes: any = await uploadImage(path)
                    const url = normalizeMediaUrl(uploadRes?.uri || uploadRes?.url || '')
                    if (url) {
                        form.images.push(url)
                    }
                }
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

const removeImage = (index: number) => {
    form.images.splice(index, 1)
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
                const url = normalizeMediaUrl(uploadRes?.uri || uploadRes?.url || '')
                if (url) {
                    form.video_url = url
                }
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

const removeVideo = () => {
    form.video_url = ''
    form.video_cover = ''
}

const chooseVideoCover = () => {
    uni.chooseImage({
        count: 1,
        sizeType: ['compressed'],
        sourceType: ['album', 'camera'],
        success: async (res) => {
            uni.showLoading({ title: '上传中...' })
            try {
                const uploadRes: any = await uploadImage(res.tempFilePaths[0])
                const url = normalizeMediaUrl(uploadRes?.uri || uploadRes?.url || '')
                if (url) {
                    form.video_cover = url
                }
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

const removeVideoCover = () => {
    form.video_cover = ''
}

const showTagInput = () => {
    tagInputValue.value = ''
    tagInputVisible.value = true
}

const confirmTag = () => {
    const value = normalizeTextValue(tagInputValue.value)
    if (value && form.tags.length < 5 && !form.tags.includes(value)) {
        form.tags.push(value)
    }
    tagInputValue.value = ''
    tagInputVisible.value = false
}

const removeTag = (index: number) => {
    form.tags.splice(index, 1)
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
    if (!canSave.value || saving.value) {
        if (!normalizeTextValue(form.title)) {
            uni.showToast({ title: '请输入动态标题', icon: 'none' })
            return
        }
        if (!normalizeTextValue(form.content)) {
            uni.showToast({ title: '请输入动态内容', icon: 'none' })
            return
        }
        if (form.dynamic_type === 1 && form.images.length <= 0) {
            uni.showToast({ title: '请至少上传一张图片', icon: 'none' })
            return
        }
        if (form.dynamic_type === 2 && !normalizeTextValue(form.video_url)) {
            uni.showToast({ title: '请上传视频', icon: 'none' })
            return
        }
        return
    }

    const locationText = normalizeTextValue(form.location)
    const payload = {
        title: normalizeTextValue(form.title),
        content: normalizeTextValue(form.content),
        dynamic_type: form.dynamic_type,
        images: form.dynamic_type === 1 ? form.images : [],
        video_url: form.dynamic_type === 2 ? normalizeTextValue(form.video_url) : '',
        video_cover: form.dynamic_type === 2 ? normalizeTextValue(form.video_cover) : '',
        tags: form.tags.join(','),
        allow_comment: form.allow_comment,
        location: locationText,
        latitude: 0,
        longitude: 0
    }

    saving.value = true
    try {
        if (isEdit.value) {
            await staffCenterDynamicEdit({
                id: form.id,
                ...payload
            })
            uni.showToast({ title: '保存成功，已重新提交审核', icon: 'success' })
        } else {
            await staffCenterDynamicAdd(payload)
            uni.showToast({ title: '发布成功，待后台审核', icon: 'success' })
        }
        setTimeout(() => uni.navigateBack(), 1200)
    } catch (error: any) {
        const msg = typeof error === 'string' ? error : error?.msg || error?.message || '保存失败'
        uni.showToast({ title: msg, icon: 'none' })
    } finally {
        saving.value = false
    }
}

onLoad(async (options: any) => {
    if (!(await ensureStaffCenterAccess())) {
        return
    }

    const id = Number(options?.id || 0)
    const instance = getCurrentInstance()
    const channel = instance?.proxy?.getOpenerEventChannel?.()
    channel?.on('detail', (data: any) => fillForm(data))

    if (id > 0) {
        form.id = id
        await loadDetail(id)
    } else {
        uni.removeStorageSync('_temp_dynamic_detail')
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
    color: var(--wm-text-primary, #1e2432);
}

.field-block + .field-block {
    margin-top: 22rpx;
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
.textarea-shell,
.tag-input-shell {
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

.field-input {
    width: 100%;
}

.textarea-shell {
    padding: 22rpx 24rpx;
}

.field-input :deep(.tn-input) {
    background: transparent !important;
}

.field-input :deep(.input-placeholder) {
    color: #b4aca8 !important;
}

.field-input :deep(.input-text) {
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

.type-chip-row {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 12rpx;
}

.type-chip-row--locked {
    opacity: 0.82;
}

.type-chip {
    min-height: 84rpx;
    padding: 0 22rpx;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10rpx;
    border-radius: 28rpx;
    background: rgba(255, 255, 255, 0.82);
    border: 1rpx solid var(--wm-color-border, #efe6e1);
    transition: all var(--wm-motion-base, 220ms) ease;

    &:active {
        transform: translateY(2rpx);
        opacity: 0.92;
    }
}

.type-chip--active {
    background: var(--wm-color-primary-soft, #fff1ee);
    border-color: var(--wm-color-border-strong, #f4c7bf);
    box-shadow: 0 12rpx 24rpx rgba(232, 90, 79, 0.12);
}

.type-chip__text {
    font-size: 26rpx;
    font-weight: 700;
    line-height: 1;
    color: var(--wm-text-primary, #1e2432);
}

.type-chip--active .type-chip__text {
    color: var(--wm-color-primary, #e85a4f);
}

.image-grid {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 12rpx;
}

.image-item,
.upload-panel--grid {
    aspect-ratio: 1;
}

.image-item {
    position: relative;
    overflow: hidden;
    border-radius: 26rpx;
    background: #f7f1ed;
}

.image-item__image {
    width: 100%;
    height: 100%;
    display: block;
}

.image-item__delete {
    position: absolute;
    top: 10rpx;
    right: 10rpx;
    width: 42rpx;
    height: 42rpx;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 999rpx;
    background: rgba(19, 24, 35, 0.48);
    backdrop-filter: blur(10rpx);
    -webkit-backdrop-filter: blur(10rpx);
}

.cover-preview,
.upload-panel--cover {
    height: 392rpx;
}

.cover-preview--sub,
.upload-panel--sub {
    height: 276rpx;
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

.upload-panel--grid {
    min-height: 0;
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

.upload-panel__icon-wrap--sm {
    width: 82rpx;
    height: 82rpx;
}

.upload-panel__title {
    font-size: 28rpx;
    font-weight: 700;
    line-height: 1.2;
    color: var(--wm-text-primary, #1e2432);
}

.upload-panel__title--sm {
    font-size: 24rpx;
}

.tags-wrap {
    display: flex;
    flex-wrap: wrap;
    gap: 12rpx;
}

.tag-chip,
.tag-add {
    min-height: 56rpx;
    padding: 0 18rpx;
    display: inline-flex;
    align-items: center;
    gap: 6rpx;
    border-radius: 999rpx;
}

.tag-chip {
    background: var(--wm-color-primary-soft, #fff1ee);
    border: 1rpx solid var(--wm-color-border-strong, #f4c7bf);
}

.tag-chip__text,
.tag-add__text {
    font-size: 24rpx;
    font-weight: 700;
    color: var(--wm-color-primary, #e85a4f);
}

.tag-chip__remove {
    width: 26rpx;
    height: 26rpx;
    display: flex;
    align-items: center;
    justify-content: center;
}

.tag-add {
    background: rgba(255, 255, 255, 0.82);
    border: 1rpx dashed rgba(244, 199, 191, 0.88);
}

.tag-input-shell {
    margin-top: 16rpx;
    padding: 0 20rpx;
}

.tag-input {
    width: 100%;
    height: 84rpx;
    font-size: 26rpx;
    color: var(--wm-text-primary, #1e2432);
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

.setting-item--switch {
    cursor: default;
}

.setting-item__label {
    flex-shrink: 0;
    font-size: 28rpx;
    font-weight: 600;
    line-height: 1.3;
    color: var(--wm-text-primary, #1e2432);
}

.setting-item__value {
    flex: 1;
    min-width: 0;
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: 12rpx;
}

.setting-item__value--link {
    cursor: pointer;
}

.setting-item__input {
    flex: 1;
    min-width: 0;
    display: flex;
    justify-content: flex-end;
}

.setting-input {
    width: 100%;
}

.setting-input :deep(.tn-input) {
    background: transparent !important;
}

.setting-input :deep(.input-placeholder) {
    color: #b4aca8 !important;
}

.setting-input :deep(.input-text),
.setting-input :deep(.input-placeholder) {
    font-size: 26rpx !important;
    text-align: right !important;
}

.setting-input :deep(.input-text) {
    color: #1e2432 !important;
}

.setting-item__text {
    font-size: 26rpx;
    font-weight: 600;
    line-height: 1.4;
    color: var(--wm-text-primary, #1e2432);
    text-align: right;
    word-break: break-all;
}

.setting-item__text--placeholder {
    color: #b4aca8;
}

.setting-item__clear {
    width: 34rpx;
    height: 34rpx;
    display: flex;
    align-items: center;
    justify-content: center;
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

@media (max-width: 720rpx) {
    .type-chip-row {
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
