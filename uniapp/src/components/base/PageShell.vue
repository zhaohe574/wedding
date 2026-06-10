<template>
    <view
        :class="shellClass"
        :style="resolvedShellStyle"
        :data-scene="shellProtocol.scene"
        :data-source="shellProtocol.source"
        :data-back="shellProtocol.back"
        :data-scope-key="shellProtocol.contract.key"
    >
        <slot />
    </view>
</template>

<script setup lang="ts">
import type { CSSProperties } from 'vue'
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

const resolvedShellStyle = computed<CSSProperties | string>(() => {
    const toneStyle = { '--wm-page-tone': props.tone } as CSSProperties
    const externalStyle = props.shellStyle

    if (!externalStyle) {
        return toneStyle
    }

    if (typeof externalStyle === 'string') {
        const normalizedStyle = externalStyle.trim()
        const separator = normalizedStyle && !normalizedStyle.endsWith(';') ? ';' : ''
        return `--wm-page-tone:${props.tone};${normalizedStyle}${separator}`
    }

    return {
        ...toneStyle,
        ...externalStyle
    }
})
</script>

<style lang="scss" scoped>
.wm-page-shell {
    position: relative;
    width: 100%;
    min-height: 100vh;
    background: radial-gradient(circle at 12% 0%, rgba(200, 164, 93, 0.12) 0, rgba(200, 164, 93, 0) 320rpx),
        linear-gradient(180deg, #ffffff 0%, var(--wm-color-bg-page, #fbfaf7) 420rpx, var(--wm-color-bg-page, #fbfaf7) 100%);
    color: var(--wm-text-primary, #111111);
    isolation: isolate;
    overflow-x: hidden;
    box-sizing: border-box;

    &::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 340rpx;
        background: radial-gradient(circle at 18% 0%, rgba(200, 164, 93, 0.16) 0, rgba(200, 164, 93, 0) 300rpx),
            linear-gradient(180deg, rgba(255, 255, 255, 0.9) 0%, rgba(255, 255, 255, 0) 100%);
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

.wm-page-shell--tone-editorial {
    --wm-page-section-gap: 30rpx;
    --wm-page-card-density: spacious;
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

.wm-page-shell--tone-workspace {
    --wm-page-section-gap: 20rpx;
    --wm-page-card-density: compact;
}

.wm-page-shell--tone-form {
    --wm-page-section-gap: 22rpx;
    --wm-page-card-density: focused;
}

.wm-page-shell--tone-detail {
    --wm-page-section-gap: 24rpx;
    --wm-page-card-density: readable;
}

.wm-page-shell--suppress-overlay::before {
    opacity: 0;
    background: transparent;
}

.wm-page-shell--with-tabbar {
    padding-bottom: var(--wm-safe-bottom-tabbar, calc(148rpx + env(safe-area-inset-bottom)));
}

.wm-page-shell--safe-bottom {
    padding-bottom: var(--wm-safe-bottom-action, calc(168rpx + env(safe-area-inset-bottom)));
}

/* #ifdef MP-WEIXIN */
.wm-page-shell::before {
    transition: none;
}
/* #endif */
</style>
