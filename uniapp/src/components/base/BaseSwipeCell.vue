<template>
    <view
        class="base-swipe-cell"
        @touchstart="handleTouchStart"
        @touchmove="handleTouchMove"
        @touchend="handleTouchEnd"
        @touchcancel="handleTouchEnd"
    >
        <view class="base-swipe-cell__actions" :style="actionsStyle">
            <view
                v-for="(option, index) in options"
                :key="index"
                class="base-swipe-cell__action"
                :style="option.style"
                @tap.stop="handleAction"
            >
                {{ option.text }}
            </view>
        </view>
        <view class="base-swipe-cell__body" :style="bodyStyle" @tap="handleBodyTap">
            <slot />
        </view>
    </view>
</template>

<script setup lang="ts">
import { computed, ref, watch } from 'vue'

interface SwipeOption {
    text: string
    style?: Record<string, string>
}

interface Props {
    show?: boolean
    index?: number
    options?: SwipeOption[]
    btnWidth?: number | string
    bodyBackground?: string
}

const props = withDefaults(defineProps<Props>(), {
    show: false,
    index: 0,
    options: () => [],
    btnWidth: 148,
    bodyBackground: 'transparent'
})

const emit = defineEmits<{
    (event: 'click', index: number): void
    (event: 'update:show', value: boolean): void
}>()

const offset = ref(0)
const dragging = ref(false)
let startX = 0
let startY = 0
let startOffset = 0
let horizontalLocked = false

const btnWidthPx = computed(() => uni.upx2px(Number(props.btnWidth) || 0))
const maxOffset = computed(() => -btnWidthPx.value * props.options.length)

const actionsStyle = computed(() => ({
    width: `${(Number(props.btnWidth) || 0) * props.options.length}rpx`
}))

const bodyStyle = computed(() => ({
    transform: `translateX(${offset.value}px)`,
    transition: dragging.value ? 'none' : 'transform 0.2s ease',
    background: props.bodyBackground
}))

const clamp = (value: number) => Math.min(0, Math.max(maxOffset.value, value))

const close = () => {
    offset.value = 0
    if (props.show) emit('update:show', false)
}

watch(
    () => props.show,
    (visible) => {
        if (dragging.value) return
        offset.value = visible ? maxOffset.value : 0
    }
)

const handleTouchStart = (event: any) => {
    const touch = event.touches?.[0]
    if (!touch) return
    startX = touch.clientX
    startY = touch.clientY
    startOffset = offset.value
    horizontalLocked = false
    dragging.value = true
}

const handleTouchMove = (event: any) => {
    const touch = event.touches?.[0]
    if (!touch) return
    const deltaX = touch.clientX - startX
    const deltaY = touch.clientY - startY

    if (!horizontalLocked) {
        if (Math.abs(deltaX) < 6) return
        if (Math.abs(deltaX) <= Math.abs(deltaY)) {
            dragging.value = false
            return
        }
        horizontalLocked = true
    }

    offset.value = clamp(startOffset + deltaX)
}

const handleTouchEnd = () => {
    if (!dragging.value) return
    dragging.value = false
    if (!horizontalLocked) return
    const shouldOpen = offset.value < maxOffset.value / 2
    offset.value = shouldOpen ? maxOffset.value : 0
    if (props.show !== shouldOpen) emit('update:show', shouldOpen)
}

const handleAction = () => {
    close()
    emit('click', props.index)
}

const handleBodyTap = () => {
    if (offset.value !== 0) close()
}
</script>

<style lang="scss" scoped>
.base-swipe-cell {
    position: relative;
    overflow: hidden;

    &__actions {
        position: absolute;
        top: 0;
        right: 0;
        bottom: 0;
        display: flex;
    }

    &__action {
        display: flex;
        align-items: center;
        justify-content: center;
        flex: 1;
        font-size: var(--wm-font-meta, 24rpx);
        color: #FFFDF8;
        background: var(--wm-color-secondary-strong, #7D4C35);
    }

    &__body {
        position: relative;
        z-index: 1;
    }
}
</style>
