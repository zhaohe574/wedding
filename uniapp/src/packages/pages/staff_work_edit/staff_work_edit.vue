<template>
    <page-meta :page-style="$theme.pageStyle">
        <navigation-bar :title="pageTitle" :front-color="$theme.navColor" :background-color="$theme.navBgColor" />
    </page-meta>

    <view class="min-h-screen bg-[#f6f6f6] pb-[40rpx]">
        <view class="bg-white mx-[24rpx] mt-[24rpx] rounded-lg p-[24rpx]">
            <view class="text-sm font-medium mb-[16rpx]">作品标题</view>
            <tn-input v-model="form.title" placeholder="请输入作品标题" />
        </view>

        <view class="bg-white mx-[24rpx] mt-[20rpx] rounded-lg p-[24rpx]">
            <view class="text-sm font-medium mb-[16rpx]">封面图</view>
            <view class="flex items-center">
                <view class="w-[160rpx] h-[160rpx] rounded-lg bg-[#f2f2f2] overflow-hidden">
                    <image v-if="form.cover" :src="form.cover" class="w-full h-full" mode="aspectFill" />
                    <view v-else class="w-full h-full flex items-center justify-center text-gray-400">暂无封面</view>
                </view>
                <tn-button class="ml-[20rpx]" type="primary" size="sm" shape="round" :plain="true" @click="chooseCover">
                    选择封面
                </tn-button>
            </view>
        </view>

        <view class="bg-white mx-[24rpx] mt-[20rpx] rounded-lg p-[24rpx]">
            <view class="text-sm font-medium mb-[16rpx]">作品图片</view>
            <view class="flex flex-wrap gap-[16rpx]">
                <view v-for="(img, idx) in form.images" :key="idx" class="w-[200rpx] h-[200rpx] relative">
                    <image :src="img" class="w-full h-full rounded-lg" mode="aspectFill" />
                    <view
                        class="absolute -top-[10rpx] -right-[10rpx] w-[40rpx] h-[40rpx] bg-black/50 rounded-full flex items-center justify-center"
                        @click="removeImage(idx)"
                    >
                        <tn-icon name="close" size="24" color="#fff" />
                    </view>
                </view>
                <view
                    v-if="form.images.length < 9"
                    class="w-[200rpx] h-[200rpx] rounded-lg bg-[#f4f4f4] flex items-center justify-center"
                    @click="chooseImages"
                >
                    <tn-icon name="plus" size="40" color="#999" />
                </view>
            </view>
        </view>

        <view class="bg-white mx-[24rpx] mt-[20rpx] rounded-lg p-[24rpx]">
            <view class="text-sm font-medium mb-[16rpx]">作品视频</view>
            <view v-if="form.video" class="relative w-full aspect-video rounded-lg overflow-hidden">
                <video :src="form.video" class="w-full h-full" object-fit="cover" />
                <view
                    class="absolute top-[12rpx] right-[12rpx] w-[48rpx] h-[48rpx] bg-black/50 rounded-full flex items-center justify-center"
                    @click="removeVideo"
                >
                    <tn-icon name="close" size="28" color="#fff" />
                </view>
            </view>
            <tn-button v-else type="primary" size="sm" shape="round" :plain="true" @click="chooseVideo">
                上传视频
            </tn-button>
        </view>

        <view class="bg-white mx-[24rpx] mt-[20rpx] rounded-lg p-[24rpx]">
            <view class="text-sm font-medium mb-[16rpx]">作品说明</view>
            <textarea
                v-model="form.description"
                class="w-full text-sm leading-6 text-[#333] bg-[#fafafa] rounded-lg p-[20rpx]"
                placeholder="请描述作品亮点"
                :maxlength="500"
            />
        </view>

        <view class="bg-white mx-[24rpx] mt-[20rpx] rounded-lg">
            <view class="flex items-center justify-between px-[24rpx] py-[26rpx] border-b border-solid border-0 border-light">
                <view class="text-sm text-[#333]">拍摄日期</view>
                <picker mode="date" :value="form.shoot_date" @change="handleDateChange">
                    <view class="text-sm text-gray-500">
                        {{ form.shoot_date || '请选择日期' }}
                    </view>
                </picker>
            </view>
            <view class="flex items-center justify-between px-[24rpx] py-[26rpx] border-b border-solid border-0 border-light">
                <view class="text-sm text-[#333]">拍摄地点</view>
                <tn-input v-model="form.location" placeholder="请输入地点" class="text-right" />
            </view>
            <view class="flex items-center justify-between px-[24rpx] py-[26rpx] border-b border-solid border-0 border-light">
                <view class="text-sm text-[#333]">排序值</view>
                <tn-input v-model="form.sort" type="number" placeholder="数值越大越靠前" class="text-right" />
            </view>
            <view class="flex items-center justify-between px-[24rpx] py-[26rpx]">
                <view class="text-sm text-[#333]">是否展示</view>
                <u-switch v-model="isShowSwitch" active-color="#16a34a" inactive-color="#e5e7eb" />
            </view>
        </view>

        <view class="mx-[24rpx] mt-[30rpx]">
            <tn-button type="primary" shape="round" size="lg" :loading="submitting" @click="handleSubmit">
                {{ isEdit ? '保存修改' : '提交审核' }}
            </tn-button>
        </view>
    </view>
</template>

<script setup lang="ts">
import { computed, reactive, ref } from 'vue'
import { onLoad } from '@dcloudio/uni-app'
import { uploadImage } from '@/api/app'
import { staffCenterWorkAdd, staffCenterWorkDetail, staffCenterWorkEdit } from '@/api/staffCenter'
import { ensureStaffCenterAccess } from '@/utils/staff-center'

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
                const uploadRes: any = await uploadImage(res.tempFilePath)
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

const removeVideo = () => {
    form.video = ''
}

const handleDateChange = (e: any) => {
    form.shoot_date = e.detail.value
}

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

<style lang="scss" scoped></style>
