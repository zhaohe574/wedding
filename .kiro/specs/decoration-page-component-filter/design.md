# 设计文档

## 概述

本设计文档描述了装修页面组件按页面类型过滤功能的技术实现方案。该功能通过在组件配置中添加页面范围属性，并在组件选择器中实现过滤逻辑，使得每个页面只显示适合该页面的组件。设计遵循最小侵入性原则，确保向后兼容性，无需数据库迁移。

## 架构

### 系统架构

```
┌─────────────────────────────────────────────────────────────┐
│                     装修系统前端                              │
├─────────────────────────────────────────────────────────────┤
│                                                               │
│  ┌──────────────┐      ┌──────────────┐      ┌───────────┐ │
│  │  页面编辑器   │─────▶│  组件选择器   │◀────│ 组件配置  │ │
│  │ (pages/index)│      │  (preview)   │      │ (widgets) │ │
│  └──────────────┘      └──────────────┘      └───────────┘ │
│         │                      │                     │       │
│         │                      │                     │       │
│         ▼                      ▼                     ▼       │
│  ┌──────────────────────────────────────────────────────┐  │
│  │            组件过滤逻辑 (新增)                        │  │
│  │  - 读取当前页面类型                                   │  │
│  │  - 读取组件pageScope配置                             │  │
│  │  - 过滤并返回可用组件列表                             │  │
│  └──────────────────────────────────────────────────────┘  │
│                                                               │
└─────────────────────────────────────────────────────────────┘
```

### 数据流

```
用户点击"添加组件"
    │
    ▼
获取当前页面类型 (activeMenu)
    │
    ▼
遍历所有可用组件
    │
    ▼
检查组件的pageScope配置
    │
    ├─ pageScope未定义 ──▶ 通用组件 ──▶ 显示
    │
    ├─ pageScope包含当前页面类型 ──▶ 显示
    │
    └─ pageScope不包含当前页面类型 ──▶ 隐藏
    │
    ▼
渲染过滤后的组件列表
```

## 组件和接口

### 1. 组件配置接口扩展

#### 位置
`admin/src/views/decoration/component/widgets/*/options.ts`

#### 接口定义

```typescript
// 页面类型枚举
export type PageType = 'home' | 'user' | 'service'

// 组件配置选项接口（扩展）
export interface WidgetOptions {
    title: string           // 组件标题
    name: string           // 组件名称
    content: any           // 组件内容配置
    styles: Record<string, any>  // 组件样式配置
    disabled?: 0 | 1       // 是否禁用（可选）
    pageScope?: PageType[] // 页面范围（新增，可选）
}
```

#### 使用示例

```typescript
// 首页专属组件
export default (): WidgetOptions => ({
    title: '首页轮播图',
    name: 'banner',
    pageScope: ['home'], // 仅在首页显示
    content: {
        enabled: 1,
        data: []
    },
    styles: {}
})

// 通用组件（未定义pageScope）
export default (): WidgetOptions => ({
    title: '客服设置',
    name: 'customer-service',
    // pageScope未定义，可在所有页面使用
    content: {},
    styles: {}
})

// 多页面组件
export default (): WidgetOptions => ({
    title: '轮播图',
    name: 'banner',
    pageScope: ['home', 'user'], // 可在首页和个人中心使用
    content: {},
    styles: {}
})
```

### 2. 组件选择器过滤逻辑

#### 位置
`admin/src/views/decoration/component/pages/preview.vue`

#### 计算属性实现

```typescript
// 当前页面类型映射
const pageTypeMap: Record<string, PageType> = {
    '1': 'home',    // 首页
    '2': 'user',    // 个人中心
    '3': 'service'  // 客服设置
}

// 获取当前页面类型
const currentPageType = computed<PageType>(() => {
    return pageTypeMap[activeMenu.value] || 'home'
})

// 过滤可用组件
const filteredAvailableWidgets = computed(() => {
    const currentType = currentPageType.value
    
    return availableWidgets.filter(widget => {
        const widgetConfig = widgets[widget.name]
        const pageScope = widgetConfig?.options?.()?.pageScope
        
        // 如果未定义pageScope，视为通用组件
        if (!pageScope || !Array.isArray(pageScope)) {
            return true
        }
        
        // 检查当前页面类型是否在pageScope中
        return pageScope.includes(currentType)
    })
})
```

#### 组件选择器渲染

```vue
<template>
    <el-dialog v-model="showWidgetSelector" title="添加组件" width="600px">
        <div v-if="filteredAvailableWidgets.length > 0" class="grid grid-cols-3 gap-4">
            <div
                v-for="item in filteredAvailableWidgets"
                :key="item.name"
                class="p-4 border rounded-lg cursor-pointer hover:border-primary hover:bg-blue-50 transition-colors"
                @click="handleAddWidget(item)"
            >
                <!-- 组件卡片内容 -->
            </div>
        </div>
        <div v-else class="text-center py-8 text-gray-400">
            暂无可添加的组件
        </div>
    </el-dialog>
</template>
```

### 3. 页面编辑器集成

#### 位置
`admin/src/views/decoration/pages/index.vue`

#### Props传递

```typescript
// 将activeMenu传递给Preview组件
<preview
    class="flex-1 scroll-view-content"
    v-model="selectWidgetIndex"
    :activeMenu="activeMenu"
    @updatePageData="updatePageData"
    :pageData="getPageData"
    :pageMeta="getPageMeta"
/>
```

## 数据模型

### 组件配置数据结构

```typescript
// 单个组件实例
interface WidgetInstance {
    id: string              // 唯一标识
    title: string           // 组件标题
    name: string            // 组件名称
    content: any            // 组件内容
    styles: any             // 组件样式
    disabled?: 0 | 1        // 是否禁用
    pageScope?: PageType[]  // 页面范围（配置时使用）
}

// 页面配置
interface PageConfig {
    id: number              // 页面ID
    type: number            // 页面类型
    name: string            // 页面名称
    pageMeta?: any          // 页面元数据
    pageData: WidgetInstance[]  // 组件列表
}
```

### 组件分类配置

```typescript
// 首页专属组件
const homeWidgets = [
    'search',           // 搜索框
    'banner',           // 轮播图
    'nav',              // 导航菜单
    'middle-banner',    // 中部轮播图
    'staff-showcase',   // 人员推荐
    'service-packages', // 服务套餐
    'portfolio-gallery',// 案例作品
    'customer-reviews', // 客户评价
    'activity-zone',    // 活动专区
    'order-quick-entry',// 订单快捷入口
    'news'              // 最新资讯
]

// 个人中心专属组件
const userWidgets = [
    'user-info',        // 用户信息
    'my-service',       // 我的服务
    'user-banner'       // 用户轮播图
]

// 客服设置专属组件
const serviceWidgets = [
    'customer-service'  // 客服设置
]

// 通用组件（未来可能添加）
const universalWidgets = [
    // 暂无
]
```

## 正确性属性

*属性是一个特征或行为，应该在系统的所有有效执行中保持为真。属性作为人类可读规范和机器可验证正确性保证之间的桥梁。*

### 属性前置工作

#### 1.1 WHEN 定义组件配置时 THEN THE Decoration_System SHALL 支持在组件options中添加pageScope属性
思考：这是关于类型系统的要求，TypeScript接口定义应该允许pageScope属性。我们可以通过类型检查来验证。
可测试性：yes - example

#### 1.2 WHEN pageScope属性未定义时 THEN THE Decoration_System SHALL 将该组件视为Universal_Widget
思考：这是关于默认行为的规则，应该对所有未定义pageScope的组件成立。我们可以生成随机组件配置（不包含pageScope），验证它们在所有页面类型中都可见。
可测试性：yes - property

#### 1.3 WHEN pageScope属性定义为数组时 THEN THE Decoration_System SHALL 仅在指定的Page_Type中显示该组件
思考：这是核心过滤逻辑，应该对所有定义了pageScope的组件成立。我们可以生成随机的pageScope配置和页面类型，验证过滤结果的正确性。
可测试性：yes - property

#### 1.4 THE Decoration_System SHALL 支持以下Page_Type值: 'home'(首页), 'user'(个人中心), 'service'(客服设置)
思考：这是关于支持的页面类型的规则。我们可以验证系统能够正确处理这三种页面类型。
可测试性：yes - example

#### 2.1 WHEN 管理员在首页装修中点击"添加组件"按钮 THEN THE Widget_Selector SHALL 仅显示pageScope包含'home'的组件和Universal_Widget
思考：这是关于首页过滤的具体规则。我们可以生成随机组件列表，验证过滤后只包含home组件和通用组件。
可测试性：yes - property

#### 2.2 WHEN 管理员在个人中心装修中点击"添加组件"按钮 THEN THE Widget_Selector SHALL 仅显示pageScope包含'user'的组件和Universal_Widget
思考：这是关于个人中心过滤的具体规则。与2.1类似，可以用属性测试验证。
可测试性：yes - property

#### 2.3 WHEN 管理员在客服设置中点击"添加组件"按钮 THEN THE Widget_Selector SHALL 仅显示pageScope包含'service'的组件和Universal_Widget
思考：这是关于客服设置过滤的具体规则。与2.1、2.2类似，可以用属性测试验证。
可测试性：yes - property

#### 2.4 WHEN 组件的pageScope包含多个Page_Type THEN THE Widget_Selector SHALL 在所有指定的页面类型中显示该组件
思考：这是关于多页面组件的规则。我们可以生成包含多个页面类型的pageScope，验证组件在所有指定页面中都可见。
可测试性：yes - property

#### 3.1-3.4 组件分类配置
思考：这些是关于具体组件配置的要求，不是通用规则。我们应该通过单元测试验证每个组件的配置是否正确。
可测试性：yes - example

#### 4.1 WHEN 系统升级后 THEN THE Decoration_System SHALL 保持现有页面的装修配置不变
思考：这是关于数据持久性的要求。由于我们不修改数据结构，这是自动满足的。
可测试性：no

#### 4.2 WHEN 已添加的组件不在当前页面的pageScope中 THEN THE Decoration_System SHALL 仍然正常显示和编辑该组件
思考：这是关于向后兼容性的规则。已添加的组件应该不受pageScope限制。这是关于渲染逻辑的，不是过滤逻辑。
可测试性：no

#### 4.3 WHEN 管理员编辑现有页面时 THEN THE Widget_Selector SHALL 根据新的过滤规则显示可添加的组件
思考：这是2.1-2.3的重复，已经被覆盖。
可测试性：no

#### 4.4 THE Decoration_System SHALL 不需要数据库迁移或配置更新即可启用新功能
思考：这是关于实现方式的要求，不是功能性要求。
可测试性：no

#### 5.1 WHEN 组件选择器显示时 THEN THE Widget_Selector SHALL 仅显示适用于当前页面的组件
思考：这是2.1-2.3的重复，已经被覆盖。
可测试性：no

#### 5.2 WHEN 组件已添加到当前页面时 THEN THE Widget_Selector SHALL 显示"已添加"状态并禁用点击
思考：这是现有功能，不是新增功能。
可测试性：no

#### 5.3 WHEN 组件选择器为空时 THEN THE Widget_Selector SHALL 显示"暂无可添加的组件"提示
思考：这是UI显示逻辑，可以通过示例测试验证。
可测试性：yes - example

#### 5.4 THE Widget_Selector SHALL 保持现有的网格布局和交互方式
思考：这是UI设计要求，不是功能性要求。
可测试性：no

### 属性反思

审查所有可测试属性，识别冗余：

1. **属性1.2, 2.1, 2.2, 2.3** 可以合并为一个综合属性：对于任意页面类型和组件列表，过滤后的组件应该只包含pageScope包含该页面类型的组件和未定义pageScope的组件。

2. **属性2.4** 是属性1.3的特例，可以被1.3覆盖。

3. **属性1.3** 和合并后的属性1可以进一步合并为一个核心过滤属性。

### 正确性属性

#### 属性 1: 组件过滤正确性
*对于任意* 页面类型和组件列表，过滤后的组件集合应该恰好包含：(1) pageScope包含该页面类型的组件，以及 (2) pageScope未定义的组件（通用组件）。

**验证: 需求 1.2, 1.3, 2.1, 2.2, 2.3, 2.4**

#### 属性 2: 通用组件可见性
*对于任意* 未定义pageScope的组件，该组件应该在所有页面类型中都可见。

**验证: 需求 1.2**

#### 属性 3: 页面类型支持
*对于任意* 页面类型值 ('home', 'user', 'service')，系统应该能够正确映射到对应的页面ID并执行过滤。

**验证: 需求 1.4**

## 错误处理

### 1. 无效的pageScope配置

**场景**: 组件配置中的pageScope不是数组或包含无效的页面类型

**处理策略**:
- 如果pageScope不是数组，将组件视为通用组件
- 如果pageScope包含无效的页面类型，忽略无效值，只使用有效值
- 在开发环境中输出警告日志

```typescript
function isValidPageScope(pageScope: any): pageScope is PageType[] {
    if (!Array.isArray(pageScope)) {
        console.warn('Invalid pageScope: not an array', pageScope)
        return false
    }
    
    const validTypes: PageType[] = ['home', 'user', 'service']
    const hasInvalidType = pageScope.some(type => !validTypes.includes(type))
    
    if (hasInvalidType) {
        console.warn('Invalid pageScope: contains invalid page type', pageScope)
    }
    
    return true
}
```

### 2. 组件配置缺失

**场景**: 尝试添加的组件在widgets中不存在

**处理策略**:
- 在控制台输出错误信息
- 阻止添加操作
- 向用户显示友好的错误提示

```typescript
const handleAddWidget = (widgetInfo: any) => {
    const widgetConfig = widgets[widgetInfo.name]
    
    if (!widgetConfig || !widgetConfig.options) {
        console.error('组件配置不存在:', widgetInfo.name)
        ElMessage.error('组件配置错误，无法添加')
        return
    }
    
    // 继续添加逻辑...
}
```

### 3. 页面类型映射失败

**场景**: activeMenu的值无法映射到有效的页面类型

**处理策略**:
- 默认使用'home'作为fallback
- 在控制台输出警告信息

```typescript
const currentPageType = computed<PageType>(() => {
    const pageType = pageTypeMap[activeMenu.value]
    
    if (!pageType) {
        console.warn('Unknown page type for activeMenu:', activeMenu.value)
        return 'home' // fallback
    }
    
    return pageType
})
```

### 4. 空组件列表

**场景**: 过滤后没有可用组件

**处理策略**:
- 显示"暂无可添加的组件"提示
- 提供关闭弹窗的选项
- 不影响现有组件的编辑

```vue
<div v-if="filteredAvailableWidgets.length === 0" class="text-center py-8">
    <el-empty description="暂无可添加的组件" />
</div>
```

## 测试策略

### 单元测试

#### 1. 组件配置测试
- 验证所有组件的pageScope配置正确
- 验证首页专属组件的pageScope为['home']
- 验证个人中心专属组件的pageScope为['user']
- 验证客服设置专属组件的pageScope为['service']
- 验证通用组件的pageScope未定义

#### 2. 过滤逻辑测试
- 测试首页过滤：只显示home组件和通用组件
- 测试个人中心过滤：只显示user组件和通用组件
- 测试客服设置过滤：只显示service组件和通用组件
- 测试多页面组件：在所有指定页面中显示
- 测试空组件列表：正确显示提示信息

#### 3. 边界条件测试
- 测试pageScope为空数组：视为通用组件
- 测试pageScope包含无效值：忽略无效值
- 测试pageScope不是数组：视为通用组件
- 测试未知页面类型：使用fallback

### 属性测试

#### 属性测试 1: 组件过滤正确性
```typescript
// 伪代码
property('组件过滤正确性', () => {
    forAll(
        arbitrary.pageType(), // 生成随机页面类型
        arbitrary.widgetList(), // 生成随机组件列表
        (pageType, widgets) => {
            const filtered = filterWidgets(widgets, pageType)
            
            // 验证：所有过滤后的组件要么pageScope包含pageType，要么pageScope未定义
            return filtered.every(widget => {
                const pageScope = widget.pageScope
                return !pageScope || pageScope.includes(pageType)
            })
        }
    )
})
```

#### 属性测试 2: 通用组件可见性
```typescript
// 伪代码
property('通用组件可见性', () => {
    forAll(
        arbitrary.universalWidget(), // 生成未定义pageScope的组件
        arbitrary.pageType(), // 生成随机页面类型
        (widget, pageType) => {
            const filtered = filterWidgets([widget], pageType)
            
            // 验证：通用组件在所有页面类型中都可见
            return filtered.includes(widget)
        }
    )
})
```

### 集成测试

#### 1. 端到端测试
- 测试完整的添加组件流程
- 测试切换页面类型后组件列表更新
- 测试已添加组件的显示和编辑
- 测试保存和加载装修配置

#### 2. 兼容性测试
- 测试现有装修配置的加载
- 测试升级后的功能正常工作
- 测试不同浏览器的兼容性

### 测试配置

- 单元测试使用Vitest框架
- 属性测试使用fast-check库
- 每个属性测试至少运行100次迭代
- 测试标签格式：**Feature: decoration-page-component-filter, Property {number}: {property_text}**
