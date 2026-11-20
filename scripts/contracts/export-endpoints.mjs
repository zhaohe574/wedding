#!/usr/bin/env node
/**
 * 端点契约扫描器（正式版）
 * ---------------------------------------------------------------
 * 扫描 server/app/api/controller/*.php，提取每个 public 方法对应的 URL，
 * 生成 shared/contracts/generated/endpoints.json。
 *
 * 同时做三项体检：
 *   1. 控制器段是否 snake_case（Linux 大小写敏感，驼峰会 404）
 *   2. action 是否 camelCase 且不含下划线（下划线会 404）
 *   3. $notNeedLogin 里声明的 action 是否真实存在（拼错即静默要求登录）
 *
 * 用法：
 *   node scripts/contracts/export-endpoints.mjs            # 生成
 *   node scripts/contracts/export-endpoints.mjs --check     # CI 校验漂移
 *   node scripts/contracts/export-endpoints.mjs --audit     # 只打印体检
 */

import { readFileSync, writeFileSync, mkdirSync, existsSync, readdirSync } from 'node:fs';
import { dirname, resolve, join, basename } from 'node:path';
import { fileURLToPath } from 'node:url';

const __dirname = dirname(fileURLToPath(import.meta.url));
const REPO_ROOT = resolve(__dirname, '../..');
const CTRL_DIR = join(REPO_ROOT, 'server/app/api/controller');
const OUT_DIR = join(REPO_ROOT, 'shared/contracts/generated');
const OUT_FILE = join(OUT_DIR, 'endpoints.json');

const IGNORED_METHODS = new Set(['initialize', '__construct', '__call', 'success', 'fail', 'data', 'forbidden']);

/** C 端不使用、不需要进入新端契约的控制器 */
const NON_CLIENT_CONTROLLERS = new Set(['StaffCenter', 'Dashboard', 'Pc']);

function toSnake(name) {
    return name
        .replace(/([a-z0-9])([A-Z])/g, '$1_$2')
        .replace(/([A-Z]+)([A-Z][a-z])/g, '$1_$2')
        .toLowerCase();
}

function parseController(file) {
    const src = readFileSync(join(CTRL_DIR, file), 'utf8');
    const className = basename(file, '.php').replace(/Controller$/, '');
    const segment = toSnake(className);

    const notNeedLogin = [];
    const nnlMatch = src.match(/\$notNeedLogin\s*=\s*\[([\s\S]*?)\]/);
    if (nnlMatch) {
        const itemRe = /['"]([^'"]+)['"]/g;
        let m;
        while ((m = itemRe.exec(nnlMatch[1])) !== null) notNeedLogin.push(m[1]);
    }

    const actions = [];
    const fnRe = /public\s+function\s+([A-Za-z_][A-Za-z0-9_]*)\s*\(/g;
    let fm;
    while ((fm = fnRe.exec(src)) !== null) {
        const name = fm[1];
        if (IGNORED_METHODS.has(name) || name.startsWith('_')) continue;
        actions.push(name);
    }

    return { className, segment, file: `app/api/controller/${file}`, actions, notNeedLogin };
}

function build() {
    const files = readdirSync(CTRL_DIR)
        .filter((f) => f.endsWith('Controller.php') && f !== 'BaseApiController.php')
        .sort();

    const controllers = files.map(parseController);
    const issues = [];

    for (const c of controllers) {
        if (!/^[a-z0-9]+(_[a-z0-9]+)*$/.test(c.segment)) {
            issues.push({ level: 'error', controller: c.className, msg: `控制器段 "${c.segment}" 非 snake_case` });
        }
        for (const a of c.actions) {
            if (a.includes('_')) {
                issues.push({ level: 'error', controller: c.className, action: a, msg: 'action 含下划线，URL 会 404' });
            }
        }
        for (const n of c.notNeedLogin) {
            if (!c.actions.includes(n)) {
                issues.push({
                    level: 'warn',
                    controller: c.className,
                    action: n,
                    msg: '$notNeedLogin 声明的 action 不存在（拼写错误会导致该接口意外要求登录）'
                });
            }
        }
    }

    const endpoints = [];
    for (const c of controllers) {
        for (const a of c.actions) {
            endpoints.push({
                id: `${c.segment}.${a}`,
                method: 'ANY',
                path: `/api/${c.segment}/${a}`,
                controller: c.className,
                action: a,
                auth: !c.notNeedLogin.includes(a),
                client: !NON_CLIENT_CONTROLLERS.has(c.className),
                source: c.file
            });
        }
    }

    return { controllers, endpoints, issues };
}

const args = new Set(process.argv.slice(2));
const { controllers, endpoints, issues } = build();
const errors = issues.filter((i) => i.level === 'error');
const warns = issues.filter((i) => i.level === 'warn');

if (args.has('--audit')) {
    console.log(`\n控制器 ${controllers.length} 个 / 端点 ${endpoints.length} 个\n`);
    console.log('控制器'.padEnd(24) + 'URL 段'.padEnd(24) + '端点  免登录');
    console.log('-'.repeat(76));
    for (const c of controllers) {
        console.log(c.className.padEnd(24) + c.segment.padEnd(24) + String(c.actions.length).padEnd(6) + c.notNeedLogin.join(','));
    }
    console.log(`\nC 端需维护：${endpoints.filter((e) => e.client).length} 个（排除 ${[...NON_CLIENT_CONTROLLERS].join(' / ')}）`);
    if (issues.length) {
        console.log('\n体检问题：');
        for (const i of issues) console.log(`  [${i.level}] ${i.controller}${i.action ? '/' + i.action : ''} — ${i.msg}`);
    } else {
        console.log('体检通过：控制器段与 action 命名均符合调度规则。');
    }
    console.log('');
    process.exit(0);
}

const payload = {
    generated_at: new Date().toISOString(),
    base: '/api',
    controller_count: controllers.length,
    endpoint_count: endpoints.length,
    client_endpoint_count: endpoints.filter((e) => e.client).length,
    endpoints
};
const json = JSON.stringify(payload, null, 2) + '\n';

if (args.has('--check')) {
    if (!existsSync(OUT_FILE)) {
        console.error(`✗ 端点契约不存在：${OUT_FILE}`);
        process.exit(1);
    }
    const current = JSON.parse(readFileSync(OUT_FILE, 'utf8'));
    const a = current.endpoints.map((e) => e.path).sort();
    const b = endpoints.map((e) => e.path).sort();
    const added = b.filter((p) => !a.includes(p));
    const removed = a.filter((p) => !b.includes(p));
    if (added.length || removed.length) {
        console.error('✗ 端点契约漂移：');
        for (const p of added) console.error('  + ' + p);
        for (const p of removed) console.error('  - ' + p);
        console.error('  修复：npm run endpoints:gen');
        process.exit(1);
    }
    console.log(`✓ 端点契约一致（${b.length} 个端点）`);
    if (errors.length) {
        console.error(`✗ 命名体检未通过（${errors.length} 项），见 --audit`);
        process.exit(1);
    }
    process.exit(0);
}

mkdirSync(OUT_DIR, { recursive: true });
writeFileSync(OUT_FILE, json, 'utf8');
console.log(`✓ 已生成 shared/contracts/generated/endpoints.json`);
console.log(`  ${controllers.length} 个控制器 / ${endpoints.length} 个端点（C 端 ${payload.client_endpoint_count} 个）`);
if (errors.length) console.warn(`  ⚠ ${errors.length} 项命名错误，执行 --audit 查看`);
if (warns.length) console.warn(`  ⚠ ${warns.length} 项 notNeedLogin 声明可疑`);
