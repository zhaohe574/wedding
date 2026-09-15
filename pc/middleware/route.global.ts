import { useAppStore } from '~~/stores/app'
import { isEmptyObject } from '~~/utils/validate'

export default defineNuxtRouteMiddleware(async () => {
    const appStore = useAppStore()
    if (isEmptyObject(appStore.config)) await appStore.getConfig()
})
