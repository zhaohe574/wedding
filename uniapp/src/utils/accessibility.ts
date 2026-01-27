/**
 * 无障碍辅助工具
 * 基于UI设计规划.md中的无障碍设计规范
 */

/**
 * 计算颜色的相对亮度
 * @param hex 十六进制颜色值
 * @returns 相对亮度值 (0-1)
 */
export function getRelativeLuminance(hex: string): number {
  // 移除#号
  const color = hex.replace('#', '')
  
  // 转换为RGB
  const r = parseInt(color.substring(0, 2), 16) / 255
  const g = parseInt(color.substring(2, 4), 16) / 255
  const b = parseInt(color.substring(4, 6), 16) / 255
  
  // 应用gamma校正
  const rsRGB = r <= 0.03928 ? r / 12.92 : Math.pow((r + 0.055) / 1.055, 2.4)
  const gsRGB = g <= 0.03928 ? g / 12.92 : Math.pow((g + 0.055) / 1.055, 2.4)
  const bsRGB = b <= 0.03928 ? b / 12.92 : Math.pow((b + 0.055) / 1.055, 2.4)
  
  // 计算相对亮度
  return 0.2126 * rsRGB + 0.7152 * gsRGB + 0.0722 * bsRGB
}

/**
 * 计算两个颜色之间的对比度
 * @param color1 第一个颜色（十六进制）
 * @param color2 第二个颜色（十六进制）
 * @returns 对比度值 (1-21)
 */
export function getContrastRatio(color1: string, color2: string): number {
  const l1 = getRelativeLuminance(color1)
  const l2 = getRelativeLuminance(color2)
  
  const lighter = Math.max(l1, l2)
  const darker = Math.min(l1, l2)
  
  return (lighter + 0.05) / (darker + 0.05)
}

/**
 * 检查对比度是否符合WCAG标准
 * @param color1 前景色
 * @param color2 背景色
 * @param level 'AA' | 'AAA'
 * @param isLargeText 是否为大号文字（>= 18pt 或 >= 14pt加粗）
 * @returns 是否符合标准
 */
export function checkContrastCompliance(
  color1: string,
  color2: string,
  level: 'AA' | 'AAA' = 'AA',
  isLargeText: boolean = false
): boolean {
  const ratio = getContrastRatio(color1, color2)
  
  if (level === 'AAA') {
    return isLargeText ? ratio >= 4.5 : ratio >= 7
  }
  
  // AA级别
  return isLargeText ? ratio >= 3 : ratio >= 4.5
}

/**
 * 检查触摸目标尺寸是否符合标准
 * @param width 宽度（rpx）
 * @param height 高度（rpx）
 * @param minSize 最小尺寸（rpx），默认88rpx
 * @returns 是否符合标准
 */
export function checkTouchTargetSize(
  width: number,
  height: number,
  minSize: number = 88
): boolean {
  return width >= minSize && height >= minSize
}

/**
 * 为图片生成alt文本建议
 * @param context 图片上下文信息
 * @returns alt文本建议
 */
export function generateAltText(context: {
  type?: 'avatar' | 'banner' | 'product' | 'icon' | 'decoration'
  name?: string
  description?: string
}): string {
  const { type, name, description } = context
  
  if (description) return description
  
  switch (type) {
    case 'avatar':
      return name ? `${name}的头像` : '用户头像'
    case 'banner':
      return name ? `${name}横幅图` : '横幅图'
    case 'product':
      return name ? `${name}产品图` : '产品图'
    case 'icon':
      return name ? `${name}图标` : '图标'
    case 'decoration':
      return '装饰图片'
    default:
      return name || '图片'
  }
}

/**
 * 检查是否启用了减少动画偏好
 * @returns 是否应该减少动画
 */
export function prefersReducedMotion(): boolean {
  // UniApp环境下，可以通过系统API检查
  // 这里提供一个基础实现
  try {
    // #ifdef H5
    if (typeof window !== 'undefined' && window.matchMedia) {
      return window.matchMedia('(prefers-reduced-motion: reduce)').matches
    }
    // #endif
    
    // 其他平台暂时返回false
    return false
  } catch (e) {
    return false
  }
}

/**
 * 为元素添加无障碍属性
 * @param element 元素配置
 * @returns 包含无障碍属性的对象
 */
export function addAriaAttributes(element: {
  role?: string
  label?: string
  describedBy?: string
  hidden?: boolean
  disabled?: boolean
  expanded?: boolean
  selected?: boolean
  checked?: boolean
}): Record<string, any> {
  const attrs: Record<string, any> = {}
  
  if (element.role) attrs['role'] = element.role
  if (element.label) attrs['aria-label'] = element.label
  if (element.describedBy) attrs['aria-describedby'] = element.describedBy
  if (element.hidden !== undefined) attrs['aria-hidden'] = element.hidden
  if (element.disabled !== undefined) attrs['aria-disabled'] = element.disabled
  if (element.expanded !== undefined) attrs['aria-expanded'] = element.expanded
  if (element.selected !== undefined) attrs['aria-selected'] = element.selected
  if (element.checked !== undefined) attrs['aria-checked'] = element.checked
  
  return attrs
}

/**
 * 验证表单输入的无障碍性
 * @param input 输入框配置
 * @returns 验证结果
 */
export function validateInputAccessibility(input: {
  hasLabel: boolean
  hasPlaceholder: boolean
  hasErrorMessage: boolean
  isRequired: boolean
}): {
  isValid: boolean
  issues: string[]
} {
  const issues: string[] = []
  
  if (!input.hasLabel) {
    issues.push('缺少标签（label）')
  }
  
  if (input.isRequired && !input.hasErrorMessage) {
    issues.push('必填项缺少错误提示')
  }
  
  if (!input.hasLabel && !input.hasPlaceholder) {
    issues.push('既没有标签也没有占位符，用户无法理解输入目的')
  }
  
  return {
    isValid: issues.length === 0,
    issues
  }
}

/**
 * 生成焦点样式
 * @param color 主题色
 * @returns 焦点样式对象
 */
export function generateFocusStyle(color: string = '#7C3AED'): Record<string, string> {
  return {
    outline: `4rpx solid ${color}`,
    outlineOffset: '4rpx'
  }
}
