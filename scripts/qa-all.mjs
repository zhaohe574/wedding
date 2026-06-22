import { spawnSync } from 'node:child_process'
import { readdirSync, statSync } from 'node:fs'
import path from 'node:path'

const root = process.cwd()

const run = (command, args, options = {}) => {
    console.log(`\n> ${command} ${args.join(' ')}`)
    const result = spawnSync(command, args, {
        cwd: options.cwd || root,
        stdio: 'inherit',
        shell: process.platform === 'win32',
    })
    if (result.status !== 0) {
        process.exit(result.status || 1)
    }
}

const collectPhpFiles = (dir, files = []) => {
    for (const entry of readdirSync(dir)) {
        const fullPath = path.join(dir, entry)
        const stat = statSync(fullPath)
        if (stat.isDirectory()) {
            collectPhpFiles(fullPath, files)
        } else if (entry.endsWith('.php')) {
            files.push(fullPath)
        }
    }
    return files
}

run('npm', ['run', 'qa:contracts'])
run('npm', ['run', 'type-check:active'], { cwd: path.join(root, 'admin') })
run('npm', ['run', 'type-check:active'], { cwd: path.join(root, 'uniapp') })
run('npm', ['run', 'validate:config'], { cwd: path.join(root, 'uniapp') })
run('npm', ['run', 'build:mp-weixin:check'], { cwd: path.join(root, 'uniapp') })
run('npm', ['run', 'quality'], { cwd: path.join(root, 'pc') })

const phpDirs = [
    path.join(root, 'server/app'),
    path.join(root, 'server/config'),
    path.join(root, 'server/route'),
    path.join(root, 'server/public/install'),
]

for (const file of phpDirs.flatMap((dir) => collectPhpFiles(dir))) {
    run('php', ['-l', file])
}

run('php', [path.join(root, 'server/tests/regression/PaymentScheduleReliabilityTest.php')])
