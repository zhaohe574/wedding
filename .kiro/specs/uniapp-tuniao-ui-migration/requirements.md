# 需求文档 - UniApp 图鸟UI库全面迁移

## 简介

本项目旨在将 UniApp 移动端应用从 uView UI 组件库完全迁移到图鸟 UI (TuniaoUI) 组件库。这是一个全面的UI重构项目，涉及所有页面、组件和样式的系统性替换和优化。

## 术语表

- **UniApp**: 基于 Vue 3 的跨平台应用开发框架
- **uView_UI**: 原有的 UI 组件库（vk-uview-ui）
- **TuniaoUI**: 目标 UI 组件库（图鸟UI）
- **Component_Migration**: 组件迁移过程，包括标签替换、属性映射和功能验证
- **Page_File**: 位于 `src/pages/` 目录下的页面文件
- **Component_File**: 位于 `src/components/` 目录下的组件文件
- **Package_File**: 位于 `packages/` 目录下的包文件
- **Easycom**: UniApp 的组件自动引入机制

## 需求

### 需求 1: 基础组件迁移

**用户故事**: 作为开发者，我希望将所有基础UI组件从 uView UI 迁移到图鸟 UI，以便使用更现代化的组件库。

#### 验收标准

1. WHEN 页面中使用图标组件 THEN THE System SHALL 将 `<u-icon>` 替换为 `<tn-icon>` 并更新图标名称映射
2. WHEN 页面中使用按钮组件 THEN THE System SHALL 将 `<u-button>` 替换为 `<tn-button>` 并更新属性映射
3. WHEN 页面中使用头像组件 THEN THE System SHALL 将 `<u-avatar>` 替换为 `<tn-avatar>`
4. WHEN 页面中使用图片组件 THEN THE System SHALL 将 `<u-image>` 替换为 `<tn-image>`
5. WHEN 页面中使用徽标组件 THEN THE System SHALL 将 `<u-badge>` 替换为 `<tn-badge>`
6. WHEN 页面中使用标签组件 THEN THE System SHALL 将 `<u-tag>` 替换为 `<tn-tag>`

### 需求 2: 表单组件迁移

**用户故事**: 作为开发者，我希望将所有表单相关组件迁移到图鸟 UI，以保持表单功能的完整性。

#### 验收标准

1. WHEN 页面中使用输入框组件 THEN THE System SHALL 将 `<u-input>` 替换为 `<tn-input>` 并保持双向绑定
2. WHEN 页面中使用搜索框组件 THEN THE System SHALL 将 `<u-search>` 替换为 `<tn-search-box>`
3. WHEN 页面中使用复选框组件 THEN THE System SHALL 将 `<u-checkbox>` 替换为 `<tn-checkbox>`
4. WHEN 页面中使用单选框组件 THEN THE System SHALL 将 `<u-radio>` 替换为 `<tn-radio>`
5. WHEN 页面中使用开关组件 THEN THE System SHALL 将 `<u-switch>` 替换为 `<tn-switch>`
6. WHEN 页面中使用选择器组件 THEN THE System SHALL 将 `<u-picker>` 替换为 `<tn-picker>`
7. WHEN 页面中使用表单项组件 THEN THE System SHALL 使用自定义样式替换 `<u-form-item>`
8. WHEN 页面中使用验证码组件 THEN THE System SHALL 使用自定义倒计时逻辑替换 `<u-verification-code>`

### 需求 3: 反馈组件迁移

**用户故事**: 作为开发者，我希望将所有反馈类组件迁移到图鸟 UI，以保持用户交互体验的一致性。

#### 验收标准

1. WHEN 页面中使用弹窗组件 THEN THE System SHALL 将 `<u-popup>` 替换为 `<tn-popup>` 并更新属性名称
2. WHEN 页面中使用模态框组件 THEN THE System SHALL 将 `<u-modal>` 替换为 `<tn-modal>`
3. WHEN 页面中使用操作菜单组件 THEN THE System SHALL 将 `<u-action-sheet>` 替换为 `<tn-action-sheet>` 并将 `:list` 改为 `:data`
4. WHEN 页面中使用加载组件 THEN THE System SHALL 将 `<u-loading>` 替换为 `<tn-loading>`
5. WHEN 页面中使用空状态组件 THEN THE System SHALL 将 `<u-empty>` 替换为 `<tn-empty>`
6. WHEN 页面中使用提示组件 THEN THE System SHALL 保持使用 `uni.showToast` 原生API

### 需求 4: 导航组件迁移

**用户故事**: 作为开发者，我希望将所有导航相关组件迁移到图鸟 UI，以保持页面导航的功能完整。

#### 验收标准

1. WHEN 页面中使用导航栏组件 THEN THE System SHALL 将 `<u-navbar>` 替换为 `<tn-navbar>`
2. WHEN 页面中使用吸顶组件 THEN THE System SHALL 将 `<u-sticky>` 替换为 `<tn-sticky>`
3. WHEN 页面中使用公告栏组件 THEN THE System SHALL 将 `<u-notice-bar>` 替换为 `<tn-notice-bar>`
4. WHEN 页面中使用返回顶部组件 THEN THE System SHALL 保留 `<u-back-top>` 组件（图鸟UI无对应组件）

### 需求 5: 属性和命名规范迁移

**用户故事**: 作为开发者，我希望所有组件属性符合图鸟 UI 的命名规范，以确保组件正常工作。

#### 验收标准

1. WHEN 组件使用驼峰命名属性 THEN THE System SHALL 将其转换为短横线命名（如 `customStyle` → `custom-style`）
2. WHEN 按钮使用 `shape="circle"` THEN THE System SHALL 将其改为 `shape="round"`
3. WHEN 组件使用 `size="mini"` THEN THE System SHALL 将其改为 `size="sm"`
4. WHEN 组件使用 `size="medium"` THEN THE System SHALL 将其改为 `size="md"`
5. WHEN 组件使用 `size="large"` THEN THE System SHALL 将其改为 `size="lg"`
6. WHEN 弹窗使用 `:closeable` THEN THE System SHALL 将其改为 `:close-btn`
7. WHEN 弹窗使用 `:maskCloseAble` THEN THE System SHALL 将其改为 `:mask-close-able`

### 需求 6: 图标名称映射

**用户故事**: 作为开发者，我希望所有图标名称正确映射到图鸟 UI 的图标系统，以确保图标正常显示。

#### 验收标准

1. WHEN 图标使用 `name="arrow-right"` THEN THE System SHALL 将其改为 `name="right"`
2. WHEN 图标使用 `name="arrow-left"` THEN THE System SHALL 将其改为 `name="left"`
3. WHEN 图标使用 `name="arrow-up"` THEN THE System SHALL 将其改为 `name="up"`
4. WHEN 图标使用 `name="arrow-down"` THEN THE System SHALL 将其改为 `name="down"`
5. WHEN 图标使用 `name="arrow-down-fill"` THEN THE System SHALL 将其改为 `name="down-fill"`

### 需求 7: 页面文件全面迁移

**用户故事**: 作为开发者，我希望所有页面文件完成组件迁移，以确保应用功能完整。

#### 验收标准

1. WHEN 迁移登录相关页面 THEN THE System SHALL 完成 `login.vue`、`register.vue`、`forget_pwd.vue` 的迁移
2. WHEN 迁移用户相关页面 THEN THE System SHALL 完成 `user_set.vue`、`user_data.vue`、`bind_mobile.vue`、`change_password.vue` 的迁移
3. WHEN 迁移订单相关页面 THEN THE System SHALL 完成 `order.vue`、`order_detail.vue`、`order_change/**/*.vue` 的迁移
4. WHEN 迁移资讯相关页面 THEN THE System SHALL 完成 `news.vue`、`news_detail.vue` 的迁移
5. WHEN 迁移其他功能页面 THEN THE System SHALL 完成 `search.vue`、`collection.vue`、`customer_service.vue`、`payment_result.vue` 的迁移
6. WHEN 迁移动态相关页面 THEN THE System SHALL 完成 `dynamic/**/*.vue` 的迁移
7. WHEN 迁移评价相关页面 THEN THE System SHALL 完成 `review/**/*.vue` 的迁移
8. WHEN 迁移优惠券相关页面 THEN THE System SHALL 完成 `coupon/**/*.vue` 的迁移
9. WHEN 迁移通知相关页面 THEN THE System SHALL 完成 `notification/**/*.vue` 的迁移
10. WHEN 迁移售后相关页面 THEN THE System SHALL 完成 `aftersale/**/*.vue` 的迁移
11. WHEN 迁移包页面 THEN THE System SHALL 完成 `packages/pages/**/*.vue` 的迁移

### 需求 8: 组件文件全面迁移

**用户故事**: 作为开发者，我希望所有自定义组件完成迁移，以确保组件库的一致性。

#### 验收标准

1. WHEN 迁移公共组件 THEN THE System SHALL 完成 `src/components/**/*.vue` 所有组件的迁移
2. WHEN 组件内部使用 uView UI 组件 THEN THE System SHALL 将其替换为对应的图鸟 UI 组件
3. WHEN 组件使用 uView UI 样式类 THEN THE System SHALL 将其替换为图鸟 UI 或 Tailwind CSS 样式类

### 需求 9: 配置文件更新

**用户故事**: 作为开发者，我希望项目配置正确引入图鸟 UI，以确保组件能够正常使用。

#### 验收标准

1. WHEN 配置 easycom THEN THE System SHALL 在 `pages.json` 中添加图鸟 UI 组件的自动引入规则
2. WHEN 引入样式 THEN THE System SHALL 在 `App.vue` 中引入图鸟 UI 的样式文件和图标
3. WHEN 配置类型声明 THEN THE System SHALL 在 `tsconfig.json` 中添加图鸟 UI 的类型声明路径
4. WHEN 更新依赖 THEN THE System SHALL 在 `package.json` 中添加图鸟 UI 依赖并移除 uView UI 依赖

### 需求 10: 特殊组件处理

**用户故事**: 作为开发者，我希望正确处理图鸟 UI 中没有对应组件的情况，以保持功能完整性。

#### 验收标准

1. WHEN 处理验证码倒计时 THEN THE System SHALL 使用自定义 TypeScript 逻辑实现倒计时功能
2. WHEN 处理表单项 THEN THE System SHALL 使用 Tailwind CSS 边框样式替代 `<u-form-item>`
3. WHEN 处理富文本解析 THEN THE System SHALL 评估是否保留 `<u-parse>` 或使用原生 `<rich-text>`
4. WHEN 处理返回顶部 THEN THE System SHALL 保留 `<u-back-top>` 组件或实现自定义返回顶部功能

### 需求 11: 跨平台兼容性验证

**用户故事**: 作为开发者，我希望迁移后的应用在所有平台上正常运行，以确保用户体验一致。

#### 验收标准

1. WHEN 在 H5 平台测试 THEN THE System SHALL 确保所有页面和组件正常显示和交互
2. WHEN 在微信小程序平台测试 THEN THE System SHALL 确保所有页面和组件正常显示和交互
3. WHEN 在 App 平台测试 THEN THE System SHALL 确保所有页面和组件正常显示和交互
4. WHEN 测试表单功能 THEN THE System SHALL 确保输入、选择、提交等功能正常
5. WHEN 测试弹窗交互 THEN THE System SHALL 确保弹窗打开、关闭、遮罩点击等功能正常

### 需求 12: 样式和主题一致性

**用户故事**: 作为开发者，我希望迁移后的应用保持视觉风格的一致性，以提供良好的用户体验。

#### 验收标准

1. WHEN 应用主题色 THEN THE System SHALL 确保图鸟 UI 组件使用项目定义的主题色
2. WHEN 检查间距和布局 THEN THE System SHALL 确保页面布局与原设计保持一致
3. WHEN 检查字体和图标 THEN THE System SHALL 确保字体大小、图标尺寸符合设计规范
4. WHEN 检查响应式布局 THEN THE System SHALL 确保在不同屏幕尺寸下布局正常

### 需求 13: 性能优化

**用户故事**: 作为开发者，我希望迁移后的应用性能不低于原应用，以确保用户体验流畅。

#### 验收标准

1. WHEN 测量页面加载时间 THEN THE System SHALL 确保加载时间不超过原应用的 110%
2. WHEN 测量组件渲染性能 THEN THE System SHALL 确保渲染性能不低于原应用
3. WHEN 检查包体积 THEN THE System SHALL 确保应用包体积不显著增加（增幅不超过 20%）

### 需求 14: 文档和注释更新

**用户故事**: 作为开发者，我希望代码中的注释和文档反映新的组件库，以便后续维护。

#### 验收标准

1. WHEN 更新代码注释 THEN THE System SHALL 将提及 uView UI 的注释更新为图鸟 UI
2. WHEN 更新迁移文档 THEN THE System SHALL 在 `doc/UI_MIGRATION_PROGRESS.md` 中记录迁移进度
3. WHEN 完成迁移 THEN THE System SHALL 更新 README 文档说明使用的 UI 库

### 需求 15: 依赖清理

**用户故事**: 作为开发者，我希望完全移除 uView UI 依赖，以减少项目体积和维护成本。

#### 验收标准

1. WHEN 所有组件迁移完成 THEN THE System SHALL 从 `package.json` 中移除 `vk-uview-ui` 依赖
2. WHEN 移除依赖后 THEN THE System SHALL 删除 `uni_modules/vk-uview-ui` 目录
3. WHEN 清理配置 THEN THE System SHALL 从 `pages.json` 中移除 uView UI 的 easycom 配置
4. WHEN 清理样式引入 THEN THE System SHALL 从 `App.vue` 中移除 uView UI 的样式引入
