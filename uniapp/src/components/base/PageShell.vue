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
        <OaReminderDialog />
    </view>
</template>

<script setup lang="ts">
import type { CSSProperties } from 'vue'
import { computed, watch } from 'vue'
import OaReminderDialog from './OaReminderDialog.vue'
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
    background: radial-gradient(circle at 12% 0%, rgba(197, 164, 109, 0.12) 0, rgba(197, 164, 109, 0) 320rpx),
        linear-gradient(180deg, #FFFFFF 0%, var(--wm-color-bg-page, #FAF8F5) 430rpx, var(--wm-color-bg-page, #FAF8F5) 100%);
    color: var(--wm-text-primary, #1A1816);

    &::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 360rpx;
        background: radial-gradient(circle at 82% 0%, rgba(197, 164, 109, 0.14) 0, transparent 320rpx);
        pointer-events: none;
        z-index: 0;
    }

    &[data-scene='staff'] {
        background: radial-gradient(circle at 20% 0%, rgba(197, 164, 109, 0.18) 0, transparent 380rpx),
            linear-gradient(180deg, #1A1816 0%, var(--wm-color-bg-page, #151412) 480rpx, var(--wm-color-bg-page, #151412) 100%);
        color: var(--wm-text-primary, #FAF8F5);

        &::before {
            background: radial-gradient(circle at 80% 0%, rgba(197, 164, 109, 0.15) 0, transparent 340rpx);
        }
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
        background: linear-gradient(180deg, #141311 0%, #221F1C 280rpx, var(--wm-color-bg-page, #FAF8F5) 760rpx);
    }

    &--tone-showcase::before {
        height: 540rpx;
        background: radial-gradient(circle at 18% 0%, rgba(197, 164, 109, 0.24) 0, transparent 360rpx);
    }

    &--suppress-overlay::before {
        display: none;
    }

    &--with-tabbar {
        padding-bottom: var(--wm-safe-bottom-tabbar, calc(152rpx + constant(safe-area-inset-bottom)));
        padding-bottom: var(--wm-safe-bottom-tabbar, calc(152rpx + env(safe-area-inset-bottom)));
    }

    &--safe-bottom {
        padding-bottom: var(--wm-safe-bottom-action, calc(148rpx + constant(safe-area-inset-bottom)));
        padding-bottom: var(--wm-safe-bottom-action, calc(148rpx + env(safe-area-inset-bottom)));
    }

    &--with-tabbar.wm-page-shell--safe-bottom {
        padding-bottom: calc(152rpx + 148rpx + env(safe-area-inset-bottom));
    }
}

.wm-page-shell::before {
    transition: none;
}

</style>
