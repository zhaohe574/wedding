import { spawnSync } from 'node:child_process'
import { readdirSync, lstatSync } from 'node:fs'
import path from 'node:path'

const root = process.cwd()
const php = process.env.QA_PHP_BINARY || 'php'

const run = (command, args, options = {}) => {
    console.log(`\n> ${command} ${args.join(' ')}`)
    const result = spawnSync(command, args, {
        cwd: options.cwd || root,
        stdio: 'inherit',
        shell: process.platform === 'win32' && command === 'npm',
    })
    if (result.status !== 0) {
        process.exit(result.status || 1)
    }
}

const collectPhpFiles = (dir, files = []) => {
    for (const entry of readdirSync(dir)) {
        const fullPath = path.join(dir, entry)
        const stat = lstatSync(fullPath)
        if (stat.isSymbolicLink()) continue
        if (stat.isDirectory()) {
            collectPhpFiles(fullPath, files)
        } else if (entry.endsWith('.php')) {
            files.push(fullPath)
        }
    }
    return files
}

// 跨端契约使用守卫：状态映射唯一事实源（STATUS-001）、金额格式化收敛（FORMAT-001）
run('node', ['scripts/check-contract-usage.mjs'])
// QA 资产化：把审计期的临时验证脚本沉淀为长期回归项（源码级，不依赖 DB / 构建产物）
//   - sms_verify_gate_checks.php：短信验证码「校验次数闸门」行为 + 源码结构守卫
//   - format_money_checks.mjs：formatMoney 与 7 个历史站点的视觉等价性
//   - order_status_consistency_checks.mjs：服务端 Order.php 常量 vs shared 契约逐值比对
run(php, [path.join(root, 'tests/qa/sms_verify_gate_checks.php')])
run('node', ['tests/qa/format_money_checks.mjs'])
run('node', ['tests/qa/order_status_consistency_checks.mjs'])
run('npm', ['run', 'qa:contracts'])
run('node', ['--test', 'uniapp/tests/staff-manual-order.test.cjs', 'uniapp/tests/oa-reminder.test.cjs', 'uniapp/tests/oa-subscribe.test.cjs', 'uniapp/tests/oa-invitation.test.cjs', 'uniapp/tests/oa-login-restore.test.cjs', 'uniapp/tests/oa-router-navigation.test.cjs'])
// 首次安装需先由 Vite 生成自动导入的类型声明。
run('npm', ['run', 'build:check'], { cwd: path.join(root, 'admin') })
run('npm', ['run', 'type-check:active'], { cwd: path.join(root, 'admin') })
run('npm', ['run', 'type-check:active'], { cwd: path.join(root, 'uniapp') })
run('npm', ['run', 'validate:config'], { cwd: path.join(root, 'uniapp') })
run('npm', ['run', 'build:mp-weixin:check'], { cwd: path.join(root, 'uniapp') })
run('npm', ['run', 'quality'], { cwd: path.join(root, 'pc') })
run('npm', ['run', 'build:check'], { cwd: path.join(root, 'pc') })

const phpDirs = [
    path.join(root, 'server/app'),
    path.join(root, 'server/config'),
    path.join(root, 'server/database'),
    path.join(root, 'server/route'),
    path.join(root, 'server/public/install'),
    path.join(root, 'server/tests'),
]

const phpFiles = [...phpDirs.flatMap((dir) => collectPhpFiles(dir)),
    path.join(root, 'server/public/index.php'), path.join(root, 'server/public/router.php')]
for (const file of phpFiles) {
    const result = spawnSync(php, ['-l', file], { encoding: 'utf8' })
    if (result.status !== 0) {
        console.error(file, result.stdout, result.stderr, result.error || '')
        process.exit(1)
    }
}
console.log(`PHP 语法检查通过：${phpFiles.length} 个文件`)

for (const test of ['PaymentScheduleReliabilityTest', 'MaterialScopeTest', 'ProjectAuditRegressionTest', 'ReleaseUpgradeTest', 'InstallerTest', 'FreshInstallPaymentTest', 'PosterRenderingTest', 'ConcurrentPaymentNotificationTest', 'AccountNotificationTest', 'StaffManualOrderTest']) {
    run(php, [path.join(root, `server/tests/regression/${test}.php`)])
}
