<template>
    <page-meta :page-style="pageStyle" />
    <PageShell scene="consumer" hasSafeBottom>
        <BaseNavbar title="发布动态" />
        <view class="publish-page">
        <!-- 内容输入 -->
        <view class="bg-white p-4">
            <textarea
                v-model="form.content"
                class="w-full text-base leading-7"
                placeholder="分享你的婚礼故事、心得体验..."
                :maxlength="2000"
                :auto-height="true"
                style="min-height: 300rpx"
            />
            <view class="text-right text-xs text-gray-400 mt-2">
                {{ form.content.length }}/2000
            </view>
        </view>

        <!-- 媒体上传 -->
        <view class="bg-white mt-2 p-4">
            <view class="flex items-center mb-3">
                <text class="text-sm font-medium">添加图片/视频</text>
                <text class="text-xs text-gray-400 ml-2">(图片最多9张，或1个视频)</text>
            </view>

            <!-- 图片上传 -->
            <view v-if="!form.video_url" class="flex flex-wrap gap-2">
                <view
                    v-for="(img, idx) in form.images"
                    :key="idx"
                    class="w-[30%] aspect-square relative"
                >
                    <image :src="img" class="w-full h-full rounded" mode="aspectFill" />
                    <view
                        class="absolute -top-2 -right-2 w-6 h-6 bg-black/50 rounded-full flex items-center justify-center"
                        @click="removeImage(idx)"
                    >
                        <tn-icon name="close" color="#fff" size="24" />
                    </view>
                </view>
                <view
                    v-if="form.images.length < 9"
                    class="w-[30%] aspect-square bg-gray-100 rounded flex flex-col items-center justify-center"
                    @click="chooseImage"
                >
                    <tn-icon name="plus" size="48" color="#999" />
                    <text class="text-xs text-gray-400 mt-1">添加图片</text>
                </view>
            </view>

            <!-- 视频上传 -->
            <view v-if="form.images.length === 0" class="mt-3">
                <view
                    v-if="form.video_url"
                    class="relative w-full aspect-video rounded overflow-hidden"
                >
                    <video :src="form.video_url" class="w-full h-full" object-fit="cover" />
                    <view
                        class="absolute top-2 right-2 w-8 h-8 bg-black/50 rounded-full flex items-center justify-center"
                        @click="removeVideo"
                    >
                        <tn-icon name="close" color="#fff" size="32" />
                    </view>
                </view>
                <view
                    v-else
                    class="w-[30%] aspect-square bg-gray-100 rounded flex flex-col items-center justify-center"
                    @click="chooseVideo"
                >
                    <tn-icon name="play-right" size="48" color="#999" />
                    <text class="text-xs text-gray-400 mt-1">添加视频</text>
                </view>
            </view>
        </view>

        <!-- 动态类型 -->
        <view class="bg-white mt-2 p-4">
            <view class="text-sm font-medium mb-3">选择类型</view>
            <view class="flex flex-wrap gap-3">
                <view
                    v-for="type in dynamicTypes"
                    :key="type.value"
                    class="px-4 py-2 rounded-full text-sm"
                    :class="
                        form.dynamic_type === type.value
                            ? 'bg-primary text-white'
                            : 'bg-gray-100 text-gray-600'
                    "
                    @click="form.dynamic_type = type.value"
                >
                    {{ type.label }}
                </view>
            </view>
        </view>

        <!-- 话题标签 -->
        <view class="bg-white mt-2 p-4">
            <view class="flex items-center justify-between mb-3">
                <text class="text-sm font-medium">添加话题</text>
                <text class="text-xs text-gray-400">最多5个</text>
            </view>
            <view class="flex flex-wrap gap-2 mb-3">
                <view
                    v-for="(tag, idx) in form.tags"
                    :key="idx"
                    class="flex items-center bg-primary/10 text-primary px-3 py-1 rounded-full text-sm"
                >
                    <text>#{{ tag }}</text>
                    <tn-icon name="close" size="24" class="ml-1" @click="removeTag(idx)" />
                </view>
                <view
                    v-if="form.tags.length < 5"
                    class="flex items-center bg-gray-100 text-gray-500 px-3 py-1 rounded-full text-sm"
                    @click="showTagInput = true"
                >
                    <tn-icon name="plus" size="24" class="mr-1" />
                    <text>添加话题</text>
                </view>
            </view>
            <!-- 热门话题推荐 -->
            <view v-if="hotTags.length > 0">
                <text class="text-xs text-gray-400">热门话题</text>
                <view class="flex flex-wrap gap-2 mt-2">
                    <view
                        v-for="tag in hotTags"
                        :key="tag.id"
                        class="text-xs px-2 py-1 bg-gray-50 text-gray-500 rounded"
                        @click="addHotTag(tag.name)"
                    >
                        #{{ tag.name }}
                    </view>
                </view>
            </view>
        </view>

        <!-- 位置 -->
        <view class="bg-white mt-2">
            <view class="flex items-center justify-between p-4" @click="chooseLocation">
                <view class="flex items-center">
                    <tn-icon name="map" size="36" class="mr-2 text-gray-500" />
                    <text class="text-sm">{{ form.location || '添加位置' }}</text>
                </view>
                <tn-icon name="right" size="28" color="#999" />
            </view>
        </view>

        <!-- 可见范围 -->
        <view class="bg-white mt-2">
            <view class="flex items-center justify-between p-4" @click="showVisiblePicker = true">
                <view class="flex items-center">
                    <tn-icon name="eye" size="36" class="mr-2 text-gray-500" />
                    <text class="text-sm">谁可以看</text>
                </view>
                <view class="flex items-center text-gray-400">
                    <text class="text-sm mr-1">{{ getVisibleText() }}</text>
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
            <view class="p-4">
                <view class="text-center font-medium mb-4">添加话题</view>
                <view class="flex items-center bg-gray-100 rounded-lg px-3">
                    <text class="text-primary text-lg">#</text>
                    <input
                        v-model="tagInput"
                        class="flex-1 py-3 px-2 text-sm"
                        placeholder="输入话题名称"
                        :maxlength="20"
                        @confirm="confirmAddTag"
                    />
                </view>
                <view class="flex gap-3 mt-4">
                    <button
                        class="flex-1 py-2 bg-gray-100 text-gray-600 rounded-lg"
                        @click="showTagInput = false"
                    >
                        取消
                    </button>
                    <button
                        class="flex-1 py-2 bg-primary text-white rounded-lg"
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

onLoad(() => {
    fetchHotTags()
})
</script>

<style lang="scss" scoped>
.publish-page {
    min-height: 100vh;
    background-color: #f5f5f5;
    padding-bottom: calc(var(--wm-safe-bottom-action, 160rpx) + 120rpx);
}
</style>
