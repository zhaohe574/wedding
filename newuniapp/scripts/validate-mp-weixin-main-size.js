/**
 * 微信小程序发布产物体积校验
 * 使用方法: node scripts/validate-mp-weixin-main-size.js
 *
 * 校验项：
 *   1. 主包体积 <= 1.4MB（微信硬上限 2MB，留 30% 余量）
 *   2. 每个分包体积 <= 2MB
 *   3. 总体积 <= 20MB
 */

const fs = require('fs')
const path = require('path')

const MAIN_PACKAGE_LIMIT_MB = 1.4
const SUB_PACKAGE_LIMIT_MB = 2
const TOTAL_LIMIT_MB = 20

const MB = 1024 * 1024
const BUILD_ROOT = path.join(__dirname, '../dist/build/mp-weixin')
const SUBPACKAGE_ROOT = 'packages'
const TOP_ENTRY_COUNT = 20
const TOP_FILE_COUNT = 20

function formatSize(bytes) {
    if (bytes >= MB) return `${(bytes / MB).toFixed(2)} MB`
    if (bytes >= 1024) return `${(bytes / 1024).toFixed(1)} KB`
    return `${bytes} B`
}

function toRelativePath(filePath) {
    return path.relative(BUILD_ROOT, filePath).replace(/\\/g, '/')
}

function getFilesRecursively(targetDir) {
    const entries = fs.readdirSync(targetDir, { withFileTypes: true })
    const files = []
    for (const entry of entries) {
        const fullPath = path.join(targetDir, entry.name)
        if (entry.isDirectory()) files.push(...getFilesRecursively(fullPath))
        else files.push(fullPath)
    }
    return files
}

function sumSize(files) {
    return files.reduce((sum, f) => sum + fs.statSync(f).size, 0)
}

function printTitle(title) {
    console.log(`\n${title}`)
    console.log('-'.repeat(title.length))
}

function main() {
    if (!fs.existsSync(BUILD_ROOT)) {
        console.error(`未找到微信小程序发布产物目录: ${BUILD_ROOT}`)
        console.error('请先执行: npm run build:mp-weixin')
        process.exit(1)
    }

    const allFiles = getFilesRecursively(BUILD_ROOT)
    const mainFiles = allFiles.filter((f) => !toRelativePath(f).startsWith(`${SUBPACKAGE_ROOT}/`))
    const subFiles = allFiles.filter((f) => toRelativePath(f).startsWith(`${SUBPACKAGE_ROOT}/`))

    const mainBytes = sumSize(mainFiles)
    const totalBytes = sumSize(allFiles)
    const mainLimit = Math.round(MAIN_PACKAGE_LIMIT_MB * MB)
    const subLimit = Math.round(SUB_PACKAGE_LIMIT_MB * MB)
    const totalLimit = Math.round(TOTAL_LIMIT_MB * MB)

    // 按分包聚合
    const subPackages = new Map()
    for (const f of subFiles) {
        const rel = toRelativePath(f)
        const name = rel.split('/')[1] || '(root)'
        if (!subPackages.has(name)) subPackages.set(name, [])
        subPackages.get(name).push(f)
    }

    const mainPassed = mainBytes <= mainLimit
    const overSubs = [...subPackages.entries()]
        .map(([name, files]) => ({ name, bytes: sumSize(files) }))
        .filter((s) => s.bytes > subLimit)
    const totalPassed = totalBytes <= totalLimit

    console.log('\n' + '='.repeat(64))
    console.log('微信小程序产物体积校验')
    console.log('='.repeat(64))
    console.log(`扫描目录: ${BUILD_ROOT}`)
    console.log(`主包: ${formatSize(mainBytes)} / 阈值 ${MAIN_PACKAGE_LIMIT_MB} MB   ${mainPassed ? '通过' : '超限'}`)
    console.log(`总体积: ${formatSize(totalBytes)} / 阈值 ${TOTAL_LIMIT_MB} MB   ${totalPassed ? '通过' : '超限'}`)
    console.log(`文件数量: ${allFiles.length}（主包 ${mainFiles.length} / 分包 ${subFiles.length}）`)

    if (subPackages.size) {
        printTitle('分包体积')
        const rows = [...subPackages.entries()]
            .map(([name, files]) => ({ name, bytes: sumSize(files) }))
            .sort((a, b) => b.bytes - a.bytes)
        for (const r of rows) {
            const flag = r.bytes > subLimit ? '  ← 超限' : ''
            console.log(`${r.name.padEnd(20)} ${formatSize(r.bytes).padStart(10)}${flag}`)
        }
    }

    const topEntries = fs
        .readdirSync(BUILD_ROOT, { withFileTypes: true })
        .filter((e) => e.name !== SUBPACKAGE_ROOT)
        .map((e) => {
            const full = path.join(BUILD_ROOT, e.name)
            return { name: e.name, size: fs.statSync(full).isDirectory() ? sumSize(getFilesRecursively(full)) : fs.statSync(full).size }
        })
        .sort((a, b) => b.size - a.size)
        .slice(0, TOP_ENTRY_COUNT)

    printTitle('主包目录体积 Top')
    topEntries.forEach((e, i) => console.log(`${String(i + 1).padStart(2)}. ${e.name}  ${formatSize(e.size)}`))

    const topFiles = mainFiles
        .map((f) => ({ path: toRelativePath(f), size: fs.statSync(f).size }))
        .sort((a, b) => b.size - a.size)
        .slice(0, TOP_FILE_COUNT)

    printTitle('主包大文件 Top')
    topFiles.forEach((f, i) => console.log(`${String(i + 1).padStart(2)}. ${f.path}  ${formatSize(f.size)}`))

    const passed = mainPassed && totalPassed && overSubs.length === 0
    if (!passed) {
        console.log('\n建议: 优先检查 common/vendor.js、主包页面脚本、全局样式、主包静态资源与 vk-uview 组件注册范围。')
    }
    console.log('\n' + '='.repeat(64))
    process.exit(passed ? 0 : 1)
}

main()
