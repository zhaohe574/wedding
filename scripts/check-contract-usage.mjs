/**
 * 跨端契约使用守卫（T19 / T20）
 *
 * STATUS-001：订单状态中文名只能来自 shared/contracts/core.ts 的 ORDER_STATUS_NAME_MAP。
 *   历史上 4 端各写一份状态映射（形如 `2: '待服务'`），导致同一订单在不同端显示不同状态。
 *   守卫规则：同一文件内出现 >=3 条形如 `数字键: '中文状态名'` 的映射条目，视为又复制了一份状态映射表。
 *   说明：筛选 tab（{ label: '待服务', value: 2 }）属于 UI 文案，不在守卫范围内。
 *
 * FORMAT-001：金额格式化只能走 shared/utils/format.ts 的 formatMoney。
 *   守卫规则：页面/组件源码中不得新增 toFixed(2) 字面量，仅允许少量非金额用途
 *   （费率取整、退款金额算术取整、文件大小），以白名单文件形式放行。
 */
import { readdirSync, readFileSync, statSync } from 'node:fs'
import path from 'node:path'

const root = process.cwd()
const sharedDir = path.join(root, 'shared')

const SOURCE_ROOTS = ['uniapp/src', 'admin/src', 'pc']
const SOURCE_EXTS = ['.vue', '.ts', '.js']
const SKIP_DIRS = new Set(['node_modules', 'dist', '.nuxt', '.output', 'unpackage', 'public'])

// 仅取订单域特有的状态名，避免误伤工单/售后等其它状态字典（如 待分配/处理中）
const STATUS_NAMES = ['待支付', '待付款', '待服务', '服务中', '已评价', '退款中', '已退款']
// 匹配 `2: '待服务'` / `[OrderStatus.PENDING_SERVICE]: '待服务'` 这类状态映射条目
const STATUS_MAP_ENTRY = new RegExp(
    `^\\s*(?:\\d+|\\[OrderStatus\\.[A-Z_]+\\])\\s*:\\s*['\"](${STATUS_NAMES.join('|')})['\"]`,
    'm'
)
const STATUS_MAP_THRESHOLD = 2

/** toFixed(2) 白名单：均为非「金额展示」用途 */
const TO_FIXED_ALLOWED_FILES = new Set([
    'admin/src/views/financial/settlement/index.vue', // 费率取整（数值，非金额展示）
    'admin/src/views/order/lists/components/dialogs/OrderRefundModal.vue', // 退款金额算术取整
    'admin/src/views/tools/quick/components/quick-tool-frame.vue' // 文件大小 MB
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
        if (stat.isDirectory()) {
            walk(fullPath, files)
        } else if (SOURCE_EXTS.includes(path.extname(entry))) {
            files.push(fullPath)
        }
    }
    return files
}

const toDisplayPath = (filePath) => path.relative(root, filePath).split(path.sep).join('/')

const failures = []

// STATUS-001
const sourceFiles = SOURCE_ROOTS.flatMap((dir) => walk(path.join(root, dir)))
for (const file of sourceFiles) {
    const displayPath = toDisplayPath(file)
    const lines = readFileSync(file, 'utf8').split(/\r?\n/)
    let entries = 0
    let firstHitLine = 0
    lines.forEach((line, index) => {
        if (STATUS_MAP_ENTRY.test(line)) {
            entries += 1
            if (firstHitLine === 0) firstHitLine = index + 1
        }
    })
    if (entries >= STATUS_MAP_THRESHOLD) {
        failures.push(
            `STATUS-001 ${displayPath}:${firstHitLine} 出现 ${entries} 条订单状态名映射条目，` +
                '请改为引用 shared/contracts/core.ts 的 ORDER_STATUS_NAME_MAP'
        )
    }
}

// FORMAT-001
for (const file of sourceFiles) {
    const displayPath = toDisplayPath(file)
    if (displayPath.startsWith('shared/')) continue
    if (TO_FIXED_ALLOWED_FILES.has(displayPath)) continue
    const content = readFileSync(file, 'utf8')
    if (content.includes('toFixed(2)')) {
        failures.push(
            `FORMAT-001 ${displayPath}: 页面/组件不得直接使用 toFixed(2) 格式化金额，` +
                '请改用 shared/utils/format.ts 的 formatMoney'
        )
    }
}

if (failures.length > 0) {
    console.error('跨端契约使用守卫未通过：')
    for (const failure of failures) console.error(` - ${failure}`)
    process.exit(1)
}

console.log(`跨端契约使用守卫通过：扫描 ${sourceFiles.length} 个文件（STATUS-001 / FORMAT-001）`)
