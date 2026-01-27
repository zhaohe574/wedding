import { useThemeStore } from '@/stores/theme'
import { useAppStore } from '@/stores/app'

export default {
    computed: {
        $theme() {
            const themeStore = useThemeStore()
            const appStore = useAppStore()
            return {
                primaryColor: themeStore.primaryColor,
                secondaryColor: themeStore.secondaryColor,
                ctaColor: themeStore.ctaColor,
                accentColor: themeStore.accentColor,
                minorColor: themeStore.minorColor,
                btnColor: themeStore.btnColor,
                pageStyle: themeStore.vars,
                navColor: themeStore.navColor,
                navBgColor: themeStore.navBgColor,
                title: appStore.getWebsiteConfig.shop_name
            }
        }
    }
}
