import { App } from 'vue'
import theme from './theme'
import share from './share'
export function setupMixin(app: App) {
    app.mixin(theme)
    app.mixin(share)
}
