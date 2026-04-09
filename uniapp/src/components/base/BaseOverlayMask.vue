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
    background: 'var(--wm-mask-color, rgba(8, 10, 16, 0.58))',
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

const stopTouchMove = () => {}
</script>

<style lang="scss" scoped>
.base-overlay-mask {
    position: fixed;
    inset: 0;
}
</style>
