#!/usr/bin/env node
/**
 * 状态契约导出器（正式版）
 * ---------------------------------------------------------------
 * 单一事实源 = 后端 PHP 模型里的 `const` 常量。
 * 扫描 → 生成 shared/contracts/generated/status.ts，供 admin / newuniapp / pc 三端共用。
 *
 * 用法：
 *   node scripts/contracts/export-status.mjs           # 生成
 *   node scripts/contracts/export-status.mjs --check    # CI 校验漂移，exit 1
 *   node scripts/contracts/export-status.mjs --report    # 生成物 vs PHP 的枚举级差异 + core.ts 再导出完整性
 *   node scripts/contracts/export-status.mjs --json      # 机器可读
 */

import { readFileSync, writeFileSync, mkdirSync, existsSync } from 'node:fs';
import { dirname, resolve, join } from 'node:path';
import { fileURLToPath } from 'node:url';

const __dirname = dirname(fileURLToPath(import.meta.url));
const REPO_ROOT = resolve(__dirname, '../..');
const MODEL_ROOT = join(REPO_ROOT, 'server/app/common/model');
const CORE_CONTRACT = join(REPO_ROOT, 'shared/contracts/core.ts');
const OUT_DIR = join(REPO_ROOT, 'shared/contracts/generated');
const OUT_FILE = join(OUT_DIR, 'status.ts');

/**
 * 契约规格：文件 + 常量前缀 -> TS 枚举名 + 文案映射名
 * 只收录「客户端需要展示或判断」的域。
 */
const SPECS = [
    // ---------- 订单主链路 ----------
    { domain: 'order', file: 'order/Order.php', prefix: 'STATUS_', enumName: 'OrderStatus', mapName: 'ORDER_STATUS_NAME_MAP', note: '订单主状态机，对应 Order.php:51-77', legacy: 'OrderStatus' },
    { domain: 'order', file: 'order/Order.php', prefix: 'PAY_STATUS_', enumName: 'OrderPayStatus', mapName: 'ORDER_PAY_STATUS_NAME_MAP', legacy: 'OrderPayStatus' },
    { domain: 'order', file: 'order/Order.php', prefix: 'PAY_WAY_', enumName: 'OrderPayWay', mapName: 'ORDER_PAY_WAY_NAME_MAP' },
    { domain: 'order', file: 'order/Order.php', prefix: 'PAYMENT_CHANNEL_', enumName: 'PaymentChannel', mapName: 'PAYMENT_CHANNEL_NAME_MAP', legacy: 'PaymentChannelEnum' },
    { domain: 'order', file: 'order/Order.php', prefix: 'VOUCHER_STATUS_', enumName: 'VoucherStatus', mapName: 'VOUCHER_STATUS_NAME_MAP' },
    { domain: 'order', file: 'order/Order.php', prefix: 'SOURCE_', enumName: 'OrderSource', mapName: 'ORDER_SOURCE_NAME_MAP' },
    // ---------- 支付流水 ----------
    { domain: 'payment', file: 'order/Payment.php', prefix: 'STATUS_', enumName: 'PaymentFlowStatus', mapName: 'PAYMENT_FLOW_STATUS_NAME_MAP', legacy: 'PaymentFlowStatus' },
    { domain: 'payment', file: 'order/Payment.php', prefix: 'TYPE_', enumName: 'PaymentType', mapName: 'PAYMENT_TYPE_NAME_MAP' },
    { domain: 'payment', file: 'order/Payment.php', prefix: 'WAY_', enumName: 'PaymentWay', mapName: 'PAYMENT_WAY_NAME_MAP', legacy: 'PaymentReceiptPayWayEnum' },
    { domain: 'payment', file: 'order/Payment.php', prefix: 'COLLECTION_', enumName: 'CollectionOwner', mapName: 'COLLECTION_OWNER_NAME_MAP' },
    // ---------- 退款 ----------
    { domain: 'refund', file: 'order/Refund.php', prefix: 'STATUS_', enumName: 'RefundStatus', mapName: 'REFUND_STATUS_NAME_MAP' },
    { domain: 'refund', file: 'order/Refund.php', prefix: 'TYPE_', enumName: 'RefundType', mapName: 'REFUND_TYPE_NAME_MAP' },
    // ---------- 变更 / 暂停 ----------
    { domain: 'change', file: 'order/OrderChange.php', prefix: 'STATUS_', enumName: 'OrderChangeStatus', mapName: 'ORDER_CHANGE_STATUS_NAME_MAP' },
    { domain: 'change', file: 'order/OrderChange.php', prefix: 'TYPE_', enumName: 'OrderChangeType', mapName: 'ORDER_CHANGE_TYPE_NAME_MAP' },
    { domain: 'change', file: 'order/OrderPause.php', prefix: 'STATUS_', enumName: 'OrderPauseStatus', mapName: 'ORDER_PAUSE_STATUS_NAME_MAP' },
    { domain: 'change', file: 'order/OrderPause.php', prefix: 'TYPE_', enumName: 'OrderPauseType', mapName: 'ORDER_PAUSE_TYPE_NAME_MAP' },
    // ---------- 售后 ----------
    { domain: 'aftersale', file: 'aftersale/AfterSaleTicket.php', prefix: 'STATUS_', enumName: 'AfterSaleTicketStatus', mapName: 'AFTER_SALE_TICKET_STATUS_NAME_MAP', note: '0=待分配、3=已完成。旧小程序写作「待处理/已解决」，以本契约为准' },
    { domain: 'aftersale', file: 'aftersale/AfterSaleTicket.php', prefix: 'TYPE_', enumName: 'AfterSaleTicketType', mapName: 'AFTER_SALE_TICKET_TYPE_NAME_MAP' },
    { domain: 'aftersale', file: 'aftersale/AfterSaleTicket.php', prefix: 'PRIORITY_', enumName: 'AfterSalePriority', mapName: 'AFTER_SALE_PRIORITY_NAME_MAP' },
    { domain: 'aftersale', file: 'aftersale/Complaint.php', prefix: 'STATUS_', enumName: 'ComplaintStatus', mapName: 'COMPLAINT_STATUS_NAME_MAP' },
    { domain: 'aftersale', file: 'aftersale/Complaint.php', prefix: 'TYPE_', enumName: 'ComplaintType', mapName: 'COMPLAINT_TYPE_NAME_MAP', note: '注意与 AfterSaleTicketType 区分：投诉类型是「服务态度/履约偏差/沟通问题/执行落差/其他」' },
    { domain: 'aftersale', file: 'aftersale/Complaint.php', prefix: 'LEVEL_', enumName: 'ComplaintLevel', mapName: 'COMPLAINT_LEVEL_NAME_MAP' },
    { domain: 'aftersale', file: 'aftersale/ServiceCallback.php', prefix: 'STATUS_', enumName: 'ServiceCallbackStatus', mapName: 'SERVICE_CALLBACK_STATUS_NAME_MAP' },
    // ---------- 评价 ----------
    { domain: 'review', file: 'review/Review.php', prefix: 'STATUS_', enumName: 'ReviewStatus', mapName: 'REVIEW_STATUS_NAME_MAP' },
    // ---------- 档期 / 候补 ----------
    { domain: 'schedule', file: 'schedule/Schedule.php', prefix: 'STATUS_', enumName: 'ScheduleStatus', mapName: 'SCHEDULE_STATUS_NAME_MAP', legacy: 'ScheduleStatus' },
    { domain: 'schedule', file: 'schedule/Waitlist.php', prefix: 'NOTIFY_STATUS_', enumName: 'WaitlistNotifyStatus', mapName: 'WAITLIST_NOTIFY_STATUS_NAME_MAP' },
    // ---------- 问卷 ----------
    { domain: 'questionnaire', file: 'questionnaire/CoupleQuestionnaireTask.php', prefix: 'STATUS_', enumName: 'QuestionnaireTaskStatus', mapName: 'QUESTIONNAIRE_TASK_STATUS_NAME_MAP', legacy: 'QuestionnaireTaskStatus' },
    { domain: 'questionnaire', file: 'questionnaire/CoupleQuestionnaireTask.php', prefix: 'SEND_STATUS_', enumName: 'QuestionnaireSendStatus', mapName: 'QUESTIONNAIRE_SEND_STATUS_NAME_MAP', legacy: 'QuestionnaireSendStatus' },
    // ---------- 结算 ----------
    { domain: 'settlement', file: 'financial/StaffSettlement.php', prefix: 'STATUS_', enumName: 'StaffSettlementStatus', mapName: 'STAFF_SETTLEMENT_STATUS_NAME_MAP' },
    { domain: 'settlement', file: 'financial/StaffSettlement.php', prefix: 'SETTLE_WAY_', enumName: 'StaffSettleWay', mapName: 'STAFF_SETTLE_WAY_NAME_MAP' },
    // ---------- 动态 ----------
    { domain: 'dynamic', file: 'dynamic/Dynamic.php', prefix: 'STATUS_', enumName: 'DynamicStatus', mapName: 'DYNAMIC_STATUS_NAME_MAP', note: '动态主状态机：服务人员发布动态后待审核，审核通过才对外可见' },
    // ---------- 服务人员作品 / 证书 ----------
    { domain: 'staff-work', file: 'staff/StaffWork.php', prefix: 'AUDIT_', enumName: 'StaffWorkAuditStatus', mapName: 'STAFF_WORK_AUDIT_STATUS_NAME_MAP', note: '服务人员作品审核状态，对应 StaffWork.audit_status 字段' },
    { domain: 'staff-certificate', file: 'staff/StaffCertificate.php', prefix: 'VERIFY_', enumName: 'StaffCertificateVerifyStatus', mapName: 'STAFF_CERTIFICATE_VERIFY_STATUS_NAME_MAP', note: '服务人员证书核验状态，对应 StaffCertificate.verify_status 字段（AUDIT_* 为同名别名常量，值一致）' }
];

const CONST_RE = /^\s*(?:public\s+|private\s+|protected\s+)?const\s+([A-Z][A-Z0-9_]*)\s*=\s*(-?\d+)\s*;\s*(?:\/\/\s*(.*))?\s*$/gm;

/**
 * 文案兜底：少数 PHP 常量没有行尾注释，生成器只能回退成成员名。
 * 这里补齐中文，保证「文案事实源在 PHP」这条规则不被破坏的前提下也能出可用文案。
 * 后端补上注释后，本表对应项应删除（生成器会优先使用 PHP 注释）。
 */
const LABEL_OVERRIDES = {
    QuestionnaireTaskStatus: {
        PENDING: '待填写',
        SUBMITTED: '已提交',
        CANCELLED: '已取消',
        VIEWED: '已查看',
        EXPIRED: '已过期'
    },
    QuestionnaireSendStatus: {
        PENDING: '待发送',
        SENT: '已发送',
        FAILED: '发送失败',
        SENDING: '发送中'
    }
};


function extract(src, prefix, enumName) {
    const members = [];
    const overrides = LABEL_OVERRIDES[enumName] || {};
    CONST_RE.lastIndex = 0;
    let m;
    while ((m = CONST_RE.exec(src)) !== null) {
        const [, rawName, rawValue, rawComment] = m;
        if (!rawName.startsWith(prefix)) continue;
        const name = rawName.slice(prefix.length);
        if (!name) continue;
        const fromPhp = (rawComment || '').trim();
        // PHP 有注释就用注释；没有则用兜底表；都没有则用成员名
        const label = fromPhp || overrides[name] || name;
        members.push({ name, value: Number(rawValue), label, labelFrom: fromPhp ? 'php' : overrides[name] ? 'override' : 'fallback' });
    }
    return members;
}

function buildContract() {
    const domains = new Map();
    const errors = [];
    const warnings = [];

    for (const spec of SPECS) {
        const abs = join(MODEL_ROOT, spec.file);
        if (!existsSync(abs)) {
            errors.push(`文件不存在：server/app/common/model/${spec.file}`);
            continue;
        }
        const members = extract(readFileSync(abs, 'utf8'), spec.prefix, spec.enumName);
        if (members.length === 0) {
            errors.push(`未解析到常量：${spec.file} 前缀 ${spec.prefix}`);
            continue;
        }
        for (const mm of members) {
            if (mm.labelFrom === 'fallback') {
                warnings.push(`${spec.enumName}.${mm.name} 缺少中文文案（PHP 无行尾注释，也未在 LABEL_OVERRIDES 登记）`);
            }
        }
        const seen = new Set();
        const unique = [];
        for (const mm of members) {
            if (seen.has(mm.name)) continue;
            seen.add(mm.name);
            unique.push(mm);
        }
        const byValue = new Map();
        for (const mm of unique) {
            if (byValue.has(mm.value)) {
                warnings.push(`${spec.enumName}: 值 ${mm.value} 同时属于 ${byValue.get(mm.value)} 与 ${mm.name}`);
            } else {
                byValue.set(mm.value, mm.name);
            }
        }
        if (!domains.has(spec.domain)) domains.set(spec.domain, []);
        domains.get(spec.domain).push({ ...spec, members: unique });
    }

    return { domains, errors, warnings };
}

function renderTs({ domains }) {
    const L = [];
    L.push('// ===============================================================');
    L.push('// 本文件由 scripts/contracts/export-status.mjs 自动生成，请勿手改。');
    L.push('// 事实源：server/app/common/model/**/*.php 的 const 常量。');
    L.push('// 修改状态值请改 PHP，然后执行：npm run contracts:gen');
    L.push('// ===============================================================');
    L.push('');

    for (const [domain, specs] of domains) {
        L.push(`// ------------------------- ${domain} -------------------------`);
        for (const spec of specs) {
            if (spec.note) L.push(`/** ${spec.note} */`);
            const nameW = Math.max(...spec.members.map((m) => m.name.length));
            const valueW = Math.max(...spec.members.map((m) => String(m.value).length));
            L.push(`export enum ${spec.enumName} {`);
            for (const mm of spec.members) {
                L.push(`    ${mm.name.padEnd(nameW)} = ${String(mm.value).padStart(valueW)}, // ${mm.label}`);
            }
            L.push('}');
            L.push('');
            L.push(`export const ${spec.mapName}: Record<${spec.enumName}, string> = {`);
            for (const mm of spec.members) {
                L.push(`    [${spec.enumName}.${mm.name}]: '${mm.label}',`);
            }
            L.push('};');
            L.push('');
        }
    }

    // 供守卫脚本读取的扁平词表：任何手写这些中文都视为重复定义
    const allLabels = new Set();
    for (const specs of domains.values()) {
        for (const spec of specs) for (const mm of spec.members) allLabels.add(mm.label);
    }
    L.push('// ------------------------- 元数据 -------------------------');
    L.push('/** 全部状态中文文案，供 check-contract-usage.mjs 做重复定义检测 */');
    L.push('export const ALL_STATUS_LABELS = [');
    L.push([...allLabels].sort().map((s) => `    '${s}'`).join(',\n'));
    L.push('] as const;');
    L.push('');
    L.push('export type StatusLabel = (typeof ALL_STATUS_LABELS)[number];');
    L.push('');
    L.push('// ------------------------- 运行时兜底 -------------------------');
    L.push('/**');
    L.push(' * 安全取文案：后端新增状态但前端未同步时，不崩、不显示 undefined。');
    L.push(' * 返回 { label, known }，known=false 时上层应上报监控。');
    L.push(' */');
    L.push('export function resolveStatusLabel(');
    L.push('    map: Record<number, string>,');
    L.push('    value: number | string | null | undefined');
    L.push('): { label: string; known: boolean } {');
    L.push('    const key = Number(value);');
    L.push('    const label = map[key];');
    L.push('    if (label) return { label, known: true };');
    L.push('    return { label: Number.isFinite(key) ? `未知状态(${key})` : \'未知状态\', known: false };');
    L.push('}');
    L.push('');

    return L.join('\n');
}

/** 解析 shared/contracts/generated/status.ts 里已有的 enum，用于差异报告 */
/** 2026-11-19：core.ts 已整体 re-export generated/status.ts（自身不再含枚举字面量），报告口径随之切换 */
function parseGeneratedEnums() {
    if (!existsSync(OUT_FILE)) return new Map();
    const src = readFileSync(OUT_FILE, 'utf8');
    const result = new Map();
    const enumRe = /export\s+enum\s+([A-Za-z0-9_]+)\s*\{([\s\S]*?)\n\}/g;
    let m;
    while ((m = enumRe.exec(src)) !== null) {
        const [, name, body] = m;
        const members = new Map();
        const memberRe = /^\s*([A-Z][A-Z0-9_]*)\s*=\s*(-?\d+)\s*,?/gm;
        let mm;
        while ((mm = memberRe.exec(body)) !== null) members.set(mm[1], Number(mm[2]));
        result.set(name, members);
    }
    return result;
}

/** core.ts 必须整体再导出生成物（三端共享入口），否则视为契约断链 */
function checkCoreReexport() {
    if (!existsSync(CORE_CONTRACT)) return 'shared/contracts/core.ts 不存在';
    const src = readFileSync(CORE_CONTRACT, 'utf8');
    if (!/export\s+\*\s+from\s+'\.\/generated\/status'/.test(src)) {
        return 'core.ts 缺少 export * from \'./generated/status\'（三端拿不到状态契约）';
    }
    return null;
}

function buildReport({ domains }, generated) {
    const rows = [];
    for (const specs of domains.values()) {
        for (const spec of specs) {
            const old = generated.get(spec.enumName);
            if (!old) {
                rows.push({ enum: spec.enumName, status: 'MISSING', detail: '生成物缺少枚举（请先 npm run contracts:gen）' });
                continue;
            }
            const diffs = [];
            for (const mm of spec.members) {
                if (!old.has(mm.name)) diffs.push(`缺成员 ${mm.name}=${mm.value}`);
                else if (old.get(mm.name) !== mm.value) diffs.push(`${mm.name}: 生成物=${old.get(mm.name)} php=${mm.value}`);
            }
            for (const [name, value] of old) {
                if (!spec.members.some((mm) => mm.name === name)) diffs.push(`多余成员 ${name}=${value}`);
            }
            rows.push({ enum: spec.enumName, status: diffs.length ? 'DRIFT' : 'OK', detail: diffs.length ? diffs.join('; ') : `${spec.members.length} 个成员一致` });
        }
    }
    return rows;
}

const args = new Set(process.argv.slice(2));
const contract = buildContract();

if (contract.errors.length) {
    console.error('契约解析失败：');
    for (const e of contract.errors) console.error('  - ' + e);
    process.exit(2);
}
for (const w of contract.warnings) console.warn('⚠ ' + w);

const ts = renderTs(contract);
const allSpecs = [...contract.domains.values()].flat();

if (args.has('--json')) {
    process.stdout.write(
        JSON.stringify(
            {
                generated_at: new Date().toISOString(),
                enums: allSpecs.map((s) => ({
                    enum: s.enumName,
                    map: s.mapName,
                    domain: s.domain,
                    source: `server/app/common/model/${s.file}`,
                    members: s.members
                }))
            },
            null,
            2
        ) + '\n'
    );
    process.exit(0);
}

if (args.has('--check')) {
    if (!existsSync(OUT_FILE)) {
        console.error(`✗ 生成物不存在：${OUT_FILE}\n  请先执行 npm run contracts:gen`);
        process.exit(1);
    }
    if (readFileSync(OUT_FILE, 'utf8') !== ts) {
        console.error('✗ 契约漂移：PHP 常量已变更，生成物未同步。');
        console.error('  修复：npm run contracts:gen 并提交生成物。');
        process.exit(1);
    }
    console.log(`✓ 状态契约一致（${allSpecs.length} 个枚举 / ${allSpecs.reduce((n, s) => n + s.members.length, 0)} 个状态值）`);
    process.exit(0);
}

if (args.has('--report')) {
    const reexportError = checkCoreReexport();
    const rows = buildReport(contract, parseGeneratedEnums());
    const w = Math.max(...rows.map((r) => r.enum.length), 5);
    console.log('\n契约报告（shared/contracts/generated/status.ts vs PHP 模型）\n');
    console.log('状态   ' + '枚举'.padEnd(w) + '  说明');
    console.log('-'.repeat(70 + w));
    for (const r of rows) {
        const mark = r.status === 'OK' ? '✓ OK ' : r.status === 'DRIFT' ? '✗ 漂移' : '· 缺失';
        console.log(mark.padEnd(6) + r.enum.padEnd(w) + '  ' + r.detail);
    }
    const missing = rows.filter((r) => r.status !== 'OK').length;
    if (reexportError) {
        console.error(`\n✗ ${reexportError}`);
        process.exit(1);
    }
    console.log(`\n合计 ${rows.length} 个枚举，${missing} 个需要处理；core.ts 再导出完整。\n`);
    process.exit(missing ? 1 : 0);
}

mkdirSync(OUT_DIR, { recursive: true });
writeFileSync(OUT_FILE, ts, 'utf8');
console.log(`✓ 已生成 shared/contracts/generated/status.ts`);
console.log(`  ${contract.domains.size} 个域 / ${allSpecs.length} 个枚举 / ${allSpecs.reduce((n, s) => n + s.members.length, 0)} 个状态值`);
