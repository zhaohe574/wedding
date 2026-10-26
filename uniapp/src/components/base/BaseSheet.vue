<template>
    <view class="base-sheet" :style="rootStyle">
        <slot />
    </view>
</template>

<script setup lang="ts">
import { computed } from 'vue'

interface Props {
    /** 上圆角，单位 rpx */
    radius?: number | string
    /** 向上压住 hero 的距离，单位 rpx；0 表示不上浮 */
    overlap?: number | string
    background?: string
    /** 是否加顶部细高光（营造纸面质感） */
    hairline?: boolean
}

const props = withDefaults(defineProps<Props>(), {
    radius: 44,
    overlap: 48,
    background: 'var(--wm-color-bg-page, #F8F6F0)',
    hairline: true
})

const toRpx = (value: number | string) =>
    typeof value === 'number' || /^\d+(\.\d+)?$/.test(String(value)) ? `${value}rpx` : String(value)

const rootStyle = computed(() => {
    const overlap = Number(props.overlap) || 0
    return {
        marginTop: overlap ? `-${toRpx(overlap)}` : '0',
        borderTopLeftRadius: toRpx(props.radius),
        borderTopRightRadius: toRpx(props.radius),
        background: props.background,
        paddingTop: '40rpx'
    }
})
</script>

<style lang="scss" scoped>
.base-sheet {
    position: relative;
    z-index: 3;
    width: 100%;
    box-sizing: border-box;
}
</style>
