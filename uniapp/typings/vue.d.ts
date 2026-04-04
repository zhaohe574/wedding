import 'vue'
declare module 'vue' {
    interface ComponentCustomProperties {
        $theme: {
            scene: string
            primaryColor: string
            secondaryColor: string
            ctaColor: string
            accentColor: string
            btnColor: string
            pageStyle: string
            navColor: string
            navBgColor: string
            tokens: Record<string, any>
            title: string
        }
    }
}
