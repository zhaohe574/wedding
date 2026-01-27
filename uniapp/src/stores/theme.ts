import { getDecorate } from '@/api/shop'
import { generateVars } from '@/utils/theme'
import { defineStore } from 'pinia'

interface ThemeStore {
    primaryColor: string
    secondaryColor: string
    ctaColor: string
    accentColor: string
    minorColor: string
    btnColor: string
    navColor: string
    navBgColor: string
    vars: string
    pageStyle: string
}
export const useThemeStore = defineStore({
    id: 'themeStore',
    state: (): ThemeStore => ({
        primaryColor: '#7C3AED',
        secondaryColor: '#EC4899',
        ctaColor: '#F97316',
        accentColor: '#FFD700',
        minorColor: '#FFB814',
        btnColor: 'white',
        navColor: '#000000',
        navBgColor: '#ffffff',
        vars: '',
        pageStyle: ''
    }),
    actions: {
        async getTheme() {
            const data = await getDecorate({
                id: 5
            })

            // 处理 data.data 字段，可能是字符串或对象
            let themeData = data.data
            if (typeof themeData === 'string') {
                try {
                    themeData = JSON.parse(themeData)
                } catch (e) {
                    console.error('解析主题数据失败:', e)
                    return
                }
            }

            const { themeColor1, themeColor2, buttonColor, navigationBarColor, topTextColor } =
                themeData

            this.primaryColor = themeColor1 || '#7C3AED'
            this.secondaryColor = themeColor2 || '#EC4899'
            this.minorColor = themeColor2 || '#FFB814'
            this.btnColor = buttonColor || 'white'
            this.navColor = topTextColor === 'white' ? '#ffffff' : '#000000'
            this.navBgColor = navigationBarColor || themeColor1 || '#7C3AED'
            
            // 生成完整的CSS变量，包含所有颜色
            this.vars = generateVars(
                {
                    primary: this.primaryColor,
                    secondary: this.secondaryColor,
                    cta: this.ctaColor,
                    accent: this.accentColor
                },
                {
                    '--color-minor': this.minorColor,
                    '--color-btn-text': this.btnColor
                }
            )
            
            // 生成页面样式字符串
            this.pageStyle = this.vars
        },
        setTheme(color: string) {
            this.primaryColor = color
            this.updateVars()
        },
        setSecondaryColor(color: string) {
            this.secondaryColor = color
            this.updateVars()
        },
        setCtaColor(color: string) {
            this.ctaColor = color
            this.updateVars()
        },
        setAccentColor(color: string) {
            this.accentColor = color
            this.updateVars()
        },
        updateVars() {
            this.vars = generateVars(
                {
                    primary: this.primaryColor,
                    secondary: this.secondaryColor,
                    cta: this.ctaColor,
                    accent: this.accentColor
                },
                {
                    '--color-minor': this.minorColor,
                    '--color-btn-text': this.btnColor
                }
            )
        }
    }
})
