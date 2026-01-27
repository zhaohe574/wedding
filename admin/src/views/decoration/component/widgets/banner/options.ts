// 轮播图数据项接口
export interface BannerItem {
    is_show: '0' | '1'  // 是否显示
    image: string        // 轮播图片URL
    bg: string          // 背景图片URL
    name: string        // 图片名称
    link: Record<string, any>  // 跳转链接配置
}

// 轮播图内容配置接口
export interface BannerContent {
    enabled: 0 | 1      // 是否启用
    style: 1 | 2        // 展示样式：1=常规，2=大屏
    bg_style: 0 | 1     // 背景联动：0=关闭，1=开启
    height?: number     // 轮播图高度（rpx），可选字段，未设置时使用默认值
    data: BannerItem[]  // 轮播图数据
}

// 轮播图配置选项接口
export interface BannerOptions {
    title: string
    name: string
    content: BannerContent
    styles: Record<string, any>
}

export default (): BannerOptions => ({
    title: '首页轮播图',
    name: 'banner',
    pageScope: ['home'], // 仅在首页显示
    content: {
        enabled: 1,
        style: 1, // 展示样式---1=常规，2=大屏
        bg_style: 0, // 背景联动---0=关闭，1=开启
        height: undefined, // 轮播图高度（rpx），未设置时使用默认值（常规321，大屏1100）
        data: [
            {
                is_show: '1',
                image: '',
                bg: '',
                name: '',
                link: {}
            }
        ]
    },
    styles: {}
})
