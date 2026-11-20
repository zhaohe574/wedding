#!/usr/bin/env node
/**
 * 后台接口契约检查：前端 admin/src/api 里声明的每个 URL，
 * 必须在 server/app/adminapi 中找到对应的控制器与 action。
 *
 * 用途：避免前端调用了不存在的接口（拼写错误、后端改名、死代码）。
 * 用法：node scripts/audit/check-admin-endpoints.mjs [--strict]
 *   --strict  发现缺失时以非 0 退出码结束（供 CI 使用）
 */

import fs from 'node:fs'
import path from 'node:path'
import { fileURLToPath } from 'node:url'

const ROOT = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '../..')
const ADMIN_API = path.join(ROOT, 'admin/src/api')
const CTRL_DIR = path.join(ROOT, 'server/app/adminapi/controller')
const STRICT = process.argv.includes('--strict')

const walk = (dir, ext) => {
    const out = []
    if (!fs.existsSync(dir)) return out
    for (const entry of fs.readdirSync(dir, { withFileTypes: true })) {
        const full = path.join(dir, entry.name)
        if (entry.isDirectory()) out.push(...walk(full, ext))
        else if (entry.name.endsWith(ext)) out.push(full)
    }
    return out
}

/** 与 ThinkPHP Str::camel 对齐：含下划线/连字符时转驼峰并小写首字母 */
const camel = (value) => {
    if (!/[_-]/.test(value)) return value
    const studly = value.replace(/[_-]+(.)/g, (_, c) => c.toUpperCase())
    return studly.charAt(0).toLowerCase() + studly.slice(1)
}

const normalizeKey = (value) => camel(value).toLowerCase()

// 1) 收集前端 URL
const urls = []
for (const file of walk(ADMIN_API, '.ts')) {
    const source = fs.readFileSync(file, 'utf8')
    for (const match of source.matchAll(/url:\s*['"`]([^'"`]+)['"`]/g)) {
        urls.push({ url: match[1], file: path.relative(ROOT, file).replace(/\\/g, '/') })
    }
}

// 2) 建立控制器 -> action 索引（跟随 extends 继承链，兼容别名控制器）
const classes = new Map()
for (const file of walk(CTRL_DIR, '.php')) {
    const source = fs.readFileSync(file, 'utf8')
    const namespace = /namespace\s+([^;]+);/.exec(source)?.[1]?.trim() || ''
    const declared = /class\s+([A-Za-z0-9_]+)\s*(?:extends\s+([A-Za-z0-9_\\]+))?/.exec(source)
    if (!declared) continue

    const actions = new Set()
    for (const match of source.matchAll(/public\s+function\s+([A-Za-z0-9_]+)\s*\(/g)) {
        actions.add(match[1].toLowerCase())
    }

    let parent = (declared[2] || '').replace(/^\\/, '')
    if (parent && !parent.includes('\\')) parent = `${namespace}\\${parent}`

    classes.set(`${namespace}\\${declared[1]}`.toLowerCase(), { actions, parent: parent.toLowerCase() })
}

const collectActions = (fqcn, seen = new Set()) => {
    if (seen.has(fqcn)) return new Set()
    seen.add(fqcn)
    const info = classes.get(fqcn)
    if (!info) return new Set()
    const result = new Set(info.actions)
    if (info.parent) {
        for (const action of collectActions(info.parent, seen)) result.add(action)
    }
    return result
}

const index = new Map()
for (const file of walk(CTRL_DIR, '.php')) {
    const source = fs.readFileSync(file, 'utf8')
    const namespace = /namespace\s+([^;]+);/.exec(source)?.[1]?.trim() || ''
    const className = /class\s+([A-Za-z0-9_]+)/.exec(source)?.[1]
    if (!className) continue

    const relative = path.relative(CTRL_DIR, file).replace(/\\/g, '/').replace(/\.php$/, '')
    const segments = relative.split('/')
    const base = segments.pop().replace(/Controller$/, '')
    const key = [...segments, base].join('.')

    index.set(normalizeKey(key), {
        file: relative + '.php',
        actions: collectActions(`${namespace}\\${className}`.toLowerCase()),
    })
}

// 3) 比对
const missingController = new Map()
const missingAction = new Map()
let matched = 0

for (const { url, file } of urls) {
    const segments = url.split('?')[0].replace(/^\//, '').split('/')
    if (segments.length < 2) continue

    const controllerKey = segments[0]
    const action = segments[1].toLowerCase()
    const hit = index.get(normalizeKey(controllerKey))

    if (!hit) {
        if (!missingController.has(controllerKey)) missingController.set(controllerKey, [])
        missingController.get(controllerKey).push(`${url}  (${file})`)
        continue
    }

    if (!hit.actions.has(action)) {
        const key = `${hit.file}#${action}`
        if (!missingAction.has(key)) missingAction.set(key, [])
        missingAction.get(key).push(`${url}  (${file})`)
        continue
    }

    matched++
}

console.log(`前端 URL: ${urls.length} | 匹配: ${matched} | 控制器缺失: ${missingController.size} | action 缺失: ${missingAction.size}`)

if (missingController.size) {
    console.log('\n== 后端不存在的控制器 ==')
    for (const [key, refs] of [...missingController].sort()) {
        console.log(`  ${key} <- ${[...new Set(refs)].join(' | ')}`)
    }
}

if (missingAction.size) {
    console.log('\n== 后端不存在的 action ==')
    for (const [key, refs] of [...missingAction].sort()) {
        console.log(`  ${key} <- ${[...new Set(refs)].join(' | ')}`)
    }
}

const failed = missingController.size + missingAction.size
if (STRICT && failed > 0) {
    console.error(`\n检查未通过：${failed} 处前端调用无法在后端解析`)
    process.exit(1)
}

if (failed === 0) console.log('\n检查通过：前端所有 URL 均可在后端解析')
