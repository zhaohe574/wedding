<template>
    <!-- 全局 Toast 挂载点（03 §3.2 N5）：组件走 easycom.autoscan 自动注册，无需在此 import。
         注：H5 端 App.vue 作为根组件渲染此 template；小程序端 App.vue 不渲染视图，
         需由后续页面批次（T-27~T-36）在需要的页面显式挂载 <app-toast />。此为跨端差异，已在批次报告单列。 -->
    <!-- ⚠️ 必须条件编译：小程序端若在 App.vue 模板里挂 <app-toast />，uni-app 会把这个组件
         挂到「App 根（无 wxss 输出）」的编译路径上，导致 app-toast.wxss 恒为 0 字节 ——
         全站 47 页的 toast 都没有样式（无背景、无定位、无动画）。
         实测：移除本行 → app-toast.wxss 0 字节；加 #ifdef H5 后 → 1093 字节，样式完整。 -->
    <!-- #ifdef H5 -->
    <app-toast />
    <!-- #endif -->
</template>

<script setup lang="ts">
import { onLaunch, onShow, onError, onPageNotFound } from '@dcloudio/uni-app'
import { useAppStore } from './stores/app'
import { useUserStore } from './stores/user'
import { useBadgeStore } from './stores/badge'
import { useColorSchemeStore } from './stores/colorScheme'
import { configureRequest } from './request'
import type { ApiError } from './request'
import { getToken } from './utils/auth'
import { toLogin } from './utils/login'
import { redirectNotFound } from './utils/link'
import { report } from './platform/report'
import { bootTrace } from './utils/boot-trace'
import { setupPrivacyGuard, promptPrivacyOnLaunch } from './composables/usePrivacyGuard'
import { isSinglePageMode } from './utils/share'

const appStore = useAppStore()
const userStore = useUserStore()

/**
 * 登录失效：清 token，跳登录。
 *
 * 走 toLogin() 而不是裸 navigateTo：
 *   1. 不写 BACK_URL —— 登录成功后要回到「正在看的这一页」，页面实例还在栈里，
 *      已选的人员 / 套餐 / 填了一半的联系人信息不会丢（原来一律 switchTab 回首页）；
 *   2. 并发 401（一个页面同时发好几个 auth 请求）只会开一层登录页。
 *
 * need_bind_mobile：后端拦下「已登录但未绑手机号」的受限请求 —— 正式 token 仍有效，
 * 只是必须先绑定手机号。此时不能把凭证一起丢掉：暂存 temToken，
 * 带 bind_mobile=1 进登录页走强制绑定（logout 刻意不清 temToken，并发拦截不互踩）。
 */
function handleUnauthorized(error?: ApiError) {
    const needBindMobile = Boolean((error?.data as any)?.need_bind_mobile)
    const currentToken = needBindMobile
        ? userStore.token || getToken() || userStore.temToken || ''
        : ''
    userStore.logout()
    if (needBindMobile && currentToken) {
        userStore.setTemToken(currentToken)
        toLogin({ bindMobile: true })
        return
    }
    userStore.setTemToken(null)
    toLogin()
}

// 请求层依赖注入：避免 store ↔ request 循环依赖
configureRequest({
    getToken,
    onUnauthorized: handleUnauthorized,
    onError: (error) => {
        if (error.shouldReport) {
            report('api_error', {
                message: error.message,
                extra: { code: error.code, errorCode: error.errorCode }
            })
        }
    }
})

/**
 * 把品牌名同步到 H5 的标签页标题。
 *
 * H5 的 document.title 默认取 pages.json 的 navigationBarTitleText（构建期写死的值），
 * 不同步就会出现「后台改了名称、浏览器标签还是旧名」。
 *
 * 小程序端不走这里：自定义导航下系统标题不显示，唯一有意义的「默认转发卡片标题」
 * 由首页的 onShareAppMessage 覆盖（见 pages/index/index.vue），不依赖系统标题。
 */
function syncDocumentTitle() {
    const name = appStore.brand.name
    if (!name) return
    // #ifdef H5
    document.title = name
    // #endif
}

onLaunch((options?: any) => {
    bootTrace('app:onLaunch')
    // 外观模式：尽早初始化 —— 系统暗色由 CSS @media 首帧生效，这里只负责
    // 读取用户上次的手动选择并同步 H5 DOM / 注册系统明暗变化监听。
    useColorSchemeStore().init()
    // 合规 B1：manifest 已开 __usePrivacyCheck__，必须配一个授权兜底，
    // 否则相册 / 手机号类接口在真机上拿不到授权（审核必测「上传评价图片」）。
    setupPrivacyGuard()
    // 用户裁决（P0-1 / P1-8）：首次启动主动展示隐私弹窗，不再只等隐私接口按需触发。
    // 已同意则静默通过；未同意会走 setupPrivacyGuard 注册的队列弹窗。
    // 朋友圈单页模式（scene=1154）例外：弹窗会直接盖住分享内容，且单页下无法完成授权。
    if (!isSinglePageMode(options?.scene)) {
        promptPrivacyOnLaunch()
    }

    // 配色由 tokens.css 静态决定：原先的运行时主题链路生成的 --color-* 从未被任何样式消费，
    // 只会让人误以为「主题可配」。颜色是设计资产，不接受运行时下发。
    //
    // 品牌（名称 / 图标 / Slogan）相反 —— 它是运营资产，必须运行时下发：
    // 这里拉一次 `/index/config`，成功后 store 会归一化成 BrandProfile 并写入本地缓存，
    // 页面通过 useBrand() 响应式拿到新值（缓存保证首帧不闪、请求失败也有上次的值）。
    appStore
        .getConfig()
        .then(() => {
            bootTrace('app:config ok')
            syncDocumentTitle()
        })
        .catch(() => {
            bootTrace('app:config failed')
            report('api_error', { endpoint: 'index.config', message: '全局配置加载失败' })
        })
})

/**
 * 回到前台 / 冷启后第一次页面展示：刷新角标。
 *
 * 必须放在 App 级 onShow 而不是只在 tab 页：用户可能从分享卡片直接进详情页，
 * 那时 tabbar 还没挂载，角标数据全靠这里先起一次（refresh 自带节流与单飞，重复调用无副作用）。
 */
onShow(() => {
    // 从后台切回 / 模拟器切换系统外观后立即重读，systemTheme 不等下一次主题回调
    useColorSchemeStore().refreshSystemTheme()
    if (userStore.isLogin) {
        // 本地资料缺 mobile 时强刷一次：绑定手机号后 userInfo 才能被及时回填
        const missingMobile = !String(userStore.userInfo?.mobile || '').trim()
        useBadgeStore().refresh(missingMobile)
    }
})

onError((err) => {
    report('page_error', { message: String(err) })
})

onPageNotFound((res) => {
    redirectNotFound(res?.path, res?.query)
})
</script>

<style lang="scss">
/* 全局样式由 vite 插件 inject-global-wxss 注入 app.wxss */
</style>
