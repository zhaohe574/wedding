<template>
    <view v-if="phase !== 'done'" class="splash" :class="colorScheme.shellClass">
        <!-- 广告主图：aspectFill 铺满，加载失败自动退回品牌底 -->
        <image
            v-if="showAd && !imageFailed"
            class="splash__media"
            :src="mediaUrl(content.image)"
            mode="aspectFill"
            @error="imageFailed = true"
        />
        <!-- 主图压字必须有遮罩：上深下深，保证底部主按钮始终可读 -->
        <view v-if="showAd && !imageFailed" class="splash__scrim" />

        <!-- 品牌标识（后台可选，透明 PNG） -->
        <image
            v-if="showAd && showLogo"
            class="splash__logo"
            :src="mediaUrl(content.logo_image)"
            mode="aspectFit"
            @error="logoFailed = true"
        />

        <!-- 右上角跳过：仅配置了跳转目标时出现，遵循同一套 skip_mode / skip_delay 可点击时机 -->
        <view
            v-if="showAd && hasLink"
            class="splash__skip hit-88"
            :class="{ 'is-disabled': !buttonEnabled }"
            :style="{ top: skipTop }"
            :hover-class="buttonEnabled ? 'is-pressed' : 'none'"
            aria-role="button"
            :aria-disabled="!buttonEnabled"
            @tap.stop="onSkip"
        >
            <text class="splash__skip-text">{{ skipLabel }}</text>
        </view>

        <!-- 主图点击层：与主按钮同一个动作（关闭开屏、按需跳转）；只在主按钮可点击后生效，
             避免绕过「第几秒可点击」的限制。主按钮与跳过胶囊在它上层。 -->
        <!-- #ifdef MP-WEIXIN -->
        <button
            v-if="showAd && buttonEnabled && isContactLink"
            open-type="contact"
            class="splash__hit splash__hit--btn"
            @tap="finish"
        />
        <view
            v-else-if="showAd && buttonEnabled"
            class="splash__hit"
            aria-hidden="true"
            @tap="onCta"
        />
        <!-- #endif -->
        <!-- #ifndef MP-WEIXIN -->
        <view v-if="showAd && buttonEnabled" class="splash__hit" aria-hidden="true" @tap="onCta" />
        <!-- #endif -->

        <!-- 无广告 / 首次启动判定中：品牌底，避免白屏 -->
        <view v-if="!showAd" class="splash__brand">
            <brand-mark :size="120" variant="dark" round />
            <text class="splash__brand-name">{{ brand.name }}</text>
        </view>

        <!-- 底部主按钮：一直显示；未到可点击时间显示倒计时并置灰 -->
        <view v-if="showAd || imageFailed" class="splash__footer">
            <!-- #ifdef MP-WEIXIN -->
            <button
                v-if="showAd && buttonEnabled && isContactLink"
                open-type="contact"
                class="splash__cta splash__cta--btn press-spring"
                hover-class="is-pressing"
                :hover-stay-time="90"
                @tap="finish"
            >
                <text class="splash__cta-text">{{ buttonLabel }}</text>
                <text v-if="autoEnter" class="splash__cta-count t-num">
                    {{ left }}s
                </text>
            </button>
            <view
                v-else
                class="splash__cta press-spring"
                :class="{ 'is-disabled': !buttonEnabled }"
                :hover-class="buttonEnabled ? 'is-pressing' : 'none'"
                :hover-stay-time="90"
                aria-role="button"
                :aria-disabled="!buttonEnabled"
                @tap="onCta"
            >
                <text class="splash__cta-text">{{ buttonLabel }}</text>
                <text v-if="buttonEnabled && autoEnter && showAd" class="splash__cta-count t-num">
                    {{ left }}s
                </text>
            </view>
            <!-- #endif -->
            <!-- #ifndef MP-WEIXIN -->
            <view
                class="splash__cta press-spring"
                :class="{ 'is-disabled': !buttonEnabled }"
                :hover-class="buttonEnabled ? 'is-pressing' : 'none'"
                :hover-stay-time="90"
                aria-role="button"
                :aria-disabled="!buttonEnabled"
                @tap="onCta"
            >
                <text class="splash__cta-text">{{ buttonLabel }}</text>
                <text v-if="buttonEnabled && autoEnter && showAd" class="splash__cta-count t-num">
                    {{ left }}s
                </text>
            </view>
            <!-- #endif -->
        </view>
    </view>
</template>

<script setup lang="ts">
/**
 * 开屏广告层。
 *
 * 为什么不再单独占一个 pages[0] 页面：
 *   实测「非 Tab 开屏页 → switchTab 首页」在自定义 tabBar 下会等待约 10 秒，
 *   时间全部花在页面 JS 开始执行之前，和首页代码/接口无关。
 *   首页本来就是 pages[0] 最合适：首页代码与数据立即开始加载，
 *   开屏层同步盖在首帧上，既没有白屏，也没有第二次页面装载。
 *
 * 展示判定顺序（全部在组件挂载时完成，不阻塞首页数据）：
 *   1. 读本地缓存 → 用 shared 纯函数判定是否展示；
 *   2. 判定通过 → 渲染广告 + 倒计时；后台静默刷新配置，供下次启动用；
 *   3. 不展示 / 无缓存且超过 800ms → 直接 emit done，露出已渲染好的首页。
 *
 * 频次、主按钮出现方式、时长全部来自 `SplashAdContent`，
 * 本组件不写死任何一条展示规则。
 */
import { computed, onBeforeUnmount, onMounted, ref } from 'vue'
import { effectiveSkipDelay, normalizeSplashContent, type SplashAdContent } from '@shared/contracts'
import { useBrand } from '@/composables/useBrand'
import { useAppStore } from '@/stores/app'
import { useColorSchemeStore } from '@/stores/colorScheme'
import { getDeviceInfo } from '@/platform/device'
import { isDecorateLinkConfigured, isWechatContactLink, navigate } from '@/utils/link'
import {
    commitSplashShown,
    fetchSplashConfig,
    readSplashConfig,
    resolveSplash
} from '@/utils/splash'
import { bootTrace } from '@/utils/boot-trace'

/** 首次安装（无缓存）最多等配置的时间，超过就露出首页 */
const BOOT_TIMEOUT = 800

const emit = defineEmits<{ (e: 'done'): void }>()

const { brand } = useBrand()
const appStore = useAppStore()
/** 开屏挂在 page-shell 之外：自己绑 .scheme-* 才能响应手动外观模式切换 */
const colorScheme = useColorSchemeStore()

/**
 * 素材地址兜底：新版接口已下发绝对地址；旧缓存里可能是相对 uri，
 * 此时用全局 config.domain 补全（拿不到就原样返回，由 @error 走品牌底）。
 */
function mediaUrl(url?: string) {
    const value = String(url || '').trim()
    if (!value) return ''
    if (/^https?:/i.test(value)) return value
    const domain = String((appStore.config as any)?.domain || '').replace(/\/+$/, '')
    return domain ? `${domain}/${value.replace(/^\/+/, '')}` : value
}

const content = ref<SplashAdContent>(normalizeSplashContent(null))
/** boot = 正在判定；ad = 正在展示；done = 已结束 */
const phase = ref<'boot' | 'ad' | 'done'>('boot')
const left = ref(0)
const elapsed = ref(0)
const imageFailed = ref(false)
const logoFailed = ref(false)

let tickTimer: ReturnType<typeof setInterval> | null = null
let bootTimer: ReturnType<typeof setTimeout> | null = null

const showAd = computed(() => phase.value === 'ad' && !imageFailed.value)
const showLogo = computed(() => !!content.value.logo_image && !logoFailed.value)
const autoEnter = computed(() => content.value.auto_enter_enabled === 1)
const hasLink = computed(() => isDecorateLinkConfigured(content.value.link))
const isContactLink = computed(() => hasLink.value && isWechatContactLink(content.value.link))
const skipTop = computed(() => `${getDeviceInfo().statusBarHeight + 52}px`)

/**
 * 主按钮可点击时机（沿用原「跳过」逻辑）：
 *   countdown → skip_delay 秒后才可点
 *   always    → 立即可点
 *   never     → 一直不可点，只等自动进入；若关闭自动进入，兜底立即可点，避免无出口
 * 按钮本身一直显示，不可点时置灰并显示倒计时。
 */
const clickDelay = computed(() => {
    if (content.value.skip_mode === 'always') return 0
    if (content.value.skip_mode === 'never') return content.value.auto_seconds
    return effectiveSkipDelay(content.value)
})

const buttonEnabled = computed(() => {
    if (phase.value !== 'ad') return false
    if (content.value.skip_mode === 'never') return !autoEnter.value
    return elapsed.value >= clickDelay.value
})

const clickCountdown = computed(() => Math.max(0, clickDelay.value - elapsed.value))

const skipLabel = computed(() => {
    if (buttonEnabled.value) return '跳过'
    if (content.value.skip_mode === 'never') return '跳过'
    return `${clickCountdown.value}s 跳过`
})

const buttonLabel = computed(() => {
    if (buttonEnabled.value) return content.value.button_text || '点击进入'
    if (content.value.skip_mode === 'never') return `${left.value}s 后自动进入`
    return `${clickCountdown.value}s 后可进入`
})

function stopTimers() {
    if (tickTimer) {
        clearInterval(tickTimer)
        tickTimer = null
    }
    if (bootTimer) {
        clearTimeout(bootTimer)
        bootTimer = null
    }
}

/** 结束开屏，露出已渲染好的首页 */
function finish() {
    if (phase.value === 'done') return
    phase.value = 'done'
    stopTimers()
    bootTrace('splash:done')
    emit('done')
}

function tick() {
    elapsed.value += 1
    if (!autoEnter.value) return
    left.value = Math.max(0, content.value.auto_seconds - elapsed.value)
    if (left.value <= 0) {
        stopTimers()
        finish()
    }
}

function startAd(next: SplashAdContent) {
    bootTrace('splash:show ad', { auto_seconds: next.auto_seconds, skip_mode: next.skip_mode })
    content.value = next
    left.value = next.auto_seconds
    elapsed.value = 0
    imageFailed.value = false
    logoFailed.value = false
    phase.value = 'ad'
    // 曝光口径：广告真正渲染出来记一次（不是点按钮才记）
    commitSplashShown()
    stopTimers()
    // 即使不自动进入也要走计时：skip_mode=countdown 的「第几秒可点击」依赖 elapsed
    tickTimer = setInterval(tick, 1000)
}

/** 右上角跳过：直接进入首页，不触发开屏跳转 */
function onSkip() {
    if (!buttonEnabled.value) return
    finish()
}

/** 主按钮 / 主图点击：可点击时关闭开屏；若配置了跳转目标则同步打开 */
function onCta() {
    if (!buttonEnabled.value) return
    const target = content.value.link
    finish()
    if (isDecorateLinkConfigured(target)) {
        navigate(target as any)
    }
}

/** 展示广告的同时静默更新配置，只影响下一次冷启动 */
function refreshInBackground() {
    fetchSplashConfig().catch(() => undefined)
}

function evaluate() {
    const cached = readSplashConfig()
    bootTrace('splash:evaluate', { hasCache: !!cached })

    if (cached) {
        // 有缓存：同步判定，零等待
        const decision = resolveSplash(cached)
        bootTrace('splash:decision(cache)', decision)
        if (decision.show) startAd(cached)
        else finish()
        refreshInBackground()
        return
    }

    // 首次安装：给配置请求一个很短的上限，超时就直接露出首页
    bootTimer = setTimeout(() => {
        if (phase.value === 'boot') finish()
    }, BOOT_TIMEOUT)

    fetchSplashConfig()
        .then((fresh) => {
            bootTrace('splash:config resolved', { hasConfig: !!fresh })
            if (bootTimer) {
                clearTimeout(bootTimer)
                bootTimer = null
            }
            if (phase.value !== 'boot') return
            if (fresh && resolveSplash(fresh).show) startAd(fresh)
            else finish()
        })
        .catch((err) => {
            bootTrace('splash:config failed', err)
            finish()
        })
}

onMounted(evaluate)

onBeforeUnmount(stopTimers)
</script>

<style lang="scss" scoped>
.splash {
    position: fixed;
    inset: 0;
    z-index: 1100; /* 高于页面内容与 tabbar；隐私授权 / toast 等系统级弹层仍可覆盖 */
    width: 100vw;
    height: 100vh;
    overflow: hidden;
    background: var(--c-dark-2);

    &__media {
        position: absolute;
        inset: 0;
        width: 100%;
        height: 100%;
    }

    /* 上下两道压暗：保证底部主按钮在任何素材上都能读 */
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
        top: 180rpx;
        left: 50%;
        width: 284rpx;
        height: 192rpx;
        transform: translateX(-50%);
    }

    /* 右上角跳过胶囊：仅配置了跳转目标时展示 */
    &__skip {
        position: absolute;
        right: 32rpx;
        z-index: 3;
        height: 56rpx;
        padding: 0 24rpx;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: var(--r-pill);
        background: rgba(24, 22, 20, 0.45);
        border: 1rpx solid rgba(255, 255, 255, 0.35);

        &.is-disabled {
            opacity: 0.65;
        }
    }

    &__skip-text {
        font-size: var(--fs-xs);
        color: var(--c-on-dark);
        font-variant-numeric: tabular-nums;
    }

    /* 主图点击层：主按钮 z2 在它上层，只有空白区域触发关闭 */
    &__hit {
        position: absolute;
        inset: 0;
        z-index: 1;

        &--btn {
            margin: 0;
            padding: 0;
            border: none;
            border-radius: 0;
            background: transparent;
            &::after {
                border: none;
            }
        }
    }

    /* 无广告时的品牌底：与首页 hero 同一套暗调 */
    &__brand {
        position: absolute;
        inset: 0;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: var(--sp-3);
        background: linear-gradient(180deg, var(--c-dark-1) 0%, var(--c-dark-2) 100%);
    }

    &__brand-name {
        font-size: var(--fs-xl);
        letter-spacing: 0.16em;
        color: var(--c-on-dark);
    }

    &__footer {
        position: absolute;
        left: 0;
        right: 0;
        bottom: calc(var(--safe-bottom) + 96rpx);
        z-index: 2;
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: var(--sp-3);
    }

    /* 实心米白胶囊：与全站 CTA 同一套对比度逻辑（浅底 + 墨字） */
    &__cta {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 12rpx;
        min-width: 320rpx;
        height: 88rpx;
        padding: 0 var(--sp-6);
        border-radius: var(--r-pill);
        background: var(--c-surface);
        box-shadow: var(--shadow-float);
        transition: opacity var(--dur-fast) var(--ease-out),
            transform var(--dur-release) var(--ease-spring-soft);

        &.is-pressing {
            transform: scale(0.96);
            transition-duration: var(--dur-press);
            transition-timing-function: var(--ease-out);
        }

        /* 未到可点击时间：仍用语义表面色，只降透明度并换次级文字色，不再写暖白 rgba */
        &.is-disabled {
            background: var(--c-surface);
            opacity: 0.72;
            box-shadow: none;

            .splash__cta-text {
                color: var(--c-ink-2);
            }

            .splash__cta-count {
                color: var(--c-ink-3);
            }
        }

        &--btn {
            margin: 0;
            border: none;
            line-height: var(--lh-none);
            font-size: inherit;
            &::after {
                border: none;
            }
        }
    }

    &__cta-count {
        font-size: var(--fs-xs);
        color: var(--c-ink-2);
        font-variant-numeric: tabular-nums;
    }

    &__cta-text {
        font-size: var(--fs-md);
        font-weight: var(--fw-semibold);
        letter-spacing: 0.04em;
        color: var(--c-ink);
    }
}

/* v10 §3.13：减弱动效下不缩放、不渐变 */
@media (prefers-reduced-motion: reduce) {
    .splash__cta {
        transition: none;

        &.is-pressing {
            transform: none;
        }
    }
}
</style>
