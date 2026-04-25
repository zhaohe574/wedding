// 轮播图数据项接口
export interface BannerItem {
    is_show: '0' | '1'  // 是否显示
    image: string        // 轮播图片URL
    bg: string          // 背景图片URL
    bg_color?: string    // 背景联动颜色
    name: string        // 图片名称
    slogan: string      // 宣传语
    slogan_top?: number | null  // 宣传语距顶部高度（rpx）
    slogan_color?: string  // 宣传语字体颜色
    link: Record<string, any>  // 跳转链接配置
}

// 轮播图内容配置接口
export interface BannerContent {
    enabled: 0 | 1      // 是否启用
    style: 1            // 固定常规模式
    bg_style: 0 | 1     // 背景联动：0=关闭，1=开启
    height?: number     // 轮播图高度（rpx），可选字段，未设置时使用默认值
    overlap_height?: number // 团队信息覆盖轮播图区域高度（rpx）
    data: BannerItem[]  // 轮播图数据
}

// 轮播图配置选项接口
export interface BannerOptions {
    title: string
    name: string
    pageScope: string[]
    content: BannerContent
    styles: Record<string, any>
}

export default (): BannerOptions => ({
    title: '首页轮播图',
    name: 'banner',
    pageScope: ['home'], // 仅在首页显示
    content: {
        enabled: 1,
        style: 1, // 固定常规模式
        bg_style: 1, // 背景联动---0=关闭，1=开启
        height: undefined, // 轮播图高度（rpx），未设置时使用默认值（321）
        overlap_height: 280,
        data: [
            {
                is_show: '1',
                image: '',
                bg: '',
                bg_color: '#000000',
                name: '',
                slogan: '',
                slogan_top: null,
                slogan_color: '',
                link: {}
            }
        ]
    },
    styles: {}
})
