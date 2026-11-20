#!/usr/bin/env node
/**
 * 菜单清单引导器。
 * ---------------------------------------------------------------
 * 输入：
 *   server/public/install/db/like.sql        安装基线（决定树的「形状」与稳定 key）
 *   server/database/menu/snapshots/*.json    当前库导出（决定每个节点的「现值」）
 *
 * 输出：
 *   server/database/menu/menu_manifest.php   唯一事实源（M / C 节点）
 *   server/database/menu/legacy_id_map.php   库内 id → menu_key
 *
 * 为什么要两份输入：
 *   like.sql 会落后于线上库（本库 557 条 vs 基线 483 条，且 440 段 id 整体偏移）。
 *   只用基线生成清单 → dry-run 会冒出十几条「把线上值改回旧值」的假差异。
 *   所以：结构以基线为准，字段值以线上为准，B0 才能做到真正的零行为变化。
 *
 * 为什么只导 M / C：
 *   A（按钮权限）由 migrations 的 admin_permissions.sql 维护，数量 400+ 且持续增长，
 *   放进清单只会淹没有效信息。菜单清单负责「导航结构」，不是权限点全集。
 *
 * 用法：
 *   node server/database/menu/bootstrap.mjs --snapshot=server/database/menu/snapshots/prod-20261108.json
 *   node server/database/menu/bootstrap.mjs --check
 */
import fs from 'node:fs'
import path from 'node:path'
import { fileURLToPath } from 'node:url'

const __dirname = path.dirname(fileURLToPath(import.meta.url))
const REPO = path.resolve(__dirname, '../../..')
const SQL = path.join(REPO, 'server/public/install/db/like.sql')
const MANIFEST = path.join(__dirname, 'menu_manifest.php')
const ID_MAP = path.join(__dirname, 'legacy_id_map.php')

const CHECK = process.argv.includes('--check')
const snapshotArg = process.argv.find((a) => a.startsWith('--snapshot='))
const SNAPSHOT = snapshotArg ? path.resolve(REPO, snapshotArg.split('=')[1]) : null

/** 解析 like.sql 中所有 la_system_menu 的 INSERT 行 */
function parseMenus() {
    const txt = fs.readFileSync(SQL, 'utf8')
    const re = /INSERT INTO `la_system_menu`[^)]*\) VALUES \((.*?)\);/g
    const rows = []
    let m
    while ((m = re.exec(txt)) !== null) {
        const body = m[1]
        const parts = []
        let cur = ''
        let quoted = false
        for (let i = 0; i < body.length; i += 1) {
            const c = body[i]
            if (c === "'" && body[i - 1] !== '\\') {
                quoted = !quoted
                cur += c
                continue
            }
            if (c === ',' && !quoted) {
                parts.push(cur.trim())
                cur = ''
                continue
            }
            cur += c
        }
        parts.push(cur.trim())
        const v = parts.map((p) =>
            p === 'NULL' ? null : p.replace(/^'/, '').replace(/'$/, '').replace(/\\'/g, "'")
        )
        rows.push({
            id: String(v[0]),
            pid: String(v[1]),
            type: v[2],
            name: v[3],
            icon: v[4] || '',
            sort: Number(v[5]) || 0,
            perms: v[6] || '',
            paths: v[7] || '',
            component: v[8] || '',
            selected: v[9] || '',
            params: v[10] || '',
            is_cache: Number(v[11]) || 0,
            is_show: Number(v[12]),
            is_disable: Number(v[13]) || 0
        })
    }
    return rows
}

/** component → 稳定 key：staff/lists/index → page.staff.lists */
function componentKey(component) {
    return 'page.' + String(component).replace(/\/index$/, '').replace(/\//g, '.')
}

/**
 * 基线里有、但线上已被替换或移除的节点：**不写进清单**。
 * 这些是「安装基线落后于线上」的产物，直接沿用会在同步时凭空多出一条菜单。
 * B4 做入口归并时再统一决定去留（现在保留原页面文件，不删代码）。
 */
const STALE_BASELINE = new Set([
    // 线上已迁到「消息中心 › 订阅消息」，且组件换成了 subscribe/template/index
    'message/mnp_notification/index'
])

const isStale = (node) => node.type === 'C' && STALE_BASELINE.has(node.component)

const baseRows = parseMenus()
const baseById = new Map(baseRows.map((r) => [r.id, r]))
const baseChildren = new Map()
for (const r of baseRows) {
    if (!baseChildren.has(r.pid)) baseChildren.set(r.pid, [])
    baseChildren.get(r.pid).push(r)
}

// ---------- 线上快照索引 ----------
let dbRows = []
if (SNAPSHOT) {
    if (!fs.existsSync(SNAPSHOT)) throw new Error('快照不存在：' + SNAPSHOT)
    dbRows = JSON.parse(fs.readFileSync(SNAPSHOT, 'utf8')).menus || []
}
const dbById = new Map(dbRows.map((r) => [String(r.id), r]))
const dbChildren = new Map()
for (const r of dbRows) {
    const pid = String(r.pid)
    if (!dbChildren.has(pid)) dbChildren.set(pid, [])
    dbChildren.get(pid).push(r)
}

// ---------- key 分配 ----------
const keyById = new Map()
const usedKeys = new Set()

/** M 节点 key：默认 nav.<paths>；重名时逐级加父级前缀消歧（nav.app.message） */
function uniqueDirKey(node) {
    const chain = [node.paths]
    // 从父级开始往上找，逐级补前缀消歧（nav.message 归一级的「消息中心」，
    // 应用管理下的「消息管理」得到 nav.app.message）
    let cursor = baseById.get(node.pid)
    for (;;) {
        const candidate = `nav.${chain.join('.')}`
        if (!usedKeys.has(candidate)) return candidate
        if (!cursor) return `nav.${chain.join('.')}.${node.id}`
        chain.unshift(cursor.paths || cursor.name)
        cursor = baseById.get(cursor.pid)
    }
}

function assignKey(node) {
    if (isStale(node)) return null
    let key = null
    if (node.type === 'M') key = uniqueDirKey(node)
    else if (node.type === 'C') key = componentKey(node.component)
    if (key) {
        if (usedKeys.has(key)) throw new Error(`key 冲突：${key}（id=${node.id} ${node.name}）`)
        usedKeys.add(key)
        keyById.set(node.id, key)
    }
    return key
}

const ROOTS = (baseChildren.get('0') || []).filter((n) => n.type === 'M' || n.type === 'C')
// 两遍：先占住一级导航的规范 key，再处理下级
for (const root of ROOTS) assignKey(root)
;(function walkAll(nodes) {
    for (const n of nodes) {
        assignKey(n)
        walkAll(baseChildren.get(n.id) || [])
    }
})(ROOTS.flatMap((r) => baseChildren.get(r.id) || []))

// ---------- 用线上快照校正「现值」 ----------
/**
 * 逐节点找线上对应行：
 *   父节点的线上 id 已知 → 在同父下的兄弟里按 paths（M）或 component（C）匹配。
 * 匹配不上说明该节点在库里不存在（需要新建），保留基线值。
 */
const matchedDbId = new Map() // key -> db id
let adopted = 0

function findDbRow(node, parentDbId) {
    const siblings = dbChildren.get(String(parentDbId)) || []
    if (node.type === 'C') {
        return siblings.find((r) => r.type === 'C' && r.component === node.component) || null
    }
    return siblings.find((r) => r.type === 'M' && r.paths === node.paths) || null
}

function resolve(node, parentDbId) {
    if (isStale(node)) return
    const key = keyById.get(node.id)
    if (!key) return
    const row = findDbRow(node, parentDbId)
    if (row) {
        matchedDbId.set(key, String(row.id))
        adopted += 1
    }
    for (const child of baseChildren.get(node.id) || []) {
        if (child.type !== 'M' && child.type !== 'C') continue
        resolve(child, row ? String(row.id) : -1)
    }
}
for (const root of ROOTS) resolve(root, 0)

/** 取节点最终值：优先线上行，其次基线行 */
function valueOf(node, field, fallback) {
    const key = keyById.get(node.id)
    const dbId = matchedDbId.get(key)
    const row = dbId ? dbById.get(dbId) : null
    const v = row ? row[field] : node[field]
    return v === undefined || v === null ? fallback : v
}

// ---------- 生成 PHP ----------
const q = (s) => "'" + String(s).replace(/\\/g, '\\\\').replace(/'/g, "\\'") + "'"

function emitNode(node, indent) {
    const pad = '    '.repeat(indent)
    if (isStale(node)) return ''
    const key = keyById.get(node.id)
    if (!key) return ''

    const name = valueOf(node, 'name', '')
    const icon = valueOf(node, 'icon', '')
    const sort = Number(valueOf(node, 'sort', 0))
    const paths = valueOf(node, 'paths', '')
    const component = valueOf(node, 'component', '')
    const perms = valueOf(node, 'perms', '')
    const selected = valueOf(node, 'selected', '')
    const params = valueOf(node, 'params', '')
    const isCache = Number(valueOf(node, 'is_cache', 0))
    const isShow = Number(valueOf(node, 'is_show', 1))
    const isDisable = Number(valueOf(node, 'is_disable', 0))
    const dbId = matchedDbId.get(key)

    const lines = []
    lines.push(`${pad}[`)
    lines.push(`${pad}    'key' => ${q(key)},`)
    lines.push(`${pad}    'type' => ${q(node.type)},`)
    lines.push(`${pad}    'name' => ${q(name)},`)
    if (icon) lines.push(`${pad}    'icon' => ${q(icon)},`)
    lines.push(`${pad}    'sort' => ${sort},`)
    if (paths) lines.push(`${pad}    'path' => ${q(paths)},`)
    if (component) lines.push(`${pad}    'component' => ${q(component)},`)
    if (perms) lines.push(`${pad}    'perms' => ${q(perms)},`)
    if (selected) lines.push(`${pad}    'selected' => ${q(selected)},`)
    if (params) lines.push(`${pad}    'params' => ${q(params)},`)
    if (isCache) lines.push(`${pad}    'is_cache' => 1,`)
    if (isShow === 0) lines.push(`${pad}    'is_show' => 0,`)
    if (isDisable) lines.push(`${pad}    'retired' => true,`)
    lines.push(`${pad}    'legacy_ids' => [${dbId ? dbId : ''}],`)

    const kids = (baseChildren.get(node.id) || []).filter(
        (c) => (c.type === 'M' || c.type === 'C') && !isStale(c)
    )
    if (kids.length) {
        lines.push(`${pad}    'children' => [`)
        for (const kid of kids) {
            const s = emitNode(kid, indent + 2)
            if (s) lines.push(s)
        }
        lines.push(`${pad}    ],`)
    }
    lines.push(`${pad}],`)
    return lines.join('\n')
}

const body = ROOTS.map((r) => emitNode(r, 2)).filter(Boolean).join('\n')

const POLICY_PUBLIC = [
    'nav.quick-tools',
    'page.tools.quick.human_split',
    'page.tools.quick.compress',
    'page.tools.quick.convert'
]

const manifest = `<?php
// +----------------------------------------------------------------------
// | 后台菜单单一事实源
// +----------------------------------------------------------------------
// | ⚠️ 由 bootstrap.mjs 引导生成，之后**手工维护**。改动后必须执行：
// |   php think admin_menu:sync --dry-run              # 看差异
// |   php think admin_menu:sync --apply --batch=xxx   # 落库（自动快照，可回滚）
// |   php think admin_menu:check --strict             # CI 校验
// |
// | 约定：
// |   key            稳定业务键，唯一且永不复用；落库到 la_system_menu.menu_key
// |   type           M=目录 / C=菜单（A 型按钮权限不在此清单，由 migrations 维护）
// |   path           仅本段路径（与 DB 的 paths 一致，前端由 vue-router 嵌套拼接）
// |   component      必须与 admin/src/views 下的相对路径一致，且全库唯一命中
// |   perms          必须等于后端控制器的「点分路径/方法」
// |   legacy_ids     库内既有行 id（认领用）；空数组=清单有而库里没有，同步时新建
// |   retired        true = 保留记录但 is_disable=1 / is_show=0（永不物理删除）
// |   route_aliases  需要继续被 getRoutePath() 命中的历史字符串（B2 收敛用）
// +----------------------------------------------------------------------

return [
    'version' => '2026-11-08.0',

    /**
     * 访问策略（替代 MenuLogic / AuthLogic 里的魔法数字）
     *   public        所有后台账号都可见，不依赖角色授权
     *   staff_only    仅绑定服务人员的账号可见
     *   leader_only   仅服务人员队长可见
     *   never_ship    永不进侧边栏（dev 工具等）
     */
    'policy' => [
        'public' => [
${POLICY_PUBLIC.map((k) => `            ${q(k)},`).join('\n')}
        ],
        'staff_only' => [],
        'leader_only' => [],
        'never_ship' => []
    ],

    'menus' => [
${body}
    ]
];
`

const idMapLines = [...matchedDbId.entries()]
    .sort((a, b) => Number(a[1]) - Number(b[1]))
    .map(([key, id]) => `    ${q(id)} => ${q(key)},`)

const idMap = `<?php
// +----------------------------------------------------------------------
// | la_system_menu.id → menu_key 对照表（由 bootstrap.mjs 生成）
// +----------------------------------------------------------------------
// | 作用仅两个：① 排查「某条数据对应哪个 key」② 首次落库时认领既有行。
// | ⚠️ id 不是身份：本库 440 段曾出现整体偏移（443 既可能是新人问卷也可能是按钮），
// | 所以任何按 id 的自动认领都必须再校验 component / paths，见 AdminMenuSync。
// +----------------------------------------------------------------------

return [
${idMapLines.join('\n')}
];
`

if (CHECK) {
    if (!fs.existsSync(MANIFEST) || !fs.existsSync(ID_MAP)) {
        console.error('清单文件缺失，请先运行 bootstrap.mjs')
        process.exit(1)
    }
    const current = fs.readFileSync(MANIFEST, 'utf8')
    const keyCount = (current.match(/'key' =>/g) || []).length
    console.log(`基线 M/C 节点 ${keyById.size} 个（根 ${ROOTS.length} 个）`)
    console.log(`清单内 key ${keyCount} 个`)
    if (keyCount !== keyById.size) {
        console.error('清单节点数与基线不一致 —— 要么基线变了，要么清单被手工改过。')
        process.exit(1)
    }
    console.log('清单与基线节点数一致。')
    process.exit(0)
}

fs.writeFileSync(MANIFEST, manifest, 'utf8')
fs.writeFileSync(ID_MAP, idMap, 'utf8')
console.log(`已生成 ${path.relative(REPO, MANIFEST)}`)
console.log(`已生成 ${path.relative(REPO, ID_MAP)}`)
console.log(`M/C 节点 ${keyById.size} 个（根 ${ROOTS.length} 个），其中 ${adopted} 个在线上库有对应行`)
if (!SNAPSHOT) {
    console.log('⚠️ 未传 --snapshot，清单值全部取自安装基线（可能把线上新值改回旧值）')
}
