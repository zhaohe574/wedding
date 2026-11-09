<template>
    <view class="shell" :class="`shell--${bg}`">
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

        <view
            class="shell__main"
            :class="{ 'shell__main--tabbar': tabbar, 'shell__main--bottom': bottomBar }"
        >
            <slot />
        </view>

        <!-- 吸底操作栏（bottom-bar）挂这里；页面私有浮层也可放 -->
        <slot name="bottom" />

        <!-- tab 页统一由容器挂 tabbar：4 个 tab 页不再各写一份 -->
        <app-tabbar v-if="tabbar" :current="current" />

        <!-- 隐私授权弹窗（合规 B1）：挂 page-shell 而不是 App.vue ——
             小程序端 App 根没有 wxss 输出路径，组件样式会变 0 字节（app-toast 踩过）。 -->
        <privacy-popup />
    </view>
</template>

<script setup lang="ts">
/**
 * 页面容器（24 屏的统一外壳）。
 *
 * 职责边界（不要越界）：
 *  - 只负责三件事：状态栏/导航避让、底部安全区留白、tabBar 挂载。
 *  - 不做数据请求、不做登录态判断、不做页面级 loading（用 base-skeleton / base-empty）。
 *  - 不接管滚动：scroll-solid 导航需要页面把 @scroll 的 scrollTop 传进来。
 *
 * 何时不用它：全屏沉浸页（首页 hero 图满屏压到状态栏下）用 navVariant="transparent"，
 * 容器不铺底色（bg="transparent"），页面自己绝对定位主图。
 */
const emit = defineEmits<{ (e: 'nav-back'): void }>()

withDefaults(
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
        bottomBar: false
    }
)
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

    &__main {
        box-sizing: border-box;
    }

    /* tab 页：内容不能被悬浮 tabbar 压住。--tabbar-h 见 tokens.css */
    &__main--tabbar {
        padding-bottom: calc(var(--tabbar-h) + var(--safe-bottom) + var(--sp-3));
    }

    /* 吸底操作栏页：--bottom-bar-h 由 bottom-bar 组件写入同名 CSS 变量值 */
    &__main--bottom {
        padding-bottom: calc(var(--bottom-bar-h) + var(--safe-bottom) + var(--sp-3));
    }
}
</style>
