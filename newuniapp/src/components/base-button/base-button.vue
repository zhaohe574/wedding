<template>
    <view
        ref="rootRef"
        class="btn"
        :class="[
            `btn--${variant}`,
            `btn--${size}`,
            {
                'is-disabled': disabled || loading,
                'btn--block': block,
                'press-spring': interactive && variant !== 'text',
                'is-pressing': pressing,
                'is-rebound': rebound,
                'btn--icon-l': iconSide === 'left',
                'btn--icon-r': iconSide === 'right',
                'ripple-origin': ripple
            }
        ]"
        :aria-busy="loading ? 'true' : undefined"
        :aria-disabled="disabled ? 'true' : undefined"
        @touchstart="onTouchStart"
        @touchend="onTouchEnd"
        @touchcancel="onTouchCancel"
        @tap="handleTap"
    >
        <!-- 波纹裁剪层：根节点带 .ripple-origin 但把 overflow 放开（见样式注释），
             由这一层按胶囊圆角裁剪，波形不会漏到按钮外，也不吃掉 sm 的 88rpx 热区。 -->
        <view v-if="ripple" class="btn__ripple" aria-hidden="true">
            <view
                v-for="w in waves"
                :key="w.id"
                class="ripple-wave"
                :style="{ '--rx': w.x, '--ry': w.y }"
            />
        </view>
        <slot name="icon-left" />
        <tn-icon v-if="iconSide === 'left' && icon" :name="icon" class="btn__icon" />
        <u-loading v-if="loading" :size="32" :color="loadingColor" class="btn__spinner" />
        <text v-if="!loading || loadingText" class="btn__text">{{
            loading ? loadingText : text
        }}</text>
        <tn-icon v-if="iconSide === 'right' && icon" :name="icon" class="btn__icon" />
        <slot name="icon-right" />
    </view>
</template>

<script setup lang="ts">
/**
 * 主按钮。颜色一律走 token，不在页面里写色值。
 * 默认带防重复点击（500ms），这是老端最常出的事故点。
 *
 * v10 §3.1：两段式弹簧按压（压实 ≥75ms → 松手 440ms 过冲回弹），
 *           hover-class 的透明度变暗已移除（与 disabled/loading 叠加会脏）。
 * v10 §3.12：落点跟手波纹，从手指真实落点扩散，不从按钮中心呆板散开。
 * v10 §3.11：触觉默认关闭（只允许关键节点由页面显式指定等级），text 变体（纯文字链）不震。
 */
import { computed, getCurrentInstance, onUnmounted, ref } from 'vue'
import { haptic } from '@/utils/haptics'

const props = withDefaults(
    defineProps<{
        text?: string
        /**
         * ink  = 黑底主操作（默认，对白底 18:1）
         * gold = 金底强调，仅限深色容器内或单屏唯一强调动作，必须配深墨字
         * ghost / danger / text 为次动作（03 §4.4 / §5.9 主次决策表）
         * T-37：历史别名 brand / primary 已随全站归零删除（顺序铁律最后一步）
         */
        variant?: 'ink' | 'gold' | 'ghost' | 'danger' | 'text'
        size?: 'lg' | 'md' | 'sm'
        disabled?: boolean
        loading?: boolean
        /** loading 时跟在 spinner 后的文案；不传则只显示 spinner（历史行为不变） */
        loadingText?: string
        block?: boolean
        /** 防重复点击间隔（毫秒），0 表示不限制 */
        lockMs?: number
        /** 图标位：none 纯文字；left / right 走不对称内边距（图标侧更窄） */
        iconSide?: 'none' | 'left' | 'right'
        /** 图鸟图标名；也可用 #icon-left / #icon-right 插槽放自定义内容 */
        icon?: string
        /** 触觉等级，none 关闭（默认不震，只有关键动作才由页面显式指定）；variant='text' 一律不震 */
        hapticLevel?: 'light' | 'medium' | 'heavy' | 'none'
        /** 落点波纹开关（disabled / loading / 锁定期不播） */
        ripple?: boolean
    }>(),
    {
        text: '',
        variant: 'ink',
        size: 'md',
        disabled: false,
        loading: false,
        loadingText: '',
        block: true,
        lockMs: 500,
        iconSide: 'none',
        icon: '',
        hapticLevel: 'none',
        ripple: true
    }
)

const emit = defineEmits<{ (e: 'click'): void }>()

const locked = ref(false)

/**
 * loading 时按钮必然进入 `.is-disabled`（中性浅底），所以 spinner 用 ink-3；
 * 原先按 variant 映射会在 ink 按钮上给出白 spinner，浅底上看不见。
 */
const loadingColor = 'var(--c-ink-3)'

/** 可交互 = 非禁用、非 loading；text 变体也走按压态（只换底色，不缩放） */
const interactive = computed(() => !props.disabled && !props.loading)

/* ---- 两段式弹簧按压（§3.1） ---- */
const PRESS_MIN_MS = 75
const RELEASE_MS = 440

const pressing = ref(false)
const rebound = ref(false)
let pressStartAt = 0
let pressTimer: ReturnType<typeof setTimeout> | null = null
let reboundTimer: ReturnType<typeof setTimeout> | null = null

function clearPressTimers() {
    if (pressTimer) {
        clearTimeout(pressTimer)
        pressTimer = null
    }
    if (reboundTimer) {
        clearTimeout(reboundTimer)
        reboundTimer = null
    }
}

/* ---- 落点跟手波纹（§3.12 / globals.scss .ripple-origin） ---- */
interface RippleWave {
    id: number
    x: string
    y: string
}

const WAVE_MS = 520
const rootRef = ref<unknown>(null)
const instance = getCurrentInstance()
const waves = ref<RippleWave[]>([])
const waveTimers = new Set<ReturnType<typeof setTimeout>>()
let waveSeq = 0

function pushWave(x: string, y: string) {
    const id = (waveSeq += 1)
    waves.value = [...waves.value, { id, x, y }]
    const timer = setTimeout(() => {
        waveTimers.delete(timer)
        waves.value = waves.value.filter((w) => w.id !== id)
    }, WAVE_MS)
    waveTimers.add(timer)
}

/** 触摸点（clientX/clientY 优先，部分基础库只给 pageX/pageY） */
function pointOf(e: unknown): { x: number; y: number } | null {
    const evt = e as { touches?: Array<Record<string, number>>; changedTouches?: Array<Record<string, number>> }
    const t = evt?.touches?.[0] ?? evt?.changedTouches?.[0]
    if (!t) return null
    const x = Number(t.clientX ?? t.pageX)
    const y = Number(t.clientY ?? t.pageY)
    if (!Number.isFinite(x) || !Number.isFinite(y)) return null
    return { x, y }
}

function spawnRipple(e: unknown) {
    if (!props.ripple || !interactive.value || locked.value) return
    const p = pointOf(e)
    if (!p) {
        pushWave('50%', '50%')
        return
    }

    // #ifdef H5
    const host = rootRef.value as { $el?: Element } | Element | null
    const el = (host && (host as { $el?: Element }).$el) || (host as Element | null)
    if (el && typeof el.getBoundingClientRect === 'function') {
        const rect = el.getBoundingClientRect()
        pushWave(`${Math.round(p.x - rect.left)}px`, `${Math.round(p.y - rect.top)}px`)
        return
    }
    pushWave('50%', '50%')
    // #endif

    // #ifndef H5
    if (instance) {
        try {
            uni.createSelectorQuery()
                .in(instance.proxy as unknown as Record<string, unknown>)
                .select('.btn')
                .boundingClientRect((rect: unknown) => {
                    const box = rect as { left?: number; top?: number } | null
                    if (box && Number.isFinite(box.left) && Number.isFinite(box.top)) {
                        pushWave(
                            `${Math.round(p.x - Number(box.left))}px`,
                            `${Math.round(p.y - Number(box.top))}px`
                        )
                    } else {
                        // 查询失败（节点未上屏 / 基础库不支持）：退化成中心扩散，不报错也不吞掉交互
                        pushWave('50%', '50%')
                    }
                })
                .exec()
        } catch {
            pushWave('50%', '50%')
        }
    } else {
        pushWave('50%', '50%')
    }
    // #endif
}

/* ---- 触摸状态机 ---- */
function onTouchStart(e: unknown) {
    if (!interactive.value) return
    // 先把按压态立起来，再做波纹坐标查询：查询失败也不能吞掉下压反馈
    clearPressTimers()
    rebound.value = false
    pressStartAt = Date.now()
    pressing.value = true
    spawnRipple(e)
}

function onTouchEnd() {
    // 只看 pressing：按压中途 props 变成 disabled / loading 时也要把状态收干净，否则会停在 96%
    if (!pressing.value) return
    const rest = Math.max(0, PRESS_MIN_MS - (Date.now() - pressStartAt))
    if (pressTimer) clearTimeout(pressTimer)
    pressTimer = setTimeout(() => {
        pressTimer = null
        pressing.value = false
        rebound.value = true
        reboundTimer = setTimeout(() => {
            rebound.value = false
            reboundTimer = null
        }, RELEASE_MS)
    }, rest)
}

/** 手指滑走 / 系统打断：平滑复位，不播回弹 */
function onTouchCancel() {
    clearPressTimers()
    pressing.value = false
    rebound.value = false
}

onUnmounted(() => {
    clearPressTimers()
    waveTimers.forEach((t) => clearTimeout(t))
    waveTimers.clear()
})

function handleTap() {
    if (props.disabled || props.loading || locked.value) return
    if (props.hapticLevel !== 'none' && props.variant !== 'text') {
        haptic(props.hapticLevel, 'base-button')
    }
    emit('click')
    if (props.lockMs > 0) {
        locked.value = true
        setTimeout(() => (locked.value = false), props.lockMs)
    }
}
</script>

<style lang="scss" scoped>
.btn {
    position: relative;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: var(--sp-1);
    border-radius: var(--r-pill);
    transition: background-color var(--dur-fast) var(--ease-out),
        transform var(--dur-press) var(--ease-out);
    font-weight: var(--fw-medium);

    /* .ripple-origin 自带 overflow:hidden；这里放开，改由 .btn__ripple 裁剪，
       否则 sm 尺寸向外扩到 88rpx 的 ::before 热区会被一起裁掉（§2.5）。 */
    &.ripple-origin {
        overflow: visible;
    }

    /* 波形层：按胶囊圆角裁剪，且不吃交互（pointer-events:none） */
    &__ripple {
        position: absolute;
        top: 0;
        right: 0;
        bottom: 0;
        left: 0;
        z-index: 0;
        border-radius: inherit;
        overflow: hidden;
        pointer-events: none;
    }

    /* 内容压在波形层之上（波形层是定位元素，不抬高层级会盖住文字） */
    &__text,
    &__icon,
    &__spinner {
        position: relative;
        z-index: 1;
    }

    &--block {
        display: flex;
        width: 100%;
    }

    &__text {
        line-height: var(--lh-none);
    }

    /* variant：ink = 主操作（浅色墨黑实心，暗色香槟金实心）；gold = 金底强调（T-37 已删 brand/primary 别名选择器） */
    &--ink {
        background: var(--c-action-bg);
        color: var(--c-action-fg);
    }
    &--gold {
        background: var(--c-brand);
        color: var(--c-on-brand);
        border: 1rpx solid var(--c-brand-text);
    }
    &--ghost {
        background: transparent;
        color: var(--c-ink);
        border: 1rpx solid var(--c-control-border);
    }
    &--danger {
        background: var(--c-danger-bg);
        color: var(--c-danger);
        border: 1rpx solid var(--c-danger);
    }
    &--text {
        background: transparent;
        color: var(--c-ink-2);
        padding: 0;
    }

    /* size：md 提到 88rpx（44px）满足触控热区下限 */
    &--lg {
        height: 104rpx;
        font-size: var(--fs-lg);
        font-weight: var(--fw-semibold);
        padding: 0 var(--sp-5);
    }
    &--md {
        height: 88rpx;
        font-size: var(--fs-md);
        font-weight: var(--fw-semibold);
        padding: 0 var(--sp-4);
    }
    &--sm {
        position: relative;
        height: 60rpx;
        min-width: 88rpx;
        font-size: var(--fs-xs);
        font-weight: var(--fw-medium);
        padding: 0 var(--sp-3);

        /* 保持 60rpx 视觉高度，纵向扩展至 88rpx 独立热区 */
        &::before {
            content: '';
            position: absolute;
            top: -14rpx;
            bottom: -14rpx;
            left: 0;
            right: 0;
        }
    }

    /* 图标位不对称内边距（v10 §2.4）：图标侧窄、文字侧宽，图标重心才落在按钮内。
       写在尺寸规则之后，同特异性下后者生效。 */
    &--icon-l {
        padding-left: 34rpx;
        padding-right: 46rpx;
    }
    &--icon-r {
        padding-left: 46rpx;
        padding-right: 34rpx;
    }

    /* 禁用态：显式色，去 opacity（视觉规范 §7.3 / §9.2 A10） */
    &.is-disabled {
        background: var(--c-neutral-bg);
        color: var(--c-ink-3);
        border: 1rpx solid var(--c-line);
        opacity: 1;
    }

    /* ghost / text 的按下态用浅底更明确（§8.2）；
       ink/gold/danger 靠弹簧缩放表达，不再整体降透明（旧 btn--hover 已移除） */
    &--ghost.is-pressing,
    &--text.is-pressing {
        background: var(--c-surface-2);
    }
}

/* v10 §3.13：减弱动效下不缩放、不播波纹，但底色变化保留 */
@media (prefers-reduced-motion: reduce) {
    .btn {
        transition: none;
    }
}
</style>
