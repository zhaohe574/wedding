import { useThemeStore } from '@/stores/theme'
import { useAppStore } from '@/stores/app'

export default {
    computed: {
        $theme() {
            const themeStore = useThemeStore()
            const appStore = useAppStore()
            return {
                presetKey: themeStore.presetKey,
                scene: themeStore.scene,
                primaryColor: themeStore.primaryColor,
                secondaryColor: themeStore.secondaryColor,
                ctaColor: themeStore.ctaColor,
                accentColor: themeStore.accentColor,
                minorColor: themeStore.minorColor,
                surfaceColor: themeStore.surfaceColor,
                surfaceElevatedColor: themeStore.surfaceElevatedColor,
                textPrimaryColor: themeStore.textPrimaryColor,
                textSecondaryColor: themeStore.textSecondaryColor,
                textInverseColor: themeStore.textInverseColor,
                borderColor: themeStore.borderColor,
                pageBgColor: themeStore.pageBgColor,
                pageBgSoftColor: themeStore.pageBgSoftColor,
                heroGradient: themeStore.heroGradient,
                maskColor: themeStore.maskColor,
                tabbarActiveColor: themeStore.tabbarActiveColor,
                tabbarInactiveColor: themeStore.tabbarInactiveColor,
                navStyle: themeStore.navStyle,
                cardStyle: themeStore.cardStyle,
                shadowLevel: themeStore.shadowLevel,
                btnColor: themeStore.btnColor,
                pageStyle: themeStore.pageStyle,
                navColor: themeStore.navColor,
                navBgColor: themeStore.navBgColor,
                tokens: themeStore.tokens,
                title: appStore.getWebsiteConfig.shop_name
            }
        }
    }
}
