#!/usr/bin/env node
/**
 * 构建产物 app.json 引用完整性校验
 * ---------------------------------------------------------------
 * 微信开发者工具读的是 dist/build/mp-weixin/app.json。
 * 这里的错误在 `uni build` 阶段**不会报**，只有打开开发者工具才暴露：
 *   - preloadRule.packages 填了目录名而不是 subPackages[].root 全路径 → 「未找到」
 *   - pages 里登记了页面但产物缺 .wxml/.js/.json/.wxss
 *   - tabBar 的 iconPath 指向不存在的图片
 *   - tabBar.pagePath 不在 pages 中
 *
 * 必须在 build 之后执行：
 *   node scripts/validate-dist.mjs
 */

import { existsSync, readFileSync } from 'node:fs'
import { dirname, join, resolve } from 'node:path'
import { fileURLToPath } from 'node:url'

const __dirname = dirname(fileURLToPath(import.meta.url))
const ROOT = resolve(__dirname, '..')
const DIST = join(ROOT, 'dist/build/mp-weixin')

if (!existsSync(DIST)) {
    console.error(`找不到构建产物：${DIST}\n请先执行 npm run build:mp-weixin`)
    process.exit(1)
}

const appJsonPath = join(DIST, 'app.json')
if (!existsSync(appJsonPath)) {
    console.error('找不到 app.json')
    process.exit(1)
}

const app = JSON.parse(readFileSync(appJsonPath, 'utf8'))
const errors = []
const PAGE_EXTS = ['.js', '.json', '.wxml', '.wxss']

const exists = (rel) => existsSync(join(DIST, rel))

// 1. 主包页面四件套齐全
for (const page of app.pages || []) {
    for (const ext of PAGE_EXTS) {
        if (!exists(page + ext)) errors.push(`主包缺文件：${page}${ext}`)
    }
}

// 2. 分包页面
const subRoots = new Set()
for (const sub of app.subPackages || []) {
    subRoots.add(sub.root)
    for (const page of sub.pages || []) {
        const rel = `${sub.root}/${page}`
        if (!exists(rel + '.wxml')) errors.push(`分包缺页面：${rel}`)
    }
}

// 3. tabBar
const pageSet = new Set(app.pages || [])
for (const tab of app.tabBar?.list || []) {
    if (!pageSet.has(tab.pagePath)) {
        errors.push(`tabBar 页面未在 pages 中：${tab.pagePath}`)
    }
    for (const key of ['iconPath', 'selectedIconPath']) {
        const value = tab[key]
        if (value && !exists(value)) errors.push(`tabBar 缺图标：${value}`)
    }
}

// 4. preloadRule —— 最容易踩的一个
for (const [pagePath, rule] of Object.entries(app.preloadRule || {})) {
    if (!pageSet.has(pagePath)) {
        errors.push(`preloadRule 指向未注册页面：${pagePath}`)
    }
    for (const pkg of rule?.packages || []) {
        if (pkg === 'all') continue
        if (!subRoots.has(pkg)) {
            errors.push(
                `preloadRule["${pagePath}"].packages 含无效分包 "${pkg}"，` +
                    `必须为 subPackages[].root 全路径。可选：${[...subRoots].join(' / ') || '（无分包）'}`
            )
        }
    }
}

if (errors.length) {
    console.error('产物 app.json 校验未通过：')
    for (const e of errors) console.error(' - ' + e)
    process.exit(1)
}

console.log(
    `产物 app.json 校验通过：主包 ${(app.pages || []).length} 页 / 分包 ${(app.subPackages || []).length} 个 / ` +
        `preloadRule ${Object.keys(app.preloadRule || {}).length} 条`
)
