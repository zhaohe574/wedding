<template>
    <view class="qr-box">
        <image
            v-if="qrSrc"
            class="qr-box__img"
            :src="qrSrc"
            mode="aspectFit"
            aria-label="活动入场核销二维码"
        />
    </view>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { buildQrPngDataUrl } from '@/utils/qrcode'

const props = defineProps<{
    value: string
}>()

const qrSrc = computed(() => {
    const val = String(props.value || '').trim()
    return val ? buildQrPngDataUrl(val, 6, 2) : ''
})
</script>

<style lang="scss" scoped>
.qr-box {
    width: 240rpx;
    height: 240rpx;
    padding: var(--sp-2);
    border-radius: var(--r-sm);
    background: var(--c-surface);
    border: 1rpx solid var(--c-primary-border);
    /* 浅色码片从深墨凭证卡上浮起；绘图尺寸与 API 行为保持不变 */
    box-shadow: var(--shadow-card);
    display: flex;
    align-items: center;
    justify-content: center;
    box-sizing: border-box;

    &__img {
        width: 100%;
        height: 100%;
    }
}
</style>
