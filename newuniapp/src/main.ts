import { createSSRApp } from 'vue'
import App from './App.vue'
import plugins from './plugins'
// 路由守卫只在 H5 生效。小程序端所有跳转都走 uni.navigateTo/switchTab
// （utils/link.ts、login.vue 等），这层 Vue 路由不会拦截它们，
// 却会在每次建页/onShow 时打印整个页面实例，开发工具序列化大对象时会明显拖慢首切。
// 条件编译把整包从 mp-weixin 产物移除；H5 行为保持不变。
// #ifndef MP-WEIXIN
import router from './router'
// #endif
import './styles/index.scss'
export function createApp() {
    const app = createSSRApp(App)
    app.use(plugins)
    // #ifndef MP-WEIXIN
    app.use(router)
    // #endif
    return {
        app
    }
}
