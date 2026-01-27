# 需求文档 - 动态评论开关功能

## 简介

为动态系统添加评论开关功能，允许管理员在后台发布动态时选择是否允许用户评论该动态。默认情况下，所有动态允许评论。

## 术语表

- **Dynamic（动态）**: 系统中的动态内容实体，包括图文、视频、案例、活动等类型
- **Comment（评论）**: 用户对动态发表的评论内容
- **Admin（管理员）**: 后台管理系统的操作人员
- **User（用户）**: 移动端用户，可以浏览动态和发表评论
- **Comment_Toggle（评论开关）**: 控制动态是否允许评论的布尔字段

## 需求

### 需求 1：数据库字段扩展

**用户故事：** 作为系统架构师，我需要在动态表中添加评论开关字段，以便存储每条动态的评论权限设置。

#### 验收标准

1. WHEN 系统初始化时，THE Database SHALL 在 `dynamic` 表中添加 `allow_comment` 字段
2. THE `allow_comment` 字段 SHALL 为 TINYINT 类型，默认值为 1（允许评论）
3. THE `allow_comment` 字段 SHALL 支持两个值：1（允许评论）和 0（禁止评论）
4. WHEN 执行数据库迁移时，THE System SHALL 将所有现有动态的 `allow_comment` 字段设置为 1

### 需求 2：后台管理界面

**用户故事：** 作为管理员，我需要在发布或编辑动态时设置评论开关，以便控制用户是否可以对该动态发表评论。

#### 验收标准

1. WHEN 管理员打开动态发布/编辑表单时，THE Admin_Panel SHALL 显示"允许评论"开关控件
2. THE "允许评论"开关 SHALL 默认为开启状态
3. WHEN 管理员切换开关状态时，THE Form SHALL 实时更新 `allow_comment` 字段值
4. WHEN 管理员提交表单时，THE System SHALL 保存 `allow_comment` 字段到数据库
5. WHEN 管理员编辑已有动态时，THE Form SHALL 正确显示该动态当前的评论开关状态

### 需求 3：后台动态列表显示

**用户故事：** 作为管理员，我需要在动态列表中看到评论开关状态，以便快速了解哪些动态允许或禁止评论。

#### 验收标准

1. WHEN 管理员查看动态列表时，THE List SHALL 显示每条动态的评论开关状态
2. THE 评论开关状态 SHALL 以图标或文字形式清晰展示（如"允许"/"禁止"）
3. WHEN 动态禁止评论时，THE List SHALL 使用醒目的视觉标识（如红色图标或灰色文字）

### 需求 4：后端 API 扩展

**用户故事：** 作为后端开发者，我需要在动态相关 API 中支持评论开关字段，以便前端能够正确处理评论权限。

#### 验收标准

1. WHEN 调用动态添加 API 时，THE API SHALL 接收并保存 `allow_comment` 参数
2. WHEN 调用动态编辑 API 时，THE API SHALL 接收并更新 `allow_comment` 参数
3. WHEN 调用动态详情 API 时，THE API SHALL 返回 `allow_comment` 字段
4. WHEN 调用动态列表 API 时，THE API SHALL 在每条动态数据中包含 `allow_comment` 字段
5. THE `allow_comment` 参数 SHALL 为可选参数，默认值为 1

### 需求 5：评论发表权限控制

**用户故事：** 作为用户，当动态禁止评论时，我应该无法发表评论，以便遵守内容管理规则。

#### 验收标准

1. WHEN 用户尝试对禁止评论的动态发表评论时，THE System SHALL 拒绝该操作
2. WHEN 评论被拒绝时，THE System SHALL 返回明确的错误提示："该动态不允许评论"
3. WHEN 动态允许评论时，THE System SHALL 正常处理评论发表请求
4. THE 评论权限检查 SHALL 在评论创建逻辑中优先执行

### 需求 6：移动端界面适配

**用户故事：** 作为用户，当浏览禁止评论的动态时，我应该看到明确的提示，以便了解为什么无法评论。

#### 验收标准

1. WHEN 用户查看禁止评论的动态详情时，THE Mobile_App SHALL 隐藏或禁用评论输入框
2. WHEN 评论功能被禁用时，THE Mobile_App SHALL 显示提示文字："该动态已关闭评论"
3. WHEN 用户点击禁用的评论区域时，THE Mobile_App SHALL 显示 Toast 提示："该动态不允许评论"
4. WHEN 动态允许评论时，THE Mobile_App SHALL 正常显示评论输入框和评论列表

### 需求 7：评论统计数据一致性

**用户故事：** 作为系统管理员，我需要确保评论开关不影响已有评论的统计数据，以便保持数据完整性。

#### 验收标准

1. WHEN 动态的评论开关从允许改为禁止时，THE System SHALL 保留该动态的现有评论
2. THE 现有评论 SHALL 继续在移动端显示
3. THE `comment_count` 字段 SHALL 保持不变
4. WHEN 动态禁止评论时，THE System SHALL 仅阻止新评论的发表，不影响已有评论的查看

### 需求 8：数据模型更新

**用户故事：** 作为开发者，我需要更新动态模型类，以便支持评论开关字段的读写操作。

#### 验收标准

1. THE Dynamic 模型 SHALL 包含 `allow_comment` 字段定义
2. WHEN 创建动态实例时，THE Model SHALL 自动设置 `allow_comment` 默认值为 1
3. THE Model SHALL 提供 `allow_comment` 字段的获取器和设置器方法
4. THE Model SHALL 在序列化时包含 `allow_comment` 字段
