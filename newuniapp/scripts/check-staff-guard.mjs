#!/usr/bin/env node
/**
 * 服务人员分包（pkg-staff）页面级角色守卫静态门禁
 *
 * 规则：
 *   1. dashboard/dashboard.vue（管理员经营驾驶舱）：须调 getDashboardAccess 并处理 phase === 'denied'。
 *   2. pkg-staff/pages/ 下其余所有页面：须接入 useStaffGuard + <staff-guard，严禁未鉴权直接渲染或发业务请求。
 *   3. scripts/redesign-zero-check.mjs 不得残留目录级 pkg-staff 跳过豁免。
 */
import { existsSync, readdirSync, readFileSync } from 'node:fs'
import { dirname, join, relative, resolve } from 'node:path'
import { fileURLToPath } from 'node:url'

const __dirname = dirname(fileURLToPath(import.meta.url))
const ROOT = resolve(__dirname, '..')
const PAGES_DIR = join(ROOT, 'src/packages/pkg-staff/pages')
const ZERO_CHECK = join(ROOT, 'scripts/redesign-zero-check.mjs')

function collectVueFiles(dir, out = []) {
    for (const entry of readdirSync(dir, { withFileTypes: true })) {
        const full = join(dir, entry.name)
        if (entry.isDirectory()) collectVueFiles(full, out)
        else if (entry.name.endsWith('.vue')) out.push(full)
    }
    return out
}

const errors = []
if (!existsSync(PAGES_DIR)) {
    console.error(`未找到目录：${PAGES_DIR}`)
    process.exit(1)
}

const files = collectVueFiles(PAGES_DIR).sort()
if (files.length < 17) {
    errors.push(`pkg-staff 页面数异常（期望 >= 17，实际 ${files.length}）`)
}

let staffPagesCount = 0
for (const file of files) {
    const rel = relative(ROOT, file).split('\\').join('/')
    const src = readFileSync(file, 'utf8')
    if (rel.endsWith('/dashboard/dashboard.vue')) {
        if (!src.includes('getDashboardAccess') || !src.includes("phase === 'denied'")) {
            errors.push(`${rel}: 经营驾驶舱缺少 getDashboardAccess 或 phase === 'denied' 拒绝态`)
        }
        continue
    }
    staffPagesCount += 1
    if (!src.includes('useStaffGuard')) {
        errors.push(`${rel}: 缺少 useStaffGuard 导入/调用`)
    }
    if (!/\buseStaffGuard\s*\(/.test(src)) {
        errors.push(`${rel}: 未调用 useStaffGuard()`)
    }
    if (!/<staff-guard\b/.test(src)) {
        errors.push(`${rel}: 模板未挂载 <staff-guard />`)
    }
}

if (existsSync(ZERO_CHECK)) {
    const zeroSrc = readFileSync(ZERO_CHECK, 'utf8')
    if (/pkg-staff\$\{path\.sep\}/.test(zeroSrc) || /includes\([^)]*pkg-staff[^)]*\)/.test(zeroSrc)) {
        errors.push('scripts/redesign-zero-check.mjs 仍包含目录级 pkg-staff 豁免')
    }
}

if (errors.length) {
    console.error('pkg-staff 角色守卫门禁未通过：')
    for (const e of errors) console.error(' - ' + e)
    process.exit(1)
}

console.log(`pkg-staff 角色守卫校验通过：${staffPagesCount} 个服务人员页 + 1 个经营驾驶舱页`)
