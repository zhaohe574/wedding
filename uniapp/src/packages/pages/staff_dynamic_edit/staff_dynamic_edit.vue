<template>
    <page-meta :page-style="$theme.pageStyle">
        <navigation-bar :title="pageTitle" :front-color="$theme.navColor" :background-color="$theme.navBgColor" />
    </page-meta>

    <view class="min-h-screen bg-[#f6f6f6] pb-[120rpx]">
        <view class="bg-white p-[24rpx]">
            <textarea
                v-model="form.content"
                class="w-full text-base leading-7"
                placeholder="分享你的服务动态..."
                :maxlength="2000"
                :auto-height="true"
                style="min-height: 260rpx"
            />
            <view class="text-right text-xs text-gray-400 mt-[8rpx]">
                {{ form.content.length }}/2000
            </view>
        </view>

        <view class="bg-white mt-[16rpx] p-[24rpx]">
            <view class="flex items-center mb-[12rpx]">
                <text class="text-sm font-medium">添加图片/视频</text>
                <text class="text-xs text-gray-400 ml-[10rpx]">(图片最多9张，或1个视频)</text>
            </view>

            <view v-if="!form.video_url" class="flex flex-wrap gap-[12rpx]">
                <view v-for="(img, idx) in form.images" :key="idx" class="w-[200rpx] h-[200rpx] relative">
                    <image :src="img" class="w-full h-full rounded" mode="aspectFill" />
                    <view
                        class="absolute -top-[8rpx] -right-[8rpx] w-[40rpx] h-[40rpx] bg-black/50 rounded-full flex items-center justify-center"
                        @click="removeImage(idx)"
                    >
                        <tn-icon name="close" size="24" color="#fff" />
                    </view>
                </view>
                <view
                    v-if="form.images.length < 9"
                    class="w-[200rpx] h-[200rpx] rounded bg-[#f4f4f4] flex items-center justify-center"
                    @click="chooseImage"
                >
                    <tn-icon name="plus" size="40" color="#999" />
                </view>
            </view>

            <view v-if="form.images.length === 0" class="mt-[12rpx]">
                <view v-if="form.video_url" class="relative w-full aspect-video rounded overflow-hidden">
                    <video :src="form.video_url" class="w-full h-full" object-fit="cover" />
                    <view
                        class="absolute top-[12rpx] right-[12rpx] w-[48rpx] h-[48rpx] bg-black/50 rounded-full flex items-center justify-center"
                        @click="removeVideo"
                    >
                        <tn-icon name="close" size="28" color="#fff" />
                    </view>
                </view>
                <view
                    v-else
                    class="w-[200rpx] h-[200rpx] rounded bg-[#f4f4f4] flex items-center justify-center"
                    @click="chooseVideo"
                >
                    <tn-icon name="play-right" size="40" color="#999" />
                </view>
            </view>
        </view>

        <view class="bg-white mt-[16rpx] p-[24rpx]">
            <view class="text-sm font-medium mb-[12rpx]">选择类型</view>
            <view class="flex flex-wrap gap-[12rpx]">
                <view
                    v-for="type in dynamicTypes"
                    :key="type.value"
                    class="px-[24rpx] py-[12rpx] rounded-full text-sm"
                    :class="form.dynamic_type === type.value ? 'bg-primary text-white' : 'bg-gray-100 text-gray-500'"
                    @click="form.dynamic_type = type.value"
                >
                    {{ type.label }}
                </view>
            </view>
        </view>

        <view class="bg-white mt-[16rpx] p-[24rpx] flex items-center justify-between">
            <view class="text-sm">允许评论</view>
            <u-switch v-model="allowCommentSwitch" active-color="#16a34a" inactive-color="#e5e7eb" />
        </view>

        <view class="fixed bottom-0 left-0 right-0 bg-white border-t border-gray-100 p-[24rpx]" style="padding-bottom: calc(24rpx + env(safe-area-inset-bottom))">
            <button
                class="w-full py-[18rpx] bg-primary text-white text-base font-medium rounded-full"
                :disabled="!canSubmit || submitting"
                :class="{ 'opacity-50': !canSubmit || submitting }"
                @click="handleSubmit"
            >
                {{ submitting ? '提交中...' : (isEdit ? '保存修改' : '发布动态') }}
            </button>
        </view>
    </view>
</template>

<script setup lang="ts">
import { computed, getCurrentInstance, reactive, ref } from 'vue'
import { onLoad } from '@dcloudio/uni-app'
import { uploadImage } from '@/api/app'
import { staffCenterDynamicAdd, staffCenterDynamicEdit } from '@/api/staffCenter'
import { ensureStaffCenterAccess } from '@/utils/staff-center'

const dynamicTypes = [
    { label: '图文', value: 1 },
    { label: '视频', value: 2 },
    { label: '案例', value: 3 },
    { label: '活动', value: 4 }
]

const submitting = ref(false)
const form = reactive({
    id: 0,
    dynamic_type: 1,
    content: '',
    images: [] as string[],
    video_url: '',
    allow_comment: 1
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
                    if (uploadRes?.url) {
                        form.images.push(uploadRes.url)
                    }
                }
                if (form.images.length > 0 && form.dynamic_type === 2) {
                    form.dynamic_type = 1
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
                const uploadRes: any = await uploadImage(res.tempFilePath)
                if (uploadRes?.url) {
                    form.video_url = uploadRes.url
                    form.dynamic_type = 2
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
    if (form.dynamic_type === 2) {
        form.dynamic_type = 1
    }
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
    form.images = Array.isArray(data.images) ? data.images : []
    form.video_url = data.video_url || data.video || ''
    form.allow_comment = data.allow_comment ? 1 : 0
}

onLoad(async (options: any) => {
    if (!(await ensureStaffCenterAccess())) return
    const instance = getCurrentInstance()
    const channel = instance?.proxy?.getOpenerEventChannel?.()
    channel?.on('detail', (data: any) => {
        fillForm(data)
    })
    if (options?.id && !form.id) {
        form.id = Number(options.id)
    }
})
</script>

<style lang="scss" scoped></style>
