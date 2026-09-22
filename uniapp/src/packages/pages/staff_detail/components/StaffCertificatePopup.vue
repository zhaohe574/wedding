<template>
    <view>
        <BaseOverlayMask :show="modelValue" @close="closePopup" />

        <tn-popup
            :value="modelValue"
            open-direction="bottom"
            :overlay="false"
            :overlay-closeable="true"
            safe-area-inset-bottom
            :radius="28"
            @input="onPopupInput"
        >
            <view
                v-if="certificate"
                class="certificate-popup-shell"
                @touchmove.stop.prevent="stopPageTouchMove"
            >
                <scroll-view
                    scroll-y
                    enhanced
                    class="certificate-popup__scroll"
                    @touchmove.stop="stopPanelTouchMove"
                >
                    <view class="certificate-popup">
                        <view class="certificate-popup__header">
                            <view class="certificate-popup__badge">
                                <text class="certificate-popup__badge-text">资质详情</text>
                            </view>

                            <text class="certificate-popup__title">
                                {{ certificate.name || '未命名证书' }}
                            </text>

                            <text class="certificate-popup__desc">
                                {{ getCertificateStatusText(certificate) }}
                            </text>
                        </view>

                        <image
                            v-if="certificate.image"
                            :src="certificate.image"
                            mode="aspectFill"
                            class="certificate-popup__image"
                            lazy-load
                            @click="previewImage(certificate.image)"
                        />

                        <view class="certificate-popup__meta-list">
                            <view class="certificate-popup__meta-item">
                                <text class="certificate-popup__meta-label">证书类型</text>
                                <text class="certificate-popup__meta-value">
                                    {{ formatField(certificate.type) }}
                                </text>
                            </view>

                            <view class="certificate-popup__meta-item">
                                <text class="certificate-popup__meta-label">证书编号</text>
                                <text class="certificate-popup__meta-value">
                                    {{ getSerialNumber(certificate) }}
                                </text>
                            </view>

                            <view class="certificate-popup__meta-item">
                                <text class="certificate-popup__meta-label">发证机构</text>
                                <text class="certificate-popup__meta-value">
                                    {{ formatField(certificate.issue_org) }}
                                </text>
                            </view>

                            <view class="certificate-popup__meta-item">
                                <text class="certificate-popup__meta-label">发证日期</text>
                                <text class="certificate-popup__meta-value">
                                    {{ formatField(certificate.issue_date) }}
                                </text>
                            </view>

                            <view class="certificate-popup__meta-item">
                                <text class="certificate-popup__meta-label">有效期至</text>
                                <text class="certificate-popup__meta-value">
                                    {{ getValidityText(certificate) }}
                                </text>
                            </view>

                            <view class="certificate-popup__meta-item">
                                <text class="certificate-popup__meta-label">当前状态</text>
                                <text class="certificate-popup__meta-value certificate-popup__meta-value--status">
                                    {{ getCertificateStatusText(certificate) }}
                                </text>
                            </view>
                        </view>

                        <view class="certificate-popup__actions">
                            <view
                                class="certificate-popup__btn"
                                :style="primaryBtnStyle"
                                @click="closePopup"
                            >
                                <text class="certificate-popup__btn-text">我知道了</text>
                            </view>
                        </view>
                    </view>
                </scroll-view>
            </view>
        </tn-popup>
    </view>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import BaseOverlayMask from '@/components/base/BaseOverlayMask.vue'

interface Props {
    modelValue: boolean
    certificate?: any
    themeColor?: string
}

const props = withDefaults(defineProps<Props>(), {
    certificate: null,
    themeColor: '#181614'
})

const emit = defineEmits<{
    (e: 'update:modelValue', val: boolean): void
}>()

const primaryBtnStyle = computed(() => ({
    background: props.themeColor || '#181614'
}))

const closePopup = () => {
    emit('update:modelValue', false)
}

const onPopupInput = (val: boolean) => {
    emit('update:modelValue', val)
}

const stopPageTouchMove = () => {}
const stopPanelTouchMove = () => {}

const formatField = (val: any) => {
    if (!val || val === '' || val === 'null' || val === 'undefined') return '暂未登记'
    return String(val)
}

const getSerialNumber = (cert: any) => {
    return cert?.certificate_no || cert?.serial_number || cert?.no || '已核验证书（官方存档）'
}

const getValidityText = (cert: any) => {
    if (cert?.expire_date) return cert.expire_date
    if (cert?.is_permanent) return '长期有效'
    return '以发证机构核验为准'
}

const getCertificateStatusText = (cert: any) => {
    const status = Number(cert?.status ?? 1)
    if (status === 1) return '已实名资质认证'
    if (status === 2) return '审核中'
    if (status === 0) return '已过期'
    return '官方认证有效'
}

const previewImage = (img: string) => {
    if (!img) return
    uni.previewImage({
        urls: [img],
        current: img
    })
}
</script>

<style lang="scss" scoped>
.certificate-popup-shell {
    max-height: 75vh;
    background: #FAF8F2;
    border-top-left-radius: 32rpx;
    border-top-right-radius: 32rpx;
}

.certificate-popup__scroll {
    max-height: 75vh;
}

.certificate-popup {
    padding: 36rpx 32rpx calc(36rpx + env(safe-area-inset-bottom));
    box-sizing: border-box;

    &__header {
        text-align: center;
        margin-bottom: 24rpx;
    }

    &__badge {
        display: inline-block;
        background: #FAF3E5;
        border-radius: 30rpx;
        padding: 6rpx 20rpx;
        margin-bottom: 12rpx;
    }

    &__badge-text {
        font-size: 22rpx;
        color: #8A6D3B;
        font-weight: 600;
    }

    &__title {
        font-size: 34rpx;
        font-weight: 700;
        color: #181614;
    }

    &__desc {
        font-size: 24rpx;
        color: #8E8880;
        margin-top: 8rpx;
    }

    &__image {
        width: 100%;
        height: 380rpx;
        border-radius: 20rpx;
        margin: 20rpx 0;
        background: #F2EFE9;
        border: 1rpx solid rgba(217, 190, 130, 0.25);
    }

    &__meta-list {
        display: flex;
        flex-direction: column;
        gap: 14rpx;
        background: #FFFFFF;
        border-radius: 20rpx;
        padding: 24rpx;
        border: 1rpx solid rgba(217, 190, 130, 0.2);
    }

    &__meta-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        font-size: 24rpx;
    }

    &__meta-label {
        color: #8E8880;
    }

    &__meta-value {
        color: #181614;
        font-weight: 500;

        &--status {
            color: #C6A15B;
            font-weight: 700;
        }
    }

    &__actions {
        margin-top: 28rpx;
    }

    &__btn {
        width: 100%;
        height: 80rpx;
        border-radius: 40rpx;
        background: #181614;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    &__btn-text {
        font-size: 28rpx;
        font-weight: 600;
        color: #FAF8F2;
    }
}
</style>
