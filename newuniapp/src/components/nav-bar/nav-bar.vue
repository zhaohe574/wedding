<template>
    <view class="nav" :class="navClass" :style="navStyle">
        <view :style="{ height: statusBarHeight + 'px' }" />

        <view class="nav__bar" :style="barStyle">
            <!--
                左区：具名 slot（如「关闭」）与返回键共用一个盒子。
                ⚠ mp-weixin 下 uni-app 移除了 initSlots，$slots 恒为空对象 —— 不能用
                v-if="$slots.left" 判断「有没有传 slot」，那样返回键和 slot 都会失灵。
                这里靠 CSS：容器不设 width，由内容撑开（返回键 88rpx / slot 自身宽度 / 空则 0）。
            -->
            <view class="nav__left" :class="{ 'nav__left--flush': titleAlign === 'left' }">
                <slot name="left" />
                <view
                    v-if="showBack"
                    class="nav__back"
                    hover-class="is-pressed"
                    aria-role="button"
                    :aria-label="backLabel"
                    @tap="onBack"
                >
                    <tn-icon name="left" :size="36" />
                </view>
            </view>

            <text class="nav__title" :class="titleClass" :style="titleStyle">
                {{ title }}
            </text>

            <view class="nav__right" :style="rightStyle">
                <slot name="right" />
            </view>
        </view>
    </view>

    <!--
        占位块：只有「页面加载起就是实底」的 solid 才需要，因为它 fixed 且不透明，
        必须在文档流内预留高度，否则内容被盖住。
        transparent / scroll-solid 初始都是透明的，内容从 y=0 开始是沉浸式设计意图；
        scroll-solid 过阈值后虽然变实底，但仍只是 fixed 覆盖在已滚上来的内容上，
        此时在流内凭空插入约 88px 会把整个 .shell__main 推下去（可感知的内容下跳）。
        注意：这里判的是 variant，不是 isSolidState —— 后者仍由 navStyle / effectiveTone 使用，
        用来在滚动过阈值时切背景色、描边与文字色，两者语义不同，不能合并。
    -->
    <view v-if="fixed && variant === 'solid'" :style="{ height: totalHeight + 'px' }" />
</template>

<script setup lang="ts">
/**
 * 自定义导航栏（24 屏唯一的头部实现）。
 *
 * 小程序 navigationStyle=custom 时没有系统导航，必须自己处理状态栏高度，
 * 否则内容会顶到刘海下面。
 *
 * 契约边界：
 *  - scrollTop 由页面 @scroll 提供，组件不监听全局滚动、不感知 pageStatus。
 *  - 返回链路：backMode='auto' 走默认（栈深>1 → navigateBack，否则回首页 tab）；
 *    backMode='none' 只 emit('back')，导航交给页面（如「关闭」语义）。
 *  - capsuleAvoid 启用时右侧 slot 避让微信右上角胶囊，优先用胶囊真实位置精算，
 *    取不到再退 --nav-capsule-reserve（200rpx）兜底。
 *
 * 颜色口径：tone 决定「透明/无底色」时的文字与图标色；一旦切到实底态，
 * 颜色强制回深色，否则 light 页滚到底会出现「白图标压白底」不可见。
 */
import { computed } from 'vue'
import { getDeviceInfo } from '@/platform/device'

const props = withDefaults(
    defineProps<{
        title?: string
        /** @deprecated 不参与任何计算，请改用 variant / tone */
        transparent?: boolean
        fixed?: boolean
        showBack?: boolean
        titleColor?: string
        /** transparent / solid / scroll-solid（滚动切实底由页面传 scrollTop 驱动） */
        variant?: 'transparent' | 'solid' | 'scroll-solid'
        /** 右侧动作避让微信胶囊 */
        capsuleAvoid?: boolean
        /** 页面 @scroll 的滚动距离（px），组件不自行监听 */
        scrollTop?: number
        /** 返回行为：auto=组件自己导航，none=只 emit('back') */
        backMode?: 'auto' | 'none'
        /** 返回键无障碍文案 */
        backLabel?: string
        /** 文字/图标基调：dark=深墨压浅底，light=白压深色图 */
        tone?: 'dark' | 'light'
        /** 标题形态：nav=导航条常规字号，display=大标题（tab 页） */
        titleVariant?: 'nav' | 'display'
        titleAlign?: 'center' | 'left'
        /** 实底态底色：surface=卡片白，page=页面米底 */
        background?: 'surface' | 'page'
        /** scroll-solid 的切实底阈值（px） */
        solidThreshold?: number
    }>(),
    {
        title: '',
        transparent: false,
        fixed: true,
        showBack: true,
        titleColor: '',
        variant: 'solid',
        capsuleAvoid: false,
        scrollTop: 0,
        backMode: 'auto',
        backLabel: '返回',
        tone: 'dark',
        titleVariant: 'nav',
        titleAlign: 'center',
        background: 'surface',
        solidThreshold: 8
    }
)

const emit = defineEmits<{ (e: 'back'): void }>()

const { statusBarHeight } = getDeviceInfo()
const BAR_HEIGHT = 44
const totalHeight = computed(() => statusBarHeight + BAR_HEIGHT)

/** 实底态：solid 恒实底；scroll-solid 过阈值才实底；transparent 永不实底 */
const isSolidState = computed(
    () =>
        props.variant === 'solid' ||
        (props.variant === 'scroll-solid' && (props.scrollTop ?? 0) > props.solidThreshold)
)

const navClass = computed(() => ({
    'nav--fixed': props.fixed,
    'nav--scroll-solid': props.variant === 'scroll-solid',
    // tone 变成 class：tn-icon 不认 var()，图标颜色只能靠父级继承。
    // 标题仍走 :style 内联（内联是能生效的），所以标题和图标必须用两套机制。
    'nav--tone-light': effectiveTone.value === 'light',
    'nav--tone-dark': effectiveTone.value === 'dark'
}))

/** 实底后强制深色，避免 tone='light' 的页面滚到底出现白压白 */
const effectiveTone = computed<'dark' | 'light'>(() => (isSolidState.value ? 'dark' : props.tone))

const resolvedTitleColor = computed(
    () =>
        props.titleColor || (effectiveTone.value === 'light' ? 'var(--c-on-dark)' : 'var(--c-ink)')
)

const titleClass = computed(() => ({
    'nav__title--display': props.titleVariant === 'display',
    'nav__title--left': props.titleAlign === 'left'
}))

const navStyle = computed(() => {
    const solid = isSolidState.value
    return {
        backgroundColor: solid
            ? props.background === 'page'
                ? 'var(--c-bg)'
                : 'var(--c-surface)'
            : 'transparent',
        borderBottom: solid ? '1rpx solid var(--c-line)' : 'none',
        color: resolvedTitleColor.value
    }
})

/**
 * 胶囊几何：一次取值，派生出两个**不同**的避让量。
 *
 * 只有 mp-weixin 有「右上角胶囊」这个概念，也只有它有 getMenuButtonBoundingClientRect()。
 * 所以精算整段写在 #ifdef MP-WEIXIN 里：H5 / 支付宝 / 其他端编译后这段被整块剥掉，
 * 直接走 --nav-capsule-reserve 兜底，绝不会算出 0 或 NaN。
 *
 * 为什么是两个值（差在 .nav__bar 的右内边距不对称）：
 *  - action：右区动作要「贴到胶囊左边界」，右区右边缘在 win - padRight，
 *            所以 margin-right = win - padRight - capsuleLeft。
 *  - title ：标题用**左右对称**上限，盒子右边缘必须 ≤ capsuleLeft。
 *            标题中心在 win/2，所以半宽上限 = capsuleLeft - win/2，
 *            折成 margin 就是 win - capsuleLeft。
 *            若错用 action 那个值做对称上限，盒子右边缘会比胶囊左边界多出
 *            padRight(375px 屏上 12px) —— 长标题最后几个字会钻到胶囊底下。
 *  375px 屏（胶囊 left=281）：action=82，title=94 → 标题盒 93.5..281，正好贴边不越界。
 *
 * 三重防御：API 存在性 → 返回值合法性（0 < left < win）→ try/catch。
 */
const capsule = computed<{ action: string; title: string }>(() => {
    // #ifdef MP-WEIXIN
    try {
        const api = uni as unknown as {
            getMenuButtonBoundingClientRect?: () => { left?: number }
        }
        const rect =
            typeof api.getMenuButtonBoundingClientRect === 'function'
                ? api.getMenuButtonBoundingClientRect()
                : null
        const win = getDeviceInfo().windowWidth || 375
        // .nav__bar 右内边距 = --sp-3(24rpx)，按当前屏宽换算成 px
        const padRight = uni.upx2px(24)
        const left = Number(rect?.left ?? 0)
        if (Number.isFinite(left) && left > 0 && left < win) {
            const action = Math.round(win - padRight - left)
            const title = Math.round(win - left)
            if (action > 0 && title > 0) {
                return { action: `${action}px`, title: `${title}px` }
            }
        }
    } catch {
        /* 落到 token 兜底 */
    }
    // #endif
    const fallback = 'var(--nav-capsule-reserve)'
    return { action: fallback, title: fallback }
})

/** 右区：避让量只把动作推到胶囊左边，不参与标题居中计算 */
const rightStyle = computed(() => ({
    marginRight: props.capsuleAvoid ? capsule.value.action : ''
}))

/**
 * 把「标题单侧内缩量」写成 CSS 变量 --nav-reserve，供 .nav__title 的 max-width 使用。
 * 标题绝对居中（left:50% + translateX(-50%)），不再受 .nav__right 的 margin 影响。
 *
 * 为什么两种情况都要注入（不能只在 capsuleAvoid 时注入）：
 *   标题绝对定位后已经脱离了 .nav__bar 的 padding，max-width 是它唯一的水平约束。
 *   如果非 capsuleAvoid 页面不注入，var() 取兜底 0 → max-width:100% → 标题盒铺满
 *   0..W，把 .nav__bar 的 padding: 0 var(--sp-3) 绕过去了（后端下发的长标题会贴到
 *   屏幕边缘，0 呼吸空间）。所以非胶囊页注入 padRight，让标题盒退回 12..W-12。
 *
 * 降级路径（必须知道，不要以为 100% 是正常态）：
 *   若当前基础库不支持「行内注入 CSS 自定义属性」，--nav-reserve 根本不会被写进样式，
 *   var(--nav-reserve, 0px) 取到兜底 0px → max-width 退化成 100%。
 *   后果：标题仍然屏幕居中、不会消失、不会崩，只是长标题少了胶囊保护（短标题无影响，
 *   因为文字本身没那么长）。这是可接受的降级，不是正常态 ——
 *   正常态在胶囊页应是 max-width = 100% - 2 × 94px（375px 屏）。
 */
const barStyle = computed(
    () =>
        ({
            '--nav-reserve': props.capsuleAvoid
                ? capsule.value.title
                : `${Math.round(uni.upx2px(24))}px`
        } as unknown as Record<string, string>)
)

/**
 * 标题：居中模式下必须「屏幕居中」，不能是「剩余空间居中」。
 *
 * 反例（本次修的 bug）：把避让量做成 .nav__right 的 margin-right 时，
 * flex 会把这份宽度从标题盒的**右侧**扣掉，text-align:center 是在扣完的盒子里居中，
 * 于是标题中心左偏 reserve/2（375px 屏上约 41px），短标题肉眼可见歪。
 *
 * 现在的做法见 .nav__title：绝对定位 + left:50% + translateX(-50%)，
 * 居中基准只由 .nav__bar 决定，与左右区宽度彻底解耦。
 */
const titleStyle = computed<Record<string, string>>(() => ({
    color: resolvedTitleColor.value
}))

function onBack() {
    emit('back')
    if (props.backMode === 'none') return
    const pages = getCurrentPages()
    if (pages.length > 1) uni.navigateBack()
    else uni.switchTab({ url: '/pages/index/index' })
}
</script>

<style lang="scss" scoped>
.nav {
    /* tone=light：透明导航压在深色 hero 上，返回键必须反白，否则看不见 */
    &--tone-light .nav__back {
        color: var(--c-on-dark);
    }

    width: 100%;
    z-index: 99; /* 必须 > .chip-bar 的 50（体验规范 §2.5） */

    &--fixed {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
    }

    /* 滚动切实底（240ms / ease-out）：背景、描边、文字/图标基色一起过渡 */
    &--scroll-solid {
        transition: background-color var(--dur-base) var(--ease-out),
            border-color var(--dur-base) var(--ease-out), color var(--dur-base) var(--ease-out);
    }

    &__bar {
        /* 44px 固定：胶囊行物理高度不随屏宽缩放，用 px 不用 rpx */
        height: 44px;
        position: relative; /* 居中标题（position:absolute）的定位基准 */
        display: flex;
        align-items: center;
        /* 标题脱离 flex 后，左右区靠 space-between 分列两端 */
        justify-content: space-between;
        padding: 0 var(--sp-3);
    }

    &__left,
    &__right {
        min-height: 88rpx;
        display: flex;
        align-items: center;
        flex: none;
        /* 动作区必须压在绝对居中的标题之上：长标题时标题不能吃掉点击热区 */
        position: relative;
        z-index: 2;
    }

    /*
     * 左区宽度：
     *  - 居中标题模式 → 恒占 88rpx，与右区对称（标题虽已绝对居中，但左右对称
     *    能让「返回键 + 右侧动作」在视觉上成对，不会一宽一窄）；
     *  - titleAlign='left' → --flush 塌陷成 0，大标题从 sp-3 起，跟随左区。
     * 左区内部仍由内容撑开（返回键 88rpx / #nav-left 自身宽度 / 空则 0），
     * 因为 mp-weixin 下无法探测 slot 是否存在。
     */
    &__left {
        min-width: 88rpx;
    }

    &__left--flush {
        min-width: 0;
    }

    &__back {
        width: 88rpx;
        height: 88rpx;
        display: flex;
        align-items: center;
        justify-content: flex-start;
        /* 返回键图标继承父级；tone 由根节点 class 决定（原 :color 传 var() 无效） */
        color: var(--c-ink);
    }

    &__right {
        /* min-width 而非 width：右侧可能是文字动作（如「全部已读」），
           写死 88rpx 会把文字挤换行。88rpx 仍是最小热区保证。 */
        min-width: 88rpx;
        justify-content: flex-end;
    }

    /*
     * 标题（居中模式，默认）：绝对居中 + 对称上限。
     *
     * ⚠ 不要改回 flex:1 + text-align:center —— 那样标题是在「扣掉左右区之后的
     * 剩余空间」里居中，一旦右区因为胶囊避让变宽（margin-right ≈ 82px），
     * 标题中心就会左偏 reserve/2（375px 屏上约 41px）。
     *
     * 三条关键声明，改动前先读这段：
     *   position:absolute + left:50% + transform:translateX(-50%)
     *     → 居中基准是 .nav__bar（position:relative），与左右区宽度彻底解耦；
     *   max-width: calc(100% - 左右各一份 --nav-reserve)
     *     → 对称上限，长标题也不会压到右上角胶囊；
     *       胶囊页 --nav-reserve = win - capsuleLeft（375px 屏 = 94px）；
     *       非胶囊页 = padRight（12px），让标题盒退回 padding 内。
     *   width:100%
     *     → 不写的话 left:50% 会让可用宽度只剩半屏，max-width 的上限用不满。
     *
     * 降级：若基础库不支持行内 CSS 自定义属性，--nav-reserve 缺失 → max-width 退化成
     * 100%（标题仍居中、不消失，只是长标题少一层胶囊保护）。100% 是降级态不是正常态，
     * 详见 barStyle 的注释。
     *
     * 为什么写两次减法而不是 `calc(100% - 2 * var(--nav-reserve))`：
     * 两者完全等价，选前者只是让 var() 参与运算时读起来更直观。
     * **乘号本身是合法 CSS3**（CSS Values and Units Level 3 §8.1.2：`*` 两侧只要有一侧是
     * <number> 就合法，`calc(2 * 10px)` 从 IE9 起就支持）。
     * ⚠ 曾有一版注释把「calc 乘号需要 Chrome 111 / Safari 17」写成兼容红线 —— 那是错的，
     * 已撤回。看到这句话的人请不要再把它当约束，也不要扩散到别处的 calc（比如 padding）。
     * CSS Values 4 新增的只是「两个长度相乘」（`calc(10px * 5px)` → px²，仍然非法），与本处无关。
     *
     * 真正要留意的兼容点是 WXSS 对 `rpx` 在 `calc()` 内的编译，本场景不受影响：
     * `--nav-capsule-reserve: 200rpx` 在 tokens.css 里，构建期就被转成 px，
     * 运行时 `var()` 替换出来已经是 px。
     */
    &__title {
        position: absolute;
        left: 50%;
        transform: translateX(-50%);
        width: 100%;
        max-width: calc(100% - var(--nav-reserve, 0px) - var(--nav-reserve, 0px));
        z-index: 1;
        min-width: 0;
        overflow: hidden;
        white-space: nowrap;
        text-overflow: ellipsis;
        text-align: center;
        font-size: var(--fs-lg);
        font-weight: var(--fw-semibold); /* 500 → 600，H3（视觉规范 §3.1） */
        line-height: var(--lh-tight);
    }

    /* tab 页大标题形态（feed / query / user） */
    &__title--display {
        font-size: var(--fs-34);
        font-weight: var(--fw-semibold);
        letter-spacing: 0.03em;
    }

    /*
     * 左对齐模式（feed / query / user）：回到 flex 流里跟着左区走，
     * 必须把绝对居中的四条声明全部复位，否则会和 .nav__title 打架。
     */
    &__title--left {
        position: static;
        left: auto;
        transform: none;
        width: auto;
        max-width: none;
        flex: 1;
        text-align: left;
    }
}
</style>
