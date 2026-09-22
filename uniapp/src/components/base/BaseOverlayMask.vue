<template>
    <view
        v-if="show"
        class="base-overlay-mask"
        :style="maskStyle"
        @tap="handleTap"
        @click="handleTap"
        @touchmove.stop.prevent="stopTouchMove"
    />
</template>

<script setup lang="ts">
import { computed, ref } from 'vue'
import type { CSSProperties } from 'vue'

interface Props {
    show: boolean
    zIndex?: number
    background?: string
    closeable?: boolean
}

const props = withDefaults(defineProps<Props>(), {
    zIndex: 20074,
    background: 'var(--wm-color-bg-mask, var(--wm-mask-color, rgba(18, 16, 14, 0.65)))',
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

let lastTriggerTime = 0

const handleTap = () => {
    const now = Date.now()
    if (now - lastTriggerTime < 180) {
        return
    }
    lastTriggerTime = now

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
    backdrop-filter: blur(10rpx);
    -webkit-backdrop-filter: blur(10rpx);
    transition: opacity 0.2s ease;
}
</style>
