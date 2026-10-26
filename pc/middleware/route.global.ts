import { useAppStore } from '~~/stores/app'
import { isEmptyObject } from '~~/utils/validate'

export default defineNuxtRouteMiddleware(async () => {
    const appStore = useAppStore()
    if (isEmptyObject(appStore.config)) {
        try {
            await appStore.getConfig()
        } catch (error) {
            // 站点配置拉取失败（后端不可达 / 预渲染环境无网络）不得中断渲染，
            // 页面使用内置默认文案继续渲染，避免整站预渲染失败。
            console.warn('[pc] 站点配置加载失败，使用默认配置渲染', error)
        }
    }
})
