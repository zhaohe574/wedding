/**
 * uView UI 到图鸟 UI 批量迁移脚本
 * 使用方法: node scripts/migrate-to-tuniao.js
 */

const fs = require('fs')
const path = require('path')

// 组件映射规则
const componentMappings = [
  // 基础组件
  { from: /<u-icon/g, to: '<tn-icon', description: '图标组件' },
  { from: /<\/u-icon>/g, to: '</tn-icon>', description: '图标组件闭合' },
  { from: /<u-button/g, to: '<tn-button', description: '按钮组件' },
  { from: /<\/u-button>/g, to: '</tn-button>', description: '按钮组件闭合' },
  { from: /<u-avatar/g, to: '<tn-avatar', description: '头像组件' },
  { from: /<\/u-avatar>/g, to: '</tn-avatar>', description: '头像组件闭合' },
  { from: /<u-image/g, to: '<image', description: '图片组件（使用原生）' },
  { from: /<\/u-image>/g, to: '</image>', description: '图片组件闭合（使用原生）' },
  { from: /<u-badge/g, to: '<tn-badge', description: '徽标组件' },
  { from: /<\/u-badge>/g, to: '</tn-badge>', description: '徽标组件闭合' },
  { from: /<u-tag/g, to: '<tn-tag', description: '标签组件' },
  { from: /<\/u-tag>/g, to: '</tn-tag>', description: '标签组件闭合' },
  
  // 表单组件
  { from: /<u-input/g, to: '<tn-input', description: '输入框组件' },
  { from: /<\/u-input>/g, to: '</tn-input>', description: '输入框组件闭合' },
  { from: /<u-search/g, to: '<tn-search-box', description: '搜索框组件' },
  { from: /<\/u-search>/g, to: '</tn-search-box>', description: '搜索框组件闭合' },
  { from: /<u-checkbox-group/g, to: '<tn-checkbox-group', description: '复选框组' },
  { from: /<\/u-checkbox-group>/g, to: '</tn-checkbox-group>', description: '复选框组闭合' },
  { from: /<u-checkbox/g, to: '<tn-checkbox', description: '复选框组件' },
  { from: /<\/u-checkbox>/g, to: '</tn-checkbox>', description: '复选框组件闭合' },
  { from: /<u-radio-group/g, to: '<tn-radio-group', description: '单选框组' },
  { from: /<\/u-radio-group>/g, to: '</tn-radio-group>', description: '单选框组闭合' },
  { from: /<u-radio/g, to: '<tn-radio', description: '单选框组件' },
  { from: /<\/u-radio>/g, to: '</tn-radio>', description: '单选框组件闭合' },
  { from: /<u-switch/g, to: '<tn-switch', description: '开关组件' },
  { from: /<\/u-switch>/g, to: '</tn-switch>', description: '开关组件闭合' },
  { from: /<u-picker/g, to: '<tn-picker', description: '选择器组件' },
  { from: /<\/u-picker>/g, to: '</tn-picker>', description: '选择器组件闭合' },
  { from: /<u-textarea/g, to: '<tn-textarea', description: '文本域组件' },
  { from: /<\/u-textarea>/g, to: '</tn-textarea>', description: '文本域组件闭合' },
  
  // 反馈组件
  { from: /<u-popup/g, to: '<tn-popup', description: '弹窗组件' },
  { from: /<\/u-popup>/g, to: '</tn-popup>', description: '弹窗组件闭合' },
  { from: /<u-modal/g, to: '<tn-modal', description: '模态框组件' },
  { from: /<\/u-modal>/g, to: '</tn-modal>', description: '模态框组件闭合' },
  { from: /<u-action-sheet/g, to: '<tn-action-sheet', description: '操作菜单组件' },
  { from: /<\/u-action-sheet>/g, to: '</tn-action-sheet>', description: '操作菜单组件闭合' },
  { from: /<u-loading/g, to: '<tn-loading', description: '加载组件' },
  { from: /<\/u-loading>/g, to: '</tn-loading>', description: '加载组件闭合' },
  { from: /<u-empty/g, to: '<tn-empty', description: '空状态组件' },
  { from: /<\/u-empty>/g, to: '</tn-empty>', description: '空状态组件闭合' },
  
  // 导航组件
  { from: /<u-navbar/g, to: '<tn-navbar', description: '导航栏组件' },
  { from: /<\/u-navbar>/g, to: '</tn-navbar>', description: '导航栏组件闭合' },
  { from: /<u-sticky/g, to: '<tn-sticky', description: '吸顶组件' },
  { from: /<\/u-sticky>/g, to: '</tn-sticky>', description: '吸顶组件闭合' },
  { from: /<u-notice-bar/g, to: '<tn-notice-bar', description: '公告栏组件' },
  { from: /<\/u-notice-bar>/g, to: '</tn-notice-bar>', description: '公告栏组件闭合' },
  { from: /<u-tabs/g, to: '<tn-tabs', description: '标签页组件' },
  { from: /<\/u-tabs>/g, to: '</tn-tabs>', description: '标签页组件闭合' },
  { from: /<u-collapse-item/g, to: '<tn-collapse-item', description: '折叠面板项组件' },
  { from: /<\/u-collapse-item>/g, to: '</tn-collapse-item>', description: '折叠面板项组件闭合' },
  { from: /<u-collapse/g, to: '<tn-collapse', description: '折叠面板组件' },
  { from: /<\/u-collapse>/g, to: '</tn-collapse>', description: '折叠面板组件闭合' },
  
  // 布局组件
  { from: /<u-row/g, to: '<tn-row', description: '行组件' },
  { from: /<\/u-row>/g, to: '</tn-row>', description: '行组件闭合' },
  { from: /<u-col/g, to: '<tn-col', description: '列组件' },
  { from: /<\/u-col>/g, to: '</tn-col>', description: '列组件闭合' },
  { from: /<u-grid/g, to: '<tn-grid', description: '宫格组件' },
  { from: /<\/u-grid>/g, to: '</tn-grid>', description: '宫格组件闭合' },
  { from: /<u-grid-item/g, to: '<tn-grid-item', description: '宫格项组件' },
  { from: /<\/u-grid-item>/g, to: '</tn-grid-item>', description: '宫格项组件闭合' },
  
  // 数据展示组件
  { from: /<u-swiper/g, to: '<tn-swiper', description: '轮播组件' },
  { from: /<\/u-swiper>/g, to: '</tn-swiper>', description: '轮播组件闭合' },
  { from: /<u-swiper-item/g, to: '<tn-swiper-item', description: '轮播项组件' },
  { from: /<\/u-swiper-item>/g, to: '</tn-swiper-item>', description: '轮播项组件闭合' },
  { from: /<u-card/g, to: '<tn-card', description: '卡片组件' },
  { from: /<\/u-card>/g, to: '</tn-card>', description: '卡片组件闭合' },
  { from: /<u-list/g, to: '<tn-list', description: '列表组件' },
  { from: /<\/u-list>/g, to: '</tn-list>', description: '列表组件闭合' },
  { from: /<u-list-item/g, to: '<tn-list-item', description: '列表项组件' },
  { from: /<\/u-list-item>/g, to: '</tn-list-item>', description: '列表项组件闭合' },
]

// 属性映射规则
const attributeMappings = [
  // 图标名称映射
  { from: /name="arrow-right"/g, to: 'name="right"', description: '右箭头图标' },
  { from: /name="arrow-left"/g, to: 'name="left"', description: '左箭头图标' },
  { from: /name="arrow-up"/g, to: 'name="up"', description: '上箭头图标' },
  { from: /name="arrow-down"/g, to: 'name="down"', description: '下箭头图标' },
  { from: /name="arrow-down-fill"/g, to: 'name="down-fill"', description: '下箭头填充图标' },
  { from: /name="arrow-up-fill"/g, to: 'name="up-fill"', description: '上箭头填充图标' },
  { from: /name="arrow-left-fill"/g, to: 'name="left-fill"', description: '左箭头填充图标' },
  { from: /name="arrow-right-fill"/g, to: 'name="right-fill"', description: '右箭头填充图标' },
  
  // 按钮形状映射
  { from: /shape="circle"/g, to: 'shape="round"', description: '圆形按钮' },
  
  // 尺寸映射
  { from: /size="mini"/g, to: 'size="sm"', description: '小尺寸' },
  { from: /size="medium"/g, to: 'size="md"', description: '中尺寸' },
  { from: /size="large"/g, to: 'size="lg"', description: '大尺寸' },
  
  // 属性命名转换（驼峰转短横线）
  { from: /:customStyle=/g, to: ':custom-style=', description: '自定义样式属性' },
  { from: /customStyle=/g, to: 'custom-style=', description: '自定义样式属性（非绑定）' },
  { from: /:maskCloseAble=/g, to: ':mask-close-able=', description: '遮罩可关闭属性' },
  { from: /maskCloseAble=/g, to: 'mask-close-able=', description: '遮罩可关闭属性（非绑定）' },
  { from: /:closeable=/g, to: ':close-btn=', description: '可关闭属性' },
  { from: /closeable=/g, to: 'close-btn=', description: '可关闭属性（非绑定）' },
  { from: /:borderRadius=/g, to: ':border-radius=', description: '边框圆角属性' },
  { from: /borderRadius=/g, to: 'border-radius=', description: '边框圆角属性（非绑定）' },
  
  // action-sheet 特殊处理
  { from: /:list=/g, to: ':data=', description: 'action-sheet 数据属性' },
  
  // 其他常见驼峰属性转换
  { from: /:bgColor=/g, to: ':bg-color=', description: '背景色属性' },
  { from: /bgColor=/g, to: 'bg-color=', description: '背景色属性（非绑定）' },
  { from: /:textColor=/g, to: ':text-color=', description: '文字颜色属性' },
  { from: /textColor=/g, to: 'text-color=', description: '文字颜色属性（非绑定）' },
]

// 需要排除的目录
const excludeDirs = ['node_modules', 'uni_modules', '.git', 'dist', '.kiro']

// 统计信息
const stats = {
  totalFiles: 0,
  modifiedFiles: 0,
  componentReplacements: 0,
  attributeReplacements: 0,
  errors: []
}

// 递归处理目录
function processDirectory(dir) {
  try {
    const files = fs.readdirSync(dir)
    
    files.forEach(file => {
      const filePath = path.join(dir, file)
      try {
        const stat = fs.statSync(filePath)
        
        if (stat.isDirectory()) {
          if (!excludeDirs.includes(file)) {
            processDirectory(filePath)
          }
        } else if (file.endsWith('.vue')) {
          stats.totalFiles++
          processFile(filePath)
        }
      } catch (error) {
        stats.errors.push({ file: filePath, error: error.message })
      }
    })
  } catch (error) {
    stats.errors.push({ file: dir, error: error.message })
  }
}

// 处理单个文件
function processFile(filePath) {
  try {
    let content = fs.readFileSync(filePath, 'utf-8')
    let modified = false
    let componentCount = 0
    let attributeCount = 0
    
    // 应用组件映射
    componentMappings.forEach(({ from, to, description }) => {
      const matches = content.match(from)
      if (matches) {
        content = content.replace(from, to)
        modified = true
        componentCount += matches.length
      }
    })
    
    // 应用属性映射
    attributeMappings.forEach(({ from, to, description }) => {
      const matches = content.match(from)
      if (matches) {
        content = content.replace(from, to)
        modified = true
        attributeCount += matches.length
      }
    })
    
    if (modified) {
      fs.writeFileSync(filePath, content, 'utf-8')
      stats.modifiedFiles++
      stats.componentReplacements += componentCount
      stats.attributeReplacements += attributeCount
      console.log(`✓ 已处理: ${filePath}`)
      if (componentCount > 0) {
        console.log(`  - 组件替换: ${componentCount} 处`)
      }
      if (attributeCount > 0) {
        console.log(`  - 属性替换: ${attributeCount} 处`)
      }
    }
  } catch (error) {
    stats.errors.push({ file: filePath, error: error.message })
    console.error(`✗ 处理失败: ${filePath}`)
    console.error(`  错误: ${error.message}`)
  }
}

// 生成报告
function generateReport() {
  console.log('\n' + '='.repeat(60))
  console.log('迁移报告')
  console.log('='.repeat(60))
  console.log(`总文件数: ${stats.totalFiles}`)
  console.log(`已修改文件: ${stats.modifiedFiles}`)
  console.log(`未修改文件: ${stats.totalFiles - stats.modifiedFiles}`)
  console.log(`组件替换总数: ${stats.componentReplacements}`)
  console.log(`属性替换总数: ${stats.attributeReplacements}`)
  
  if (stats.errors.length > 0) {
    console.log(`\n错误数: ${stats.errors.length}`)
    console.log('\n错误详情:')
    stats.errors.forEach((error, index) => {
      console.log(`${index + 1}. ${error.file}`)
      console.log(`   ${error.error}`)
    })
  }
  
  console.log('\n' + '='.repeat(60))
  console.log('\n注意事项：')
  console.log('1. 请手动检查 u-form-item 组件，图鸟 UI 使用方式不同')
  console.log('2. 请手动检查 u-verification-code 组件，需要自定义实现')
  console.log('3. 请手动检查 u-parse 组件，可使用 rich-text 替代')
  console.log('4. u-back-top 组件图鸟 UI 无对应组件，建议保留')
  console.log('5. 建议运行验证脚本检查迁移结果')
}

// 主函数
function main() {
  const srcDir = path.join(__dirname, '../src')
  const packagesDir = path.join(__dirname, '../packages')
  
  console.log('开始迁移 uView UI 到图鸟 UI...\n')
  console.log('处理 src 目录...')
  processDirectory(srcDir)
  
  // 如果 packages 目录存在，也处理它
  if (fs.existsSync(packagesDir)) {
    console.log('\n处理 packages 目录...')
    processDirectory(packagesDir)
  }
  
  generateReport()
}

main()
