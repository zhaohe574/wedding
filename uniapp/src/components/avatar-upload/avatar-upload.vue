<template>
    <button
        class="avatar-upload"
        :style="containerStyles"
        hover-class="none"
        open-type="chooseAvatar"
        @click="chooseAvatar"
        @chooseavatar="chooseAvatar"
    >
        <image 
            class="avatar-image" 
            mode="aspectFill" 
            :src="modelValue" 
            v-if="modelValue" 
        />
        <slot v-else>
            <view
                class="avatar-placeholder"
                :style="containerStyles"
            >
                <tn-icon name="plus" :size="48" color="#94A3B8" />
                <text class="placeholder-text">添加图片</text>
            </view>
        </slot>
    </button>
</template>
<script lang="ts" setup>
import { uploadImage } from '@/api/app'
import { useUserStore } from '@/stores/user'
import { addUnit } from '@/utils/util'
import { isBoolean } from 'lodash'
import { computed, CSSProperties, onUnmounted } from 'vue'

const props = defineProps({
    modelValue: {
        type: String
    },
    fileKey: {
        type: String,
        default: 'uri'
    },
    size: {
        type: [String, Number],
        default: 120
    },
    round: {
        type: [Boolean, String, Number],
        default: false
    },
    border: {
        type: Boolean,
        default: true
    }
})
const emit = defineEmits<{
    (event: 'update:modelValue', value: string): void
}>()
const userStore = useUserStore()
const containerStyles = computed<CSSProperties>(() => {
    const size = addUnit(props.size)
    return {
        width: size,
        height: size,
        borderRadius: isBoolean(props.round) ? (props.round ? '50%' : '16rpx') : addUnit(props.round)
    }
})

const chooseAvatar = (e: any) => {
    // #ifdef MP-WEIXIN
    // 微信小程序使用官方头像选择
    const path = e.detail?.avatarUrl
    if (path) {
        uploadImageIng(path)
    }
    // #endif
    
    // #ifndef MP-WEIXIN
    // 非微信小程序使用系统相册
    uni.chooseImage({
        count: 1,
        sizeType: ['compressed'],
        sourceType: ['album', 'camera'],
        success: (res) => {
            const tempFilePath = res.tempFilePaths[0]
            uploadImageIng(tempFilePath)
        },
        fail: (err) => {
            console.error('选择图片失败:', err)
        }
    })
    // #endif
}

const uploadImageIng = async (file: string) => {
    uni.showLoading({
        title: '正在上传中...'
    })
    try {
        const res: any = await uploadImage(file, userStore.temToken!)
        uni.hideLoading()
        emit('update:modelValue', res[props.fileKey])
        uni.showToast({
            title: '上传成功',
            icon: 'success'
        })
    } catch (error) {
        uni.hideLoading()
        uni.$u.toast(error)
    }
}

onUnmounted(() => {
    // 清理事件监听
})
</script>

<style lang="scss" scoped>
.avatar-upload {
    background: #FFFFFF;
    overflow: hidden;
    padding: 0;
    margin: 0;
    border: none;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 8rpx 24rpx rgba(0, 0, 0, 0.12);
    transition: all 0.2s ease;
    
    &::after {
        border: none;
    }
    
    &:active {
        transform: scale(0.98);
        opacity: 0.9;
    }
    
    .avatar-image {
        width: 100%;
        height: 100%;
        display: block;
    }
    
    .avatar-placeholder {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: 12rpx;
        border: 2rpx dashed #E2E8F0;
        box-sizing: border-box;
        
        .placeholder-text {
            font-size: 24rpx;
            color: #94A3B8;
        }
    }
}
</style>
