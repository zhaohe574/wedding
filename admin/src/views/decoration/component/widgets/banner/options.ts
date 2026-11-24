import { HOME_HERO_DEFAULTS, HOME_WIDGET_TITLES, type HomeHeroContent } from '@shared/contracts/decorate'

/**
 * 首屏主图（banner）。
 *
 * 真机消费 `content.enabled`、`content.hero_height`（首屏高度 rpx）与 `data[0]` 的 14 个字段：
 * 主图、5 组文案与显隐开关、3 个颜色。
 * 旧版轮播图的 style / bg_style / height / overlap_height / bg / link / is_show 等
 * 已从数据形状中删除，不要再加回来；高度字段叫 `hero_height` 而不是 `height`，
 * 避免与老数据里已废弃的旧版轮播图 `content.height` 脏字段撞名。
 * 深拷贝 HOME_HERO_DEFAULTS 会自动携带 `hero_height: 1054`（默认高度，与设计稿一致）。
 */
export default () => {
    /** 深拷贝：buildWidgetOptions 对 content 只做浅展开，不能与契约常量共享数组 */
    const defaults = JSON.parse(JSON.stringify(HOME_HERO_DEFAULTS)) as HomeHeroContent

    return {
        title: HOME_WIDGET_TITLES.banner,
        name: 'banner',
        pageScope: ['home'], // 仅在首页显示
        content: defaults,
        styles: {}
    }
}
