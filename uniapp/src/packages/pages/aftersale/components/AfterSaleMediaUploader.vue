<template>
    <view class="aftersale-media">
        <view v-if="variant === 'ticket-evidence'" class="aftersale-media__entry-list">
            <view
                v-for="(entry, index) in resolvedEntryLabels"
                :key="`${entry}-${index}`"
                class="aftersale-media__entry"
                :class="{
                    'is-primary': index === 0,
                    'is-disabled': !canAdd || uploading
                }"
                @click="chooseFile"
            >
                <text class="aftersale-media__entry-text">
                    {{ index === 0 ? addText : entry }}
                </text>
            </view>
        </view>

        <view
            v-if="localList.length || variant !== 'ticket-evidence'"
            class="aftersale-media__grid"
        >
            <view
                v-for="(item, index) in localList"
                :key="`${item}-${index}`"
                class="aftersale-media__item"
            >
                <image
                    v-if="kind === 'image'"
                    :src="item"
                    mode="aspectFill"
                    class="aftersale-media__image"
                    @click="previewImage(index)"
                />
                <view v-else class="aftersale-media__video-card">
                    <video :src="item" class="aftersale-media__video" controls object-fit="cover" />
                </view>
                <view class="aftersale-media__remove" @click="removeItem(index)">
                    <tn-icon name="close" size="20" color="#FFFFFF" />
                </view>
            </view>

            <view
                v-if="canAdd && variant !== 'ticket-evidence'"
                class="aftersale-media__adder"
                :class="{ 'is-uploading': uploading }"
                @click="chooseFile"
            >
                <tn-icon
                    :name="kind === 'image' ? 'camera' : 'play-circle'"
                    size="30"
                    color="#B4ACA8"
                />
                <text class="aftersale-media__adder-title">
                    {{ uploading ? '上传中' : addText }}
                </text>
                <text class="aftersale-media__adder-tip">{{ tipText }}</text>
            </view>
        </view>
    </view>
</template>

<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import { uploadImage, uploadVideo } from '@/api/app'
import { normalizeMediaList, openImagePreview } from '../shared'

interface Props {
    modelValue: string[]
    kind?: 'image' | 'video'
    max?: number
    addText?: string
    tipText?: string
    variant?: 'default' | 'ticket-evidence'
    entryLabels?: string[]
}

const props = withDefaults(defineProps<Props>(), {
    kind: 'image',
    max: 9,
    addText: '添加素材',
    tipText: '支持上传附件',
    variant: 'default',
    entryLabels: () => []
})

const emit = defineEmits<{
    (event: 'update:modelValue', value: string[]): void
    (event: 'uploading-change', value: boolean): void
}>()

const localList = ref<string[]>(normalizeMediaList(props.modelValue))
const uploading = ref(false)

watch(
    () => props.modelValue,
    (value) => {
        localList.value = normalizeMediaList(value)
    }
)

const canAdd = computed(() => localList.value.length < props.max)
const resolvedEntryLabels = computed(() => {
    if (props.entryLabels.length) {
        return props.entryLabels
    }

    return ['上传', '现场照片', '聊天记录']
})

const updateValue = (value: string[]) => {
    emit('update:modelValue', value)
}

const previewImage = (index: number) => {
    if (props.kind !== 'image') {
        return
    }
    openImagePreview(localList.value, index)
}

const removeItem = (index: number) => {
    const nextList = [...localList.value]
    nextList.splice(index, 1)
    localList.value = nextList
    updateValue(nextList)
}

const setUploading = (value: boolean) => {
    uploading.value = value
    emit('uploading-change', value)
}

const uploadFiles = async (paths: string[]) => {
    const nextItems: string[] = []
    try {
        setUploading(true)
        for (const path of paths) {
            const result =
                props.kind === 'image' ? await uploadImage(path) : await uploadVideo(path)
            const url = String(result?.uri || result?.url || '').trim()
            if (url) {
                nextItems.push(url)
            }
        }

        if (!nextItems.length) {
            uni.showToast({ title: '上传失败', icon: 'none' })
            return
        }

        localList.value = [...localList.value, ...nextItems]
        updateValue(localList.value)
    } catch (error: any) {
        uni.showToast({ title: error?.message || '上传失败', icon: 'none' })
    } finally {
        setUploading(false)
    }
}

const chooseFile = () => {
    if (uploading.value || !canAdd.value) {
        return
    }

    if (props.kind === 'image') {
        uni.chooseImage({
            count: Math.max(1, props.max - localList.value.length),
            sizeType: ['compressed'],
            sourceType: ['album', 'camera'],
            success: async (res) => {
                const filePaths = Array.isArray(res.tempFilePaths) ? res.tempFilePaths : []
                await uploadFiles(filePaths)
            }
        })
        return
    }

    uni.chooseVideo({
        sourceType: ['album', 'camera'],
        compressed: true,
        success: async (res) => {
            const path = res.tempFilePath
            if (!path) {
                return
            }
            await uploadFiles([path])
        }
    })
}
</script>

<style lang="scss" scoped>
@import '../../../../styles/aftersale.scss';

.aftersale-media__grid {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 14rpx;
}

.aftersale-media__entry-list {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 10rpx;
}

.aftersale-media__entry {
    min-height: 119rpx;
    border-radius: 34rpx;
    border: 1rpx solid rgba(239, 230, 225, 0.96);
    background: rgba(255, 248, 245, 0.96);
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 0 10rpx;
    box-sizing: border-box;

    &.is-primary {
        background: rgba(255, 241, 238, 0.98);
        border-color: rgba(244, 199, 191, 0.96);
    }

    &.is-disabled {
        opacity: 0.58;
    }
}

.aftersale-media__entry-text {
    font-size: 24rpx;
    line-height: 1.4;
    font-weight: 700;
    color: var(--wm-text-secondary, #7f7b78);
    text-align: center;

    .aftersale-media__entry.is-primary & {
        color: var(--wm-color-primary, #e85a4f);
    }
}

.aftersale-media__item,
.aftersale-media__adder {
    position: relative;
    min-height: 196rpx;
    border-radius: 26rpx;
    overflow: hidden;
}

.aftersale-media__item {
    background: rgba(255, 255, 255, 0.92);
    border: 1rpx solid rgba(239, 230, 225, 0.96);
}

.aftersale-media__image,
.aftersale-media__video-card,
.aftersale-media__video {
    width: 100%;
    height: 196rpx;
    display: block;
}

.aftersale-media__video-card {
    background: #120f0d;
}

.aftersale-media__remove {
    position: absolute;
    top: 12rpx;
    right: 12rpx;
    width: 40rpx;
    height: 40rpx;
    border-radius: 999rpx;
    display: flex;
    align-items: center;
    justify-content: center;
    background: rgba(30, 36, 50, 0.56);
}

.aftersale-media__adder {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 8rpx;
    padding: 20rpx;
    border: 2rpx dashed rgba(239, 230, 225, 0.96);
    background: rgba(255, 247, 244, 0.86);
    box-sizing: border-box;

    &.is-uploading {
        opacity: 0.72;
    }
}

.aftersale-media__adder-title {
    font-size: 24rpx;
    font-weight: 700;
    color: var(--wm-text-primary, #1e2432);
}

.aftersale-media__adder-tip {
    font-size: 20rpx;
    line-height: 1.45;
    color: var(--wm-text-tertiary, #b4aca8);
    text-align: center;
}
</style>
