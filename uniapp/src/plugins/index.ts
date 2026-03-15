import { isFunction } from '@vue/shared'
import { App } from 'vue'

type PluginModule = {
    default?: (app: App) => void
}

const modules = import.meta.globEager('./modules/**/*.ts') as Record<string, PluginModule>

export default {
    install: (app: App) => {
        for (const module of Object.values(modules)) {
            const fun = module.default
            isFunction(fun) && fun(app)
        }
    }
}
