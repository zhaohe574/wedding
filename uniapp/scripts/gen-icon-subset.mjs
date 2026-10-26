#!/usr/bin/env node
/**
 * 生成 tn 图标子集样式。
 *
 * 背景：@tuniao/tn-icon 的 dist/index.css 含 800+ 个 .tn-icon-* 类（约 36KB），
 * 被 App.vue 整包 import 后全部打进 app.wxss，而项目实际只用几十个。
 *
 * 策略（宁可多留，不可漏留）：
 *   1. 扫 src 下所有 .vue/.ts/.js，收集「所有字符串字面量」
 *   2. 与图标类名求交集 —— 这能覆盖 name="x"、icon: 'x'、entryIconMap 值、
 *      后端下发的装修配置默认值等间接引用
 *   3. 叠加别名表（BaseIcon 的 iconAliasMap）与手动白名单兜底
 *   4. 只输出被引用到的 .tn-icon-xxx:before 规则
 *
 * 用法：
 *   node scripts/gen-icon-subset.mjs          生成 src/styles/tn-icon-subset.scss
 *   node scripts/gen-icon-subset.mjs --check  只校验（CI 用），不一致则 exit 1
 */
import { readFileSync, writeFileSync, readdirSync, statSync, existsSync } from 'node:fs'
import { join, relative, dirname } from 'node:path'
import { fileURLToPath } from 'node:url'

const __dirname = dirname(fileURLToPath(import.meta.url))
const ROOT = join(__dirname, '..')
const SRC = join(ROOT, 'src')
const SOURCE_CSS = join(ROOT, 'node_modules/@tuniao/tn-icon/dist/index.css')
const OUT_FILE = join(SRC, 'styles/tn-icon-subset.scss')

/** 后端/配置驱动、无法静态分析出的图标，必须在此兜底 */
const MANUAL_WHITELIST = [
    'home', 'home-fill',
    'news', 'news-fill',
    'team', 'team-fill',
    'order', 'order-fill',
    'user', 'user-fill',
    'menu-circle', 'menu-circle-fill',
    'menu-grille',
    'right', 'down', 'up', 'left',
    'close', 'close-circle', 'close-circle-fill',
    'add', 'reduce', 'delete',
    'search', 'refresh', 'share', 'set', 'set-fill',
    'star', 'star-fill',
    'success', 'success-circle', 'warning', 'warning-circle', 'tip',
    'calendar', 'clock', 'location', 'phone', 'chat', 'email',
    'order', 'folder', 'funds', 'my', 'like', 'like-fill',
    'eye', 'edit', 'empty-data', 'trusty', 'play', 'play-fill',
    'wechat', 'wechat-fill', 'service', 'coupon', 'gift', 'money', 'wallet'
]

const EXT = new Set(['.vue', '.ts', '.js', '.mjs'])
const SKIP_DIRS = new Set(['node_modules', 'dist', 'unpackage', '.git'])

function walk(dir, acc = []) {
    for (const name of readdirSync(dir)) {
        if (SKIP_DIRS.has(name)) continue
        const full = join(dir, name)
        const st = statSync(full)
        if (st.isDirectory()) walk(full, acc)
        else if (EXT.has(name.slice(name.lastIndexOf('.')))) acc.push(full)
    }
    return acc
}

function extractAliasMap(source) {
    const block = source.match(/iconAliasMap[^{]*\{([\s\S]*?)\n\}/)
    if (!block) return []
    return [...block[1].matchAll(/:\s*'([a-z0-9-]+)'/g)].map((m) => m[1])
}

function main() {
    const checkMode = process.argv.includes('--check')

    if (!existsSync(SOURCE_CSS)) {
        console.error(`找不到源样式：${SOURCE_CSS}`)
        console.error('请先在 uniapp 目录执行 npm install')
        process.exit(1)
    }

    const css = readFileSync(SOURCE_CSS, 'utf8')
    const fontFace = css.match(/@font-face\{[^}]*\}/)?.[0] ?? ''
    const baseRule = css.match(/\[class\*=[^\]]*\tn-icon-[^\]]*\]\{[^}]*\}/)?.[0]
        ?? css.match(/\[class\*="?tn-icon-"?\]\{[^}]*\}/)?.[0]
        ?? ''
    const rules = [...css.matchAll(/\.tn-icon-([a-z0-9-]+):before\{content:"\\[0-9a-fA-F]+"\}/g)]
        .map((m) => ({ name: m[1], rule: m[0] }))

    if (!fontFace || !rules.length) {
        console.error('源样式解析失败：未找到 @font-face 或图标规则')
        process.exit(1)
    }

    const files = walk(SRC)
    const literals = new Set()
    let aliasNames = []
    for (const file of files) {
        const text = readFileSync(file, 'utf8')
        for (const m of text.matchAll(/['"`]([a-z0-9][a-z0-9-]{1,40})['"`]/g)) {
            literals.add(m[1])
        }
        if (file.endsWith('BaseIcon.vue')) aliasNames = extractAliasMap(text)
    }

    const known = new Set(rules.map((r) => r.name))
    const used = new Set()
    for (const name of literals) if (known.has(name)) used.add(name)
    for (const name of aliasNames) if (known.has(name)) used.add(name)
    for (const name of MANUAL_WHITELIST) if (known.has(name)) used.add(name)

    const picked = rules.filter((r) => used.has(r.name))
    const missing = [...used].filter((n) => !known.has(n))

    const header = [
        '// 本文件由 scripts/gen-icon-subset.mjs 自动生成，请勿手动修改。',
        '// 重新生成：npm run icons:gen',
        `// 源：@tuniao/tn-icon/dist/index.css（全量 ${rules.length} 个图标，本子集 ${picked.length} 个）`
    ].join('\n')

    const body = [fontFace, baseRule, ...picked.map((r) => r.rule)]
        .filter(Boolean)
        .join('\n')

    const output = `${header}\n${body}\n`

    if (checkMode) {
        const current = existsSync(OUT_FILE) ? readFileSync(OUT_FILE, 'utf8') : ''
        if (current !== output) {
            console.error('tn 图标子集与源码不一致，请执行 npm run icons:gen 后提交')
            process.exit(1)
        }
        console.log(`tn 图标子集校验通过：${picked.length} / ${rules.length} 个图标`)
        return
    }

    writeFileSync(OUT_FILE, output, 'utf8')
    const kb = (Buffer.byteLength(output) / 1024).toFixed(1)
    console.log(`已生成 ${relative(ROOT, OUT_FILE)}`)
    console.log(`  全量图标 ${rules.length} 个 → 保留 ${picked.length} 个`)
    console.log(`  体积 ${(Buffer.byteLength(css) / 1024).toFixed(1)} KB → ${kb} KB`)
    if (missing.length) {
        console.warn(`  警告：白名单中有 ${missing.length} 个图标在源样式里不存在：${missing.join(', ')}`)
    }
}

main()
