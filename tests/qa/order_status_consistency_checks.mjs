// QA 资产 · 订单状态跨端一致性验证
//
// 运行：node tests/qa/order_status_consistency_checks.mjs
//
// 背景：STATUS-001 要求「订单状态映射唯一事实源」。服务端 PHP 的
// Order::getOrderStatusDescAttr 与前端 shared/contracts/core.ts 的
// ORDER_STATUS_NAME_MAP 是两份独立字面量，一旦某一端改了文案或新增状态而另一端没跟上，
// 用户就会在小程序里看到空白或错位的状态名。
// 本脚本按源码字面量逐值比对，确保两端 status 数值与文案完全一致。
//
// 说明：Node 的 strip-only TS 模式不支持 enum 运行时反射，故改用文本解析（两端都按源码字面量比对），
// 这样不依赖任何构建产物，纯源码级校验。
//
// 退出码：0 完全一致；1 存在差异；2 解析失败（源码结构变了）。
import { readFileSync } from 'node:fs'

const phpUrl = new URL('../../server/app/common/model/order/Order.php', import.meta.url)
const tsUrl = new URL('../../shared/contracts/core.ts', import.meta.url)

const php = readFileSync(phpUrl, 'utf8')
const ts = readFileSync(tsUrl, 'utf8')

// ---------------------------------------------------------------- 服务端

// 1) 先解析常量表：const STATUS_X = 5; 或 const STATUS_X = self::STATUS_Y;
const constMap = {}
for (const m of php.matchAll(/const\s+STATUS_([A-Z_]+)\s*=\s*(?:self::STATUS_([A-Z_]+)|(\d+))\s*;/g)) {
    if (m[3] !== undefined) constMap[m[1]] = Number(m[3])
    else if (m[2] !== undefined && constMap[m[2]] !== undefined) constMap[m[1]] = constMap[m[2]]
}

// 2) 再解析 getOrderStatusDescAttr 里的 self::STATUS_X => '文案' 映射
const serverMap = {}
for (const m of php.matchAll(/self::STATUS_([A-Z_]+)\s*=>\s*'([^']+)'/g)) {
    const num = constMap[m[1]]
    if (num !== undefined && serverMap[num] === undefined) serverMap[num] = m[2]
}

// ---------------------------------------------------------------- 共享契约

const enumMatch = /export\s+enum\s+OrderStatus\s*\{([\s\S]*?)\}/.exec(ts)
const mapMatch = /export\s+const\s+ORDER_STATUS_NAME_MAP[^=]*=\s*\{([\s\S]*?)\n\}/.exec(ts)
if (!enumMatch || !mapMatch) {
    console.error('解析失败：shared/contracts/core.ts 中未找到 OrderStatus 枚举或 ORDER_STATUS_NAME_MAP')
    process.exit(2)
}

const tsEnum = {}
for (const m of enumMatch[1].matchAll(/([A-Z_]+)\s*=\s*(\d+)/g)) tsEnum[m[1]] = Number(m[2])

const sharedMap = {}
for (const m of mapMatch[1].matchAll(/\[OrderStatus\.([A-Z_]+)\]\s*:\s*'([^']+)'/g)) {
    if (tsEnum[m[1]] !== undefined) sharedMap[tsEnum[m[1]]] = m[2]
}

if (Object.keys(serverMap).length === 0) {
    console.error('解析失败：服务端 Order.php 未解析出任何状态文案，源码结构可能已变更')
    process.exit(2)
}
if (Object.keys(sharedMap).length === 0) {
    console.error('解析失败：shared 契约未解析出任何状态文案，源码结构可能已变更')
    process.exit(2)
}

// ---------------------------------------------------------------- 逐值比对

console.log('服务端 Order.php:', JSON.stringify(serverMap))
console.log('shared 契约     :', JSON.stringify(sharedMap))

let diff = 0
const keys = new Set([...Object.keys(serverMap), ...Object.keys(sharedMap)].map(Number))

console.log('\nstatus  服务端        共享契约      结果')
for (const key of [...keys].sort((a, b) => a - b)) {
    const same = serverMap[key] === sharedMap[key]
    if (!same) diff++
    console.log(
        `${String(key).padStart(6)}  ${String(serverMap[key] ?? '-').padEnd(12)}  `
        + `${String(sharedMap[key] ?? '-').padEnd(12)}  ${same ? 'OK' : 'DIFF'}`
    )
}

console.log(`\n差异数：${diff}`)
process.exit(diff === 0 ? 0 : 1)
