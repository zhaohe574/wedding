<template>
    <view v-if="rendered" class="snap">
        <view
            class="sheet-mask"
            :class="{ 'is-open': shown }"
            @tap="onMaskTap"
            @touchmove.stop.prevent="blockTouch"
        />
        <view
            class="sheet"
            :class="{ 'is-dragging': dragging }"
            :style="sheetStyle"
            aria-role="dialog"
            :aria-label="title || undefined"
        >
            <!-- 把手 + 标题栏是主拖拽区：catchtouchmove 防止页面跟着滚 -->
            <view
                class="sheet__handle-zone"
                @touchstart="onDragStart"
                @touchmove.stop.prevent="onDragMove"
                @touchend="onTouchEnd"
                @touchcancel="onTouchCancel"
            >
                <view class="sheet__handle" />
            </view>
            <view
                class="sheet__head"
                @touchstart="onDragStart"
                @touchmove.stop.prevent="onDragMove"
                @touchend="onTouchEnd"
                @touchcancel="onTouchCancel"
            >
                <view class="sheet__head-main">
                    <slot name="head" />
                    <text v-if="title" class="sheet__title t-balance">{{ title }}</text>
                </view>
                <view
                    class="sheet__close hit-88"
                    aria-role="button"
                    aria-label="关闭"
                    @tap="close"
                >
                    <tn-icon name="close" :size="28" />
                </view>
            </view>

            <scroll-view
                class="sheet__body"
                scroll-y
                @scroll="onBodyScroll"
                @touchstart="onBodyDragStart"
                @touchmove="onBodyDragMove"
                @touchend="onTouchEnd"
                @touchcancel="onTouchCancel"
            >
                <view class="sheet__body-inner" :style="bodyInnerStyle">
                    <slot />
                </view>
            </scroll-view>

            <view v-if="hasFoot" class="sheet__foot" :style="footStyle">
                <slot name="foot" />
            </view>
        </view>
    </view>
</template>

<script setup lang="ts">
/**
 * 可拖拽半屏↔全屏磁吸底部抽屉（01 总方案 §3.10）。
 *
 * 几何：抽屉高度 = 视口 94%，底部对齐；位移相对「视口高」：
 *   full   = 0      → 抽屉顶距屏顶 6%（留状态栏与胶囊）
 *   half   = 46%    → 抽屉顶距屏顶 52%，可视约 48% 屏高
 *   closed = 94%    → 整块移出屏幕
 * 位移小于抽屉高度的一半时，.sheet__foot 反向位移同样的距离，让脚部按钮
 * 始终贴在屏底；超过一半后反向位移封顶，脚部随抽屉一起滑出屏幕。
 * body 的内边距同步留出这段位移，保证半屏时列表尾部仍能滚到脚部上方。
 *
 * 手势：位移 > 25% 屏高 或 速度 > 0.5px/ms 才换档，否则回弹；
 * 抽屉内 scroll-view 滚动到顶（scrollTop <= 2）才把下拉手势交还给抽屉；
 * 越界（高于全屏 / 低于关闭）统一 0.35 阻力。
 *
 * 性能：拖拽期间只改 transform；拖拽中 transition: none，松手恢复 320ms spring-soft；
 * prefers-reduced-motion 下时长降为 1ms。
 */
import { computed, onMounted, onUnmounted, ref, watch } from 'vue'
import { getDeviceInfo } from '@/platform/device'
import { haptic } from '@/utils/haptics'

type SnapKey = 'closed' | 'half' | 'full'

const props = withDefaults(
    defineProps<{
        modelValue: boolean
        title?: string
        initialSnap?: 'half' | 'full'
        enableFull?: boolean
        closeOnMask?: boolean
        /** 是否渲染 foot 插槽：mp-weixin 下 $slots 恒为空对象，必须显式声明（同 bottom-bar 的 has-note） */
        hasFoot?: boolean
    }>(),
    {
        title: '',
        initialSnap: 'half',
        enableFull: true,
        closeOnMask: true,
        hasFoot: false
    }
)

const emit = defineEmits<{
    (e: 'update:modelValue', value: boolean): void
    (e: 'close'): void
    (e: 'snapChange', snap: 'half' | 'full'): void
}>()

/** 抽屉自身高度占视口高的比例 */
const SHEET_RATIO = 0.94
/** 三档 translateY，比例同样相对视口高（不是抽屉自身高度） */
const SNAP_RATIO: Record<SnapKey, number> = { closed: SHEET_RATIO, half: 0.46, full: 0 }
const DISMISS_RATIO = 0.25
const DISMISS_VELOCITY = 0.5
const EDGE_RESIST = 0.35
const SNAP_MS = 320
/** 手势启动阈值：小于 4px 视为点按，不打断当前过渡 */
const DRAG_START_PX = 4
/** 打开时先画一帧关闭态，再抬起来：否则框架会把两次状态合并，看不到上滑动画 */
const OPEN_REVEAL_MS = 24
/** body 尾部留白的最小值（px） */
const FOOT_GAP_PX = 12

function readWindowHeight(): number {
    const height = Number(getDeviceInfo().windowHeight) || 0
    return height > 0 ? height : 667
}

function readReducedMotion(): boolean {
    try {
        const matchMedia = (globalThis as any).matchMedia
        return typeof matchMedia === 'function'
            ? !!matchMedia('(prefers-reduced-motion: reduce)').matches
            : false
    } catch {
        return false
    }
}

const viewportHeight = ref(readWindowHeight())
const reducedMotion = ref(readReducedMotion())
const rendered = ref(false)
const shown = ref(false)
const dragging = ref(false)
const activeSnap = ref<SnapKey>('closed')
/** 当前 translateY（px） */
const offset = ref(0)
/** 静止档位下 foot 的反向位移（px），用于给 body 留出等量尾部空间 */
const settledLift = ref(0)
const bodyScrollTop = ref(0)

let closeTimer: ReturnType<typeof setTimeout> | null = null
let openTimer: ReturnType<typeof setTimeout> | null = null
let dragStarted = false
let moving = false
let startY = 0
let startOffset = 0
let startAt = 0
let lastY = 0
let lastAt = 0

function sheetHeight(): number {
    return viewportHeight.value * SHEET_RATIO
}

function snapOffset(snap: SnapKey): number {
    return viewportHeight.value * SNAP_RATIO[snap]
}

/** foot 反向位移：0 → half 逐像素抵消，half 之后封顶（随抽屉一起滑出） */
const footStyle = computed(() => {
    const lift = Math.min(Math.max(0, offset.value), snapOffset('half'))
    return { transform: `translate3d(0, ${-Math.round(lift)}px, 0)` }
})

/** body 尾部留白：与静止档位的 foot 位移等量，保证列表尾部能滚到脚部上方 */
const bodyInnerStyle = computed(() => ({
    paddingBottom: `${Math.round(settledLift.value) + FOOT_GAP_PX}px`
}))

const sheetStyle = computed(() => ({
    height: `${Math.round(sheetHeight())}px`,
    transform: `translate3d(0, ${Math.round(offset.value)}px, 0)`
}))

function clearCloseTimer() {
    if (!closeTimer) return
    clearTimeout(closeTimer)
    closeTimer = null
}

function clearOpenTimer() {
    if (!openTimer) return
    clearTimeout(openTimer)
    openTimer = null
}

function open() {
    clearCloseTimer()
    clearOpenTimer()
    viewportHeight.value = readWindowHeight()
    bodyScrollTop.value = 0
    rendered.value = true
    dragging.value = false
    activeSnap.value = 'closed'
    offset.value = sheetHeight()
    settledLift.value = 0

    const target: SnapKey = props.initialSnap === 'full' && props.enableFull ? 'full' : 'half'
    openTimer = setTimeout(() => {
        openTimer = null
        // 打开动画排到下一帧前可能已被关闭（快速 toggle），此时不能再抬起来
        if (!props.modelValue) return
        shown.value = true
        activeSnap.value = target
        offset.value = snapOffset(target)
        settledLift.value = Math.min(snapOffset(target), snapOffset('half'))
    }, OPEN_REVEAL_MS)
}

function playClose() {
    clearCloseTimer()
    clearOpenTimer()
    dragging.value = false
    shown.value = false
    activeSnap.value = 'closed'
    offset.value = sheetHeight()
    settledLift.value = 0
    closeTimer = setTimeout(
        () => {
            rendered.value = false
            closeTimer = null
        },
        reducedMotion.value ? 30 : SNAP_MS + 40
    )
}

function close() {
    if (!props.modelValue) return
    emit('update:modelValue', false)
    emit('close')
    // 单向绑定（父级只听 close 不回写 v-model）时也要收起，不能停在遮罩上
    playClose()
}

function onMaskTap() {
    if (!props.closeOnMask) return
    close()
}

watch(
    () => props.modelValue,
    (visible) => {
        if (visible) open()
        else if (rendered.value) playClose()
    }
)

onMounted(() => {
    reducedMotion.value = readReducedMotion()
    if (props.modelValue) open()
})

onUnmounted(() => {
    clearCloseTimer()
    clearOpenTimer()
})

/** 遮罩上的 touchmove 只用于吃掉事件，锁住底层页面滚动 */
function blockTouch() {
    // no-op：拦截由 .stop.prevent 完成
}

function readTouchY(event: any): number | null {
    const touch =
        (event?.touches && event.touches[0]) || (event?.changedTouches && event.changedTouches[0])
    return touch ? Number(touch.clientY) : null
}

function onDragStart(event: any) {
    if (!props.modelValue) return
    const y = readTouchY(event)
    if (y === null) return
    dragStarted = true
    moving = false
    startY = y
    lastY = y
    startOffset = offset.value
    startAt = Date.now()
    lastAt = startAt
}

function applyDrag(currentY: number) {
    const raw = startOffset + (currentY - startY)
    const min = snapOffset('full')
    const max = sheetHeight()

    let next = raw
    if (next < min) next = min + (next - min) * EDGE_RESIST
    else if (next > max) next = max + (next - max) * EDGE_RESIST

    offset.value = next
    lastY = currentY
    lastAt = Date.now()
}

function onDragMove(event: any) {
    if (!dragStarted) return
    const y = readTouchY(event)
    if (y === null) return

    if (!moving && Math.abs(y - startY) < DRAG_START_PX) return
    moving = true
    dragging.value = true
    applyDrag(y)
}

/** 内容区：滚动到顶才允许把下拉手势交给抽屉，否则继续交给 scroll-view */
function onBodyDragStart(event: any) {
    if (bodyScrollTop.value > 2) return
    onDragStart(event)
}

function onBodyDragMove(event: any) {
    if (!dragStarted) return
    const y = readTouchY(event)
    if (y === null) return
    if (y - startY > 0 && bodyScrollTop.value > 2) {
        cancelDrag()
        return
    }
    onDragMove(event)
}

function onBodyScroll(event: any) {
    bodyScrollTop.value = Number(event?.detail?.scrollTop) || 0
}

function syncSettledLift() {
    settledLift.value = Math.min(Math.max(0, offset.value), snapOffset('half'))
}

function cancelDrag() {
    dragStarted = false
    moving = false
    dragging.value = false
    offset.value = snapOffset(activeSnap.value)
    syncSettledLift()
}

function lowerSnap(snap: SnapKey): SnapKey {
    if (snap === 'full') return 'half'
    if (snap === 'half') return 'closed'
    return 'closed'
}

function upperSnap(snap: SnapKey): SnapKey {
    if (snap === 'closed') return 'half'
    if (snap === 'half') return props.enableFull ? 'full' : 'half'
    return 'full'
}

function settleTo(snap: SnapKey) {
    const prev = activeSnap.value
    dragging.value = false

    if (snap === 'closed') {
        activeSnap.value = 'closed'
        if (prev !== 'closed') {
            haptic('light')
            emit('update:modelValue', false)
            emit('close')
            playClose()
        } else {
            offset.value = sheetHeight()
            syncSettledLift()
        }
        return
    }

    if (prev !== snap) {
        activeSnap.value = snap
        emit('snapChange', snap)
        haptic(snap === 'full' ? 'medium' : 'light')
    }
    offset.value = snapOffset(snap)
    syncSettledLift()
}

function onTouchEnd() {
    const wasDragging = dragStarted && moving
    dragStarted = false
    moving = false
    if (!wasDragging) {
        dragging.value = false
        return
    }

    const delta = offset.value - startOffset
    const elapsed = Math.max(1, lastAt - startAt)
    const velocity = (lastY - startY) / elapsed

    const far = Math.abs(delta) >= viewportHeight.value * DISMISS_RATIO
    const fast = Math.abs(velocity) >= DISMISS_VELOCITY

    if (delta > 0) {
        // 下拉：降一档（half → closed，full → half）
        settleTo(far || fast ? lowerSnap(activeSnap.value) : activeSnap.value)
    } else if (delta < 0) {
        // 上推：升一档（closed → half，half → full）
        settleTo(far || fast ? upperSnap(activeSnap.value) : activeSnap.value)
    } else {
        settleTo(activeSnap.value)
    }
}

function onTouchCancel() {
    if (!dragStarted) return
    cancelDrag()
}
</script>

<style lang="scss" scoped>
.sheet-mask {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    z-index: 300;
    background: var(--c-mask);
    opacity: 0;
    transition: opacity 240ms var(--ease-out);
}

.sheet-mask.is-open {
    opacity: 1;
}

.sheet {
    position: fixed;
    bottom: 0;
    left: 0;
    right: 0;
    z-index: 301;
    display: flex;
    flex-direction: column;
    background: var(--c-surface-float);
    border-radius: var(--r-48) var(--r-48) 0 0;
    box-shadow: var(--shadow-sheet);
    overflow: hidden;
    will-change: transform;
    transition: transform 320ms var(--ease-spring-soft);
}

.sheet.is-dragging {
    transition: none;
}

.sheet__handle-zone {
    display: flex;
    justify-content: center;
    padding: var(--sp-3) 0 var(--sp-1);
}

.sheet__handle {
    width: 72rpx;
    height: 8rpx;
    border-radius: var(--r-pill);
    background: var(--c-line-strong);
}

.sheet__head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: var(--sp-2);
    min-height: 88rpx;
    padding: 0 var(--sp-4) var(--sp-2);
}

.sheet__head-main {
    flex: 1;
    min-width: 0;
    display: flex;
    align-items: center;
}

.sheet__title {
    flex: 1;
    min-width: 0;
    font-size: var(--fs-lg);
    font-weight: var(--fw-semibold);
    line-height: var(--lh-tight);
    color: var(--c-ink);
}

.sheet__close {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 56rpx;
    height: 56rpx;
    color: var(--c-ink-3);
}

.sheet__body {
    flex: 1;
    min-height: 0;
}

.sheet__body-inner {
    padding: 0 var(--sp-4) var(--sp-3);
}

/* 脚部贴在屏底：与抽屉反向位移同频，拖拽时跟随手指，松手与抽屉同步回弹 */
.sheet__foot {
    position: relative;
    z-index: 1;
    background: var(--c-surface-float);
    padding: var(--sp-3) var(--sp-4) calc(var(--sp-3) + var(--safe-bottom));
    transition: transform 320ms var(--ease-spring-soft);
}

.sheet.is-dragging .sheet__foot {
    transition: none;
}

@media (prefers-reduced-motion: reduce) {
    .sheet,
    .sheet__foot {
        transition-duration: 1ms;
    }
    .sheet-mask {
        transition: none;
    }
}
</style>
