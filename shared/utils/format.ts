/**
 * 跨端金额与日期格式化（唯一事实源）
 *
 * 设计约束：
 * 1. 不引入任何第三方依赖（uniapp 主包余量紧张，禁止 dayjs）。
 * 2. 默认行为必须与历史各端实现视觉一致：
 *    - 金额默认不带货币符号、不带千分位（历史代码绝大多数是 `Number(v).toFixed(2)`）。
 *    - 需要 `¥` 前缀或千分位时显式传参，避免一刀切改变用户看到的样式。
 * 3. 小程序 `new Date('2026-10-31 10:00')` 在部分机型/基础库上解析失败，
 *    因此字符串统一走手工解析，不依赖 Date 构造函数对非 ISO 格式的支持。
 */

/** 金额可接受入参：number / 数字字符串 / null / undefined */
export type MoneyLike = number | string | null | undefined
/** 日期可接受入参：Date / 秒级或毫秒级时间戳 / 日期字符串 */
export type DateLike = Date | number | string | null | undefined

export interface FormatMoneyOptions {
    /** 小数位，默认 2 */
    decimals?: number
    /** 货币符号前缀，默认不加 */
    symbol?: string
    /** 是否加千分位，默认不加 */
    thousand?: boolean
    /** 是否去掉小数末尾多余的 0（1.50 -> 1.5，2.00 -> 2） */
    trimZeros?: boolean
    /** 小数部分全为 0 时隐藏小数（2.00 -> 2，2.30 -> 2.30），等价历史 .replace(/\.00$/, '') */
    hideZeroDecimals?: boolean
    /** 整数不补小数位（100 -> '100'，100.5 -> '100.50'），兼容历史 Number.isInteger 分支 */
    integerAsIs?: boolean
    /** 数值非法时的返回值，默认 '0.00' 按 decimals 生成 */
    fallback?: string
}

/** 给整数部分加千分位 */
const withThousandSeparator = (text: string): string => {
    const negative = text.startsWith('-')
    const body = negative ? text.slice(1) : text
    const [integerPart, decimalPart] = body.split('.')
    const grouped = integerPart.replace(/\B(?=(\d{3})+(?!\d))/g, ',')
    const result = decimalPart === undefined ? grouped : `${grouped}.${decimalPart}`
    return negative ? `-${result}` : result
}

/** 去掉小数末尾多余的 0 与孤立的小数点 */
const trimTrailingZeros = (text: string): string => {
    if (!text.includes('.')) return text
    return text.replace(/0+$/, '').replace(/\.$/, '')
}

/**
 * 金额格式化。
 * @example formatMoney(1234.5) // '1234.50'
 * @example formatMoney(1234.5, { symbol: '¥' }) // '¥1234.50'
 * @example formatMoney(1234.5, { symbol: '¥', thousand: true }) // '¥1,234.50'
 * @example formatMoney(100, { integerAsIs: true }) // '100'
 */
export function formatMoney(value: MoneyLike, options: FormatMoneyOptions = {}): string {
    const decimals = Number.isInteger(options.decimals) && (options.decimals as number) >= 0 ? (options.decimals as number) : 2
    const raw = typeof value === 'number' ? value : Number(value ?? 0)

    // 非法数值：有 fallback 时直接返回 fallback，否则按 0 处理（与历史实现一致）
    if (!Number.isFinite(raw) && typeof options.fallback === 'string') {
        return options.fallback
    }

    const num = Number.isFinite(raw) ? raw : 0

    if (options.integerAsIs && Number.isInteger(num)) {
        const text = String(num)
        const grouped = options.thousand ? withThousandSeparator(text) : text
        return `${options.symbol || ''}${grouped}`
    }

    let text = num.toFixed(decimals)
    if (options.trimZeros) text = trimTrailingZeros(text)
    if (options.hideZeroDecimals && /\.0+$/.test(text)) text = text.replace(/\.0+$/, '')
    if (options.thousand) text = withThousandSeparator(text)

    if (text === '' || text === '-') text = decimals > 0 ? `0.${'0'.repeat(decimals)}` : '0'

    return `${options.symbol || ''}${text}`
}

/**
 * 解析日期入参为 Date；无法解析返回 null。
 * 兼容：Date / 秒级时间戳 / 毫秒级时间戳 / 'YYYY-MM-DD' / 'YYYY-MM-DD HH:mm(:ss)' / ISO 字符串。
 */
export function parseDateValue(value: DateLike): Date | null {
    if (value instanceof Date) {
        return Number.isNaN(value.getTime()) ? null : value
    }

    if (typeof value === 'number') {
        if (!Number.isFinite(value) || value <= 0) return null
        const timestamp = value < 1e12 ? value * 1000 : value
        const date = new Date(timestamp)
        return Number.isNaN(date.getTime()) ? null : date
    }

    if (typeof value === 'string') {
        const trimmed = value.trim()
        if (!trimmed) return null
        if (/^\d+$/.test(trimmed)) return parseDateValue(Number(trimmed))

        const matched = trimmed.match(
            /^(\d{4})[-/](\d{1,2})[-/](\d{1,2})(?:[T ](\d{1,2}):(\d{1,2})(?::(\d{1,2}))?)?/
        )
        if (matched) {
            const date = new Date(
                Number(matched[1]),
                Number(matched[2]) - 1,
                Number(matched[3]),
                Number(matched[4] || 0),
                Number(matched[5] || 0),
                Number(matched[6] || 0)
            )
            return Number.isNaN(date.getTime()) ? null : date
        }

        const parsed = new Date(trimmed)
        return Number.isNaN(parsed.getTime()) ? null : parsed
    }

    return null
}

const padZero = (value: number): string => (value < 10 ? `0${value}` : String(value))

/**
 * 按 pattern 格式化日期，支持 YYYY / MM / DD / HH / mm / ss。
 * 默认 'YYYY-MM-DD'；无法解析时返回 fallback（默认空字符串）。
 */
export function formatDate(value: DateLike, pattern = 'YYYY-MM-DD', fallback = ''): string {
    const date = parseDateValue(value)
    if (!date) return fallback

    return pattern
        .replace(/YYYY/g, String(date.getFullYear()))
        .replace(/MM/g, padZero(date.getMonth() + 1))
        .replace(/DD/g, padZero(date.getDate()))
        .replace(/HH/g, padZero(date.getHours()))
        .replace(/mm/g, padZero(date.getMinutes()))
        .replace(/ss/g, padZero(date.getSeconds()))
}

/** 日期时间格式化，默认 'YYYY-MM-DD HH:mm'。 */
export function formatDateTime(value: DateLike, pattern = 'YYYY-MM-DD HH:mm', fallback = ''): string {
    return formatDate(value, pattern, fallback)
}
