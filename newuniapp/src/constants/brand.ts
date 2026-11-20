/**
 * 品牌兜底常量 —— **只用于兜底，不是品牌真源**。
 *
 * 运行时真源是后台「网站信息 → 前台设置」的三个字段，经 `GET /api/index/config`
 * 的 `website` 节点下发，由 `stores/app` 归一化成 `BrandProfile`：
 *
 *   后台表单「前台名称」   → website.shop_name   → BrandProfile.name    （小程序名称）
 *   后台表单「前台LOGO」   → website.shop_logo   → BrandProfile.logo    （小程序图标）
 *   后台表单「前台Slogan」 → website.shop_slogan → BrandProfile.slogan  （副标题 / 英文锁定行）
 *   —                      → 名称首字            → BrandProfile.seal    （图标缺失时的文字回退）
 *
 * 本文件只在下面两种情况下被读到：
 *   1. 首次启动（本地还没有任何缓存）且配置请求还没回来 / 已经失败；
 *   2. 后台把某个字段清空了（此时该字段按「未配置」处理）。
 *
 * ⚠ 页面 / 组件**不要**直接 import 这里的值 —— 一律用 `useBrand()`。
 *   直接引用会让后台改了名称、这里还显示旧值，等于把动态配置又写死了一遍。
 */

export interface BrandProfile {
    /** 小程序名称（后台 website.shop_name） */
    name: string
    /** 图标地址（后台 website.shop_logo，绝对 URL；空串 = 未配置） */
    logo: string
    /** 副标题 / 英文锁定行（后台 website.shop_slogan；空串 = 未配置，展示位整体隐藏） */
    slogan: string
    /** 印章字：名称首字，图标缺失或加载失败时的文字回退 */
    seal: string
}

/**
 * 兜底品牌（最后一次防线）。
 * 只有在「本地无缓存 + 配置请求失败」时才会出现在界面上。
 * 改这里等于改兜底文案，不等于改品牌 —— 品牌请去后台改。
 */
export const BRAND_FALLBACK: Readonly<BrandProfile> = Object.freeze({
    name: '格林社',
    logo: '',
    slogan: '',
    seal: '格'
})

/**
 * 取名称首字作为印章字。
 * 中文取第一个字；英文取首字母（大写）；空名回落到兜底印章字。
 */
export function sealOf(name: string): string {
    const text = String(name || '').trim()
    if (!text) return BRAND_FALLBACK.seal
    return [...text][0]
}
