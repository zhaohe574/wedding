<template>
    <view class="sh">
        <!-- 吸顶后补等高空位，避免内容跳动（这是小程序里替代 position:sticky 的关键一步） -->
        <view v-if="pinned" :style="{ height: innerHeight + 'px' }" aria-hidden="true" />
        <view ref="innerRef" class="sh__inner" :class="{ 'is-pinned': pinned }" :style="innerStyle">
            <slot />
        </view>
    </view>
</template>

<script setup lang="ts">
/**
 * 吸顶容器（05 主持人详情三页签、列表筛选条等）。
 *
 * 为什么不用 position: sticky：
 *   - scroll-view 内部 sticky 在小程序里不生效（UX 文档 R 系列已确认）；
 *   - 页面级 scroll 下 sticky 在部分 iOS/Android WebView 上会被原生导航栏覆盖。
 * 统一做法（不要各写一遍）：
 *   1. 页面用页面级滚动（不用 scroll-view 包裹整页），在 onPageScroll 里把 e.scrollTop 传给本组件；
 *   2. 组件在 onMounted 量一次自身相对页面顶部的距离，scrollTop 越过它就吸顶；
 *   3. 吸顶时渲染一个等高空位，内容不跳动。
 *
 * 契约：
 *  - props.scrollTop 必须来自页面的 onPageScroll（px），组件不自己监听滚动；
 *  - props.top 是吸顶位置（px），通常是「状态栏 + 导航栏」总高，无导航传 0；
 *  - 内容高度变化（如页签换行）后调 ref.remeasure()。
 */
import { computed, nextTick, onMounted, ref, watch, getCurrentInstance } from 'vue'

const props = withDefaults(
    defineProps<{
        /** 页面 @page-scroll / onPageScroll 的滚动距离（px） */
        scrollTop: number
        /** 吸顶停留位置（px），一般传导航总高 */
        top?: number
        /** 吸顶后层级，需低于 nav-bar(99) 与 tabbar(100) */
        zIndex?: number
    }>(),
    { top: 0, zIndex: 60 }
)

const instance = getCurrentInstance()
const innerRef = ref<any>(null)
const pinned = ref(false)
const innerHeight = ref(0)
/** 元素原始位置（相对页面顶部，未滚动时） */
let triggerY = 0

const innerStyle = computed(() =>
    pinned.value
        ? {
              position: 'fixed' as const,
              top: `${props.top}px`,
              left: '0',
              right: '0',
              zIndex: props.zIndex
          }
        : {}
)

function remeasure() {
    const query = uni.createSelectorQuery().in(instance?.proxy as any)
    query
        .select('.sh__inner')
        .boundingClientRect((rect: any) => {
            if (!rect) return
            innerHeight.value = Math.round(rect.height)
            // 未吸顶时 rect.top 即「元素距视口顶部的距离」，此时 scrollTop 为 0（页面首次渲染），
            // 因此可直接作为触发阈值；已吸顶时不再重算，避免 fixed 定位把自己算进去。
            if (!pinned.value) triggerY = Math.round(rect.top) + props.scrollTop
        })
        .exec()
}

watch(
    () => props.scrollTop,
    (v) => {
        if (!triggerY) return
        pinned.value = v >= triggerY - props.top
    }
)

onMounted(() => {
    nextTick(remeasure)
})

defineExpose({ remeasure })
</script>

<style lang="scss" scoped>
.sh {
    position: relative;

    /* 吸顶后必须有底色，否则内容会从它下面透出来 */
    &__inner.is-pinned {
        background: var(--c-surface);
        border-bottom: 1rpx solid var(--c-line);
    }
}
</style>
