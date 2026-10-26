// QA 资产 · 金额格式化视觉等价性验证
//
// 运行：node tests/qa/format_money_checks.mjs
//
// 背景：FORMAT-001 把散落在 7 个业务站点上的手写金额格式化收敛到
// shared/utils/format.ts 的 formatMoney()。收敛的最大风险是「视觉不一致」——
// 用户看到的金额多一位小数、少了 ¥、或者 100 变成 100.00。
// 本脚本把每个站点的【原实现】与【formatMoney 调用方式】逐值对比，
// 覆盖整数 / 一位小数 / 两位小数 / 三位小数 / 0 / 负数 / 字符串 / 空值 / 非法值 / 大额。
//
// 退出码：0 全部站点输出完全一致；1 存在差异（会打印差异表）。
import { formatMoney } from '../../shared/utils/format.ts'

// 覆盖：整数 / 一位小数 / 两位小数 / 三位小数 / 0 / 负数 / 字符串 / 空值 / 非法值 / 大额
const VALUES = [
    0, 1, 12, 12.3, 12.34, 12.345, 12.5, 100, 100.0, 100.5,
    1234.5, 1234.56, 12345.678, 99999.99, 1000000,
    -0.001, -12.3, -100,
    '0', '12.3', '12.34', '100', '1000.00', '', '   ', 'abc', null, undefined, NaN
]

const pad = (text, width = 18) => String(text).padEnd(width)
let failures = 0
let improvements = 0

/**
 * 判断一组差异是否属于「NaN 泄漏修复」这一预期改进。
 *
 * 原实现在遇到非数字字符串（如 'abc'）时会算出 NaN 并直接渲染到界面上（用户看到 "NaN" / "¥NaN.00"）；
 * formatMoney 按设计回落到 0.00（见 shared/utils/format.ts 的 fallback 注释）。
 * 这类差异属于修正而非回归，但必须满足「新实现绝不输出 NaN」，否则依然判失败。
 */
const isNaNLeakFix = (oldOut, newOut) => {
    const oldText = String(oldOut)
    const newText = String(newOut)
    return oldText.includes('NaN') && !newText.includes('NaN')
}

/**
 * 对某个业务站点的「原实现」与「formatMoney 实现」逐值比对。
 * @param {string} name 站点标识（含文件与行号，便于回溯）
 * @param {(v: unknown) => unknown} oldFn 原实现
 * @param {(v: unknown) => unknown} newFn 收敛后的实现
 * @returns {number} 未预期的差异组数
 */
function cmp(name, oldFn, newFn) {
    const rows = []
    const fixed = []
    for (const value of VALUES) {
        let oldOut
        let newOut
        try { oldOut = oldFn(value) } catch (e) { oldOut = `[THROW ${e.constructor.name}]` }
        try { newOut = newFn(value) } catch (e) { newOut = `[THROW ${e.constructor.name}]` }
        if (oldOut === newOut) continue
        if (isNaNLeakFix(oldOut, newOut)) {
            improvements++
            fixed.push({ value, oldOut, newOut })
            continue
        }
        failures++
        rows.push({ value, oldOut, newOut })
    }

    console.log(`\n=== ${name} ===`)
    if (rows.length === 0) {
        console.log(`  ✔ 全部 ${VALUES.length} 组输入输出完全一致`)
    } else {
        console.log(`  ✘ 未预期差异 ${rows.length} 组：`)
        console.log('  ' + pad('input') + pad('原实现') + '新实现')
        for (const row of rows) {
            console.log('  ' + pad(JSON.stringify(row.value)) + pad(JSON.stringify(row.oldOut)) + JSON.stringify(row.newOut))
        }
    }

    for (const row of fixed) {
        console.log(`  · 预期改进：${JSON.stringify(row.value)} 原实现 ${JSON.stringify(row.oldOut)} → 新实现 ${JSON.stringify(row.newOut)}（NaN 不再泄漏到界面）`)
    }

    return rows.length
}

const toNumber = (value) => {
    const num = Number(value)
    return Number.isFinite(num) ? num : 0
}

// 1) staff_order_detail.vue / admin offline-order-drawer.vue / order.vue：Number(x||0).toFixed(2)
cmp(
    '站点1 staff_order_detail.vue:560 + admin/offline-order-drawer.vue:475 + order.vue:374',
    (v) => Number(v || 0).toFixed(2),
    (v) => formatMoney(v)
)

// 2) order_change/shared.ts:100  amount.toFixed(2).replace(/\.00$/,'')
cmp(
    '站点3 order_change/shared.ts:100',
    (v) => toNumber(v).toFixed(2).replace(/\.00$/, ''),
    (v) => formatMoney(v, { hideZeroDecimals: true })
)

// 3) staff_center.vue:508  `¥${amount.toFixed(2).replace(/\.00$/,'')}`
cmp(
    '站点4 staff_center.vue:508',
    (v) => `¥${toNumber(v).toFixed(2).replace(/\.00$/, '')}`,
    (v) => formatMoney(v, { symbol: '¥', hideZeroDecimals: true })
)

// 4) staff_settlement.vue:327  `¥${amount.toFixed(2)}`
cmp(
    '站点5 staff_settlement.vue:327',
    (v) => `¥${toNumber(v).toFixed(2)}`,
    (v) => formatMoney(v, { symbol: '¥' })
)

// 5) staff_addon_list.vue:325  Number.isInteger(amount) ? String(amount) : amount.toFixed(2)
cmp(
    '站点6 staff_addon_list.vue:325 (integerAsIs)',
    (v) => {
        const amount = Number(v || 0)
        return Number.isInteger(amount) ? String(amount) : amount.toFixed(2)
    },
    (v) => formatMoney(v, { integerAsIs: true })
)

// 6) order.vue:379 formatPriceParts 的 split('.') 拆分展示
cmp(
    '站点7 order.vue:379 formatPriceParts(拆分后展示)',
    (v) => {
        const num = Math.max(Number(v || 0), 0)
        const [integerPart, decimalPart] = num.toFixed(2).split('.')
        return `¥${integerPart || '0'}.${decimalPart || '00'}`
    },
    (v) => {
        const num = Math.max(Number(v || 0), 0)
        const [integerPart, decimalPart] = formatMoney(num).split('.')
        return `¥${integerPart || '0'}.${decimalPart || '00'}`
    }
)

console.log(`\n未预期差异组数：${failures}（另有 ${improvements} 组 NaN 泄漏修复，属预期改进）`)
process.exit(failures > 0 ? 1 : 0)
