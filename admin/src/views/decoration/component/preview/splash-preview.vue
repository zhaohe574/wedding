<template>
    <div class="pv-splash">
        <!-- 未启用 / 无素材：真机直接进首页，这里给明确说明而不是空白 -->
        <div v-if="!configured" class="pv-splash__off">
            <el-icon :size="30"><Picture /></el-icon>
            <p class="pv-splash__off-title">{{ enabled ? '未上传开屏素材' : '开屏广告未启用' }}</p>
            <p class="pv-splash__off-desc">真机冷启动会直接进入首页，不展示开屏页。</p>
        </div>

        <template v-else>
            <img v-if="imageSrc" class="pv-splash__media" :src="imageSrc" alt="" />
            <div class="pv-splash__scrim" />

            <img v-if="logo" class="pv-splash__logo" :src="logo" alt="" />

            <div
                v-if="hasLink"
                class="pv-splash__skip"
                :class="{ 'is-disabled': !buttonEnabled }"
            >
                {{ skipLabel }}
            </div>

            <div class="pv-splash__footer">
                <div class="pv-splash__cta" :class="{ 'is-disabled': !buttonEnabled }">
                    <span>{{ buttonLabel }}</span>
                    <span v-if="buttonEnabled && autoEnter" class="pv-splash__cta-count">
                        {{ left }}s
                    </span>
                </div>
            </div>

            <!-- 预览专用控制条，不进入真机 -->
            <div class="pv-splash__toolbar">
                <span class="pv-splash__meta">{{ summary }}</span>
                <el-button size="small" @click="restart">重播倒计时</el-button>
            </div>
        </template>
    </div>
</template>

<script lang="ts" setup>
/**
 * 开屏广告预览（后台自绘版）。
 *
 * 与小程序开屏层对齐的部分：
 *   - 素材铺满 + 上下压暗遮罩、Logo 位置、常显主按钮与可点击状态、
 *     倒计时文案 —— 尺寸全部取自小程序源码的 rpx 数字；
 *   - 主按钮可点击时机（countdown / always / never）与展示时长直接影响预览表现。
 *
 * 有意省略的部分（预览不产生业务行为）：
 *   - 不真的跳转、不真的离开页面、不计曝光、不写频次状态；
 *   - 倒计时到 0 只停表，不自动进入首页。
 */
import { Picture } from '@element-plus/icons-vue'
import {
    effectiveSkipDelay,
    formatDecorateLinkSummary,
    isDecorateLinkConfigured,
    normalizeSplashContent
} from '@shared/contracts/decorate'
import { computed, onBeforeUnmount, ref, watch } from 'vue'

const props = defineProps<{
    /** splash-ad 组件的 content（编辑态草稿） */
    content: Record<string, any>
    /** 文件域名，用于把相对路径补成可访问地址 */
    domain: string
    /** 每次自增触发倒计时重播 */
    replayKey: number
}>()

const normalized = computed(() => normalizeSplashContent(props.content))

const enabled = computed(() => normalized.value.enabled === 1)
const configured = computed(() => enabled.value && !!normalized.value.image)

function toUrl(url?: string): string {
    const value = String(url || '').trim()
    if (!value) return ''
    if (/^https?:/i.test(value)) return value
    return props.domain ? `${props.domain.replace(/\/+$/, '')}/${value.replace(/^\/+/, '')}` : value
}

const imageSrc = computed(() => toUrl(normalized.value.image))
const logo = computed(() => toUrl(normalized.value.logo_image))
const autoEnter = computed(() => normalized.value.auto_enter_enabled === 1)

const left = ref(normalized.value.auto_seconds)
const elapsed = ref(0)

let timer: ReturnType<typeof setInterval> | null = null

/** 主按钮一直显示；可点击时机与真机同一套，never 且关闭自动进入时兜底可点 */
const clickDelay = computed(() => {
    if (normalized.value.skip_mode === 'always') return 0
    if (normalized.value.skip_mode === 'never') return normalized.value.auto_seconds
    return effectiveSkipDelay(normalized.value)
})

const hasLink = computed(() => isDecorateLinkConfigured(normalized.value.link))

const buttonEnabled = computed(() => {
    if (normalized.value.skip_mode === 'never') return !autoEnter.value
    return elapsed.value >= clickDelay.value
})

const skipLabel = computed(() => {
    if (buttonEnabled.value) return '跳过'
    if (normalized.value.skip_mode === 'never') return '跳过'
    return `${Math.max(0, clickDelay.value - elapsed.value)}s 跳过`
})

const buttonLabel = computed(() => {
    if (buttonEnabled.value) return normalized.value.button_text || '点击进入'
    if (normalized.value.skip_mode === 'never') return `${left.value}s 后自动进入`
    return `${Math.max(0, clickDelay.value - elapsed.value)}s 后可进入`
})

const summary = computed(() => {
    const c = normalized.value
    const delay = effectiveSkipDelay(c)
    const button =
        c.skip_mode === 'never'
            ? c.auto_enter_enabled === 1
                ? '按钮常显、不可点击，只自动进入'
                : '按钮常显、不可点击，已兜底立即可点'
            : c.skip_mode === 'always' || delay <= 0
              ? '按钮常显、立即可点击'
              : `按钮常显、${delay}s 后可点击`
    const target = hasLink.value ? `跳转：${formatDecorateLinkSummary(c.link)}` : '仅进入首页'
    return `${c.auto_seconds}s 展示 · ${button} · ${c.frequency} · ${target}`
})

function stop() {
    if (timer) {
        clearInterval(timer)
        timer = null
    }
}

function restart() {
    stop()
    left.value = normalized.value.auto_seconds
    elapsed.value = 0
    if (!configured.value) return
    timer = setInterval(() => {
        elapsed.value += 1
        left.value = Math.max(0, normalized.value.auto_seconds - elapsed.value)
        // 预览到点只停表，不跳转
        if (left.value <= 0) stop()
    }, 1000)
}

watch(
    () => props.replayKey,
    () => restart()
)

watch(
    () => [normalized.value.auto_seconds, normalized.value.skip_mode, normalized.value.skip_delay],
    () => restart()
)

restart()

onBeforeUnmount(stop)
</script>

<style lang="scss" scoped>
@use './preview-tokens.scss' as *;

.pv-splash {
    --pv-rpx: 0.5px;
    position: absolute;
    inset: 0;
    overflow: hidden;
    background: #14110f;
    font-family: $font-sans;

    &__media {
        position: absolute;
        inset: 0;
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    &__scrim {
        position: absolute;
        inset: 0;
        background: linear-gradient(
            180deg,
            rgba(24, 22, 20, 0.42) 0%,
            rgba(24, 22, 20, 0) 22%,
            rgba(24, 22, 20, 0) 62%,
            rgba(24, 22, 20, 0.62) 100%
        );
    }

    &__logo {
        position: absolute;
        top: rpx(180);
        left: 50%;
        width: rpx(284);
        height: rpx(192);
        transform: translateX(-50%);
        object-fit: contain;
    }

    &__skip {
        position: absolute;
        top: rpx(120);
        right: rpx(32);
        z-index: 3;
        height: rpx(56);
        padding: 0 rpx(24);
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: $r-pill;
        background: rgba(24, 22, 20, 0.45);
        border: 1px solid rgba(255, 255, 255, 0.35);
        font-size: $fs-xs;
        color: $on-dark;

        &.is-disabled {
            opacity: 0.65;
        }
    }

    &__footer {
        position: absolute;
        left: 0;
        right: 0;
        bottom: rpx(96);
        z-index: 2;
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: rpx(24);
    }

    &__cta {
        min-width: rpx(320);
        height: rpx(88);
        padding: 0 rpx(64);
        display: flex;
        align-items: center;
        justify-content: center;
        gap: rpx(12);
        border-radius: $r-pill;
        background: $surface;
        box-shadow: 0 rpx(12) rpx(32) rgba(24, 22, 20, 0.32);
        font-size: $fs-md;
        font-weight: 600;
        letter-spacing: 0.04em;
        color: $ink;

        &.is-disabled {
            background: rgba(255, 253, 248, 0.72);
            box-shadow: none;
            color: rgba(24, 22, 20, 0.52);
        }
    }

    &__cta-count {
        font-size: $fs-xs;
        font-weight: 400;
        color: $ink-2;
        font-variant-numeric: tabular-nums;
    }

    /* 预览控制条：真机没有，只用来重播倒计时 */
    &__toolbar {
        position: absolute;
        left: 0;
        right: 0;
        bottom: 0;
        z-index: 4;
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 6px 8px;
        background: rgba(17, 17, 17, 0.72);
    }

    &__meta {
        flex: 1;
        min-width: 0;
        overflow: hidden;
        white-space: nowrap;
        text-overflow: ellipsis;
        font-size: 11px;
        color: rgba(255, 255, 255, 0.72);
    }

    &__off {
        position: absolute;
        inset: 0;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: 6px;
        padding: 24px;
        text-align: center;
        color: rgba(255, 255, 255, 0.6);
    }

    &__off-title {
        margin: 8px 0 0;
        font-size: 13px;
        color: rgba(255, 255, 255, 0.86);
    }

    &__off-desc {
        margin: 0;
        font-size: 11px;
        line-height: 1.7;
    }
}
</style>
