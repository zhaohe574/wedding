/**
 * 配置文件验证脚本
 * 使用方法: node scripts/validate-config.js
 */

const fs = require('fs')
const path = require('path')

// 验证结果
const validationResult = {
  pagesJson: { passed: false, errors: [], warnings: [] },
  appVue: { passed: false, errors: [], warnings: [] },
  tsconfig: { passed: false, errors: [], warnings: [] },
  packageJson: { passed: false, errors: [], warnings: [] }
}

// 验证 pages.json
function validatePagesJson() {
  const filePath = path.join(__dirname, '../src/pages.json')
  
  try {
    const content = fs.readFileSync(filePath, 'utf-8')
    
    // 检查是否包含图鸟 UI 的 easycom 配置
    const hasTuniaoEasycom = content.includes('@tuniao/tnui-vue3-uniapp')
    if (!hasTuniaoEasycom) {
      validationResult.pagesJson.errors.push('缺少图鸟 UI 的 easycom 配置')
    }
    
    // 检查 easycom 配置格式
    if (hasTuniaoEasycom) {
      const hasItemGroup = content.includes('^tn-(.*)-(item|group)$')
      const hasBasic = content.includes('^tn-(.*)') && content.includes('@tuniao/tnui-vue3-uniapp/components/$1/src/$1.vue')
      
      if (!hasItemGroup) {
        validationResult.pagesJson.warnings.push('建议添加 tn-*-item 和 tn-*-group 的 easycom 配置')
      }
      if (!hasBasic) {
        validationResult.pagesJson.errors.push('图鸟 UI 基础组件的 easycom 配置不正确')
      }
    }
    
    // 检查是否还包含 uView UI 的配置
    const hasUViewConfig = content.includes('vk-uview-ui') || content.includes('uview-ui')
    if (hasUViewConfig) {
      validationResult.pagesJson.errors.push('仍包含 uView UI 的配置，应该移除')
    }
    
    validationResult.pagesJson.passed = 
      hasTuniaoEasycom && 
      !hasUViewConfig && 
      validationResult.pagesJson.errors.length === 0
    
  } catch (error) {
    validationResult.pagesJson.errors.push(`读取文件失败: ${error.message}`)
  }
}

// 验证 App.vue
function validateAppVue() {
  const filePath = path.join(__dirname, '../src/App.vue')
  
  try {
    const content = fs.readFileSync(filePath, 'utf-8')
    
    // 检查是否引入图鸟 UI 样式
    const hasTuniaoStyle = content.includes('@tuniao/tn-style')
    if (!hasTuniaoStyle) {
      validationResult.appVue.errors.push('缺少图鸟 UI 样式引入 (@tuniao/tn-style)')
    }
    
    const hasTuniaoIcon = content.includes('@tuniao/tn-icon')
    if (!hasTuniaoIcon) {
      validationResult.appVue.errors.push('缺少图鸟 UI 图标引入 (@tuniao/tn-icon)')
    }
    
    // 检查引入路径是否正确
    if (hasTuniaoStyle) {
      const hasCorrectStylePath = content.includes('@tuniao/tn-style/dist/uniapp/index.css')
      if (!hasCorrectStylePath) {
        validationResult.appVue.warnings.push('图鸟 UI 样式路径可能不正确，建议使用: @tuniao/tn-style/dist/uniapp/index.css')
      }
    }
    
    if (hasTuniaoIcon) {
      const hasCorrectIconPath = content.includes('@tuniao/tn-icon/dist/index.css')
      if (!hasCorrectIconPath) {
        validationResult.appVue.warnings.push('图鸟 UI 图标路径可能不正确，建议使用: @tuniao/tn-icon/dist/index.css')
      }
    }
    
    // 检查是否还引入 uView UI 样式
    const hasUViewStyle = content.includes('vk-uview-ui') || content.includes('uview-ui')
    if (hasUViewStyle) {
      validationResult.appVue.errors.push('仍包含 uView UI 的样式引入，应该移除')
    }
    
    validationResult.appVue.passed = 
      hasTuniaoStyle && 
      hasTuniaoIcon && 
      !hasUViewStyle && 
      validationResult.appVue.errors.length === 0
    
  } catch (error) {
    validationResult.appVue.errors.push(`读取文件失败: ${error.message}`)
  }
}

// 验证 tsconfig.json
function validateTsconfig() {
  const filePath = path.join(__dirname, '../tsconfig.json')
  
  try {
    const content = fs.readFileSync(filePath, 'utf-8')
    
    // 检查是否包含图鸟 UI 的类型声明
    const hasTuniaoTypes = content.includes('@tuniao/tnui-vue3-uniapp')
    if (!hasTuniaoTypes) {
      validationResult.tsconfig.warnings.push('建议添加图鸟 UI 的类型声明路径')
    }
    
    // 检查 types 配置
    try {
      const config = JSON.parse(content.replace(/\/\/.*/g, '').replace(/\/\*[\s\S]*?\*\//g, ''))
      if (config.compilerOptions && config.compilerOptions.types) {
        const types = config.compilerOptions.types
        if (!types.includes('@tuniao/tnui-vue3-uniapp')) {
          validationResult.tsconfig.warnings.push('types 数组中未包含 @tuniao/tnui-vue3-uniapp')
        }
      }
    } catch (e) {
      // JSON 解析失败，跳过详细检查
    }
    
    validationResult.tsconfig.passed = validationResult.tsconfig.errors.length === 0
    
  } catch (error) {
    validationResult.tsconfig.errors.push(`读取文件失败: ${error.message}`)
  }
}

// 验证 package.json
function validatePackageJson() {
  const filePath = path.join(__dirname, '../package.json')
  
  try {
    const content = fs.readFileSync(filePath, 'utf-8')
    const packageJson = JSON.parse(content)
    
    // 检查是否包含图鸟 UI 依赖
    const deps = packageJson.dependencies || {}
    
    const hasTuniaoUI = deps['@tuniao/tnui-vue3-uniapp']
    if (!hasTuniaoUI) {
      validationResult.packageJson.errors.push('缺少 @tuniao/tnui-vue3-uniapp 依赖')
    }
    
    const hasTuniaoStyle = deps['@tuniao/tn-style']
    if (!hasTuniaoStyle) {
      validationResult.packageJson.errors.push('缺少 @tuniao/tn-style 依赖')
    }
    
    const hasTuniaoIcon = deps['@tuniao/tn-icon']
    if (!hasTuniaoIcon) {
      validationResult.packageJson.errors.push('缺少 @tuniao/tn-icon 依赖')
    }
    
    // 检查版本号
    if (hasTuniaoUI) {
      const version = deps['@tuniao/tnui-vue3-uniapp']
      console.log(`  图鸟 UI 版本: ${version}`)
    }
    
    // 检查是否还包含 uView UI 依赖
    const hasUViewDeps = deps['vk-uview-ui'] || deps['uview-ui']
    if (hasUViewDeps) {
      validationResult.packageJson.errors.push('仍包含 uView UI 依赖，应该移除')
    }
    
    // 检查脚本命令
    const scripts = packageJson.scripts || {}
    if (!scripts['validate:migration']) {
      validationResult.packageJson.warnings.push('建议添加 validate:migration 脚本命令')
    }
    if (!scripts['validate:config']) {
      validationResult.packageJson.warnings.push('建议添加 validate:config 脚本命令')
    }
    
    validationResult.packageJson.passed = 
      hasTuniaoUI && 
      hasTuniaoStyle && 
      hasTuniaoIcon && 
      !hasUViewDeps && 
      validationResult.packageJson.errors.length === 0
    
  } catch (error) {
    validationResult.packageJson.errors.push(`读取或解析文件失败: ${error.message}`)
  }
}

// 生成报告
function generateReport() {
  console.log('\n' + '='.repeat(60))
  console.log('配置文件验证报告')
  console.log('='.repeat(60))
  
  const files = [
    { name: 'pages.json', result: validationResult.pagesJson },
    { name: 'App.vue', result: validationResult.appVue },
    { name: 'tsconfig.json', result: validationResult.tsconfig },
    { name: 'package.json', result: validationResult.packageJson }
  ]
  
  files.forEach(({ name, result }) => {
    const status = result.passed ? '✓' : '✗'
    const color = result.passed ? '' : ''
    console.log(`\n${status} ${name}`)
    
    if (result.errors.length > 0) {
      console.log('  错误:')
      result.errors.forEach(error => {
        console.log(`    - ${error}`)
      })
    }
    
    if (result.warnings.length > 0) {
      console.log('  警告:')
      result.warnings.forEach(warning => {
        console.log(`    - ${warning}`)
      })
    }
    
    if (result.passed && result.errors.length === 0 && result.warnings.length === 0) {
      console.log('  配置正确')
    }
  })
  
  console.log('\n' + '='.repeat(60))
  
  const allPassed = files.every(f => f.result.passed)
  if (allPassed) {
    console.log('\n✓ 所有配置文件验证通过！')
  } else {
    console.log('\n✗ 部分配置文件存在问题，请修复')
  }
  
  return allPassed
}

// 主函数
function main() {
  console.log('开始验证配置文件...\n')
  
  validatePagesJson()
  validateAppVue()
  validateTsconfig()
  validatePackageJson()
  
  const success = generateReport()
  process.exit(success ? 0 : 1)
}

main()
