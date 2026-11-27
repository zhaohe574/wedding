import { useColorSchemeStore } from '@/stores/colorScheme'

/**
 * 设计令牌的十六进制镜像（01 总方案 §2.2 硬编码清零清单）。
 *
 * 为什么需要这份镜像：`uni.showModal({ confirmColor })`、`tn-swipe-action` 的
 * 行内样式对象等原生 API 只接受字面色值，不认 `var(--c-*)`（wxss 变量在行内样式
 * 与原生参数里都不生效）。这里把当前生效明暗的那一套值取出来给它们。
 *
 * 与 tokens.css 严格同值：改色必须两处同步（tokens.css 的四处块 + 本文件的 LIGHT/DARK）。
 * 注意文件名是 scheme-color.ts —— 门禁 R16 拦截 `utils/theme`，不要改回 theme-color。
 */

export type SchemeColorSlot =
    | 'brand'
    | 'brandText'
    | 'danger'
    | 'onDanger'
    | 'actionBg'
    | 'actionFg'
    | 'surface'
    | 'surfaceFloat'
    | 'ink'
    | 'switchActive'

const LIGHT: Record<SchemeColorSlot, string> = {
    brand: '#c6a15b',
    brandText: '#8a6424',
    danger: '#a33b2c',
    onDanger: '#ffffff',
    actionBg: '#181614',
    actionFg: '#ffffff',
    surface: '#fffdf8',
    surfaceFloat: '#fffdf8',
    ink: '#181614',
    switchActive: '#c6a15b'
}

const DARK: Record<SchemeColorSlot, string> = {
    brand: '#d4b16a',
    brandText: '#e0c180',
    danger: '#f08b7d',
    onDanger: '#181614',
    actionBg: '#c6a15b',
    actionFg: '#181614',
    surface: '#1e1b18',
    surfaceFloat: '#282420',
    ink: '#f3ede3',
    switchActive: '#d4b16a'
}

/**
 * 取当前生效明暗下的字面色值。
 * 手动选择优先于系统，与 tokens.css 的 `.scheme-*` / `@media` 覆盖顺序一致。
 * pinia 尚未初始化（极早期调用）时按浅色兜底，不抛错。
 */
export function schemeColor(slot: SchemeColorSlot): string {
    let dark = false
    try {
        dark = useColorSchemeStore().isDark
    } catch {
        /* 无 active pinia：首帧前调用，按浅色兜底 */
    }
    return (dark ? DARK : LIGHT)[slot]
}
