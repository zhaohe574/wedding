const fs = require('fs')
const path = require('path')

// 需要处理的目录
const directories = [
    path.join(__dirname, '../src/pages'),
    path.join(__dirname, '../src/components'),
    path.join(__dirname, '../packages/pages')
]

let totalFiles = 0
let modifiedFiles = 0
let totalReplacements = 0

// 递归遍历目录
function walkDir(dir) {
    const files = fs.readdirSync(dir)
    
    files.forEach(file => {
        const filePath = path.join(dir, file)
        const stat = fs.statSync(filePath)
        
        if (stat.isDirectory()) {
            walkDir(filePath)
        } else if (file.endsWith('.vue')) {
            processFile(filePath)
        }
    })
}

// 处理单个文件
function processFile(filePath) {
    totalFiles++
    let content = fs.readFileSync(filePath, 'utf-8')
    let modified = false
    let fileReplacements = 0
    
    // 注意：不要替换 tn-image-upload，只替换 tn-image
    // 使用负向前瞻确保 tn-image 后面不是 -upload
    
    // 替换 <tn-image 为 <image（但不包括 tn-image-upload）
    const tnImageOpenRegex = /<tn-image(?!-upload)\b/g
    if (tnImageOpenRegex.test(content)) {
        const matches = content.match(tnImageOpenRegex)
        fileReplacements += matches ? matches.length : 0
        content = content.replace(tnImageOpenRegex, '<image')
        modified = true
    }
    
    // 替换 </tn-image> 为 </image>（但不包括 tn-image-upload）
    const tnImageCloseRegex = /<\/tn-image(?!-upload)>/g
    if (tnImageCloseRegex.test(content)) {
        content = content.replace(tnImageCloseRegex, '</image>')
        modified = true
    }
    
    if (modified) {
        fs.writeFileSync(filePath, content, 'utf-8')
        modifiedFiles++
        totalReplacements += fileReplacements
        console.log(`✓ ${path.relative(path.join(__dirname, '..'), filePath)} - 替换 ${fileReplacements} 处`)
    }
}

console.log('开始修复 tn-image 组件...\n')

directories.forEach(dir => {
    if (fs.existsSync(dir)) {
        walkDir(dir)
    }
})

console.log('\n============================================================')
console.log('修复完成！')
console.log('============================================================')
console.log(`总文件数: ${totalFiles}`)
console.log(`修改文件数: ${modifiedFiles}`)
console.log(`总替换次数: ${totalReplacements}`)
console.log('============================================================\n')
