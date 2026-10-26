#!/usr/bin/env node
/**
 * 把 uni.$u.toast(...) 替换为 @/utils/feedback 的 showToast(...)。
 *
 * 目的：解除对 uView 全局插件的依赖，让 vk-uview-ui 不再被打进小程序主包
 * （全局插件会把整库 120KB 组件 + 47KB libs 拉进主包，而实际用到 uView 的页面都在分包）。
 *
 * 用法：node scripts/codemod/uview-toast.mjs [--dry]
 */
import { readFileSync, writeFileSync } from 'node:fs'
import { join, dirname } from 'node:path'
import { fileURLToPath } from 'node:url'

const __dirname = dirname(fileURLToPath(import.meta.url))
const ROOT = join(__dirname, '../..')
const DRY = process.argv.includes('--dry')

const FILES = [
    'src/components/mplogin-popup/mplogin-popup.vue',
    'src/packages/pages/news_detail/news_detail.vue',
    'src/pages/forget_pwd/forget_pwd.vue',
    'src/pages/index/component/mp-privacy-popup.vue',
    'src/pages/register/register.vue',
    'src/utils/request/index.ts'
]

const IMPORT_RE = /import\s*\{([^}]*)\}\s*from\s*['"]@\/utils\/feedback['"];?/

function ensureImport(code) {
    const existing = code.match(IMPORT_RE)
    if (existing) {
        const names = existing[1].split(',').map((s) => s.trim()).filter(Boolean)
        if (names.some((n) => n.split(/\s+as\s+/)[0].trim() === 'showToast')) return code
        const merged = `import { ${[...names, 'showToast'].join(', ')} } from '@/utils/feedback'`
        return code.replace(IMPORT_RE, merged)
    }

    const importLine = "import { showToast } from '@/utils/feedback'"
    const lastImport = [...code.matchAll(/^import\s.+$/gm)].pop()
    if (lastImport) {
        const end = lastImport.index + lastImport[0].length
        return `${code.slice(0, end)}\n${importLine}${code.slice(end)}`
    }
    return code.replace(/(<script[^>]*>)/, `$1\n${importLine}`)
}

let totalReplace = 0
for (const rel of FILES) {
    const file = join(ROOT, rel)
    const before = readFileSync(file, 'utf8')
    const count = (before.match(/uni\.\$u\.toast\(/g) || []).length
    if (!count) {
        console.log(`跳过 ${rel}（无匹配）`)
        continue
    }
    let after = before.replace(/uni\.\$u\.toast\(/g, 'showToast(')
    after = ensureImport(after)
    totalReplace += count
    if (DRY) {
        console.log(`[dry] ${rel}: ${count} 处`)
    } else {
        writeFileSync(file, after, 'utf8')
        console.log(`${rel}: 替换 ${count} 处`)
    }
}
console.log(`合计 ${totalReplace} 处${DRY ? '（未写入）' : ''}`)
