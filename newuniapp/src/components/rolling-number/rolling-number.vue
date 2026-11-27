<template>
    <view class="rn t-num" :style="rootStyle">
        <text v-if="prefix" class="rn__affix">{{ prefix }}</text>
        <view
            v-for="(item, index) in chars"
            :key="item.key"
            class="rn__unit"
            :class="item.digit >= 0 ? 'rn__unit--digit' : 'rn__unit--symbol'"
        >
            <!-- 数字位：0–9 纵向排两圈（共 20 格），整条数字带一次 transform 滚到目标格 -->
            <view v-if="item.digit >= 0" class="rn__slot">
                <view
                    class="rn__reel"
                    :class="{ 'is-instant': instant[index] }"
                    :style="reelStyle(index, item)"
                >
                    <text v-for="cell in CELLS" :key="cell" class="rn__cell">{{ cell % 10 }}</text>
                </view>
            </view>
            <!-- 符号位（千分位逗号 / 小数点 / 负号）：静态占位，不参与滚动 -->
            <text v-else class="rn__symbol">{{ item.char }}</text>
        </view>
        <text v-if="suffix" class="rn__affix">{{ suffix }}</text>
    </view>
</template>

<script setup lang="ts">
/**
 * 滚轮数字（01 总方案 §3.7）。
 *
 * 与 useCountUp 的分工：这里是 GPU 滚轮（视觉主方案），逐帧不改文字；
 * 只有非滚轮场景（Canvas 等）才回退 useCountUp。
 *
 * 性能：每次数值变化只写 1 次 transform，无定时器、无逐帧 setData。
 */
import { computed, nextTick, onUnmounted, ref, watch } from 'vue'

interface CharItem {
    key: string
    char: string
    /** -1 = 非数字（符号位） */
    digit: number
}

const props = withDefaults(
    defineProps<{
        value: number | string
        decimals?: number
        prefix?: string
        suffix?: string
        /** 千分位逗号 */
        thousands?: boolean
        /** 首位数字的基础时长（ms） */
        duration?: number
        /** > 0 时锁定 min-width，按目标位数预留 ch，防止数字展开顶开布局 */
        minChars?: number
    }>(),
    {
        decimals: 0,
        prefix: '',
        suffix: '',
        thousands: false,
        duration: 1080,
        minChars: 0
    }
)

/** 两圈 20 格：第二圈（10–19）是稳定落点，第一圈只用于「回卷」时保持同一视觉数字 */
const CELLS: number[] = Array.from({ length: 20 }, (_, i) => i)
/** 低位错峰：步进 150ms / 延迟 45ms，总时长封顶 1680ms */
const STEP_MS = 150
const DELAY_MS = 45
const MAX_DURATION = 1680

function groupThousands(intPart: string): string {
    const sign = intPart.charAt(0) === '-' ? '-' : ''
    const digits = sign ? intPart.slice(1) : intPart
    let out = ''
    for (let i = 0; i < digits.length; i++) {
        if (i > 0 && (digits.length - i) % 3 === 0) out += ','
        out += digits.charAt(i)
    }
    return sign + out
}

const formatted = computed(() => {
    const numeric = Number(props.value)
    if (!Number.isFinite(numeric)) return String(props.value ?? '')
    const fixed = numeric.toFixed(Math.max(0, props.decimals))
    if (!props.thousands) return fixed
    const parts = fixed.split('.')
    return [groupThousands(parts[0]), parts[1]].filter((part) => part !== undefined).join('.')
})

const chars = computed<CharItem[]>(() => {
    const text = formatted.value
    const out: CharItem[] = []
    for (let i = 0; i < text.length; i++) {
        const char = text.charAt(i)
        const code = char.charCodeAt(0)
        const isDigit = code >= 48 && code <= 57
        out.push({ key: `${i}-${char}`, char, digit: isDigit ? code - 48 : -1 })
    }
    return out
})

/** 每个字符位的当前格位（数字位 0–19） */
const positions = ref<number[]>([])
/** true = 该位本次不做过渡（回卷到第一圈 / 首帧起步时使用） */
const instant = ref<boolean[]>([])

/** 首帧放行定时器：等浏览器/小程序真正绘制一帧后再开过渡，否则两次状态会被合并 */
let revealTimer: ReturnType<typeof setTimeout> | null = null

function clearRevealTimer() {
    if (!revealTimer) return
    clearTimeout(revealTimer)
    revealTimer = null
}

/** 数字位在整串里的序号（从高位到低位），决定错峰时长与延迟 */
function digitOrder(index: number): number {
    let order = 0
    for (let i = 0; i < index; i++) {
        if (chars.value[i] && chars.value[i].digit >= 0) order++
    }
    return order
}

function reelStyle(index: number, item: CharItem) {
    const pos = positions.value[index] ?? 10 + item.digit
    const order = digitOrder(index)
    const duration = Math.min(props.duration + order * STEP_MS, MAX_DURATION)
    return {
        transform: `translate3d(0, -${(pos * 1.16).toFixed(3)}em, 0)`,
        transitionDuration: `${duration}ms`,
        transitionDelay: `${order * DELAY_MS}ms`
    }
}

/** 放开过渡，把所有数字位滚到第二圈目标格 */
function rollToTargets(list: CharItem[]) {
    instant.value = list.map(() => false)
    positions.value = list.map((item) => (item.digit >= 0 ? 10 + item.digit : 0))
}

/**
 * 数值变化：
 *   目标格恒为「第二圈」10 + digit，保证滚入的是第二圈目标数字；
 *   首次挂载 / 新增数字位：先以 is-instant 停在第一圈 0 位，等一帧真实绘制后
 *     再放开过渡滚向 10 + digit —— 否则首帧直接落在终态，完全看不到滚动；
 *   数字变小：先把该位瞬移到第一圈同位（视觉不变），下一帧再动画到第二圈目标位，
 *     观感始终向前滚，不倒卷。
 */
watch(
    () => chars.value.map((item) => item.char).join(''),
    () => {
        const list = chars.value
        const nextPositions: number[] = []
        const nextInstant: boolean[] = []
        let hasNewSlot = false

        list.forEach((item, index) => {
            if (item.digit < 0) {
                nextPositions[index] = 0
                nextInstant[index] = false
                return
            }
            const prev = positions.value[index]
            const target = 10 + item.digit

            if (typeof prev !== 'number') {
                // 首次出现：停在本圈起点 0，等待 24ms 后开滚
                nextPositions[index] = 0
                nextInstant[index] = true
                hasNewSlot = true
                return
            }
            if (target === prev) {
                nextPositions[index] = prev
                nextInstant[index] = false
                return
            }
            if (target < prev) {
                nextPositions[index] = prev - 10
                nextInstant[index] = true
                return
            }
            nextPositions[index] = target
            nextInstant[index] = false
        })

        clearRevealTimer()
        positions.value = nextPositions
        instant.value = nextInstant

        if (hasNewSlot) {
            revealTimer = setTimeout(() => {
                revealTimer = null
                rollToTargets(list)
            }, 24)
        } else {
            nextTick(() => rollToTargets(list))
        }
    },
    { immediate: true }
)

onUnmounted(clearRevealTimer)

const rootStyle = computed(() =>
    props.minChars > 0 ? { minWidth: `${props.minChars}ch` } : {}
)
</script>

<style lang="scss" scoped>
.rn {
    display: inline-flex;
    align-items: baseline;
    /* 等宽由 .t-num 兜底；这里只锁纵向裁切盒的行高，保持数字基线稳定 */
    line-height: var(--lh-none);
}

.rn__unit {
    display: inline-flex;
}

.rn__affix {
    display: inline-block;
}

.rn__symbol {
    display: inline-block;
}

/* 单格裁切盒：高度 = 行高，保证一次只露出一格 */
.rn__slot {
    height: 1.16em;
    line-height: 1.16em;
    overflow: hidden;
}

/* 数字带：transition-property / timing 固定在样式里，时长与延迟由内联按位错峰 */
.rn__reel {
    will-change: transform;
    transition-property: transform;
    transition-timing-function: var(--ease-decel);
    transition-duration: 1080ms;
    transition-delay: 0ms;
}

.rn__reel.is-instant {
    transition-property: none;
}

.rn__cell {
    display: block;
    height: 1.16em;
    line-height: 1.16em;
    text-align: center;
}

@media (prefers-reduced-motion: reduce) {
    .rn__reel {
        transition-property: none;
    }
}
</style>
