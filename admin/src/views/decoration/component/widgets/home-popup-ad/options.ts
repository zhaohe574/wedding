export type HomePopupAdType = 'image' | 'text' | 'image_text'
export type HomePopupAdFrequency =
    | 'daily'
    | 'session'
    | 'every_home_entry'
    | 'once'
    | 'interval_days'
    | 'custom_limit'
export type HomePopupAdShowTiming = 'page_ready' | 'delay'

export interface HomePopupAdContent {
    enabled: 0 | 1
    type: HomePopupAdType
    title: string
    content: string
    image: string
    background_color: string
    button_text: string
    link: Record<string, any>
    frequency: HomePopupAdFrequency
    show_timing: HomePopupAdShowTiming
    delay_seconds: number
    interval_days: number
    max_total_count: number
    max_daily_count: number
    start_time: string
    end_time: string
    close_counts_as_shown: 0 | 1
    show_close: 0 | 1
}

export default () => ({
    title: '首页弹窗广告',
    name: 'home-popup-ad',
    pageScope: ['home'],
    content: {
        enabled: 0,
        type: 'image_text',
        title: '',
        content: '',
        image: '',
        background_color: '#FFFDF8',
        button_text: '查看详情',
        link: {},
        frequency: 'daily',
        show_timing: 'page_ready',
        delay_seconds: 0,
        interval_days: 7,
        max_total_count: 0,
        max_daily_count: 1,
        start_time: '',
        end_time: '',
        close_counts_as_shown: 1,
        show_close: 1
    } as HomePopupAdContent,
    styles: {}
})
