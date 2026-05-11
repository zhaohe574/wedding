export type PcWidgetName =
    | 'pc-hero'
    | 'pc-about'
    | 'pc-advantages'
    | 'pc-gallery'
    | 'pc-stats'
    | 'pc-contact'

export const getImageUrl = (url?: string) => {
    if (!url) return ''
    if (/^https?:\/\//.test(url)) return url
    return url
}

export const normalizeList = <T = any>(value: unknown): T[] => {
    if (Array.isArray(value)) return value as T[]
    if (value && typeof value === 'object') return Object.values(value as Record<string, T>)
    return []
}

export const createPcStyles = (top: number, height: number) => ({
    position: 'absolute',
    left: '0px',
    top: `${top}px`,
    width: '1200px',
    height: `${height}px`
})

export const pcPreviewTheme = {
    ink: '#111111',
    muted: '#6f6a61',
    gold: '#c8a45d',
    paper: '#f7f3ec'
}
