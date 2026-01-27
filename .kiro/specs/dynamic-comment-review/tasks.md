# 实施计划 - 动态评论审核功能

## 概述

本实施计划将动态评论审核功能的开发分解为一系列可执行的编码任务。每个任务都是独立的、可测试的步骤，按照从后端到前端的顺序逐步实现。该功能允许管理员通过后台配置控制是否启用评论审核，并提供专门的审核管理界面。

## 任务列表

### 1. 数据库迁移

- [ ] 1.1 创建数据库迁移文件
  - 在 `server/sql/wedding/` 目录创建迁移 SQL 文件（如 `019_add_comment_review_fields.sql`）
  - 添加 `review_status` 字段到 `ls_dynamic_comment` 表（TINYINT(1)，默认值 0）
  - 添加 `review_admin_id` 字段到 `ls_dynamic_comment` 表（INT(11)，默认值 0）
  - 添加 `review_time` 字段到 `ls_dynamic_comment` 表（INT(11)，默认值 0）
  - 添加 `review_remark` 字段到 `ls_dynamic_comment` 表（VARCHAR(255)，默认值空字符串）
  - 为已存在的评论记录设置 `review_status = 1`（已通过）
  - _需求: 1.1, 1.2, 1.3, 1.4, 1.5, 1.6, 1.7, 1.8, 1.9, 1.10_

- [ ] 1.2 执行数据库迁移
  - 在开发环境执行迁移 SQL
  - 验证字段已成功添加
  - 检查已有数据的默认值是否正确
  - _需求: 1.1, 1.10_


### 2. 后端模型层更新

- [ ] 2.1 更新 DynamicComment 模型常量
  - 在 `server/app/common/model/dynamic/DynamicComment.php` 中添加审核状态常量
  - 添加 `REVIEW_STATUS_PENDING = 0`（待审核）
  - 添加 `REVIEW_STATUS_APPROVED = 1`（已通过）
  - 添加 `REVIEW_STATUS_REJECTED = 2`（已拒绝）
  - _需求: 13.1_

- [ ] 2.2 修改 DynamicComment 的 addComment() 方法
  - 获取审核配置：`ConfigService::get('dynamic', 'comment_review_enabled', 0)`
  - 根据配置设置 `review_status`：审核开启时为 0，关闭时为 1
  - 只有 `review_status = 1` 时才更新 `comment_count` 和 `reply_count`
  - 根据审核状态返回不同的成功消息
  - _需求: 3.1, 3.2, 10.3, 10.4, 13.5_

- [ ] 2.3 添加 DynamicComment 的 approveComment() 方法
  - 验证评论是否存在
  - 验证评论是否为待审核状态
  - 更新 `review_status` 为 1（已通过）
  - 记录 `review_admin_id` 和 `review_time`
  - 更新动态的 `comment_count` 和父评论的 `reply_count`
  - 返回操作结果
  - _需求: 5.1, 5.4, 5.5, 10.1_

- [ ] 2.4 添加 DynamicComment 的 rejectComment() 方法
  - 验证评论是否存在
  - 验证评论是否为待审核状态
  - 更新 `review_status` 为 2（已拒绝）
  - 记录 `review_admin_id`、`review_time` 和 `review_remark`
  - 不更新评论计数
  - 返回操作结果
  - _需求: 5.3, 5.4, 5.5, 5.6, 10.2_

- [ ] 2.5 添加 DynamicComment 的 getReviewStatusDescAttr() 获取器
  - 返回审核状态的中文描述
  - 0 -> "待审核"，1 -> "已通过"，2 -> "已拒绝"
  - _需求: 13.6_

- [ ] 2.6 修改 DynamicComment 的 getCommentList() 方法
  - 添加过滤条件：`where('review_status', self::REVIEW_STATUS_APPROVED)`
  - 确保只返回已通过审核的评论
  - _需求: 3.5, 8.1_


### 3. 后端业务逻辑层

- [ ] 3.1 创建 DynamicCommentLogic 类
  - 在 `server/app/adminapi/logic/dynamic/` 目录创建 `DynamicCommentLogic.php`
  - 继承 `BaseLogic` 类
  - _需求: 7.1, 7.2, 7.3_

- [ ] 3.2 实现 getReviewList() 方法
  - 查询 `review_status = 0` 的评论
  - 关联用户和动态信息
  - 按创建时间倒序排序
  - 支持分页
  - _需求: 4.1, 4.2, 4.3, 4.4_

- [ ] 3.3 实现 approve() 方法
  - 调用 `DynamicComment::approveComment()`
  - 处理错误并设置错误消息
  - 返回操作结果
  - _需求: 5.1, 5.4, 5.5, 5.7_

- [ ] 3.4 实现 reject() 方法
  - 调用 `DynamicComment::rejectComment()`
  - 处理错误并设置错误消息
  - 返回操作结果
  - _需求: 5.2, 5.3, 5.4, 5.5, 5.6, 5.7_

- [ ] 3.5 实现 batchApprove() 方法
  - 循环调用 `DynamicComment::approveComment()`
  - 统计成功和失败数量
  - 返回统计结果
  - _需求: 12.1, 12.4, 12.6_

- [ ] 3.6 实现 batchReject() 方法
  - 循环调用 `DynamicComment::rejectComment()`
  - 应用相同的拒绝原因到所有评论
  - 统计成功和失败数量
  - 返回统计结果
  - _需求: 12.1, 12.5, 12.6_

- [ ] 3.7 实现 getReviewConfig() 和 setReviewConfig() 方法
  - 使用 `ConfigService::get()` 读取配置
  - 使用 `ConfigService::set()` 保存配置
  - 处理异常并返回结果
  - _需求: 2.1, 2.2, 2.3, 2.4, 2.5, 2.6_


### 4. 后端验证器

- [ ] 4.1 创建 DynamicCommentValidate 验证器
  - 在 `server/app/adminapi/validate/dynamic/` 目录创建 `DynamicCommentValidate.php`
  - 继承 `BaseValidate` 类
  - 定义验证规则：`id`、`ids`、`remark`、`enabled`
  - 定义错误消息
  - _需求: 7.6_

- [ ] 4.2 定义验证场景
  - `sceneApprove()`: 只验证 `id`
  - `sceneReject()`: 验证 `id` 和 `remark`
  - `sceneBatchApprove()`: 验证 `ids`
  - `sceneBatchReject()`: 验证 `ids` 和 `remark`
  - `sceneSetConfig()`: 验证 `enabled`
  - _需求: 7.4, 7.5_

### 5. 后端控制器层

- [ ] 5.1 创建 DynamicCommentController 控制器
  - 在 `server/app/adminapi/controller/dynamic/` 目录创建 `DynamicCommentController.php`
  - 继承 `BaseAdminController` 类
  - _需求: 7.1, 7.2, 7.3_

- [ ] 5.2 实现 reviewList() 方法
  - 获取请求参数
  - 调用 `DynamicCommentLogic::getReviewList()`
  - 返回 JSON 响应
  - _需求: 7.1_

- [ ] 5.3 实现 approve() 方法
  - 验证参数
  - 调用 `DynamicCommentLogic::approve()`
  - 返回成功或失败响应
  - _需求: 7.2_

- [ ] 5.4 实现 reject() 方法
  - 验证参数
  - 调用 `DynamicCommentLogic::reject()`
  - 返回成功或失败响应
  - _需求: 7.3_

- [ ] 5.5 实现 batchApprove() 和 batchReject() 方法
  - 验证参数
  - 调用相应的 Logic 方法
  - 返回操作结果统计
  - _需求: 7.2, 7.3_

- [ ] 5.6 实现 getReviewConfig() 和 setReviewConfig() 方法
  - 调用 `DynamicCommentLogic` 的配置方法
  - 返回配置值或操作结果
  - _需求: 2.1, 2.4_

### 6. 路由配置

- [ ] 6.1 添加评论审核路由
  - 在 `server/app/adminapi/config/route.php` 中添加路由组
  - 配置路由：`reviewList`、`approve`、`reject`、`batchApprove`、`batchReject`
  - 配置路由：`getReviewConfig`、`setReviewConfig`
  - 应用中间件：`InitMiddleware`、`LoginMiddleware`、`AuthMiddleware`
  - _需求: 7.1, 7.2, 7.3, 14.1, 14.2_


### 7. 后台管理界面 - API 接口定义

- [ ] 7.1 添加 API 接口定义
  - 在 `admin/src/api/dynamic.ts` 中添加评论审核相关接口
  - `apiGetReviewConfig()`: 获取审核配置
  - `apiSetReviewConfig()`: 设置审核配置
  - `apiGetReviewList()`: 获取待审核评论列表
  - `apiApproveComment()`: 审核通过
  - `apiRejectComment()`: 拒绝评论
  - `apiBatchApproveComment()`: 批量通过
  - `apiBatchRejectComment()`: 批量拒绝
  - _需求: 7.1, 7.2, 7.3_

### 8. 后台管理界面 - 配置页面

- [ ] 8.1 创建或更新动态配置页面
  - 在 `admin/src/views/dynamic/` 目录创建或更新 `config/index.vue`
  - 添加"评论审核"配置项
  - 使用 `<el-switch>` 组件
  - 实现配置的读取和保存
  - 添加配置说明文字
  - _需求: 2.1, 2.2, 2.3, 2.4_

### 9. 后台管理界面 - 审核列表页面

- [ ] 9.1 创建评论审核列表页面
  - 在 `admin/src/views/dynamic/comment/` 目录创建 `review.vue`
  - 实现评论列表展示（内容、用户、动态、时间）
  - 添加多选功能
  - 实现分页
  - _需求: 4.1, 4.2, 4.3, 4.4_

- [ ] 9.2 实现单个审核操作
  - 添加"通过"按钮，点击调用 `apiApproveComment()`
  - 添加"拒绝"按钮，点击弹出拒绝原因输入框
  - 实现拒绝原因弹窗和提交逻辑
  - 操作成功后刷新列表
  - _需求: 4.5, 5.1, 5.2, 5.3, 5.6, 5.7_

- [ ] 9.3 实现批量审核操作
  - 添加批量操作按钮（批量通过、批量拒绝）
  - 实现批量通过逻辑
  - 实现批量拒绝逻辑（共用拒绝原因）
  - 显示操作结果统计
  - _需求: 12.1, 12.2, 12.3, 12.4, 12.5, 12.6_

### 10. 后台管理界面 - 路由和菜单

- [ ] 10.1 添加路由配置
  - 在 `admin/src/router/` 中添加评论审核页面路由
  - 配置路由路径和组件
  - _需求: 4.1_

- [ ] 10.2 添加菜单项（可选）
  - 在后台菜单配置中添加"评论审核"菜单项
  - 配置菜单图标和权限
  - _需求: 4.1_


### 11. 移动端界面 - 评论发表提示

- [ ] 11.1 更新动态详情页面的评论发表逻辑
  - 定位 `uniapp/src/pages/dynamic_detail/dynamic_detail.vue` 或类似文件
  - 修改 `handleSendComment()` 方法
  - 根据返回消息判断是否需要审核
  - 显示相应的 Toast 提示（"评论成功，等待审核" 或 "评论成功"）
  - _需求: 3.3_

### 12. 移动端界面 - 我的评论页面

- [ ] 12.1 创建我的评论页面
  - 在 `uniapp/src/pages/user/` 目录创建 `my_comments.vue`
  - 实现标签页切换（全部、审核中、已发布、未通过）
  - 实现评论列表展示
  - _需求: 9.1, 9.2, 9.3_

- [ ] 12.2 实现审核状态显示
  - 使用 `<tn-tag>` 组件显示审核状态
  - 待审核显示"审核中"（warning 类型）
  - 已通过显示"已发布"（success 类型）
  - 已拒绝显示"未通过"（danger 类型）
  - _需求: 9.2, 9.3, 9.4_

- [ ] 12.3 实现拒绝原因显示
  - 对于已拒绝的评论，显示拒绝原因
  - 使用醒目的样式（红色背景）
  - _需求: 9.5_

- [ ] 12.4 添加 API 接口
  - 在 `uniapp/src/api/dynamic.ts` 中添加 `apiGetMyComments()` 接口
  - 支持按审核状态筛选
  - _需求: 9.1_

- [ ] 12.5 添加页面路由
  - 在 `uniapp/src/pages.json` 中添加我的评论页面路由
  - 配置页面标题和样式
  - _需求: 9.1_

### 13. 后端 API - 我的评论接口

- [ ] 13.1 创建我的评论 API
  - 在 `server/app/api/controller/` 或相应位置创建接口
  - 实现获取当前用户评论列表的逻辑
  - 支持按 `review_status` 筛选
  - 返回评论内容、审核状态、审核备注等信息
  - _需求: 9.1, 9.2, 9.3, 9.4, 9.5_


### 14. 通知功能（可选）

- [ ]* 14.1 实现审核通过通知
  - 在 `DynamicComment::approveComment()` 中添加通知逻辑
  - 向评论作者发送站内通知："您的评论已通过审核"
  - 包含评论内容摘要和动态链接
  - _需求: 11.1, 11.2, 11.5_

- [ ]* 14.2 实现审核拒绝通知
  - 在 `DynamicComment::rejectComment()` 中添加通知逻辑
  - 向评论作者发送站内通知："您的评论未通过审核"
  - 包含拒绝原因和评论内容摘要
  - _需求: 11.3, 11.4, 11.5_

### 15. 测试

- [ ]* 15.1 编写单元测试 - 模型层
  - 测试 `addComment()` 方法的审核状态设置
  - 测试 `approveComment()` 方法
  - 测试 `rejectComment()` 方法
  - 测试 `getCommentList()` 方法的过滤逻辑
  - _需求: 3.1, 3.2, 5.1, 5.3, 8.1_

- [ ]* 15.2 编写单元测试 - 业务逻辑层
  - 测试 `getReviewList()` 方法
  - 测试 `approve()` 和 `reject()` 方法
  - 测试 `batchApprove()` 和 `batchReject()` 方法
  - 测试配置读取和保存方法
  - _需求: 4.1, 5.1, 5.3, 12.4, 12.5_

- [ ]* 15.3 编写属性测试
  - **属性 1**: 审核状态根据配置自动设置
  - **属性 2**: 待审核评论不在列表中显示
  - **属性 7**: 评论计数只计算已通过评论
  - **属性 9**: 批量审核通过更新所有选中评论
  - _需求: 3.1, 3.2, 3.4, 8.1, 10.1, 10.2, 10.3, 10.4, 12.4_

- [ ]* 15.4 API 接口测试
  - 测试 `/dynamic/comment/reviewList` 接口
  - 测试 `/dynamic/comment/approve` 接口
  - 测试 `/dynamic/comment/reject` 接口
  - 测试 `/dynamic/comment/batchApprove` 接口
  - 测试 `/dynamic/comment/batchReject` 接口
  - 测试配置接口
  - _需求: 7.1, 7.2, 7.3_

### 16. 集成测试和验证

- [ ] 16.1 端到端测试 - 配置功能
  - 在后台管理界面开启评论审核
  - 验证配置已保存
  - 用户发表评论，验证进入待审核状态
  - 关闭评论审核，验证评论自动通过
  - _需求: 2.1, 2.2, 2.3, 2.4, 3.1, 3.2_

- [ ] 16.2 端到端测试 - 审核流程
  - 用户发表评论（审核开启）
  - 管理员在审核列表中看到待审核评论
  - 管理员审核通过评论
  - 验证评论在移动端显示
  - 验证评论计数正确
  - _需求: 3.2, 4.1, 5.1, 8.1, 10.1_

- [ ] 16.3 端到端测试 - 拒绝流程
  - 用户发表评论（审核开启）
  - 管理员拒绝评论并输入原因
  - 验证评论不在移动端显示
  - 用户在"我的评论"中看到未通过状态和拒绝原因
  - _需求: 5.2, 5.3, 5.6, 9.4, 9.5_

- [ ] 16.4 端到端测试 - 批量操作
  - 创建多条待审核评论
  - 管理员选择多条评论
  - 执行批量通过操作
  - 验证所有评论状态已更新
  - 执行批量拒绝操作
  - 验证所有评论应用了相同的拒绝原因
  - _需求: 12.1, 12.4, 12.5, 12.6_

### 17. 最终检查点

- [ ] 17.1 代码审查和清理
  - 检查所有代码是否符合项目编码规范
  - 移除调试代码和 console.log
  - 确保中文注释清晰准确
  - 检查错误处理是否完善
  - _需求: 所有_

- [ ] 17.2 文档更新
  - 更新 API 文档，添加评论审核相关接口说明
  - 更新数据库文档，记录新增字段
  - 创建功能使用说明文档
  - _需求: 所有_

- [ ] 17.3 最终功能验证
  - 确保所有测试通过
  - 在开发环境进行完整的功能演示
  - 验证所有需求都已实现
  - 向用户确认功能是否符合预期
  - _需求: 所有_

## 注意事项

1. **数据库迁移**: 在执行迁移前务必备份数据库
2. **向后兼容**: 确保已有评论的 `review_status` 默认为 1（已通过）
3. **配置缓存**: 配置变更后需要清除缓存
4. **权限控制**: 确保审核接口有适当的权限验证
5. **错误处理**: 所有 API 调用都要有适当的错误处理和用户提示
6. **测试优先**: 每完成一个任务后立即进行测试，不要等到最后
7. **增量开发**: 按照任务顺序逐步实现，每个任务完成后提交代码
8. **中文规范**: 所有用户可见的文字都使用中文
9. **通知功能**: 通知功能标记为可选，可以在后续迭代中实现

## 技术栈参考

- **后端**: PHP 8.0 + ThinkPHP 8.0
- **后台前端**: Vue 3 + Element Plus + TypeScript
- **移动端**: UniApp + Vue 3 + TuniaoUI
- **数据库**: MySQL
- **测试工具**: PHPUnit (后端), Vitest (前端), Postman (API 测试)

