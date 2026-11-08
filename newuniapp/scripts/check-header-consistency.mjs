#!/usr/bin/env node
/**
 * 页面头部一致性校验（R-H1 / R-H2 / R-H3）
 * ---------------------------------------------------------------
 * 24 屏头部统一到 page-shell → nav-bar 之后，剩下的三类回归都必须被机器拦住：
 *
 *  R-H1 双头部：页面用了 <page-shell>（没传 :nav="false"），但 pages.json 没给该页
 *        navigationStyle:custom —— 系统导航条 + 自绘 nav-bar 会同时渲染，顶上多一条。
 *  R-H2 自绘头部复活：页面里又出现 statusBarRpx / statusTopRpx / navSolid /
 *        class="dnav" / class="pnav" / class="bnav" / class="hd__title"。
 *        这些是本次治理删掉的私有实现，谁再抄一份就是绕过组件。
 *  R-H3 胶囊避让漏传：#nav-right 里有动作，page-shell 却没传 :nav-capsule-avoid="true"，
 *        按钮会被微信右上角胶囊压住（feed 曾经的 margin-right:96rpx 就是这么来的）。
 *
 * 用法：node scripts/check-header-consistency.mjs
 * 输出：每条违规 = 相对路径:行号  [规则] 说明；任一命中则 exit 1
 */
import { readFileSync, readdirSync, statSync, existsSync } from 'node:fs'
import { dirname, join, resolve, relative } from 'node:path'
import { fileURLToPath } from 'node:url'

const __dirname = dirname(fileURLToPath(import.meta.url))
const ROOT = resolve(__dirname, '..')
const SRC = join(ROOT, 'src')

const SKIP_DIRS = new Set(['node_modules', 'dist', 'uni_modules', 'unpackage', 'static'])

/** 去掉 pages.json 里的 // 与 /* *\/ 注释（uni-app 允许注释，且条件编译块也是注释） */
function stripJsonComments(text) {
    let out = ''
    let inString = false
    let inLine = false
    let inBlock = false

    for (let i = 0; i < text.length; i += 1) {
        const ch = text[i]
        const next = text[i + 1]

        if (inLine) {
            if (ch === '\n') {
                inLine = false
                out += ch
            }
            continue
        }
        if (inBlock) {
            if (ch === '*' && next === '/') {
                inBlock = false
                i += 1
            }
            continue
        }
        if (inString) {
            out += ch
            if (ch === '\\') {
                out += next
                i += 1
            } else if (ch === '"') {
                inString = false
            }
            continue
        }
        if (ch === '"') {
            inString = true
            out += ch
            continue
        }
        if (ch === '/' && next === '/') {
            inLine = true
            i += 1
            continue
        }
        if (ch === '/' && next === '*') {
            inBlock = true
            i += 1
            continue
        }
        out += ch
    }
    return out
}

/**
 * 去掉尾随逗号（`},` / `],`）。
 *
 * 为什么需要：pages.json 是 JSONC，uni-app 允许尾逗号，页面 style 只有条件编译声明时
 * 很容易写出 `"navigationBarTitleText": "x",\n}` 这种结构。直接 JSON.parse 会报
 * 「Expected double-quoted property name」，让校验脚本自己先崩掉——脚本崩了就等于没有校验。
 */
function stripTrailingCommas(text) {
    let out = ''
    let inString = false
    for (let i = 0; i < text.length; i += 1) {
        const ch = text[i]
        if (inString) {
            out += ch
            if (ch === '\\') {
                out += text[i + 1] ?? ''
                i += 1
            } else if (ch === '"') {
                inString = false
            }
            continue
        }
        if (ch === '"') {
            inString = true
            out += ch
            continue
        }
        if (ch === ',') {
            // 向后看：跳过空白后若是 } 或 ]，说明这是尾逗号
            let j = i + 1
            while (j < text.length && /\s/.test(text[j])) j += 1
            if (text[j] === '}' || text[j] === ']') continue
        }
        out += ch
    }
    return out
}

/* ------------------------------------------------------------------ */
/* 1. pages.json 注册表：route -> style                                 */
/* ------------------------------------------------------------------ */

const pagesJsonPath = join(SRC, 'pages.json')
if (!existsSync(pagesJsonPath)) {
    console.error('找不到 src/pages.json')
    process.exit(1)
}

// 注意：条件编译的 // #ifndef H5 / // #endif 也是注释，剥完注释后
// 「#ifndef H5 分支的 navigationStyle」与「#ifdef H5 分支的标题」会同时留在同一个
// style 对象里 —— 这正是我们想要的：只要存在 navigationStyle:custom 就算通过。
const config = JSON.parse(stripTrailingCommas(stripJsonComments(readFileSync(pagesJsonPath, 'utf8'))))

const pageStyles = new Map() // '/pages/x/y' -> style 对象
for (const page of config.pages || []) {
    pageStyles.set('/' + page.path, page.style || {})
}
for (const sub of config.subPackages || []) {
    for (const page of sub.pages || []) {
        pageStyles.set('/' + sub.root + '/' + page.path, page.style || {})
    }
}

/* ------------------------------------------------------------------ */
/* 2. 收集所有 .vue                                                      */
/* ------------------------------------------------------------------ */

function walk(dir, files = []) {
    let entries
    try {
        entries = readdirSync(dir)
    } catch {
        return files
    }
    for (const entry of entries) {
        if (SKIP_DIRS.has(entry)) continue
        const full = join(dir, entry)
        if (statSync(full).isDirectory()) walk(full, files)
        else if (full.endsWith('.vue')) files.push(full)
    }
    return files
}

/** src 内的 .vue 文件 → 页面路由；非页面（组件等）返回 '' */
function toRoute(file) {
    const rel = relative(SRC, file).split('\\').join('/')
    if (!/^(pages|packages)\//.test(rel)) return ''
    return '/' + rel.replace(/\.vue$/, '')
}

/**
 * 从 `<page-shell` 开始，按引号状态扫描到真正的标签结束 `>`。
 * 不能直接用 /<page-shell[^>]*>/ —— booking 的属性里有 `step < 3 ? ... : ...`。
 */
function readOpenTag(text, start) {
    let quote = ''
    for (let i = start; i < text.length; i += 1) {
        const ch = text[i]
        if (quote) {
            if (ch === quote) quote = ''
            continue
        }
        if (ch === '"' || ch === "'") {
            quote = ch
            continue
        }
        if (ch === '>') return text.slice(start, i + 1)
    }
    return text.slice(start)
}

/** 行号：字符下标 → 1-based 行号 */
function lineOf(text, index) {
    return text.slice(0, index).split(/\r?\n/).length
}

/* ------------------------------------------------------------------ */
/* 3. 三条规则                                                          */
/* ------------------------------------------------------------------ */

const hits = []

/**
 * R-H2 命中表。
 *  - re  ：标识符必须整词命中（\b），否则 nav-bar 的 navSolidThreshold 这类
 *          合法新 prop 会被 "navSolid" 子串误伤。
 *  - 字面量片段（class="dnav" 等）本身带引号，直接 includes 即可。
 */
const R_H2_TOKENS = [
    { re: /\bstatusBarRpx\b/, label: 'statusBarRpx' },
    { re: /\bstatusTopRpx\b/, label: 'statusTopRpx' },
    { re: /\bnavSolid\b/, label: 'navSolid' },
    { re: /class="dnav"/, label: 'class="dnav"' },
    { re: /class="pnav"/, label: 'class="pnav"' },
    { re: /class="bnav"/, label: 'class="bnav"' },
    { re: /class="hd__title"/, label: 'class="hd__title"' }
]

const files = walk(SRC)

for (const file of files) {
    const text = readFileSync(file, 'utf8')
    const rel = relative(ROOT, file).split('\\').join('/')
    const route = toRoute(file)

    /* ---- R-H1 / R-H3：只看页面自己（组件里的 page-shell 不是页面） ---- */
    const shellIdx = text.indexOf('<page-shell')
    if (shellIdx >= 0 && route) {
        const openTag = readOpenTag(text, shellIdx)
        const usesCustomNav = !/:(?:nav|nav\s*)=?\s*"false"/.test(openTag) && !/\snav="false"/.test(openTag)

        // R-H1 双头部
        if (usesCustomNav) {
            const style = pageStyles.get(route)
            if (!style) {
                hits.push({
                    rel,
                    line: lineOf(text, shellIdx),
                    rule: 'R-H1',
                    msg: `用了 <page-shell> 自定义导航，但 pages.json 未注册该页：${route}`
                })
            } else if (style.navigationStyle !== 'custom') {
                hits.push({
                    rel,
                    line: lineOf(text, shellIdx),
                    rule: 'R-H1',
                    msg: `用了 <page-shell> 自定义导航，但 pages.json 里 ${route} 缺 "navigationStyle": "custom"（会出现系统导航 + 自绘导航双头部）`
                })
            }
        }

        // R-H3 胶囊避让
        const rightIdx = text.indexOf('#nav-right')
        if (rightIdx >= 0) {
            const rightBody = text.slice(rightIdx, text.indexOf('</template>', rightIdx))
            const hasContent = rightBody.replace(/#nav-right[^>]*>/g, '').trim().length > 0
            if (hasContent && !/:nav-capsule-avoid\s*=\s*"\s*(true|isLogin|1)\s*"/.test(openTag)) {
                hits.push({
                    rel,
                    line: lineOf(text, rightIdx),
                    rule: 'R-H3',
                    msg: '用了 #nav-right 放头部动作，但 <page-shell> 没传 :nav-capsule-avoid="true"（按钮会被右上角胶囊压住）'
                })
            }
        }
    }

    /* ---- R-H2：自绘头部复活（整文件扫描，注释行豁免） ---- */
    const lines = text.split(/\r?\n/)
    lines.forEach((line, i) => {
        const trimmed = line.trim()
        if (/^(\/\/|\/\*|\*|<!--)/.test(trimmed)) return
        for (const token of R_H2_TOKENS) {
            if (token.re.test(line)) {
                hits.push({
                    rel,
                    line: i + 1,
                    rule: 'R-H2',
                    msg: `自绘头部实现复活（命中 ${token.label}）：必须走 page-shell → nav-bar，不要再在页面里自己算状态栏/实底态`
                })
            }
        }
    })
}

for (const h of hits) console.log(`${h.rel}:${h.line}  [${h.rule}] ${h.msg}`)

const byRule = hits.reduce((m, h) => ((m[h.rule] = (m[h.rule] || 0) + 1), m), {})
console.log(`\n扫描 ${files.length} 个 .vue 文件，违规 ${hits.length} 处`)
console.log('按规则汇总：', Object.keys(byRule).length ? JSON.stringify(byRule, null, 2) : '{}（全绿）')
process.exit(hits.length ? 1 : 0)
