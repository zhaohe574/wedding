#!/usr/bin/env node
/**
 * B2：21 → 9 一级导航重排。
 *
 * 三种动作，全部**不改 key**（key 是身份，重排改身份会让角色授权与前端 getMenuPath 失效）：
 *   MOVE     整棵子树搬到新父级（可改名/排序）
 *   FLATTEN  子节点上提到新父级，自身退役（is_disable=1 / is_show=0，不删记录）
 *   MOVE_ONE 单个节点搬到新父级（用于「服务管理」里的人员相关拆到排期与团队）
 *
 * 用法：
 *   node server/database/menu/restructure.mjs --dry-run
 *   node server/database/menu/restructure.mjs --write
 */
import fs from 'node:fs'
import path from 'node:path'
import { execFileSync } from 'node:child_process'
import { fileURLToPath } from 'node:url'

const __dirname = path.dirname(fileURLToPath(import.meta.url))
const REPO = path.resolve(__dirname, '../../..')
const MANIFEST = path.join(__dirname, 'menu_manifest.php')
const WRITE = process.argv.includes('--write')

// ---------------------------------------------------------------- 目标结构
const ROOTS = [
    { key: 'nav.workbench', name: '工作台', icon: 'el-icon-Monitor', sort: 1000, path: 'workbench' },
    { key: 'nav.crm', name: '客户与跟进', icon: 'el-icon-User', sort: 900 },
    { key: 'nav.order', name: '订单与履约', icon: 'el-icon-List', sort: 800 },
    { key: 'nav.schedule', name: '排期与团队', icon: 'el-icon-Calendar', sort: 700 },
    { key: 'nav.financial', name: '财务', icon: 'el-icon-Money', sort: 600 },
    { key: 'nav.service', name: '服务与内容', icon: 'el-icon-Goods', sort: 500 },
    { key: 'nav.aftersale', name: '售后', icon: 'el-icon-Service', sort: 400 },
    { key: 'nav.quick-tools', name: '运营工具', icon: 'el-icon-Tools', sort: 300 },
    { key: 'nav.setting', name: '设置', icon: 'el-icon-Setting', sort: 200 }
]

/** 整棵子树搬迁：key → 新父级 */
const MOVE = {
    'page.workbench': { parent: 'nav.workbench', name: '工作台首页', sort: 100 },
    'nav.staff-center': { parent: 'nav.workbench', name: '我的工作', sort: 90, staffOnly: true },
    'nav.article': { parent: 'nav.service', name: '文章资讯', sort: 40 },
    'nav.material': { parent: 'nav.quick-tools', name: '素材管理', sort: 10 },
    'page.order.monthly_report': { parent: 'nav.financial', name: '单量月报', sort: 10 },
    // 设置下的分组保留一层，避免摊平成 20+ 个并列项
    'nav.permission': { parent: 'nav.setting', name: '权限与账号', sort: 90 },
    'nav.organization': { parent: 'nav.setting', name: '组织', sort: 80 },
    'nav.channel': { parent: 'nav.setting', name: '渠道', sort: 70 },
    'nav.decoration': { parent: 'nav.setting', name: '装修', sort: 60 },
    'nav.message': { parent: 'nav.setting', name: '通知', sort: 50 },
    'nav.app.message': { parent: 'nav.setting', name: '消息管理', sort: 40 }
}

/** 子项上提 + 自身退役：key → 新父级 */
const FLATTEN = {
    'nav.app': 'nav.setting',
    'nav.consumer': 'nav.crm',
    'nav.dynamic': 'nav.service',
    'nav.review': 'nav.service',
    'nav.staff_work': 'nav.service',
    'nav.couple-questionnaire': 'nav.service'
}

/** 单节点搬迁（把「服务管理」里的人员相关拆到「排期与团队」） */
const MOVE_ONE = {
    'page.staff.lists': { parent: 'nav.schedule', sort: 90 },
    'page.staff.lists.edit': { parent: 'nav.schedule', sort: 89 },
    'page.staff.certificate': { parent: 'nav.schedule', sort: 80 },
    'page.staff.tag_review': { parent: 'nav.schedule', sort: 70 },
    'page.staff.team': { parent: 'nav.schedule', sort: 60 },
    'page.service.tag': { parent: 'nav.schedule', name: '服务人员标签', sort: 50 }
}

// ---------------------------------------------------------------- 读清单
function loadManifest() {
    const code = `
$m = require ${JSON.stringify(MANIFEST.replace(/\\/g, '/'))};
echo json_encode($m, JSON_UNESCAPED_UNICODE);
`
    const out = execFileSync('php', ['-r', code], { cwd: REPO, encoding: 'utf8' })
    return JSON.parse(out.slice(out.indexOf('{')))
}

const manifest = loadManifest()

// ---------------------------------------------------------------- 扁平化
const entries = new Map() // key -> { node, parent }
;(function walk(nodes, parent) {
    for (const n of nodes) {
        entries.set(n.key, { node: n, parent })
        walk(n.children || [], n.key)
    }
})(manifest.menus, null)

const report = []
const parentOf = new Map([...entries].map(([k, e]) => [k, e.parent]))

// 1) 整棵搬迁
for (const [key, spec] of Object.entries(MOVE)) {
    if (!entries.has(key)) {
        report.push(`跳过（清单无此节点）：${key}`)
        continue
    }
    parentOf.set(key, spec.parent)
    const node = entries.get(key).node
    if (spec.name) node.name = spec.name
    if (spec.sort !== undefined) node.sort = spec.sort
    if (spec.staffOnly) node.staff_only = true
    report.push(`搬迁 ${key} → ${spec.parent}${spec.name ? '（改名 ' + spec.name + '）' : ''}`)
}

// 2) 子项上提 + 自身退役
for (const [key, newParent] of Object.entries(FLATTEN)) {
    const entry = entries.get(key)
    if (!entry) {
        report.push(`跳过（清单无此节点）：${key}`)
        continue
    }
    const kids = (entry.node.children || []).map((c) => c.key)
    for (const kid of kids) {
        parentOf.set(kid, newParent)
    }
    entry.node.retired = true
    entry.node.is_show = 0
    delete entry.node.children
    report.push(`上提 ${key} 的 ${kids.length} 个子项 → ${newParent}，自身退役`)
}

// 3) 单节点搬迁
for (const [key, spec] of Object.entries(MOVE_ONE)) {
    if (!entries.has(key)) {
        report.push(`跳过（清单无此节点）：${key}`)
        continue
    }
    parentOf.set(key, spec.parent)
    const node = entries.get(key).node
    if (spec.name) node.name = spec.name
    if (spec.sort !== undefined) node.sort = spec.sort
    report.push(`搬迁 ${key} → ${spec.parent}${spec.name ? '（改名 ' + spec.name + '）' : ''}`)
}

// ---------------------------------------------------------------- 重建树
const childrenOf = new Map()
for (const [key, p] of parentOf) {
    if (p === null) continue
    if (!childrenOf.has(p)) childrenOf.set(p, [])
    childrenOf.get(p).push(key)
}

const rootSpec = new Map(ROOTS.map((r) => [r.key, r]))
const sortOf = (key) => (rootSpec.has(key) ? rootSpec.get(key).sort : entries.get(key).node.sort || 0)

function build(key) {
    const spec = rootSpec.get(key)
    const base = entries.get(key)?.node
    const node = base
        ? { ...base }
        : {
              key,
              type: 'M',
              name: spec.name,
              icon: spec.icon,
              sort: spec.sort,
              path: spec.path,
              legacy_ids: []
          }
    if (spec) {
        node.name = spec.name
        node.icon = spec.icon
        node.sort = spec.sort
        if (spec.path) node.path = spec.path
    }
    const kids = (childrenOf.get(key) || []).slice().sort((a, b) => {
        const d = sortOf(b) - sortOf(a)
        return d !== 0 ? d : String(a).localeCompare(String(b))
    })
    if (kids.length) node.children = kids.map(build)
    else delete node.children
    return node
}

const menus = ROOTS.map((r) => build(r.key))

// 退役的一级仍要写进清单（落库后 is_disable=1），否则同步时它只是「清单里没有」，
// 库里的记录会变成无人管理的孤儿。
const retiredRoots = [...entries]
    .filter(([key, e]) => e.parent === null && e.node.retired && !rootSpec.has(key))
    .map(([key]) => build(key))
menus.push(...retiredRoots)

// 完整性校验：原节点一个都不能丢
const emitted = new Set()
;(function collect(nodes) {
    for (const n of nodes) {
        emitted.add(n.key)
        collect(n.children || [])
    }
})(menus)
const lost = [...entries.keys()].filter((k) => !emitted.has(k))
if (lost.length) {
    console.error('⚠️ 以下节点在新树里丢失：')
    lost.forEach((k) => console.error('   ' + k))
    process.exit(1)
}

// ---------------------------------------------------------------- 写回
const q = (s) => "'" + String(s).replace(/\\/g, '\\\\').replace(/'/g, "\\'") + "'"

function emit(node, indent) {
    const pad = '    '.repeat(indent)
    const lines = [`${pad}[`]
    lines.push(`${pad}    'key' => ${q(node.key)},`)
    lines.push(`${pad}    'type' => ${q(node.type)},`)
    lines.push(`${pad}    'name' => ${q(node.name)},`)
    if (node.icon) lines.push(`${pad}    'icon' => ${q(node.icon)},`)
    lines.push(`${pad}    'sort' => ${Number(node.sort) || 0},`)
    if (node.path) lines.push(`${pad}    'path' => ${q(node.path)},`)
    if (node.component) lines.push(`${pad}    'component' => ${q(node.component)},`)
    if (node.perms) lines.push(`${pad}    'perms' => ${q(node.perms)},`)
    if (node.selected) lines.push(`${pad}    'selected' => ${q(node.selected)},`)
    if (node.params) lines.push(`${pad}    'params' => ${q(node.params)},`)
    if (Number(node.is_cache)) lines.push(`${pad}    'is_cache' => 1,`)
    if (Number(node.is_show) === 0) lines.push(`${pad}    'is_show' => 0,`)
    if (node.retired) lines.push(`${pad}    'retired' => true,`)
    if (node.staff_only) lines.push(`${pad}    'staff_only' => true,`)
    const ids = (node.legacy_ids || []).filter(Boolean)
    lines.push(`${pad}    'legacy_ids' => [${ids.join(', ')}],`)
    if (node.children?.length) {
        lines.push(`${pad}    'children' => [`)
        for (const c of node.children) lines.push(emit(c, indent + 2))
        lines.push(`${pad}    ],`)
    }
    lines.push(`${pad}],`)
    return lines.join('\n')
}

const policyBlock = Object.entries(manifest.policy || {})
    .map(([bucket, keys]) => {
        const items = (keys || []).map((k) => `            ${q(k)},`).join('\n')
        return `        '${bucket}' => [\n${items}\n        ],`
    })
    .join('\n')

const out = `<?php
// +----------------------------------------------------------------------
// | 后台菜单单一事实源
// +----------------------------------------------------------------------
// | ⚠️ 手工维护。改动后必须执行：
// |   php think admin_menu:sync --dry-run
// |   php think admin_menu:sync --apply --batch=xxx
// |   php think admin_menu:check --strict
// |
// | 结构：9 个一级导航，按「运营任务」组织，不按控制器命名。
// |   key          稳定业务键；重排只改父子，不改 key
// |   type         M=目录 / C=菜单（A 型按钮权限不在此清单）
// |   path         仅本段路径（前端由 vue-router 嵌套拼接）
// |   component    必须与 admin/src/views 下相对路径完全一致（模糊匹配会白屏）
// |   perms        必须等于后端控制器的「点分路径/方法」
// |   legacy_ids   库内既有行 id；空数组=同步时新建
// |   retired      true = 保留记录但 is_disable=1 / is_show=0（永不物理删除）
// |   staff_only   true = 仅绑定服务人员的账号可见
// +----------------------------------------------------------------------

return [
    'version' => '${manifest.version || '2026-11-08.0'}',

    'policy' => [
${policyBlock}
    ],

    'menus' => [
${menus.map((m) => emit(m, 2)).join('\n')}
    ]
];
`

if (!WRITE) {
    console.log('== 搬迁计划 ==')
    report.forEach((r) => console.log('  ' + r))
    console.log(`\n一级导航（${menus.length}）：`)
    menus.forEach((m) => console.log(`  ${m.name.padEnd(6)} 子节点 ${(m.children || []).length} 个`))
    console.log('\n[DRY-RUN] 未写回，加 --write 生效')
} else {
    fs.writeFileSync(MANIFEST, out, 'utf8')
    console.log('已写回 ' + path.relative(REPO, MANIFEST))
    console.log(`搬迁 ${report.length} 条；一级导航 ${menus.length} 个`)
}
