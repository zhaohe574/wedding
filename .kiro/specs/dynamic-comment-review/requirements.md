# 需求文档 - 动态评论审核功能

## 简介

为动态评论系统添加审核功能，允许管理员在后台配置是否需要审核用户评论，并提供审核管理界面来审核或拒绝待审核的评论。用户发表的评论在审核通过前不会公开显示。

## 术语表

- **Comment（评论）**: 用户对动态发表的评论内容
- **Review（审核）**: 管理员对评论内容进行审查并决定是否通过的过程
- **Admin（管理员）**: 后台管理系统的操作人员，负责审核评论
- **User（用户）**: 移动端用户，可以发表评论
- **Review_Status（审核状态）**: 评论的审核状态，包括待审核、已通过、已拒绝
- **Review_Config（审核配置）**: 系统配置项，控制是否启用评论审核功能
- **Pending_Comment（待审核评论）**: 用户发表但尚未通过审核的评论
- **Approved_Comment（已通过评论）**: 已通过审核并公开显示的评论
- **Rejected_Comment（已拒绝评论）**: 被管理员拒绝的评论

## 需求

### 需求 1：数据库字段扩展

**用户故事：** 作为系统架构师，我需要在评论表中添加审核相关字段，以便存储评论的审核状态和审核信息。

#### 验收标准

1. WHEN 系统初始化时，THE Database SHALL 在 `dynamic_comment` 表中添加 `review_status` 字段
2. THE `review_status` 字段 SHALL 为 TINYINT 类型，默认值为 0（待审核）
3. THE `review_status` 字段 SHALL 支持三个值：0（待审核）、1（已通过）、2（已拒绝）
4. WHEN 系统初始化时，THE Database SHALL 在 `dynamic_comment` 表中添加 `review_admin_id` 字段
5. THE `review_admin_id` 字段 SHALL 为 INT 类型，默认值为 0，存储审核管理员 ID
6. WHEN 系统初始化时，THE Database SHALL 在 `dynamic_comment` 表中添加 `review_time` 字段
7. THE `review_time` 字段 SHALL 为 INT 类型，默认值为 0，存储审核时间戳
8. WHEN 系统初始化时，THE Database SHALL 在 `dynamic_comment` 表中添加 `review_remark` 字段
9. THE `review_remark` 字段 SHALL 为 VARCHAR(255) 类型，默认值为空字符串，存储审核备注
10. WHEN 执行数据库迁移时，THE System SHALL 将所有现有评论的 `review_status` 字段设置为 1（已通过）

### 需求 2：系统配置管理

**用户故事：** 作为管理员，我需要在后台配置页面中设置是否启用评论审核功能，以便根据运营需求灵活控制评论发布流程。

#### 验收标准

1. WHEN 管理员访问动态配置页面时，THE Admin_Panel SHALL 显示"评论审核"配置项
2. THE "评论审核"配置项 SHALL 包含一个开关控件
3. THE 开关控件 SHALL 默认为关闭状态（不需要审核）
4. WHEN 管理员切换开关状态时，THE System SHALL 保存配置到数据库
5. THE 配置 SHALL 存储在 `config` 表中，分组为 `dynamic`，键名为 `comment_review_enabled`
6. THE 配置值 SHALL 为 0（关闭审核）或 1（开启审核）

### 需求 3：评论发表流程控制

**用户故事：** 作为用户，当我发表评论时，系统应该根据审核配置决定评论是否需要审核，以便符合平台管理规则。

#### 验收标准

1. WHEN 用户发表评论且审核功能关闭时，THE System SHALL 将评论的 `review_status` 设置为 1（已通过）
2. WHEN 用户发表评论且审核功能开启时，THE System SHALL 将评论的 `review_status` 设置为 0（待审核）
3. WHEN 评论状态为待审核时，THE System SHALL 向用户显示提示："您的评论正在审核中"
4. WHEN 评论状态为待审核时，THE System SHALL 不在评论列表中显示该评论
5. WHEN 评论状态为已通过时，THE System SHALL 在评论列表中正常显示该评论

### 需求 4：后台审核列表页面

**用户故事：** 作为管理员，我需要一个专门的审核列表页面来查看所有待审核的评论，以便高效地进行审核工作。

#### 验收标准

1. WHEN 管理员访问评论审核列表页面时，THE Admin_Panel SHALL 显示所有待审核评论
2. THE 审核列表 SHALL 显示评论内容、发表用户、所属动态、发表时间
3. THE 审核列表 SHALL 支持按发表时间排序（最新的在前）
4. THE 审核列表 SHALL 支持分页显示
5. THE 审核列表 SHALL 为每条评论提供"通过"和"拒绝"操作按钮
6. WHEN 列表中没有待审核评论时，THE Admin_Panel SHALL 显示空状态提示

### 需求 5：评论审核操作

**用户故事：** 作为管理员，我需要能够审核通过或拒绝评论，以便控制评论内容质量。

#### 验收标准

1. WHEN 管理员点击"通过"按钮时，THE System SHALL 将评论的 `review_status` 更新为 1（已通过）
2. WHEN 管理员点击"拒绝"按钮时，THE System SHALL 弹出备注输入框
3. WHEN 管理员输入拒绝原因并确认时，THE System SHALL 将评论的 `review_status` 更新为 2（已拒绝）
4. WHEN 审核操作完成时，THE System SHALL 记录 `review_admin_id`（审核管理员 ID）
5. WHEN 审核操作完成时，THE System SHALL 记录 `review_time`（审核时间戳）
6. WHEN 评论被拒绝时，THE System SHALL 保存 `review_remark`（拒绝原因）
7. WHEN 审核操作成功时，THE System SHALL 显示成功提示并刷新列表

### 需求 6：评论审核历史记录

**用户故事：** 作为管理员，我需要查看所有评论的审核历史，包括已通过和已拒绝的评论，以便进行审核质量管理。

#### 验收标准

1. WHEN 管理员访问评论管理页面时，THE Admin_Panel SHALL 提供审核状态筛选功能
2. THE 筛选功能 SHALL 支持选择：全部、待审核、已通过、已拒绝
3. WHEN 查看已审核评论时，THE List SHALL 显示审核管理员、审核时间、审核备注
4. THE 已拒绝评论 SHALL 以醒目的视觉标识显示（如红色标签）
5. THE 已通过评论 SHALL 以绿色标签显示

### 需求 7：后端 API 扩展

**用户故事：** 作为后端开发者，我需要提供评论审核相关的 API 接口，以便前端能够实现审核功能。

#### 验收标准

1. THE System SHALL 提供 `/adminapi/dynamic/comment/reviewList` API 获取待审核评论列表
2. THE System SHALL 提供 `/adminapi/dynamic/comment/approve` API 审核通过评论
3. THE System SHALL 提供 `/adminapi/dynamic/comment/reject` API 拒绝评论
4. WHEN 调用审核通过 API 时，THE API SHALL 接收评论 ID 参数
5. WHEN 调用拒绝 API 时，THE API SHALL 接收评论 ID 和拒绝原因参数
6. THE 审核 API SHALL 验证管理员权限
7. THE 审核 API SHALL 返回标准的 JSON 响应格式

### 需求 8：评论列表 API 过滤

**用户故事：** 作为前端开发者，我需要评论列表 API 只返回已通过审核的评论，以便用户只能看到审核通过的内容。

#### 验收标准

1. WHEN 调用移动端评论列表 API 时，THE API SHALL 只返回 `review_status = 1` 的评论
2. WHEN 调用后台评论列表 API 时，THE API SHALL 支持按 `review_status` 筛选
3. THE 评论统计数量 SHALL 只计算已通过审核的评论
4. WHEN 动态详情 API 返回评论数量时，THE API SHALL 只计算 `review_status = 1` 的评论

### 需求 9：用户评论状态查询

**用户故事：** 作为用户，我需要能够查看我发表的评论的审核状态，以便了解评论是否已通过审核。

#### 验收标准

1. WHEN 用户查看"我的评论"列表时，THE Mobile_App SHALL 显示每条评论的审核状态
2. THE 待审核评论 SHALL 显示"审核中"标签
3. THE 已通过评论 SHALL 显示"已发布"标签
4. THE 已拒绝评论 SHALL 显示"未通过"标签和拒绝原因
5. WHEN 用户点击已拒绝评论时，THE Mobile_App SHALL 显示拒绝原因详情

### 需求 10：评论数量统计更新

**用户故事：** 作为系统管理员，我需要确保评论数量统计只计算已通过审核的评论，以便保持数据准确性。

#### 验收标准

1. WHEN 评论被审核通过时，THE System SHALL 增加动态的 `comment_count` 计数
2. WHEN 评论被拒绝时，THE System SHALL 不增加动态的 `comment_count` 计数
3. WHEN 用户发表评论且需要审核时，THE System SHALL 不立即增加 `comment_count`
4. WHEN 用户发表评论且不需要审核时，THE System SHALL 立即增加 `comment_count`
5. THE `comment_count` 字段 SHALL 始终反映已通过审核的评论数量

### 需求 11：通知机制

**用户故事：** 作为用户，当我的评论审核结果出来时，我希望收到通知，以便及时了解审核结果。

#### 验收标准

1. WHEN 评论被审核通过时，THE System SHALL 向用户发送站内通知
2. THE 通过通知 SHALL 包含消息："您的评论已通过审核"
3. WHEN 评论被拒绝时，THE System SHALL 向用户发送站内通知
4. THE 拒绝通知 SHALL 包含消息："您的评论未通过审核"和拒绝原因
5. THE 通知 SHALL 包含评论内容摘要和所属动态链接

### 需求 12：批量审核操作

**用户故事：** 作为管理员，我需要能够批量审核多条评论，以便提高审核效率。

#### 验收标准

1. WHEN 管理员在审核列表中选择多条评论时，THE Admin_Panel SHALL 显示批量操作按钮
2. THE 批量操作 SHALL 支持"批量通过"功能
3. THE 批量操作 SHALL 支持"批量拒绝"功能
4. WHEN 执行批量通过时，THE System SHALL 将所有选中评论的状态更新为已通过
5. WHEN 执行批量拒绝时，THE System SHALL 弹出备注输入框，应用相同的拒绝原因到所有选中评论
6. WHEN 批量操作完成时，THE System SHALL 显示操作结果统计（成功数量、失败数量）

### 需求 13：数据模型更新

**用户故事：** 作为开发者，我需要更新评论模型类，以便支持审核相关字段的读写操作。

#### 验收标准

1. THE DynamicComment 模型 SHALL 包含 `review_status` 字段定义
2. THE DynamicComment 模型 SHALL 包含 `review_admin_id` 字段定义
3. THE DynamicComment 模型 SHALL 包含 `review_time` 字段定义
4. THE DynamicComment 模型 SHALL 包含 `review_remark` 字段定义
5. WHEN 创建评论实例时，THE Model SHALL 根据系统配置自动设置 `review_status` 默认值
6. THE Model SHALL 提供审核状态的获取器方法，返回状态描述文本
7. THE Model SHALL 提供审核操作的业务方法（approve、reject）

### 需求 14：权限控制

**用户故事：** 作为系统管理员，我需要确保只有具有审核权限的管理员才能执行审核操作，以便保证审核流程的安全性。

#### 验收标准

1. WHEN 管理员访问评论审核页面时，THE System SHALL 验证管理员是否具有审核权限
2. WHEN 管理员调用审核 API 时，THE System SHALL 验证管理员是否具有审核权限
3. WHEN 管理员没有审核权限时，THE System SHALL 返回权限错误提示
4. THE 审核权限 SHALL 在权限管理系统中配置
5. THE 超级管理员 SHALL 默认具有审核权限

