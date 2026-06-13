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
    tone?: 'default' | 'editorial' | 'workspace' | 'business' | 'form' | 'detail' | 'showcase'
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
    if (!externalStyle) return toneStyle
    if (typeof externalStyle === 'string') {
        const normalizedStyle = externalStyle.trim()
        const separator = normalizedStyle && !normalizedStyle.endsWith(';') ? ';' : ''
        return `--wm-page-tone:${props.tone};${normalizedStyle}${separator}`
    }
    return { ...toneStyle, ...externalStyle }
})
</script>

<script lang="ts">
export default {
    name: 'PageShell',
    options: {
        virtualHost: true
    }
}
</script>

<style lang="scss" scoped>
.wm-page-shell {
    position: relative;
    width: 100%;
    min-height: 100vh;
    overflow-x: hidden;
    isolation: isolate;
    box-sizing: border-box;
    background: radial-gradient(circle at 12% 0%, rgba(217, 190, 130, 0.16) 0, rgba(217, 190, 130, 0) 320rpx),
        linear-gradient(180deg, #FFFDF8 0%, var(--wm-color-bg-page, #F5F1E8) 430rpx, var(--wm-color-bg-page, #F5F1E8) 100%);
    color: var(--wm-text-primary, #191713);

    &::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 360rpx;
        background: radial-gradient(circle at 82% 0%, rgba(217, 190, 130, 0.2) 0, transparent 320rpx);
        pointer-events: none;
        z-index: 0;
    }

    &--tone-workspace,
    &--tone-business,
    &--tone-form,
    &--tone-detail,
    &--tone-showcase {
        --wm-flow-gap: 24rpx;
        --wm-list-gap: 20rpx;
    }

    &--tone-showcase {
        background: linear-gradient(180deg, #191713 0%, #2B261D 260rpx, var(--wm-color-bg-page, #F5F1E8) 720rpx);
    }

    &--tone-showcase::before {
        height: 520rpx;
        background: radial-gradient(circle at 18% 0%, rgba(217, 190, 130, 0.28) 0, transparent 340rpx);
    }

    &--suppress-overlay::before {
        display: none;
    }

    &--with-tabbar {
        padding-bottom: var(--wm-safe-bottom-tabbar, calc(164rpx + env(safe-area-inset-bottom)));
    }

    &--safe-bottom {
        padding-bottom: var(--wm-safe-bottom-action, calc(156rpx + env(safe-area-inset-bottom)));
    }
}

/* #ifdef MP-WEIXIN */
.wm-page-shell::before {
    transition: none;
}
/* #endif */
</style>
