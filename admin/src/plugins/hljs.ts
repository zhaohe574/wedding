/**
 * highlight.js 语法高亮（异步按需加载）。
 *
 * 改造背景：原实现由 `install/index.ts` 的 eager glob 在首屏注册全局
 * `<highlightjs>` 组件并引入 github 主题 CSS，但实际只有
 * `dev_tools/components/code-preview.vue` 一个页面用到，
 * 因此改为按需动态加载，避免首屏加载无用的高亮代码与样式。
 */
import type { Component } from 'vue'

/** 组件缓存，避免重复加载 */
let componentPromise: Promise<Component> | null = null

/**
 * 加载 highlight.js 的 Vue 组件（同时按需引入 github 主题样式）。
 */
export function ensureHljsComponent(): Promise<Component> {
    if (componentPromise) {
        return componentPromise
    }

    componentPromise = (async () => {
        // 样式仅在真正使用高亮的页面加载
        await import('highlight.js/styles/github.css')
        const plugin = await import('@highlightjs/vue-plugin')
        return plugin.default.component as unknown as Component
    })()

    return componentPromise
}

export default { ensureHljsComponent }
