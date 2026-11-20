#!/usr/bin/env node
/**
 * 后台权限登记检查：前端 admin/src/api 调用的接口，必须已在
 * la_system_menu 的 perms 中登记，否则 AuthMiddleware 会对非 root 管理员
 * 一律返回「权限未登记，无法访问或操作」。
 *
 * 权限来源优先级：
 *   1. --db  读取运行库（server/runtime/perms.json，由 --export 生成）
 *   2. 默认  读取 DDL 基线（安装 SQL + 权限迁移），反映「全新部署」的权限集
 *
 * 用法：
 *   node scripts/audit/check-admin-permissions.mjs               # 按 DDL 基线检查
 *   node scripts/audit/check-admin-permissions.mjs --db          # 按运行库检查
 *   node scripts/audit/check-admin-permissions.mjs --export      # 先导出运行库再检查
 *   node scripts/audit/check-admin-permissions.mjs --strict      # CI 模式
 */

import fs from 'node:fs'
import path from 'node:path'
import { execFileSync } from 'node:child_process'
import { fileURLToPath } from 'node:url'

const ROOT = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '../..')
const ADMIN_API = path.join(ROOT, 'admin/src/api')
const CTRL_DIR = path.join(ROOT, 'server/app/adminapi/controller')
const PERMS_JSON = path.join(ROOT, 'server/runtime/perms.json')
const SQL_DIRS = [
    path.join(ROOT, 'server/public/install/db'),
    path.join(ROOT, 'server/database/migrations'),
    path.join(ROOT, 'server/database/schema'),
]

const ARGS = process.argv.slice(2)
const STRICT = ARGS.includes('--strict')
const USE_DB = ARGS.includes('--db') || ARGS.includes('--export')

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

const camel = (value) => {
    if (!/[_-]/.test(value)) return value
    const studly = value.replace(/[_-]+(.)/g, (_, c) => c.toUpperCase())
    return studly.charAt(0).toLowerCase() + studly.slice(1)
}

// ---------------------------------------------------------------- 前端调用
const calledUrls = new Set()
for (const file of walk(ADMIN_API, '.ts')) {
    const source = fs.readFileSync(file, 'utf8')
    for (const match of source.matchAll(/url:\s*['"`]([^'"`]+)['"`]/g)) {
        const segments = match[1].split('?')[0].replace(/^\//, '').split('/')
        if (segments.length >= 2) calledUrls.add(`${segments[0]}/${segments[1]}`.toLowerCase())
    }
}

// ---------------------------------------------------------------- 后端 action
const classes = new Map()
for (const file of walk(CTRL_DIR, '.php')) {
    const source = fs.readFileSync(file, 'utf8')
    const namespace = /namespace\s+([^;]+);/.exec(source)?.[1]?.trim() || ''
    const declared = /class\s+([A-Za-z0-9_]+)\s*(?:extends\s+([A-Za-z0-9_\\]+))?/.exec(source)
    if (!declared) continue

    const actions = new Set()
    for (const match of source.matchAll(/public\s+function\s+([A-Za-z0-9_]+)\s*\(/g)) {
        actions.add(match[1])
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

const endpoints = new Map()
for (const file of walk(CTRL_DIR, '.php')) {
    const source = fs.readFileSync(file, 'utf8')
    const namespace = /namespace\s+([^;]+);/.exec(source)?.[1]?.trim() || ''
    const className = /class\s+([A-Za-z0-9_]+)/.exec(source)?.[1]
    if (!className) continue

    const relative = path.relative(CTRL_DIR, file).replace(/\\/g, '/').replace(/\.php$/, '')
    const segments = relative.split('/')
    const base = segments.pop().replace(/Controller$/, '')
    const key = [...segments, base].join('.')

    for (const action of collectActions(`${namespace}\\${className}`.toLowerCase())) {
        if (['__construct', 'initialize', 'isnotneedlogin'].includes(action.toLowerCase())) continue
        endpoints.set(`${key}/${action}`.toLowerCase(), `${key}/${action}`)
    }
}

// 免登录接口不参与权限校验（LoginMiddleware 放行，AuthMiddleware 直接 return）
const publicActions = new Set()
for (const file of walk(CTRL_DIR, '.php')) {
    const source = fs.readFileSync(file, 'utf8')
    const declared = /public\s+array\s+\$notNeedLogin\s*=\s*\[([^\]]*)\]/.exec(source)
    if (!declared) continue

    const relative = path.relative(CTRL_DIR, file).replace(/\\/g, '/').replace(/\.php$/, '')
    const segments = relative.split('/')
    const base = segments.pop().replace(/Controller$/, '')
    const key = [...segments, base].join('.')

    for (const match of declared[1].matchAll(/'([^']+)'/g)) {
        publicActions.add(`${key}/${match[1]}`.toLowerCase())
    }
}

// ---------------------------------------------------------------- 权限来源
const PERM_PATTERN = /^[a-z][a-z_0-9]*(?:\.[A-Za-z][A-Za-z_0-9]*)*\/[A-Za-z][A-Za-z_0-9]*$/

const exportFromDb = () => {
    execFileSync(
        'php',
        [
            '-r',
            "require 'vendor/autoload.php';$a=new think\\App(__DIR__.DIRECTORY_SEPARATOR);$a->initialize();" +
                "$p=think\\facade\\Db::name('system_menu')->where('perms','<>','')->column('perms');" +
                "file_put_contents(__DIR__.'/runtime/perms.json', json_encode(array_values(array_unique($p))));",
        ],
        { cwd: path.join(ROOT, 'server'), stdio: 'inherit' }
    )
}

const readPerms = () => {
    if (USE_DB) {
        if (ARGS.includes('--export') || !fs.existsSync(PERMS_JSON)) exportFromDb()
        return new Set(JSON.parse(fs.readFileSync(PERMS_JSON, 'utf8')))
    }

    const perms = new Set()
    for (const dir of SQL_DIRS) {
        for (const file of walk(dir, '.sql')) {
            const sql = fs.readFileSync(file, 'utf8')
            for (const match of sql.matchAll(/'([^']{3,80})'/g)) {
                if (PERM_PATTERN.test(match[1])) perms.add(match[1])
            }
        }
    }
    return perms
}

const rawPerms = readPerms()

// ---------------------------------------------------------------- 别名与依赖展开（与 AuthLogic 对齐）
const authSource = fs.readFileSync(path.join(ROOT, 'server/app/adminapi/logic/auth/AuthLogic.php'), 'utf8')
const aliasBlock = authSource.slice(
    authSource.indexOf('function permissionAliasMap'),
    authSource.indexOf('function expandPermissionAliases')
)
const prefixAliases = [...aliasBlock.matchAll(/'([^']+)'\s*=>\s*'([^']+)'/g)].map((m) => [m[1], m[2]])

const dependencies = new Map()
const collectDependencies = (source) => {
    for (const match of source.matchAll(/'([^']+)'\s*=>\s*\[([\s\S]*?)\]/g)) {
        const list = [...match[2].matchAll(/'([^']+)'/g)].map((x) => x[1])
        if (!dependencies.has(match[1])) dependencies.set(match[1], [])
        dependencies.get(match[1]).push(...list)
    }
}
collectDependencies(fs.readFileSync(path.join(ROOT, 'server/app/common/service/AdminPermissionService.php'), 'utf8'))
collectDependencies(
    authSource.slice(
        authSource.indexOf('function exactPermissionAliasMap'),
        authSource.indexOf('function permissionAliasMap')
    )
)

const expand = (perms) => {
    const set = new Set(perms)
    const applyPrefixes = () => {
        for (const item of [...set]) {
            for (const [from, to] of prefixAliases) {
                if (item.startsWith(from)) set.add(to + item.slice(from.length))
                if (item.startsWith(to)) set.add(from + item.slice(to.length))
            }
        }
    }
    applyPrefixes()
    for (const item of [...set]) {
        for (const dep of dependencies.get(item) ?? []) set.add(dep)
    }
    applyPrefixes()
    return set
}

const registered = new Set([...expand([...rawPerms])].map((item) => camel(item).toLowerCase()))

// 中间件硬编码放行
const middlewareSource = fs.readFileSync(
    path.join(ROOT, 'server/app/adminapi/http/middleware/AuthMiddleware.php'),
    'utf8'
)
for (const match of middlewareSource.matchAll(/'([a-z_]+(?:\.[A-Za-z_]+)?\/[A-Za-z_]+)'/g)) {
    registered.add(match[1].toLowerCase())
}

// ---------------------------------------------------------------- 比对
const unreachable = [...endpoints]
    .filter(([key]) => !registered.has(key) && !publicActions.has(key))
    .map(([, raw]) => raw)
    .sort()

const calledButUnregistered = unreachable.filter((uri) => calledUrls.has(uri.toLowerCase()))

console.log(
    `权限来源: ${USE_DB ? '运行库' : 'DDL 基线'} | 后端 action: ${endpoints.size} | 权限点: ${rawPerms.size} | 展开后: ${registered.size}`
)
console.log(`前端调用但未登记: ${calledButUnregistered.length}`)
for (const uri of calledButUnregistered) console.log(`  ${uri}`)

console.log(`\n后端存在但未登记（含未被前端调用的历史接口）: ${unreachable.length}`)

if (STRICT && calledButUnregistered.length > 0) {
    console.error('\n检查未通过：存在前端已调用但未登记权限的接口')
    process.exit(1)
}

if (calledButUnregistered.length === 0) {
    console.log('\n检查通过：前端调用的后台接口均已登记权限')
}
