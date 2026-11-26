<template>
    <view
        class="shell"
        :class="[`shell--${bg}`, shellSchemeClass, { 'shell--locked': isLocked }]"
        :style="shellStyle"
    >
        <!--
            自定义导航（小程序 navigationStyle:custom 时没有系统导航，状态栏高度必须自己补）。
            注意：不要给 nav-bar 传 `transparent` —— 那个 prop 已 @deprecated 且不参与任何计算，
            `variant` 是「透明 / 实底 / 滚动切实底」的唯一真源。两套真源必然漂移。
        -->
        <nav-bar
            v-if="nav"
            :title="title"
            :show-back="showBack"
            :variant="navVariant"
            :scroll-top="scrollTop"
            :title-color="navTitleColor"
            :tone="navTone"
            :title-align="navTitleAlign"
            :title-variant="navTitleVariant"
            :background="navBackground"
            :solid-threshold="navSolidThreshold"
            :capsule-avoid="navCapsuleAvoid"
            :back-mode="navBackMode"
            @back="emit('nav-back')"
        >
            <!-- 左侧具名 slot 无条件透传：mp-weixin 下 $slots 恒为空，不能拿它做存在性判断；
                 nav-bar 内部靠 CSS 决定左区宽度（有 slot 内容就撑开，没有就是 0） -->
            <template #left><slot name="nav-left" /></template>
            <template #right><slot name="nav-right" /></template>
        </nav-bar>

        <!-- tab 页统一由容器挂 tabbar：4 个 tab 页不再各写一份。
             放在页面内容之前创建，首次切 Tab 时底部导航与内容同帧出现，不会最后补一块。 -->
        <app-tabbar v-if="tabbar" :current="current" />

        <!--
            内置自定义下拉刷新：锁定视口 + scroll-view 承载滚动，下拉阻尼在组件里算完，
            顶部导航不再跟着真机手势下移。吸顶条走 #sticky（在 scroll-view 之外，不随内容滚动）。
        -->
        <template v-if="pullRefresh">
            <view class="shell__sticky">
                <slot name="sticky" />
            </view>
            <scroll-view
                class="shell__scroll"
                scroll-y
                :enhanced="true"
                :bounces="false"
                :show-scrollbar="false"
                :upper-threshold="10"
                :lower-threshold="80"
                @scroll="onPullScroll"
                @scrolltoupper="onPullScrollUpper"
                @scrolltolower="emit('reach-bottom')"
            >
                <view
                    class="shell__scroll-body"
                    :class="{
                        'shell__main--tabbar': tabbar,
                        'shell__main--bottom': bottomBar
                    }"
                    :style="mainStyle"
                    @touchstart="onPullTouchStart"
                    @touchmove="onPullTouchMove"
                    @touchend="onPullTouchEnd"
                    @touchcancel="onPullTouchEnd"
                >
                    <pull-refresh
                        :state="pullState"
                        :height="pullHeight"
                        :progress="pullProgress"
                        :dragging="pullDragging"
                        :tone="pullTone"
                        :top-inset="pullTopInset"
                    />
                    <slot />
                </view>
            </scroll-view>
        </template>
        <view
            v-else
            class="shell__main"
            :class="{
                'shell__main--tabbar': tabbar && !lockViewport,
                'shell__main--bottom': bottomBar && !lockViewport,
                'shell__main--locked': lockViewport
            }"
            :style="mainStyle"
        >
            <slot />
        </view>

        <!-- 吸底操作栏（bottom-bar）挂这里；页面私有浮层也可放 -->
        <slot name="bottom" />

        <!-- 隐私授权弹窗（合规 B1）：挂 page-shell 而不是 App.vue ——
             小程序端 App 根没有 wxss 输出路径，组件样式会变 0 字节（app-toast 踩过）。
             只有真正需要弹窗时才创建 tn-popup 的全屏 fixed 容器，避免 50 页首帧各带一份。 -->
        <privacy-popup v-if="privacyVisible" />

        <!-- 服务号提醒软引导：每个页面一个宿主实例，靠 hostId 只让归属页渲染 -->
        <oa-reminder-dialog />
    </view>
</template>

<script setup lang="ts">
/**
 * 页面容器（24 屏的统一外壳）。
 *
 * 职责边界（不要越界）：
 *  - 只负责这些事：状态栏/导航避让、底部安全区留白、tabBar 挂载、（pullRefresh 时）内置滚动与下拉刷新。
 *  - 不做数据请求、不做登录态判断、不做页面级 loading（用 base-skeleton / base-empty）。
 *  - 默认不接管滚动：scroll-solid 导航需要页面把 @scroll 的 scrollTop 传进来。
 *    （lockViewport 例外：页面自己用内置 scroll-view 做局部滚动时，容器只负责锁死外层视口。
 *     pullRefresh 例外：容器内置 scroll-view + 自定义阻尼下拉刷新，彻底消除真机上下拉
 *     把顶部固定导航一起拖下来的问题；页面只监听 @refresh / @reach-bottom / @scroll。）
 *
 * 何时不用它：全屏沉浸页（首页 hero 图满屏压到状态栏下）用 navVariant="transparent"，
 * 容器不铺底色（bg="transparent"），页面自己绝对定位主图。
 * navVariant="scroll-solid" 的页面（头图 + 滚动切实底）同样从 y=0 起沉浸：
 * 顶部是深色图，下拉指示器跟 navTone 走浅色、并避让状态栏（见 pullTone / pullTopInset）；
 * 只有 solid 的浅底页才既不用避让、也固定深色指示器。
 */
import { computed, onMounted, onUnmounted, ref, watch } from 'vue'
import { onHide, onShow, onUnload } from '@dcloudio/uni-app'
import { privacyVisible } from '@/composables/usePrivacyGuard'
import { getDeviceInfo } from '@/platform/device'
import { useColorSchemeStore } from '@/stores/colorScheme'

const emit = defineEmits<{
    (e: 'nav-back'): void
    (e: 'refresh', done: () => void): void
    (e: 'reach-bottom'): void
    (e: 'scroll', scrollTop: number): void
}>()

const props = withDefaults(
    defineProps<{
        /** 自定义导航标题；不传则不显示标题 */
        title?: string
        /** 是否渲染自定义导航。系统导航页（navigationBarTitleText）传 false */
        nav?: boolean
        navVariant?: 'transparent' | 'solid' | 'scroll-solid'
        showBack?: boolean
        /** 页面 @scroll 的滚动距离（px），配合 navVariant="scroll-solid" */
        scrollTop?: number
        navTitleColor?: string
        /** 头部文字/图标基调（透明态生效；切实底后自动转深色） */
        navTone?: 'dark' | 'light'
        /** 标题对齐：tab 页用 left */
        navTitleAlign?: 'center' | 'left'
        /** 标题形态：nav=导航条字号 / display=tab 页大标题 */
        navTitleVariant?: 'nav' | 'display'
        /** 实底态底色：surface=卡片白（默认）/ page=页面米底 */
        navBackground?: 'surface' | 'page'
        /** scroll-solid 切实底阈值（px） */
        navSolidThreshold?: number
        /** 右侧动作避让微信胶囊（有 #nav-right 内容时必须开） */
        navCapsuleAvoid?: boolean
        /** auto=组件自己返回；none=只 emit('back')，由页面接管（关闭语义） */
        navBackMode?: 'auto' | 'none'
        /** 页面底色：page=--c-bg（默认）/ surface=--c-surface / transparent=不铺 */
        bg?: 'page' | 'surface' | 'transparent'
        /** 是否挂载 app-tabbar（只有 4 个 tab 页传 true） */
        tabbar?: boolean
        /** 当前 tab 路径，与 tabbar 的 current 对应 */
        current?: string
        /** 是否为吸底操作栏（bottom-bar）预留高度 */
        bottomBar?: boolean
        /** 锁定外层视口（页面用内置 scroll-view 局部滚动，如 feed）：容器 100vh 不滚，
            底部预留高度交给页面 scroll-view 自己算 */
        lockViewport?: boolean
        /** 内置自定义下拉刷新：容器锁死视口并接管滚动，页面在 @refresh 里拉数据、调 done() 收尾 */
        pullRefresh?: boolean
    }>(),
    {
        title: '',
        nav: true,
        navVariant: 'solid',
        showBack: true,
        scrollTop: 0,
        /* 留空 → 由 navTone 推导；显式传值仍优先（feed-detail 等历史用法不断） */
        navTitleColor: '',
        navTone: 'dark',
        navTitleAlign: 'center',
        navTitleVariant: 'nav',
        navBackground: 'surface',
        navSolidThreshold: 8,
        navCapsuleAvoid: false,
        navBackMode: 'auto',
        bg: 'page',
        tabbar: false,
        current: '',
        bottomBar: false,
        lockViewport: false,
        pullRefresh: false
    }
)

const device = getDeviceInfo()

/**
 * 真机 JS 侧安全区底部（px）。env(safe-area-inset-bottom) 在部分安卓 WebView /
 * 嵌套 calc() 场景会被吃掉，这里用平台 API 的值行内注入 var(--safe-bottom) 兜底。
 */
const safeBottomPx = Math.max(0, Number(device.safeAreaBottom) || 0)

/** 状态栏高度（px）：transparent / scroll-solid 下拉时，指示器要垫在状态栏之下而不是被主图盖住 */
const statusBarHeight = Number(device.statusBarHeight || 0)

/** 锁定视口的两种来源：显式 lockViewport，或开内置下拉刷新（滚动收进 scroll-view） */
const isLocked = computed(() => props.lockViewport || props.pullRefresh)

/** 根节点：把真实安全区值写回 --safe-bottom，覆盖 CSS env() 求值（取不到时不写） */
const shellStyle = computed<Record<string, string> | undefined>(() =>
    safeBottomPx > 0 ? { '--safe-bottom': `${safeBottomPx}px` } : undefined
)

/**
 * 内容区底部预留：safeBottomPx > 0 时用「rpx 换算 px + 真实安全区」直接给 px 值，
 * 绕开真机上 calc(var() 嵌套) 失效的问题；取不到安全区时不写，交给 CSS 两段式降级。
 * pullRefresh 时照常计算（scroll-body 在 scroll-view 内，同样不能被悬浮 tabbar 压住）。
 */
const mainStyle = computed<Record<string, string> | undefined>(() => {
    if (props.lockViewport && !props.pullRefresh) return undefined
    if (props.tabbar && safeBottomPx > 0)
        return { paddingBottom: `${Math.round(uni.upx2px(180)) + safeBottomPx}px` }
    if (props.bottomBar && safeBottomPx > 0)
        return { paddingBottom: `calc(var(--bottom-bar-h) + ${safeBottomPx}px + var(--sp-4))` }
    return undefined
})

/* ------------------------------------------------------------------ */
/* 外观模式：shell 类名（模板）+ 小程序窗口底色（原生 API）              */
/* ------------------------------------------------------------------ */

const colorScheme = useColorSchemeStore()

/**
 * 模板绑定的 .scheme-* 类名。
 *
 * 显式用本地 ref 承接并 watch：常驻 Tab 页从「设置 → 外观模式」返回时，
 * 页面实例不重建，getter 若没有被模板依赖追踪到就会漏更新；这里保证每次
 * shellClass 变化都真正触发 setData，类名 100% 重放。
 */
const shellSchemeClass = ref(colorScheme.shellClass)

watch(
    () => colorScheme.shellClass,
    (next) => {
        shellSchemeClass.value = next
    },
    { immediate: true }
)

/**
 * 哪些页面要改原生窗口底色。
 *
 * .scheme-light / .scheme-dark 挂在 shell 根节点上，只能影响 shell 内部；
 * page 节点（下拉回弹、转场、短内容页的剩余高度）底色由全局 wxss 的 page{} 决定，
 * 手动选择时必须靠 uni.setBackgroundColor 才能一起变。
 *
 * 沉浸页（bg=transparent，登录头图）不调用：它们的窗口底色由
 * pages.json 写死为墨黑，JS 介入反而会在转场瞬间露出浅色。
 */
const pageActive = ref(true)
let lastAppliedWindowBg: string | null = null

function syncWindowBg(force = false) {
    // #ifdef MP-WEIXIN
    if (!pageActive.value) return
    const bg = props.bg
    if (bg === 'transparent') return
    // 跟随系统：首帧由 theme.json + @media 决定；只有在用户手动选过、
    // 或本组件已经接管过一次（切换回 system 需要复位）时才调用原生 API。
    if (colorScheme.preference === 'system' && lastAppliedWindowBg === null) return
    // 沉浸深色头图页（透明导航压在深色主图上）：顶部回弹区保持墨黑，
    // 下方仍跟页面底色走，避免暗色下顶部转场闪白
    const darkHeroTop = props.navTone === 'light' && props.navVariant !== 'solid'
    const bottomHex = colorScheme.getNativeWindowBg(bg)
    const topHex = darkHeroTop ? colorScheme.getNativeWindowBg('darkHero') : bottomHex
    const key = `${topHex}|${bottomHex}`
    if (!force && key === lastAppliedWindowBg) return
    uni.setBackgroundColor({
        backgroundColor: topHex,
        backgroundColorTop: topHex,
        backgroundColorBottom: bottomHex,
        fail: () => undefined
    })
    lastAppliedWindowBg = key
    // #endif
}

onMounted(() => {
    pageActive.value = true
    colorScheme.refreshSystemTheme()
    shellSchemeClass.value = colorScheme.shellClass
    syncWindowBg(false)
})

// onShow 强制重放：从后台 / 别的页面回来时，窗口底色可能已被上一页改写过，
// 且常驻 Tab 页需同步最新系统明暗与 shell 类名
onShow(() => {
    pageActive.value = true
    colorScheme.refreshSystemTheme()
    shellSchemeClass.value = colorScheme.shellClass
    syncWindowBg(true)
})

onHide(() => {
    pageActive.value = false
})

onUnload(() => {
    pageActive.value = false
})

// 手动切换（或系统切换时 systemTheme 变化）→ 重新算一遍窗口底色
watch(
    [() => colorScheme.resolved, () => colorScheme.preference],
    () => syncWindowBg(false)
)

/* ------------------------------------------------------------------ */
/* 内置下拉刷新状态机：严格阻尼 + 硬上限 + 定时器清理                    */
/* ------------------------------------------------------------------ */

type PullState = 'idle' | 'pulling' | 'ready' | 'refreshing' | 'done'

/** 触发阈值（px）：越过即「松手刷新」 */
const PULL_THRESHOLD = 56
/** 拉出硬上限（px）：阻尼曲线再陡也到不了这里 */
const PULL_MAX = 76
/** 刷新中指示器的保持高度（px） */
const PULL_HOLD = 54
/** 「已更新」停留时长（ms） */
const DONE_HOLD_MS = 380
/** 页面迟迟不调 done() 的保险丝：超时自动收起，指示器不会永远卡住 */
const SAFETY_TIMEOUT_MS = 12000

const pullState = ref<PullState>('idle')
const pullHeight = ref(0)
const pullDragging = ref(false)
/** scroll-view 内部滚动位置：> 2px 时手势判给滚动，不进下拉 */
const innerScrollTop = ref(0)

const pullTone = computed<'dark' | 'light'>(() => {
    if (props.navVariant === 'solid') return 'dark'
    if (props.navVariant === 'scroll-solid') return props.navTone
    return 'light'
})
/** 状态栏避让：transparent 与 scroll-solid 都是从 y=0 起的沉浸页，指示器必须垫在状态栏下 */
const pullTopInset = computed(() => (props.navVariant !== 'solid' ? statusBarHeight : 0))
const pullProgress = computed(() => Math.min(1, pullHeight.value / PULL_THRESHOLD))

function onPullScroll(e: any) {
    innerScrollTop.value = Number(e?.detail?.scrollTop ?? 0)
    emit('scroll', innerScrollTop.value)
}

function onPullScrollUpper() {
    innerScrollTop.value = 0
}

// 触摸手势状态（非响应式：只参与计算，不驱动渲染）
let startX = 0
let startY = 0
let gestureMode: 'idle' | 'pull' | 'ignore' = 'idle'
let refreshToken = 0
let doneTimer: ReturnType<typeof setTimeout> | null = null
let safetyTimer: ReturnType<typeof setTimeout> | null = null

function onPullTouchStart(e: any) {
    if (pullState.value === 'refreshing' || pullState.value === 'done') return
    const touch = e?.touches?.[0] ?? e?.changedTouches?.[0] ?? {}
    startX = Number(touch.clientX ?? touch.pageX ?? 0)
    startY = Number(touch.clientY ?? touch.pageY ?? 0)
    gestureMode = innerScrollTop.value > 2 ? 'ignore' : 'idle'
}

function onPullTouchMove(e: any) {
    if (gestureMode === 'ignore') return
    if (pullState.value === 'refreshing' || pullState.value === 'done') return
    const touch = e?.touches?.[0] ?? e?.changedTouches?.[0] ?? {}
    const curX = Number(touch.clientX ?? touch.pageX ?? 0)
    const curY = Number(touch.clientY ?? touch.pageY ?? 0)
    const dx = curX - startX
    const dy = curY - startY

    if (gestureMode === 'idle') {
        // 方向判定：横向为主或明显上滑 → 让给滚动；竖直下拉且已在顶部 → 进入下拉
        if ((Math.abs(dx) > 6 && Math.abs(dx) >= Math.abs(dy)) || dy < -4) {
            gestureMode = 'ignore'
            return
        }
        if (!(dy > 6 && dy > Math.abs(dx) && innerScrollTop.value <= 2)) return
        gestureMode = 'pull'
        pullDragging.value = true
    }

    if (dy <= 0) {
        pullHeight.value = 0
        pullState.value = 'idle'
        return
    }
    // 阻尼封顶：越拉越费劲，且永远封在 PULL_MAX
    const next = Math.round(Math.min(PULL_MAX, PULL_MAX * (1 - Math.exp(-dy / 90)) * 1.15))
    pullHeight.value = next
    if (next >= PULL_THRESHOLD) {
        if (pullState.value !== 'ready') {
            pullState.value = 'ready'
            // 越过阈值一次性轻震动提示（uni 类型未收录 type，运行时 mp-weixin 支持）
            try {
                uni.vibrateShort({ type: 'light' } as any)
            } catch {}
        }
    } else {
        pullState.value = 'pulling'
    }
}

function onPullTouchEnd() {
    if (gestureMode !== 'pull') {
        gestureMode = 'idle'
        return
    }
    gestureMode = 'idle'
    pullDragging.value = false
    if (pullState.value === 'ready') {
        const token = ++refreshToken
        pullState.value = 'refreshing'
        pullHeight.value = PULL_HOLD
        safetyTimer = setTimeout(() => finishRefresh(token), SAFETY_TIMEOUT_MS)
        emit('refresh', () => finishRefresh(token))
    } else {
        pullState.value = 'idle'
        pullHeight.value = 0
    }
}

/** 结束刷新：done() 只认当次 token；「已更新」短暂停留后收起 */
function finishRefresh(token: number) {
    if (token !== refreshToken || pullState.value !== 'refreshing') return
    if (safetyTimer) {
        clearTimeout(safetyTimer)
        safetyTimer = null
    }
    pullState.value = 'done'
    doneTimer = setTimeout(() => {
        if (token !== refreshToken) return
        pullHeight.value = 0
        pullState.value = 'idle'
    }, DONE_HOLD_MS)
}

onUnmounted(() => {
    pageActive.value = false
    if (doneTimer) clearTimeout(doneTimer)
    if (safetyTimer) clearTimeout(safetyTimer)
    doneTimer = null
    safetyTimer = null
})
</script>

<style lang="scss" scoped>
.shell {
    min-height: 100vh;
    box-sizing: border-box;

    &--page {
        background: var(--c-bg);
    }
    &--surface {
        background: var(--c-surface);
    }
    &--transparent {
        background: transparent;
    }

    /* 锁定视口（lockViewport / pullRefresh）：外层 100vh 不可滚，滚动交给内置 scroll-view */
    &--locked {
        height: 100vh;
        overflow: hidden;
        display: flex;
        flex-direction: column;
    }

    &__main {
        box-sizing: border-box;
    }

    /* 锁定视口时内容区吃满剩余高度，底部预留由页面 scroll-view 自己承担 */
    &__main--locked {
        flex: 1;
        height: 0;
        min-height: 0;
        display: flex;
        flex-direction: column;
        overflow: hidden;
        padding-bottom: 0;
    }

    /* tab 页：内容不能被悬浮 tabbar 压住。--tabbar-h 见 tokens.css。
       两段式降级：env()/嵌套 calc 在部分真机失效时退回不含安全区的第一段 */
    &__main--tabbar {
        padding-bottom: calc(var(--tabbar-h) + var(--sp-4));
        padding-bottom: calc(var(--tabbar-h) + var(--safe-bottom) + var(--sp-4));
    }

    /* 吸底操作栏页：--bottom-bar-h 由 bottom-bar 组件写入同名 CSS 变量值。两段式降级同上 */
    &__main--bottom {
        padding-bottom: calc(var(--bottom-bar-h) + var(--sp-4));
        padding-bottom: calc(var(--bottom-bar-h) + var(--safe-bottom) + var(--sp-4));
    }

    /* 内置下拉刷新：吸顶条（#sticky）不随内容滚动 */
    &__sticky {
        flex: none;
        z-index: 20;
        position: relative;
    }

    /* 内部滚动区：flex 剩余高度全部交给 scroll-view */
    &__scroll {
        flex: 1;
        height: 0;
        min-height: 0;
        width: 100%;
    }

    /* 滚动内容体：至少撑满一屏。
       禁止加 overflow: hidden —— 会干掉子元素 position: sticky（后续批次吸顶条依赖它） */
    &__scroll-body {
        min-height: 100%;
        box-sizing: border-box;
    }
}
</style>
