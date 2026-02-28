import { defineStore } from 'pinia'
import { getConfig } from '@/api/app'

interface AppSate {
    config: Record<string, any>
}
export const useAppStore = defineStore({
    id: 'appStore',
    state: (): AppSate => ({
        config: {
            domain: ''
        }
    }),
    getters: {
        getWebsiteConfig: (state) => state.config.website || {},
        getLoginConfig: (state) => state.config.login || {},
        getTabbarConfig: (state) => state.config.tabbar || [],
        getStyleConfig: (state) => state.config.style || {},
        getH5Config: (state) => state.config.webPage || {},
        getCopyrightConfig: (state) => state.config.copyright || []
    },
    actions: {
        getImageUrl(url: string) {
            if (!url) return ''
            // 已经是完整 URL 则直接返回
            if (url.startsWith('http')) return url
            // 拼接 domain，避免双斜杠
            const domain = this.config.domain?.replace(/\/+$/, '') || ''
            const path = url.startsWith('/') ? url : `/${url}`
            return `${domain}${path}`
        },
        async getConfig() {
            const data = await getConfig()
            this.config = data
        }
    }
})
