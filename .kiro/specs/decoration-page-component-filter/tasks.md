# 实施计划: 装修页面组件按页面类型过滤

## 概述

本实施计划将装修页面组件按页面类型过滤功能分解为可执行的开发任务。实施将分为三个主要阶段：组件配置更新、过滤逻辑实现、测试和优化。所有任务都专注于前端代码修改，无需后端改动。

## 任务

- [x] 1. 更新组件配置，添加页面范围属性
  - 为所有现有组件的options.ts文件添加pageScope属性
  - 首页专属组件配置pageScope: ['home']
  - 个人中心专属组件配置pageScope: ['user']
  - 客服设置专属组件配置pageScope: ['service']
  - _需求: 1.1, 1.2, 1.3, 3.1, 3.2, 3.3_

- [x] 1.1 更新首页专属组件配置
  - 更新 `search/options.ts` 添加 `pageScope: ['home']`
  - 更新 `banner/options.ts` 添加 `pageScope: ['home']`
  - 更新 `nav/options.ts` 添加 `pageScope: ['home']`
  - 更新 `middle-banner/options.ts` 添加 `pageScope: ['home']`
  - 更新 `staff-showcase/options.ts` 添加 `pageScope: ['home']`
  - 更新 `service-packages/options.ts` 添加 `pageScope: ['home']`
  - 更新 `portfolio-gallery/options.ts` 添加 `pageScope: ['home']`
  - 更新 `customer-reviews/options.ts` 添加 `pageScope: ['home']`
  - 更新 `activity-zone/options.ts` 添加 `pageScope: ['home']`
  - 更新 `order-quick-entry/options.ts` 添加 `pageScope: ['home']`
  - 更新 `news/options.ts` 添加 `pageScope: ['home']`
  - _需求: 3.1_

- [x] 1.2 更新个人中心专属组件配置
  - 更新 `user-info/options.ts` 添加 `pageScope: ['user']`
  - 更新 `my-service/options.ts` 添加 `pageScope: ['user']`
  - 更新 `user-banner/options.ts` 添加 `pageScope: ['user']`
  - _需求: 3.2_

- [x] 1.3 更新客服设置专属组件配置
  - 更新 `customer-service/options.ts` 添加 `pageScope: ['service']`
  - _需求: 3.3_

- [x] 2. 实现组件选择器过滤逻辑
  - 在preview.vue中添加页面类型映射
  - 实现currentPageType计算属性
  - 实现filteredAvailableWidgets计算属性
  - 更新组件选择器渲染逻辑使用过滤后的组件列表
  - 添加空组件列表提示
  - _需求: 1.4, 2.1, 2.2, 2.3, 2.4, 5.1, 5.3_

- [x] 2.1 添加页面类型映射和计算属性
  - 在preview.vue中定义PageType类型
  - 创建pageTypeMap映射对象
  - 实现currentPageType计算属性
  - 从父组件接收activeMenu prop
  - _需求: 1.4_

- [x] 2.2 实现组件过滤逻辑
  - 实现filteredAvailableWidgets计算属性
  - 添加pageScope验证逻辑
  - 处理未定义pageScope的情况（通用组件）
  - 处理pageScope包含当前页面类型的情况
  - _需求: 2.1, 2.2, 2.3, 2.4_

- [x] 2.3 更新组件选择器UI
  - 修改el-dialog使用filteredAvailableWidgets
  - 添加空组件列表提示（v-if判断）
  - 保持现有的网格布局和交互方式
  - _需求: 5.1, 5.3, 5.4_

- [x] 3. 更新父组件传递activeMenu
  - 在pages/index.vue中向preview组件传递activeMenu prop
  - 确保activeMenu值正确传递
  - _需求: 2.1, 2.2, 2.3_

- [x] 4. 添加错误处理和边界情况处理
  - 添加pageScope验证函数
  - 处理无效的pageScope配置
  - 处理组件配置缺失的情况
  - 处理页面类型映射失败的情况
  - 添加开发环境警告日志
  - _需求: 4.1, 4.2, 4.3_

- [x] 5. 检查点 - 功能验证
  - 手动测试首页组件选择器，验证只显示首页组件
  - 手动测试个人中心组件选择器，验证只显示个人中心组件
  - 手动测试客服设置组件选择器，验证只显示客服设置组件
  - 验证已添加的组件仍然正常显示和编辑
  - 验证空组件列表提示正常显示
  - 如有问题请向用户反馈

- [ ]* 6. 编写单元测试
  - 测试所有组件的pageScope配置正确性
  - 测试首页过滤逻辑
  - 测试个人中心过滤逻辑
  - 测试客服设置过滤逻辑
  - 测试多页面组件显示
  - 测试边界条件（空数组、无效值、非数组）
  - _需求: 3.1, 3.2, 3.3, 2.1, 2.2, 2.3, 2.4_

- [ ]* 6.1 编写组件配置测试
  - 测试首页专属组件的pageScope为['home']
  - 测试个人中心专属组件的pageScope为['user']
  - 测试客服设置专属组件的pageScope为['service']
  - 测试page-meta组件的pageScope未定义（通用组件）
  - _需求: 3.1, 3.2, 3.3_

- [ ]* 6.2 编写过滤逻辑测试
  - 测试首页过滤：只显示home组件
  - 测试个人中心过滤：只显示user组件
  - 测试客服设置过滤：只显示service组件
  - 测试通用组件在所有页面显示
  - 测试多页面组件在指定页面显示
  - _需求: 2.1, 2.2, 2.3, 2.4_

- [ ]* 6.3 编写边界条件测试
  - 测试pageScope为空数组的处理
  - 测试pageScope包含无效值的处理
  - 测试pageScope不是数组的处理
  - 测试未知页面类型的fallback
  - 测试组件配置缺失的处理
  - _需求: 4.1, 4.2_

- [ ]* 7. 编写属性测试
  - **属性 1: 组件过滤正确性**
  - **验证: 需求 1.2, 1.3, 2.1, 2.2, 2.3, 2.4**

- [ ]* 7.1 编写属性测试：组件过滤正确性
  - 使用fast-check生成随机页面类型和组件列表
  - 验证过滤后的组件都满足条件：pageScope包含当前页面类型或pageScope未定义
  - 配置至少100次迭代
  - 添加测试标签：**Feature: decoration-page-component-filter, Property 1: 组件过滤正确性**
  - _需求: 1.2, 1.3, 2.1, 2.2, 2.3, 2.4_

- [ ]* 7.2 编写属性测试：通用组件可见性
  - 使用fast-check生成未定义pageScope的组件和随机页面类型
  - 验证通用组件在所有页面类型中都可见
  - 配置至少100次迭代
  - 添加测试标签：**Feature: decoration-page-component-filter, Property 2: 通用组件可见性**
  - _需求: 1.2_

- [ ]* 7.3 编写属性测试：页面类型支持
  - 验证系统能够正确处理'home', 'user', 'service'三种页面类型
  - 验证页面类型映射的正确性
  - 配置至少100次迭代
  - 添加测试标签：**Feature: decoration-page-component-filter, Property 3: 页面类型支持**
  - _需求: 1.4_

- [ ] 8. 检查点 - 测试验证
  - 运行所有单元测试，确保通过
  - 运行所有属性测试，确保通过
  - 检查测试覆盖率
  - 如有失败测试，修复代码或测试
  - 如有问题请向用户反馈

- [x] 9. 更新进展文档
  - 在doc目录创建或更新功能实现文档
  - 记录实现的功能点
  - 记录测试结果
  - 记录已知问题（如有）
  - _需求: 所有_

- [x] 10. 最终检查点
  - 验证所有需求都已实现
  - 验证向后兼容性（现有配置正常工作）
  - 验证用户界面友好性
  - 验证代码质量和注释完整性
  - 准备交付

## 注意事项

- 任务标记 `*` 的为可选任务，可以跳过以加快MVP开发
- 每个任务都引用了相关的需求编号，便于追溯
- 属性测试任务明确标注了要验证的属性和需求
- 检查点任务确保增量验证，及时发现问题
- 所有代码修改都在前端，无需后端改动
- 保持代码风格与现有代码一致
- 添加必要的中文注释
