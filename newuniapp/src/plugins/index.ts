import { isFunction } from '@vue/shared'
import type { App } from 'vue'

type PluginModule = { default?: (app: App) => void }

// Vite 4 的 globEager 未在 @dcloudio 类型中声明，显式标注避免 unknown 扩散。
// 升级到 Vite 5 时改为 import.meta.glob('./modules/**/*.ts', { eager: true })。
const modules = (import.meta as any).globEager('./modules/**/*.ts') as Record<string, PluginModule>

export default {
    install: (app: App) => {
        for (const module of Object.values(modules)) {
            const fun = module?.default
            if (isFunction(fun)) fun(app)
        }
    }
}
