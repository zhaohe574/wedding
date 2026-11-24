<template>
    <div class="splash-ad-preview">
        <!-- 主图 -->
        <div v-if="content.image" class="splash-ad-preview__image">
            <decoration-img width="100%" height="100%" :src="content.image" fit="cover" />
        </div>
        <div v-else class="splash-ad-preview__empty">
            <el-icon size="36"><Picture /></el-icon>
            <span>请上传开屏广告主图</span>
        </div>

        <!-- 品牌标识 -->
        <div v-if="content.logo_image" class="splash-ad-preview__logo">
            <decoration-img
                class="splash-ad-preview__logo-image"
                width="142px"
                height="96px"
                :src="content.logo_image"
                fit="contain"
            />
        </div>

        <!-- 主按钮：一直显示；不可点击时置灰，延迟秒数写在按钮上 -->
        <div v-if="hasLink" class="splash-ad-preview__skip" :class="{ 'is-disabled': !buttonEnabled }">
            {{ skipLabel }}
        </div>

        <div class="splash-ad-preview__footer">
            <button
                class="splash-ad-preview__button"
                :class="{ 'is-disabled': !buttonEnabled }"
            >
                {{ buttonLabel }}
                <span
                    v-if="buttonEnabled && normalized.auto_enter_enabled === 1"
                    class="splash-ad-preview__timer"
                >
                    {{ normalized.auto_seconds }}s
                </span>
            </button>
        </div>

        <!-- 配置摘要：让运营在画布上就能确认「配了什么」 -->
        <div class="splash-ad-preview__meta">
            <span v-if="content.enabled !== 1" class="splash-ad-preview__badge is-off">未启用</span>
            <span v-else-if="!content.image" class="splash-ad-preview__badge is-warn">缺主图</span>
            <span v-else class="splash-ad-preview__badge is-on">启用中</span>
            <span class="splash-ad-preview__meta-text">{{ summary }}</span>
        </div>
    </div>
</template>
<script lang="ts" setup>
import { Picture } from '@element-plus/icons-vue'
import {
    effectiveSkipDelay,
    formatDecorateLinkSummary,
    isDecorateLinkConfigured,
    normalizeSplashContent,
    SPLASH_FREQUENCIES
} from '@shared/contracts/decorate'
import { computed, type PropType } from 'vue'

import DecorationImg from '../../decoration-img.vue'
import type options from './options'

type OptionsType = ReturnType<typeof options>

const props = defineProps({
    content: {
        type: Object as PropType<OptionsType['content']>,
        default: () => ({})
    },
    styles: {
        type: Object as PropType<OptionsType['styles']>,
        default: () => ({})
    }
})

const normalized = computed(() => normalizeSplashContent(props.content))
const hasLink = computed(() => isDecorateLinkConfigured(normalized.value.link))

/** 静态面板没有计时器：按当前配置给出初始的可点击状态 */
const buttonEnabled = computed(() => {
    if (normalized.value.skip_mode === 'always') return true
    if (normalized.value.skip_mode === 'never') return normalized.value.auto_enter_enabled !== 1
    return effectiveSkipDelay(normalized.value) <= 0
})

const buttonLabel = computed(() => {
    if (buttonEnabled.value) return normalized.value.button_text || '点击进入'
    if (normalized.value.skip_mode === 'never') return '自动进入中'
    return `${effectiveSkipDelay(normalized.value)}s 后可进入`
})

const skipLabel = computed(() => {
    if (!buttonEnabled.value) {
        return normalized.value.skip_mode === 'never'
            ? `${normalized.value.auto_seconds}s`
            : `跳过 ${effectiveSkipDelay(normalized.value)}s`
    }
    return normalized.value.auto_enter_enabled === 1
        ? `跳过 ${normalized.value.auto_seconds}s`
        : '跳过'
})

const summary = computed(() => {
    const c = normalized.value
    const freq = SPLASH_FREQUENCIES.find((f) => f.value === c.frequency)?.label || c.frequency
    const delay = effectiveSkipDelay(c)
    const button =
        c.skip_mode === 'never'
            ? c.auto_enter_enabled === 1
                ? '按钮常显、不可点击，只自动进入'
                : '按钮常显、不可点击，已兜底立即可点'
            : c.skip_mode === 'always' || delay <= 0
              ? '按钮常显、立即可点击'
              : `按钮常显、${delay}s 后可点击`
    const auto = c.auto_enter_enabled === 1 ? `${c.auto_seconds}s 自动进入` : '不自动进入'
    const linkSummary = formatDecorateLinkSummary(c.link)
    const target = linkSummary.configured ? ` · 跳转:${linkSummary.title}` : ''
    return `${auto} · ${button} · ${freq}${target}`
})
</script>

<style lang="scss" scoped>
.splash-ad-preview {
    position: relative;
    width: 100%;
    height: 640px;
    overflow: hidden;
    background: linear-gradient(180deg, #f8f4ec 0%, #111 100%);

    &__image {
        width: 100%;
        height: 100%;
    }

    &__empty {
        position: absolute;
        inset: 0;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: 12px;
        color: #8c8c8c;
        background: #f5f5f5;
    }

    &__logo {
        position: absolute;
        top: 96px;
        left: 50%;
        z-index: 1;
        display: flex;
        justify-content: center;
        transform: translateX(-50%);
    }

    &__logo-image {
        animation: splash-preview-logo-breathe 2.35s ease-in-out infinite;
        transform-origin: center center;
        will-change: transform;
    }

    &__footer {
        position: absolute;
        left: 0;
        right: 0;
        bottom: 74px;
        z-index: 1;
        display: flex;
        justify-content: center;
    }

    &__skip {
        position: absolute;
        top: 16px;
        right: 16px;
        z-index: 2;
        height: 28px;
        padding: 0 12px;
        display: inline-flex;
        align-items: center;
        border-radius: 999px;
        background: rgba(24, 22, 20, 0.45);
        border: 1px solid rgba(255, 255, 255, 0.35);
        color: #ffffff;
        font-size: 12px;
        line-height: 1;

        &.is-disabled {
            opacity: 0.65;
        }
    }

    &__button {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        height: 36px;
        padding: 0 18px;
        border: 1px solid rgba(255, 255, 255, 0.72);
        border-radius: 999px;
        background: rgba(156, 158, 166, 0.78);
        color: #ffffff;
        font-size: 14px;
        font-weight: 600;
        line-height: 34px;
        box-shadow:
            inset 0 1px 0 rgba(255, 255, 255, 0.5),
            0 6px 14px rgba(0, 0, 0, 0.24);
        backdrop-filter: blur(5px);

        &.is-disabled {
            background: rgba(255, 253, 248, 0.72);
            color: rgba(24, 22, 20, 0.52);
            box-shadow: none;
        }
    }

    &__timer {
        opacity: 0.82;
        font-size: 12px;
        font-variant-numeric: tabular-nums;
    }

    &__meta {
        position: absolute;
        left: 0;
        right: 0;
        bottom: 0;
        z-index: 2;
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 8px 12px;
        background: rgba(17, 17, 17, 0.72);
        color: rgba(255, 255, 255, 0.86);
        font-size: 12px;
        line-height: 18px;
    }

    &__badge {
        flex: none;
        padding: 1px 8px;
        border-radius: 999px;
        font-size: 11px;

        &.is-on {
            background: rgba(70, 160, 110, 0.24);
            color: #7fd6a6;
        }

        &.is-off {
            background: rgba(255, 255, 255, 0.14);
            color: rgba(255, 255, 255, 0.72);
        }

        &.is-warn {
            background: rgba(214, 150, 40, 0.24);
            color: #f0c274;
        }
    }

    &__meta-text {
        overflow: hidden;
        white-space: nowrap;
        text-overflow: ellipsis;
    }
}

@keyframes splash-preview-logo-breathe {
    0%,
    100% {
        opacity: 0.96;
        transform: scale(1);
    }

    50% {
        opacity: 1;
        transform: scale(1.06);
    }
}
</style>
