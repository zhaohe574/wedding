import 'vue'
declare module 'vue' {
    interface ComponentCustomProperties {
        $theme: {
            primaryColor: string
            secondaryColor: string
            ctaColor: string
            accentColor: string
            btnColor: string
            pageStyle: string
            navColor: string
            navBgColor: string
            title: string
        }
    }
}
