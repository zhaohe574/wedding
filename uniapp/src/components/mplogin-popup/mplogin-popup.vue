<template>
    <view>
        <tn-popup
            v-model="showPopup"
            open-direction="bottom"
            :radius="32"
            :overlay-closeable="false"
            safe-area-inset-bottom
        >
            <view class="mp-login-popup">
                <view class="mp-login-popup__body">
                    <view class="mp-login-popup__header">
                        <image
                            v-if="logo"
                            class="mp-login-popup__logo"
                            mode="aspectFit"
                            :src="logo"
                        ></image>
                        <view class="mp-login-popup__brand">
                            <text class="mp-login-popup__title">{{ title || '微信授权登录' }}</text>
                            <text class="mp-login-popup__desc">
                                建议使用您的微信头像和昵称，以便获得更好的体验
                            </text>
                        </view>
                    </view>

                    <form class="mp-login-popup__form" @submit="handleSubmit">
                        <view class="mp-login-popup__field">
                            <view class="mp-login-popup__label">
                                <text class="mp-login-popup__required">*</text>
                                <text>头像</text>
                            </view>
                            <view class="mp-login-popup__avatar">
                                <avatar-upload v-model="avatar" :size="160" :round="24" />
                            </view>
                        </view>

                        <view class="mp-login-popup__field">
                            <view class="mp-login-popup__label">
                                <text class="mp-login-popup__required">*</text>
                                <text>昵称</text>
                            </view>
                            <input
                                class="mp-login-popup__input"
                                name="nickname"
                                type="nickname"
                                placeholder="请输入昵称"
                                placeholder-style="color: #9ca3af;"
                            />
                        </view>

                        <button
                            class="mp-login-popup__submit"
                            hover-class="none"
                            form-type="submit"
                        >
                            确定
                        </button>

                        <view class="mp-login-popup__skip" @click="showPopup = false">
                            暂不登录
                        </view>
                    </form>
                </view>
            </view>
        </tn-popup>
    </view>
</template>

<script lang="ts" setup>
import { computed, ref } from 'vue'
const props = defineProps({
    show: {
        type: Boolean
    },
    logo: {
        type: String
    },
    title: {
        type: String
    }
})
const emit = defineEmits<{
    (event: 'update:show', show: boolean): void
    (event: 'update', value: any): void
}>()

const showPopup = computed({
    get() {
        return props.show
    },
    set(val) {
        emit('update:show', val)
    }
})

const avatar = ref()

const handleSubmit = (e: any) => {
    const { nickname } = e.detail.value
    if (!avatar.value) return uni.$u.toast('请添加头像')
    if (!nickname) return uni.$u.toast('请输入昵称')
    emit('update', {
        avatar: avatar.value,
        nickname
    })
}
</script>

<style lang="scss" scoped>
.mp-login-popup {
    width: 100%;
    max-height: 80vh;
    background: #ffffff;
    box-sizing: border-box;
    overflow-y: auto;
}

.mp-login-popup__body {
    width: 100%;
    padding: 40rpx 32rpx 48rpx;
    box-sizing: border-box;
}

.mp-login-popup__header {
    display: flex;
    align-items: center;
    gap: 24rpx;
}

.mp-login-popup__logo {
    width: 96rpx;
    height: 96rpx;
    flex-shrink: 0;
    border-radius: 24rpx;
    background: #f8fafc;
}

.mp-login-popup__brand {
    flex: 1;
    min-width: 0;
}

.mp-login-popup__title {
    display: block;
    font-size: 36rpx;
    line-height: 1.3;
    font-weight: 700;
    color: #1e2432;
    word-break: break-all;
}

.mp-login-popup__desc {
    display: block;
    margin-top: 12rpx;
    font-size: 26rpx;
    line-height: 1.6;
    color: #6b7280;
}

.mp-login-popup__form {
    margin-top: 36rpx;
}

.mp-login-popup__field {
    padding: 28rpx 0;
    border-bottom: 2rpx solid #f1f5f9;
}

.mp-login-popup__label {
    display: flex;
    align-items: center;
    margin-bottom: 20rpx;
    font-size: 28rpx;
    line-height: 1.4;
    font-weight: 600;
    color: #1f2937;
}

.mp-login-popup__required {
    margin-right: 8rpx;
    color: #ef4444;
}

.mp-login-popup__avatar {
    display: flex;
    align-items: center;
}

.mp-login-popup__input {
    width: 100%;
    height: 88rpx;
    padding: 0 24rpx;
    border: 2rpx solid #e5e7eb;
    border-radius: 20rpx;
    background: #f8fafc;
    font-size: 28rpx;
    color: #1e2432;
    box-sizing: border-box;
}

.mp-login-popup__submit {
    width: 100%;
    height: 88rpx;
    margin-top: 48rpx;
    border: none;
    border-radius: 999rpx;
    background: linear-gradient(
        135deg,
        var(--color-primary, #7c3aed) 0%,
        var(--color-primary-dark-2, #6d28d9) 100%
    );
    color: var(--color-btn-text, #ffffff);
    font-size: 30rpx;
    line-height: 88rpx;
    font-weight: 600;
    box-shadow: 0 12rpx 32rpx rgba(124, 58, 237, 0.24);
}

.mp-login-popup__skip {
    display: flex;
    justify-content: center;
    margin-top: 32rpx;
    font-size: 28rpx;
    line-height: 1.5;
    color: #9ca3af;
}
</style>
