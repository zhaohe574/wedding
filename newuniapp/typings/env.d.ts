/// <reference types="vite/client" />

declare module '*.vue' {
    import { DefineComponent } from 'vue'
    // eslint-disable-next-line @typescript-eslint/no-explicit-any, @typescript-eslint/ban-types
    const component: DefineComponent<{}, {}, any>
    export default component
}

declare interface Window {
    signLink: string
}

declare module 'uview-plus'

declare module 'weixin-js-sdk'

declare module 'css-color-function'

/** unplugin-uni-router 在构建期生成的虚拟模块，tsc 需要占位声明 */
declare module 'uni-router-routes' {
    const routes: any[]
    export default routes
}

/** vk-uview-ui 为 JS 包，无类型声明 */
declare module '@/uni_modules/vk-uview-ui' {
    const uView: any
    export default uView
}
