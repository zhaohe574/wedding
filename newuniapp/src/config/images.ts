/**
 * 静态配图入口（唯一出口，页面不要写死路径）。
 *
 * 为什么要有这一层：
 *   小程序里 `/static/**` 会被打进**主包**，8 张配图压缩后约 242 KB。如果哪天主包逼近阈值，
 *   把图搬到 CDN 只需要改这里的 REMOTE_BASE（或改成 manifest 里的 CDN 域名），
 *   不需要改 24 屏的任何一个页面 —— 这是「不要把线上地址写死在页面里」的落点。
 *
 * 用法：
 *   import { IMG } from '@/config/images'
 *   <image :src="IMG.heroCeremony" mode="aspectFill" />
 *
 *   // 需要拼动态图名时：
 *   import { img } from '@/config/images'
 *   <image :src="img('host-chenmo.jpg')" />
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
    /** 动态两张现场图（660/600 宽，70q/60q） */
    feedBackstage: img('feed-backstage.jpg'),
    feedLawn: img('feed-lawn.jpg'),
    /** 主持人头像（240×240 裁切，82q，约 11–13 KB/张） */
    hostChenmo: img('host-chenmo.jpg'),
    hostZhixia: img('host-zhixia.jpg'),
    hostZhouba: img('host-zhouba.jpg'),
    /** 作品缩略图（360 宽，76q） */
    workChinese: img('work-chinese.jpg'),
    workFirstlook: img('work-firstlook.jpg')
} as const

export default IMG
