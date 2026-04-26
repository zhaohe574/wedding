<template>
    <view
        :class="shellClass"
        :style="shellStyle"
        :data-scene="shellProtocol.scene"
        :data-source="shellProtocol.source"
        :data-back="shellProtocol.back"
        :data-scope-key="shellProtocol.contract.key"
    >
        <slot />
    </view>
</template>

<script setup lang="ts">
import { computed, watch } from 'vue'
import { useThemeStore } from '@/stores/theme'
import { resolvePageShellProtocol } from '@/utils/page-contract'

interface Props {
    scene?: 'consumer' | 'staff' | 'admin' | ''
    source?: string
    back?: string
    hasTabbar?: boolean
    hasSafeBottom?: boolean
    headerMode?: 'default' | 'transparent'
    tone?: 'default' | 'editorial' | 'workspace' | 'business' | 'form' | 'detail'
    shellStyle?: any
    suppressOverlay?: boolean
}

const props = withDefaults(defineProps<Props>(), {
    scene: '',
    source: '',
    back: '',
    hasTabbar: false,
    hasSafeBottom: false,
    headerMode: 'default',
    tone: 'default',
    shellStyle: '',
    suppressOverlay: false
})
const themeStore = useThemeStore()

const shellProtocol = computed(() =>
    resolvePageShellProtocol({
        declaredScene: props.scene,
        declaredSource: props.source,
        declaredBack: props.back
    })
)

watch(
    () => shellProtocol.value.scene,
    (scene) => {
        themeStore.setScene(scene)
    },
    { immediate: true }
)

const shellClass = computed(() => [
    'wm-page-shell',
    'wm-page',
    `wm-page--${shellProtocol.value.scene}`,
    `wm-page-shell--tone-${props.tone}`,
    {
        'page-with-tabbar-safe-bottom': props.hasTabbar,
        'wm-page-shell--with-tabbar': props.hasTabbar,
        'wm-page-shell--safe-bottom': props.hasSafeBottom,
        'wm-page-shell--header-transparent': props.headerMode === 'transparent',
        'wm-page-shell--header-default': props.headerMode === 'default',
        'wm-page-shell--suppress-overlay': props.suppressOverlay
    }
])
</script>

<style lang="scss" scoped>
.wm-page-shell {
    position: relative;
    width: 100%;
    min-height: 100vh;
    background: var(--wm-color-bg-page, #ffffff);
    color: var(--wm-text-primary, #111111);
    isolation: isolate;
    overflow-x: hidden;

    &::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 260rpx;
        background: linear-gradient(180deg, rgba(255, 255, 255, 0.86) 0%, rgba(255, 255, 255, 0) 100%);
        pointer-events: none;
        opacity: 0;
        transition: opacity var(--wm-motion-base, 220ms) ease;
        z-index: 0;
    }
}

.wm-page-shell--header-transparent::before {
    opacity: 1;
}

.wm-page-shell--tone-editorial::before {
    height: 360rpx;
    background: linear-gradient(180deg, rgba(11, 11, 11, 0.06) 0%, rgba(255, 255, 255, 0) 100%);
    opacity: 1;
}

.wm-page-shell--tone-workspace::before,
.wm-page-shell--tone-business::before,
.wm-page-shell--tone-form::before,
.wm-page-shell--tone-detail::before,
.wm-page--staff::before,
.wm-page--admin::before {
    height: 260rpx;
    background: linear-gradient(180deg, rgba(255, 255, 255, 0.88) 0%, rgba(255, 255, 255, 0) 100%);
    opacity: 1;
}

.wm-page-shell--suppress-overlay::before {
    opacity: 0;
    background: transparent;
}

.wm-page-shell--with-tabbar {
    padding-bottom: var(--wm-safe-bottom-tabbar, calc(177rpx + env(safe-area-inset-bottom)));
}

.wm-page-shell--safe-bottom {
    padding-bottom: var(--wm-safe-bottom-action, calc(150rpx + env(safe-area-inset-bottom)));
}

/* #ifdef MP-WEIXIN */
.wm-page-shell::before {
    transition: none;
}
/* #endif */
</style>
