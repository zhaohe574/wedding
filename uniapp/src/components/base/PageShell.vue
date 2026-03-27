<template>
    <view :class="shellClass">
        <slot />
    </view>
</template>

<script setup lang="ts">
import { computed, watch } from 'vue'
import { useThemeStore } from '@/stores/theme'

interface Props {
    scene?: 'consumer' | 'staff' | 'admin'
    hasTabbar?: boolean
    hasSafeBottom?: boolean
    headerMode?: 'default' | 'transparent'
}

const props = withDefaults(defineProps<Props>(), {
    scene: 'consumer',
    hasTabbar: false,
    hasSafeBottom: false,
    headerMode: 'default'
})
const themeStore = useThemeStore()

watch(
    () => props.scene,
    (scene) => {
        themeStore.setScene(scene)
    },
    { immediate: true }
)

const shellClass = computed(() => [
    'wm-page-shell',
    'wm-page',
    `wm-page--${props.scene}`,
    {
        'page-with-tabbar-safe-bottom': props.hasTabbar,
        'wm-page-shell--safe-bottom': props.hasSafeBottom
    }
])
</script>

<style lang="scss" scoped>
.wm-page-shell {
    min-height: 100vh;
}

.wm-page-shell--safe-bottom {
    padding-bottom: calc(132rpx + env(safe-area-inset-bottom));
}
</style>
