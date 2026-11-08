/**
 * 品牌常量 —— 唯一来源。
 *
 * 背景：设计稿里的「声宴 / SHENG YAN」是模板演示用的占位品牌，真实品牌是「格林社」。
 * 所有会出现在界面上的品牌字样（页头、印章、分享标题、版本串、活动主办方）都从这里取，
 * 换品牌只改这一个文件，不要在页面里再写死中文品牌名。
 */

/** 中文品牌名 */
export const BRAND_NAME = '格林社'

/** 英文品牌锁定行（设计稿 hero/登录页的 SHENG YAN · WEDDING HOSTS 位） */
export const BRAND_NAME_EN = 'GELIN · WEDDING HOSTS'

/**
 * 首页 hero 的英文 eyebrow（设计稿位：SINCE 2014 · HANGZHOU）。
 * ⚠ 这句是成立年份与城市的对外声明，属设计稿演示文案，**需业务确认后再定稿**。
 * 改这里一处即可，不要散落到页面。
 */
export const BRAND_EYEBROW = 'SINCE 2014 · HANGZHOU'

/** 压印印章里的单字（印章是方的，只放一个字） */
export const BRAND_SEAL = '格'

/** 版本串前缀（「我的」页脚 Gelin · V1.2.0） */
export const BRAND_VERSION_PREFIX = 'Gelin'
