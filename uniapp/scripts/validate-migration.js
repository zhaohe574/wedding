/**
 * 图鸟 UI 迁移验证脚本
 * 使用方法: node scripts/validate-migration.js
 */

const fs = require('fs')
const path = require('path')

// uView UI 组件列表（需要检查是否还存在）
const uViewComponents = [
  'u-icon', 'u-button', 'u-avatar', 'u-image', 'u-badge', 'u-tag',
  'u-input', 'u-search', 'u-checkbox', 'u-radio', 'u-switch', 'u-picker', 'u-textarea',
  'u-checkbox-group', 'u-radio-group',
  'u-popup', 'u-modal', 'u-action-sheet', 'u-loading', 'u-empty',
  'u-navbar', 'u-sticky', 'u-notice-bar', 'u-tabs',
  'u-row', 'u-col', 'u-grid', 'u-grid-item',
  'u-swiper', 'u-swiper-item', 'u-card', 'u-list', 'u-list-item',
  'u-form-item', 'u-verification-code' // 这两个需要特殊处理
]

// 需要排除的目录
const excludeDirs = ['node_modules', 'uni_modules', '.git', 'dist', '.kiro']

// 验证结果
const validationResult = {
  totalFiles: 0,
  passedFiles: 0,
  failedFiles: 0,
  errors: [],
  warnings: []
}

// 检查文件中是否包含 uView UI 组件
function hasUViewComponents(content, filePath) {
  const lines = content.split('\n')
  const foundComponents = []
  
  lines.forEach((line, index) => {
    uViewComponents.forEach(component => {
      // 检查开标签和闭标签
      if (line.includes(`<${component}`) || line.includes(`</${component}>`)) {
        foundComponents.push({
          component,
          line: index + 1,
          content: line.trim()
        })
      }
    })
  })
  
  return foundComponents
}

// 检查图鸟 UI 组件中是否有无效属性
function hasInvalidAttributes(content, filePath) {
  const lines = content.split('\n')
  const invalidAttributes = []
  
  lines.forEach((line, index) => {
    // 只检查图鸟 UI 组件的行
    if (line.includes('<tn-')) {
      // 检查驼峰命名属性
      const camelCaseAttrs = ['customStyle', 'maskCloseAble', 'closeable', 'borderRadius', 'bgColor', 'textColor']
      camelCaseAttrs.forEach(attr => {
        if (line.includes(attr) && !line.includes(`${attr}=`)) {
          // 排除变量名中包含这些词的情况
          const regex = new RegExp(`\\b${attr}\\s*=`, 'g')
          if (regex.test(line)) {
            invalidAttributes.push({
              type: 'camelCase',
              attribute: attr,
              line: index + 1,
              content: line.trim(),
              suggestion: attr.replace(/([A-Z])/g, '-$1').toLowerCase()
            })
          }
        }
      })
      
      // 检查旧的尺寸值
      if (line.match(/size=["'](mini|medium|large)["']/)) {
        const match = line.match(/size=["'](mini|medium|large)["']/)
        const sizeMap = { mini: 'sm', medium: 'md', large: 'lg' }
        invalidAttributes.push({
          type: 'size',
          attribute: 'size',
          line: index + 1,
          content: line.trim(),
          suggestion: `size="${sizeMap[match[1]]}"`
        })
      }
      
      // 检查旧的形状值
      if (line.includes('shape="circle"')) {
        invalidAttributes.push({
          type: 'shape',
          attribute: 'shape',
          line: index + 1,
          content: line.trim(),
          suggestion: 'shape="round"'
        })
      }
      
      // 检查 action-sheet 的 :list 属性
      if (line.includes('<tn-action-sheet') && line.includes(':list=')) {
        invalidAttributes.push({
          type: 'actionSheet',
          attribute: ':list',
          line: index + 1,
          content: line.trim(),
          suggestion: ':data='
        })
      }
    }
  })
  
  return invalidAttributes
}

// 验证单个文件
function validateFile(filePath) {
  try {
    const content = fs.readFileSync(filePath, 'utf-8')
    let hasError = false
    
    // 检查 uView UI 组件
    const uViewComps = hasUViewComponents(content, filePath)
    if (uViewComps.length > 0) {
      // 检查是否是需要保留的组件
      const shouldKeep = uViewComps.filter(c => 
        c.component === 'u-back-top' || c.component === 'u-parse'
      )
      const shouldReplace = uViewComps.filter(c => 
        c.component !== 'u-back-top' && c.component !== 'u-parse'
      )
      
      if (shouldReplace.length > 0) {
        validationResult.errors.push({
          file: filePath,
          type: 'uViewComponent',
          message: `发现 ${shouldReplace.length} 个未替换的 uView UI 组件`,
          details: shouldReplace
        })
        hasError = true
      }
      
      if (shouldKeep.length > 0) {
        validationResult.warnings.push({
          file: filePath,
          type: 'keepComponent',
          message: `发现 ${shouldKeep.length} 个保留的 uView UI 组件（u-back-top 或 u-parse）`,
          details: shouldKeep
        })
      }
    }
    
    // 检查无效属性
    const invalidAttrs = hasInvalidAttributes(content, filePath)
    if (invalidAttrs.length > 0) {
      validationResult.errors.push({
        file: filePath,
        type: 'invalidAttribute',
        message: `发现 ${invalidAttrs.length} 个无效属性`,
        details: invalidAttrs
      })
      hasError = true
    }
    
    return !hasError
  } catch (error) {
    validationResult.errors.push({
      file: filePath,
      type: 'readError',
      message: `读取文件失败: ${error.message}`
    })
    return false
  }
}

// 递归验证目录
function validateDirectory(dir) {
  try {
    const files = fs.readdirSync(dir)
    
    files.forEach(file => {
      const filePath = path.join(dir, file)
      try {
        const stat = fs.statSync(filePath)
        
        if (stat.isDirectory()) {
          if (!excludeDirs.includes(file)) {
            validateDirectory(filePath)
          }
        } else if (file.endsWith('.vue')) {
          validationResult.totalFiles++
          if (validateFile(filePath)) {
            validationResult.passedFiles++
          } else {
            validationResult.failedFiles++
          }
        }
      } catch (error) {
        validationResult.errors.push({
          file: filePath,
          type: 'statError',
          message: `获取文件信息失败: ${error.message}`
        })
      }
    })
  } catch (error) {
    validationResult.errors.push({
      file: dir,
      type: 'readDirError',
      message: `读取目录失败: ${error.message}`
    })
  }
}

// 生成报告
function generateReport() {
  const passRate = validationResult.totalFiles > 0 
    ? ((validationResult.passedFiles / validationResult.totalFiles) * 100).toFixed(2)
    : 0
  
  console.log('\n' + '='.repeat(60))
  console.log('迁移验证报告')
  console.log('='.repeat(60))
  console.log(`总文件数: ${validationResult.totalFiles}`)
  console.log(`通过: ${validationResult.passedFiles}`)
  console.log(`失败: ${validationResult.failedFiles}`)
  console.log(`通过率: ${passRate}%`)
  
  if (validationResult.warnings.length > 0) {
    console.log(`\n警告数: ${validationResult.warnings.length}`)
  }
  
  if (validationResult.errors.length > 0) {
    console.log(`\n错误数: ${validationResult.errors.length}`)
    console.log('\n错误详情:')
    validationResult.errors.forEach((error, index) => {
      console.log(`\n${index + 1}. ${error.file}`)
      console.log(`   类型: ${error.type}`)
      console.log(`   ${error.message}`)
      
      if (error.details && error.details.length > 0) {
        console.log('   详细信息:')
        error.details.slice(0, 5).forEach(detail => {
          console.log(`   - 行 ${detail.line}: ${detail.content || detail.component}`)
          if (detail.suggestion) {
            console.log(`     建议: ${detail.suggestion}`)
          }
        })
        if (error.details.length > 5) {
          console.log(`   ... 还有 ${error.details.length - 5} 个问题`)
        }
      }
    })
  }
  
  if (validationResult.warnings.length > 0) {
    console.log('\n警告详情:')
    validationResult.warnings.forEach((warning, index) => {
      console.log(`\n${index + 1}. ${warning.file}`)
      console.log(`   ${warning.message}`)
    })
  }
  
  console.log('\n' + '='.repeat(60))
  
  if (validationResult.failedFiles === 0) {
    console.log('\n✓ 所有文件验证通过！')
  } else {
    console.log('\n✗ 发现问题，请修复后重新验证')
  }
  
  return validationResult.failedFiles === 0
}

// 主函数
function main() {
  const srcDir = path.join(__dirname, '../src')
  const packagesDir = path.join(__dirname, '../packages')
  
  console.log('开始验证图鸟 UI 迁移...\n')
  console.log('验证 src 目录...')
  validateDirectory(srcDir)
  
  // 如果 packages 目录存在，也验证它
  if (fs.existsSync(packagesDir)) {
    console.log('验证 packages 目录...')
    validateDirectory(packagesDir)
  }
  
  const success = generateReport()
  process.exit(success ? 0 : 1)
}

main()
