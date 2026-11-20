/**
 * 跨端契约使用守卫
 *
 * STATUS-001：状态中文名只能来自 shared/contracts/generated/status.ts 的 *_NAME_MAP。
 *   历史问题：4 端各写一份状态映射，导致同一订单在不同端显示不同状态。
 *   守卫规则：
 *     - 严格区（newuniapp/src）：出现任意一条 `数字: '中文'` 或 `{ label: '中文', value: 数字 }`
 *       且中文命中契约词表，即判失败。
 *     - 遗留区（uniapp/src、admin/src、pc）：沿用「同文件 >=2 条数字键映射」的宽松规则，
 *       避免一次性拦下数百处历史写法（老端正在被替换）。
 *
 * FORMAT-001：金额格式化只能走 shared/utils/format.ts 的 formatMoney。
 *   守卫规则：页面/组件源码中不得新增 toFixed(2) 字面量，以白名单文件放行。
 */
import { readdirSync, readFileSync, statSync, existsSync } from 'node:fs'
import path from 'node:path'

const root = process.cwd()
const STATUS_CONTRACT = path.join(root, 'shared/contracts/generated/status.ts')

/** 新代码：严格 */
const STRICT_ROOTS = ['newuniapp/src']
/** 遗留代码：宽松，待随老端下线一并移除 */
const LEGACY_ROOTS = ['uniapp/src', 'admin/src', 'pc']

const SOURCE_EXTS = ['.vue', '.ts', '.js']
const SKIP_DIRS = new Set(['node_modules', 'dist', '.nuxt', '.output', 'unpackage', 'public', 'uni_modules'])

/** 从生成契约读取全部状态文案；契约缺失时退回订单域硬编码，保证脚本始终可运行 */
function loadStatusLabels() {
    if (existsSync(STATUS_CONTRACT)) {
        const src = readFileSync(STATUS_CONTRACT, 'utf8')
        const block = src.match(/export const ALL_STATUS_LABELS = \[([\s\S]*?)\] as const/)
        if (block) {
            const labels = [...block[1].matchAll(/'([^']+)'/g)].map((m) => m[1])
            if (labels.length) return labels
        }
    }
    return ['待支付', '待付款', '待服务', '服务中', '已评价', '退款中', '已退款', '待确认', '已取消', '已暂停']
}

const STATUS_LABELS = loadStatusLabels()
const LABEL_ALT = STATUS_LABELS.map((s) => s.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')).join('|')

/** `2: '待服务'` / `[OrderStatus.X]: '待服务'` */
const MAP_ENTRY = new RegExp(`^\\s*(?:\\d+|\\[OrderStatus\\.[A-Z_]+\\])\\s*:\\s*['"](${LABEL_ALT})['"]`, 'm')
/** `{ label: '待服务', value: 2 }` */
const TAB_ENTRY = new RegExp(`\\{\\s*(?:label|name|text|title)\\s*:\\s*['"](${LABEL_ALT})['"]\\s*,\\s*value\\s*:\\s*\\d+`, 'g')

const TO_FIXED_ALLOWED_FILES = new Set([
    'admin/src/views/financial/settlement/index.vue',
    'admin/src/views/order/lists/components/dialogs/OrderRefundModal.vue',
    'admin/src/views/tools/quick/components/quick-tool-frame.vue'
])

const walk = (dir, files = []) => {
    let entries
    try {
        entries = readdirSync(dir)
    } catch {
        return files
    }
    for (const entry of entries) {
        if (SKIP_DIRS.has(entry)) continue
        const fullPath = path.join(dir, entry)
        const stat = statSync(fullPath)
        if (stat.isDirectory()) walk(fullPath, files)
        else if (SOURCE_EXTS.includes(path.extname(entry))) files.push(fullPath)
    }
    return files
}

const toDisplayPath = (filePath) => path.relative(root, filePath).split(path.sep).join('/')
const failures = []
/** 遗留区问题只报告不阻断：老端正在被 newuniapp 替换，全量迁移不划算，作为迁移清单跟踪 */
const warnings = []

// ---------- STATUS-001 严格区 ----------
const strictFiles = STRICT_ROOTS.flatMap((dir) => walk(path.join(root, dir)))
for (const file of strictFiles) {
    const displayPath = toDisplayPath(file)
    const lines = readFileSync(file, 'utf8').split(/\r?\n/)
    lines.forEach((line, index) => {
        if (MAP_ENTRY.test(line)) {
            failures.push(`STATUS-001 ${displayPath}:${index + 1} 手写状态映射条目，请引用 ORDER_STATUS_NAME_MAP`)
            return
        }
        TAB_ENTRY.lastIndex = 0
        if (TAB_ENTRY.test(line)) {
            failures.push(`STATUS-001 ${displayPath}:${index + 1} 手写状态 tab 文案，请引用 *_NAME_MAP`)
        }
    })
}

// ---------- STATUS-001 遗留区 ----------
const legacyFiles = LEGACY_ROOTS.flatMap((dir) => walk(path.join(root, dir)))
for (const file of legacyFiles) {
    const displayPath = toDisplayPath(file)
    const lines = readFileSync(file, 'utf8').split(/\r?\n/)
    let entries = 0
    let firstHitLine = 0
    lines.forEach((line, index) => {
        if (MAP_ENTRY.test(line)) {
            entries += 1
            if (firstHitLine === 0) firstHitLine = index + 1
        }
    })
    if (entries >= 2) {
        warnings.push(
            `STATUS-001 ${displayPath}:${firstHitLine} 出现 ${entries} 条状态名映射条目，` +
                '应改为引用 shared/contracts/generated/status.ts 的 *_NAME_MAP'
        )
    }
}

// ---------- FORMAT-001 ----------
const allFiles = [...strictFiles, ...legacyFiles]
for (const file of allFiles) {
    const displayPath = toDisplayPath(file)
    if (displayPath.startsWith('shared/')) continue
    if (TO_FIXED_ALLOWED_FILES.has(displayPath)) continue
    if (readFileSync(file, 'utf8').includes('toFixed(2)')) {
        failures.push(
            `FORMAT-001 ${displayPath}: 页面/组件不得直接使用 toFixed(2) 格式化金额，` +
                '请改用 shared/utils/format.ts 的 formatMoney'
        )
    }
}

if (failures.length > 0) {
    console.error('跨端契约使用守卫未通过：')
    for (const failure of failures) console.error(` - ${failure}`)
    if (warnings.length) {
        console.error(`\n另有 ${warnings.length} 处遗留区待迁移（不阻断）：`)
        for (const w of warnings) console.error(` ~ ${w}`)
    }
    process.exit(1)
}

if (warnings.length) {
    console.warn(`遗留区待迁移 ${warnings.length} 处（不阻断，随老端下线一并移除）：`)
    for (const w of warnings) console.warn(` ~ ${w}`)
}

console.log(
    `跨端契约使用守卫通过：严格区 ${strictFiles.length} 个文件 / 遗留区 ${legacyFiles.length} 个文件` +
        `（词表 ${STATUS_LABELS.length} 条，来源 ${existsSync(STATUS_CONTRACT) ? 'generated/status.ts' : '内置兜底'}）`
)
