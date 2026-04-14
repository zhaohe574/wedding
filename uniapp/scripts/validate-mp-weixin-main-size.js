/**
 * 微信小程序发布主包体积校验
 * 使用方法: node scripts/validate-mp-weixin-main-size.js
 */

const fs = require('fs')
const path = require('path')

const MAIN_PACKAGE_LIMIT_MB = 1.4
const MAIN_PACKAGE_LIMIT_BYTES = Math.round(MAIN_PACKAGE_LIMIT_MB * 1024 * 1024)
const BUILD_ROOT = path.join(__dirname, '../dist/build/mp-weixin')
const SUBPACKAGE_ROOT_NAME = 'packages'
const TOP_ENTRY_COUNT = 20
const TOP_FILE_COUNT = 20

function formatSize(bytes) {
    if (bytes >= 1024 * 1024) {
        return `${(bytes / (1024 * 1024)).toFixed(2)} MB`
    }
    if (bytes >= 1024) {
        return `${(bytes / 1024).toFixed(1)} KB`
    }
    return `${bytes} B`
}

function toRelativePath(filePath) {
    return path.relative(BUILD_ROOT, filePath).replace(/\\/g, '/')
}

function getFilesRecursively(targetDir) {
    const entries = fs.readdirSync(targetDir, { withFileTypes: true })
    const files = []

    entries.forEach((entry) => {
        const fullPath = path.join(targetDir, entry.name)
        if (entry.isDirectory()) {
            files.push(...getFilesRecursively(fullPath))
            return
        }
        files.push(fullPath)
    })

    return files
}

function getMainPackageFiles() {
    if (!fs.existsSync(BUILD_ROOT)) {
        throw new Error(`未找到微信小程序发布产物目录: ${BUILD_ROOT}`)
    }

    return getFilesRecursively(BUILD_ROOT).filter((filePath) => {
        const relativePath = toRelativePath(filePath)
        return !relativePath.startsWith(`${SUBPACKAGE_ROOT_NAME}/`)
    })
}

function getTopLevelEntrySize(entryPath) {
    const stat = fs.statSync(entryPath)
    if (!stat.isDirectory()) {
        return stat.size
    }

    return getFilesRecursively(entryPath).reduce(
        (sum, filePath) => sum + fs.statSync(filePath).size,
        0
    )
}

function printSectionTitle(title) {
    console.log(`\n${title}`)
    console.log('-'.repeat(title.length))
}

function main() {
    const files = getMainPackageFiles()
    const totalBytes = files.reduce((sum, filePath) => sum + fs.statSync(filePath).size, 0)
    const passed = totalBytes <= MAIN_PACKAGE_LIMIT_BYTES

    console.log('\n' + '='.repeat(64))
    console.log('微信小程序发布主包体积校验')
    console.log('='.repeat(64))
    console.log(`扫描目录: ${BUILD_ROOT}`)
    console.log(`阈值: ${formatSize(MAIN_PACKAGE_LIMIT_BYTES)} (${MAIN_PACKAGE_LIMIT_MB} MB)`)
    console.log(`主包体积: ${formatSize(totalBytes)}`)
    console.log(`文件数量: ${files.length}`)
    console.log(`结果: ${passed ? '通过' : '超限'}`)

    const topLevelEntries = fs
        .readdirSync(BUILD_ROOT, { withFileTypes: true })
        .filter((entry) => entry.name !== SUBPACKAGE_ROOT_NAME)
        .map((entry) => {
            const fullPath = path.join(BUILD_ROOT, entry.name)
            return {
                name: entry.name,
                size: getTopLevelEntrySize(fullPath)
            }
        })
        .sort((a, b) => b.size - a.size)
        .slice(0, TOP_ENTRY_COUNT)

    printSectionTitle('主包目录体积 Top')
    topLevelEntries.forEach((entry, index) => {
        console.log(`${index + 1}. ${entry.name}  ${formatSize(entry.size)}`)
    })

    const topFiles = files
        .map((filePath) => ({
            path: toRelativePath(filePath),
            size: fs.statSync(filePath).size
        }))
        .sort((a, b) => b.size - a.size)
        .slice(0, TOP_FILE_COUNT)

    printSectionTitle('主包大文件 Top')
    topFiles.forEach((file, index) => {
        console.log(`${index + 1}. ${file.path}  ${formatSize(file.size)}`)
    })

    if (!passed) {
        console.log('\n建议: 优先检查 common/vendor.js、主包页面脚本、全局样式和主包静态资源。')
    }

    console.log('\n' + '='.repeat(64))
    process.exit(passed ? 0 : 1)
}

main()
