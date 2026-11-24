/**
 * 静态配图入口（唯一出口，页面不要写死路径）。
 *
 * 为什么要有这一层：
 *   小程序里 `/static/**` 会被打进**主包**，微信代码质量检测对「图片和音频资源」划了
 *   200 KB 红线，超出直接判扣分项。本地图片总量因此必须严格受控：目前只保留
 *   首页 hero（65 KB）与默认头像（6 KB）两张，其余配图一律走 CDN 或删除。
 *   如果哪天需要新增本地图，先确认 主包静态图 + 音频总量 仍 < 200 KB。
 *
 * 用法：
 *   import { IMG } from '@/config/images'
 *   <image :src="IMG.heroCeremony" mode="aspectFill" />
 *
 *   // 需要拼动态图名时：
 *   import { img } from '@/config/images'
 *   <image :src="img('hero-ceremony.jpg')" />
 */
const REMOTE_BASE = '' // 例如 'https://cdn.example.com/wedding' ；为空则用本地 /static/images

/** 本地静态目录（压缩规格见 docs/redesign/v2/00-工程底座.md §8.4） */
const LOCAL_BASE = '/static/images'

export function img(file: string): string {
    return REMOTE_BASE ? `${REMOTE_BASE}/${file}` : `${LOCAL_BASE}/${file}`
}

export const IMG = {
    /** 首页首屏主图（600×900，72q，65 KB） */
    heroCeremony: img('hero-ceremony.jpg'),
    /** 用户默认头像（6 KB） */
    defaultAvatar: img('user/default_avatar.png')
} as const

export default IMG
