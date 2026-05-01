<template>
    <page-meta :page-style="pageStyle" />

    <PageShell scene="consumer" hasSafeBottom>
        <BaseNavbar title="发布动态" />

        <view class="publish-page wm-page-content">
            <!-- 内容输入 -->

            <view class="publish-card wm-form-block">
                <textarea
                    v-model="form.content"
                    class="publish-card__textarea"
                    placeholder="分享动态内容..."
                    :maxlength="2000"
                    :auto-height="true"
                    style="min-height: 300rpx"
                />

                <view class="publish-card__counter"> {{ form.content.length }}/2000 </view>
            </view>

            <!-- 媒体上传 -->

            <view class="publish-card publish-card--media wm-form-block">
                <view class="publish-card__head">
                    <text class="publish-card__title">添加图片/视频</text>

                    <text class="publish-card__caption">(最多9张图或1个视频)</text>
                </view>

                <!-- 图片上传 -->

                <view v-if="!form.video_url" class="publish-grid">
                    <view v-for="(img, idx) in form.images" :key="idx" class="publish-grid__item">
                        <image :src="img" class="publish-grid__image" mode="aspectFill" />

                        <view class="publish-grid__remove" @click="removeImage(idx)">
                            <tn-icon name="close" color="#fff" size="24" />
                        </view>
                    </view>

                    <view
                        v-if="form.images.length < 9"
                        class="publish-grid__add"
                        @click="chooseImage"
                    >
                        <tn-icon name="plus" size="48" color="#9A9388" />

                        <text class="publish-grid__add-text">添加图片</text>
                    </view>
                </view>

                <!-- 视频上传 -->

                <view v-if="form.images.length === 0" class="publish-video-shell">
                    <view v-if="form.video_url" class="publish-video-shell__preview">
                        <video
                            :src="form.video_url"
                            class="publish-video-shell__video"
                            object-fit="cover"
                        />

                        <view class="publish-video-shell__remove" @click="removeVideo">
                            <tn-icon name="close" color="#fff" size="32" />
                        </view>
                    </view>

                    <view v-else class="publish-grid__add" @click="chooseVideo">
                        <tn-icon name="play-right" size="48" color="#9A9388" />

                        <text class="publish-grid__add-text">添加视频</text>
                    </view>
                </view>
            </view>

            <!-- 动态类型 -->

            <view class="publish-card wm-form-block">
                <view class="publish-card__title">选择类型</view>

                <view class="publish-chip-row">
                    <view
                        v-for="type in dynamicTypes"
                        :key="type.value"
                        class="publish-chip"
                        :class="{ 'publish-chip--active': form.dynamic_type === type.value }"
                        @click="form.dynamic_type = type.value"
                    >
                        {{ type.label }}
                    </view>
                </view>
            </view>

            <!-- 话题标签 -->

            <view class="publish-card wm-form-block">
                <view class="publish-card__head">
                    <text class="publish-card__title">添加话题</text>

                    <text class="publish-card__caption">最多5个</text>
                </view>

                <view class="publish-tag-row">
                    <view v-for="(tag, idx) in form.tags" :key="idx" class="publish-tag">
                        <text>#{{ tag }}</text>

                        <tn-icon name="close" size="24" class="ml-1" @click="removeTag(idx)" />
                    </view>

                    <view
                        v-if="form.tags.length < 5"
                        class="publish-tag publish-tag--adder"
                        @click="showTagInput = true"
                    >
                        <tn-icon name="plus" size="24" class="mr-1" />

                        <text>添加话题</text>
                    </view>
                </view>

                <!-- 热门话题推荐 -->

                <view v-if="hotTags.length > 0">
                    <text class="publish-card__caption">热门话题</text>

                    <view class="publish-hot-row">
                        <view
                            v-for="tag in hotTags"
                            :key="tag.id"
                            class="publish-hot-tag"
                            @click="addHotTag(tag.name)"
                        >
                            #{{ tag.name }}
                        </view>
                    </view>
                </view>
            </view>

            <!-- 位置 -->

            <view class="publish-card publish-card--row wm-form-block">
                <view class="publish-row" @click="chooseLocation">
                    <view class="publish-row__main">
                        <tn-icon name="map" size="36" color="#5F5A50" />

                        <text class="publish-row__text">{{ form.location || '添加位置' }}</text>
                    </view>

                    <tn-icon name="right" size="28" color="#9A9388" />
                </view>
            </view>

            <!-- 可见范围 -->

            <view class="publish-card publish-card--row wm-form-block">
                <view class="publish-row" @click="showVisiblePicker = true">
                    <view class="publish-row__main">
                        <tn-icon name="eye" size="36" color="#5F5A50" />

                        <text class="publish-row__text">谁可以看</text>
                    </view>

                    <view class="publish-row__tail">
                        <text class="publish-row__tail-text">{{ getVisibleText() }}</text>

                        <tn-icon name="right" size="28" />
                    </view>
                </view>
            </view>

            <ActionArea class="publish-page__action" sticky safeBottom>
                <BaseButton
                    block
                    size="lg"
                    :disabled="!canPublish || publishing"
                    :loading="publishing"
                    @click="handlePublish"
                >
                    {{ publishing ? '发布中...' : '发布' }}
                </BaseButton>
            </ActionArea>

            <!-- 添加话题弹窗 -->

            <BaseOverlayMask :show="showTagInput" @close="showTagInput = false" />

            <tn-popup
                v-model="showTagInput"
                open-direction="bottom"
                :safe-area-inset-bottom="true"
                :overlay="false"
                :overlay-closeable="true"
            >
                <view class="publish-sheet wm-form-block">
                    <view class="publish-sheet__title">添加话题</view>

                    <view class="publish-sheet__input-shell">
                        <text class="publish-sheet__hash">#</text>

                        <input
                            v-model="tagInput"
                            class="publish-sheet__input"
                            placeholder="输入话题名称"
                            :maxlength="20"
                            @confirm="confirmAddTag"
                        />
                    </view>

                    <view class="publish-sheet__actions">
                        <button
                            class="publish-sheet__btn publish-sheet__btn--secondary"
                            @click="showTagInput = false"
                        >
                            取消
                        </button>

                        <button
                            class="publish-sheet__btn publish-sheet__btn--primary"
                            @click="confirmAddTag"
                        >
                            确定
                        </button>
                    </view>
                </view>
            </tn-popup>

            <!-- 可见范围选择器 -->

            <tn-action-sheet
                v-model="showVisiblePicker"
                :data="visibleOptions"
                @click="selectVisible"
            />
        </view>
    </PageShell>
</template>

<script setup lang="ts">
import { ref, reactive, computed } from 'vue'

import { onLoad } from '@dcloudio/uni-app'

import { publishDynamic, getHotTags } from '@/api/dynamic'

import { uploadImage, uploadVideo } from '@/api/app'

import { useThemeStore } from '@/stores/theme'

import { DYNAMIC_LIST_REFRESH_KEY } from '@/enums/constantEnums'

import cache from '@/utils/cache'

import {
    ensureMiniProgramReviewModeConfig,
    isMiniProgramReviewMode,
    leaveBlockedMiniProgramReviewPage,
    showMiniProgramReviewModeTip
} from '@/utils/miniProgramReviewMode'

const $theme = useThemeStore()

const dynamicTypes = [
    { label: '图文', value: 1 },

    { label: '视频', value: 2 },

    { label: '案例', value: 3 },

    { label: '活动', value: 4 }
]

const visibleOptions = [
    { text: '公开', value: 1 },

    { text: '仅粉丝可见', value: 2 },

    { text: '仅自己可见', value: 3 }
]

const form = reactive({
    content: '',

    images: [] as string[],

    video_url: '',

    video_cover: '',

    dynamic_type: 1,

    tags: [] as string[],

    location: '',

    longitude: '',

    latitude: '',

    visible: 1
})

const hotTags = ref<any[]>([])

const showTagInput = ref(false)

const tagInput = ref('')

const showVisiblePicker = ref(false)

const publishing = ref(false)

const miniProgramReviewMode = computed(() => isMiniProgramReviewMode())

const pageStyle = computed(() => {
    const base = String($theme.pageStyle || '').trim()

    const separator = !base || base.endsWith(';') ? '' : ';'

    const locked = showTagInput.value || showVisiblePicker.value

    return `${base}${separator}overflow:${locked ? 'hidden' : 'visible'};`
})

const canPublish = computed(() => {
    return form.content.trim().length > 0 || form.images.length > 0 || Boolean(form.video_url)
})

const getVisibleText = () => {
    const item = visibleOptions.find((v) => v.value === form.visible)

    return item?.text || '公开'
}

const fetchHotTags = async () => {
    try {
        const res = await getHotTags()

        hotTags.value = res || []
    } catch (e) {
        console.error(e)
    }
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

                    if (uploadRes?.uri) {
                        form.images.push(uploadRes.uri)
                    }
                }

                // 选择了图片，自动切换为图文类型

                if (form.dynamic_type === 2) {
                    form.dynamic_type = 1
                }
            } catch (e: any) {
                uni.showToast({ title: e.message || '上传失败', icon: 'none' })
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

                    form.video_cover = ''

                    // 选择了视频，自动切换为视频类型

                    form.dynamic_type = 2
                }
            } catch (e: any) {
                uni.showToast({ title: e.message || '上传失败', icon: 'none' })
            } finally {
                uni.hideLoading()
            }
        }
    })
}

const removeVideo = () => {
    form.video_url = ''

    form.video_cover = ''

    form.dynamic_type = 1
}

const addHotTag = (name: string) => {
    if (form.tags.length >= 5) {
        uni.showToast({ title: '最多添加5个话题', icon: 'none' })

        return
    }

    if (form.tags.includes(name)) {
        uni.showToast({ title: '话题已存在', icon: 'none' })

        return
    }

    form.tags.push(name)
}

const confirmAddTag = () => {
    const tag = tagInput.value.trim()

    if (!tag) {
        uni.showToast({ title: '请输入话题名称', icon: 'none' })

        return
    }

    if (form.tags.length >= 5) {
        uni.showToast({ title: '最多添加5个话题', icon: 'none' })

        return
    }

    if (form.tags.includes(tag)) {
        uni.showToast({ title: '话题已存在', icon: 'none' })

        return
    }

    form.tags.push(tag)

    tagInput.value = ''

    showTagInput.value = false
}

const removeTag = (idx: number) => {
    form.tags.splice(idx, 1)
}

const chooseLocation = () => {
    uni.chooseLocation({
        success: (res) => {
            form.location = res.name || res.address

            form.longitude = String(res.longitude)

            form.latitude = String(res.latitude)
        }
    })
}

const selectVisible = (index: number) => {
    form.visible = visibleOptions[index].value
}

const handlePublish = async () => {
    if (miniProgramReviewMode.value) {
        showMiniProgramReviewModeTip('小程序送审模式已开启，暂不支持发布动态')
        return
    }

    if (!canPublish.value) return

    if (publishing.value) return

    publishing.value = true

    uni.showLoading({ title: '发布中...' })

    try {
        const params: any = {
            content: form.content.trim(),

            dynamic_type: form.dynamic_type,

            visible: form.visible
        }

        if (form.images.length > 0) {
            params.images = form.images
        }

        if (form.video_url) {
            params.video_url = form.video_url

            if (form.video_cover) {
                params.video_cover = form.video_cover
            }
        }

        if (form.tags.length > 0) {
            params.tags = form.tags
        }

        if (form.location) {
            params.location = form.location

            params.longitude = form.longitude

            params.latitude = form.latitude
        }

        await publishDynamic(params)

        cache.set(DYNAMIC_LIST_REFRESH_KEY, 1)

        uni.showToast({ title: '发布成功' })

        setTimeout(() => {
            uni.navigateBack()
        }, 1500)
    } catch (e: any) {
        uni.showToast({ title: e.message || '发布失败', icon: 'none' })
    } finally {
        publishing.value = false

        uni.hideLoading()
    }
}

onLoad(async () => {
    const reviewModeEnabled = await ensureMiniProgramReviewModeConfig()
    if (reviewModeEnabled) {
        leaveBlockedMiniProgramReviewPage()
        return
    }

    fetchHotTags()
})
</script>

<style lang="scss" scoped>
.publish-page {
    min-height: 100vh;

    background-color: transparent;

    padding-bottom: calc(var(--wm-safe-bottom-action, 160rpx) + 120rpx);
}

.publish-card {
    background: rgba(255, 255, 255, 0.9);
}

.publish-card + .publish-card {
    margin-top: 20rpx;
}

.publish-card__head {
    display: flex;

    align-items: center;

    justify-content: space-between;

    gap: 16rpx;

    margin-bottom: 18rpx;
}

.publish-card__title {
    font-size: 28rpx;

    font-weight: 700;

    color: var(--wm-text-primary, #111111);
}

.publish-card__caption,
.publish-card__counter {
    font-size: 22rpx;

    color: var(--wm-text-secondary, #5f5a50);
}

.publish-card__counter {
    text-align: right;

    margin-top: 12rpx;
}

.publish-card__textarea {
    width: 100%;

    font-size: 28rpx;

    line-height: 1.8;

    color: var(--wm-text-primary, #111111);
}

.publish-grid {
    display: flex;

    flex-wrap: wrap;

    gap: 16rpx;
}

.publish-grid__item,
.publish-grid__add {
    position: relative;

    width: calc((100% - 32rpx) / 3);

    aspect-ratio: 1;

    border-radius: 24rpx;

    overflow: hidden;
}

.publish-grid__image {
    width: 100%;

    height: 100%;
}

.publish-grid__remove,
.publish-video-shell__remove {
    position: absolute;

    top: 12rpx;

    right: 12rpx;

    width: 48rpx;

    height: 48rpx;

    border-radius: 999rpx;

    background: rgba(0, 0, 0, 0.5);

    display: flex;

    align-items: center;

    justify-content: center;
}

.publish-grid__add {
    display: flex;

    flex-direction: column;

    align-items: center;

    justify-content: center;

    background: rgba(248, 247, 242, 0.92);

    border: 1rpx dashed var(--wm-color-border-strong, #d8c28a);
}

.publish-grid__add-text {
    margin-top: 8rpx;

    font-size: 22rpx;

    color: var(--wm-text-secondary, #5f5a50);
}

.publish-video-shell {
    margin-top: 18rpx;
}

.publish-video-shell__preview {
    position: relative;

    width: 100%;

    aspect-ratio: 16 / 9;

    border-radius: 28rpx;

    overflow: hidden;
}

.publish-video-shell__video {
    width: 100%;

    height: 100%;
}

.publish-chip-row,
.publish-tag-row,
.publish-hot-row {
    display: flex;

    flex-wrap: wrap;

    gap: 16rpx;
}

.publish-chip,
.publish-tag,
.publish-hot-tag {
    display: inline-flex;

    align-items: center;

    justify-content: center;

    min-height: 68rpx;

    padding: 0 26rpx;

    border-radius: 999rpx;

    background: rgba(255, 255, 255, 0.9);

    border: 1rpx solid var(--wm-color-border, #e7e2d6);

    font-size: 24rpx;

    color: var(--wm-text-secondary, #5f5a50);
}

.publish-chip--active,
.publish-tag {
    background: var(--wm-color-primary-soft, #f3f2ee);

    border-color: var(--wm-color-border-strong, #d8c28a);

    color: var(--wm-color-primary, #0b0b0b);
}

.publish-tag--adder {
    background: rgba(255, 255, 255, 0.9);

    color: var(--wm-text-secondary, #5f5a50);
}

.publish-hot-row {
    margin-top: 12rpx;
}

.publish-hot-tag {
    min-height: 56rpx;

    padding: 0 20rpx;

    font-size: 22rpx;
}

.publish-row {
    display: flex;

    align-items: center;

    justify-content: space-between;

    gap: 20rpx;
}

.publish-row__main,
.publish-row__tail {
    display: flex;

    align-items: center;

    gap: 12rpx;
}

.publish-row__main {
    min-width: 0;

    flex: 1;
}

.publish-row__text,
.publish-row__tail-text {
    font-size: 26rpx;

    color: var(--wm-text-primary, #111111);
}

.publish-row__tail-text {
    color: var(--wm-text-secondary, #5f5a50);
}

.publish-sheet {
    border-radius: 32rpx 32rpx 0 0;
}

.publish-sheet__title {
    text-align: center;

    font-size: 30rpx;

    font-weight: 700;

    color: var(--wm-text-primary, #111111);

    margin-bottom: 24rpx;
}

.publish-sheet__input-shell {
    display: flex;

    align-items: center;

    border-radius: 24rpx;

    background: rgba(248, 247, 242, 0.92);

    border: 1rpx solid var(--wm-color-border, #e7e2d6);

    padding: 0 20rpx;
}

.publish-sheet__hash {
    font-size: 32rpx;

    color: var(--wm-color-primary, #0b0b0b);
}

.publish-sheet__input {
    flex: 1;

    min-height: 88rpx;

    padding: 0 16rpx;

    font-size: 26rpx;

    color: var(--wm-text-primary, #111111);
}

.publish-sheet__actions {
    display: flex;

    gap: 16rpx;

    margin-top: 24rpx;
}

.publish-sheet__btn {
    flex: 1;

    min-height: 84rpx;

    border-radius: 24rpx;

    font-size: 26rpx;
}

.publish-sheet__btn--secondary {
    background: rgba(248, 247, 242, 0.92);

    color: var(--wm-text-secondary, #5f5a50);
}

.publish-sheet__btn--primary {
    background: linear-gradient(
        135deg,
        var(--wm-color-primary, #0b0b0b) 0%,

        var(--wm-color-secondary, #c8a45d) 100%
    );

    color: #ffffff;
}
</style>
