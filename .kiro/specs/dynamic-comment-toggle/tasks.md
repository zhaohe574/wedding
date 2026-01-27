# 实施计划 - 动态评论开关功能

## 概述

本实施计划将动态评论开关功能的开发分解为一系列可执行的编码任务。每个任务都是独立的、可测试的步骤，按照从后端到前端的顺序逐步实现。

## 任务列表

### 1. 数据库迁移

- [ ] 1.1 创建数据库迁移文件
  - 在 `server/database/migrations/` 目录创建迁移文件
  - 添加 `allow_comment` 字段到 `ls_dynamic` 表
  - 设置字段类型为 TINYINT(1)，默认值为 1
  - 为已存在的记录设置默认值为 1
  - _需求: 1.1, 1.2, 1.3, 1.4_

- [ ] 1.2 执行数据库迁移
  - 运行迁移命令
  - 验证字段已成功添加
  - 检查已有数据的默认值
  - _需求: 1.1, 1.4_

### 2. 后端模型层更新

- [ ] 2.1 更新 Dynamic 模型
  - 在 `server/app/common/model/dynamic/Dynamic.php` 中添加 `allow_comment` 字段处理
  - 更新 `publish()` 方法，添加 `allow_comment` 参数处理（默认值 1）
  - 添加 `getAllowCommentDescAttr()` 获取器方法
  - _需求: 8.1, 8.2, 8.3_

- [ ] 2.2 更新 DynamicComment 模型
  - 在 `server/app/common/model/dynamic/DynamicComment.php` 中更新 `addComment()` 方法
  - 添加评论开关检查逻辑：`if ($dynamic->allow_comment != 1) return [false, '该动态不允许评论', null]`
  - 确保检查在状态检查之后执行
  - _需求: 5.1, 5.2, 5.3, 5.4_

### 3. 后端业务逻辑层更新

- [ ] 3.1 更新 DynamicLogic 的 add() 方法
  - 在 `server/app/adminapi/logic/dynamic/DynamicLogic.php` 中更新 `add()` 方法
  - 添加 `allow_comment` 参数处理：`'allow_comment' => $params['allow_comment'] ?? 1`
  - _需求: 4.1, 4.5_

- [ ] 3.2 更新 DynamicLogic 的 edit() 方法
  - 在 `server/app/adminapi/logic/dynamic/DynamicLogic.php` 中更新 `edit()` 方法
  - 添加 `allow_comment` 参数更新逻辑
  - _需求: 4.2, 4.5_

- [ ] 3.3 更新 DynamicLogic 的 detail() 方法
  - 确保 `detail()` 方法返回的数据包含 `allow_comment` 字段
  - _需求: 4.3_

### 4. 后端验证器更新

- [ ] 4.1 更新 DynamicValidate 验证规则
  - 在 `server/app/adminapi/validate/dynamic/DynamicValidate.php` 中添加验证规则
  - 添加规则：`'allow_comment' => 'integer|in:0,1'`
  - 添加错误消息：`'allow_comment.integer' => '评论开关格式错误'`
  - 添加错误消息：`'allow_comment.in' => '评论开关参数错误'`
  - _需求: 4.1, 4.2_

### 5. 后台管理界面 - 表单更新

- [ ] 5.1 更新动态编辑表单组件
  - 在 `admin/src/views/dynamic/lists/edit.vue` 中添加评论开关表单项
  - 使用 `<el-switch>` 组件，`:active-value="1"` `:inactive-value="0"`
  - 在 `formData` 中添加 `allow_comment: 1` 字段
  - 添加表单提示文字："关闭后用户将无法对该动态发表新评论"
  - _需求: 2.1, 2.2, 2.3, 2.4, 2.5_

### 6. 后台管理界面 - 列表显示

- [ ] 6.1 更新动态列表页面
  - 在 `admin/src/views/dynamic/lists/index.vue` 中添加评论状态列
  - 使用 `<el-tag>` 显示评论状态
  - 允许评论显示绿色标签，禁止评论显示红色标签
  - _需求: 3.1, 3.2, 3.3_

### 7. 移动端界面更新

- [ ] 7.1 查找并更新动态详情页面
  - 定位 `uniapp/src/pages/dynamic_detail/dynamic_detail.vue` 文件
  - 如果文件不存在，查找类似的动态详情页面文件
  - _需求: 6.1, 6.2, 6.3_

- [ ] 7.2 更新评论输入区域
  - 在动态详情页面中添加 `v-if="detail.allow_comment === 1"` 条件到评论输入框
  - 添加评论关闭提示区域：`v-else` 显示"该动态已关闭评论"
  - 使用 TuniaoUI 的 `<tn-icon>` 组件显示提示图标
  - _需求: 6.1, 6.2_

- [ ] 7.3 更新评论交互逻辑
  - 在 `handleCommentFocus()` 方法中添加评论开关检查
  - 在 `handleSendComment()` 方法中添加评论开关检查
  - 当评论被禁止时显示 Toast 提示："该动态不允许评论"
  - _需求: 6.3_

### 8. 样式优化

- [ ] 8.1 添加移动端评论禁用提示样式
  - 在动态详情页面的 `<style>` 中添加 `.comment-disabled-tip` 样式
  - 设置居中对齐、灰色背景、圆角等样式
  - _需求: 6.2_

### 9. API 接口测试

- [ ] 9.1 测试后台动态添加接口
  - 使用 Postman 或类似工具测试 `/adminapi/dynamic/add` 接口
  - 验证 `allow_comment` 参数可以正确传递和保存
  - 测试默认值（不传参数时应为 1）
  - _需求: 4.1, 4.5_

- [ ] 9.2 测试后台动态编辑接口
  - 测试 `/adminapi/dynamic/edit` 接口
  - 验证 `allow_comment` 参数可以正确更新
  - _需求: 4.2, 4.5_

- [ ] 9.3 测试动态详情接口
  - 测试 `/adminapi/dynamic/detail` 和 `/api/dynamic/detail` 接口
  - 验证返回数据包含 `allow_comment` 字段
  - _需求: 4.3_

- [ ] 9.4 测试评论发表接口
  - 测试 `/api/dynamic/commentAdd` 接口
  - 创建一个 `allow_comment=0` 的动态
  - 尝试发表评论，验证返回错误："该动态不允许评论"
  - _需求: 5.1, 5.2_

### 10. 功能集成测试

- [ ] 10.1 端到端测试 - 后台发布流程
  - 在后台管理界面发布新动态
  - 设置评论开关为"禁止"
  - 保存并验证数据库中 `allow_comment=0`
  - _需求: 2.1, 2.2, 2.3, 2.4_

- [ ] 10.2 端到端测试 - 移动端评论限制
  - 在移动端打开禁止评论的动态
  - 验证评论输入框被隐藏
  - 验证显示"该动态已关闭评论"提示
  - 尝试点击评论区域，验证 Toast 提示
  - _需求: 6.1, 6.2, 6.3_

- [ ] 10.3 端到端测试 - 已有评论保留
  - 创建一个允许评论的动态并添加几条评论
  - 记录 `comment_count` 值
  - 将评论开关改为"禁止"
  - 验证 `comment_count` 值未变化
  - 验证已有评论仍然可见
  - _需求: 7.1, 7.2, 7.3, 7.4_

### 11. 最终检查点

- [ ] 11.1 代码审查和清理
  - 检查所有代码是否符合项目编码规范
  - 移除调试代码和 console.log
  - 确保中文注释清晰准确
  - _需求: 所有_

- [ ] 11.2 文档更新
  - 更新 API 文档，添加 `allow_comment` 字段说明
  - 更新数据库文档，记录新增字段
  - _需求: 所有_

- [ ] 11.3 最终功能验证
  - 确保所有测试通过
  - 在开发环境进行完整的功能演示
  - 向用户确认功能是否符合预期
  - _需求: 所有_

## 注意事项

1. **数据库迁移**: 在执行迁移前务必备份数据库
2. **向后兼容**: 确保已有动态的 `allow_comment` 默认为 1
3. **错误处理**: 所有 API 调用都要有适当的错误处理和用户提示
4. **测试优先**: 每完成一个任务后立即进行测试，不要等到最后
5. **增量开发**: 按照任务顺序逐步实现，每个任务完成后提交代码
6. **中文规范**: 所有用户可见的文字都使用中文

## 技术栈参考

- **后端**: PHP 8.0 + ThinkPHP 8.0
- **后台前端**: Vue 3 + Element Plus + TypeScript
- **移动端**: UniApp + Vue 3 + TuniaoUI
- **数据库**: MySQL
- **测试工具**: Postman (API 测试)
