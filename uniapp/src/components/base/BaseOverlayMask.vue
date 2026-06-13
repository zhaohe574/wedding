<template>
    <view
        v-if="show"
        class="base-overlay-mask"
        :style="maskStyle"
        @tap="handleTap"
        @touchmove.stop.prevent="stopTouchMove"
    />
</template>

<script setup lang="ts">
import { computed } from 'vue'
import type { CSSProperties } from 'vue'

interface Props {
    show: boolean
    zIndex?: number
    background?: string
    closeable?: boolean
}

const props = withDefaults(defineProps<Props>(), {
    zIndex: 20074,
    background: 'var(--wm-color-bg-mask, var(--wm-mask-color, rgba(25, 23, 19, 0.68)))',
    closeable: true
})

const emit = defineEmits<{
    (event: 'click'): void
    (event: 'close'): void
}>()

const maskStyle = computed<CSSProperties>(() => ({
    zIndex: props.zIndex,
    background: props.background
}))

const handleTap = () => {
    emit('click')
    if (props.closeable) {
        emit('close')
    }
}

const stopTouchMove = () => {
    return undefined
}
</script>

<style lang="scss" scoped>
.base-overlay-mask {
    position: fixed;
    inset: 0;
    width: 100vw;
    height: 100vh;
    pointer-events: auto;
    backdrop-filter: blur(6rpx);
    -webkit-backdrop-filter: blur(6rpx);
}

/* #ifdef MP-WEIXIN */
.base-overlay-mask {
    backdrop-filter: none;
    -webkit-backdrop-filter: none;
}
/* #endif */
</style>
